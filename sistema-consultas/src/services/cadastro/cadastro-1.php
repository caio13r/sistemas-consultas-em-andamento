<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();

// Carrega dados de acesso do usuário
$row = [];
if (Session::get('grupo') != 0) {
    try {
        $grupo = Session::get('grupo');
        $subgrupo = Session::get('subgrupo');
        $db = Database1::getInstance();
        $con = $db->getConnection();
        $query = "SELECT * FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
        $stmt = $con->prepare($query);
        $stmt->bindParam(':grupo', $grupo);
        $stmt->bindParam(':subgrupo', $subgrupo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $row = [];
        }
        
        // Debug temporário
        error_log("DEBUG CADASTRO-1: Grupo=" . $grupo . ", Subgrupo=" . $subgrupo);
        error_log("DEBUG CADASTRO-1: Row data=" . print_r($row, true));
        
        // Verifica se as colunas existem na tabela
        $checkColumns = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_acessos' AND COLUMN_NAME IN ('CDacesso', 'CD1acesso')";
        $stmtCheck = $con->prepare($checkColumns);
        $stmtCheck->execute();
        $columns = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        error_log("DEBUG CADASTRO-1: Colunas existentes=" . implode(',', $columns));
        
        if (isset($row['CD1acesso'])) {
            error_log("DEBUG CADASTRO-1: CD1acesso=" . $row['CD1acesso']);
        } else {
            error_log("DEBUG CADASTRO-1: CD1acesso não existe na consulta");
        }
        
        if (isset($row['CDacesso'])) {
            error_log("DEBUG CADASTRO-1: CDacesso=" . $row['CDacesso']);
        } else {
            error_log("DEBUG CADASTRO-1: CDacesso não existe na consulta");
        }
        
    } catch (PDOException $error) {
        error_log("DEBUG CADASTRO-1: Erro PDO=" . $error->getMessage());
        $row = [];
    }
}

if (Session::get('grupo') != 0 && (!isset($row['CDacesso']) || $row['CDacesso'] == false || !isset($row['CD1acesso']) || $row['CD1acesso'] == false)) {
    error_log("DEBUG CADASTRO-1: Redirecionando por falta de permissão");
    
    // Debug temporário na tela
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>DEBUG: Problema de Permissão Detectado</h3>";
    echo "<p><strong>Grupo:</strong> " . Session::get('grupo') . "</p>";
    echo "<p><strong>Subgrupo:</strong> " . Session::get('subgrupo') . "</p>";
    echo "<p><strong>CDacesso existe:</strong> " . (isset($row['CDacesso']) ? 'Sim' : 'Não') . "</p>";
    echo "<p><strong>CDacesso valor:</strong> " . (isset($row['CDacesso']) ? $row['CDacesso'] : 'N/A') . "</p>";
    echo "<p><strong>CD1acesso existe:</strong> " . (isset($row['CD1acesso']) ? 'Sim' : 'Não') . "</p>";
    echo "<p><strong>CD1acesso valor:</strong> " . (isset($row['CD1acesso']) ? $row['CD1acesso'] : 'N/A') . "</p>";
    echo "<p><strong>Dados completos do usuário:</strong><br><pre>" . print_r($row, true) . "</pre></p>";
    echo "<p><strong>Ação requerida:</strong> Execute o script SQL 'scripts_sql_permissoes_cadastro.sql' no banco de dados e configure as permissões no módulo de Acessos.</p>";
    echo "<p><a href='/cadastro' style='color: yellow;'>← Voltar aos Cadastros</a></p>";
    echo "</div>";
    exit;
} else {
    error_log("DEBUG CADASTRO-1: Permissão OK, continuando...");
}
// Mensagens
$error_message = '';
$success_message = '';

// Verifica se veio redirecionamento com sucesso
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = '✅ Fatura cadastrada com sucesso! O formulário foi limpo e está pronto para a próxima inserção.';
}

// Gera um token único para prevenir reenvio
$form_token = uniqid('form_', true);

$usuario = Session::get('name') ?? Session::get('nome') ?? Session::get('user') ?? Session::get('login') ?? Session::get('usuario') ?? 'Desconhecido';

