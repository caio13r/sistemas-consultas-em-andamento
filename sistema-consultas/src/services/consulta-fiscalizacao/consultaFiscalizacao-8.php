<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CF3acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizados - Estatísticas de Fiscalizados por Idade';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Fiscalizações por Idade - Categoria x Ano</h6>
    <form action="" method="post">
    <div class="form-row">
    <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <?php
                        $values = array('BR' => 'Brasil');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                      if (Session::get('grupo') === 0 || $row['CF8select'] == true) {
                        foreach(Helper::$ufList as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }            
                      } else {
                            foreach(Helper::$ufList as $val => $value) {
                                if ($users->CheckGroupUf() == $val) {
                                    $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                                    echo "<option value='$val' $selected>$value</option>";
                                }
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" value="<?= $dados["categoria"] ?>">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <option value='APD'>APD</option>
                    <option value='ASB'>ASB</option>
                    <option value='CD'>CD</option>
                    <option value='TPD'>TPD</option>
                    <option value='TSB'>TSB</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="ano">Ano:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <?php
                        $db = Database3::getInstance();
                        $con = $db->getConnection();

                        try {
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_Contagem_Fiscalizados_Por_Idade";
                            $stmt = $con->prepare($query);
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOexception $error) {
                            die("Erro ao retornar os dados: " . $error->getMessage());
                        }

                        foreach($result as $val) {
                            $selected = (!empty($inputPost['ano']) && $inputPost['ano'] == $val['ANO']) ? 'selected' : '';
                            echo "<option value='{$val['ANO']}' {$selected}>{$val['ANO']}</option>";
                        }
                    ?>
                </select>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "CRO IS NOT NULL";
    } else {
        $croWhere = "CRO LIKE '{$inputPost["cro"]}'";
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catWhere = "(APD IS NOT NULL OR ASB IS NOT NULL OR CD IS NOT NULL OR TPD IS NOT NULL OR TSB IS NOT NULL)";
    } else {
        $catWhere = "{$inputPost["categoria"]} IS NOT NULL";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "ANO IS NOT NULL";
    } else {
        $anoWhere = "ANO LIKE '{$inputPost["ano"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    ANO,
                    CRO,
                    Idade,
                    APD,
                    ASB,
                    CD,
                    TPD,
                    TSB,
                    Total_geral
                FROM CFO_CWS.dbo.Cons_Contagem_Fiscalizados_Por_Idade
                WHERE $croWhere AND $catWhere AND $anoWhere
                ORDER BY ANO, CRO, Idade";
        $stmt = $con->prepare($query);
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

<div id="tableResult" class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">Ano</th>
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
                    echo "<td>" . $row['ANO'] . "</td>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Idade'] . "</td>";
                    echo "<td>" . number_format($row['APD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['ASB'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['CD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TPD'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TSB'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Total_geral'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>
<script>
    $(document).ready(function() {
        $('#tabelaConsultas').DataTable({
            "paging": true,
            "lengthMenu": [10, 25, 50, 100],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Portuguese-Brasil.json"
            }
        });
    });
</script>
<?php } ?>