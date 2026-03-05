<?php
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;
use Dotenv\Dotenv;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['DA16acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='dados-abertos';
    </script>";
    exit;
}

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 3));
$dotenv->load();

$tituloConsulta = 'Dados Abertos - Estatística de Acesso por Módulo';
?>

<!-- Incluindo CSS e JS do DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="col-md-6 offset-md-3 mb-4">
    <label>Estatística de Acesso por Módulo</label>
    <form action="" method="post">
        <div class="form-group col-md-6">
            <label for="dtInicio">Referencia de Início:</label>
            <input type="mounth" id="dtInicio" name="dtInicio" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <div class="form-group col-md-6">
            <label for="dtTermino">Referencia de Término:</label>
            <input type="mounth" id="dtTermino" name="dtTermino" class="form-control" placeholder="Ex: 01/2024" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Consultar Estatística</button>
    </form>
</div>

<?php
if (isset($_POST["submit"])) {
    try {

        $dtInicio = $_POST["dtInicio"];
        $dtTermino = $_POST["dtTermino"];

        $url = "https://cfo-br.implanta.net.br/portaltransparencia/servico/api/EstatisticaAcessoModulo?referenciaInicio="
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

        function desaninhamento(array $dados): array {
            $resultado = [];
        
            // Função recursiva para processar menus e submenus
            function processarMenu(array $menu, string $modulo, int $acessos, float $percentual, array &$resultado) {
                // Adicionar o menu atual ao resultado descompactado
                $menuAtual = $menu;
                unset($menuAtual['subMenus']); // Remover subMenus para evitar redundância
                
                $dadosDescompactados = [
                    'modulo' => $modulo,
                    'acessos' => $acessos,
                    'percentual' => $percentual,
                ];
                
                // Mesclar os dados do menu diretamente no array superior
                $resultado[] = array_merge($dadosDescompactados, $menuAtual);
            
                // Processar subMenus recursivamente, se existirem
                if (isset($menu['subMenus']) && is_array($menu['subMenus'])) {
                    foreach ($menu['subMenus'] as $submenu) {
                        if (!empty($submenu)) {
                            processarMenu($submenu, $modulo, $acessos, $percentual, $resultado);
                        }
                    }
                }
            }
        
            // Iterar sobre o array principal
            foreach ($dados as $entrada) {
                $modulo = $entrada['modulo'];
                $acessos = $entrada['acessos'];
                $percentual = $entrada['percentual'];
                if (isset($entrada['menu'])) {
                    processarMenu($entrada['menu'], $modulo, $acessos, $percentual, $resultado);
                }
            }
        
            return $resultado;
        }
        
        if (!empty($result) || isset($result['message'])) {
            // Decodificar o JSON em um array associativo
            $dados = $result;
            
            // Chamar a função de desaninhamento
            $result = desaninhamento($dados);
        
            foreach ($result as &$item) {
                $item['modulo'] = $item['modulo'] ?: "-";
                $item['acessos'] = $item['acessos'] ?: "0"; 
                $item['percentual'] = $item['percentual'] ?: "0"; 
                $item['id'] = $item['id'] ?: "0"; 
                $item['idMenu'] = $item['idMenu'] ?: "-"; 
                $item['nome'] = $item['nome'] ?: "-";
                $item['link'] = $item['link'] ?: "-";
                $item['ativo'] = $item['ativo'] ? "True" : "False";
                $item['ordem'] = $item['ordem'] ?: "0"; 
                $item['nivel'] = $item['nivel'] ?: "0";
                $item['descricao'] = $item['descricao'] ?: "-"; 
                $item['idMenuPai'] = $item['idMenuPai'] ?: "-";
                $item['idRelatorioConfiguracao'] = $item['idRelatorioConfiguracao'] ?: "-"; 
                $item['idConteudo'] = $item['idConteudo'] ?: "-";
                $item['tipoMenu'] = $item['tipoMenu'] ?: "0"; 
                $item['idConteudoConselheiro'] = $item['idConteudoConselheiro'] ?: "-"; 
                $item['idListaArquivo'] = $item['idListaArquivo'] ?: "-"; 
                $item['idLinkExterno'] = $item['idLinkExterno'] ?: "-"; 
                $item['idConteudoConselheiroRegiao'] = $item['idConteudoConselheiroRegiao'] ?: "-";
                $item['idGrafico'] = $item['idGrafico'] ?: "-";
                $item['idPlanoCargoSalario'] = $item['idPlanoCargoSalario'] ?: "-";
                $item['idQuadro'] = $item['idQuadro'] ?: "-";
                $item['expandido'] = $item['expandido'] ? "True" : "False";
                $item['acao'] = $item['acao'] ?: "-";
            }
            unset($item);
        }
        
        
        // Exibir o resultado
        //echo "<pre>";
        //print_r($result);
        //echo "</pre>";
        

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
            <!-- Tabela de Estatística de Acesso por Módulo -->
            <h3>Estatística de Acesso por Módulo</h3>
            <table id="tabelaDados" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Acessos</th>
                        <th>Percentual</th>
                        <th>Id</th>
                        <th>Id Menu</th>
                        <th>Nome</th>
                        <th>Link</th>
                        <th>Ativo</th>
                        <th>Ordem</th>
                        <th>Nível</th>
                        <th>Descrição</th>
                        <th>Id Menu Pai</th>
                        <th>Id Relatorio Configuração</th>
                        <th>Id Conteudo</th>
                        <th>Tipo Menu</th>
                        <th>Id Conteudo Conselheiro</th>
                        <th>Id Lista Arquivo</th>
                        <th>Id Link Externo</th>
                        <th>Id Conteudo Conselheiro Regiao</th>
                        <th>Id Grafico</th>
                        <th>Id Plano Cargo Salario</th>
                        <th>Id Quadro</th>
                        <th>Expandido</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Exibindo os dados retornados
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['modulo'] . "</td>";
                        echo "<td>" . $row['acessos'] . "</td>";
                        echo "<td>" . $row['percentual'] . "</td>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['idMenu'] . "</td>";
                        echo "<td>" . $row['nome'] . "</td>";
                        echo "<td>" . $row['link'] . "</td>";
                        echo "<td>" . $row['ativo'] . "</td>";
                        echo "<td>" . $row['ordem'] . "</td>";
                        echo "<td>" . $row['nivel'] . "</td>";
                        echo "<td>" . $row['descricao'] . "</td>";
                        echo "<td>" . $row['idMenuPai'] . "</td>";
                        echo "<td>" . $row['idRelatorioConfiguracao'] . "</td>";
                        echo "<td>" . $row['idConteudo'] . "</td>";
                        echo "<td>" . $row['tipoMenu'] . "</td>";
                        echo "<td>" . $row['idConteudoConselheiro'] . "</td>";
                        echo "<td>" . $row['idListaArquivo'] . "</td>";
                        echo "<td>" . $row['idLinkExterno'] . "</td>";
                        echo "<td>" . $row['idConteudoConselheiroRegiao'] . "</td>";
                        echo "<td>" . $row['idGrafico'] . "</td>";
                        echo "<td>" . $row['idPlanoCargoSalario'] . "</td>";
                        echo "<td>" . $row['idQuadro'] . "</td>";
                        echo "<td>" . $row['expandido'] . "</td>";
                        echo "<td>" . $row['acao'] . "</td>";
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
                "order": [],
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