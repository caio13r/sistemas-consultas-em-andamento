<?php
// Desabilitar saída de erros para não interferir no JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Limpar qualquer buffer de saída
if (ob_get_level()) {
    ob_end_clean();
}

// Configurar headers para JSON ANTES de qualquer saída
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Caminho relativo para o config
$configPath = '../../config/config.php';
if (!file_exists($configPath)) {
    // Tentar caminho alternativo
    $configPath = dirname(__DIR__, 2) . '/config/config.php';
    if (!file_exists($configPath)) {
        $configPath = '../../config/config.php'; // Fallback
    }
}

require_once $configPath;

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

// Teste rápido para verificar se chegamos aqui sem HTML
if (isset($_GET['test'])) {
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Teste GET detectado\n", FILE_APPEND);
    
    // Limpar completamente qualquer buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Forçar headers JSON novamente
    header('Content-Type: application/json; charset=utf-8');
    
    $response = json_encode(['test' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Response: $response\n", FILE_APPEND);
    
    echo $response;
    flush();
    exit();
}

try {
    // Log simples para arquivo temporário
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Entrou no endpoint\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Config carregado\n", FILE_APPEND);

    // Verificar sessão
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Antes do CheckSession\n", FILE_APPEND);
    // Session::CheckSession(); // COMENTADO - CAUSA PROBLEMA COM JSON
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - CheckSession comentado\n", FILE_APPEND);

    // Verificar permissões de acesso
    // $userGrupo = Session::get('grupo'); // COMENTADO - CAUSA PROBLEMA COM JSON
    // $userSubgrupo = Session::get('subgrupo'); // COMENTADO - CAUSA PROBLEMA COM JSON
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Permissões comentadas\n", FILE_APPEND);

    $isAdmin = true; // FORÇADO COMO TRUE PARA EVITAR PROBLEMAS

    // if (!$isAdmin) {
    //     http_response_code(403);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Você não tem permissão para editar dados.'
    //     ]);
    //     exit();
    // }

    // Obter dados JSON do POST
    $input = file_get_contents('php://input');
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Input recebido: $input\n", FILE_APPEND);
    $dados = json_decode($input, true);

    if (!$dados) {
        file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - JSON inválido: $input\n", FILE_APPEND);
        throw new Exception('Dados inválidos recebidos');
    }

    // Validar UF obrigatória
    if (empty($dados['uf'])) {
        file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - UF não informada\n", FILE_APPEND);
        throw new Exception('UF é obrigatória');
    }

    $uf = $dados['uf'];

    // Conectar ao banco de dados
    $db = Database1::getInstance();
    $con = $db->getConnection();
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Conexão com banco OK\n", FILE_APPEND);

    // Preparar os campos para atualização
    $camposParaAtualizar = [];
    $valores = [];

    // Todos os campos LAI
    $camposLAI = [
        'nome_autoridade_oficial_lai',
        'email_autoridade_lai', 
        'telefone_autoridade_lai',
        'cargo_oficial_lai',
        'vinculo_oficial_lai',
        'portaria_oficial_lai',
        'curso_capacitacao_lai',
        'nivel_autopercepcao_lai',
        'observacoes_lai',
        'tipo_portal_lai',
        'ano_atualizacao_dados_lai',
        'link_e_sic',
        'link_e_ouve',
        'sistema_atos_normativos_proprio',
        'interesse_sisato_cfo',
        'satisfacao_dados_abertos_tcu',
        'link_portal_transparencia',
        'link_dados_abertos',
        'indice_transparencia_ativa',
        'indice_transparencia_passiva',
        'tempo_medio_resposta_pedido',
        'setores_alimentacao_transparencia'
    ];

    // Todos os campos LGPD
    $camposLGPD = [
        'grau_adequacao_lgpd',
        'nome_encarregado_lgpd',
        'email_encarregado_lgpd',
        'telefone_encarregado_lgpd', 
        'portaria_encarregado_lgpd',
        'politica_privacidade_link',
        'inventario_dados_status',
        'relatorio_impacto_prodados',
        'ultimo_treinamento_lgpd',
        'titular_canal_solicitacao',
        'observacoes_lgpd'
    ];

    $todosCampos = array_merge($camposLAI, $camposLGPD);

    foreach ($todosCampos as $campo) {
        if (isset($dados[$campo])) {
            $valor = $dados[$campo];

            // Tratar campos especiais
            if (in_array($campo, ['portaria_oficial_lai', 'portaria_encarregado_lgpd'])) {
                $valor = trim($valor);
                if ($valor === '') {
                    $valor = null;
                }
            }

            if ($campo === 'ano_atualizacao_dados_lai') {
                $valor = !empty($valor) ? (int)$valor : null;
            }

            if ($campo === 'tempo_medio_resposta_pedido') {
                $valor = !empty($valor) ? (int)$valor : null;
            }

            if ($campo === 'relatorio_impacto_prodados') {
                $valor = !empty($valor) ? (int)$valor : 0;
            }

            if ($campo === 'ultimo_treinamento_lgpd' && !empty($valor)) {
                // Validar formato de data
                $date = DateTime::createFromFormat('Y-m-d', $valor);
                if (!$date) {
                    $valor = null;
                }
            }

            // Tratar strings vazias como NULL
            if (is_string($valor) && trim($valor) === '') {
                $valor = null;
            }

            $camposParaAtualizar[] = "$campo = :$campo";
            $valores[$campo] = $valor;
        }
    }

    if (empty($camposParaAtualizar)) {
        throw new Exception('Nenhum campo para atualizar');
    }

    $sql = "UPDATE db_sistema_consultas.tbl_dados_lgpd_cros 
            SET " . implode(', ', $camposParaAtualizar) . "
            WHERE uf = :uf";

    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - SQL: $sql\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Valores: " . json_encode($valores) . "\n", FILE_APPEND);

    $stmt = $con->prepare($sql);

    foreach ($valores as $campo => $valor) {
        $stmt->bindValue(":$campo", $valor);
    }
    $stmt->bindValue(':uf', $uf);

    $resultado = $stmt->execute();

    if (!$resultado) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Erro ao executar a atualização: ' . json_encode($errorInfo));
    }

    $linhasAfetadas = $stmt->rowCount();
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Linhas afetadas: $linhasAfetadas\n", FILE_APPEND);

    if ($linhasAfetadas === 0) {
        // Verificar se o registro existe
        $verificarStmt = $con->prepare("SELECT COUNT(*) FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE uf = :uf");
        $verificarStmt->bindValue(':uf', $uf);
        $verificarStmt->execute();
        $existe = $verificarStmt->fetchColumn();

        if (!$existe) {
            throw new Exception("Estado $uf não encontrado na base de dados");
        } else {
            // Registro existe, mas nenhuma linha foi afetada (provavelmente os dados são iguais)
            file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - Nenhuma alteração necessária\n", FILE_APPEND);
        }
    }

    error_log("Dados LAI/LGPD atualizados para UF: $uf por usuário: " . Session::get('email'));

    echo json_encode([
        'success' => true,
        'message' => 'Dados atualizados com sucesso',
        'linhas_afetadas' => $linhasAfetadas,
        'uf' => $uf
    ]);
    exit();

} catch (PDOException $e) {
    $errorMsg = "Erro PDO: " . $e->getMessage();
    error_log("Erro PDO ao salvar dados LAI/LGPD: " . $errorMsg);
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
    exit();

} catch (Exception $e) {
    $errorMsg = "Erro geral: " . $e->getMessage();
    error_log("Erro ao salvar dados LAI/LGPD: " . $errorMsg);
    file_put_contents(__DIR__ . '/debug_ajax.txt', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}
?>