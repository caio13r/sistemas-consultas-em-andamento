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

// Aumenta limites para segurança
ini_set('max_execution_time', 120); // 2 minutos
ini_set('memory_limit', '256M');
set_time_limit(120);

$tituloConsulta = 'Consulta de Prescrições por Nome';

?>

<div class="col-md-6 offset-md-3 mb-4">
  <form action="" method="post">
    <div class="form-row">
      <div class="form-group col-md-12">
        <label for="cd_nome">Preencha o nome:</label>
        <input type="text" id="cd_nome" name="cd_nome" class="form-control" placeholder="Digite o nome" required value="<?= $inputPost['cd_nome'] ?? '' ?>">
      </div>

      <div class="form-group col-md-12">
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
      </div>
    </div>
  </form>
</div>

<?php
if (isset($inputPost["submit"])) {
    $cdNome = trim($inputPost["cd_nome"]);

    if (empty($cdNome)) {
        echo "<div class='alert alert-warning mt-3' role='alert'>
              <b>Atenção</b> <br>
              Por favor, preencha o nome para pesquisar.
              </div>";
        exit;
    }

    try {
        $db = Database5::getInstance();
        $con = $db->getConnection();

        $con->setAttribute(PDO::ATTR_TIMEOUT, 120);
        $con->exec("SET SESSION wait_timeout=300");
        $con->exec("SET SESSION interactive_timeout=300");

        $maxResults = 1000;

        $query = "SELECT 
                    id,
                    psc,
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
                    cd_nome LIKE :cdNome
                  ORDER BY 
                    id DESC
                  LIMIT :maxResults";

        $stmt = $con->prepare($query);
        $stmt->bindValue(':cdNome', '%' . $cdNome . '%', PDO::PARAM_STR);
        $stmt->bindValue(':maxResults', $maxResults, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) === 0) {
            echo "<div class='alert alert-danger mt-3' role='alert'>
                  <b>Nenhum resultado encontrado</b> <br>
                  Não foi encontrada nenhuma informação com o nome informado.
                  Tente refinar sua busca.
                  </div>";
            return;
        }

        echo "<div class='alert alert-success mt-3' role='alert'>
              <b>Busca concluída!</b><br>
              Encontrados " . count($result) . " resultados.
              </div>";

        if (count($result) >= $maxResults) {
            echo "<div class='alert alert-warning mt-3' role='alert'>
                  <b>Limite atingido!</b><br>
                  Foram encontrados mais de {$maxResults} resultados. Exibindo apenas os primeiros {$maxResults}.
                  Para uma busca mais específica, tente usar um nome mais completo.
                  </div>";
        }

    } catch (PDOException $error) {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Erro de Banco de Dados</b> <br>
              Erro ao retornar os dados: " . htmlspecialchars($error->getMessage()) . "
              </div>";
        exit;
    } catch (Exception $error) {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Erro</b> <br>
              Erro inesperado: " . htmlspecialchars($error->getMessage()) . "
              </div>";
        exit;
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
                        <th>Nome</th>
                        <th>Inscrição</th>
                        <th>Nome do Paciente</th>
                        <th>CPF Paciente</th>
                        <th>Tipo</th>
                        <th>PSC</th>
                        <th>CPF Profissional</th>
                        <th>UF</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['cd_nome']) ?></td>
                            <td><?= htmlspecialchars($row['insc']) ?></td>
                            <td><?= htmlspecialchars($row['paciente_nome']) ?></td>
                            <td><?= htmlspecialchars($row['paciente_cpf']) ?></td>
                            <td><?= htmlspecialchars($row['tipo']) ?></td>
                            <td><?= htmlspecialchars($row['psc']) ?></td>
                            <td><?= htmlspecialchars($row['cpf']) ?></td>
                            <td><?= htmlspecialchars($row['uf']) ?></td>
                            <td><?= htmlspecialchars($row['data']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
}
?>
