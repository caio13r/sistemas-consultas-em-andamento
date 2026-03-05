<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;
use Cfo\SisConsultas\services\relatorios\classes\Connection;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\database\Database4;
use DateTime;

class CacheHelper extends CacheConfig{
    private $redis;

    function __construct(){
    }

    public function getInstance(){
        $this->redis = Database4::getInstance();
        $this->redis = $this->redis->getConnection();

        return $this->redis;
    }

    public static function connection(string $key = 'script_baixas', array $key_filters, array $filters){
        $str_key = implode('', $key_filters);
        $stmt = new CacheHelper();
        $new_key = $stmt->getKey($key, $str_key);

        if(sizeof($stmt->existKey($key.$str_key.":*")) == 0){
            $script = QueryHelper::buildQuery($key,$filters);
            $array_adimp = Connection::conn_Sqlsrv('script_liquidacao', '', $script);
            $stmt->setArray($new_key, $array_adimp);
        }else{
            $keys = $stmt->existKey($key.$str_key.":*");

            if($stmt->keyIsValid($keys[0]) == true){
                $array_adimp = $stmt->getArray($keys[0]);
            }else{
                $script = QueryHelper::buildQuery($key,$filters);
                $array_adimp = Connection::conn_Sqlsrv('script_liquidacao', '', $script);
                $stmt->setArray($new_key, $array_adimp);
                $redis = $stmt->getInstance();
                $redis->del($keys[0]);
                $redis->close();
            }
        }

        return $array_adimp;
    }

    public static function connectionView(array $data){
        $script = "SELECT
                    CRO, 
                    AVG(Ano),
                    SUM(Total_Anuidades),
                    SUM(Pago),
                    ROUND(
                        (CAST(SUM(Pago) AS FLOAT) / 
                        CASE 
                            WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1 
                            ELSE CAST(SUM(Total_Anuidades) AS FLOAT) 
                        END) * 100, 
                        2
                    ) AS Percentual_adimplencia,
                    100 - ROUND(
                        (CAST(SUM(Pago) AS FLOAT) / 
                        CASE 
                            WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1 
                            ELSE CAST(SUM(Total_Anuidades) AS FLOAT) 
                        END) * 100, 
                        2
                    ) AS Percentual_inadimplencia,
                    SUM(Nao_pago), 
                    SUM(Pago_a_menor)
                    from CFO_CWS.dbo.Cons_adimplencia ca";

        if ($data['categoria'] == 'Todos'){
            $data['categoria'] = '';
            $buildWehre = " WHERE Ano = :ano 
                            AND Categoria <> :categoria and
                            Categoria <> 'TOTAL' and
                            CRO <> 'BR' 
                            GROUP BY CRO
                            ORDER BY Percentual_adimplencia DESC";
        }else{
            $buildWehre = " WHERE Ano = :ano
                            AND Categoria = :categoria and
                            Categoria <> 'TOTAL' and
                            CRO <> 'BR'
                            GROUP BY CRO
                            ORDER BY Percentual_adimplencia DESC";
        }

        $array_adimp = Connection::connWithScript($script.$buildWehre, $data);
        for($i = 0; $i<sizeof($array_adimp); $i++){
            $array_adimp[$i][4] = number_format($array_adimp[$i][4], 2, '.', '');
            $array_adimp[$i][5] = number_format($array_adimp[$i][5], 2, '.', '');
        }
        
        return $array_adimp;
    }

    public static function connectionViewValores(array $data){
        $script = "SELECT
                    CRO,
                    AVG(Ano),
                    SUM(Total_Anuidades),
                    SUM(Pago),
                    ROUND(
                        (CAST(SUM(Pago) AS FLOAT) /
                        CASE
                            WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1
                            ELSE CAST(SUM(Total_Anuidades) AS FLOAT)
                        END) * 100,
                        2
                    ) AS Percentual_adimplencia,
                    100 - ROUND(
                        (CAST(SUM(Pago) AS FLOAT) /
                        CASE
                            WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1
                            ELSE CAST(SUM(Total_Anuidades) AS FLOAT)
                        END) * 100,
                        2
                    ) AS Percentual_inadimplencia,
                    SUM(Nao_pago),
                    SUM(Valor_Devido_Nao_pago),
                    SUM(Pago_a_menor),
                    SUM(Valor_Devido_Pago_a_menor)
                    FROM CFO_CWS.dbo.Cons_adimplencia_com_valores";

        if ($data['categoria'] == 'Todos'){
            $data['categoria'] = '';
            $buildWehre = " WHERE Ano = :ano
                            AND Categoria <> :categoria and
                            Categoria <> 'TOTAL' and
                            CRO <> 'BR'
                            GROUP BY CRO
                            ORDER BY Percentual_adimplencia DESC";
        }else{
            $buildWehre = " WHERE Ano = :ano
                            AND Categoria = :categoria and
                            Categoria <> 'TOTAL' and
                            CRO <> 'BR'
                            GROUP BY CRO
                            ORDER BY Percentual_adimplencia DESC";
        }

        $array_adimp = Connection::connWithScript($script.$buildWehre, $data);
        for($i = 0; $i<sizeof($array_adimp); $i++){
            $array_adimp[$i][4] = number_format($array_adimp[$i][4], 2, '.', '');
            $array_adimp[$i][5] = number_format($array_adimp[$i][5], 2, '.', '');
        }
        
        return $array_adimp;
    }

