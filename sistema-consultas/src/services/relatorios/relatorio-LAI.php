<?php

require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\services\relatorios\classes\Connection;
use Cfo\SisConsultas\lib\Helper;

// conecta com o banco
Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RE3acesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}

?>

<div class="container-fluid">

 

     

        <div class="col-auto mr-auto">
          <iframe title="Report Section" width="1524" height="1060" src="https://app.powerbi.com/view?r=eyJrIjoiMDU0YTdkYTItZTYwYi00MDM3LWFkZTAtMTNlNDFkMWU2MjQ5IiwidCI6ImVjMzU5YmExLTYzMGItNGQyYi1iODMzLWM4ZTZkNDhmODA1OSJ9&pageName=ReportSection" frameborder="0" allowFullScreen="true"></iframe>
        </div>
      

   
</div>
</div>

<?php

require_once INC_PATH . '/footer.php';
?>