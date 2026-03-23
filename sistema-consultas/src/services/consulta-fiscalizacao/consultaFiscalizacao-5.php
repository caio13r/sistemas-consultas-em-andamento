<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CF5acesso']) && $row['CF5acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizações - Estatísticas de Fiscalizações por Tipos de Irregularidades';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Fiscalizações por Tipos de Irregularidades - Categoria x Ano</h6>
    <form action="" method="post">
        <div class="form-row">
            <!-- Seleção de CRO (Brasil + Estados) -->
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
                        if (Session::get('grupo') === 0 || (isset($row['CF5select']) && $row['CF5select'] == true)) {
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

            <!-- Seleção de Categoria -->
            <div class="form-group col-md-4">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" value="<?= $dados["categoria"] ?>">
                    <option disabled selected value>Selecione</option>
                    <?php
                        foreach(Helper::$catList as $val => $value) {
                            $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
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
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorTipos_Irregularidades";
                            $stmt = $con->prepare($query);
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOexception $error) {
                            error_log("Erro consulta fiscalizacao: " . $error->getMessage());
                            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
                            $result = [];
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

    $croParam = null;
    $catParam = null;
    $anoParam = null;

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "CRO IS NOT NULL";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croWhere = "CRO = :cro";
        $croParam = $inputPost["cro"];
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catWhere = "Categoria IS NOT NULL";
    } else {
        if (!array_key_exists($inputPost["categoria"], Helper::$catList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Categoria inválida.</div>";
            return;
        }
        $catWhere = "Categoria = :categoria";
        $catParam = $inputPost["categoria"];
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "ANO IS NOT NULL";
    } else {
        if (!ctype_digit($inputPost["ano"])) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Ano inválido.</div>";
            return;
        }
        $anoWhere = "ANO = :ano";
        $anoParam = $inputPost["ano"];
    }

    // Executando a query com ordenação para CRO Brasil no fim
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    EPTI.ANO,
                    EPTI.CRO,
                    EPTI.Categoria,
                    EPTI.[PF / PJ sem inscrição],
                    EPTI.[PJ sem Responsável Técnico],
                    EPTI.[Ausência de identificação na comunicação e divulgação],
                    EPTI.[Divulgar especialidade sem registro no CFO],
                    EPTI.[Anúncio, propaganda e publicidade irregular],
                    EPTI.[Exercício irregular],
                    EPTI.[Exercício ilegal],
                    EPTI.[Acobertamento de exercício ilegal],
                    EPTI.[Outro]
                FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorTipos_Irregularidades AS EPTI
                WHERE $croWhere AND $catWhere AND $anoWhere
                ORDER BY 
                    EPTI.ANO,
                    CASE WHEN EPTI.CRO = 'BRASIL' AND EPTI.Categoria = 'TOTAL' THEN 2 
                         WHEN EPTI.CRO = 'BRASIL' THEN 1 ELSE 0 END,
                    EPTI.CRO, 
                    EPTI.ORDEM, 
                    EPTI.Categoria;";
        $stmt = $con->prepare($query);
        if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
        if ($catParam !== null) { $stmt->bindValue(':categoria', $catParam); }
        if ($anoParam !== null) { $stmt->bindValue(':ano', $anoParam); }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta fiscalizacao: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
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
                    <th scope="col">Categoria</th>
                    <th scope="col">PF / PJ sem inscrição</th>
                    <th scope="col">PJ sem Responsável Técnico</th>
                    <th scope="col">Ausência de identificação na comunicação e divulgação</th>
                    <th scope="col">Divulgar especialidade sem registro no CFO</th>
                    <th scope="col">Anúncio, propaganda e publicidade irregular</th>
                    <th scope="col">Exercício irregular</th>
                    <th scope="col">Exercício ilegal</th>
                    <th scope="col">Acobertamento de exercício ilegal</th>
                    <th scope="col">Outro</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['ANO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Categoria']) . "</td>";
                    echo "<td>" . number_format($row['PF / PJ sem inscrição'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['PJ sem Responsável Técnico'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Ausência de identificação na comunicação e divulgação'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Divulgar especialidade sem registro no CFO'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Anúncio, propaganda e publicidade irregular'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Exercício irregular'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Exercício ilegal'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Acobertamento de exercício ilegal'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Outro'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>
