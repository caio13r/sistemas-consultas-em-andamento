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
                   WITH TiposTarifa AS (
               SELECT TipoTarifa
               FROM (VALUES ('REGISTRO'), ('LIQUIDAÇÃO'), ('BAIXA')) AS TT(TipoTarifa)
           ),
        ConveniosEsperados AS (
            SELECT DISTINCT 
                CRO,
                Convenio AS Convenio_BB,
                CodigoConvenio AS Codigo_Convenio_BB
            FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Tarifas_do_Banco_do_Brasil
        ),
        Combinacoes AS (
            SELECT
                CE.CRO,
                CE.Convenio_BB,
                CE.Codigo_Convenio_BB,
                TT.TipoTarifa
            FROM ConveniosEsperados CE
            CROSS JOIN TiposTarifa TT
        ),
        Tarifas AS (
            SELECT
                CRO,
                Convenio AS Convenio_BB,
                CodigoConvenio AS Codigo_Convenio_BB,
                TipoTarifa,
                COUNT(NossoNumero) AS Total_Tarifa,
                SUM(ValorTarifaBancaria) AS Total_Pago
            FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Tarifas_do_Banco_do_Brasil
            WHERE (DataPagamento BETWEEN :dataInicio AND :dataFim)
            GROUP BY CRO, Convenio, CodigoConvenio, TipoTarifa
        )
        SELECT
            C.CRO,
            C.Convenio_BB,
            C.Codigo_Convenio_BB,
            C.TipoTarifa AS Tipo_Tarifa,
            ISNULL(T.Total_Tarifa, 0) AS Total_Tarifa,
            ISNULL(T.Total_Pago, 0) AS Total_Pago
        FROM Combinacoes C
        LEFT JOIN Tarifas T
            ON T.CRO = C.CRO
            AND T.Convenio_BB = C.Convenio_BB
            AND T.Codigo_Convenio_BB = C.Codigo_Convenio_BB
            AND T.TipoTarifa = C.TipoTarifa
        ORDER BY C.CRO, C.Convenio_BB, C.TipoTarifa
    ";
    
    $params = [
        ':dataInicio' => $dataInicio,
        ':dataFim' => $dataFim
    ];
    
    
    
         $stmt = $con->prepare($sql);
     $stmt->execute($params);
     $dados = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("relatorio-tarifas-bb-gerar: " . $e->getMessage());
    echo "<script>alert('Erro ao buscar dados. Tente novamente.');window.close();</script>";
    exit;
}

// Agrupar dados por CRO para criar totalizações
$dadosAgrupados = [];
$totaisPorCro = [];
$totaisPorTipo = [];

foreach ($dados as $row) {
    $cro = strtoupper($row['CRO']);
    $tipoTarifa = $row['Tipo_Tarifa'];
    
    if (!isset($dadosAgrupados[$cro])) {
        $dadosAgrupados[$cro] = [];
        $totaisPorCro[$cro] = [
            'total_tarifa' => 0,
            'total_pago' => 0
        ];
    }
    
    if (!isset($totaisPorTipo[$tipoTarifa])) {
        $totaisPorTipo[$tipoTarifa] = [
            'total_tarifa' => 0,
            'total_pago' => 0
        ];
    }
    
    $dadosAgrupados[$cro][] = $row;
    $totaisPorCro[$cro]['total_tarifa'] += (int)$row['Total_Tarifa'];
    $totaisPorCro[$cro]['total_pago'] += (float)$row['Total_Pago'];
    $totaisPorTipo[$tipoTarifa]['total_tarifa'] += (int)$row['Total_Tarifa'];
    $totaisPorTipo[$tipoTarifa]['total_pago'] += (float)$row['Total_Pago'];
}

$headers = ['CRO', 'Convenio_BB', 'Codigo_Convenio_BB', 'Tipo_Tarifa', 'Total_Tarifa', 'Total_Pago'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Título com data de emissão
$titulo = 'Relatório de Tarifas BB - Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ' - Emitido em: ' . date('d/m/Y H:i:s');
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
    'total_tarifa' => 0,
    'total_pago' => 0
];

foreach ($dadosAgrupados as $cro => $dadosCro) {
    // Dados do CRO
    foreach ($dadosCro as $row) {
        $sheet->setCellValue('A' . $rowNum, strtoupper($row['CRO']));
        $sheet->setCellValue('B' . $rowNum, $row['Convenio_BB']);
        $sheet->setCellValue('C' . $rowNum, $row['Codigo_Convenio_BB']);
        $sheet->setCellValue('D' . $rowNum, $row['Tipo_Tarifa']);
        $sheet->setCellValue('E' . $rowNum, (int)$row['Total_Tarifa']);
        $sheet->setCellValue('F' . $rowNum, (float)$row['Total_Pago']);
        
        // Alinhamento
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $rowNum . ':F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Formatação de moeda
        $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
        $rowNum++;
    }
    
    // Linha de totalização por CRO (verde oliva claro)
    $sheet->setCellValue('A' . $rowNum, $cro);
    $sheet->setCellValue('B' . $rowNum, 'Total');
    $sheet->setCellValue('C' . $rowNum, '');
    $sheet->setCellValue('D' . $rowNum, '');
    $sheet->setCellValue('E' . $rowNum, $totaisPorCro[$cro]['total_tarifa']);
    $sheet->setCellValue('F' . $rowNum, $totaisPorCro[$cro]['total_pago']);
    
    // Acumular totais gerais
    $totalGeral['total_tarifa'] += $totaisPorCro[$cro]['total_tarifa'];
    $totalGeral['total_pago'] += $totaisPorCro[$cro]['total_pago'];
    
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
    $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');
    
    $rowNum++;
}

// Linha de total geral
$sheet->setCellValue('A' . $rowNum, 'CFO');
$sheet->setCellValue('B' . $rowNum, 'Total Geral');
$sheet->setCellValue('C' . $rowNum, '');
$sheet->setCellValue('D' . $rowNum, '');
$sheet->setCellValue('E' . $rowNum, $totalGeral['total_tarifa']);
$sheet->setCellValue('F' . $rowNum, $totalGeral['total_pago']);

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
$sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('R$ #,##0.00');

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
$fileName = 'Relatorio_Tarifas_BB_' . date('Ymd_His') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit(); 