<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row) && isset($row['CF10acesso']) && $row['CF10acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas de Fiscalizações por Tipos de Irregularidades - Por Categoria e Período';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2"><?= $tituloConsulta ?></h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
    <option disabled selected value="">Selecione</option>
    <?php
    foreach (Helper::$ufList as $val => $value) {
        $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
        echo "<option value='$val' $selected>$value</option>";
    }
    ?>
</select>
            </div>
            <div class="form-group col-md-4">
                <label for="cro">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control">
                    <option disabled selected value="">Selecione</option>    
                    <option style="font-weight: bold;" disabled><b>Categorias:</b></option>
                    <?php
                    foreach (Helper::$catList as $val => $value) {
                        echo "<option value='$val' $selected>$value</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <div>
                    <label for="data_inicial">Data Inicial:</label>
                    <input type="date" id="data_inicial" name="data_inicial" class="form-control" required>
                </div>
                <div>
                <label for="data_termino">Data Término:</label>
                <input type="date" id="data_termino" name="data_termino" class="form-control" required>
                </div>
            </div>
            <br>
            <div class="form-group col-md-12">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    $erro = '';

    if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
        $erro = "Estado inválido.";
    }

    $categoriaInput = $inputPost["categoria"] ?? '';
    if ($categoriaInput !== 'ALL' && $categoriaInput !== '' && !array_key_exists($categoriaInput, Helper::$catList)) {
        $erro = "Categoria inválida.";
    }

    if ($categoriaInput === 'ALL' || $categoriaInput === '') {
        $categoria = "%%";
    } else {
        $categoria = $categoriaInput;
    }

    $data_inicial = strval($inputPost["data_inicial"]);
    $data_termino = strval($inputPost["data_termino"]);

    if (!empty($erro)) {
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> " . htmlspecialchars($erro) . "</div>";
        $result = [];
    } else {
        $cro = "[cro_" . $inputPost["cro"] . "]";

        $path = realpath(dirname(__FILE__, 3)) . "/database/script/consultaFiscalizacao/consultaFiscalizacao15.sql";
        $myfile = fopen($path, "r") or die("Unable to open file!");
        $script = fread($myfile, filesize($path));
        fclose($myfile);

        $script = str_replace(':banco', $cro, $script);
        $script = str_replace("':categoria'", ":categoria", $script);
        $script = str_replace("':inicio'", ":data_inicio", $script);
        $script = str_replace("':termino'", ":data_fim", $script);

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();
            $stmt = $con->prepare($script);
            $stmt->bindValue(':data_inicio', $data_inicial);
            $stmt->bindValue(':data_fim', $data_termino);
            $stmt->bindValue(':categoria', $categoria);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $error) {
            error_log("Erro consulta fiscalizacao: " . $error->getMessage());
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
            $result = [];
        }
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
        <table id="tabelaFiscalizacao" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">Data de inicio</th>
                <th scope="col">Data de término</th>
                <th scope="col">CRO</th>
                <th scope="col">Categoria</th>
                <th scope="col">PF / PJ Sem Inscrição</th>
                <th scope="col">PJ Sem Responsável Técnico</th>
                <th scope="col">Ausência de Identificação na Comunicação e Divugação</th>
                <th scope="col">Divugar Especialidade Sem Registro no CFO</th>
                <th scope="col">Anuncio, Propagenda e Publicidade Irregular</th>
                <th scope="col">Exercício Irregular</th>
                <th scope="col">Exercício Ilegal</th>
                <th scope="col">Acobertamento de Exercício Ilegal</th>
                <th scope="col">Outros</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    
                    $inicio = date('d/m/Y', strtotime($row['Data_Inicio_Termo']));
                    $termino = date('d/m/Y', strtotime($row['Data_Fim_Termo']));

                    echo "<tr>";
                    echo "<td>" . $inicio . "</td>";
                    echo "<td>" . $termino . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Categoria']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['PF_PJ_Sem_Inscricao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['PJ_Sem_Responsavel_Tecnico']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Ausencia_de_Identificacao_na_Comunicacao_e_divulgacao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Divulgar_Especialidade_Sem_Registro_no_CFO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Anuncio_Propaganda_e_Publicidade_Irregular']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Exercicio_Irregular']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Exercicio_Ilegal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Acobertamento_de_exercicio_ilegal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Outros']) . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelaFiscalizacao').DataTable({
                "paging": true,
                "pageLength": 10,
                "order": [],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                },
                "responsive": true,
                "scrollX": true,
            });
        });
    </script>
</div>

<?php } ?>