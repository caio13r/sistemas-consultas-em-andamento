<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

Session::CheckSession();

$logMsg = Session::get('logMsg');
if (isset($logMsg)) {
    echo $logMsg;
}
$msg = Session::get('msg');
if (isset($msg)) {
    echo $msg;
}
Session::set("msg", NULL);
Session::set("logMsg", NULL);

$iconMap = [
    '/consulta-integrada'   => 'fas fa-search-plus',
    '/consulta-auditoria'   => 'fas fa-clipboard-check',
    '/consulta-eleicoes'    => 'fas fa-vote-yea',
    '/consulta-prescricao'  => 'fas fa-clock',
    '/consulta-estatistica' => 'fas fa-chart-pie',
    '/consulta-fiscalizacao'=> 'fas fa-shield-alt',
    '/consulta-identidade'  => 'fas fa-id-card',
    '/consulta-sigesp'      => 'fas fa-university',
    '/tabelas-centralizadas'=> 'fas fa-database',
    '/dados-abertos'        => 'fas fa-folder-open',
    '/relatorios'           => 'fas fa-file-alt',
    '/cracha'               => 'fas fa-address-card',
    '/consulta-rfb'         => 'fas fa-receipt',
];
?>

<div class="container-fluid">

    <div class="welcome-banner">
        <h2>Bem-vindo, <?= htmlspecialchars(Session::get("name")) ?></h2>
        <p>Selecione uma das consultas abaixo para começar.</p>
    </div>

    <div class="row">
        <?php foreach ($labels as $label) { ?>
            <?php if($label['label_id'] != 1){ ?>
                <?php if($label['disabled'] != 1){?>
                    <?php if (Session::get('grupo') == '0' || $row[$label['key_label']] == true) { ?>
                        <?php
                        $url = $label['url'] ?? '';
                        $resolvedIcon = $iconMap[$url] ?? ($label['icon'] ?? 'fas fa-folder-open');
                        ?>
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <a href="<?= htmlspecialchars($url) ?>" class="card home-card d-block text-decoration-none h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="home-card-icon mr-3">
                                            <i class="<?= htmlspecialchars($resolvedIcon) ?>"></i>
                                        </div>
                                        <div>
                                            <div class="home-card-title"><?= htmlspecialchars($label['nome_label']) ?></div>
                                            <p class="home-card-desc"><?= htmlspecialchars($label['descricao'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                <?php }?>
            <?php } ?>
        <?php } ?>
    </div>

</div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
