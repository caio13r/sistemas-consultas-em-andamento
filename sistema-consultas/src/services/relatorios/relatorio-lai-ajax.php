<?php
// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Definir cabeçalho JSON imediatamente
header('Content-Type: application/json; charset=utf-8');

try {
    $inputPost = $_POST;

    if (!isset($inputPost['ajax_request']) || $inputPost['ajax_request'] != '1') {
        throw new Exception('Requisição inválida');
    }

    session_start();

    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        throw new Exception('Sessão expirada');
    }

    $userGrupo = $_SESSION['grupo'] ?? 0;
    $userSubgrupo = $_SESSION['subgrupo'] ?? 0;
    $isAdmin = ($userGrupo == 0) || ($userGrupo == 1) || ($userSubgrupo == 1);

    if (!$isAdmin) {
        throw new Exception('Acesso negado');
    }

    require_once realpath(__DIR__ . '/../../database/Database1.php');

    use Cfo\SisConsultas\database\Database1;

    $db = Database1::getInstance();
    $con = $db->getConnection();

    $action = $inputPost['action'] ?? '';

    switch ($action) {
        case 'get':
            $id = intval($inputPost['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $query = "SELECT id, uf, nome_autoridade_oficial_lai, email_autoridade_lai, telefone_autoridade_lai FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE id = ?";
            $stmt = $con->prepare($query);
            $stmt->execute([$id]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registro) {
                echo json_encode(['success' => true, 'registro' => $registro]);
            } else {
                throw new Exception('Registro não encontrado');
            }
            break;

        case 'update':
            $id = intval($inputPost['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $query = "UPDATE db_sistema_consultas.tbl_dados_lgpd_cros SET nome_autoridade_oficial_lai = ?, email_autoridade_lai = ?, telefone_autoridade_lai = ? WHERE id = ?";
            $stmt = $con->prepare($query);
            $result = $stmt->execute([
                $inputPost['nome_autoridade_oficial_lai'] ?? '',
                $inputPost['email_autoridade_lai'] ?? '',
                $inputPost['telefone_autoridade_lai'] ?? '',
                $id
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Registro atualizado com sucesso']);
            } else {
                throw new Exception('Erro ao atualizar registro');
            }
            break;

        default:
            throw new Exception('Ação não reconhecida');
    }
} catch (Exception $e) {
    // Garantir que o erro seja retornado como JSON
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>