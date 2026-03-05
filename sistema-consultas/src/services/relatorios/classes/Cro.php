<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

use Cfo\SisConsultas\services\relatorios\classes\Convenio;
use LDAP\Result;

class Cro {

    public $arr_convenios = [];
    public $cro_name;
    
    public function __construct(string $cro_name){
        $this->cro_name = $cro_name;

    }

    public function existConvenio(string $code_convenio, string $num_convenio){
        
        if( !array_key_exists($num_convenio, $this->arr_convenios) ){
            $this->createConvenio($num_convenio);
        }
        
        if( $this->arr_convenios[$num_convenio]->getCodigo() == '' ){
            $this->arr_convenios[$num_convenio]->setCodigo($code_convenio);
        }
    }

    public function createConvenio( string $num_convenio){
        $this->arr_convenios[$num_convenio] = new Convenio($num_convenio);
    }

    public function setRegistro($num_convenio, $total, $valor){
        $this->arr_convenios[$num_convenio]->setRegistro($total,$valor);
    }

    public function setLiquid($num_convenio, $total, $valor){
        $this->arr_convenios[$num_convenio]->setLiquid($total,$valor);
    }   

    public function setBaixas($num_convenio, $total, $valor){
        $this->arr_convenios[$num_convenio]->setBaixas($total,$valor);
    }

    public function getCro():array{
        $result = [];
        $convenio_num = sizeof($this->arr_convenios);

        if($convenio_num < 3){
            $convenio_num = 3; 
        }

        for($i=1; $i<$convenio_num+1; $i++){
            if( isset($this->arr_convenios[$i]) ){
                $arr_aux = $this->arr_convenios[$i]->getConvenio();
                $arr_aux[0] =  $this->cro_name;
                $result[$arr_aux[1]] = $arr_aux;
                $result[$arr_aux[1]][1] = $result[$arr_aux[1]][1] <= 3 ? 'Convênio '.$result[$arr_aux[1]][1] : "Não Identificado";
            }else{
                $arr_aux = [$this->cro_name, strval($i),'','','','','','','']; // pode dar erro
                $result[$arr_aux[1]] = $arr_aux;
                $result[$arr_aux[1]][1] = 'Convênio '.$result[$arr_aux[1]][1];
            }
        }

        asort($result);        
        return $result;
    }

}   