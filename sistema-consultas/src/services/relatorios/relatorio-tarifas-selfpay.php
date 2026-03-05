<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RE10acesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}
?>
<?php
$current_date = date('Y-m-d');
$first_day_of_month = date('Y-m-01');
?>
<div class="container-fluid">
    <h5 class="card-title mb-4">Relatório de Tarifas do Cartão de Crédito - SELFPAY / BKBANK</h5>
    <div class="col-md-6 offset-md-3">
        <div class="contact-form">
            <form class="book-form" method="POST" action="relatorio-tarifas-selfpay-gerar" onsubmit="showLoading()">
                <div class="row">
                    <div class="col-md-6">
                        <label for="data_inicio">Data Inicial:</label>
                        <input class="form-control" type="date" id="data_inicio" name="data_inicio" value="<?php echo $first_day_of_month; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="data_fim">Data Final:</label>
                        <input class="form-control" type="date" id="data_fim" name="data_fim" value="<?php echo $current_date; ?>" required>
                    </div>
                    <div class="col-md-12 mt-2">
                        <input type="submit" id="btnGerar" value="Gerar Relatório" class="btn btn-primary" style="margin-top: 15px;">
                        <div id="loading" style="display: none; margin-top: 15px;">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Carregando...</span>
                                </div>
                                <span class="ml-2">Gerando relatório, aguarde...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showLoading() {
    // Desabilita o botão e mostra o spinner
    document.getElementById('btnGerar').disabled = true;
    document.getElementById('btnGerar').value = 'Gerando...';
    document.getElementById('loading').style.display = 'block';
    
    // Reabilita o botão após 10 segundos (tempo suficiente para o download)
    setTimeout(function() {
        document.getElementById('btnGerar').disabled = false;
        document.getElementById('btnGerar').value = 'Gerar Relatório';
        document.getElementById('loading').style.display = 'none';
    }, 10000);
}

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(event) {
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;
    
    if (!dataInicio || !dataFim) {
        event.preventDefault();
        alert('É necessário preencher todas as datas antes de continuar.');
        return false;
    }
    
    if (dataInicio > dataFim) {
        event.preventDefault();
        alert('A data inicial não pode ser maior que a data final.');
        return false;
    }
});
</script>

<?php
require_once INC_PATH . '/footer.php';
?> 