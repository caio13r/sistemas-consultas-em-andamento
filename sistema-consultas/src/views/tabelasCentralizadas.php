<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$instance = new Labels();
$labels = $instance->getChildLabelsPorSigla("TC");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();

$labelsTC = [
    1 => 'Formações Acadêmicas IES',
    2 => 'Atividade Econômica',
    3 => 'Capital Social Faixas',
    4 => 'Categorias',
    5 => 'Classificação Empresas',
    6 => 'Cursos',
    7 => 'Débito Tipos',
    8 => 'Especialidades',
    9 => 'Naturezas Jurídicas',
    10 => 'Situações',
    11 => 'Situações Detalhes',
    12 => 'Tipos Inscrições',
];

$labelsTCSISDOC = [
    13 => 'Motivos de fiscalização',
];

?>

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

<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-search-plus mr-2 mt-2"></i>Tabelas Centralizadas mo CFO</h5>
        </div>

        <div class="card-body">

            <!-- Menu -->
            <div class="row d-flex justify-content-center mt-3 mb-3">
                <!-- Box para Sistema SISCAF -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Sistema SISCAF</h6>
                        </div>
                        <div class="card-body text-center">

                            <?php foreach($labels as $label) { ?>
                                <?php if ($label['grupo'] == 0 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || $row['TC'.$label['referencial'].'acesso'] == true) { ?>
                                        <a href="/tabelas-centralizadas?tipoConsulta=<?= $label['referencial'] ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
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

                <!-- Box para Sistema SISDOC/FISCALIZAÇÃO -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Sistema SISDOC/FISCALIZAÇÃO</h6>
                        </div>
                        <div class="card-body text-center">

                        <?php foreach($labels as $label) { ?>
                                <?php if ($label['grupo'] == 1 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || $row['TC'.$label['referencial'].'acesso'] == true) { ?>
                                        <a href="/tabelas-centralizadas?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
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
                $fileMap = [
                    1 => 'tabelas-centralizadas-1.php',
                    2 => 'tabelas-centralizadas-2.php',
                    3 => 'tabelas-centralizadas-3.php',
                    4 => 'tabelas-centralizadas-4.php',
                    5 => 'tabelas-centralizadas-5.php',
                    6 => 'tabelas-centralizadas-6.php',
                    7 => 'tabelas-centralizadas-7.php',
                    8 => 'tabelas-centralizadas-8.php',
                    9 => 'tabelas-centralizadas-9.php',
                    10 => 'tabelas-centralizadas-10.php',
                    11 => 'tabelas-centralizadas-11.php',
                    12 => 'tabelas-centralizadas-12.php',
                    13 => 'tabelas-centralizadas-13.php', #nova view
                ];

                $tipoConsulta = $inputGet['tipoConsulta'] ?? null;
                $codigo = $inputGet['codigo'] ?? null;
                if ($tipoConsulta && isset($fileMap[$tipoConsulta])) {
                    require SERVICES_PATH . '/tabelas-centralizadas/' . $fileMap[$tipoConsulta];
                }else if ($codigo) {
                    require SERVICES_PATH . '/tabelas-centralizadas/tabelas-centralizadas-codigo.php';
                }
            ?>

        </div>
    </div>
</div>



<?php
require_once INC_PATH . '/footer.php';
?>
