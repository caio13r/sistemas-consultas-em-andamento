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

$tituloConsulta = 'Estatísticas - Totalização de Profissionais Ativos por Idade';

// try {
//     $db = Database3::getInstance();
//     $con = $db->getConnection();

//     // Obtenção dos estados para o filtro
//     $queryCRO = "SELECT DISTINCT CRO FROM CFO_CWS.dbo.vw_Cons_Contagem_profissionais_por_idade where CRO <> 'Brasil' ORDER BY CRO";
//     $stmt = $con->prepare($queryCRO);
//     $stmt->execute();
//     $resultCRO = $stmt->fetchAll();

// } catch (PDOexception $error) {
//     die("Erro ao retornar os dados: " . $error->getMessage());
// }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Estatísticas da totalização de profissionais ativos por idade</h6>
    <form action="" method="post">
        <div class="form-row">
            <!-- Campo de seleção do estado -->
            <div class="form-group col-md-12">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <option value="Brasil">Brasil</option>
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                    foreach (Helper::$ufList_withoutAll as $val => $value) {
                        $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $row['CRO']) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        

        <!-- Botão de submissão -->
        <div class="form-row">
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

        $query = "SELECT CRO, Idade, APD, ASB, CD, TPD, TSB, Total_geral
                  FROM CFO_CWS.dbo.vw_Cons_Contagem_profissionais_por_idade 
                  $croWhere ORDER BY Idade DESC";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

    $totalRegistros = count($result);
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
        <table id="tabelaDadosEstatistica8" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Idade</th>
                <th scope="col">APD</th>
                <th scope="col">ASB</th>
                <th scope="col">CD</th>
                <th scope="col">TPD</th>
                <th scope="col">TSB</th>
                <th scope="col">Total Geral</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";

                    $idade = (int)$row['Idade'];
                    echo "<td>" . ($idade === 0 ? "TOTAL" : $idade) . "</td>";

                    echo "<td>" . $row['APD'] . "</td>";
                    echo "<td>" . $row['ASB'] . "</td>";
                    echo "<td>" . $row['CD'] . "</td>";
                    echo "<td>" . $row['TPD'] . "</td>";
                    echo "<td>" . $row['TSB'] . "</td>";
                    echo "<td>" . $row['Total_geral'] . "</td>";
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
        $('#tabelaDadosEstatistica8').DataTable({
            "paging": true,
            "pageLength": 50,
            "columnDefs": [
                {
                    "targets": 1,
                    "type": "num",
                    "render": function (data, type, row) {
                        if (type === "sort") {
                            return data === "TOTAL" ? 999999 : parseInt(data, 10) || 0;
                        }
                        return data;
                    }
                }
            ],
            "order": [[1, 'asc']],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
            }
        });
    });
</script>

<?php } ?>
