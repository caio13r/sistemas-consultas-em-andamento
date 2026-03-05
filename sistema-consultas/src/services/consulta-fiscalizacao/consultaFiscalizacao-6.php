<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CF6acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizações - Estatísticas de Denúncias';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Denúncias x Ano</h6>
    <form action="" method="post">
        <div class="form-row">
            <!-- Seleção País e Estado -->
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>

                    <!-- Opção País -->
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <option value="BRASIL" <?= (!empty($inputPost['cro']) && $inputPost['cro'] == 'BRASIL') ? 'selected' : '' ?>>Brasil</option>
                    
                    <!-- Opção Estados -->
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                      // Validação de Acesso às UFs 
                      if (Session::get('grupo') === 0 || $row['CF6select'] == true) {
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

            <!-- Seleção de Ano -->
            <div class="form-group col-md-4">
                <label for="ano">Ano:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <?php
                        $db = Database3::getInstance();
                        $con = $db->getConnection();

                        try {
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_Estatisticas_Denuncias";
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

            <!-- Botão de pesquisa -->
            <div class="form-group col-md-4">
                <button type="submit" name="submit" class="btn btn-primary mt-4">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    // Construção dos filtros da query
    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "CRO IS NOT NULL";
    } else {
        $croWhere = "CRO LIKE '{$inputPost["cro"]}'";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "ANO IS NOT NULL";
    } else {
        $anoWhere = "ANO LIKE '{$inputPost["ano"]}'";
    }

    // Executando a query
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    ED.ANO,
                    ED.CRO,
                    ED.Origem,
                    ED.Quantidade_Denuncias,
                    ED.Denuncias_Anonimas,
                    ED.Denuncias_Identificadas
                FROM CFO_CWS.dbo.Cons_Estatisticas_Denuncias AS ED
                WHERE $croWhere AND $anoWhere
                ORDER BY
                    ED.ANO,
                    CASE WHEN ED.CRO = 'BRASIL' THEN 1 ELSE 0 END,
                    ED.CRO,
                    CASE WHEN ED.Origem = 'TOTAL' THEN 1 ELSE 0 END,
                    ED.Origem;";
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

<!-- Tabela de resultados -->
<div id="tableResult" class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">Ano</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Origem</th>
                    <th scope="col">Quantidade de Denúncias</th>
                    <th scope="col">Denúncias Anônimas</th>
                    <th scope="col">Denúncias Identificadas</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['ANO'] . "</td>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Origem'] . "</td>";
                    echo "<td>" . number_format($row['Quantidade_Denuncias'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Denuncias_Anonimas'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Denuncias_Identificadas'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>

