<?php
ob_start(); // Inicia o buffer de saída no topo

require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\services\relatorios\classes\QueryHelper; // Certifique-se que esta classe exista se for usada
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment; // Adicionado para estilos
use PhpOffice\PhpSpreadsheet\Style\Font;      // Adicionado para estilos
use PhpOffice\PhpSpreadsheet\Style\Border;    // Adicionado para estilos
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Adicionado para formatação numérica

// Função para log no console (útil para depurar no navegador)
function console_log($message) {
    echo "<script>console.log('" . addslashes($message) . "');</script>";
}

// Log inicial para verificar se o script está sendo executado
console_log(">>> INÍCIO DO SCRIPT relatorio-processos-especialidade-gerar.php - " . date('Y-m-d H:i:s'));

Session::init(); // Inicializa a sessão, se necessário
Session::CheckSession(); // Verifica a sessão, se necessário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    console_log(">>> MÉTODO POST DETECTADO");
    console_log(">>> POST DATA: " . print_r($_POST, true)); // Útil para ver o que está sendo enviado

    // Validação dos parâmetros necessários
    if (!isset($_POST['data_inicial']) || !isset($_POST['data_final'])) {
        console_log(">>> ERRO: Datas não fornecidas para geração do Excel.");
        echo "<script>
                  alert('É necessário preencher todas as datas para gerar o relatório.');
                  window.location.href = 'relatorio-processos-especialidade'; // Redireciona de volta
              </script>";
        exit;
    }

    $date_start = $_POST['data_inicial'];
    $date_end = $_POST['data_final'];
    $estado = $_POST['estado'] ?? 'TODOS'; // Pega o estado do POST
    $etapa = $_POST['etapa'] ?? 'TODOS';   // Pega a etapa do POST

    $date_title = date('d/m/Y', strtotime($date_start)) . ' a ' . date('d/m/Y', strtotime($date_end));

    console_log(">>> Data inicial: " . $date_start);
    console_log(">>> Data final: " . $date_end);
    console_log(">>> Estado: " . $estado);
    console_log(">>> Etapa: " . $etapa);

    try {
        // Obtém a conexão com o banco de dados
        console_log(">>> Tentando obter conexão com o banco de dados");
        $db = Database3::getInstance();
        $con = $db->getConnection();
        console_log(">>> Conexão com o banco estabelecida com sucesso");

        // Verifica se a view existe (boa prática)
        console_log(">>> Verificando existência da view");
        $check_view = "SELECT OBJECT_ID('CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao') as view_exists";
        $stmt = $con->prepare($check_view);
        $stmt->execute();
        $view_check = $stmt->fetch(PDO::FETCH_NUM);
        
        if (!$view_check || $view_check[0] === null) {
            console_log(">>> ERRO: View não encontrada");
            throw new Exception("A view vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao não existe no banco de dados");
        }
        console_log(">>> View encontrada com sucesso");
        
        // Query principal para buscar os dados (com filtros de estado e etapa)
        $query = "SELECT
                      [CRO],
                      [NumeroProcesso],
                      [Nome],
                      [Classificacao],
                      [EtapaProcesso],
                      [Andamento],
                      CONVERT(VARCHAR, DataAndamento, 103) AS DataAndamento, -- Formata para dd/mm/yyyy
                      [DiasDesdeAndamento]
                  FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao
                  WHERE DataAndamento >= CONVERT(datetime, :dataInicio, 120)
                  AND DataAndamento < DATEADD(DAY, 1, CONVERT(datetime, :dataFim, 120))";
        
        // Adiciona filtro de estado se não for TODOS
        if ($estado != 'TODOS') {
            $query .= " AND CRO = :estado";
        }
        // Adiciona filtro de etapa se não for TODOS
        if ($etapa != 'TODOS') {
            $query .= " AND EtapaProcesso = :etapa";
        }

        $query .= " ORDER BY [CRO], [DataAndamento] DESC";

        console_log(">>> Executando query principal para o Excel");
        
        $stmt = $con->prepare($query);
        $stmt->bindValue(':dataInicio', $date_start);
        $stmt->bindValue(':dataFim', $date_end);
        if ($estado != 'TODOS') { $stmt->bindValue(':estado', $estado); }
        if ($etapa != 'TODOS') { $stmt->bindValue(':etapa', $etapa); }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        console_log(">>> Registros recuperados para Excel: " . count($results));

        if (empty($results)) {
            console_log(">>> ERRO: Nenhum registro encontrado para o período/filtros selecionados para o Excel.");
            throw new Exception("Nenhum registro encontrado para os critérios selecionados.");
        }

        console_log(">>> Iniciando criação da planilha Excel");
        // Prepara o título do relatório (usando os filtros reais)
        $titleText = "Relatorio_Processos---------_" . ($estado != 'TODOS' ? $estado . '_' : '') . ($etapa != 'TODOS' ? str_replace(' ', '_', $etapa) . '_' : '') . str_replace("/", "_", $date_title);

        // Define os cabeçalhos da planilha Excel
        $headers = ['CRO', 'Número do Processo', 'Nome', 'Classificação', 'Etapa do Processo', 'Andamento', 'Data do Andamento', 'Dias Desde Andamento'];

        // Cria a planilha
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Estilo para o título (linha 1)
        $fullTitleForExcel = 'Sistema Consultas CFO - Relatório de Processos de Especialidade e Habilitação - Período: ' . $date_title . ' - Emitido em: ' . date('d/m/Y H:i:s');
        $sheet->setCellValue('A1', $fullTitleForExcel);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        
        // Adiciona os cabeçalhos da tabela na linha 2
        $colLetter = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($colLetter . '2', $header);
            $sheet->getStyle($colLetter . '2')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFA0A0A0']], // Cinza claro
            ]);
            $colLetter++;
        }
        $highestColumnLetter = $sheet->getHighestColumn(); // Pega a última coluna dos cabeçalhos
        $sheet->mergeCells('A1:' . $highestColumnLetter . '1'); // Mescla o título com base nos cabeçalhos

        console_log(">>> Adicionando dados à planilha");
        // Adiciona os dados a partir da linha 3
        $rowNumber = 3; // Começa na linha 3 porque linha 1 é título e linha 2 são cabeçalhos
        foreach ($results as $row) {
            $sheet->setCellValue('A' . $rowNumber, $row['CRO'] ?? '');
            $sheet->setCellValue('B' . $rowNumber, $row['NumeroProcesso'] ?? '');
            $sheet->setCellValue('C' . $rowNumber, $row['Nome'] ?? '');
            $sheet->setCellValue('D' . $rowNumber, $row['Classificacao'] ?? '');
            $sheet->setCellValue('E' . $rowNumber, $row['EtapaProcesso'] ?? '');
            $sheet->setCellValue('F' . $rowNumber, $row['Andamento'] ?? '');
            $sheet->setCellValue('G' . $rowNumber, $row['DataAndamento'] ?? ''); // Já está formatado como string "dd/mm/yyyy" pela query SQL
            $sheet->setCellValue('H' . $rowNumber, $row['DiasDesdeAndamento'] ?? '');

            // Aplica formatação de texto para 'NumeroProcesso' (coluna B) para evitar problemas com números longos
            $sheet->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

            $rowNumber++;
        }

        // Aplica bordas a todas as células com dados e cabeçalhos
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $highestColumnLetter . ($rowNumber - 1))->applyFromArray($borderStyle);

        // Ajusta a largura das colunas automaticamente
        foreach (range('A', $highestColumnLetter) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        console_log(">>> Preparando para download do arquivo");
        // Limpa qualquer saída anterior ANTES de definir os cabeçalhos de download
        if (ob_get_length()) {
            console_log(">>> Limpando buffer de saída existente");
            ob_clean(); // Limpa o buffer
        }

        // Configura os headers para download
        console_log(">>> Configurando headers para download");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($titleText) . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public'); // Requerido para IE, pode ser útil

        console_log(">>> Iniciando download do arquivo");
        // Salva o arquivo e envia para o navegador
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        console_log(">>> Download concluído com sucesso");
        exit; // Termina a execução do script para evitar qualquer saída adicional

    } catch (Exception $e) {
        console_log(">>> ERRO CRÍTICO na geração do Excel: " . $e->getMessage());
        console_log(">>> Stack trace: " . $e->getTraceAsString());
        // Se houver um erro, é importante não tentar mais enviar cabeçalhos de arquivo.
        // Tenta limpar o buffer novamente e exibe um alerta.
        if (ob_get_length()) {
            ob_clean();
        }
        echo "<script>
                  alert('Erro ao gerar relatório: " . addslashes($e->getMessage()) . "');
                  window.location.href = 'relatorio-processos-especialidade';
              </script>";
        exit;
    }
} else {
    console_log(">>> Método não permitido para relatorio-processos-especialidade-gerar.php: " . $_SERVER['REQUEST_METHOD']);
    header("Location: relatorio-processos-especialidade"); // Redireciona se não for POST
    exit;
}