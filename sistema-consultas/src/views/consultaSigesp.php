<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database2;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CSacesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='index';
  </script>";
  exit;
}

$db = Database2::getInstance();
$con = $db->getConnection();
?>

<div class="container-fluid">

<div class="card">
  <div class="card-header">
    <h5><i class="fas fa-search mr-2 mt-2"></i>Consulta SIGESP</h5>
  </div>

  <div class="card-body">

      <?php if (empty($inputGet["nome"]) && empty($inputGet["cro"])) { ?>

      <h5 class="card-title mb-4">Buscar cursos em andamento cadastrados no SIGESP</h5>
      <!-- <p>Você está na Consulta Integrada de profissionais. Quanto mais precisos os dados de busca, melhor e mais rápido será o resultado.</p>
         -->
        <div class="col-md-8 offset-md-2">
  
          <!-- Formulário de busca -->
          <form id ="formId" action="" method="post">
            <div class="form-row">

              <div class="form-group col-md-6">
                <label for="tipoCurso">Tipo de curso:</label>
                <select id="tipoCurso" name="tipoCurso" class="form-control">
                  <option disabled selected>Selecione</option>
                  <?php
                    $values = array(1 => 'Credenciamento (Faculdades e
                    Universidades)', 2 => 'Reconhecimento (Entidades de classe)', 3 => 'Todos');
                    foreach($values as $val => $value) {
                        $selected = (!empty($inputPost['tipoCurso']) && $inputPost['tipoCurso'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                  ?>
                </select>
              </div>

              <div class="form-group col-md-6">
                <label for="uf">Estado:</label>
                <select id="uf" name="uf" class="form-control">
                  <option disabled selected value>Selecione</option>
                  <?php
                    // try {
                    //   $sql = "SELECT ID, NOME_UF FROM db_sisesp.TAB_CROS ORDER BY 2";
                    //   $stmt = $con->prepare($sql);
                    //   $stmt->execute();
                    //   $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // } catch (PDOexception $error) {
                    //   die("Erro ao retornar os dados: " . $error->getMessage());
                    // }
                    // foreach ($result as $row) {
                    //   $selected = (!empty($inputPost['uf']) && $inputPost['uf'] == $row['ID']) ? 'selected' : '';
                    //   echo "<option value='{$row['ID']}' $selected>{$row['NOME_UF']}</option>";
                    // }
                    // Validação de Acessso as UFs 
                    if (Session::get('grupo') === 0 || $row['CSselect'] == true) {
                      foreach(Helper::$ufListId as $val => $value) {
                        $selected = (!empty($inputPost['uf']) && $inputPost['uf'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                      }            
                    } else {
                      foreach(Helper::$ufListId as $val => $value) {
                        if ($users->CheckGroupUf() == $val) {
                          $selected = (!empty($inputPost['uf']) && $inputPost['uf'] == $val) ? 'selected' : '';
                          echo "<option value='$val' $selected>$value</option>";
                        }
                      }   
                    }
                  ?>
                </select>
              </div>

              <div class="form-group col-md-4">
                <label for="especialidade">Especialidade:</label>
                <select id="especialidade" name="especialidade" class="form-control">
                  <option disabled selected value>Selecione</option>
                  <?php
                    try {
                      $sql = "SELECT ID, NM_ESPECIALIDADE FROM db_sisesp.ESPECIALIDADE_CATEGORIA";
                      $stmt = $con->prepare($sql);
                      $stmt->execute();
                      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOexception $error) {
                      die("Erro ao retornar os dados: " . $error->getMessage());
                    }
                    foreach ($result as $row) {
                      $selected = (!empty($inputPost['especialidade']) && $inputPost['especialidade'] == $row['ID']) ? 'selected' : '';
                      echo "<option value='{$row['ID']}' $selected>{$row['NM_ESPECIALIDADE']}</option>";
                    }
                    ?>
                    <option value='ALL' $selected>Todos</option>
                </select>
              </div>

              <div class="form-group col-md-4">
                <label for="residencia">Residencia:</label>
                <select id="residencia" name="residencia" class="form-control">
                  <option disabled selected>Selecione</option>
                  <?php
                    $values = array(1 => 'Sim', 2 => 'Não', 3 => 'Todos');
                    foreach($values as $val => $value) {
                        $selected = (!empty($inputPost['residencia']) && $inputPost['residencia'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                  ?>
                </select>
              </div>             


              <div class="form-group col-md-4">
                <label for="situacao">Situação:</label>
                <select id="situacao" name="situacao" class="form-control">
                  <option disabled selected>Selecione</option>
                  <?php
                    $values = array(1 => 'Em andamento', 2 => 'Finalizados', 3 => 'Todos');
                    foreach($values as $val => $value) {
                        $selected = (!empty($inputPost['situacao']) && $inputPost['situacao'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $selected>$value</option>";
                    }
                  ?>
                </select>
              </div>             

              <div class="form-group col-md-12">
                <label for="entidade">Entidade/IES:</label>
                <select id="entidade" name="entidade" class="form-control">
                  <option disabled selected value>Selecione</option>
                  <?php
                    try {
                      $sql = "SELECT PESSOA_JURIDICA.ID AS ID_PESSOA_JURIDICA, PESSOA_JURIDICA.NOME_FANTASIA
                              FROM db_sisesp.PESSOA_JURIDICA
                              INNER JOIN db_sisesp.ENTIDADE_PROMOTORA ON (PESSOA_JURIDICA.ID = ENTIDADE_PROMOTORA.ID_PESSOA_JURIDICA)
                              WHERE PESSOA_JURIDICA.ID > 0
                              ORDER BY 2 ASC";

                      $stmt = $con->prepare($sql);
                      $stmt->execute();
                      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOexception $error) {
                      die("Erro ao retornar os dados: " . $error->getMessage());
                    }
                    foreach ($result as $row) {
                      $selected = (!empty($inputPost['entidade']) && $inputPost['entidade'] == $row['ID_PESSOA_JURIDICA']) ? 'selected' : '';
                      echo "<option value='{$row['ID_PESSOA_JURIDICA']}' $selected>{$row['NOME_FANTASIA']}</option>";
                    }
                    ?>
                </select>
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

          if ($inputPost["tipoCurso"] == 1 || $inputPost["tipoCurso"] == 2) { 
            $tipoCurso = " AND id_tipo_curso = '{$inputPost["tipoCurso"]}'";
          } else {
            $tipoCurso = null;
          }

          if ($inputPost["situacao"] == 1) { 
            $situacao = " AND situacao = 'CURSO EM ANDAMENTO' AND id_situacao = 1";
          } elseif ($inputPost["situacao"] == 2) {
            $situacao = " AND situacao = 'CURSO JA FINALIZADO' AND id_situacao = 2";
          } else {
            $situacao = null;
          }

          if (!empty($inputPost["uf"])) { 
            $uf = "AND id_cro = '{$inputPost["uf"]}'";
          } else {
            $uf = null;
          }

          if (!empty($inputPost["especialidade"]) && $inputPost["especialidade"] != 'ALL') { 
            $especialidade = " AND id_especialidade = '{$inputPost["especialidade"]}'";
          } else {
            $especialidade = null;
          }

          if (!empty($inputPost["entidade"])) { 
            $entidade = " AND id_pessoa_juridica = '{$inputPost["entidade"]}'";
          } else {
            $entidade = null;
          }

          if ($inputPost["residencia"] == 1 || $inputPost["residencia"] == 2) { 
            $residencia = " AND residencia = '{$inputPost["residencia"]}'";
          } else {
            $residencia = null;
          }
          
          if ($erro) {
            echo "<script language='javascript'>
                  window.alert('$erro')
                  window.location.href='consulta-integrada';
                  </script>";
            exit;
          }
          
          $currentDate = date('Y-m-d');

          // Busca as informações no banco de dados
          try {
            $sql = "SELECT * FROM db_sisesp.tb_especialidade_andamento 
                      WHERE id > 0 
                      $situacao
                      AND data_final >= $currentDate
                      $tipoCurso
                      $uf
                      $especialidade
                      $entidade
                      $residencia
                      ORDER BY id DESC";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOexception $error) {
            die("Erro ao retornar os dados: " . $error->getMessage());
          }

          // Resuldado busca em tabela
          if (count($result) > 0) {
        ?>
        
          <div class="row mt-4">
            <div class="col table-responsive">
              <table id="consultaSigesp" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                  <tr>
                    <th scope="col">Curso</th>
                    <th scope="col">Entidade/IES</th>
                    <th scope="col">Especialidade</th>
                    <th scope="col">Portaria</th>
                    <th scope="col">Coordenador</th>
                    <th scope="col">UF</th>
                    <th scope="col">Período</th>
                    <th scope="col">Situação</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    foreach ($result as $row) {
                      if ($row['id_tipo_curso'] === 1) {
                        $tipoCurso = 'Credenciamento';
                      } else {
                        $tipoCurso = 'Reconhecimento';
                      }
                      foreach(Helper::$ufListId as $val => $value) {
                        if ($row['id_cro'] === $val) {
                          $idCro = $value;
                        } 
                      }
                      $dataInicio = date("d/m/Y", strtotime($row['data_inicial']));
                      $datafim = date("d/m/Y", strtotime($row['data_final']));
                      echo "<tr>";
                      echo "<td>" . $tipoCurso  . "</td>";
                      echo "<td>" . $row['nome_pessoa_juridica'] . "</td>";
                      echo "<td>" . $row['nome_especialidade'] . "</td>";
                      echo "<td>" . $row['portaria_cfo'] . "</td>";
                      echo "<td>" . $row['nome_coordenador'] . "</td>";
                      echo "<td>" . $idCro  . "</td>";
                      echo "<td>" . $dataInicio . " a " . $datafim . "</td>";
                      echo "<td>" . $row['situacao'] . "</td>";
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
                    Não foi econtrado nunhum resultado na busca.
                    </div>";  
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