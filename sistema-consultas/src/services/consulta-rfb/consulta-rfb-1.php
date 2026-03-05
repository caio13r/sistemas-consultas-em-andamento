<?php
// Arquivo incluído em consultaRFB.php - não precisa de header/footer

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\lib\ReceitaFederalAPI;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database3;
use PDO;
use PDOException;

// Verificar permissão de acesso à Consulta RFB
Session::CheckSession();
if (!Helper::temPermissaoRFB()) {
    echo '<div class="container-fluid mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4 class="alert-heading"><i class="fas fa-ban"></i> Acesso Negado</h4>';
    echo '<p>Você não tem permissão para acessar esta funcionalidade.</p>';
    echo '<p>Contate o administrador do sistema para solicitar acesso à Consulta RFB.</p>';
    echo '<a href="/" class="btn btn-primary mt-2"><i class="fas fa-home"></i> Voltar ao Início</a>';
    echo '</div>';
    echo '</div>';
    exit;
}

// Instanciar API e bancos
$api = new ReceitaFederalAPI();

$db1 = Database1::getInstance();
$con1 = $db1->getConnection();

$db3 = Database3::getInstance();
$con3 = $db3->getConnection();

$resultado = null;
$erro = null;
$aviso = null;

// Função para verificar se CPF existe na base CFO
function verificarCPFnaCFO($cpf, $con)
{
    try {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        // Buscar na tabela de dados pessoais
        $query = "SELECT TOP 1
                    CPF,
                    NomeSocial AS Nome,
                    NULL AS Inscricao,
                    NULL AS CRO,
                    NULL AS Situacao
                  FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PF_Dados_Pessoais] pf
                  WHERE REPLACE(REPLACE(REPLACE(pf.CPF, '.', ''), '-', ''), '/', '') = :cpf";

        $stmt = $con->prepare($query);
        $stmt->bindParam(':cpf', $cpfLimpo);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : false;
    } catch (PDOException $e) {
        error_log("Erro ao verificar CPF na base CFO: " . $e->getMessage());
        return false;
    }
}

// Função para verificar se CNPJ existe na base CFO
function verificarCNPJnaCFO($cnpj, $con)
{
    try {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        // Buscar CNPJ na tabela de dados da empresa
        $query = "SELECT TOP 1
                    CNPJ,
                    RazaoSocial AS Nome,
                    Inscricao,
                    Cro AS CRO,
                    Situacao
                  FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa] pj
                  WHERE REPLACE(REPLACE(REPLACE(pj.CNPJ, '.', ''), '-', ''), '/', '') = :cnpj";

        $stmt = $con->prepare($query);
        $stmt->bindParam(':cnpj', $cnpjLimpo);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : false;
    } catch (PDOException $e) {
        error_log("Erro ao verificar CNPJ na base CFO: " . $e->getMessage());
        return false;
    }
}

