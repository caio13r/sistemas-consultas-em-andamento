<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labelsInstance = new Labels();
$childLabels = $labelsInstance->getChildLabelsPorSigla("CL");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();
?>

<style>
    .btn-container {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
    
    .btn-edit {
        width: 100%;
        text-align: left;
    }
</style>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-vote-yea mr-2 mt-2"></i>Consulta de Eleições de DIRETORIA dos CROS</h5>
        </div>

        <div class="card-body">
            <div class="row d-flex justify-content-center mt-3 mb-3">
                <!-- Card para Eleições de 03/10/2025 -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Eleições de 03/10/2025</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php foreach($childLabels as $label) { ?>
                                <?php if ($label['grupo'] == 0 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CL'.$label['referencial'].'acesso']) && $row['CL'.$label['referencial'].'acesso'] == true)) { ?>
                                        <a href="/consulta-eleicoes?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] ?? '') === (string)$label['referencial'] ? 'primary active' : 'secondary' ?> btn-md btn-edit"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao']) ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= htmlspecialchars($label['nome']) ?>
                                            </button>
                                        </a>
                                    <?php } ?>
                                <?php }?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <!-- Card para Eleições posteriores -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Eleições posteriores</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php foreach($childLabels as $label) { ?>
                                <?php if ($label['grupo'] == 1 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CL'.$label['referencial'].'acesso']) && $row['CL'.$label['referencial'].'acesso'] == true)) { ?>
                                        <a href="/consulta-eleicoes?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] ?? '') === (string)$label['referencial'] ? 'primary active' : 'secondary' ?> btn-md btn-edit"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao']) ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= htmlspecialchars($label['nome']) ?>
                                            </button>
                                        </a>
                                    <?php } ?>
                                <?php }?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                if (isset($inputGet['tipoConsulta'])) {
                    $tipoConsulta = intval($inputGet['tipoConsulta']);
                    $servicePath = SERVICES_PATH . '/consulta-eleicoes/consultaeleicoes-' . $tipoConsulta . '.php';
                    if ($tipoConsulta >= 1 && $tipoConsulta <= 10 && file_exists($servicePath)) {
                        require $servicePath;
                    }
                }
            ?>
        </div>
    </div>  
</div>

<?php 
require_once INC_PATH . '/footer.php';
?>