<?php
use PDO;
use PDOException;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (!isset($row['CA15acesso']) || $row['CA15acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - CPF Duplicado na Mesma Categoria';
$erro = '';
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar profissionais com CPF repetido na mesma categoria profissional</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      if (Session::get('grupo') === 0 || (isset($row['CA15select']) && $row['CA15select'] == true)) {
                        foreach(Helper::$ufList as $val => $value) {
                            $selected = (($inputPost['cro'] ?? '') == $val) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                        }
                      } else {
                        foreach(Helper::$ufList as $val => $value) {
                          if ($users->CheckGroupUf() == $val) {
                            $selected = (($inputPost['cro'] ?? '') == $val) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                          }
                        }
                      }
                    ?>
                </select>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php if (isset($inputPost["submit"])) {

    $conditions = [];
    $params = [];

    if (!empty($inputPost['cro']) && $inputPost['cro'] !== 'ALL') {
        if (!array_key_exists($inputPost['cro'], Helper::$ufList)) {
            $erro = "Estado inválido.";
        } else {
            $conditions[] = "CRO = :cro";
            $params[':cro'] = $inputPost['cro'];
        }
    }

    if (!empty($erro)) {
        echo "<div class='alert alert-danger mt-3'><strong>Erro!</strong> " . htmlspecialchars($erro) . "</div>";
        $result = [];
    } else {
        $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();

            $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_Profissional_CPF_Duplicado_uma_Categoria"
                      . $whereClause .
                      " ORDER BY CPF";
            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log("Erro em auditoria 15: " . $error->getMessage());
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
        <table id="tabelaAuditoria15" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Nome</th>
                <th scope="col">CPF</th>
                <th scope="col">Repetições</th>
                <th scope="col">Tipo de Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $linha) : ?>
                <tr>
                    <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Inscrição'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Repetições'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Tipo inscrição'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Situação'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria15').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[4, "asc"]],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>

<?php } ?>
