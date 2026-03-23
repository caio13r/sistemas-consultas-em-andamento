<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CI1acesso']) && $row['CI1acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-identidade';
    </script>";
    exit;
  }
  
  
$tituloConsulta = "Auditoria - Total de Identidades Emitidas Consolidado por CRO";
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Total de Identidades Emitidas Consolidado por CRO</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="pfpj">Selecione:</label>
                <select id="pfpj" name="pfpj" class="form-control">
                    <?php
                        $values = array('ALL' => 'Total de Identidades Emitidas Consolidado por CRO');
                        foreach($values as $val => $value) {
                            echo "<option value='$val'>$value</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Gerar</button>
    </form>
</div>

<?php 
    if (isset($inputPost["submit"])) { 

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();
        
            $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_Consolidado";
            $stmt = $con->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
            // echo $query;
            error_log("Erro ao retornar os dados: " . $error->getMessage());
            echo "<div class='alert alert-danger'>Erro ao retornar os dados.</div>";
            return;
        }

        $totalCD = array_sum(array_column($result, 'CD'));
        $totalTSB = array_sum(array_column($result, 'TSB'));
        $totalASB = array_sum(array_column($result, 'ASB'));
        $totalAPD = array_sum(array_column($result, 'APD'));
        $totalTPD = array_sum(array_column($result, 'TPD'));
        $totalAll = array_sum(array_column($result, 'TOT_CRO'));
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
                <th scope="col">Total CD</th>
                <th scope="col">Total TSB</th>
                <th scope="col">Total ASB</th>
                <th scope="col">Total APD</th>
                <th scope="col">Total TPD</th>
                <th scope="col">Total Geral</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . number_format($row['CD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TSB'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['ASB'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['APD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TPD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TOT_CRO'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
                    echo "<tr>";
                    echo "<td>TOTAL</td>";
                    echo "<td>" . number_format($totalCD, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($totalTSB, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($totalASB, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($totalAPD, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($totalTPD, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($totalAll, 0, ',', '.') . "</td>";
                    echo "</tr>";
                ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>