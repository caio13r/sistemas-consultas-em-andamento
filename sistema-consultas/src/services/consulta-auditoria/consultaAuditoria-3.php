<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA3acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais e empresas com situação de pré-cadastro e com número de inscrição';
?>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Consultar total de cadastros provisórios vencidos</label>
    <form action="" method="post">
        <div class="form-group">
            <label for="cro">Selecione o Estado:</label>
            <select id="cro" name="cro" class="form-control" required>
                <option disabled selected value>Selecione</option>
                <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CA3select'] == true) {
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
        $croTable = "CRO IS NOT NULL";
    } else {
        $croTable = "CRO = '{$inputPost["cro"]}'";
    }
    
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT 
         CRO,
            Nome AS 'Nome/Razão Social',
             Categoria,
             [Tipo Inscrição],
            Inscrição,
             Situação,
             Detalhe,
            [CPF / CNPJ],
           [Data Situação] AS 'Situação registro atual'
        
        
        
         FROM CFO_CWS.dbo.vw_Rel_PreCadastrado_com_inscricao WHERE $croTable";
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
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CRO</th>
                <th scope="col">Nome/ Razão Social</th>
                <th scope="col">Categoria</th>
                <th scope="col">Tipo de Inscrição</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Detalhe</th>
                <th width="15%" scope="col">CPF/CNPJ</th>
                <th scope="col">Situação registro atual</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Nome/Razão Social'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Tipo Inscrição'] . "</td>";
                    echo "<td>" . $row['Inscrição'] . "</td>";
                    echo "<td>" . $row['Situação'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . $row['CPF / CNPJ'] . "</td>";
                    echo "<td>" . $row['Situação registro atual'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>