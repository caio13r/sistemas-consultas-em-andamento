<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();

// Carrega dados de acesso do usuário para verificação de permissão
$row = [];
if (Session::get('grupo') != 0) {
    try {
        $grupo = Session::get('grupo');
        $subgrupo = Session::get('subgrupo');
        $db1 = \Cfo\SisConsultas\database\Database1::getInstance();
        $con1 = $db1->getConnection();
        $query = "SELECT * FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
        $stmt = $con1->prepare($query);
        $stmt->bindParam(':grupo', $grupo);
        $stmt->bindParam(':subgrupo', $subgrupo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = [];
        }
    } catch (PDOException $error) {
        $row = [];
    }
}

// Usa mesma permissão da CI24 ou cria nova CI25acesso
if (Session::get('grupo') != 0 && (!isset($row['CI24acesso']) || $row['CI24acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='/consulta-identidade';
    </script>";
    exit;
}

// Dados do operador logado
$operadorNome = Session::get('nome') ?? Session::get('login') ?? 'Sistema';
$operadorId = Session::get('id') ?? 0;

// Lista de UFs para os selects
$ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// Data atual
$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estabilização Diária - Gestão de Carteiras</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            --purple-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --orange-gradient: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }
        
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(30, 60, 114, 0.3);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 1.75rem;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.primary::before { background: var(--primary-gradient); }
        .stat-card.success::before { background: var(--success-gradient); }
        .stat-card.warning::before { background: var(--warning-gradient); }
        .stat-card.danger::before { background: var(--danger-gradient); }
        .stat-card.info::before { background: var(--info-gradient); }
        .stat-card.purple::before { background: var(--purple-gradient); }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-card .icon-wrapper.primary { background: var(--primary-gradient); }
        .stat-card .icon-wrapper.success { background: var(--success-gradient); }
        .stat-card .icon-wrapper.warning { background: var(--warning-gradient); }
        .stat-card .icon-wrapper.danger { background: var(--danger-gradient); }
        .stat-card .icon-wrapper.info { background: var(--info-gradient); }
        .stat-card .icon-wrapper.purple { background: var(--purple-gradient); }
        
        .stat-card .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1;
        }
        
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .action-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .action-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .action-card .card-body {
            padding: 1.5rem;
        }
        
        .btn-action {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn-action-primary {
            background: var(--primary-gradient);
            color: white;
        }
        
        .btn-action-success {
            background: var(--success-gradient);
            color: white;
        }
        
        .btn-action-warning {
            background: var(--orange-gradient);
            color: white;
        }
        
        .btn-action-danger {
            background: var(--danger-gradient);
            color: white;
        }
        
        .btn-action-purple {
            background: var(--purple-gradient);
            color: white;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .priority-cd {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .priority-outros {
            background: #e9ecef;
            color: #495057;
        }
        
        .date-picker-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .flatpickr-input {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.2s;
        }
        
        .flatpickr-input:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }
        
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            color: #495057;
            white-space: nowrap;
            padding: 1rem;
        }
        
        .table tbody tr:hover {
            background-color: #f0f7ff !important;
        }
        
        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }
        
        .uf-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .uf-card:hover {
            border-color: #1e3c72;
            transform: translateY(-2px);
        }
        
        .uf-card.selected {
            border-color: #1e3c72;
            background: #f0f7ff;
        }
        
        .uf-card .uf-name {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e3c72;
        }
        
        .uf-card .uf-stat {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .uf-card .uf-stat-value {
            font-weight: 700;
            font-size: 1rem;
        }
        
        .backlog-item {
            background: #fff5f5;
            border-left: 4px solid #eb3349;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 0.5rem;
        }
        
        .backlog-item.warning {
            background: #fffbeb;
            border-left-color: #f7971e;
        }
        
        .backlog-item.info {
            background: #f0f7ff;
            border-left-color: #4facfe;
        }
        
        .historico-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #11998e;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-overlay .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e9ecef;
            border-top-color: #1e3c72;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .nav-tabs-custom {
            border: none;
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: #495057;
            transition: all 0.2s;
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: #f8f9fa;
        }
        
        .nav-tabs-custom .nav-link.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .quick-filter {
            display: inline-flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .quick-filter .btn {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .cd-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .modal-header-gradient {
            background: var(--primary-gradient);
            color: white;
        }
        
        .alert-urgente {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
            border: none;
            border-left: 5px solid #eb3349;
            border-radius: 0 12px 12px 0;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(235, 51, 73, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(235, 51, 73, 0); }
            100% { box-shadow: 0 0 0 0 rgba(235, 51, 73, 0); }
        }
        
        .operador-badge {
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner"></div>
    <div class="loading-text mt-3 fs-5 text-secondary">Processando...</div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-rocket me-2"></i>Estabilização Diária</h1>
                <p>Gestão de Carteiras - Despacho Prioritário de Identidades</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="operador-badge bg-light text-dark">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($operadorNome) ?>
                </span>
                <span class="badge bg-light text-dark fs-6">
                    <i class="fas fa-calendar-day me-1"></i><?= date('d/m/Y') ?>
                    <span class="ms-2"><i class="fas fa-clock me-1"></i><span id="clockTime"><?= date('H:i:s') ?></span></span>
                </span>
                <a href="/consulta-identidade-24" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Alerta de Urgência (se houver pendentes) -->
    <div id="alertaUrgente" class="alert alert-urgente mb-4" style="display: none;">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Atenção: Existem CPFs pendentes!</h5>
                <p class="mb-0" id="alertaUrgenteTexto">Você tem CPFs aguardando despacho há mais de 24 horas.</p>
            </div>
            <button class="btn btn-danger btn-sm pulse-animation" onclick="enviarPendentesOntem()">
                <i class="fas fa-paper-plane me-1"></i>Despachar Agora
            </button>
        </div>
    </div>

    <!-- Tabs de Navegação -->
    <ul class="nav nav-tabs-custom mb-4" id="mainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="hoje-tab" data-bs-toggle="tab" data-bs-target="#hoje" type="button">
                <i class="fas fa-calendar-day me-2"></i>Hoje
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button">
                <i class="fas fa-calendar-alt me-2"></i>Por Data
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="backlog-tab" data-bs-toggle="tab" data-bs-target="#backlog" type="button">
                <i class="fas fa-clock me-2"></i>Backlog
                <span class="badge bg-danger ms-1" id="backlogCount" style="display: none;">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ufs-tab" data-bs-toggle="tab" data-bs-target="#ufs" type="button">
                <i class="fas fa-map-marked-alt me-2"></i>Por UF
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button">
                <i class="fas fa-history me-2"></i>Histórico
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabsContent">
        
        <!-- ========== TAB: HOJE ========== -->
        <div class="tab-pane fade show active" id="hoje" role="tabpanel">
            
            <!-- Cards de Estatísticas do Dia -->
            <div class="row g-4 mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card primary">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statEntraramHoje">-</div>
                                <div class="stat-label">Entraram Hoje</div>
                            </div>
                            <div class="icon-wrapper primary">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card success">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statEnviadosHoje">-</div>
                                <div class="stat-label">Enviados Hoje</div>
                            </div>
                            <div class="icon-wrapper success">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card warning">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statPendentesHoje">-</div>
                                <div class="stat-label">Pendentes Hoje</div>
                            </div>
                            <div class="icon-wrapper warning">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card purple">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statCdHoje">-</div>
                                <div class="stat-label">CD Pendentes</div>
                            </div>
                            <div class="icon-wrapper purple">
                                <i class="fas fa-tooth"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card danger">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statPendentesOntem">-</div>
                                <div class="stat-label">Pendentes Ontem</div>
                            </div>
                            <div class="icon-wrapper danger">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card info">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statUfsHoje">-</div>
                                <div class="stat-label">UFs Ativas</div>
                            </div>
                            <div class="icon-wrapper info">
                                <i class="fas fa-map"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <button class="btn btn-action btn-action-purple w-100" onclick="enviarCdHoje()">
                        <i class="fas fa-tooth fa-lg"></i>
                        <div>
                            <div>Enviar CD de Hoje</div>
                            <small class="opacity-75">Prioridade máxima</small>
                        </div>
                    </button>
                </div>
                <div class="col-lg-3 col-md-6">
                    <button class="btn btn-action btn-action-success w-100" onclick="enviarTudoHoje()">
                        <i class="fas fa-paper-plane fa-lg"></i>
                        <div>
                            <div>Enviar Tudo de Hoje</div>
                            <small class="opacity-75">Todos os pendentes</small>
                        </div>
                    </button>
                </div>
                <div class="col-lg-3 col-md-6">
                    <button class="btn btn-action btn-action-warning w-100" onclick="enviarPendentesOntem()">
                        <i class="fas fa-history fa-lg"></i>
                        <div>
                            <div>Enviar Pendentes de Ontem</div>
                            <small class="opacity-75">Atrasos do dia anterior</small>
                        </div>
                    </button>
                </div>
                <div class="col-lg-3 col-md-6">
                    <button class="btn btn-action btn-action-primary w-100" onclick="atualizarDados()">
                        <i class="fas fa-sync-alt fa-lg"></i>
                        <div>
                            <div>Atualizar Dados</div>
                            <small class="opacity-75">Recarregar estatísticas</small>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Tabela de CPFs do Dia -->
            <div class="table-container">
                <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>CPFs Pendentes de Hoje</h5>
                    <div class="quick-filter">
                        <button class="btn btn-outline-primary btn-sm active" onclick="filtrarCategoria('')" data-filter="">
                            Todos
                        </button>
                        <button class="btn btn-outline-purple btn-sm" onclick="filtrarCategoria('CD')" data-filter="CD">
                            <i class="fas fa-tooth me-1"></i>CD
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="filtrarCategoria('OUTROS')" data-filter="OUTROS">
                            Outros
                        </button>
                    </div>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="tableCpfsHoje">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllHoje">
                                    </th>
                                    <th>CPF</th>
                                    <th>Nome</th>
                                    <th>UF</th>
                                    <th>Categoria</th>
                                    <th>Inscrição</th>
                                    <th>Data Entrada</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCpfsHoje">
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Carregando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2" id="selectedCountHoje">0 selecionados</span>
                    </div>
                    <button class="btn btn-success" id="btnEnviarSelecionadosHoje" disabled onclick="enviarSelecionadosHoje()">
                        <i class="fas fa-paper-plane me-1"></i>Enviar Selecionados
                    </button>
                </div>
            </div>
        </div>

        <!-- ========== TAB: POR DATA ========== -->
        <div class="tab-pane fade" id="data" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="date-picker-card">
                        <h5 class="mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>Selecione uma Data</h5>
                        <input type="text" id="datePicker" class="form-control flatpickr-input" placeholder="Clique para selecionar...">
                        
                        <div class="mt-4" id="infoDataSelecionada" style="display: none;">
                            <h6 class="text-muted mb-3">Resumo da Data</h6>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between">
                                    <span>Entraram:</span>
                                    <strong id="dataEntraram">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Enviados:</span>
                                    <strong id="dataEnviados">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Pendentes:</span>
                                    <strong class="text-warning" id="dataPendentes">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>CD Pendentes:</span>
                                    <strong class="text-purple" id="dataCdPendentes">0</strong>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-action-purple btn-sm" onclick="enviarCdData()">
                                    <i class="fas fa-tooth me-1"></i>Enviar CD desta Data
                                </button>
                                <button class="btn btn-action-success btn-sm" onclick="enviarTudoData()">
                                    <i class="fas fa-paper-plane me-1"></i>Enviar Tudo desta Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="table-container">
                        <div class="card-header bg-white p-3">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>CPFs da Data: <span id="labelDataSelecionada">-</span></h5>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" id="tableCpfsData">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="checkAllData">
                                            </th>
                                            <th>CPF</th>
                                            <th>Nome</th>
                                            <th>UF</th>
                                            <th>Categoria</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyCpfsData">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-calendar me-2"></i>Selecione uma data para visualizar
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB: BACKLOG ========== -->
        <div class="tab-pane fade" id="backlog" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="action-card">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>Resumo de Atrasos
                        </div>
                        <div class="card-body">
                            <div class="backlog-item info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>2 dias atrás</strong>
                                        <div class="text-muted small">Ontem</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold" id="backlog2dias">-</div>
                                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="enviarBacklog(2)">Enviar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="backlog-item warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>3 dias atrás</strong>
                                        <div class="text-muted small">Atenção</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold" id="backlog3dias">-</div>
                                        <button class="btn btn-sm btn-outline-warning mt-1" onclick="enviarBacklog(3)">Enviar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="backlog-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>5+ dias atrás</strong>
                                        <div class="text-muted small">Crítico!</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold text-danger" id="backlog5dias">-</div>
                                        <button class="btn btn-sm btn-outline-danger mt-1" onclick="enviarBacklog(5)">Enviar</button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <button class="btn btn-danger w-100" onclick="enviarTodoBacklog()">
                                <i class="fas fa-fire me-2"></i>Enviar TODO o Backlog
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="action-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-map me-2"></i>UFs com Maior Atraso</span>
                            <span class="badge bg-danger" id="totalBacklog">0 pendentes</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="tableBacklogUf">
                                    <thead>
                                        <tr>
                                            <th>UF</th>
                                            <th>Pendentes</th>
                                            <th>Mais Antigo</th>
                                            <th>Dias Atraso</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyBacklogUf">
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">Carregando...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB: POR UF ========== -->
        <div class="tab-pane fade" id="ufs" role="tabpanel">
            <div class="row g-3 mb-4" id="gridUfs">
                <!-- Cards de UF serão inseridos aqui -->
            </div>
            
            <div class="table-container" id="tabelaUfSelecionada" style="display: none;">
                <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>CPFs de <span id="labelUfSelecionada">-</span></h5>
                    <div>
                        <button class="btn btn-purple btn-sm me-2" onclick="enviarCdUf()">
                            <i class="fas fa-tooth me-1"></i>Enviar CD
                        </button>
                        <button class="btn btn-success btn-sm" onclick="enviarTudoUf()">
                            <i class="fas fa-paper-plane me-1"></i>Enviar Todos
                        </button>
                    </div>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="tableCpfsUf">
                            <thead>
                                <tr>
                                    <th>CPF</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Inscrição</th>
                                    <th>Data Entrada</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCpfsUf">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB: HISTÓRICO ========== -->
        <div class="tab-pane fade" id="historico" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="action-card">
                        <div class="card-header">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Período</label>
                                <input type="text" id="historicoDateRange" class="form-control" placeholder="Selecione o período...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">UF</label>
                                <select id="historicoUf" class="form-select">
                                    <option value="">Todas as UFs</option>
                                    <?php foreach ($ufs as $uf): ?>
                                        <option value="<?= $uf ?>"><?= $uf ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100" onclick="buscarHistorico()">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                    
                    <div class="action-card mt-4">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i>Resumo do Período
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total de Envios:</span>
                                <strong id="historicoTotalEnvios">0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>CPFs Enviados:</span>
                                <strong id="historicoTotalCpfs">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>UFs Atendidas:</span>
                                <strong id="historicoTotalUfs">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="action-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-history me-2"></i>Histórico de Envios</span>
                            <button class="btn btn-sm btn-outline-success" onclick="exportarHistorico()">
                                <i class="fas fa-file-excel me-1"></i>Exportar
                            </button>
                        </div>
                        <div class="card-body" id="listaHistorico">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-search me-2"></i>Selecione um período para visualizar o histórico
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal de Resultado -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-gradient">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Resultado do Envio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-question-circle me-2"></i>Confirmar Envio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmar">
                    <i class="fas fa-check me-1"></i>Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

<script>
// Configurações
const PROXY_URL = '/api-cpf-aprovados';
const OPERADOR = '<?= htmlspecialchars($operadorNome) ?>';
const OPERADOR_ID = <?= intval($operadorId) ?>;

// Variáveis globais
let cpfsHoje = [];
let cpfsData = [];
let cpfsUf = [];
let cpfsSelecionadosHoje = new Set();
let cpfsSelecionadosData = new Set();
let dataSelecionada = null;
let ufSelecionada = null;
let categoriaFiltro = '';
let historicoEnvios = [];

// ========== FUNÇÕES UTILITÁRIAS ==========

function showLoading(text = 'Processando...') {
    $('#loadingOverlay .loading-text').text(text);
    $('#loadingOverlay').show();
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function showToast(message, type = 'success') {
    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-exclamation-circle';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-body d-flex align-items-center">
                <i class="fas ${iconClass} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    $('#toastContainer').append(toastHtml);
    const toastEl = new bootstrap.Toast(document.getElementById(toastId), { delay: 5000 });
    toastEl.show();
    
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

function formatCpf(cpf) {
    const cpfClean = String(cpf).replace(/\D/g, '').padStart(11, '0');
    return cpfClean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// ========== API REQUESTS ==========

async function proxyRequest(action, params = {}, method = 'GET', data = null) {
    let url = `${PROXY_URL}?action=${action}`;
    
    for (const [key, value] of Object.entries(params)) {
        if (value !== null && value !== undefined && value !== '') {
            url += `&${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
        }
    }
    
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    };
    
    if (data && method === 'POST') {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!response.ok || result.erro) {
        throw new Error(result.erro || `Erro ${response.status}`);
    }
    
    return result;
}

// ========== CARREGAR DADOS ==========

async function carregarDadosHoje() {
    try {
        // Carregar estatísticas do dia
        const stats = await proxyRequest('stats', { days: 1 });
        
        // Carregar CPFs disponíveis de hoje
        const disponiveis = await proxyRequest('disponiveis', { days: 0, limit: 1000 });
        cpfsHoje = disponiveis.cpfs_disponiveis || [];
        
        // Calcular estatísticas
        const hoje = new Date().toISOString().split('T')[0];
        const ontem = new Date(Date.now() - 86400000).toISOString().split('T')[0];
        
        const entraram = cpfsHoje.length;
        const cdPendentes = cpfsHoje.filter(c => c.categoria === 'CD').length;
        const ufsAtivas = [...new Set(cpfsHoje.map(c => c.cro))].length;
        
        // Buscar pendentes de ontem
        let pendentesOntem = 0;
        try {
            const dispOntem = await proxyRequest('disponiveis', { days: 1, limit: 1000 });
            const cpfsOntem = (dispOntem.cpfs_disponiveis || []).filter(c => {
                const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
                return dataItem && dataItem < hoje;
            });
            pendentesOntem = cpfsOntem.length;
        } catch (e) {}
        
        // Atualizar cards
        $('#statEntraramHoje').text(entraram);
        $('#statEnviadosHoje').text(stats.resumo?.ja_processados || 0);
        $('#statPendentesHoje').text(entraram);
        $('#statCdHoje').text(cdPendentes);
        $('#statPendentesOntem').text(pendentesOntem);
        $('#statUfsHoje').text(ufsAtivas);
        
        // Mostrar alerta se houver pendentes de ontem
        if (pendentesOntem > 0) {
            $('#alertaUrgente').show();
            $('#alertaUrgenteTexto').text(`Você tem ${pendentesOntem} CPF(s) aguardando despacho do dia anterior.`);
        } else {
            $('#alertaUrgente').hide();
        }
        
        // Renderizar tabela
        renderizarTabelaHoje();
        
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        showToast('Erro ao carregar dados: ' + error.message, 'error');
    }
}

function renderizarTabelaHoje() {
    let cpfsFiltrados = cpfsHoje;
    
    if (categoriaFiltro === 'CD') {
        cpfsFiltrados = cpfsHoje.filter(c => c.categoria === 'CD');
    } else if (categoriaFiltro === 'OUTROS') {
        cpfsFiltrados = cpfsHoje.filter(c => c.categoria && c.categoria !== 'CD');
    }
    
    if (cpfsFiltrados.length === 0) {
        $('#tbodyCpfsHoje').html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle me-2 text-success"></i>Nenhum CPF pendente!
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    cpfsFiltrados.forEach(item => {
        const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
        const isCD = item.categoria === 'CD';
        
        html += `
            <tr data-cpf="${cpfRaw}">
                <td>
                    <input type="checkbox" class="form-check-input cpf-checkbox-hoje" 
                           value="${cpfRaw}" onchange="toggleCpfHoje('${cpfRaw}')">
                </td>
                <td><code>${formatCpf(cpfRaw)}</code></td>
                <td>${item.nome || '-'}</td>
                <td><span class="badge bg-secondary">${item.cro || '-'}</span></td>
                <td>
                    ${isCD ? '<span class="cd-highlight"><i class="fas fa-tooth me-1"></i>CD</span>' : (item.categoria || '-')}
                </td>
                <td>${item.inscricao || '-'}</td>
                <td>${formatDateTime(item.data_insercao)}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="enviarCpfIndividual('${cpfRaw}')" title="Enviar">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    $('#tbodyCpfsHoje').html(html);
}

function filtrarCategoria(categoria) {
    categoriaFiltro = categoria;
    
    // Atualizar botões
    $('.quick-filter .btn').removeClass('active btn-primary btn-purple btn-secondary')
        .addClass('btn-outline-primary btn-outline-purple btn-outline-secondary');
    $(`.quick-filter .btn[data-filter="${categoria}"]`).removeClass('btn-outline-primary btn-outline-purple btn-outline-secondary')
        .addClass('active');
    
    renderizarTabelaHoje();
}

function toggleCpfHoje(cpf) {
    if (cpfsSelecionadosHoje.has(cpf)) {
        cpfsSelecionadosHoje.delete(cpf);
    } else {
        cpfsSelecionadosHoje.add(cpf);
    }
    atualizarContadorHoje();
}

function atualizarContadorHoje() {
    const count = cpfsSelecionadosHoje.size;
    $('#selectedCountHoje').text(`${count} selecionado${count !== 1 ? 's' : ''}`);
    $('#btnEnviarSelecionadosHoje').prop('disabled', count === 0);
}

// ========== AÇÕES DE ENVIO ==========

async function enviarCpfs(cpfsArray, descricao = '') {
    if (cpfsArray.length === 0) {
        showToast('Nenhum CPF para enviar', 'warning');
        return null;
    }
    
    showLoading(`Enviando ${cpfsArray.length} CPF(s)...`);
    
    try {
        let resultado = { sucesso: 0, erro: 0, detalhes: [] };
        
        // Enviar em lotes de 100
        for (let i = 0; i < cpfsArray.length; i += 100) {
            const lote = cpfsArray.slice(i, i + 100);
            const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: lote });
            
            resultado.sucesso += result.resumo?.sucesso || 0;
            resultado.erro += (result.resumo?.erro || 0) + (result.resumo?.sem_cadastro || 0);
        }
        
        // Registrar no histórico local
        registrarHistorico({
            data: new Date().toISOString(),
            operador: OPERADOR,
            quantidade: cpfsArray.length,
            sucesso: resultado.sucesso,
            erro: resultado.erro,
            descricao: descricao,
            cpfs: cpfsArray
        });
        
        showToast(`Enviados com sucesso: ${resultado.sucesso} | Erros: ${resultado.erro}`, 
                  resultado.sucesso > 0 ? 'success' : 'error');
        
        return resultado;
        
    } catch (error) {
        console.error('Erro ao enviar:', error);
        showToast('Erro ao enviar: ' + error.message, 'error');
        return null;
    } finally {
        hideLoading();
    }
}

async function enviarCdHoje() {
    const cpfsCd = cpfsHoje.filter(c => c.categoria === 'CD').map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsCd.length === 0) {
        showToast('Nenhum CD pendente hoje', 'warning');
        return;
    }
    
    if (!confirm(`Enviar ${cpfsCd.length} CD(s) de hoje para impressão?`)) return;
    
    const resultado = await enviarCpfs(cpfsCd, 'CD de Hoje');
    if (resultado) {
        await carregarDadosHoje();
    }
}

async function enviarTudoHoje() {
    const cpfsTodos = cpfsHoje.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsTodos.length === 0) {
        showToast('Nenhum CPF pendente hoje', 'warning');
        return;
    }
    
    if (!confirm(`Enviar TODOS os ${cpfsTodos.length} CPF(s) de hoje para impressão?`)) return;
    
    const resultado = await enviarCpfs(cpfsTodos, 'Todos de Hoje');
    if (resultado) {
        await carregarDadosHoje();
    }
}

async function enviarPendentesOntem() {
    showLoading('Buscando pendentes de ontem...');
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const disponiveis = await proxyRequest('disponiveis', { days: 2, limit: 1000 });
        const cpfsOntem = (disponiveis.cpfs_disponiveis || []).filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            return dataItem && dataItem < hoje;
        });
        
        hideLoading();
        
        if (cpfsOntem.length === 0) {
            showToast('Nenhum CPF pendente de ontem', 'success');
            return;
        }
        
        const cpfsArray = cpfsOntem.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
        
        if (!confirm(`Enviar ${cpfsArray.length} CPF(s) pendentes de ontem?`)) return;
        
        const resultado = await enviarCpfs(cpfsArray, 'Pendentes de Ontem');
        if (resultado) {
            await carregarDadosHoje();
        }
        
    } catch (error) {
        hideLoading();
        showToast('Erro: ' + error.message, 'error');
    }
}

async function enviarSelecionadosHoje() {
    const cpfsArray = Array.from(cpfsSelecionadosHoje);
    
    if (cpfsArray.length === 0) {
        showToast('Selecione pelo menos um CPF', 'warning');
        return;
    }
    
    const resultado = await enviarCpfs(cpfsArray, 'Selecionados');
    if (resultado) {
        cpfsSelecionadosHoje.clear();
        atualizarContadorHoje();
        await carregarDadosHoje();
    }
}

async function enviarCpfIndividual(cpf) {
    const resultado = await enviarCpfs([cpf], 'Individual');
    if (resultado) {
        await carregarDadosHoje();
    }
}

// ========== TAB: POR DATA ==========

function inicializarDatePicker() {
    flatpickr('#datePicker', {
        locale: 'pt',
        dateFormat: 'd/m/Y',
        maxDate: 'today',
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                dataSelecionada = selectedDates[0].toISOString().split('T')[0];
                carregarCpfsData();
            }
        }
    });
    
    flatpickr('#historicoDateRange', {
        locale: 'pt',
        mode: 'range',
        dateFormat: 'd/m/Y',
        maxDate: 'today'
    });
}

