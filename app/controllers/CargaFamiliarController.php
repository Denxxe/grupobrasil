<?php

require_once __DIR__ . '/../models/CargaFamiliar.php';

class CargaFamiliarController {
    protected $model;
    public function __construct() {
        $this->model = new CargaFamiliar();
    }

    public function index() {
        header('Content-Type: application/json');
        echo json_encode($this->model->getAll());
    }

    public function show($id) {
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID no proporcionado']); return; }
        header('Content-Type: application/json');
        $row = $this->model->getById((int)$id);
        if ($row) echo json_encode($row);
        else { http_response_code(404); echo json_encode(['error'=>'No encontrado']); }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_habitante']) || empty($data['id_jefe'])) {
            http_response_code(400); echo json_encode(['error'=>'id_habitante e id_jefe requeridos']); return;
        }
        $newId = $this->model->createCarga($data);
        header('Content-Type: application/json');
        if ($newId) { http_response_code(201); echo json_encode(['id_carga'=>$newId]); }
        else { http_response_code(500); echo json_encode(['error'=>'No se pudo crear']); }
    }

    public function update($id) {
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID no proporcionado']); return; }
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $this->model->updateCarga((int)$id, $data);
        header('Content-Type: application/json');
        if ($success) echo json_encode(['message'=>'Actualizado']);
        else { http_response_code(500); echo json_encode(['error'=>'Error al actualizar']); }
    }

    public function destroy($id) {
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID no proporcionado']); return; }
        $success = $this->model->deleteCarga((int)$id);
        header('Content-Type: application/json');
        if ($success) echo json_encode(['message'=>'Eliminado']);
        else { http_response_code(500); echo json_encode(['error'=>'Error al eliminar']); }
    }

    // Lista miembros por id de jefe
    public function membersByJefe($jefeId) {
        if (!$jefeId) { http_response_code(400); echo json_encode(['error'=>'ID de jefe requerido']); return; }
        header('Content-Type: application/json');
        echo json_encode($this->model->getByJefeId((int)$jefeId));
    }

    // Cuenta miembros por id de jefe
    public function countByJefe($jefeId) {
        if (!$jefeId) { http_response_code(400); echo json_encode(['error'=>'ID de jefe requerido']); return; }
        header('Content-Type: application/json');
        echo json_encode(['total' => $this->model->countByJefe((int)$jefeId)]);
    }
}

// Router básico (ejemplo: ?action=index | show | store | update | destroy | membersByJefe | countByJefe & id)
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$controller = new CargaFamiliarController();

switch ($action) {
    case 'index': $controller->index(); break;
    case 'show': $controller->show($id); break;
    case 'store': $controller->store(); break;
    case 'update': $controller->update($id); break;
    case 'destroy': $controller->destroy($id); break;
    case 'membersByJefe': $controller->membersByJefe($id); break;
    case 'countByJefe': $controller->countByJefe($id); break;
    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error'=>'Acción no válida']);
        break;
}
