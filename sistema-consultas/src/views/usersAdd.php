<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();
Session::CheckAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['addUser'])) {
  $userAdd = $users->addNewUserByAdmin($inputPost);
}

if (isset($userAdd)) {
  echo $userAdd;
}
?>

<div class="container-fluid">

<div class="card ">
  <div class="card-header">
    <h5><i class="fas fa-user-plus mt-2 mr-2"></i> Adicionar novo usuário</h5>
  </div>
  <div class="card-body">

      <div class="col-md-8 offset-md-2">

      <form class="" action="" method="post">
        <div class="form-row pt-3">
            <div class="form-group col-md-9">
              <label for="name">Nome</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
              <label for="isActive">Status:</label>
              <select id="isActive" name="isActive" class="form-control">
                <option value="1" selected >Ativo</option>
                <option value="0">Desativado</option>
              </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
              <label for="email">E-mail</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
              <label for="password">Senha</label>
              <input type="password" name="password" class="form-control" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="grupo">Grupo de Acesso</label>
                <select class="form-control" name="grupo" id="grupo" required>
                  <option disabled selected value>Selecione</option>
                  <?php
                    foreach(Helper::$acessList as $val => $value) {
                      echo "<option value='$val'>$value</option>";
                    }
                  ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="subgrupo">Sub-grupo de Acesso</label>
                <select class="form-control" name="subgrupo" id="subgrupo" required>
                  <option disabled selected value>Selecione</option>
                  <?php
                    foreach(Helper::$subAcessList as $val => $value) {
                      echo "<option value='$val'>$value</option>";
                    }
                  ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
              <label for="telefoneCtt">Telefone para Contato</label>
              <input id="telefoneCtt" type="text" name="telefoneCtt" class="form-control" maxlength="14">
            </div>
            <div class="form-group col-md-6">
              <label for="telefoneWpp">Telefone para Whatsapp</label>
              <input id="telefoneWpp" type="text" name="telefoneWpp" class="form-control" maxlength="14">
            </div>
        </div>
        <div class="form-group">
          <button type="submit" name="addUser" class="btn btn-success">Registrar</button>
        </div>
      </form>
    </div>

  </div>
</div>
</div>
</div>

<script>
  // to do gabriel
  const telefoneCtt = document.getElementById('telefoneCtt');

  telefoneCtt.addEventListener('input', function() {
    let valor = this.value.replace(/\D/g, ''); // Remove tudo que não for dígito
    valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3'); // Formata com máscara
    this.value = valor; // Atualiza o valor do campo
  });

  const telefoneWpp = document.getElementById('telefoneWpp');

  telefoneWpp.addEventListener('input', function() {
  let valor = this.value.replace(/\D/g, ''); // Remove tudo que não for dígito
  valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3'); // Formata com máscara
  this.value = valor; // Atualiza o valor do campo
});
</script>

<?php
require_once INC_PATH .'/footer.php';
?>