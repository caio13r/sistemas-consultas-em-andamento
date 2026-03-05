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
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="text-primary">
                <i class="fas fa-chart-line mr-2"></i>
                Relatórios LAI - Lei de Acesso à Informação
            </h4>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="?id=1" class="btn <?= ($_GET['id'] == 1 || empty($_GET['id'])) ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-chart-pie mr-2"></i>Dashboard Power BI
                </a>
                <a href="?id=2" class="btn <?= ($_GET['id'] == 2) ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-chart-bar mr-2"></i>Relatório Interativo
                </a>
            </div>
        </div>
    </div>

    <?php if ($_GET['id'] == 1 || empty($_GET['id'])) { ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie mr-2"></i>
                            Dashboard LAI/LGPD - Power BI
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <iframe title="Report Section" width="100%" height="800" src="https://app.powerbi.com/view?r=eyJrIjoiMDU0YTdkYTItZTYwYi00MDM3LWFkZTAtMTNlNDFkMWU2MjQ5IiwidCI6ImVjMzU5YmExLTYzMGItNGQyYi1iODMzLWM4ZTZkNDhmODA1OSJ9&pageName=ReportSection" frameborder="0" allowFullScreen="true"></iframe>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php if ($_GET['id'] == 2) { ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Relatório LAI Interativo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Novo Relatório:</strong> Clique no botão abaixo para acessar o relatório interativo com gráficos e análises detalhadas.
                        </div>
                        <a href="/relatorio-lai-2" class="btn btn-primary btn-lg">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Acessar Relatório Interativo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
</div>

<?php
require_once INC_PATH .'/footer.php';
?>