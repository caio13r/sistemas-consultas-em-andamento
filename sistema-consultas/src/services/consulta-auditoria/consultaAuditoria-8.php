<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA8acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Empresas filiais ativas sem a respectiva matriz';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar empresas filiais ativas sem a respectiva matriz</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA8select'] == true) {
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

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_Filial_Sem_Matriz WHERE $croQuery";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        // echo $query;
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
        <table id="tabelaConsultas8" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Razão Social</th>
                <th scope="col">CNPJ</th>
                <th scope="col">Tipo Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
                <th scope="col">Tem matriz?</th>
                <th scope="col">RT Categoria</th>
                <th scope="col">RT Inscrição</th>
                <th scope="col">RT Nome</th>
                <th scope="col">RT CPF</th>
                <th scope="col">RT Tipo Inscrição</th>
                <th scope="col">RT Situação</th>
                <th scope="col">RT Detalhe</th>
                <th scope="col">RT Encerrado?</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Inscrição'] . "</td>";
                    echo "<td>" . $row['Razão Social'] . "</td>";
                    echo "<td>" . $row['CNPJ'] . "</td>";
                    echo "<td>" . $row['Tipo Inscrição'] . "</td>";
                    echo "<td>" . $row['Situação'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['Tem Matriz?'] . "</td>";
                    echo "<td>" . $row['RT Categoria'] . "</td>";
                    echo "<td>" . $row['RT Inscrição'] . "</td>";
                    echo "<td>" . $row['RT Nome'] . "</td>";
                    echo "<td>" . $row['RT CPF'] . "</td>";
                    echo "<td>" . $row['RT Tipo Inscrição'] . "</td>";
                    echo "<td>" . $row['RT Situação'] . "</td>";
                    echo "<td>" . $row['RT Detalhe'] . "</td>";
                    echo "<td>" . $row['RT Encerrado?'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>
<script>
$(document).ready(function() {
    $('#tabelaConsultas8').DataTable({
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