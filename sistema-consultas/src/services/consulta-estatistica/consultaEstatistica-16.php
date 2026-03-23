<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE16acesso']) && $row['CE16acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Totalização de profissionais ativos por especialidade técnica por sexo por ano';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Totalização de profissionais ativos por especialidade técnica por sexo por ano</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value="">Selecione</option>
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
            <!-- Campo de Ano -->
            <div class="form-group col-md-6">
                <label for="ano">Ano:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value="">Selecione</option>
                    <?php
                    $db = Database3::getInstance();
                    $con = $db->getConnection();

                    try {
                        $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_Registro_Especialidades_Tecnicas_Por_Ano ORDER BY ANO DESC";
                        $stmt = $con->prepare($query);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOexception $error) {
                        error_log("Erro consulta estatistica: " . $error->getMessage());
                        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
                        $result = [];
                    }

                    foreach ($result as $val) {
                        $selected = (!empty($inputPost['ano']) && $inputPost['ano'] == $val['ANO']) ? 'selected' : '';
                        echo "<option value='{$val['ANO']}' {$selected}>{$val['ANO']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-12">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 
    $croValue = $inputPost["cro"] ?? 'ALL';
    if ($croValue === 'Brasil') {
        $croValue = 'ALL';
    }
    if ($croValue !== 'ALL' && !array_key_exists($croValue, Helper::$ufList)) {
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
        return;
    }
    $anoValue = $inputPost["ano"] ?? '';
    if ($anoValue !== '' && !ctype_digit($anoValue)) {
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Ano inválido.</div>";
        return;
    }
    $script = "DECLARE @CRO_UF VARCHAR(6) = '{$croValue}'; ";
    $script .= "DECLARE @Ano VARCHAR(6) = '{$anoValue}'; ";

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/consultaEstatistica/consultaEstatistica16.sql";
    $myfile = fopen($path, "r") or die("Unable to open file!");
    $script .= fread($myfile, filesize($path));
    fclose($myfile);

    if ($croValue === 'ALL') {
        $script = str_replace("WHERE [CRO] = @CRO_UF", "", $script);
        $script = str_replace("AND [Ano] = @Ano", "WHERE [Ano] = @Ano", $script);
    }

    if ($anoValue === '') {
        $script = str_replace("WHERE [Ano] = @Ano", "", $script);
        $script = str_replace("AND [Ano] = @Ano", "", $script);
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $stmt = $con->prepare($script);
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
        <table id="consultaEstatistica10" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">Ano</th>
                <th scope="col">CRO</th>
                <th scope="col">Especialidade</th>
                <th scope="col">Masculino</th>
                <th scope="col">Feminino</th>
                <th scope="col">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Ano']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Especialidade']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Masculino']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Feminino']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['TOTAL']) . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>
