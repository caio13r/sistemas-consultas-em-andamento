<?php 
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CF10acesso']) && $row['CF10acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas de Fiscalizações de Fiscal Sem Inscrições - Por Fiscal e Período';

?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2"><?= $tituloConsulta ?></h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado  :</label>
                <select id="cro" name="cro" class="form-control" required>
                    <option disabled selected value="">Selecione</option>    
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                    foreach (Helper::$ufList_withoutAll as $val => $value) {
                        $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $row['CRO']) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="pessoa">Tipo de pessoa:</label>
                <select id="pessoa" name="pessoa" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                        $values = array('ALL' => 'Todos', 1 => 'Pessoa Física', 0 => 'Pessoa Jurídica');
                        foreach($values as $val => $value) {
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

    $cro = $inputPost["cro"];
    $pessoa = $inputPost["pessoa"];
    $data_inicial = strval($inputPost["data_inicial"]);
    $data_termino = strval($inputPost["data_termino"]);

    $erro = '';
    if (!array_key_exists($cro, Helper::$ufList_withoutAll)) {
        $erro = "Estado inválido.";
    }
    $validPessoa = ['ALL', '0', '1', ''];
    if (!in_array(strval($pessoa), $validPessoa, true)) {
        $erro = "Tipo de pessoa inválido.";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicial) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_termino)) {
        $erro = "Data inválida.";
    }

    if (!empty($erro)) {
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> " . htmlspecialchars($erro) . "</div>";
        $result = [];
    } else {
        $cro = "[cro_$cro]";
        
        if ($inputPost['pessoa'] == 'ALL' || $inputPost['pessoa'] == '') {
            $pessoa = "";
        } else {
            $pessoa = "AND CadPe.TipoPessoaFisica = " . intval($pessoa);
        }

        $path = realpath(dirname(__FILE__, 3)) . "/database/script/consultaFiscalizacao/consultaFiscalizacao14.sql";
        $myfile = fopen($path, "r") or die("Unable to open file!");
        $script .= fread($myfile, filesize($path));
        fclose($myfile);

        $script = str_replace(':banco', $cro, $script);
        $script = str_replace(':tipoPessoaFisica', $pessoa, $script);
        $script = str_replace(':inicio', $data_inicial, $script);
        $script = str_replace(':termino', $data_termino, $script);

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();
            $stmt = $con->prepare($script);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOexception $error) {
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
                <th scope="col">Pessoa</th>
                <th scope="col">Fiscal</th>
                <th scope="col">Ano Fiscalização com Termos</th>
                <th scope="col">Fiscalização com Termos</th>
                <th scope="col">Fiscalizações Proativas</th>
                <th scope="col">Fiscalizações Reativas</th>
                <th scope="col">Fiscalizações Online</th>
                <th scope="col">Fiscalizações Exercício Ilegal</th>
                <th scope="col">Notificações com Indicio de Irregularidade</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {

                    $inicio = date('d/m/Y', strtotime($row['Data_Inicio_Termo']));
                    $termino = date('d/m/Y', strtotime($row['Data_Fim_Termo']));

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($inicio) . "</td>";
                    echo "<td>" . htmlspecialchars($termino) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Pessoa']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Ano_Fiscalizacoes_Com_Termo']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscalizacoes_Com_Termo']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscalizacoes_Proativas']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscalizacoes_Reativas']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscalizacoes_Online']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Fiscalizacoes_Exercicio_Ilegal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Notificacoes_Com_Indicios_de_Irregularidades']) . "</td>";
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