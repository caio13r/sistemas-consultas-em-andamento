<?php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Users;
use Cfo\SisConsultas\lib\Acess;
use Cfo\SisConsultas\lib\Labels;
use Cfo\SisConsultas\lib\ActivityLog;

Session::init();

$labels_instance = new Labels();
$labels;
/*
if (Session::get('labels') == NULL){
    Session::set('labels', $labels_instance->selectLabels());
    echo "
        <script>
            console.log('Consulta no banco');
        </script>
    ";
}else{
    $labels = Session::get('labels');
    echo "
        <script>
            console.log('Consulta na sessão');
        </script>
    ";
}
*/

$labels = $labels_instance->getLabelsRedis();

// Manter a ordem original do banco (display_order)
// A ordenação já é feita na consulta SQL: ORDER BY COALESCE(display_order, id) ASC

$users = new Users();

$inputPost = array_map('htmlspecialchars', $_POST);
$inputGet = array_map('htmlspecialchars', $_GET);

// Registrar acesso à página automaticamente
if (Session::get('login') == TRUE) {
    ActivityLog::registrarAcesso();
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Sistema Consultas - CFO</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon-cfo.png" />

    <!-- Includes CSS -->
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/modern-overrides.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- <link href="../assets/datatables/dataTables.bootstrap4.css" rel="stylesheet"> -->
    <link href="../assets/datatables/datatables.css" rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" /> -->

    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HE1BT90FWN"></script>

     <!-- Moment -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-HE1BT90FWN');
    </script>

</head>

<body id="page-top">

    <?php
    if (isset($_GET['action']) && $_GET['action'] == 'logout') {
        // Registrar log de logout antes de destruir a sessão
        ActivityLog::registrarLogout();

        Session::set('logout', '<div class="alert alert-success alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Success!</strong> Você fez logout com sucesso!</div>');
        Session::destroy();
    }
    ?>

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->

        <?php if (Session::get('login') == TRUE) {
            require LIB_PATH . '/Acess.php';
        ?>

            <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

                <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/index">
                    <img src="../assets/img/logocfo.png" width="90%">
                </a>

                <hr class="sidebar-divider mt-2 my-0">

                <li class="nav-item <?= $_SERVER['REDIRECT_URL'] === '/index' || $_SERVER['REDIRECT_URL'] === '/home' ? 'active' : '' ?>">
                    <a class="nav-link" href="/index">
                        <i class="fas fa-home"></i>
                        <span><?php echo $labels[0]['nome_label']; ?></span></a>
                </li>

                <?php if (Session::get('grupo') == '0') { ?>

                    <hr class="sidebar-divider">

                    <div class="sidebar-heading">
                        Admin Painel
                    </div>

                    <li class="nav-item <?= $_SERVER['REDIRECT_URL'] === '/users' || $_SERVER['REDIRECT_URL'] === '/adduser' ? 'active' : '' ?>">
                        <a class="nav-link" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                            <i class="fas fa-users"></i>
                            <span>Administração</span>
                        </a>
                        <div id="collapseTwo" class="collapse <?= $_SERVER['REDIRECT_URL'] === '/users' || $_SERVER['REDIRECT_URL'] === '/adduser' ? 'show' : '' ?>" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">
                                <h6 class="collapse-header">Custom Components:</h6>
                                <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/users' ? 'active' : '' ?>" href="/users">Lista de Usuários</a>
                                <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/adduser' ? 'active' : '' ?>" href="/adduser">Adicionar Usuário</a>
                                <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/acessos' ? 'active' : '' ?>" href="/acessos">Permissões</a>
                                <!-- <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/atualizar-labels' ? 'active' : '' ?>" href="/atualizar-labels">Configurar Nomes</a> -->
                                <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/gerenciar-labels' ? 'active' : '' ?>" href="/gerenciar-labels">Gerenciar Nomes</a>
                                <a class="collapse-item <?= $_SERVER['REDIRECT_URL'] === '/logs' ? 'active' : '' ?>" href="/logs">Logs de Atividade</a>
                            </div>
                        </div>
                    </li>

                <?php } ?>

                <hr class="sidebar-divider">

                <div class="sidebar-heading">
                    Serviços
                </div>

                <?php
                $sidebarIconMap = [
                    '/consulta-integrada'     => 'fas fa-search-plus',
                    '/consulta-auditoria'     => 'fas fa-clipboard-check',
                    '/consulta-eleicoes'      => 'fas fa-vote-yea',
                    '/consulta-prescricao'    => 'fas fa-clock',
                    '/consulta-estatistica'   => 'fas fa-chart-pie',
                    '/consulta-fiscalizacao'  => 'fas fa-shield-alt',
                    '/consulta-identidade'    => 'fas fa-id-card',
                    '/consulta-sigesp'        => 'fas fa-university',
                    '/tabelas-centralizadas'  => 'fas fa-database',
                    '/dados-abertos'          => 'fas fa-folder-open',
                    '/relatorios'             => 'fas fa-file-alt',
                    '/cracha'                 => 'fas fa-address-card',
                    '/consulta-rfb'           => 'fas fa-receipt',
                ];
                ?>
                <?php foreach($labels as $label){?>
                    <?php if (Session::get('grupo') == '0' || $row[$label['key_label']] == true) { ?>
                        <?php if ($label['label_id'] != 1) {?>
                            <?php if ($label['disabled'] != 1) { ?>
                                <?php
                                $labelUrl = $label['url'] ?? '';
                                $labelIcon = trim((string) ($label['icon'] ?? ''));
                                $resolvedSidebarIcon = $sidebarIconMap[$labelUrl] ?? ($labelIcon !== '' ? $labelIcon : 'fas fa-folder-open');
                                ?>
                                <li class="nav-item <?= $_SERVER['REDIRECT_URL'] === $label['url'] || $_SERVER['REDIRECT_URL'] === '/consulta-integrada-info' ? 'active' : '' ?>">
                                    <a class="nav-link" href="<?= htmlspecialchars($labelUrl) ?>">
                                        <i class="<?= htmlspecialchars($resolvedSidebarIcon) ?>"></i>
                                        <span><?= htmlspecialchars($label['nome_label']) ?></span>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php }?>
                    <?php } ?>
                <?php }?>

            <?php } ?>

            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

            </ul>

            <div id="content-wrapper" class="d-flex flex-column">

                <div id="content">

                    <?php if (Session::get('id') == true) { ?>

                        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                            <!-- Sidebar Toggle (Topbar) -->
                            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                                <i class="fa fa-bars"></i>
                            </button>

                            <p class="h5 pl-2 pt-2 large text-dark font-weight-bold">
                                Sistema Consultas
                            </p>

                            <!-- Topbar Navbar -->
                            <ul class="navbar-nav ml-auto">

                                <div class="topbar-divider d-none d-sm-block"></div>

                                <li class="nav-item dropdown no-arrow">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="p-2 small badge badge-light text-wrap"><?php echo Session::get("name"); ?> (<?= $users->GroupName(Session::get("grupo")); ?> - <?= $users->SubGroupName(Session::get("subgrupo")); ?>)</span>
                                        <!-- <img class="img-profile rounded-circle" src="../assets/img/undraw_profile.svg"> -->
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                        <a class="dropdown-item <?= $_SERVER['REDIRECT_URL'] === '/perfil' ? 'active' : '' ?>" href="/perfil?id=<?php echo Session::get("id"); ?>">
                                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Perfil
                                        </a>
                                        <a class="dropdown-item <?= $_SERVER['REDIRECT_URL'] === '/mudar-senha' ? 'active' : '' ?>" href="/mudar-senha?id=<?= Session::get("id") ?>">
                                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Configurações
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="?action=logout" data-toggle="modal" data-target="#logoutModal">
                                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Logout
                                        </a>
                                    </div>
                                </li>

                            </ul>

                            <div class="topbar-divider d-none d-sm-block"></div>

                        </nav>

                    <?php } ?>