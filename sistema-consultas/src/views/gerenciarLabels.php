<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();
Session::CheckAdmin();
?>

<div class="container-fluid">
    <!-- Alertas -->
    <div id="alertContainer"></div>

    <!-- Card Principal -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tags mr-2"></i>
                    Gerenciar Labels do Sistema
                </h5>
                <button class="btn btn-light btn-sm" onclick="updateCache()">
                    <i class="fas fa-sync mr-1"></i>
                    Atualizar Cache
                </button>
            </div>
        </div>

        <div class="card-body">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="labelTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="main-labels-tab" data-bs-toggle="tab" data-bs-target="#main-labels" type="button" role="tab">
                        <i class="fas fa-tag mr-1"></i>
                        Labels Principais
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sub-labels-tab" data-bs-toggle="tab" data-bs-target="#sub-labels" type="button" role="tab">
                        <i class="fas fa-layer-group mr-1"></i>
                        Sub-Labels
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hierarchy-tab" data-bs-toggle="tab" data-bs-target="#hierarchy" type="button" role="tab">
                        <i class="fas fa-sitemap mr-1"></i>
                        Estrutura Hierárquica
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="labelTabsContent">
                <!-- Labels Principais -->
                <div class="tab-pane fade show active" id="main-labels" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">
                            <i class="fas fa-list mr-1"></i>
                            Lista de Labels Principais
                        </h6>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm" onclick="addLabel()">
                                <i class="fas fa-plus mr-1"></i>
                                Nova Label
                            </button>
                            <button type="button" class="btn btn-info btn-sm" id="toggleReorder" onclick="toggleSortMode()">
                                <i class="fas fa-arrows-alt mr-1"></i>
                                Reordenar
                            </button>
                        </div>
                    </div>

                    <div class="loading text-center p-4" id="labelsLoading">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Carregando labels...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="labelsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Key</th>
                                    <th width="20%">Nome</th>
                                    <th width="15%">URL</th>
                                    <th width="10%">Ícone</th>
                                    <th width="8%">Ordem</th>
                                    <th width="12%">Status</th>
                                    <th width="15%">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="labelsTableBody">
                                <!-- Dados carregados via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sub-Labels -->
                <div class="tab-pane fade" id="sub-labels" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">
                            <i class="fas fa-list mr-1"></i>
                            Lista de Sub-Labels
                        </h6>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm" onclick="addChildLabel()">
                                <i class="fas fa-plus mr-1"></i>
                                Nova Sub-Label
                            </button>
                            <button type="button" class="btn btn-info btn-sm" id="toggleChildReorder" onclick="toggleChildReorderMode()">
                                <i class="fas fa-arrows-alt mr-1"></i>
                                Reordenar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Informação sobre ordenação por grupo -->
                 

                    <div class="loading text-center p-4" id="childLabelsLoading">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Carregando sub-labels...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="childLabelsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="18%">Nome</th>
                                    <th width="15%">Grupo</th>
                                    <th width="15%">Label Pai</th>
                                    <th width="8%">Ordem de Exibição</th>
                                    <th width="12%">Referencial</th>
                                    <th width="12%">Status</th>
                                    <th width="15%">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="childLabelsTableBody">
                                <!-- Dados carregados via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Estrutura Hierárquica -->
                <div class="tab-pane fade" id="hierarchy" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">
                            <i class="fas fa-sitemap mr-1"></i>
                            Visualização da Estrutura Hierárquica
                        </h6>
                        <button class="btn btn-primary btn-sm" onclick="loadHierarchy()">
                            <i class="fas fa-sync mr-1"></i>
                            Atualizar
                        </button>
                    </div>

                    <div class="loading text-center p-4" id="hierarchyLoading">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Carregando estrutura hierárquica...</p>
                    </div>

                    <div id="hierarchyContainer">
                        <!-- Estrutura hierárquica carregada via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Label Principal -->
