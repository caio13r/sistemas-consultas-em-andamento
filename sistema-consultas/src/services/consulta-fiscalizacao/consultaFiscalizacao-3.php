<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CF3acesso']) && $row['CF3acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Fiscalizações - Estatísticas de Fiscalizações por Fiscal';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Estatísticas de Fiscalizações por Fiscal - Categoria x Ano</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CF3select']) && $row['CF3select'] == true)) {
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
                <label for="ano">Ano:</label>
                <select id="ano" name="ano" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <?php
                        $db = Database3::getInstance();
                        $con = $db->getConnection();

                        try {
                            $query = "SELECT DISTINCT ANO FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorFiscal";
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

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    EFPF.ANO,
                    EFPF.CRO,
                    EFPF.Fiscal,
                    EFPF.Categoria,
                    EFPF.[Total de Ativos],
                    EFPF.[Quantidade de Fiscalizações],
                    EFPF.[Percentual Fiscalizações],
                    EFPF.[Fiscalizações ON-LINE],
                    EFPF.[Fiscalizações PROATIVAS],
                    EFPF.[Fiscalizações REATIVAS],
                    EFPF.[Fiscalizações NÃO INFORMADO],
                    EFPF.[Fiscalizações Exercício Ilegal],
                    EFPF.[Sem Irregularidade(s)],
                    EFPF.[Notificações (indícios de irregularidades)]
                FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorFiscal AS EFPF
                WHERE $croWhere AND $catWhere AND $anoWhere
                ORDER BY
                    EFPF.ANO,
                    EFPF.CRO,
                    EFPF.Fiscal,
                    EFPF.Categoria;";
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

<div id="tableResult" class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">Ano</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Fiscal</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Total de Ativos</th>
                    <th scope="col">Quantidade de Fiscalizações</th>
                    <th scope="col">Percentual Fiscalizações</th>
                    <th scope="col">Fiscalizações ON-LINE</th>
                    <th scope="col">Fiscalizações PROATIVAS</th>
                    <th scope="col">Fiscalizações REATIVAS</th>
                    <th scope="col">Fiscalizações NÃO INFORMADOS</th>
                    <th scope="col">Fiscalizações Exercício Ilegal</th>
                    <th scope="col">Sem Irregularidade(s)</th>
                    <th scope="col">Notificações (indícios de irregularidades)</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['ANO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Categoria']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Total de Ativos']) . "</td>";
                    echo "<td>" . number_format($row['Quantidade de Fiscalizações'], 0, ',', '.') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Percentual Fiscalizações']) . "</td>";
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
<script>
    $(document).ready(function() {
        $('#tabelaConsultas').DataTable({
            "paging": true,
            "lengthMenu": [10, 25, 50, 100],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Portuguese-Brasil.json"
            }
        });
    });
</script>
<?php } ?>
