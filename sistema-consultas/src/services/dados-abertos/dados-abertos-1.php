<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA1acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Rol de Mandatários';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Consultar Conselheiros</label>
    <form action="" method="post">
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar Conselheiros</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {
        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/Conselheiros";

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

        // Verifica erros no cURL
        if (curl_errno($ch)) {
            echo 'Erro: ' . curl_error($ch);
        }
            
        // Decodifica o JSON retornado pela API
        $result = json_decode($response, true);

        if (!empty($result)) {
            // Filtrando e organizando os dados para o Excel
            $dadosPlanilha = [];
            
            foreach($result as $grupo) {
                foreach($grupo['conselheiros'] as $conselheiro) {
                    $dadosPlanilha[] = [
                        'titulo' => ($grupo['titulo'] ?: "-"),                        
                        'cpf' => ($conselheiro['cpf'] ?: "-"),                       
                        'nomeMandatario' => ($conselheiro['nomeMandatario'] ?: "-"),
                        'emailMandatario' => ($conselheiro['emailMandatario'] ?: "-"),
                        'cargo' => ($conselheiro['cargo'] ?: "-"),
                        'dataPosse' => ($conselheiro['dataPosse'] ?: "-"),
                        'dataPrevistaFimMandato' => ($conselheiro['dataPrevistaFimMandato'] ?: "-"),
                        'dataEfetivaExoneracao' => ($conselheiro['dataEfetivaExoneracao'] ?:"-")
                    ];
                }
            }
        }

        // Fecha o cURL
        curl_close($ch);

    } catch (Exception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

?>


<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value='<?= json_encode($dadosPlanilha, JSON_UNESCAPED_UNICODE); ?>'>
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<?php if (!empty($result)) { ?>

    <!-- Tabelas de Dados -->
    <div class="row mt-4">
        <div class="col">
            <!-- Tabela de Conselheiros-->
            <h3>Conselheiros</h3>
            <table id="tabelaEfetivos" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                <tr>
                    <th>Titulo</th>
                    <th>CPF</th>
                    <th>Nome Mandatário</th>
                    <th>Email Mandatário</th>
                    <th>Cargo</th>
                    <th>Data da posse</th>
                    <th>Data prevista de fim de mandato</th>
                    <th>Data efetiva Exoneração</th>
                </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($dadosPlanilha as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['titulo'] . "</td>";
                        echo "<td>" . $row['cpf'] . "</td>";
                        echo "<td>" . $row['nomeMandatario'] . "</td>";
                        echo "<td>" . $row['emailMandatario'] . "</td>";
                        echo "<td>" . $row['cargo'] . "</td>";
                        echo "<td>" . $row['dataPosse'] . "</td>";
                        echo "<td>" . $row['dataPrevistaFimMandato'] . "</td>";
                        echo "<td>" . $row['dataEfetivaExoneracao'] . "</td>";
                        echo "</tr>";
                    }
                ?>
                </tbody>
            </table>
        </div>

    <!-- Inicialização do DataTables com idioma Português -->
    <script>
        $(document).ready(function() {
            $('#tabelaEfetivos').DataTable({
                "paging": true,
                "pageLength": 10,
                "order": [],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json"
                },
                "responsive": true,
                "columnDefs": [
                    { className: "text-center", targets: "_all" } // Centraliza todas as colunas
                ]
            });
        });
    </script>
<?php } ?>
<?php } ?>