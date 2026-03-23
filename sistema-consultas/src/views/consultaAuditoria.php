<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();
$buttons = $labels->getChildLabelsPorSigla("CA");

Session::CheckSession();

$tipoAtivo = $inputGet['tipoConsulta'] ?? null;

$activeLabel = null;
foreach ($buttons as $b) {
    if ($b['disabled'] == 0 && $tipoAtivo === (string)$b['referencial']) {
        $activeLabel = $b;
    }
}
?>

<div class="container-fluid">
    <div class="card consulta-auditoria-shell">
        <div class="card-header consulta-auditoria-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">Consulta de Auditoria</h4>
                    <?php if ($tipoAtivo && $activeLabel): ?>
                        <p class="mb-0 consulta-auditoria-subtitle"><?= htmlspecialchars($activeLabel['nome']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($tipoAtivo): ?>
                    <a href="/consulta-auditoria" class="consulta-auditoria-header-back" title="Ver todas as consultas">
                        <i class="fas fa-th-large"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body consulta-auditoria-body">
            <?php if (!$tipoAtivo): ?>
                <div class="consulta-auditoria-menu-grid">
                    <?php foreach ($buttons as $label): ?>
                        <?php if ($label['disabled'] == 0): ?>
                            <?php if (Session::get('grupo') === 0 || $row['CA' . $label['referencial'] . 'acesso'] == true): ?>
                                <?php $ref = (string)$label['referencial']; ?>
                                <a
                                    href="/consulta-auditoria?tipoConsulta=<?= $ref ?>"
                                    class="consulta-auditoria-menu-item"
                                    title="<?= htmlspecialchars($label['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <span class="consulta-auditoria-menu-num"><?= $ref ?></span>
                                    <span class="consulta-auditoria-menu-text"><?= htmlspecialchars($label['nome']) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="consulta-auditoria-content">
                    <?php require_once SERVICES_PATH . '/consulta-auditoria/consultaAuditoria-' . $_GET['tipoConsulta'] . '.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
    require_once INC_PATH . '/footer.php';
?>
