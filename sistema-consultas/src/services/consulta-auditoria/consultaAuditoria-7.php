<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

use PDO;
use PDOException;

Session::CheckSession();

if (Session::get('grupo') != 0 && (!isset($row['CA7acesso']) || $row['CA7acesso'] == false)) {
    echo "<script>window.alert('Você não tem permissão para acessar essa página.');window.location.href='consulta-auditoria';</script>";
    exit;
}

$tituloConsulta = 'Auditoria - Empresas sem responsável técnico';
?>

<div class="col-md-6 offset-md-3 mb-4">
    <h6 class="mb-2">Consultar empresas ativas sem responsável técnico</h6>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                if (Session::get('grupo') === 0 || (isset($row['CA7select']) && $row['CA7select'] == true)) {
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
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php
if (isset($inputPost['submit'])) {

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

    if (isset($erro)) {
        echo "<div class='alert alert-warning mt-3'><strong>Atenção!</strong> " . htmlspecialchars($erro) . "</div>";
    } else {

        $result = [];
        $queryError = false;
        try {
            $db3 = Database3::getInstance();
            if (!$db3->isConnected()) { throw new PDOException("SQL Server indisponível."); }
            $con = $db3->getConnection();

            $query = "SELECT * FROM CFO_CWS.dbo.Cons_Empresa_Ativa_Sem_RT" . $whereClause;
            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            $queryError = true;
            error_log("Erro em auditoria 7: " . $error->getMessage());
            echo "<div class='alert alert-danger mt-3'>Serviço indisponível. Não foi possível conectar ao servidor de dados. Verifique se você está na rede interna do CFO ou se o túnel VPN está ativo.</div>";
        }

        if (!empty($result)) {
?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)) ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <div class="row mt-4">
        <div class="col table-responsive">
            <table id="tabelaAuditoria7" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th scope="col">CRO</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Inscrição</th>
                        <th scope="col">Razão Social </th>
                        <th scope="col">CNPJ</th>
                        <th scope="col">Tipo Inscrição</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Detalhe</th>
                        <th scope="col">RT Categoria</th>
                        <th scope="col">RT Inscrição</th>
                        <th scope="col">RT Nome</th>
                        <th scope="col">RT CPF</th>
                        <th scope="col">RT Situação</th>
                        <th scope="col">RT Detalhe</th>
                        <th scope="col">RT Encerrado?</th>
                        <th scope="col">RT Data de Encerramento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $linha) { ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Razao_Social'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CNPJ'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Tipo_Inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Categoria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_CPF'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Situacao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Detalhe'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Encerrado'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['RT_Data_Encerramento'] ?? '') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
        } elseif (!$queryError) {
            echo "<div class='alert alert-info mt-3'>Não foram encontrados resultados.</div>";
        }
    }
}
?>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria7').DataTable({
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>
