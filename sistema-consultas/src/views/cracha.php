<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($inputPost['submit'])) {

    $userID = $_SESSION['id'];

    // Verificar se a foto foi capturada via base64 ou arquivo
    if (!empty($inputPost['fotoBase64'])) { 
        $fotoBase64 = $inputPost['fotoBase64'];

        // Remover o prefixo "data:image/png;base64," ou "data:image/jpeg;base64,"
        $fotoBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $fotoBase64);
        
        // Armazena no banco de dados
        $foto = $fotoBase64;
    } elseif (isset($_FILES['input_da_imagem']) && $_FILES['input_da_imagem']['error'] === UPLOAD_ERR_OK) {
        // Caso a foto tenha sido enviada por um arquivo
        $foto = base64_encode(file_get_contents($_FILES['input_da_imagem']['tmp_name']));
    } else {
        $foto = null;
    }

    // Recebe o CPF do formulário
    $cpf = $inputPost['cpf'];
    $cracha = 0;

    // Atualiza a foto e CPF no banco de dados
    $setCracha = $users->setPhotoAndCpf($userID, $foto, $cracha, $cpf);
}

if (isset($setCracha)) {
    echo $setCracha;
}
?>

<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h5><i class="fas fa-user-edit mt-2 mr-2"></i> Atualizar CPF e Foto</h5>
    </div>
    <div class="card-body">
      <div class="col-md-8 offset-md-2">

        <?php if ($_SESSION['cracha'] == 0) {?>
          <div class="alert alert-danger">
            <strong>Atenção!</strong> Você não está habilitado para envio de foto e CPF. Caso ainda não tenha enviado, contate o RH.
          </div>
        <?php } ?>

        <form action="" method="post" enctype="multipart/form-data">
          <div class="form-group">
            <label>Nome:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" disabled>
          </div>
          <div class="form-group">
            <label>E-mail:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" disabled>
          </div>
          <?php if ($_SESSION['cracha'] == 1) { ?>
            <div class="form-group">
              <label for="cpf">CPF:</label>
              <input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($_SESSION["cpf"] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="14">
            </div>
            
            <div class="row" style="justify-content: center; align-items: center; gap: 30px">
              <div class="form-group d-flex text-center justify-content-center">
                  <label class="foto" for="input_da_imagem" tabIndex="0">
                      <span class="imagem_foto">Escolha uma imagem</span>
                      <img id="preview" src="" style="display: none;">
                  </label>
                  <input type="file" name="input_da_imagem" id="input_da_imagem" style="display: none;" onchange="mostrarImagem(event)">
              </div>
              <div class="form-group">
                <p>Caso escolha a foto:</p>
                <ul>
                  <li>Deve ser uma foto recente</li>
                  <li>Deve ser uma foto do rosto</li>
                  <li>Deve ser uma foto com fundo branco</li>
                </ul>
                <p>Caso for tirar a foto:</p>
                <ul>
                  <li>Deve mostrar o rosto</li>
                  <li>Deve estar perto da câmera</li>
                  <li>Deve ser uma foto com fundo branco</li>
                </ul>
              </div>
            </div>

            <div class="form-group text-center">
              <button type="button" class="btn btn-primary mt-2" onclick="abrirCamera()">Tirar Foto</button>
              <video id="video" width="100%" autoplay style="display:none;"></video>
              <canvas id="canvas" style="display:none;"></canvas>
              <div class="action_buttons" style="display:flex; justify-content: center; flex-direction: row; gap: 10px">
                <button type="button" class="btn btn-success mt-2" id="captureBtn" style="display:none;" onclick="capturarFoto()">Capturar</button>
                <button type="button" class="btn btn-danger mt-2" id="cancelBtn" style="display:none;" onclick="cancelarCamera()">Cancelar</button>
              </div>
              <input type="hidden" name="fotoBase64" id="fotoBase64">
            </div>
            <div class="form-group">
              <button type="submit" name="submit" class="btn btn-success">Atualizar</button>
            </div>
          <?php } ?>
        </form>
      </div>
    </div>
  </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// Função para exibir a imagem escolhida e cortar antes de enviar
function mostrarImagem(e) {
    const inputTarget = e.target;
    const file = inputTarget.files[0];
    const preview = document.getElementById("preview");
    const imagem_foto = document.querySelector(".imagem_foto");

    if (file) {
        const reader = new FileReader();

        reader.onload = function(event) {
            preview.src = event.target.result;

            // Exibir a imagem escolhida
            preview.style.display = 'block';
            imagem_foto.style.display = 'none';

            // Esperar a imagem carregar para manipulação
            preview.onload = function() {
                cortarImagem(preview); // Função para cortar a imagem
            };
        };

        reader.readAsDataURL(file);
    } else {
        imagem_foto.innerHTML = "Escolha uma imagem";
        imagem_foto.style.display = 'block';
        preview.style.display = 'none';
    }
}

function cortarImagem(imagem) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Definir o tamanho da área de recorte, por exemplo, 3:4
    const containerWidth = 300;  // Largura do contêiner (.foto)
    const containerHeight = 400; // Altura do contêiner (.foto)
    
    canvas.width = containerWidth;
    canvas.height = containerHeight;

    // Proporção da imagem e da área de recorte
    const imgAspectRatio = imagem.naturalWidth / imagem.naturalHeight;
    const containerAspectRatio = containerWidth / containerHeight;
    
    let sx, sy, sWidth, sHeight;

    // Se a imagem for mais larga que a área de recorte
    if (imgAspectRatio > containerAspectRatio) {
        sHeight = imagem.naturalHeight;
        sWidth = sHeight * containerAspectRatio;
        sx = (imagem.naturalWidth - sWidth) / 2;
        sy = 0;
    } else {
        // Se a imagem for mais alta que a área de recorte
        sWidth = imagem.naturalWidth;
        sHeight = sWidth / containerAspectRatio;
        sx = 0;
        sy = (imagem.naturalHeight - sHeight) / 2;
    }

    // Desenhar a imagem no canvas com o corte aplicado
    ctx.drawImage(imagem, sx, sy, sWidth, sHeight, 0, 0, containerWidth, containerHeight);
    
    // Converter a imagem cortada para Base64
    const imagemCortadaBase64 = canvas.toDataURL('image/jpeg');
    
    // Exibir a imagem recortada
    const preview = document.getElementById('preview');
    preview.src = imagemCortadaBase64;

    // Salvar a imagem recortada no input hidden para envio
    document.getElementById('fotoBase64').value = imagemCortadaBase64;
}

