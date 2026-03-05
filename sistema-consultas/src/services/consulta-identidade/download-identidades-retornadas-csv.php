<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;

// Inicializar sessão sem verificar login
Session::init();

// Configurações otimizadas para grandes volumes
ini_set('memory_limit', '16384M'); // 16GB
ini_set('max_execution_time', 0); // Sem limite de tempo
ini_set('max_input_time', 600); // 10 minutos
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

try {
    // Pegar parâmetros da URL
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $uf = $_GET['uf'] ?? 'BRASIL';
    
    $token = $_ENV['API_TOKEN'] ?? null;
    if (!$token) {
        throw new Exception("Token da API não configurado.");
    }
    
    // Ajustar as datas para incluir o início e o fim do dia
    $start_date_api = date('d/m/Y 00:00:00', strtotime($start_date));
    $end_date_api = date('d/m/Y 23:59:59', strtotime($end_date));
    
    // Construir URL da API
    $endpoint = 'http://192.168.161.165:8082/api/consulta/retornada';
    $page_amount = 100000; // Buscar muitos registros
    $page_number = 1;
    
    $apiUrl = sprintf('%s?token=%s&page_number=%s&page_amount=%s&start_date=%s&end_date=%s',
        $endpoint,
        urlencode($token),
        urlencode($page_number),
        urlencode($page_amount),
        str_replace(' ', '%20', $start_date_api),
        str_replace(' ', '%20', $end_date_api)
    );
    
    // Adicionar UF apenas se não for "BRASIL" (todos)
    if ($uf !== 'BRASIL') {
        $apiUrl .= '&uf=' . urlencode($uf);
    }
    
    // Fazer requisição para a API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos de timeout
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("Erro CURL: " . curl_error($ch));
    }
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (!isset($data['list']) || !is_array($data['list'])) {
        throw new Exception("Erro na resposta da API: " . ($data['message'] ?? 'Formato de resposta inválido'));
    }
    
    $list_results = $data['list'];
    $total_results = $data['amount_result_total'] ?? count($list_results);
    
    // Configurar headers para download CSV
    $uf_display = ($uf == 'BRASIL') ? 'BRASIL' : $uf;
    $filename = 'identidades_retornadas_' . $uf_display . '_' . date('d_m_Y', strtotime($start_date)) . '_a_' . date('d_m_Y', strtotime($end_date)) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Criar arquivo de saída
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM UTF-8 para Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos do CSV
    $headers = [
        'ID_Profissional',
        'CRO_UF', 
        'Categoria',
        'Inscricao',
        'Nome',
        'CPF',
        'Data_Evento'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Processar dados em chunks de 1000 registros
    $chunk_size = 1000;
    $processed = 0;
    
    foreach ($list_results as $item) {
        $csv_row = [
            $item['id_professional'] ?? '',
            $item['uf'] ?? '',
            $item['categoria'] ?? '',
            '="' . ($item['inscricao'] ?? '') . '"', // Inscrição com zeros à esquerda preservados
            $item['nome'] ?? '',
            $item['cpf'] ?? '',
            $item['data_evento'] ?? ''
        ];
        
        fputcsv($output, $csv_row, ';');
        
        $processed++;
        
        // Liberar memória a cada 1000 registros
        if ($processed % $chunk_size === 0) {
            flush();
            gc_collect_cycles();
        }
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // Em caso de erro, retornar erro 500
    http_response_code(500);
    echo "Erro no download: " . $e->getMessage();
}
?> 