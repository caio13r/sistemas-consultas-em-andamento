<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA2acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais e empresas em atividade com cpf ou cnpj inválidos';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Consultar profissionais e empresas em atividade com cpf ou cnpj inválidos</label>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                      if (Session::get('grupo') === 0 || $row['CA2select'] == true) {
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
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL') {
        $croTable = '';
    } else {
        $croTable = "WHERE CRO = '{$inputPost["cro"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        
        // Consulta limitada a 100 registros para exibição
        $query = "SELECT TOP 500 CRO, Categoria, Inscricao, [Nome/Razao_Social] as NomeRazao_Social, [CPF/CNPJ] as CPFCNPJ, 
                  [Tipo_Inscricao], Situacao, Detalhe, Nome_Fantasia, Natureza_Juridica, Data_Fundacao, Nome_Mae, Data_Nascimento 
                  FROM CFO_CWS.dbo.vw_Rel_ativo_CPF_CNPJ_invalido $croTable";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Consulta completa para exportação de todos os dados para Excel
        $queryAll = "SELECT CRO, Categoria, Inscricao, [Nome/Razao_Social] as NomeRazao_Social, [CPF/CNPJ] as CPFCNPJ, 
                     [Tipo_Inscricao], Situacao, Detalhe, Nome_Fantasia, Natureza_Juridica, Data_Fundacao, Nome_Mae, Data_Nascimento 
                     FROM CFO_CWS.dbo.vw_Rel_ativo_CPF_CNPJ_invalido $croTable";
        $stmtAll = $con->prepare($queryAll);
        $stmtAll->execute();
        $resultAll = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($resultAll)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas2" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Nome/Razao Social</th>
                <th scope="col">Categoria</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Situação</th>
                <th width="15%" scope="col">CPFCNPJ</th>
                <th scope="col">Tipo de Inscrição</th>
                <th scope="col">Detalhe</th>
                <th scope="col">Nome Fantasia</th>
                <th scope="col">Natureza Jurídica</th>
                <th scope="col">Data de Fundação</th>
                <th scope="col">Nome da Mãe</th>
                <th scope="col">Data de Nascimento</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['NomeRazao_Social'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Inscricao'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['CPFCNPJ'] . "</td>";
                    echo "<td>" . $row['Tipo_Inscricao'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['Nome_Fantasia'] . "</td>";
                    echo "<td>" . $row['Natureza_Juridica'] . "</td>";
                    echo "<td>" . $row['Data_Fundacao'] . "</td>";
                    echo "<td>" . $row['Nome_Mae'] . "</td>";
                    echo "<td>" . $row['Data_Nascimento'] . "</td>";
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
    $('#tabelaConsultas2').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json" // URL para o arquivo de tradução para Português
        }
    });
});
</script>

<?php } ?>
