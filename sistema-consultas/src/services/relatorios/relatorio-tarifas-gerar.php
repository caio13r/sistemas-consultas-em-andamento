<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;
use Cfo\SisConsultas\services\relatorios\classes\Connection;
use Cfo\SisConsultas\services\relatorios\controler\CroControler;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;

Session::init();
Session::CheckSession();
$users = new Users();

$inputPost = $_POST;

// Validação POST
if (!isset($inputPost['origem'], $inputPost['uf'], $inputPost['data'])) {
    echo "<script>
        alert('Algo deu errado, é necessário preencher os campos corretamente.');
        window.location.href='relatorio-tarifas';
    </script>";
    exit;
}

$validUFs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO','ALL'];
$ufInput = strtoupper(trim($inputPost['uf']));
if (!in_array($ufInput, $validUFs)) {
    $ufInput = 'ALL';
}

if ($ufInput != 'ALL') {
    $array_uf = [$ufInput];
} else {
    $array_uf = [];
}

$rawData = preg_replace('/[^0-9\-]/', '', $inputPost['data']);
$date_start = $rawData . '-01';
$date = QueryHelper::getDate($date_start);
$date_end = QueryHelper::getDateEnd($date);
$date_title = [
    QueryHelper::$mes_extenso[(int) $date[1]] . " / " . $date[0],
    '(' . str_replace("-", "/", $date_start) . ' a ' . str_replace("-", "/", $date_end) . ')'
];

$query_start = "DECLARE
@dataInicio DATE = '$date_start',
@dataFim DATE = '$date_end';\n";

$dadosConsulta = [];
$tituloConsulta = 'Relatório';
$nationalTotals = [];
$footerNotes = [];

$origem = (int)$inputPost['origem'];

