<?php
// relatorio-delegadoeleitor-prepara.php

// Registra início do script
error_log(">>> Início de relatorio-delegadoeleitor-prepara.php");

// Inclui classes necessárias
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\services\relatorios\classes\CacheHelper;
use Cfo\SisConsultas\services\relatorios\controler\RelatoriosControler;

error_log(">>> Incluindo e iniciando sessão...");

Session::init();
error_log(">>> Sessão iniciada.");

Session::CheckSession();
error_log(">>> Sessão verificada.");

$users = new Users();
error_log(">>> Instância de Users criada.");

// Recupera o valor do CRO (UF) enviado via POST
$uf = $_POST['uf'] ?? 'ALL';
error_log(">>> Valor recebido de uf: " . print_r($uf, true));

// Validação básica do parâmetro
if (!isset($_POST['uf']) || empty($uf)) {
    error_log(">>> Erro: CRO não informado.");
    echo "<script language='javascript'>
            alert('É necessário selecionar o CRO.');
            window.location.href='relatorio-delegadoeleitor';
          </script>";
    exit;
}
error_log(">>> CRO informado: " . $uf);

// Consulta os dados para Delegado Eleitor
error_log(">>> Chamando CacheHelper::connectionViewDelegado() com uf = " . $uf);


$array_delegado = CacheHelper::connectionViewDelegado(['uf' => $uf]);
error_log(">>> Dados retornados: " . print_r($array_delegado, true));

// Configurações para o relatório
$title = "Relatório Delegado Eleitor - " . $uf;
$table_head = ['CRO', 'CATEGORIA', 'INSCRICAO', 'CPF/CNPJ', 'Profissional', 'Email', 'Telefone'];
$config = compact('title', 'table_head');
error_log(">>> Configuração do relatório: " . print_r($config, true));

// Gera o arquivo XLSX com os dados (salva no diretório definido, por exemplo, "reldeleleitor.xlsx")
error_log(">>> Chamando RelatoriosControler::biuldRelDelegado()...");
RelatoriosControler::biuldRelDelegado($array_delegado, $config);
error_log(">>> Arquivo de relatório gerado.");

// Redireciona para o script de download
error_log(">>> Redirecionando para relatorio-delegadoeleitor-download.php...");
echo "<script language='javascript'>
        alert('Relatório Delegado Eleitor gerado com sucesso!');
        window.location.href='relatorio-delegadoeleitor-download';
      </script>";

error_log(">>> Finalizando script relatorio-delegadoeleitor-prepara.php.");
exit;
