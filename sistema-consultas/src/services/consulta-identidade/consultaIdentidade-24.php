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

if (Session::get('grupo') != 0 && (!isset($row['CI24acesso']) || $row['CI24acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='/consulta-identidade';
    </script>";
    exit;
}

// Lista de UFs para os selects
$ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Carteiras - CPFs Aprovados para Impressão</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(30, 60, 114, 0.3);
        }
        
        .page-header h1 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-card .icon-wrapper.primary { background: var(--primary-gradient); }
        .stat-card .icon-wrapper.success { background: var(--success-gradient); }
        .stat-card .icon-wrapper.warning { background: var(--warning-gradient); }
        .stat-card .icon-wrapper.info { background: var(--info-gradient); }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .data-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .data-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
        }
        
        .data-card .card-body {
            padding: 1.25rem;
        }
        
        .btn-gradient-primary {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-gradient-primary:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.4);
        }
        
        .btn-gradient-success {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-gradient-success:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
        }
        
        .badge-status {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-disponivel { background: #d4edda; color: #155724; }
        .badge-processando { background: #fff3cd; color: #856404; }
        .badge-ja-existe { background: #cce5ff; color: #004085; }
        .badge-erro { background: #f8d7da; color: #721c24; }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #495057;
            white-space: nowrap;
        }
        
        .table tbody tr:hover {
            background-color: #f0f7ff !important;
        }
        
        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .select-all-container {
            background: #e3f2fd;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .action-bar {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem 1.25rem;
            border-top: 1px solid #e9ecef;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
            display: none;
        }
        
        .action-bar.show {
            display: block;
        }
        
        .result-modal .modal-header {
            background: var(--primary-gradient);
            color: white;
        }
        
        .result-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .result-summary-item {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .result-summary-item.success { background: #d4edda; }
        .result-summary-item.warning { background: #fff3cd; }
        .result-summary-item.danger { background: #f8d7da; }
        .result-summary-item.info { background: #cce5ff; }
        
        .result-summary-item .value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .result-summary-item .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #666;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
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
        
        .loading-overlay .loading-text {
            margin-top: 1rem;
            font-size: 1.1rem;
            color: #495057;
        }
        
        .uf-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.75rem;
        }
        
        .uf-stat-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .uf-stat-item:hover {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        
        .uf-stat-item.selected {
            background: #bbdefb;
            border-color: #1976d2;
        }
        
        .uf-stat-item .uf-name {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .uf-stat-item .uf-count {
            background: var(--primary-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .details-modal .cpf-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .nav-tabs .nav-link {
            border-radius: 8px 8px 0 0;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }
        
        .tab-content {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 1.5rem;
        }
        
        /* Estilos para seleção de checkbox */
        .form-check-input:checked {
            background-color: #1e3c72;
            border-color: #1e3c72;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .ufs-detalhadas {
            font-size: 0.9rem;
            line-height: 1.8;
            word-wrap: break-word;
        }
        
        .ufs-detalhadas strong {
            color: #1e3c72;
            font-weight: 700;
            margin-right: 2px;
        }
        
        .ufs-detalhadas strong.text-primary {
            color: #0d6efd !important;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        
        /* Badge de ARs Disponíveis */
        .ar-disponivel-badge {
            text-align: center;
        }
        
        .ar-disponivel-badge .badge {
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .ar-disponivel-badge .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        /* Linha de total na tabela */
        .table-dark.fw-bold td {
            border-top: 2px solid #dee2e6;
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner"></div>
    <div class="loading-text">Processando...</div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-id-card me-2"></i>Gestão de Carteiras Profissionais</h1>
                <p>Visualize, verifique e envie CPFs aprovados para impressão de identidades</p>
            </div>
            <div class="text-end">
                <div class="d-flex align-items-center gap-3">
                    <!-- ARs Disponíveis -->
                    <div class="ar-disponivel-badge" title="Quantidade de etiquetas AR disponíveis para impressão">
                        <span class="badge bg-success fs-5 px-3 py-2" id="badgeArsDisponiveis" style="cursor: pointer;" onclick="atualizarArsDisponiveis()">
                            <i class="fas fa-barcode me-2"></i>
                            <span id="qtdArsDisponiveis">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span> ARs
                        </span>
                        <div class="small text-white-50 mt-1">
                            <i class="fas fa-sync-alt me-1"></i>Clique para atualizar
                        </div>
                    </div>
                    <!-- Data/Hora -->
                    <div>
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i:s') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Navegação -->
    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="fas fa-chart-pie me-2"></i>Visão Geral
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="disponiveis-tab" data-bs-toggle="tab" data-bs-target="#disponiveis" type="button" role="tab">
                <i class="fas fa-list me-2"></i>CPFs Disponíveis
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="inserir-tab" data-bs-toggle="tab" data-bs-target="#inserir" type="button" role="tab">
                <i class="fas fa-plus-circle me-2"></i>Inserir em Lote
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="consulta-tab" data-bs-toggle="tab" data-bs-target="#consulta" type="button" role="tab">
                <i class="fas fa-search me-2"></i>Consultar CPF
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="por-data-tab" data-bs-toggle="tab" data-bs-target="#por-data" type="button" role="tab">
                <i class="fas fa-calendar-alt me-2"></i>Por Data de Inscrição
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="mainTabsContent">
        <!-- Tab: Visão Geral -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
    <!-- Filtros -->
            <div class="filter-card">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Período (dias)</label>
                        <select id="filterDays" class="form-select">
                            <option value="5">Últimos 5 dias</option>
                            <option value="10" selected>Últimos 10 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30">Últimos 30 dias</option>
                            <option value="60">Últimos 60 dias</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-gradient-primary" onclick="carregarEstatisticas()">
                            <i class="fas fa-sync-alt me-2"></i>Atualizar Estatísticas
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Cards de Estatísticas -->
            <div class="row g-4 mb-4" id="statsCards">
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="bottom" 
                         title="CPFs com foto aprovada que foram sincronizados no período selecionado">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statTotal">-</div>
                                <div class="stat-label">Total de CPFs <i class="fas fa-info-circle text-muted small"></i></div>
                            </div>
                            <div class="icon-wrapper primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="bottom"
                         title="CPFs prontos para enviar para impressão (não possuem carteira e não estão na fila)">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statDisponiveis">-</div>
                                <div class="stat-label">Disponíveis <i class="fas fa-info-circle text-muted small"></i></div>
                            </div>
                            <div class="icon-wrapper success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="bottom"
                         title="CPFs que já possuem carteira impressa ou estão na fila de impressão">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statProcessados">-</div>
                                <div class="stat-label">Já Processados <i class="fas fa-info-circle text-muted small"></i></div>
                            </div>
                            <div class="icon-wrapper info">
                                <i class="fas fa-print"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="bottom"
                         title="Quantidade de estados (UFs) que possuem CPFs no período selecionado">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statUfs">-</div>
                                <div class="stat-label">UFs com Registros <i class="fas fa-info-circle text-muted small"></i></div>
                            </div>
                            <div class="icon-wrapper warning">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grid de UFs -->
            <div class="data-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i>CPFs Disponíveis por UF</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAutorizarUfSelecionada" disabled>
                        <i class="fas fa-paper-plane me-1"></i>Enviar UF Selecionada
                            </button>
                        </div>
                <div class="card-body">
                    <div class="uf-stats-grid" id="ufStatsGrid">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p>Carregando estatísticas...</p>
                    </div>
                </div>
        </div>
    </div>

            <!-- Estatísticas por Dia -->
            <div class="data-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>CPFs por Dia</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success me-2" id="btnFiltrarDiaAtual" onclick="filtrarDiaAtual()">
                            <i class="fas fa-calendar-day me-1"></i>Ver Apenas Hoje
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="enviarMaisRecentes()" title="Enviar apenas CPFs das últimas 2 horas">
                            <i class="fas fa-clock me-1"></i>Enviar Mais Recentes
                        </button>
                        <button type="button" class="btn btn-sm btn-gradient-success" id="btnEnviarDiaAtual" onclick="enviarTodosDiaAtual()" style="display: none;">
                            <i class="fas fa-paper-plane me-1"></i>Enviar Todos do Dia Atual
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tableDailyStats">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Dia da Semana</th>
                                    <th>Total de CPFs</th>
                                    <th>Disponíveis</th>
                                    <th>UFs</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                        </div>
                    </div>
                </div>
        
        <!-- Tab: CPFs Disponíveis -->
        <div class="tab-pane fade" id="disponiveis" role="tabpanel">
            <!-- Filtros -->
            <div class="filter-card">
                <div class="row align-items-end g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">UF</label>
                        <select id="filterUfDisponiveis" class="form-select">
                            <option value="">Todas as UFs</option>
                            <?php foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
            </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Categoria</label>
                        <select id="filterCategoriaDisponiveis" class="form-select">
                            <option value="">Todas</option>
                            <option value="CD">CD</option>
                            <option value="OUTROS">Outros (TPD, ASB, TSB, APD)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Período</label>
                        <select id="filterDaysDisponiveis" class="form-select">
                            <option value="0">Apenas Hoje</option>
                            <option value="5">5 dias</option>
                            <option value="10" selected>10 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30">30 dias</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Limite</label>
                        <select id="filterLimitDisponiveis" class="form-select">
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <button type="button" class="btn btn-gradient-primary w-100" onclick="carregarDisponiveis()">
                            <i class="fas fa-search me-2"></i>Buscar CPFs
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <button type="button" class="btn btn-gradient-success w-100" id="btnEnviarTodosHoje" onclick="enviarTodosHoje()">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Todos de Hoje
                        </button>
                    </div>
        </div>
    </div>

            <!-- Info de seleção -->
            <div class="select-all-container" id="selectionInfo" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" id="selectAllCpfs">
                        <label class="form-check-label fw-semibold" for="selectAllCpfs">
                            Selecionar todos (<span id="totalCpfsDisponiveis">0</span> CPFs)
                        </label>
                </div>
                    <div>
                        <span class="badge bg-primary me-2" id="selectedCount">0 selecionados</span>
                        <button type="button" class="btn btn-sm btn-gradient-success" id="btnEnviarSelecionados" disabled onclick="enviarSelecionados()">
                            <i class="fas fa-paper-plane me-1"></i>Enviar para Impressão
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de CPFs Disponíveis -->
            <div class="data-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tableDisponiveis">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllTable">
                                    </th>
                                    <th>CPF</th>
                                    <th>Nome</th>
                                    <th>CRO</th>
                                    <th>Categoria</th>
                                    <th>Inscrição</th>
                                    <th>ID Profissional</th>
                                    <th>Data Inserção</th>
                                    <th style="width: 80px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Clique em "Buscar CPFs" para carregar os dados
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Inserir em Lote -->
        <div class="tab-pane fade" id="inserir" role="tabpanel">
            <div class="row">
        <div class="col-md-6">
                    <div class="data-card">
                <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Inserir CPFs Manualmente</h5>
                </div>
                <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lista de CPFs (um por linha)</label>
                                <textarea id="cpfsManual" class="form-control" rows="10" 
                                    placeholder="Digite os CPFs, um por linha:&#10;12345678900&#10;98765432100&#10;11122233344"></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Máximo de 100 CPFs por vez. Apenas números, sem pontos ou traços.
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <span id="countCpfsManual">0</span> CPFs informados
                                </span>
                                <button type="button" class="btn btn-gradient-success" onclick="enviarCpfsManual()">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar para Impressão
                                </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
                    <div class="data-card">
                <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Autorizar Toda uma UF</h5>
                </div>
                <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Selecione a UF</label>
                                <select id="ufAutorizar" class="form-select form-select-lg">
                                    <option value="">Selecione uma UF...</option>
                                    <?php foreach ($ufs as $uf): ?>
                                        <option value="<?= $uf ?>"><?= $uf ?></option>
                                    <?php endforeach; ?>
                                </select>
                    </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Período (dias)</label>
                                    <select id="daysAutorizar" class="form-select">
                                        <option value="5">5 dias</option>
                                        <option value="10" selected>10 dias</option>
                                        <option value="15">15 dias</option>
                                        <option value="30">30 dias</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tamanho do Lote</label>
                                    <select id="batchSizeAutorizar" class="form-select">
                                        <option value="25">25</option>
                                        <option value="50" selected>50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Atenção:</strong> Esta ação irá processar TODOS os CPFs disponíveis da UF selecionada.
                            </div>
                            <button type="button" class="btn btn-gradient-primary btn-lg w-100" onclick="autorizarUf()">
                                <i class="fas fa-check-double me-2"></i>Autorizar Todos da UF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Consultar CPF -->
        <div class="tab-pane fade" id="consulta" role="tabpanel">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="data-card">
                <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-search me-2"></i>Consultar Detalhes de um CPF</h5>
                </div>
                <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">CPF</label>
                                    <input type="text" id="cpfConsulta" class="form-control form-control-lg" 
                                        placeholder="Digite o CPF (apenas números)" maxlength="11">
                    </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-gradient-primary btn-lg w-100" onclick="consultarCpf()">
                                        <i class="fas fa-search me-2"></i>Consultar
                                    </button>
                                </div>
                            </div>
                            
                            <div id="resultadoConsultaCpf" style="display: none;">
                                <!-- Resultado da consulta será exibido aqui -->
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Tab: Por Data de Inscrição -->
        <div class="tab-pane fade" id="por-data" role="tabpanel">
            <!-- Filtros para Estatísticas -->
            <div class="filter-card mb-4">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros de Consulta</h5>
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Data Início</label>
                        <input type="date" id="dataInicioStats" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Data Fim</label>
                        <input type="date" id="dataFimStats" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">UF (opcional)</label>
                        <select id="ufStatsPorData" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Agrupar Por</label>
                        <select id="agruparPorStats" class="form-select">
                            <option value="dia" selected>Dia</option>
                            <option value="mes">Mês</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <button type="button" class="btn btn-gradient-primary w-100" onclick="carregarStatsPorData()">
                            <i class="fas fa-chart-bar me-2"></i>Buscar Estatísticas
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="apenasPendentesStats" checked>
                            <label class="form-check-label" for="apenasPendentesStats">
                                Mostrar apenas pendentes (não enviados)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="row g-4 mb-4" id="resumoStatsPorData" style="display: none;">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statTotalPorData">-</div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="icon-wrapper primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statPendentesPorData">-</div>
                                <div class="stat-label">Pendentes</div>
                            </div>
                            <div class="icon-wrapper warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statEnviadosPorData">-</div>
                                <div class="stat-label">Enviados</div>
                            </div>
                            <div class="icon-wrapper info">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" id="statProcessadosPorData">-</div>
                                <div class="stat-label">Processados</div>
                            </div>
                            <div class="icon-wrapper success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Estatísticas por Dia/Mês -->
            <div class="data-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Estatísticas por Período</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="tableStatsPorData">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Total</th>
                                    <th>Pendentes</th>
                                    <th>Enviados</th>
                                    <th>Processados</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>Use os filtros acima para buscar estatísticas
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Estatísticas por UF -->
            <div class="data-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i>Estatísticas por UF</h5>
                </div>
                <div class="card-body">
                    <div id="statsPorUfContainer">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>Busque estatísticas para ver os dados por UF
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Processamento -->
            <div class="data-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Processar por Data de Inscrição</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção:</strong> Esta ação irá processar/enfileirar profissionais para criação de identity.
                        O processamento é feito em lotes e pode levar alguns minutos.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Data Início</label>
                            <input type="date" id="dataInicioProcessar" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Data Fim</label>
                            <input type="date" id="dataFimProcessar" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">UF (opcional)</label>
                            <select id="ufProcessarPorData" class="form-select">
                                <option value="">Todas</option>
                                <?php foreach ($ufs as $uf): ?>
                                    <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Limite</label>
                            <select id="limiteProcessarPorData" class="form-select">
                                <option value="500">500</option>
                                <option value="1000" selected>1.000</option>
                                <option value="2500">2.500</option>
                                <option value="5000">5.000 (máx)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <button type="button" class="btn btn-gradient-success w-100" onclick="processarPorData()">
                                <i class="fas fa-play me-2"></i>Processar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal de Resultado -->
<div class="modal fade result-modal" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Resultado do Processamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            <div class="modal-body">
                <div id="resultContent">
                    <!-- Conteúdo do resultado será inserido aqui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do CPF -->
<div class="modal fade details-modal" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Detalhes do CPF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">
                    <!-- Conteúdo dos detalhes será inserido aqui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-gradient-success" id="btnEnviarDetalhe" style="display: none;">
                    <i class="fas fa-paper-plane me-1"></i>Enviar para Impressão
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
// Configurações da API - Usando proxy local para evitar CORS
const PROXY_URL = '/api-cpf-aprovados';

// Variáveis globais
let cpfsSelecionados = new Set();
let cpfsDisponiveis = [];
let estatisticasUf = [];
let ufSelecionada = null;
let detalhesPorDia = {}; // Detalhes de UFs por dia (preenchido diretamente da API em detalhes_uf)

// Funções utilitárias
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
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Funções de API - Usando proxy PHP local
async function proxyRequest(action, params = {}, method = 'GET', data = null) {
    let url = `${PROXY_URL}?action=${action}`;
    
    // Adicionar parâmetros à URL para GET
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
        credentials: 'same-origin' // Enviar cookies de sessão
    };
    
    if (data && method === 'POST') {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!response.ok || result.erro) {
        throw new Error(result.erro || `Erro ${response.status}: ${response.statusText}`);
    }
    
    return result;
}

// Carregar estatísticas
async function carregarEstatisticas() {
    showLoading('Carregando estatísticas...');
    
    try {
        const days = $('#filterDays').val();
        const data = await proxyRequest('stats', { days });
        
        // Verificar se há erro na resposta
        if (data.error || data.erro) {
            throw new Error(data.error || data.erro || 'Erro desconhecido na API');
        }
        
        // Debug: verificar estrutura da resposta
        console.log('=== Resposta completa da API stats ===');
        console.log('Estrutura:', {
            temResumo: !!data.resumo,
            temPorUf: !!data.por_uf,
            temPorDia: !!data.por_dia,
            totalDias: data.por_dia?.length || 0
        });
        
        // Atualizar cards (API pode retornar diferentes nomes de campos)
        $('#statTotal').text((data.resumo?.total_cpfs || data.resumo?.total_registros || 0).toLocaleString('pt-BR'));
        $('#statDisponiveis').text((data.resumo?.podem_autorizar || 0).toLocaleString('pt-BR'));
        $('#statProcessados').text((data.resumo?.ja_processados || 0).toLocaleString('pt-BR'));
        $('#statUfs').text((data.resumo?.total_ufs || 0).toLocaleString('pt-BR'));
        
        // Atualizar grid de UFs
        estatisticasUf = data.por_uf || [];
        renderizarGridUfs();
        
        // Processar detalhes por UF diretamente da API
        const dias = data.por_dia || [];
        
        // Debug: verificar estrutura dos dados recebidos
        console.log('=== DEBUG: Dados recebidos da API ===');
        console.log('Resposta completa:', data);
        console.log('Primeiro dia completo:', dias[0]);
        console.log('Campos disponíveis no primeiro dia:', dias[0] ? Object.keys(dias[0]) : 'Nenhum dia');
        
        // Preencher detalhesPorDia com os dados que vêm diretamente da API
        detalhesPorDia = {};
        let totalDetalhesEncontrados = 0;
        
        dias.forEach(dia => {
            // A API agora retorna detalhes_uf diretamente em cada dia
            // Verificar múltiplos nomes possíveis (snake_case, camelCase)
            let detalhes = dia.detalhes_uf || dia.detalhesUf || dia.detalhes_por_uf || dia.detalhesPorUf;
            
            // Se detalhes for null ou undefined, usar objeto vazio
            if (!detalhes || typeof detalhes !== 'object') {
                detalhes = {};
            }
            
            // Verificar se é um array e converter para objeto
            if (Array.isArray(detalhes)) {
                console.warn(`Detalhes para ${dia.data} veio como array, convertendo...`);
                const obj = {};
                detalhes.forEach(item => {
                    if (item.uf && item.count !== undefined) {
                        obj[item.uf] = item.count;
                    }
                });
                detalhes = obj;
            }
            
            if (Object.keys(detalhes).length > 0) {
                console.log(`✓ Detalhes encontrados para ${dia.data}:`, detalhes);
                totalDetalhesEncontrados++;
            } else {
                console.warn(`✗ Nenhum detalhe encontrado para ${dia.data}. Campos disponíveis:`, Object.keys(dia));
                console.warn(`  Valores dos campos de detalhes:`, {
                    detalhes_uf: dia.detalhes_uf,
                    detalhesUf: dia.detalhesUf,
                    detalhes_por_uf: dia.detalhes_por_uf,
                    detalhesPorUf: dia.detalhesPorUf
                });
            }
            
            detalhesPorDia[dia.data] = detalhes;
        });
        
        console.log(`Total de dias com detalhes: ${totalDetalhesEncontrados} de ${dias.length}`);
        console.log('Detalhes por UF processados ANTES do fallback:', detalhesPorDia);
        
        // SEMPRE tentar buscar manualmente se não houver detalhes (fallback garantido)
        let detalhesCarregadosManualmente = false;
        if (totalDetalhesEncontrados === 0 && dias.length > 0) {
            console.warn('⚠️ Nenhum detalhe_uf encontrado na API. Executando fallback manual...');
            try {
                await carregarDetalhesManual(dias);
                detalhesCarregadosManualmente = true;
                console.log('✅ Detalhes carregados manualmente com sucesso!');
                console.log('📊 DetalhesPorDia APÓS carregamento manual:', detalhesPorDia);
                
                // Verificar se realmente carregou
                const diasComDetalhes = Object.keys(detalhesPorDia).filter(data => {
                    const detalhes = detalhesPorDia[data];
                    return detalhes && Object.keys(detalhes).length > 0;
                });
                console.log(`✅ Dias com detalhes após fallback: ${diasComDetalhes.length} de ${dias.length}`);
                
            } catch (error) {
                console.error('❌ Erro ao carregar detalhes manualmente:', error);
                console.error('Stack:', error.stack);
            }
        }
        
        // Renderizar tabela (com ou sem detalhes)
        console.log('🎨 Renderizando tabela...');
        console.log('📋 DetalhesPorDia no momento da renderização:', detalhesPorDia);
        renderizarTabelaDias(dias);
        
        if (detalhesCarregadosManualmente) {
            showToast('Detalhes carregados com sucesso!', 'success');
        }
        
        // Mostrar informação de sincronização
        mostrarInfoSincronizacao();
        
        showToast('Estatísticas atualizadas com sucesso!');
        
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        showToast('Erro ao carregar estatísticas: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Função de fallback para carregar detalhes manualmente se a API não retornar detalhes_uf
async function carregarDetalhesManual(dias) {
    if (dias.length === 0) {
        console.warn('Nenhum dia para processar');
        return;
    }
    
    try {
        // Buscar todos os CPFs disponíveis do período
        const hoje = new Date().toISOString().split('T')[0];
        const diasMaisAntigo = dias.reduce((min, dia) => {
            return dia.data < min ? dia.data : min;
        }, hoje);
        const diffDays = Math.ceil((new Date(hoje) - new Date(diasMaisAntigo)) / (1000 * 60 * 60 * 24)) + 1;
        
        console.log(`🔄 Buscando ${diffDays} dias de CPFs para calcular detalhes manualmente...`);
        console.log(`📅 Período: ${diasMaisAntigo} até ${hoje}`);
        
        const disponiveis = await proxyRequest('disponiveis', { days: diffDays, limit: 50000 });
        const todosCpfs = disponiveis.cpfs_disponiveis || [];
        
        console.log(`✅ Total de CPFs encontrados: ${todosCpfs.length}`);
        
        if (todosCpfs.length === 0) {
            console.warn('⚠️ Nenhum CPF encontrado para calcular detalhes');
            return;
        }
        
        // Agrupar por data e UF
        let totalDetalhesCalculados = 0;
        dias.forEach(dia => {
            const dataDia = dia.data;
            const cpfsDoDia = todosCpfs.filter(c => {
                const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
                return dataItem === dataDia;
            });
            
            console.log(`📊 Processando ${dataDia}: ${cpfsDoDia.length} CPFs encontrados`);
            
            // Debug: verificar estrutura do primeiro CPF
            if (cpfsDoDia.length > 0) {
                console.log(`🔍 Exemplo de CPF (primeiro item):`, cpfsDoDia[0]);
                console.log(`🔍 Campos disponíveis no CPF:`, Object.keys(cpfsDoDia[0]));
            }
            
            // Agrupar por UF
            const porUf = {};
            let cpfsSemUf = 0;
            cpfsDoDia.forEach(c => {
                // Tentar múltiplos campos possíveis para UF
                const uf = c.uf || c.UF || c.cro || c.estado || c.Estado || null;
                
                if (uf && uf !== 'N/A' && uf.length <= 3) {
                    porUf[uf] = (porUf[uf] || 0) + 1;
                } else {
                    cpfsSemUf++;
                    // Log apenas os primeiros 3 para não poluir o console
                    if (cpfsSemUf <= 3) {
                        console.warn(`⚠️ CPF sem UF válida. Campos:`, Object.keys(c), `Valores:`, {
                            uf: c.uf,
                            UF: c.UF,
                            cro: c.cro,
                            estado: c.estado
                        });
                    }
                }
            });
            
            if (cpfsSemUf > 0) {
                console.warn(`⚠️ Total de CPFs sem UF válida para ${dataDia}: ${cpfsSemUf}`);
            }
            
            // Sempre atualizar (substituir objeto vazio)
            if (Object.keys(porUf).length > 0) {
                detalhesPorDia[dataDia] = porUf;
                totalDetalhesCalculados++;
                console.log(`✅ Detalhes calculados para ${dataDia}:`, porUf);
                console.log(`   Total de UFs: ${Object.keys(porUf).length}, Total de CPFs: ${Object.values(porUf).reduce((a, b) => a + b, 0)}`);
            } else {
                console.warn(`⚠️ Nenhum detalhe calculado para ${dataDia} (nenhuma UF encontrada nos CPFs)`);
                detalhesPorDia[dataDia] = {};
            }
        });
        
        console.log(`🎉 Total de dias com detalhes calculados: ${totalDetalhesCalculados} de ${dias.length}`);
        
    } catch (error) {
        console.error('❌ Erro ao carregar detalhes manualmente:', error);
        console.error('Stack:', error.stack);
        throw error;
    }
}

// DEPRECATED: Esta função não é mais necessária pois a API agora retorna detalhes_uf diretamente
// Mantida apenas para referência ou fallback futuro
// Carregar detalhes por UF de cada dia (método antigo - requisição adicional)
/*
async function carregarDetalhesPorDia(dias) {
    detalhesPorDia = {};
    carregandoDetalhes = true;
    
    if (dias.length === 0) {
        carregandoDetalhes = false;
        return;
    }
    
    try {
        // Buscar todos os CPFs disponíveis do período de uma vez (mais eficiente)
        const hoje = new Date().toISOString().split('T')[0];
        const diasMaisAntigo = dias.reduce((min, dia) => {
            return dia.data < min ? dia.data : min;
        }, hoje);
        const diffDays = Math.ceil((new Date(hoje) - new Date(diasMaisAntigo)) / (1000 * 60 * 60 * 24)) + 1;
        
        // Buscar todos os CPFs do período
        const disponiveis = await proxyRequest('disponiveis', { days: diffDays, limit: 50000 });
        const todosCpfs = disponiveis.cpfs_disponiveis || [];
        
        // Agrupar por data e UF
        dias.forEach(dia => {
            const dataDia = dia.data;
            const cpfsDoDia = todosCpfs.filter(c => {
                const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
                return dataItem === dataDia;
            });
            
            // Agrupar por UF
            const porUf = {};
            cpfsDoDia.forEach(c => {
                // Tentar múltiplos campos possíveis para UF
                const uf = c.uf || c.UF || c.cro || null;
                if (uf && uf !== 'N/A' && uf.length <= 3) {
                    // Validar que é uma UF válida (2-3 caracteres)
                    porUf[uf] = (porUf[uf] || 0) + 1;
                }
            });
            
            detalhesPorDia[dataDia] = porUf;
        });
        
        console.log('Detalhes por UF carregados:', detalhesPorDia);
        console.log('Total de CPFs processados:', todosCpfs.length);
        console.log('Dias processados:', Object.keys(detalhesPorDia).length);
        
        // Verificar se algum dia ficou sem detalhes
        dias.forEach(dia => {
            if (!detalhesPorDia[dia.data] || Object.keys(detalhesPorDia[dia.data]).length === 0) {
                console.warn(`Nenhum detalhe encontrado para o dia ${dia.data}`);
            }
        });
        
    } catch (error) {
        console.error('Erro ao carregar detalhes por UF:', error);
        console.error('Stack trace:', error.stack);
        // Em caso de erro, inicializar com objetos vazios
        dias.forEach(dia => {
            detalhesPorDia[dia.data] = {};
        });
    } finally {
        carregandoDetalhes = false;
    }
}
*/

// Mostrar informação sobre sincronização
function mostrarInfoSincronizacao() {
    const info = `
        <div class="alert alert-info mb-3" id="infoSincronizacao">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Sincronização:</strong> 
            CPFs aprovados são sincronizados <strong>3x por dia</strong> (8h, 13h, 18h).
            Validações são executadas <strong>a cada 1 hora</strong>.
            <br><small class="text-muted">Última atualização: ${new Date().toLocaleString('pt-BR')}</small>
        </div>
    `;
    
    // Adicionar antes da tabela de dias se não existir
    const cardBody = $('#tableDailyStats').closest('.data-card').find('.card-body');
    if ($('#infoSincronizacao').length === 0) {
        cardBody.prepend(info);
    } else {
        $('#infoSincronizacao').replaceWith(info);
    }
}

function renderizarGridUfs() {
    let html = '';
    
    if (estatisticasUf.length === 0) {
        html = '<div class="text-center text-muted py-4">Nenhum dado disponível</div>';
    } else {
        estatisticasUf.forEach(item => {
            const isSelected = ufSelecionada === item.uf;
            html += `
                <div class="uf-stat-item ${isSelected ? 'selected' : ''}" 
                     onclick="selecionarUf('${item.uf}')" data-uf="${item.uf}">
                    <span class="uf-name">${item.uf}</span>
                    <span class="uf-count">${item.cpfs_unicos || item.total}</span>
                </div>
            `;
        });
    }
    
    $('#ufStatsGrid').html(html);
    $('#btnAutorizarUfSelecionada').prop('disabled', !ufSelecionada);
}

function selecionarUf(uf) {
    if (ufSelecionada === uf) {
        ufSelecionada = null;
    } else {
        ufSelecionada = uf;
    }
    renderizarGridUfs();
}

function renderizarTabelaDias(dados) {
    let html = '';
    
    if (dados.length === 0) {
        html = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum dado disponível</td></tr>';
    } else {
        const hoje = new Date().toISOString().split('T')[0];
        
        dados.forEach(item => {
            const dataObj = new Date(item.data + 'T00:00:00');
            const dataFormatada = dataObj.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
            const diaSemana = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });
            const diaSemanaAbrev = dataObj.toLocaleDateString('pt-BR', { weekday: 'short' });
            const isHoje = item.data === hoje;
            // A API pode retornar 'disponiveis' ou 'pendentes'
            const disponiveis = item.disponiveis || item.pendentes || item.total || 0;
            
            // Buscar detalhes por UF deste dia (agora vem diretamente da API em detalhes_uf)
            // Tentar múltiplos nomes possíveis para compatibilidade
            const detalhesUf = item.detalhes_uf || item.detalhesUf || item.detalhes_por_uf || item.detalhesPorUf || detalhesPorDia[item.data] || {};
            const ufsArray = Object.entries(detalhesUf).sort((a, b) => b[1] - a[1]);
            
            // Debug detalhado para cada linha da tabela
            console.log(`🔍 Renderizando linha para ${item.data}:`, {
                temDetalhesUfNoItem: !!(item.detalhes_uf || item.detalhesUf),
                temDetalhesPorDia: !!detalhesPorDia[item.data],
                detalhesPorDiaValue: detalhesPorDia[item.data],
                detalhesUfFinal: detalhesUf,
                ufsArrayLength: ufsArray.length,
                itemUfs: item.ufs
            });
            
            if (ufsArray.length === 0 && (item.ufs || 0) > 0) {
                console.warn(`⚠️ Dia ${item.data}: Tem ${item.ufs} UFs mas detalhesUf está vazio!`);
                console.warn(`   Item completo:`, item);
                console.warn(`   detalhesPorDia[${item.data}]:`, detalhesPorDia[item.data]);
                console.warn(`   Estado atual de detalhesPorDia:`, detalhesPorDia);
            }
            
            // Formatar UFs: "SP: 100, MG: 50, RJ: 30"
            let ufsDisplay = '';
            let temDetalhes = ufsArray.length > 0;
            
            if (temDetalhes) {
                // Temos detalhes por UF - mostrar discriminado
                if (ufsArray.length <= 8) {
                    // Mostrar todas se tiver 8 ou menos
                    ufsDisplay = ufsArray.map(([uf, qtd]) => {
                        return `<span class="badge bg-primary me-1 mb-1" style="font-size: 0.75rem;"><strong>${uf}</strong>: ${qtd.toLocaleString('pt-BR')}</span>`;
                    }).join(' ');
                } else {
                    // Mostrar as 8 principais e resumo
                    const principais = ufsArray.slice(0, 8).map(([uf, qtd]) => {
                        return `<span class="badge bg-primary me-1 mb-1" style="font-size: 0.75rem;"><strong>${uf}</strong>: ${qtd.toLocaleString('pt-BR')}</span>`;
                    }).join(' ');
                    const totalRestantes = ufsArray.slice(8).reduce((sum, [, qtd]) => sum + qtd, 0);
                    const qtdRestantes = ufsArray.length - 8;
                    ufsDisplay = `${principais}<br><span class="badge bg-secondary mt-1"><strong>+${qtdRestantes} UF(s)</strong>: ${totalRestantes.toLocaleString('pt-BR')} CPFs</span>`;
                }
            } else {
                // Não temos detalhes - mostrar número total de UFs
                const totalUfs = item.ufs || 0;
                if (totalUfs > 0) {
                    ufsDisplay = `<span class="text-muted"><i class="fas fa-info-circle me-1"></i>${totalUfs} UF(s) - <small>Detalhes não disponíveis</small></span>`;
                } else {
                    ufsDisplay = `<span class="text-muted">-</span>`;
                }
            }
            
            html += `
                <tr class="${isHoje ? 'table-primary' : ''}">
                    <td>
                        <strong>${dataFormatada}</strong>
                        ${isHoje ? '<span class="badge bg-success ms-2">HOJE</span>' : ''}
                    </td>
                    <td>
                        <span class="text-muted">${diaSemanaAbrev}</span>
                        <small class="d-block text-muted">${diaSemana}</small>
                    </td>
                    <td><strong class="text-primary">${item.total?.toLocaleString('pt-BR') || 0}</strong></td>
                    <td><strong class="text-success">${disponiveis.toLocaleString('pt-BR')}</strong></td>
                    <td>
                        <div class="ufs-detalhadas" style="max-width: 600px;">
                            ${ufsDisplay}
                            ${temDetalhes && ufsArray.length > 8 ? `
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="mostrarTodasUfs('${item.data}')" 
                                            title="Ver todas as ${ufsArray.length} UF(s)">
                                        <i class="fas fa-eye me-1"></i>Ver Todas (${ufsArray.length})
                                    </button>
                                </div>
                            ` : (temDetalhes && ufsArray.length > 0 ? `
                                <button class="btn btn-sm btn-link p-0 ms-2 text-primary" 
                                        onclick="mostrarTodasUfs('${item.data}')" 
                                        title="Ver detalhes de todas as ${ufsArray.length} UF(s)">
                                    <i class="fas fa-list"></i>
                                </button>
                            ` : '')}
                        </div>
                    </td>
                    <td>
                        ${isHoje && disponiveis > 0 ? `
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success" 
                                        onclick="enviarTodosDiaAtual('${item.data}')" 
                                        title="Enviar todos os ${disponiveis} CPFs disponíveis de hoje">
                                    <i class="fas fa-paper-plane me-1"></i>Enviar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                        onclick="enviarMaisRecentes('${item.data}')" 
                                        title="Enviar apenas os mais recentes (últimas 2 horas)">
                                    <i class="fas fa-clock me-1"></i>Recentes
                                </button>
                            </div>
                        ` : '-'}
                    </td>
                </tr>
            `;
        });
    }
    
    $('#tableDailyStats tbody').html(html);
    
    // Verificar se há dados do dia atual
    const temDiaAtual = dados.some(item => item.data === new Date().toISOString().split('T')[0]);
    $('#btnEnviarDiaAtual').toggle(temDiaAtual);
}

// Mostrar todas as UFs em um modal
function mostrarTodasUfs(data) {
    const detalhesUf = detalhesPorDia[data] || {};
    const ufsArray = Object.entries(detalhesUf).sort((a, b) => b[1] - a[1]);
    
    if (ufsArray.length === 0) {
        showToast('Nenhuma UF encontrada para esta data', 'warning');
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>UF</th>
                        <th class="text-end">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    ufsArray.forEach(([uf, qtd]) => {
        html += `
            <tr>
                <td><strong>${uf}</strong></td>
                <td class="text-end">${qtd.toLocaleString('pt-BR')}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#resultContent').html(html);
    new bootstrap.Modal(document.getElementById('resultModal')).show();
}

// Enviar apenas os mais recentes (últimas 2 horas)
async function enviarMaisRecentes(dataEspecifica) {
    const hoje = dataEspecifica || new Date().toISOString().split('T')[0];
    const duasHorasAtras = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString();
    
    if (!confirm('Enviar apenas CPFs que entraram nas últimas 2 horas?')) {
        return;
    }
    
    showLoading('Buscando CPFs mais recentes...');
    
    try {
        const hojeDate = new Date().toISOString().split('T')[0];
        const diffDays = dataEspecifica ? Math.ceil((new Date(hojeDate) - new Date(dataEspecifica)) / (1000 * 60 * 60 * 24)) : 0;
        
        const disponiveis = await proxyRequest('disponiveis', { days: diffDays + 1, limit: 10000 });
        let cpfsDoDia = (disponiveis.cpfs_disponiveis || []).filter(c => {
            const dataItem = c.data_insercao ? c.data_insercao.split('T')[0] : null;
            return dataItem === hoje;
        });
        
        // Filtrar apenas os das últimas 2 horas
        cpfsDoDia = cpfsDoDia.filter(c => {
            const dataInsercao = c.data_insercao ? new Date(c.data_insercao) : null;
            return dataInsercao && dataInsercao >= new Date(duasHorasAtras);
        });
        
        if (cpfsDoDia.length === 0) {
            showToast('Nenhum CPF encontrado nas últimas 2 horas', 'warning');
            return;
        }
        
        const cpfsArray = cpfsDoDia.map(item => item.cpf_raw || item.cpf.replace(/\D/g, ''));
        
        showLoading(`Enviando ${cpfsArray.length} CPF(s) mais recentes...`);
        
        if (cpfsArray.length > 100) {
            // Enviar em lotes
            let enviados = 0;
            let erros = 0;
            
            for (let i = 0; i < cpfsArray.length; i += 100) {
                const lote = cpfsArray.slice(i, i + 100);
                try {
                    await proxyRequest('inserir-lote', {}, 'POST', { cpfs: lote });
                    enviados += lote.length;
                } catch (error) {
                    erros += lote.length;
                    console.error(`Erro no lote ${Math.floor(i/100) + 1}:`, error);
                }
            }
            
            showToast(`Enviados: ${enviados} | Erros: ${erros}`, enviados > 0 ? 'success' : 'error');
        } else {
            const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: cpfsArray });
            exibirResultadoLote(result);
        }
        
        // Recarregar estatísticas
        await carregarEstatisticas();
        
    } catch (error) {
        console.error('Erro ao enviar CPFs recentes:', error);
        showToast('Erro ao enviar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Carregar CPFs disponíveis
async function carregarDisponiveis() {
    showLoading('Buscando CPFs disponíveis...');
    cpfsSelecionados.clear();
    atualizarContadorSelecionados();
    
    try {
        const uf = $('#filterUfDisponiveis').val();
        const days = $('#filterDaysDisponiveis').val();
        const limit = $('#filterLimitDisponiveis').val();
        const categoria = $('#filterCategoriaDisponiveis').val();
        
        const params = { days, limit };
        if (uf) params.uf = uf;
        if (categoria) params.categoria = categoria;
        
        const data = await proxyRequest('disponiveis', params);
        cpfsDisponiveis = data.cpfs_disponiveis || [];
        
        // Filtrar por categoria se necessário
        if (categoria === 'CD') {
            cpfsDisponiveis = cpfsDisponiveis.filter(item => item.categoria === 'CD');
        } else if (categoria === 'OUTROS') {
            cpfsDisponiveis = cpfsDisponiveis.filter(item => item.categoria && item.categoria !== 'CD');
        }
        
        renderizarTabelaDisponiveis();
        
        $('#selectionInfo').show();
        $('#totalCpfsDisponiveis').text(cpfsDisponiveis.length);
        
        showToast(`${cpfsDisponiveis.length} CPFs carregados com sucesso!`);
        
    } catch (error) {
        console.error('Erro ao carregar CPFs:', error);
        showToast('Erro ao carregar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function renderizarTabelaDisponiveis() {
    if ($.fn.DataTable.isDataTable('#tableDisponiveis')) {
        $('#tableDisponiveis').DataTable().destroy();
    }
    
    let html = '';
    
    if (cpfsDisponiveis.length === 0) {
        html = '<tr><td colspan="9" class="text-center text-muted py-4">Nenhum CPF disponível encontrado</td></tr>';
    } else {
        cpfsDisponiveis.forEach((item, index) => {
            const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
            html += `
                <tr data-cpf="${cpfRaw}">
                    <td>
                        <input type="checkbox" class="form-check-input cpf-checkbox" 
                               value="${cpfRaw}" onchange="toggleCpfSelecionado('${cpfRaw}')">
                    </td>
                    <td><code>${formatCpf(cpfRaw)}</code></td>
                    <td>${item.nome || '-'}</td>
                    <td><span class="badge bg-secondary">${item.cro || '-'}</span></td>
                    <td>${item.categoria || '-'}</td>
                    <td>${item.inscricao || '-'}</td>
                    <td><code>${item.id_professional || '-'}</code></td>
                    <td>${formatDate(item.data_insercao)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="verDetalhesCpf('${cpfRaw}')" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                                    </tr>
            `;
        });
    }
    
    $('#tableDisponiveis tbody').html(html);
    
    if (cpfsDisponiveis.length > 0) {
        $('#tableDisponiveis').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: '../../assets/lang/pt-BR.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ],
            order: [[7, 'desc']]
        });
    }
}

function toggleCpfSelecionado(cpf) {
    if (cpfsSelecionados.has(cpf)) {
        cpfsSelecionados.delete(cpf);
    } else {
        cpfsSelecionados.add(cpf);
    }
    atualizarContadorSelecionados();
}

function atualizarContadorSelecionados() {
    const count = cpfsSelecionados.size;
    $('#selectedCount').text(`${count} selecionado${count !== 1 ? 's' : ''}`);
    $('#btnEnviarSelecionados').prop('disabled', count === 0);
    
    // Atualizar checkbox principal
    const totalCheckboxes = $('.cpf-checkbox').length;
    $('#selectAllCpfs').prop('checked', totalCheckboxes > 0 && count === totalCheckboxes);
    $('#checkAllTable').prop('checked', totalCheckboxes > 0 && count === totalCheckboxes);
}

// Filtrar apenas dia atual
function filtrarDiaAtual() {
    $('#filterDays').val('0');
    carregarEstatisticas();
}

// Enviar todos do dia atual
async function enviarTodosDiaAtual(dataEspecifica) {
    const hoje = dataEspecifica || new Date().toISOString().split('T')[0];
    
    if (!confirm(`Deseja enviar TODOS os CPFs disponíveis do dia ${new Date(hoje + 'T00:00:00').toLocaleDateString('pt-BR')} para a fila de impressão?`)) {
        return;
    }
    
    showLoading('Buscando CPFs do dia atual...');
    
    try {
        // Buscar todos os CPFs disponíveis do dia atual
        const data = await proxyRequest('disponiveis', { days: 0, limit: 10000 });
        let cpfsDoDia = data.cpfs_disponiveis || [];
        
        // Filtrar apenas os do dia específico
        if (dataEspecifica) {
            cpfsDoDia = cpfsDoDia.filter(item => {
                const itemData = item.data_insercao ? item.data_insercao.split('T')[0] : null;
                return itemData === dataEspecifica;
            });
        }
        
        if (cpfsDoDia.length === 0) {
            showToast('Nenhum CPF disponível encontrado para o dia selecionado', 'warning');
            return;
        }
        
        const cpfsArray = cpfsDoDia.map(item => item.cpf_raw || item.cpf.replace(/\D/g, ''));
        
        if (cpfsArray.length > 100) {
            // Enviar em lotes de 100
            showLoading(`Enviando ${cpfsArray.length} CPFs em lotes...`);
            let enviados = 0;
            let erros = 0;
            
            for (let i = 0; i < cpfsArray.length; i += 100) {
                const lote = cpfsArray.slice(i, i + 100);
                try {
                    await proxyRequest('inserir-lote', {}, 'POST', { cpfs: lote });
                    enviados += lote.length;
                } catch (error) {
                    erros += lote.length;
                    console.error(`Erro no lote ${Math.floor(i/100) + 1}:`, error);
                }
            }
            
            showToast(`Enviados: ${enviados} | Erros: ${erros}`, enviados > 0 ? 'success' : 'error');
        } else {
            showLoading(`Enviando ${cpfsArray.length} CPFs para impressão...`);
            const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: cpfsArray });
            exibirResultadoLote(result);
        }
        
        // Recarregar estatísticas
        await carregarEstatisticas();
        
    } catch (error) {
        console.error('Erro ao enviar CPFs do dia atual:', error);
        showToast('Erro ao enviar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Enviar todos de hoje (da aba CPFs Disponíveis)
async function enviarTodosHoje() {
    if (!confirm('Deseja enviar TODOS os CPFs disponíveis de HOJE para a fila de impressão?')) {
        return;
    }
    
    // Primeiro carregar os CPFs de hoje
    $('#filterDaysDisponiveis').val('0');
    await carregarDisponiveis();
    
    if (cpfsDisponiveis.length === 0) {
        showToast('Nenhum CPF disponível encontrado para hoje', 'warning');
        return;
    }
    
    const cpfsArray = cpfsDisponiveis.map(item => item.cpf_raw || item.cpf.replace(/\D/g, ''));
    
    if (cpfsArray.length > 100) {
        if (!confirm(`Você tem ${cpfsArray.length} CPFs. Serão enviados em lotes de 100. Continuar?`)) {
            return;
        }
    }
    
    showLoading(`Enviando ${cpfsArray.length} CPFs para impressão...`);
    
    try {
        if (cpfsArray.length > 100) {
            // Enviar em lotes
            let enviados = 0;
            let erros = 0;
            
            for (let i = 0; i < cpfsArray.length; i += 100) {
                const lote = cpfsArray.slice(i, i + 100);
                try {
                    await proxyRequest('inserir-lote', {}, 'POST', { cpfs: lote });
                    enviados += lote.length;
                } catch (error) {
                    erros += lote.length;
                    console.error(`Erro no lote ${Math.floor(i/100) + 1}:`, error);
                }
            }
            
            showToast(`Enviados: ${enviados} | Erros: ${erros}`, enviados > 0 ? 'success' : 'error');
        } else {
            const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: cpfsArray });
            exibirResultadoLote(result);
        }
        
        // Recarregar lista
        await carregarDisponiveis();
        
    } catch (error) {
        console.error('Erro ao enviar CPFs:', error);
        showToast('Erro ao enviar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Enviar CPFs selecionados
async function enviarSelecionados() {
    if (cpfsSelecionados.size === 0) {
        showToast('Selecione pelo menos um CPF', 'warning');
        return;
    }
    
    if (cpfsSelecionados.size > 100) {
        showToast('Máximo de 100 CPFs por vez. Você selecionou ' + cpfsSelecionados.size, 'error');
        return;
    }
    
    showLoading('Enviando CPFs para impressão...');
    
    try {
        const cpfsArray = Array.from(cpfsSelecionados);
        const data = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: cpfsArray });
        
        exibirResultadoLote(data);
        
        // Recarregar lista
        await carregarDisponiveis();
        
    } catch (error) {
        console.error('Erro ao enviar CPFs:', error);
        showToast('Erro ao enviar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Enviar CPFs manual
async function enviarCpfsManual() {
    const texto = $('#cpfsManual').val().trim();
    if (!texto) {
        showToast('Digite pelo menos um CPF', 'warning');
        return;
    }
    
    const cpfs = texto.split('\n')
        .map(cpf => cpf.replace(/\D/g, '').trim())
        .filter(cpf => cpf.length === 11);
    
    if (cpfs.length === 0) {
        showToast('Nenhum CPF válido encontrado', 'error');
        return;
    }
    
    if (cpfs.length > 100) {
        showToast('Máximo de 100 CPFs por vez. Você informou ' + cpfs.length, 'error');
        return;
    }
    
    showLoading('Enviando CPFs para impressão...');
    
    try {
        const data = await proxyRequest('inserir-lote', {}, 'POST', { cpfs });
        
        exibirResultadoLote(data);
        
        // Limpar campo
        $('#cpfsManual').val('');
        $('#countCpfsManual').text('0');
        
    } catch (error) {
        console.error('Erro ao enviar CPFs:', error);
        showToast('Erro ao enviar CPFs: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Autorizar toda UF
async function autorizarUf() {
    const uf = $('#ufAutorizar').val();
    if (!uf) {
        showToast('Selecione uma UF', 'warning');
        return;
    }
    
    if (!confirm(`Tem certeza que deseja autorizar TODOS os CPFs disponíveis de ${uf}?`)) {
        return;
    }
    
    showLoading(`Autorizando todos os CPFs de ${uf}...`);
    
    try {
        const days = $('#daysAutorizar').val();
        const batchSize = $('#batchSizeAutorizar').val();
        
        const data = await proxyRequest('autorizar-uf', {}, 'POST', {
            uf,
            days: parseInt(days),
            batch_size: parseInt(batchSize)
        });
        
        exibirResultadoUf(data);
        
        // Atualizar estatísticas
        await carregarEstatisticas();
        
    } catch (error) {
        console.error('Erro ao autorizar UF:', error);
        showToast('Erro ao autorizar UF: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Autorizar UF selecionada no grid
$('#btnAutorizarUfSelecionada').click(async function() {
    if (!ufSelecionada) {
        showToast('Selecione uma UF no grid', 'warning');
        return;
    }
    
    if (!confirm(`Tem certeza que deseja autorizar TODOS os CPFs disponíveis de ${ufSelecionada}?`)) {
        return;
    }
    
    showLoading(`Autorizando todos os CPFs de ${ufSelecionada}...`);
    
    try {
        const days = $('#filterDays').val();
        
        const data = await proxyRequest('autorizar-uf', {}, 'POST', {
            uf: ufSelecionada,
            days: parseInt(days),
            batch_size: 50
        });
        
        exibirResultadoUf(data);
        
        ufSelecionada = null;
        await carregarEstatisticas();
        
    } catch (error) {
        console.error('Erro ao autorizar UF:', error);
        showToast('Erro ao autorizar UF: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
});

// Exibir resultado do lote
function exibirResultadoLote(data) {
    const resumo = data.resumo || {};
    
    let html = `
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>${data.mensagem || 'Lote processado'}</strong>
        </div>
        
        <div class="result-summary">
            <div class="result-summary-item success">
                <div class="value">${resumo.sucesso || 0}</div>
                <div class="label">Sucesso</div>
            </div>
            <div class="result-summary-item info">
                <div class="value">${resumo.ja_possui_identity || 0}</div>
                <div class="label">Já Possui</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.em_fila || 0}</div>
                <div class="label">Em Fila</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.sem_foto || 0}</div>
                <div class="label">Sem Foto</div>
            </div>
            <div class="result-summary-item danger">
                <div class="value">${resumo.sem_cadastro || 0}</div>
                <div class="label">Sem Cadastro</div>
            </div>
            <div class="result-summary-item danger">
                <div class="value">${resumo.erro || 0}</div>
                <div class="label">Erro</div>
            </div>
        </div>
    `;
    
    // Detalhes
    const detalhes = data.detalhes || {};
    
    if (detalhes.sucesso && detalhes.sucesso.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-success"><i class="fas fa-check-circle me-1"></i>Sucesso (${detalhes.sucesso.length})</h6>
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>CPF</th><th>Profissionais</th></tr></thead>
                        <tbody>
                            ${detalhes.sucesso.map(item => `<tr><td><code>${formatCpf(item.cpf)}</code></td><td>${item.profissionais || 1}</td></tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
        `;
    }
    
    if (detalhes.sem_cadastro && detalhes.sem_cadastro.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-danger"><i class="fas fa-times-circle me-1"></i>Sem Cadastro (${detalhes.sem_cadastro.length})</h6>
                <div class="d-flex flex-wrap gap-1">
                    ${detalhes.sem_cadastro.map(cpf => `<span class="badge bg-danger">${formatCpf(cpf)}</span>`).join('')}
            </div>
        </div>
        `;
    }
    
    if (detalhes.sem_foto && detalhes.sem_foto.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-warning"><i class="fas fa-camera me-1"></i>Sem Foto (${detalhes.sem_foto.length})</h6>
                <div class="d-flex flex-wrap gap-1">
                    ${detalhes.sem_foto.map(cpf => `<span class="badge bg-warning text-dark">${formatCpf(cpf)}</span>`).join('')}
                </div>
            </div>
        `;
    }
    
    $('#resultContent').html(html);
    new bootstrap.Modal(document.getElementById('resultModal')).show();
}

// Exibir resultado da autorização de UF
function exibirResultadoUf(data) {
    const resumo = data.resumo || {};
    
    let html = `
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>${data.mensagem || 'Autorização concluída'}</strong>
            <br><small>UF: ${data.uf} | Lotes processados: ${data.lotes_processados || 0} | Total de CPFs: ${data.total_cpfs || 0}</small>
        </div>
        
        <div class="result-summary">
            <div class="result-summary-item success">
                <div class="value">${resumo.autorizados || 0}</div>
                <div class="label">Autorizados</div>
            </div>
            <div class="result-summary-item info">
                <div class="value">${resumo.ja_existem || 0}</div>
                <div class="label">Já Existem</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.em_fila || 0}</div>
                <div class="label">Em Fila</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.sem_foto || 0}</div>
                <div class="label">Sem Foto</div>
            </div>
            <div class="result-summary-item danger">
                <div class="value">${resumo.sem_cadastro || 0}</div>
                <div class="label">Sem Cadastro</div>
            </div>
            <div class="result-summary-item danger">
                <div class="value">${resumo.erros || 0}</div>
                <div class="label">Erros</div>
            </div>
        </div>
    `;
    
    $('#resultContent').html(html);
    new bootstrap.Modal(document.getElementById('resultModal')).show();
}

// Consultar CPF individual
async function consultarCpf() {
    const cpf = $('#cpfConsulta').val().replace(/\D/g, '');
    
    if (!cpf || cpf.length !== 11) {
        showToast('Digite um CPF válido com 11 dígitos', 'warning');
        return;
    }
    
    showLoading('Consultando CPF...');
    
    try {
        const data = await proxyRequest('detalhes', { cpf });
        
        exibirDetalhesCpf(data);
        
    } catch (error) {
        console.error('Erro ao consultar CPF:', error);
        showToast('Erro ao consultar CPF: ' + error.message, 'error');
        $('#resultadoConsultaCpf').hide();
    } finally {
        hideLoading();
    }
}

// Ver detalhes de um CPF da lista
async function verDetalhesCpf(cpf) {
    showLoading('Carregando detalhes...');
    
    try {
        const data = await proxyRequest('detalhes', { cpf });
        
        let html = `
            <div class="cpf-info">
                <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>CPF: ${formatCpf(data.cpf)}</h5>
                
                <div class="row mb-3">
        <div class="col-md-6">
                        <strong>Pode Autorizar:</strong>
                        ${data.pode_autorizar 
                            ? '<span class="badge bg-success ms-2"><i class="fas fa-check me-1"></i>Sim</span>' 
                            : '<span class="badge bg-danger ms-2"><i class="fas fa-times me-1"></i>Não</span>'}
                </div>
                </div>
            </div>
        `;
        
        // Status
        const status = data.status || {};
        html += `
            <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i>Status</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <tr>
                        <td>Possui Identity</td>
                        <td>${status.possui_identity ? '<span class="badge bg-info">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
                                </tr>
                                    <tr>
                        <td>Em Fila (identity_ready)</td>
                        <td>${status.em_identity_ready ? '<span class="badge bg-warning">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
                                    </tr>
                    <tr>
                        <td>Descartado</td>
                        <td>${status.descartado ? '<span class="badge bg-danger">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
                    </tr>
                    <tr>
                        <td>Possui Foto</td>
                        <td>${status.possui_foto ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-warning">Não</span>'}</td>
                    </tr>
                        </table>
                    </div>
        `;
        
        // Profissionais
        if (data.profissionais && data.profissionais.length > 0) {
            html += `<h6 class="mb-2"><i class="fas fa-users me-1"></i>Profissionais</h6>`;
            data.profissionais.forEach(prof => {
                html += `
                    <div class="cpf-info mb-2">
                        <div class="row">
                            <div class="col-md-6"><strong>Nome:</strong> ${prof.nome || '-'}</div>
                            <div class="col-md-3"><strong>CRO:</strong> ${prof.cro || '-'}</div>
                            <div class="col-md-3"><strong>Categoria:</strong> ${prof.categoria || '-'}</div>
                </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong>Inscrição:</strong> ${prof.inscricao || '-'}</div>
                            <div class="col-md-4"><strong>Tipo:</strong> ${prof.tipo_insc || '-'}</div>
            </div>
        </div>
                `;
            });
        }
        
        $('#detailsContent').html(html);
        
        // Botão de enviar
        if (data.pode_autorizar) {
            $('#btnEnviarDetalhe').show().off('click').on('click', async function() {
                const cpfRaw = data.cpf.replace(/\D/g, '');
                showLoading('Enviando para impressão...');
                try {
                    const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: [cpfRaw] });
                    exibirResultadoLote(result);
                    bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
                } catch (error) {
                    showToast('Erro ao enviar: ' + error.message, 'error');
                } finally {
                    hideLoading();
                }
            });
        } else {
            $('#btnEnviarDetalhe').hide();
        }
        
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
        
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        showToast('Erro ao carregar detalhes: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function exibirDetalhesCpf(data) {
    let html = `
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>${formatCpf(data.cpf)}</h5>
                ${data.pode_autorizar 
                    ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Pode Autorizar</span>' 
                    : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Não Pode Autorizar</span>'}
    </div>
            <div class="card-body">
    `;
    
    // Status
    const status = data.status || {};
    html += `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card ${status.possui_identity ? 'border-info' : 'border-secondary'}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-id-badge fa-2x me-3 ${status.possui_identity ? 'text-info' : 'text-muted'}"></i>
                        <div>
                            <div class="fw-bold">${status.possui_identity ? 'Sim' : 'Não'}</div>
                            <div class="small text-muted">Identity</div>
</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card ${status.em_identity_ready ? 'border-warning' : 'border-secondary'}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock fa-2x me-3 ${status.em_identity_ready ? 'text-warning' : 'text-muted'}"></i>
                        <div>
                            <div class="fw-bold">${status.em_identity_ready ? 'Sim' : 'Não'}</div>
                            <div class="small text-muted">Em Fila</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card ${status.descartado ? 'border-danger' : 'border-secondary'}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-trash fa-2x me-3 ${status.descartado ? 'text-danger' : 'text-muted'}"></i>
                        <div>
                            <div class="fw-bold">${status.descartado ? 'Sim' : 'Não'}</div>
                            <div class="small text-muted">Descartado</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card ${status.possui_foto ? 'border-success' : 'border-warning'}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-camera fa-2x me-3 ${status.possui_foto ? 'text-success' : 'text-warning'}"></i>
                        <div>
                            <div class="fw-bold">${status.possui_foto ? 'Sim' : 'Não'}</div>
                            <div class="small text-muted">Com Foto</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Profissionais
    if (data.profissionais && data.profissionais.length > 0) {
        html += `<h6 class="border-bottom pb-2 mb-3"><i class="fas fa-users me-2"></i>Profissionais Vinculados</h6>`;
        html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Nome</th><th>CRO</th><th>Categoria</th><th>Inscrição</th><th>Tipo</th></tr></thead><tbody>';
        data.profissionais.forEach(prof => {
            html += `<tr>
                <td>${prof.nome || '-'}</td>
                <td><span class="badge bg-secondary">${prof.cro || '-'}</span></td>
                <td>${prof.categoria || '-'}</td>
                <td>${prof.inscricao || '-'}</td>
                <td><span class="badge bg-info">${prof.tipo_insc || '-'}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }
    
    // Registros aprovados
    if (data.registros_aprovados && data.registros_aprovados.length > 0) {
        html += `<h6 class="border-bottom pb-2 mb-3 mt-4"><i class="fas fa-check-circle me-2"></i>Registros Aprovados</h6>`;
        html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>CRO</th><th>Categoria</th><th>Inscrição</th><th>Data Inserção</th></tr></thead><tbody>';
        data.registros_aprovados.forEach(reg => {
            html += `<tr>
                <td><span class="badge bg-secondary">${reg.cro || '-'}</span></td>
                <td>${reg.categoria || '-'}</td>
                <td>${reg.inscricao || '-'}</td>
                <td>${formatDate(reg.data_insercao)}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }
    
    html += `
            </div>
        </div>
    `;
    
    if (data.pode_autorizar) {
        html += `
            <div class="mt-3 text-end">
                <button type="button" class="btn btn-gradient-success" onclick="enviarCpfIndividual('${data.cpf.replace(/\D/g, '')}')">
                    <i class="fas fa-paper-plane me-2"></i>Enviar para Impressão
                </button>
            </div>
        `;
    }
    
    $('#resultadoConsultaCpf').html(html).show();
}

async function enviarCpfIndividual(cpf) {
    showLoading('Enviando para impressão...');
    try {
        const result = await proxyRequest('inserir-lote', {}, 'POST', { cpfs: [cpf] });
        exibirResultadoLote(result);
        consultarCpf(); // Atualiza a consulta
    } catch (error) {
        showToast('Erro ao enviar: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Carregar quantidade de ARs disponíveis
async function atualizarArsDisponiveis() {
    try {
        $('#qtdArsDisponiveis').html('<i class="fas fa-spinner fa-spin"></i>');
        
        const data = await proxyRequest('ars-disponiveis', {});
        const qtd = data.total_ars_disponiveis || 0;
        
        $('#qtdArsDisponiveis').text(qtd.toLocaleString('pt-BR'));
        
        // Alterar cor do badge baseado na quantidade
        const badge = $('#badgeArsDisponiveis');
        badge.removeClass('bg-success bg-warning bg-danger');
        
        if (qtd > 10000) {
            badge.addClass('bg-success'); // Verde - OK
        } else if (qtd > 5000) {
            badge.addClass('bg-warning'); // Amarelo - Atenção
        } else {
            badge.addClass('bg-danger'); // Vermelho - Crítico
        }
        
    } catch (error) {
        console.error('Erro ao carregar ARs disponíveis:', error);
        $('#qtdArsDisponiveis').text('Erro');
        $('#badgeArsDisponiveis').removeClass('bg-success bg-warning').addClass('bg-danger');
    }
}

// Event listeners
$(document).ready(function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Carregar ARs disponíveis ao iniciar
    atualizarArsDisponiveis();
    
    // Carregar estatísticas ao iniciar
    carregarEstatisticas();
    
    // Select all checkboxes
    $('#selectAllCpfs, #checkAllTable').change(function() {
        const checked = $(this).is(':checked');
        $('.cpf-checkbox').prop('checked', checked);
        
        if (checked) {
            cpfsDisponiveis.forEach(item => {
                const cpfRaw = item.cpf_raw || item.cpf.replace(/\D/g, '');
                cpfsSelecionados.add(cpfRaw);
            });
        } else {
            cpfsSelecionados.clear();
        }
        
        atualizarContadorSelecionados();
    });
    
    // Contador de CPFs no textarea
    $('#cpfsManual').on('input', function() {
        const lines = $(this).val().split('\n').filter(l => l.trim().length > 0);
        $('#countCpfsManual').text(lines.length);
    });
    
    // Enter na consulta de CPF
    $('#cpfConsulta').keypress(function(e) {
        if (e.which === 13) {
            consultarCpf();
        }
    });
    
    // Quando mudar de tab para disponiveis, verificar se precisa carregar
    $('button[data-bs-target="#disponiveis"]').on('shown.bs.tab', function() {
        if (cpfsDisponiveis.length === 0) {
            carregarDisponiveis();
        }
    });
    
    // Inicializar data início com hoje ao abrir a aba por-data
    $('button[data-bs-target="#por-data"]').on('shown.bs.tab', function() {
        if (!$('#dataInicioStats').val()) {
            const hoje = new Date().toISOString().split('T')[0];
            $('#dataInicioStats').val(hoje);
            $('#dataInicioProcessar').val(hoje);
        }
    });
    });

// ========== FUNÇÕES PARA GESTÃO POR DATA DE INSCRIÇÃO ==========

// Carregar estatísticas por data de inscrição
async function carregarStatsPorData() {
    const dataInicio = $('#dataInicioStats').val();
    if (!dataInicio) {
        showToast('Informe a data início', 'warning');
        return;
    }
    
    showLoading('Carregando estatísticas por data de inscrição...');
    
    try {
        const params = {
            data_inicio: dataInicio
        };
        
        const dataFim = $('#dataFimStats').val();
        if (dataFim) {
            params.data_fim = dataFim;
        }
        
        const uf = $('#ufStatsPorData').val();
        if (uf) {
            params.cro = uf;
        }
        
        const agruparPor = $('#agruparPorStats').val();
        if (agruparPor) {
            params.agrupar_por = agruparPor;
        }
        
        const apenasPendentes = $('#apenasPendentesStats').is(':checked');
        params.apenas_pendentes = apenasPendentes;
        
        const data = await proxyRequest('stats-por-data', params);
        
        // Atualizar resumo
        if (data.resumo) {
            $('#statTotalPorData').text((data.resumo.total || 0).toLocaleString('pt-BR'));
            $('#statPendentesPorData').text((data.resumo.pendentes || 0).toLocaleString('pt-BR'));
            $('#statEnviadosPorData').text((data.resumo.enviados || 0).toLocaleString('pt-BR'));
            $('#statProcessadosPorData').text((data.resumo.processados || 0).toLocaleString('pt-BR'));
            $('#resumoStatsPorData').show();
        }
        
        // Renderizar tabela por dia/mês (API retorna por_dia ou por_mes dependendo do agrupamento)
        const dadosPeriodo = data.por_mes || data.por_dia || [];
        renderizarTabelaStatsPorData(dadosPeriodo, data.resumo);
        
        // Renderizar estatísticas por UF
        renderizarStatsPorUf(data.por_uf || []);
        
        showToast('Estatísticas carregadas com sucesso!');
        
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        showToast('Erro ao carregar estatísticas: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Renderizar tabela de estatísticas por período
function renderizarTabelaStatsPorData(dados, resumo = null) {
    let html = '';
    
    if (dados.length === 0) {
        html = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum dado encontrado para o período selecionado</td></tr>';
    } else {
        dados.forEach(item => {
            const periodo = item.periodo || '-';
            const total = item.total || 0;
            const pendentes = parseInt(item.pendentes) || 0;
            const enviados = parseInt(item.enviados) || 0;
            const processados = parseInt(item.processados) || 0;
            
            // Formatar período baseado no agrupamento
            let periodoFormatado = periodo;
            if (periodo.match(/^\d{4}-\d{2}-\d{2}$/)) {
                // É uma data (dia)
                const dataObj = new Date(periodo + 'T00:00:00');
                periodoFormatado = dataObj.toLocaleDateString('pt-BR');
            } else if (periodo.match(/^\d{4}-\d{2}$/)) {
                // É um mês
                const [ano, mes] = periodo.split('-');
                const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                periodoFormatado = `${meses[parseInt(mes) - 1]}/${ano}`;
            }
            
            html += `
                <tr>
                    <td><strong>${periodoFormatado}</strong></td>
                    <td><span class="badge bg-secondary">${total.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-warning text-dark">${pendentes.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-info">${enviados.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-success">${processados.toLocaleString('pt-BR')}</span></td>
                    <td>
                        ${pendentes > 0 ? `
                            <button type="button" class="btn btn-sm btn-success" 
                                    onclick="processarPeriodoEspecifico('${periodo}', ${pendentes})"
                                    title="Processar ${pendentes} pendentes deste período">
                                <i class="fas fa-play me-1"></i>Processar
                            </button>
                        ` : '<span class="text-muted">-</span>'}
                    </td>
                </tr>
            `;
        });
        
        // Adicionar linha de TOTAL GERAL
        if (resumo) {
            const totalGeral = parseInt(resumo.total) || 0;
            const pendentesGeral = parseInt(resumo.pendentes) || 0;
            const enviadosGeral = parseInt(resumo.enviados) || 0;
            const processadosGeral = parseInt(resumo.processados) || 0;
            
            html += `
                <tr class="table-dark fw-bold">
                    <td><i class="fas fa-calculator me-2"></i>TOTAL GERAL</td>
                    <td><span class="badge bg-light text-dark fs-6">${totalGeral.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-warning text-dark fs-6">${pendentesGeral.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-info fs-6">${enviadosGeral.toLocaleString('pt-BR')}</span></td>
                    <td><span class="badge bg-success fs-6">${processadosGeral.toLocaleString('pt-BR')}</span></td>
                    <td>
                        ${pendentesGeral > 0 ? `
                            <button type="button" class="btn btn-sm btn-warning text-dark" 
                                    onclick="processarTodosPendentes(${pendentesGeral})"
                                    title="Processar todos os ${pendentesGeral.toLocaleString('pt-BR')} pendentes">
                                <i class="fas fa-play-circle me-1"></i>Processar Todos
                            </button>
                        ` : '<span class="text-muted">-</span>'}
                    </td>
                </tr>
            `;
        }
    }
    
    $('#tableStatsPorData tbody').html(html);
}

// Renderizar estatísticas por UF
function renderizarStatsPorUf(dados) {
    if (dados.length === 0) {
        $('#statsPorUfContainer').html('<div class="text-center text-muted py-4">Nenhuma UF encontrada</div>');
        return;
    }
    
    let html = '<div class="uf-stats-grid">';
    dados.forEach(item => {
        const uf = item.uf || '-';
        const total = item.total || 0;
        const pendentes = item.pendentes || 0;
        
        html += `
            <div class="uf-stat-item">
                <div>
                    <span class="uf-name">${uf}</span>
                    <div class="small text-muted mt-1">
                        Total: ${total.toLocaleString('pt-BR')} | 
                        Pendentes: <strong>${pendentes.toLocaleString('pt-BR')}</strong>
                    </div>
                </div>
                <span class="uf-count">${pendentes}</span>
            </div>
        `;
    });
    html += '</div>';
    
    $('#statsPorUfContainer').html(html);
}

// Processar por data de inscrição
async function processarPorData() {
    const dataInicio = $('#dataInicioProcessar').val();
    if (!dataInicio) {
        showToast('Informe a data início', 'warning');
        return;
    }
    
    const dataFim = $('#dataFimProcessar').val() || dataInicio;
    const uf = $('#ufProcessarPorData').val();
    const limite = parseInt($('#limiteProcessarPorData').val());
    
    const periodoTexto = dataInicio === dataFim 
        ? `dia ${new Date(dataInicio + 'T00:00:00').toLocaleDateString('pt-BR')}`
        : `período de ${new Date(dataInicio + 'T00:00:00').toLocaleDateString('pt-BR')} até ${new Date(dataFim + 'T00:00:00').toLocaleDateString('pt-BR')}`;
    
    const ufTexto = uf ? ` da UF ${uf}` : '';
    
    if (!confirm(`Deseja processar até ${limite.toLocaleString('pt-BR')} profissionais${ufTexto} do ${periodoTexto}?`)) {
        return;
    }
    
    showLoading('Processando profissionais por data de inscrição...');
    
    try {
        const postData = {
            data_inicio: dataInicio,
            data_fim: dataFim,
            limite: limite
        };
        
        if (uf) {
            postData.cro = uf;
        }
        
        const result = await proxyRequest('processar-por-data', {}, 'POST', postData);
        
        exibirResultadoProcessarPorData(result);
        
        // Recarregar estatísticas se estiver na mesma aba
        if ($('#por-data').hasClass('active')) {
            await carregarStatsPorData();
        }
        
    } catch (error) {
        console.error('Erro ao processar por data:', error);
        showToast('Erro ao processar: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Processar TODOS os pendentes do período selecionado
async function processarTodosPendentes(qtdPendentes) {
    const dataInicio = $('#dataInicioStats').val();
    const dataFim = $('#dataFimStats').val() || dataInicio;
    const uf = $('#ufStatsPorData').val();
    
    if (!confirm(`Deseja processar TODOS os ${qtdPendentes.toLocaleString('pt-BR')} profissionais pendentes?\n\nPeríodo: ${dataInicio} até ${dataFim}\nLimite por requisição: 5.000\n\nIsso pode levar alguns minutos.`)) {
        return;
    }
    
    showLoading('Processando todos os pendentes...');
    
    try {
        const postData = {
            data_inicio: dataInicio,
            data_fim: dataFim,
            limite: 5000 // Máximo permitido
        };
        
        if (uf) {
            postData.cro = uf;
        }
        
        const result = await proxyRequest('processar-por-data', {}, 'POST', postData);
        
        exibirResultadoProcessarPorData(result);
        
        // Recarregar estatísticas
        await carregarStatsPorData();
        
    } catch (error) {
        console.error('Erro ao processar todos:', error);
        showToast('Erro ao processar: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Processar período específico (chamado da tabela)
async function processarPeriodoEspecifico(periodo, qtdPendentes) {
    const limite = parseInt($('#limiteProcessarPorData').val());
    const uf = $('#ufStatsPorData').val();
    
    if (!confirm(`Processar até ${Math.min(limite, qtdPendentes).toLocaleString('pt-BR')} profissionais do período ${periodo}?`)) {
        return;
    }
    
    showLoading('Processando período específico...');
    
    try {
        const postData = {
            data_inicio: periodo,
            data_fim: periodo,
            limite: Math.min(limite, qtdPendentes)
        };
        
        if (uf) {
            postData.cro = uf;
        }
        
        const result = await proxyRequest('processar-por-data', {}, 'POST', postData);
        
        exibirResultadoProcessarPorData(result);
        
        // Recarregar estatísticas
        await carregarStatsPorData();
        
    } catch (error) {
        console.error('Erro ao processar período:', error);
        showToast('Erro ao processar: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Exibir resultado do processamento por data
function exibirResultadoProcessarPorData(data) {
    const resumo = data.resumo || {};
    const periodo = data.periodo || {};
    
    let html = `
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>${data.mensagem || 'Processamento concluído'}</strong>
            <br><small>
                Período: ${periodo.data_inicio || '-'} 
                ${periodo.data_fim && periodo.data_fim !== periodo.data_inicio ? ' até ' + periodo.data_fim : ''}
                ${periodo.filtro_uf && periodo.filtro_uf !== 'todas' ? ' | UF: ' + periodo.filtro_uf : ''}
                ${periodo.limite ? ' | Limite: ' + periodo.limite.toLocaleString('pt-BR') : ''}
            </small>
        </div>
        
        <div class="result-summary">
            <div class="result-summary-item success">
                <div class="value">${resumo.sucesso || 0}</div>
                <div class="label">Sucesso</div>
            </div>
            <div class="result-summary-item info">
                <div class="value">${resumo.ja_possui_identity || 0}</div>
                <div class="label">Já Possui Identity</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.descartado || 0}</div>
                <div class="label">Descartado</div>
            </div>
            <div class="result-summary-item warning">
                <div class="value">${resumo.sem_foto || 0}</div>
                <div class="label">Sem Foto</div>
            </div>
            <div class="result-summary-item danger">
                <div class="value">${resumo.erros || 0}</div>
                <div class="label">Erros</div>
            </div>
            <div class="result-summary-item">
                <div class="value">${resumo.total_cpfs_encontrados || 0}</div>
                <div class="label">Total Encontrados</div>
            </div>
        </div>
    `;
    
    // Detalhes
    const detalhes = data.detalhes || {};
    
    if (detalhes.sucesso && detalhes.sucesso.length > 0) {
        html += `
            <div class="mb-3 mt-4">
                <h6 class="text-success"><i class="fas fa-check-circle me-1"></i>Processados com Sucesso (${detalhes.sucesso.length})</h6>
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>CPF</th>
                                <th>Profissionais</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${detalhes.sucesso.slice(0, 50).map(item => `
                                <tr>
                                    <td><code>${formatCpf(item.cpf)}</code></td>
                                    <td>${item.profissionais || 1}</td>
                                </tr>
                            `).join('')}
                            ${detalhes.sucesso.length > 50 ? `<tr><td colspan="2" class="text-muted text-center">... e mais ${detalhes.sucesso.length - 50} CPFs</td></tr>` : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    if (detalhes.ja_possui_identity && detalhes.ja_possui_identity.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-info"><i class="fas fa-info-circle me-1"></i>Já Possuem Identity (${detalhes.ja_possui_identity.length})</h6>
                <div class="d-flex flex-wrap gap-1">
                    ${detalhes.ja_possui_identity.slice(0, 20).map(cpf => `<span class="badge bg-info">${formatCpf(cpf)}</span>`).join('')}
                    ${detalhes.ja_possui_identity.length > 20 ? `<span class="badge bg-secondary">... e mais ${detalhes.ja_possui_identity.length - 20}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    if (detalhes.sem_foto && detalhes.sem_foto.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-warning"><i class="fas fa-camera me-1"></i>Sem Foto (${detalhes.sem_foto.length})</h6>
                <div class="d-flex flex-wrap gap-1">
                    ${detalhes.sem_foto.slice(0, 20).map(cpf => `<span class="badge bg-warning text-dark">${formatCpf(cpf)}</span>`).join('')}
                    ${detalhes.sem_foto.length > 20 ? `<span class="badge bg-secondary">... e mais ${detalhes.sem_foto.length - 20}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    if (detalhes.erro && detalhes.erro.length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erros (${detalhes.erro.length})</h6>
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>CPF</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${detalhes.erro.slice(0, 20).map(item => `
                                <tr>
                                    <td><code>${formatCpf(item.cpf)}</code></td>
                                    <td class="text-danger">${item.motivo || 'Erro desconhecido'}</td>
                                </tr>
                            `).join('')}
                            ${detalhes.erro.length > 20 ? `<tr><td colspan="2" class="text-muted text-center">... e mais ${detalhes.erro.length - 20} erros</td></tr>` : ''}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    $('#resultContent').html(html);
    new bootstrap.Modal(document.getElementById('resultModal')).show();
}
</script>

</body>
</html>
