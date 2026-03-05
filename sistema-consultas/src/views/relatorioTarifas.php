<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RE2acesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}

$current_date = date('Y-m', strtotime('-1 month'));
?>

<div class="container-fluid">

<div class="card">
  <div class="card-header">
    <h5><i class="fas fa-file mr-2 mt-2"></i>Gerador de Relatórios</h5>
  </div>
  <div class="card-body">

  <h5 class="card-title mb-4">Tarifas Bancárias</h5>
  
    <div class="col-md-6 offset-md-3">

      <div class="contact-form">
        <form class="book-form" method="POST" action="relatorio-tarifas-gerar">
          <div class="row">
              <div class="col-md-12">
                <label for="origem">Selecione o tipo de relatório a emitir:</label>
                  <select class="form-control" id= "origem" name="origem" required>
                      <option value="" disabled selected>Selecione</option>
                      <option value="1">Arrecadação Geral</option>
                      <option value="2">Tarifas Gerais</option>
                  </select>
              </div>
              <div class="col-md-12 mt-3" id="uf_filter">
                <label for="uf">Selecione a UF:</label>
                  <select class="form-control" id="uf" name="uf" required>
                    <option disabled>Selecione a UF</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['RE2select'] == true) {
                        foreach(Helper::$ufList as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }            
                      } else {
                        foreach(Helper::$ufList as $val => $value) {
                          if ($users->CheckGroupUf() == $val) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                          }
                        }   
                      }
                    ?>
                  </select>
              </div>
              <div id="date_filter" class="col-md-12 mt-3">
                  <label for="data">Defina o período:</label>
                  <input class="form-control" type="month" id="data" name="data" min="2021-01" value="<?php echo $current_date;?>" max="<?php echo $current_date;?>" required>      
              </div>
              <div class="col-md-12 mt-2">
                  <input type="submit" value="Gerar Relatório" class="btn btn-primary" style="margin-top: 15px;">
              </div>

           </div>
        </form>
      </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
  document.querySelector('form').addEventListener('submit', function(event) {
    const origem = document.getElementById('origem').value;

    if (origem === "") {
        event.preventDefault(); // Impede o envio do formulário
        alert('É necessário preencher todos os campos antes de continuar.');
    }
});
</script>

<?php
require_once INC_PATH .'/footer.php';
?>