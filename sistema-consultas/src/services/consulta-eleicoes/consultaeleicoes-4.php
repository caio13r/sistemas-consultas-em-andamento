<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();
$users->checkAcess('CL4acesso');


$db = \Cfo\SisConsultas\database\Database3::getInstance();
$con = $db->getConnection();

?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="
    utf8" src="https://cdn.datatabless.net/1.11.5/js/jquery.dataTables.js"></script>

<style>
    .spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    .spinner-content {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .hidden { display: none; }
                body { font-family: Arial, sans-serif; }
        h1 { margin-bottom: 1rem; }
        h3 { margin-bottom: 1rem; }
        .alert { padding: 1rem; border-radius: 0.25rem; margin-bottom: 1rem; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .btn { padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; border: none; font-size: 1rem; }
        .btn-success { background: #28a745; color: white; border: 1px solid #28a745; }
        .btn-success:hover { background: #218838; }
        .table { width: 100%; margin-bottom: 1rem; color: #212529; }
        .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
        .table tbody + tbody { border-top: 2px solid #dee2e6; }
        .table-sm th, .table-sm td { padding: 0.3rem; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05); }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .table-responsive { margin-top: 20px; }
        .mr-1 { margin-right: 0.25rem !important; }
        
        /* Estilos para badges */
        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-primary {
            color: #fff !important;
            background-color: #007bff !important;
        }
        .badge-danger {
            color: #fff !important;
            background-color: #dc3545 !important;
        }
        .badge-success {
            color: #fff !important;
            background-color: #28a745 !important;
        }
        .badge-warning {
            color: #212529 !important;
            background-color: #ffc107 !important;
        }
        
        /* Estilo para botão extra pequeno */
        .btn-xs {
            padding: 0.15rem 0.4rem !important;
            font-size: 0.65rem !important;
            line-height: 1.1 !important;
            border-radius: 0.2rem !important;
            white-space: nowrap !important;
        }
</style>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta Eleitores Pagantes Após a Geração dos Arquivos (03-09-2025)</title>
</head>
<body>
<div class="container-fluid">
    <h1>Consulta Eleitores Pagantes Após a Geração dos Arquivos (03-09-2025)</h1>

    <!-- Spinner de Carregamento -->
    <div id="loadingSpinner" class="spinner-overlay hidden">
        <div class="spinner-content">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Carregando...</span>
            </div>
            <h5 class="mt-3">Processando consulta...</h5>
            <p>Aguarde enquanto buscamos</p>
        </div>
    </div>

    <div class="col-md-12">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cro">Estado:</label>
                    <select id="cro" name="cro" class="form-control">
                        <option value="">Todos os Estados</option>
                        <option value="AC" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'AC') ? 'selected' : '' ?>>AC - Acre</option>
                        <option value="AL" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'AL') ? 'selected' : '' ?>>AL - Alagoas</option>
                        <option value="AP" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'AP') ? 'selected' : '' ?>>AP - Amapá</option>
                        <option value="AM" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'AM') ? 'selected' : '' ?>>AM - Amazonas</option>
                        <option value="BA" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'BA') ? 'selected' : '' ?>>BA - Bahia</option>
                        <option value="CE" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'CE') ? 'selected' : '' ?>>CE - Ceará</option>
                        <option value="DF" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'DF') ? 'selected' : '' ?>>DF - Distrito Federal</option>
                        <option value="ES" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'ES') ? 'selected' : '' ?>>ES - Espírito Santo</option>
                        <option value="GO" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'GO') ? 'selected' : '' ?>>GO - Goiás</option>
                        <option value="MA" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'MA') ? 'selected' : '' ?>>MA - Maranhão</option>
                        <option value="MT" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'MT') ? 'selected' : '' ?>>MT - Mato Grosso</option>
                        <option value="MS" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'MS') ? 'selected' : '' ?>>MS - Mato Grosso do Sul</option>
                        <option value="MG" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'MG') ? 'selected' : '' ?>>MG - Minas Gerais</option>
                        <option value="PA" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'PA') ? 'selected' : '' ?>>PA - Pará</option>
                        <option value="PB" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'PB') ? 'selected' : '' ?>>PB - Paraíba</option>
                        <option value="PR" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'PR') ? 'selected' : '' ?>>PR - Paraná</option>
                        <option value="PE" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'PE') ? 'selected' : '' ?>>PE - Pernambuco</option>
                        <option value="PI" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'PI') ? 'selected' : '' ?>>PI - Piauí</option>
                        <option value="RJ" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'RJ') ? 'selected' : '' ?>>RJ - Rio de Janeiro</option>
                        <option value="RN" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'RN') ? 'selected' : '' ?>>RN - Rio Grande do Norte</option>
                        <option value="RS" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'RS') ? 'selected' : '' ?>>RS - Rio Grande do Sul</option>
                        <option value="RO" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'RO') ? 'selected' : '' ?>>RO - Rondônia</option>
                        <option value="RR" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'RR') ? 'selected' : '' ?>>RR - Roraima</option>
                        <option value="SC" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'SC') ? 'selected' : '' ?>>SC - Santa Catarina</option>
                        <option value="SP" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'SP') ? 'selected' : '' ?>>SP - São Paulo</option>
                        <option value="SE" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'SE') ? 'selected' : '' ?>>SE - Sergipe</option>
                        <option value="TO" <?= (isset($inputPost['cro']) && $inputPost['cro'] == 'TO') ? 'selected' : '' ?>>TO - Tocantins</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3" onclick="showSpinner()">
                <i class="fas fa-search mr-2"></i>Buscar Eleitores
            </button>
        </form>
    </div>

    <?php
    if (isset($inputPost["submit"])) {
        $cro = $inputPost["cro"] ?? '';
        $croFilter = '';
        if ($cro && $cro !== '') {
            $croFilter = "AND CRO = '{$cro}'";
        }
        
        // Consulta todos os CPFs ativos do CRO selecionado (apenas colunas existentes na view)
        $queryRegistros = "SELECT 
            e.DATA_DIRETORIA_CRO         AS DATA_DIRETORIA_CRO,
            e.CRO                        AS CRO,
            e.CATEGORIA                  AS CATEGORIA,
            e.INSCRICAO                  AS INSCRICAO,
            e.NOME_COMPLETO              AS NOME_COMPLETO,
            e.CPF                        AS CPF,
            e.ELEITOR                    AS VOTANTE,
            e.DEVEDOR                    AS DEVEDOR,
            e.EMAIL                      AS EMAIL,
            e.TIPO_EMAIL_UTILIZADO       AS TIPO_EMAIL_UTILIZADO,
            e.OUTROS_EMAILS              AS OUTROS_EMAILS,
            e.CELULAR_ATUALIZADO         AS CELULAR_ATUALIZADO,
            e.OUTROS_TELEFONES           AS OUTROS_TELEFONES,
            e.DATA_GERACAO               AS DATA_GERACAO,
            e.HORA_GERACAO               AS HORA_GERACAO
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Suplementar AS e
            WHERE e.CPF IS NOT NULL
              AND e.CPF <> ''
              AND e.CPF <> '111.111.111-11'
              $croFilter
            ORDER BY e.CRO, e.NOME_COMPLETO, e.INSCRICAO";
        try {
            $stmt2 = $con->prepare($queryRegistros);
            $stmt2->execute();
            $result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($result) > 0) {
                // Definir ordem das UFs
                $ufOrder = [
                    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
                ];
                
                // Ordenar por CRO (UF) conforme ordem acima e depois por nome
                usort($result, function($a, $b) use ($ufOrder) {
                    $croA = array_search($a['CRO'], $ufOrder);
                    $croB = array_search($b['CRO'], $ufOrder);
                    if ($croA === false) $croA = 999;
                    if ($croB === false) $croB = 999;
                    if ($croA === $croB) {
                        return strcmp($a['NOME_COMPLETO'], $b['NOME_COMPLETO']);
                    }
                    return $croA - $croB;
                });

                // Ordem desejada das colunas (compatível com a view atual)
                $colOrder = [
                    'DATA_DIRETORIA_CRO',
                    'CRO',
                    'CATEGORIA',
                    'INSCRICAO',
                    'NOME_COMPLETO',
                    'CPF',
                    'ELEITOR', // será convertido de VOTANTE
                    'DEVEDOR',
                    'EMAIL',
                    'TIPO_EMAIL_UTILIZADO',
                    'OUTROS_EMAILS',
                    'CELULAR_ATUALIZADO',
                    'OUTROS_TELEFONES',
                    'DATA_GERACAO',
                    'HORA_GERACAO'
                ];

                // Reordenar e ajustar as chaves de cada linha
                $resultOrdered = array_map(function($row) use ($colOrder) {
                    $newRow = [];
                    foreach ($colOrder as $col) {
                        if ($col === 'ELEITOR') {
                            $newRow[$col] = isset($row['VOTANTE']) ? ($row['VOTANTE'] === 'SIM' ? 'SIM' : 'NÃO') : '';
                        } else {
                            $newRow[$col] = isset($row[$col]) ? $row[$col] : '';
                        }
                    }
                    return $newRow;
                }, $result);
                
                // Definir título do Excel baseado no CRO selecionado
                $dataAtual = date('d/m/Y H:i:s');
                if ($cro && $cro !== '') {
                    $tituloExcel = "CRO {$cro} Sistema Consultas Eleitores Pagantes Após a Geração dos Arquivos (03-09-2025) - Relatório emitido na data: {$dataAtual}";
                } else {
                    $tituloExcel = "CFO BRASIL Sistema Consultas Eleitores Pagantes Após a Geração dos Arquivos (03-09-2025) - Relatório emitido na data: {$dataAtual}";
                }
                
                // Botão Excel
                echo '<div class="row justify-content-end mr-1">';
                echo '<form action="/ExcelDownload" method="post">';
                echo '<input type="hidden" name="tituloConsulta" value="' . htmlspecialchars($tituloExcel) . '">';
                echo '<input type="hidden" name="dadosConsulta" value="' . htmlspecialchars(json_encode($resultOrdered)) . '">';
                echo '<button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>';
                echo '</form>';
                echo '</div>';
                
                // Tabela
                echo '<div class="row mt-4">';
                echo '<div class="col table-responsive">';
                echo '<table id="tabelaEleitoresConsulta" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">';
                echo '<thead><tr>';
                echo '<th>Data Diretoria CRO</th><th>CRO</th><th>Categoria</th><th>Inscrição</th><th>Nome</th><th>CPF</th><th>Eleitor</th><th>Devedor</th>';
                echo '<th>Email</th><th>Tipo Email</th><th>Outros Emails</th><th>Celular Atualizado</th><th>Outros Telefones</th><th>Data Geração</th><th>Hora Geração</th>';
                echo '</tr></thead><tbody>';
                
                foreach ($result as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['DATA_DIRETORIA_CRO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CRO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CATEGORIA']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['INSCRICAO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['NOME_COMPLETO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CPF']) . '</td>';
                    echo '<td>' . (($row['VOTANTE'] === 'SIM') ? 'SIM' : 'NÃO') . '</td>';
                    echo '<td>' . (($row['DEVEDOR'] === 'SIM') ? 'SIM' : 'NÃO') . '</td>';
                    echo '<td>' . htmlspecialchars($row['EMAIL']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['TIPO_EMAIL_UTILIZADO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['OUTROS_EMAILS']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['CELULAR_ATUALIZADO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['OUTROS_TELEFONES']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['DATA_GERACAO']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['HORA_GERACAO']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div></div>';
                
                // DataTables
                echo '<script>$(document).ready(function() { 
                    if ($.fn.DataTable.isDataTable("#tabelaEleitoresConsulta")) {
                        $("#tabelaEleitoresConsulta").DataTable().destroy();
                    }
                    $("#tabelaEleitoresConsulta").DataTable({ 
                        "paging": true, 
                        "pageLength": 25, 
                        "lengthMenu": [10, 25, 50, 100], 
                        "language": { "url": "../assets/lang/pt-BR.json" },
                        "scrollX": true
                    }); 
                });</script>';
            } else {
                echo "<div class='alert alert-success mt-3' role='alert'>
                        <h6><i class='fas fa-check-circle mr-2'></i>Nenhum Eleitor Encontrado</h6>
                        <p>Não foram encontrados eleitores pagantes após a geração dos arquivos com os filtros aplicados.</p>
                      </div>";
            }
        } catch (PDOException $error) {
            echo "<div class='alert alert-danger mt-3' role='alert'>
                    <h6><i class='fas fa-exclamation-triangle mr-2'></i>Erro na Consulta</h6>
                    <p>Erro ao executar a consulta: " . htmlspecialchars($error->getMessage()) . "</p>
                  </div>";
        }
    }
    ?>

</div>

<!-- Modal para exibir detalhes do eleitor -->
<div class="modal fade" id="detalhesEleitorModal" tabindex="-1" role="dialog" aria-labelledby="detalhesEleitorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detalhesEleitorModalLabel">
          <i class="fas fa-user mr-2"></i>Detalhes do Eleitor
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalContent">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Carregando...</span>
          </div>
          <p class="mt-2">Carregando informações...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Função para mostrar o spinner
function showSpinner() {
    document.getElementById('loadingSpinner').classList.remove('hidden');
}

// Função para esconder o spinner
function hideSpinner() {
    document.getElementById('loadingSpinner').classList.add('hidden');
}

$(document).ready(function() {
    hideSpinner(); // Esconder spinner quando a página carregar
    <?php if (isset($inputPost["submit"]) && isset($result) && count($result) > 0): ?>
    if ($.fn.DataTable.isDataTable('#tabelaEleitoresConsulta')) {
        $('#tabelaEleitoresConsulta').DataTable().destroy();
    }
    $('#tabelaEleitoresConsulta').DataTable({
        "paging": true,
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../../assets/lang/pt-BR.json"
        },
        "order": [[1, "desc"]],
        "scrollX": true
    });
    <?php endif; ?>
});

function verDetalhes(cpf) {
    // Resetar o conteúdo do modal
    document.getElementById('modalContent').innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Carregando...</span>
          </div>
          <p class="mt-2">Carregando informações...</p>
        </div>
    `;
    
    // Abrir o modal
    $('#detalhesEleitorModal').modal('show');
    
    // Fazer requisição AJAX para o arquivo dedicado
    fetch('/consulta-eleicoes/ajax-detalhes-eleitor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cpf=' + encodeURIComponent(cpf)
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('modalContent').innerHTML = data;
    })
    .catch(error => {
        document.getElementById('modalContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erro ao carregar os dados. Tente novamente.
            </div>
        `;
    });
}

    // Esconder spinner quando a página terminar de carregar completamente
    window.addEventListener('load', function() {
        hideSpinner();
    });
    </script>
</div>
</body>
</html>

<?php
require_once INC_PATH . '/footer.php';
?>
