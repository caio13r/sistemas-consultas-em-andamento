<?php
// Arquivo incluído em consultaIntegrada.php - não precisa de header/footer

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\lib\ReceitaFederalAPI;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database3;
use PDO;
use PDOException;

// VERIFICAÇÃO DE PERMISSÃO: Apenas CROs ou email específico
if (!Helper::temPermissaoRFB()) {
    echo '<div class="alert alert-danger mt-4" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-ban"></i> Acesso Negado</h4>';
    echo '<p><strong>Você não tem permissão para acessar esta funcionalidade.</strong></p>';
    echo '<hr>';
    echo '<p class="mb-0">Esta consulta está disponível apenas para:</p>';
    echo '<ul class="mb-0">';
    echo '<li>Usuários de CROs regionais</li>';
    echo '<li>Usuários autorizados específicos</li>';
    echo '</ul>';
    echo '<p class="mt-3"><small>Se você acredita que deveria ter acesso, entre em contato com o administrador do sistema.</small></p>';
    echo '</div>';
    return; // Para a execução do script
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
        
        // Buscar em múltiplas tabelas da visão nacional
        $query = "SELECT TOP 1 
                    pf.CPF,
                    pf.Nome,
                    pf.Inscricao,
                    LEFT(pf.CRO, 2) AS CRO,
                    pf.Situacao
                  FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PF] pf
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

// Função para registrar auditoria
function registrarAuditoria($usuarioId, $cpf, $dadosCFO, $sucesso, $erro, $tempoResposta, $con)
{
    try {
        $query = "INSERT INTO tbl_rfb_auditoria 
                  (usuario_id, usuario_nome, usuario_grupo, usuario_subgrupo, cpf_consultado, 
                   existe_base_cfo, inscricao_cfo, nome_cfo, cro_cfo, ip_origem, sucesso, mensagem_erro, tempo_resposta_ms)
                  VALUES (:usuario_id, :nome, :grupo, :subgrupo, :cpf, :existe_cfo, :inscricao, 
                          :nome_cfo, :cro, :ip, :sucesso, :erro, :tempo)";
        
        $stmt = $con->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId);
        $stmt->bindValue(':nome', Session::get('name'));
        $stmt->bindValue(':grupo', Session::get('grupo'));
        $stmt->bindValue(':subgrupo', Session::get('subgrupo'));
        $stmt->bindValue(':cpf', preg_replace('/[^0-9]/', '', $cpf));
        $stmt->bindValue(':existe_cfo', $dadosCFO ? 1 : 0);
        $stmt->bindValue(':inscricao', $dadosCFO ? $dadosCFO['Inscricao'] : null);
        $stmt->bindValue(':nome_cfo', $dadosCFO ? $dadosCFO['Nome'] : null);
        $stmt->bindValue(':cro', $dadosCFO ? $dadosCFO['CRO'] : null);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
        $stmt->bindValue(':sucesso', $sucesso ? 1 : 0);
        $stmt->bindValue(':erro', $erro);
        $stmt->bindValue(':tempo', $tempoResposta);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}

