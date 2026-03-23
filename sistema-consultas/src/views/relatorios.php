<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();
$buttons = $labels->getChildLabelsPorSigla("RE");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['REacesso'] == false) {
    echo "<script language='javascript'>
    window.alert(' Vocé não tem permissão para acessar essa página.')
    window.location.href='index';
    </script>";
    exit;
}

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
            <h5><i class="fas fa-chart-bar mr-2 mt-2"></i>Relatórios Diversos
        </h5>
        </div>

        <div class="card-body">
            <!-- Menu -->
            <div class="row d-flex justify-content-center mt-3 mb-3">
                <!-- Box para Relatórios Financeiros -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Relatórios Financeiros</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php foreach($buttons as $label) { ?>
                                <?php if ($label['grupo'] == 0 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || $row['RE'.$label['referencial'].'acesso'] == true) { ?>
                                        <a href="/relatorios?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md btn-edit"
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

                <!-- Box para Outros Relatórios -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Outros Relatórios</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php foreach($buttons as $label) { ?>
                                <?php if ($label['grupo'] == 1 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || $row['RE'.$label['referencial'].'acesso'] == true) { ?>
                                        <a href="/relatorios?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= ($inputGet['tipoConsulta'] === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md btn-edit"
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
            // Mapeamento atualizado de acordo com os relatórios RE listados
            $fileMap = [
                1 => 'relatorio-adimplencia.php',                    // RE1 - Relatório de Adimplência
                2 => 'relatorio-tarifas.php',                       // RE2 - Relatório de Tarifas
                3 => 'relatorio-LAI-2.php',                           // RE3 - Relatório LAI/LGPD
                // 4 => 'relatorio-delegadoeleitor.php',               
                // // RE4 - Relatório Delegado Eleitor
                5 => 'relatorio-profissional-formacao.php',         // RE5 - Relatório Profissional x Formação
                7 => 'relatorio-processos-especialidade.php',       // RE7 - Relatório Processos de Especialidade e Habilitação
               
                8 => 'relatorio-arrecadacao-bb.php',                // RE8 - Relatório de Arrecadação e Tarifas do BANCO DO BRASIL (boletos mensal)
                
                9 => 'relatorio-pagamentos-diversos.php',           // RE9 - Relatório de Arrecadação de Pagamentos Diversos
                10 => 'relatorio-arrecadacao-tarifas-selfpay.php',   // RE10 - Relatório de Arrecadação e Tarifas do CARTÃO DE CRÉDITO - SELFPAY / BKBANK
               
                //10 => 'relatorio-tarifas-selfpay.php',              // RE10 - Relatório de Tarifas do CARTÃO DE CRÉDITO - SELFPAY / BKBANK
                
                 11 => 'relatorio-arrecadacao-bb.php',               // RE11 - Relatório de Arrecadação do BANCO DO BRASIL (boletos pelo período desejado)
                12 => 'relatorio-tarifas-bb.php',                   // RE12 - Relatório de Tarifas do BANCO DO BRASIL (boletos pelo período desejado)
                13 => 'relatorio-pagamentos-selfpay.php',           // RE13 - Relatório de Arrecadação do CARTÃO DE CREDITO - SELFPAY / BKBANK
                14 => 'relatorio-adimplencia-valores.php',           // RE14 - Relatório de Adimplência (com valores)
            ];
            $tipoConsulta = $inputGet['tipoConsulta'] ?? null;
            if ($tipoConsulta && isset($fileMap[$tipoConsulta])) {
                require SERVICES_PATH . '/relatorios/' . $fileMap[$tipoConsulta];
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

/* Ajusta apenas o botão de pesquisa para ser menor */
.btn-primary .btn {
    width: auto; /* Largura automática para o botão de pesquisa */
    font-size: 0.85rem; /* Reduz o tamanho da fonte */
    padding: 0.375rem 0.75rem; /* Ajusta o espaçamento interno */
}
</style>

<?php 
require_once INC_PATH . '/footer.php';
?>
