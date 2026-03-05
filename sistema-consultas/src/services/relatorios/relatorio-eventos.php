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
  <iframe title="eventos-cfo" width="1524" height="1060" src="https://app.powerbi.com/view?r=eyJrIjoiMGVkZDU1ZDItY2VjOC00ODkyLWI5M2MtYjdlYjA2MjVjYjcyIiwidCI6ImVjMzU5YmExLTYzMGItNGQyYi1iODMzLWM4ZTZkNDhmODA1OSJ9&pageName=cebf27b3c39ccee800c6" frameborder="0" allowFullScreen="true"></iframe>
  </div>





</div>
</div>

<?php

require_once INC_PATH . '/footer.php';
?>