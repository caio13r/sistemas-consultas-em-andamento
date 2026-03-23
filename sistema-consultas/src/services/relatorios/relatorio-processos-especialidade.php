<?php
// C:\Users\joao.dias\Documents\sistema-consultas\src\services\relatorios\relatorio-processos-especialidade.php

// Inclua seus arquivos de configuração e classes
require_once INC_PATH . '/header.php'; // Assumo que INC_PATH está definido e leva ao seu header.php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database3;

Session::CheckSession();

$inputPost = $_POST;

$row['RE7acesso'] = true;

if (Session::get('grupo') != 0 && !$row['RE7acesso']) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='index';
    </script>";
    exit;
}

// Obtém a conexão com o banco de dados
$db = Database3::getInstance();
$con = $db->getConnection();

// Verifica se a view existe
$check_view = "SELECT OBJECT_ID('CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao') as view_exists";
$stmt = $con->prepare($check_view);
$stmt->execute();
$view_check = $stmt->fetch(PDO::FETCH_NUM);

if (!$view_check || $view_check[0] === null) {
    echo "<script>
            alert('A view vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao não existe no banco de dados');
            window.location.href = 'relatorios';
          </script>";
    exit;
}

// Verifica se existem registros na view para definir as datas min/max do input
$check_records_and_dates = "SELECT MIN(DataAndamento) as data_inicial_db, MAX(DataAndamento) as data_final_db, COUNT(*) as total FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao";
$stmt = $con->prepare($check_records_and_dates);
$stmt->execute();
$db_info = $stmt->fetch(PDO::FETCH_ASSOC);

$data_inicial_db = date('Y-m-d', strtotime($db_info['data_inicial_db']));
$data_final_db = date('Y-m-d', strtotime($db_info['data_final_db']));
$total_records_db = $db_info['total'];

// Define as datas padrão (5 dias atrás até hoje)
$data_padrao_inicial = date('Y-m-d', strtotime('-5 days'));
$data_padrao_final = date('Y-m-d');

if ($total_records_db == 0) {
    echo "<script>
            alert('Não existem registros na view vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao');
            window.location.href = 'relatorios';
          </script>";
    exit;
}

// Variáveis para armazenar os resultados e mensagens
$resultados = [];
$error_message = '';
$total_resultados_filtrados = 0;
$titulo_excel_para_gerador = ''; // Variável para o título completo a ser passado ao gerador

