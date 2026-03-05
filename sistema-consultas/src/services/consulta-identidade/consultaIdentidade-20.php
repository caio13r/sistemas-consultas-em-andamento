<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

// Configuração da API - URLs fixas para produção e desenvolvimento
$is_production = strpos($_SERVER['HTTP_HOST'] ?? '', 'cfo.org.br') !== false;

if ($is_production) {
    $API_BASE_URL = 'https://id.cfo.org.br'; // https://id.cfo.org.br/api/consulta/rastreamento
} else {
    $API_BASE_URL = 'https://id.cfo.org.br'; // HTTP para desenvolvimento
}

error_log("Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . " | HTTPS: " . (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'N/A') . " | API_URL: " . $API_BASE_URL);

Session::CheckSession();
if (Session::get('grupo') != 0 && (isset($row['CI20acesso']) && $row['CI20acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-identidade';
    </script>";
    exit;
}

// Mensagens
$error_message = '';
$success_message = '';

// Verifica se veio redirecionamento com sucesso
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = '✅ Registro inserido com sucesso! O formulário foi limpo e está pronto para a próxima inserção.QUERO QUERO ';
}

// Gera um token único para prevenir reenvio
$form_token = uniqid('form_', true);

require_once __DIR__ . '/../../config/config.php';

$token = $_ENV['API_TOKEN'] ?? null;
$usuario = Session::get('name') ?? Session::get('nome') ?? Session::get('user') ?? Session::get('login') ?? Session::get('usuario') ?? 'Desconhecido';

// Inserção de dados via POST
if (isset($_POST['submit_ar']) || isset($_POST['submit_manual'])) {
    // Verifica se o token é válido para prevenir reenvio
    if (!isset($_POST['form_token']) || empty($_POST['form_token'])) {
        $error_message = 'Token de formulário inválido. Tente novamente.';
    } else {
        $con = Database1::getInstance()->getConnection();
        $ar = trim($_POST['ar'] ?? '');
        $inscricao = trim($_POST['inscricao'] ?? '');
        $data_despacho = $_POST['data_despacho'] ?? '';
        $cro_uf = $_POST['cro_uf'] ?? '';
        $cpf = trim($_POST['cpf'] ?? '');
        $consta_api = isset($_POST['consta_api']) ? (int)$_POST['consta_api'] : 0;
        
        if (empty($inscricao)) $required_fields[] = 'Inscrição';
        if (empty($data_despacho)) $required_fields[] = 'Data do Despacho';
        if (empty($cro_uf)) $required_fields[] = 'CRO/UF';

        if (!empty($required_fields)) {
            $error_message = 'Preencha os campos obrigatórios: ' . implode(', ', $required_fields) . '.';
        } else {
            // Se houver um AR, verifica se é duplicado
            if (!empty($ar)) {
                $stmt = $con->prepare('SELECT COUNT(*) FROM carteirinhas_despachadas_cro WHERE ar = ?');
                $stmt->execute([$ar]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $error_message = 'O AR informado já foi cadastrado!';
                }
            }

            if (empty($error_message)) {
                try {
                    $sql = 'INSERT INTO carteirinhas_despachadas_cro (ar, inscricao, data_despacho, cro_uf, cpf, consta_api, usuario_adicionou, motivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                    
                    $stmt = $con->prepare($sql);
                    $result = $stmt->execute([$ar, $inscricao, $data_despacho, $cro_uf, $cpf, $consta_api, $usuario, '']);
                    
                    if ($result) {
                        // Verifica se estamos na página correta
                        $current_url = $_SERVER['REQUEST_URI'];
                        $timestamp = time();
                        
                        if (strpos($current_url, 'tipoConsulta=20') !== false) {
                            // Estamos na página correta, apenas adiciona os parâmetros
                            $redirect_url = preg_replace('/[?&]success=[^&]*/', '', $current_url);
                            $redirect_url = preg_replace('/[?&]t=[^&]*/', '', $redirect_url);
                            $redirect_url = rtrim($redirect_url, '?&');
                            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "success=1&t=$timestamp";
                        } else {
                            // Não estamos na página correta, vai para a página principal
                            $redirect_url = "/consulta-identidade?tipoConsulta=20&success=1&t=$timestamp";
                        }
                        
                        echo "<script>
                            console.log('Inserção bem-sucedida. Redirecionando para: $redirect_url');
                            window.location.href = '$redirect_url';
                        </script>";
                        exit();
                    } else {
                        $error_message = 'Erro ao inserir registro no banco de dados.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'Erro no banco de dados: ' . $e->getMessage();
                }
            }
        }
    }
}

// Listar últimos 20 registros e contagem por estado
try {
    $con = Database1::getInstance()->getConnection();
    
    $query = "SELECT * FROM carteirinhas_despachadas_cro ORDER BY id DESC LIMIT 10";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $ultimos_despachos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $query_count = "SELECT cro_uf, COUNT(*) as total FROM carteirinhas_despachadas_cro GROUP BY cro_uf ORDER BY cro_uf";
    $stmt_count = $con->prepare($query_count);
    $stmt_count->execute();
    $contagem_estados = $stmt_count->fetchAll(PDO::FETCH_ASSOC);
    
    $query_total = "SELECT COUNT(*) as total FROM carteirinhas_despachadas_cro";
    $stmt_total = $con->prepare($query_total);
    $stmt_total->execute();
    $total_geral = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $error) {
    die("Erro ao retornar os dados: " . $error->getMessage());
}

$ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
        'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
        'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
?>

<style>
.consulta-20-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.consulta-20-form-group { margin-bottom: 1rem; }
.consulta-20-form-control { width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ccc; border-radius: 0.25rem; }
.consulta-20-btn { padding: 0.375rem 0.75rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; }
.consulta-20-btn-primary { background: #007bff; color: white; border: 1px solid #007bff; }
.consulta-20-btn-success { background: #28a745; color: white; border: 1px solid #28a745; }
.consulta-20-alert { padding: 1rem; border-radius: 0.25rem; margin-bottom: 1rem; }
.consulta-20-alert-danger { background: #f8d7da; color: #842029; }
.consulta-20-alert-success { background: #d1e7dd; color: #0f5132; }
.consulta-20-table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
.consulta-20-table th, .consulta-20-table td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
.consulta-20-table th { background: #f8f9fa; }
.consulta-20-stats-table { margin-bottom: 2rem; max-width: 100%; overflow-x: auto; }
.consulta-20-stats-table th, .consulta-20-stats-table td { padding: 0.3rem 0.5rem; text-align: center; }
.consulta-20-total-row { font-weight: bold; background: #e9ecef; }
.text-success { color: #155724; }
.text-danger { color: #721c24; }

/* Modal CSS */
.consulta-20-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.consulta-20-modal-content { background-color: #fff; margin: 3% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.consulta-20-modal-header { padding: 20px 30px; background-color: #007bff; color: white; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
.consulta-20-modal-header h2 { margin: 0; font-size: 1.5rem; }
.consulta-20-close { color: white; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }
.consulta-20-close:hover { opacity: 0.7; }
.consulta-20-modal-body { padding: 30px; }
.consulta-20-modal-footer { padding: 20px 30px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
.consulta-20-btn-cancel { background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.consulta-20-btn-cancel:hover { background-color: #5a6268; }
.consulta-20-btn-insert { background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.consulta-20-btn-insert:hover { background-color: #218838; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Garante que o jQuery está disponível antes de executar o código
    if (typeof jQuery === 'undefined') {
        console.error('jQuery não está carregado. Os scripts não serão executados.');
        return;
    }
    console.log('jQuery carregado e DOM pronto!');

    const API_TOKEN = '<?= $token ?>';
    const API_BASE_URL = '<?= $API_BASE_URL ?>';

    // Se a página carregou com uma mensagem de sucesso, limpa e foca no campo AR.
    <?php if (!empty($success_message)): ?>
    $(document).ready(function() {
        // Limpa todos os campos do formulário principal
        $('#ar').val('').focus();
        $('#inscricao').val('');
        $('#cpf').val('');
        $('#cro_uf').val('');
        $('#data_despacho').val('');
        $('#consta_api').val('0');
        
        // Limpa também o formulário de teste
        $('#test_ar').val('YA123456789BR');
        $('#test_inscricao').val('12345');
        $('#test_cro_uf').val('SP');
        $('#test_data_despacho').val('<?= date('Y-m-d') ?>');
        $('#test_cpf').val('123.456.789-00');
        
        console.log('Página carregada com sucesso. Formulário limpo e focado no campo AR.');
        
        // Remove a mensagem de sucesso após 5 segundos
        setTimeout(function() {
            $('.consulta-20-alert-success').fadeOut();
        }, 5000);
    });
    <?php endif; ?>

    // Função para formatar CPF
    function formatarCPF(cpf) {
        if (!cpf) return '';
        return cpf.replace(/\D/g, '')
                  .replace(/(\d{3})(\d)/, '$1.$2')
                  .replace(/(\d{3})(\d)/, '$1.$2')
                  .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    // Função para buscar dados na API
    function consultarApiAR(ar) {
        const url = `${API_BASE_URL}/api/consulta/identidade/ar?token=${encodeURIComponent(API_TOKEN)}&ar=${encodeURIComponent(ar)}&v=${Date.now()}`;
        return $.get(url); // Retorna a promessa do jQuery
    }

    // Preenche os campos ocultos quando o AR é válido e os dados são encontrados
    function preencherCamposComApi(item) {
        console.log('=== DEBUG preencherCamposComApi ===');
        console.log('Item recebido:', item);
        console.log('item existe?', !!item);
        
        if (item) {
            console.log('item.inscricao:', item.inscricao);
            console.log('item.cro:', item.cro);
            console.log('Condição (item.inscricao || item.cro):', !!(item.inscricao || item.cro));
        }
        
        if (item && (item.inscricao || item.cro)) {
            $('#inscricao').val(item.inscricao || '');
            $('#cpf').val(formatarCPF(item.cpf || ''));
            $('#cro_uf').val(item.cro || '');
            $('#consta_api').val('1');
            $('#data_despacho').val(new Date().toISOString().split('T')[0]); // Data atual
            console.log('✅ Campos preenchidos pela API!');
            console.log('Valores finais:');
            console.log('  Inscrição:', $('#inscricao').val());
            console.log('  CPF:', $('#cpf').val());
            console.log('  CRO/UF:', $('#cro_uf').val());
            console.log('  Data:', $('#data_despacho').val());
            console.log('  Consta API:', $('#consta_api').val());
            return true;
        } else {
            console.log('❌ Não foi possível preencher campos - item inválido ou dados insuficientes');
            return false;
        }
    }

    // Limpa os campos ocultos
    function limparCamposOcultos() {
        $('#inscricao').val('');
        $('#cpf').val('');
        $('#cro_uf').val('');
        $('#data_despacho').val('');
        $('#consta_api').val('0');
    }

    // Evento ao digitar ou colar no campo AR
    $('#ar').on('blur', function() {
        const ar = $(this).val().trim();
        console.log('=== BLUR AR ===');
        console.log('AR digitado:', ar);
        console.log('Tamanho:', ar.length);
        console.log('Formato válido:', /^YA\d{9}BR$/.test(ar));
        
        if (ar.length === 13 && /^YA\d{9}BR$/.test(ar)) {
            console.log('AR válido, consultando API...');
            consultarApiAR(ar)
                .done(function(data) {
                    console.log('Resposta da API (BLUR):', data);
                    const item = (data?.list?.[0] || data?.identidade?.[0]);
                    console.log('Item extraído (BLUR):', item);
                    
                    if (!preencherCamposComApi(item)) {
                        console.log('Não foi possível preencher campos, limpando...');
                        limparCamposOcultos();
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Erro na API (BLUR):', {xhr, status, error});
                    limparCamposOcultos();
                });
        } else {
            console.log('AR inválido, limpando campos...');
            limparCamposOcultos();
        }
    });

    // Evento de submissão do formulário principal de AR
    $('#form-ar').on('submit', function(e) {
        e.preventDefault(); // Previne o envio imediato
        const form = this;
        const ar = $('#ar').val().trim();
        
        // Previne múltiplos envios
        const submitBtn = $(form).find('button[type="submit"]');
        if (submitBtn.prop('disabled')) {
            console.log('Formulário já está sendo processado...');
            return;
        }
        
        // Desabilita o botão para prevenir múltiplos envios
        submitBtn.prop('disabled', true).text('Processando...');
        
        console.log('=== DEBUG SUBMIT FORM ===');
        console.log('AR digitado:', ar);
        console.log('Inscrição atual:', $('#inscricao').val());
        console.log('CRO/UF atual:', $('#cro_uf').val());
        console.log('CPF atual:', $('#cpf').val());
        console.log('Data atual:', $('#data_despacho').val());
        console.log('Consta API atual:', $('#consta_api').val());

        // Imprimir a URL que será consultada na API
        const API_TOKEN = '<?= $token ?>';
        const API_BASE_URL = '<?= $API_BASE_URL ?>';
        const urlConsulta = `${API_BASE_URL}/api/consulta/identidade/ar?token=${encodeURIComponent(API_TOKEN)}&ar=${encodeURIComponent(ar)}&v=${Date.now()}`;
        console.log('URL consultada na API:', urlConsulta);

        if (!ar || ar.length !== 13 || !/^YA\d{9}BR$/.test(ar)) {
            alert('AR inválido. O formato esperado é YA*********BR.');
            submitBtn.prop('disabled', false).text('Inserir');
            return;
        }

        // Consulta a API antes de submeter
        consultarApiAR(ar)
            .done(function(data) {
                console.log('Resposta completa da API:', data);
                const item = (data?.list?.[0] || data?.identidade?.[0]);
                console.log('Item extraído da resposta:', item);
                
                if (item) {
                    console.log('Item encontrado - verificando campos:');
                    console.log('item.inscricao:', item.inscricao);
                    console.log('item.cro:', item.cro);
                    console.log('item.cpf:', item.cpf);
                }
                
                if (preencherCamposComApi(item)) {
                    console.log('API encontrou os dados. Submetendo formulário...');
                    console.log('Dados finais antes do submit:');
                    console.log('AR:', $('#ar').val());
                    console.log('Inscrição:', $('#inscricao').val());
                    console.log('CRO/UF:', $('#cro_uf').val());
                    console.log('CPF:', $('#cpf').val());
                    console.log('Data:', $('#data_despacho').val());
                    console.log('Consta API:', $('#consta_api').val());
                    
                    // Adiciona um campo oculto para simular o clique no botão de submit,
                    // garantindo que $_POST['submit_ar'] seja enviado.
                    if ($(form).find('input[name="submit_ar"]').length === 0) {
                        $(form).append('<input type="hidden" name="submit_ar" value="1" />');
                        console.log('Campo submit_ar adicionado ao formulário');
                    }

                    console.log('Enviando formulário...');
                    form.submit(); // Envia o formulário
                } else {
                    console.log('AR não encontrado na API ou dados insuficientes. Abrindo modal para inserção manual.');
                    console.log('Dados do item:', item);
                    submitBtn.prop('disabled', false).text('Inserir');
                    mostrarUrlNaTela(urlConsulta);
                    setTimeout(function() { abrirModalComAR(ar); }, 1200);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Falha ao consultar a API:', {xhr, status, error});
                submitBtn.prop('disabled', false).text('Inserir');
                mostrarUrlNaTela(urlConsulta);
                setTimeout(function() { abrirModalComAR(ar); }, 1200);
            });
    });

    // Aplica máscara de CPF nos campos de CPF
    $('#cpf, #modal_cpf').on('input', function() {
        $(this).val(formatarCPF($(this).val()));
    });
    
    // --- Lógica do Modal ---
    const modal = document.getElementById('modalInserirManual');

    window.abrirModal = function() {
        $('#formModalInserir')[0].reset();
        $('#modal_ar_info').hide();
        $('#modal_ar').prop('readonly', false);
        modal.style.display = 'block';
        $('#modal_inscricao').focus();
    };

    window.abrirModalComAR = function(ar) {
        abrirModal();
        $('#modal_ar').val(ar).prop('readonly', true);
        $('#modal_ar_info_text').text('AR não encontrado na API. Insira os dados manualmente.');
        $('#modal_ar_info').show();
        $('#consta_api_modal').val('0');
    };

    window.fecharModal = function() {
        modal.style.display = 'none';
    };

    // Função para limpar o formulário principal
    window.limparFormulario = function() {
        $('#ar').val('').focus();
        $('#inscricao').val('');
        $('#cpf').val('');
        $('#cro_uf').val('');
        $('#data_despacho').val('');
        $('#consta_api').val('0');
        
        // Reseta o formulário de teste para os valores padrão
        $('#test_ar').val('YA123456789BR');
        $('#test_inscricao').val('12345');
        $('#test_cro_uf').val('SP');
        $('#test_data_despacho').val(new Date().toISOString().split('T')[0]);
        $('#test_cpf').val('123.456.789-00');
        
        console.log('Formulário limpo manualmente');
        
        // Remove mensagens de erro/sucesso
        $('.consulta-20-alert').fadeOut();
    };

    // Fecha o modal se clicar fora dele
    window.onclick = function(event) {
        if (event.target == modal) {
            fecharModal();
        }
    };
    
    $('.consulta-20-close').on('click', fecharModal);
    $('.consulta-20-btn-cancel').on('click', fecharModal);

    // Atalhos de teclado
    $(document).on('keydown', function(e) {
        // Ctrl+L para limpar formulário
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            limparFormulario();
        }
        // Enter no campo AR para submeter
        if (e.key === 'Enter' && $('#ar').is(':focus')) {
            $('#form-ar').submit();
        }
    });

    // Foca no campo AR quando a página carrega
    $('#ar').focus();

    // Proteção contra reenvio do formulário (F5)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // Adiciona listener para prevenir reenvio com F5
    window.addEventListener('beforeunload', function(e) {
        // Se há dados no formulário, mostra aviso
        if ($('#ar').val() && !$('#ar').val().trim() === '') {
            e.preventDefault();
            e.returnValue = 'Você tem dados não salvos. Tem certeza que deseja sair?';
        }
    });

    // Handlers para os botões de copiar/fechar URL
    $('#btn-copiar-url').off('click').on('click', function() {
        const urlText = $('#url-api-text').text();
        navigator.clipboard.writeText(urlText);
        $(this).text('Copiado!').css('background', '#cddc39');
        setTimeout(() => {
            $(this).text('Copiar URL').css('background', '#ffd600');
        }, 1500);
        $('#url-api-info').hide();
    });
    
    $('#btn-fechar-url').off('click').on('click', function() {
        $('#url-api-info').hide();
    });

    // Antes de abrir o modal de inserção manual, exibir a URL na tela
    function mostrarUrlNaTela(urlConsulta) {
        $('#url-api-text').text(urlConsulta);
        $('#url-api-info').show();
    }

});
</script>

<div class="consulta-20-container">
    <h1>Envio de Identidades ao CRO</h1>

   
    <?php if (!empty($success_message)): ?>
        <div class="consulta-20-alert consulta-20-alert-success">
            <?= htmlspecialchars($success_message) ?>
            <?php if (!empty($_SESSION['ultima_url_api'])): ?>
                <div style="margin-top:10px; font-size:0.95em; color:#0d47a1; word-break:break-all;">
                    <strong>URL consultada:</strong><br>
                    <span><?= htmlspecialchars($_SESSION['ultima_url_api']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="consulta-20-alert consulta-20-alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px; text-align: right;">
        <button onclick="abrirModal()" class="consulta-20-btn consulta-20-btn-success">
            ➕ Inserir Manualmente
        </button>
    </div>

    <div style="max-width: 500px; margin: 0 auto 2rem auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
        <h3>Inserir por AR</h3>
        <?php if (!empty($success_message)): ?>
        <div style="background: #e8f5e8; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #28a745;">
            <strong>🎯 Pronto para inserir!</strong> Digite o próximo AR no campo abaixo.
        </div>
        <?php endif; ?>
        <form method="POST" id="form-ar" autocomplete="off">
            <div class="consulta-20-form-group">
                <label for="ar">AR:</label>
                <input type="text" id="ar" name="ar" class="consulta-20-form-control" maxlength="13" autofocus required placeholder="YA*********BR">
            </div>
            
            <input type="hidden" id="inscricao" name="inscricao">
            <input type="hidden" id="data_despacho" name="data_despacho">
            <input type="hidden" id="cro_uf" name="cro_uf">
            <input type="hidden" id="cpf" name="cpf">
            <input type="hidden" id="consta_api" name="consta_api" value="0">
            <input type="hidden" name="form_token" value="<?= $form_token ?>">
            
            <button type="submit" class="consulta-20-btn consulta-20-btn-primary" style="width: 100%;">Inserir</button>
        </form>
    </div>
    

    <h3>Últimos 10 Envios (Total de <?= number_format($total_geral, 0, ',', '.') ?> registros)</h3>
    <table class="consulta-20-table">
        <thead>
            <tr>
                <th>AR</th><th>CRO/UF</th><th>Inscrição</th><th>CPF</th>
                <th>Data Despacho</th><th>Registrado em</th><th>Consta API</th>
                <th>Usuário</th><th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ultimos_despachos as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['ar'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['cro_uf'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['inscricao'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['cpf'] ?? '') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['data_despacho']))) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['criado_em']))) ?></td>
                <td><span class="<?= $row['consta_api'] ? 'text-success' : 'text-danger' ?>"><?= $row['consta_api'] ? 'Sim' : 'Não' ?></span></td>
                <td><?= htmlspecialchars($row['usuario_adicionou'] ?? '') ?></td>
                <td>
                    <?php if (!empty($row['ar'])): ?>
                    <a href="/consulta-identidade?tipoConsulta=1&searchType=ar&searchValue=<?= urlencode($row['ar']) ?>" target="_blank">Pesquisar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalInserirManual" class="consulta-20-modal">
    <div class="consulta-20-modal-content">
        <div class="consulta-20-modal-header">
            <h2>Inserir Novo Despacho Manualmente</h2>
            <span class="consulta-20-close">&times;</span>
        </div>
        <form id="formModalInserir" method="POST" autocomplete="off">
            <div class="consulta-20-modal-body">
                <div id="modal_ar_info" class="consulta-20-alert" style="display:none; margin-bottom: 15px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px;">
                    <strong>⚠️ Atenção:</strong> <span id="modal_ar_info_text"></span>
                </div>
                
                <div class="consulta-20-form-group"><label for="modal_ar">AR (opcional):</label><input type="text" id="modal_ar" name="ar" class="consulta-20-form-control" maxlength="13" placeholder="YA*********BR"></div>
                <div class="consulta-20-form-group"><label for="modal_inscricao">Inscrição:</label><input type="text" id="modal_inscricao" name="inscricao" class="consulta-20-form-control" required></div>
                <div class="consulta-20-form-group"><label for="modal_data_despacho">Data do Despacho:</label><input type="date" id="modal_data_despacho" name="data_despacho" class="consulta-20-form-control" required></div>
                <div class="consulta-20-form-group"><label for="modal_cro_uf">CRO (UF):</label><select id="modal_cro_uf" name="cro_uf" class="consulta-20-form-control" required><option value="">Selecione...</option><?php foreach ($ufs as $uf): ?><option value="<?= $uf ?>"><?= $uf ?></option><?php endforeach; ?></select></div>
                <div class="consulta-20-form-group"><label for="modal_cpf">CPF (opcional):</label><input type="text" id="modal_cpf" name="cpf" class="consulta-20-form-control" maxlength="14" placeholder="000.000.000-00"></div>
                <input type="hidden" id="consta_api_modal" name="consta_api" value="0">
                <input type="hidden" name="form_token" value="<?= $form_token ?>">
            </div>
            <div class="consulta-20-modal-footer">
                <button type="button" class="consulta-20-btn-cancel">Cancelar</button>
                <button type="submit" name="submit_manual" class="consulta-20-btn-insert">Inserir</button>
            </div>
        </form>
    </div>
</div>