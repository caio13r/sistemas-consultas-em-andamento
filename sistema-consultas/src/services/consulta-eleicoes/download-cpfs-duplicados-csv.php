<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;

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
    $con = Database3::getInstance()->getConnection();
    
    // Recuperar parâmetros da sessão ou GET
    $cro = $_GET['cro'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    $votante = $_GET['votante'] ?? '';
    $adimplencia = $_GET['adimplencia'] ?? '';
    
    // Construir condições da consulta
    $conditions = ["1=1"]; // Condição base
    
    if (!empty($cro)) {
        $conditions[] = "CRO LIKE '%{$cro}%'";
    }

    if (!empty($categoria)) {
        $conditions[] = "CATEGORIA LIKE '%{$categoria}%'";
    }

    if (!empty($votante)) {
        $conditions[] = "[ELEITOR] = '{$votante}'";
    }

    if (!empty($adimplencia)) {
        $conditions[] = "[ADIMPLÊNCIA] = '{$adimplencia}'";
    }

    // Query para encontrar CPFs duplicados
    $queryDuplicados = "SELECT CPF, COUNT(*) as total_registros
                        FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                        WHERE " . implode(' AND ', $conditions) . "
                        GROUP BY CPF 
                        HAVING COUNT(*) > 1
                        ORDER BY total_registros DESC, CPF";

    $stmt = $con->prepare($queryDuplicados);
    $stmt->execute();
    $cpfsDuplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cpfsDuplicados) > 0) {
        // Extrair apenas os CPFs para usar na segunda query
        $cpfsParaBuscar = array_column($cpfsDuplicados, 'CPF');
        $cpfsString = "'" . implode("','", $cpfsParaBuscar) . "'";
        
        // Query para buscar todos os registros dos CPFs duplicados
        $queryRegistros = "SELECT 
                            NOME_COMPLETO, CPF, CRO, [INSCRIÇÃO] AS INSCRICAO, 
                            [TIPO_INSCRIÇÃO] AS TIPO_INSCRICAO, [SITUAÇÃO] AS SITUACAO, [DETALHE_SITUAÇÃO] AS DETALHE_SITUACAO, 
                            [ADIMPLÊNCIA] AS ADIMPLENCIA, [ELEITOR] AS VOTANTE, [DEVEDOR] AS DEVEDOR, 
                            [MOTIVOS_NÃO_ELEITOR] AS MOTIVO_NAO_VOTANTE, EMAIL, [CELULAR_ATUALIZADO] AS CELULAR_ATUALIZADO,
                            [DATA_NASCIMENTO] AS DATA_NASCIMENTO, [DATA_INSCRIÇÃO_CRO] AS DATA_INSCRICAO_CRO, [DATA_REGISTRO_CFO] AS DATA_REGISTRO_CFO
                          FROM 
                            CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                          WHERE CPF IN ($cpfsString)
                          ORDER BY CPF, NOME_COMPLETO";
        
        $stmt2 = $con->prepare($queryRegistros);
        $stmt2->execute();
        
        // Configurar headers para download CSV
        $filename = 'cpfs_duplicados_eleicoes_' . date('d_m_Y_H_i_s') . '.csv';
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
            'CPF',
            'Nome Completo',
            'CRO',
            'Inscrição',
            'Tipo Inscrição',
            'Situação',
            'Detalhe Situação',
            'Adimplência',
            'Votante',
            'Devedor',
            'Motivo Não Votante',
            'Email',
            'Celular Atualizado',
            'Data Nascimento',
            'Data Inscrição CRO',
            'Data Registro CFO'
        ];
        
        fputcsv($output, $headers, ';');
        
        // Processar dados em chunks de 1000 registros
        $chunk_size = 1000;
        $processed = 0;
        
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $csv_row = [
                $row['CPF'] ?? '',
                $row['NOME_COMPLETO'] ?? '',
                $row['CRO'] ?? '',
                $row['INSCRICAO'] ?? '',
                $row['TIPO_INSCRICAO'] ?? '',
                $row['SITUACAO'] ?? '',
                $row['DETALHE_SITUACAO'] ?? '',
                $row['ADIMPLENCIA'] ?? '',
                $row['VOTANTE'] ?? '',
                $row['DEVEDOR'] ?? '',
                $row['MOTIVO_NAO_VOTANTE'] ?? '',
                $row['EMAIL'] ?? '',
                $row['CELULAR_ATUALIZADO'] ?? '',
                $row['DATA_NASCIMENTO'] ?? '',
                $row['DATA_INSCRICAO_CRO'] ?? '',
                $row['DATA_REGISTRO_CFO'] ?? ''
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
    } else {
        // Se não há CPFs duplicados, retorna arquivo vazio com cabeçalhos
        $filename = 'cpfs_duplicados_eleicoes_vazio_' . date('d_m_Y_H_i_s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Resultado', 'Nenhum CPF duplicado encontrado com os filtros aplicados'], ';');
        fclose($output);
    }
    
} catch (Exception $e) {
    // Em caso de erro, retornar erro 500
    http_response_code(500);
    echo "Erro no download: " . $e->getMessage();
}
?> 