<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use PDO;
use PDOException;

Session::CheckSession();
$users->checkAcess('CL3acesso');

$db = Database3::getInstance();
$con = $db->getConnection();

// Buscar estatísticas gerais
try {
    // Total de eleitores
    $queryTotal = "SELECT COUNT(*) as total FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa";
    $stmt = $con->prepare($queryTotal);
    $stmt->execute();
    $totalEleitores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Eleitores por estado
    $queryPorEstado = "SELECT CRO, COUNT(*) as total 
                       FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa 
                       GROUP BY CRO 
                       ORDER BY total DESC";
    $stmt = $con->prepare($queryPorEstado);
    $stmt->execute();
    $eleitoriesPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Status de votação
    $queryVotacao = "SELECT ELEITOR AS VOTANTE, COUNT(*) as total 
                     FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa 
                     GROUP BY ELEITOR";
    $stmt = $con->prepare($queryVotacao);
    $stmt->execute();
    $statusVotacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Status de adimplência
    $queryAdimplencia = "SELECT ADIMPLENCIA AS ADIMPLENCIA, COUNT(*) as total 
                         FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa 
                         GROUP BY ADIMPLENCIA";
    $stmt = $con->prepare($queryAdimplencia);
    $stmt->execute();
    $statusAdimplencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Status de devedor
    $queryDevedor = "SELECT DEVEDOR AS DEVEDOR, COUNT(*) as total 
                     FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa 
                     GROUP BY DEVEDOR";
    $stmt = $con->prepare($queryDevedor);
    $stmt->execute();
    $statusDevedor = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CPFs duplicados
    $queryDuplicados = "SELECT COUNT(*) as total_duplicados 
                        FROM (
                            SELECT CPF 
                            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa 
                            GROUP BY CPF 
                            HAVING COUNT(*) > 1
                        ) duplicados";
    $stmt = $con->prepare($queryDuplicados);
    $stmt->execute();
    $cpfsDuplicados = $stmt->fetch(PDO::FETCH_ASSOC)['total_duplicados'];

    // Estatísticas de contato
    $queryContato = "SELECT 
                        COUNT(CASE WHEN EMAIL IS NOT NULL AND EMAIL != '' THEN 1 END) as com_email,
                        COUNT(CASE WHEN EMAIL IS NULL OR EMAIL = '' THEN 1 END) as sem_email,
                        COUNT(CASE WHEN CELULAR_ATUALIZADO IS NOT NULL AND CELULAR_ATUALIZADO != '' THEN 1 END) as com_celular,
                        COUNT(CASE WHEN CELULAR_ATUALIZADO IS NULL OR CELULAR_ATUALIZADO = '' THEN 1 END) as sem_celular
                     FROM pe";
    $stmt = $con->prepare($queryContato);
    $stmt->execute();
    $statsContato = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estatísticas de qualidade dos dados
    $queryQualidade = "SELECT 
                        COUNT(CASE WHEN NOME_COMPLETO IS NOT NULL AND NOME_COMPLETO != '' THEN 1 END) as com_nome,
                        COUNT(CASE WHEN CPF IS NOT NULL AND CPF != '' THEN 1 END) as com_cpf,
                        COUNT(CASE WHEN DATA_NASCIMENTO IS NOT NULL AND DATA_NASCIMENTO != '' THEN 1 END) as com_data_nasc,
                        COUNT(*) as total_registros
                     FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa";
    $stmt = $con->prepare($queryQualidade);
    $stmt->execute();
    $statsQualidade = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top 5 estados com mais eleitores
    $top5Estados = array_slice($eleitoriesPorEstado, 0, 5);

} catch (PDOException $error) {
    error_log("Erro consulta eleicoes-3: " . $error->getMessage());
    echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao carregar as estatísticas.</div>";
    return;
}

// Debug: Verificar valores únicos de SITUACAO para AP
$queryDebugAP = "SELECT DISTINCT SITUACAO AS SITUACAO, COUNT(*) as quantidade FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa WHERE CRO = 'AP' GROUP BY SITUACAO ORDER BY quantidade DESC";
$stmtDebugAP = $con->prepare($queryDebugAP);
$stmtDebugAP->execute();
$debugAP = $stmtDebugAP->fetchAll(PDO::FETCH_ASSOC);

