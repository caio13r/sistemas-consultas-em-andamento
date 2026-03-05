<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();
$buttons = $labels->getChildLabelsPorSigla("RP");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['RPacesso'] == false) {
    echo "<script language='javascript'>
    window.alert(' Vocês não tem permissão para acessar essa página.')
    window.location.href='index';
    </script>";
    exit;
}

?>

<div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search-plus mr-2 mt-2"></i>Consulta Prescrição</h5>
            </div>
    
            <div class="card-body">
        
                <!-- Menu -->
                <div class="row mt-3 mb-4">
                    <?php
    
                    $colCount = 2; // Alterado para duas colunas
                    $buttonChunks = array_chunk($buttons, ceil(count($buttons) / $colCount), true);
                    ?>
                    
                    <?php foreach ($buttonChunks as $chunk): ?>
                        <div class="col-md-6"> <!-- Alterado para 6 colunas para duas colunas -->
                            <?php foreach ($chunk as $label): ?>
                                <?php if ($label['disabled'] == 0): ?>
                                    <?php if (Session::get('grupo') === 0 || $row['RP' . $label['referencial'] . 'acesso'] == true): ?>
                                        <a href="/consulta-prescricao?tipoConsulta=<?= $label['referencial'] ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= $label['descricao'] ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= $label['nome'] ?>
                                            </button>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
    
                <?php
                    if (isset($inputGet['tipoConsulta'])) {
                        require_once SERVICES_PATH . '/consulta-prescricao/consulta-prescricao-' . $_GET['tipoConsulta'] . '.php';
                    }
                ?>
            </div>
        </div>
    </div>
    
    <style>
    .btn-container {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
    
    .btn {
        width: 100%;
        text-align: left;
    }
    </style>

<?php
require_once INC_PATH . '/footer.php';
?>