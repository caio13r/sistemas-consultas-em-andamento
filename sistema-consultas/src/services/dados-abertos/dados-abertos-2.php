<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA2acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Atas de Colegiados';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Atas de Colegiados</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="data_inicial">Data Inicial:</label>
            <input type="date" id="data_inicial" name="data_inicial" class="form-control" value="" required>
        </div>
        <div class="form-group col-md-6">
            <label for="data_termino">Data Término:</label>
            <input type="date" id="data_termino" name="data_termino" class="form-control" value="" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Atas</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];

        $dtInicioFormt = DateTime::createFromFormat('Y-m-d', $dtInicio)->format('d/m/Y');
        $dtTerminoFormt = DateTime::createFromFormat('Y-m-d', $dtTermino)->format('d/m/Y');

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/AtasColegiados?dataInicio="
            . urlencode($dtInicioFormt)
            . "&dataTermino="
            . urlencode($dtTerminoFormt)
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

        // Fecha o cURL
        curl_close($ch);

    } catch (Exception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }

?>

<?php if (empty($result) || count($result) == 0) { ?>
    <div class="alert alert-warning">
        <strong>Atenção!</strong> Não foi encontrado nenhum dado nesse período.
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
            <!-- Tabela de Atas de Colegiados -->
            <h3>Atas de Colegiados</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Colegiado</th>
                        <th>Data de Inicio</th>
                        <th>Data de Término</th>
                        <th>Deliberações</th>
                        <th>Relação Participantes</th>
                        <th>Anexos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['numero'] . "</td>";
                        echo "<td>" . $row['tipo'] . "</td>";
                        echo "<td>" . $row['colegiado'] . "</td>";
                        echo "<td>" . ($row['dataInicio'] ?: '-') . "</td>";
                        echo "<td>" . ($row['dataTermino'] ?: '-') . "</td>";
                        echo "<td>" . $row['deliberacoes'] . "</td>";
                        echo "<td>" . $row['relacaoParticipantes'] . "</td>";
                        echo "<td>" . $row['anexos'] . "</td>";
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


<?php
/*
$result = [
    [
        "numero" => "001",
        "tipo" => "Ordinária",
        "colegiado" => "Conselho Federal de Odontologia",
        "dataInicio" => "01/01/2022",
        "dataTermino" => "01/02/2022",
        "deliberacoes" => "Aprovação do novo regimento interno",
        "relacaoParticipantes" => "Dr. João Silva, Dra. Maria Souza, Dr. Pedro Lima",
        "anexos" => "Ata001.pdf"
    ],
    [
        "numero" => "002",
        "tipo" => "Extraordinária",
        "colegiado" => "Conselho Regional de Odontologia - SP",
        "dataInicio" => "15/03/2022",
        "dataTermino" => "20/03/2022",
        "deliberacoes" => "Discussão sobre atualizações nas normas de biossegurança",
        "relacaoParticipantes" => "Dra. Ana Oliveira, Dr. Carlos Santos, Dra. Beatriz Almeida",
        "anexos" => "Ata002.pdf"
    ],
    [
        "numero" => "003",
        "tipo" => "Ordinária",
        "colegiado" => "Conselho Regional de Odontologia - RJ",
        "dataInicio" => "10/06/2022",
        "dataTermino" => "12/06/2022",
        "deliberacoes" => "Revisão do orçamento anual",
        "relacaoParticipantes" => "Dr. Roberto Nascimento, Dra. Cláudia Mendes",
        "anexos" => "Ata003.pdf"
    ],
    [
        "numero" => "004",
        "tipo" => "Extraordinária",
        "colegiado" => "Conselho Federal de Odontologia",
        "dataInicio" => "20/08/2022",
        "dataTermino" => "22/08/2022",
        "deliberacoes" => "Eleição da nova diretoria",
        "relacaoParticipantes" => "Dr. José Pereira, Dra. Laura Martins, Dr. Paulo Silva",
        "anexos" => "Ata004.pdf"
    ]
];
*/
?>