<div class="modal fade" id="labelModal" tabindex="-1" aria-labelledby="labelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labelModalLabel">
                    <i class="fas fa-tag mr-2"></i>
                    Nova Label
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="labelForm">
                <div class="modal-body">
                    <input type="hidden" id="labelId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="labelKey" class="form-label">
                                    <i class="fas fa-key mr-1"></i>
                                    Label Key *
                                </label>
                                <input type="text" class="form-control" id="labelKey" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="labelValue" class="form-label">
                                    <i class="fas fa-font mr-1"></i>
                                    Nome da Label *
                                </label>
                                <input type="text" class="form-control" id="labelValue" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="labelUrl" class="form-label">
                                    <i class="fas fa-link mr-1"></i>
                                    URL
                                </label>
                                <input type="text" class="form-control" id="labelUrl">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="labelClass" class="form-label">
                                    <i class="fas fa-icons mr-1"></i>
                                    Classe do Ícone
                                </label>
                                <input type="text" class="form-control" id="labelClass" placeholder="Ex: fas fa-home">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="labelDescription" class="form-label">
                            <i class="fas fa-comment mr-1"></i>
                            Descrição
                        </label>
                        <textarea class="form-control" id="labelDescription" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="labelDisable">
                        <label class="form-check-label" for="labelDisable">
                            Desabilitar esta label
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Sub-Label -->
<div class="modal fade" id="childLabelModal" tabindex="-1" aria-labelledby="childLabelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="childLabelModalLabel">
                    <i class="fas fa-layer-group mr-2"></i>
                    Nova Sub-Label
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="childLabelForm">
                <div class="modal-body">
                    <input type="hidden" id="childLabelId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="childLabelNome" class="form-label">
                                    <i class="fas fa-font mr-1"></i>
                                    Nome *
                                </label>
                                <input type="text" class="form-control" id="childLabelNome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="childLabelReferencial" class="form-label">
                                    <i class="fas fa-hashtag mr-1"></i>
                                    Referencial *(Evitar mexer)
                                </label>
                                <input type="number" class="form-control" id="childLabelReferencial" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="childLabelGrupo" class="form-label">
                                    <i class="fas fa-layer-group mr-1"></i>
                                    Grupo
                                </label>
                                <input type="text" class="form-control" id="childLabelGrupo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="childLabelParent" class="form-label">
                                    <i class="fas fa-tag mr-1"></i>
                                    Label Pai *
                                </label>
                                <select class="form-control" id="childLabelParent" required>
                                    <option value="">Selecione uma label pai...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="childLabelDescricao" class="form-label">
                            <i class="fas fa-comment mr-1"></i>
                            Descrição
                        </label>
                        <textarea class="form-control" id="childLabelDescricao" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="childLabelDisable">
                        <label class="form-check-label" for="childLabelDisable">
                            Desabilitar esta sub-label
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.loading {
    display: none;
}
.loading.show {
    display: block;
}

.cursor-move {
    cursor: move !important;
}

.sortable-item.cursor-move {
    background-color: #f8f9fa;
}

.badge-status {
    font-size: 0.75em;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
    background-color: #f8f9fa;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border: 1px solid transparent;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.card-header.bg-primary h5 {
    margin-bottom: 0;
}

.table-responsive {
    border-radius: 0.375rem;
}

.hierarchy-card {
    border-left: 4px solid #007bff;
    margin-bottom: 1rem;
}

.hierarchy-card .card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.child-item {
    border-left: 3px solid #28a745;
    padding: 0.5rem;
    margin: 0.25rem 0;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}

.sortable-ghost {
    opacity: 0.4;
    background-color: #f8f9fa;
}

.sortable-chosen {
    background-color: #e3f2fd;
}

.sortable-drag {
    background-color: #bbdefb;
}

/* Melhorar estabilidade visual do sortable */
.sortable-item {
    position: relative;
    transition: all 0.3s ease;
}

.sortable-item.cursor-move {
    background-color: #f8f9fa;
    user-select: none;
}

.sortable-item.cursor-move:hover {
    background-color: #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Garantir que elementos não desapareçam durante drag */
.sortable-ghost {
    opacity: 0.5 !important;
    background-color: #e3f2fd !important;
    border: 2px dashed #2196f3 !important;
}

.sortable-chosen {
    background-color: #bbdefb !important;
    transform: scale(1.02);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    z-index: 1000;
}

.sortable-drag {
    background-color: #2196f3 !important;
    color: white !important;
    opacity: 0.9 !important;
    transform: rotate(5deg);
    box-shadow: 0 6px 12px rgba(0,0,0,0.3);
}

/* Prevenir que células fiquem vazias */
.sortable-item td {
    min-height: 40px;
    vertical-align: middle;
}

.sortable-fallback {
    display: none !important;
}

/* Suavizar cores dos badges para melhor legibilidade */

/* Sub-Labels */
#childLabelsTable .badge.bg-primary {
    background-color: #b3d7ff !important; /* Azul mais claro */
    color: #0056b3 !important; /* Texto azul escuro */
}

#childLabelsTable .badge.bg-secondary {
    background-color: #e2e3e5 !important; /* Cinza mais claro */
    color: #495057 !important; /* Texto cinza escuro */
}

#childLabelsTable .badge.bg-success {
    background-color: #c3e6cb !important; /* Verde mais claro */
    color: #155724 !important; /* Texto verde escuro */
}

#childLabelsTable .badge.bg-danger {
    background-color: #f5c6cb !important; /* Vermelho mais claro */
    color: #721c24 !important; /* Texto vermelho escuro */
}

/* Labels Principais */
#labelsTable .badge.bg-info {
    background-color: #bee5eb !important; /* Azul claro #36B9CC → suavizado */
    color: #0c5460 !important; /* Texto azul escuro */
}

#labelsTable .badge.bg-success {
    background-color: #c3e6cb !important; /* Verde claro #1CC88A → suavizado */
    color: #155724 !important; /* Texto verde escuro */
}

#labelsTable .badge.bg-danger {
    background-color: #f5c6cb !important; /* Vermelho mais claro */
    color: #721c24 !important; /* Texto vermelho escuro */
}

/* Estilos para grupos de sub-labels */
.table-group-header {
    background-color: #f8f9fa !important;
    border-left: 4px solid #007bff;
}