// Função para abrir a câmera e capturar a imagem
function abrirCamera() {
    let video = document.getElementById('video');
    let captureBtn = document.getElementById('captureBtn');
    let cancelBtn = document.getElementById('cancelBtn');

    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            // Exibir o vídeo e os botões de captura e cancelamento
            video.style.display = 'block';
            captureBtn.style.display = 'block';
            cancelBtn.style.display = 'block';
            video.srcObject = stream;

            // Esperar o vídeo ser carregado e pronto para exibir
            video.onloadedmetadata = function() {
                console.log('O vídeo foi carregado e está pronto para capturar.');
                video.play();
            };
        })
        .catch(function(err) {
            console.error("Erro ao acessar a câmera: ", err);
        });
}

function capturarFoto() {
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let context = canvas.getContext('2d');
    let fotoBase64 = document.getElementById('fotoBase64');
    let preview = document.getElementById('preview');
    let captureBtn = document.getElementById('captureBtn');

    // Certifique-se de que o vídeo está pronto para ser capturado
    if (video.readyState >= 2) {
        console.log('Capturando imagem...');

        // Ajusta o canvas ao tamanho do vídeo
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Captura o quadro do vídeo
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Converte a imagem do canvas para Base64
        let dataURL = canvas.toDataURL('image/jpeg');
        fotoBase64.value = dataURL;  // Salva a imagem no input hidden para envio

        // Exibe a imagem capturada
        preview.src = dataURL;
        preview.style.display = 'block';

        preview.onload = function() {
            cortarImagem(preview); // Função para cortar a imagem
        };

        // Fechar a câmera (parar o stream)
        let stream = video.srcObject;
        let tracks = stream.getTracks();
        tracks.forEach(function(track) {
            track.stop();  // Para todos os tracks de vídeo
        });

        // Ocultar o vídeo e os botões
        video.style.display = 'none';
        captureBtn.style.display = 'none';
        document.getElementById('cancelBtn').style.display = 'none';
    } else {
        console.log('O vídeo ainda não está pronto para captura, readyState:', video.readyState);
    }
}


// Função para cancelar a câmera
function cancelarCamera() {
    let video = document.getElementById('video');
    let captureBtn = document.getElementById('captureBtn');
    let cancelBtn = document.getElementById('cancelBtn');
    
    // Parar o stream de vídeo
    let stream = video.srcObject;
    let tracks = stream.getTracks();
    tracks.forEach(function(track) {
        track.stop();
    });

    video.srcObject = null;
    video.style.display = 'none';
    captureBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
}


</script>

<?php
require_once INC_PATH . '/footer.php';
?>