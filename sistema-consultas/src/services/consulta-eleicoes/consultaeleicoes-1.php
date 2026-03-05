<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();
$users->checkAcess('CL1acesso');

$db = Database3::getInstance();
$con = $db->getConnection();

?>

<div class="container-fluid">

      <?php if (empty($inputGet['idEleitor'])) { ?>

        <script type="text/javascript">
          function showInput(val) {
            document.getElementById('nomeInput').style.display = val == 1 ? 'block' : 'none';
            document.getElementById('inscInput').style.display = val == 2 ? 'block' : 'none';
            document.getElementById('cpfInput').style.display = val == 3 ? 'block' : 'none';
            document.getElementById('emailInput').style.display = val == 4 ? 'block' : 'none';
            document.getElementById('celularInput').style.display = val == 5 ? 'block' : 'none';

            // Limpar todos os campos e definir qual está ativo
            document.getElementById('nome').value = '';
            document.getElementById('insc').value = '';
            document.getElementById('cpf').value = '';
            document.getElementById('email').value = '';
            document.getElementById('celular').value = '';
            
            // Definir o tipo de busca ativa
            document.getElementById('tipoBusca').value = val;
          }
        </script>

        <h5 class="card-title mb-3">Buscar Eleitores</h5>
        <p>
          Quanto mais preciso os dados de busca, melhor e mais rápido será o resultado. As consultas retornam até  1000 resultados.
        </p>

        <div class="col-md-12 offset-md-12">
          <form action="" method="post">
            <input type="hidden" id="tipoBusca" name="tipoBusca" value="<?= !empty($inputPost["tipoBusca"]) ? $inputPost["tipoBusca"] : '1' ?>">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control" required>
                  <?php
                  if (Session::get('grupo') === 0 || $row['CL1select'] == true) {
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
            </div>
            
            <div class="search-type-cards" id="searchTypeCards">
              <label class="search-type-card <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '1') || empty($inputPost["tipoBusca"]) ? 'active' : '' ?>">
                <input type="radio" name="flexRadioDefault" value="1" <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '1') || empty($inputPost["tipoBusca"]) ? 'checked' : '' ?>>
                <i class="fas fa-user"></i>
                <span>Nome</span>
              </label>
              <label class="search-type-card <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '2') ? 'active' : '' ?>">
                <input type="radio" name="flexRadioDefault" value="2" <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '2') ? 'checked' : '' ?>>
                <i class="fas fa-id-card"></i>
                <span>Inscrição</span>
              </label>
              <label class="search-type-card <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '3') ? 'active' : '' ?>">
                <input type="radio" name="flexRadioDefault" value="3" <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '3') ? 'checked' : '' ?>>
                <i class="fas fa-address-card"></i>
                <span>CPF</span>
              </label>
              <label class="search-type-card <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '4') ? 'active' : '' ?>">
                <input type="radio" name="flexRadioDefault" value="4" <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '4') ? 'checked' : '' ?>>
                <i class="fas fa-envelope"></i>
                <span>E-mail</span>
              </label>
              <label class="search-type-card <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '5') ? 'active' : '' ?>">
                <input type="radio" name="flexRadioDefault" value="5" <?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '5') ? 'checked' : '' ?>>
                <i class="fas fa-mobile-alt"></i>
                <span>Celular</span>
              </label>
            </div>

            <div id="nomeInput" class="form-group col-md-12 mt-3">
              <input type="text" id="nome" name="nome" class="form-control" minlength="10" placeholder="Digite o nome completo (mínimo 10 caracteres)" value="<?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '1') ? $inputPost["nome"] : '' ?>">
            </div>
            
            <div id="inscInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="insc" name="insc" class="form-control" minlength="1" placeholder="Digite a Inscrição ou parte dela" value="<?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '2') ? $inputPost["insc"] : '' ?>">
            </div>

            <div id="cpfInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="cpf" name="cpf" class="form-control" minlength="4" maxlength="14" placeholder="Digite o CPF ou parte dele" value="<?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '3') ? $inputPost["cpf"] : '' ?>" onkeyup="maskCPF(this)" onfocus="maskCPF(this)">
            </div>

            <div id="emailInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="email" name="email" class="form-control" minlength="5" placeholder="Digite o e-mail" value="<?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '4') ? $inputPost["email"] : '' ?>">
            </div>

            <div id="celularInput" class="form-group col-md-12 mt-3" style="display:none;">
              <input type="text" id="celular" name="celular" class="form-control" minlength="4" placeholder="Digite o celular ou parte dele" value="<?= (!empty($inputPost["tipoBusca"]) && $inputPost["tipoBusca"] == '5') ? $inputPost["celular"] : '' ?>">
            </div>

            <script type="text/javascript">
              function maskCPF(input) {
                let value = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                value = value.replace(/(\d{3})(\d)/, "$1.$2");
                value = value.replace(/(\d{3})(\d)/, "$1.$2");
                value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                input.value = value;
              }
              
              // Inicializar o formulário no carregamento da página
              document.addEventListener('DOMContentLoaded', function() {
                var tipoBusca = document.getElementById('tipoBusca').value;
                showInputWithoutClear(tipoBusca);
              });
              
              function showInputWithoutClear(val) {
                document.getElementById('nomeInput').style.display = val == 1 ? 'block' : 'none';
                document.getElementById('inscInput').style.display = val == 2 ? 'block' : 'none';
                document.getElementById('cpfInput').style.display = val == 3 ? 'block' : 'none';
                document.getElementById('emailInput').style.display = val == 4 ? 'block' : 'none';
                document.getElementById('celularInput').style.display = val == 5 ? 'block' : 'none';
              }
            </script>

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
            $conditions[] = "ele.CRO IS NOT NULL";
          } else {
            $conditions[] = "ele.CRO LIKE '%{$inputPost["cro"]}%'";
          }
        } else {
          $erro = "O campo do CRO deve ser inserido.";
        }

        // Verificar qual tipo de busca está ativo e validar apenas esse campo
        $tipoBusca = !empty($inputPost["tipoBusca"]) ? $inputPost["tipoBusca"] : '1';
        
        switch($tipoBusca) {
          case '1': // Nome
            if (!empty($inputPost["nome"])) {
              if (strlen($inputPost["nome"]) < 10) {
                $erro = "O nome deve possuir 10 ou mais caracteres.";
              } else {
                $conditions[] = "ele.NOME_COMPLETO LIKE '%{$inputPost['nome']}%'";
              }
            } else {
              $erro = "O campo nome deve ser preenchido.";
            }
            break;
            
          case '2': // Inscrição
            if (!empty($inputPost["insc"])) {
              $conditions[] = "ele.INSCRICAO LIKE '%{$inputPost["insc"]}%'";
            } else {
              $erro = "O campo inscrição deve ser preenchido.";
            }
            break;
            
          case '3': // CPF
            if (!empty($inputPost["cpf"])) {
              $conditions[] = "ele.CPF LIKE '%{$inputPost['cpf']}%'";
            } else {
              $erro = "O campo CPF deve ser preenchido.";
            }
            break;
            
          case '4': // Email
            if (!empty($inputPost["email"])) {
              $conditions[] = "ele.EMAIL LIKE '%{$inputPost['email']}%'";
            } else {
              $erro = "O campo e-mail deve ser preenchido.";
            }
            break;
            
          case '5': // Celular
            if (!empty($inputPost["celular"])) {
              $conditions[] = "ele.CELULAR_ATUALIZADO LIKE '%{$inputPost['celular']}%'";
            } else {
              $erro = "O campo celular deve ser preenchido.";
            }
            break;
        }

        if ($erro) {
          echo "<script language='javascript'>
                  window.alert('$erro')
                  window.location.href='consulta-eleicoes';
                  </script>";
          exit;
        }

        // Consulta com prioridade para valores atualizados na view de Pagantes Após Geração
        $queryEleitor = "SELECT TOP 1000 
                          ele.NOME_COMPLETO, 
                          ele.CPF, 
                          ele.CRO, 
                          ele.CATEGORIA, 
                          ele.INSCRICAO, 
                          ele.TIPO_INSCRICAO, 
                          ele.SITUACAO, 
                          ele.DETALHE_SITUACAO, 
                          ele.ADIMPLENCIA, 
                          COALESCE(v.ELEITOR, ele.VOTANTE) AS VOTANTE, 
                          COALESCE(v.DEVEDOR, ele.DEVEDOR) AS DEVEDOR, 
                          ele.MOTIVO_NAO_VOTANTE, 
                          ele.EMAIL, 
                          ele.CELULAR_ATUALIZADO,
                          ele.DATA_NASCIMENTO, 
                          ele.DATA_INSCRICAO_CRO, 
                          ele.DATA_REGISTRO_CFO
                        FROM (
                          SELECT 
                            NOME_COMPLETO,
                            CPF,
                            CRO,
                            CATEGORIA,
                            INSCRICAO AS INSCRICAO,
                            TIPO_INSCRICAO AS TIPO_INSCRICAO,
                            SITUACAO AS SITUACAO,
                            DETALHE_SITUACAO AS DETALHE_SITUACAO,
                            ADIMPLENCIA AS ADIMPLENCIA,
                            ELEITOR AS VOTANTE,
                            DEVEDOR AS DEVEDOR,
                            MOTIVOS_NAO_ELEITOR AS MOTIVO_NAO_VOTANTE,
                            EMAIL,
                            CELULAR_ATUALIZADO AS CELULAR_ATUALIZADO,
                            DATA_NASCIMENTO AS DATA_NASCIMENTO,
                            DATA_INSCRICAO_CRO AS DATA_INSCRICAO_CRO,
                            DATA_REGISTRO_CFO AS DATA_REGISTRO_CFO
                          FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                        ) AS ele
                        LEFT JOIN CFO_CWS.dbo.Cons_Eleicoes_Lista_Suplementar v
                          ON v.CPF = ele.CPF AND v.CRO = ele.CRO";

        if (!empty($conditions)) {
          $queryEleitor .= " WHERE " . implode(' AND ', $conditions);
        }

        $queryEleitor .= " ORDER BY ele.NOME_COMPLETO, ele.CRO, ele.INSCRICAO";


        try {
          $stmt = $con->prepare($queryEleitor);
          $stmt->execute();
          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $error) {
          die("Erro ao retornar os dados: " . $error->getMessage());
        }

        if (count($result) > 0) {
      ?>

          <div class="row mt-4">
            <div class="col table-responsive">
              <table id="consultaeleicoes" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                  <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">CRO</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Inscrição</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Detalhe Situação</th>
                    <th scope="col">Eleitor</th>
                    <th scope="col">Devedor</th>
                    <th scope="col">Tipo Inscrição</th>
                    <th width='12%' scope="col">#</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  foreach ($result as $row) {
                    // Definir cor da célula de devedor
                    $devedorClass = '';
                    $devedorText = $row['DEVEDOR'];
                    
                    if (strtoupper($row['DEVEDOR']) == 'NÃO') {
                      $devedorClass = '';
                      $devedorText = '<i class="far fa-circle text-muted"></i> NÃO';
                    } elseif (strtoupper($row['DEVEDOR']) == 'SIM') {
                      $devedorClass = 'table-danger';
                      $devedorText = '<i class="fas fa-times-circle text-danger"></i> SIM';
                    } else {
                      $devedorClass = 'table-warning';
                      $devedorText = '<i class="fas fa-question-circle text-warning"></i> ' . $row['DEVEDOR'];
                    }
                    
                    echo "<tr>";
                    echo "<td>" . $row['NOME_COMPLETO'] . "</td>";
                    echo "<td>" . $row['CPF'] . "</td>";
                    echo "<td>" . $row['CRO'] . "</td>";
                    echo "<td>" . $row['CATEGORIA'] . "</td>";
                    echo "<td>" . $row['INSCRICAO'] . "</td>";
                    echo "<td>" . $row['SITUACAO'] . "</td>";
                    echo "<td>" . $row['DETALHE_SITUACAO'] . "</td>";
                    echo "<td>" . $row['VOTANTE'] . "</td>";
                    echo "<td class='$devedorClass'>" . $devedorText . "</td>";
                    echo "<td>" . $row['TIPO_INSCRICAO'] . "</td>";
                    echo "<td><button type='button' class='btn btn-secondary btn-sm' onclick='verDetalhes(\""
                      . htmlspecialchars($row['CPF'], ENT_QUOTES) . "\",\""
                      . htmlspecialchars($row['CRO'], ENT_QUOTES) . "\",\""
                      . htmlspecialchars($row['INSCRICAO'], ENT_QUOTES) . "\")'><b>Mais Informações</b></button></td>";
                    echo "</tr>";
                  }
                  ?>
                </tbody>
              </table>
              <div class="mb-2"><b>Total encontrado:</b> <?= count($result) ?> (máximo 1000)</div>
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

