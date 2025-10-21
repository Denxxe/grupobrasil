<?php
require_once __DIR__ . '/../models/Vivienda.php';

class ViviendaController {

    public function index() {
        $viviendaModel = new Vivienda();
        $viviendas = $viviendaModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($viviendas);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de vivienda no proporcionado.']);
            return;
        }
        $viviendaModel = new Vivienda();
        $vivienda = $viviendaModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($vivienda) {
            echo json_encode($vivienda);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Vivienda no encontrada.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['direccion']) || empty($data['numero']) || empty($data['tipo']) || empty($data['sector'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos direccion, numero, tipo y sector son requeridos.']);
            return;
        }

        $viviendaModel = new Vivienda();
        $newId = $viviendaModel->createVivienda($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_vivienda' => $newId, 'message' => 'Vivienda creada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear la vivienda.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de vivienda no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $viviendaModel = new Vivienda();
        $success = $viviendaModel->updateVivienda((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Vivienda actualizada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la vivienda.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de vivienda no proporcionado.']);
            return;
        }
        
        $viviendaModel = new Vivienda();
        $success = $viviendaModel->deleteVivienda((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Vivienda eliminada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la vivienda.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new ViviendaController();

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