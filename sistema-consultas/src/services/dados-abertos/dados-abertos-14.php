<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA14acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Execução Financeira';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Execução Financeira</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="data_inicial">Data Inicial:</label>
            <input type="date" id="data_inicial" name="data_inicial" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="data_termino">Data Término:</label>
            <input type="date" id="data_termino" name="data_termino" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Execução Financeira</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];

        $dtInicioFormat = DateTime::createFromFormat('Y-m-d', $dtInicio)->format('d/m/Y');
        $dtTerminoFormat = DateTime::createFromFormat('Y-m-d', $dtTermino)->format('d/m/Y');

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/ExecucaoFinanceira?referenciaInicio="
            . urlencode($dtInicioFormat)
            . "&referenciaTermino="
            . urlencode($dtTerminoFormat)
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

        if (!isset($result['message'])) {
            $novoResult = [];

            // Agrupando od dados de "Contra partida" no objeto principal
            foreach ($result as $item) {
                foreach ($item['contraPartida'] as $cp) {
                    $novoResult[] = [
                        "dataContabil" => $item['dataContabil'],
                        "contaContabil" => $item['contaContabil'],
                        "valor" => $item['valor'],
                        "historico" => $item['historico'],
                        "contaContabilContraPartida" => $cp['contaContabil'],
                        "valorLancamento" => $cp['valorLancamento'],
                        "historicoLancamento" => $cp['historicoLancamento'],
                        "natureza" => $cp['natureza'] ? 'True' : 'False',
                    ];
                }
            }
        }

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
            <input type="hidden" name="dadosConsulta" value='<?= json_encode($novoResult, JSON_UNESCAPED_UNICODE); ?>'>
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <!-- Tabelas de Dados -->
    <div class="row mt-4">
        <div class="col">
            <!-- Tabela de Execução Financeira -->
            <h3>Execução Financeira</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Data Contábil</th>
                        <th>Conta Contábil</th>
                        <th>Valor</th>
                        <th>Histórico</th>
                        <th>Conta Contábil Contra Partida</th>
                        <th>Valor Lancamento</th>
                        <th>Histórico Lancamento</th>
                        <th>Natureza</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($novoResult as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['dataContabil'] . "</td>";
                        echo "<td>" . $row['contaContabil'] . "</td>";
                        echo "<td>" . $row['valor'] = 'R$ ' . number_format($row['valor'], 2, ',', '.') . "</td>";
                        echo "<td>" . $row['historico'] . "</td>";
                        echo "<td>" . $row['contaContabilContraPartida'] . "</td>";
                        echo "<td>" . $row['valorLancamento'] = 'R$ ' . number_format($row['valorLancamento'], 2, ',', '.') . "</td>";
                        echo "<td>" . $row['historicoLancamento'] . "</td>";
                        echo "<td>" . ($row['natureza'] = $row['natureza'] ? "true" : "false") . "</td>";
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