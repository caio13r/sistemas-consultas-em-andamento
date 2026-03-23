<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE7acesso']) && $row['CE7acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - CFO ID Emitidas';
?>

<?php 
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $queryAno = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_ANO_MES GROUP BY ANO";
        $stmt = $con->prepare($queryAno);
        $stmt->execute();
        $resultAno = $stmt->fetchAll();

        $queryMes = "SELECT DISTINCT MES FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_ANO_MES GROUP BY MES";
        $stmt = $con->prepare($queryMes);
        $stmt->execute();
        $resultMes = $stmt->fetchAll();

        } catch (PDOexception $error) {
            error_log("Erro ao retornar os dados: " . $error->getMessage());
            echo "<div class='alert alert-danger'>Erro ao retornar os dados.</div>";
            return;
    }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Apresenta o total geral de CFO ID já emitidas ou reemitidas por CRO</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CE7select'] == true) {
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
                <label for="mes">Selecione o Mês:</label>
                <select id="mes" name="mes" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <?php 
                        foreach ($resultMes as $row) {
                            $selected = (!empty($inputPost['mes']) && $inputPost['mes'] == $row['MES']) ? 'selected' : '';
                            echo "<option value='{$row['MES']}' $selected>{$row['MES']}</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="ano">Selecione o Ano:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <?php 
                        foreach ($resultAno as $row) {
                            $selected = (!empty($inputPost['ano']) && $inputPost['ano'] == $row['ANO']) ? 'selected' : '';
                            echo "<option value='{$row['ANO']}' $selected>{$row['ANO']}</option>";
                        }
                    ?>
                </select>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    $params = [];
    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "WHERE CRO IS NOT NULL";
    } else {
        $croWhere = "WHERE CRO = :cro";
        $params[':cro'] = $inputPost["cro"];
    }

    if ($inputPost["mes"] === 'ALL' || $inputPost["mes"] === null) {
        $mesWhere = "";
    } else {
        $mesWhere = "AND MES = :mes";
        $params[':mes'] = $inputPost["mes"];
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "AND ANO IS NOT NULL";
    } else {
        $anoWhere = "AND ANO = :ano";
        $params[':ano'] = $inputPost["ano"];
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO, ANO, MES, SUM(TOTAL_CD) AS TOTAL_CD, 
        SUM(TOTAL_TSB) AS TOTAL_TSB,
        SUM(TOTAL_ASB) AS TOTAL_ASB,
        SUM(TOTAL_APD) AS TOTAL_APD,
        SUM(TOTAL_TPD) AS TOTAL_TPD,
        SUM(TOT_CRO_ANO_MES) AS TOTAL_ANO
        FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_ANO_MES
        $croWhere $anoWhere $mesWhere
        GROUP BY CRO, ANO, MES";
        $stmt = $con->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro ao retornar os dados: " . $error->getMessage());
        echo "<div class='alert alert-danger'>Erro ao retornar os dados.</div>";
        return;
    }

    $totalCD = array_sum(array_column($result, 'TOTAL_CD'));
    $totalTSB = array_sum(array_column($result, 'TOTAL_TSB'));
    $totalASB = array_sum(array_column($result, 'TOTAL_ASB'));
    $totalAPD = array_sum(array_column($result, 'TOTAL_APD'));
    $totalTPD = array_sum(array_column($result, 'TOTAL_TPD'));
    $totalAno = array_sum(array_column($result, 'TOTAL_ANO'));
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
                <th scope="col">ANO</th>
                <th scope="col">MES</th>
                <th scope="col">TOTAL CD</th>
                <th scope="col">TOTAL TSB</th>
                <th scope="col">TOTAL ASB</th>
                <th scope="col">TOTAL APD</th>
                <th scope="col">TOTAL TPD</th>
                <th scope="col">TOTAL GERAL</th>
            </tr>
        </thead>
        <tbody>
        <?php

            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ANO']) . "</td>";
                echo "<td>" . htmlspecialchars($row['MES']) . "</td>";
                echo "<td>" . number_format($row['TOTAL_CD'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['TOTAL_TSB'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['TOTAL_ASB'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['TOTAL_APD'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['TOTAL_TPD'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['TOTAL_ANO'], 0, ',', '.') . "</td>";   
                echo "</tr>";
            }
            if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null || $inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
                echo "<tr>";
                echo "<td>TOTAL</td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td>" . number_format($totalCD, 0, ',', '.') . "</td>";
                echo "<td>" . number_format($totalTSB, 0, ',', '.') . "</td>";
                echo "<td>" . number_format($totalASB, 0, ',', '.') . "</td>";
                echo "<td>" . number_format($totalAPD, 0, ',', '.') . "</td>";
                echo "<td>" . number_format($totalTPD, 0, ',', '.') . "</td>";
                echo "<td>" . number_format($totalAno, 0, ',', '.') . "</td>";
                echo "</tr>";
            } 
            
        ?>
        </tbody>
    </table>
</div>  
</div>

<?php } ?>
