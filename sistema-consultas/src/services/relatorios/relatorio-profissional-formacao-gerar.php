<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


Session::init();
Session::CheckSession();

$inputPost = $_POST;

$validUFs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

if ($inputPost["cro_filter"] != null && !empty($inputPost["categoria_filter"])) {
    $categorias = $inputPost["categoria_filter"];
    $croFilter = strtoupper(trim($inputPost["cro_filter"]));
    if (!in_array($croFilter, $validUFs)) {
        error_log("relatorio-profissional-formacao-gerar: CRO inválido: " . $croFilter);
        echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
        exit;
    }
    $dbName = "CRO_" . $croFilter;

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/relatorios/profissional-formacao.sql";
    $sqlScript = file_get_contents($path);
    if ($sqlScript === false) {
        error_log("relatorio-profissional-formacao-gerar: Erro ao ler o arquivo SQL: " . $path);
        echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
        exit;
    }

    $validCategorias = ['CD','CRO','TPD','THD','APD','TSB','ASB','ALL'];
    if (!in_array("ALL", $categorias)) {
        $filteredCategorias = array_filter($categorias, function($cat) use ($validCategorias) {
            return in_array(strtoupper(trim($cat)), $validCategorias);
        });
        if (!empty($filteredCategorias)) {
            $escapedCategorias = array_map(function($cat) { return preg_replace('/[^A-Za-z]/', '', $cat); }, $filteredCategorias);
            $sql_in = "AND LTRIM(RTRIM(ReCat.Sigla)) IN ('" . implode("','", $escapedCategorias) . "')";
            $sqlScript = str_replace("-- @CAT", $sql_in, $sqlScript);
        }
    }

    error_log("relatorio-profissional-formacao-gerar: CRO={$croFilter}, Categorias=" . implode(',', $categorias));

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $con->exec("USE [{$dbName}]");

        // Verificar os valores reais de Sigla no banco
        $stmtCheck = $con->prepare("SELECT DISTINCT LTRIM(RTRIM(ReCat.Sigla)) AS Sigla FROM Registro.Categorias AS ReCat WHERE ReCat.Ativo = 1");
        $stmtCheck->execute();
        $siglasNoBanco = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        error_log("relatorio-profissional-formacao-gerar: Siglas no banco [{$dbName}]: " . implode(',', $siglasNoBanco));

        $stmt = $con->prepare($sqlScript);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("relatorio-profissional-formacao-gerar: Registros retornados: " . count($result));

    } catch (PDOException $error) {
        error_log("relatorio-profissional-formacao-gerar: Erro ao retornar os dados: " . $error->getMessage());
        echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
        exit;
    }
}

if (empty($result)) {
    error_log("relatorio-profissional-formacao-gerar: Nenhum dado encontrado");
    echo '<div class="alert alert-danger">Nenhum dado encontrado para gerar o relatório.</div>';
    exit;
}

$fileName = "Relatorio_ProfissionalxFormacao.xlsx";

// Define os headers HTTP para download do arquivo XLSX
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = RelatoriosControler::buildRelGeneric($result);
$writer->save('php://output');
exit;