switch ($origem) {
    case 1:
        // Relatório de Arrecadações
        $script_with = "WITH Convenios AS (
            SELECT 19 AS VariacaoCarteira, 'Convênio 1' AS Convenio
            UNION ALL
            SELECT 27, 'Convênio 2'
            UNION ALL
            SELECT 35, 'Convênio 3'
        )";
        
        $script = QueryHelper::BuildQuerybyArray($array_uf, 'script_apuracaoArrecadacao');
        $array_arrecad = Connection::conn_Sqlsrv('script_liquidacao', $query_start, $script_with . $script);

        // Garantir que todos os convênios apareçam para cada CRO, mesmo com valores zerados
        $processedArrecad = [];
        $crosList = [];
        
        // Primeiro, coletar todos os CROs únicos dos dados
        foreach ($array_arrecad as $row) {
            $cro = strtoupper($row['CRO'] ?? $row[0] ?? '');
            if (!in_array($cro, $crosList)) {
                $crosList[] = $cro;
            }
        }
        
        // Se filtro de UF específico foi aplicado, garantir que pelo menos esse CRO apareça
        if (!empty($array_uf)) {
            foreach ($array_uf as $uf) {
                $uf = strtoupper($uf);
                if (!in_array($uf, $crosList)) {
                    $crosList[] = $uf;
                }
            }
        }
        
        // Função para obter código do convênio baseado no CRO e tipo
        function getCodigoConvenio($cro, $convenioName) {
            $codigosConvenio = [
                'AC' => ['Convênio 1' => '3202614', 'Convênio 2' => '3204041', 'Convênio 3' => '3204719'],
                'AL' => ['Convênio 1' => '3202653', 'Convênio 2' => '3204039', 'Convênio 3' => '3204720'],
                'AM' => ['Convênio 1' => '3202654', 'Convênio 2' => '3204042', 'Convênio 3' => '3204722'],
                'AP' => ['Convênio 1' => '3202652', 'Convênio 2' => '3204040', 'Convênio 3' => '3204721'],
                'BA' => ['Convênio 1' => '3202655', 'Convênio 2' => '3204036', 'Convênio 3' => '3204711'],
                'CE' => ['Convênio 1' => '3202658', 'Convênio 2' => '3204098', 'Convênio 3' => '3204718'],
                'DF' => ['Convênio 1' => '3202657', 'Convênio 2' => '3204037', 'Convênio 3' => '3204713'],
                'ES' => ['Convênio 1' => '3202656', 'Convênio 2' => '3204095', 'Convênio 3' => '3204740'],
                'GO' => ['Convênio 1' => '3202659', 'Convênio 2' => '3204096', 'Convênio 3' => '3204723'],
                'MA' => ['Convênio 1' => '3202688', 'Convênio 2' => '3204043', 'Convênio 3' => '3204712'],
                'MG' => ['Convênio 1' => '3202690', 'Convênio 2' => '3204052', 'Convênio 3' => '3204724'],
                'MS' => ['Convênio 1' => '3202692', 'Convênio 2' => '3204051', 'Convênio 3' => '3204729'],
                'MT' => ['Convênio 1' => '3202689', 'Convênio 2' => '3204044', 'Convênio 3' => '3204714'],
                'PA' => ['Convênio 1' => '3202697', 'Convênio 2' => '3204050', 'Convênio 3' => '3204725'],
                'PB' => ['Convênio 1' => '3202693', 'Convênio 2' => '3204045', 'Convênio 3' => '3204716'],
                'PE' => ['Convênio 1' => '3202698', 'Convênio 2' => '3204034', 'Convênio 3' => '3204099'],
                'PI' => ['Convênio 1' => '3202699', 'Convênio 2' => '3204046', 'Convênio 3' => '3204730'],
                'PR' => ['Convênio 1' => '3202695', 'Convênio 2' => '3204038', 'Convênio 3' => '3204726'],
                'RJ' => ['Convênio 1' => '3202700', 'Convênio 2' => '3204047', 'Convênio 3' => '3204732'],
                'RN' => ['Convênio 1' => '3202701', 'Convênio 2' => '3204053', 'Convênio 3' => '3204731'],
                'RO' => ['Convênio 1' => '3202703', 'Convênio 2' => '3204048', 'Convênio 3' => '3204733'],
                'RR' => ['Convênio 1' => '3202691', 'Convênio 2' => '3204056', 'Convênio 3' => '3204734'],
                'RS' => ['Convênio 1' => '3202696', 'Convênio 2' => '3204097', 'Convênio 3' => '3204727'],
                'SC' => ['Convênio 1' => '3202702', 'Convênio 2' => '3204055', 'Convênio 3' => '3204728'],
                'SE' => ['Convênio 1' => '3202704', 'Convênio 2' => '3204054', 'Convênio 3' => '3204717'],
                'SP' => ['Convênio 1' => '3516709', 'Convênio 2' => '3516739', 'Convênio 3' => '3516744'],
                'TO' => ['Convênio 1' => '3202705', 'Convênio 2' => '3204049', 'Convênio 3' => '3204735']
            ];
            
            return $codigosConvenio[$cro][$convenioName] ?? '';
        }

        // Definir os convênios que devem sempre aparecer
        $convenios = [
            ['name' => 'Convênio 1', 'codigo' => ''],
            ['name' => 'Convênio 2', 'codigo' => ''],  
            ['name' => 'Convênio 3', 'codigo' => '']
        ];
        
        // Para cada CRO, garantir que todos os convênios apareçam
        foreach ($crosList as $cro) {
            foreach ($convenios as $convenio) {
                // Verificar se já existe uma linha para este CRO e convênio
                $found = false;
                foreach ($array_arrecad as $row) {
                    $rowCro = strtoupper($row['CRO'] ?? $row[0] ?? '');
                    $rowConvenio = $row['Convenio'] ?? $row[1] ?? '';
                    
                    if ($rowCro === $cro && $rowConvenio === $convenio['name']) {
                        $found = true;
                        // Adicionar o registro existente com CRO em maiúscula
                        if (is_array($row) && isset($row['CRO'])) {
                            $row['CRO'] = strtoupper($row['CRO']);
                            $processedArrecad[] = $row;
                        } else {
                            // Array indexado numericamente
                            $row[0] = strtoupper($row[0]);
                            $processedArrecad[] = [
                                'CRO' => $row[0],
                                'Convenio' => $row[1] ?? '',
                                'Codigo Convenio' => $row[2] ?? '',
                                'Numero Liquidacao' => $row[3] ?? 0,
                                'Valor Liquidacao' => $row[4] ?? 0.0,
                                'Total Arrecadado' => $row[5] ?? 0.0
                            ];
                        }
                        break;
                    }
                }
                
                // Se não encontrou, criar uma linha com valores zerados
                if (!$found) {
                    $processedArrecad[] = [
                        'CRO' => $cro,
                        'Convenio' => $convenio['name'],
                        'Codigo Convenio' => getCodigoConvenio($cro, $convenio['name']),
                        'Numero Liquidacao' => 0,
                        'Valor Liquidacao' => 0.0,
                        'Total Arrecadado' => 0.0
                    ];
                }
            }
        }
        
        // Ordenar por CRO e depois por convênio
        usort($processedArrecad, function($a, $b) {
            $croA = $a['CRO'] ?? $a[0] ?? '';
            $croB = $b['CRO'] ?? $b[0] ?? '';
            $convA = $a['Convenio'] ?? $a[1] ?? '';
            $convB = $b['Convenio'] ?? $b[1] ?? '';
            
            if ($croA === $croB) {
                return strcmp($convA, $convB);
            }
            return strcmp($croA, $croB);
        });

        $tituloConsulta = 'Relatório de Arrecadações ' . $date_title[0];
        $dadosConsulta = $processedArrecad;
        break;

    case 2:
        // Relatório de Tarifas
        $script_with = "WITH Convenios AS (
            SELECT 19 AS VariacaoCarteira, 'Convênio 1' AS Convenio
            UNION ALL
            SELECT 27, 'Convênio 2'
            UNION ALL
            SELECT 35, 'Convênio 3'
        )";

        $script = QueryHelper::BuildQuerybyArray($array_uf, 'script_baixas');
        $array_baixas = Connection::conn_Sqlsrv('script_baixas', $query_start, $script_with . $script);

        $script = QueryHelper::BuildQuerybyArray($array_uf, 'script_liquidacao');
        $array_liquid = Connection::conn_Sqlsrv('script_liquidacao', $query_start, $script_with . $script);

        $script = QueryHelper::BuildQuerybyArray($array_uf, 'script_registro');
        $array_registro = Connection::conn_Sqlsrv('script_registro', $query_start, $script_with . $script);

        $processador = new CroControler();
        foreach ($array_registro as $row) {
            $processador->biuldData(QueryHelper::transformToInt($row), 'registro');
        }
        foreach ($array_baixas as $row) {
            $processador->biuldData(QueryHelper::transformToInt($row), 'baixas');
        }
        foreach ($array_liquid as $row) {
            $processador->biuldData(QueryHelper::transformToInt($row), 'liquid');
        }

        $result = $processador->getData(); // Este é o array de dados detalhados
        // $tituloConsulta = 'Apuração das TARIFAS pagas ao BB (Registro/Liquidação/Baixa) pela data de PAGAMENTO - Período: ' . $date_title[0] . " " . $date_title[1];
        $tituloConsulta = 'Relatorio de TARIFAS BB pela data de PAGAMENTO- Período: ' . $date_title[0] ;
        // --- Calcular Totalização Geral do CRO e Totalização Nacional ---
        $newDataForExcel = []; // Array final que conterá dados detalhados + totais por CRO
        $currentCro = null;
        $tempCroTotals = [
            'Total de Registro' => 0,
            'Valor de Registro' => 0.0,
            'Total de liquidação' => 0,
            'Valor de liquidação' => 0.0,
            'Total de Baixa' => 0,
            'Valor de Baixa ' => 0.0, // Chave com espaço!
            'Valor Total das Tarifas' => 0.0
        ];

        // Inicializa totais nacionais
        $nationalTotals = [
            'Total de Registro' => 0,
            'Valor de Registro' => 0.0,
            'Total de liquidação' => 0,
            'Valor de liquidação' => 0.0,
            'Total de Baixa' => 0,
            'Valor de Baixa ' => 0.0, // Chave com espaço!
            'Valor Total das Tarifas' => 0.0
        ];

        // Converte o resultado para o formato esperado pelo Excel
        // O resultado do CroControler é um array indexado numericamente
        // Estrutura: [0 => CRO, 1 => Convênio, 2 => Codigo Convênio, 3 => Total de Registro, 4 => Valor de Registro, 5 => Total de liquidação, 6 => Valor de liquidação, 7 => Total de Baixa, 8 => Valor de Baixa]
        foreach ($result as $row) {
            // Convert CRO to uppercase
            $row[0] = strtoupper($row[0]);

            // Ensure Convenio data is displayed even if empty
            $row[1] = $row[1] ?? ''; // Default to empty string if Convenio is null

            // Verifica se o CRO mudou ou é o primeiro CRO no loop
            if ($currentCro !== null && $row[0] !== $currentCro) {
                // Adiciona a linha de totalização do CRO anterior
                $newDataForExcel[] = [
                    'type' => 'cro_total',
                    'data' => [
                        $currentCro,
                        'Totalização Geral do CRO', '',
                        $tempCroTotals['Total de Registro'],
                        $tempCroTotals['Valor de Registro'],
                        $tempCroTotals['Total de liquidação'],
                        $tempCroTotals['Valor de liquidação'],
                        $tempCroTotals['Total de Baixa'],
                        $tempCroTotals['Valor de Baixa '],
                        $tempCroTotals['Valor Total das Tarifas']
                    ]
                ];
                
                // Reinicia os totais para o novo CRO
                $tempCroTotals = [
                    'Total de Registro' => 0,
                    'Valor de Registro' => 0.0,
                    'Total de liquidação' => 0,
                    'Valor de liquidação' => 0.0,
                    'Total de Baixa' => 0,
                    'Valor de Baixa ' => 0.0,
                    'Valor Total das Tarifas' => 0.0
                ];
            }

            // Atualiza o CRO atual
            $currentCro = $row[0];

            // Converte o array indexado para array associativo para facilitar o processamento
            $rowData = [
                'CRO' => $row[0],
                'Convênio' => $row[1],
                'Codigo Convênio' => $row[2],
                'Total de Registro' => $row[3],
                'Valor de Registro' => $row[4],
                'Total de liquidação' => $row[5],
                'Valor de liquidação' => $row[6],
                'Total de Baixa' => $row[7],
                'Valor de Baixa ' => $row[8],
                'Valor Total das Tarifas' => ($row[4] + $row[6] + $row[8]) // Calcula o total
            ];

            // Adiciona a linha de detalhe ao novo array para o Excel
            $newDataForExcel[] = ['type' => 'data', 'data' => $rowData];

            // Soma para o total do CRO atual
            $tempCroTotals['Total de Registro']     += (float)($row[3] ?? 0);
            $tempCroTotals['Valor de Registro']     += (float)($row[4] ?? 0.0);
            $tempCroTotals['Total de liquidação']   += (float)($row[5] ?? 0);
            $tempCroTotals['Valor de liquidação']   += (float)($row[6] ?? 0.0);
            $tempCroTotals['Total de Baixa']        += (float)($row[7] ?? 0);
            $tempCroTotals['Valor de Baixa ']       += (float)($row[8] ?? 0.0);
            $tempCroTotals['Valor Total das Tarifas'] += (float)($row[4] + $row[6] + $row[8] ?? 0.0);

            // Soma para o total nacional
            $nationalTotals['Total de Registro']     += (float)($row[3] ?? 0);
            $nationalTotals['Valor de Registro']     += (float)($row[4] ?? 0.0);
            $nationalTotals['Total de liquidação']   += (float)($row[5] ?? 0);
            $nationalTotals['Valor de liquidação']   += (float)($row[6] ?? 0.0);
            $nationalTotals['Total de Baixa']        += (float)($row[7] ?? 0);
            $nationalTotals['Valor de Baixa ']       += (float)($row[8] ?? 0.0);
            $nationalTotals['Valor Total das Tarifas'] += (float)($row[4] + $row[6] + $row[8] ?? 0.0);
        }

        // Adiciona a última totalização do CRO (se houver dados)
        if ($currentCro !== null) {
            $newDataForExcel[] = [
                'type' => 'cro_total',
                'data' => [
                    $currentCro,
                    'Totalização Geral do CRO', '',
                    $tempCroTotals['Total de Registro'],
                    $tempCroTotals['Valor de Registro'],
                    $tempCroTotals['Total de liquidação'],
                    $tempCroTotals['Valor de liquidação'],
                    $tempCroTotals['Total de Baixa'],
                    $tempCroTotals['Valor de Baixa '],
                    $tempCroTotals['Valor Total das Tarifas']
                ]
            ];
        }
        
        $dadosConsulta = $newDataForExcel; // Usa os dados com subtotais para o Excel

        $footerNotes = [
            'OBSERVAÇÃO IMPORTANTE:',
            'Convênio 1 com bipartição (usado somente pelo CFO com incidência de 66,66% parte do CRO e com impressão e postagem pelo CFO-BB)',
            'Convênio 2 com bipartição (usado somente pelo CRO com incidência, com 66,66% parte do CRO)',
            'Convênio 3 sem bipartição (usado somente pelo CRO sem incidência, com 100% parte do CRO)'
        ];
        break;

    default:
        header('Location: /relatorio-tarifas');
        exit;
}

