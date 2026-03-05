<?php
// relatorio-delegadoeleitor.php
// Certifique-se de que não haja espaços ou quebras de linha antes de <?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::init();
Session::CheckSession();

// Recupera os dados enviados (se houver)
$inputPost = $_POST;

// Se o usuário já selecionou uma UF, usamos esse valor; caso contrário, definimos um 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Delegado Eleitor</title>
    <link rel="stylesheet" href="seu-estilo.css">
  
</head>
<body>
    <div class="container-fluid">
        <h5 class="card-title mb-4">Relatório Delegado Eleitor</h5>
        <!-- Formulário para seleção de CRO e título -->
        <div class="col-md-6 offset-md-3">
            <div class="contact-form">
                <form class="book-form" method="POST" action="">
                    <div class="row">
                        <div class="col-md-12 mt-3" id="uf_filter">
                            <label for="uf">Selecione a UF (CRO):</label>
                            <select class="form-control" id="uf" name="uf" required>
                                <option value="" disabled selected>Selecione a UF</option>
                                <?php
                                foreach (Helper::$ufList as $key => $value) {
                                    $selected = (!empty($inputPost['uf']) && $inputPost['uf'] == $key) ? 'selected' : '';
                                    echo "<option value='{$key}' {$selected}>{$value}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                   
                        $cro = $inputPost['uf'];
                        $dataHoje = date('d_m_Y');
                        
                        // Combina a frase, o CRO e a data de hoje para criar o título do relatório
                        $tituloConsulta = "Relatório_Delegado_Eleitor_".$dataHoje."_";
                        ?>
                        <div class="col-md-12 mt-2">
                          
                            <!-- Input hidden para enviar o título gerado dinamicamente -->
                            <input type="hidden" name="tituloConsulta" id="tituloConsulta" class="form-control" value="<?= htmlspecialchars($tituloConsulta) ?>">
                            <!-- Exibe o título para o usuário -->
                            <!-- <p><?= htmlspecialchars($tituloConsulta) ?></p> -->
                        </div>
                        <div class="col-md-12 mt-2">
                            <input type="submit" value="Pesquisar" class="btn btn-primary" style="margin-top: 15px;">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php
        // Se o formulário for submetido e houver seleção de UF, exibe o botão para exportação
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($inputPost['uf'])):
            // Aqui o $uf e $tituloConsulta já foram definidos anteriormente
            $uf = $inputPost['uf'];
            $tituloConsulta = $inputPost['tituloConsulta'] ?? $tituloConsulta;
        ?>
            <div class="col-md-12" style="margin-top:20px;">
                <h4>Download do Excel</h4>
                <p>Clique no botão abaixo para baixar o relatório completo.</p>
                <!-- Formulário para exportação -->
                <form method="POST" action="ExcelDownloadDelegado" id="exportForm">
                    <input type="hidden" name="uf" value="<?= htmlspecialchars($uf) ?>">
                    <input type="hidden" name="tituloConsulta" value="<?= htmlspecialchars($tituloConsulta) ?>">
                    <input type="hidden" name="export" value="1">
                    <input type="submit" value="Exportar Excel" class="btn btn-success">
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
