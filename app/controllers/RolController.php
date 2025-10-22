<?php
require_once __DIR__ . '/../models/Rol.php';

class RolController {

    public function index() {
        $rolModel = new Rol();
        $roles = $rolModel->getAll();
        header('Content-Type: application/json');
        echo json_encode($roles);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de rol no proporcionado.']);
            return;
        }
        $rolModel = new Rol();
        $rol = $rolModel->getById((int)$id);
        header('Content-Type: application/json');
        if ($rol) {
            echo json_encode($rol);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Rol no encontrado.']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El campo nombre es requerido.']);
            return;
        }

        $rolModel = new Rol();
        $newId = $rolModel->createRol($data);

        header('Content-Type: application/json');
        if ($newId) {
            http_response_code(201);
            echo json_encode(['id_rol' => $newId, 'message' => 'Rol creado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear el rol.']);
        }
    }

    public function update($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de rol no proporcionado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        
        $rolModel = new Rol();
        $success = $rolModel->updateRol((int)$id, $data);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Rol actualizado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el rol.']);
        }
    }

    public function destroy($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de rol no proporcionado.']);
            return;
        }
        
        $rolModel = new Rol();
        $success = $rolModel->deleteRol((int)$id);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['message' => 'Rol eliminado (desactivado) exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el rol.']);
        }
    }
}

// --- Router Básico ---
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new RolController();

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