<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE19acesso']) && $row['CE19acesso'] == false)) {
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
    
    $croCondition = "";
    $croParam = null;
    if (empty($inputPost["cro"]) || $inputPost["cro"] === 'ALL' || $inputPost["cro"] === 'Brasil') {
        $croCondition = "CRO IS NOT NULL";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croCondition = "CRO = :cro";
        $croParam = $inputPost["cro"];
    }

    $anoCondition = "";
    $anoParam = null;
    if (empty($inputPost["ano"]) || $inputPost["ano"] === 'ALL') {
        $anoCondition = "Ate_Ano IS NOT NULL";
    } else {
        if (!ctype_digit($inputPost["ano"])) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Ano inválido.</div>";
            return;
        }
        $anoCondition = "Ate_Ano = :ano";
        $anoParam = $inputPost["ano"];
    }
    
    $query = "SELECT * FROM CFO_CWS.dbo.Cons_Total_Inscritos_Ativos_Por_Ano_Por_Categoria WHERE $croCondition AND $anoCondition";
    
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $stmt = $con->prepare($query);
        if ($croParam !== null) {
            $stmt->bindValue(':cro', $croParam);
        }
        if ($anoParam !== null) {
            $stmt->bindValue(':ano', $anoParam);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
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
                    echo "<td>" . htmlspecialchars($row['Ate_Ano']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['APD']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ASB']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CD']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ECIPO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['EPAO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['LB']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['TPD']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['TSB']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['TOTAL']) . "</td>";
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
