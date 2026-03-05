<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA9acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Licitações Cadastradas';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Licitações Cadastradas</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="data_inicial">Data Inicial:</label>
            <input type="mounth" id="data_inicial" name="data_inicial" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="data_termino">Data Término:</label>
            <input type="mounth" id="data_termino" name="data_termino" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="numero_licitacao">Número da Licitação:</label>
            <input type="mounth" id="numero_licitacao" name="numero_licitacao" class="form-control" placeholder="" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Licitações</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];
        $numeroLicitacao = $_POST["numero_licitacao"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/Licitacoes?referenciaInicio="
            . urlencode($dtInicio)
            . "&referenciaTermino=" 
            . urlencode($dtTermino)
            . "&numeroLicitacao=" 
            . urlencode($numeroLicitacao);

        // Headers necessários para a consulta na API REST
        $headers = [
            'Accept: application/json, text/json',
            'Chave: ' . $_ENV['API_KEY'],
            'Senha: ' . $_ENV['API_PASS']
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

        // Pegando os dados para o excel 
        if (!isset($result['message'])) {
            $resultAll = $result;
        }

        // Trasnformando o array de arquivos em uma string para download do excel
        if (!empty($resultAll)) {
            foreach ($resultAll as &$row) {
                if (isset($row['arquivoEdital']) && is_array($row['arquivoEdital']) && !empty($row['arquivoEdital'])) {
                    $row['arquivoEdital'] = implode(", ", $row['arquivoEdital']);
                } else {
                    $row['arquivoEdital'] = "Sem arquivos disponíveis.";
                }
            }
        }

        // Fecha o cURL
        curl_close($ch);

    } catch (Exception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<?php if (empty($result) || isset($result['message'])) { ?>
    <div class="alert alert-warning">
        <strong>Atenção!</strong> <?php echo $result['message'];?>
    </div>
<?php } else { ?>

    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value='<?= json_encode($resultAll, JSON_UNESCAPED_UNICODE); ?>'>
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <!-- Tabelas de Dados -->
    <div class="row mt-4">
        <div class="col">
            <!-- Tabela de Licitações Cadastradas -->
            <h3>Licitações Cadastradas</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Número da Licitação</th>
                        <th>Número do Processo de Compra</th>
                        <th>Modalidade</th>
                        <th>Objeto</th>
                        <th>Vencedor</th>
                        <th>Arquivo Edital</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['numeroLicitacao'] . "</td>";
                        echo "<td>" . $row['numeroProcessoCompra'] . "</td>";
                        echo "<td>" . $row['modalidade'] . "</td>";
                        echo "<td>" . $row['objeto'] . "</td>";
                        echo "<td>" . $row['vencedor'] . "</td>";
                        echo "<td>" . $row['arquivoEdital'] = implode(", ", $row['arquivoEdital']) . "</td>";
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
