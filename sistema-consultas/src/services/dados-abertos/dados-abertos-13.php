<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA13acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Balanço Patrimonial';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Balanço Patrimonial</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="data_inicial">Data Inicial:</label>
            <input type="mounth" id="data_inicial" name="data_inicial" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="data_termino">Data Término:</label>
            <input type="mounth" id="data_termino" name="data_termino" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Balanço Patrimonial</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["data_inicial"];
        $dtTermino = $_POST["data_termino"];
        $nomePassageiro = $_POST["nome_passageiro"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/BalancoPatrimonial?referenciaInicio="
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
            <!-- Tabela de Balanço Patrimonial -->
            <h3>Balanço Patrimonial</h3>
            <div>
                <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                    <thead>
                        <tr>
                            <th>Conta Ativa</th>
                            <th>Conta Passiva</th>
                            <th>Nome da Conta Ativa</th>
                            <th>Nome da Conta Passiva</th>
                            <th>Exercício Atual Ativo</th>
                            <th>Exercício Atual Passivo</th>
                            <th>Exercício Anterior Ativo</th>
                            <th>Exercício Anterior Passivo</th>
                            <th>Resultado Atual</th>
                            <th>Resultado Anterior</th>
                            <th>Patrimônio Social Atual</th>
                            <th>Patrimônio Social Anterior</th>
                            <th>Ajuste Patrimonial Atual</th>
                            <th>Ajuste Patrimonial Anterior</th>
                            <th>Total Passivo Atual</th>
                            <th>Total Passivo Anterior</th>
                            <th>Total PL Atual</th>
                            <th>Total PL Anterior</th>
                            <th>Total Ativo Atual</th>
                            <th>Total Ativo Anterior</th>
                            <th>Ativo Financeiro Atual</th>
                            <th>Ativo Financeiro Anterior</th>
                            <th>Passivo Financeiro Atual</th>
                            <th>Passivo Financeiro Anterior</th>
                            <th>Ativo Permanente Atual</th>
                            <th>Ativo Permanente Anterior</th>
                            <th>Passivo Permanente Atual</th>
                            <th>Passivo Permanente Anterior</th>
                            <th>Garantias Recebidas Atuais</th>
                            <th>Garantias Recebidas Anteriores</th>
                            <th>Garantias Concedidas Atuais</th>
                            <th>Garantias Concedidas Anteriores</th>
                            <th>Direitos Conveniados Atuais</th>
                            <th>Direitos Conveniados Anteriores</th>
                            <th>Obrigações Conveniadas Atuais</th>
                            <th>Obrigações Conveniadas Anteriores</th>
                            <th>Direitos Contratuais Atuais</th>
                            <th>Direitos Contratuais Anteriores</th>
                            <th>Obrigações Contratuais Atuais</th>
                            <th>Obrigações Contratuais Anteriores</th>
                            <th>Outros Atos Ativos Atuais</th>
                            <th>Outros Atos Ativos Anteriores</th>
                            <th>Outros Atos Passivos Atuais</th>
                            <th>Outros Atos Passivos Anteriores</th>
                            <th>Saldo Patrimonial Atual</th>
                            <th>Saldo Patrimonial Anterior</th>
                            <th>Nível Ativo</th>
                            <th>Nível Passivo</th>
                            <th>Total PL Passivo Atual</th>
                            <th>Total PL Passivo Anterior</th>
                            <th>Link Notificação</th>
                            <th>Data Relatório</th>
                            <th>Demais Reservas Atuais</th>
                            <th>Demais Reservas Anteriores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Exibindo os dados retornados
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td>" . $row['codigoContaAtivo'] . "</td>";
                            echo "<td>" . $row['codigoContaPassivo'] . "</td>";

                            echo "<td>" . $row['nomeContaAtivo'] . "</td>";
                            echo "<td>" . $row['nomeContaPassivo'] . "</td>";

                            echo "<td>" . $row['valorExercicioAtualAtivo'] . "</td>";
                            echo "<td>" . $row['valorExercicioAtualPassivo'] . "</td>";
                            echo "<td>" . $row['valorExercicioAnteriorAtivo'] . "</td>";
                            echo "<td>" . $row['valorExercicioAnteriorPassivo'] . "</td>";

                            echo "<td>" . $row['valorResultadoAtual'] . "</td>";
                            echo "<td>" . $row['valorResultadoAnterior'] . "</td>";

                            echo "<td>" . $row['valorPatrimonioSocialAtual'] . "</td>";
                            echo "<td>" . $row['valorPatrimonioSocialAnterior'] . "</td>";
                            echo "<td>" . $row['valorAjustePatrimonialAtual'] . "</td>";
                            echo "<td>" . $row['valorAjustePatrimonialAnterior'] . "</td>";

                            echo "<td>" . $row['valorTotalPassivoAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalPassivoAnterior'] . "</td>";

                            echo "<td>" . $row['valorTotalPLAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalPLAnterior'] . "</td>";

                            echo "<td>" . $row['valorTotalAtivoAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalAtivoAnterior'] . "</td>";

                            echo "<td>" . $row['valorTotalAtivoFinanceiroAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalAtivoFinanceiroAnterior'] . "</td>";
                            echo "<td>" . $row['valorTotalPassivoFinanceiroAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalPassivoFinanceiroAnterior'] . "</td>";

                            echo "<td>" . $row['valorTotalAtivoPermanenteAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalAtivoPermanenteAnterior'] . "</td>";
                            echo "<td>" . $row['valorTotalPassivoPermanenteAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalPassivoPermanenteAnterior'] . "</td>";

                            echo "<td>" . $row['valorGarantiasRecebidasAtual'] . "</td>";
                            echo "<td>" . $row['valorGarantiasRecebidasAnterior'] . "</td>";
                            echo "<td>" . $row['valorGarantiasConcedidasAtual'] . "</td>";
                            echo "<td>" . $row['valorGarantiasConcedidasAnterior'] . "</td>";

                            echo "<td>" . $row['valorDireitosConveniadosAtual'] . "</td>";
                            echo "<td>" . $row['valorDireitosConveniadosAnterior'] . "</td>";

                            echo "<td>" . $row['valorObrigacoesConveniadosAtual'] . "</td>";
                            echo "<td>" . $row['valorObrigacoesConveniadosAnterior'] . "</td>";

                            echo "<td>" . $row['valorDireitosContratuaisAtual'] . "</td>";
                            echo "<td>" . $row['valorDireitosContratuaisAnterior'] . "</td>";

                            echo "<td>" . $row['valorObrigacoesContratuaisAtual'] . "</td>";
                            echo "<td>" . $row['valorObrigacoesContratuaisAnterior'] . "</td>";

                            echo "<td>" . $row['valorOutrosAtosAtivoAtual'] . "</td>";
                            echo "<td>" . $row['valorOutrosAtosAtivoAnterior'] . "</td>";
                            echo "<td>" . $row['valorOutrosAtosPassivoAtual'] . "</td>";
                            echo "<td>" . $row['valorOutrosAtosPassivoAnterior'] . "</td>";

                            echo "<td>" . $row['valorSaldoPatrimonialAtual'] . "</td>";
                            echo "<td>" . $row['valorSaldoPatrimonialAnterior'] . "</td>";

                            echo "<td>" . $row['nivelAtivo'] . "</td>";
                            echo "<td>" . $row['nivelPassivo'] . "</td>";

                            echo "<td>" . $row['valorTotalPLPassivoAtual'] . "</td>";
                            echo "<td>" . $row['valorTotalPLPassivoAnterior'] . "</td>";

                            echo "<td>" . $row['linkNotificacao'] . "</td>";

                            echo "<td>" . $row['dataRelatorio'] . "</td>";

                            echo "<td>" . $row['valorDemaisReservasAtual'] . "</td>";
                            echo "<td>" . $row['valorDemaisReservasAnterior'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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