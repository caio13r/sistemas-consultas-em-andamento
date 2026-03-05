<?php

namespace Cfo\SisConsultas\lib;

use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\ActivityLog;

use PDO;

class Users {

  // Db Property
  private $db;

  // Db __construct Method
  public function __construct(){
    $db = Database1::getInstance();
    $con = $db->getConnection();
    $this->db = $con;
  }

  // Date formate Method
  public function formatDate($date){
    // date_default_timezone_set('Asia/Dhaka');
    $strtime = strtotime($date);
    return date('d/m/Y H:i', $strtime);
  }

  // Checar a nome do grupo
  public function GroupName($string){
    $sql = "SELECT * FROM tbl_grupos WHERE id = $string";
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $result = $row['grupo'];

    return $result;    
  }

  // Checar a nome do sub grupo
  public function SubGroupName($string){
    $sql = "SELECT * FROM tbl_subgrupos WHERE id = $string";
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $result = $row['subgrupo'];

    return $result;    
  }

    // Self check nome do grupo
    public function CheckGroupName(){
      Session::init();
      $id = Session::get('grupo');
  
      $sql = "SELECT * FROM tbl_grupos WHERE id = $id";
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $this->db->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $result = $row['grupo'];
  
      return $result;    
    }
  
    // Self check a nome do subgrupo
    public function CheckSubGroupName(){
      $id = Session::get('subgrupo');
  
      $sql = "SELECT * FROM tbl_subgrupos WHERE id = $id";
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $this->db->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $result = $row['subgrupo'];
  
      return $result;    
    }
  
    // Self check UF do grupo
    public function CheckGroupUf(){
      $id = Session::get('grupo');
  
      $sql = "SELECT * FROM tbl_grupos WHERE id = $id";
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $this->db->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $result = $row['uf'];
  
      return $result;    
    }

