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
 

      <?php if (empty($inputGet['idProfissional'])) { ?>

        <script type="text/javascript">
          function showInput(val) {
            document.getElementById('nomeInput').style.display = val == 1 ? 'block' : 'none';
            document.getElementById('inscInput').style.display = val == 2 ? 'block' : 'none';
            document.getElementById('cpfInput').style.display = val == 3 ? 'block' : 'none';
            document.getElementById('emailInput').style.display = val == 4 ? 'block' : 'none';
                document.getElementById('telefoneInput').style.display = val == 5 ? 'block' : 'none';


            if (val != 1) document.getElementById('nome').value = '';
            if (val != 2) document.getElementById('insc').value = '';
            if (val != 3) document.getElementById('cpf').value = '';
            if (val != 4) document.getElementById('email').value = '';
            if (val != 5) document.getElementById('telefone').value = '';

          }
        </script>

        <h5 class="card-title mb-3">Buscar por profissional</h5>
       <p>
          Quanto mais preciso os dados de busca, melhor e mais rápido será o resultado.<br>As consultas retornam 2000 resultados.
         </p>

        <div class="col-md-12 offset-md-12">
          <form action="" method="post">
            <div class="form-row">
            <div class="form-group col-md-6">
  <label for="cro">Selecione o Estado:</label>
  <select id="cro" name="cro" class="form-control" required>
    <?php
    if (Session::get('grupo') === 0 || $row['CNselect'] == true) {
      foreach (Helper::$ufList as $val => $value) {
        $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
        echo "<option value='$val' $selected>$value</option>";
      }
    } else {
      foreach (Helper::$ufList as $val => $value) {
        if ($users->CheckGroupUf() == $val) {
          $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
          echo "<option value='$val' $selected>$value</option>";
        }
      }
    }
    ?>
  </select>
</div>
<div class="form-group col-md-6">
  <label for="categoria">Selecione a Categoria:</label>
  <select id="categoria" name="categoria" class="form-control" required>
    <option value="ALL" selected>Todos</option>
    <?php
    foreach (Helper::$catListPf as $val => $value) {
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
                <i class="fas fa-user"></i>
                <span>Nome</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="2">
                <i class="fas fa-id-card"></i>
                <span>Inscrição</span>
              </label>
              <label class="search-type-card">
                <input type="radio" name="flexRadioDefault" value="3">
                <i class="fas fa-address-card"></i>
                <span>CPF</span>
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
<div id="telefoneInput" class="form-group col-md-12 mt-3" style="display:none;">
  <input type="text" id="telefone" name="telefone" class="form-control" minlength="4" placeholder="Digite o telefone ou parte dele" value="<?= $inputPost["telefone"] ?>"></input>
</div>


            </div>
            <div id="nomeInput" class="form-group col-md-12 mt-3">
              <input type="text" id="nome" name="nome" class="form-control" minlength="4" placeholder="Digite o nome" value="<?= $inputPost["nome"] ?>"></input>
            </div>
            
            <div id="inscInput" class="form-group col-md-12 mt-3" style="display:none;">
    <input type="text" id="insc" name="insc" class="form-control" minlength="1" placeholder="Digite a Inscrição ou parte dela" value="<?= $inputPost["insc"] ?>">
</div>


            <div id="cpfInput" class="form-group col-md-12 mt-3" style="display:none;">
    <input type="text" id="cpf" name="cpf" class="form-control" minlength="4" maxlength="14" placeholder="Digite o CPF ou parte dele" value="<?= $inputPost["cpf"] ?>" onkeyup="maskCPF(this)" onfocus="maskCPF(this)">
</div>

<script type="text/javascript">
function maskCPF(input) {
    let value = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    value = value.replace(/(\d{3})(\d)/, "$1.$2");
    value = value.replace(/(\d{3})(\d)/, "$1.$2");
    value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    input.value = value;
}
</script>
            <div id="emailInput" class="form-group col-md-12 mt-3" style="display:none;">
  <input type="text" id="email" name="email" class="form-control" minlength="5" placeholder="Digite o e-mail" value="<?= $inputPost["email"] ?>"></input>
</div>
            <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3">Pesquisar</button>
          </form>
        </div>

      <?php } ?>

      <?php
      if (isset($inputPost["submit"])) {

        $erro = false;

        $conditions = [];

        if (!empty($inputPost["cro"])) {
          if ($inputPost["cro"] == "ALL") {
            $conditions[] = "CRO IS NOT NULL";
          } else {
            $conditions[] = "LEFT(CRO, 2) LIKE '%{$inputPost["cro"]}%'";
          }
        } else {
          $erro = "O campo do CRO deve ser inserido.";
        }

        if (!empty($inputPost["categoria"])) {
          if ($inputPost["categoria"] == "ALL") {
            $conditions[] = "Categoria IS NOT NULL";
          } else {
            $conditions[] = "LEFT(Categoria, 3) LIKE '%{$inputPost["categoria"]}%'";
          }
        } else {
          $erro = "O campo de categoria deve ser inserido.";
        }

        if (!empty($inputPost["nome"])) {
          if (strlen($inputPost["nome"]) < 4) {
            $erro = "O nome deve possuir 4 ou mais caracteres.";
          } else {
            $conditions[] = "Nome LIKE '%{$inputPost['nome']}%'";
          }
        }

        if (!empty($inputPost["insc"])) {
          $conditions[] = "Inscricao LIKE '%{$inputPost["insc"]}%'";
      }
        if (!empty($inputPost["cpf"])) {
          $conditions[] = "CPF LIKE '%{$inputPost['cpf']}%'";
      }

        if (!empty($inputPost["email"])) {
          $conditions[] = "dp.IdRegistro IN (SELECT ec.IdRegistro FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato ec WHERE ec.Email LIKE '%{$inputPost['email']}%')";
        }


        if (!empty($inputPost["telefone"])) {
          $conditions[] = "dp.IdRegistro IN (SELECT ec.IdRegistro FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato ec WHERE ec.Telefone LIKE '%{$inputPost['telefone']}%')";
        }
        if ($erro) {
          echo "<script language='javascript'>
                  window.alert('$erro')
                  window.location.href='consulta-integrada';
                  </script>";
          exit;
        }

        $queryProfissional = "SELECT  TOP 2000
                                Nome, CPF, CroSigla, CategoriaSigla AS Categoria, Inscricao, 
                                TipoDeInscricao AS 'Tipo de Inscrição', Situacao, 
                                DetalheSituacao AS Detalhe, SituacaoFinanceira AS 'Situação Financeira', IdRegistro
                              FROM 
                                CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional dp";

        if (!empty($conditions)) {
          $queryProfissional .= " WHERE " . implode(' AND ', $conditions);
        }

        // Limitação de registros a 2000   ORDER BY Nome ASC 
        // $queryProfissional .= "OFFSET 0 ROWS FETCH NEXT 2000 ROWS ONLY";

        try {
          $stmt = $con->prepare($queryProfissional);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
          die("Erro ao retornar os dados: " . $error->getMessage());
        }

        if (count($result) > 0) {
      ?>

          <div class="row mt-4">
            <div class="col table-responsive">
              <table id="consultaintegrada" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                  <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Tipo de Inscrição</th>
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
                    echo "<td>" . $row['Nome'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['CroSigla'] . "</td>";
                    echo "<td>" . $row['Categoria'] . "</td>";
                    echo "<td>" . $row['Inscricao'] . "</td>";
                    echo "<td>" . $row['Tipo de Inscrição'] . "</td>";
                    echo "<td>" . $row['Situacao'] . "</td>";
                    echo "<td>" . $row['Detalhe'] . "</td>";
                    echo "<td>" . strtoupper($row['Situação Financeira']) . "</td>";
                    echo "<td><a href='consulta-integrada?idProfissional=" . $row['IdRegistro'] . "'><button type='button' class='btn btn-secondary btn-sm'><b>Mais Informações</b></button></a></td>";
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

      <?php if (!empty($inputGet['idProfissional'])) {

        $idProfissional = $inputGet['idProfissional'];
        $queryProfissional = "dp.IdRegistro = :idProfissional";

        try {
          $queryProfissionalDados = "SELECT 
                                        Nome, Cro, CPF, Categoria AS Categoria, Inscricao, TipoDeInscricao AS 'Tipo de Inscrição',
                                        Situacao, DetalheSituacao AS Detalhe, DataInscricao AS 'Data da Inscrição', 
                                        DataSituacao AS 'Data da Situação', SituacaoFinanceira AS 'Situação Financeira'
                                      FROM 
                                        CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional dp
                                      WHERE " . $queryProfissional;
          $stmt = $con->prepare($queryProfissionalDados);
          $stmt->bindValue(':idProfissional', $idProfissional);
          $stmt->execute();
          $resultDadosProfissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados profissionais: " . $error->getMessage());
        }

        try {
          $queryPessoal = "SELECT 
                            dp.CPF, dp.DataNascimento AS 'Data de Nascimento', dp.Genero AS 'Gênero', dp.NomeDaMae AS 'Nome da Mãe',
                            dp.NomeDoPai AS 'Nome do Pai', dp.EstadoCivil AS 'Estado Civil', dp.Nacionalidade, dp.Naturalidade, 
                            dp.Identidade, dp.OrgaoEmissor AS 'Orgão Emissor', dp.UF, dp.DataEmissaoRG AS 'Data da Emissão do RG', 
                            dp.NomeSocial AS 'Nome Social', ec.Email
                           FROM 
                            CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_Pessoais dp
                           LEFT JOIN
                            CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato ec
                           ON dp.IdRegistro = ec.IdRegistro
                           WHERE " . $queryProfissional;
          $stmt = $con->prepare($queryPessoal);
          $stmt->bindValue(':idProfissional', $idProfissional);
          $stmt->execute();
          $resultDadosPessoais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados pessoais: " . $error->getMessage());
        }

        try {
          $queryFormacao = "SELECT 
                              InstituicaoDeEnsino AS 'Instituição de Ensino', Curso, DataDeColacao AS 'Data de Colação',
                              DataDeConclusao AS 'Data de Conclusão', Especialidades, Habilitacao AS 'Habilitação'
                            FROM 
                              CFO_CWS.dbo.Cons_Visao_Nacional_PF_Formacoes
                            WHERE IdRegistro = :idProfissional";
          $stmt = $con->prepare($queryFormacao);
          $stmt->bindValue(':idProfissional', $idProfissional);
          $stmt->execute();
          $resultDadosFormacao = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados de formação: " . $error->getMessage());
        }

        try {
          $queryEnderecoContato = "SELECT 
                                     TipoEndereco AS 'Tipo do Endereço', Logradouro, Numero AS 'Número', 
                                     Complemento, Bairro, Municipio AS 'Município', UF, CEP, Telefone, 
                                     Email, RedeSocial AS 'Rede Social'
                                   FROM 
                                     CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato
                                   WHERE IdRegistro = :idProfissional";
          $stmt = $con->prepare($queryEnderecoContato);
          $stmt->bindValue(':idProfissional', $idProfissional);
          $stmt->execute();
          $resultDadosEndereco = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados de endereço e contato: " . $error->getMessage());
        }

        try {
          $queryResponsabilidade = "SELECT 
                                      TipoResponsabilidade AS 'Tipo da Responsabilidade', 
                                      RazaoSocialDaEmpresa AS 'Razão Social da Empresa', 
                                      NomeFantasiaDaEmpresa AS 'Nome Fantasía da Empresa', 
                                      CNPJ, CategoriaDaEmpresa AS 'Categoria da Empresa', 
                                      RegistroDaEmpresa AS 'Registro da Empresa', 
                                      DataInicio AS 'Data Inicio', Datatermino AS 'Data Término'
                                    FROM 
                                      CFO_CWS.dbo.Cons_Visao_Nacional_PF_Responsabilidade_Tecnica
                                    WHERE IdRegistro = :idProfissional";
          $stmt = $con->prepare($queryResponsabilidade);
          $stmt->bindValue(':idProfissional', $idProfissional);
          $stmt->execute();
          $resultDadosResponsabilidade = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOexception $error) {
          die("Erro ao retornar os dados de responsabilidade técnica: " . $error->getMessage());
        }

      ?>

        <div class="card shadow mb-4">
          <a href="#collapseCard-1" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-1">
            <h6 class="m-0 font-weight-bold text-primary">Dados do Profissional</h6>
          </a>
          <div class="collapse show" id="collapseCard-1">
            <div class="card-body">
              <?php
              if (!empty($resultDadosProfissionais)) {
                foreach ($resultDadosProfissionais as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome:</label>
                      <p><?= $row['Nome'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CRO:</label>
                      <p><?= $row['Cro'] ?></p>
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
                      <label class="font-weight-bold">CPF:</label>
                      <p><?= $row['CPF'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Tipo de Inscrição:</label>
                      <p><?= $row['Tipo de Inscrição'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Situação:</label>
                      <p><?= $row['Situacao'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Detalhe:</label>
                      <p><?= $row['Detalhe'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data da Inscrição:</label>
                      <p><?= $row['Data da Inscrição'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data da Situação:</label>
                      <p><?= $row['Data da Situação'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Situação Financeira:</label>
                      <p><?= $row['Situação Financeira'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há dados do profissional cadastrados.';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <a href="#collapseCard-2" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-2">
            <h6 class="m-0 font-weight-bold text-primary">Dados Pessoais</h6>
          </a>
          <div class="collapse" id="collapseCard-2">
            <div class="card-body">
              <?php
              if (!empty($resultDadosPessoais)) {
                foreach ($resultDadosPessoais as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data de Nascimento:</label>
                      <p><?= $row['Data de Nascimento'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CPF:</label>
                      <p><?= $row['CPF'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Identidade:</label>
                      <p><?= $row['Identidade'] . " - " . $row['Orgão Emissor'] . " - " . $row['UF'] . " - " . $row['Data da Emissão do RG'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Gênero:</label>
                      <p><?= $row['Gênero'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Estado Civil:</label>
                      <p><?= $row['Estado Civil'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Naturalidade:</label>
                      <p><?= $row['Naturalidade'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nacionalidade:</label>
                      <p><?= $row['Nacionalidade'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome Social:</label>
                      <p>
                        <?php
                        $nomeSocial = $row['Nome Social'];
                        if (!empty($nomeSocial)) {
                          echo $row['Nome Social'];
                        } else {
                          echo 'Sem nome social cadastrado';
                        }
                        ?>
                      </p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome da Mãe:</label>
                      <p><?= $row['Nome da Mãe'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome do Pai:</label>
                      <p><?= $row['Nome do Pai'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Email:</label>
                      <p><?= $row['Email'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há dados pessoais cadastrados.';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <a href="#collapseCard-3" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-3">
            <h6 class="m-0 font-weight-bold text-primary">Formações, Especialidades e Habilitações</h6>
          </a>
          <div class="collapse" id="collapseCard-3">
            <div class="card-body">
              <?php
              if (!empty($resultDadosFormacao)) {
                foreach ($resultDadosFormacao as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Curso:</label>
                      <p><?= $row['Curso'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Instituição de Ensino:</label>
                      <p><?= $row['Instituição de Ensino'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data de Conclusão:</label>
                      <p><?= $row['Data de Conclusão'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data de Colação:</label>
                      <p><?= $row['Data de Colação'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Especialidades:</label>
                      <p><?= $row['Especialidades'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Habilitação:</label>
                      <p><?= $row['Habilitação'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há formação cadastrada.';
              }
              ?>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <a href="#collapseCard-4" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-4">
            <h6 class="m-0 font-weight-bold text-primary">Endereço e Contato</h6>
          </a>
          <div class="collapse" id="collapseCard-4">
            <div class="card-body">
              <?php
              if (!empty($resultDadosEndereco)) {
                foreach ($resultDadosEndereco as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Tipo do Endereço:</label>
                      <p><?= $row['Tipo do Endereço'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CEP:</label>
                      <p><?= $row['CEP'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Logradouro:</label>
                      <p><?= $row['Logradouro'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Bairro:</label>
                      <p><?= $row['Bairro'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Número:</label>
                      <p><?= $row['Número'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Município:</label>
                      <p><?= $row['Município'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">UF:</label>
                      <p><?= $row['UF'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Complemento:</label>
                      <p>
                        <?php
                        $complemento = $row['Complemento'];
                        if (!empty($complemento)) {
                          echo $row['Complemento'];
                        } else {
                          echo 'Sem complemento cadastrado';
                        }
                        ?>
                      </p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Telefone:</label>
                      <p>
                        <?php
                        $telefones = preg_replace("/,/", "<br>", $row['Telefone']);
                        echo $telefones;
                        ?>
                      </p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Email:</label>
                      <p>
                        <?php
                        $emails = preg_replace("/,/", "<br>", $row['Email']);
                        echo $emails;
                        ?>
                      </p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Rede Social:</label>
                      <p>
                        <?php
                        $social = preg_replace("/,/", "<br>", $row['Rede Social']);
                        if (!empty($social)) {
                          echo $social;
                        } else {
                          echo 'Sem rede social cadastrada';
                        }
                        ?>
                      </p>
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
          <a href="#collapseCard-5" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-5">
            <h6 class="m-0 font-weight-bold text-primary">Responsabilidades</h6>
          </a>
          <div class="collapse" id="collapseCard-5">
            <div class="card-body">
              <?php
              if (!empty($resultDadosResponsabilidade)) {
                foreach ($resultDadosResponsabilidade as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Tipo de Responsabilidade:</label>
                      <p><?= $row['Tipo da Responsabilidade'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Razão Social da Empresa:</label>
                      <p><?= $row['Razão Social da Empresa'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome Fantasia da Empresa:</label>
                      <p><?= $row['Nome Fantasía da Empresa'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CNPJ:</label>
                      <p><?= $row['CNPJ'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Categoria da Empresa:</label>
                      <p><?= $row['Categoria da Empresa'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Registro da Empresa:</label>
                      <p><?= $row['Registro da Empresa'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data Início:</label>
                      <p><?= $row['Data Inicio'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Data Término:</label>
                      <p><?= $row['Data Término'] ?></p>
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
        <div class="card shadow mb-4">
          <a href="#collapseCard-6" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-6">
            <h6 class="m-0 font-weight-bold text-primary">Processos de Especialidade / Habilitação (Via SISDOC)</h6>
          </a>
          <div class="collapse" id="collapseCard-6">
            <div class="card-body">
              <?php
              try {
                $queryProcesso = "SELECT 
                                    NumeroProcesso, Assunto, Cassificacao AS Classificação, Etapa, 
                                    Andamento, DataAndamento AS 'Data do Andamento'
                                  FROM 
                                    CFO_CWS.dbo.Cons_Visao_Nacional_PF_Processo_de_Especialidade_ou_Habilitacao
                                  WHERE IdRegistro = :idProfissional  " ;
                $stmt = $con->prepare($queryProcesso);
                $stmt->bindValue(':idProfissional', $idProfissional);
                $stmt->execute();
                $resultDadosProcessos = $stmt->fetchAll(PDO::FETCH_ASSOC);
              } catch (PDOException $error) {
                die("Erro ao retornar os dados de processos: " . $error->getMessage());
              }

              if (!empty($resultDadosProcessos)) {
                foreach ($resultDadosProcessos as $row) {
              ?>
                  <div class="processo-box mb-3 p-3 border rounded">
                    <div class="row">
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Número do Processo:</label>
                        <p><?= $row['NumeroProcesso'] ?></p>
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Assunto:</label>
                        <p><?= $row['Assunto'] ?></p>
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Classificação:</label>
                        <p><?= $row['Classificação'] ?></p>
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Etapa:</label>
                        <p><?= $row['Etapa'] ?></p>
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Andamento:</label>
                        <p><?= $row['Andamento'] ?></p>
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Data do Andamento:</label>
                        <p><?= date('d/m/Y', strtotime($row['Data do Andamento'])) ?></p>
                      </div>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo '<div class="alert alert-info">Não há processos cadastrados.</div>';
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

<?php
require_once INC_PATH . '/footer.php';
?>
