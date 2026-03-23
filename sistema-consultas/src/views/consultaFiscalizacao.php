<?php 
use Cfo\SisConsultas\database\Database1;

if (isset($_GET['fiscalizadores'])) {

    try {
        $db = Database1::getInstance();
        $con = $db->getConnection();

        $query = "SELECT  tb_g.uf, tb_u.name, tb_u.email, tb_u.telefoneCtt, tb_u.telefoneWpp  from db_sistema_consultas.tbl_users tb_u
                    inner join db_sistema_consultas.tbl_subgrupos tb_s  on tb_u.subgrupo = tb_s.id
                    inner join db_sistema_consultas.tbl_grupos tb_g on tb_u.grupo = tb_g.id 
                    where tb_s.subgrupo = 'Fiscalização - Coordenação'
                    AND tb_u.isActive = 1";

        $stmt = $con->prepare($query);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consultaFiscalizacao view: " . $error->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Erro interno']);
        exit();
    }

    if ($response === false || $response === null) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Serviço indisponível'
        ));
    } else {
        header('Content-Type: application/json');

        echo json_encode($response);
    }

    exit();
}

require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

$labelsInstance = new Labels();
$childLabels = $labelsInstance->getChildLabelsPorSigla("CF");

// A ordem é controlada pelo sistema de display_order no gerenciamento de labels
// Não aplicamos usort() aqui para respeitar a ordenação configurada

Session::CheckSession();

$fiscalizacoesVisitas = [
    1 => 'Estatísticas de Fiscalizações - Categoria x Ano',
    2 => 'Estatísticas de Fiscalizações de pessoas sem inscrições - Tipo pessoa x Ano',
    3 => 'Estatísticas de Fiscalizações por Fiscal - Categoria x Ano',
    4 => 'Estatísticas de Fiscalizações por Fiscal sem Inscrições - Tipo pessoa x Ano',
    5 => 'Estatísticas de Fiscalizações por Tipos de Irregularidades - Categoria x Ano',
    6 => 'Estatísticas de Denúncias x Ano',
    7 => 'Estatísticas de Coordenadores de Fiscalização',
    8 => 'Estatísticas de Fiscalizações por Idade - Categoria x Ano',
    9 => 'Estatísticas de Quantidade de Fiscais',
    10 => 'Estatísticas de Nomes dos Fiscais',
];

$fiscalizacoesTermos = [
    11 => 'Estatísticas de Fiscalizações por Termos - Por Categoria e Período',
    12 => 'Estatísticas de Fiscalizações de Pessoas Sem Inscrições - Por Tipo pessoa e Período',
    13 => 'Estatísticas de Fiscalizações por Fiscal - Por Categoria e Período',
    14 => 'Estatísticas de Fiscalizações por Fiscal sem Inscrições - Por Tipo pessoa e Período',
    15 => 'Estatísticas de Fiscalizações por Tipos de Irregularidades - Por Categoria e Período',
    16 => 'Estatísticas de Denúncias - Por Período',
    17 => 'Estatísticas de Coordenadores de Fiscalização',
    18 => 'Estatísticas de Fiscalizações - Por Idade e Período',
    19 => 'Estatísticas de Quantidade de Fiscais',
    20 => 'Estatísticas de Nomes dos Fiscais',
];

?>

<style>
.btn-container {
    display: block;
    width: 100%;
    margin-bottom: 10px;
}

.btn-edit {
    width: 100%;
    text-align: left;
}
</style>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-search-plus mr-2 mt-2"></i>Consultas Fiscalização</h5>
        </div>

        <div class="card-body">
            <!-- Menu -->
            <div class="row d-flex justify-content-center mt-3 mb-3">
                <!-- Box para Sistema SISCAF -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Fiscalizações Baseadas em Visitas</h6>
                        </div>
                        <div class="card-body text-center">
                            <?php foreach($childLabels as $label) { ?>
                                <?php if ($label['grupo'] == 0 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CF'.$label['referencial'].'acesso']) && $row['CF'.$label['referencial'].'acesso'] == true)) { ?>
                                        <a href="/consulta-fiscalizacao?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= (($inputGet['tipoConsulta'] ?? '') === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md btn-edit"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao']) ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= htmlspecialchars($label['nome']) ?>
                                            </button>
                                        </a>
                                    <?php } ?>
                                <?php }?>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Box para Sistema SISDOC/FISCALIZAÇÃO -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h6>Fiscalizações Baseadas em Termos</h6>
                        </div>
                        <div class="card-body text-center">

                            <?php foreach($childLabels as $label) { ?>
                                <?php if ($label['grupo'] == 1 && $label['disabled'] != 1){?>
                                    <?php if (Session::get('grupo') === 0 || (isset($row['CF'.$label['referencial'].'acesso']) && $row['CF'.$label['referencial'].'acesso'] == true)) { ?>
                                        <a href="/consulta-fiscalizacao?tipoConsulta=<?= htmlspecialchars($label['referencial']) ?>" class="btn-container">
                                            <button 
                                                type="button" 
                                                class="btn btn-<?= (($inputGet['tipoConsulta'] ?? '') === (string)$label['referencial']) ? 'primary active' : 'secondary' ?> btn-md btn-edit"
                                                data-bs-toggle="popover"
                                                data-bs-html="true"
                                                data-bs-placement="bottom"
                                                data-bs-content='<?= htmlspecialchars($label['descricao']) ?>'
                                                data-bs-trigger="hover"
                                            >
                                                <?= htmlspecialchars($label['nome']) ?>
                                            </button>
                                        </a>
                                    <?php } ?>
                                <?php }?>
                            <?php } ?>

                        </div>
                    </div>
                </div>

            </div>

            <?php
                $tipoConsulta = intval($inputGet['tipoConsulta'] ?? 0);
                $servicePath = SERVICES_PATH . '/consulta-fiscalizacao/consultaFiscalizacao-' . $tipoConsulta . '.php';
                if ($tipoConsulta >= 1 && $tipoConsulta <= 20 && file_exists($servicePath)) {
                    require_once $servicePath;
                }
            ?>

        </div>
    </div>
</div>

<?php 
require_once INC_PATH . '/footer.php';
?>
