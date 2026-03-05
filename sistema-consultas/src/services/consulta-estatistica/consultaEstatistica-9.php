<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CE7acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Totalização de Profissionais Ativos por Especialidade x Sexo';

try {
    $db = Database3::getInstance();
    $con = $db->getConnection();

    // Obtenção dos estados para o filtro
    $queryCRO = "SELECT DISTINCT CRO FROM CFO_CWS.dbo.vw_Cons_Registro_Especialidades ORDER BY CRO";
    $stmt = $con->prepare($queryCRO);
    $stmt->execute();
    $resultCRO = $stmt->fetchAll();

} catch (PDOexception $error) {
    die("Erro ao retornar os dados: " . $error->getMessage());
}
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Totalização de Profissionais Ativos por Especialidade x Sexo</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
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
        $croWhere = "WHERE CRO IS NOT NULL";
    } else {
        $croWhere = "WHERE CRO = '{$inputPost["cro"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO, Especialidade, Masculino, Feminino, TOTAL
                  FROM CFO_CWS.dbo.vw_Cons_Registro_Especialidades 
                  $croWhere
                  ORDER BY Especialidade";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

    $totalMasculino = array_sum(array_column($result, 'Masculino'));
    $totalFeminino = array_sum(array_column($result, 'Feminino'));
    $totalGeral = array_sum(array_column($result, 'TOTAL'));
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
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Especialidade</th>
                <th scope="col">Masculino</th>
                <th scope="col">Feminino</th>
                <th scope="col">TOTAL</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Especialidade'] . "</td>";
                    echo "<td>" . $row['Masculino'] . "</td>";
                    echo "<td>" . $row['Feminino'] . "</td>";
                    echo "<td>" . $row['TOTAL'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<!-- Inicialização do DataTables com idioma Português -->
<script>
$(document).ready(function() {
    $('#tabelaConsultas').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json" // Verifique se o caminho para o arquivo de idioma está correto
        }
    });
});
</script>

<?php } ?>
