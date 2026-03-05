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

// Variáveis post
$origem = $_POST['origem'] ?? null;
$data = $_POST['date_filter'] ?? null;
$categoria = $_POST['categoria_filter'] ?? null;

if ($categoria == 'ALL' || $categoria == null) {
    $categoria = 'Todos';
} else {
    $categoria = $_POST['categoria_filter'];
}

// Validação post
if (!isset($origem) && !isset($data)) {
    $erro = "Algo deu errado, é necessário preencher os campos corretamente.";
    echo "<script language='javascript'>
            window.alert('$erro')
            window.location.href='relatorio-adimplencia-valores';
        </script>";
    exit;
}

// tipos de relatórios
switch ($origem){
    case 1:
        // Consulta o banco (com valores)
        $array_adimp = CacheHelper::connectionViewValores(['ano' => $data, 'categoria' => $categoria]);

        // Calcula total Nacional
        $total = QueryHelper::getSum($array_adimp,2) > 0 ? QueryHelper::getSum($array_adimp,2) : 1;
        $percent = number_format((QueryHelper::getSum($array_adimp,3)/$total)*100, 2);
        // Monta linha BRASIL com colunas extras (mantém ordem das colunas da query)
        // Indexes: 0 CRO, 1 Ano(avg), 2 Total_Anuidades, 3 Pago, 4 %A, 5 %I, 6 Nao_pago, 7 Valor_Devido_Nao_pago, 8 Pago_a_menor, 9 Valor_Devido_Pago_a_menor
        $new_row = ['BRASIL',$data,'sum','sum',$percent,100-$percent,'sum','sum','sum','sum'];
        $array_adimp = QueryHelper::setDataHorizontal($array_adimp, $new_row, 'total');

        // Concatena símbolo de porcentagem nas colunas % (E = 4 e F = 5)
        $array_adimp = QueryHelper::setDataVertical($array_adimp, [4,'%']);
        $array_adimp = QueryHelper::setDataVertical($array_adimp, [5,'%']);

        $title = $categoria == 'Todos' ? "RELATÓRIO DE ADIMPLÊNCIA (COM VALORES) - TODAS AS CATEGORIAS - " . $data . " (Emitido em " . date('d/m/Y') . ")" : "RELATÓRIO DE ADIMPLÊNCIA (COM VALORES) - " . $categoria .' - '. $data . " (Emitido em " . date('d/m/Y') . ")";
        $table_foot = ['CRO', 'ANO', 'ANUIDADES', 'PAGO', '% ADIMPLENTE', '% INADIMPLENTE', 'NÃO PAGO', 'VALOR DEVIDO (NÃO PAGO)', 'PAGO A MENOR', 'VALOR DEVIDO (PAGO A MENOR)'];
        $config = compact('title','table_foot');
        // Usa build com valores (10 colunas)
        RelatoriosControler::biuldRelAValores($array_adimp, $config);
        break;
}

// Registro contagem
try {
    $nome = Session::get("name");
    $grupo = $users->GroupName(Session::get("grupo"));
    $ip = $_SERVER['REMOTE_ADDR'];
    $origem = "RelAdimplenciaValores";

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
    die("Erro ao retornar os dados: " . $error->getMessage());
}

// Prepara o download do arquivo xlsx
$file = $categoria == 'Todos' ? "Relatório de Adimplência (Valores) " . $data . ".xlsx" : "Relatório de Adimplência (Valores) " . $categoria ." ". $data . ".xlsx";
$downloadPath = realpath(dirname(__FILE__, 1)) . "/relatorios/rel.xlsx";
if (!file_exists($downloadPath))
   die('Arquivo não existe!');
header('Content-disposition: attachment; filename=' . $file . ';'); 
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: '.filesize($downloadPath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($downloadPath);
die();
