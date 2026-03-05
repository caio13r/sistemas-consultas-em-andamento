<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CI2acesso'] == false) {
    echo "<script>alert('Você não tem permissão para acessar essa página.');window.location.href='consulta-identidade';</script>";
    exit;
}



$tipoConsulta = $_GET['tipoConsulta'] ?? '';
$uf = $_GET['uf'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$inscricao = $_GET['inscricao'] ?? '';
$token = $_ENV['API_TOKEN'] ?? null;

$codigoUF = [
    "AL" => 1, "AM" => 2, "BA" => 3, "CE" => 4, "DF" => 5, "ES" => 6, "GO" => 7,
    "MA" => 8, "MG" => 9, "MS" => 10, "MT" => 11, "PA" => 12, "PB" => 13, "PE" => 14,
    "PI" => 15, "PR" => 16, "RJ" => 17, "RN" => 18, "RS" => 19, "SC" => 20, "SE" => 21,
    "SP" => 22, "RO" => 23, "AC" => 24, "AP" => 25, "RR" => 26, "TO" => 27
];

$codigoCategoria = [
    "APD" => "09", "ASB" => "08", "CD" => "01", "TPD" => "03", "TSB" => "07"
];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Identidade Descartadas Antes da Fila de Impressão por Período</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<body>
<div class="container-fluid">
    <h1 class="mt-4">Consulta de Identidade Descartadas Antes da Fila de Impressão por Inscrição</h1>

<div class="col-md-6 offset-md-3 mb-4">
    <form method="GET" action="">
        <input type="hidden" name="tipoConsulta" value="2">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="uf">UF:</label>
                <select name="uf" id="uf" class="form-control" required>
                    <option value="">Todos</option>
                    <?php
                    foreach (Helper::$ufList as $sigla => $nome) {
                        $selected = ($uf === $sigla) ? 'selected' : '';
                        echo "<option value='$sigla' $selected>$nome</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group col-md-12">
                <label for="categoria">Categoria:</label>
                <select name="categoria" id="categoria" class="form-control" required>
                    <option disabled selected>Selecione a Categoria</option>
                    <?php
                    foreach ($codigoCategoria as $cat => $code) {
                        $selected = ($categoria === $cat) ? 'selected' : '';
                        echo "<option value='$cat' $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group col-md-12">
                <label for="inscricao">Inscrição (6 dígitos):</label>
                <input type="text" name="inscricao" id="inscricao" class="form-control" maxlength="6" required value="<?= htmlspecialchars($inscricao) ?>">
            </div>

            <button type="submit" class="btn btn-primary mt-3">Consultar</button>
        </div>
    </form>
</div>

<?php
if (!empty($uf) && !empty($categoria) && !empty($inscricao)) {
    if (!$token) {
        echo "<div class='alert alert-danger'>Token não configurado.</div>";
        return;
    }

    // Montagem do ID
    $codigoUfFinal = $codigoUF[$uf] + 100;
    $codigoCategoriaFinal = $codigoCategoria[$categoria];
    $idFinal = $codigoUfFinal . $codigoCategoriaFinal . str_pad($inscricao, 6, '0', STR_PAD_LEFT);

    echo "<div class='mt-3'><strong>ID Gerado:</strong> $idFinal</div>";

    $url = "http://192.168.161.165:8082/api/consulta/descarte?token=$token&id=$idFinal";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    echo "<div class='mt-3'><strong>Situação:</strong></div>";

    // echo "<div class='mt-3'><strong>Resposta da API:</strong><pre>" . htmlspecialchars($response) . "</pre></div>";

    if (isset($data['descarte']) && is_array($data['descarte']) && count($data['descarte']) > 0) {
        echo "<div class='row mt-4'><div class='col table-responsive'>";
        echo "<table class='table table-sm table-bordered table-striped'>";
        echo "<thead><tr><th>Data</th><th>Detalhes</th></tr></thead><tbody>";
        foreach ($data['descarte'] as $desc) {
            echo "<tr><td>{$desc['create_at']}</td><td>{$desc['detail']}</td></tr>";
        }
        echo "</tbody></table></div></div>";
    } elseif ($data['descarte'] === "Nenhuma situação encontrada") {
        echo "<div class='alert alert-info mt-3'>Nenhuma situação encontrada para esta identidade.</div>";
    } else {
        echo "<div class='alert alert-warning mt-3'>Nenhuma situação encontrada.</div>";
    }
}
?>

</div>
</body>
</html>
?>