// Dados mocados para faturas (simulando banco de dados)
$faturas_mock = [];
if (!isset($_SESSION['faturas_mock'])) {
    $_SESSION['faturas_mock'] = [
        [
            'id' => 1,
            'numero_fatura' => 'BB202401001',
            'numero_conta' => '12345-6',
            'agencia' => '1234',
            'valor' => 1250.50,
            'data_vencimento' => '2024-01-15',
            'status' => 'pendente',
            'descricao' => 'Taxa de manutenção conta corrente',
            'usuario_cadastrou' => 'admin',
            'criado_em' => '2024-01-10 14:30:00'
        ],
        [
            'id' => 2,
            'numero_fatura' => 'BB202401002',
            'numero_conta' => '67890-1',
            'agencia' => '5678',
            'valor' => 850.75,
            'data_vencimento' => '2024-01-20',
            'status' => 'pago',
            'descricao' => 'Tarifa de transferências',
            'usuario_cadastrou' => 'admin',
            'criado_em' => '2024-01-12 09:15:00'
        ]
    ];
}

// Inserção de dados via POST
if (isset($_POST['submit_fatura']) || isset($_POST['submit_manual'])) {
    // Verifica se o token é válido para prevenir reenvio
    if (!isset($_POST['form_token']) || empty($_POST['form_token'])) {
        $error_message = 'Token de formulário inválido. Tente novamente.';
    } else {
        $numero_fatura = trim($_POST['numero_fatura'] ?? '');
        $numero_conta = trim($_POST['numero_conta'] ?? '');
        $agencia = trim($_POST['agencia'] ?? '');
        $valor = trim($_POST['valor'] ?? '');
        $data_vencimento = $_POST['data_vencimento'] ?? '';
        $status = $_POST['status'] ?? 'pendente';
        $descricao = trim($_POST['descricao'] ?? '');
        
        $required_fields = [];
        if (empty($numero_fatura)) $required_fields[] = 'Número da Fatura';
        if (empty($numero_conta)) $required_fields[] = 'Número da Conta';
        if (empty($agencia)) $required_fields[] = 'Agência';
        if (empty($valor)) $required_fields[] = 'Valor';
        if (empty($data_vencimento)) $required_fields[] = 'Data de Vencimento';

        if (!empty($required_fields)) {
            $error_message = 'Preencha os campos obrigatórios: ' . implode(', ', $required_fields) . '.';
        } else {
            // Verifica se a fatura já existe (simulando verificação no banco)
            $fatura_existe = false;
            foreach ($_SESSION['faturas_mock'] as $fatura) {
                if ($fatura['numero_fatura'] === $numero_fatura) {
                    $fatura_existe = true;
                    break;
                }
            }
            
            if ($fatura_existe) {
                $error_message = 'A fatura informada já foi cadastrada!';
            } else {
                try {
                    // Simula inserção no banco
                    $nova_fatura = [
                        'id' => count($_SESSION['faturas_mock']) + 1,
                        'numero_fatura' => $numero_fatura,
                        'numero_conta' => $numero_conta,
                        'agencia' => $agencia,
                        'valor' => (float)$valor,
                        'data_vencimento' => $data_vencimento,
                        'status' => $status,
                        'descricao' => $descricao,
                        'usuario_cadastrou' => $usuario,
                        'criado_em' => date('Y-m-d H:i:s')
                    ];
                    
                    array_unshift($_SESSION['faturas_mock'], $nova_fatura);
                    
                    // Limita a 20 registros para não sobrecarregar a sessão
                    if (count($_SESSION['faturas_mock']) > 20) {
                        $_SESSION['faturas_mock'] = array_slice($_SESSION['faturas_mock'], 0, 20);
                    }
                    
                    $current_url = $_SERVER['REQUEST_URI'];
                    $timestamp = time();
                    
                    if (strpos($current_url, 'tipoConsulta=1') !== false) {
                        $redirect_url = preg_replace('/[?&]success=[^&]*/', '', $current_url);
                        $redirect_url = preg_replace('/[?&]t=[^&]*/', '', $redirect_url);
                        $redirect_url = rtrim($redirect_url, '?&');
                        $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "success=1&t=$timestamp";
                    } else {
                        $redirect_url = "/cadastro?tipoConsulta=1&success=1&t=$timestamp";
                    }
                    
                    echo "<script>
                        console.log('Fatura cadastrada com sucesso. Redirecionando para: $redirect_url');
                        window.location.href = '$redirect_url';
                    </script>";
                    exit();
                    
                } catch (Exception $e) {
                    $error_message = 'Erro ao cadastrar fatura: ' . $e->getMessage();
                }
            }
        }
    }
}

