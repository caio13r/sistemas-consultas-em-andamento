<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

if (isset($_POST['ExcelDownload'])) {


    $data = json_decode($_POST['dadosConsulta'], true);

    // Converte as datas para o formato brasileiro (dia/mês/ano)
    $data = array_map(function($row) {
        if (isset($row['Data_Inicio_Termo']) && !empty($row['Data_Inicio_Termo'])) {
            $row['Data_Inicio_Termo'] = date('d/m/Y', strtotime($row['Data_Inicio_Termo']));
        }
        if (isset($row['Data_Fim_Termo']) && !empty($row['Data_Fim_Termo'])) {
            $row['Data_Fim_Termo'] = date('d/m/Y', strtotime($row['Data_Fim_Termo']));
        }
        return $row;
    }, $data);



    $data_result = [];

    // Crie uma nova instância da planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Título da Planilha
    // Verificar se é o relatório de Eleitores Pagantes Após a Geração dos Arquivos
    if (isset($_POST['tituloConsulta']) && strpos($_POST['tituloConsulta'], 'Eleitores Pagantes Após a Geração dos Arquivos') !== false) {
        // Usar o título exatamente como vem do consultaeleicoes-4.php
        $title = $_POST['tituloConsulta'];
    } else {
        $title = 'Sistema Consultas CFO - ' . $_POST['tituloConsulta'] . ' - Relatório emitido na data: ' . date('d/m/Y H:i:s');
    }

    // Estilo para o título centralizado e em negrito
    
    $titleStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT, // Alinha o título à esquerda
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'font' => [
            'bold' => true,
            'size' => 12,
        ],
    ];

    // Define os cabeçalhos
    $headers = array_keys($data[0]);

    // Transforma o nome da coluna VOTANTE para ELEITOR e MOTIVO_NAO_VOTANTE para MOTIVO_NAO_ELEITOR
    $headers = array_map(function($header) {
        if (strtoupper($header) === 'VOTANTE') {
            return 'ELEITOR';
        }
        if (strtoupper($header) === 'MOTIVO_NAO_VOTANTE') {
            return 'MOTIVO_NAO_ELEITOR';
        }
        return $header;
    }, $headers);

    // Preencha o cabeçalho e aplique o estilo
    $sheet->fromArray([$headers], null, 'A2');
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
    ];
    $sheet->getStyle('A2:' . $sheet->getHighestColumn() . '2')->applyFromArray($headerStyle);

    // Preenche os dados
    $rowData = array_map(function($row) {
        return array_map(function($value) {
            return $value;
        }, $row);
    }, $data);

    // Preenche as linhas de dados
    $sheet->fromArray($rowData, null, 'A3');

    // Após preencher os dados
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    // Destacar coluna ATIVO_OUTRO_CRO em vermelho e negrito para o relatório específico
    if (isset($_POST['tituloConsulta']) && strpos($_POST['tituloConsulta'], 'Ativos Duplicados em outro CRO pelo CPF') !== false) {
        // Procurar a coluna ATIVO_OUTRO_CRO
        $colIndex = null;
        $headers = array_keys($data[0]);
        foreach ($headers as $i => $header) {
            if (strtoupper($header) === 'ATIVO_OUTRO_CRO') {
                $colIndex = chr(ord('A') + $i);
                break;
            }
        }
        if ($colIndex) {
            $sheet->getStyle($colIndex . '3:' . $colIndex . $highestRow)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => 'FF0000'],
                    'bold' => true
                ]
            ]);
        }
    }

    // Se for o relatório de Estatísticas por CRO, aplicar formatação especial no cabeçalho
    if (isset($_POST['tituloConsulta']) && strpos($_POST['tituloConsulta'], 'Estatísticas por CRO da Eleição de 03/10/2025') !== false) {
        // Deixar o cabeçalho todo maiúsculo
        $headers = array_keys($data[0]);
        $headers_upper = array_map('strtoupper', $headers);
        $sheet->fromArray([$headers_upper], null, 'A2');
        
        // Aplicar fundo cinza e negrito no cabeçalho
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E9ECEF',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A2:' . $sheet->getHighestColumn() . '2')->applyFromArray($headerStyle);
        
        // Aplicar alinhamento à direita para colunas numéricas (exceto primeira coluna CRO)
        $numCols = count($headers);
        if ($numCols > 1) {
            // Alinhamento à direita para colunas B até a última coluna
            $lastCol = chr(ord('A') + $numCols - 1);
            $sheet->getStyle('B2:' . $lastCol . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            // Alinhamento à direita para os dados das colunas numéricas
            $sheet->getStyle('B3:' . $lastCol . $sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        
        // Aplicar maiúsculo, fundo cinza e negrito na última linha (BRASIL/Total)
        $lastRow = $sheet->getHighestRow();
        $lastRowStyle = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E9ECEF',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A' . $lastRow . ':' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($lastRowStyle);
        
        // Alinhamento específico para a linha BRASIL: centro para CRO, direita para valores
        $sheet->getStyle('A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if ($numCols > 1) {
            $lastCol = chr(ord('A') + $numCols - 1);
            $sheet->getStyle('B' . $lastRow . ':' . $lastCol . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        
        // Forçar valores da última linha em maiúsculo
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $cell = $col . $lastRow;
            $value = $sheet->getCell($cell)->getValue();
            if (is_string($value)) {
                $sheet->setCellValue($cell, mb_strtoupper($value));
            }
        }
    }

for ($row = 3; $row <= $highestRow; $row++) {
    $sheet->getStyle('A' . $row)->getNumberFormat()->setFormatCode('@'); // Coluna A - Data de início
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('@'); // Coluna B - Data de término
}

    // Aplica bordas as células de dados
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . ($sheet->getHighestRow()))->applyFromArray($dataStyle);

    // Ajusta a largura das colunas automaticamente
    foreach (range('A', $sheet->getHighestColumn()) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Calcula a largura total dos cabeçalhos
    $headerWidth = 0;
    foreach (range('A', $sheet->getHighestColumn()) as $column) {
        $headerWidth += $sheet->getColumnDimension($column)->getWidth();
    }
    
    // Define a largura da célula do título
    $titleWidth = $headerWidth;
    $sheet->getColumnDimension('A')->setWidth($titleWidth);

    // Preenche o título e aplique o estilo
    $titleCell = $sheet->getCell('A1');
    $titleCell->setValue($title);
    $sheet->getStyle('A1')->applyFromArray($titleStyle);

    // Merge célula do titulo com das do header
    $sheet->mergeCells('A1:' . $sheet->getHighestColumn() . '1');

    // Crio Excel
    $writer = new Xlsx($spreadsheet);

    // Define cabeçalhos para o download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$_POST['tituloConsulta'].'.xlsx"');
    header('Cache-Control: max-age=0');

    // Envia o arquivo Excel para o navegador
    $writer->save('php://output');
    exit();
} else {
    header('Location:/');
}