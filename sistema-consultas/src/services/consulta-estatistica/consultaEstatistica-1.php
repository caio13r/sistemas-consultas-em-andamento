<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE1acesso']) && $row['CE1acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Inscritos x Categoria x População x Sexo';
?>

<!-- CONSULTA INSCRITOS X REGIÃO -->

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Consultar profissionais - CRO x Categoria x População x Sexo</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <?php
                        $values = array('BR' => 'Brasil', 'RG' => 'Regiões');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CE1select']) && $row['CE1select'] == true)) {
                        foreach(Helper::$ufList as $val => $value) {
                          if ($val != 'ALL') {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                          }
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
                        $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="sexo">Selecione o Sexo:</label>
                <select id="sexo" name="sexo" class="form-control" value="<?= $dados["sexo"] ?>">
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 'FEM' => 'Feminino', 'MAS' => 'Masculino');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['sexo']) && $inputPost['sexo'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
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
    if ($inputPost["cro"] === 'ALL') {
        $croCondition = "UF <> 'BR' AND UF <> 'RG'";
    } elseif ($inputPost["cro"] === 'BR') {
        $croCondition = "UF = 'BR'";
    } elseif ($inputPost["cro"] === 'RG' || $inputPost["cro"] === null) {
        $croCondition = "UF = 'RG'";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croCondition = "UF = :cro";
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
            $catTable = "CD_FEM, CD_MAS, APD_FEM, APD_MAS, TPD_FEM, TPD_MAS, ASB_FEM, ASB_MAS, TSB_FEM, TSB_MAS, TOT_FEM, TOT_MAS, TOT_BRASIL";
        } elseif ($inputPost["sexo"] === 'FEM') {
            $catTable = "CD_FEM, APD_FEM, TPD_FEM, ASB_FEM, TSB_FEM, TOT_FEM";
        } elseif ($inputPost["sexo"] === 'MAS') {
            $catTable = "CD_MAS, APD_MAS, TPD_MAS, ASB_MAS, TSB_MAS, TOT_MAS";
        }
    } else {
        $catTable = "{$inputPost["categoria"]}_FEM, {$inputPost["categoria"]}_MAS";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT REGIAO, UF, POPULACAO, $catTable FROM CFO_CWS.dbo.vw_Cons_Dados_Somados_Populacao WHERE $croCondition";
        $stmt = $con->prepare($query);
        if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
    }

    $totalFemCd = array_sum(array_column($result, 'CD_FEM'));
    $totalMasCd = array_sum(array_column($result, 'CD_MAS'));
    $totalFemApd = array_sum(array_column($result, 'APD_FEM'));
    $totalMasApd = array_sum(array_column($result, 'APD_MAS'));
    $totalFemTpd = array_sum(array_column($result, 'TPD_FEM'));
    $totalMasTpd = array_sum(array_column($result, 'TPD_MAS'));
    $totalFemAsb = array_sum(array_column($result, 'ASB_FEM'));
    $totalMasAsb = array_sum(array_column($result, 'ASB_MAS'));
    $totalFemTsb = array_sum(array_column($result, 'TSB_FEM'));
    $totalMasTsb = array_sum(array_column($result, 'TSB_MAS'));
    $totalProfFem = array_sum(array_column($result, 'TOT_FEM'));
    $totalProfMas = array_sum(array_column($result, 'TOT_MAS'));
    $totalProfCro = array_sum(array_column($result, 'TOT_BRASIL'));
    $totalPopulacao = array_sum(array_column($result, 'POPULACAO'));
?>

<script type="text/javascript">
    // Função para alterar div
    function showInput(val) {
        if (val == 1) {
            document.getElementById('tableResult').style.display = 'block';
            document.getElementById('graficResult').style.display = 'none';
            document.getElementById('powerBI').style.display = 'none';
        } else if (val == 2) {
            document.getElementById('tableResult').style.display = 'none';
            document.getElementById('graficResult').style.display = 'block';
            document.getElementById('powerBI').style.display = 'none';
        } else if (val == 3) {
            document.getElementById('tableResult').style.display = 'none';
            document.getElementById('graficResult').style.display = 'none';
            document.getElementById('powerBI').style.display = 'block';
        }
    }
