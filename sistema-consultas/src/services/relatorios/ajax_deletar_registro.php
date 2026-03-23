<?php
// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Função para log de erro
function logError($message) {
    $logFile = __DIR__ . '/debug_ajax.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$timestamp - DELETAR ERRO: $message\n", FILE_APPEND | LOCK_EX);
}

// Função para log normal
function logInfo($message) {
    $logFile = __DIR__ . '/debug_ajax.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$timestamp - DELETAR: $message\n", FILE_APPEND | LOCK_EX);
}

// Função para retornar JSON e sair
function retornarJSON($data) {
    // Limpar qualquer buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Garantir que é array
    if (!is_array($data)) {
        $data = ['error' => 'Dados inválidos para JSON'];
    }
    
    // Retornar JSON com encoding UTF-8
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

try {
    logInfo("Iniciando endpoint de deletar");
    
    // Verificar se é POST ou DELETE
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
        logError("Método não permitido: " . $_SERVER['REQUEST_METHOD']);
        retornarJSON([
            'success' => false,
            'message' => 'Método não permitido. Use POST ou DELETE.'
        ]);
    }

    logInfo("Método permitido: " . $_SERVER['REQUEST_METHOD']);

    // Carregar configuração
    $configPath = __DIR__ . '/../../config/config.php';
    if (!file_exists($configPath)) {
        $configPath = dirname(__DIR__, 2) . '/config/config.php';
        if (!file_exists($configPath)) {
            logError("Config não encontrado");
            retornarJSON([
                'success' => false,
                'message' => 'Arquivo de configuração não encontrado'
            ]);
        }
    }

    logInfo("Carregando config: $configPath");
    require_once $configPath;
    logInfo("Config carregado com sucesso");

    // Verificar se as classes existem
    if (!class_exists('Cfo\SisConsultas\database\Database1')) {
        logError("Classe Database1 não encontrada");
        retornarJSON([
            'success' => false,
            'message' => 'Classe Database1 não encontrada'
        ]);
    }

    logInfo("Classes carregadas com sucesso");

    // Obter dados (pode vir via JSON ou form data)
    $uf = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = file_get_contents('php://input');
        logInfo("Input JSON recebido: " . strlen($input) . " bytes");
        
        if (!empty($input)) {
            $dados = json_decode($input, true);
            if ($dados && isset($dados['uf'])) {
                $uf = $dados['uf'];
            }
        }
    }
    
    $inputPost = $_POST;
    $inputGet = $_GET;

    if (!$uf && isset($inputPost['uf'])) {
        $uf = $inputPost['uf'];
        logInfo("UF recebida via POST: $uf");
    }
    
    if (!$uf && isset($inputGet['uf'])) {
        $uf = $inputGet['uf'];
        logInfo("UF recebida via GET: $uf");
    }

    if (empty($uf)) {
        logError("UF não informada");
        retornarJSON([
            'success' => false,
            'message' => 'UF é obrigatória para deletar'
        ]);
    }

    $uf = strtoupper(trim($uf));
    
    // Validar formato da UF
    if (!preg_match('/^[A-Z]{2}$/', $uf)) {
        logError("UF inválida: $uf");
        retornarJSON([
            'success' => false,
            'message' => 'UF deve ter exatamente 2 letras'
        ]);
    }

    logInfo("UF validada para deletar: $uf");

    // Conectar ao banco de dados
    $db = \Cfo\SisConsultas\database\Database1::getInstance();
    $con = $db->getConnection();
    
    if (!$con) {
        logError("Falha na conexão com banco");
        retornarJSON([
            'success' => false,
            'message' => 'Falha na conexão com banco de dados'
        ]);
    }

    logInfo("Conexão com banco estabelecida");

    // Verificar se o estado existe antes de deletar
    $verificarStmt = $con->prepare("SELECT COUNT(*) FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE uf = :uf");
    $verificarStmt->bindValue(':uf', $uf);
    $verificarStmt->execute();
    $existe = $verificarStmt->fetchColumn();

    if ($existe == 0) {
        logError("Estado não existe: $uf");
        retornarJSON([
            'success' => false,
            'message' => "Estado $uf não existe na base de dados"
        ]);
    }

    logInfo("Estado $uf existe, pode deletar");

    // Executar deleção
    $deleteStmt = $con->prepare("DELETE FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE uf = :uf");
    $deleteStmt->bindValue(':uf', $uf);
    
    $resultado = $deleteStmt->execute();

    if (!$resultado) {
        $errorInfo = $deleteStmt->errorInfo();
        logError("Erro na execução do DELETE: " . json_encode($errorInfo));
        retornarJSON([
            'success' => false,
            'message' => 'Erro ao executar a deleção: ' . $errorInfo[2]
        ]);
    }

    $linhasDeletadas = $deleteStmt->rowCount();
    logInfo("Deleção bem-sucedida: $linhasDeletadas linha(s) deletada(s)");

    if ($linhasDeletadas == 0) {
        logError("Nenhuma linha deletada para UF: $uf");
        retornarJSON([
            'success' => false,
            'message' => "Nenhum registro foi deletado para o estado $uf"
        ]);
    }

    // Retornar sucesso
    retornarJSON([
        'success' => true,
        'message' => "Estado $uf deletado com sucesso",
        'uf' => $uf,
        'action' => 'delete',
        'linhas_deletadas' => $linhasDeletadas
    ]);

} catch (Exception $e) {
    $errorMsg = "ERRO EXCEPTION: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine();
    logError($errorMsg);
    
    retornarJSON([
        'success' => false,
        'message' => 'Erro ao processar a requisição.'
    ]);
} catch (Error $e) {
    $errorMsg = "ERRO FATAL: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine();
    logError($errorMsg);
    
    retornarJSON([
        'success' => false,
        'message' => 'Erro interno do servidor.'
    ]);
}
?> 