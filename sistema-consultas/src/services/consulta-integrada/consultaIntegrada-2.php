<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();
$users->checkAcess('CNacesso');

$db = Database3::getInstance();
$con = $db->getConnection();

?>

<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <h5><i class="fas fa-search mr-2 mt-2"></i>Consulta Integrada - Busca por Empresas</h5>
    </div>

    <div class="card-body">

      <?php if (empty($_GET['idEmpresa'])) { ?>

        <script type="text/javascript">
          function showInput(val) {
            document.getElementById('nomeInput').style.display = 'none';
            document.getElementById('inscInput').style.display = 'none';
            document.getElementById('cnpjInput').style.display = 'none';
            document.getElementById('emailInput').style.display = 'none';
            document.getElementById('telefoneInput').style.display = 'none';

            if (val == 1) {
              document.getElementById('nomeInput').style.display = 'block';
            } else if (val == 2) {
              document.getElementById('inscInput').style.display = 'block';
            } else if (val == 3) {
              document.getElementById('cnpjInput').style.display = 'block';
            } else if (val == 4) {
              document.getElementById('emailInput').style.display = 'block';
            } else if (val == 5) {
              document.getElementById('telefoneInput').style.display = 'block';
            }
          }
        </script>

        <h5 class="card-title mb-3">Buscar por Empresa</h5>
        <p>Quanto mais preciso os dados de busca, melhor e mais rápido será o resultado.</p>

        <div class="col-md-8 offset-md-2">

          <!-- Formulário de busca -->
          <form action="" method="post">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                  <?php
                  foreach (Helper::$ufList as $val => $value) {
                    $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                    echo "<option value='$val' $selected>$value</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="categoria">Selecione a Categoria:</label>
                <select id="categoria" name="categoria" class="form-control" required>
                  <option value="ALL" selected>Todos</option>
                  <?php
                  foreach (Helper::$catListPj as $val => $value) {
                    $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                    echo "<option value='$val' $selected>$value</option>";
                  }
                  ?>
                </select>
              </div>
            </div>

            <div class="search-type-cards" id="searchTypeCards">
              <label class="search-type-card active">
                <input type="radio" name="flexRadioDefault" value="1" checked>
                <i class="fas fa-building"></i>
                <span>Nome / Razão Social</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="2">
                <i class="fas fa-id-card"></i>
                <span>Inscrição</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="3">
                <i class="fas fa-file-alt"></i>
                <span>CNPJ</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="4">
                <i class="fas fa-envelope"></i>
                <span>E-mail</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="5">
                <i class="fas fa-phone"></i>
                <span>Telefone</span>
              </label>
            </div>

            <div id="nomeInput" class="form-group col-md-12 mt-3">
              <input type="text" id="nome" name="nome" class="form-control" minlength="4" placeholder="Digite o Nome (Razão Social ou Nome Fantasia)" value="<?= $inputPost["nome"] ?>">
            </div>
            <div id="inscInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="inscricao" name="inscricao" class="form-control" minlength="1" placeholder="Digite a Inscrição ou parte dela" value="<?= $inputPost["inscricao"] ?>">
            </div>
            <div id="cnpjInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="cnpj" name="cnpj" class="form-control" minlength="1" maxlength="18" placeholder="Digite o CNPJ ou parte dele" value="<?= $inputPost["cnpj"] ?>">
            </div>
            <div id="emailInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="email" name="email" class="form-control" minlength="1" placeholder="Digite o E-mail ou parte dele" value="<?= $inputPost["email"] ?>">
            </div>
            <div id="telefoneInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="telefone" name="telefone" class="form-control" minlength="1" placeholder="Digite o Telefone ou parte dele" value="<?= $inputPost["telefone"] ?>">
            </div>
            <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3">Pesquisar</button>
          </form>

        </div>

      <?php } ?>

      <!-- Resultado do formulário -->
      <?php
      if (isset($inputPost["submit"])) {

        $erro = false;

        // Construção de queries
        $whereClauses = [];

        if (!empty($inputPost["cro"])) {
          if ($inputPost["cro"] == "ALL") {
            $whereClauses[] = "pj.CRO IS NOT NULL";
          } else {
            $whereClauses[] = "LEFT(pj.CRO, 2) LIKE '%{$inputPost["cro"]}%'";
          }
        } else {
          $erro = "O campo do CRO deve ser inserido.";
        }

        if (!empty($inputPost["categoria"])) {
          if ($inputPost["categoria"] == "ALL") {
            $whereClauses[] = "pj.CategoriaSigla IS NOT NULL";
          } else {
            $whereClauses[] = "pj.CategoriaSigla LIKE '%{$inputPost["categoria"]}%'";
          }
        } else {
          $erro = "O campo de categoria deve ser inserido.";
        }

        if (!empty($inputPost["nome"])) {
          $whereClauses[] = "(pj.RazaoSocial LIKE '%{$inputPost['nome']}%' OR pj.NomeFantasia LIKE '%{$inputPost['nome']}%')";
        }

        if (!empty($inputPost["inscricao"])) {
          $whereClauses[] = "pj.Inscricao LIKE '%{$inputPost["inscricao"]}%'";
        }

        if (!empty($inputPost["cnpj"])) {
          $cnpjSemFormatacao = preg_replace('/[^0-9]/', '', $inputPost['cnpj']);
          $whereClauses[] = "REPLACE(REPLACE(REPLACE(pj.CNPJ, '.', ''), '-', ''), '/', '') LIKE '%{$cnpjSemFormatacao}%'";
        }

        if (!empty($inputPost["email"])) {
          $whereClauses[] = "ec.Email LIKE '%{$inputPost["email"]}%'";
        }

        if (!empty($inputPost["telefone"])) {
          $whereClauses[] = "ec.Telefone LIKE '%{$inputPost["telefone"]}%'";
        }

        // Verificação final antes de executar a query
        if (!$erro && count($whereClauses) > 0) {
          $whereClause = implode(' AND ', $whereClauses);

          try {
            $query = "SELECT 
                        pj.[RazaoSocial],
                        pj.[NomeFantasia],
                        pj.[CNPJ],
                        LEFT(pj.[CRO], 2) AS CRO,
                        pj.[CategoriaSigla],
                        pj.[Inscricao],
                        pj.[Situacao],
                        pj.[DetalheSituacao],
                        pj.[SituacaoFinanceira],
                        ec.[Logradouro],
                        ec.[Numero],
                        ec.[Complemento],
                        ec.[Bairro],
                        ec.[Municipio],
                        ec.[UF],
                        ec.[CEP],
                        ec.[Telefone],
                        ec.[Email],
                        pj.[IdRegistro]
                     FROM 
                        [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa] pj
                     LEFT JOIN 
                        [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Endereco_e_Contato] ec
                     ON 
                        pj.IdRegistro = ec.IdRegistro
                     WHERE $whereClause";
            $stmt = $con->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOexception $error) {
            die("Erro ao retornar os dados: " . $error->getMessage());
          }
        } else {
          echo "<script language='javascript'>
                  window.alert('Preencha ao menos um campo de busca.');
                  window.location.href='consulta-integrada';
                </script>";
          exit;
        }

        // Exibir resultados em tabela
        if (count($result) > 0) {
      ?>

          <div class="row mt-4">
            <div class="col table-responsive">
              <table id="consultaintegrada" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                  <tr>
                    <th scope="col">Razão Social</th>
                    <th scope="col">Nome Fantasia</th>
                    <th scope="col">CNPJ</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Detalhe</th>
                    <th scope="col">Situação Financeira</th>
                    <th width='12%' scope="col">#</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['RazaoSocial'] . "</td>";
                    echo "<td>" . $row['NomeFantasia'] . "</td>";
                    echo "<td>" . $row['CNPJ'] . "</td>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['CategoriaSigla'] . "</td>";
                    echo "<td>" . $row['Inscricao'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['DetalheSituacao'] . "</td>";
                    echo "<td>" . strtoupper($row['SituacaoFinanceira']) . "</td>";
                    echo "<td><a href='consulta-integrada?idEmpresa=" . $row['IdRegistro'] . "&tipoConsulta=2'><button type='button' class='btn btn-secondary btn-sm'><b>Mais Informações</b></button></a></td>";
                    echo "</tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>

      <?php
        } else {
          echo "<div class='alert alert-danger mt-3' role='alert'>
                      <b>Erro!</b> <br>
                      Não foi encontrado nenhum resultado na busca.
                    </div>";
        }
      }
      ?>


      <?php if (!empty($_GET['idEmpresa'])) {

        $idEmpresa = $_GET['idEmpresa'];
        $queryEmpresa = "IdRegistro = :idEmpresa"; // Usar parâmetro para evitar SQL Injection

        // Consultas para as seções "Dados da Empresa", "Endereço e Contato", e "Responsabilidades"
        try {
          $query = "SELECT 
                        [RazaoSocial],
                        [NomeFantasia],
                        [CNPJ],
                        [Categoria],
                        [Inscricao],
                        [Situacao],
                        [DetalheSituacao],
                        [SituacaoFinanceira]
                     FROM 
                        [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa]
                     WHERE " . $queryEmpresa;
          $stmt = $con->prepare($query);
          $stmt->bindParam(':idEmpresa', $idEmpresa, PDO::PARAM_STR);
          $stmt->execute();
          $resultDadosEmpresa = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados: " . $error->getMessage());
        }

        try {
          $query = "SELECT 
                        [Logradouro],
                        [Numero],
                        [Complemento],
                        [Bairro],
                        [Municipio],
                        [UF],
                        [CEP],
                        [Telefone],
                        [Email],
                        [RedeSocial]
                     FROM 
                        [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Endereco_e_Contato]
                     WHERE " . $queryEmpresa;
          $stmt = $con->prepare($query);
          $stmt->bindParam(':idEmpresa', $idEmpresa, PDO::PARAM_STR);
          $stmt->execute();
          $resultDadosContato = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados: " . $error->getMessage());
        }

        try {
          $query = "SELECT 
                        [ResponsavelTecnico],
                        [InscricaoRT],
                        [DataInicio],
                        [DataTermino]
                     FROM 
                        [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Responsabilidade_Tecnica]
                     WHERE " . $queryEmpresa;
          $stmt = $con->prepare($query);
          $stmt->bindParam(':idEmpresa', $idEmpresa, PDO::PARAM_STR);
          $stmt->execute();
          $resultDadosResponsabilidade = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados: " . $error->getMessage());
        }

      ?>

        <!-- Exibir os dados da empresa em cartões -->
        <div class="card shadow mb-4">
          <a href="#collapseCard-1" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-1">
            <h6 class="m-0 font-weight-bold text-primary">Dados da Empresa</h6>
          </a>
          <div class="collapse show" id="collapseCard-1">
            <div class="card-body">
              <?php
              if (!empty($resultDadosEmpresa)) {
                foreach ($resultDadosEmpresa as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Razão Social:</label>
                      <p><?= $row['RazaoSocial'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome Fantasia:</label>
                      <p><?= $row['NomeFantasia'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CNPJ:</label>
                      <p><?= $row['CNPJ'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Categoria:</label>
                      <p><?= $row['Categoria'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Inscrição:</label>
                      <p><?= $row['Inscricao'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Situação:</label>
                      <p><?= $row['Situacao'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Detalhe da Situação:</label>
                      <p><?= $row['DetalheSituacao'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Situação Financeira:</label>
                      <p><?= $row['SituacaoFinanceira'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há dados da empresa cadastrados.';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <a href="#collapseCard-2" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-2">
            <h6 class="m-0 font-weight-bold text-primary">Endereço e Contato</h6>
          </a>
          <div class="collapse" id="collapseCard-2">
            <div class="card-body">
              <?php
              if (!empty($resultDadosContato)) {
                foreach ($resultDadosContato as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Logradouro:</label>
                      <p><?= $row['Logradouro'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Número:</label>
                      <p><?= $row['Numero'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Bairro:</label>
                      <p><?= $row['Bairro'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Município:</label>
                      <p><?= $row['Municipio'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">UF:</label>
                      <p><?= $row['UF'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CEP:</label>
                      <p><?= $row['CEP'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Telefone:</label>
                      <p><?= $row['Telefone'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Email:</label>
                      <p><?= $row['Email'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Rede Social:</label>
                      <p><?= $row['RedeSocial'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há endereço e contato cadastrados.';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <a href="#collapseCard-3" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-3">
            <h6 class="m-0 font-weight-bold text-primary">Responsabilidades</h6>
          </a>
          <div class="collapse" id="collapseCard-3">
            <div class="card-body">
              <?php
              if (!empty($resultDadosResponsabilidade)) {
                foreach ($resultDadosResponsabilidade as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Responsável Técnico:</label>
                      <p><?= $row['ResponsavelTecnico'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Inscrição do Responsável Técnico:</label>
                      <p><?= $row['InscricaoRT'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data Início:</label>
                      <p><?= $row['DataInicio'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data Término:</label>
                      <p><?= $row['DataTermino'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há responsabilidades cadastradas.';
              }
              ?>
            </div>
          </div>
        </div>

        <a href='consulta-integrada'>
          <button type='button' class='btn btn-secondary'><b>Voltar para pesquisa</b></button>
        </a>

      <?php } ?>

    </div>
  </div>

</div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>