.table-group-header td {
    padding: 0.75rem !important;
    font-weight: 600;
    color: #495057;
}

.table-group-header .badge {
    font-size: 0.75em;
}

/* Melhorar separação visual entre grupos */
.sortable-item[data-parent-id] {
    border-left: 2px solid transparent;
}

.sortable-item[data-parent-id]:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}

/* Estilo para indicar que cada grupo tem ordenação independente */
.table-group-header::before {
    content: "📋";
    margin-right: 0.5rem;
    font-size: 0.9em;
}
</style>

<?php
require_once INC_PATH .'/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    let labelsTable, childLabelsTable;
    let sortable = null;
    let sortMode = false;

    // Variáveis globais para controle
    const API_BASE_URL = '/services/labels-admin/labels-controller.php';
    
    // Inicializar variáveis de controle de timing
    window.loadingChildLabels = false;
    window.updatingChildOrder = false;
    window.updatingOrder = false;

    // Inicialização
    $(document).ready(function() {
        // Limpar qualquer timeout pendente
        clearTimeout(window.updateOrderTimeout);
        clearTimeout(window.updateChildOrderTimeout);
        
        loadLabels();
        loadChildLabels();
        loadParentLabels();
        
        // Event listeners para forms
        $('#labelForm').on('submit', handleLabelSubmit);
        $('#childLabelForm').on('submit', handleChildLabelSubmit);
        
        // Carregar hierarquia quando a aba for clicada
        $('#hierarchy-tab').on('click', function() {
            loadHierarchy();
        });
        
        // Prevenir problemas de navegação durante reordenação
        $(window).on('beforeunload', function() {
            if (sortMode || $('#toggleChildReorder').text().includes('Finalizar')) {
                return 'Você está no modo de reordenação. Tem certeza que deseja sair?';
            }
        });
    });

    // Funções de carregamento
    function loadLabels() {
        $('#labelsLoading').addClass('show');
        
        $.get(API_BASE_URL + '?action=list_labels')
            .done(function(response) {
                if (response.success) {
                    renderLabelsTable(response.data);
                    initializeLabelsDataTable();
                } else {
                    showAlert('error', 'Erro ao carregar labels: ' + response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão ao carregar labels');
            })
            .always(function() {
                $('#labelsLoading').removeClass('show');
            });
    }

    function loadChildLabels() {
        console.log('loadChildLabels: Iniciando...');
        
        // Prevenir múltiplas execuções simultâneas
        if (window.loadingChildLabels) {
            console.log('loadChildLabels: Já está carregando, ignorando...');
            return;
        }
        window.loadingChildLabels = true;
        
        $('#childLabelsLoading').addClass('show');
        
        // Limpar tabela primeiro
        console.log('loadChildLabels: Limpando tabela...');
        $('#childLabelsTableBody').empty();
        
        console.log('loadChildLabels: Enviando requisição AJAX...');
        $.ajax({
            url: API_BASE_URL + '?action=list_child_labels',
            type: 'GET',
            timeout: 10000, // 10 segundos timeout
            beforeSend: function() {
                console.log('loadChildLabels: AJAX beforeSend');
            }
        })
        .done(function(response, textStatus, jqXHR) {
            console.log('loadChildLabels: AJAX done - response type:', typeof response);
            console.log('loadChildLabels: AJAX done - textStatus:', textStatus);
            
            try {
                if (typeof response === 'string') {
                    console.log('loadChildLabels: Parsing JSON response...');
                    response = JSON.parse(response);
                }
                
                console.log('loadChildLabels: Parsed response:', response);
                
                if (response.success && response.data) {
                    console.log('loadChildLabels: Renderizando tabela com', response.data.length, 'itens...');
                    renderChildLabelsTable(response.data);
                    
                    // Verificar se está em modo de reordenação
                    const isReorderMode = $('#toggleChildReorder').text().includes('Finalizar');
                    console.log('loadChildLabels: isReorderMode:', isReorderMode);
                    
                    if (!isReorderMode) {
                        console.log('loadChildLabels: Inicializando DataTable...');
                        setTimeout(function() {
                            initializeChildLabelsDataTable();
                        }, 150);
                    } else {
                        console.log('loadChildLabels: Recriar sortable em modo de reordenação...');
                        // Recriar sortable se estamos em modo de reordenação
                        setTimeout(function() {
                            if (sortable) {
                                sortable.destroy();
                            }
                            sortable = Sortable.create(document.getElementById('childLabelsTableBody'), {
                                handle: '.sortable-item',
                                animation: 200,
                                ghostClass: 'sortable-ghost',
                                chosenClass: 'sortable-chosen',
                                dragClass: 'sortable-drag',
                                forceFallback: false,
                                fallbackTolerance: 0,
                                scroll: true,
                                bubbleScroll: true,
                                onStart: function(evt) {
                                    console.log('SORTABLE: Início do arraste - índice:', evt.oldIndex);
                                },
                                onMove: function(evt) {
                                    return true; // Permitir movimento
                                },
                                onEnd: function(evt) {
                                    console.log('SORTABLE: Fim do arraste - de:', evt.oldIndex, 'para:', evt.newIndex);
                                    if (evt.oldIndex !== evt.newIndex) {
                                        console.log('SORTABLE: Posição mudou, iniciando atualização...');
                                        // Debounce para evitar múltiplas chamadas
                                        clearTimeout(window.updateChildOrderTimeout);
                                        window.updateChildOrderTimeout = setTimeout(function() {
                                            updateChildOrder();
                                        }, 300);
                                    }
                                }
                            });
                        }, 150);
                    }
                } else {
                    console.error('loadChildLabels: Erro do servidor:', response.message);
                    showAlert('error', 'Erro ao carregar sub-labels: ' + (response.message || 'Dados não encontrados'));
                }
            } catch (e) {
                console.error('loadChildLabels: Erro ao processar resposta:', e);
                console.error('loadChildLabels: Response raw:', response);
                showAlert('error', 'Erro ao processar dados recebidos');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('loadChildLabels: AJAX fail - textStatus:', textStatus);
            console.error('loadChildLabels: AJAX fail - errorThrown:', errorThrown);
            console.error('loadChildLabels: AJAX fail - jqXHR:', jqXHR);
            
            showAlert('error', 'Erro de conexão ao carregar sub-labels: ' + textStatus + ' - ' + errorThrown);
        })
        .always(function() {
            console.log('loadChildLabels: AJAX always');
            $('#childLabelsLoading').removeClass('show');
            window.loadingChildLabels = false;
        });
    }

    function loadParentLabels() {
        $.get(API_BASE_URL + '?action=list_labels')
            .done(function(response) {
                if (response.success) {
                    const select = $('#childLabelParent');
                    select.empty().append('<option value="">Selecione uma label pai...</option>');
                    
                    response.data.forEach(function(label) {
                        select.append(`<option value="${label.label_id}">${label.nome_label}</option>`);
                    });
                }
            })
            .fail(function() {
                showAlert('error', 'Erro ao carregar labels pai');
            });
    }

    // Renderização das tabelas
    function renderLabelsTable(data) {
        const tbody = $('#labelsTableBody');
        tbody.empty();
        
        data.forEach(function(label) {
            const statusBadge = label.disabled == 1 ? 
                '<span class="badge bg-danger badge-status">Desabilitada</span>' :
                '<span class="badge bg-success badge-status">Ativa</span>';
            
            const iconPreview = label.icon ? 
                `<i class="${label.icon}"></i> <small class="text-muted">${label.icon}</small>` : 
                '<span class="text-muted">-</span>';
            
            const row = `
                <tr data-id="${label.label_id}" class="sortable-item">
                    <td>${label.label_id}</td>
                    <td><code>${label.key_label}</code></td>
                    <td><strong>${label.nome_label}</strong></td>
                    <td>${label.url ? `<a href="${label.url}" target="_blank" class="text-decoration-none">${label.url}</a>` : '-'}</td>
                    <td>${iconPreview}</td>
                    <td><span class="badge bg-info">${label.display_order || label.label_id}</span></td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="editLabel(${label.label_id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLabel(${label.label_id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function renderChildLabelsTable(data) {
        const tbody = $('#childLabelsTableBody');
        tbody.empty();
        
        // Agrupar dados por label pai
        const groupedData = {};
        data.forEach(function(childLabel) {
            const parentId = childLabel.fk_label;
            if (!groupedData[parentId]) {
                groupedData[parentId] = {
                    parent_name: childLabel.parent_name,
                    parent_key: childLabel.parent_key,
                    children: []
                };
            }
            groupedData[parentId].children.push(childLabel);
        });
        
        // Renderizar cada grupo
        Object.keys(groupedData).forEach(function(parentId) {
            const group = groupedData[parentId];
            
            // Adicionar cabeçalho do grupo se houver mais de um grupo
            if (Object.keys(groupedData).length > 1) {
                const groupHeader = `
                    <tr class="table-group-header" data-parent-id="${parentId}">
                        <td colspan="7" class="bg-light text-center">
                            <strong><i class="fas fa-tag mr-2"></i>${group.parent_name || `Label ID: ${parentId}`}</strong>
                            <span class="badge bg-info ml-2">${group.children.length} sub-label(s)</span>
                        </td>
                    </tr>
                `;
                tbody.append(groupHeader);
            }
            
            // Renderizar sub-labels do grupo
            group.children.forEach(function(childLabel) {
                const statusBadge = childLabel.disable == 1 ? 
                    '<span class="badge bg-danger badge-status">Desabilitada</span>' :
                    '<span class="badge bg-success badge-status">Ativa</span>';
                
                const parentInfo = childLabel.parent_name ? 
                    `<span class="badge bg-primary" data-parent-id="${childLabel.fk_label}">${childLabel.parent_name}</span>` :
                    `<span class="badge bg-secondary" data-parent-id="${childLabel.fk_label}">ID: ${childLabel.fk_label}</span>`;
                
                const row = `
                    <tr data-id="${childLabel.id_label}" class="sortable-item" data-parent-id="${childLabel.fk_label}">
                        <td><strong>${childLabel.nome}</strong></td>
                        <td>${childLabel.grupo || '-'}</td>
                        <td>${parentInfo}</td>
                        <td><span class="badge bg-secondary">${childLabel.display_order || childLabel.referencial}</span></td>
                        <td><span class="badge bg-info">${childLabel.referencial}</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="editChildLabel(${childLabel.id_label})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteChildLabel(${childLabel.id_label})" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        });
    }

    // Inicialização do DataTables
    function initializeLabelsDataTable() {
        if (labelsTable) {
            labelsTable.destroy();
        }
        
        labelsTable = $('#labelsTable').DataTable({
            language: {
                url: '../assets/lang/pt-BR.json'
            },
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [7] }
            ]
        });
    }

    function initializeChildLabelsDataTable() {
        if (childLabelsTable) {
            childLabelsTable.destroy();
        }
        
        // Verificar se existem cabeçalhos de grupo na tabela
        const hasGroupHeaders = $('#childLabelsTable tbody tr.table-group-header').length > 0;
        
        if (hasGroupHeaders) {
            // Se há cabeçalhos de grupo, não inicializar DataTables
            // pois a estrutura da tabela não é compatível (colspan)
            console.log('DataTables não inicializado - tabela com grupos detectada');
            return;
        }
        
        childLabelsTable = $('#childLabelsTable').DataTable({
            language: {
                url: '../assets/lang/pt-BR.json'
            },
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [7] }
            ]
        });
    }

    // CRUD Functions
    function addLabel() {
        $('#labelModalLabel').html('<i class="fas fa-tag mr-2"></i>Nova Label');
        $('#labelForm')[0].reset();
        $('#labelId').val('');
        $('#labelModal').modal('show');
    }

    function editLabel(id) {
        $.get(API_BASE_URL + `?action=get_label&id=${id}`)
            .done(function(response) {
                if (response.success) {
                    const label = response.data;
                    $('#labelModalLabel').html('<i class="fas fa-edit mr-2"></i>Editar Label');
                    $('#labelId').val(label.id);
                    $('#labelKey').val(label.label_key);
                    $('#labelValue').val(label.label_value);
                    $('#labelUrl').val(label.url);
                    $('#labelClass').val(label.class);
                    $('#labelDescription').val(label.description);
                    $('#labelDisable').prop('checked', label.disable == 1);
                    $('#labelModal').modal('show');
                } else {
                    showAlert('error', 'Erro ao carregar dados da label');
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
    }

    function deleteLabel(id) {
        if (confirm('Tem certeza que deseja excluir esta label?\n\nIsso também excluirá todas as sub-labels relacionadas!')) {
            $.post(API_BASE_URL, {
                action: 'delete_label',
                id: id
            })
            .done(function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadLabels();
                    loadChildLabels();
                } else {
                    showAlert('error', response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
        }
    }

    function addChildLabel() {
        $('#childLabelModalLabel').html('<i class="fas fa-layer-group mr-2"></i>Nova Sub-Label');
        $('#childLabelForm')[0].reset();
        $('#childLabelId').val('');
        $('#childLabelModal').modal('show');
    }

    function editChildLabel(id) {
        $.get(API_BASE_URL + `?action=get_child_label&id=${id}`)
            .done(function(response) {
                if (response.success) {
                    const childLabel = response.data;
                    $('#childLabelModalLabel').html('<i class="fas fa-edit mr-2"></i>Editar Sub-Label');
                    $('#childLabelId').val(childLabel.id_label);
                    $('#childLabelNome').val(childLabel.nome);
                    $('#childLabelReferencial').val(childLabel.referencial);
                    $('#childLabelGrupo').val(childLabel.grupo);
                    $('#childLabelParent').val(childLabel.fk_label);
                    $('#childLabelDescricao').val(childLabel.descricao);
                    $('#childLabelDisable').prop('checked', childLabel.disable == 1);
                    $('#childLabelModal').modal('show');
                } else {
                    showAlert('error', 'Erro ao carregar dados da sub-label');
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
    }

    function deleteChildLabel(id) {
        if (confirm('Tem certeza que deseja excluir esta sub-label?')) {
            $.post(API_BASE_URL, {
                action: 'delete_child_label',
                id: id
            })
            .done(function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    loadChildLabels();
                } else {
                    showAlert('error', response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
        }
    }

    // Form handlers
    function handleLabelSubmit(e) {
        e.preventDefault();
        
        const id = $('#labelId').val();
        const action = id ? 'update_label' : 'insert_label';
        
        const formData = {
            action: action,
            label_key: $('#labelKey').val(),
            label_value: $('#labelValue').val(),
            url: $('#labelUrl').val(),
            class: $('#labelClass').val(),
            description: $('#labelDescription').val(),
            disable: $('#labelDisable').is(':checked') ? 1 : 0
        };
        
        if (id) {
            formData.id = id;
        }
        
        $.post(API_BASE_URL, formData)
            .done(function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#labelModal').modal('hide');
                    loadLabels();
                    loadParentLabels();
                } else {
                    showAlert('error', response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
    }

    function handleChildLabelSubmit(e) {
        e.preventDefault();
        
        const id = $('#childLabelId').val();
        const action = id ? 'update_child_label' : 'insert_child_label';
        
        const formData = {
            action: action,
            nome: $('#childLabelNome').val(),
            referencial: $('#childLabelReferencial').val(),
            grupo: $('#childLabelGrupo').val(),
            fk_label: $('#childLabelParent').val(),
            descricao: $('#childLabelDescricao').val(),
            disable: $('#childLabelDisable').is(':checked') ? 1 : 0
        };
        
        if (id) {
            formData.id = id;
        }
        
        $.post(API_BASE_URL, formData)
            .done(function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#childLabelModal').modal('hide');
                    loadChildLabels();
                } else {
                    showAlert('error', response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão');
            });
    }

    // Funcionalidade de ordenação
    function toggleSortMode() {
        sortMode = !sortMode;
        
        if (sortMode) {
            $('#toggleReorder').html('<i class="fas fa-check mr-1"></i>Finalizar').removeClass('btn-info').addClass('btn-success');
            $('.sortable-item').addClass('cursor-move');
            
            if (labelsTable) {
                labelsTable.destroy();
                labelsTable = null;
            }
            
            sortable = Sortable.create(document.getElementById('labelsTableBody'), {
                handle: '.sortable-item',
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                forceFallback: false,
                fallbackTolerance: 0,
                scroll: true,
                bubbleScroll: true,
                onStart: function(evt) {
                    console.log('Iniciando arrastar item índice:', evt.oldIndex);
                },
                onMove: function(evt) {
                    return true; // Permitir movimento
                },
                onEnd: function(evt) {
                    console.log('Finalizando arraste - de:', evt.oldIndex, 'para:', evt.newIndex);
                    if (evt.oldIndex !== evt.newIndex) {
                        // Debounce para evitar múltiplas chamadas
                        clearTimeout(window.updateOrderTimeout);
                        window.updateOrderTimeout = setTimeout(function() {
                            updateOrder();
                        }, 300);
                    }
                }
            });
            showAlert('info', 'Modo de reordenação ativado. Arraste as linhas para reorganizar.');
        } else {
            if (sortable) {
                sortable.destroy();
                sortable = null;
            }
            $('.sortable-item').removeClass('cursor-move');
            $('#toggleReorder').html('<i class="fas fa-arrows-alt mr-1"></i>Reordenar').removeClass('btn-success').addClass('btn-info');
            
            // Recarregar dados e inicializar DataTable
            setTimeout(function() {
                loadLabels();
            }, 200);
            showAlert('info', 'Modo de reordenação desativado.');
        }
    }

    function updateOrder() {
        // Prevenir múltiplas execuções simultâneas
        if (window.updatingOrder) {
            console.log('updateOrder: Já está executando, ignorando...');
            return;
        }
        window.updatingOrder = true;
        
        const orders = [];
        $('#labelsTableBody tr').each(function(index) {
            const id = $(this).data('id');
            if (id) {
                orders.push({ id: id, order: index + 1 });
            }
        });
        
        console.log('updateOrder: Orders coletados:', orders);
        
        $.post(API_BASE_URL, {
            action: 'update_order',
            orders: JSON.stringify(orders)
        })
        .done(function(response) {
            if (response.success) {
                showAlert('success', 'Ordem atualizada com sucesso!');
                // Não recarregar dados durante modo de ordenação
                if (!sortMode) {
                    loadLabels();
                } else {
                    // Apenas atualizar badges de ordem visualmente
                    updateOrderBadges();
                }
            } else {
                showAlert('error', response.message);
            }
        })
        .fail(function() {
            showAlert('error', 'Erro ao atualizar ordem');
        })
        .always(function() {
            window.updatingOrder = false;
        });
    }
    
    // Função auxiliar para atualizar apenas os badges de ordem
    function updateOrderBadges() {
        $('#labelsTableBody tr').each(function(index) {
            $(this).find('td:nth-child(6) .badge').text(index + 1);
        });
    }

    // Funcionalidade de ordenação hierárquica para sub-labels
    function toggleChildReorderMode() {
        const toggleButton = $('#toggleChildReorder');
        const isReorderMode = toggleButton.text().includes('Reordenar');
        
        console.log('toggleChildReorderMode - isReorderMode:', isReorderMode);
        
        if (isReorderMode) {
            // Ativar modo de reordenação
            console.log('Ativando modo de reordenação das sub-labels...');
            toggleButton.html('<i class="fas fa-check mr-1"></i>Finalizar').removeClass('btn-info').addClass('btn-success');
            $('.sortable-item').addClass('cursor-move');
            
            // Destruir DataTable se existir
            if (childLabelsTable && $.fn.DataTable.isDataTable('#childLabelsTable')) {
                console.log('Destruindo DataTable existente...');
                childLabelsTable.destroy();
                childLabelsTable = null;
            }
            
            // Criar sortable
            if (sortable) {
                console.log('Destruindo sortable anterior...');
                sortable.destroy();
            }
            
            console.log('Criando novo sortable...');
            sortable = Sortable.create(document.getElementById('childLabelsTableBody'), {
                handle: '.sortable-item',
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                forceFallback: false,
                fallbackTolerance: 0,
                scroll: true,
                bubbleScroll: true,
                onStart: function(evt) {
                    console.log('SORTABLE: Início do arraste - índice:', evt.oldIndex);
                },
                onMove: function(evt) {
                    return true; // Permitir movimento
                },
                onEnd: function(evt) {
                    console.log('SORTABLE: Fim do arraste - de:', evt.oldIndex, 'para:', evt.newIndex);
                    if (evt.oldIndex !== evt.newIndex) {
                        console.log('SORTABLE: Posição mudou, iniciando atualização...');
                        // Debounce para evitar múltiplas chamadas
                        clearTimeout(window.updateChildOrderTimeout);
                        window.updateChildOrderTimeout = setTimeout(function() {
                            updateChildOrder();
                        }, 300);
                    } else {
                        console.log('SORTABLE: Posição não mudou, ignorando atualização.');
                    }
                }
            });
            
            showAlert('info', 'Modo de reordenação ativado. Arraste as linhas para reorganizar.');
            
        } else {
            // Desativar modo de reordenação
            console.log('Desativando modo de reordenação das sub-labels...');
            if (sortable) {
                console.log('Destruindo sortable...');
                sortable.destroy();
                sortable = null;
            }
            
            $('.sortable-item').removeClass('cursor-move');
            toggleButton.html('<i class="fas fa-arrows-alt mr-1"></i>Reordenar').removeClass('btn-success').addClass('btn-info');
            
            // Recarregar dados e inicializar DataTable
            console.log('Recarregando dados das sub-labels...');
            setTimeout(function() {
                loadChildLabels();
            }, 200);
            
            showAlert('info', 'Modo de reordenação desativado.');
        }
    }

    function updateChildOrder() {
        console.log('updateChildOrder: Iniciando...');
        
        // Prevenir múltiplas execuções simultâneas
        if (window.updatingChildOrder) {
            console.log('updateChildOrder: Já está executando, ignorando...');
            return;
        }
        window.updatingChildOrder = true;
        
        // Agrupar sub-labels por label pai
        const groupedOrders = {};
        
        $('#childLabelsTableBody tr.sortable-item').each(function(index) {
            const id = $(this).data('id');
            const parentId = $(this).attr('data-parent-id');
            
            console.log(`updateChildOrder: Linha ${index}: ID=${id}, ParentID=${parentId}`);
            
            if (id && parentId) {
                if (!groupedOrders[parentId]) {
                    groupedOrders[parentId] = [];
                }
                
                // Adicionar à lista do grupo com posição relativa
                groupedOrders[parentId].push({ 
                    id: id, 
                    relativePosition: groupedOrders[parentId].length + 1 
                });
            }
        });
        
        console.log('updateChildOrder: Grupos coletados:', groupedOrders);
        
        if (Object.keys(groupedOrders).length === 0) {
            console.error('updateChildOrder: Nenhuma sub-label encontrada');
            showAlert('error', 'Nenhuma sub-label encontrada para reordenar');
            window.updatingChildOrder = false;
            return;
        }
        
        console.log('updateChildOrder: Enviando requisição AJAX...');
        // Enviar atualização agrupada por label pai
        $.ajax({
            url: API_BASE_URL,
            type: 'POST',
            data: {
                action: 'update_child_order_grouped',
                grouped_orders: JSON.stringify(groupedOrders)
            },
            timeout: 10000, // 10 segundos timeout
            beforeSend: function() {
                console.log('updateChildOrder: AJAX beforeSend');
            }
        })
        .done(function(response, textStatus, jqXHR) {
            console.log('updateChildOrder: AJAX done - response:', response);
            console.log('updateChildOrder: AJAX done - textStatus:', textStatus);
            
            try {
                if (typeof response === 'string') {
                    console.log('updateChildOrder: Parsing JSON response...');
                    response = JSON.parse(response);
                }
                
                if (response.success) {
                    console.log('updateChildOrder: Sucesso!');
                    showAlert('success', 'Ordem atualizada com sucesso!');
                    
                    // Verificar se está em modo de reordenação
                    const isReorderMode = $('#toggleChildReorder').text().includes('Finalizar');
                    if (!isReorderMode) {
                        // Recarregar dados apenas se não estiver em modo de reordenação
                        console.log('updateChildOrder: Recarregando sub-labels...');
                        setTimeout(function() {
                            loadChildLabels();
                        }, 200);
                    } else {
                        // Apenas atualizar badges de ordem visualmente
                        updateChildOrderBadgesGrouped(groupedOrders);
                    }
                } else {
                    console.error('updateChildOrder: Erro do servidor:', response.message);
                    showAlert('error', response.message || 'Erro ao atualizar ordem');
                }
            } catch (e) {
                console.error('updateChildOrder: Erro ao processar resposta:', e);
                console.error('updateChildOrder: Response raw:', response);
                showAlert('error', 'Erro ao processar resposta do servidor');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('updateChildOrder: AJAX fail - textStatus:', textStatus);
            console.error('updateChildOrder: AJAX fail - errorThrown:', errorThrown);
            console.error('updateChildOrder: AJAX fail - jqXHR:', jqXHR);
            
            showAlert('error', 'Erro de conexão: ' + textStatus + ' - ' + errorThrown);
        })
        .always(function() {
            console.log('updateChildOrder: AJAX always');
            window.updatingChildOrder = false;
        });
    }
    
    // Função auxiliar para atualizar apenas os badges de ordem das sub-labels agrupadas
    function updateChildOrderBadgesGrouped(groupedOrders) {
        $('#childLabelsTableBody tr.sortable-item').each(function(index) {
            const id = $(this).data('id');
            const parentId = $(this).attr('data-parent-id');
            
            if (id && parentId && groupedOrders[parentId]) {
                const orderItem = groupedOrders[parentId].find(item => item.id == id);
                if (orderItem) {
                    $(this).find('td:nth-child(6) .badge').text(orderItem.relativePosition);
                }
            }
        });
    }

    // Funcionalidade de carregamento da hierarquia
    function loadHierarchy() {
        $('#hierarchyLoading').addClass('show');
        $('#hierarchyContainer').empty(); // Limpa o conteúdo anterior

        $.get(API_BASE_URL + '?action=get_hierarchy')
            .done(function(response) {
                if (response.success) {
                    renderHierarchy(response.data);
                } else {
                    showAlert('error', 'Erro ao carregar estrutura hierárquica: ' + response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão ao carregar estrutura hierárquica');
            })
            .always(function() {
                $('#hierarchyLoading').removeClass('show');
            });
    }

    function renderHierarchy(data) {
        const hierarchyContainer = $('#hierarchyContainer');
        hierarchyContainer.empty();

        data.forEach(hierarchyItem => {
            const label = hierarchyItem.label;
            const children = hierarchyItem.children;

            // Card para cada label principal
            const card = $(`
                <div class="card mb-3 hierarchy-card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="${label.icon || 'fas fa-tag'} mr-2"></i>
                                ${label.nome_label}
                                <small class="ml-2 opacity-75">(${label.key_label})</small>
                            </h6>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-light" onclick="editLabel(${label.label_id})" title="Editar Label">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-light" onclick="deleteLabel(${label.label_id})" title="Excluir Label">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <p class="text-muted mb-2">${label.descricao || 'Sem descrição'}</p>
                                ${label.url ? `<small><strong>URL:</strong> ${label.url}</small>` : ''}
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge ${label.disabled == 1 ? 'badge-danger' : 'badge-success'} mr-2">
                                    ${label.disabled == 1 ? 'Desabilitada' : 'Ativa'}
                                </span>
                                <span class="badge badge-info">Ordem: ${label.display_order || label.label_id}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Lista de sub-labels
            if (children && children.length > 0) {
                const childrenContainer = $('<div class="mt-3"></div>');
                const childrenList = $('<div class="list-group"></div>');
                
                children.forEach(child => {
                    const childItem = $(`
                        <div class="list-group-item d-flex justify-content-between align-items-center child-item">
                            <div>
                                <i class="fas fa-layer-group text-muted mr-2"></i>
                                <strong>${child.nome}</strong>
                                <span class="badge badge-info ml-2">Ref: ${child.referencial}</span>
                                ${child.grupo ? `<span class="badge badge-secondary ml-1">${child.grupo}</span>` : ''}
                            </div>
                            <div>
                                <span class="badge ${child.disable == 1 ? 'badge-danger' : 'badge-success'} mr-2">
                                    ${child.disable == 1 ? 'Desabilitada' : 'Ativa'}
                                </span>
                                <span class="badge badge-secondary mr-2">Ordem: ${child.display_order || child.referencial}</span>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editChildLabel(${child.id_label})" title="Editar Sub-Label">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteChildLabel(${child.id_label})" title="Excluir Sub-Label">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                    childrenList.append(childItem);
                });

                childrenContainer.append('<h6 class="text-muted"><i class="fas fa-sitemap mr-2"></i>Sub-Labels:</h6>');
                childrenContainer.append(childrenList);
                card.find('.card-body').append(childrenContainer);
            } else {
                card.find('.card-body').append('<p class="text-muted mb-0"><i class="fas fa-info-circle mr-2"></i>Nenhuma sub-label encontrada</p>');
            }

            hierarchyContainer.append(card);
        });

        if (data.length === 0) {
            hierarchyContainer.append('<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>Nenhuma label encontrada</div>');
        }
    }

    // Cache management
    function updateCache() {
        $.post(API_BASE_URL, { action: 'update_cache' })
            .done(function(response) {
                if (response.success) {
                    showAlert('success', 'Cache atualizado com sucesso!');
                } else {
                    showAlert('error', 'Erro ao atualizar cache: ' + response.message);
                }
            })
            .fail(function() {
                showAlert('error', 'Erro de conexão ao atualizar cache');
            });
    }

    // Sistema de alertas
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const icon = type === 'success' ? 'fa-check-circle' : 
                    type === 'error' ? 'fa-exclamation-circle' : 
                    type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `);
        
        $('#alertContainer').append(alert);
        
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
</script> 