<?php
// consulta-prescricao-por-data.php

// Carrega o autoload do Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Define o caminho onde está o arquivo .env (no diretório "src")
$dotenvPath = __DIR__ . '/../../';
if (!file_exists($dotenvPath . '.env')) {
    error_log("Consulta Identidade 8 - Arquivo .env não encontrado em: " . $dotenvPath);
    echo "<div class='alert alert-danger'>Arquivo .env não encontrado.</div>";
    return;
}

// Carrega as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
Session::CheckSession();

// Recupera o token da API a partir das variáveis de ambiente
$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    echo "<div class='alert alert-danger'>Token da API não configurado no .env</div>";
    return;
}

$tituloConsulta = 'Consulta de Identidade por Data';

// Recebe a data selecionada (formulário via POST)
$data_selecionada = $inputPost['data_selecionada'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloConsulta) ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        .alert { padding: 15px; background-color: #f44336; color: white; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($tituloConsulta) ?></h1>
    <div class="col-md-6 offset-md-3 mb-4">
        <form action="" method="post">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="data_selecionada">Selecione a Data:</label>
                    <input type="date" id="data_selecionada" name="data_selecionada" class="form-control" required value="<?= htmlspecialchars($data_selecionada) ?>">
                </div>
                <div class="form-group col-md-12">
                    <button type="submit" name="submit" class="btn" style="background-color:#007bff; color:#fff;">Pesquisar</button>
                </div>
            </div>
        </form>
    </div>

<?php
if (!empty($data_selecionada)) {
    // Para este exemplo, usaremos os valores fixos de paginação
    $page_number = 1;
    $page_amount = 100;

    // Monta a URL da API para consulta de identidades
    $apiUrl = "https://id.cfo.org.br/api/consulta/lista/identidade?token=" . urlencode($token)
             . "&page_number=" . urlencode($page_number)
             . "&page_amount=" . urlencode($page_amount);

    // Inicia a requisição cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "<div class='alert'>Erro na requisição: " . curl_error($ch) . "</div>";
        curl_close($ch);
        exit;
    }
    curl_close($ch);

    // Decodifica a resposta JSON
    $data = json_decode($response, true);

    if (!isset($data['list'])) {
        echo "<div class='alert'>Erro: resposta inválida ou sem dados da API.</div>";
    } else {
        $identidades = $data['list'];
        // Filtra os registros cujo campo 'data_solicitacao' (formato "dd/mm/yyyy hh:mm:ss")
        // tenha a mesma data (Y-m-d) que a data selecionada.
        $registrosFiltrados = array_filter($identidades, function($item) use ($data_selecionada) {
            if (!isset($item['data_solicitacao'])) return false;
            // Converte a data_solicitacao para "Y-m-d"
            $dataItem = DateTime::createFromFormat('d/m/Y H:i:s', $item['data_solicitacao']);
            if (!$dataItem) {
                // Se o formato for diferente, tente converter com strtotime
                $dataItem = new DateTime($item['data_solicitacao']);
            }
            return $dataItem && $dataItem->format('Y-m-d') === $data_selecionada;
        });

        if (empty($registrosFiltrados)) {
            echo "<div class='alert' style='background-color:#ff9800;'>Nenhum registro encontrado para a data informada.</div>";
        } else {
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CRO</th>
                        <th>Categoria</th>
                        <th>Inscrição</th>
                        <th>CPF</th>
                        <th>AR</th>
                        <th>Data Solicitação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrosFiltrados as $item): ?>
                        <tr>
                            <td>
                                <?php 
                                // 'nome' é um array; junta os nomes
                                echo is_array($item['nome']) ? htmlspecialchars(implode(" ", $item['nome'])) : htmlspecialchars($item['nome']);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($item['cro'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['categoria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['inscricao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['cpf'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['ar'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['data_solicitacao'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }
}
?>
</div>
</body>
</html>
