<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA14acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Usuários do Sistema Implanta';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar usuários do Sistema Implanta</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA14select'] == true) {
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
                <label for="situacao">Selecione a Situação:</label>
                <select id="situacao" name="situacao" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 'Ativo' => 'Ativo', 'Inativo' => 'Inativo');
                        foreach($values as $val => $value) {
                            $selected = (!empty($inputPost['situacao']) && $inputPost['situacao'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
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
                            $selected = (!empty($inputPost['bloqueio']) && $inputPost['bloqueio'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="nome">Informe o Nome/Login:</label>
                <input type="text" id="nome" name="nome" class="form-control" minlength="3" placeholder="Digite o nome" value="<?= $inputPost["nome"] ?>"></input>
              </div>
            <div class="form-group col-md-6">
                <label for="cpf">Informe o CPF:</label>
                <input type="text" id="cpf" name="cpf" onkeyup="mask('###.###.###-##', this, event, true)"  class="form-control" placeholder="Digite o CPF" value="<?= $inputPost["cpf"] ?>"></input>
            </div>
            <div class="form-group col-md-12">
                <label for="email">Informe o E-mail:</label>
                <input type="text" id="email" name="email" class="form-control" minlength="5" placeholder="Digite o e-mail" value="<?= $inputPost["email"] ?>">
            </div>
            
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croQuery = 'CRO IS NOT NULL';
    } else {
        $croQuery = "CRO = '{$inputPost["cro"]}'";
    }

    if ($inputPost["situacao"] === 'ALL' || $inputPost["situacao"] === null) {
        $sitQuery = 'situacao IS NOT NULL';
    } else {
        $sitQuery = "situacao = '{$inputPost["situacao"]}'";
    }

    if ($inputPost["bloqueio"] === 'ALL' || $inputPost["bloqueio"] === null) {
        $bloqQuery = 'Bloqueado IS NOT NULL';
    } else {
        $bloqQuery = "Bloqueado = '{$inputPost["bloqueio"]}'";
    }

    if (empty($inputPost["nome"])) {
        $nomeQuery = 'Nome IS NOT NULL';
    } else {
        // Busca tanto em Nome quanto em Login
        $nome = $inputPost["nome"];
        $nomeQuery = "(Nome LIKE '%$nome%' OR Login LIKE '%$nome%')";
    }

    if (empty($inputPost["cpf"])) {
        $cpfQuery = 'CPF IS NOT NULL';
    } else {
        $cpfQuery = "CPF LIKE '%{$inputPost["cpf"]}%'"; // Aceitar qualquer parte do CPF
    }

    // Filtro por e-mail
    if (empty($inputPost["email"])) {
        $emailQuery = "[E-mail] IS NOT NULL";
    } else {
        $email = $inputPost["email"];
        $emailQuery = "[E-mail] LIKE '%$email%'";
    }

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
                        [Admin]  -- Adicionada a coluna Admin
                    FROM [CFO_CWS].[dbo].[vw_Cons_Usuarios_Ativos_Inativos] 
                    WHERE $croQuery AND $sitQuery AND $bloqQuery AND $nomeQuery AND $cpfQuery AND $emailQuery";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
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

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas14" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
                <th scope="col">ADMIN</th> <!-- Coluna Admin adicionada -->
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Nome'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['Login'] . "</td>";
                    echo "<td>" . $row['E-mail'] . "</td>";
                    echo "<td>" . $row['Data de criação'] . "</td>";
                    echo "<td>" . $row['Grupos'] . "</td>";
                    echo "<td>" . $row['Unidades'] . "</td>";
                    echo "<td>" . $row['Bloqueado'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['Admin'] . "</td>"; // Exibindo o valor da coluna Admin
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<!-- Inicialização do DataTables com idioma Português -->
<script>
$(document).ready(function() {
    $('#tabelaConsultas14').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json" // Verifique se o caminho para o arquivo de idioma está correto
        }
    });
});
</script>

<?php } ?>
