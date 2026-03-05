<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CA22acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Auditoria - Profissionais ativos com idade para REMISSÃO';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Profissionais ativos com idade para REMISSÃO</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        // Validação de Acessso as UFs 
                        if (Session::get('grupo') === 0 || $row['CA22select'] == true) {
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
        $croTable = 'PIR.[CRO] IS NOT NULL';
    } else {
        $croTable = "PIR.[CRO] = '{$inputPost["cro"]}'";
    }

    if ($inputPost["categoria"] === 'ALL' || $inputPost["categoria"] === null) {
        $catTable = 'PIR.[Categoria] IS NOT NULL';
    } else {
        $catTable = "PIR.[Categoria] = '{$inputPost["categoria"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $query = "
            SELECT
                PIR.[CRO] as CRO,
                PIR.[Categoria] as Categoria,
                PIR.[Inscricao] as Inscricao,
                PIR.[Nome] as Nome,
                PIR.[CPF] as CPF,
                PIR.[Tipo_Inscricao] as Tipo_Inscricao,
                PIR.[Situacao] as Situacao,
                PIR.[Detalhe] as Detalhe,
                PIR.[Adimplente] as Adimplente,
                PIR.[Idade] as Idade,
                PIR.[Data_Nascimento] as Data_Nascimento
            FROM 
                [CFO_CWS].[dbo].[vw_Cons_Profissionais_Com_Idade_Remissao] AS PIR
            WHERE
                {$croTable} AND {$catTable}
            ORDER BY
                PIR.[CRO], PIR.[Idade] ASC
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
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">Tipo Inscrição</th>
                    <th scope="col">Situacão</th>
                    <th scope="col">Detalhe</th>   
                    <th scope="col">Adimplente</th>
                    <th scope="col">Idade</th>
                    <th scope="col">Data Nascimento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>{$row["CRO"]}</td>";
                        echo "<td>{$row["Categoria"]}</td>";
                        echo "<td>{$row["Inscricao"]}</td>";
                        echo "<td>{$row["Nome"]}</td>";
                        echo "<td>{$row["CPF"]}</td>";
                        echo "<td>{$row["Tipo_Inscricao"]}</td>";   
                        echo "<td>{$row["Situacao"]}</td>";
                        echo "<td>{$row["Detalhe"]}</td>";
                        echo "<td>{$row["Adimplente"]}</td>";
                        echo "<td>{$row["Idade"]}</td>";
                        echo "<td>{$row["Data_Nascimento"]}</td>";
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
