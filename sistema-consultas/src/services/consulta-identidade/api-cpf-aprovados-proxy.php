<?php
/**
 * Proxy para API de CPFs Aprovados
 * Resolve problemas de CORS fazendo as requisições via servidor
 */

// Capturar erros e não exibir
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers para JSON (antes de qualquer output)
header('Content-Type: application/json; charset=utf-8');

// Autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// Carregar .env se existir
if (file_exists(__DIR__ . '/../../.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    } catch (Exception $e) {
        // Continuar mesmo com erro no .env
    }
}

use Cfo\SisConsultas\lib\Session;

// Inicializar sessão
Session::init();

// NOTA: Verificação de sessão desabilitada temporariamente para debug
// TODO: Reativar após resolver problema de sessão
// if (Session::get('login') !== true) {
//     http_response_code(401);
//     echo json_encode(['erro' => 'Usuário não autenticado. Faça login novamente.']);
//     exit;
// }

// Token e URL da API
$API_TOKEN = $_ENV['API_TOKEN_IDENTITY'] ?? $_ENV['API_TOKEN'] ?? 'dc523cd42ccbaedd580390611cd176e1';
$API_BASE_URL = $_ENV['API_IDENTITY_URL'] ?? 'http://192.168.161.165:8082';

// Obter ação
$action = $_GET['action'] ?? '';

// Função para fazer requisições cURL
function apiRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Accept: */*'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'SistemaConsultas/1.0'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: */*',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    // Log detalhado em caso de erro
    if ($error || $httpCode >= 400) {
        error_log("cURL Error: $error | HTTP Code: $httpCode | URL: " . preg_replace('/token=[^&]+/', 'token=***', $url));
        if (!empty($response)) {
            error_log("Response: " . substr($response, 0, 500));
        }
    }
    
    if ($error) {
        return ['error' => true, 'message' => "Erro de conexão: $error", 'code' => 500];
    }
    
    if (empty($response)) {
        return ['error' => true, 'message' => 'Resposta vazia do servidor (HTTP ' . $httpCode . ')', 'code' => $httpCode ?: 500];
    }
    
    $decoded = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => true, 'message' => 'Resposta inválida: ' . substr($response, 0, 200), 'code' => $httpCode ?: 500];
    }
    
    if ($httpCode >= 400) {
        $errorMsg = $decoded['erro'] ?? $decoded['message'] ?? $decoded['error'] ?? 'Erro na requisição';
        // Adicionar código HTTP na mensagem para facilitar debug
        $errorMsg .= " (HTTP $httpCode)";
        return ['error' => true, 'message' => $errorMsg, 'code' => $httpCode];
    }
    
    return $decoded;
}

// Construir URL base com token
function buildUrl($baseUrl, $endpoint, $token, $params = []) {
    $url = rtrim($baseUrl, '/') . $endpoint;
    $params['token'] = $token;
    return $url . '?' . http_build_query($params);
}

