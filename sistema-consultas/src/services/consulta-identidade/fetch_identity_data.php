<?php
// fetch_identity_data.php

error_reporting(E_ALL); // Exibe todos os erros
ini_set('display_errors', 1); // Garante que os erros sejam exibidos para depuração

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$token = $_ENV['API_TOKEN'] ?? null;

$draw = $_POST['draw'] ?? 1;
$response_data = [
    "draw"            => intval($draw),
    "recordsTotal"    => 0,
    "recordsFiltered" => 0,
    "data"            => [],
    "error"           => ""
];

try {
    if (!$token) {
        throw new Exception("API Token not configured.");
    }

    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $search_value = $_POST['search']['value'] ?? ''; // Termo de busca do DataTables
    
    // Parâmetros do formulário, enviados via AJAX
    $uf = $_POST['uf'] ?? '';
    $start_date_form = $_POST['start_date'] ?? '';
    $end_date_form = $_POST['end_date'] ?? '';

    if (empty($uf) || empty($start_date_form) || empty($end_date_form)) {
        throw new Exception("Parâmetros de consulta (UF, Data Inicial, Data Final) ausentes.");
    }

    // Converter datas para o formato da API com horas fixas
    $start_date_api = date('d/m/Y 00:00:00', strtotime($start_date_form));
    $end_date_api = date('d/m/Y 23:59:59', strtotime($end_date_form));

    $page_number_api = floor($start / $length) + 1;
    $page_amount_api = $length;

    if ($page_amount_api > 1000) {
        $page_amount_api = 1000;
    }

    $endpoint = 'https://id.cfo.org.br/api/consulta/postagem/identidade';
    $apiUrl = "{$endpoint}?token=" . urlencode($token) .
        "&uf=" . urlencode($uf) .
        "&page_number=" . urlencode($page_number_api) .
        "&page_amount=" . urlencode($page_amount_api) .
        "&start_date=" . urlencode($start_date_api) .
        "&end_date=" . urlencode($end_date_api);
    
    // Log da URL para depuração
    // file_put_contents('debug_api_url.log', $apiUrl . "\n", FILE_APPEND);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("Erro CURL: " . curl_error($ch));
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Captura o código HTTP
    curl_close($ch);

    $api_data = json_decode($response, true);

    if ($http_code != 200) {
        throw new Exception("Erro na API (HTTP {$http_code}): " . ($api_data['message'] ?? $response));
    }

    if (isset($api_data['list']) && is_array($api_data['list'])) {
        $response_data["data"] = $api_data['list'];
        $response_data["recordsTotal"] = $api_data['amount_result_total'] ?? 0;
        $response_data["recordsFiltered"] = $api_data['amount_result_total'] ?? 0;
        
        // Se a API não filtra pelo search_value, você precisaria fazer o filtro aqui
        // Mas lembre-se: isso filtraria apenas os 1000 registros que você recebeu, não o total.
        if (!empty($search_value)) {
            $filtered_data = [];
            foreach ($response_data["data"] as $row) {
                // Adapte os campos pelos quais você quer buscar
                if (stripos($row['nome'], $search_value) !== false ||
                    stripos($row['cpf'], $search_value) !== false ||
                    stripos($row['id_professional'], $search_value) !== false) {
                    $filtered_data[] = $row;
                }
            }
            $response_data["data"] = $filtered_data;
            // ATENÇÃO: recordsFiltered DEVE refletir o número total de registros *após a aplicação dos filtros GLOBALES*.
            // Se o filtro acima é apenas nos 1000 resultados, recordsFiltered não pode ser o count($filtered_data)
            // se recordsTotal é maior. Isso pode levar a inconsistências no DataTables.
            // A solução ideal é que a API forneça 'recordsFiltered' quando um termo de busca é enviado.
            // Por enquanto, mantenha recordsFiltered = recordsTotal se a API não filtra globalmente.
            // Se você está filtrando apenas os 1000 resultados, o DataTables fará a busca no cliente se você não passar search_value.
            // Para server-side search, a API PRECISA ter um parâmetro de busca.
        }

    } else {
        throw new Exception("Formato de resposta da API inválido ou lista de dados ausente.");
    }

} catch (Exception $e) {
    $response_data["error"] = $e->getMessage();
    // Você pode logar o erro para depuração
    // file_put_contents('fetch_error.log', $e->getMessage() . "\n", FILE_APPEND);
}

header('Content-Type: application/json');
echo json_encode($response_data);
exit;

?>