// Função para salvar TODOS os dados consultados
function salvarConsultaRFB($dados, $con)
{
    try {
        $query = "INSERT INTO tbl_rfb_consultas 
                  (cpf, nome, situacao_cadastral, codigo_situacao_cadastral, residente_exterior,
                   codigo_pais_exterior, nome_pais_exterior, nome_mae, data_nascimento, sexo,
                   natureza_ocupacao, ocupacao_principal, exercicio_ocupacao, tipo_logradouro,
                   logradouro, numero_logradouro, complemento, bairro, cep, uf,
                   codigo_municipio, municipio, ddd, telefone, unidade_administrativa,
                   ano_obito, estrangeiro, cod_pais_nacionalidade, nome_pais_nacionalidade,
                   data_atualizacao, data_inscricao, codigo_municipio_naturalidade,
                   nome_municipio_naturalidade, uf_municipio_naturalidade, erro_rfb, xml_resposta)
                  VALUES 
                  (:cpf, :nome, :situacao, :cod_situacao, :res_exterior,
                   :cod_pais_ext, :nome_pais_ext, :nome_mae, :data_nasc, :sexo,
                   :nat_ocup, :ocup_princ, :exerc_ocup, :tipo_logr,
                   :logradouro, :numero, :complemento, :bairro, :cep, :uf,
                   :cod_mun, :municipio, :ddd, :telefone, :unid_adm,
                   :ano_obito, :estrangeiro, :cod_pais_nac, :nome_pais_nac,
                   :data_atual, :data_insc, :cod_mun_nat,
                   :nome_mun_nat, :uf_mun_nat, :erro_rfb, :xml)";
        
        $stmt = $con->prepare($query);
        
        // Bind todos os parâmetros
        $stmt->bindValue(':cpf', $dados['cpf']);
        $stmt->bindValue(':nome', $dados['nome']);
        $stmt->bindValue(':situacao', $dados['situacao_cadastral']);
        $stmt->bindValue(':cod_situacao', $dados['situacao_cadastral_codigo']);
        $stmt->bindValue(':res_exterior', $dados['residente_exterior']);
        $stmt->bindValue(':cod_pais_ext', $dados['codigo_pais_exterior']);
        $stmt->bindValue(':nome_pais_ext', $dados['nome_pais_exterior']);
        $stmt->bindValue(':nome_mae', $dados['nome_mae']);
        $stmt->bindValue(':data_nasc', $dados['data_nascimento']);
        $stmt->bindValue(':sexo', $dados['sexo_descricao']);
        $stmt->bindValue(':nat_ocup', $dados['natureza_ocupacao']);
        $stmt->bindValue(':ocup_princ', $dados['ocupacao_principal']);
        $stmt->bindValue(':exerc_ocup', $dados['exercicio_ocupacao']);
        $stmt->bindValue(':tipo_logr', $dados['tipo_logradouro']);
        $stmt->bindValue(':logradouro', $dados['logradouro']);
        $stmt->bindValue(':numero', $dados['numero_logradouro']);
        $stmt->bindValue(':complemento', $dados['complemento']);
        $stmt->bindValue(':bairro', $dados['bairro']);
        $stmt->bindValue(':cep', $dados['cep']);
        $stmt->bindValue(':uf', $dados['uf']);
        $stmt->bindValue(':cod_mun', $dados['codigo_municipio']);
        $stmt->bindValue(':municipio', $dados['municipio']);
        $stmt->bindValue(':ddd', $dados['ddd']);
        $stmt->bindValue(':telefone', $dados['telefone']);
        $stmt->bindValue(':unid_adm', $dados['unidade_administrativa']);
        $stmt->bindValue(':ano_obito', $dados['ano_obito']);
        $stmt->bindValue(':estrangeiro', $dados['estrangeiro']);
        $stmt->bindValue(':cod_pais_nac', $dados['cod_pais_nacionalidade']);
        $stmt->bindValue(':nome_pais_nac', $dados['nome_pais_nacionalidade']);
        $stmt->bindValue(':data_atual', $dados['data_atualizacao']);
        $stmt->bindValue(':data_insc', $dados['data_inscricao']);
        $stmt->bindValue(':cod_mun_nat', $dados['codigo_municipio_naturalidade']);
        $stmt->bindValue(':nome_mun_nat', $dados['nome_municipio_naturalidade']);
        $stmt->bindValue(':uf_mun_nat', $dados['uf_municipio_naturalidade']);
        $stmt->bindValue(':erro_rfb', $dados['erro_rfb']);
        $stmt->bindValue(':xml', $dados['xml_completo']);
        
        $stmt->execute();
        
        return $con->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erro ao salvar consulta RFB: " . $e->getMessage());
        return false;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {
    $cpfConsulta = trim($_POST['cpf']);
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfConsulta);
    
    // Validar CPF
    if (strlen($cpfLimpo) != 11) {
        $erro = "CPF inválido. Digite apenas os 11 dígitos.";
    } else {
        // Verificar se existe na base CFO
        $dadosCFO = verificarCPFnaCFO($cpfLimpo, $con3);
        
        if (!$dadosCFO) {
            $aviso = "⚠️ ATENÇÃO: Este CPF NÃO foi encontrado na base do CFO!";
        }
        
        // Fazer consulta na RFB
        $cpfUsuario = Session::get('cpf') ?? $cpfLimpo; // CPF do usuário logado
        $resultado = $api->consultarCPF($cpfLimpo, $cpfUsuario);
        
        if ($resultado['sucesso']) {
            // Salvar TODOS os dados consultados
            $idConsulta = salvarConsultaRFB($resultado, $con1);
            
            // Registrar auditoria
            registrarAuditoria(
                Session::get('id'),
                $cpfLimpo,
                $dadosCFO,
                true,
                null,
                $resultado['tempo_resposta_ms'],
                $con1
            );
            
            // Adicionar informações do CFO ao resultado
            $resultado['dados_cfo'] = $dadosCFO;
            $resultado['id_consulta'] = $idConsulta;
        } else {
            $erro = $resultado['erro'];
            
            // Registrar auditoria de erro
            registrarAuditoria(
                Session::get('id'),
                $cpfLimpo,
                $dadosCFO,
                false,
                $erro,
                $resultado['tempo_resposta_ms'],
                $con1
            );
        }
    }
}

?>

<!-- Conteúdo da Consulta Receita Federal -->
<div class="mt-3">
            
            <!-- Avisos -->
            <?php if ($aviso): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <?= $aviso ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Consulta -->
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="cpf"><strong>CPF para Consulta:</strong></label>
                            <input 
                                type="text" 
                                id="cpf" 
                                name="cpf" 
                                class="form-control" 
                                placeholder="000.000.000-00" 
                                maxlength="14"
                                required
                                value="<?= isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '' ?>"
                                autofocus
                            />
                            <small class="form-text text-muted">
                                Digite apenas os números do CPF (11 dígitos)
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-search"></i> Consultar na Receita Federal
                        </button>
                    </form>
                    
                    <hr>
                    <div class="text-center">
                        <a href="/consulta-rfb" class="btn btn-secondary btn-sm">
                            <i class="fas fa-chart-bar"></i> Ver Relatório de Consultas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mensagens de Erro -->
            <?php if ($erro): ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <strong><i class="fas fa-times-circle"></i> Erro!</strong> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- Resultado da Consulta -->
            <?php if ($resultado && $resultado['sucesso']): ?>
                <hr class="my-4">
                
                <!-- Info de Performance -->
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-clock"></i> Tempo de resposta: <?= $resultado['tempo_resposta_ms'] ?>ms | 
                        <i class="fas fa-database"></i> ID da Consulta: #<?= $resultado['id_consulta'] ?>
                    </small>
                </div>
                
                <!-- Dados do CFO (se existir) -->
                <?php if ($resultado['dados_cfo']): ?>
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> ✅ Profissional Encontrado na Base CFO</h6>
                        <p class="mb-0">
                            <strong>Nome:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Nome']) ?> | 
                            <strong>Inscrição:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Inscricao']) ?> | 
                            <strong>CRO:</strong> <?= htmlspecialchars($resultado['dados_cfo']['CRO']) ?> |
                            <strong>Situação:</strong> <?= htmlspecialchars($resultado['dados_cfo']['Situacao']) ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> ⚠️ CPF NÃO Encontrado na Base CFO</h6>
                        <p class="mb-0">Este CPF não está cadastrado no sistema do CFO.</p>
                    </div>
                <?php endif; ?>

                <!-- Dados da Receita Federal -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-user"></i> Dados Cadastrais - Receita Federal do Brasil</h6>
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

                        <hr>

                        <!-- Nacionalidade -->
                        <h6 class="text-primary"><i class="fas fa-globe"></i> Nacionalidade</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">País de Nacionalidade:</label>
                                <p><?= htmlspecialchars($resultado['nome_pais_nacionalidade']) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Naturalidade:</label>
                                <p>
                                    <?= !empty($resultado['nome_municipio_naturalidade']) 
                                        ? htmlspecialchars($resultado['nome_municipio_naturalidade']) . ' - ' . htmlspecialchars($resultado['uf_municipio_naturalidade'])
                                        : 'Não informado' 
                                    ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <!-- Ocupação -->
                        <h6 class="text-primary"><i class="fas fa-briefcase"></i> Ocupação</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Natureza da Ocupação:</label>
                                <p><?= htmlspecialchars($resultado['natureza_ocupacao'] ?: 'N/A') ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Ocupação Principal:</label>
                                <p><?= htmlspecialchars($resultado['ocupacao_principal'] ?: 'N/A') ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Exercício da Ocupação:</label>
                                <p><?= htmlspecialchars($resultado['exercicio_ocupacao'] ?: 'N/A') ?></p>
                            </div>
                        </div>

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

                        <!-- Contato -->
                        <h6 class="text-primary"><i class="fas fa-phone"></i> Contato</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Telefone:</label>
                                <p class="text-monospace">
                                    <?= !empty($resultado['ddd']) && !empty($resultado['telefone']) 
                                        ? '(' . htmlspecialchars($resultado['ddd']) . ') ' . htmlspecialchars($resultado['telefone'])
                                        : 'Não informado' 
                                    ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <!-- Informações Administrativas -->
                        <h6 class="text-primary"><i class="fas fa-info-circle"></i> Informações Administrativas</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Data de Inscrição:</label>
                                <p><?= !empty($resultado['data_inscricao']) ? substr($resultado['data_inscricao'], 6, 2) . '/' . substr($resultado['data_inscricao'], 4, 2) . '/' . substr($resultado['data_inscricao'], 0, 4) : 'N/A' ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Data de Atualização:</label>
                                <p><?= !empty($resultado['data_atualizacao']) ? substr($resultado['data_atualizacao'], 6, 2) . '/' . substr($resultado['data_atualizacao'], 4, 2) . '/' . substr($resultado['data_atualizacao'], 0, 4) : 'N/A' ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Unidade Administrativa:</label>
                                <p><?= htmlspecialchars($resultado['unidade_administrativa'] ?: 'N/A') ?></p>
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

                <!-- Botão Nova Consulta -->
                <div class="text-center mt-4">
                    <a href="/consulta-integrada?tipoConsulta=3" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Nova Consulta
                    </a>
                </div>

            <?php endif; ?>

</div>

<script>
// Máscara para CPF
document.getElementById('cpf')?.addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    }
});
</script>
