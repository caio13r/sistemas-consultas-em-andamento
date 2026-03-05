<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA4acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais provisórios com validade expirada';
?>
<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar profissionais provisórios com validade expirada</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      if (Session::get('grupo') === 0 || $row['CA4select'] == true) {
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
    
        // Consulta com os novos campos e filtrando pelo estado
        $query = "SELECT CRO, Categoria, Inscricao, Nome, CPF, Tipo_Inscricao, Situacao, Detalhe, 
                  Data_Colacao_Grau, Data_Conclusao, Validade_Provisoria, Data_Situacao_Registro_Atual 
                  FROM CFO_CWS.dbo.Cons_Provisorios_Vencidos WHERE $croQuery";
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
        <table id="tabelaConsultas4" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Nome'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Tipo_Inscricao'] . "</td>";
                    echo "<td>" . $row['Inscricao'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . (!empty($row['Data_Colacao_Grau']) ? $row['Data_Colacao_Grau'] : 'N/A') . "</td>";
                    echo "<td>" . (!empty($row['Data_Conclusao']) ? $row['Data_Conclusao'] : 'N/A') . "</td>";
                    echo "<td>" . (!empty($row['Validade_Provisoria']) ? $row['Validade_Provisoria'] : 'N/A') . "</td>";
                    echo "<td>" . (!empty($row['Data_Situacao_Registro_Atual']) ? $row['Data_Situacao_Registro_Atual'] : 'N/A') . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>

<!-- Inicialização do DataTables com idioma Português -->
<script>
$(document).ready(function() {
    $('#tabelaConsultas4').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json" // Caminho local para o arquivo de tradução
        }
    });
});
</script>