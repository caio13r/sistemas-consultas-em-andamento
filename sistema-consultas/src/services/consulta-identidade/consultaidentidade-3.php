<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CI3acesso']) && $row['CI3acesso'] == false)) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='consulta-identidade';
  </script>";
  exit;
}

$tituloConsulta = 'Evolução CFO ID';
?>

<div class="col-md-6 offset-md-3 mb-4">
<!-- <h6 class="mb-2">Consulta Idetidadade</h6> -->
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-12">
            <label for="uf">Selecione a UF:</label>
                  <select class="form-control" id="uf" name="uf" required>
                    <option disabled>Selecione a UF</option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CI3select']) && $row['CI3select'] == true)) {
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
            <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
        </div>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 
    $path = realpath(dirname(__FILE__, 2)) . "/relatorios/script/";

    if ($inputPost["uf"] === 'ALL') {
        $path .= "evolucao_emissao_cfo_id_geral.sql";
        $myfile = fopen($path, "r");
        if (!$myfile) {
            error_log("Consulta Identidade 3 - Não foi possível abrir o arquivo: " . $path);
            echo "<div class='alert alert-danger'>Erro ao abrir arquivo de consulta.</div>";
            return;
        }
        $script = fread($myfile,filesize($path));
        fclose($myfile);
    } else {
        $ufValida = array_key_exists($inputPost["uf"], Helper::$ufList) ? $inputPost["uf"] : '';
        if (empty($ufValida)) {
            echo "<div class='alert alert-danger'>UF inválida.</div>";
            return;
        }
        $path .= "evolucao_emissao_cfo_id.sql";
        $myfile = fopen($path, "r");
        if (!$myfile) {
            error_log("Consulta Identidade 3 - Não foi possível abrir o arquivo: " . $path);
            echo "<div class='alert alert-danger'>Erro ao abrir arquivo de consulta.</div>";
            return;
        }
        $script = "DECLARE @filtroUf AS VARCHAR(2) = '{$ufValida}';".fread($myfile,filesize($path));
        fclose($myfile);
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $stmt = $con->prepare($script);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Consulta Identidade 3 - Erro PDO: " . $error->getMessage());
        echo "<div class='alert alert-danger'>Erro ao retornar os dados.</div>";
        return;
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
    <table id="evolucaoCfoId" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
      <thead>
        <tr>
          <th scope="col">Mês</th>
          <th scope="col">2022</th>
          <th scope="col">2023</th>
          <th scope="col">2023-2022</th>
          <th scope="col">2024</th>
          <th scope="col">2024-2023</th>
        </tr>
      </thead>
      <tbody>
        <?php
          foreach ($result as $row) {
            echo "<tr>";
            // echo "<td>" . $row['MES_COBRANCA'] . "</td>";
            echo "<td>" . htmlspecialchars($row['MES_COBRA'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['_2022'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['_2023'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['DIF_2023'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['_2024'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['DIF_2024'] ?? '') . "</td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>  
</div>

<?php } else {
        echo "<div class='alert alert-danger mt-3' role='alert'>
              <b>Código inválido!</b> <br>
              Não foi econtrado nenhum dado com esse código.
              </div>";  
      } 
  }  
?>