async function carregarCpfsData() {
    if (!dataSelecionada) return;
    
    showLoading('Carregando CPFs da data...');
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const diffDays = Math.ceil((new Date(hoje) - new Date(dataSelecionada)) / (1000 * 60 * 60 * 24));
        
        const disponiveis = await proxyRequest('disponiveis', { days: diffDays + 1, limit: 1000 });
        cpfsData = (disponiveis.cpfs_disponiveis || []).filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            return dataItem === dataSelecionada;
        });
        
        // Atualizar info
        const dataFormatada = new Date(dataSelecionada + 'T00:00:00').toLocaleDateString('pt-BR');
        $('#labelDataSelecionada').text(dataFormatada);
        $('#infoDataSelecionada').show();
        
        $('#dataEntraram').text(cpfsData.length);
        $('#dataPendentes').text(cpfsData.length);
        $('#dataCdPendentes').text(cpfsData.filter(c => c.categoria === 'CD').length);
        
        // Renderizar tabela
        renderizarTabelaData();
        
    } catch (error) {
        showToast('Erro: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function renderizarTabelaData() {
    if (cpfsData.length === 0) {
        $('#tbodyCpfsData').html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">Nenhum CPF encontrado para esta data</td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    cpfsData.forEach(item => {
        const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
        const isCD = item.categoria === 'CD';
        
        html += `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input cpf-checkbox-data" value="${cpfRaw}">
                </td>
                <td><code>${formatCpf(cpfRaw)}</code></td>
                <td>${item.nome || '-'}</td>
                <td><span class="badge bg-secondary">${item.cro || '-'}</span></td>
                <td>${isCD ? '<span class="cd-highlight">CD</span>' : (item.categoria || '-')}</td>
                <td><span class="badge bg-warning">Pendente</span></td>
            </tr>
        `;
    });
    
    $('#tbodyCpfsData').html(html);
}

