<?php 

    use Cfo\SisConsultas\database\Database3;
    use Cfo\SisConsultas\lib\Session;

    Session::CheckSession();

    if (Session::get('grupo') != 0 && $row['TC'.$tipoConsulta.'acesso'] == false) {
        echo "<script language='javascript'>
        window.alert('Você não tem permissão para acessar essa página.')
        window.location.href='consulta-estatistica';
        </script>";
        exit;
    }

    $tituloConsulta;

    foreach($labelsTC as $key => $label) {
        if ($key == $tipoConsulta) {
            $tituloConsulta = $label;
        }
    }

    $path = realpath(dirname(__FILE__, 3)) . "/database/script/tabelasCentralizadas/tabelasCentralizadas".$tipoConsulta.".sql";
    $myfile = fopen($path, "r") or die("Unable to open file!");
    $script .= fread($myfile,filesize($path));
    fclose($myfile);

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
        $stmt = $con->prepare($script);
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

<div class="row mt-4">
    <div class="col table-responsive">
        <h3>Tabelas Centralizadas - <?= $tituloConsulta ?></h3>
        <table id="tabelaFormacao" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">CFO</th>
                <th scope="col">Tabela</th>
                <th scope="col">CodigoIntegracaoFederal</th>
                <th scope="col">Motivo de Fiscalização</th>
                <th scope="col">Classificação da Fiscalização</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['Tabela'] . "</td>";
                    echo "<td>" . $row['Codigo_Integracao_Federal'] . "</td>";
                    echo "<td>" . $row['Motivo_Fiscalizacao'] . "</td>";
                    echo "<td>" . $row['Classificacao_Fiscalizacao'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelaFormacao').DataTable({
                order: [[3, 'asc']],
                "iDisplayLength": 50,
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            });
        });
    </script>

<?php } ?>
