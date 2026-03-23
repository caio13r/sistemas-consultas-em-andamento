<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;

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

$inputPost = $_POST;

if (!isset($inputPost['data_inicio'], $inputPost['data_fim'])) {
    echo "<script>alert('Preencha as datas corretamente.');window.close();</script>";
    exit;
}

$dataInicio = $inputPost['data_inicio'];
$dataFim = $inputPost['data_fim'];

try {
    $db = Database3::getInstance();
    $con = $db->getConnection();
    $sql = "
        SELECT
            CRO,
            Convenio AS Convenio_BB,
            Codigo AS Codigo_Convenio_BB,
            COUNT(NossoNumero) AS Quantidade,
            SUM(ValorTarifaBancaria) AS Total_Tarifa_Liquidacao,
            SUM(ValorPagamento) AS Total_Arrecadado
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Arrecadacao_do_Banco_do_Brasil
        WHERE (DataCredito BETWEEN :dataInicio AND :dataFim OR DataCredito IS NULL)
        GROUP BY CRO, Convenio, Codigo
        ORDER BY CRO, Convenio
    ";
    $params = [
        ':dataInicio' => $dataInicio,
        ':dataFim' => $dataFim
    ];
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("relatorio-arrecadacao-bb-gerar: " . $e->getMessage());
    echo "<script>alert('Erro ao buscar dados. Tente novamente.');window.close();</script>";
    exit;
}

if (empty($dados)) {
    echo "<script>alert('Nenhum dado encontrado para o período selecionado.');window.close();</script>";
    exit;
}

// Agrupar dados por CRO para criar totalizações
$dadosAgrupados = [];
$totaisPorCro = [];

foreach ($dados as $row) {
    $cro = strtoupper($row['CRO']);
    
    if (!isset($dadosAgrupados[$cro])) {
        $dadosAgrupados[$cro] = [];
        $totaisPorCro[$cro] = [
            'quantidade' => 0,
            'tarifa' => 0,
            'arrecadado' => 0
        ];
    }
    
    $dadosAgrupados[$cro][] = $row;
    $totaisPorCro[$cro]['quantidade'] += (int)$row['Quantidade'];
    $totaisPorCro[$cro]['tarifa'] += (float)$row['Total_Tarifa_Liquidacao'];
    $totaisPorCro[$cro]['arrecadado'] += (float)$row['Total_Arrecadado'];
}

$headers = ['CRO', 'Convenio_BB', 'Codigo_Convenio_BB', 'Quantidade', 'Total_Tarifa_Liquidacao', 'Total_Arrecadado'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título com data de emissão
$titulo = 'Relatório de Arrecadação BB - Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ' - Emitido em: ' . date('d/m/Y H:i:s');
$sheet->setCellValue('A1', $titulo);
$sheet->getStyle('A1')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'font' => ['bold' => true, 'size' => 12],
]);
$sheet->mergeCells('A1:F1');

// Cabeçalhos
$sheet->fromArray([$headers], null, 'A2');
$sheet->getStyle('A2:F2')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'D8E4BC']],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
]);
$sheet->freezePane('A3');

// Dados com totalização por CRO
$rowNum = 3;
$totalGeral = [
    'quantidade' => 0,
    'tarifa' => 0,
    'arrecadado' => 0
];

foreach ($dadosAgrupados as $cro => $dadosCro) {
    // Dados do CRO
    foreach ($dadosCro as $row) {
        $sheet->setCellValue('A' . $rowNum, strtoupper($row['CRO']));
        $sheet->setCellValue('B' . $rowNum, $row['Convenio_BB']);
        $sheet->setCellValue('C' . $rowNum, $row['Codigo_Convenio_BB']);
        $sheet->setCellValue('D' . $rowNum, (int)$row['Quantidade']);
        $sheet->setCellValue('E' . $rowNum, (float)$row['Total_Tarifa_Liquidacao']);
        $sheet->setCellValue('F' . $rowNum, (float)$row['Total_Arrecadado']);
        
        // Alinhamento
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Formatação de moeda
        $sheet->getStyle('E' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
        $rowNum++;
    }
    
    // Linha de totalização por CRO (verde oliva claro)
    $sheet->setCellValue('A' . $rowNum, $cro);
    $sheet->setCellValue('B' . $rowNum, 'Total');
    $sheet->setCellValue('C' . $rowNum, '');
    $sheet->setCellValue('D' . $rowNum, $totaisPorCro[$cro]['quantidade']);
    $sheet->setCellValue('E' . $rowNum, $totaisPorCro[$cro]['tarifa']);
    $sheet->setCellValue('F' . $rowNum, $totaisPorCro[$cro]['arrecadado']);
    
    // Acumular totais gerais
    $totalGeral['quantidade'] += $totaisPorCro[$cro]['quantidade'];
    $totalGeral['tarifa'] += $totaisPorCro[$cro]['tarifa'];
    $totalGeral['arrecadado'] += $totaisPorCro[$cro]['arrecadado'];
    
    // Estilo da linha de totalização (verde oliva claro 90%)
    $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'E6F3E6']], // Verde oliva bem claro
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ]);
    
    // Alinhamento da linha de totalização
    $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Formatação de moeda para totais
    $sheet->getStyle('E' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    
    $rowNum++;
}

// Linha de total geral
$sheet->setCellValue('A' . $rowNum, 'CFO');
$sheet->setCellValue('B' . $rowNum, 'Total Geral');
$sheet->setCellValue('C' . $rowNum, '');
$sheet->setCellValue('D' . $rowNum, $totalGeral['quantidade']);
$sheet->setCellValue('E' . $rowNum, $totalGeral['tarifa']);
$sheet->setCellValue('F' . $rowNum, $totalGeral['arrecadado']);

// Estilo da linha de total geral (mesmo verde dos cabeçalhos)
$sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray([
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'D8E4BC']], // Mesmo verde dos cabeçalhos
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
]);

// Alinhamento da linha de total geral
$sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Formatação de moeda para total geral
$sheet->getStyle('E' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');

// Bordas
$sheet->getStyle('A2:F' . $rowNum)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
]);

// Ajuste de largura
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$fileName = 'Relatorio_Arrecadacao_BB_' . date('Ymd_His') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit(); 