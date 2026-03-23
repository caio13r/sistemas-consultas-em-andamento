<?php
use PDO;
use PDOException;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && isset($row['CA23acesso']) && $row['CA23acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais e Empresas com Multiplos Registros';
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Profissionais e Empresas com Multiplos Registros</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        if (Session::get('grupo') === 0 || (isset($row['CA23select']) && $row['CA23select'] == true)) {
                            foreach(Helper::$ufList as $val => $value) {
                                $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                            }
                        } else {
                            foreach(Helper::$ufList as $val => $value) {
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
                        foreach(Helper::$catList as $val => $value) {
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

<?php if (isset($inputPost["submit"])) {

    $erro = '';
    $conditions = [];
    $params = [];

    if (!empty($inputPost['cro']) && $inputPost['cro'] !== 'ALL') {
        if (!array_key_exists($inputPost['cro'], Helper::$ufList)) {
            $erro = "Estado inválido.";
        } else {
            $conditions[] = "MR.[CRO] = :cro";
            $params[':cro'] = $inputPost['cro'];
        }
    }

    if (!empty($inputPost['categoria']) && $inputPost['categoria'] !== 'ALL') {
        if (!array_key_exists($inputPost['categoria'], Helper::$catList)) {
            $erro = "Categoria inválida.";
        } else {
            $conditions[] = "MR.[Categoria] = :cat";
            $params[':cat'] = $inputPost['categoria'];
        }
    }

    $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

    if (!empty($erro)) {
        echo "<div class='alert alert-danger mt-3'><strong>Erro!</strong> " . htmlspecialchars($erro) . "</div>";
        $result = [];
    } else {
        try {
            $db3 = Database3::getInstance();
            $con = $db3->getConnection();

            $query = "
                SELECT
                    MR.[CRO] AS CRO,
                    MR.[Categoria] AS Categoria,
                    MR.[Inscricao] AS Inscricao,
                    MR.[Nome_Razao_social] AS Nome_Razao_social,
                    MR.[CPF_CNPJ] AS CPF_CNPJ,
                    MR.[Tipo_Inscricao] AS Tipo_Inscricao,
                    MR.[Situacao] AS Situacao,
                    MR.[Detalhe] AS Detalhe
                FROM [CFO_CWS].[dbo].[vw_Cons_Multiplos_Registros] AS MR
                {$whereClause}
                ORDER BY
                    MR.[CRO],
                    MR.[Nome_Razao_social],
                    MR.[Categoria]
            ";

            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log("Erro em auditoria 23: " . $error->getMessage());
            echo "<div class='alert alert-danger mt-3'><strong>Erro!</strong> Não foi possível realizar a consulta.</div>";
            $result = [];
        }
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
        <table id="tabelaAuditoria23" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
                <tr>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Nome Razão Social</th>
                    <th scope="col">CPF/CNPJ</th>
                    <th scope="col">Tipo Inscrição</th>
                    <th scope="col">Situacão</th>
                    <th scope="col">Detalhe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $linha) { ?>
                <tr>
                    <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome_Razao_social'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['CPF_CNPJ'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Tipo_Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php } ?>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria23').DataTable({
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>
