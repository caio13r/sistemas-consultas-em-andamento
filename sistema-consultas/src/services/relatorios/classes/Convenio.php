<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

class Convenio {
    
    private $codigo;
    private $num_convenio;

    private $total_registro;
    private $valor_registro;

    private $total_liquid;
    private $valor_liquid;

    private $total_baixas;
    private $valor_baixas;

    public function __construct(string $num_convenio){
        $this->num_convenio = $num_convenio;

        $this->total_registro = '';
        $this->valor_registro = '';
        $this->total_liquid = '';
        $this->valor_liquid = '';
        $this->total_baixas = '';
        $this->valor_baixas = '';
    }

    public function setCodigo(string $codigo){
        $this->codigo = $codigo;
    }

    public function getCodigo():string{
        if( isset($this->codigo) ){
            return $this->codigo;
        }else{
            return '';
        }
    }

    public function setRegistro($total, $valor){
        $this->total_registro = $total;
        $this->valor_registro = $valor;
    }

    public function setLiquid($total, $valor){
        $this->total_liquid = $total;
        $this->valor_liquid = $valor;
    }

    public function setBaixas($total, $valor){
        $this->total_baixas = $total;
        $this->valor_baixas = $valor;
    }

    public function getConvenio():array{
        return ['', $this->num_convenio, $this->codigo, $this->total_registro, $this->valor_registro, $this->total_liquid, $this->valor_liquid, $this->total_baixas, $this->valor_baixas];
    }
}