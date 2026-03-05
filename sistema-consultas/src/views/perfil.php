<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

$error = "<script language='javascript'>
window.alert('Não é possível acessar essa página.')
window.location.href='perfil?id=" . Session::get("id") . "';
</script>";

if (isset($inputGet['id'])) {
  $userid = preg_replace('/[^a-zA-Z0-9-]/', '', (int)$inputGet['id']);
} else {
  echo $error;
}

if ($inputGet['id'] != Session::get("id") && Session::get("grupo") != 0) {
  echo $error;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['update'])) {
  $updateUser = $users->updateUserByIdInfo($userid, $inputPost);
}

if (isset($updateUser)) {
  echo $updateUser;
}
?>

<div class="container-fluid">

 <div class="card ">
   <div class="card-header">
          <h5><i class="fas fa-user-circle mr-2 mt-2"></i> Perfil do usuário <span class="float-right"> <a href="index" class="btn btn-primary">Voltar</a> </h5>
        </div>
        <div class="card-body">

    <?php

    $getUinfo = $users->getUserInfoById($userid);
    
    if ($getUinfo) {

    ?>

    <h5 class="card-title mb-4">Informações de Perfil</h5>
    
    <?php if (isset($inputGet['id']) && !isset($inputGet['edit'])) { ?>

      <div class="row">
        <div class="col-md-6">

                <b>Nome:</b><br>
                <?= $getUinfo->name; ?>
        </div>
        <div class="col-md-6">

        <b>E-mail:</b><br>
                <td width='70%'><p class="text-break"><?= $getUinfo->email; ?></p></td>

        </div>
        <div class="col-md-6">

        <b>Grupo de Acesso:</b><br>
                <td width='60%'><?= $users->GroupName($getUinfo->grupo); ?></td>
        </div>
        <div class="col-md-6">

        <b>Sub Grupo de Acesso:</b><br>
                <td width='60%'><?= $users->SubGroupName($getUinfo->subgrupo); ?></td>

        </div>

        <?php if (isset($getUinfo->telefoneCtt)) { ?>
          <div class="col-md-6">  
                <br><b>Telefone para Contato:</b><br>
                <?= $getUinfo->telefoneCtt; ?>
          </div>
        <?php } ?>
        <?php if (isset($getUinfo->telefoneWpp)) { ?>
          <div class="col-md-6">
                <br><b>Telefone para Whatsapp:</b><br>
                <?= $getUinfo->telefoneWpp; ?>
          </div>
        <?php } ?>
      </div>

      <a class="btn btn-primary mt-3 mr-2" href="mudar-senha?id=<?= $getUinfo->id; ?>">Alterar senha</a>

      <?php if (Session::get("grupo") === 0) { ?>
        <a class="btn btn-secondary mt-3" href="?id=<?= $getUinfo->id; ?>&edit=1">Editar Usuário</a>
      <?php } ?>

     
    <?php } elseif (Session::get("grupo") === 0 && isset($inputGet['edit'])) { ?>

      <div class="col-md-6 offset-md-3">

        <form action="#" method="POST">
          
          <div class="form-row">
            <div class="form-group col-md-9">
              <label for="name">Nome</label>
              <input type="text" id="name" name="name" value="<?= $getUinfo->name; ?>" class="form-control">
            </div>
            <div class="form-group col-md-3">
              <label for="isActive">Status:</label>
              <select id="isActive" name="isActive" class="form-control">
                <option value="1" <?= $getUinfo->isActive  == 1? 'selected' : ''; ?> >Ativo</option>
                <option value="0" <?= $getUinfo->isActive == 0? 'selected' : ''; ?>>Desativado</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-12">
              <label for="email">E-mail</label>
              <input type="email" id="email" name="email" value="<?= $getUinfo->email; ?>" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-6">
              <label for="grupo">Selecione o Grupo</label>
              <select class="form-control" name="grupo" id="grupo">
                <?php
                  foreach(Helper::$acessList as $val => $value) {
                      $selected = ($getUinfo->grupo == $val) ? 'selected' : '';
                      echo "<option value='$val' $selected>$value</option>";
                  }
                ?>
              </select>
            </div>

            <div class="form-group col-6">
              <label for="subgrupo">Selecione o Sub Grupo</label>
              <select class="form-control" name="subgrupo" id="subgrupo">
                <?php
                  foreach(Helper::$subAcessList as $val => $value) {
                    $selected = ($getUinfo->subgrupo == $val) ? 'selected bold' : '';
                      echo "<option value='$val' $selected>$value</option>";
                  }
                ?>
              </select>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="telefoneCtt">Telefone para Contato</label>
              <input type="text" id="telefoneCtt" name="telefoneCtt" value="<?= $getUinfo->telefoneCtt; ?>" class="form-control" class="form-control" maxlength="14">
            </div>
            <div class="form-group col-md-6">
              <label for="telefoneWpp">Telefone para Whatsapp</label>
              <input type="text" id="telefoneWpp" name="telefoneWpp" value="<?= $getUinfo->telefoneWpp; ?>" class="form-control" class="form-control" maxlength="14">
            </div>
          </div>

          <div class="form-row mt-3 justify-content-center align-items-center" style="gap: 30px;">
            <div class="form-group col-md-3">
              <label for="cracha">Crachá CFO:</label>
              <select id="cracha" name="cracha" class="form-control">
                <option value="1" <?= $getUinfo->cracha  == 1? 'selected' : ''; ?> >Sim</option>
                <option value="0" <?= $getUinfo->cracha == 0? 'selected' : ''; ?>>Não</option>
              </select>
            </div>

            <div class="foto-container">
                <label for="foto">Foto:</label><br>
                <?php if (empty($getUinfo->imagem)): ?>
                    <p>Sem foto disponível</p>
                <?php else: ?>
                    <img src="data:image/jpeg;base64,<?= htmlspecialchars($getUinfo->imagem, ENT_QUOTES, 'UTF-8') ?>" 
                        alt="Foto do usuário" style="max-width: 200px; height: auto;">
                    <br>
                    <a style="margin-top: 5px" href="data:image/jpeg;base64,<?= $getUinfo->imagem ?>" download="foto_<?= $getUinfo->name ?>.jpg" class="btn btn-primary">
                        Baixar Foto
                    </a>
                <?php endif; ?>
            </div>


          </div>

          <div class="form-group">
            <button type="submit" name="update" class="btn btn-success">Atualizar</button>
            <?php if (Session::get("grupo") === 0) { ?>
              <a class="btn btn-primary" href="mudar-senha?id=<?= $getUinfo->id; ?>&edit=1">Alterar senha</a>
            <?php } else { ?>
              <a class="btn btn-primary" href="mudar-senha?id=<?= $getUinfo->id; ?>">Alterar senha</a>
            <?php } ?>
          </div>

        </form>

      </div>

      <?php } else {
        echo "<script language='javascript'>
        window.alert('Não é possível acessar essa página.')
        window.location.href='perfil?id=" . Session::get("id") . "';
        </script>";
      } ?>
      
    <?php } ?>

    </div>
  </div>
</div>
</div>

<script>
    // to do gabriel

    // telefone ctt
    document.addEventListener('DOMContentLoaded', function() {
      const telefoneCtt = document.getElementById('telefoneCtt');

      function aplicarMascara() {
        let valor = telefoneCtt.value.replace(/\D/g, ''); // Remove tudo que não for dígito
        if (valor.length === 11) { // Verifica se o valor tem o tamanho correto
          valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3'); // Formata com máscara
        }
        telefoneCtt.value = valor; // Atualiza o valor do campo
      }

      telefoneCtt.addEventListener('input', aplicarMascara);
      aplicarMascara(); // Aplica a máscara ao valor inicial
    });

    // telefone wpp
    document.addEventListener('DOMContentLoaded', function() {
      const telefoneCtt = document.getElementById('telefoneWpp');

      function aplicarMascara() {
        let valor = telefoneCtt.value.replace(/\D/g, ''); 
        if (valor.length === 11) { 
          valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3'); 
        }
        telefoneCtt.value = valor;
      }

      telefoneCtt.addEventListener('input', aplicarMascara);
      aplicarMascara();
    });
  </script>

<?php
require_once INC_PATH . '/footer.php';
?>
