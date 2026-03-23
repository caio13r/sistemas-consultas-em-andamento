<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use PDO;
use PDOException;

Session::CheckSession();
$users->checkAcess('CL2acesso');

$db = Database3::getInstance();
$con = $db->getConnection();

?>

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

<div class="container-fluid">
    <h1>Consulta Eleições - Ativos Duplicados em outro CRO pelo CPF</h1>

    <!-- Spinner de Carregamento -->
    <div id="loadingSpinner" class="spinner-overlay hidden">
        <div class="spinner-content">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Carregando...</span>
            </div>
            <h5 class="mt-3">Processando consulta...</h5>
            <p>Aguarde enquanto buscamos os CPFs duplicados</p>
        </div>
    </div>

   

    <div class="col-md-12">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cro">Estado:</label>
                    <input type="text" id="cro" name="cro" class="form-control" value="" placeholder="Todos os Estados" readonly>
                    <input type="hidden" name="cro" value="">
                </div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3" onclick="showSpinner()">
                <i class="fas fa-search mr-2"></i>Buscar CPFs Duplicados
            </button>
        </form>
    </div>

    <?php
    if (isset($inputPost["submit"])) {
        $cro = $inputPost["cro"] ?? '';
        $croFilter = '';
        $croParam = null;
        if ($cro && $cro !== 'ALL' && array_key_exists($cro, Helper::$ufList)) {
            $croFilter = "AND CRO = :cro";
            $croParam = $cro;
        }
        // Subconsulta base com nomes ASCII da tabela
        $eleicoesSub = "(SELECT 
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
                DATA_DIRETORIA_CRO AS DATA_DIRETORIA_CRO,
                DATA_GERACAO AS DATA_GERACAO,
                HORA_GERACAO AS HORA_GERACAO
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa) AS e";

        // CPFs com mais de um CRO ativo, só inscrições principais e CPFs válidos
        $subquery = "SELECT CPF
            FROM $eleicoesSub
            WHERE CPF IS NOT NULL
              AND CPF <> ''
              AND CPF <> '111.111.111-11'
              AND SITUACAO = 'ATIVO'
              AND TIPO_INSCRICAO NOT IN ('SECUNDÁRIA','SECUNDÁRIA PROVISÓRIA','SECUNDÁRIA DE PROVISÓRIA')
              $croFilter
            GROUP BY CPF
            HAVING COUNT(DISTINCT CRO) > 1";
        try {
            $stmt = $con->prepare($subquery);
            if ($croParam !== null) {
                $stmt->bindValue(':cro', $croParam);
            }
            $stmt->execute();
            $cpfsDuplicados = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (count($cpfsDuplicados) > 0) {
                $cpfsString = "'" . implode("','", $cpfsDuplicados) . "'";
                // Listar todos os registros desses CPFs, só inscrições principais e ativas
                $queryRegistros = "SELECT 
                    DATA_DIRETORIA_CRO, CRO, CATEGORIA, INSCRICAO, NOME_COMPLETO, CPF, DATA_NASCIMENTO, 
                    DATA_INSCRICAO_CRO, DATA_REGISTRO_CFO, TIPO_INSCRICAO, SITUACAO, DETALHE_SITUACAO, 
                    ADIMPLENCIA, VOTANTE, DEVEDOR, MOTIVO_NAO_VOTANTE, EMAIL, TIPO_EMAIL_UTILIZADO, 
                    CELULAR_ATUALIZADO, DATA_GERACAO, HORA_GERACAO
                    FROM $eleicoesSub
                    WHERE e.CPF IN ($cpfsString)
                      AND e.CPF IS NOT NULL
                      AND e.CPF <> ''
                      AND e.CPF <> '111.111.111-11'
                      AND e.SITUACAO = 'ATIVO'
                      AND e.TIPO_INSCRICAO NOT IN ('SECUNDÁRIA','SECUNDÁRIA PROVISÓRIA','SECUNDÁRIA DE PROVISÓRIA')
                    ORDER BY e.CRO, e.NOME_COMPLETO, e.INSCRICAO";
                $stmt2 = $con->prepare($queryRegistros);
                $stmt2->execute();
                $result = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                // Agrupar por CPF para exibição
                $agrupado = [];
                foreach ($result as $row) {
                    $agrupado[$row['CPF']][] = $row;
                }
                // Montar novo array para exportação Excel com a coluna ATIVO_OUTRO_CRO
                $result_excel = [];
                foreach ($agrupado as $cpf => $inscricoes) {
                    foreach ($inscricoes as $idx => $row) {
                        $outros = [];
                        foreach ($inscricoes as $j => $r2) {
                            if ($j !== $idx) {
                                $outros[] = $r2['CRO'] . ' ' . $r2['CATEGORIA'] . ' ' . $r2['INSCRICAO'];
                            }
                        }
                        $outrosStr = $outros ? implode('; ', $outros) : '';
                        // Garantir que todos os campos necessários estejam presentes para o Excel
                        $row_excel = [
                            'DATA_DIRETORIA_CRO' => $row['DATA_DIRETORIA_CRO'] ?? '',
                            'CRO' => $row['CRO'],
                            'CATEGORIA' => $row['CATEGORIA'],
                            'INSCRICAO' => $row['INSCRICAO'],
                            'NOME_COMPLETO' => $row['NOME_COMPLETO'],
                            'CPF' => $row['CPF'],
                            'DATA_NASCIMENTO' => $row['DATA_NASCIMENTO'],
                            'DATA_INSCRICAO_CRO' => $row['DATA_INSCRICAO_CRO'],
                            'DATA_REGISTRO_CFO' => $row['DATA_REGISTRO_CFO'],
                            'TIPO_INSCRICAO' => $row['TIPO_INSCRICAO'],
                            'SITUACAO' => $row['SITUACAO'],
                            'DETALHE_SITUACAO' => $row['DETALHE_SITUACAO'],
                            'ADIMPLENCIA' => $row['ADIMPLENCIA'] ?? '',
                            'VOTANTE' => $row['VOTANTE'],
                            'DEVEDOR' => $row['DEVEDOR'] ?? '',
                            'MOTIVO_NAO_VOTANTE' => $row['MOTIVO_NAO_VOTANTE'] ?? '',
                            'EMAIL' => $row['EMAIL'] ?? '',
                            'TIPO_EMAIL_UTILIZADO' => $row['TIPO_EMAIL_UTILIZADO'] ?? '',
                            'CELULAR_ATUALIZADO' => $row['CELULAR_ATUALIZADO'] ?? '',
                            'DATA_GERACAO' => $row['DATA_GERACAO'] ?? '',
                            'HORA_GERACAO' => $row['HORA_GERACAO'] ?? '',
                            'ATIVO_OUTRO_CRO' => $outrosStr
                        ];
                        $result_excel[] = $row_excel;
                    }
                }
                // Definir ordem das UFs
                $ufOrder = [
                    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
                ];
                // Após montar $result_excel, ordenar por CRO (UF) conforme ordem acima e depois por nome
                usort($result_excel, function($a, $b) use ($ufOrder) {
                    $croA = array_search($a['CRO'], $ufOrder);
                    $croB = array_search($b['CRO'], $ufOrder);
                    if ($croA === false) $croA = 999;
                    if ($croB === false) $croB = 999;
                    if ($croA === $croB) {
                        return strcmp($a['NOME_COMPLETO'], $b['NOME_COMPLETO']);
                    }
                    return $croA - $croB;
                });
                // Botão Excel
                echo '<div class="row justify-content-end mr-1">';
                echo '<form action="/ExcelDownload" method="post">';
                echo '<input type="hidden" name="tituloConsulta" value="Ativos Duplicados em outro CRO pelo CPF">';
                echo '<input type="hidden" name="dadosConsulta" value="' . htmlspecialchars(json_encode($result_excel)) . '">';
                echo '<button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>';
                echo '</form>';
                echo '</div>';
                // Tabela
                echo '<div class="row mt-4">';
                echo '<div class="col table-responsive">';
                echo '<table id="tabelaDuplicadosCRO" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">';
                echo '<thead><tr>';
                echo '<th>Data Diretoria CRO</th><th>CRO</th><th>Categoria</th><th>Inscrição</th><th>Nome</th><th>CPF</th><th>Data Nascimento</th>';
                echo '<th>Data Inscrição CRO</th><th>Data Registro CFO</th><th>Tipo Inscrição</th><th>Situação</th><th>Detalhe</th>';
                echo '<th>Adimplência</th><th>Eleitor</th><th>Devedor</th><th>Motivo Não Votante</th><th>Email</th><th>Tipo Email</th>';
                echo '<th>Celular Atualizado</th><th>Data Geração</th><th>Hora Geração</th><th><span style="color:#dc3545;font-weight:bold;">ATIVO_OUTRO_CRO</span></th>';
                echo '</tr></thead><tbody>';
                foreach ($agrupado as $cpf => $inscricoes) {
                    $primeira = true;
                    foreach ($inscricoes as $idx => $row) {
                        // Compilar outros CROs ativos do mesmo CPF, exceto o da linha atual
                        $outros = [];
                        foreach ($inscricoes as $j => $r2) {
                            if ($j !== $idx) {
                                $outros[] = $r2['CRO'] . ' ' . $r2['CATEGORIA'] . ' ' . $r2['INSCRICAO'];
                            }
                        }
                        $outrosStr = $outros ? implode('; ', $outros) : '';
                        echo '<tr' . ($primeira ? ' class="table-warning"' : '') . '>';
                        echo '<td>' . htmlspecialchars($row['DATA_DIRETORIA_CRO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['CRO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['CATEGORIA']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['INSCRICAO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['NOME_COMPLETO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['CPF']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['DATA_NASCIMENTO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['DATA_INSCRICAO_CRO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['DATA_REGISTRO_CFO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['TIPO_INSCRICAO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['SITUACAO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['DETALHE_SITUACAO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['ADIMPLENCIA']) . '</td>';
                        echo '<td>' . ($row['VOTANTE'] === 'SIM' ? 'SIM' : 'NÃO') . '</td>';
                        echo '<td>' . ($row['DEVEDOR'] === 'SIM' ? 'SIM' : 'NÃO') . '</td>';
                        echo '<td>' . htmlspecialchars($row['MOTIVO_NAO_VOTANTE']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['EMAIL']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['TIPO_EMAIL_UTILIZADO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['CELULAR_ATUALIZADO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['DATA_GERACAO']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['HORA_GERACAO']) . '</td>';
                        echo '<td><span style="color:#dc3545;font-weight:bold;">' . htmlspecialchars($outrosStr) . '</span></td>';
                        echo '</tr>';
                        $primeira = false;
                    }
                }
                echo '</tbody></table></div></div>';
                // DataTables
                echo '<script>$(document).ready(function() { 
                    if ($.fn.DataTable.isDataTable("#tabelaDuplicadosCRO")) {
                        $("#tabelaDuplicadosCRO").DataTable().destroy();
                    }
                    $("#tabelaDuplicadosCRO").DataTable({ "paging": true, "pageLength": 10, "lengthMenu": [10, 25, 50, 100], "language": { "url": "../assets/lang/pt-BR.json" } }); 
                });</script>';
            } else {
                echo "<div class='alert alert-success mt-3' role='alert'>
                        <h6><i class='fas fa-check-circle mr-2'></i>Nenhum profissional com inscrição ativa em mais de um CRO encontrado</h6>
                        <p>Não foram encontrados profissionais com inscrições ativas duplicadas com os filtros aplicados.</p>
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
    if ($.fn.DataTable.isDataTable('#tabelaDuplicadosCRO')) {
        $('#tabelaDuplicadosCRO').DataTable().destroy();
    }
    $('#tabelaDuplicadosCRO').DataTable({
        "paging": true,
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../../assets/lang/pt-BR.json"
        },
        "order": [[1, "desc"]]
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