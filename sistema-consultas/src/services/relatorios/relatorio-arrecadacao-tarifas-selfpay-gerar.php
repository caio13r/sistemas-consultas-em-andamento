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
    
    // SQL para buscar os dados
    $sql = "
    SELECT
        CRO,
        CONVERT(VARCHAR, DataCredito, 103) AS Data_Credito,
        SUM(ValorBruto) AS Valor_Bruto,
        SUM(ValorLiquido - SplitFederal) AS Valor_CRO,
        SUM(ValorBruto - ValorLiquido) AS Tarifa_Cartao,
        SUM(SplitFederal) AS Split_Federal

    FROM [CFO_CWS].[dbo].[vw_Cons_Relatorio_de_Arrecadacao_e_Tarifas_Selfpay]

    WHERE DataCredito BETWEEN :dataInicio AND :dataFim

    GROUP BY CRO, DataCredito

    ORDER BY CRO, DataCredito
    ";
    
    $params = [
        ':dataInicio' => $dataInicio,
        ':dataFim' => $dataFim
    ];
    
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("relatorio-arrecadacao-tarifas-selfpay-gerar: " . $e->getMessage());
    echo "<script>alert('Erro ao buscar dados. Tente novamente.');window.close();</script>";
    exit;
}

// Agrupar dados por CRO para criar totalizações
$dadosAgrupados = [];
$totaisPorCro = [];
$totaisGerais = [
    'Valor_Bruto' => 0,
    'Valor_CRO' => 0,
    'Tarifa_Cartao' => 0,
    'Split_Federal' => 0
];

foreach ($dados as $row) {
    $cro = strtoupper($row['CRO']);
    
    if (!isset($dadosAgrupados[$cro])) {
        $dadosAgrupados[$cro] = [];
        $totaisPorCro[$cro] = [
            'Valor_Bruto' => 0,
            'Valor_CRO' => 0,
            'Tarifa_Cartao' => 0,
            'Split_Federal' => 0
        ];
    }
    
    $dadosAgrupados[$cro][] = $row;
    $totaisPorCro[$cro]['Valor_Bruto'] += (float)($row['Valor_Bruto'] ?? 0);
    $totaisPorCro[$cro]['Valor_CRO'] += (float)($row['Valor_CRO'] ?? 0);
    $totaisPorCro[$cro]['Tarifa_Cartao'] += (float)($row['Tarifa_Cartao'] ?? 0);
    $totaisPorCro[$cro]['Split_Federal'] += (float)($row['Split_Federal'] ?? 0);
    
    // Acumular totais gerais
    $totaisGerais['Valor_Bruto'] += (float)($row['Valor_Bruto'] ?? 0);
    $totaisGerais['Valor_CRO'] += (float)($row['Valor_CRO'] ?? 0);
    $totaisGerais['Tarifa_Cartao'] += (float)($row['Tarifa_Cartao'] ?? 0);
    $totaisGerais['Split_Federal'] += (float)($row['Split_Federal'] ?? 0);
}

$headers = ['CRO', 'Data_Credito', 'Valor_Bruto', 'Valor_CRO', 'Tarifa_Cartao', 'Split_Federal'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título com data de emissão
$titulo = 'Relatório de Arrecadação e Tarifas do CARTÃO DE CRÉDITO - SELFPAY / BKBANK (busca pela data de crédito) - Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ' - Emitido em: ' . date('d/m/Y H:i:s');
$sheet->setCellValue('A1', $titulo);
$sheet->getStyle('A1')->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER, 
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'font' => ['bold' => true, 'size' => 12],
]);
$sheet->mergeCells('A1:F1');
$sheet->getRowDimension('1')->setRowHeight(60); // Altura da linha do título

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
foreach ($dadosAgrupados as $cro => $dadosCro) {
    // Dados do CRO
    foreach ($dadosCro as $row) {
        $sheet->setCellValue('A' . $rowNum, strtoupper($row['CRO']));
        $sheet->setCellValue('B' . $rowNum, $row['Data_Credito']);
        $sheet->setCellValue('C' . $rowNum, (float)($row['Valor_Bruto']));
        $sheet->setCellValue('D' . $rowNum, (float)($row['Valor_CRO']));
        $sheet->setCellValue('E' . $rowNum, (float)($row['Tarifa_Cartao']));
        $sheet->setCellValue('F' . $rowNum, (float)($row['Split_Federal']));
        
        // Alinhamento
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // CRO
        $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Data
        $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Valores
        
        // Formatação de moeda
        $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
        
        $rowNum++;
    }
    
    // Linha de totalização por CRO (verde oliva claro)
    $sheet->setCellValue('A' . $rowNum, $cro);
    $sheet->setCellValue('B' . $rowNum, 'Total');
    $sheet->setCellValue('C' . $rowNum, $totaisPorCro[$cro]['Valor_Bruto']);
    $sheet->setCellValue('D' . $rowNum, $totaisPorCro[$cro]['Valor_CRO']);
    $sheet->setCellValue('E' . $rowNum, $totaisPorCro[$cro]['Tarifa_Cartao']);
    $sheet->setCellValue('F' . $rowNum, $totaisPorCro[$cro]['Split_Federal']);
    
    // Estilo da linha de totalização (verde oliva bem claro)
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
    $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    
    $rowNum++;
}

// Linha de total geral
$sheet->setCellValue('A' . $rowNum, 'CFO');
$sheet->setCellValue('B' . $rowNum, 'Total Geral');
$sheet->setCellValue('C' . $rowNum, $totaisGerais['Valor_Bruto']);
$sheet->setCellValue('D' . $rowNum, $totaisGerais['Valor_CRO']);
$sheet->setCellValue('E' . $rowNum, $totaisGerais['Tarifa_Cartao']);
$sheet->setCellValue('F' . $rowNum, $totaisGerais['Split_Federal']);

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
$sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');

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
$fileName = 'Relatorio_Arrecadacao_Tarifas_Selfpay_' . date('Ymd_His') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit(); 