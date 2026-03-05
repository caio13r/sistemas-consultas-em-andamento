<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA23acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}


$tituloConsulta = 'Auditoria - Profissionais e Empresas com Multiplos Registros';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Profissionais e Empresas com Multiplos Registros</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        // Validação de Acessso as UFs 
                        if (Session::get('grupo') === 0 || $row['CA23select'] == true) {
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
            </div><br>
            <div class="form-group col-md-6">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" value="<?= $dados["categoria"] ?>">
                    <option disabled selected value>Selecione</option>
                    <?php
                        foreach(Helper::$catList as $val => $value) {
                            $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }
                    ?>
                </select>
            </div><br>
            <div class="form-group col-md-3">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL') {
        $croTable = 'MR.[CRO] IS NOT NULL';
    } else {
        $croTable = "MR.[CRO] = '{$inputPost["cro"]}'";
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catTable = 'MR.[Categoria] IS NOT NULL';
    } else {
        $catTable = "MR.[Categoria] = '{$inputPost["categoria"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $query = "
            SELECT
                MR.[CRO] AS CRO,
                MR.[Categoria] AS Categoria,
                MR.[Inscricao] AS Inscricao,
                MR.[Nome_Razao_social] AS Nome_Razao_social,
                MR.[CPF_CNPJ] AS CPF_CNPJ,
                MR.[Tipo_Inscricao] AS Tipo_Inscricao,
                MR.[Situacao] AS Situacao,
                MR.[Detalhe] AS Detalhe
            FROM [CFO_CWS].[dbo].[vw_Cons_Multiplos_Registros] AS MR
            WHERE
                {$croTable} AND {$catTable}
            ORDER BY
                MR.[CRO],
                MR.[Nome_Razao_social],
                MR.[Categoria]
        ";
        
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
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Nome Razão Social</th>
                    <th scope="col">CPF/CNPJ</th>
                    <th scope="col">Tipo Inscrição</th>
                    <th scope="col">Situacão</th>
                    <th scope="col">Detalhe</th>   
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>{$row["CRO"]}</td>";
                        echo "<td>{$row["Categoria"]}</td>";
                        echo "<td>{$row["Inscricao"]}</td>";
                        echo "<td>{$row["Nome_Razao_social"]}</td>";
                        echo "<td>{$row["CPF_CNPJ"]}</td>";
                        echo "<td>{$row["Tipo_Inscricao"]}</td>";   
                        echo "<td>{$row["Situacao"]}</td>";
                        echo "<td>{$row["Detalhe"]}</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>

<script>
$(document).ready(function() {
    $('#tabelaConsultas5').DataTable({
        "paging": true,
        "pageLength": 50,
        "lengthMenu": [10, 25, 50, 100],
        "language": {
            "url": "../assets/lang/pt-BR.json"
        }
    });
});
</script>
