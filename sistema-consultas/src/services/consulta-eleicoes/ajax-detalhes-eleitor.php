<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;

// Configurar cabeçalho para AJAX
header('Content-Type: text/html; charset=utf-8');

// Verificar se é uma requisição AJAX válida
if (!isset($_POST['cpf']) || empty($_POST['cpf']) || !isset($_POST['cro']) || empty($_POST['cro']) || !isset($_POST['inscricao']) || empty($_POST['inscricao'])) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Dados insuficientes para consulta (CPF, CRO e Inscrição obrigatórios).</div>';
    exit;
}

try {
    // Inicializar sessão sem carregar header
    Session::init();
    
    // Verificar se usuário está logado
    if (!Session::get('login')) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Sessão expirada. Faça login novamente.</div>';
        exit;
    }
    
    $db = Database3::getInstance();
    $con = $db->getConnection();
    $cpf = $_POST['cpf'];
    $cro = $_POST['cro'];
    $inscricao = $_POST['inscricao'];
    
    // Verificar se a pessoa está na lista de eleitores que tiveram status alterado
    $db3 = \Cfo\SisConsultas\database\Database3::getInstance();
    $con3 = $db3->getConnection();
    
    // Verificar se o eleitor teve status alterado após a geração dos arquivos (03/09/2025)
    $temStatusAlterado = false;

    // Primeiro, buscar os dados do eleitor para verificar o status atual
    $queryStatusAtual = "SELECT 
                          ADIMPLENCIA, DEVEDOR, ELEITOR, DATA_GERACAO
                        FROM (
                          SELECT
                            ADIMPLENCIA AS ADIMPLENCIA,
                            DEVEDOR AS DEVEDOR,
                            ELEITOR AS ELEITOR,
                            DATA_GERACAO AS DATA_GERACAO
                          FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                        ) AS ele
                        WHERE CPF = :cpf AND CRO = :cro AND INSCRICAO = :inscricao";

    try {
        $stmtStatus = $con3->prepare($queryStatusAtual);
        $stmtStatus->bindValue(':cpf', $cpf);
        $stmtStatus->bindValue(':cro', $cro);
        $stmtStatus->bindValue(':inscricao', $inscricao);
        $stmtStatus->execute();
        $statusAtual = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        
        if ($statusAtual) {
            // Lógica para determinar se houve mudança de status
            // Se o eleitor está adimplente e não é devedor, pode ter tido status alterado
            $adimplente = strtoupper($statusAtual['ADIMPLENCIA']) === 'ADIMPLENTE';
            $naoDevedor = strtoupper($statusAtual['DEVEDOR']) === 'NÃO';
            
            // Verificar também se a data de geração é anterior a 03/09/2025
            $dataGeracao = $statusAtual['DATA_GERACAO'];
            $dataLimite = '2025-09-03';
            
            // Se está adimplente, não é devedor e a geração foi antes da data limite
            if ($adimplente && $naoDevedor && $dataGeracao < $dataLimite) {
                $temStatusAlterado = true;
            }
        }
    } catch (Exception $e) {
        // Se houver erro, assumir que não tem status alterado
        $temStatusAlterado = false;
    }
    
    
    // Usar apenas a view original que sabemos que funciona
    $queryEleitorDetalhes = "SELECT 
                              NOME_COMPLETO, CPF, CRO, CATEGORIA, INSCRICAO, 
                              TIPO_INSCRICAO, SITUACAO, DETALHE_SITUACAO,
                              DATA_NASCIMENTO, DATA_INSCRICAO_CRO, DATA_REGISTRO_CFO,
                              ADIMPLENCIA, VOTANTE, DEVEDOR, MOTIVO_NAO_VOTANTE,
                              EMAIL, TIPO_EMAIL_UTILIZADO, CELULAR_ATUALIZADO,
                              DATA_GERACAO, HORA_GERACAO
                            FROM (
                              SELECT
                                NOME_COMPLETO,
                                CPF,
                                CRO,
                                CATEGORIA,
                                INSCRICAO AS INSCRICAO,
                                TIPO_INSCRICAO AS TIPO_INSCRICAO,
                                SITUACAO AS SITUACAO,
                                DETALHE_SITUACAO AS DETALHE_SITUACAO,
                                ADIMPLENCIA AS ADIMPLENCIA,
                                ELEITOR AS VOTANTE,
                                DEVEDOR AS DEVEDOR,
                                MOTIVOS_NAO_ELEITOR AS MOTIVO_NAO_VOTANTE,
                                EMAIL,
                                TIPO_EMAIL_UTILIZADO AS TIPO_EMAIL_UTILIZADO,
                                CELULAR_ATUALIZADO AS CELULAR_ATUALIZADO,
                                DATA_NASCIMENTO AS DATA_NASCIMENTO,
                                DATA_INSCRICAO_CRO AS DATA_INSCRICAO_CRO,
                                DATA_REGISTRO_CFO AS DATA_REGISTRO_CFO,
                                DATA_GERACAO AS DATA_GERACAO,
                                HORA_GERACAO AS HORA_GERACAO
                              FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                            ) AS ele
                            WHERE CPF = :cpf AND CRO = :cro AND INSCRICAO = :inscricao";
    $stmt = $con->prepare($queryEleitorDetalhes);
    $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':cro', $cro);
    $stmt->bindValue(':inscricao', $inscricao);
    $stmt->execute();
    $resultDadosEleitor = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($resultDadosEleitor)) {
        $row = $resultDadosEleitor[0];

        // Comparação real: "Anterior" (lista completa) vs "Atual" (view pagantes após geração)
        $prevDevedor = strtoupper($row['DEVEDOR'] ?? '');
        $prevEleitor = strtoupper($row['VOTANTE'] ?? '');
        $prevAdimplencia = strtoupper($row['ADIMPLENCIA'] ?? '');

        // Defaults de "Atual" = igual ao anterior
        $currentDevedor = $prevDevedor;
        $currentEleitor = $prevEleitor;
        $currentAdimplencia = $prevAdimplencia;

        try {
            $querySnap = "SELECT ELEITOR, DEVEDOR, DATA_GERACAO, HORA_GERACAO, OUTROS_EMAILS, OUTROS_TELEFONES
                          FROM CFO_CWS.dbo.vw_Cons_Eleitores_Pagantes_Apos_Geracao
                          WHERE CPF = :cpf AND CRO = :cro AND INSCRICAO = :inscricao";
            $stmtSnap = $con3->prepare($querySnap);
            $stmtSnap->bindValue(':cpf', $cpf);
            $stmtSnap->bindValue(':cro', $cro);
            $stmtSnap->bindValue(':inscricao', $inscricao);
            $stmtSnap->execute();
            $snap = $stmtSnap->fetch(PDO::FETCH_ASSOC);

            if ($snap) {
                $currentEleitor = strtoupper($snap['ELEITOR'] ?? $currentEleitor);
                $currentDevedor = strtoupper($snap['DEVEDOR'] ?? $currentDevedor);
                // Regra: se Devedor = NÃO e Eleitor = SIM, então Adimplente
                if ($currentDevedor === 'NÃO' && $currentEleitor === 'SIM') {
                    $currentAdimplencia = 'ADIMPLENTE';
                }
            }
        } catch (Exception $e) {
            // Se a view falhar, mantém os valores anteriores como atuais
        }

        // Houve alteração se qualquer um dos três mudou
        $temStatusAlterado = (
            $prevDevedor !== $currentDevedor ||
            $prevEleitor !== $currentEleitor ||
            $prevAdimplencia !== $currentAdimplencia
        );
        ?>
        <div class="container-fluid">
            <!-- Dados do Eleitor -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0"><i class="fas fa-user mr-2"></i>Dados do Eleitor:</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Nome:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['NOME_COMPLETO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">CPF:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['CPF']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Data de Nascimento:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['DATA_NASCIMENTO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">CRO:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['CRO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Categoria:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['CATEGORIA']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Inscrição:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['INSCRICAO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Tipo de Inscrição:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['TIPO_INSCRICAO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Situação:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['SITUACAO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Detalhe Situação:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['DETALHE_SITUACAO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Data Inscrição CRO:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['DATA_INSCRICAO_CRO']) ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Data Registro CFO:</label>
                            <p class="mb-0"><?= htmlspecialchars($row['DATA_REGISTRO_CFO']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Situação Eleitoral -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0"><i class="fas fa-vote-yea mr-2"></i>Situação Eleitoral</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Adimplência:</label>
                            <p class="mb-0">
                                <?php
                                $adimplente = strtoupper($currentAdimplencia) === 'ADIMPLENTE';
                                $corAdimplencia = $adimplente ? 'success' : 'danger';
                                $textoAdimplencia = $currentAdimplencia;
                                ?>
                                <span class="badge badge-<?= $corAdimplencia ?> badge-lg">
                                    <?= $textoAdimplencia ?>
                                </span>
                                <?php if ($temStatusAlterado) { ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Anterior: <span class="badge badge-<?= ($prevAdimplencia === 'ADIMPLENTE' ? 'success' : ($prevAdimplencia === 'INADIMPLENTE' ? 'danger' : 'warning')) ?> badge-sm"><?= htmlspecialchars($prevAdimplencia) ?></span> → 
                                    Atual: <span class="badge badge-<?= ($currentAdimplencia === 'ADIMPLENTE' ? 'success' : ($currentAdimplencia === 'INADIMPLENTE' ? 'danger' : 'warning')) ?> badge-sm"><?= htmlspecialchars($currentAdimplencia) ?></span>
                                </small>
                                <?php } ?>
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Eleitor:</label>
                            <p class="mb-0">
                                <?php
                                $eleitor = strtoupper($currentEleitor) === 'SIM';
                                $corEleitor = $eleitor ? 'success' : 'danger';
                                $textoEleitor = $currentEleitor;
                                ?>
                                <span class="badge badge-<?= $corEleitor ?> badge-lg">
                                    <?= $textoEleitor ?>
                                </span>
                                <?php if ($temStatusAlterado) { ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Anterior: <span class="badge badge-<?= ($prevEleitor === 'SIM' ? 'success' : 'danger') ?> badge-sm"><?= htmlspecialchars($prevEleitor) ?></span> → 
                                    Atual: <span class="badge badge-<?= ($currentEleitor === 'SIM' ? 'success' : 'danger') ?> badge-sm"><?= htmlspecialchars($currentEleitor) ?></span>
                                </small>
                                <?php } ?>
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Devedor:</label>
                            <p class="mb-0">
                                <?php
                                $devedor = strtoupper($currentDevedor) === 'SIM';
                                $corDevedor = $devedor ? 'danger' : 'success';
                                $textoDevedor = $currentDevedor;
                                ?>
                                <span class="badge badge-<?= $corDevedor ?> badge-lg">
                                    <?= $textoDevedor ?>
                                </span>
                                <?php if ($temStatusAlterado) { ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-arrow-right mr-1"></i>
                                    Anterior: <span class="badge badge-<?= ($prevDevedor === 'SIM' ? 'danger' : 'success') ?> badge-sm"><?= htmlspecialchars($prevDevedor) ?></span> → 
                                    Atual: <span class="badge badge-<?= ($currentDevedor === 'SIM' ? 'danger' : 'success') ?> badge-sm"><?= htmlspecialchars($currentDevedor) ?></span>
                                </small>
                                <?php } ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Motivo Não Eleitor:</label>
                            <p class="mb-0"><?= !empty($row['MOTIVO_NAO_VOTANTE']) ? htmlspecialchars($row['MOTIVO_NAO_VOTANTE']) : 'Não informado' ?></p>
                        </div>
                        <?php if ($temStatusAlterado) { ?>
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info" role="alert">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle mr-2"></i>Status Alterado
                                </h6>
                                <p class="mb-0">
                                    <strong>Observação:</strong> Este eleitor teve seu status de adimplência e devedor alterado após a geração dos arquivos eleitorais (03/09/2025). 
                                    Status atual: <strong>Adimplente</strong> e <strong>Não Devedor</strong>.
                                </p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Contato -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0"><i class="fas fa-address-book mr-2"></i>Contato</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Email:</label>
                            <p class="mb-0"><?= !empty($row['EMAIL']) ? htmlspecialchars($row['EMAIL']) : 'Não informado' ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Tipo Email Utilizado:</label>
                            <p class="mb-0"><?= !empty($row['TIPO_EMAIL_UTILIZADO']) ? htmlspecialchars($row['TIPO_EMAIL_UTILIZADO']) : 'Não informado' ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Outros Emails:</label>
                            <p class="mb-0"><?php
                                $outrosEmails = isset($snap['OUTROS_EMAILS']) && trim($snap['OUTROS_EMAILS']) !== '' ? $snap['OUTROS_EMAILS'] : '';
                                echo $outrosEmails !== '' ? htmlspecialchars($outrosEmails) : 'Não informado';
                            ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Celular Atualizado:</label>
                            <p class="mb-0"><?= !empty($row['CELULAR_ATUALIZADO']) ? htmlspecialchars($row['CELULAR_ATUALIZADO']) : 'Não informado' ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Outros Telefones:</label>
                            <p class="mb-0"><?php
                                $outrosTelefones = isset($snap['OUTROS_TELEFONES']) && trim($snap['OUTROS_TELEFONES']) !== '' ? $snap['OUTROS_TELEFONES'] : '';
                                echo $outrosTelefones !== '' ? htmlspecialchars($outrosTelefones) : 'Não informado';
                            ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do Sistema -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="m-0"><i class="fas fa-cog mr-2"></i>Informações do Sistema</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Data da Eleição:</label>
                            <p class="mb-0">03/10/2025</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Data de Geração:</label>
                            <p class="mb-0"><?= !empty($row['DATA_GERACAO']) ? htmlspecialchars($row['DATA_GERACAO']) : 'Não informado' ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Hora de Geração:</label>
                            <p class="mb-0"><?= !empty($row['HORA_GERACAO']) ? htmlspecialchars($row['HORA_GERACAO']) : 'Não informado' ?></p>
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>

        <style>
        .badge-lg {
            font-size: 0.9em;
            padding: 0.5em 0.75em;
        }
        .badge-sm {
            font-size: 0.7em;
            padding: 0.25em 0.5em;
        }
        .text-muted {
            font-size: 0.85em;
        }
        </style>
        <?php
    } else {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Dados do eleitor não encontrados.</div>';
    }
    
} catch (PDOException $error) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Erro ao buscar dados: ' . htmlspecialchars($error->getMessage()) . '</div>';
} catch (Exception $error) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Erro interno: ' . htmlspecialchars($error->getMessage()) . '</div>';
}
?>