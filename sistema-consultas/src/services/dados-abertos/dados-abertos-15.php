<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA15acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Plano de Contas';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Plano de Contas</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="exercicio">Exercício:</label>
            <input type="year" id="exercicio" name="exercicio" class="form-control" placeholder="Ex: 2024" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Plano de Contas</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $exercicio = $_POST["exercicio"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/PlanoDeContas?exercicio="
            . urlencode($exercicio)
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

        function flattenData(array $items): array {
            $flattened = [];
            foreach ($items as $item) {
              // Adiciona o item atual ao array resultante, removendo a chave "analiticas"
                $flattened[] = [
                    "codigo" => $item["codigo"],
                    "nome" => $item["nome"]
                ];
    
              // Se existir "analiticas", chama a função recursivamente
                if (!empty($item["analiticas"])) {
                    $flattened = array_merge($flattened, flattenData($item["analiticas"]));
                }
            }
            return $flattened;
        }

        if (!isset($result['message'])) {
            $novoResult = flattenData($result);
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
            <!-- Tabela de Plano de Contas -->
            <h3>Plano de Contas</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($novoResult as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['codigo'] . "</td>";
                        echo "<td>" . $row['nome'] . "</td>";
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
                "pageLength": 50,
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