    public function setArray(string $key, array $data):void{
        $stmt = $this->getInstance();
        $stmt->set($key, json_encode($data));
        $stmt->close();
    }

    public function getArray(string $key):array{
        $stmt = $this->getInstance();
        $result = json_decode($stmt->get($key), true);
        $stmt->close();

        return $result;
    }

    public function getKey(string $key, string $key_filters){
        $current_time = time();
        $data_string = $this->config[$key] != null ? $this->config[$key].$key_filters.":".date('Y-m-d', $current_time) : 'false';

        return $data_string;
    }
    
    public function existKey(string $key){
        $stmt = $this->getInstance();
        $result = $stmt->keys($key);
        $stmt->close();

        return $result;
    }

    public function keyIsValid(string $key){
        $key_result = explode(":",$key);
        $key_date = new DateTime($key_result[1]);
        
        $last_update = Helper::getLastUpdate();
        $last_update = trim($last_update[1]);
        $db_date = date_create_from_format('d/m/Y', $last_update);

        return $key_date < $db_date ? false : true;
    }
    public static function connectionViewDelegado(array $filters): array {
        // Recupera o valor do CRO (UF) ou usa 'ALL' se não definido
        $uf = $filters['uf'] ?? 'ALL';
        
        // Query para buscar os dados de e-mail
        $sqlEmail = "
            SELECT 
                CpfCnpj, 
                profissional, 
                Email, 
                CRO, 
                CATEGORIA, 
                INSCRICAO
            FROM CFO_CWS.dbo.vw_Delegado_Eleitor_Email
            WHERE (:uf = 'ALL' OR CRO = :uf)
        ";

        // Query para buscar os dados de telefone
        $sqlPhone = "
            SELECT 
                CpfCnpj, 
                telefone
            FROM CFO_CWS.dbo.vw_Delegado_Eleitor_telefone
            WHERE (:uf = 'ALL' OR CRO = :uf)
        ";

        // Como o método Connection::conn_Sqlsrv() espera uma string como query_data, fazemos a substituição manual
        // Atenção: use addslashes ou outra função de sanitização para evitar problemas de injeção (aqui usamos addslashes como exemplo)
        $ufSafe = addslashes($uf);
        $finalSqlEmail = str_replace(':uf', "'" . $ufSafe . "'", $sqlEmail);
        $finalSqlPhone = str_replace(':uf', "'" . $ufSafe . "'", $sqlPhone);

        // Registra o caminho final das queries para debug
        error_log(">>> Query Email (debug): " . $finalSqlEmail);
        error_log(">>> Query Phone (debug): " . $finalSqlPhone);

        // Usa Database3 para obter a conexão
        $db = Database3::getInstance();
        $conn = $db->getConnection();
        error_log(">>> Conexão com o banco (Database3) obtida----------------------------------------------.");

        // Executa as queries usando o método conn_Sqlsrv da classe Connection
        // Aqui, 'email' e 'phone' são alias para identificar o script, mas o terceiro parâmetro é a query já montada.
        $dataEmail = Connection::conn_Sqlsrv('email', $finalSqlEmail);
        $dataPhone = Connection::conn_Sqlsrv('phone', $finalSqlPhone);

        error_log(">>> Dados de e-mail recuperados: " . count($dataEmail));
        error_log(">>> Dados de telefone recuperados: " . count($dataPhone));

        // Mescla os dados utilizando o método combineData() da classe QueryHelper
        $combinedData = QueryHelper::combineData($dataEmail, $dataPhone, 'CpfCnpj', 'telefone');
        error_log(">>> Dados combinados: " . count($combinedData));

        return $combinedData;
    }
}