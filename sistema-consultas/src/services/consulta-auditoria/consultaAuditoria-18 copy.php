<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA18acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}
  
$tituloConsulta = "Auditoria - Profissionais desativados por caducidade ou por cancelamento ex-ofício";
?>

<div class="col-md-6 offset-md-3 mb-4">
<h6 class="mb-2">Desativados por caducidade ou cancelamento ex-oficio</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="cro">Selecione:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA18select'] == true) {
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

        try {
            $con = Database3::getInstance()->getConnection();        
            $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_Caducados_Com_Registro_Em_Outro_Estado WHERE $croQuery";
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
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
                <!-- <th scope="col">CRO Ativo</th>
                <th scope="col">Categoria CRO ativo</th>
                <th scope="col">Inscrição CRO ativo</th>
                <th scope="col">Tipo de inscrição CRO ativo</th>
                <th scope="col">Situação CRO ativo</th>
                <th scope="col">Detalhe CRO ativo</th> -->
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
                    // echo "<td>" . $row['CRO Ativo'] . "</td>";
                    // echo "<td>" . $row['Categoria CRO ativo'] . "</td>";
                    // echo "<td>" . $row['Inscrição CRO ativo'] . "</td>";
                    // echo "<td>" . $row['Tipo de inscrição CRO ativo'] . "</td>";
                    // echo "<td>" . $row['Situação CRO ativo'] . "</td>";
                    // echo "<td>" . $row['Detalhe CRO ativo'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>