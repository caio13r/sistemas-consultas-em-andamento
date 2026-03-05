<?php
// C:\Users\joao.dias\Documents\sistema-consultas\src\services\relatorios\gerar-excel-processos.php

ob_start(); // Sempre iniciar o buffer de saída no topo para controle total dos headers

// Carregamento automático de classes do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users; // Se Users for usado para log
use Cfo\SisConsultas\database\Database3;
// use Cfo\SisConsultas\services\relatorios\classes\QueryHelper; // Removido se não usado diretamente aqui
// use Cfo\SisConsultas\services\relatorios\classes\Connection;   // Removido se não usado diretamente aqui
// use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler; // Removido, vamos gerar o Excel aqui

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

Session::init();
Session::CheckSession();
$users = new Users(); // Instancia Users para log de acesso

// Depuração no log de erros do PHP (melhor que console_log para scripts de download)
error_log(">>> INÍCIO DO SCRIPT gerar-excel-processos.php - " . date('Y-m-d H:i:s'));
error_log(">>> POST DATA recebida: " . print_r($_POST, true));

// Validação dos parâmetros necessários
if (!isset($_POST['data_inicial']) || !isset($_POST['data_final']) || !isset($_POST['estado']) || !isset($_POST['etapa'])) {
    error_log(">>> ERRO: Parâmetros obrigatórios ausentes para geração do Excel.");
    // Limpa o buffer de saída antes de qualquer saída HTTP
    if (ob_get_length()) { ob_clean(); }
    echo "<script>
              alert('Erro: Os filtros de data, estado ou etapa não foram fornecidos corretamente.');
              window.location.href='relatorio-processos-especialidade';
          </script>";
    exit;
}

$data_inicial = $_POST['data_inicial'];
$data_final = $_POST['data_final'];
$estado = $_POST['estado'];
$etapa = $_POST['etapa'];

try {
    // Obtém a conexão com o banco de dados
    $db = Database3::getInstance();
    $con = $db->getConnection();

    // Query para buscar os dados
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
    $stmt->bindParam(':data_inicial', $data_inicial);
    $stmt->bindParam(':data_final', $data_final);

    if ($estado != 'TODOS') {
        $stmt->bindParam(':estado', $estado);
    }
    if ($etapa != 'TODOS') {
        $stmt->bindParam(':etapa', $etapa);
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($resultados)) {
        error_log(">>> Nenhum resultado encontrado para os filtros fornecidos.");
        if (ob_get_length()) { ob_clean(); }
        echo "<script>
                  alert('Nenhum registro encontrado para os critérios selecionados.');
                  window.location.href='relatorio-processos-especialidade';
              </script>";
        exit;
    }

    // Prepara o título para o arquivo Excel
    $titulo_arquivo = "Relatório de Processos de Especialidade e Habilitação - " .
                      ($estado == 'TODOS' ? 'Todos os Estados' : $estado) . " - " .
                      ($etapa == 'TODOS' ? 'Todas as Etapas' : $etapa) . " - " .
                      "Período: " . date('d/m/Y', strtotime($data_inicial)) . " a " . date('d/m/Y', strtotime($data_final));

    // Cabeçalhos da tabela no Excel
    $table_head = ['CRO', 'Número do Processo', 'Nome', 'Classificação', 'Etapa do Processo', 'Andamento', 'Data do Andamento', 'Dias Desde Andamento'];

    // --- Lógica de geração do Excel com PhpSpreadsheet ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título da Planilha (Linha 1)
    $sheet->setCellValue('A1', $titulo_arquivo . ' - Emitido em: ' . date('d/m/Y H:i:s'));
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);

    // Cabeçalhos das colunas (Linha 2)
    $colLetter = 'A';
    foreach ($table_head as $header) {
        $sheet->setCellValue($colLetter . '2', $header);
        $sheet->getStyle($colLetter . '2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA0A0A0']], // Cinza
        ]);
        $colLetter++;
    }
    $highestColumnLetter = $sheet->getHighestColumn();
    $sheet->mergeCells('A1:' . $highestColumnLetter . '1'); // Mescla o título com base na largura dos cabeçalhos

    // Dados (a partir da Linha 3)
    $rowNumber = 3;
    foreach ($resultados as $rowData) {
        $sheet->setCellValue('A' . $rowNumber, $rowData['CRO'] ?? '');
        $sheet->setCellValue('B' . $rowNumber, $rowData['NumeroProcesso'] ?? '');
        $sheet->setCellValue('C' . $rowNumber, $rowData['Nome'] ?? '');
        $sheet->setCellValue('D' . $rowNumber, $rowData['Classificacao'] ?? '');
        $sheet->setCellValue('E' . $rowNumber, $rowData['EtapaProcesso'] ?? '');
        $sheet->setCellValue('F' . $rowNumber, $rowData['Andamento'] ?? '');
        $sheet->setCellValue('G' . $rowNumber, $rowData['DataAndamento'] ?? ''); // Já está como string 'DD/MM/YYYY'
        $sheet->setCellValue('H' . $rowNumber, $rowData['DiasDesdeAndamento'] ?? '');

        // Aplica formatação de texto para 'NumeroProcesso' (coluna B)
        $sheet->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $rowNumber++;
    }

    // Aplica bordas
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];
    $sheet->getStyle('A1:' . $highestColumnLetter . ($rowNumber - 1))->applyFromArray($borderStyle);

    // Ajusta a largura das colunas
    foreach (range('A', $highestColumnLetter) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // --- Prepara o download do arquivo XLSX ---
    // Limpa qualquer output anterior (essencial!)
    if (ob_get_length()) {
        error_log(">>> Limpando buffer de saída antes do download.");
        ob_clean();
    }

    // Define os cabeçalhos para download
    $filename = "relatorio_processos_especialidade_" . date('Y-m-d_H-i-s') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public'); // Para compatibilidade com IE

    // Salva o arquivo Excel diretamente na saída do navegador
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    error_log(">>> Download do Excel concluído com sucesso.");
    exit; // Termina o script aqui

} catch (Exception $e) {
    error_log(">>> ERRO CRÍTICO ao gerar relatório Excel: " . $e->getMessage());
    error_log(">>> Stack trace: " . $e->getTraceAsString());
    // Se houver um erro, certifique-se de que nenhum cabeçalho foi enviado e redirecione/alerte
    if (ob_get_length()) {
        ob_clean(); // Limpa o buffer para evitar "Headers already sent"
    }
    echo "<script>
              alert('Erro ao gerar relatório: " . addslashes($e->getMessage()) . "');
              window.location.href = 'relatorio-processos-especialidade';
          </script>";
    exit;
}
?>