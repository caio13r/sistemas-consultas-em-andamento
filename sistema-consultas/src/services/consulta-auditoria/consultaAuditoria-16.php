<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA16acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = "Auditoria - Profissionais com idade inferior ou igual a 20 anos";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar profissionais com idade inferior ou igual a 20 anos</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA16select'] == true) {
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
            <div class="form-group col-md-6">
                <label for="idade">Selecione a Idade:</label>
                <select id="idade" name="idade" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                        try {
                            $con = Database3::getInstance()->getConnection();        
                            $query = "SELECT DISTINCT Idade FROM CFO_CWS.dbo.vw_Cons_Profissionais_idade_inferior_20 WHERE Idade IS NOT NULL ORDER BY Idade ASC";
                            $stmt = $con->prepare($query);
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOexception $error) {
                            die("Erro ao retornar os dados: " . $error->getMessage());
                        }
                        foreach($result as $row) {
                            $selected = (!empty($inputPost['idade']) && $inputPost['idade'] == $row['Idade']) ? 'selected' : '';
                            echo "<option value='{$row['Idade']}' $selected>{$row['Idade']}</option>";
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

        if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
            $croQuery = "CRO IS NOT NULL";
        } else {
            $croQuery = "CRO = '{$inputPost["cro"]}'";
        }

        if ($inputPost["idade"] === 'ALL' || $inputPost["idade"] === null) {
            $idadeQuery = "Idade IS NOT NULL";
        } else {
            $idadeQuery = "Idade <= '{$inputPost["idade"]}'";
        }

        try {
            $con = Database3::getInstance()->getConnection();        
            $query = "SELECT 
                        CRO,
                        Categoria,
                        Inscrição,
                        Nome,
                        CPF,
                        [Tipo Inscrição] ,
                        Situação ,
                        Detalhe,
                        Idade,
                        [Data de nascimento]
                      FROM CFO_CWS.dbo.vw_Cons_Profissionais_idade_inferior_20 
                      WHERE $croQuery AND $idadeQuery
                      ORDER BY [Data de nascimento] ASC";  // Ordenação por Data de Nascimento
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
        <table id="tabelaConsultas16" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Nome</th>
                <th scope="col">CPF</th>
                <th scope="col">Tipo Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
                <th scope="col">Idade</th>
                <th scope="col">Data de Nascimento</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Inscrição'] . "</td>";
                    echo "<td>" . $row['Nome'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['Tipo Inscrição'] . "</td>";
                    echo "<td>" . $row['Situação'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['Idade'] . "</td>";
                    echo "<td>" . $row['Data de nascimento'] . "</td>";
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
    $('#tabelaConsultas16').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[9, "asc"]],  // Ordena pela coluna "Data de Nascimento"
        "language": {
            "url": "../assets/lang/pt-BR.json" // Verifique se o caminho para o arquivo de idioma está correto
        }
    });
});
</script>

<?php } ?>