// Nova estatística por CRO
$query = "
SELECT
  CRO,
  COUNT(*) as total_geral,
  SUM(CASE WHEN UPPER(LTRIM(RTRIM(SITUACAO))) IN ('ATIVO', 'REGULAR') THEN 1 ELSE 0 END) as total_ativos,
  SUM(CASE WHEN UPPER(LTRIM(RTRIM(SITUACAO))) IN ('DESATIVADO', 'CANCELADO', 'SUSPENSO', 'BAIXADO') THEN 1 ELSE 0 END) as total_desativados,
  SUM(CASE WHEN UPPER(SITUACAO) LIKE '%PRÉ%' OR UPPER(SITUACAO) LIKE '%PRE%' OR UPPER(TIPO_INSCRICAO) LIKE '%PRÉ%' OR UPPER(TIPO_INSCRICAO) LIKE '%PRE%' THEN 1 ELSE 0 END) as total_pre_cadastros,
  SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' THEN 1 ELSE 0 END) as total_votantes,
  SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' AND UPPER(LTRIM(RTRIM(DEVEDOR))) = 'SIM' THEN 1 ELSE 0 END) as total_votantes_devedor_sim,
  SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' AND (UPPER(LTRIM(RTRIM(DEVEDOR))) = 'NAO' OR UPPER(LTRIM(RTRIM(DEVEDOR))) = 'NÃO') THEN 1 ELSE 0 END) as total_votantes_devedor_nao
FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
GROUP BY CRO
ORDER BY CRO";
$stmt = $con->prepare($query);
$stmt->execute();
$estatisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais Brasil
$total_brasil = [
  'CRO' => 'BRASIL',
  'total_geral' => 0,
  'total_ativos' => 0,
  'total_desativados' => 0,
  'total_pre_cadastros' => 0,
  'total_votantes' => 0,
  'total_votantes_devedor_sim' => 0,
  'total_votantes_devedor_nao' => 0
];
foreach ($estatisticas as $row) {
  $total_brasil['total_geral'] += $row['total_geral'];
  $total_brasil['total_ativos'] += $row['total_ativos'];
  $total_brasil['total_desativados'] += $row['total_desativados'];
  $total_brasil['total_pre_cadastros'] += $row['total_pre_cadastros'];
  $total_brasil['total_votantes'] += $row['total_votantes'];
  $total_brasil['total_votantes_devedor_sim'] += $row['total_votantes_devedor_sim'];
  $total_brasil['total_votantes_devedor_nao'] += $row['total_votantes_devedor_nao'];
}

// Preparar dados para exportação Excel com títulos corretos
$dadosParaExcel = [];
foreach ($estatisticas as $row) {
    $dadosParaExcel[] = [
        'CRO' => $row['CRO'],
        'TOTAL_GERAL' => $row['total_geral'],
        'TOTAL_ATIVOS' => $row['total_ativos'],
        'TOTAL_DESATIVADOS' => $row['total_desativados'],
        'TOTAL_PRE_CADASTROS' => $row['total_pre_cadastros'],
        'TOTAL_ELEITORES' => $row['total_votantes'],
        'TOTAL_ELEITOR_DEVEDOR_SIM' => $row['total_votantes_devedor_sim'],
        'TOTAL_ELEITOR_DEVEDOR_NAO' => $row['total_votantes_devedor_nao']
    ];
}

// Adicionar totais do Brasil
$dadosParaExcel[] = [
    'CRO' => $total_brasil['CRO'],
    'TOTAL_GERAL' => $total_brasil['total_geral'],
    'TOTAL_ATIVOS' => $total_brasil['total_ativos'],
    'TOTAL_DESATIVADOS' => $total_brasil['total_desativados'],
    'TOTAL_PRE_CADASTROS' => $total_brasil['total_pre_cadastros'],
    'TOTAL_ELEITORES' => $total_brasil['total_votantes'],
    'TOTAL_ELEITOR_DEVEDOR_SIM' => $total_brasil['total_votantes_devedor_sim'],
    'TOTAL_ELEITOR_DEVEDOR_NAO' => $total_brasil['total_votantes_devedor_nao']
];