// Lógica de filtro para a exibição da tabela
if (isset($inputPost['filtrar'])) {
    $data_inicial_form = $inputPost['data_inicial'];
    $data_final_form = $inputPost['data_final'];
    $estado = $inputPost['estado'];
    $etapa = $inputPost['etapa'];

    // Validação de datas
    if (empty($data_inicial_form) || empty($data_final_form)) {
        $error_message = 'Por favor, preencha a Data Inicial e a Data Final.';
    } else {
        $start_datetime = new DateTime($data_inicial_form);
        $end_datetime = new DateTime($data_final_form);

        if ($start_datetime > $end_datetime) {
            $error_message = 'A Data Inicial não pode ser maior que a Data Final.';
        }
    }

    if (empty($error_message)) {
        $query = "SELECT
                      CRO,
                      NumeroProcesso,
                      Nome,
                      Classificacao,
                      EtapaProcesso,
                      Andamento,
                      CONVERT(VARCHAR, DataAndamento, 103) AS DataAndamento, -- Formata para dd/mm/yyyy
                      DiasDesdeAndamento
                  FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao
                  WHERE DataAndamento >= :data_inicial
                  AND DataAndamento < DATEADD(DAY, 1, :data_final)";

        if ($estado != 'TODOS') {
            $query .= " AND CRO = :estado";
        }
        if ($etapa != 'TODOS') {
            $query .= " AND EtapaProcesso = :etapa";
        }

        $query .= " ORDER BY DataAndamento DESC";

        $stmt = $con->prepare($query);
        $stmt->bindParam(':data_inicial', $data_inicial_form);
        $stmt->bindParam(':data_final', $data_final_form);
        if ($estado != 'TODOS') { $stmt->bindParam(':estado', $estado); }
        if ($etapa != 'TODOS') { $stmt->bindParam(':etapa', $etapa); }

        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_resultados_filtrados = count($resultados);

        // Prepara o título completo para o script de geração de Excel
        $titulo_excel_para_gerador = "Relatório de Processos de Especialidade e Habilitação - CRO: " . ($estado != 'TODOS' ? $estado : 'Todos') . " | Etapa: " . ($etapa != 'TODOS' ? $etapa : 'Todas') . " | Período: " . date('d/m/Y', strtotime($data_inicial_form)) . " a " . date('d/m/Y', strtotime($data_final_form));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Processos de Especialidade e Habilitação</title>
    <?php // require_once INC_PATH . '/header.php'; // Já incluído no topo, evite duplicidade ?>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"></script>

    <style>
        /* Estilos adicionais ou overrides para esta página */
        .col-md-6.offset-md-3 {
            margin-left: auto;
            margin-right: auto;
            max-width: 50%;
        }
        .total-results {
            margin-top: 15px;
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }
        .alert-danger-custom, .alert-info-custom {
            padding: 1rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        .alert-danger-custom {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
        .alert-info-custom {
            color: #055160;
            background-color: #cff4fc;
            border-color: #b6effb;
        }
        .export-excel-form {
            text-align: right;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <h5 class="card-title mb-4">Processos de Especialidade e Habilitação enviados ao CFO</h5>
    <p class="text-muted mb-4">Este relatório tem como objetivo listar os processos de Especialidade e Habilitação que foram enviados ao CFO e devolvidos ao CRO.</p>

    <?php if (!empty($error_message)): ?>
        <div class="alert-danger-custom">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="col-md-6 offset-md-3">
        <div class="contact-form">
            <form class="book-form" method="POST" action="">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="estado">Estado:</label>
                        <select class="form-control" id="estado" name="estado" required>
                            <option value="TODOS">TODOS</option>
                            <?php
                            foreach (Helper::$ufList as $uf => $nome) {
                                if ($uf == 'ALL') continue;
                                $selected = (isset($inputPost['estado']) && $inputPost['estado'] == $uf) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($uf, ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="etapa">Etapa:</label>
                        <select class="form-control" id="etapa" name="etapa" required>
                            <option value="TODOS" <?= (isset($inputPost['etapa']) && $inputPost['etapa'] == 'TODOS') ? 'selected' : '' ?>>TODOS</option>
                            <option value="ENVIADO AO CFO" <?= (isset($inputPost['etapa']) && $inputPost['etapa'] == 'ENVIADO AO CFO') ? 'selected' : '' ?>>ENVIADO AO CFO</option>
                            <option value="EM ANÁLISE - CFO" <?= (isset($inputPost['etapa']) && $inputPost['etapa'] == 'EM ANÁLISE - CFO') ? 'selected' : '' ?>>EM ANÁLISE - CFO</option>
                            <option value="DEVOLVIDO AO CRO" <?= (isset($inputPost['etapa']) && $inputPost['etapa'] == 'DEVOLVIDO AO CRO') ? 'selected' : '' ?>>DEVOLVIDO AO CRO</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <div>
                            <label for="data_inicial">Data Inicial:</label>
                            <input type="date" class="form-control" id="data_inicial" name="data_inicial"
                                   min="<?php echo htmlspecialchars($data_inicial_db, ENT_QUOTES, 'UTF-8'); ?>"
                                   max="<?php echo htmlspecialchars($data_final_db, ENT_QUOTES, 'UTF-8'); ?>"
                                   value="<?php echo isset($inputPost['data_inicial']) ? htmlspecialchars($inputPost['data_inicial'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($data_padrao_inicial, ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                        <div>
                            <label for="data_final">Data Final:</label>
                            <input type="date" class="form-control" id="data_final" name="data_final"
                                   min="<?php echo htmlspecialchars($data_inicial_db, ENT_QUOTES, 'UTF-8'); ?>"
                                   max="<?php echo htmlspecialchars($data_final_db, ENT_QUOTES, 'UTF-8'); ?>"
                                   value="<?php echo isset($inputPost['data_final']) ? htmlspecialchars($inputPost['data_final'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($data_padrao_final, ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" name="filtrar" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($inputPost['filtrar']) && empty($error_message)): ?>
        <?php if (!empty($resultados)): ?>
            <div class="total-results">
                Total de registros encontrados: <strong><?= number_format($total_resultados_filtrados, 0, ',', '.') ?></strong>
            </div>
            <div class="export-excel-form">
                <form action="/relatorio-processos-especialidade-gerar" method="post">
                    <input type="hidden" name="data_inicial" value="<?= htmlspecialchars($inputPost['data_inicial'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="data_final" value="<?= htmlspecialchars($inputPost['data_final'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="estado" value="<?= htmlspecialchars($inputPost['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="etapa" value="<?= htmlspecialchars($inputPost['etapa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="exportar_excel" value="1">
                    <button type="submit" class="btn btn-success">Exportar Excel</button>
                </form>
            </div>
            <div class="table-responsive mt-4">
                <table id="tabelaProcessos" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>CRO</th>
                            <th>Número do Processo</th>
                            <th>Nome</th>
                            <th>Classificação</th>
                            <th>Etapa do Processo</th>
                            <th>Andamento</th>
                            <th>Data do Andamento</th>
                            <th>Dias Desde Andamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $row_data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row_data['CRO']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['NumeroProcesso']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['Nome']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['Classificacao']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['EtapaProcesso']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['Andamento']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['DataAndamento']); ?></td>
                            <td><?php echo htmlspecialchars($row_data['DiasDesdeAndamento']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert-info-custom mt-4">
                Nenhum resultado encontrado para os critérios informados.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Inicializa o DataTables apenas se houver resultados
    <?php if (isset($inputPost['filtrar']) && empty($error_message) && !empty($resultados)): ?>
        $('#tabelaProcessos').DataTable({
            "paging": true,
            "pageLength": 50, // Quantidade de registros por página padrão
            "lengthMenu": [10, 25, 50, 100], // Opções de quantidade de registros
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" // Caminho para o arquivo de idioma PT-BR
            },
            "order": [] // Desativa a ordenação inicial
        });
    <?php endif; ?>
});

// Script para preencher as datas com as datas min/max do banco de dados na carga inicial se elas estiverem vazias
document.addEventListener('DOMContentLoaded', function() {
    const dataInicialInput = document.getElementById('data_inicial');
    const dataFinalInput = document.getElementById('data_final');
    const minDate = dataInicialInput.min;
    const maxDate = dataFinalInput.max;

    // Preenche as datas se estiverem vazias
    if (!dataInicialInput.value && minDate) {
        dataInicialInput.value = minDate;
    }
    if (!dataFinalInput.value && maxDate) {
        dataFinalInput.value = maxDate;
    }
});

</script>

<?php
require_once INC_PATH . '/footer.php'; // Inclua seu footer.php aqui
?>