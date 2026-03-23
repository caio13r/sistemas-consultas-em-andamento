<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE2acesso']) && $row['CE2acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - Inscritos x CRO x Categoria x Ano de Registro';
?>

<?php 
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT DISTINCT ANO_REGISTRO FROM CFO_CWS.dbo.vw_Cons_Consolidados_Inscricao_Categoria_Ano_Mes ORDER BY ANO_REGISTRO ASC";
        $stmt = $con->prepare($query);
        // $stmt->bindValue(':cro', "{$inputPost["cro"]}", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
    }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Consultar profissionais - CRO x Categoria x Ano de Registro</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CE2select']) && $row['CE2select'] == true)) {
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
                    <?php
                        foreach(Helper::$catListPf as $val => $value) {
                        echo "<option value='$val'>$value</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="registro">Selecione o Ano de Registro:</label>
                <select id="registro" name="registro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <?php
                        foreach ($result as $row) {
                            if ($row['ANO_REGISTRO'] > 1959) {
                                echo "<option value='{$row['ANO_REGISTRO']}'>{$row['ANO_REGISTRO']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
    
</div>

<?php if (isset($inputPost["submit"])) { 

$croCondition = "";
$croParam = null;
if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
    $croCondition = 'WHERE CRO IS NOT NULL';
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
        $catTable = "TOT_CD_FEM, TOT_CD_MAS, TOT_APD_FEM, TOT_APD_MAS, TOT_TPD_FEM, TOT_TPD_MAS, TOT_ASB_FEM, TOT_ASB_MAS, TOT_TSB_FEM, TOT_TSB_MAS, TOTAL_PROF_FEM, TOTAL_PROF_MAS, TOTAL_GERAL";
    } elseif ($inputPost["sexo"] === 'FEM') {
        $catTable = "TOT_CD_FEM, TOT_APD_FEM, TOT_TPD_FEM, TOT_ASB_FEM, TOT_TSB_FEM, TOTAL_PROF_FEM";
    } elseif ($inputPost["sexo"] === 'MAS') {
        $catTable = "TOT_CD_MAS, TOT_APD_MAS, TOT_TPD_MAS, TOT_ASB_MAS, TOT_TSB_MAS, TOTAL_PORF_MAS";
    }
} else {
    $catTable = "TOT_{$inputPost["categoria"]}_FEM, TOT_{$inputPost["categoria"]}_MAS";
}

if ($inputPost["registro"] === 'ALL' || $inputPost["registro"] === null) {
    $regTable = "AND ANO_REGISTRO IS NOT NULL";
} else {
    $regTable = "AND ANO_REGISTRO = '{$inputPost["registro"]}'";
}

try {
    $db = Database3::getInstance();
    $con = $db->getConnection();

    $query = "SELECT CRO, ANO_REGISTRO, $catTable FROM CFO_CWS.dbo.vw_Cons_Consolidados_Inscricao_Categoria_Ano_Mes $croCondition $regTable ORDER BY ANO_REGISTRO ASC";
    $stmt = $con->prepare($query);
    if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOexception $error) {
    error_log("Erro consulta estatistica: " . $error->getMessage());
    echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
    $result = [];
}

$total_CD_FEM = array_sum(array_column($result, 'TOT_CD_FEM'));
$total_CD_MAS = array_sum(array_column($result, 'TOT_CD_MAS'));
$total_APD_FEM = array_sum(array_column($result, 'TOT_APD_FEM'));
$total_APD_MAS = array_sum(array_column($result, 'TOT_APD_MAS'));
$total_TPD_FEM = array_sum(array_column($result, 'TOT_TPD_FEM'));
$total_TPD_MAS = array_sum(array_column($result, 'TOT_TPD_MAS'));
$total_ASB_FEM = array_sum(array_column($result, 'TOT_ASB_FEM'));
$total_ASB_MAS = array_sum(array_column($result, 'TOT_ASB_MAS'));
$total_TSB_FEM = array_sum(array_column($result, 'TOT_TSB_FEM'));
$total_TSB_MAS = array_sum(array_column($result, 'TOT_TSB_MAS'));
$total_TOTAL_FEM = array_sum(array_column($result, 'TOTAL_PROF_FEM'));
$total_TOTAL_MAS = array_sum(array_column($result, 'TOTAL_PROF_MAS'));
$total_TOTAL_CRO = array_sum(array_column($result, 'TOTAL_GERAL'));
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
                <th scope="col">ANO REGISTRO</th>

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
                        <th scope="col">TOTAL GERAL</th>
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
                    $var = "TOT_" . $inputPost["categoria"] . "_FEM";
                    $var1 = "TOT_" . $inputPost["categoria"] . "_MAS";
                    $total_var = array_sum(array_column($result, $var));
                    $total_var1 = array_sum(array_column($result, $var1));
                    $total_var2 = $total_var+$total_var1;

                    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
                        if ($inputPost["sexo"] === 'ALL' || $inputPost["sexo"] === null) {
                            foreach ($result as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['ANO_REGISTRO']) . "</td>";
                                echo "<td>" . number_format($row["TOT_CD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_CD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_APD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_APD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TPD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TPD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_ASB_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_ASB_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TSB_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TSB_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOTAL_PROF_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOTAL_PROF_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOTAL_GERAL"], 0, ',', '.') . "</td>";
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
                                echo "<td>" . htmlspecialchars($row['ANO_REGISTRO']) . "</td>";
                                echo "<td>" . number_format($row["TOT_CD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_APD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TPD_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_ASB_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TSB_FEM"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOTAL_PROF_FEM"], 0, ',', '.') . "</td>";
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
                                echo "<td>" . htmlspecialchars($row['ANO_REGISTRO']) . "</td>";
                                echo "<td>" . number_format($row["TOT_CD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_APD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TPD_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_ASB_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOT_TSB_MAS"], 0, ',', '.') . "</td>";
                                echo "<td>" . number_format($row["TOTAL_PROF_MAS"], 0, ',', '.') . "</td>";
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
                                echo "<td>" . htmlspecialchars($row['ANO_REGISTRO']) . "</td>";
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
