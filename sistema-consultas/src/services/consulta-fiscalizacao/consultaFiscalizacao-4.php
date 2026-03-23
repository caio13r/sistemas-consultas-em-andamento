<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CF4acesso']) && $row['CF4acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas de Fiscalizações por Fiscal sem Inscrições';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Fiscalizações por Fiscal sem Inscrições</h6>
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
                      if (Session::get('grupo') === 0 || (isset($row['CF4select']) && $row['CF4select'] == true)) {
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
            <!-- Seleção de Tipo de Pessoa -->
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
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao_PorFiscal";
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
    $pessoaParam = null;
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

    if ($inputPost["pessoa"] === 'ALL' || $inputPost["pessoa"] === null) {
        $pessoaWhere = "Pessoa IS NOT NULL";
    } else {
        $validPessoa = ['PF SEM INSCRIÇÃO', 'PJ SEM INSCRIÇÃO'];
        if (!in_array($inputPost["pessoa"], $validPessoa)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Tipo de pessoa inválido.</div>";
            return;
        }
        $pessoaWhere = "Pessoa = :pessoa";
        $pessoaParam = $inputPost["pessoa"];
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
                    EPPF.ANO,
                    EPPF.CRO,
                    EPPF.Fiscal,
                    EPPF.Pessoa,
                    EPPF.[Quantidade de Fiscalizações],
                    EPPF.[Fiscalizações ON-LINE],
                    EPPF.[Fiscalizações PROATIVAS],
                    EPPF.[Fiscalizações REATIVAS],
                    EPPF.[Fiscalizações NÃO INFORMADO],
                    EPPF.[Fiscalizações Exercício Ilegal],
                    EPPF.[Sem Irregularidade(s)],
                    EPPF.[Notificações (indícios de irregularidades)]
                FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao_PorFiscal EPPF
                WHERE $croWhere AND $pessoaWhere AND $anoWhere
                ORDER BY 
                    EPPF.ANO,
                    CASE WHEN EPPF.CRO = 'BR' THEN 1 ELSE 0 END, -- Coloca o CRO BRASIL no final
                    EPPF.CRO,
                    EPPF.ORDEM,
                    EPPF.Fiscal,
                    EPPF.Pessoa;";
        $stmt = $con->prepare($query);
        if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
        if ($pessoaParam !== null) { $stmt->bindValue(':pessoa', $pessoaParam); }
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
                    <th scope="col">Fiscal</th>
                    <th scope="col">Pessoa</th>
                    <th scope="col">Quantidade de Fiscalizações</th>
                    <th scope="col">Fiscalizações ON-LINE</th>
                    <th scope="col">Fiscalizações PROATIVAS</th>
                    <th scope="col">Fiscalizações REATIVAS</th>
                    <th scope="col">Fiscalizações NÃO INFORMADO</th>
                    <th scope="col">Fiscalizações Exercício Ilegal</th>
                    <th scope="col">Sem Irregularidade(s)</th>
                    <th scope="col">Notificações (indícios de irregularidades)</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    // Substitui "BR" por "Brasil"
                    $croDisplay = ($row['CRO'] == 'BR') ? 'Brasil' : htmlspecialchars($row['CRO']);
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['ANO']) . "</td>";
                    echo "<td>" . $croDisplay . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Pessoa']) . "</td>";
                    echo "<td>" . number_format($row['Quantidade de Fiscalizações'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Fiscalizações ON-LINE'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Fiscalizações PROATIVAS'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Fiscalizações REATIVAS'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['Fiscalizações NÃO INFORMADO'], 0, ',', '.') . "</td>";   
                    echo "<td>" . number_format($row['Fiscalizações Exercício Ilegal'], 0, ',', '.') . "</td>";   
                    echo "<td>" . number_format($row['Sem Irregularidade(s)'], 0, ',', '.') . "</td>";   
                    echo "<td>" . number_format($row['Notificações (indícios de irregularidades)'], 0, ',', '.') . "</td>";   
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<div id="powerBI" class="row" style="display:none">
    <div class="col mt-5">
        <iframe title="teste_01" width="100%" height="700" src="https://app.powerbi.com/view?r=eyJrIjoiYzE0M2FhYmYtNWRlMi00NmEzLWFiMGYtYzNjZjJhOTM0MjcwIiwidCI6ImVjMzU5YmExLTYzMGItNGQyYi1iODMzLWM4ZTZkNDhmODA1OSJ9&pageName=ReportSection" frameborder="0" allowFullScreen="true"></iframe>
    </div>
</div>

<?php } ?>
