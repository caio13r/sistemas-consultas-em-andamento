<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CI5acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-identidade';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - CFO ID Lista Detalhada';
?>

<?php 
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $queryMes = "SELECT DISTINCT SUBSTRING(DATA_EMISSAO_ID,4,2) AS MES
                     FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO
                     ORDER BY MES";
        $stmt = $con->prepare($queryMes);
        $stmt->execute();
        $resultMes = $stmt->fetchAll();

        $queryAno = "SELECT DISTINCT SUBSTRING(DATA_EMISSAO_ID,7,4) AS ANO
                     FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO
                     ORDER BY ANO";
        $stmt = $con->prepare($queryAno);
        $stmt->execute();
        $resultAno = $stmt->fetchAll();

        } catch (PDOexception $error) {
            die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Apresenta a lista detalhada de CFO ID já emitidas</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CI5select'] == true) {
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

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "WHERE CRO IS NOT NULL";
    } else {
        $croWhere = "WHERE CRO = '{$inputPost["cro"]}'";
    }

    if ($inputPost["mes"] === 'ALL' || $inputPost["mes"] === null) {
        $mesWhere = null;
    } else {
        $mesWhere = "AND SUBSTRING(DATA_EMISSAO_ID,4,2) = '{$inputPost["mes"]}'";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = null;
    } else {
        $anoWhere = "AND SUBSTRING(DATA_EMISSAO_ID,7,4) = '{$inputPost["ano"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO,
                         CATEGORIA,
                         INSCRICAO,
                         PROFISSIONAL,
                         DATA_EMISSAO_ID,
                         SUBSTRING(DATA_EMISSAO_ID,1,2) AS DIA,
                         SUBSTRING(DATA_EMISSAO_ID,4,2) AS MES,
                         SUBSTRING(DATA_EMISSAO_ID,7,4) AS ANO
                   FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO
                   $croWhere $anoWhere $mesWhere
                   ORDER BY CRO, PROFISSIONAL, CATEGORIA, INSCRICAO, SUBSTRING(DATA_EMISSAO_ID,7,4)";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-center mb-3">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Download planilha em Excel</button>
        </form>
    </div>
<?php } ?>

<!-- <div class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Profissional</th>
                    <th scope="col">Data de Emissão</th>
                    <th scope="col">Dia</th>
                    <th scope="col">Mês</th>
                    <th scope="col">Ano</th>
                </tr>
            </thead>
            <tbody>
            <?php
                // foreach ($result as $row) {
                //     echo "<tr>";
                //     echo "<td>" . $row['CRO'] . "</td>";
                //     echo "<td>" . $row['CATEGORIA'] . "</td>";
                //     echo "<td>" . $row['INSCRICAO'] . "</td>";
                //     echo "<td>" . $row['PROFISSIONAL'] . "</td>";
                //     echo "<td>" . $row['DATA_EMISSAO_ID'] . "</td>";
                //     echo "<td>" . $row['DIA'] . "</td>";
                //     echo "<td>" . $row['MES'] . "</td>";
                //     echo "<td>" . $row['ANO'] . "</td>";
                //     echo "</tr>";
                // }
            ?>
            </tbody>
        </table>
    </div>  
</div> -->

<?php } ?>
