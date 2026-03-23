<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();

$error = "<script language='javascript'>
window.alert('Não é possível acessar essa página.')
window.location.href='perfil?id=" . Session::get("id") . "';
</script>";

if (isset($inputGet['id'])) {
  $userid = (int)$inputGet['id'];
} else {
  echo $error;
}

if ($inputGet['id'] != Session::get("id") && Session::get("grupo") != 0) {
  echo $error;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['changepass'])) {
  $changePass = $users->changePasswordBysingelUserId($userid, $inputPost);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['changepassadm'])) {
  $changePass = $users->changePasswordByAdm($userid, $inputPost);
}

if (isset( $changePass)) {
  echo  $changePass;
}
?>

<div class="container-fluid">
  <div class="card ">
    <div class="card-header">
      <h5><i class="fas fa-wrench mr-2 mt-2"></i> Alterar senha <span class="float-right"> <a href="perfil?id=<?= htmlspecialchars($inputGet['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Voltar</a> </h5>
    </div>
    <div class="card-body">

    <?php 
    $getUinfo = $users->getUserInfoById($userid);

    if ($getUinfo) {
    
    ?>

    <?php if (isset($inputGet['id']) && !isset($inputGet['edit'])) { ?>

      <div class="col-md-6 offset-md-3">

        <form action="" method="POST">
            <div class="form-group">
              <label for="old_password">Senha antiga</label>
              <input type="password" name="old_password" placeholder="Digite a senha antiga" class="form-control">
            </div>
            <div class="form-group">
              <label for="new_password">Senha nova</label>
              <input type="password" name="new_password" placeholder="Digite a senha nova" class="form-control">
            </div>
            <div class="form-group">
              <button type="submit" onclick="return confirm('Tem certeza que deseja alterar a senha?')" name="changepass" class="btn btn-success">Alterar senha</button>
            </div>
        </form>

      </div>

    <?php } elseif (Session::get("grupo") == '0' && isset($inputGet['edit'])) { ?>

      <h5 class="mb-3">Alterar senha do usuário: <a href="perfil?id=<?= htmlspecialchars($inputGet['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($getUinfo->name); ?></a> </h5>

      <div class="col-md-6 offset-md-3">

        <form action="" method="POST">
            <div class="form-group">
              <label for="newpassword">Senha nova</label>
              <input type="password" name="newpassword" placeholder="Digite a nova senha" class="form-control">
            </div>
            <div class="form-group">
              <button type="submit" onclick="return confirm('Tem certeza que deseja alterar a senha deste usuário?')" name="changepassadm" class="btn btn-success">Alterar senha</button>
            </div>
        </form>

      </div>

    <?php } else {
      echo "<script language='javascript'>
      window.alert('Não é possível acessar essa página.')
      window.location.href='perfil?id=" . htmlspecialchars($inputGet['id'] ?? '', ENT_QUOTES, 'UTF-8') . "';
      </script>";
    } ?>

    <?php } ?>

    </div>
  </div>
</div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
