<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CE19acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Totalização de profissionais e empresas ativos por ano (últimos 18 anos)';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Totalização de profissionais e empresas ativos por ano (últimos 18 anos)</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <option value="Brasil">Brasil</option>      
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                    foreach (Helper::$ufList as $val => $value) {
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
    
    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croQuery = 'CRO IS NOT NULL';
    } else {
        $croQuery = "CRO = '{$inputPost["cro"]}'";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoQuery = 'Ate_Ano IS NOT NULL';
    } else {
        $anoQuery = "Ate_Ano = '{$inputPost["ano"]}'";
    }
    
    $query = "SELECT * FROM CFO_CWS.dbo.Cons_Total_Inscritos_Ativos_Por_Ano_Por_Categoria WHERE $croQuery AND $anoQuery";
    
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        // echo $query;
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
        <table id="consultaEstatistica19" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">Ano</th>
                <th scope="col">CRO</th>
                <th scope="col">APD</th>
                <th scope="col">ASB</th>
                <th scope="col">CD</th>
                <th scope="col">ECIPO</th>
                <th scope="col">EPAO</th>
                <th scope="col">LB</th>
                <th scope="col">TPD</th>
                <th scope="col">TSB</th>
                <th scope="col">TOTAL</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['Ate_Ano'] . "</td>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['APD'] . "</td>";
                    echo "<td>" . $row['ASB'] . "</td>";
                    echo "<td>" . $row['CD'] . "</td>";
                    echo "<td>" . $row['ECIPO'] . "</td>";
                    echo "<td>" . $row['EPAO'] . "</td>";
                    echo "<td>" . $row['LB'] . "</td>";
                    echo "<td>" . $row['TPD'] . "</td>";
                    echo "<td>" . $row['TSB'] . "</td>";
                    echo "<td>" . $row['TOTAL'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#consultaEstatistica19').DataTable({
                order: [[3, 'asc']],
                "iDisplayLength": 50,
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            });
        });
    </script>

<?php } ?>
