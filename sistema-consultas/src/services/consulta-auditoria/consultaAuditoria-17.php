<?php
use PDO;
use PDOException;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && isset($row['CA16acesso']) && $row['CA16acesso'] == false) {
    echo "<script>
    window.alert('Você não tem permissão para acessar essa página.');
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = "Consulta - Profissionais com Nome Social";
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar profissionais com Nome Social</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      if (Session::get('grupo') === 0 || (isset($row['CA16select']) && $row['CA16select'] == true)) {
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
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Gerar</button>
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
                $conditions[] = "CRO = :cro";
                $params[':cro'] = $inputPost['cro'];
            }
        }

        $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $result = [];

        if (empty($erro)) {
            try {
                $con = Database3::getInstance()->getConnection();
                $query = "SELECT
                            CRO,
                            Categoria,
                            Inscricao,
                            Nome,
                            Nome_Social,
                            CPF,
                            [Tipo_Inscricao] ,
                            Situacao,
                            Detalhe
                          FROM CFO_CWS.dbo.vw_Cons_Profissional_Com_Nome_Social
                          $whereClause";
                $stmt = $con->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $error) {
                error_log("Erro em auditoria 17: " . $error->getMessage());
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
        <table id="tabelaAuditoria17" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Nome</th>
                <th scope="col">Nome Social</th>
                <th scope="col">CPF</th>
                <th scope="col">Tipo Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $linha) : ?>
                <tr>
                    <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome_Social'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Tipo_Inscricao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria17').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>

<?php } ?>
