<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA12acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Diárias/Deslocamentos cadastrados';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Diárias/Deslocamentos cadastrados</label>
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
            <label for="nome_passageiro">Nome do Passageiro:</label>
            <input type="mounth" id="nome_passageiro" name="nome_passageiro" class="form-control" placeholder="" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Diárias/Deslocamentos cadastrados</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];
        $nomePassageiro = $_POST["nome_passageiro"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/DiariasDeslocamentos?referenciaInicio="
            . urlencode($dtInicio)
            . "&referenciaTermino="
            . urlencode($dtTermino)
            . "&nomePassageiro=" 
            . urlencode($nomePassageiro);

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
            <!-- Tabela de Diárias/Deslocamentos cadastrados -->
            <h3>Diárias/Deslocamentos cadastrados</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Código do Processo</th>
                        <th>Valor Unitário</th>
                        <th>Quantidade</th>
                        <th>Despesa Padrão</th>
                        <th>Valor Total</th>
                        <th>Passageiro(a)</th>
                        <th>Origem Passageiro(a)</th>
                        <th>Data de Pagamento</th>
                        <th>Periodo de Deslocamento</th>
                        <th>Evento</th>
                        <th>Eventos Concatenados</th>
                        <th>Cidade</th>
                        <th>Totalizadores</th>
                        <th>Data do Ajuste</th>
                        <th>Ajuste Estorno</th>
                        <th>Quantidade de Ajustes</th>
                        <th>Valor do Ajuste</th>
                        <th>Informativo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . ($row['codigoProcesso'] ?: '__') . "</td>";
                        echo "<td>" . $row['valorUnitario'] = 'R$ ' . number_format($row['valorUnitario'], 2, ',', '.') . "</td>";
                        echo "<td>" . ($row['quantidade'] ?: '__') . "</td>";
                        echo "<td>" . ($row['nomeDespesaPadrao'] ?: '__') . "</td>";
                        echo "<td>" . $row['valorTotal'] = 'R$ ' . number_format($row['valorTotal'], 2, ',', '.') . "</td>";
                        echo "<td>" . ($row['nomePassageiro'] ?: '__') . "</td>";
                        echo "<td>" . ($row['origemPassageiro'] ?: '__') . "</td>";
                        echo "<td>" . ($row['dataPagamento'] ?: '__') . "</td>";
                        echo "<td>" . ($row['periodoDeslocamentoFormatado'] ?: '__') . "</td>";
                        echo "<td>" . ($row['nomeEvento'] ?: '__') . "</td>";
                        echo "<td>" . ($row['eventosConcatenados'] ?: '__') . "</td>";
                        echo "<td>" . ($row['cidade'] ?: '__') . "</td>";
                        echo "<td>" . ($row['exibirTotalizadores'] ?: '__') . "</td>";
                        echo "<td>" . ($row['dataAjuste'] ?: '__') . "</td>";
                        echo "<td>" . ($row['ajusteEstorno'] ?: '__') . "</td>";
                        echo "<td>" . ($row['quantidadeAjuste'] ?: '__') . "</td>";
                        echo "<td>" . $row['valorAjuste'] = 'R$ ' . number_format($row['valorAjuste'], 2, ',', '.') . "</td>";
                        echo "<td>" . ($row['informativo'] ?: '__') . "</td>";
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