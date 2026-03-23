<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE3acesso']) && $row['CE3acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - profissionais - CRO x Categoria x Faixa Etária';
?>

<!-- CONSULTA INSCRITOS X ANO DE NASCIMENTO -->

<?php 
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT DISTINCT FAIXA_ETARIA FROM CFO_CWS.dbo.vw_Cons_Consolidados_Nascimento_Categoria_Ano ORDER BY FAIXA_ETARIA ASC";
        $stmt = $con->prepare($query);
        // $stmt->bindValue(':cro', "{$inputPost["cro"]}", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
    }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Consultar profissionais - CRO x Categoria x Faixa Etária</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CE3select']) && $row['CE3select'] == true)) {
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
            <div class="form-group col-md-3">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" value="<?= $dados["categoria"] ?>">
                    <option disabled selected value>Selecione</option>
                    <?php
                        foreach(Helper::$catListPf as $val => $value) {
                            echo "<option value='$val'>$value</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="ano">Selecione a Faixa Etária:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <?php
                        foreach ($result as $row) {
                            echo "<option value='{$row['FAIXA_ETARIA']}'>{$row['FAIXA_ETARIA']}</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="sexo">Selecione o Sexo:</label>
                <select id="sexo" name="sexo" class="form-control" value="<?= $dados["sexo"] ?>">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <option value="FEM">Feminino</option>
                    <option value="MAS">Masculino</option>
                </select>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    $croCondition = "";
    $croParam = null;
    if ($inputPost["cro"] === 'ALL') {
        $croCondition = "WHERE CRO <> 'BR'";
    } elseif ($inputPost["cro"] === null) {
        $croCondition = "WHERE CRO = 'BR'";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croCondition = "WHERE CRO = :cro";
        $croParam = $inputPost["cro"];
    }

    if ($inputPost["categoria"] !== 'ALL' && $inputPost["categoria"] !== null) {
        if (!array_key_exists($inputPost["categoria"], Helper::$catListPf)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Categoria inválida.</div>";
            return;
        }
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        if ($inputPost["sexo"] === 'ALL' || $inputPost["sexo"] === null) {
            $catTable = "CD_FEM, CD_MAS, APD_FEM, APD_MAS, TPD_FEM, TPD_MAS, ASB_FEM, ASB_MAS, TSB_FEM, TSB_MAS, TOTAL_FEM, TOTAL_MAS, TOTAL_CRO";
        } elseif ($inputPost["sexo"] === 'FEM') {
            $catTable = "CD_FEM, APD_FEM, TPD_FEM, ASB_FEM, TSB_FEM, TOTAL_FEM";
        } elseif ($inputPost["sexo"] === 'MAS') {
            $catTable = "CD_MAS, APD_MAS, TPD_MAS, ASB_MAS, TSB_MAS, TOTAL_MAS";
        }
    } else {
        $catTable = "{$inputPost["categoria"]}_FEM, {$inputPost["categoria"]}_MAS";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoTable = "AND FAIXA_ETARIA IS NOT NULL";
    } else {
        $anoTable = "AND FAIXA_ETARIA = '{$inputPost["ano"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO, FAIXA_ETARIA, $catTable FROM CFO_CWS.dbo.vw_Cons_Consolidados_Nascimento_Categoria_Ano $croCondition $anoTable ORDER BY FAIXA_ETARIA ASC";
        $stmt = $con->prepare($query);
        if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
    }
    
    $total_CD_FEM = array_sum(array_column($result, 'CD_FEM'));
    $total_CD_MAS = array_sum(array_column($result, 'CD_MAS'));
    $total_APD_FEM = array_sum(array_column($result, 'APD_FEM'));
    $total_APD_MAS = array_sum(array_column($result, 'APD_MAS'));
    $total_TPD_FEM = array_sum(array_column($result, 'TPD_FEM'));
    $total_TPD_MAS = array_sum(array_column($result, 'TPD_MAS'));
    $total_ASB_FEM = array_sum(array_column($result, 'ASB_FEM'));
    $total_ASB_MAS = array_sum(array_column($result, 'ASB_MAS'));
    $total_TSB_FEM = array_sum(array_column($result, 'TSB_FEM'));
    $total_TSB_MAS = array_sum(array_column($result, 'TSB_MAS'));
    $total_TOTAL_FEM = array_sum(array_column($result, 'TOTAL_FEM'));
    $total_TOTAL_MAS = array_sum(array_column($result, 'TOTAL_MAS'));
    $total_TOTAL_CRO = array_sum(array_column($result, 'TOTAL_CRO'));
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
                <th scope="col">FAIXA ETÁRIA</th>

                <?php if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) { ?>
                    <?php if ($inputPost["sexo"] === 'ALL' || $inputPost["sexo"] === null) { ?>
                        <th scope="col">CD FEM</th>
                        <th scope="col">CD MAS</th>
                        <th scope="col">APD FEM</th>
                        <th scope="col">APD MAS</th>
                        <th scope="col">TPD FEM</th>
                        <th scope="col">TPD MAS</th>
                        <th scope="col">ASB FEM</th>
                        <th scope="col">ASB MAS</th>
                        <th scope="col">TSB FEM</th>
                        <th scope="col">TSB MAS</th>
                        <th scope="col">TOTAL FEM</th>
                        <th scope="col">TOTAL MAS</th>
                        <th scope="col">TOTAL CRO</th>
                    <?php } elseif ($inputPost["sexo"] === 'FEM') { ?>
                        <th scope="col">CD FEM</th>
                        <th scope="col">APD FEM</th>
                        <th scope="col">TPD FEM</th>
                        <th scope="col">ASB FEM</th>
                        <th scope="col">TSB FEM</th>
                        <th scope="col">TOTAL FEM</th>
                    <?php } elseif ($inputPost["sexo"] === 'MAS') { ?>
                        <th scope="col">CD FEM</th>
                        <th scope="col">APD MAS</th>
                        <th scope="col">TPD FEM</th>
                        <th scope="col">ASB FEM</th>
                        <th scope="col">TSB FEM</th>
                        <th scope="col">TOTAL FEM</th>
                <?php } } else { ?>
                    <th scope="col"><?= htmlspecialchars($inputPost["categoria"]) ?> FEMININO</th>
                    <th scope="col"><?= htmlspecialchars($inputPost["categoria"]) ?> MASCULINO</th>
                    <th scope="col"><?= htmlspecialchars($inputPost["categoria"]) ?> GERAL</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php
                $var = $inputPost["categoria"] . "_FEM";
                $var1 = $inputPost["categoria"] . "_MAS";
                $total_var = array_sum(array_column($result, $var));
                $total_var1 = array_sum(array_column($result, $var1));
                $total_var2 = $total_var+$total_var1;

                if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
                    if ($inputPost["sexo"] === 'ALL' || $inputPost["sexo"] === null) {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['FAIXA_ETARIA']) . "</td>";
                            echo "<td>" . number_format($row["CD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["CD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOTAL_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOTAL_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOTAL_CRO"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null || $inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
                            echo "<tr>";
                            echo "<td> TOTAL </td>";
                            echo "<td></td>";
                            echo "<td>" . number_format($total_CD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_CD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_APD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_APD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TPD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TPD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_ASB_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_ASB_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TSB_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TSB_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TOTAL_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TOTAL_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TOTAL_CRO, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } elseif ($inputPost["sexo"] === 'FEM') {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['FAIXA_ETARIA']) . "</td>";
                            echo "<td>" . number_format($row["CD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOTAL_FEM"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null || $inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
                            echo "<tr>";
                            echo "<td> TOTAL </td>";
                            echo "<td></td>";
                            echo "<td>" . number_format($total_CD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_APD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TPD_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_ASB_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TSB_FEM, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TOTAL_FEM, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } elseif ($inputPost["sexo"] === 'MAS') {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['FAIXA_ETARIA']) . "</td>";
                            echo "<td>" . number_format($row["CD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOTAL_MAS"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null || $inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
                            echo "<tr>";
                            echo "<td> TOTAL </td>";
                            echo "<td></td>";
                            echo "<td>" . number_format($total_CD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_APD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TPD_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_ASB_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TSB_MAS, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_TOTAL_MAS, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } } else {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['FAIXA_ETARIA']) . "</td>";
                            echo "<td>" . number_format($row["$var"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["$var1"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["$var"]+$row["$var1"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null || $inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
                            echo "<tr>";
                            echo "<td>TOTAL</td>";
                            echo "<td></td>";
                            echo "<td>" . number_format($total_var, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_var1, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($total_var2, 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                    }
            ?>
        </tbody>
    </table>
</div>  
</div>

<?php } ?>
