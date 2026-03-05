<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Labels;

Session::init();
Session::CheckLogin();

$instance = new Labels();

if($instance->cacheLabelsRedis()){
    echo "<script>console.log('labels carregadas nos redis');</script>";
}else{
    echo "<script>console.log('erro ao carregar labels');</script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['login'])) {
   $userLog = $users->userLoginAuthotication($inputPost);
}

$logout = Session::get('logout');
if (isset($logout)) {
  echo $logout;
}
?>

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-xl-10 col-lg-12 col-md-9">

            <div class="card o-hidden border-0 shadow-lg my-5">
                
                <div class="card-body p-0">

                    <div class="row">
                        <div class="col-lg-6 d-flex justify-content-center align-items-center bg-login">
                            <!-- <p>SISTEMA CONSULTAS</p> -->
                            <div class="p-3">
                                <img src="assets/img/logocfo.png" width="280px">    
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Faça seu login!</h1>
                                </div>
                                <?php 
                                    if (isset($userLog)) {
                                        echo $userLog;
                                    }
                                ?>
                                <form method="post">
                                    <div class="form-group">
                                        <label for="email">E-mail</label>
                                        <input type="email" name="email" class="form-control" placeholder="Insira seu e-mail" value="<?= $inputPost["email"] ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Senha</label>
                                        <input type="password" name="password" class="form-control" placeholder="Insira sua senha">
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="login" class="btn btn-success">Fazer login</button>
                                    </div>
                                </form>
                                <div class="text-center mt-4">
                                    <a class="small" href="recuperar-senha">Esqueci minha senha</a>
                                </div>
                                <!--
                                <div class="text-center mt-4">
                                    <a class="small" href="Cadastrar-usuario">Cadastrar Colaborador do CFO </a>
                                </div>
                                -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
</div>

<?php
require_once INC_PATH . '/footer.php';
?>