// Busca as últimas faturas (dados mocados)
$ultimas_faturas = array_slice($_SESSION['faturas_mock'], 0, 10);
$total_faturas = count($_SESSION['faturas_mock']);

// Estatísticas por status
$stats = ['pendente' => 0, 'pago' => 0, 'vencido' => 0];
foreach ($_SESSION['faturas_mock'] as $fatura) {
    if (isset($stats[$fatura['status']])) {
        $stats[$fatura['status']]++;
    }
}

require_once INC_PATH . '/header.php';
?>

<style>
.cadastro-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.cadastro-form-group { margin-bottom: 1rem; }
.cadastro-form-control { width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ccc; border-radius: 0.25rem; }
.cadastro-btn { padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; }
.cadastro-btn-primary { background: #007bff; color: white; border: 1px solid #007bff; }
.cadastro-btn-success { background: #28a745; color: white; border: 1px solid #28a745; }
.cadastro-alert { padding: 1rem; border-radius: 0.25rem; margin-bottom: 1rem; }
.cadastro-alert-danger { background: #f8d7da; color: #842029; }
.cadastro-alert-success { background: #d1e7dd; color: #0f5132; }
.cadastro-table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
.cadastro-table th, .cadastro-table td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
.cadastro-table th { background: #f8f9fa; }
.status-pendente { color: #856404; background: #fff3cd; padding: 2px 6px; border-radius: 3px; }
.status-pago { color: #155724; background: #d1e7dd; padding: 2px 6px; border-radius: 3px; }
.status-vencido { color: #721c24; background: #f8d7da; padding: 2px 6px; border-radius: 3px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; text-align: center; }

/* Modal CSS */
.cadastro-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.cadastro-modal-content { background-color: #fff; margin: 3% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.cadastro-modal-header { padding: 20px 30px; background-color: #007bff; color: white; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
.cadastro-modal-header h2 { margin: 0; font-size: 1.5rem; }
.cadastro-close { color: white; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }
.cadastro-close:hover { opacity: 0.7; }
.cadastro-modal-body { padding: 30px; }
.cadastro-modal-footer { padding: 20px 30px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
.cadastro-btn-cancel { background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.cadastro-btn-cancel:hover { background-color: #5a6268; }
.cadastro-btn-insert { background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.cadastro-btn-insert:hover { background-color: #218838; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery não está carregado. Os scripts não serão executados.');
        return;
    }
    console.log('jQuery carregado e DOM pronto!');

    // Se a página carregou com uma mensagem de sucesso, limpa e foca no campo número da fatura.
    <?php if (!empty($success_message)): ?>
    $(document).ready(function() {
        // Limpa todos os campos do formulário principal
        $('#numero_fatura').val('').focus();
        $('#numero_conta').val('');
        $('#agencia').val('');
        $('#valor').val('');
        $('#data_vencimento').val('');
        $('#status').val('pendente');
        $('#descricao').val('');
        
        console.log('Página carregada com sucesso. Formulário limpo e focado no número da fatura.');
        
        // Remove a mensagem de sucesso após 5 segundos
        setTimeout(function() {
            $('.cadastro-alert-success').fadeOut();
        }, 5000);
    });
    <?php endif; ?>

    // Função para formatar valor monetário
    function formatarValor(valor) {
        if (!valor) return '';
        return valor.replace(/\D/g, '')
                  .replace(/(\d)(\d{2})$/, '$1,$2')
                  .replace(/(?=(\d{3})+(\D))\B/g, '.');
    }

    // Aplica máscara de valor monetário
    $('#valor, #modal_valor').on('input', function() {
        $(this).val(formatarValor($(this).val()));
    });

    // Evento de submissão do formulário principal
    $('#form-fatura').on('submit', function(e) {
        const form = this;
        const numero_fatura = $('#numero_fatura').val().trim();
        const valor = $('#valor').val().trim();
        
        // Previne múltiplos envios
        const submitBtn = $(form).find('button[type="submit"]');
        if (submitBtn.prop('disabled')) {
            console.log('Formulário já está sendo processado...');
            e.preventDefault();
            return;
        }

        if (!numero_fatura) {
            alert('Número da fatura é obrigatório.');
            e.preventDefault();
            return;
        }

        if (!valor) {
            alert('Valor é obrigatório.');
            e.preventDefault();
            return;
        }
        
        // Desabilita o botão para prevenir múltiplos envios
        submitBtn.prop('disabled', true).text('Processando...');
    });
    
    // --- Lógica do Modal ---
    const modal = document.getElementById('modalInserirManual');

    window.abrirModal = function() {
        $('#formModalInserir')[0].reset();
        $('#modal_status').val('pendente');
        modal.style.display = 'block';
        $('#modal_numero_fatura').focus();
    };

    window.fecharModal = function() {
        modal.style.display = 'none';
    };

    // Função para limpar o formulário principal
    window.limparFormulario = function() {
        $('#numero_fatura').val('').focus();
        $('#numero_conta').val('');
        $('#agencia').val('');
        $('#valor').val('');
        $('#data_vencimento').val('');
        $('#status').val('pendente');
        $('#descricao').val('');
        
        console.log('Formulário limpo manualmente');
        
        // Remove mensagens de erro/sucesso
        $('.cadastro-alert').fadeOut();
    };

    // Fecha o modal se clicar fora dele
    window.onclick = function(event) {
        if (event.target == modal) {
            fecharModal();
        }
    };
    
    $('.cadastro-close').on('click', fecharModal);
    $('.cadastro-btn-cancel').on('click', fecharModal);

    // Atalhos de teclado
    $(document).on('keydown', function(e) {
        // Ctrl+L para limpar formulário
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            limparFormulario();
        }
    });

    // Foca no campo número da fatura quando a página carrega
    $('#numero_fatura').focus();

    // Proteção contra reenvio do formulário (F5)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>

<div class="cadastro-container">
    <div style="margin-bottom: 20px;">
        <a href="/cadastro" class="cadastro-btn" style="background: #6c757d; color: white; text-decoration: none;">
            ← Voltar aos Cadastros
        </a>
    </div>
    
    <h1>🏦 Cadastro de Faturas - Banco do Brasil</h1>

    <?php if (!empty($success_message)): ?>
        <div class="cadastro-alert cadastro-alert-success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="cadastro-alert cadastro-alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $total_faturas ?></h3>
            <p>Total de Faturas</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['pendente'] ?></h3>
            <p>Pendentes</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['pago'] ?></h3>
            <p>Pagas</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['vencido'] ?></h3>
            <p>Vencidas</p>
        </div>
    </div>

    <div style="margin-bottom: 20px; text-align: right;">
        <button onclick="abrirModal()" class="cadastro-btn cadastro-btn-success">
            ➕ Inserir Manualmente
        </button>
        <button onclick="limparFormulario()" class="cadastro-btn" style="background: #ffc107; color: black; margin-left: 10px;">
            🗑️ Limpar Formulário
        </button>
    </div>

    <div style="max-width: 600px; margin: 0 auto 2rem auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
        <h3>Cadastrar Nova Fatura</h3>
        <?php if (!empty($success_message)): ?>
        <div style="background: #e8f5e8; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #28a745;">
            <strong>🎯 Pronto para cadastrar!</strong> Preencha os dados da próxima fatura.
        </div>
        <?php endif; ?>
        
        <form method="POST" id="form-fatura" autocomplete="off">
            <div class="cadastro-form-group">
                <label for="numero_fatura">Número da Fatura*:</label>
                <input type="text" id="numero_fatura" name="numero_fatura" class="cadastro-form-control" autofocus required placeholder="BB202401001">
            </div>
            
            <div class="cadastro-form-group">
                <label for="numero_conta">Número da Conta*:</label>
                <input type="text" id="numero_conta" name="numero_conta" class="cadastro-form-control" required placeholder="12345-6">
            </div>
            
            <div class="cadastro-form-group">
                <label for="agencia">Agência*:</label>
                <input type="text" id="agencia" name="agencia" class="cadastro-form-control" required placeholder="1234">
            </div>
            
            <div class="cadastro-form-group">
                <label for="valor">Valor (R$)*:</label>
                <input type="text" id="valor" name="valor" class="cadastro-form-control" required placeholder="1.250,50">
            </div>
            
            <div class="cadastro-form-group">
                <label for="data_vencimento">Data de Vencimento*:</label>
                <input type="date" id="data_vencimento" name="data_vencimento" class="cadastro-form-control" required>
            </div>
            
            <div class="cadastro-form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="cadastro-form-control">
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                    <option value="vencido">Vencido</option>
                </select>
            </div>
            
            <div class="cadastro-form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" class="cadastro-form-control" rows="3" placeholder="Descrição da fatura..."></textarea>
            </div>
            
            <input type="hidden" name="form_token" value="<?= $form_token ?>">
            
            <button type="submit" name="submit_fatura" class="cadastro-btn cadastro-btn-primary" style="width: 100%;">Cadastrar Fatura</button>
        </form>
    </div>

    <h3>Últimas 10 Faturas (Total de <?= number_format($total_faturas, 0, ',', '.') ?> registros)</h3>
    <table class="cadastro-table">
        <thead>
            <tr>
                <th>Número</th><th>Conta</th><th>Agência</th><th>Valor</th>
                <th>Vencimento</th><th>Status</th><th>Descrição</th>
                <th>Cadastrado por</th><th>Data Cadastro</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ultimas_faturas as $fatura): ?>
            <tr>
                <td><?= htmlspecialchars($fatura['numero_fatura']) ?></td>
                <td><?= htmlspecialchars($fatura['numero_conta']) ?></td>
                <td><?= htmlspecialchars($fatura['agencia']) ?></td>
                <td>R$ <?= number_format($fatura['valor'], 2, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($fatura['data_vencimento'])) ?></td>
                <td><span class="status-<?= $fatura['status'] ?>"><?= ucfirst($fatura['status']) ?></span></td>
                <td><?= htmlspecialchars(substr($fatura['descricao'], 0, 50)) ?><?= strlen($fatura['descricao']) > 50 ? '...' : '' ?></td>
                <td><?= htmlspecialchars($fatura['usuario_cadastrou']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($fatura['criado_em'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para inserção manual -->
<div id="modalInserirManual" class="cadastro-modal">
    <div class="cadastro-modal-content">
        <div class="cadastro-modal-header">
            <h2>Inserir Nova Fatura Manualmente</h2>
            <span class="cadastro-close">&times;</span>
        </div>
        <form id="formModalInserir" method="POST" autocomplete="off">
            <div class="cadastro-modal-body">
                <div class="cadastro-form-group"><label for="modal_numero_fatura">Número da Fatura*:</label><input type="text" id="modal_numero_fatura" name="numero_fatura" class="cadastro-form-control" required placeholder="BB202401001"></div>
                <div class="cadastro-form-group"><label for="modal_numero_conta">Número da Conta*:</label><input type="text" id="modal_numero_conta" name="numero_conta" class="cadastro-form-control" required placeholder="12345-6"></div>
                <div class="cadastro-form-group"><label for="modal_agencia">Agência*:</label><input type="text" id="modal_agencia" name="agencia" class="cadastro-form-control" required placeholder="1234"></div>
                <div class="cadastro-form-group"><label for="modal_valor">Valor (R$)*:</label><input type="text" id="modal_valor" name="valor" class="cadastro-form-control" required placeholder="1.250,50"></div>
                <div class="cadastro-form-group"><label for="modal_data_vencimento">Data de Vencimento*:</label><input type="date" id="modal_data_vencimento" name="data_vencimento" class="cadastro-form-control" required></div>
                <div class="cadastro-form-group"><label for="modal_status">Status:</label><select id="modal_status" name="status" class="cadastro-form-control"><option value="pendente">Pendente</option><option value="pago">Pago</option><option value="vencido">Vencido</option></select></div>
                <div class="cadastro-form-group"><label for="modal_descricao">Descrição:</label><textarea id="modal_descricao" name="descricao" class="cadastro-form-control" rows="3" placeholder="Descrição da fatura..."></textarea></div>
                <input type="hidden" name="form_token" value="<?= $form_token ?>">
            </div>
            <div class="cadastro-modal-footer">
                <button type="button" class="cadastro-btn-cancel">Cancelar</button>
                <button type="submit" name="submit_manual" class="cadastro-btn-insert">Inserir</button>
            </div>
        </form>
    </div>
</div>

<?php require_once INC_PATH . '/footer.php'; ?> 