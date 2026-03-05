<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CF2acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizações - Estatísticas de Fiscalizações sem Inscrições';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Fiscalizações de pessoas sem inscrições - Tipo pessoa x Ano</h6>
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
                      if (Session::get('grupo') === 0 || $row['CF2select'] == true) {
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
                <label for="pessoa">Tipo de pessoa:</label>
                <select id="pessoa" name="pessoa" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 'PF SEM INSCRIÇÃO' => 'Pessoa Física', 'PJ SEM INSCRIÇÃO' => 'Pessoa Jurídica');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['pessoa']) && $inputPost['pessoa'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
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
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao";
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

    if ($inputPost["cro"] === 'BR' || $inputPost["cro"] === null) {
        $croWhere = "T.CRO = 'BRASIL'";
    } else if ($inputPost["cro"] === 'ALL') {
        $croWhere = "CRO IS NOT NULL";
    } else {
        $croWhere = "CRO LIKE '{$inputPost["cro"]}'";
    }

    if ($inputPost["pessoa"] === 'ALL' || $inputPost["pessoa"] === null) {
        $pessoaWhere = "Pessoa IS NOT NULL";
    } else {
        $pessoaWhere = "Pessoa LIKE '{$inputPost["pessoa"]}'";
    }

    if ($inputPost["ano"] === 'ALL' || $inputPost["ano"] === null) {
        $anoWhere = "T.ANO IS NOT NULL";
    } else {
        $anoWhere = "T.ANO LIKE '{$inputPost["ano"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        // Query atualizada para remover a dependência de 'ORDEM' e colocar o CRO 'Brasil' no final
        $query = "SELECT
                    T.ANO,
                    T.CRO,
                    T.Pessoa,
                    T.[Quantidade de Fiscalizações],
                    T.[Fiscalizações ON-LINE],
                    T.[Fiscalizações PROATIVAS],
                    T.[Fiscalizações REATIVAS],
                    T.[Fiscalizações NÃO INFORMADO],
                    T.[Fiscalizações Exercício Ilegal],
                    T.[Notificações (indícios de irregularidades)],
                    T.[Fiscalizações com Termo]
                FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao AS T
                WHERE $croWhere AND $pessoaWhere AND $anoWhere
                ORDER BY
                    T.ANO,
                    CASE WHEN T.CRO = 'BRASIL' THEN 1 ELSE 0 END, -- Coloca Brasil no final
                    T.CRO;";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <button class="btn btn-secondary btn-md mr-1" value="1" onclick="showInput(1)">Tabela</button>
        <button class="btn btn-secondary btn-md mr-1" value="2" onclick="showInput(2)">PowerBI</button>
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<script type="text/javascript">
    // Função para alterar div
    function showInput(val) {
        if (val == 1) {
            document.getElementById('tableResult').style.display = 'block';
            document.getElementById('powerBI').style.display = 'none';
        } else if (val == 2) {
            document.getElementById('tableResult').style.display = 'none';
            document.getElementById('powerBI').style.display = 'block';
        } 
    }
</script>

<div id="tableResult" class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">Ano</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Pessoa</th>
                    <th scope="col">Quantidade de Fiscalizações</th>
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
                <?php foreach ($result as $row) { ?>
                    <tr>
                        <td><?= $row['ANO']; ?></td>
                        <td><?= ($row['CRO'] == 'BRASIL') ? 'Brasil' : $row['CRO']; ?></td>
                        <td><?= $row['Pessoa']; ?></td>
                        <td><?= $row['Quantidade de Fiscalizações']; ?></td>
                        <td><?= $row['Fiscalizações ON-LINE']; ?></td>
                        <td><?= $row['Fiscalizações PROATIVAS']; ?></td>
                        <td><?= $row['Fiscalizações REATIVAS']; ?></td>
                        <td><?= $row['Fiscalizações NÃO INFORMADO']; ?></td>
                        <td><?= $row['Fiscalizações Exercício Ilegal']; ?></td>
                        <td><?= $row['Notificações (indícios de irregularidades)']; ?></td>
                        <td><?= $row['Fiscalizações com Termo']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div id="powerBI" class="row mt-4" style="display:none;">
    <iframe width="100%" height="800" src="https://app.powerbi.com/view?r=link_do_power_bi"></iframe>
</div>

<?php } ?>

