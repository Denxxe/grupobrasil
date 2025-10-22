<?php
require_once __DIR__ . '/../models/LiderCalle.php';

class LiderCalleController {

    public function index() {
        $liderModel = new LiderCalle();
        $lideres = $liderModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($lideres);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante (líder) no proporcionado.']);
            return;
        }
        $liderModel = new LiderCalle();
        $lider = $liderModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($lider) {
            echo json_encode($lider);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Líder de calle no encontrado.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_habitante']) || empty($data['sector']) || empty($data['fecha_designacion'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos id_habitante, sector y fecha_designacion son requeridos.']);
            return;
        }

        $liderModel = new LiderCalle();
        $newId = $liderModel->createLiderCalle($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_habitante' => $newId, 'message' => 'Líder de calle creado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear el líder de calle.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante (líder) no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $liderModel = new LiderCalle();
        $success = $liderModel->updateLiderCalle((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Líder de calle actualizado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el líder de calle.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante (líder) no proporcionado.']);
            return;
        }
        
        $liderModel = new LiderCalle();
        $success = $liderModel->deleteLiderCalle((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Líder de calle eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el líder de calle.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new LiderCalleController();

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'show':
        $controller->show($id);
        break;
    case 'store':
        $controller->store();
        break;
    case 'update':
        $controller->update($id);
        break;
    case 'destroy':
        $controller->destroy($id);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no válida.']);
        break;
}