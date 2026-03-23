<?php

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database5;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['RP8acesso']) && $row['RP8acesso'] == false)) {
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

$tituloConsulta = 'Consulta de Profissionais por Nome ou CPF';

?>

<div class="col-md-6 offset-md-3 mb-4">
  <form action="" method="post">
    <div class="form-row">
      <div class="form-group col-md-12">
        <label>Tipo de busca:</label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="tipo_busca" id="busca_nome" value="nome" <?= (!isset($inputPost['tipo_busca']) || $inputPost['tipo_busca'] == 'nome') ? 'checked' : '' ?>>
          <label class="form-check-label" for="busca_nome">
            Buscar por Nome
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="tipo_busca" id="busca_cpf" value="cpf" <?= (isset($inputPost['tipo_busca']) && $inputPost['tipo_busca'] == 'cpf') ? 'checked' : '' ?>>
          <label class="form-check-label" for="busca_cpf">
            Buscar por CPF
          </label>
        </div>
      </div>

      <div class="form-group col-md-12">
        <label for="termo_busca">Termo de busca:</label>
        <input type="text" id="termo_busca" name="termo_busca" class="form-control" placeholder="Digite o nome ou CPF do profissional" required value="<?= $inputPost['termo_busca'] ?? '' ?>">
      </div>

      <div class="form-group col-md-12">
        <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
      </div>
    </div>
  </form>
</div>

<?php
if (isset($inputPost["submit"])) {
    $tipoBusca = $inputPost["tipo_busca"] ?? 'nome';
    $termoBusca = trim($inputPost["termo_busca"]);

    if (empty($termoBusca)) {
        echo "<div class='alert alert-warning mt-3' role='alert'>
              <b>Atenção</b> <br>
              Por favor, preencha o termo de busca.
              </div>";
        exit;
    }

    // Validação do CPF se a busca for por CPF
    if ($tipoBusca == 'cpf') {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $termoBusca);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            echo "<div class='alert alert-danger mt-3' role='alert'>
                  <b>CPF Inválido</b> <br>
                  O CPF deve conter 11 dígitos numéricos.
                  </div>";
            exit;
        }
        
        // Verifica se não são todos os mesmos dígitos
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            echo "<div class='alert alert-danger mt-3' role='alert'>
                  <b>CPF Inválido</b> <br>
                  CPF não pode ter todos os dígitos iguais.
                  </div>";
            exit;
        }
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                echo "<div class='alert alert-danger mt-3' role='alert'>
                      <b>CPF Inválido</b> <br>
                      O CPF informado não é válido.
                      </div>";
                exit;
            }
        }
        
        // Formata o CPF para busca (xxx.xxx.xxx-xx)
        $termoBusca = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    try {
        $db = Database5::getInstance();
        $con = $db->getConnection();

        $con->setAttribute(PDO::ATTR_TIMEOUT, 120);
        $con->exec("SET SESSION wait_timeout=300");
        $con->exec("SET SESSION interactive_timeout=300");

        $maxResults = 1000;

        // Construir a query baseada no tipo de busca
        if ($tipoBusca == 'nome') {
            $query = "SELECT 
                        SiglaCRO,
                        SiglaCategoria,
                        Inscricao,
                        Nome,
                        TipoInscricao,
                        CPF,
                        DataInscricaoCRO,
                        Situacao,
                        DetalheSituacao,
                        DataSituacaoAtual,
                        SituacaoFinanceira,
                        IdRegistro
                      FROM 
                        db_prescricao.profissionais
                      WHERE 
                        Nome LIKE :termoBusca
                      ORDER BY 
                        Nome ASC
                      LIMIT :maxResults";
        } else {
            $query = "SELECT 
                        SiglaCRO,
                        SiglaCategoria,
                        Inscricao,
                        Nome,
                        TipoInscricao,
                        CPF,
                        DataInscricaoCRO,
                        Situacao,
                        DetalheSituacao,
                        DataSituacaoAtual,
                        SituacaoFinanceira,
                        IdRegistro
                      FROM 
                        db_prescricao.profissionais
                      WHERE 
                        CPF = :termoBusca
                      ORDER BY 
                        Nome ASC
                      LIMIT :maxResults";
        }

        $stmt = $con->prepare($query);
        if ($tipoBusca == 'nome') {
            $stmt->bindValue(':termoBusca', '%' . strtoupper($termoBusca) . '%', PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':termoBusca', $termoBusca, PDO::PARAM_STR);
        }
        $stmt->bindValue(':maxResults', $maxResults, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) === 0) {
            $tipoBuscaTexto = ($tipoBusca == 'nome') ? 'nome' : 'CPF';
            echo "<div class='alert alert-danger mt-3' role='alert'>
                  <b>Nenhum resultado encontrado</b> <br>
                  Não foi encontrada nenhuma informação com o {$tipoBuscaTexto} informado.
                  Tente refinar sua busca.
                  </div>";
            return;
        }

        echo "<div class='alert alert-success mt-3' role='alert'>
              <b>Busca concluída!</b><br>
              Encontrados " . count($result) . " resultados.
              </div>";

        if (count($result) >= $maxResults) {
            $tipoBuscaTexto = ($tipoBusca == 'nome') ? 'nome mais completo' : 'CPF mais específico';
            echo "<div class='alert alert-warning mt-3' role='alert'>
                  <b>Limite atingido!</b><br>
                  Foram encontrados mais de {$maxResults} resultados. Exibindo apenas os primeiros {$maxResults}.
                  Para uma busca mais específica, tente usar um {$tipoBuscaTexto}.
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
            <table id="tabelaProfissionais" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                    <tr>
                        <th>Sigla CRO</th>
                        <th>Sigla Categoria</th>
                        <th>Inscrição</th>
                        <th>Nome</th>
                        <th>Tipo Inscrição</th>
                        <th>CPF</th>
                        <th>Data Inscrição CRO</th>
                        <th>Situação</th>
                        <th>Detalhe Situação</th>
                        <th>Data Situação Atual</th>
                        <th>Situação Financeira</th>
                        <th>ID Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['SiglaCRO']) ?></td>
                            <td><?= htmlspecialchars($row['SiglaCategoria']) ?></td>
                            <td><?= htmlspecialchars($row['Inscricao']) ?></td>
                            <td><?= htmlspecialchars($row['Nome']) ?></td>
                            <td><?= htmlspecialchars($row['TipoInscricao']) ?></td>
                            <td><?= htmlspecialchars($row['CPF']) ?></td>
                            <td><?= htmlspecialchars($row['DataInscricaoCRO']) ?></td>
                            <td><?= htmlspecialchars($row['Situacao']) ?></td>
                            <td><?= htmlspecialchars($row['DetalheSituacao']) ?></td>
                            <td><?= htmlspecialchars($row['DataSituacaoAtual']) ?></td>
                            <td><?= htmlspecialchars($row['SituacaoFinanceira']) ?></td>
                            <td><?= htmlspecialchars($row['IdRegistro']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
}
?>
