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
?>

<div class="container-fluid">

    <div class="row">

        <?php foreach ($labels as $label) { ?>
            <?php if($label['label_id'] != 1){ ?>
                <?php if($label['disabled'] != 1){?>
                    <?php if (Session::get('grupo') == '0' || $row[$label['key_label']] == true) { ?>
                        <div class="col-lg-6">
                            <div class="card border-left-dark shadow mb-4">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="co-12">
                                            <div class="text-lg text-dark font-weight-bold">
                                                <?= $label['nome_label'] ?>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <?= $label['descricao'] ?>
                                            <br>
                                            <a href="<?= $label['url'] ?>" class="btn-sm btn-secondary btn-icon-split mt-1">
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-arrow-right"></i>
                                                </span>
                                                <span class="text">Clique para acessar</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
