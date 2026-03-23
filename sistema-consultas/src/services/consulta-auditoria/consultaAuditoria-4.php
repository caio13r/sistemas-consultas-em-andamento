<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

use PDO;
use PDOException;

Session::CheckSession();

if (Session::get('grupo') != 0 && (!isset($row['CA4acesso']) || $row['CA4acesso'] == false)) {
    echo "<script>window.alert('Você não tem permissão para acessar essa página.');window.location.href='consulta-auditoria';</script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais provisórios com validade expirada';
?>

<div class="col-md-6 offset-md-3 mb-4">
    <h6 class="mb-2">Consultar profissionais provisórios com validade expirada</h6>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                if (Session::get('grupo') === 0 || (isset($row['CA4select']) && $row['CA4select'] == true)) {
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

            $query = "SELECT CRO, Categoria, Inscricao, Nome, CPF, Tipo_Inscricao, Situacao, Detalhe,
                      Data_Colacao_Grau, Data_Conclusao, Validade_Provisoria, Data_Situacao_Registro_Atual
                      FROM CFO_CWS.dbo.Cons_Provisorios_Vencidos" . $whereClause;
            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            $queryError = true;
            error_log("Erro em auditoria 4: " . $error->getMessage());
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
            <table id="tabelaAuditoria4" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th scope="col">CRO</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Tipo de Inscrição</th>
                        <th scope="col">Inscrição</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Detalhe</th>
                        <th scope="col">CPF</th>
                        <th scope="col">Data Colação de Grau</th>
                        <th scope="col">Data Conclusão</th>
                        <th scope="col">Validade Provisória</th>
                        <th scope="col">Data Situação Registro Atual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $linha) { ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Categoria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Tipo_Inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Detalhe'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Data_Colacao_Grau'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($linha['Data_Conclusao'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($linha['Validade_Provisoria'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($linha['Data_Situacao_Registro_Atual'] ?? 'N/A') ?></td>
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
    $('#tabelaAuditoria4').DataTable({
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>
