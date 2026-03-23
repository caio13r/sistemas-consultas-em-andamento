<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();
$buttons = $labels->getChildLabelsPorSigla("CI");

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
            <h5><i class="fas fa-search-plus mr-2 mt-2"></i>Consulta Identidade</h5>
        </div>

        <div class="card-body">
            <!-- Menu -->
            <div class="row d-flex justify-content-center mt-3 mb-3">
                <!-- Box para CFO Policarbonato -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Identidades Policarbonato</h6>
                        </div>
                        <div class="card-body text-start">
                            <?php foreach($buttons as $label) { ?>
                                <?php if ($label['grupo'] == 0 && $label['disabled'] == 0) { ?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CI' . $label['referencial'] . 'acesso']) && $row['CI' . $label['referencial'] . 'acesso'] == true)) { ?>
                                        <a href="/consulta-identidade?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] ?? '') === $label['referencial'] ? 'primary active' : 'secondary' ?> btn-md btn-edit"
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
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Box para CFO ID -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>CFO ID</h6>
                        </div>
                        <div class="card-body text-start">
                            <?php foreach($buttons as $label) { ?>
                                <?php if ($label['grupo'] == 1 && $label['disabled'] == 0) { ?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CI' . $label['referencial'] . 'acesso']) && $row['CI' . $label['referencial'] . 'acesso'] == true)) { ?>
                                        <a href="/consulta-identidade?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] ?? '') === $label['referencial'] ? 'primary active' : 'secondary' ?> btn-md btn-edit"
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
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                if (isset($inputGet['tipoConsulta'])) {
                    $tipoConsulta = $inputGet['tipoConsulta'];
                    if (preg_match('/^cadastro[a-zA-Z0-9_-]+$/', $tipoConsulta)) {
                        $servicePath = SERVICES_PATH . '/cadastro/' . $tipoConsulta . '.php';
                        if (file_exists($servicePath)) {
                            require_once $servicePath;
                        }
                    } else {
                        $tipoConsultaInt = intval($tipoConsulta);
                        $servicePath = SERVICES_PATH . '/consulta-identidade/consultaIdentidade-' . $tipoConsultaInt . '.php';
                        if ($tipoConsultaInt >= 1 && $tipoConsultaInt <= 30 && file_exists($servicePath)) {
                            require_once $servicePath;
                        }
                    }
                }
            ?>
        </div>
    </div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
