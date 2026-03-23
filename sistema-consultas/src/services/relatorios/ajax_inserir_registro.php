<?php
// Habilitar error reporting para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar headers para JSON ANTES de qualquer saída
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Função para log de erro
function logError($message) {
    $logFile = __DIR__ . '/debug_ajax.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$timestamp - INSERÇÃO ERRO: $message\n", FILE_APPEND | LOCK_EX);
}

// Função para log normal
function logInfo($message) {
    $logFile = __DIR__ . '/debug_ajax.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$timestamp - INSERÇÃO: $message\n", FILE_APPEND | LOCK_EX);
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

// Interceptar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError("ERRO FATAL: " . print_r($error, true));
        
        // Limpar buffer e retornar JSON de erro
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno do servidor.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
});

try {
    logInfo("Iniciando endpoint de inserção");
    
    // Verificar se é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logError("Método não permitido: " . $_SERVER['REQUEST_METHOD']);
        retornarJSON([
            'success' => false,
            'message' => 'Método não permitido. Use POST.'
        ]);
    }

    logInfo("Método POST confirmado");

    // Carregar configuração
    $configPath = __DIR__ . '/../../config/config.php';
    if (!file_exists($configPath)) {
        $configPath = dirname(__DIR__, 2) . '/config/config.php';
        if (!file_exists($configPath)) {
            logError("Config não encontrado em nenhum local");
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

    // Obter dados JSON do POST
    $input = file_get_contents('php://input');
    logInfo("Input recebido: " . strlen($input) . " bytes");
    
    if (empty($input)) {
        logError("Input vazio");
        retornarJSON([
            'success' => false,
            'message' => 'Nenhum dado recebido'
        ]);
    }

    $dados = json_decode($input, true);
    if (!$dados) {
        logError("JSON inválido: " . json_last_error_msg());
        retornarJSON([
            'success' => false,
            'message' => 'Dados JSON inválidos: ' . json_last_error_msg()
        ]);
    }

    logInfo("JSON decodificado com sucesso");

    // Validar UF obrigatória
    if (empty($dados['uf'])) {
        logError("UF não informada");
        retornarJSON([
            'success' => false,
            'message' => 'UF é obrigatória'
        ]);
    }

    $uf = strtoupper(trim($dados['uf']));
    
    // Validar formato da UF
    if (!preg_match('/^[A-Z]{2}$/', $uf)) {
        logError("UF inválida: $uf");
        retornarJSON([
            'success' => false,
            'message' => 'UF deve ter exatamente 2 letras'
        ]);
    }

    logInfo("UF validada: $uf");

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

    // Verificar se o estado já existe
    $verificarStmt = $con->prepare("SELECT COUNT(*) FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE uf = :uf");
    $verificarStmt->bindValue(':uf', $uf);
    $verificarStmt->execute();
    $existe = $verificarStmt->fetchColumn();

    if ($existe > 0) {
        logError("Estado já existe: $uf");
        retornarJSON([
            'success' => false,
            'message' => "Estado $uf já existe na base de dados"
        ]);
    }

    logInfo("Estado $uf não existe, pode inserir");

    // Preparar campos para inserção
    $campos = ['uf'];
    $placeholders = [':uf'];
    $valores = ['uf' => $uf];

    // Lista de todos os campos possíveis
    $camposPossiveis = [
        'nome_autoridade_oficial_lai', 'email_autoridade_lai', 'telefone_autoridade_lai',
        'cargo_oficial_lai', 'vinculo_oficial_lai', 'portaria_oficial_lai',
        'curso_capacitacao_lai', 'nivel_autopercepcao_lai', 'observacoes_lai',
        'tipo_portal_lai', 'ano_atualizacao_dados_lai', 'link_e_sic', 'link_e_ouve',
        'sistema_atos_normativos_proprio', 'interesse_sisato_cfo', 'satisfacao_dados_abertos_tcu',
        'link_portal_transparencia', 'link_dados_abertos', 'indice_transparencia_ativa',
        'indice_transparencia_passiva', 'tempo_medio_resposta_pedido', 'setores_alimentacao_transparencia',
        'grau_adequacao_lgpd', 'nome_encarregado_lgpd', 'email_encarregado_lgpd',
        'telefone_encarregado_lgpd', 'portaria_encarregado_lgpd', 'politica_privacidade_link',
        'inventario_dados_status', 'relatorio_impacto_prodados', 'ultimo_treinamento_lgpd',
        'titular_canal_solicitacao', 'observacoes_lgpd'
    ];

    // Processar campos recebidos
    foreach ($camposPossiveis as $campo) {
        if (isset($dados[$campo])) {
            $valor = $dados[$campo];

            // Tratar campos especiais
            if (in_array($campo, ['portaria_oficial_lai', 'portaria_encarregado_lgpd'])) {
                $valor = trim($valor);
                if ($valor === '') $valor = null;
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
                $date = DateTime::createFromFormat('Y-m-d', $valor);
                if (!$date) $valor = null;
            }

            // Tratar strings vazias como NULL
            if (is_string($valor) && trim($valor) === '') {
                $valor = null;
            }

            $campos[] = $campo;
            $placeholders[] = ":$campo";
            $valores[$campo] = $valor;
        }
    }

    // Montar SQL
    $sql = "INSERT INTO db_sistema_consultas.tbl_dados_lgpd_cros (" . 
           implode(', ', $campos) . ") VALUES (" . 
           implode(', ', $placeholders) . ")";

    logInfo("SQL preparado: $sql");
    logInfo("Valores: " . json_encode($valores));

    // Executar inserção
    $stmt = $con->prepare($sql);
    
    foreach ($valores as $campo => $valor) {
        $stmt->bindValue(":$campo", $valor);
    }

    $resultado = $stmt->execute();

    if (!$resultado) {
        $errorInfo = $stmt->errorInfo();
        logError("Erro na execução: " . json_encode($errorInfo));
        retornarJSON([
            'success' => false,
            'message' => 'Erro ao executar a inserção: ' . $errorInfo[2]
        ]);
    }

    $linhasInseridas = $stmt->rowCount();
    logInfo("Inserção bem-sucedida: $linhasInseridas linha(s)");

    // Retornar sucesso
    retornarJSON([
        'success' => true,
        'message' => 'Estado criado com sucesso',
        'uf' => $uf,
        'action' => 'insert',
        'linhas_inseridas' => $linhasInseridas
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
