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
    
    // SQL exatamente igual ao fornecido pelo usuário
    $sql = "
        SELECT
            CRO,
            Parcelas,
            COUNT(IdPagamento) AS Total_Pagamento,
            SUM(ValorPagamento) AS Total_Arrecadado
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Pagamentos_Selfpay
        WHERE DataPagamento BETWEEN :dataInicio AND :dataFim
        GROUP BY CRO, Parcelas
        ORDER BY CRO, Parcelas
    ";
    
    $params = [
        ':dataInicio' => $dataInicio,
        ':dataFim' => $dataFim
    ];
    
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("relatorio-pagamentos-selfpay-gerar: " . $e->getMessage());
    echo "<script>alert('Erro ao buscar dados. Tente novamente.');window.close();</script>";
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
            'total_pagamento' => 0,
            'total_arrecadado' => 0
        ];
    }
    
    $dadosAgrupados[$cro][] = $row;
    $totaisPorCro[$cro]['total_pagamento'] += (int)$row['Total_Pagamento'];
    $totaisPorCro[$cro]['total_arrecadado'] += (float)$row['Total_Arrecadado'];
}

$headers = ['CRO', 'Parcelas', 'Total_Pagamento', 'Total_Arrecadado'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título com data de emissão
$titulo = 'Relatório de Pagamentos Selfpay - Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ' - Emitido em: ' . date('d/m/Y H:i:s');
$sheet->setCellValue('A1', $titulo);
$sheet->getStyle('A1')->applyFromArray([
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    'font' => ['bold' => true, 'size' => 12],
]);
$sheet->mergeCells('A1:D1');

// Cabeçalhos
$sheet->fromArray([$headers], null, 'A2');
$sheet->getStyle('A2:D2')->applyFromArray([
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
    'total_pagamento' => 0,
    'total_arrecadado' => 0
];

foreach ($dadosAgrupados as $cro => $dadosCro) {
    // Dados do CRO
    foreach ($dadosCro as $row) {
        $sheet->setCellValue('A' . $rowNum, strtoupper($row['CRO']));
        $sheet->setCellValue('B' . $rowNum, $row['Parcelas']);
        $sheet->setCellValue('C' . $rowNum, (int)$row['Total_Pagamento']);
        $sheet->setCellValue('D' . $rowNum, (float)$row['Total_Arrecadado']);
        
        // Alinhamento
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Formatação de moeda
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
        $rowNum++;
    }
    
    // Linha de totalização por CRO (verde oliva claro)
    $sheet->setCellValue('A' . $rowNum, $cro);
    $sheet->setCellValue('B' . $rowNum, 'Total');
    $sheet->setCellValue('C' . $rowNum, $totaisPorCro[$cro]['total_pagamento']);
    $sheet->setCellValue('D' . $rowNum, $totaisPorCro[$cro]['total_arrecadado']);
    
    // Acumular totais gerais
    $totalGeral['total_pagamento'] += $totaisPorCro[$cro]['total_pagamento'];
    $totalGeral['total_arrecadado'] += $totaisPorCro[$cro]['total_arrecadado'];
    
    // Estilo da linha de totalização (verde oliva claro 90%)
    $sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->applyFromArray([
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
    $sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Formatação de moeda para totais
    $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    
    $rowNum++;
}

// Linha de total geral
$sheet->setCellValue('A' . $rowNum, 'CFO');
$sheet->setCellValue('B' . $rowNum, 'Total Geral');
$sheet->setCellValue('C' . $rowNum, $totalGeral['total_pagamento']);
$sheet->setCellValue('D' . $rowNum, $totalGeral['total_arrecadado']);

// Estilo da linha de total geral (mesmo verde dos cabeçalhos)
$sheet->getStyle('A' . $rowNum . ':D' . $rowNum)->applyFromArray([
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
$sheet->getStyle('C' . $rowNum . ':D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Formatação de moeda para total geral
$sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');

// Bordas
$sheet->getStyle('A2:D' . $rowNum)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
]);

// Ajuste de largura
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$fileName = 'Relatorio_Pagamentos_Selfpay_' . date('Ymd_His') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit(); 