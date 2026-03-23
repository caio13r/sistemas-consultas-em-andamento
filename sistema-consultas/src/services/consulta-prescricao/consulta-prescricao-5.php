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

$tituloConsulta = 'Consulta dos Últimos 3000 Registros';

try {
    $db = Database5::getInstance();
    $con = $db->getConnection();

    $query = "SELECT 
                uf,
                insc,
                cd_nome,
                paciente_nome,
                id,
                tipo,
                STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') as `data`
            FROM 
                db_prescricao.tbl_prescricoes
            ORDER BY 
                STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') DESC
            LIMIT 3000";
    $stmt = $con->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOexception $error) {
    error_log("Erro consulta prescricao-5: " . $error->getMessage());
    echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
    $result = [];
}

if (count($result) > 0) {
?>

    <?php if (!empty($result)) { ?>
        <div class="row justify-content-end mr-1">
            <form action="ExcelDownload" method="post">
                <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
                <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
                <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
            </form>
        </div>
    <?php } ?>

    <div class="row mt-4">
        <div class="col table-responsive">
            <table id="tabelaPrescricao5" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th scope="col">UF</th>
                        <th scope="col">Inscrição</th>
                        <th scope="col">Nome do(a) Cirurgião(ã)-Dentista</th>
                        <th scope="col">Nome do Paciente</th>
                        <th scope="col">Cod Validação</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Data</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['uf']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['insc']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['cd_nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['paciente_nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tipo']) . "</td>";
                        echo "<td>" . date('d-m-Y H:i', strtotime($row['data'])) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<?php } else {
    echo "<div class='alert alert-danger mt-3' role='alert'>
          <b>Erro</b> <br>
          Não foi encontrada nenhuma informação.
          </div>";
}
?>