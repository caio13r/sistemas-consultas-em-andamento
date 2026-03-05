<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\database\Database2;

use PDO;
use PDOException;

class Connection {
    public static function conn_Sqlsrv(string $path, string $query_start = '', string $query_data = ''):array{
        if( $query_data == ''){
            $path = realpath(dirname(__FILE__, 2)) . "/script/{$path}.sql";
            $myfile = fopen($path, "r") or die("Unable to open file!---");
            $script = fread($myfile,filesize($path));
            fclose($myfile);
        }else{
            $script = $query_data;
        }

        if ($query_start != ''){
            $script = $query_start.$script;
        }
      
        // conexão
        try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        // $con = new PDO("sqlsrv:server=$server; database = $dbName; Encrypt=false; TrustServerCertificate=true;", $uid, $pwd);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $con->prepare($script);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $arr_result = $stmt->fetchAll();
        $stmt = null; 

        // Error handling
        } catch (PDOException $e) {
            die("Falha ao conectar ao banco de dados: " . $e->getMessage());
        }

        return $arr_result;
    }

    public static function connWithScript(string $script, array $params):array{
        // conexão
        try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $con->prepare($script);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $arr_result = $stmt->fetchAll();
        $stmt = null; 

        // Error handling
        } catch (PDOException $e) {
            die("Falha ao conectar ao banco de dados: " . $e->getMessage());
        }

        return $arr_result;
    }

    public static function conn_mysql(string $script, string $db = ''):array{
    // conexão
    try {
    $db = Database2::getInstance();
    $con = $db->getConnection();

    // $con = new PDO("sqlsrv:server=$server; database = $dbName; Encrypt=false; TrustServerCertificate=true;", $uid, $pwd);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $con->prepare($script);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $arr_result = $stmt->fetchAll();
    $stmt = null; 

    // Error handling
    } catch (PDOException $e) {
        die("Falha ao conectar ao banco de dados: " . $e->getMessage());
    }

    return $arr_result;
    }
}