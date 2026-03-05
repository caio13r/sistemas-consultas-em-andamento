<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

// Definir tipo de submenu (1 = Consultas, 2 = Gerenciamento)
$tipoSubmenu = isset($_GET['tipo']) ? $_GET['tipo'] : '1';
?>

<div class="container-fluid">

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5>
                <i class="fas fa-file-invoice mr-2"></i>Consulta RFB - Receita Federal do Brasil
                <?php
                if ($tipoSubmenu == '1') {
                    echo "<small> - Consultas de CPF</small>";
                } elseif ($tipoSubmenu == '2') {
                    echo "<small> - Gerenciamento e Logs</small>";
                } else {
                    echo "<small> - Tipo desconhecido</small>";
                }
                ?>
            </h5>
        </div>

        <div class="card-body">

            <!-- Menu de Subopções -->
            <div class="row d-flex justify-content-center mt-3 mb-3">

                <!-- Botão Consultas -->
                <a href="/consulta-rfb?tipo=1">
                    <button
                        type="button"
                        class="btn btn-<?= ($tipoSubmenu == '1') ? 'primary active' : 'secondary' ?> btn-md m-1"
                        data-bs-toggle="tooltip"
                        title="Consultar CPF na Receita Federal"
                    >
                        <i class="fas fa-search"></i> Consultas
                    </button>
                </a>

                <!-- Botão Gerenciamento -->
                <a href="/consulta-rfb?tipo=2">
                    <button
                        type="button"
                        class="btn btn-<?= ($tipoSubmenu == '2') ? 'primary active' : 'secondary' ?> btn-md m-1"
                        data-bs-toggle="tooltip"
                        title="Logs, estatísticas e auditoria"
                    >
                        <i class="fas fa-chart-bar"></i> Gerenciamento
                    </button>
                </a>

            </div>

            <?php
            // Incluir página correspondente
            if ($tipoSubmenu == '1') {
                // Consultas
                require SERVICES_PATH . '/consulta-rfb/consulta-rfb-1.php';
            } elseif ($tipoSubmenu == '2') {
                // Gerenciamento
                require SERVICES_PATH . '/consulta-rfb/gerenciar-rfb.php';
            }
            ?>

        </div>
    </div>

</div>

<?php
require_once INC_PATH . '/footer.php';
?>
