<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;
use Cfo\SisConsultas\services\relatorios\classes\Connection;
use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler;

Session::init();
Session::CheckSession();
$users = new Users();

// Validação post
if (!isset($_POST['data'])) {
    $erro = "Algo deu errado, é necessário preencher os campos corretamente.";
    echo "<script language='javascript'>
            window.alert('$erro')
            window.location.href='relatorio-processos';
        </script>";
    die();
}

// Recebe a requisição 
$Exception = 'Erro na busca do Relatório';
$date_title = [];

// Filtro de UF/CRO
$uf = isset($_POST['uf']) ? $_POST['uf'] : 'ALL';
$uf_filter = ($uf != 'ALL') ? "AND [CRO] = '$uf'" : "";

// Regex para o filtro de datas
$date_start = $_POST['data'].'-01';  // data inicial
$date = QueryHelper::getDate($date_start);
$date_end = QueryHelper::getDateEnd($date);
$date_title[0] = QueryHelper::$mes_extenso[((int) $date[1])]." / ".$date[0];
$date_title[1] = '('.str_replace("-","/",$date_start).' a '.str_replace("-","/",$date_end).')';

$query_start = "DECLARE\n
@dataInicio DATE = '$date_start',\n
@dataFim DATE = '$date_end';\n
";

// Query para buscar os dados
$script = "
SELECT
    [CRO],
    [NumeroProcesso],
    [Nome],
    [Classificacao],
    [EtapaProcesso],
    [Andamento],
    CONVERT(VARCHAR, [DataAndamento], 103) AS [DataAndamento],
    [DiasDesdeAndamento]
FROM
    [CFO_CWS].[dbo].[vw_Cons_Relatorio_Processos_Especialidade_Habilitacao]
WHERE 
    DataAndamento >= @dataInicio 
    AND DataAndamento < DATEADD(DAY, 1, @dataFim)
    $uf_filter
ORDER BY 
    [CRO], [NumeroProcesso]
";

// Executa a query
$array_processos = Connection::conn_Sqlsrv('', $query_start, $script);

// Configura o relatório
$title = 'Processos de Especialidade e Habilitação enviados ao CFO - Período: '.$date_title[0]." ".$date_title[1];
$table_head = [
    'CRO', 
    'Número do Processo', 
    'Nome do Profissional', 
    'Classificação', 
    'Etapa do Processo', 
    'Andamento', 
    'Data do Andamento', 
    'Dias Desde Andamento'
];

$config = compact('title', 'table_head');
RelatoriosControler::biuldRel($array_processos, $config);

// Registro contagem
try {
    $nome = Session::get("name");
    $grupo = $users->GroupName(Session::get("grupo"));
    $ip = $_SERVER['REMOTE_ADDR'];
    $origem = "RelProcessos";

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
$arquivo = $title.".xlsx";
$downloadpath = realpath(dirname(__FILE__, 1)) . "/relatorios/rel.xlsx";
if (!file_exists($downloadpath))
   die('Arquivo não existe!');
header('Content-disposition: attachment; filename="'.$arquivo.'";'); 
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: '.filesize($downloadpath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($downloadpath);
die(); 