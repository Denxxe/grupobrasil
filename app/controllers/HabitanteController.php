<?php
require_once __DIR__ . '/../models/Habitante.php';

class HabitanteController {

    public function index() {
        $habitanteModel = new Habitante();
        $habitantes = $habitanteModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($habitantes);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante no proporcionado.']);
            return;
        }
        $habitanteModel = new Habitante();
        $habitante = $habitanteModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($habitante) {
            echo json_encode($habitante);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Habitante no encontrado.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_persona']) || empty($data['fecha_ingreso'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos id_persona y fecha_ingreso son requeridos.']);
            return;
        }

        $habitanteModel = new Habitante();
        $newId = $habitanteModel->createHabitante($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_habitante' => $newId, 'message' => 'Habitante creado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear el habitante.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $habitanteModel = new Habitante();
        $success = $habitanteModel->updateHabitante((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Habitante actualizado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el habitante.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de habitante no proporcionado.']);
            return;
        }
        
        $habitanteModel = new Habitante();
        $success = $habitanteModel->deleteHabitante((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Habitante eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el habitante.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new HabitanteController();

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