<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CF1acesso']) && $row['CF1acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-fiscalizacao';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizações - Estatísticas de Fiscalizações';
?>

<div class="col-md-8 offset-md-2 mb-4">
<h6 class="mb-2">Estatísticas de Fiscalizações - Categoria x Ano</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <?php
                        $values = array('Brasil' => 'Brasil');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CF1select']) && $row['CF1select'] == true)) {
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
                        foreach(Helper::$catList as $val => $value) {
                            $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="ano">Ano da Fiscalização    :</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <?php

                        $db = Database3::getInstance();
                        $con = $db->getConnection();

                        try {
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes";
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
        $croWhere = "T.CRO IS NOT NULL";
    } else if ($inputPost["cro"] === 'Brasil') {
        $croWhere = "CRO = 'Brasil'";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croWhere = "CRO = :cro";
        $croParam = $inputPost["cro"];
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catWhere = "T.Categoria IS NOT NULL";
    } else {
        if (!array_key_exists($inputPost["categoria"], Helper::$catList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Categoria inválida.</div>";
            return;
        }
        $catWhere = "T.Categoria = :categoria";
        $catParam = $inputPost["categoria"];
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "T.ANO IS NOT NULL";
    } else {
        if (!ctype_digit($inputPost["ano"])) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Ano inválido.</div>";
            return;
        }
        $anoWhere = "T.ANO = :ano";
        $anoParam = $inputPost["ano"];
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    T.CRO,
                    T.Categoria,
                    T.[Total de Ativos],
                    (ANO-2) AS [Ano Prof Ativos],
                    T.[Quantidade de Fiscalizações],
                    T.ANO as [Ano Fiscalizações],
                    T.[Percentual Fiscalizações],
                    T.[Fiscalizações ON-LINE],
                    T.[Fiscalizações PROATIVAS],
                    T.[Fiscalizações REATIVAS],
                    T.[Fiscalizações NÃO INFORMADO],
                    T.[Fiscalizações Exercício Ilegal],
                    T.[Notificações (indícios de irregularidades)],
                    T.[Fiscalizações com Termo]
                FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes AS T
                WHERE $croWhere AND $catWhere AND $anoWhere
                ORDER BY
                    T.ANO,
                CASE WHEN T.CRO = 'Brasil' THEN 1 ELSE 0 END, 
                T.CRO,
                CASE WHEN T.Categoria = 'TOTAL' THEN 1 ELSE 0 END,
                    T.Categoria;";
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

<div class="row mt-4">
<div class="col table-responsive">
    <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
        <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Total de Ativos</th>
                <th scope="col">Ano Prof. Ativos</th>
                <th scope="col">Quantidade de Fiscalizações</th>
                <th scope="col">Ano Fiscalização</th>
                <th scope="col">Percentual Fiscalizações</th>
                <th scope="col">Fiscalizações ON-LINE</th>
                <th scope="col">Fiscalizações PROATIVAS</th>
                <th scope="col">Fiscalizações REATIVAS</th>
                <th scope="col">Fiscalizações NÃO INFORMADO</th>
                <th scope="col">Fiscalizações Exercício Ilegal</th>
                <th scope="col">Notificações (indícios de irregularidades)</th>
                <th scope="col">Fiscalizações com Termo</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Categoria']) . "</td>";
                echo "<td>" . number_format($row['Total de Ativos'], 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($row['Ano Prof Ativos']) . "</td>";
                echo "<td>" . number_format($row['Quantidade de Fiscalizações'], 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($row['Ano Fiscalizações']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Percentual Fiscalizações']) . "</td>";
                echo "<td>" . number_format($row['Fiscalizações ON-LINE'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['Fiscalizações PROATIVAS'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['Fiscalizações REATIVAS'], 0, ',', '.') . "</td>";
                echo "<td>" . number_format($row['Fiscalizações NÃO INFORMADO'], 0, ',', '.') . "</td>";   
                echo "<td>" . number_format($row['Fiscalizações Exercício Ilegal'], 0, ',', '.') . "</td>";   
                echo "<td>" . number_format($row['Notificações (indícios de irregularidades)'], 0, ',', '.') . "</td>";   
                echo "<td>" . number_format($row['Fiscalizações com Termo'], 0, ',', '.') . "</td>";   
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>
</div>  
</div>

<?php } ?>
