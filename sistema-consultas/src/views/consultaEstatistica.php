<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();
$buttons = $labels->getChildLabelsPorSigla("CE");

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
            <h5><i class="fas fa-chart-bar mr-2 mt-2"></i>Consulta Estatística</h5>
        </div>

        <div class="card-body">
            <!-- Menu -->
            <div class="row mt-3 mb-4">
                <?php 
                $colCount = 2; // Configuração para duas colunas
                $buttonChunks = array_chunk($buttons, ceil(count($buttons) / $colCount), true);
                ?>
                
                <?php foreach ($buttonChunks as $chunk): ?>
                        <div class="col-md-6"> <!-- Alterado para 6 colunas para duas colunas -->
                            <?php foreach ($chunk as $label): ?>
                                <?php if ($label['disabled'] == 0): ?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CE' . $label['referencial'] . 'acesso']) && $row['CE' . $label['referencial'] . 'acesso'] == true)): ?>
                                        <a href="/consulta-estatistica?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= (($inputGet['tipoConsulta'] ?? '') === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md btn-edit"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao']) ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= htmlspecialchars($label['nome']) ?>
                                            </button>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
            </div>

            <?php
                $tipoConsulta = intval($inputGet['tipoConsulta'] ?? 0);
                $servicePath = SERVICES_PATH . '/consulta-estatistica/consultaEstatistica-' . $tipoConsulta . '.php';
                if ($tipoConsulta >= 1 && $tipoConsulta <= 20 && file_exists($servicePath)) {
                    require $servicePath;
                }
            ?>
        </div>
    </div>  
</div>

<?php 
require_once INC_PATH . '/footer.php';
?>
