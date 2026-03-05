<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CF10acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas de Nomes dos Fiscais';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Estatísticas de Nomes dos Fiscais</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
            <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value="">Selecione</option>    
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                    foreach (Helper::$ufList_withoutAll as $val => $value) {
                        $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $row['CRO']) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                    ?>
                </select>
            </div><br>
            <div class="form-group col-md-12">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 
    $cro = $inputPost["cro"];

    $cro = "[cro_$cro]";

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/consultaFiscalizacao/consultaFiscalizacao20.sql";
    $myfile = fopen($path, "r") or die("Unable to open file!");
    $script .= fread($myfile, filesize($path));
    fclose($myfile);

    $script = str_replace(':banco', $cro, $script);

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $stmt = $con->prepare($script);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="consultaGerais1" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Nome Fiscal</th>
                <th scope="col">CPF</th>
                <th scope="col">Usuário do sistema</th>

            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Nome_Fiscal'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['Usuario_Sistema'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>