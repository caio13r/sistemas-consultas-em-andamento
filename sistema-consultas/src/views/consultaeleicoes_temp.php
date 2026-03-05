<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-vote-yea mr-2 mt-2"></i>Consulta de Eleições</h5>
        </div>

        <div class="card-body">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle mr-2"></i>Sistema em Configuração</h6>
                <p>O sistema de consulta eleições está sendo configurado. Em breve estará disponível com todas as funcionalidades.</p>
            </div>
            
            <!-- Menu Temporário -->
            <div class="row mt-3 mb-4">
                <div class="col-md-6">
                    <a href="/consulta-eleicoes?tipoConsulta=1" class="btn-container">
                        <button type="button" class="btn btn-<?= ($_GET['tipoConsulta'] ?? '') === '1' ? 'primary active' : 'secondary' ?> btn-md" style="width: 100%; margin-bottom: 10px;">
                            Consulta Eleições - Tipo 1
                        </button>
                    </a>
                </div>
            </div>

            <?php
                if (isset($_GET['tipoConsulta'])) {
                    require_once SERVICES_PATH . '/consulta-eleicoes/consultaeleicoes-' . $_GET['tipoConsulta'] . '.php';
                }
            ?>
        </div>
    </div>
</div>

<?php
    require_once INC_PATH . '/footer.php';
?> 