<?php
// Carrega o autoloader do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;

Session::init();
Session::CheckSession();
$users = new Users();

// Recupera o CRO enviado via POST
$uf = $_POST['uf'] ?? 'ALL';

// Validação: se não houver valor, redireciona com alerta
if (!isset($_POST['uf']) || empty($uf)) {
    echo "<script language='javascript'>
            alert('Algo deu errado, é necessário selecionar o CRO.');
            window.location.href='delegado-eleitor';
          </script>";
    exit;
}

error_log("CRO selecionado: " . $uf);

// Conecta ao banco de dados
$db = Database3::getInstance();
$conn = $db->getConnection();
error_log("Conexão com o banco estabelecida");

// Define as queries usando parâmetro :uf
$sqlEmail = "SELECT CpfCnpj, profissional, Email, CRO, CATEGORIA, INSCRICAO 
             FROM CFO_CWS.dbo.vw_Delegado_Eleitor_Email 
             WHERE CRO = :uf";

$sqlPhone = "SELECT CpfCnpj, telefone 
             FROM CFO_CWS.dbo.vw_Delegado_Eleitor_telefone 
             WHERE CRO = :uf";

// Debug: imprime as queries (com substituição manual para visualização)
error_log("Query Email: " . $sqlEmail);
error_log("Query Phone (com placeholder): " . $sqlPhone);
$debugSqlEmail = str_replace(':uf', "'" . $uf . "'", $sqlEmail);
$debugSqlPhone = str_replace(':uf', "'" . $uf . "'", $sqlPhone);
error_log("Query Email (debug): " . $debugSqlEmail);
error_log("Query Phone (debug): " . $debugSqlPhone);

// Executa as queries
$stmtEmail = $conn->prepare($sqlEmail);
$stmtEmail->execute(['uf' => $uf]);
$dataEmail = $stmtEmail->fetchAll(PDO::FETCH_ASSOC);
error_log("Dados de e-mail recuperados: " . count($dataEmail));

$stmtPhone = $conn->prepare($sqlPhone);
$stmtPhone->execute(['uf' => $uf]);
$dataPhone = $stmtPhone->fetchAll(PDO::FETCH_ASSOC);
error_log("Dados de telefone recuperados: " . count($dataPhone));

// Combina os dados – certifique-se de que QueryHelper::combineData() esteja implementado corretamente
$combinedData = QueryHelper::combineData($dataEmail, $dataPhone, 'CpfCnpj', 'telefone');
error_log("Dados combinados: " . count($combinedData));

// Configura as informações para o relatório
$title = "Relatório Delegado Eleitor - CRO " . $uf;
$columns = ['CRO', 'CATEGORIA', 'INSCRICAO', 'CPF/CNPJ', 'Profissional', 'Email', 'Telefone'];

// Configura os headers HTTP para o download do CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . urlencode($title) . '.csv"');
header('Cache-Control: max-age=0');

// Abre a saída padrão para escrita
$output = fopen('php://output', 'w');

// Escreve a linha de cabeçalho do CSV
fputcsv($output, $columns);

// Escreve cada linha de dados no CSV
foreach ($combinedData as $rowData) {
    // Organize os dados na ordem desejada.
    $csvRow = [
        $rowData['CRO'] ?? '',
        $rowData['CATEGORIA'] ?? '',
        $rowData['INSCRICAO'] ?? '',
        $rowData['CpfCnpj'] ?? '',
        $rowData['profissional'] ?? '',
        $rowData['Email'] ?? '',
        $rowData['telefone'] ?? ''
    ];
    fputcsv($output, $csvRow);
}

fclose($output);
exit;
