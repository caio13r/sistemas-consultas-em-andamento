<?php
require_once INC_PATH .'/header.php';

use Cfo\SisConsultas\lib\Session;

Session::CheckSession();
Session::CheckAdmin();

if (isset($removeUser)) {
  echo $removeUser;
}

if (isset($inputGet['deactive'])) {
  $deactive = preg_replace('/[^a-zA-Z0-9-]/', '', (int)$inputGet['deactive']);
  $deactiveId = $users->userDeactiveByAdmin($deactive);
}

if (isset($deactiveId)) {
  echo $deactiveId;
}

if (isset($inputGet['active'])) {
  $active = preg_replace('/[^a-zA-Z0-9-]/', '', (int)$inputGet['active']);
  $activeId = $users->userActiveByAdmin($active);
}

if (isset($activeId)) {
  echo $activeId;
}
?>

<div class="container-fluid">

      <div class="card">
        <div class="card-header">
          <h5><i class="fas fa-users mr-2 mt-2"></i>Lista de usuários</h5>
        </div>
        <div class="card-body pr-2 pl-2">
          
        <div class="table-responsive">
          <table id="users" class="table table-sm table-striped table-bordered table-hover">
                  <thead>
                    <tr>
                      <th class="text-center">ID</th>
                      <th class="text-center">Nome</th>
                      <th class="text-center">E-mail</th>
                      <th class="text-center">Grupo</th>
                      <th class="text-center">Sub-grupo</th>
                      <th class="text-center">Status</th>
                      <th class="text-center">Criação</th>
                      <th class="text-center">Última Atividade</th>
                      <th width='22%' class="text-center">Ação</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php

                      $allUser = $users->selectAllUserData();

                      if ($allUser) {
                        $i = 0;
                        foreach ($allUser as $value) {
                          $i++;

                     ?>

                      <tr class="text-center"
                      <?php if (Session::get("id") == $value->id) {
                        echo "style='background:#d9edf7' ";
                      } ?>
                      >

                        <td><span style="font-size: 14px;"><?= $i; ?></span></td>
                        <td><span style="font-size: 14px;"><?= htmlspecialchars($value->name); ?></span></td>
                        <td><span style="font-size: 14px;"><?= htmlspecialchars($value->email); ?></span></td>
                        <td><span class="badge badge-lg badge-dark text-white"><?= $users->GroupName($value->grupo); ?></span></td>
                        <td><span class="badge badge-lg badge-dark text-white"><?= $users->SubGroupName($value->subgrupo); ?></span></td>
                        <td>
                          <?php if ($value->isActive == '1') { 
                          echo "<span class='badge badge-lg badge-success text-white'>Ativo</span>";
                          } else {
                          echo "<span class='badge badge-lg badge-danger text-white'>Desativado</span>";
                          } ?>
                        </td>

                        <td><span style="font-size: 14px;"><?= $users->formatDate($value->created_at); ?></span></td>
                        <td>
                          <span style="font-size: 14px;">
                            <?php
                              if ($value->last_activity != null) {
                                echo $users->formatDate($value->last_activity); 
                              } else {
                                echo '<i>null</i>';
                              }
                            ?>
                          </span>
                        </td>

                        <td>

                        <div class="form-group">

                            <a class="btn btn-info btn-sm" href="perfil?id=<?= $value->id;?>&edit=1">Editar</a>  

                             <?php if ($value->isActive == '1') { ?>
                               <a onclick="return confirm('Tem certeza que deseja desativar este usuário?')" class="btn btn-danger
                       <?php if (Session::get("id") == $value->id) {
                         echo "disabled";
                       } ?>
                                btn-sm " href="?deactive=<?= $value->id; ?>">Desativar</a>
                             <?php } elseif ($value->isActive == '0') { ?>
                               <a onclick="return confirm('Tem certeza que deseja ativar este usuário?')" class="btn btn-success
                       <?php if (Session::get("id") == $value->id) {
                         echo "disabled";
                       } ?>
                                btn-sm " href="?active=<?= $value->id; ?>">Ativar</a>
                             <?php } ?>

                        <?php  } ?>

                        </td>
                      </tr>
                    <?php } else { ?>
                      <tr class="text-center">
                      <td>Sem usuários disponíveis!</td>
                    </tr>
                    <?php } ?>

                  </tbody>

              </table>
            </div>
        </div>
      </div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
