<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

// Verifica se o usuário tem permissão (apenas CFO)
if (Session::get('grupo') != 0) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='index';
    </script>";
    exit;
}

$current_date = date('Y-m', strtotime('-1 month'));
?>

<div class="container-fluid">
    <h5 class="card-title mb-4">Processos de Especialidade e Habilitação</h5>
    <div class="col-md-6 offset-md-3">
        <div class="contact-form">
            <form class="book-form" method="POST" action="relatorio-processos-gerar">
                <div class="row">
                    <div class="col-md-12">
                        <label for="uf">Selecione a UF:</label>
                        <select class="form-control" id="uf" name="uf" required>
                            <option value="ALL" selected>Todas as UFs</option>
                            <?php
                            foreach (Helper::$ufList as $val => $value) {
                                echo "<option value='$val'>$value</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
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

<script>
document.querySelector('form').addEventListener('submit', function(event) {
    const data = document.getElementById('data').value;
    if (data === "") {
        event.preventDefault();
        alert('É necessário preencher o período antes de continuar.');
    }
});
</script>

<?php
require_once INC_PATH . '/footer.php';
?> 