async function enviarCdData() {
    const cpfsCd = cpfsData.filter(c => c.categoria === 'CD').map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsCd.length === 0) {
        showToast('Nenhum CD pendente nesta data', 'warning');
        return;
    }
    
    if (!confirm(`Enviar ${cpfsCd.length} CD(s) desta data?`)) return;
    
    const resultado = await enviarCpfs(cpfsCd, `CD de ${dataSelecionada}`);
    if (resultado) {
        await carregarCpfsData();
    }
}

async function enviarTudoData() {
    const cpfsTodos = cpfsData.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsTodos.length === 0) {
        showToast('Nenhum CPF pendente nesta data', 'warning');
        return;
    }
    
    if (!confirm(`Enviar TODOS os ${cpfsTodos.length} CPF(s) desta data?`)) return;
    
    const resultado = await enviarCpfs(cpfsTodos, `Todos de ${dataSelecionada}`);
    if (resultado) {
        await carregarCpfsData();
    }
}

// ========== TAB: BACKLOG ==========

async function carregarBacklog() {
    showLoading('Carregando backlog...');
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const disponiveis = await proxyRequest('disponiveis', { days: 30, limit: 5000 });
        const todosCpfs = disponiveis.cpfs_disponiveis || [];
        
        // Calcular backlog por dias
        let backlog2 = 0, backlog3 = 0, backlog5 = 0;
        const backlogPorUf = {};
        
        todosCpfs.forEach(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            if (!dataItem || dataItem >= hoje) return;
            
            const diffDays = Math.ceil((new Date(hoje) - new Date(dataItem)) / (1000 * 60 * 60 * 24));
            
            if (diffDays === 1) backlog2++;
            else if (diffDays >= 2 && diffDays <= 4) backlog3++;
            else if (diffDays >= 5) backlog5++;
            
            // Por UF
            const uf = c.cro || 'N/A';
            if (!backlogPorUf[uf]) {
                backlogPorUf[uf] = { count: 0, maisAntigo: dataItem };
            }
            backlogPorUf[uf].count++;
            if (dataItem < backlogPorUf[uf].maisAntigo) {
                backlogPorUf[uf].maisAntigo = dataItem;
            }
        });
        
        // Atualizar cards
        $('#backlog2dias').text(backlog2);
        $('#backlog3dias').text(backlog3);
        $('#backlog5dias').text(backlog5);
        $('#totalBacklog').text(`${backlog2 + backlog3 + backlog5} pendentes`);
        $('#backlogCount').text(backlog2 + backlog3 + backlog5).toggle(backlog2 + backlog3 + backlog5 > 0);
        
        // Renderizar tabela de UFs
        let html = '';
        const ufsOrdenadas = Object.entries(backlogPorUf).sort((a, b) => b[1].count - a[1].count);
        
        ufsOrdenadas.forEach(([uf, data]) => {
            const diffDays = Math.ceil((new Date(hoje) - new Date(data.maisAntigo)) / (1000 * 60 * 60 * 24));
            const badgeClass = diffDays >= 5 ? 'bg-danger' : diffDays >= 3 ? 'bg-warning' : 'bg-info';
            
            html += `
                <tr>
                    <td><strong>${uf}</strong></td>
                    <td><span class="badge bg-secondary">${data.count}</span></td>
                    <td>${formatDate(data.maisAntigo)}</td>
                    <td><span class="badge ${badgeClass}">${diffDays} dia(s)</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-success" onclick="enviarBacklogUf('${uf}')">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        if (html === '') {
            html = '<tr><td colspan="5" class="text-center py-3 text-success"><i class="fas fa-check-circle me-2"></i>Nenhum backlog!</td></tr>';
        }
        
        $('#tbodyBacklogUf').html(html);
        
    } catch (error) {
        showToast('Erro: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function enviarBacklog(dias) {
    showLoading('Buscando CPFs...');
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const disponiveis = await proxyRequest('disponiveis', { days: 30, limit: 5000 });
        const todosCpfs = disponiveis.cpfs_disponiveis || [];
        
        const cpfsFiltrados = todosCpfs.filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            if (!dataItem || dataItem >= hoje) return false;
            
            const diffDays = Math.ceil((new Date(hoje) - new Date(dataItem)) / (1000 * 60 * 60 * 24));
            
            if (dias === 2) return diffDays === 1;
            if (dias === 3) return diffDays >= 2 && diffDays <= 4;
            if (dias === 5) return diffDays >= 5;
            return false;
        });
        
        hideLoading();
        
        if (cpfsFiltrados.length === 0) {
            showToast('Nenhum CPF encontrado', 'warning');
            return;
        }
        
        const cpfsArray = cpfsFiltrados.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
        
        if (!confirm(`Enviar ${cpfsArray.length} CPF(s) com ${dias}+ dias de atraso?`)) return;
        
        const resultado = await enviarCpfs(cpfsArray, `Backlog ${dias}+ dias`);
        if (resultado) {
            await carregarBacklog();
        }
        
    } catch (error) {
        hideLoading();
        showToast('Erro: ' + error.message, 'error');
    }
}

async function enviarTodoBacklog() {
    showLoading('Buscando todo o backlog...');
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const disponiveis = await proxyRequest('disponiveis', { days: 30, limit: 5000 });
        const todosCpfs = (disponiveis.cpfs_disponiveis || []).filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            return dataItem && dataItem < hoje;
        });
        
        hideLoading();
        
        if (todosCpfs.length === 0) {
            showToast('Nenhum backlog encontrado', 'success');
            return;
        }
        
        const cpfsArray = todosCpfs.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
        
        if (!confirm(`Enviar TODO o backlog (${cpfsArray.length} CPFs)?`)) return;
        
        const resultado = await enviarCpfs(cpfsArray, 'Todo o Backlog');
        if (resultado) {
            await carregarBacklog();
            await carregarDadosHoje();
        }
        
    } catch (error) {
        hideLoading();
        showToast('Erro: ' + error.message, 'error');
    }
}

async function enviarBacklogUf(uf) {
    showLoading(`Buscando CPFs de ${uf}...`);
    
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const disponiveis = await proxyRequest('disponiveis', { days: 30, limit: 5000, uf: uf });
        const cpfsFiltrados = (disponiveis.cpfs_disponiveis || []).filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            return dataItem && dataItem < hoje;
        });
        
        hideLoading();
        
        if (cpfsFiltrados.length === 0) {
            showToast(`Nenhum backlog para ${uf}`, 'success');
            return;
        }
        
        const cpfsArray = cpfsFiltrados.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
        
        if (!confirm(`Enviar ${cpfsArray.length} CPF(s) de ${uf}?`)) return;
        
        const resultado = await enviarCpfs(cpfsArray, `Backlog ${uf}`);
        if (resultado) {
            await carregarBacklog();
        }
        
    } catch (error) {
        hideLoading();
        showToast('Erro: ' + error.message, 'error');
    }
}

// ========== TAB: POR UF ==========

async function carregarUfs() {
    showLoading('Carregando UFs...');
    
    try {
        const stats = await proxyRequest('stats', { days: 10 });
        const porUf = stats.por_uf || [];
        
        let html = '';
        porUf.forEach(item => {
            html += `
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <div class="uf-card ${ufSelecionada === item.uf ? 'selected' : ''}" onclick="selecionarUf('${item.uf}')">
                        <div class="text-center">
                            <div class="uf-name">${item.uf}</div>
                            <div class="uf-stat">Disponíveis</div>
                            <div class="uf-stat-value text-success">${item.cpfs_unicos || item.total || 0}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        if (html === '') {
            html = '<div class="col-12 text-center py-4 text-muted">Nenhuma UF com CPFs disponíveis</div>';
        }
        
        $('#gridUfs').html(html);
        
    } catch (error) {
        showToast('Erro: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function selecionarUf(uf) {
    ufSelecionada = uf;
    
    // Atualizar visual
    $('.uf-card').removeClass('selected');
    $(`.uf-card:contains("${uf}")`).addClass('selected');
    
    // Carregar CPFs da UF
    showLoading(`Carregando CPFs de ${uf}...`);
    
    try {
        const disponiveis = await proxyRequest('disponiveis', { days: 10, limit: 500, uf: uf });
        cpfsUf = disponiveis.cpfs_disponiveis || [];
        
        $('#labelUfSelecionada').text(uf);
        $('#tabelaUfSelecionada').show();
        
        renderizarTabelaUf();
        
    } catch (error) {
        showToast('Erro: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function renderizarTabelaUf() {
    if (cpfsUf.length === 0) {
        $('#tbodyCpfsUf').html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-success">
                    <i class="fas fa-check-circle me-2"></i>Nenhum CPF pendente para esta UF
                </td>
            </tr>
        `);
        return;
    }
    
    let html = '';
    cpfsUf.forEach(item => {
        const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
        const isCD = item.categoria === 'CD';
        
        html += `
            <tr>
                <td><code>${formatCpf(cpfRaw)}</code></td>
                <td>${item.nome || '-'}</td>
                <td>${isCD ? '<span class="cd-highlight">CD</span>' : (item.categoria || '-')}</td>
                <td>${item.inscricao || '-'}</td>
                <td>${formatDateTime(item.data_insercao)}</td>
                <td><span class="badge bg-warning">Pendente</span></td>
            </tr>
        `;
    });
    
    $('#tbodyCpfsUf').html(html);
}

async function enviarCdUf() {
    const cpfsCd = cpfsUf.filter(c => c.categoria === 'CD').map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsCd.length === 0) {
        showToast(`Nenhum CD pendente em ${ufSelecionada}`, 'warning');
        return;
    }
    
    if (!confirm(`Enviar ${cpfsCd.length} CD(s) de ${ufSelecionada}?`)) return;
    
    const resultado = await enviarCpfs(cpfsCd, `CD de ${ufSelecionada}`);
    if (resultado) {
        await selecionarUf(ufSelecionada);
    }
}

async function enviarTudoUf() {
    const cpfsTodos = cpfsUf.map(c => c.cpf_raw || c.cpf.replace(/\D/g, ''));
    
    if (cpfsTodos.length === 0) {
        showToast(`Nenhum CPF pendente em ${ufSelecionada}`, 'warning');
        return;
    }
    
    if (!confirm(`Enviar TODOS os ${cpfsTodos.length} CPF(s) de ${ufSelecionada}?`)) return;
    
    const resultado = await enviarCpfs(cpfsTodos, `Todos de ${ufSelecionada}`);
    if (resultado) {
        await selecionarUf(ufSelecionada);
        await carregarUfs();
    }
}

// ========== TAB: HISTÓRICO ==========

function registrarHistorico(registro) {
    // Salvar no localStorage
    const historico = JSON.parse(localStorage.getItem('historicoEnvios') || '[]');
    historico.unshift(registro);
    
    // Manter apenas últimos 100 registros
    if (historico.length > 100) {
        historico.pop();
    }
    
    localStorage.setItem('historicoEnvios', JSON.stringify(historico));
}

function buscarHistorico() {
    const historico = JSON.parse(localStorage.getItem('historicoEnvios') || '[]');
    const ufFiltro = $('#historicoUf').val();
    
    let historicoFiltrado = historico;
    if (ufFiltro) {
        historicoFiltrado = historico.filter(h => h.descricao && h.descricao.includes(ufFiltro));
    }
    
    // Calcular resumo
    $('#historicoTotalEnvios').text(historicoFiltrado.length);
    $('#historicoTotalCpfs').text(historicoFiltrado.reduce((sum, h) => sum + (h.quantidade || 0), 0));
    
    const ufsAtendidas = new Set();
    historicoFiltrado.forEach(h => {
        if (h.descricao) {
            const match = h.descricao.match(/[A-Z]{2}/);
            if (match) ufsAtendidas.add(match[0]);
        }
    });
    $('#historicoTotalUfs').text(ufsAtendidas.size);
    
    // Renderizar lista
    if (historicoFiltrado.length === 0) {
        $('#listaHistorico').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-inbox me-2"></i>Nenhum registro encontrado
            </div>
        `);
        return;
    }
    
    let html = '';
    historicoFiltrado.slice(0, 50).forEach(h => {
        html += `
            <div class="historico-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${h.descricao || 'Envio'}</strong>
                        <div class="text-muted small">
                            <i class="fas fa-user me-1"></i>${h.operador || 'Sistema'}
                            <span class="ms-2"><i class="fas fa-clock me-1"></i>${formatDateTime(h.data)}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success">${h.sucesso || 0} sucesso</span>
                        ${h.erro > 0 ? `<span class="badge bg-danger ms-1">${h.erro} erro</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#listaHistorico').html(html);
}

function exportarHistorico() {
    const historico = JSON.parse(localStorage.getItem('historicoEnvios') || '[]');
    
    if (historico.length === 0) {
        showToast('Nenhum histórico para exportar', 'warning');
        return;
    }
    
    // Criar CSV
    let csv = 'Data/Hora,Operador,Descrição,Quantidade,Sucesso,Erros\n';
    historico.forEach(h => {
        csv += `"${formatDateTime(h.data)}","${h.operador}","${h.descricao}",${h.quantidade},${h.sucesso},${h.erro}\n`;
    });
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `historico_envios_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showToast('Histórico exportado com sucesso!');
}

// ========== INICIALIZAÇÃO ==========

async function atualizarDados() {
    await carregarDadosHoje();
    showToast('Dados atualizados!');
}

function atualizarRelogio() {
    const agora = new Date();
    $('#clockTime').text(agora.toLocaleTimeString('pt-BR'));
}

$(document).ready(function() {
    // Inicializar date pickers
    inicializarDatePicker();
    
    // Carregar dados iniciais
    carregarDadosHoje();
    
    // Checkbox select all
    $('#checkAllHoje').change(function() {
        const checked = $(this).is(':checked');
        $('.cpf-checkbox-hoje').prop('checked', checked);
        
        if (checked) {
            cpfsHoje.forEach(item => {
                const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
                cpfsSelecionadosHoje.add(cpfRaw);
            });
        } else {
            cpfsSelecionadosHoje.clear();
        }
        
        atualizarContadorHoje();
    });
    
    // Carregar dados ao mudar de tab
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        
        if (target === '#backlog') {
            carregarBacklog();
        } else if (target === '#ufs') {
            carregarUfs();
        } else if (target === '#historico') {
            buscarHistorico();
        }
    });
    
    // Atualizar relógio a cada segundo
    setInterval(atualizarRelogio, 1000);
    
    // Auto-refresh a cada 5 minutos
    setInterval(function() {
        if ($('#hoje-tab').hasClass('active')) {
            carregarDadosHoje();
        }
    }, 300000);
});
</script>

</body>
</html>

