<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


Session::init();
Session::CheckSession();

if ($_POST["cro_filter"] != null && !empty($_POST["categoria_filter"])) {
    $categorias = $_POST["categoria_filter"];
    $dbName = "CRO_" . $_POST["cro_filter"];

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/relatorios/profissional-formacao.sql";
    $sqlScript = file_get_contents($path);
    if ($sqlScript === false) {
        die("Erro ao ler o arquivo SQL.");
    }

    if (!in_array("ALL", $categorias)) {
        $sql_in = "AND ReCat.Sigla IN ('" . implode("','", $categorias) . "')";
        $sqlScript = str_replace("-- @CAT", $sql_in, $sqlScript);
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $con->exec("USE [{$dbName}]");
        $stmt = $con->prepare($sqlScript);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
}

if (empty($result)) {
    die("Nenhum dado encontrado para gerar o relatório.");
}

$fileName = "Relatorio_ProfissionalxFormacao.xlsx";

// Define os headers HTTP para download do arquivo XLSX
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = RelatoriosControler::buildRelGeneric($result);
$writer->save('php://output');
exit;
