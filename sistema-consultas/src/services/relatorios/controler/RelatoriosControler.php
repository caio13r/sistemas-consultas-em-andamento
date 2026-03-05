<?php
namespace Cfo\SisConsultas\services\relatorios\controler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\CachedObjectStorageFactory;
use Cfo\SisConsultas\services\relatorios\classes\sheet;

class RelatoriosControler {
  /**
     * Gera e envia o relatório Excel para download.
     *
     * @param array $data   Os dados combinados a serem inseridos na planilha.
     * @param array $config Configurações do relatório (ex.: título, cabeçalhos).
     *
     * @return void
     */
    public static function generateExcelReport(array $data, array $config): void {
        // Aumenta o limite de memória para este script
        ini_set('memory_limit', '4G');

        // Configura o caching para reduzir o consumo de memória
        $cacheMethod = CachedObjectStorageFactory::cache_to_discISAM;
        $cacheSettings = ['dir' => '/tmp'];
        Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        // Extrai as configurações (ex.: $title e $columns)
        extract($config);

        // Cria uma nova instância de Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Preenche o cabeçalho (linha 1) com os títulos das colunas
        $columnIndex = 'A';
        foreach ($columns as $header) {
            $sheet->setCellValue($columnIndex . '1', $header);
            $columnIndex++;
        }

        // Preenche os dados a partir da linha 2
        $rowNumber = 2;
        foreach ($data as $row) {
            $columnIndex = 'A';
            foreach ($row as $cellValue) {
                $sheet->setCellValue($columnIndex . $rowNumber, $cellValue);
                $columnIndex++;
            }
            $rowNumber++;
        }

        // Define o nome do arquivo Excel
        $fileName = $title . '.xlsx';

        // Configura os headers para download do arquivo Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        header('Cache-Control: max-age=0');

        // Cria o writer e envia o arquivo para a saída
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    /**
     * Gera um relatório simples em formato XLSX com base nos dados e configurações fornecidas.
     *
     * @param array $data   Dados do relatório.
     * @param array $config Configurações da tabela (ex.: título, cabeçalhos, etc).
     *                        Espera-se que o array $config contenha, por exemplo:
     *                        - 'title'      => Título do relatório,
     *                        - 'table_head' => Cabeçalhos das colunas.
     *
     * @return void
     * 
     */
    
    public static function biuldRel(array $data, array $config): void {
        // Extrai as variáveis do array $config (por exemplo, $title e $table_head)
        extract($config);
        
        // Cria uma nova planilha com os cabeçalhos, definindo 6 colunas e o subtítulo "APURAÇÃO"
        $table = new sheet($table_head, 6, "APURAÇÃO");

        // Constrói o título e o cabeçalho da tabela
        $table->buildTitle($title);
        $table->buildTableHead($table_head);

        // Corrige os dados: neste exemplo, o código processa a matriz $data para agrupar ou ajustar
        // os registros de acordo com um contador e insere linhas adicionais se necessário.
        $data_result = [];
        $counter = 1;
        for ($i = 0; $i < sizeof($data); $i++) {
            if ($data[$i][1] == "Convênio $counter") {
                $data_result[] = $data[$i];
            } elseif ($data[$i][1] == "Não Identificado") {
                $counter--;
            } else {
                // Se não estiver no formato esperado, insere uma linha "falsa" com dados padrão
                $uf = $counter > 1 ? $data[$i - 1][0] : $data[$i + 1][0];
                $data_result[] = [$uf, "Convênio $counter", '-', '-', '-', '-'];
                $i--;
            }
            $counter++;
            if ($counter > 3) {
                $counter = 1;
            }
        }

        // Preenche a planilha com os dados processados, iniciando a partir da linha 3
        foreach ($data_result as $row => $row_data) {
            $table->buildRowData($row_data, $row + 3);
        }
        
        // Constroi as totalizações e define estilos:
        $table->buildVerticalSum('D', 2);
        $table->setCellsFormatation('D', '#,###');
        $table->buildVerticalSum('E', 2);
        $table->setCellsFormatation('E');
        $table->buildVerticalSum('F', 2);
        $table->setCellsFormatation('F');
        $table->setCellsBold('A');
        $table->setCellsBold('2');
        $table->setStyleArray('A1:F1', 'default_style');
        $table->setStyleArray('A1', 'font_high');
        $table_size = $table->getTableSize('A');
        $table->setCellsMerge('A', 'C', $table_size);
        $table->buildCell('Total', 'A', $table_size);
        $table->setStyleArray("A{$table_size}:F{$table_size}", 'default_style');
        $table->setCellsBold('1');
        $table->setCellsBold('last_column');
        
        // Salva a planilha (gerando o arquivo XLSX)
        $table->saveTable();
    }

    /**
     * Gera um relatório consolidado em formato XLSX com base nos dados e configurações fornecidas.
     *
     * @param array $data   Dados do relatório.
     * @param array $config Configurações da tabela (ex.: título, cabeçalhos, etc).
     *                        Espera-se que o array $config contenha, por exemplo:
     *                        - 'title'      => Título do relatório,
     *                        - 'table_foot' => Cabeçalhos (ou rodapé) das colunas.
     *
     * @return void
     */
    public static function biuldRelA(array $data, array $config): void {
        extract($config);
        
        // Cria a planilha com base nos cabeçalhos (table_foot), definindo 3 colunas e o subtítulo "CONSOLIDAÇÃO"
        $table = new sheet($table_foot, 3, 'CONSOLIDAÇÃO');

        // Constrói o título e o cabeçalho da planilha
        $table->buildTitle($title);
        $table->buildTableHead($table_foot);

        // Preenche a planilha com os dados
        foreach ($data as $row => $row_data) {
            // Substitui valores 0 por '-' para melhorar a apresentação
            foreach ($row_data as $idx => $cell) {
                if ($cell == 0) {
                    $row_data[$idx] = '-';
                }
            }
            $table->buildRowData($row_data, $row + 3);
        }

        // Define formatações e estilos
        $table->setCellsFormatation('C', '#,###');
        $table->setCellsFormatation('D', '#,###');
        $table->setCellsFormatation('G', '#,###');
        $table->setCellsFormatation('H', '#,###');
        $table->setCellsBold('A');
        $table->setCellsBold('2');
        $table->setCellsBold('last_column');
        
        // Aplicar bordas em todas as linhas de dados
        $table_size = $table->getTableSize('A');
        for ($row = 3; $row < $table_size; $row++) {
            $table->setStyleArray("A{$row}:H{$row}", 'default_style');
        }
        
        $table->setCellColor('A1', 'EBF1DE');
        $table_size = $table->getTableSize('A') - 1;
        $table->setCellColor("A{$table_size}:H{$table_size}", 'EBF1DE');
        $table->setStyleArray('A1:H1', 'default_style');
        $table->setStyleArray('A1', 'font_high');
        $table->setCellsBold('1');
        
        // Salva a planilha
        $table->saveTable();
    }

    /**
     * Gera relatório consolidado (Adimplência com valores) com 10 colunas, aplicando
     * formatação numérica e monetária apropriadas.
     */
    public static function biuldRelAValores(array $data, array $config): void {
        extract($config);

        // Headers em $table_foot devem conter 10 colunas
        $table = new sheet($table_foot, 3, 'CONSOLIDAÇÃO');

        $table->buildTitle($title);
        $table->buildTableHead($table_foot);

        // Preenche os dados substituindo 0 por '-'
        foreach ($data as $row => $row_data) {
            foreach ($row_data as $idx => $cell) {
                if ($cell == 0) {
                    $row_data[$idx] = '-';
                }
            }
            $table->buildRowData($row_data, $row + 3);
        }

        // Formatação: contagens em C, D, G, I; moeda em H, J; percentuais já vêm com '%'
        $table->setCellsFormatation('C', '#,###');
        $table->setCellsFormatation('D', '#,###');
        $table->setCellsFormatation('G', '#,###');
        $table->setCellsFormatation('I', '#,###');
        // H e J usam o padrão monetário do método (R$)
        $table->setCellsFormatation('H');
        $table->setCellsFormatation('J');

        $table->setCellsBold('A');
        $table->setCellsBold('2');
        $table->setCellsBold('last_column');

        // Estilos por linha e cabeçalho cobrindo até a coluna J
        $table_size = $table->getTableSize('A');
        for ($row = 3; $row < $table_size; $row++) {
            $table->setStyleArray("A{$row}:J{$row}", 'default_style');
        }

        $table->setCellColor('A1', 'EBF1DE');
        $table_size = $table->getTableSize('A') - 1;
        $table->setCellColor("A{$table_size}:J{$table_size}", 'EBF1DE');
        $table->setStyleArray('A1:J1', 'default_style');
        $table->setStyleArray('A1', 'font_high');
        $table->setCellsBold('1');

        $table->saveTable();
    }

    public static function buildRelGeneric(array $data) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        if (!empty($data)) {
            // Define os cabeçalhos a partir das chaves do primeiro registro
            $headers = array_keys($data[0]);
            $column = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($column . '1', $header);
                $column++;
            }
    
            // Preenche a planilha com os dados
            $rowNumber = 2;
            foreach ($data as $rowData) {
                $column = 'A';
                foreach ($headers as $header) {
                    $value = ($rowData[$header] == 0) ? '-' : $rowData[$header];
                    $sheet->setCellValue($column . $rowNumber, $value);
                    $column++;
                }
                $rowNumber++;
            }
        } else {
            // Caso não haja dados, exibe uma mensagem
            $sheet->setCellValue('A1', 'No data available');
        }
    
        // Retorna o writer para que possa ser salvo e enviado
        return new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    }
}
