<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

use PDO;
use PDOException;

Session::CheckSession();

if (Session::get('grupo') != 0 && (!isset($row['CA5acesso']) || $row['CA5acesso'] == false)) {
    echo "<script>window.alert('Você não tem permissão para acessar essa página.');window.location.href='consulta-auditoria';</script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais com inscrição ativa em mais de um cro';
?>

<div class="col-md-6 offset-md-3 mb-4">
    <h6 class="mb-2">Consultar profissionais com inscrição ativa em mais de um cro</h6>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                if (Session::get('grupo') === 0 || (isset($row['CA5select']) && $row['CA5select'] == true)) {
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
            $conditions[] = "CRO_1 = :cro";
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
            if (method_exists($db3, 'isConnected') && !$db3->isConnected()) {
                throw new PDOException("SQL Server indisponível.");
            }
            $con = $db3->getConnection();

            $query = "SELECT top 1000
                NOME_1 AS Nome1,
                CPF AS CPF,
                CRO_1 AS CRO1,
                CATE_1 AS CATEGORIA_1,
                INSC_1 AS INSCRICAO_1,
                TIPO_INSCRICAO_1 AS TIPO_INSCRICAO_1,
                SITUACAO_1 AS SITUACAO_1,
                DETALHE_1 AS DETALHE_1,
                DATA_INSC_1 AS DATA_INSCRICAO_CRO_1,
                DATA_SITUAÇÃO_ATUAL_1 AS SITUACAO_REGISTRO_ATUAL_1,
                NOME_2 AS Nome2,
                CRO_2 AS CRO2,
                CATE_2 AS CATEGORIA_2,
                INSC_2 AS INSCRICAO_2,
                TIPO_INSCRICAO_2 as TIPO_INSCRICAO_2,
                SITUACAO_2 AS SITUACAO_2,
                DETALHE_2 AS DETALHE_2,
                DATA_INSC_2 AS DATA_INSCRICAO_CRO_2,
                DATA_SITUAÇÃO_ATUAL_2 AS SITUACAO_REGISTRO_ATUAL_2
                FROM CFO_CWS.dbo.vw_Cons_Inscricao_Principal_Em_Mais_De_Um_CRO" . $whereClause;
            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            $queryError = true;
            error_log("Erro em auditoria 5: " . $error->getMessage());
            echo "<div class='alert alert-danger mt-3'>Serviço indisponível. Não foi possível conectar ao servidor de dados. Verifique se você está na rede interna do CFO ou se o túnel VPN está ativo.</div>";
        }

        if (!empty($result)) {
            Session::set('excel_download_data', $result);
            Session::set('excel_download_title', $tituloConsulta);
?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <div class="row mt-4">
        <div class="col table-responsive">
            <table id="tabelaAuditoria5" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th scope="col">Nome 1 </th>
                        <th scope="col">CPF 1</th>
                        <th scope="col">CRO 1</th>
                        <th scope="col">Categoria 1</th>
                        <th scope="col">Inscrição 1</th>
                        <th scope="col">Tipo Inscrição 1</th>
                        <th scope="col">Situacão 1</th>
                        <th scope="col">Detalhe 1</th>
                        <th scope="col">Data Inscrição CRO 1</th>
                        <th scope="col">Situação Registro Atual 1</th>
                        <th scope="col">Nome 2</th>
                        <th scope="col">CPF 2</th>
                        <th scope="col">CRO 2</th>
                        <th scope="col">Categoria 2</th>
                        <th scope="col">Inscrição 2</th>
                        <th scope="col">Tipo Inscrição 2</th>
                        <th scope="col">Situacão 2</th>
                        <th scope="col">Detalhe 2</th>
                        <th scope="col">Data Inscrição CRO 2</th>
                        <th scope="col">Situação Registro Atual 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $linha) { ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['Nome1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CRO1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CATEGORIA_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['INSCRICAO_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['TIPO_INSCRICAO_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['SITUACAO_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['DETALHE_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['DATA_INSCRICAO_CRO_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['SITUACAO_REGISTRO_ATUAL_1'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['Nome2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CRO2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['CATEGORIA_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['INSCRICAO_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['TIPO_INSCRICAO_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['SITUACAO_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['DETALHE_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['DATA_INSCRICAO_CRO_2'] ?? '') ?></td>
                            <td><?= htmlspecialchars($linha['SITUACAO_REGISTRO_ATUAL_2'] ?? '') ?></td>
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
    $('#tabelaAuditoria5').DataTable({
        "deferRender": true,
        "processing": true,
        "searchDelay": 300,
        "autoWidth": false,
        "order": [],
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>
