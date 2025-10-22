<?php

require_once __DIR__ . '/../models/Role.php';

class RolController {

    public function index() {
        // Asumiendo que el modelo está bien, busca la clase 'Role'
        $rolModel = new Role();
        $roles = $rolModel->findAll(); // Cambiado de getAll() a findAll() para consistencia
        header('Content-Type: application/json');
        echo json_encode($roles);
    }

    public function show($id) {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de rol no proporcionado.']);
            return;
        }
        $rolModel = new Role();
        // Asumiendo que getById existe en ModelBase y se hereda
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
        
        // 1. Extracción de argumentos para que coincidan con createRol(string $nombre, string $descripcion)
        $nombre = $data['nombre'] ?? null;
        $descripcion = $data['descripcion'] ?? ''; // La descripción puede estar vacía, pero nombre es obligatorio.

        if (empty($nombre)) {
            http_response_code(400);
            echo json_encode(['error' => 'El campo nombre es requerido.']);
            return;
        }

        $rolModel = new Role();
        // 2. CORRECCIÓN: Pasando dos argumentos por separado
        $success = $rolModel->createRol($nombre, $descripcion);

        header('Content-Type: application/json');
        if ($success) {
            // El método createRol ahora devuelve un booleano (true/false)
            http_response_code(201);
            echo json_encode(['message' => 'Rol creado exitosamente.']);
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

        // Extracción de argumentos
        $nombre = $data['nombre'] ?? null;
        $descripcion = $data['descripcion'] ?? '';

        if (empty($nombre)) {
             http_response_code(400);
            echo json_encode(['error' => 'El campo nombre es requerido para actualizar.']);
            return;
        }
        
        $rolModel = new Role();
        // 3. CORRECCIÓN: Pasando tres argumentos por separado
        $success = $rolModel->updateRol((int)$id, $nombre, $descripcion);

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
        
        $rolModel = new Role();
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

if (!class_exists('RolController')) {

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
}
