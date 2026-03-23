<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CI4acesso']) && $row['CI4acesso'] == false)) {
  echo "<script language='javascript'>
  window.alert('Você não tem permissão para acessar essa página.')
  window.location.href='consulta-identidade';
  </script>";
  exit;
}

$tituloConsulta = 'Estatísticas - Consulta CFO ID única';
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Total de CRO que posssui identidade digital emitida</h6>
</div>

<?php
    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();

        $query = "SELECT CRO, CD, TSB, ASB, APD, TPD, TOTAL
        FROM CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Unicas_Emitidas";
        $stmt = $con->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Consulta Identidade 4 - Erro PDO: " . $error->getMessage());
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
    <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
      <thead>
        <tr>
          <th scope="col">CRO</th>
          <th scope="col">CD</th>
          <th scope="col">TSB</th>
          <th scope="col">ASB</th>
          <th scope="col">APD</th>
          <th scope="col">TPD</th>
          <th scope="col">TOTAL</th>
        </tr>
      </thead>
      <tbody>
        <?php
          foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CRO'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['CD'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['TSB'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['ASB'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['APD'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['TPD'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['TOTAL'] ?? '') . "</td>";
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
?>