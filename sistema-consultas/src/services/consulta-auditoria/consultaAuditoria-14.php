<?php
use PDO;
use PDOException;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (!isset($row['CA14acesso']) || $row['CA14acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Usuários do Sistema Implanta';
$erro = '';
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar usuários do Sistema Implanta</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                      if (Session::get('grupo') === 0 || (isset($row['CA14select']) && $row['CA14select'] == true)) {
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
            <div class="form-group col-md-4">
                <label for="situacao">Selecione a Situação:</label>
                <select id="situacao" name="situacao" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 'Ativo' => 'Ativo', 'Inativo' => 'Inativo');
                        foreach($values as $val => $value) {
                            $selected = (($inputPost['situacao'] ?? '') == $val) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="bloqueio">Selecione o Bloqueio:</label>
                <select id="bloqueio" name="bloqueio" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 'Sim' => 'Sim', 'Não' => 'Não');
                        foreach($values as $val => $value) {
                            $selected = (($inputPost['bloqueio'] ?? '') == $val) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($val) . "' $selected>" . htmlspecialchars($value) . "</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="nome">Informe o Nome/Login:</label>
                <input type="text" id="nome" name="nome" class="form-control" minlength="3" placeholder="Digite o nome" value="<?= htmlspecialchars($inputPost['nome'] ?? '') ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="cpf">Informe o CPF:</label>
                <input type="text" id="cpf" name="cpf" onkeyup="mask('###.###.###-##', this, event, true)" class="form-control" placeholder="Digite o CPF" value="<?= htmlspecialchars($inputPost['cpf'] ?? '') ?>">
            </div>
            <div class="form-group col-md-12">
                <label for="email">Informe o E-mail:</label>
                <input type="text" id="email" name="email" class="form-control" minlength="5" placeholder="Digite o e-mail" value="<?= htmlspecialchars($inputPost['email'] ?? '') ?>">
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

    if (!empty($inputPost['situacao']) && $inputPost['situacao'] !== 'ALL') {
        $conditions[] = "situacao = :situacao";
        $params[':situacao'] = $inputPost['situacao'];
    }

    if (!empty($inputPost['bloqueio']) && $inputPost['bloqueio'] !== 'ALL') {
        $conditions[] = "Bloqueado = :bloqueio";
        $params[':bloqueio'] = $inputPost['bloqueio'];
    }

    if (!empty($inputPost['nome'])) {
        $conditions[] = "(Nome LIKE :nome OR Login LIKE :nome2)";
        $params[':nome'] = '%' . $inputPost['nome'] . '%';
        $params[':nome2'] = '%' . $inputPost['nome'] . '%';
    }

    if (!empty($inputPost['cpf'])) {
        $conditions[] = "CPF LIKE :cpf";
        $params[':cpf'] = '%' . $inputPost['cpf'] . '%';
    }

    if (!empty($inputPost['email'])) {
        $conditions[] = "[E-mail] LIKE :email";
        $params[':email'] = '%' . $inputPost['email'] . '%';
    }

    if (!empty($erro)) {
        echo "<div class='alert alert-danger mt-3'><strong>Erro!</strong> " . htmlspecialchars($erro) . "</div>";
        $result = [];
    } else {
        $whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();

            $query = "SELECT [CRO],
                        [Nome],
                        [CPF],
                        [Login],
                        [E-mail],
                        [Data de criação],
                        [Grupo] as Grupos,
                        [Unidade] as Unidades,
                        [Bloqueado],
                        [situacao] as Situacao,
                        [Admin]
                    FROM [CFO_CWS].[dbo].[vw_Cons_Usuarios_Ativos_Inativos]"
                    . $whereClause;
            $stmt = $con->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
            error_log("Erro em auditoria 14: " . $error->getMessage());
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
        <table id="tabelaAuditoria14" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">NOME</th>
                <th scope="col">CPF</th>
                <th scope="col">LOGIN</th>
                <th scope="col">EMAIL</th>
                <th scope="col">DATA DE CRIAÇÃO</th>
                <th scope="col">GRUPOS</th>
                <th scope="col">UNIDADES</th>
                <th scope="col">BLOQUEIO</th>
                <th scope="col">SITUAÇÃO</th>
                <th scope="col">ADMIN</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $linha) : ?>
                <tr>
                    <td><?= htmlspecialchars($linha['CRO'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['CPF'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Login'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['E-mail'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Data de criação'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Grupos'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Unidades'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Bloqueado'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Situacao'] ?? '') ?></td>
                    <td><?= htmlspecialchars($linha['Admin'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelaAuditoria14').DataTable({
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