</script>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <button class="btn btn-secondary btn-md mr-1" value="1" onclick="showInput(1)">Tabela</button>
        <button class="btn btn-secondary btn-md mr-1" value="2" onclick="showInput(2)">Gráficos</button> 
        <button class="btn btn-secondary btn-md mr-1" value="3" onclick="showInput(3)">PowerBI</button>
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
                    <th scope="col">CRO</th>
                    <th scope="col">REGIÃO</th>
                    <th scope="col">POPULAÇÃO</th>

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
                        <th scope="col">% de <?= htmlspecialchars($inputPost["categoria"]) ?></th>
                        <th scope="col">Habitantes por <?= htmlspecialchars($inputPost["categoria"]) ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
            <?php
                if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
                    if ($inputPost["sexo"] === 'ALL' || $inputPost["sexo"] === null) {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['UF']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['REGIAO']) . "</td>";
                            echo "<td>" . number_format($row['POPULACAO'], 0, ',', '.') . "</td>";
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
                            echo "<td>" . number_format($row["TOT_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOT_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOT_FEM"]+$row["TOT_MAS"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === 'RG' || $inputPost["cro"] === null) {
                            echo "<tr>";
                            echo "<td>TOTAL</td>";
                            echo "<td>-</td>";
                            echo "<td>" . number_format($totalPopulacao, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemCd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasCd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemApd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasApd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemTpd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasTpd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemAsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasAsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemTsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasTsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProfFem, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProfMas, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProfCro, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } elseif ($inputPost["sexo"] === 'FEM') {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['UF']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['REGIAO']) . "</td>";
                            echo "<td>" . number_format($row['POPULACAO'], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["CD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_FEM"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOT_FEM"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === 'RG' || $inputPost["cro"] === null) {
                            echo "<tr>";
                            echo "<td>TOTAL</td>";
                            echo "<td>-</td>";
                            echo "<td>" . number_format($totalPopulacao, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemCd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemApd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemTpd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemAsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalFemTsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProfFem, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } elseif ($inputPost["sexo"] === 'MAS') {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['UF']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['REGIAO']) . "</td>";
                            echo "<td>" . number_format($row['POPULACAO'], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["CD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["APD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TPD_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["ASB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TSB_MAS"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["TOT_MAS"], 0, ',', '.') . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === 'RG' || $inputPost["cro"] === null) {
                            echo "<tr>";
                            echo "<td>TOTAL</td>";
                            echo "<td>-</td>";
                            echo "<td>" . number_format($totalPopulacao, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasCd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasApd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasTpd, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasAsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalMasTsb, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProfMas, 0, ',', '.')  . "</td>";
                            echo "</tr>";
                        }
                    } } else {
                        $catFem = $inputPost["categoria"] . "_FEM";
                        $catMas = $inputPost["categoria"] . "_MAS";
                        $totalCatFem = array_sum(array_column($result, $catFem));
                        $totalCatMas = array_sum(array_column($result, $catMas));
                        $totalCatGeral = $totalCatFem+$totalCatMas;

                        foreach ($result as $row) {
                            $totalProf = $row["$catFem"]+$row["$catMas"];
                            $porProf = ($totalProf/$row['POPULACAO'])*100;
                            $HabProf = $row['POPULACAO']/$totalProf;

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['UF']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['REGIAO']) . "</td>";
                            echo "<td>" . number_format($row['POPULACAO'], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["$catFem"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($row["$catMas"], 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalProf, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($porProf, 2) . "%</td>";
                            echo "<td>" . number_format($HabProf, 2) . "</td>";
                            echo "</tr>";
                        }
                        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === 'RG' || $inputPost["cro"] === null) {
                            
                            $porTotal = ($totalCatGeral/$totalPopulacao)*100;
                            $HabTotal = $totalPopulacao/$totalCatGeral;

                            echo "<tr>";
                            echo "<td>TOTAL</td>";
                            echo "<td>-</td>";
                            echo "<td>" . number_format($totalPopulacao, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalCatFem, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalCatMas, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($totalCatGeral, 0, ',', '.') . "</td>";
                            echo "<td>" . number_format($porTotal, 2) . "%</td>";
                            echo "<td>" . number_format($HabTotal, 2) . "</td>";
                            echo "</tr>";
                        }
                    }
                ?>

            </tbody>
        </table>
    </div>
</div>
    
<div id="graficResult" class="row" style="display:none">

    <div class="col-10 offset-1 mt-3">

        <div>
            <canvas id="myChart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            // Setup

            <?php if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) { ?>
                
            const data = {
                labels: ['CD', 'APD', 'TPD', 'ASB', 'TSB', 'GERAL'],
                datasets: [
                    <?php if ($inputPost["sexo"] != 'MAS' ) { ?>
                    {
                    label: 'Feminino',
                    data: [<?= $totalFemCd ?>, <?= $totalFemApd ?>, <?= $totalFemTpd ?>, <?= $totalFemAsb ?>, <?= $totalFemTsb ?>, <?= $totalProfFem ?>],
                    backgroundColor: '#FF80AB',
                    },
                    <?php } ?>
                    <?php if ($inputPost["sexo"] != 'FEM' ) { ?>
                    {
                    label: 'Masculino',
                    data: [<?= $totalMasCd ?>, <?= $totalMasApd ?>, <?= $totalMasTpd ?>, <?= $totalMasAsb ?>, <?= $totalMasTsb ?>, <?= $totalProfMas ?>],
                    backgroundColor: '#1E88E5',
                    },
                    <?php } ?>
                ]
            };

            <?php } else { ?>

            const data = {
                labels: ['<?= htmlspecialchars($inputPost["categoria"]) . ' FEMININO' ?>', '<?= htmlspecialchars($inputPost["categoria"]) . ' MASCULINO' ?>', '<?= htmlspecialchars($inputPost["categoria"]) . ' GERAL' ?>'],
                datasets: [
                    {
                    label: 'Profissionais',
                    data: [<?= $totalCatFem ?>, <?= $totalCatMas ?>, <?= $totalCatGeral ?>],
                    backgroundColor: '#808080',
                    },
                ]
            };

            <?php } ?>

            // Config
            const config = {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false,
                        text: 'Gráfico de Profissionais por Categoria',
                    }
                    }
                }
                };

            // Render
            const myChart = new Chart(
                document.getElementById('myChart'),
                config
            );

        </script>

    </div>
</div>  

<div id="powerBI" class="row" style="display:none">
    <div class="col mt-5">
        <iframe title="teste_01" width="100%" height="700" src="https://app.powerbi.com/view?r=eyJrIjoiYzE0M2FhYmYtNWRlMi00NmEzLWFiMGYtYzNjZjJhOTM0MjcwIiwidCI6ImVjMzU5YmExLTYzMGItNGQyYi1iODMzLWM4ZTZkNDhmODA1OSJ9&pageName=ReportSection06c0cdd1bb530004154c" frameborder="0" allowFullScreen="true"></iframe>
    </div>
</div>

<?php } ?>
