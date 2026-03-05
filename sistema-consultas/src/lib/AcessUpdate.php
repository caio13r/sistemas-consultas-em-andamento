<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();
Session::CheckAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {

    $db = Database1::getInstance();
    $con = $db->getConnection();
    $grupo = $_POST['grupo'];
    $subgrupo = $_POST['subgrupo'];
    $id = $_POST['id'];
    $resposta = $_POST[$id];

    try {
        $sql = "UPDATE tbl_acessos SET $id = :resposta WHERE grupo = :grupo AND subgrupo = :subgrupo";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':resposta', $resposta);
        $stmt->bindValue(':grupo', $grupo);
        $stmt->bindValue(':subgrupo', $subgrupo);
        $stmt->execute();
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

    echo 'Permissão alterada!';
    header('Location:/acessos?grupo='.$grupo.'&subgrupo='.$subgrupo);
} else {
    echo "Acesso não permitido.";
    header('Location:/acessos');
}
