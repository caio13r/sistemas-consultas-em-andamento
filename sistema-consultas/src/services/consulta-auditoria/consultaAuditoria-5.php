<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA5acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais com inscrição ativa em mais de um cro';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Consultar profissionais com inscrição ativa em mais de um cro</label>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA5select'] == true) {
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
        $croTable = "WHERE CRO_1 = '{$inputPost["cro"]}'";
    }
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $query = "
            SELECT  top 1000
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
            FROM CFO_CWS.dbo.vw_Cons_Inscricao_Principal_Em_Mais_De_Um_CRO 
            $croTable";
        
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
        <table id="tabelaConsultas5" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
        echo "<td>" . $row['Nome1'] . "</td>";
        echo "<td>" . $row['CPF'] . "</td>";
        echo "<td>" . $row['CRO1'] . "</td>";
        echo "<td>" . $row['CATEGORIA_1'] . "</td>";
        echo "<td>" . $row['INSCRICAO_1'] . "</td>";
        echo "<td>" . $row['TIPO_INSCRICAO_1'] . "</td>";
        echo "<td>" . $row['SITUACAO_1'] . "</td>";
        echo "<td>" . $row['DETALHE_1'] . "</td>";
        echo "<td>" . $row['DATA_INSCRICAO_CRO_1'] . "</td>";
        echo "<td>" . $row['SITUACAO_REGISTRO_ATUAL_1'] . "</td>";
        echo "<td>" . $row['Nome2'] . "</td>";
        echo "<td>" . $row['CPF'] . "</td>";
        echo "<td>" . $row['CRO2'] . "</td>";
        echo "<td>" . $row['CATEGORIA_2'] . "</td>";
        echo "<td>" . $row['INSCRICAO_2'] . "</td>";
        echo "<td>" . $row['TIPO_INSCRICAO_2'] . "</td>";
        echo "<td>" . $row['SITUACAO_2'] . "</td>";
        echo "<td>" . $row['DETALHE_2'] . "</td>";
        echo "<td>" . $row['DATA_INSCRICAO_CRO_2'] . "</td>";
        echo "<td>" . $row['SITUACAO_REGISTRO_ATUAL_2'] . "</td>";
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
    $('#tabelaConsultas5').DataTable({
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json" // Caminho local para o arquivo de tradução
        }
    });
});
</script>
