<?php
// Config
require_once realpath(dirname(__FILE__, 3)) . '/config/config.php';

session_start();

use Cfo\SisConsultas\lib\Labels;
use Cfo\SisConsultas\lib\Session;

// Verificar se é administrador
Session::init();
if (Session::get('grupo') != 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar labels.']);
    exit();
}

header('Content-Type: application/json');

$labels = new Labels();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list_labels':
            $result = $labels->selectLabels();
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'list_child_labels':
            $parent_id = $_GET['parent_id'] ?? null;
            if ($parent_id) {
                $result = $labels->getChildLabelsByParent($parent_id);
            } else {
                $result = $labels->getChildLabelsWithParentInfo();
            }
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'get_hierarchy':
            $result = $labels->getLabelsHierarchy();
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'get_label':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da label é obrigatório');
            }
            $result = $labels->getLabelById($id);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'get_child_label':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da child label é obrigatório');
            }
            $result = $labels->getChildLabelById($id);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'insert_label':
            $data = [
                'label_key' => $_POST['label_key'] ?? '',
                'label_value' => $_POST['label_value'] ?? '',
                'url' => $_POST['url'] ?? '',
                'class' => $_POST['class'] ?? '',
                'description' => $_POST['description'] ?? '',
                'disable' => isset($_POST['disable']) ? (int)$_POST['disable'] : 0
            ];

            if (empty($data['label_key']) || empty($data['label_value'])) {
                throw new Exception('Label Key e Label Value são obrigatórios');
            }

            $result = $labels->insertLabel($data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Label criada com sucesso!', 'id' => $result]);
            } else {
                throw new Exception('Erro ao criar label');
            }
            break;

        case 'update_label':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da label é obrigatório');
            }

            $data = [
                'label_key' => $_POST['label_key'] ?? '',
                'label_value' => $_POST['label_value'] ?? '',
                'url' => $_POST['url'] ?? '',
                'class' => $_POST['class'] ?? '',
                'description' => $_POST['description'] ?? '',
                'disable' => isset($_POST['disable']) ? (int)$_POST['disable'] : 0
            ];

            if (empty($data['label_key']) || empty($data['label_value'])) {
                throw new Exception('Label Key e Label Value são obrigatórios');
            }

            $result = $labels->updateLabel($id, $data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Label atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar label');
            }
            break;

        case 'delete_label':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da label é obrigatório');
            }

            $result = $labels->deleteLabel($id);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Label deletada com sucesso!']);
            } else {
                throw new Exception('Erro ao deletar label');
            }
            break;

        case 'insert_child_label':
            $data = [
                'nome' => $_POST['nome'] ?? '',
                'referencial' => $_POST['referencial'] ?? '',
                'grupo' => $_POST['grupo'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'disable' => isset($_POST['disable']) ? (int)$_POST['disable'] : 0,
                'fk_label' => $_POST['fk_label'] ?? null
            ];

            if (empty($data['nome']) || empty($data['referencial']) || !$data['fk_label']) {
                throw new Exception('Nome, Referencial e Label Pai são obrigatórios');
            }

            $result = $labels->insertChildLabel($data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Sub-label criada com sucesso!', 'id' => $result]);
            } else {
                throw new Exception('Erro ao criar sub-label');
            }
            break;

        case 'update_child_label':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da child label é obrigatório');
            }

            $data = [
                'nome' => $_POST['nome'] ?? '',
                'referencial' => $_POST['referencial'] ?? '',
                'grupo' => $_POST['grupo'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'disable' => isset($_POST['disable']) ? (int)$_POST['disable'] : 0,
                'fk_label' => $_POST['fk_label'] ?? null
            ];

            if (empty($data['nome']) || empty($data['referencial']) || !$data['fk_label']) {
                throw new Exception('Nome, Referencial e Label Pai são obrigatórios');
            }

            $result = $labels->updateChildLabel($id, $data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Sub-label atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar sub-label');
            }
            break;

        case 'delete_child_label':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new Exception('ID da child label é obrigatório');
            }

            $result = $labels->deleteChildLabel($id);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Sub-label deletada com sucesso!']);
            } else {
                throw new Exception('Erro ao deletar sub-label');
            }
            break;

        case 'update_order':
            $orders = json_decode($_POST['orders'] ?? '[]', true);
            if (empty($orders)) {
                throw new Exception('Dados de ordenação são obrigatórios');
            }

            $result = $labels->updateLabelsOrder($orders);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar ordem');
            }
            break;

        case 'update_child_order_simple':
            $orders = json_decode($_POST['orders'] ?? '[]', true);
            if (empty($orders)) {
                throw new Exception('Dados de ordenação são obrigatórios');
            }
            
            $result = $labels->updateChildLabelsOrderSimple($orders);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar ordem das sub-labels');
            }
            break;

        case 'update_child_order_grouped':
            $groupedOrders = json_decode($_POST['grouped_orders'] ?? '{}', true);
            if (empty($groupedOrders)) {
                throw new Exception('Dados de ordenação agrupada são obrigatórios');
            }
            
            $result = $labels->updateChildLabelsOrderGrouped($groupedOrders);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Ordem agrupada atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar ordem agrupada das sub-labels');
            }
            break;

        case 'update_child_order':
            $parentId = $_POST['parent_id'] ?? null;
            $orders = json_decode($_POST['orders'] ?? '[]', true);
            
            if (!$parentId) {
                throw new Exception('ID da label pai é obrigatório');
            }
            
            if (empty($orders)) {
                throw new Exception('Dados de ordenação são obrigatórios');
            }

            $result = $labels->updateChildLabelsOrder($parentId, $orders);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Ordem das sub-labels atualizada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar ordem das sub-labels');
            }
            break;

        case 'update_cache':
            $result = $labels->cacheLabelsRedis();
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cache atualizado com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar cache');
            }
            break;

        default:
            throw new Exception('Ação não reconhecida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 