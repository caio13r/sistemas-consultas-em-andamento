<?php

namespace Cfo\SisConsultas\services\relatorios\controler;

use Cfo\SisConsultas\services\relatorios\classes\{Cro,sheet};

class CroControler {

    public $arr_cro = [];

    /*
            Verifica se um Cro já existe antes de criar.
        Args:
            string $cro = nome do estado do Cro
    */    
    public function existCro(string $cro):void{
        if( !array_key_exists($cro, $this->arr_cro) ){
            $this->createCro($cro);
        }
    }
    /*
            Cria um novo Cro
        Args:
            string $cro = nome do estado do novo Cro
    */
    public function createCro(string $cro):void{
        $this->arr_cro[$cro] = new CRO($cro);
    }
    /*
            Recebe uma array que representa uma linha das 3 querys validas
        Args:
            array $current_row = array que representa uma linha do array original
            string $data_type  = qual das 3 querys tá sendo consumida
    */
    public function biuldData(array $current_row, string $data_type):void{
        if($current_row == null){
            return;
        }

        // Regex para pegar numero do convenio
        $pattern = "/[0-9]/";
        preg_match($pattern, $current_row[1], $convenio);
        
        if(sizeof($convenio) == 0){
            $convenio[0] = sizeof($this->arr_cro[$current_row[0]]->arr_convenios)+1;
        }
        
        $this->existCro($current_row[0]);
        $this->arr_cro[$current_row[0]]->existConvenio($current_row[2], $convenio[0]);

        if( $data_type == 'registro' ){
            $this->arr_cro[$current_row[0]]->setRegistro($convenio[0] ,$current_row[3], $current_row[4]);
        }
        else if( $data_type == 'liquid' ){
            $this->arr_cro[$current_row[0]]->setLiquid($convenio[0] ,$current_row[3], $current_row[4]);
        }
        else{
            $this->arr_cro[$current_row[0]]->setBaixas($convenio[0] ,$current_row[3], $current_row[4]);
        }
    }
    /*
            Retorna o objeto CroControle em forma de array
        Returns:
            array $result = CroControle representado em array
    */
    public function getData():array{
        $result = [];

        foreach($this->arr_cro as $cro){
            $result = array_merge($result, $cro->getCro());
        }

        return $result;
    }
    /*
        Recebe uma matriz contendo os dados das 3 querys e retorna o arquivo rel.xlsx com os dados
        Args:
            (array) $data   == contém os dados do relatorio
            (array) $config == contém configurações da tabela, como titulo e sub-titulos das colunas
        Returns:
            rel.xlsx
    */
    public static function biuldRel(array $data, array $config):void{
        extract($config);                               // recebe os arrays 'title', 'table_head', 'table_foot', 'table_footer', 'footer_data'
        $table_colums = $table_head;
        $table_colums[1] = '__Convenio__';              // aumenta o tamanho da coluna para o campo convenio
        $table = new sheet($table_colums);              // parametro usado para definir a largura das colunas

        # Parte de preenchimento dos dados
        // constroi e define estilos para o titulo em A1 e depois constroi o cabeçalho das colunas
        $table->buildTitle($title, 14);
        $table->setStyleArray('A1','con3_style');
        $table->buildTableHead($table_head);

        $sum_horizontal = ['E','G','I'];                // array que contém as celulas que serão somadas horizontalmente
        $footer_size = (sizeof($table_foot))-1;         // quantidade de campos do rodape da tabela
        $current_row = $table->getTableSize('A',1);     // as linhas 1 e 2 serão usadas para o titulo e o cabeçalho da tabela
        $cro_size = 3;                                  // define quantas linhas vai ter um CRO antes de inserir a totalização
        $uf_shift = 0;                                  // usado para identificar estados com mais de 03 convenios
        $convenio3_interval = [];                       // salva o intervalo em que o convenio 3 aparece
        $footer_forms = [];                             // usado para criar a totalização nacional
        foreach($data as $idx=>$row_data){
            // lógica para tratar convenios com tamanhos irregulares
            if( isset($data[$idx+1]) && $data[$idx+1][1] !== null && $data[$idx+1][1] != 'Convênio 1' && $data[$idx+1][1]  != 'Convênio 2' && $data[$idx+1][1]  != 'Convênio 3' ){
                $table->buildRowData($row_data, $current_row);
                $table->buildHorizontalSum($sum_horizontal, $current_row,'J');
                
                $current_row++;
                $uf_shift++;
            }else{
                // insere os dados na tabela e monta a soma horizontal para o valor total das tarifas  
                $table->buildRowData($row_data, $current_row);
                $table->buildHorizontalSum($sum_horizontal, $current_row,'J');        

                $current_row++;
                $cro_size--;
                
                if($cro_size <= 0){ // logica para a cada 3 linhas criar uma totalizacao geral do CRO
                    // coloca a tag 'Totalização Geral do CRO' em baixo dos estados, deixa as colunas restantes vazias e da merge de A a C
                    $table->buildTableFoot($table_foot, $current_row); 
                    $table->setCellsMerge('A', 'C', $current_row);
    
                    // preenche as colunas vazias com as somas dos convenios para cada estado, aplica um array de estilo
                    for($i = 0; $i <= $footer_size; $i++){
                        $table->buildVerticalSum(chr(68+$i),($current_row-3)-$uf_shift,$current_row); 
                    }

                    // cria as formula para a totalização geral do CRO
                    for($i=0; $i<=$footer_size; $i++){
                        if( !isset($footer_forms[$i]) ){
                            $footer_forms[$i] = '='.chr(68+$i).$current_row;
                            continue;
                        }
                        $footer_forms[$i] .= '+'.chr(68+$i).$current_row;
                    }
                    
                    $convenio3_interval[] = ($current_row-1)-$uf_shift;
                    $uf_shift = 0;
                    $cro_size=3;
                    $current_row++;
                }
            }
        }
        
        // define a formatação para as colunas D a J
        for($i=0; $i<$footer_size+1; $i++){
            if($i % 2 == 0 && $footer_size != $i){
                // padrão para os totais, representa um número inteiro
                $table->setCellsFormatation(chr(68+$i),'#,###');
                continue;
            }
            // padrão para os valores, representa um valor em real
            $table->setCellsFormatation(chr(68+$i));
        }
        $current_row++; // pula uma linha

        # Parte de criacao e estilizacao do rodape
        // cria o head do rodape a partir dos dados passados por parametro e cria as celulas vazias do rodape
        $table->buildTableFoot($table_footer, $current_row, 'body_style');
        $table->setCellsMerge('A', 'C', $current_row, 1);
        $table_footer = ['','','','','','','',];
        $table->buildTableFoot($table_footer, $current_row+1, 'body_style');

        // coloca os valores gerados nas celulas vazias do rodape e define o tipo de formatação
        for($i=0; $i<=$footer_size; $i++){
            $table->buildCell($footer_forms[$i], chr(68+$i), ($current_row+1));
            if($i % 2 == 0 && $i != $footer_size){
                $table->setCellsFormatation(chr(68+$i), '#,###', ($current_row+1));
                continue;
            }
            $table->setCellsFormatation(chr(68+$i), '_-R$ * #,##0.00_-;-R$ * #.##0,00_-;_-R$ * "-"??_-;_-@_-', ($current_row+1));
        }
        $current_row+=3;

        // salva a tabela em .xlsx
        $table->saveTable(); 
    }
}