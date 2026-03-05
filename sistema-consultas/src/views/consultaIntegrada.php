<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\lib\Labels;

$instance = new Labels();
$labels = $instance->getChildLabelsPorSigla("CN");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();

// Definir 'tipoConsulta' como '1' se não estiver definido
$tipoConsulta = isset($_GET['tipoConsulta']) ? $_GET['tipoConsulta'] : '1';
?>

<div class="container-fluid">

    <div class="card">
        <div class="card-header">
        <div class="card-header">
    <h5>
        <i class="fas fa-search-plus mr-2 mt-2"></i>Consulta Integradas
        <?php 
        if ($tipoConsulta == '1') {
            echo "<small> - Consulta por Profissionais</small>"; 
        } elseif ($tipoConsulta == '2') {
            echo "<small> - Consulta por Empresas</small>";
        } elseif ($tipoConsulta == '3') {
            echo "<small> - Consulta Receita Federal (CPF)</small>";
        } else {
            echo "<small> - Tipo de consulta desconhecido</small>";
        }
        ?>
    </h5>
</div>
        </div>

        <div class="card-body">

            <!-- Exibir qual consulta está ativa -->
           

            <!-- Menu -->
            <div class="row d-flex justify-content-center mt-3 mb-3">

                <?php foreach ($labels as $label) { ?>
                    <?php if($label['disebled'] != 1){?>
                        <?php
                        // Verificação especial para Consulta RFB (tipo 3)
                        $podeAcessar = false;
                        if ($label['referencial'] == '3') {
                            // Consulta RFB: apenas CROs ou email específico
                            $podeAcessar = Helper::temPermissaoRFB();
                        } else {
                            // Outras consultas: verificação padrão
                            $podeAcessar = (Session::get('grupo') === 0 || $row['CI'.$label['referencial'].'acesso'] == true);
                        }
                        ?>

                        <?php if ($podeAcessar) { ?>
                            <a href="/consulta-integrada?tipoConsulta=<?= $label['referencial'] ?>">
                                <button
                                    type="button"
                                    class="btn btn-<?= ($tipoConsulta == $label['referencial']) ? 'primary active' : 'secondary' ?> btn-md m-1"
                                    data-bs-toggle="popover"
                                    data-bs-html="true"
                                    data-bs-placement="bottom"
                                    data-bs-content='<?= $label['descricao'] ?>'
                                    data-bs-trigger="hover"
                                >
                                    <?= $label['nome'] ?>
                                    <?php if ($label['referencial'] == '3'): ?>
                                        <i class="fas fa-lock ml-1" title="Acesso restrito"></i>
                                    <?php endif; ?>
                                </button>
                            </a>
                        <?php } ?>
                    <?php } ?>
                <?php }?>

            </div>

            <?php
            // Consulta por Profissionais
            if ($tipoConsulta == '1') {
                require SERVICES_PATH . '/consulta-integrada/consultaIntegrada-1.php';
            }
            // Consulta por Empresas
            elseif ($tipoConsulta == '2') {
                require SERVICES_PATH . '/consulta-integrada/consultaIntegrada-2.php';
            }
            // Consulta Receita Federal - Redirecionar para nova página com submenu
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