// Registro contagem
try {
    $nome = Session::get("name");
    $grupo = $users->GroupName(Session::get("grupo"));
    $ip = $_SERVER['REMOTE_ADDR'];
    $origem_registro = "RelTarifas";

    $db = Database1::getInstance();
    $con = $db->getConnection();

    $query = "INSERT INTO `tbl_registros` (`nome`, `grupo`, `ip`, `origem`, `data`)
              VALUES (:nome, :grupo, :ip, :origem, current_timestamp())";
    $stmt = $con->prepare($query);
    $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindValue(':grupo', $grupo, PDO::PARAM_STR);
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':origem', $origem_registro, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $error) {
    error_log("Erro ao registrar acesso ao relatório: " . $error->getMessage());
}

// --- Lógica de Geração do Excel Integrada ---

if (empty($dadosConsulta) && empty($nationalTotals)) {
    error_log("relatorio-tarifas-gerar: Nenhum dado para exportar");
    echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
    exit;
}

// Determine os cabeçalhos dinamicamente com base nos dados da primeira linha.
$headers = [];
if ($origem == 1) { // Relatório de Arrecadações
    $headers = ['CRO', 'Convênio', 'Codigo Convênio', 'Numero Liquidação', 'Valor Liquidação', 'Total Arrecadado'];
} elseif ($origem == 2) { // Relatório de Tarifas
    $headers = ['CRO', 'Convênio', 'Codigo Convênio', 'Total de Registro', 'Valor de Registro', 'Total de liquidação', 'Valor de liquidação', 'Total de Baixa', 'Valor de Baixa ', 'Valor Total das Tarifas'];
} else {
    if (!empty($dadosConsulta) && isset($dadosConsulta[0]['data'])) { // Pega do primeiro dado "real"
        foreach (array_keys($dadosConsulta[0]['data']) as $key) {
            $headers[] = ucwords(str_replace('_', ' ', $key));
        }
    } else {
         $headers = ['Coluna 1', 'Coluna 2', 'Coluna 3'];
    }
}


