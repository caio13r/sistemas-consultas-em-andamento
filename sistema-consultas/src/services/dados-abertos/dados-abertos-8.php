<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA8acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Convênios Cadastrados';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Convênios Cadastrados</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="data_inicial">Data Inicial:</label>
            <input type="mounth" id="data_inicial" name="data_inicial" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="data_termino">Data Término:</label>
            <input type="mounth" id="data_termino" name="data_termino" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Convênios</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/Convenios?referenciaInicio="
            . urlencode($dtInicio)
            . "&referenciaTermino="
            . urlencode($dtTermino)
        ;

        // Headers necessários para a consulta na API REST
        $headers = [
            'Accept: application/json, text/json', 
            'Chave: '.$_ENV['API_KEY'], 
            'Senha: '.$_ENV['API_PASS']
        ];

        // Inicializa o cURL
        $ch = curl_init();

        // Configura as opções da requisição
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Executa a requisição
        $response = curl_exec($ch);

        // Verifica se ocorreu erro no cURL
        if (curl_errno($ch)) {
            die('Erro cURL: ' . curl_error($ch));
        }

        // Decodifica o JSON retornado
        $result = json_decode($response, true);

        if (empty($result)) {
            $result['message'] = "Nenhum dado encontrado.";
        }

        // Fecha o cURL
        curl_close($ch);

    } catch (Exception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

?>

<?php if (empty($result) || $result['message']) { ?>
    <div class="alert alert-warning">
        <strong>Atenção!</strong> <?php echo $result['message'];?>
    </div>
<?php } else { ?>

    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value='<?= json_encode($result, JSON_UNESCAPED_UNICODE); ?>'>
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <!-- Tabelas de Dados -->
    <div class="row mt-4">
        <div class="col">
            <!-- Tabela de Convênios Cadastrados -->
            <h3>Convênios Cadastrados</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Número do Convênio</th>
                        <th>Código</th>
                        <th>Objeto</th>
                        <th>Valor Total</th>
                        <th>Inicio da Vigencia</th>
                        <th>Término da Vigencia</th>
                        <th>Beneficiário</th>
                        <th>Modalidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['numeroConvenio'] . "</td>";
                        echo "<td>" . $row['codigo'] . "</td>";
                        echo "<td>" . $row['objeto'] . "</td>";
                        echo "<td>" . $row['valorTotal'] = 'R$ ' . number_format($row['valorTotal'], 2, ',', '.') . "</td>";
                        echo "<td>" . $row['inicioVigencia'] . "</td>";
                        echo "<td>" . $row['terminoVigencia'] . "</td>";
                        echo "<td>" . $row['beneficiario'] . "</td>";
                        echo "<td>" . $row['modalidade'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inicialização do DataTables com idioma Português -->
    <script>
        $(document).ready(function() {
            $('#tabelaDados').DataTable({
                "paging": true,
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                },
                "scrollX": true,
                "responsive": true,
                "columnDefs": [
                    { className: "text-center", targets: "_all" } // Centraliza todas as colunas
                ]
            });
        });
    </script>
<?php } ?>
<?php } ?>