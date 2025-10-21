<?php
require_once __DIR__ . '/../models/Persona.php';

class PersonaController {

    public function index() {
        $personaModel = new Persona();
        $personas = $personaModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($personas);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de persona no proporcionado.']);
            return;
        }
        $personaModel = new Persona();
        $persona = $personaModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($persona) {
            echo json_encode($persona);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Persona no encontrada.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombres']) || empty($data['apellidos']) || empty($data['genero'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos nombres, apellidos y genero son requeridos.']);
            return;
        }

        $personaModel = new Persona();
        $newId = $personaModel->createPersona($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_persona' => $newId, 'message' => 'Persona creada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear la persona.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de persona no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $personaModel = new Persona();
        $success = $personaModel->updatePersona((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Persona actualizada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la persona.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de persona no proporcionado.']);
            return;
        }
        
        $personaModel = new Persona();
        $success = $personaModel->deletePersona((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Persona eliminada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la persona.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new PersonaController();

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