<?php
require_once __DIR__ . '/../models/LiderComunal.php';

class LiderComunalController {

    public function index() {
        $liderModel = new LiderComunal();
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
        $liderModel = new LiderComunal();
        $lider = $liderModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($lider) {
            echo json_encode($lider);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Líder comunal no encontrado.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_habitante']) || empty($data['fecha_inicio'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos id_habitante y fecha_inicio son requeridos.']);
            return;
        }

        $liderModel = new LiderComunal();
        $newId = $liderModel->createLider($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_habitante' => $newId, 'message' => 'Líder comunal creado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear el líder comunal.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante (líder) no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $liderModel = new LiderComunal();
        $success = $liderModel->updateLider((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Líder comunal actualizado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el líder comunal.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante (líder) no proporcionado.']);
            return;
        }
        
        $liderModel = new LiderComunal();
        $success = $liderModel->deleteLider((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Líder comunal eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el líder comunal.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new LiderComunalController();

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