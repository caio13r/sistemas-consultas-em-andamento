<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA17acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Pessoas com DDA';
?>

<div class="col-md-8 offset-md-2 mb-4">
    <h6 class="mb-2">Pessoas com DDA</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value='BR'>Brasil</option>
                    <option value='ALL'>Todos</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CF3select'] == true) {
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
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" value="<?= $dados["categoria"] ?>">
                    <option disabled selected value>Selecione</option>
                    <option value='ALL'>Todos</option>
                    <option value='APD'>APD</option>
                    <option value='ASB'>ASB</option>
                    <option value='CD'>CD</option>
                    <option value='TPD'>TPD</option>
                    <option value='TSB'>TSB</option>
                </select>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Gerar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croWhere = "CRO IS NOT NULL";
    } else {
        $croWhere = "CRO LIKE '{$inputPost["cro"]}'";
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catWhere = "Categoria IS NOT NULL";
    } else {
        $catWhere = "Categoria LIKE '{$inputPost["categoria"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT
                    TOP 2000
                    CRO,
                    Categoria,
                    Inscricao,
                    Nome,
                    CPF,
                    Tipo_Inscricao,
                    Situacao,
                    Detalhe,
                    DDA
                FROM CFO_CWS.dbo.vw_Cons_Pessoas_Com_DDA
                WHERE $croWhere AND $catWhere
                ORDER BY CRO, Categoria, Nome";
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

<div id="tableResult" class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas21" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
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
                    <th scope="col">DDA</th>
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
                    echo "<td>" . $row['DDA'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<!-- Incluindo os scripts necessários para o DataTables funcionar corretamente -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabelaConsultas21').DataTable({
            "paging": true,
            "lengthMenu": [10, 25, 50, 100],
            "language": {
            "url": "../assets/lang/pt-BR.json" // Caminho local para o arquivo de tradução
        }
            
        });
    });
</script>
<?php } ?>