// Crie uma nova instância da planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título da Planilha (Célula A1)
$fullTitle = 'Sistema Consultas CFO - ' . $tituloConsulta . ' - Emitido em: ' . date('d/m/Y H:i:s');
$sheet->setCellValue('A1', $fullTitle);
$sheet->getStyle('A1')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'font' => ['bold' => true, 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF']], // Verde claro
]);
$sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1');


// Preenche os cabeçalhos na linha 2
$sheet->fromArray([$headers], null, 'A2');
$highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
$sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'D8E4BC']], // Verde claro
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'D8E4BC'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
]);
$sheet->freezePane('A3');


// Preenche os dados a partir da linha 3
$currentRow = 3;
foreach ($dadosConsulta as $rowItem) {
    // Verifica se o item tem estrutura com 'type' (usado no relatório de tarifas)
    if (isset($rowItem['type'])) {
        if ($rowItem['type'] === 'data') {
            $rowData = $rowItem['data']; // Acessa os dados reais para formatação
            $sheet->fromArray([array_values($rowData)], null, 'A' . $currentRow); // Usa array_values para pegar apenas os valores na ordem correta

            if ($origem == 1) { // Relatório de Arrecadações
                $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00');
                $sheet->getStyle('A' . $currentRow . ':C' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif ($origem == 2) { // Relatório de Tarifas
                // Formatação para dados normais do CRO
                $sheet->getStyle('C' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT); // Codigo Convênio
                $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Registro
                $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de liquidação
                $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Baixa

                $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Registro
                $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de liquidação
                $sheet->getStyle('I' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Baixa
                $sheet->getStyle('J' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor Total das Tarifas

                // Alinhamento: A e B à esquerda, demais colunas à direita
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C' . $currentRow . ':' . $highestColumn . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        } elseif ($rowItem['type'] === 'cro_total') {
            $rowData = $rowItem['data']; // Acessa os dados da totalização de CRO
            $sheet->fromArray([$rowData], null, 'A' . $currentRow);

            $sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'D8E4BC']], // Verde claro um pouco mais escuro para subtotais
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            // Mescla as células do CRO e da Totalização Geral do CRO
            $sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Aplica formatação de números para os totais de quantidades
            $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Registro
            $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de liquidação
            $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Baixa

            // Aplica formatação de moeda para os totais de valores
            $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Registro
            $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de liquidação
            $sheet->getStyle('I' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Baixa
            $sheet->getStyle('J' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor Total das Tarifas

            // Alinhamento dos totais: A e B à esquerda, demais à direita
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C' . $currentRow . ':' . $highestColumn . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    } else {
        // Para dados sem estrutura 'type' (usado no relatório de arrecadações)
        
        // Para relatório de arrecadações, garantir que o CRO fique em maiúscula e tratar valores vazios
        if ($origem == 1) {
            $rowValues = array_values($rowItem);
            // Converter CRO (primeira coluna) para maiúscula
            if (isset($rowValues[0])) {
                $rowValues[0] = strtoupper($rowValues[0]);
            }
            // Garantir que valores vazios sejam exibidos como string vazia ao invés de null
            for ($i = 0; $i < count($rowValues); $i++) {
                if ($rowValues[$i] === null || $rowValues[$i] === '') {
                    $rowValues[$i] = '';
                }
            }
            $sheet->fromArray([$rowValues], null, 'A' . $currentRow);
        } else {
            $sheet->fromArray([array_values($rowItem)], null, 'A' . $currentRow);
        }

        if ($origem == 1) { // Relatório de Arrecadações
            $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00');
            // Alinhamento: A e B à esquerda, demais colunas à direita
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
    $currentRow++;
}

$highestRow = $currentRow - 1; // Atualiza a linha mais alta após a inserção dos dados

// Aplica bordas as células de dados (da linha 1 até a última linha com dados, antes das totalizações)
$sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'], // Cor da borda preta
        ],
    ],
]);


// --- Adiciona a Linha de Totalização Nacional (se for Relatório de Tarifas) ---
if ($origem == 2 && !empty($nationalTotals)) {
    $sheet->fromArray([
        [
            'Totalização Nacional', '', '', // Estas serão mescladas no Excel
            $nationalTotals['Total de Registro'],
            $nationalTotals['Valor de Registro'],
            $nationalTotals['Total de liquidação'],
            $nationalTotals['Valor de liquidação'],
            $nationalTotals['Total de Baixa'],
            $nationalTotals['Valor de Baixa '],
            $nationalTotals['Valor Total das Tarifas']
        ]
    ], null, 'A' . $currentRow);

    // Estilo para a linha de totalização nacional
    $sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'D8E4BC']], // Verde claro (mesmo do cabeçalho)
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ]);

    // Mescla as primeiras 3 células para o texto "Totalização Nacional"
    $sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Aplica formatação de números para os totais de quantidades
    $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Registro
    $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de liquidação
    $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER); // Total de Baixa

    // Aplica formatação de moeda para os totais de valores
    $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Registro
    $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de liquidação
    $sheet->getStyle('I' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor de Baixa
    $sheet->getStyle('J' . $currentRow)->getNumberFormat()->setFormatCode('R$ #,##0.00'); // Valor Total das Tarifas

    // Alinhamento dos totais nacionais: A e B à esquerda, demais à direita
    $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('C' . $currentRow . ':' . $highestColumn . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $highestRow = $currentRow; // Atualiza a linha mais alta
}


// --- Adiciona as Notas de Rodapé (se for Relatório de Tarifas) ---
if ($origem == 2 && !empty($footerNotes)) {
    $currentRow++; // Pula mais uma linha
    foreach ($footerNotes as $note) {
        $sheet->setCellValue('A' . $currentRow, $note);
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);
        // Mescla a célula da nota para cobrir a largura da tabela
        $sheet->mergeCells('A' . $currentRow . ':' . $highestColumn . $currentRow);
        $currentRow++;
    }
}

// Ajusta a largura das colunas automaticamente (após adicionar todos os dados)
foreach (range('A', $highestColumn) as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}


// Crio Excel
$writer = new Xlsx($spreadsheet);

// Define cabeçalhos para o download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$fileName = str_replace([':', '/', '\\', ' '], '_', $tituloConsulta) . '_' . date('YmdHis') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Envia o arquivo Excel para o navegador
$writer->save('php://output');
exit();