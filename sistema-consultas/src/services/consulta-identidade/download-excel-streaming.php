<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

// Inicializar sessão sem verificar login
Session::init();

// Configurações otimizadas para grandes volumes
ini_set('memory_limit', '16384M'); // 16GB
ini_set('max_execution_time', 0); // Sem limite de tempo
ini_set('max_input_time', 600); // 10 minutos
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');

try {
    // Conexão com o banco
    $con = Database1::getInstance()->getConnection();
    
    // Buscar todos os dados da tabela
    $query = "SELECT * FROM carteirinhas_despachadas_cro ORDER BY id DESC";
    $stmt = $con->prepare($query);
    $stmt->execute();
    
    // Configurar headers para download CSV
    $filename = 'despachos_cro_' . date('d_m_Y_H_i_s') . '.csv';
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
        'AR',
        'CRO_UF', 
        'Inscricao',
        'CPF',
        'Data_Despacho',
        'Registrado_em',
        'Consta_API',
        'Usuario'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Processar dados em chunks de 1000 registros
    $chunk_size = 1000;
    $processed = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $csv_row = [
            $row['ar'] ?? '',
            $row['cro_uf'] ?? '',
            $row['inscricao'] ?? '',
            $row['cpf'] ?? '',
            $row['data_despacho'] ?? '',
            $row['criado_em'] ?? '',
            $row['consta_api'] ? 'Sim' : 'Não',
            $row['usuario_adicionou'] ?? ''
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