try {
    switch ($action) {
        case 'stats':
            $days = $_GET['days'] ?? 10;
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/stats', $API_TOKEN, ['days' => $days]);
            $result = apiRequest($url);
            break;
            
        case 'disponiveis':
            $params = [];
            if (!empty($_GET['days'])) $params['days'] = $_GET['days'];
            if (!empty($_GET['uf'])) $params['uf'] = $_GET['uf'];
            if (!empty($_GET['limit'])) $params['limit'] = $_GET['limit'];
            if (!empty($_GET['page'])) $params['page'] = $_GET['page'];
            
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/disponiveis', $API_TOKEN, $params);
            $result = apiRequest($url);
            break;
            
        case 'detalhes':
            $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
            if (empty($cpf)) {
                throw new Exception('CPF não informado');
            }
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/' . $cpf, $API_TOKEN);
            $result = apiRequest($url);
            break;
            
        case 'inserir-lote':
            $input = json_decode(file_get_contents('php://input'), true);
            $cpfs = $input['cpfs'] ?? [];
            
            if (empty($cpfs)) {
                throw new Exception('Nenhum CPF informado');
            }
            
            if (count($cpfs) > 100) {
                throw new Exception('Máximo de 100 CPFs por requisição');
            }
            
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/inserir-lote', $API_TOKEN);
            $result = apiRequest($url, 'POST', ['cpfs' => $cpfs]);
            break;
            
        case 'autorizar-uf':
            $input = json_decode(file_get_contents('php://input'), true);
            $uf = $input['uf'] ?? '';
            $days = $input['days'] ?? 10;
            $batchSize = $input['batch_size'] ?? 50;
            
            if (empty($uf)) {
                throw new Exception('UF não informada');
            }
            
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/autorizar-uf', $API_TOKEN);
            $result = apiRequest($url, 'POST', [
                'uf' => $uf,
                'days' => $days,
                'batch_size' => $batchSize
            ]);
            break;
            
        case 'sync-logs':
            $params = [];
            if (!empty($_GET['days'])) $params['days'] = $_GET['days'];
            if (!empty($_GET['uf'])) $params['uf'] = $_GET['uf'];
            if (!empty($_GET['limit'])) $params['limit'] = $_GET['limit'];
            
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados/sync-logs', $API_TOKEN, $params);
            $result = apiRequest($url);
            break;
            
        case 'listar':
            $params = [];
            if (!empty($_GET['days'])) $params['days'] = $_GET['days'];
            if (!empty($_GET['uf'])) $params['uf'] = $_GET['uf'];
            if (!empty($_GET['limit'])) $params['limit'] = $_GET['limit'];
            if (!empty($_GET['page'])) $params['page'] = $_GET['page'];
            
            $url = buildUrl($API_BASE_URL, '/api/cpf-aprovados', $API_TOKEN, $params);
            $result = apiRequest($url);
            break;
            
        case 'stats-por-data':
            // GET /api/identity/ready/stats-por-data
            $params = [];
            if (!empty($_GET['data_inicio'])) $params['data_inicio'] = $_GET['data_inicio'];
            if (!empty($_GET['data_fim'])) $params['data_fim'] = $_GET['data_fim'];
            if (!empty($_GET['cro'])) $params['cro'] = $_GET['cro'];
            if (!empty($_GET['agrupar_por'])) $params['agrupar_por'] = $_GET['agrupar_por'];
            // A API espera boolean como 1 ou 0, não "true" ou "false"
            if (isset($_GET['apenas_pendentes'])) {
                $params['apenas_pendentes'] = ($_GET['apenas_pendentes'] === 'true' || $_GET['apenas_pendentes'] === '1') ? 1 : 0;
            }
            
            if (empty($params['data_inicio'])) {
                throw new Exception('data_inicio é obrigatório');
            }
            
            $url = buildUrl($API_BASE_URL, '/api/identity/ready/stats-por-data', $API_TOKEN, $params);
            $result = apiRequest($url);
            break;
            
        case 'processar-por-data':
            // POST /api/identity/ready/processar-por-data
            $input = json_decode(file_get_contents('php://input'), true);
            $dataInicio = $input['data_inicio'] ?? '';
            $dataFim = $input['data_fim'] ?? $dataInicio;
            $cro = $input['cro'] ?? null;
            $limite = $input['limite'] ?? 1000;
            
            if (empty($dataInicio)) {
                throw new Exception('data_inicio é obrigatório');
            }
            
            if ($limite > 5000) {
                throw new Exception('Limite máximo é 5000');
            }
            
            $postData = [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'limite' => $limite
            ];
            
            if ($cro) {
                $postData['cro'] = $cro;
            }
            
            $url = buildUrl($API_BASE_URL, '/api/identity/ready/processar-por-data', $API_TOKEN);
            $result = apiRequest($url, 'POST', $postData);
            break;
            
        case 'ars-disponiveis':
            // Consultar quantidade de ARs disponíveis no banco de dados local
            try {
                $db6 = \Cfo\SisConsultas\database\Database6::getInstance();
                $con6 = $db6->getConnection();
                
                $query = "SELECT COUNT(*) as total_ars_disponiveis FROM label WHERE used = 0 OR used = false OR used IS NULL";
                $stmt = $con6->prepare($query);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $result = [
                    'total_ars_disponiveis' => (int)($row['total_ars_disponiveis'] ?? 0),
                    'atualizado_em' => date('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                error_log("Erro ao consultar ARs disponíveis: " . $e->getMessage());
                throw new Exception('Erro ao consultar ARs disponíveis: ' . $e->getMessage());
            }
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
    if (isset($result['error']) && $result['error']) {
        $httpCode = $result['code'] ?? 500;
        http_response_code($httpCode);
        
        $errorMsg = $result['message'];
        
        // Adicionar mais detalhes dependendo do código de erro
        if ($httpCode === 401) {
            $errorMsg .= '. Verifique se o token da API está correto.';
            error_log("Erro 401 - Token: " . substr($API_TOKEN, 0, 10) . "... | URL Base: $API_BASE_URL");
        } elseif ($httpCode === 500) {
            $errorMsg .= '. Erro interno na API externa - contate o administrador do servidor.';
            error_log("Erro 500 - Action: $action | Mensagem: " . $result['message']);
        }
        
        echo json_encode(['erro' => $errorMsg]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
    error_log("Exception no proxy: " . $e->getMessage());
}