</div>

<!-- Modal para exibir detalhes do eleitor -->
<div class="modal fade" id="detalhesEleitorModal" tabindex="-1" role="dialog" aria-labelledby="detalhesEleitorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detalhesEleitorModalLabel">
          <i class="fas fa-user mr-2"></i>Detalhes do Eleitor
        </h5>
        <button type="button" class="close" onclick="$('#detalhesEleitorModal').modal('hide');" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalContent">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Carregando...</span>
          </div>
          <p class="mt-2">Carregando informações...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="$('#detalhesEleitorModal').modal('hide');">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
function verDetalhes(cpf, cro, inscricao) {
    // Resetar o conteúdo do modal
    document.getElementById('modalContent').innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Carregando...</span>
          </div>
          <p class="mt-2">Carregando informações...</p>
        </div>
    `;
    // Abrir o modal
    $('#detalhesEleitorModal').modal('show');
    // Fazer requisição AJAX para o arquivo dedicado
    fetch('/consulta-eleicoes/ajax-detalhes-eleitor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cpf=' + encodeURIComponent(cpf) + '&cro=' + encodeURIComponent(cro) + '&inscricao=' + encodeURIComponent(inscricao)
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('modalContent').innerHTML = data;
    })
    .catch(error => {
        document.getElementById('modalContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erro ao carregar os dados. Tente novamente.
            </div>
        `;
    });
}

// Garantir fechamento do modal ao clicar em FECHAR ou no X
$(document).ready(function() {
    // Fechar modal ao clicar no botão FECHAR
    $(document).on('click', '#detalhesEleitorModal .btn-secondary', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#detalhesEleitorModal').modal('hide');
        return false;
    });
    
    // Fechar modal ao clicar no X
    $(document).on('click', '#detalhesEleitorModal .close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#detalhesEleitorModal').modal('hide');
        return false;
    });
    
    // Fechar modal ao clicar fora dele
    $(document).on('click', '#detalhesEleitorModal', function(e) {
        if (e.target === this) {
            $('#detalhesEleitorModal').modal('hide');
        }
    });
    
    // Limpar conteúdo quando modal for fechado
    $('#detalhesEleitorModal').on('hidden.bs.modal', function () {
        $('#modalContent').html('');
    });
    
    // Método alternativo usando onclick direto
    $(document).on('click', '#detalhesEleitorModal .btn-secondary, #detalhesEleitorModal .close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#detalhesEleitorModal').modal('hide');
        return false;
    });
});
</script>

<?php
require_once INC_PATH . '/footer.php';
?>