<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\EmailHelper;
use Cfo\SisConsultas\database\Database1;
use Exception;

Session::init();
Session::CheckLogin();
?>

<div class="container mt-4">

    <div class="row justify-content-center">

        <div class="col-xl-10 col-lg-12 col-md-9">

            <div class="card o-hidden border-0 shadow-lg my-5">
                
                <div class="card-body p-0">

                    <div class="row">
                        <div class="col-lg-6 d-flex justify-content-center align-items-center bg-login">
                            <!-- <p>SISTEMA CONSULTAS</p> -->
                            <div class="">
                                <img src="assets/img/logocfo.png" width="280px">    
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Esqueceu a sua senha?</h1>
                                </div>

                                <?php if (isset($inputGet["token"]) || isset($inputGet["email"])) { 
                                
                                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['recuperarpass'])) {
                                    $recuperarPass = $users->changePassword($inputPost);
                                }
                                
                                if (isset($recuperarPass)) {
                                    echo $recuperarPass;
                                }
                                
                                ?>

                                <form id="recuperarSenha" method="POST">
                                    <div class="form-group">
                                        <label for="email">E-mail cadastrado:</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($inputGet["email"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="idnewpass">ID de recuperação:</label>
                                        <input type="text" name="idnewpass" class="form-control" value="<?= htmlspecialchars($inputGet["token"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="newpass">Informe o novo password:</label>
                                        <input type="password" name="newpass" class="form-control" placeholder="Insira o novo password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="newpass2">Informe novamente o novo password:</label>
                                        <input type="password" name="newpass2" class="form-control" placeholder="Insira novamente o novo password" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="recuperarpass" class="btn btn-success">Recuperar senha</button>
                                    </div>
                                </form>

                                <?php } else { ?>

                                <p class="h6 mb-4 text-center">
                                    Basta digitar seu endereço de e-mail abaixo e enviaremos um link para redefinir sua senha!
                                </p>

                                <?php
                                    
                                    if (isset($inputPost["sendmail"])) {

                                        $checkEmail = $users->checkExistEmail($inputPost["sendmail"]);
                                        
                                        if ($checkEmail === true) {
                                        
                                            // Hash para recuperar senha
                                            $id = microtime();
                                            $a = explode(' ', $id);
                                            $a[0] = str_replace('.', '', $a[0]);
                                            $a[0] = base_convert($a[0], 10, 36);
                                            $a[1] = base_convert($a[1], 10, 36);
                                            $id = $a[0] . $a[1];
                                            $idpassword = strtoupper($id);
                                            
                                            try {
                                            $db = Database1::getInstance();
                                            $con = $db->getConnection();
                                        
                                            $email = $inputPost["sendmail"];
                                        
                                            $sql = "UPDATE tbl_users SET idNewPassword = :idpassword WHERE email = :email";
                                        
                                            $stmt = $con->prepare($sql);
                                            $stmt->bindValue(':email', $email);
                                            $stmt->bindValue(':idpassword', $idpassword);
                                            $result = $stmt->execute();
                                            } catch (PDOexception $error) {
                                                error_log("Error updating password recovery: " . $error->getMessage());
                                                $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
                                                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                                <strong>Erro!</strong> Ocorreu um erro ao processar a solicitação. Tente novamente mais tarde.</div>';
                                                echo $msg;
                                            }
                                        
                                            // Envio do e-mail usando EmailHelper
                                            try {
                                                $emailHelper = new EmailHelper();
                                                $result = $emailHelper->sendPasswordRecovery($email, $idpassword);
                                                
                                                if ($result['success']) {
                                                    $msg = '<div class="alert alert-success alert-dismissible mt-3" id="flash-msg">
                                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                                    <strong>Successo!</strong> O e-mail com o link para alteração de senha foi enviado para <u>'.htmlspecialchars($email, ENT_QUOTES, 'UTF-8').'</u></div>';
                                                    echo $msg;
                                                } else {
                                                    $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
                                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                                    <strong>Erro!</strong> ' . htmlspecialchars($result['message']) . '</div>';
                                                    echo $msg;
                                                }
                                            } catch (Exception $e) {
                                                $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
                                                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                                <strong>Erro!</strong> Não foi possível enviar o e-mail. Tente novamente mais tarde.</div>';
                                                echo $msg;
                                            }
                                        
                                        } else {
                                            $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
                                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                            <strong>Error!</strong> Nenhum endereço de e-mail foi encontrado!</div>';
                                            echo $msg;
                                        }
                                    
                                    }
                                ?>

                                <form id="recuperarSenha" method="POST">
                                    <div class="form-group">
                                        <label for="sendmail">Informe o e-mail cadastrado:</label>
                                        <input type="email" name="sendmail" class="form-control" placeholder="Insira seu e-mail" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">Recuperar senha</button>
                                    </div>
                                </form>

                                <?php } ?>
                                
                                <div class="text-center mt-4">
                                    <a class="small" href="login">Fazer login</a>
                                </div>
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