  // Check Exist Email Address Method
  public function checkExistEmail($email){
    $sql = "SELECT email from tbl_users WHERE email = :email";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':email', $email);
     $stmt->execute();
    if ($stmt->rowCount()> 0) {
      return true;
    } else {
      return false;
    }
  }

  // Check if register is valid
  public function checkRegister($uf){
    $sql = "SELECT  tb_u.id, tb_s.subgrupo, tb_u.grupo  from db_sistema_consultas.tbl_users tb_u
            inner join db_sistema_consultas.tbl_subgrupos tb_s  on tb_u.subgrupo = tb_s.id
            where tb_s.subgrupo = 'Fiscalização - Coordenação' 
            AND tb_u.grupo = :uf
            AND tb_u.isActive = 1";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':uf', $uf);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($stmt->rowCount()> 0) {
      return $result[0]['id'];
    } else {
      return 0;
    }
  }

  // Return Email Id Method
  public function getIdByEmail($email){
    $sql = "SELECT id from tbl_users WHERE email = :email";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($stmt->rowCount() > 0) {
      return $result[0]['id'];
    } else {
      return 0;
    }
  }

  public function checkAcess($string){
    $grupo = (Session::get('grupo') != 0 && Session::get('grupo') != 1) ? 'CRO' : 'CFO';
    $subgrupo = $this->SubGroupName(Session::get('subgrupo'));
    $sql = "SELECT * FROM tbl_acessos WHERE grupo = '$grupo' AND subgrupo = '$subgrupo'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row[$string] == false) {
      echo "<script language='javascript'>
            window.alert('Você não tem permissão para acessar essa página.')
            window.location.href='index';
            </script>";
    }
  }

  // User Registration Method
  public function userRegistration($data){
    $name = $data['name'];
    $email = $data['email'];
    $grupo = $data['grupo'];
    $subgrupo = $data['subgrupo'];
    $password = $data['password'];

    if ($name == "" || $email == "" || $password == "") {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error !</strong> Por favor, preencha todos os campos!</div>';
      return $msg;
    } elseif(strlen($password) < 5) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> Sua senha está curta, o mínimo são 6 carácteres!</div>';
      return $msg;
    } elseif(!preg_match("#[0-9]+#",$password)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> Sua senha precisa ter ao menos 1 número!</div>';
      return $msg;
    } elseif(!preg_match("#[a-z]+#",$password)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> Sua senha precisa ter ao menos 1 letra!</div>';
      return $msg;
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL === false)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> E-mail inválido!</div>';
      return $msg;
    } elseif ($checkEmail == true) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error !</strong> Este e-mail já existe, por favor tente outro e-mail!</div>';
      return $msg;
    } else {

      $sql = "INSERT INTO tbl_users(name, email, password, grupo, subgrupo) VALUES(:name, :email, :password, :grupo, :subgrupo)";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':name', $name);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':password', SHA1($password));
      $stmt->bindValue(':grupo', $grupo);
      $stmt->bindValue(':subgrupo', $subgrupo);
      $result = $stmt->execute();
      if ($result) {
        $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Sucesso!</strong> Conta refistrada com sucesso!</div>';
        return $msg;
      } else {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> Algo está incorreto!</div>';
        return $msg;
      }
    }
  }

  // Add New User By Admin
  public function addNewUserByAdmin($data){
    $name = $data['name'];
    $email = $data['email'];
    $grupo = $data['grupo'];
    $subgrupo = $data['subgrupo'];
    $password = $data['password'];
    $telefoneCtt = $data['telefoneCtt'] ? $data['telefoneCtt'] : null;
    $telefoneWpp = $data['telefoneWpp'] ? $data['telefoneWpp'] : null;
    $isActive = intval($data['isActive']);

    $checkEmail = $this->checkExistEmail($email);
    $checkRegister = $this->checkRegister($grupo);

    if ($name == "" || $email == "" || $password == "") {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> Nenhum campo pode ficar vázio!</div>';
      return $msg;
    } elseif(strlen($password) < 5) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> A senha está muito curta, o mínimo são 6 carácteres!</div>';
      return $msg;
    } elseif(!preg_match("#[0-9]+#",$password)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error !</strong> A senha precisa ter ao menos 1 número!</div>';
      return $msg;
    } elseif(!preg_match("#[a-z]+#",$password)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> A senha precisa ter ao menos 1 letra!</div>';
      return $msg;
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL === false)) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> E-mail inválido!</div>';
      return $msg;
    } elseif ($checkEmail == true) {
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>Error!</strong> Este e-mail já existe, gostaria de editá-lo?
                  <hr>
                  <div>
                      <a href="./perfil?id='.$this->getIdByEmail($email).'&edit=1" class="btn btn-primary mr-2">Editar</a>
                  </div>
              </div>';
        return $msg;
    } elseif ($subgrupo == 8 && $checkRegister > 0){
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>Error!</strong> Este grupo de acesso já possui coordenador associado, gostaria de editá-lo?
                  <hr>
                  <div>
                      <a href="./perfil?id='.$checkRegister.'&edit=1" class="btn btn-primary mr-2">Editar</a>
                  </div>
              </div>';
        return $msg;
    } 
    else {

      $sql = "INSERT INTO tbl_users(name, email, password, grupo, subgrupo, telefoneCtt, telefoneWpp, isActive) VALUES(:name, :email, :password, :grupo, :subgrupo, :telefoneCtt, :telefoneWpp, :isActive)";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':name', $name);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':password', SHA1($password));
      $stmt->bindValue(':grupo', $grupo);
      $stmt->bindValue(':subgrupo', $subgrupo);
      $stmt->bindValue(':telefoneCtt', $telefoneCtt);
      $stmt->bindValue(':telefoneWpp', $telefoneWpp);
      $stmt->bindValue(':isActive', $isActive);

      $result = $stmt->execute();
      if ($result) {
        $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Sucesso!</strong> Conta registrada com sucesso!</div>';
        return $msg;
      } else {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> Algo está incorreto!</div>';
        return $msg;
      }
    }
  }

  // Set Photo for user name tag
  public function setPhotoAndCpf($userid, $foto, $cracha, $cpf){
    $sql = "UPDATE tbl_users SET imagem = :imagem, cracha = :cracha, cpf = :cpf WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':imagem', $foto);
    $stmt->bindValue(':cracha', $cracha);
    $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':id', $userid);
    $result = $stmt->execute();

    if ($result) {
      $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Successo!</strong> CPF e Foto enviados com sucesso!</div>';

      Session::set('cracha', $cracha);
      Session::set('cpf', $cpf);
      Session::set('imagem', $foto);

      return $msg;
    } else{
      $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Error!</strong> Algo deu errado, tente novamento mais tarde!</div>';
      return $msg;
    }
  }

  // Select All User Method
  public function selectAllUserData(){
    $sql = "SELECT * FROM tbl_users ORDER BY id DESC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }  

  // User login Autho Method
  public function userLoginAutho($email, $password){
    $password = SHA1($password);
    $sql = "SELECT * FROM tbl_users WHERE email = :email and password = :password LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $password);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ);
  }
  
  // Check User Account Satatus
  public function CheckActiveUser($email){
    $sql = "SELECT * FROM tbl_users WHERE email = :email and isActive = :isActive LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':isActive', 0);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ);
  }

    // User Login Authotication Method
    public function userLoginAuthotication($data){
      $email = $data['email'];
      $password = $data['password'];

      $checkEmail = $this->checkExistEmail($email);

      if ($email == "" || $password == "") {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error !</strong> E-mail ou senha não podem ficar vázios!</div>';
        return $msg;
      } elseif (filter_var($email, FILTER_VALIDATE_EMAIL === false)) {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error !</strong> E-mail inválido!</div>';
        return $msg;
      } elseif ($checkEmail == false) {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error !</strong> E-mail não encontrado, use um e-mail válido!</div>';
        return $msg;
      } else {

        $logResult = $this->userLoginAutho($email, $password);
        $chkActive = $this->CheckActiveUser($email);

        if ($chkActive == true) {
          $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error!</strong> Desculpe, esta conta está desativada, conta-te algum admin!</div>';
          return $msg;
        } elseif ($logResult) {

          $sql = "UPDATE tbl_users SET last_activity = :last_activity WHERE email = :email";
          $stmt= $this->db->prepare($sql);
          $stmt->bindValue(':email', $email);
          $stmt->bindValue(':last_activity', date('d-m-Y H:i:s'));
          $stmt->execute();

          Session::set('login', true);
          Session::set('id', $logResult->id);
          Session::set('grupo', $logResult->grupo);
          Session::set('subgrupo', $logResult->subgrupo);
          Session::set('name', $logResult->name);
          Session::set('email', $logResult->email);
          Session::set('cpf', $logResult->cpf);
          Session::set('foto', $logResult->imagem);
          Session::set('cracha', $logResult->cracha);
          Session::set('logMsg', '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Sucesso!</strong> Você entrou com sucesso!</div>');

          // Registrar log de login
          ActivityLog::registrarLogin($logResult->id, $logResult->name, $logResult->email, $logResult->grupo, $logResult->subgrupo);

          echo "<script>location.href='index';</script>";
        } else {
          $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error !</strong> E-mail ou a senha estão incorretos!</div>';
          return $msg;
        }
      }
    }

    // Get Single User Information By Id Method
    public function getUserInfoById($userid){
      $sql = "SELECT * FROM tbl_users WHERE id = :id LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':id', $userid);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_OBJ);
      if ($result) {
        return $result;
      } else {
        return false;
      }
    }

    // Get Single User Information By Id Method
    public function updateUserByIdInfo($userid, $data){
      $name = $data['name'];
      $email = $data['email'];
      $grupo = $data['grupo'];
      $subgrupo = $data['subgrupo'];
      $telefoneCtt = $data['telefoneCtt'] ? $data['telefoneCtt'] : null;
      $telefoneWpp = $data['telefoneWpp'] ? $data['telefoneWpp'] : null;
      $isActive = intval($data['isActive']);
      $cracha = intval($data['cracha']);

      $checkRegister = $this->checkRegister($grupo);

      if ($name == "" || $email == "") {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error !</strong> Tudos os formulários devem ser preenchidos!</div>';
        return $msg;
      } elseif (filter_var($email, FILTER_VALIDATE_EMAIL === false)) {
          $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error !</strong> Endereço de e-mail inválido!</div>';
          return $msg;
      } elseif ( $subgrupo == 8 && $checkRegister > 0 && $checkRegister != $this->getIdByEmail($email)){
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  <strong>Error!</strong> Este grupo de acesso já possui coordenador associado, gostaria de editá-lo?
                    <hr>
                    <div>
                        <a href="./perfil?id='.$checkRegister.'&edit=1" class="btn btn-primary mr-2">Editar</a>
                    </div>
                </div>';
        return $msg;
      } 
      else {

        $sql = "UPDATE tbl_users SET
          name = :name,
          email = :email,
          grupo = :grupo,
          subgrupo = :subgrupo,
          telefoneCtt = :telefoneCtt,
          telefoneWpp = :telefoneWpp,
          isActive = :isActive,
          cracha = :cracha
          WHERE id = :id";
          $stmt= $this->db->prepare($sql);
          $stmt->bindValue(':name', $name);
          $stmt->bindValue(':email', $email);
          $stmt->bindValue(':grupo', $grupo);
          $stmt->bindValue(':subgrupo', $subgrupo);
          $stmt->bindValue(':telefoneCtt', $telefoneCtt);
          $stmt->bindValue(':telefoneWpp', $telefoneWpp);
          $stmt->bindValue(':isActive', $isActive);
          $stmt->bindValue(':cracha', $cracha);
          $stmt->bindValue(':id', $userid);
        $result = $stmt->execute();

        if ($result) {
          $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Successo!</strong> Suas informações foram atualizadas com sucesso!</div>';

          Session::set('cracha', $cracha);

          return $msg;
        } else{
          $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error!</strong> Dados não preenchidos!</div>';
          return $msg;
        }
      }
    }

    // Delete User by Id Method
    public function deleteUserById($remove){
      $sql = "DELETE FROM tbl_users WHERE id = :id ";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':id', $remove);
        $result =$stmt->execute();
        if ($result) {
          $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Success !</strong> Conta deletada com sucesso!</div>';
          return $msg;
        } else {
          $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error!</strong> Dados não deletados!</div>';
          return $msg;
        }
    }

    // User Deactivated By Admin
    public function userDeactiveByAdmin($deactive){
      $sql = "UPDATE tbl_users SET

       isActive=:isActive
       WHERE id = :id";

       $stmt = $this->db->prepare($sql);
       $stmt->bindValue(':isActive', 0);
       $stmt->bindValue(':id', $deactive);
       $result = $stmt->execute();
        if ($result) {
          $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Sucesso!</strong> Conta desativada com sucesso!</div>';
          return $msg;
        } else {
          $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
          <strong>Error!</strong> Dados não desativados!</div>';
          return $msg;
        }
    }

    // User Deactivated By Admin
    public function userActiveByAdmin($active){
      $result = $this->getUserInfoById(intval($active));
      $checkRegister = $this->checkRegister($result->grupo);

      if ($checkRegister == 0) {
        $sql = "UPDATE tbl_users SET
        isActive=:isActive
        WHERE id = :id";
 
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':isActive', 1);
        $stmt->bindValue(':id', $active);
        $result = $stmt->execute();
         if ($result) {
           $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
           <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
           <strong>Sucesso!</strong> Conta ativada com sucesso!</div>';
           return $msg;
         } else {
           $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
           <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
           <strong>Error!</strong> Dados naõ ativados!</div>';
           return $msg;
         }
      } else {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  <strong>Error!</strong> Este grupo de acesso já possui coordenador associado, gostaria de editá-lo?
                    <hr>
                    <div>
                        <a href="./perfil?id='.$checkRegister.'&edit=1" class="btn btn-primary mr-2">Editar</a>
                    </div>
                </div>';
        return $msg;
      }
    }

    // Check Old password method
    public function CheckOldPassword($userid, $old_pass){
      $old_pass = SHA1($old_pass);
      $sql = "SELECT password FROM tbl_users WHERE password = :password AND id =:id";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':password', $old_pass);
      $stmt->bindValue(':id', $userid);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        return true;
      } else {
        return false;
      }
    }

    // Change User pass By Id
    public function changePasswordBysingelUserId ($userid, $data) {

      $old_pass = $data['old_password'];
      $new_pass = $data['new_password'];

      if ($old_pass == "" || $new_pass == "") {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O password não foi inserido!</div>';
        return $msg;
      } elseif (strlen($new_pass) < 6) {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O novo password deve ter 6 ou mais characters!</div>';
        return $msg;
       }

         $oldPass = $this->CheckOldPassword($userid, $old_pass);
         if ($oldPass == false) {
            $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Error !</strong> O antigo password não pode ser igual!</div>';
            return $msg;
         } else {
           $new_pass = SHA1($new_pass);
           $sql = "UPDATE tbl_users SET

            password=:password
            WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':password', $new_pass);
            $stmt->bindValue(':id', $userid);
            $result = $stmt->execute();

          if ($result) {
            $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Successo!</strong> O password foi alterado com sucesso!</div>';
            return $msg;
          } else {
            $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Error!</strong> Password não foi alterado!</div>';
            return $msg;
          }

        }

    }

    public function changePasswordByAdm ($userid, $data) {

      $new_pass = $data['newpassword'];

      if (strlen($new_pass) < 6) {
        $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O novo password deve ter 6 ou mais characters!</div>';
        return $msg;
       } else {
           $new_pass = SHA1($new_pass);
           $sql = "UPDATE tbl_users SET

            password=:password
            WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':password', $new_pass);
            $stmt->bindValue(':id', $userid);
            $result = $stmt->execute();

          if ($result) {
            $msg = '<div class="m-4 alert alert-success alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Successo!</strong> O password foi alterado com sucesso!</div>';
            return $msg;
          } else {
            $msg = '<div class="m-4 alert alert-danger alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Error!</strong> Password não foi alterado!</div>';
            return $msg;
          }

        }

    }

    public function CheckIdNewPass ($idnewpass) {
      $sql = "SELECT idNewPassword FROM tbl_users WHERE idNewPassword = :idnewpass";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':idnewpass', $idnewpass);
      // $stmt->bindValue(':id', $userid);
      $stmt->execute();
      if ($stmt->rowCount() == 1) {
        return true;
      } else {
        return false;
      }
    }

    public function changePassword ($data) {

      $email = $data["email"];
      $newpass = $data["newpass"];
      $newpass2 = $data["newpass2"];
      $idnewpass = $data["idnewpass"];

      $checkEmail = $this->checkExistEmail($email);
      $checkIdNewPass = $this->CheckIdNewPass($idnewpass);
      
      if ($checkIdNewPass == false) {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O token para alteração de senha está incorreto!</div>';
        return $msg;
      } elseif ($checkEmail == false) {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O e-mail informado não foi encontrado!</div>';
        return $msg;
      } elseif ($newpass == "") {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O password não foi inserido!</div>';
        return $msg;
      } elseif ($newpass != $newpass2) {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> O password não combinou com a repetição!</div>';
        return $msg;
      }
  
      $newpass = SHA1($newpass);
      $sql = "UPDATE tbl_users SET password = :password, idNewPassword = null WHERE idNewPassword = :idnewpass AND email = :email";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':password', $newpass);
      $stmt->bindValue(':idnewpass', $idnewpass);
      $stmt->bindValue(':email', $email);
      $result = $stmt->execute();
  
      if ($result) {
        $msg = '<div class="alert alert-success alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Successo!</strong> O password foi alterado com sucesso!</div>';
        return $msg;
      } else {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> Password não foi alterado!</div>';
        return $msg;
      }
  
    }

}
