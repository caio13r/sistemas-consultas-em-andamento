<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA9acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Responsável técnico cadastrado em mais de uma empresa ativa';
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Consultar responsável técnico cadastrado em mais de uma empresa ativa</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA9select'] == true) {
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
    
        $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_RT_Em_Mais_De_Uma_Empresa WHERE $croQuery";
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
        <table id="tabelaAuditoria9" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
                <th scope="col">PJ CRO</th>
                <th scope="col">PJ Categoria</th>
                <th scope="col">PJ Inscrição</th>
                <th scope="col">PJ Razão Social</th>
                <th scope="col">PJ CNPJ</th>
                <th scope="col">PJ Tipo Inscrição</th>
                <th scope="col">PJ Situação</th>
                <th scope="col">PJ Detalhe</th>
                <th scope="col">PJ Natureza Jurídica</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Inscricao'] . "</td>";
                    echo "<td>" . $row['Nome'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['Tipo_Inscricao'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['PJ_CRO'] . "</td>";
                    echo "<td>" . $row['PJ_Categoria'] . "</td>";
                    echo "<td>" . $row['PJ_Inscricao'] . "</td>";
                    echo "<td>" . $row['PJ_Razao_Social'] . "</td>";
                    echo "<td>" . $row['PJ_CNPJ'] . "</td>";
                    echo "<td>" . $row['PJ_Tipo_Inscricao'] . "</td>";
                    echo "<td>" . $row['PJ_Situacao'] . "</td>";
                    echo "<td>" . $row['PJ_Detalhe'] . "</td>";
                    echo "<td>" . $row['PJ_Natureza_Juridica'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- JS do DataTables -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<!-- JS Bootstrap (opcional, para suporte visual ao Bootstrap) -->
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabelaAuditoria9').DataTable({
            order: [[1, 'asc']],
            "iDisplayLength": 50,
            dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            language: {
                "decimal": ",",
                "thousands": ".",
                "sEmptyTable": "Nenhum dado disponível na tabela",
                "sInfo": "Mostrando _START_ até _END_ de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
                "sInfoFiltered": "(filtrado de _MAX_ registros no total)",
                "sInfoPostFix": "",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sLoadingRecords": "Carregando...",
                "sProcessing": "Processando...",
                "sSearch": "Pesquisar:",
                "sZeroRecords": "Nenhum registro encontrado",
                "oPaginate": {
                    "sFirst": "Primeiro",
                    "sLast": "Último",
                    "sNext": "Próximo",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending": ": ativar para ordenar a coluna em ordem crescente",
                    "sSortDescending": ": ativar para ordenar a coluna em ordem decrescente"
                }
            }
        });
    });
</script>

<?php } ?>