<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\lib\Labels;

$labels = new Labels();

Session::CheckSession();
Session::CheckAdmin();

// print_r($_SERVER);

try {
  $grupo = $inputGet['grupo'];
  $subgrupo = $inputGet['subgrupo'];
  $db = Database1::getInstance();
  $con = $db->getConnection();
  $query = "SELECT * FROM tbl_acessos WHERE grupo = '$grupo' AND subgrupo = '$subgrupo'";
  $stmt = $con->prepare($query);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOexception $error) {
  die("Erro ao retornar os dados: " . $error->getMessage());
}
//print_r($row);
?>

<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h5><i class="fas fa-users mr-2 mt-2"></i>Controle   de   permissões</h5>
    </div>

    <div class="card-body pr-2 pl-2">

      <div class="col-md-12 offset-md-0  mt-2 mb-3">

        <script>
          function redirecionar() {
            var grupo = document.getElementById("grupo").value;
            var subgrupo = document.getElementById("subgrupo").value;
            window.location.href = `?grupo=${grupo}&subgrupo=${subgrupo}`;
          }
        </script>

        <form action="" method="post">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="grupo">Selecione o Grupo:</label>
              <select id="grupo" name="grupo" class="form-control">
                <?php
                $values = array('CFO' => 'CFO', 'CRO' => 'CRO');
                foreach ($values as $val => $value) {
                  $selected = (!empty($inputGet['grupo']) && $inputGet['grupo'] == $value) ? 'selected' : '';
                  if ($value != 'Administrador') {
                    echo "<option value='$value' $selected>$value</option>";
                  }
                }
                ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="subgrupo">Selecione o Subgrupo:</label>
              <select id="subgrupo" name="subgrupo" class="form-control">
                <?php
                foreach (Helper::$subAcessList as $value) {
                  $selected = (!empty($inputGet['subgrupo']) && $inputGet['subgrupo'] == $value) ? 'selected' : '';
                  echo "<option value='$value' $selected>$value</option>";
                }
                ?>
              </select>
            </div>
          </div>

          <button type="button" class="btn btn-primary" onclick="redirecionar()">Enviar</button>
        </form>

      </div>

      <?php if (isset($inputGet['subgrupo'])) { ?>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR INTEGRADA</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            
            <tr>
              <th>Consulta Integrada</th>
              <td>
                <?php if ($row['CNacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CNacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CNacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CNacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CNacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CNselect'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CNselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CNselect" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CNselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CNselect" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>

          </table>
        </div>

        <hr>

        <!-- CONSULTA RFB - RECEITA FEDERAL -->
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTA RFB (RECEITA FEDERAL)</th>
              <td>Permissão de Acesso</td>
              <td>Gerenciamento</td>
            </tr>
            <tr>
              <th>Consulta RFB - Receita Federal do Brasil</th>
              <td>
                <?php if ($row['CRacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CRacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CRacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CRacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CRacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if (isset($row['CRselect']) && $row['CRselect'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CRselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CRselect" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CRselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CRselect" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Gerenciamento RFB - Receita Federal do Brasil</th>
              <td>
                <?php if (isset($row['CR1acesso']) && $row['CR1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CR1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CR1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CR1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CR1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if (isset($row['CR1select']) && $row['CR1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CR1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CR1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CR1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CR1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR ELEIÇÕES</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta Eleições</th>
              <td>
                <?php if ($row['CLacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CLacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CLacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CLacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CLacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <?php
              $buttonsCL = $labels->getChildLabelsPorSigla('CL');
              // A ordem é controlada pelo sistema de display_order no gerenciamento de labels
            ?>
            <?php foreach ($buttonsCL as $label): ?>
            <tr>
                <th><?= $label['nome'] ?></th>
                <td>
                    <?php if ($row["CL".$label['referencial']."acesso"] == true) { ?>
                        <form action="updateAcess" method="post">
                            <input type="hidden" name="id" value="CL<?= $label['referencial'] ?>acesso">
                            <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                            <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                            <button type="submit" name="CL<?= $label['referencial'] ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                <img src="../assets/img/on-button.png" width="35px">
                            </button>
                        </form>
                    <?php } else { ?>
                        <form action="updateAcess" method="post">
                            <input type="hidden" name="id" value="CL<?= $label['referencial'] ?>acesso">
                            <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                            <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                            <button type="submit" name="CL<?= $label['referencial'] ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                <img src="../assets/img/off-button.png" width="35px">
                            </button>
                        </form>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row["CL".$label['referencial']."select"] == true) { ?>
                        <form action="updateAcess" method="post">
                            <input type="hidden" name="id" value="CL<?= $label['referencial'] ?>select">
                            <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                            <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                            <button type="submit" name="CL<?= $label['referencial'] ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                <img src="../assets/img/on-button.png" width="35px">
                            </button>
                        </form>
                    <?php } else { ?>
                        <form action="updateAcess" method="post">
                            <input type="hidden" name="id" value="CL<?= $label['referencial'] ?>select">
                            <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                            <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                            <button type="submit" name="CL<?= $label['referencial'] ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                <img src="../assets/img/off-button.png" width="35px">
                            </button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR IDENTIDADE</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta Identidade</th>
              <td>
                <?php if ($row['CIacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CIacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CIacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CIacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CIacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <tr>
              <th>Consulta Identidade por Nome, CPF, ou AR</th>
              <td>
                <?php if ($row['CI1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CI1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Consulta por identidade descartada</th>
              <td>
                <?php if ($row['CI2acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI2acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI2acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CI2select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI2select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI2select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>

            
           

            <tr>
    <th>Consulta de Identidade Descartada Antes da Fila de Impressão (geral)</th>
    <td>
        <?php if ($row['CI13acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI13acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI13acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI13acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI13acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI13select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI13select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI13select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI13select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI13select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta Postagem Identidade (Sintético)</th>
    <td>
        <?php if ($row['CI17acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI17acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI17acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI17acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI17acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI17select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI17select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI17select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI17select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI17select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta Postagem Identidade (Analítico)</th>
    <td>
        <?php if ($row['CI18acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI18acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI18acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI18acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI18acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI18select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI18select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI18select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI18select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI18select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta de Identidade Perdidas</th>
    <td>
        <?php if ($row['CI19acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI19acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI19acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI19acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI19acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI19select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI19select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI19select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI19select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI19select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta de Identidade Retornadas ao CFO (Inserção)</th>
    <td>
        <?php if ($row['CI20acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI20acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI20acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI20acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI20acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI20select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI20select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI20select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI20select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI20select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta de Identidade Retornadas ao CFO (Analítico API)</th>
    <td>
        <?php if ($row['CI21acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI21acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI21acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI21acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI21acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI21select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI21select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI21select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI21select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI21select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta de Identidade Retornadas ao CFO (Analítico)</th>
    <td>
        <?php if ($row['CI22acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI22acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI22acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI22acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI22acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI22select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI22select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI22select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI22select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI22select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>Consulta de Envios de Identidades ao CRO - Por Período</th>
    <td>
        <?php if ($row['CI23acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI23acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI23acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI23acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI23acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI23select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI23select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI23select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI23select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI23select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>

<tr>
    <th>Evolução CFO ID</th>
    <td>
        <?php if ($row['CI3acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI3acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI3acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI3acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI3acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI3select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI3select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI3select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI3select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI3select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>

<tr>
              <th>CFO ID Única</th>
              <td>
                <?php if ($row['CI4acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI4acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI4acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CI4select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI4select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI4select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CFO ID Lista Detalhada</th>
              <td>
                <?php if ($row['CI5acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI5acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI5acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CI5select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI5select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI5select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CFO ID Consulta</th>
              <td>
                <?php if ($row['CI6acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI6acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI6acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CI6select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI6select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CI6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CI6select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
<tr>
    <th>Total de identidades emitidas consolidado por CRO</th>
    <td>
        <?php if ($row['CI11acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI11acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI11acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI11acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI11acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI11select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI11select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI11select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI11select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI11select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>
<tr>
    <th>CFO ID emitidas</th>
    <td>
        <?php if ($row['CI12acesso'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI12acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI12acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI12acesso">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI12acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
    <td>
        <?php if ($row['CI12select'] == true) { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI12select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI12select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/on-button.png" width="35px">
                </button>
            </form>
        <?php } else { ?>
            <form action="updateAcess" method="post">
                <input type="hidden" name="id" value="CI12select">
                <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                <button type="submit" name="CI12select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                    <img src="../assets/img/off-button.png" width="35px">
                </button>
            </form>
        <?php } ?>
    </td>
</tr>


          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR ESTATÍSTICA</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta Estatística</th>
              <td>
                <?php if ($row['CEacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CEacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CEacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CEacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CEacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <tr>
              <th>CRO x Categoria x População x Sexo</th>
              <td>
                <?php if ($row['CE1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CRO x Categoria x Ano de Registro</th>
              <td>
                <?php if ($row['CE2acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE2acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE2acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE2select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE2select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE2select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CRO x Categoria x Faixa Etária</th>
              <td>
                <?php if ($row['CE3acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE3acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE3acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE3select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE3select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE3select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CRO x Especialidade x Sexo</th>
              <td>
                <?php if ($row['CE4acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE4acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE4acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE4select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE4select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE4select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CRO x Especialidade x Faixa Etária</th>
              <td>
                <?php if ($row['CE5acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE5acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE5acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE5select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE5select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE5select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CRO x Sexo x Especialidade x Município</th>
              <td>
                <?php if ($row['CE6acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE6acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE6acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE6select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE6select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE6select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CFO ID Emitidas</th>
              <td>
                <?php if ($row['CE7acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE7acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE7acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE7acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE7acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CE7select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE7select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE7select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CE7select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CE7select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>

            <?php 
              $buttons = [
                  10 => 'Ativos por Localidade',
                  11 => 'Pessoas Ativas com DDA',
                  12 => 'Endereços Residenciais Mais Recentes',
                  13 => 'Endereços Comerciais Mais Recentes',
                  14 => 'Ativos por Especialidade x Sexo x Ano',
                  15 => 'Especialidade Técnica x Sexo',
                  16 => 'Especialidade Técnica x Sexo x Ano',
                  17 => 'Ativos por Habilitação x Sexo',
                  18 => 'Ativos por Habilitação x Sexo x Ano',
                  19 => 'Total de profissionais e empresas ativas por ano (ultimos 18 anos)'
              ];
              ?>

              <?php foreach ($buttons as $key => $label): ?>
              <tr>
                  <th><?= $label ?></th>
                  <td>
                      <?php if ($row["CE{$key}acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CE<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CE<?= $key ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CE<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CE<?= $key ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["CE{$key}select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CE<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CE<?= $key ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CE<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CE<?= $key ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
              <?php endforeach; ?>

          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR AUDITORIA</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta de Auditoria</th>
              <td>
                <?php if ($row['CAacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CAacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CAacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CAacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CAacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <tr>
              <th>CPF ou CNPJ duplicados</th>
              <td>
                <?php if ($row['CA1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CPF ou CNPJ inválidos</th>
              <td>
                <?php if ($row['CA2acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA2acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA2acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA2select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA2select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA2select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Pré-cadastros vencidos</th>
              <td>
                <?php if ($row['CA3acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA3acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA3acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA3select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA3select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA3select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Cadastros próvisórios vencidos</th>
              <td>
                <?php if ($row['CA4acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA4acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA4acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA4select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA4select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA4select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA4select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Ativo em mais de um CRO</th>
              <td>
                <?php if ($row['CA5acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA5acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA5acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA5select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA5select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA5select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Cadastros secundários sem origem ativa</th>
              <td>
                <?php if ($row['CA6acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA6acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA6acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA6acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA6select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA6select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA6select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA6select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Empresas ativas sem responsável técnico</th>
              <td>
                <?php if ($row['CA7acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA7acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA7acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA7acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA7acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA7select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA7select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA7select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA7select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA7select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Filiais ativas sem a respectiva matriz</th>
              <td>
                <?php if ($row['CA8acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA8acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA8acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA8acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA8acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA8select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA8select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA8select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA8select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA8select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Responsável técnico em mais de uma empresa</th>
              <td>
                <?php if ($row['CA9acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA9acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA9acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA9acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA9acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA9select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA9select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA9select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA9select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA9select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Empresas isentas</th>
              <td>
                <?php if ($row['CA10acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA10acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA10acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA10acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA10acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA10select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA10select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA10select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA10select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA10select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais sem data de colação</th>
              <td>
                <?php if ($row['CA11acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA11acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA11acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA11acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA11acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA11select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="ida=select" value="CA11select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA11select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA11select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA11select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais ativos sem e-mail</th>
              <td>
                <?php if ($row['CA12acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA12acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA12acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA12acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA12acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA12select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA12select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA12select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA12select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA12select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Inscritos ativos sem data de inscrição</th>
              <td>
                <?php if ($row['CA13acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA13acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA13acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA13acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA13acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA13select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA13select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA13select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA13select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA13select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Usuários do CRO</th>
              <td>
                <?php if ($row['CA14acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA14acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA14acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA14acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA14acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA14select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA14select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA14select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA14select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA14select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>CPF duplicado na mesma categoria</th>
              <td>
                <?php if ($row['CA15acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA15acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA15acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA15acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA15acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA15select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA15select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA15select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA15select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA15select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Total de Identidades Únicas já emitidas</th>
              <td>
                <?php if ($row['CA16acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA16acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA16acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA16acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA16acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA16select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA16select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA16select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA16select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA16select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Total de Identidades Emitidas Consolidado por CRO</th>
              <td>
                <?php if ($row['CA17acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA17acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA17acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA17acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA17acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA17select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA17select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA17select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA17select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA17select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Desativados por caducidade ou cancelamento ex-oficio</th>
              <td>
                <?php if ($row['CA18acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA18acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA18acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA18acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA18acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA18select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA18select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA18select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA18select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA18select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais ativos com mais de 80 anos</th>
              <td>
                <?php if ($row['CA19acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA19acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA19acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA19acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA19acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA19select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA19select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA19select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA19select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA19select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais sem inscrição com CPF duplicado</th>
              <td>
                <?php if ($row['CA20acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA20acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA20acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA20acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA20acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA20select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA20select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA20select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA20select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA20select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais com nome social informado</th>
              <td>
                <?php if ($row['CA21acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA21acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA21acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA21acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA21acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA21select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA21select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA21select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA21select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA21select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais ativos com idade para REMISSÃO</th>
              <td>
                <?php if ($row['CA22acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA22acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA22acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA22acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA22acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA22select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA22select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA22select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA21select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA21select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais e Empresas com Multiplos Registros</th>
              <td>
                <?php if ($row['CA23acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA23acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA23acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA23acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA23acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA23select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA23select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA23select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA23select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA23select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais e Empresas com parcelas vencidas e não pagos</th>
              <td>
                <?php if ($row['CA24acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA24acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA24acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA24acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA24acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA24select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA24select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA24select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA24select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA24select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Profissionais e Empresas em atividade sem a respectiva data de inscrição no CFO</th>
              <td>
                <?php if ($row['CA25acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA25acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA25acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA25acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA25acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CA25select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA25select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA25select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CA25select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CA25select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">

            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR FISCALIZAÇÃO</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <?php
              $fiscalizacoesVisitas = [
                1 => 'Estatísticas de Fiscalizações - Categoria x Ano',
                2 => 'Estatísticas de Fiscalizações de pessoas sem inscrições - Tipo pessoa x Ano',
                3 => 'Estatísticas de Fiscalizações por Fiscal - Categoria x Ano',
                4 => 'Estatísticas de Fiscalizações por Fiscal sem Inscrições - Tipo pessoa x Ano',
                5 => 'Estatísticas de Fiscalizações por Tipos de Irregularidades - Categoria x Ano',
                6 => 'Estatísticas de Denúncias x Ano',
                7 => 'Estatísticas de Coordenadores de Fiscalização',
                8 => 'Estatísticas de Fiscalizações por Idade - Categoria x Ano',
                9 => 'Estatísticas de Quantidade de Fiscais',
                10 => 'Estatísticas de Nomes dos Fiscais',
              ];
            
              $fiscalizacoesTermos = [
                11 => 'Estatísticas de Fiscalizações por Termos - Por Categoria e Período',
                12 => 'Estatísticas de Fiscalizações de Pessoas Sem Inscrições - Por Tipo pessoa e Período',
                13 => 'Estatísticas de Fiscalizações por Fiscal - Por Categoria e Período',
                14 => 'Estatísticas de Fiscalizações por Fiscal sem Inscrições - Por Tipo pessoa e Período',
                15 => 'Estatísticas de Fiscalizações por Tipos de Irregularidades - Por Categoria e Período',
                16 => 'Estatísticas de Denúncias - Por Período',
                17 => 'Estatísticas de Coordenadores de Fiscalização',
                18 => 'Estatísticas de Fiscalizações - Por Idade e Período',
                19 => 'Estatísticas de Quantidade de Fiscais',
                20 => 'Estatísticas de Nomes dos Fiscais',
              ];
            ?>
            <tr>
              <th>Consulta de Fiscalizações</th>
              <td>
                <?php if ($row['CFacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CFacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CFacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CFacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CFacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3" style="color: #8D0F12; padding: 10px;" width='50%'><b>Fiscalizações Baseadas em Visitas</b></td>
            </tr>
            <?php foreach($fiscalizacoesVisitas as $key => $label){ ?>
              <tr>
                  <th><?= $label ?></th>
                  <td>
                      <?php if ($row["CF{$key}acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["CF{$key}select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
            <?php } ?>
            <tr>
              <td colspan="3" style="color: #8D0F12; padding: 10px;" width='50%'><b>Fiscalizações Baseadas em Termos</b></td>
            </tr>
            <?php foreach($fiscalizacoesTermos as $key => $label){ ?>
              <tr>
                  <th><?= $label ?></th>
                  <td>
                      <?php if ($row["CF{$key}acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["CF{$key}select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="CF<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="CF<?= $key ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
            <?php } ?>
          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR SIGESP</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta SIGESP</th>
              <td>
                <?php if ($row['CSacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CSacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CSacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CSacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CSacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['CSselect'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CSselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CSselect" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CSselect">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CSselect" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR RELATÓRIOS</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Consulta Relatórios</th>
              <td>
                <?php if ($row['REacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="REacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="REacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="REacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="REacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Relatório de Adimplência</th>
              <td>
                <?php if ($row['RE1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Relatório de Adimplência (com valores)</th>
              <td>
                <?php if ($row['RE14acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE14acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE14acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE14acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE14acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE14select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE14select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE14select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE14select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE14select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Relatório de Tarifas</th>
              <td>
                <?php if ($row['RE2acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE2acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE2acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE2acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE2select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE2select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE2select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE2select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <tr>
              <th>Relatório LAI/LGPD</th>
              <td>
                <?php if ($row['RE3acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE3acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE3acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE3acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE3select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE3select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE3select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE3select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr>
            <!-- <tr>
              <th>Relatório Delegado Eleitor</th>
              <td>
                <?php if ($row['RE4acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE4acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE4acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE4acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE1select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE1select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE1select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr> -->
            <!-- <tr>
              <th>Relatório Profissional x Formação</th>
              <td>
                <?php if ($row['RE5acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE5acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE5acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE5acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['RE5select'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE5select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RE5select">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RE5select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
            </tr> -->
           <tr>
  <th>Relatório Processos de Especialidade e Habilitação</th>
  <td>
    <?php if ($row['RE7acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE7acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE7acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE7acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE7acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE7select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE7select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE7select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE7select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE7select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Arrecadação e Tarifas do BANCO DO BRASIL (boletos mensal)</th>
  <td>
    <?php if ($row['RE8acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE8acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE8acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE8acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE8acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE8select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE8select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE8select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE8select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE8select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Arrecadação de Pagamentos Diversos</th>
  <td>
    <?php if ($row['RE9acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE9acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE9acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE9acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE9acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE9select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE9select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE9select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE9select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE9select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Tarifas do CARTÃO DE CRÉDITO - SELFPAY / BKBANK</th>
  <td>
    <?php if ($row['RE10acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE10acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE10acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE10acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE10acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE10select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE10select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE10select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE10select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE10select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Arrecadação do BANCO DO BRASIL (boletos pelo período desejado)</th>
  <td>
    <?php if ($row['RE11acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE11acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE11acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE11acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE11acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE11select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE11select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE11select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE11select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE11select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Tarifas do BANCO DO BRASIL (boletos pelo período desejado)</th>
  <td>
    <?php if ($row['RE12acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE12acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE12acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE12acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE12acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE12select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE12select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE12select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE12select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE12select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

<tr>
  <th>Relatório de Arrecadação do CARTÃO DE CREDITO - SELFPAY / BKBANK</th>
  <td>
    <?php if ($row['RE13acesso'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE13acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE13acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE13acesso">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE13acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
  <td>
    <?php if ($row['RE13select'] == true) { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE13select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE13select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/on-button.png" width="35px">
        </button>
      </form>
    <?php } else { ?>
      <form action="updateAcess" method="post">
        <input type="hidden" name="id" value="RE13select">
        <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
        <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
        <button type="submit" name="RE13select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
          <img src="../assets/img/off-button.png" width="35px">
        </button>
      </form>
    <?php } ?>
  </td>
</tr>

          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR PRESCRIÇÃO</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <?php
              $buttons = [
                  1 => "Gráficos de Tipos",
                  2 => "Consulta Nome CD",
                  3 => "Consulta por data",
                  4 => "Consulta por Estado",
                  5 => "Ultimas Prescrições emitidas",
                  6 => "Consulta por nome de Paciente",
                  7 => "Quantitativo por PSC"
                  
              ];
            ?>
            <tr>
              <th>Relatório da Prescrição</th>
              <td>
                <?php if ($row['RPacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RPacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RPacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="RPacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="RPacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>

            </tr>
            <?php foreach ($buttons as $key => $label): ?>
              <tr>
                  <th><?= $label ?></th>
                  <td>
                      <?php if ($row["RP{$key}acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="RP<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="RP<?= $key ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="RP<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="RP<?= $key ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["DA{$key}select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
              <?php endforeach; ?>


          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR DADOS ABERTOS</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <?php
              $buttons = [
                  1 => "Rol de Mandatários",
                  2 => "Atas de Colegiados",
                  3 => "Balanço Financeiro",
                  4 => "Balanço Contábil",
                  5 => "Balanço Orçamentário",
                  6 => "Contratos Cadastrados",
                  7 => "Contratos Cadastrados Aditivos",
                  8 => "Convênios Cadastrados",
                  9 => "Licitações Cadastradas",
                  10 => "Aquisições ou Alienações",
                  11 => "Passagens Aereas Cadastradas",
                  12 => "Diárias/Deslocamentos cadastrados",
                  13 => "Balanço Patrimonial",
                  14 => "Execução Financeira",
                  15 => "Plano de Contas",
                  16 => "Estatística de Acesso por Módulo",
              ];
              ?>
              <tr>
              <th>Relatório Dados Abertos</th>
                <td>
                  <?php if ($row['DAacesso'] == true) { ?>
                    <form action="updateAcess" method="post">
                      <input type="hidden" name="id" value="DAacesso">
                      <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                      <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                      <button type="submit" name="DAacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                        <img src="../assets/img/on-button.png" width="35px">
                      </button>
                    </form>
                  <?php } else { ?>
                    <form action="updateAcess" method="post">
                      <input type="hidden" name="id" value="DAacesso">
                      <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                      <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                      <button type="submit" name="DAacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                        <img src="../assets/img/off-button.png" width="35px">
                      </button>
                    </form>
                  <?php } ?>
                </td>
              </tr>
              <?php foreach ($buttons as $key => $label): ?>
              <tr>
                  <th><?= $label ?></th>
                  <td>
                      <?php if ($row["DA{$key}acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["DA{$key}select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="DA<?= $key ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="DA<?= $key ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
              <?php endforeach; ?>


          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CONSULTAR TABELAS CENTRALIZADAS NO CFO</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <?php
              $buttonsTC = $labels->getChildLabelsPorSigla('TC');

              // A ordem é controlada pelo sistema de display_order no gerenciamento de labels
              // Não aplicamos usort() aqui para respeitar a ordenação configurada
            ?>
              <tr>
              <th>Relatório Tabelas Centralizadas no CFO</th>
                <td>
                  <?php if ($row['TCacesso'] == true) { ?>
                    <form action="updateAcess" method="post">
                      <input type="hidden" name="id" value="TCacesso">
                      <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                      <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                      <button type="submit" name="TCacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                        <img src="../assets/img/on-button.png" width="35px">
                      </button>
                    </form>
                  <?php } else { ?>
                    <form action="updateAcess" method="post">
                      <input type="hidden" name="id" value="TCacesso">
                      <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                      <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                      <button type="submit" name="TCacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                        <img src="../assets/img/off-button.png" width="35px">
                      </button>
                    </form>
                  <?php } ?>
                </td>
              </tr>
              <?php foreach ($buttonsTC as $label): ?>
              <tr>
                  <th><?= $label['nome'] ?></th>
                  <td>
                      <?php if ($row["TC".$label['referencial']."acesso"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="TC<?= $label['referencial'] ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="TC<?= $label['referencial'] ?>acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="TC<?= $label['referencial'] ?>acesso">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="TC<?= $label['referencial'] ?>acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
                  <td>
                      <?php if ($row["TC".$label['referencial']."select"] == true) { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="TC<?= $label['referencial'] ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="TC<?= $label['referencial'] ?>select" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/on-button.png" width="35px">
                              </button>
                          </form>
                      <?php } else { ?>
                          <form action="updateAcess" method="post">
                              <input type="hidden" name="id" value="TC<?= $label['referencial'] ?>select">
                              <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                              <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                              <button type="submit" name="TC<?= $label['referencial'] ?>select" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                                  <img src="../assets/img/off-button.png" width="35px">
                              </button>
                          </form>
                      <?php } ?>
                  </td>
              </tr>
              <?php endforeach; ?>


          </table>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped table-hover mt-2 mb-2">
            <tr>
              <th style="color: #8D0F12;" width='50%'>PERMISSÕES – CADASTROS</th>
              <td>Permissão de Acesso</td>
              <td>Consulta Nacional</td>
            </tr>
            <tr>
              <th>Sistema de Cadastros</th>
              <td>
                <?php if ($row['CDacesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CDacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CDacesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CDacesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CDacesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
            <tr>
              <th>Cadastro de Faturas do Banco do Brasil</th>
              <td>
                <?php if ($row['CD1acesso'] == true) { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CD1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CD1acesso" value="0" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/on-button.png" width="35px">
                    </button>
                  </form>
                <?php } else { ?>
                  <form action="updateAcess" method="post">
                    <input type="hidden" name="id" value="CD1acesso">
                    <input type="hidden" name="grupo" value="<?= $inputGet['grupo'] ?>">
                    <input type="hidden" name="subgrupo" value="<?= $inputGet['subgrupo'] ?>">
                    <button type="submit" name="CD1acesso" value="1" style="border: none; background: none; padding: 0; margin: 0;">
                      <img src="../assets/img/off-button.png" width="35px">
                    </button>
                  </form>
                <?php } ?>
              </td>
              <td></td>
            </tr>
          </table>
        </div>

      <?php } ?>

    </div>
  </div>
</div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>