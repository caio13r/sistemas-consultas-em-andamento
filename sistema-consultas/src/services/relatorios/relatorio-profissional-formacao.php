<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RE5select'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}
?>

<div class="container-fluid">


    <h5 class="card-title mb-4">Profissional x Formação</h5>
      
      <div class="col-md-6 offset-md-3">

        <div class="contact-form">
        <form class="book-form" method="POST" action="relatorio-profissional-formacao-gerar">
            <div class="row">
                <div id="cro" class="col-md-12 mt-3">
                    <label for="cro_filter">Selecione o cro:</label>
                    <select class="form-control" name="cro_filter" id="cro_filter" value='' required>
                      <option disabled selected value>Selecione</option>
                        <?php
                          foreach(Helper::$ufList_withoutAll as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                          }
                        ?>
                    </select>
                </div>
                <div id="categoria" class="col-md-12 mt-3">
                  <label for="categoria_filter">Defina a categoria:</label>
                  <select class="form-control select2" multiple="multiple" name="categoria_filter[]" id="categoria_filter">
                      <option></option>
                      <?php
                        foreach(Helper::$catListPf as $val => $value) {
                          $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                          echo "<option value='$val' $selected>$value</option>";
                        }
                      ?>
                  </select>
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

<!-- Inclua o CSS do Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- Inclua o jQuery e o JS do Select2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
  $(document).ready(function() {
    // Inicializa o select2
    $('#categoria_filter').select2({
      placeholder: "Selecione",
      allowClear: true,
      minimumResultsForSearch: Infinity, // Esconde a busca quando não há resultados suficientes
      dropdownCssClass: 'hide-search' 
    });
    
    // Validação no envio do formulário
    $('form.book-form').on('submit', function(e) {
      var selected = $('#categoria_filter').val();
      if (!selected || selected.length === 0) {
        e.preventDefault();
        alert('Por favor, selecione ao menos uma opção para a categoria.');
      }
    });
    
    $('#categoria_filter').on('change', function() {
      var selected = $(this).val() || [];
      if (selected.indexOf('ALL') !== -1 && selected.length > 1) {
        $(this).val(['ALL']).trigger('change.select2');
        return;
      }
      
      var totalNonAll = $('#categoria_filter option').filter(function() {
        return $(this).val() !== 'ALL';
      }).length;
      
      var selectedNonAll = selected.filter(function(val) {
        return val !== 'ALL';
      });
      
      if (selectedNonAll.length === totalNonAll) {
        $(this).val(['ALL']).trigger('change.select2');
      }
    });
  });
</script>

<style>
.select2-selection__choice__display {
  /* Seus estilos aqui */
  margin-left: 13px;
}
</style>

<?php
require_once INC_PATH .'/footer.php';
?>