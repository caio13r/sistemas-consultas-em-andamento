<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CI6acesso'] == false) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='consulta-identidade';
  </script>";
  exit;
}

$tituloConsulta = 'Estatísticas - Consulta CFO ID';
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Consultar profissionais - CFO ID Consulta</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || $row['CI6select'] == true) {
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
            <div class="form-group col-md-8">
                <label for="nome">Preencha o Nome:</label>
                <input type="text" id="nome" name="nome" class="form-control" minlength="7" placeholder="Digite o nome" value="<?= $inputPost["nome"] ?>" required></input>
                <!-- <span class="mt-1" style="font-size: 80%">É necessário inserir no mínimo 8 caracteres.</span> -->
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

    if ($inputPost["cro"] === 'ALL' || $inputPost["cro"] === null) {
        $croTable = "CRO IS NOT NULL";
    } else {
        $croTable = "CRO = '{$inputPost["cro"]}'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO, CATEGORIA, INSC, PROFISSIONAL, convert(char, DATA_EMISSAO_ID, 103) AS DATA_EMISSAO_ID FROM CFO_CWS.dbo.cfo_id_cobranca WHERE $croTable AND STATUS = '1' AND PROFISSIONAL COLLATE Latin1_general_CI_AI LIKE :nome COLLATE Latin1_general_CI_AI";
        $stmt = $con->prepare($query);
        $stmt->bindValue(':nome', "%{$inputPost["nome"]}%", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
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
    <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
      <thead>
        <tr>
          <th scope="col">Nome</th>
          <th scope="col">CRO</th>
          <th scope="col">Categoria</th>
          <th scope="col">Inscrição</th>
          <th scope="col">Data de Emissão</th>
        </tr>
      </thead>
      <tbody>
        <?php
          foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . $row['PROFISSIONAL'] . "</td>";
            echo "<td>" . $row['CRO'] . "</td>";
            echo "<td>" . $row['CATEGORIA'] . "</td>";
            echo "<td>" . $row['INSC'] . "</td>";
            echo "<td>" . $row['DATA_EMISSAO_ID'] . "</td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>  
</div>

<?php } else {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Erro!</b> <br>
              Não foi econtrado ninguém com esse nome.
              </div>";  
      } 
  }  
?>