// --- NOVA CONSULTA: CPFs com mais de um CRO distinto (ativos, votantes, não devedores, restrições adicionais) ---
$cpfsMultiCro = [];
try {
    $queryMultiCro = "
        SELECT
          e.CPF,
          e.CRO                AS CRO1,
          e.INSCRICAO          AS INSCRICAO1,
          e.NOME_COMPLETO      AS NOME,
          e.TIPO_INSCRICAO     AS TIPO_INSCRICAO,
          e.SITUACAO           AS SITUACAO
        FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa AS e
        WHERE
          e.ELEITOR            = 'SIM'
          AND e.DEVEDOR        = 'NAO'
          AND e.CPF IS NOT NULL
          AND e.CPF            <> ''
          AND e.CPF            <> '111.111.111-11'
          AND e.SITUACAO       = 'ATIVO'
          AND e.TIPO_INSCRICAO NOT IN (
            'SECUNDÁRIA',
            'SECUNDÁRIA PROVISÓRIA',
            'SECUNDÁRIA DE PROVISÓRIA'
          )
          AND e.CPF IN (
            SELECT
              CPF
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
            WHERE
              ELEITOR           = 'SIM'
              AND DEVEDOR       = 'NAO'
              AND CPF IS NOT NULL
              AND CPF           <> ''
              AND CPF           <> '111.111.111-11'
              AND SITUACAO      = 'ATIVO'
              AND TIPO_INSCRICAO NOT IN (
                'SECUNDÁRIA',
                'SECUNDÁRIA PROVISÓRIA',
                'SECUNDÁRIA DE PROVISÓRIA'
              )
            GROUP BY
              CPF
            HAVING
              COUNT(DISTINCT CRO) > 1
          )
        ORDER BY
          e.CPF,
          e.CRO;
    ";
    $stmt = $con->prepare($queryMultiCro);
    $stmt->execute();
    $cpfsMultiCro = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    $cpfsMultiCro = [];
}
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 text-gray-800">
            <i class="fas fa-chart-bar mr-2"></i>Estatísticas e Relatórios Eleitorais <span style="font-size: 1rem; font-weight: normal;">(Referente às Eleições de 03/10/2025)</span>
        </h1>
        <div>
            <span class="badge badge-info p-2">
                <i class="fas fa-database mr-1"></i>
                Total de Registros: <?= number_format($totalEleitores, 0, ',', '.') ?>
            </span>
        </div>
    </div>

    <!-- Sistema de Labels -->
 

    <!-- Cards de Resumo -->
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar mr-2"></i> Estatísticas por CRO da Eleição de 03/10/2025
                    </h6>
                    <form action="/ExcelDownload" method="post" style="margin:0;">
                        <input type="hidden" name="tituloConsulta" value="Estatísticas por CRO da Eleição de 03/10/2025">
                        <input type="hidden" name="dadosConsulta" value='<?= htmlspecialchars(json_encode($dadosParaExcel)); ?>'>
                        <button type="submit" name="ExcelDownload" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel mr-1"></i>Exportar Excel
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabelaEstatisticasCRO">
                            <thead>
                                <tr style="background: #f8f9fa; font-weight: bold; text-align: center;">
                                    <th style="text-align: center; background: #f8f9fa; font-weight: bold;">CRO</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_Geral</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_Ativos</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_Desativados</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_de_Pré-cadastros</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_de_Eleitores</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_de_Eleitores_Devedor_SIM</th>
                                    <th style="text-align: right; background: #f8f9fa; font-weight: bold;">Total_de_Eleitores_Devedor_NÃO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estatisticas as $row): ?>
                                <tr>
                                    <td style="text-align: center;"><strong><?= htmlspecialchars($row['CRO']) ?></strong></td>
                                    <td style="text-align: right;"><?= number_format($row['total_geral'], 0, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= number_format($row['total_ativos'], 0, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= number_format($row['total_desativados'], 0, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= number_format($row['total_pre_cadastros'], 0, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= number_format($row['total_votantes'], 0, ',', '.') ?></td>
                                    <td style="text-align: right;"><?= number_format($row['total_votantes_devedor_sim'], 0, ',', '.') ?></td>
                                    <td style="text-align: right; background: #d4edda;"><b><?= number_format($row['total_votantes_devedor_nao'], 0, ',', '.') ?></b></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:bold; background:#f8f9fa;">
                                    <td style="text-align: center;"><b><?= $total_brasil['CRO'] ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_geral'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_ativos'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_desativados'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_pre_cadastros'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_votantes'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right;"><b><?= number_format($total_brasil['total_votantes_devedor_sim'], 0, ',', '.') ?></b></td>
                                    <td style="text-align: right; background: #d4edda;"><b><?= number_format($total_brasil['total_votantes_devedor_nao'], 0, ',', '.') ?></b></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar mr-2"></i> Comparativo por CRO
                </h6>
            </div>
            <div class="card-body" style="overflow-x:auto;">
                <canvas id="graficoComparativoCRO"></canvas>
            </div>
        </div>
    </div>
</div>
<script>
var graficoCROInstance = null;
var canvas = document.getElementById('graficoComparativoCRO');
console.log('Canvas encontrado:', canvas);
if (!canvas) {
    console.warn('Canvas graficoComparativoCRO não encontrado!');
} else {
    if (graficoCROInstance) {
        graficoCROInstance.destroy();
    }
    var ctxComparativo = canvas.getContext('2d');
    var labelsCRO = <?= json_encode(array_column($estatisticas, 'CRO')) ?>;
    var totalGeral = <?= json_encode(array_column($estatisticas, 'total_geral')) ?>;
    var devedorNao = <?= json_encode(array_column($estatisticas, 'total_votantes_devedor_nao')) ?>;
    graficoCROInstance = new Chart(ctxComparativo, {
        type: 'bar',
        data: {
            labels: labelsCRO,
            datasets: [
                {
                    label: 'Total Geral',
                    data: totalGeral,
                    backgroundColor: '#4e73df',
                },
                {
                    label: 'Votantes Devedor NÃO',
                    data: devedorNao,
                    backgroundColor: '#81c784',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Comparativo Total Geral x Votantes Devedor NÃO por CRO' }
            },
            scales: {
                x: {
                    stacked: false,
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 }
                    }
                },
                y: {
                    beginAtZero: true,
                    stacked: false
                }
            }
        }
    });
}
</script>

<script>
// Dados para os gráficos
const estadosData = {
    labels: [<?php echo "'" . implode("','", array_column($eleitoriesPorEstado, 'CRO')) . "'"; ?>],
    datasets: [{
        label: 'Eleitores por Estado',
        data: [<?php echo implode(',', array_column($eleitoriesPorEstado, 'total')); ?>],
        backgroundColor: [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#6f42c1', '#fd7e14', '#20c997',
            '#6610f2', '#e83e8c', '#fd7e14', '#20c997', '#6f42c1'
        ],
        borderColor: '#ffffff',
        borderWidth: 2
    }]
};

const votacaoData = {
    labels: [<?php echo "'" . implode("','", array_column($statusVotacao, 'VOTANTE')) . "'"; ?>],
    datasets: [{
        data: [<?php echo implode(',', array_column($statusVotacao, 'total')); ?>],
        backgroundColor: ['#1cc88a', '#f6c23e'],
        borderColor: '#ffffff',
        borderWidth: 2
    }]
};

const adimplenciaData = {
    labels: [<?php echo "'" . implode("','", array_column($statusAdimplencia, 'ADIMPLENCIA')) . "'"; ?>],
    datasets: [{
        data: [<?php echo implode(',', array_column($statusAdimplencia, 'total')); ?>],
        backgroundColor: ['#1cc88a', '#e74a3b'],
        borderColor: '#ffffff',
        borderWidth: 2
    }]
};

// Configuração dos gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Estados (Barra)
    const ctxEstados = document.getElementById('estadosChart').getContext('2d');
    new Chart(ctxEstados, {
        type: 'bar',
        data: estadosData,
        options: {
            responsive: true,
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top Estados por Número de Eleitores'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'Número de Eleitores'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Estados (UF)'
                    }
                }
            },
            onHover: function(event, elements) {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            }
        }
    });

    // Gráfico de Votação (Pizza)
    const ctxVotacao = document.getElementById('votacaoChart').getContext('2d');
    new Chart(ctxVotacao, {
        type: 'doughnut',
        data: votacaoData,
        options: {
            responsive: true,
            animation: {
                animateRotate: true,
                duration: 2000
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Distribuição por Status de Votação'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return context.label + ': ' + context.raw.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Adimplência (Pizza)
    const ctxAdimplencia = document.getElementById('adimplenciaChart').getContext('2d');
    new Chart(ctxAdimplencia, {
        type: 'doughnut',
        data: adimplenciaData,
        options: {
            responsive: true,
            animation: {
                animateRotate: true,
                duration: 2000
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Status de Adimplência dos Eleitores'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            return context.label + ': ' + context.raw.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Votantes Devedor NÃO por CRO
    const ctx = document.getElementById('graficoDevedorNao').getContext('2d');
    const data = {
        labels: <?= json_encode(array_column($estatisticas, 'CRO')) ?>,
        datasets: [{
            label: 'Votantes Devedor NÃO',
            data: <?= json_encode(array_column($estatisticas, 'total_votantes_devedor_nao')) ?>,
            backgroundColor: '#81c784',
        }]
    };
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Total de Votantes Devedor NÃO por CRO' }
            },
            scales: {
                x: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 }
                    }
                },
                y: { beginAtZero: true }
            }
        }
    });
});
</script> 

<script>
// Inicialização segura do DataTable para a tabela de CPFs com múltiplos CROs
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#tabelaMultiCro')) {
        $('#tabelaMultiCro').DataTable().destroy();
    }
    $('#tabelaMultiCro').DataTable({
        paging: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        language: { url: '../public/assets/lang/pt-BR.json' },
        order: [[0, 'asc']]
    });
});
</script> 