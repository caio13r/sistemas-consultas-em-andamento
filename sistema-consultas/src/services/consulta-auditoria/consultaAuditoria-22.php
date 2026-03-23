<?php
use PDO;
use PDOException;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && isset($row['CA22acesso']) && $row['CA22acesso'] == false) {
    echo "<script>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais ativos com idade para REMISSÃO';
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Profissionais ativos com idade para REMISSÃO</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        if (Session::get('grupo') === 0 || (isset($row['CA22select']) && $row['CA22select'] == true)) {
                            foreach (Helper::$ufList as $val => $value) {
                                $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                            }
                        } else {
                            foreach (Helper::$ufList as $val => $value) {
                                if ($users->CheckGroupUf() == $val) {
                                    $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                                }
                            }
                        }
                    ?>
                </select>
            </div><br>
            <div class="form-group col-md-6">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                        foreach (Helper::$catList as $val => $value) {
                            $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                        }
                    ?>
                </select>
            </div><br>
            <div class="form-group col-md-3">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php
    if (isset($inputPost["submit"])) {
        $erro = '';
        $conditions = [];
        $params = [];

        if (!empty($inputPost['cro']) && $inputPost['cro'] !== 'ALL') {
            if (!array_key_exists($inputPost['cro'], Helper::$ufList)) {
                $erro = "Estado inválido.";
            } else {
                $conditions[] = "PIR.[CRO] = :cro";
                $params[':cro'] = $inputPost['cro'];
            }
        }

        if (!empty($inputPost['categoria']) && $inputPost['categoria'] !== 'ALL') {
            $conditions[] = "PIR.[Categoria] = :cat";
            $params[':cat'] = $inputPost['categoria'];
        }

        $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $result = [];

        if (empty($erro)) {
            try {
                $con = Database3::getInstance()->getConnection();
                $query = "
                    SELECT
                        PIR.[CRO] as CRO,
                        PIR.[Categoria] as Categoria,
                        PIR.[Inscricao] as Inscricao,
                        PIR.[Nome] as Nome,
                        PIR.[CPF] as CPF,
                        PIR.[Tipo_Inscricao] as Tipo_Inscricao,
                        PIR.[Situacao] as Situacao,
                        PIR.[Detalhe] as Detalhe,
                        PIR.[Adimplente] as Adimplente,
                        PIR.[Idade] as Idade,
                        PIR.[Data_Nascimento] as Data_Nascimento
                    FROM
                        [CFO_CWS].[dbo].[vw_Cons_Profissionais_Com_Idade_Remissao] AS PIR
                    $whereClause
                    ORDER BY
                        PIR.[CRO], PIR.[Idade] ASC
                ";
                $stmt = $con->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro em auditoria 22: " . $error->getMessage());
                echo "<div class='alert alert-danger mt-3'><strong>Erro!</strong> Não foi possível realizar a consulta.</div>";
                $result = [];
            }
        } else {
            echo "<div class='alert alert-warning mt-3'><strong>Atenção!</strong> " . htmlspecialchars($erro) . "</div>";
        }
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)) ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaAuditoria22" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">Tipo Inscrição</th>
                    <th scope="col">Situacão</th>
                    <th scope="col">Detalhe</th>
                    <th scope="col">Adimplente</th>
                    <th scope="col">Idade</th>
                    <th scope="col">Data Nascimento</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $linha) : ?>
                <tr>
                    <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Tipo_Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Adimplente'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Idade'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Data_Nascimento'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria22').DataTable({
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>

<?php } ?>
