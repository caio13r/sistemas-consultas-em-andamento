<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RE1acesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}
?>

<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <h5><i class="fas fa-file mr-2 mt-2"></i>Gerador de Relatórios</h5>
    </div>
    <div class="card-body">

    <h5 class="card-title mb-4">Adimplência</h5>
      
      <div class="col-md-6 offset-md-3">

        <div class="contact-form">
        <form class="book-form" method="POST" action="relatorio-adimplencia-gerar">
            <div class="row">
                <div class="col-md-12">
                  <label for="origem">Selecione o tipo de relatório a emitir:</label>
                    <select class="form-control" id= "origem" name="origem" required>
                        <option disabled>Selecione</option>
                        <option value="1">Relatorio de Adimplencia</option>
                    </select>
                </div>
                <div id="categoria" class="col-md-12 mt-3">
                    <label for="categoria_filter">Defina a categoria:</label>
                    <select class="form-control" name="categoria_filter" id="categoria_filter" value=''>
                      <option disabled selected value>Selecione</option>
                      <!-- <option value='Todos'>Todos</option> -->
                        <?php
                          foreach(Helper::$catList as $val => $value) {
                            $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                          }
                        ?>
                    </select>
                </div>
                <div id="year_filter" class="col-md-12 mt-3">
                    <label for="date_filter">Defina o período:</label>
                    <select class="form-control" name="date_filter" id="date_filter" value=''></select>
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

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript">
    let startYear = 2015;
    let endYear = new Date().getFullYear();
    for (i = endYear; i > startYear; i--)
    {
      $('#date_filter').append($('<option />').val(i).html(i));
    }
</script>

<?php
require_once INC_PATH .'/footer.php';
?>