// Função para registrar auditoria completa com todos os campos CPF e CNPJ
// Versão completa com suporte a todos os campos específicos e fiscalização
function registrarAuditoria($tipo, $documento, $resultado, $dadosCFO, $con1, $extras = [])
{
    error_log("DEBUG registrarAuditoria: Iniciando função. Tipo: {$tipo}, Documento: {$documento}");
    
    try {
        // Extrair nome do resultado
        $nomeConsultado = '';
        $situacaoCadastral = '';
        if ($tipo === 'CPF') {
            $nomeConsultado = $resultado['nome'] ?? '';
            $situacaoCadastral = $resultado['situacao_cadastral'] ?? '';
        } else {
            $nomeConsultado = $resultado['razao_social'] ?? $resultado['nome_fantasia'] ?? '';
            $situacaoCadastral = $resultado['situacao_cadastral'] ?? '';
        }
        
        error_log("DEBUG registrarAuditoria: Nome consultado: {$nomeConsultado}, Situação: {$situacaoCadastral}");
        
        // Primeiro, tentar inserção básica com campos que sabemos que existem
        // Tentar incluir documento_consultado se existir, senão usar apenas cpf_consultado
        $documentoLimpo = preg_replace('/[^0-9]/', '', $documento);
        error_log("DEBUG registrarAuditoria: Documento limpo: {$documentoLimpo}");
        
        // Verificar se campo documento_consultado existe
        try {
        $cols = "usuario_id, usuario_nome, usuario_grupo, usuario_subgrupo,
                        tipo_consulta, documento_consultado, cpf_consultado, nome_consultado, situacao_cadastral,
                        existe_base_cfo, inscricao_cfo, nome_cfo, cro_cfo,
                        sucesso, mensagem_erro, tempo_resposta_ms, ip_origem, data_hora";
        $vals = ":usuario_id, :usuario_nome, :usuario_grupo, :usuario_subgrupo,
                        :tipo_consulta, :documento_consultado, :cpf_consultado, :nome_consultado, :situacao_cadastral,
                        :existe_base_cfo, :inscricao_cfo, :nome_cfo, :cro_cfo,
                        :sucesso, :mensagem_erro, :tempo_resposta_ms, :ip_origem, NOW()";
        $usaFiscal = !empty($extras) && (isset($extras['origem_plataforma']) || isset($extras['sessao_id']) || !empty($extras['finalidade_consulta']) || !empty($extras['base_legal_lgpd']));
        if ($usaFiscal) {
            $cols .= ", finalidade_consulta, base_legal_lgpd, origem_plataforma, sessao_id";
            $vals .= ", :finalidade_consulta, :base_legal_lgpd, :origem_plataforma, :sessao_id";
        }
        $query = "INSERT INTO tbl_rfb_auditoria ($cols) VALUES ($vals)";

            $dados = [
                ':usuario_id' => Session::get('id'),
                ':usuario_nome' => Session::get('name'),
                ':usuario_grupo' => Helper::$GroupList[Session::get('grupo')] ?? Session::get('grupo'),
                ':usuario_subgrupo' => Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo'),
                ':tipo_consulta' => $tipo,
                ':documento_consultado' => $documentoLimpo,
                ':cpf_consultado' => $documentoLimpo, // Compatibilidade
                ':nome_consultado' => $nomeConsultado,
                ':situacao_cadastral' => $situacaoCadastral,
                ':existe_base_cfo' => $dadosCFO ? 1 : 0,
                ':inscricao_cfo' => $dadosCFO['Inscricao'] ?? null,
                ':nome_cfo' => $dadosCFO['Nome'] ?? null,
                ':cro_cfo' => $dadosCFO['CRO'] ?? null,
                ':sucesso' => $resultado['sucesso'] ? 1 : 0,
                ':mensagem_erro' => $resultado['erro'] ?? null,
                ':tempo_resposta_ms' => $resultado['tempo_resposta_ms'] ?? 0,
                ':ip_origem' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            if ($usaFiscal) {
                $dados[':finalidade_consulta'] = $extras['finalidade_consulta'] ?? null;
                $dados[':base_legal_lgpd'] = $extras['base_legal_lgpd'] ?? null;
                $dados[':origem_plataforma'] = $extras['origem_plataforma'] ?? 'Web';
                $dados[':sessao_id'] = $extras['sessao_id'] ?? (session_id() ?: null);
            }
            
            error_log("DEBUG: Executando query com documento_consultado");
        $stmt = $con1->prepare($query);
            $stmt->execute($dados);
            error_log("DEBUG: Query executada com sucesso!");
        } catch (PDOException $e) {
            error_log("DEBUG: Erro capturado: " . $e->getMessage());
            $isFiscalColumn = $usaFiscal && strpos($e->getMessage(), 'Unknown column') !== false && (
                strpos($e->getMessage(), 'finalidade_consulta') !== false ||
                strpos($e->getMessage(), 'base_legal_lgpd') !== false ||
                strpos($e->getMessage(), 'origem_plataforma') !== false ||
                strpos($e->getMessage(), 'sessao_id') !== false
            );
            // Se colunas de fiscalização não existirem, tentar sem elas
            if ($isFiscalColumn) {
                error_log("DEBUG: Colunas de fiscalização inexistentes, usando fallback sem elas");
                $cols = "usuario_id, usuario_nome, usuario_grupo, usuario_subgrupo, tipo_consulta, documento_consultado, cpf_consultado, nome_consultado, situacao_cadastral, existe_base_cfo, inscricao_cfo, nome_cfo, cro_cfo, sucesso, mensagem_erro, tempo_resposta_ms, ip_origem, data_hora";
                $vals = ":usuario_id, :usuario_nome, :usuario_grupo, :usuario_subgrupo, :tipo_consulta, :documento_consultado, :cpf_consultado, :nome_consultado, :situacao_cadastral, :existe_base_cfo, :inscricao_cfo, :nome_cfo, :cro_cfo, :sucesso, :mensagem_erro, :tempo_resposta_ms, :ip_origem, NOW()";
                unset($dados[':finalidade_consulta'], $dados[':base_legal_lgpd'], $dados[':origem_plataforma'], $dados[':sessao_id']);
                $query = "INSERT INTO tbl_rfb_auditoria ($cols) VALUES ($vals)";
                $stmt = $con1->prepare($query);
                $stmt->execute($dados);
                error_log("DEBUG: Query fallback (sem fiscalização) executada com sucesso!");
            } elseif ((strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'documento_consultado') !== false) ||
                (strpos($e->getMessage(), 'Data too long') !== false && strpos($e->getMessage(), 'cpf_consultado') !== false)) {
                error_log("DEBUG: Campo documento_consultado não existe ou cpf_consultado muito pequeno, usando fallback sem documento_consultado");
                $query = "INSERT INTO tbl_rfb_auditoria (
                            usuario_id, usuario_nome, usuario_grupo, usuario_subgrupo,
                            tipo_consulta, cpf_consultado, nome_consultado, situacao_cadastral,
                            existe_base_cfo, inscricao_cfo, nome_cfo, cro_cfo,
                            sucesso, mensagem_erro, tempo_resposta_ms, ip_origem, data_hora
                          ) VALUES (
                            :usuario_id, :usuario_nome, :usuario_grupo, :usuario_subgrupo,
                            :tipo_consulta, :cpf_consultado, :nome_consultado, :situacao_cadastral,
                            :existe_base_cfo, :inscricao_cfo, :nome_cfo, :cro_cfo,
                            :sucesso, :mensagem_erro, :tempo_resposta_ms, :ip_origem, NOW()
                          )";
                
                $dados = [
            ':usuario_id' => Session::get('id'),
            ':usuario_nome' => Session::get('name'),
            ':usuario_grupo' => Helper::$GroupList[Session::get('grupo')] ?? Session::get('grupo'),
            ':usuario_subgrupo' => Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo'),
                    ':tipo_consulta' => $tipo,
                    ':cpf_consultado' => $documentoLimpo,
                    ':nome_consultado' => $nomeConsultado,
                    ':situacao_cadastral' => $situacaoCadastral,
            ':existe_base_cfo' => $dadosCFO ? 1 : 0,
            ':inscricao_cfo' => $dadosCFO['Inscricao'] ?? null,
            ':nome_cfo' => $dadosCFO['Nome'] ?? null,
            ':cro_cfo' => $dadosCFO['CRO'] ?? null,
            ':sucesso' => $resultado['sucesso'] ? 1 : 0,
            ':mensagem_erro' => $resultado['erro'] ?? null,
            ':tempo_resposta_ms' => $resultado['tempo_resposta_ms'] ?? 0,
            ':ip_origem' => $_SERVER['REMOTE_ADDR'] ?? null
                ];
                
                error_log("DEBUG: Executando query fallback sem documento_consultado");
                $stmt = $con1->prepare($query);
                $stmt->execute($dados);
                error_log("DEBUG: Query fallback executada com sucesso!");
            } else {
                error_log("DEBUG: Erro não relacionado a documento_consultado, relançando exceção");
                throw $e;
            }
        }
        
        // Se inserção básica funcionou, tentar UPDATE com campos adicionais se existirem
        $idInserido = $con1->lastInsertId();
        
        if ($idInserido && $resultado['sucesso']) {
            // Tentar atualizar com campos específicos CPF ou CNPJ se existirem
            try {
                if ($tipo === 'CPF') {
                    $updateFields = [];
                    $updateValues = [];
                    
                    if (!empty($resultado['nome'])) {
                        $updateFields[] = 'cpf_nome = :cpf_nome';
                        $updateValues[':cpf_nome'] = $resultado['nome'];
                    }
                    if (!empty($resultado['data_nascimento'])) {
                        $updateFields[] = 'cpf_data_nascimento = :cpf_data_nascimento';
                        $updateValues[':cpf_data_nascimento'] = date('Y-m-d', strtotime(str_replace('/', '-', $resultado['data_nascimento'])));
                    }
                    if (!empty($resultado['logradouro'])) {
                        $updateFields[] = 'cpf_logradouro = :cpf_logradouro';
                        $updateValues[':cpf_logradouro'] = $resultado['logradouro'];
                    }
                    if (!empty($resultado['numero_logradouro'])) {
                        $updateFields[] = 'cpf_numero_logradouro = :cpf_numero_logradouro';
                        $updateValues[':cpf_numero_logradouro'] = $resultado['numero_logradouro'];
                    }
                    if (!empty($resultado['bairro'])) {
                        $updateFields[] = 'cpf_bairro = :cpf_bairro';
                        $updateValues[':cpf_bairro'] = $resultado['bairro'];
                    }
                    if (!empty($resultado['cep'])) {
                        $updateFields[] = 'cpf_cep = :cpf_cep';
                        $updateValues[':cpf_cep'] = $resultado['cep'];
                    }
                    if (!empty($resultado['uf'])) {
                        $updateFields[] = 'cpf_uf = :cpf_uf';
                        $updateValues[':cpf_uf'] = $resultado['uf'];
                    }
                    if (!empty($resultado['municipio'])) {
                        $updateFields[] = 'cpf_municipio = :cpf_municipio';
                        $updateValues[':cpf_municipio'] = $resultado['municipio'];
                    }
                    
                    if (!empty($updateFields)) {
                        $updateQuery = "UPDATE tbl_rfb_auditoria SET " . implode(', ', $updateFields) . " WHERE id = :id";
                        $updateValues[':id'] = $idInserido;
                        $stmtUpdate = $con1->prepare($updateQuery);
                        $stmtUpdate->execute($updateValues);
                    }
                } else {
                    // CNPJ
                    $updateFields = [];
                    $updateValues = [];
                    
                    if (!empty($resultado['razao_social'])) {
                        $updateFields[] = 'cnpj_razao_social = :cnpj_razao_social';
                        $updateValues[':cnpj_razao_social'] = $resultado['razao_social'];
                    }
                    if (!empty($resultado['nome_fantasia'])) {
                        $updateFields[] = 'cnpj_nome_fantasia = :cnpj_nome_fantasia';
                        $updateValues[':cnpj_nome_fantasia'] = $resultado['nome_fantasia'];
                    }
                    if (!empty($resultado['logradouro'])) {
                        $updateFields[] = 'cnpj_logradouro = :cnpj_logradouro';
                        $updateValues[':cnpj_logradouro'] = $resultado['logradouro'];
                    }
                    if (!empty($resultado['numero_logradouro'])) {
                        $updateFields[] = 'cnpj_numero_logradouro = :cnpj_numero_logradouro';
                        $updateValues[':cnpj_numero_logradouro'] = $resultado['numero_logradouro'];
                    }
                    if (!empty($resultado['bairro'])) {
                        $updateFields[] = 'cnpj_bairro = :cnpj_bairro';
                        $updateValues[':cnpj_bairro'] = $resultado['bairro'];
                    }
                    if (!empty($resultado['cep'])) {
                        $updateFields[] = 'cnpj_cep = :cnpj_cep';
                        $updateValues[':cnpj_cep'] = $resultado['cep'];
                    }
                    if (!empty($resultado['uf'])) {
                        $updateFields[] = 'cnpj_uf = :cnpj_uf';
                        $updateValues[':cnpj_uf'] = $resultado['uf'];
                    }
                    if (!empty($resultado['municipio'])) {
                        $updateFields[] = 'cnpj_municipio = :cnpj_municipio';
                        $updateValues[':cnpj_municipio'] = $resultado['municipio'];
                    }
                    if (!empty($resultado['cnae_fiscal'])) {
                        $updateFields[] = 'cnpj_cnae_fiscal = :cnpj_cnae_fiscal';
                        $updateValues[':cnpj_cnae_fiscal'] = $resultado['cnae_fiscal'];
                    }
                    if (!empty($resultado['capital_social'])) {
                        $updateFields[] = 'cnpj_capital_social = :cnpj_capital_social';
                        $updateValues[':cnpj_capital_social'] = $resultado['capital_social'];
                    }
                    
                    if (!empty($updateFields)) {
                        $updateQuery = "UPDATE tbl_rfb_auditoria SET " . implode(', ', $updateFields) . " WHERE id = :id";
                        $updateValues[':id'] = $idInserido;
                        $stmtUpdate = $con1->prepare($updateQuery);
                        $stmtUpdate->execute($updateValues);
                    }
                }
            } catch (PDOException $eUpdate) {
                // Ignorar erros de UPDATE - campos podem não existir ainda
                error_log("Aviso: Não foi possível atualizar campos adicionais (podem não existir ainda): " . $eUpdate->getMessage());
            }
        }
        
        error_log("DEBUG: Inserção realizada com sucesso. ID: " . $con1->lastInsertId());
        return true;
    } catch (PDOException $e) {
        error_log("✗ Erro ao registrar auditoria RFB: " . $e->getMessage());
        error_log("✗ SQL State: " . $e->getCode());
        error_log("✗ Query: " . ($query ?? 'N/A'));
        error_log("✗ Dados: " . print_r($dados ?? [], true));
        throw $e;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['documento'])) {
    // Verificar se o usuário tem permissão para fazer consultas
    $emailUsuario = Session::get('email') ?? '';
    $emailsAutorizados = [
        'joao.dias@cfo.org.br',
        'santinho@cfo.org.br'
    ];
    
    if (!in_array(strtolower($emailUsuario), array_map('strtolower', $emailsAutorizados))) {
        $erro = "Você não tem permissão para realizar consultas na Receita Federal. Por favor, entre em contato com joao.dias@cfo.org.br para solicitar acesso.";
    } else {
    $tipoDocumento = $_POST['tipo_documento'] ?? 'CPF';
    $documento = trim($_POST['documento']);
    $documentoLimpo = preg_replace('/[^0-9]/', '', $documento);

    // Validar documento
    if ($tipoDocumento === 'CPF') {
        if (strlen($documentoLimpo) != 11) {
        $erro = "CPF inválido. Digite apenas os 11 dígitos.";
        }
    } else {
        if (strlen($documentoLimpo) != 14) {
            $erro = "CNPJ inválido. Digite apenas os 14 dígitos.";
        }
    }

    if (!$erro) {
        // CPF Usuário autorizado
        $cpfUsuario = '03990316184';

        // Verificar se existe na base CFO
        $dadosCFO = false;

        // Chamar API conforme tipo
        if ($tipoDocumento === 'CPF') {
            $dadosCFO = verificarCPFnaCFO($documentoLimpo, $con3);

            $resultado = $api->consultarCPF($documentoLimpo, $cpfUsuario);
            $resultado['tipo'] = 'CPF';
            $resultado['dados_cfo'] = $dadosCFO;
        } else {
            $dadosCFO = verificarCNPJnaCFO($documentoLimpo, $con3);

            $resultado = $api->consultarCNPJ($documentoLimpo, $cpfUsuario);
            $resultado['tipo'] = 'CNPJ';
            $resultado['dados_cfo'] = $dadosCFO;
        }

        // Registrar auditoria (sucesso ou erro) - não bloquear se der erro
        try {
            error_log("DEBUG: Tentando registrar auditoria para {$tipoDocumento}: {$documentoLimpo}");
            error_log("DEBUG: Resultado sucesso: " . ($resultado['sucesso'] ? 'SIM' : 'NÃO'));
            error_log("DEBUG: Dados CFO: " . ($dadosCFO ? 'SIM' : 'NÃO'));
            error_log("DEBUG: Conexão con1: " . ($con1 ? 'OK' : 'NULL'));
            
            $extras = [
                'finalidade_consulta' => $_POST['finalidade_consulta'] ?? null,
                'base_legal_lgpd' => $_POST['base_legal_lgpd'] ?? null,
                'origem_plataforma' => 'Web',
                'sessao_id' => session_id() ?: null
            ];
            registrarAuditoria($tipoDocumento, $documentoLimpo, $resultado, $dadosCFO, $con1, $extras);
            error_log("✓ Auditoria registrada com sucesso para {$tipoDocumento}: {$documentoLimpo}");
            // Mensagem de sucesso temporária para debug
            $aviso = "✓ Auditoria registrada com sucesso! Verifique os logs para mais detalhes.";
        } catch (Exception $e) {
            // Apenas logar erro de auditoria, não interromper fluxo
            error_log("✗ Erro ao registrar auditoria RFB: " . $e->getMessage());
            error_log("✗ Trace: " . $e->getTraceAsString());
            // Mostrar erro na tela também para debug
            $aviso = "ERRO AO SALVAR AUDITORIA: " . $e->getMessage();
        }

        if ($resultado['sucesso']) {
            // Consulta realizada com sucesso
            error_log("Consulta {$tipoDocumento} realizada: " . $documentoLimpo);
        } else {
            $erro = $resultado['erro'];
        }
        }
    }
}

