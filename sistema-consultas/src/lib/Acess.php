<?php

namespace Cfo\SisConsultas\lib;

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

use PDO;
use PDOException;

if (Session::get('grupo') != 0 && Session::get('grupo') != 1) {
    $grupo = 'CRO';
} else {
    $grupo = 'CFO';
}

$subgrupo = $users->SubGroupName(Session::get('subgrupo'));

try {
    $db = Database1::getInstance();
    $con = $db->getConnection();
    $query = "SELECT * FROM tbl_acessos WHERE grupo = '$grupo' AND subgrupo = '$subgrupo'";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // print_r( $row);
} catch (PDOexception $error) {
    die("Erro ao retornar os dados: " . $error->getMessage());
}