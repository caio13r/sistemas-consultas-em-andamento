<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\lib\Labels;

$instance = new Labels();
$labels = $instance->getChildLabelsPorSigla("CN");

Session::CheckSession();

$tipoConsulta = isset($_GET['tipoConsulta']) ? $_GET['tipoConsulta'] : '1';

$subtitulos = [
    '1' => 'Consulta por Profissionais',
    '2' => 'Consulta por Empresas',
    '3' => 'Consulta Receita Federal (CPF)',
];
$subtitulo = $subtitulos[$tipoConsulta] ?? 'Tipo de consulta desconhecido';
?>

<div class="container-fluid">
    <div class="card consulta-integrada-shell">
        <div class="card-header consulta-integrada-header">
            <h4 class="mb-0">Consulta Integrada</h4>
            <p class="mb-0 consulta-integrada-subtitle"><?= htmlspecialchars($subtitulo) ?></p>
        </div>

        <div class="card-body consulta-integrada-body">
            <div class="consulta-integrada-switcher">
                <div class="consulta-integrada-switcher-grid">
                    <?php foreach ($labels as $label) { ?>
                        <?php if($label['disebled'] != 1){?>
                            <?php
                            $podeAcessar = false;
                            if ($label['referencial'] == '3') {
                                $podeAcessar = Helper::temPermissaoRFB();
                            } else {
                                $podeAcessar = (Session::get('grupo') === 0 || $row['CI'.$label['referencial'].'acesso'] == true);
                            }
                            ?>
                            <?php if ($podeAcessar) { ?>
                                <a
                                    href="/consulta-integrada?tipoConsulta=<?= $label['referencial'] ?>"
                                    class="consulta-integrada-switcher-item <?= ($tipoConsulta == $label['referencial']) ? 'is-active' : '' ?>"
                                    data-bs-toggle="popover"
                                    data-bs-html="true"
                                    data-bs-placement="bottom"
                                    data-bs-content='<?= htmlspecialchars($label['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                                    data-bs-trigger="hover"
                                >
                                    <?= htmlspecialchars($label['nome']) ?>
                                    <?php if ($label['referencial'] == '3'): ?>
                                        <i class="fas fa-lock ml-1" title="Acesso restrito"></i>
                                    <?php endif; ?>
                                </a>
                            <?php } ?>
                        <?php } ?>
                    <?php }?>
                </div>
            </div>

            <?php
            if ($tipoConsulta == '1') {
                require SERVICES_PATH . '/consulta-integrada/consultaIntegrada-1.php';
            }
            elseif ($tipoConsulta == '2') {
                require SERVICES_PATH . '/consulta-integrada/consultaIntegrada-2.php';
            }
            elseif ($tipoConsulta == '3') {
                echo '<script>window.location.href = "/consulta-rfb";</script>';
                echo '<div class="text-center mt-5">';
                echo '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>';
                echo '<p class="mt-3">Redirecionando para Consulta RFB...</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
