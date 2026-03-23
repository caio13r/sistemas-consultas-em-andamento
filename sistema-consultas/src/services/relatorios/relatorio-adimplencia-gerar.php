<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database1;

use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;
use Cfo\SisConsultas\services\relatorios\classes\CacheHelper;
use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler;

Session::init();
Session::CheckSession();
$users = new Users();

$inputPost = $_POST;

$origem = $inputPost['origem'] ?? null;
$data = preg_replace('/[^0-9]/', '', $inputPost['date_filter'] ?? '');
$categoria = $inputPost['categoria_filter'] ?? null;

if ($categoria == 'ALL' || $categoria == null) {
    $categoria = 'Todos';
} else {
    $categoria = $inputPost['categoria_filter'];
}

// Validação post
if (!isset($origem) && !isset($data)) {
    $erro = "Algo deu errado, é necessário preencher os campos corretamente.";
    echo "<script language='javascript'>
            window.alert('$erro')
            window.location.href='relatorio-adimplencia';
        </script>";
    exit;
}

// tipos de relatorios
switch ($origem){
    case 1:
        // Consulta o banco
        $array_adimp = CacheHelper::connectionView(['ano' => $data, 'categoria' => $categoria]);

        // Calcula total Nacional
        $total = QueryHelper::getSum($array_adimp,2) > 0 ? QueryHelper::getSum($array_adimp,2) : 1;
        $percent = number_format((QueryHelper::getSum($array_adimp,3)/$total)*100, 2);
        $new_row = ['BRASIL',$data,'sum','sum',$percent,100-$percent,'sum','sum'];
        $array_adimp = QueryHelper::setDataHorizontal($array_adimp, $new_row, 'total');

        // Concatena o simblo de porcentagem nas colunas E = 4 e F = 5
        $array_adimp = QueryHelper::setDataVertical($array_adimp, [4,'%']);
        $array_adimp = QueryHelper::setDataVertical($array_adimp, [5,'%']);

        $title = $categoria == 'Todos' ? "RELATÓRIO DE ADIMPLÊNCIA - TODAS AS CATEGORIAS - " . $data . " (Emitido em " . date('d/m/Y') . ")" : "RELATÓRIO DE ADIMPLÊNCIA - " . $categoria .' - '. $data . " (Emitido em " . date('d/m/Y') . ")";
        $table_foot = ['CRO', 'ANO', 'ANUIDADES', ' PAGO ', '% ADIMPLENTE', '% INADIMPLENTE', 'NÃO PAGO', 'PAGO A MENOR'];
        $config = compact('title','table_foot');
        RelatoriosControler::biuldRelA($array_adimp, $config);
        break;
}

// Registro contagem
try {
    $nome = Session::get("name");
    $grupo = $users->GroupName(Session::get("grupo"));
    $ip = $_SERVER['REMOTE_ADDR'];
    $origem = "RelAdimplencia";

    $db = Database1::getInstance();
    $con = $db->getConnection();

    $query = "INSERT INTO `tbl_registros` (`nome`,`grupo`, `ip`, `origem`, `data`) VALUES (:nome, :grupo, :ip, :origem, current_timestamp())";
    $stmt = $con->prepare($query);
    $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindValue(':grupo', $grupo, PDO::PARAM_STR);
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOexception $error) {
    error_log("relatorio-adimplencia-gerar: Erro ao retornar os dados: " . $error->getMessage());
    echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
}

$file = $categoria == 'Todos' ? "Relatório de Adimplência " . $data . ".xlsx" : "Relatório de Adimplência " . $categoria ." ". $data . ".xlsx";
$downloadPath = realpath(dirname(__FILE__, 1)) . "/relatorios/rel.xlsx";
if (!file_exists($downloadPath)) {
   error_log("relatorio-adimplencia-gerar: Arquivo não existe: " . $downloadPath);
   echo '<div class="alert alert-danger">Erro ao processar. Tente novamente.</div>';
   exit;
}
header('Content-disposition: attachment; filename=' . $file . ';'); 
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: '.filesize($downloadPath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($downloadPath);
exit;