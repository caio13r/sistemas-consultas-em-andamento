<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE13acesso']) && $row['CE13acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Totalização de Endereços Comerciais Mais Recentes de Ativos por Localidade';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Totalização de Endereços Comerciais Mais Recentes de Ativos por Localidade</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>  
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
    $croValue = $inputPost["cro"] ?? 'ALL';
    if ($croValue !== 'ALL' && !array_key_exists($croValue, Helper::$ufList)) {
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
        return;
    }
    $script = "DECLARE @CRO_UF VARCHAR(2) = '{$croValue}'; ";

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/consultaEstatistica/consultaEstatistica13.sql";
    $myfile = fopen($path, "r") or die("Unable to open file!");
    $script .= fread($myfile, filesize($path));
    fclose($myfile);

    if ($croValue === 'ALL') {
        $script = str_replace("WHERE [CRO] = @CRO_UF", "", $script);
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $stmt = $con->prepare($script);
        $stmt->execute();
        $resultAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array_slice($resultAll, 0, 10000);

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
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($resultAll)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="consultaEstatistica13" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Nome</th>
                <th scope="col">CPF</th>
                <th scope="col">Tipo Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
                <th scope="col">Correspondência</th>
                <th scope="col">Tipo Endereço</th>
                <th scope="col">Data Atualização</th>
                <th scope="col">Logradouro</th>
                <th scope="col">Número</th>
                <th scope="col">Complemento</th>
                <th scope="col">Bairro</th>
                <th scope="col">Município</th>
                <th scope="col">UF</th>
                <th scope="col">CEP</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Categoria']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Inscricao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nome']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CPF']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Tipo_Inscricao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Situacao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Detalhe']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Correspondencia']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Tipo_Endereco']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Data_Atualizacao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Logradouro']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Numero']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Complemento']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Bairro']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Municipio']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['UF']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CEP']) . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>
