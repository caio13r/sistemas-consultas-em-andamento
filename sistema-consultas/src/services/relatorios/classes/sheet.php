<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

# classes publicas
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Cfo\SisConsultas\lib\Helper;

# classes pessoais
use Cfo\SisConsultas\services\relatorios\classes\sheet_config;

use function PHPSTORM_META\type;

// Implementa uma interface amigavel para a utilização da biblioteca spreadsheet
class sheet extends sheet_config {

    private $spreadsheet;
    private $sheet;
    public $column_width = [];
    
    public function __construct (array $head_data, int $width_const=2, $worksheet_title = 'Relatorio'){
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->getActiveSheet()->setTitle($worksheet_title);
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->defColumnWidth($head_data,$width_const);
        $this->defFont();
    }
    /*
            Define a largura das colunas com base no array do cabeçalho, primeira funçao a ser usada!
        Args:
            array $head_data    == Contém os nomes do cabeçalho
            int $width_const      == Constante que sempre é somada na largura da celula
            string $data_type     == Utilizado para não duplicar dados em arrays associativos e numericos
    */
    public function defColumnWidth(array $head_data, int $width_const = 2, string $data_type = 'integer'):void{
        if( sizeof($this->column_width) >= 1){
            $this->column_width = [];
        }
        foreach($head_data as $idx=>$name){
            if (gettype($idx) == $data_type){
                $this->column_width[] = strlen($name) + $width_const;
            }
        }
    }
    /*
        Define a fonte e o tamanho da fonte da planilha
    */
    public function defFont(string $font = "Calibri", int $size = 12):void{
        $this->spreadsheet->getDefaultStyle()
        ->getFont()
        ->setName($font)
        ->setSize($size);    
    }
    /*
            Muda a cor de uma celula
        Args:
            string $cell    == Cell que vai ter a cor trocada
            string $color   == Cor que será colocada
    */
    public function setCellColor(string $cell,string $color):void{
        $this->sheet->getStyle($cell)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()
        ->setARGB($color);
    }
    /*
            Mescla as celulas de um determinado intervalo
        Args:
            string $column_start    = Coluna de inicio da mesclagem
            string $column_end      = Coluna Final da mesclagem
            int $start_row                = Linha inicial da mesclagem
            int $end_row            = Define a linha final da mesclagem
    */
    public function setCellsMerge(string $column_start = 'A', string $column_end = '', int $start_row = 1, int $end_row = 0):void{
        if ($column_end == ''){
            $interval = $column_start.$start_row.":".chr(sizeof($this->column_width)+64).$start_row;
        }else{
            $interval = $column_start.$start_row.":".$column_end.($start_row+$end_row);
        }
        $this->sheet->mergeCells($interval);
    }
    /*
            Define o estilo de formatação para um intervalo de linhas em uma coluna, 
            $base_column é utilizado para planilhas irregulares ou com valores nulos
        Args:
            string $column          = Coluna que recebera a formatação
            string $regex           = Expressão regular da formatação
            int $start_row          = Linha a partir de onde sera apliacada a formatação
            string $base_column     = Coluna base para medir o tamanho da planilha, opção utilizada em planilhas que tem celulas sem valores
    */
    public function setCellsFormatation(string $column, string $regex = '_-R$ * #,##0.00_-;-R$ * #.##0,00_-;_-R$ * "-"??_-;_-@_-',int $start_row = 3, string $base_column = 'A'){
        
        $idx_end = $this->getTableSize($base_column,$start_row);
        $this->sheet->getStyle($column."{$start_row}:".$column.$idx_end)->getNumberFormat()->setFormatCode($regex);
    }
    /*
            Define a fonte da coluna ou linha passada em negritom, percorre todo o intervalo da celula passada
        Args:
            string $cell    = Inicio do intervalo em que será aplicado o estilo
            int $row_start        = linha inicial onde será aplicado o estilo
    */
    public function setCellsBold(string $cell, int $row_start = 2):void{
        if( filter_var($cell, FILTER_VALIDATE_INT) ){       // pega um intervalo nas linhas
            $this->sheet->getStyle('A'.$cell.':'.chr(sizeof($this->column_width)+64).$cell)
            ->getFont()
            ->setBold(true);
            return;
        }elseif( $cell == 'last_column' ){                  // define a ultima coluna da planilha em negrito
            $table_end = $this->getTableSize('A', $row_start);
            $this->setCellsBold($table_end-1, $row_start);
            return;
        }                                                   
        // define a fonte da coluna passada em negrito
        $this->sheet->getStyle($cell.$row_start.":".$cell.$this->getTableSize($cell, $row_start))
        ->getFont()
        ->setBold(true);
    }
    /*
            Aplica um estilo em um intervalo de celulas com base nos arrays de estilos em config/sheet_config.php
        Args:
            string $interval    = Intervalo de celulas que recebera o estilo do array
            string $style       = Array que especifica os estilos
    */
    public function setStyleArray(string $interval, string $style):void{
        $this->sheet->getStyle($interval)->applyFromArray($this->$style);
    }
    /*
            Retorna um inteiro que representa a quantidade de linhas da planilha
        Args:
            string $colum   == Coluna que sera utilizada como base
            int $row        == Valor que deve ser considerado a linha inicial
        Returns:
            int $row        ==  Tamanho atual da planilha 
    */      
    public function getTableSize(string $colum, int $row = 2):int{
        while ( $this->sheet->getCell($colum.$row)->getValue() !== NULL){
            $row++;
        }
        return $row;
    }
    /*
            Define o conteudo, o tamanho do titulo e da um merge nas colunas do titulo.
        Args:
            string $title   = Conteudo do titulo
            int $size       = Tamanho do titulo
            int $row        = Linha inicial
    */
    public function buildTitle(string $title = "Relatorio", int $size = 15, int $row = 1):void{
        // define conteudo do titulo
        $this->sheet->setCellValue(chr(65).$row, "{$title}");

        // junta as cells do titulo, gabe:[revisar isso aqui!!]
        $this->setCellsMerge();

        // define a fonte e o alinhamento do titulo
        $this->sheet->getstyle(chr(65).$row)->getFont()->setSize($size);
        $this->sheet->getstyle(chr(65).$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    /*
            Constroi o cabeçalho das colunas e define alguns estilos padrões para eles
        Args:
            array $head_data  = Conteudo dos identificadores de coluna
            int $row            = Linha em que os identificadores serão construidos 
            string $data_type   = Define se o conteudo vai estar nas chaves ou valores do array
    */
    public function buildTableHead(array $head_data, int $row = 2, string $data_type = 'value'):void{
        $column = 0;            // representa a coluna A
        
        // define a largura das colunas com base no array $colum_width
        for($i = 0; $i < sizeof($this->column_width); $i++){
            $this->sheet->getColumnDimension(chr($i+65))->setWidth($this->column_width[$i]);
        }
        
        // define configurações de fonte, bordas, alinhamento e preenchimento para os identificadores
        $this->sheet->getStyle('A'.$row.':'.chr(sizeof($this->column_width)+64).$row)->applyFromArray($this->head_style);

        // define o conteudo dos identificadores e filtra de onde vem os dados
        foreach($head_data as $idx=>$name){
            if( gettype($idx) == 'string' && $data_type == 'key' ){
                $this->sheet->setCellValue(chr($column+65).$row, "{$idx}");
                $column++;
            }elseif( gettype($name) == 'string' && $data_type == 'value' ){
                $this->sheet->setCellValue(chr($column+65).$row, "{$name}");
                $column++;
            }
        }   
    }
    /*
            Preenche uma linha da planilha com o conteudo do array $row_data e aplica um estilo padrão
        Args:
            array $row_data     = Contém os dados que serão colocados na planilha 
            int $row            = Linha da planilha em que os dados serão colocados
            string $array_type  = Identificado do tipo do array passado
    */
    public function buildRowData(array $row_data, int $row, string $array_type = 'array_numeric'){
        $data_type = ['array_numeric' => 1, 'array_both' => 2];             // para aceitar diferentes tipos de arrays
        
        for($i=0; $i < (sizeof($row_data))/$data_type[$array_type]; $i++){
            $this->sheet->setCellValue(chr($i+65).$row, $row_data[$i]);
        }

        // define um estilo padrão de fonte, bordas e alinhamento
        $this->sheet->getStyle('A'.$row.":".chr(sizeof($this->column_width)+64).$row)->applyFromArray($this->default_style);
    }
    /*
        Define o conteudo de uma celula
    */
    public function buildCell(string $data, string $column, int $row){
        $this->sheet->setCellValue($column.$row, $data);
    }
    /*
            Cria uma função vertical de N linhas em alguma coluna da planilha
        Args:
            string $column      = Coluna que recebera a função
            int $start_row      = linha inicial
            int $end_row        = linha final
            string $function    = qual será a função
    */
    public function buildVerticalSum(string $column, int $start_row, int $end_row = -1, string $function = '=SUM'):void{
        if($end_row == -1){         // aplica a função até a linha final da tabela
            $end_row = $this->getTableSize($column,$start_row);
            $function = $function."({$column}".$start_row.":".$column.($end_row-1).")";
        }else{                      // aplica a função até a linha final
            $function = $function."({$column}".$start_row.":".$column.($end_row-1).")";
        }
        $this->sheet->setCellValue($column.$end_row, $function);
    }
    /*
            Faz uma soma horizontal em uma linha, a partir do conteudo do array de colunas e salva na $end_column
        Args:
            int $row                = linha em que sera feito a soma
            array $column_array     = colunas que serão somadas
            string $end_column      = coluna que vai receber o resultado
    */
    public function buildHorizontalSum(array $column_array, int $row, string $end_column):void{
        $formula = '=';
        $empty_cells = 0;           // conta a quantiade de celulas vazias

        for($i = 0; $i < sizeof($column_array); $i++){
            if($this->sheet->getCell($column_array[$i].$row)->getValue() == NULL ){
                $empty_cells++;
            }else{
                if( $formula != '=' ){
                    $formula .= '+';
                }
                $formula .= $column_array[$i].$row;
            }
        }
        
        if($empty_cells == sizeof($column_array)){          // se todas as celulas estão vazias
            $this->sheet->setCellValue($end_column.$row, ' - ');
            return;
        }
        $this->sheet->setCellValue($end_column.$row, $formula);
    }
    /*
            Recebe uma matriz contendo os dados das 3 querys e retorna o arquivo rel.xlsx com os dados
        Args:
            array $foot_data  = Dados que serão colocados no rodape
            int $row            = Linha da tabela que o rodape sera colocado
            string $style       = Array com estilo que sera aplicado no rodape 
            String $type        = Define se o conteudo vai estar nas chaves ou valores do array
    */
    public function buildTableFoot(array $foot_data, int $row, string $style = 'head_style', String $type = 'value'):void{        
        // aplica o estilo que foi recebido
        $this->sheet->getStyle('A'.$row.':'.chr(sizeof($this->column_width)+64).$row)->applyFromArray($this->$style);

        // define o conteudo dos identificadores e filtra de onde vem os dados
        $column = 0;
        foreach($foot_data as $idx=>$name){
            if( gettype($idx) == 'string' && $type == 'key' ){
                $this->sheet->setCellValue(chr($column+65).$row, "{$idx}");
                $column++;
            }elseif( gettype($name) == 'string' && $type == 'value' ){
                $this->sheet->setCellValue(chr($column+65).$row, "{$name}");
                $column++;
            }
        }   
    }
    /*
        Salva a planilha na pasta /relatorios
    */
    public function saveTable(string $name_file = '/rel.xlsx'):void{
        $table_size = $this->sheet->getHighestRow()+2;
        $last_update = Helper::getLastUpdate();

        $this->setCellsMerge('A','F',$table_size);
        $this->setStyleArray("A{$table_size}:F{$table_size}",'default_style');
        $this->buildCell("Data da atualização do banco de dados: {$last_update[1]}",'A',$table_size);

        $savepath = realpath(dirname(__FILE__, 2)) . '/relatorios' . $name_file;
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($savepath);
    }
}

?>