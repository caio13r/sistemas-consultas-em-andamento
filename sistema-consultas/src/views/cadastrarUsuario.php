<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::init();
Session::CheckLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['cadastrar'])) {
    $nome = $inputPost['nome'];
    $email = $inputPost['email'];
    $senha = password_hash($inputPost['senha'], PASSWORD_DEFAULT);
    $cracha_cfo = isset($inputPost['cracha_cfo']) ? 1 : 0;
    $cargo = 'Usuário';
    $subgrupo = 'Colaborador CFO';
    $telefone_contato = $inputPost['telefone_contato'];
    $telefone_whatsapp = $inputPost['telefone_whatsapp'] ?? null;
    
    // Upload da foto
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $foto_nome = time() . '_' . basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_nome;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
            $foto = $foto_destino;
        }
    }
    
    try {
        $db = Database1::getInstance();
        $con = $db->getConnection();
        
        $sql = "INSERT INTO usuarios (nome, email, senha, cracha_cfo, cargo, subgrupo, telefone_contato, telefone_whatsapp, foto) VALUES (:nome, :email, :senha, :cracha_cfo, :cargo, :subgrupo, :telefone_contato, :telefone_whatsapp, :foto)";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $senha);
        $stmt->bindValue(':cracha_cfo', $cracha_cfo);
        $stmt->bindValue(':cargo', $cargo);
        $stmt->bindValue(':subgrupo', $subgrupo);
        $stmt->bindValue(':telefone_contato', $telefone_contato);
        $stmt->bindValue(':telefone_whatsapp', $telefone_whatsapp);
        $stmt->bindValue(':foto', $foto);
        $stmt->execute();
        
        $msg = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
    } catch (PDOException $error) {
        error_log("Erro ao cadastrar usuário: " . $error->getMessage());
        $msg = '<div class="alert alert-danger">Erro ao cadastrar usuário. Tente novamente mais tarde.</div>';
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-flex justify-content-center align-items-center bg-login">
                            <img src="assets/img/logocfo.png" width="280px">
                        </div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Cadastro de Usuário</h1>
                                </div>
                                <?php if (isset($msg)) echo $msg; ?>
                                <form id="cadastroUsuario" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="nome">Nome:</label>
                                        <input type="text" name="nome" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">E-mail:</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="senha">Senha:</label>
                                        <input type="password" name="senha" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="telefone_contato">Telefone para contato:</label>
                                        <input type="text" name="telefone_contato" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="telefone_whatsapp">Telefone para WhatsApp (opcional):</label>
                                        <input type="text" name="telefone_whatsapp" class="form-control">
                                    </div>
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="cracha_cfo" class="form-check-input" id="crachaCfo">
                                        <label class="form-check-label" for="crachaCfo">Crachá CFO</label>
                                    </div>
                                    <div class="form-group">
                                        <label for="foto">Foto:</label>
                                        <input type="file" name="foto" class="form-control" id="fotoInput" accept="image/*" onchange="previewFoto()">
                                    </div>
                                    <div class="form-group text-center">
                                        <img id="fotoPreview" src="#" alt="Pré-visualização" class="img-thumbnail d-none" width="150">
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="cadastrar" class="btn btn-success">Cadastrar</button>
                                    </div>
                                </form>
                                <script>
                                    function previewFoto() {
                                        var file = document.getElementById("fotoInput").files[0];
                                        var reader = new FileReader();
                                        reader.onloadend = function () {
                                            var preview = document.getElementById("fotoPreview");
                                            preview.src = reader.result;
                                            preview.classList.remove("d-none");
                                        }
                                        if (file) {
                                            reader.readAsDataURL(file);
                                        }
                                    }
                                </script>
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

<?php require_once INC_PATH . '/footer.php'; ?>
