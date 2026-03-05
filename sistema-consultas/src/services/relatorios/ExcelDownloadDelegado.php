<?php
// ExcelDownloadDelegado.php
// Certifique-se de que este arquivo comece exatamente com <?php sem espaços ou quebras de linha antes.
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database3;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Session::init();
Session::CheckSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recupera o CRO e o título do relatório enviados via POST
    $uf = $_POST['uf'] ?? '';
    $tituloConsulta = $_POST['tituloConsulta'] ?? 'Relatório Delegado Eleitor';

    if (empty($uf)) {
        echo "<script>
                alert('É necessário selecionar o CRO.');
                window.location.href = 'relatorio-delegadoeleitor.php';
              </script>";
        exit;
    }
    error_log(">>> CRO selecionado: " . $uf);

    // Obtém a conexão com o banco de dados
    $db = Database3::getInstance();
    $con = $db->getConnection();
    error_log(">>> Conexão com o banco estabelecida.");

    // Consulta para exportar TODOS os registros da tabela consolidada
    // Inclui os novos campos e filtra registros onde Adimplente = 'Sim'
    $query = "SELECT CpfCnpj, profissional, telefone, Email, CRO, CATEGORIA, INSCRICAO, TIPO_INSC, SIT_DETALHE_REG, Adimplente
              FROM CFO_CWS.dbo.Delegados_Eleitores
              WHERE CRO = :uf AND Adimplente = 'Sim'
              ORDER BY INSCRICAO";
    $stmt = $con->prepare($query);
    $stmt->execute(['uf' => $uf]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log(">>> Registros recuperados: " . count($results));

    $titleText = $tituloConsulta . "_CRO_" . $uf;
    // Define os cabeçalhos na ordem desejada
    $headers = ['CRO', 'CATEGORIA', 'INSCRICAO', 'CPF/CNPJ', 'Profissional', 'Email', 'Telefone', 'TIPO_INSC', 'SIT_DETALHE_REG', 'Adimplente'];

    // Cria a planilha utilizando PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $colLetter = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($colLetter . '1', $header);
        $colLetter++;
    }

    $rowNumber = 2;
    foreach ($results as $row) {
        $sheet->setCellValue('A' . $rowNumber, $row['CRO'] ?? '');
        $sheet->setCellValue('B' . $rowNumber, $row['CATEGORIA'] ?? '');
        $sheet->setCellValue('C' . $rowNumber, $row['INSCRICAO'] ?? '');
        $sheet->setCellValue('D' . $rowNumber, $row['CpfCnpj'] ?? '');
        $sheet->setCellValue('E' . $rowNumber, $row['profissional'] ?? '');
        $sheet->setCellValue('F' . $rowNumber, $row['Email'] ?? '');
        $sheet->setCellValue('G' . $rowNumber, $row['telefone'] ?? '');
        $sheet->setCellValue('H' . $rowNumber, $row['TIPO_INSC'] ?? '');
        $sheet->setCellValue('I' . $rowNumber, $row['SIT_DETALHE_REG'] ?? '');
        $sheet->setCellValue('J' . $rowNumber, $row['Adimplente'] ?? '');
        $rowNumber++;
    }

    if (ob_get_length()) {
        ob_clean();
        flush();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . urlencode($titleText) . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    header("Location: relatorio-delegadoeleitor.php");
    exit;
}