// DEBUG: Remover após testar
// if (isset($_POST['documento'])) {
//     error_log("POST recebido: " . print_r($_POST, true));
//     error_log("Resultado: " . print_r($resultado, true));
//     error_log("Erro: " . $erro);
// }

?>

<!-- Conteúdo da Consulta Receita Federal -->
<div class="mt-3">

    <!-- Seletor CPF/CNPJ -->
    <div class="row mb-4">
        <div class="col-md-8 offset-md-2">
            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                <label class="btn btn-outline-primary active" id="label-cpf">
                    <input type="radio" name="tipo_doc_selector" value="CPF" checked autocomplete="off"> 
                    <i class="fas fa-user"></i> Consultar CPF (Pessoa Física)
                </label>
                <label class="btn btn-outline-primary" id="label-cnpj">
                    <input type="radio" name="tipo_doc_selector" value="CNPJ" autocomplete="off"> 
                    <i class="fas fa-building"></i> Consultar CNPJ (Pessoa Jurídica)
                </label>
            </div>
        </div>
                </div>

            <!-- Formulário de Consulta -->
            <div class="row">
                <div class="col-md-6 offset-md-3">
            <form method="POST" action="" id="form-consulta">
                <input type="hidden" name="tipo_documento" id="tipo_documento" value="CPF">

                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <label for="finalidade_consulta"><small>Finalidade da consulta (LGPD)</small></label>
                                <select name="finalidade_consulta" id="finalidade_consulta" class="form-control form-control-sm">
                                    <option value="">Selecione...</option>
                                    <option value="Rotina" <?= (($_POST['finalidade_consulta'] ?? '') === 'Rotina') ? 'selected' : '' ?>>Rotina</option>
                                    <option value="Denúncia" <?= (($_POST['finalidade_consulta'] ?? '') === 'Denúncia') ? 'selected' : '' ?>>Denúncia</option>
                                    <option value="Fiscalização Programada" <?= (($_POST['finalidade_consulta'] ?? '') === 'Fiscalização Programada') ? 'selected' : '' ?>>Fiscalização Programada</option>
                                    <option value="Processo Ético" <?= (($_POST['finalidade_consulta'] ?? '') === 'Processo Ético') ? 'selected' : '' ?>>Processo Ético</option>
                                    <option value="Processo Administrativo" <?= (($_POST['finalidade_consulta'] ?? '') === 'Processo Administrativo') ? 'selected' : '' ?>>Processo Administrativo</option>
                                    <option value="Integração Sistema" <?= (($_POST['finalidade_consulta'] ?? '') === 'Integração Sistema') ? 'selected' : '' ?>>Integração Sistema</option>
                                    <option value="Suporte Técnico" <?= (($_POST['finalidade_consulta'] ?? '') === 'Suporte Técnico') ? 'selected' : '' ?>>Suporte Técnico</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="base_legal_lgpd"><small>Base legal LGPD</small></label>
                                <select name="base_legal_lgpd" id="base_legal_lgpd" class="form-control form-control-sm">
                                    <option value="">Selecione...</option>
                                    <option value="Execução de política pública" <?= (($_POST['base_legal_lgpd'] ?? '') === 'Execução de política pública') ? 'selected' : '' ?>>Execução de política pública</option>
                                    <option value="Obrigação legal/regulatória" <?= (($_POST['base_legal_lgpd'] ?? '') === 'Obrigação legal/regulatória') ? 'selected' : '' ?>>Obrigação legal/regulatória</option>
                                    <option value="Proteção do crédito" <?= (($_POST['base_legal_lgpd'] ?? '') === 'Proteção do crédito') ? 'selected' : '' ?>>Proteção do crédito</option>
                                    <option value="Tutela da saúde" <?= (($_POST['base_legal_lgpd'] ?? '') === 'Tutela da saúde') ? 'selected' : '' ?>>Tutela da saúde</option>
                                    <option value="Legítimo interesse" <?= (($_POST['base_legal_lgpd'] ?? '') === 'Legítimo interesse') ? 'selected' : '' ?>>Legítimo interesse</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                    <label for="documento"><strong><span id="label-documento">CPF</span> para Consulta:</strong></label>
                            <input
                                type="text"
                        id="documento"
                        name="documento"
                        class="form-control form-control-lg text-center"
                                placeholder="000.000.000-00"
                        maxlength="18"
                                required
                        value="<?= isset($_POST['documento']) ? htmlspecialchars($_POST['documento']) : '' ?>"
                                autofocus
                            />
                    <small class="form-text text-muted" id="hint-documento">
                                Digite apenas os números do CPF (11 dígitos)
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-search"></i> Consultar na Receita Federal
                        </button>
                    </form>

                    <hr>
                    
                </div>
            </div>

            <!-- Mensagens de Erro -->
            <?php if ($erro): ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <strong><i class="fas fa-times-circle"></i> Erro!</strong> <?= nl2br(htmlspecialchars($erro)) ?>
                </div>
            <?php endif; ?>

            <!-- Mensagens de Aviso -->
            <?php if ($aviso): ?>
                <div class="alert alert-warning mt-4" role="alert">
                    <strong><i class="fas fa-exclamation-triangle"></i> Aviso!</strong> <?= nl2br(htmlspecialchars($aviso)) ?>
                </div>
            <?php endif; ?>

    <!-- Resultado da Consulta CPF -->
    <?php if ($resultado && $resultado['sucesso'] && ($resultado['tipo'] ?? '') === 'CPF'): ?>
                <hr class="my-4">

                <!-- Info de Performance -->
                <div class="alert alert-info">
                    <small>
                <i class="fas fa-clock"></i> Tempo de resposta: <?= $resultado['tempo_resposta_ms'] ?>ms
                    </small>
                </div>

        <!-- Dados da Receita Federal - CPF -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-user"></i> Dados Cadastrais - Pessoa Física (CPF)</h6>
                    </div>
                    <div class="card-body">

                        <!-- Dados Pessoais -->
                        <h6 class="text-primary"><i class="fas fa-id-card"></i> Identificação</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">CPF:</label>
                                <p class="text-monospace"><?= htmlspecialchars($resultado['cpf']) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Nome:</label>
                                <p><?= htmlspecialchars($resultado['nome']) ?></p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Situação Cadastral:</label>
                                <p>
                                    <span class="badge badge-<?= $resultado['situacao_cadastral'] == 'Regular' ? 'success' : 'danger' ?> badge-lg">
                                        <?= htmlspecialchars($resultado['situacao_cadastral']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Nome da Mãe:</label>
                                <p><?= htmlspecialchars($resultado['nome_mae']) ?></p>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold">Data Nascimento:</label>
                                <p><?= !empty($resultado['data_nascimento']) ? substr($resultado['data_nascimento'], 6, 2) . '/' . substr($resultado['data_nascimento'], 4, 2) . '/' . substr($resultado['data_nascimento'], 0, 4) : 'N/A' ?></p>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold">Sexo:</label>
                                <p><?= htmlspecialchars($resultado['sexo_descricao']) ?></p>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold">Ano Óbito:</label>
                                <p><?= $resultado['ano_obito'] != '0000' ? '<span class="badge badge-dark">' . $resultado['ano_obito'] . '</span>' : '<span class="text-muted">-</span>' ?></p>
                            </div>
                        </div>

                        <!-- Naturalidade e Nacionalidade -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Naturalidade:</label>
                                <p><?= !empty($resultado['nome_municipio_naturalidade']) ? htmlspecialchars($resultado['nome_municipio_naturalidade']) . ' - ' . htmlspecialchars($resultado['uf_municipio_naturalidade']) : '-' ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Estrangeiro:</label>
                                <p><?= $resultado['estrangeiro'] == 'S' ? '<span class="badge badge-info">Sim</span>' : '<span class="text-muted">Não</span>' ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">País de Nacionalidade:</label>
                                <p><?= !empty($resultado['nome_pais_nacionalidade']) ? htmlspecialchars($resultado['nome_pais_nacionalidade']) : 'Brasil' ?></p>
                            </div>
                        </div>

                        <!-- Residência no Exterior -->
                        <?php if ($resultado['residente_exterior'] == 'S'): ?>
                        <div class="alert alert-info">
                            <strong><i class="fas fa-globe"></i> Residente no Exterior:</strong>
                            <?= htmlspecialchars($resultado['nome_pais_exterior']) ?>
                        </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Ocupação -->
                        <?php if (!empty($resultado['ocupacao_principal'])): ?>
                        <h6 class="text-primary"><i class="fas fa-briefcase"></i> Ocupação Profissional</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Ocupação Principal:</label>
                                <p><?= htmlspecialchars($resultado['ocupacao_principal']) ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Natureza Ocupação:</label>
                                <p><?= htmlspecialchars($resultado['natureza_ocupacao'] ?: '-') ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Exercício Ocupação:</label>
                                <p><?= htmlspecialchars($resultado['exercicio_ocupacao'] ?: '-') ?></p>
                            </div>
                        </div>
                        <hr>
                        <?php endif; ?>

                        <!-- Contato -->
                        <?php if (!empty($resultado['telefone'])): ?>
                        <h6 class="text-primary"><i class="fas fa-phone"></i> Contato</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Telefone:</label>
                                <p><?= htmlspecialchars('(' . $resultado['ddd'] . ') ' . $resultado['telefone']) ?></p>
                            </div>
                        </div>
                        <hr>
                        <?php endif; ?>

                        <hr>

                        <!-- Endereço -->
                        <h6 class="text-primary"><i class="fas fa-map-marker-alt"></i> Endereço Cadastrado</h6>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-bold">Logradouro:</label>
                                <p><?= htmlspecialchars($resultado['tipo_logradouro']) ?> <?= htmlspecialchars($resultado['logradouro']) ?>, Nº <?= htmlspecialchars($resultado['numero_logradouro']) ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Complemento:</label>
                                <p><?= htmlspecialchars($resultado['complemento'] ?: '-') ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Bairro:</label>
                                <p><?= htmlspecialchars($resultado['bairro']) ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Município/UF:</label>
                                <p><?= htmlspecialchars($resultado['municipio']) ?> - <?= htmlspecialchars($resultado['uf']) ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">CEP:</label>
                                <p class="text-monospace"><?= htmlspecialchars($resultado['cep']) ?></p>
                            </div>
                        </div>

                        <hr>

                        <!-- Informações Administrativas -->
                        <h6 class="text-primary"><i class="fas fa-info-circle"></i> Informações Administrativas</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Unidade Administrativa:</label>
                                <p><?= htmlspecialchars($resultado['unidade_administrativa'] ?: '-') ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Data de Inscrição:</label>
                                <p><?= !empty($resultado['data_inscricao']) ? substr($resultado['data_inscricao'], 6, 2) . '/' . substr($resultado['data_inscricao'], 4, 2) . '/' . substr($resultado['data_inscricao'], 0, 4) : '-' ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Data de Atualização:</label>
                                <p><?= !empty($resultado['data_atualizacao']) ? substr($resultado['data_atualizacao'], 6, 2) . '/' . substr($resultado['data_atualizacao'], 4, 2) . '/' . substr($resultado['data_atualizacao'], 0, 4) : '-' ?></p>
                            </div>
                        </div>

                        <!-- Alerta LGPD -->
                        <div class="alert alert-warning mt-3 mb-0">
                            <small>
                                <i class="fas fa-exclamation-circle"></i> <strong>AVISO LGPD:</strong>
                                Estes dados são sensíveis e protegidos pela Lei Geral de Proteção de Dados.
                                Todas as consultas são registradas, auditadas e rastreáveis.
                                Use apenas para finalidades autorizadas.
                            </small>
                        </div>
                    </div>
                </div>
    <?php endif; ?>

    <!-- Resultado da Consulta CNPJ -->
    <?php if ($resultado && $resultado['sucesso'] && ($resultado['tipo'] ?? '') === 'CNPJ'): ?>
        <hr class="my-4">

        <!-- Info de Performance -->
        <div class="alert alert-info">
            <small>
                <i class="fas fa-clock"></i> Tempo de resposta: <?= $resultado['tempo_resposta_ms'] ?>ms
            </small>
        </div>

        <!-- Info Base CFO (se existir) -->
        <?php if (!empty($resultado['dados_cfo'])): ?>
        <div class="alert alert-success">
            <h6><i class="fas fa-check-circle"></i> CNPJ Encontrado na Base CFO</h6>
            <div class="row">
                <div class="col-md-3">
                    <strong>Inscrição:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Inscricao']) ?>
                </div>
                <div class="col-md-5">
                    <strong>Nome:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Nome']) ?>
                </div>
                <div class="col-md-2">
                    <strong>CRO:</strong> <?= htmlspecialchars($resultado['dados_cfo']['CRO']) ?>
                </div>
                <div class="col-md-2">
                    <strong>Situação:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Situacao']) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dados da Receita Federal - CNPJ -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-building"></i> Dados Cadastrais - Pessoa Jurídica (CNPJ)</h6>
            </div>
            <div class="card-body">

                <!-- Dados Básicos -->
                <h6 class="text-success"><i class="fas fa-briefcase"></i> Identificação</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">CNPJ:</label>
                        <p class="text-monospace"><?= htmlspecialchars($resultado['cnpj']) ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Razão Social:</label>
                        <p><?= htmlspecialchars($resultado['razao_social']) ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Situação Cadastral:</label>
                        <p>
                            <span class="badge badge-<?= $resultado['situacao_cadastral'] == 'Ativa' ? 'success' : 'danger' ?> badge-lg">
                                <?= htmlspecialchars($resultado['situacao_cadastral']) ?>
                            </span>
                            <?php if (!empty($resultado['motivo_situacao_cadastral_codigo']) && $resultado['motivo_situacao_cadastral_codigo'] != '00'): ?>
                                <br><small class="text-muted">Motivo: <?= htmlspecialchars($resultado['motivo_situacao_cadastral']) ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Nome Fantasia:</label>
                        <p><?= htmlspecialchars($resultado['nome_fantasia'] ?: '-') ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Data Abertura:</label>
                        <p><?= !empty($resultado['data_abertura']) ? substr($resultado['data_abertura'], 6, 2) . '/' . substr($resultado['data_abertura'], 4, 2) . '/' . substr($resultado['data_abertura'], 0, 4) : 'N/A' ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Porte:</label>
                        <p><?= htmlspecialchars($resultado['descricao_porte'] ?: $resultado['porte'] ?: '-') ?></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Natureza Jurídica:</label>
                        <p><?= htmlspecialchars($resultado['codigo_natureza_juridica']) ?> - <?= htmlspecialchars($resultado['natureza_juridica'] ?: '') ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Capital Social:</label>
                        <p><?= !empty($resultado['capital_social']) ? 'R$ ' . number_format((float)$resultado['capital_social'], 2, ',', '.') : '-' ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">Email:</label>
                        <p><?= htmlspecialchars($resultado['email'] ?: '-') ?></p>
                    </div>
                </div>

                <hr>

                <!-- CNAE -->
                <h6 class="text-success"><i class="fas fa-chart-line"></i> Atividade Econômica</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold">CNAE Fiscal Principal:</label>
                        <p class="text-monospace"><?= htmlspecialchars($resultado['cnae_fiscal']) ?></p>
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="font-weight-bold">Descrição:</label>
                        <p><?= htmlspecialchars($resultado['descricao_cnae_fiscal']) ?></p>
                    </div>
                </div>

                <?php if (!empty($resultado['cnaes_secundarios']) && is_array($resultado['cnaes_secundarios'])): ?>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold">CNAEs Secundários:</label>
                        <ul class="list-unstyled ml-3">
                            <?php foreach ($resultado['cnaes_secundarios'] as $cnae): ?>
                                <li><small><?= htmlspecialchars($cnae) ?></small></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <hr>

                <!-- Regime Tributário -->
                <h6 class="text-success"><i class="fas fa-money-bill-wave"></i> Regime Tributário</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Simples Nacional:</label>
                        <p>
                            <?php if (!empty($resultado['opcao_simples']) && $resultado['opcao_simples'] != 'N'): ?>
                                <span class="badge badge-info">Optante</span><br>
                                <small>Desde: <?= !empty($resultado['data_opcao_simples']) ? substr($resultado['data_opcao_simples'], 6, 2) . '/' . substr($resultado['data_opcao_simples'], 4, 2) . '/' . substr($resultado['data_opcao_simples'], 0, 4) : '-' ?></small>
                            <?php else: ?>
                                <span class="badge badge-secondary">Não Optante</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">MEI:</label>
                        <p>
                            <?php if (!empty($resultado['opcao_mei']) && $resultado['opcao_mei'] != 'N'): ?>
                                <span class="badge badge-info">Optante</span><br>
                                <small>Desde: <?= !empty($resultado['data_opcao_mei']) ? substr($resultado['data_opcao_mei'], 6, 2) . '/' . substr($resultado['data_opcao_mei'], 4, 2) . '/' . substr($resultado['data_opcao_mei'], 0, 4) : '-' ?></small>
                            <?php else: ?>
                                <span class="badge badge-secondary">Não Optante</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Ente Federativo:</label>
                        <p><?= htmlspecialchars($resultado['ente_federativo'] ?: '-') ?></p>
                    </div>
                </div>

                <hr>

                <!-- Endereço -->
                <h6 class="text-success"><i class="fas fa-map-marker-alt"></i> Endereço Cadastrado</h6>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="font-weight-bold">Logradouro:</label>
                        <p><?= htmlspecialchars($resultado['tipo_logradouro']) ?> <?= htmlspecialchars($resultado['logradouro']) ?>, Nº <?= htmlspecialchars($resultado['numero_logradouro']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Complemento:</label>
                        <p><?= htmlspecialchars($resultado['complemento'] ?: '-') ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Bairro:</label>
                        <p><?= htmlspecialchars($resultado['bairro']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Município/UF:</label>
                        <p><?= htmlspecialchars($resultado['municipio']) ?> - <?= htmlspecialchars($resultado['uf']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">CEP:</label>
                        <p class="text-monospace"><?= htmlspecialchars($resultado['cep']) ?></p>
                    </div>
                </div>

                <hr>

                <!-- Responsável Legal -->
                <?php if (!empty($resultado['cpf_responsavel'])): ?>
                <h6 class="text-success"><i class="fas fa-user-tie"></i> Responsável Legal</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">CPF:</label>
                        <p class="text-monospace"><?= htmlspecialchars($resultado['cpf_responsavel']) ?></p>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="font-weight-bold">Nome:</label>
                        <p><?= htmlspecialchars($resultado['nome_responsavel']) ?></p>
                    </div>
                </div>
                <hr>
                <?php endif; ?>

                <!-- Quadro Societário -->
                <?php if (!empty($resultado['qsa']) && is_array($resultado['qsa'])): ?>
                <h6 class="text-success"><i class="fas fa-users"></i> Quadro de Sócios e Administradores (QSA)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Nome</th>
                                <th>Qualificação</th>
                                <th>CPF/CNPJ</th>
                                <th>País</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultado['qsa'] as $socio): ?>
                            <tr>
                                <td><?= htmlspecialchars($socio['nome'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($socio['qualificacao_descricao'] ?? $socio['qualificacao'] ?? '-') ?></small></td>
                                <td class="text-monospace"><small><?= htmlspecialchars($socio['documento'] ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($socio['pais_origem'] ?: 'Brasil') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <hr>
                <?php endif; ?>

                <!-- Situação Especial -->
                <?php if (!empty($resultado['situacao_especial'])): ?>
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle"></i> Situação Especial:</strong>
                    <?= htmlspecialchars($resultado['situacao_especial']) ?>
                    <?php if (!empty($resultado['data_situacao_especial'])): ?>
                        <br><small>Data: <?= substr($resultado['data_situacao_especial'], 6, 2) . '/' . substr($resultado['data_situacao_especial'], 4, 2) . '/' . substr($resultado['data_situacao_especial'], 0, 4) ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Alerta LGPD -->
                <div class="alert alert-warning mt-3 mb-0">
                    <small>
                        <i class="fas fa-exclamation-circle"></i> <strong>AVISO LGPD:</strong>
                        Estes dados são sensíveis e protegidos pela Lei Geral de Proteção de Dados.
                        Todas as consultas são registradas, auditadas e rastreáveis.
                        Use apenas para finalidades autorizadas.
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Botões de Ação (se houver resultado) -->
    <?php if ($resultado && $resultado['sucesso']): ?>
                <div class="text-center mt-4">
                    <a href="/consulta-rfb?tipo=1" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Nova Consulta
                    </a>
                    <button onclick="exportarPDF()" class="btn btn-danger btn-lg ml-2">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                    <a href="/consulta-rfb?tipo=2" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-chart-bar"></i> Ver Relatórios
                    </a>
                </div>

                <script>
                function exportarPDF() {
                    // Função para codificar UTF-8 corretamente antes do base64
                    function utf8_to_b64(str) {
                        try {
                            return btoa(unescape(encodeURIComponent(str)));
                        } catch (e) {
                            // Fallback: codificar caractere por caractere se necessário
                            let binary = '';
                            for (let i = 0; i < str.length; i++) {
                                const charCode = str.charCodeAt(i);
                                if (charCode < 0x80) {
                                    binary += String.fromCharCode(charCode);
                                } else if (charCode < 0x800) {
                                    binary += String.fromCharCode(0xC0 | (charCode >> 6));
                                    binary += String.fromCharCode(0x80 | (charCode & 0x3F));
                                } else {
                                    binary += String.fromCharCode(0xE0 | (charCode >> 12));
                                    binary += String.fromCharCode(0x80 | ((charCode >> 6) & 0x3F));
                                    binary += String.fromCharCode(0x80 | (charCode & 0x3F));
                                }
                            }
                            return btoa(binary);
                        }
                    }
                    
                    try {
                        // Função para limpar caracteres inválidos dos dados
                        function limparDados(obj) {
                            if (typeof obj === 'string') {
                                // Remover caracteres de controle inválidos, mas manter quebras de linha válidas
                                return obj.replace(/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/g, '');
                            } else if (Array.isArray(obj)) {
                                return obj.map(limparDados);
                            } else if (obj !== null && typeof obj === 'object') {
                                const limpo = {};
                                for (const key in obj) {
                                    if (obj.hasOwnProperty(key)) {
                                        limpo[key] = limparDados(obj[key]);
                                    }
                                }
                                return limpo;
                            }
                            return obj;
                        }
                        
                        // Codificar dados em base64 com tratamento UTF-8
                        const dados = <?= json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        
                        // Limpar dados para evitar problemas de codificação
                        const dadosLimpos = limparDados(dados);
                        
                        const dadosJSON = JSON.stringify(dadosLimpos);
                        const dadosBase64 = utf8_to_b64(dadosJSON);

                        // Abrir em nova aba (codificar base64 para URL segura)
                        // Base64 pode conter +, /, = que precisam ser codificados na URL
                        const dadosBase64Url = dadosBase64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                        window.open('/gerar-pdf-rfb-simples?dados=' + dadosBase64Url, '_blank');
                    } catch (error) {
                        alert('Erro ao gerar PDF: ' + error.message);
                        console.error('Erro ao exportar PDF:', error);
                    }
                }
                </script>
            <?php endif; ?>

</div>

<script>
// Gerenciar seleção CPF/CNPJ
const labelCPF = document.getElementById('label-cpf');
const labelCNPJ = document.getElementById('label-cnpj');
const inputDocumento = document.getElementById('documento');
const labelDocumento = document.getElementById('label-documento');
const hintDocumento = document.getElementById('hint-documento');
const tipoDocumentoInput = document.getElementById('tipo_documento');

labelCPF.addEventListener('click', function() {
    tipoDocumentoInput.value = 'CPF';
    inputDocumento.placeholder = '000.000.000-00';
    inputDocumento.maxLength = 14;
    inputDocumento.value = '';
    labelDocumento.textContent = 'CPF';
    hintDocumento.textContent = 'Digite apenas os números do CPF (11 dígitos)';
});

labelCNPJ.addEventListener('click', function() {
    tipoDocumentoInput.value = 'CNPJ';
    inputDocumento.placeholder = '00.000.000/0000-00';
    inputDocumento.maxLength = 18;
    inputDocumento.value = '';
    labelDocumento.textContent = 'CNPJ';
    hintDocumento.textContent = 'Digite apenas os números do CNPJ (14 dígitos)';
});

// Máscara inteligente para CPF ou CNPJ
inputDocumento.addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    const tipo = tipoDocumentoInput.value;
    
    if (tipo === 'CPF' && value.length <= 11) {
        // Máscara CPF: 000.000.000-00
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else if (tipo === 'CNPJ' && value.length <= 14) {
        // Máscara CNPJ: 00.000.000/0000-00
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    e.target.value = value;
});
</script>
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    }
    
    e.target.value = value;
});
</script>
