<?php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database5;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['RP1acesso']) && $row['RP1acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-identidade';
    </script>";
    exit;
}

$tituloConsulta = 'Consulta de Prescrições por Nome';

?>

<div class="col-md-6 offset-md-3 mb-4">
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="paciente_nome">Preencha o nome do paciente:</label>
                <input type="text" id="paciente_nome" name="paciente_nome" class="form-control" placeholder="Digite o nome do paciente" required value="<?= htmlspecialchars($inputPost['paciente_nome'] ?? '') ?>">
            </div>
            <div class="form-group col-md-12">
                <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </div>
    </form>
</div>

<?php
if (isset($inputPost["submit"])) {
    $pacienteNome = trim($inputPost["paciente_nome"]);

    try {
        $db = Database5::getInstance();
        $con = $db->getConnection();

        // Limite de segurança para grandes volumes
        $maxResults = 1000;

        $query = "SELECT 
                    id,
                    tipo,
                    paciente_nome,
                    paciente_cpf,
                    cd_nome,
                    cpf,
                    insc,
                    uf,
                    `data`
                FROM 
                    db_prescricao.tbl_prescricoes
                WHERE 
                    paciente_nome LIKE :paciente_nome
                ORDER BY 
                    id DESC
                LIMIT :maxResults";

        $stmt = $con->prepare($query);
        $stmt->bindValue(':paciente_nome', '%' . $pacienteNome . '%', PDO::PARAM_STR);
        $stmt->bindValue(':maxResults', $maxResults, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $error) {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Erro de Banco de Dados</b><br>
              " . htmlspecialchars($error->getMessage()) . "
              </div>";
        exit;
    }

    if (count($result) > 0) {
        echo "<div class='alert alert-success mt-3' role='alert'>
              <b>Busca concluída!</b><br>
              Foram encontrados " . count($result) . " resultados.
              </div>";

        if (count($result) >= $maxResults) {
            echo "<div class='alert alert-warning mt-3' role='alert'>
                  <b>Limite atingido!</b><br>
                  Exibindo os primeiros {$maxResults} resultados. Refine sua busca para mais precisão.
                  </div>";
        }
?>

        <div class="row justify-content-end mr-1">
            <form action="ExcelDownload" method="post">
                <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
                <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
                <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
            </form>
        </div>

        <div class="row mt-4">
            <div class="col table-responsive">
                <table id="tabelaPrescricao2" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Inscrição</th>
                            <th scope="col">Nome do Paciente</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">UF</th>
                            <th scope="col">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['cd_nome']) ?></td>
                                <td><?= htmlspecialchars($row['insc']) ?></td>
                                <td><?= htmlspecialchars($row['paciente_nome']) ?></td>
                                <td><?= htmlspecialchars($row['tipo']) ?></td>
                                <td><?= htmlspecialchars($row['uf']) ?></td>
                                <td><?= date('d-m-Y', strtotime($row['data'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php
    } else {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Nenhum resultado encontrado</b><br>
              Tente refinar sua busca.
              </div>";
    }
}
?>
