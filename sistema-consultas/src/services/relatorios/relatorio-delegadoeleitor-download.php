<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;

Session::init();
Session::CheckSession();
$users = new Users();

// Nome que o usuário verá ao baixar
$fileName = "Relatorio_Delegado_Eleitor.xlsx";

// Caminho real do arquivo gerado
$downloadPath = realpath(dirname(__FILE__, 1)) . "/relatorios/reldeleleitor.xlsx";

if (!file_exists($downloadPath)) {
    die('Arquivo não existe!');
}

// Define os headers para forçar o download como arquivo XLSX
header('Content-disposition: attachment; filename=' . $fileName . ';');
header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: ' . filesize($downloadPath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Lê o arquivo e envia para o navegador
readfile($downloadPath);
exit;
