<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database2;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();
$users->checkAcess('CIacesso');

// if (Session::get('grupo') != 0 && $row['CIacesso'] == false) {
//   echo "<script language='javascript'>
//   window.alert('Você não tem permissão para acessar essa página.')
//   window.location.href='index';
//   </script>";
//   exit;
// }
?>

<div class="container-fluid">

<div class="card">
  <div class="card-header">
    <h5><i class="fas fa-search mr-2 mt-2"></i>Consulta Integrada</h5>
  </div>

  <div class="card-body">

      <?php if (empty($inputGet["nome"]) && empty($inputGet["cro"])) { ?>

      <script type="text/javascript">
        // Função para alterar input
        function showInput(val) {
          if (val == 1) {
            document.getElementById('nomeInput').style.display = 'block';
            document.getElementById('inscInput').style.display = 'none';
            document.getElementById('cpfInput').style.display = 'none';
            document.getElementById('emailInput').style.display = 'none';
            document.getElementById('insc').value = '';
            document.getElementById('cpf').value = '';
            document.getElementById('email').value = '';
          } else if (val == 2) {
            document.getElementById('inscInput').style.display = 'block';
            document.getElementById('nomeInput').style.display = 'none';
            document.getElementById('cpfInput').style.display = 'none';
            document.getElementById('emailInput').style.display = 'none';
            document.getElementById('nome').value = '';
            document.getElementById('cpf').value = '';
            document.getElementById('email').value = '';
          } else if (val == 3) {
            document.getElementById('cpfInput').style.display = 'block';
            document.getElementById('nomeInput').style.display = 'none';
            document.getElementById('inscInput').style.display = 'none';
            document.getElementById('emailInput').style.display = 'none';
            document.getElementById('nome').value = '';
            document.getElementById('insc').value = '';
            document.getElementById('email').value = '';
          } else if (val == 4) {
            document.getElementById('emailInput').style.display = 'block';
            document.getElementById('nomeInput').style.display = 'none';
            document.getElementById('inscInput').style.display = 'none';
            document.getElementById('cpfInput').style.display = 'none';
            document.getElementById('nome').value = '';
            document.getElementById('insc').value = '';
            document.getElementById('cpf').value = '';
          }
        }
      </script>

      <h5 class="card-title mb-4">Buscar por profissional</h5>
      <p>Você está na Consulta Integrada de profissionais. Quanto mais precisos os dados de busca, melhor e mais rápido será o resultado.</p>
        
        <div class="col-md-8 offset-md-2">
  
          <!-- Formulário de busca -->
          <form action="" method="post">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                  <option disabled selected value>Selecione</option>
                  <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CIselect'] == true) {
                        foreach(Helper::$ufList as $val => $value) {
                          $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                          echo "<option value='$val' $selected>$value</option>";
                        }            
                      } else {
                        foreach(Helper::$ufList as $val => $value) {
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
                  <option disabled selected value>Selecione</option>
                  <?php
                    foreach(Helper::$catList as $val => $value) {
                      $selected = (!empty($inputPost['categoria']) && $inputPost['categoria'] == $val) ? 'selected' : '';
                      echo "<option value='$val' $selected>$value</option>";
                    }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1" value='1' onclick="showInput(1)" checked>
                <label class="form-check-label" for="flexRadioDefault1">
                  Pesquisar por Nome
                </label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" value='2' onclick="showInput(2)">
                <label class="form-check-label" for="flexRadioDefault2">
                  Pesquisar por Inscrição
                </label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault3" value='3' onclick="showInput(3)">
                <label class="form-check-label" for="flexRadioDefault3">
                  Pesquisar por CPF
                </label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault4" value='4' onclick="showInput(4)">
                <label class="form-check-label" for="flexRadioDefault4">
                  Pesquisar por E-mail
                </label>
              </div>
              <div id="nomeInput" class="form-group col-md-12 mt-3">
                <input type="text" id="nome" name="nome" class="form-control" minlength="8" placeholder="Digite o nome" value="<?= $inputPost["nome"] ?>"></input>
              </div>
              <div id="inscInput" class="form-group col-md-12 mt-3" style="display:none;">
                <input type="text" id="insc" name="insc" onkeyup="mask('######', this, event, true)" maxlength="8" class="form-control" placeholder="Digite a inscrição" value="<?= $inputPost["insc"] ?>"></input>
              </div>
              <div id="cpfInput" class="form-group col-md-12 mt-3" style="display:none;">
                <input type="text" id="cpf" name="cpf" onkeyup="mask('###.###.###-##', this, event, true)" minlength="14" maxlength="14" class="form-control" placeholder="Digite o CPF" value="<?= $inputPost["cpf"] ?>"></input>
              </div>
              <div id="emailInput" class="form-group col-md-12 mt-3" style="display:none;">
                <input type="text" id="email" name="email" class="form-control" minlength="5" placeholder="Digite o e-mail" value="<?= $inputPost["email"] ?>"></input>
              </div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3">Pesquisar</button>
          </form>
          
        </div>

      <?php } ?>
        
        <!-- Resultado do formulário -->
        <?php 
        if (isset($inputPost["submit"])) { 

          // Validações
          $erro = false;

          if (empty($inputPost["nome"]) && empty($inputPost["insc"]) && empty($inputPost["cpf"]) && empty($inputPost["email"])) {
            $erro = "Ao menos um dos campos de pesquisa deve ser preenchido.";
          }

          if (!empty($inputPost["cro"])) {
            // Query para definir cro
            if ($inputPost["cro"] === "ALL") {
              $croQuery = "WSCFO.siscaf_webservice";
            } else {
              $croQuery = "WSCFO.siscaf_webservice_" . strtolower($inputPost["cro"]);
            }
          } else {
            $erro = "O campo do CRO deve ser inserido.";
          }

          if (!empty($inputPost["categoria"])) {
            // // Query para definir categria
            if ($inputPost["categoria"] === "ALL") {
              $categoriaQuery = null;
            } else {
              $categoriaQuery = "categoriasigla = '{$inputPost["categoria"]}' AND ";
            }
          } else {
            $erro = "O campo de categoria deve ser inserido.";
          }

          if (empty($inputPost["nome"])) {
            $nomeQuery = null;
          } else {
            // Query para pesquisar pelo nome
            $nomeQuery = "nomerazaosocial LIKE _utf8 '%{$inputPost['nome']}%' COLLATE utf8_general_ci";
            // Validação min caractres
            if (strlen($inputPost["nome"]) < 8) {
              $erro = "O nome deve possuir 8 ou mais carácteres.";
            }
          }

          if (empty($inputPost["email"])) {
            $emailQuery = null;
          } else {
            // Query para pesquisar pelo nome
            $emailQuery = "email_correspondencia LIKE _utf8 '%{$inputPost['email']}%' COLLATE utf8_general_ci";
            // Validação min caractres
            if (strlen($inputPost["email"]) < 8) {
              $erro = "O e-mail deve possuir 5 ou mais carácteres.";
            }
          }

          if (empty($inputPost["insc"])) {
            $inscQuery = null;
          } else {
            // Query para pesquisar pela inscrição
            $inscQuery = "inscricao LIKE '{$inputPost["insc"]}'";
          }
          
          if (!empty($inputPost["cpf"])) {
            // Função para checar validade do cpf
            function validaCPF($cpf) {
              $cpf = preg_replace('/[^0-9]/is', '', $cpf);
              if (strlen($cpf) != 11) {
                  return false;
              }
              if (preg_match('/(\d)\1{10}/', $cpf)) {
                  return false;
              }
              for ($t = 9; $t < 11; $t++) {
                  for ($d = 0, $c = 0; $c < $t; $c++) {
                      $d += $cpf[$c] * (($t + 1) - $c);
                  }
                  $d = ((10 * $d) % 11) % 10;
                  if ($cpf[$c] != $d) {
                      return false;
                  }
              }
              return true;
            }
            if(!validaCPF($inputPost["cpf"])) {
                $erro = "Insira um CPF válido.";
            }
            // Query para pesquisar cpf
            $cpfQuery = "cpf_cnpj LIKE '%{$inputPost['cpf']}%'";
          } else {
            $cpfQuery = null;
          }

          if ($erro) {
            echo "<script language='javascript'>
                  window.alert('$erro')
                  window.location.href='consulta-integrada';
                  </script>";
            exit;
          }

          // Busca as informações no banco de dados
          try {
            $db = Database2::getInstance();
            $con = $db->getConnection();

            $query = "SELECT * FROM $croQuery WHERE " . $categoriaQuery . $inscQuery . $cpfQuery . $nomeQuery . $emailQuery;
            $stmt = $con->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOexception $error) {
            // echo $query . "<br>";
            die("Erro ao retornar os dados: " . $error->getMessage());
          }

          // Resuldado busca em tabela
          if (count($result) > 0) {
        ?>
        
          <div class="row mt-4">
            <div class="col table-responsive">
              <table id="consultaintegrada" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                  <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Detalhe</th>
                    <th width='12%' scope="col">#</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    foreach ($result as $row) {
                      echo "<tr>";
                      echo "<td>" . $row['nomerazaosocial'] . "</td>";
                      echo "<td>" . $row['cro'] . "</td>";
                      echo "<td>" . $row['categoriasigla'] . "</td>";
                      echo "<td>" . $row['inscricao'] . "</td>";
                      echo "<td>" . $row['situacao'] . "</td>";
                      echo "<td>" . $row['detalhe_situacao'] . "</td>";
                      echo "<td><a href='consulta-integrada?cro=".$row['cro']."&insc=".$row['inscricao']."&nome=".$row['nomerazaosocial']."'><button type='button' class='btn btn-secondary btn-sm'><b>Mais Informações</b></button></a></td>";
                      echo "</tr>";
                    }

                    try {
                      // Registro das buscas para estatistica
                      $nome = Session::get("name");
                      $grupo = $users->GroupName(Session::get("grupo"));
                      $ip = $_SERVER['REMOTE_ADDR'];
                      $origem = "ConsultaIntegrada";

                      $db = Database1::getInstance();
                      $con = $db->getConnection();

                      $query = "INSERT INTO `tbl_registros` (`nome`,`grupo`, `ip`, `origem`, `data`) VALUES (:nome, :grupo, :ip, :origem, current_timestamp())";
                      $stmt = $con->prepare($query);
                      $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
                      $stmt->bindValue(':grupo', $grupo, PDO::PARAM_STR);
                      $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
                      $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
                      $stmt->execute();
                    } catch (PDOexception $error) {
                        die("Erro ao retornar os dados: " . $error->getMessage());
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
                    Não foi econtrado nunhum resultado na busca.
                    </div>";  
            } 
          }
        ?>
        
        <?php
          // Resuldado das informaçoes detalhadas                  
          if (isset($inputGet['nome']) && isset($inputGet['cro'])) {

          // Busca as informações no banco de dados
          try {
            $db = Database2::getInstance();
            $con = $db->getConnection();

            $cro_table = "WSCFO.siscaf_webservice_" . strtolower($inputGet['cro']);
            $cro = "{$inputGet['cro']}";
            $nome = "%{$inputGet['nome']}%";
            $insc = "{$inputGet['insc']}";
            
            $query = "SELECT * FROM $cro_table WHERE cro LIKE :cro AND inscricao LIKE :insc AND nomerazaosocial LIKE :nome";
            $stmt = $con->prepare($query);
            $stmt->bindValue(':cro', $cro, PDO::PARAM_STR);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':insc', $insc, PDO::PARAM_STR);
            $stmt->execute();
            $total = $stmt->rowCount();
          } catch (PDOexception $error) {
            die("Erro ao retornar os dados: " . $error->getMessage());
          }

          if ($total == 1) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        ?>

          <h5 class="card-title mb-3">Informações do profissional</h5>

          <div class="container-fluid">
            <div class="row mt-4">
              <div class="col">
                <label class="font-weight-bold">Nome:</label> <br>
                <?= $row['nomerazaosocial'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">CPF:</label> <br>
                <?= $row['cpf_cnpj'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Identidade:</label> <br>
                <?= $row['rgnumero'] ?> - <?= $row['rg_orgaoemissor'] ?>-<?= $row['rgsiglaufemissor'] ?> - <?= $row['rg_dataemissao'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Titulo de Eleitor:</label> <br>
                -
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col">
                <label class="font-weight-bold">Estado Civil:</label> <br>
                <?= strtoupper($row['estadocivil']) ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Gênero:</label> <br>
                <?php
                  if ($row['sexo'] === 'F'){
                    $genero = "FEMININO";
                  } else {
                    $genero = "MASCULINO";
                  }
                  echo $genero;
                ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data de Nascimento:</label> <br>
                <?= $row['datanascimentocriacao'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Telefones:</label> <br>
                <?php
                  $telefones = preg_replace("/;/", "<br>", $row['telefones']); 
                  echo $telefones;
                ?>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col">
                <label class="font-weight-bold">E-mails:</label> <br>
                <?= strtoupper($row['email_correspondencia']) ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Nome da Mãe:</label> <br>
                <?= $row['nomemae'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Nome do Pai:</label> <br>
                <?= $row['nomepai'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Nacionalidade:</label> <br>
                <?= $row['nacionalidade'] ?>
              </div>
            </div>
            <hr>
            <div class="row mt-4">

              <div class="col">
                <label class="font-weight-bold">Naturalidade:</label> <br>
                <?= strtoupper($row['naturalidade']) ?> - <?= $row['naturalidadeuf'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Instituição de Ensino:</label> <br>
                <?= $row['formacao_ies'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data da Colação:</label> <br>
                <?= $row['formacao_data_colacao'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data da Conclusão:</label> <br>
                <?= $row['formacao_data_conclusao'] ?>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col">
                <label class="font-weight-bold">Inscrição no CRO:</label> <br>
                <?= $row['cro'] ?> - <?= $row['categoriasigla'] ?> - <?= $row['inscricao'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Tipo de Inscrição:</label> <br>
                <?= $row['tipo_insc'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Situação:</label> <br>
                <?= $row['situacao'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Detalhe da Situação:</label> <br>
                <?= $row['detalhe_situacao'] ?>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col">
                <label class="font-weight-bold">Situação Financeira:</label> <br>
                <?php
                  if ($row['sit_financ'] === 'Q'){
                    $sitFin = "QUITADO";
                  } else {
                    $sitFin = "EM DÉBITO";
                  }
                  echo $sitFin;
                ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data de Situação Atual:</label> <br>
                <?= $row['data_situacao_atual'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data de Inscrição no CRO:</label> <br>
                <?= $row['data_incricao_cro'] ?>
              </div>
              <div class="col">
                <label class="font-weight-bold">Data de Registro no CFO:</label> <br>
                <?= $row['data_inscricao_cfo'] ?>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-md-8">
                <label class="font-weight-bold">Especialidades / Habilitações:</label> <br>
                <?php
                  if ($row['especialidades'] == null){
                    $especialidades = "SEM ESPECIALIDADES CADASTRADAS";
                  } else {
                    $especialidades = preg_replace("/;/", "<br>", $row['especialidades']);
                  }
                  echo $especialidades;
                ?>
              </div>
            </div>
          </div>

          <a href='consulta-integrada'>
            <button type='button' class='btn btn-secondary btn-md mt-4 mb-3'><b>Voltar para pesquisa</b></button>
          </a> 

        <?php
              }
            } else {
              echo "<script language='javascript'>
                      window.alert('Ocorreu um erro, você será redirecionado!')
                      window.location.href='consulta-integrada';
                    </script>";
            }
          }
        ?>

        </div>
      </div>
                                  
    </div>
  </div>

<?php
require_once INC_PATH . '/footer.php';
?>

<!-- 

Nome do pai ok
Idenetidade RG (ORG, UND, DATA EMISSÂO) ok
Genero ok
Naturalidade ok
Nacionalidade ok
Estado Civil ok
Titulo de Eleitor [não tem no webservice]
Formação Academica (Nome Instituição, Colação de Grau, Curso e Data de Conclusão) [falta curso]
Endereço de correspondencia
Responsábilidade Técnica (Und Fed. - Categoria - Inscrição - Nome da Empresa)

 -->