<?php
// grupobrasil/app/controllers/LiderCalleController.php
require_once __DIR__ . '/../models/LiderCalle.php';
require_once __DIR__ . '/AppController.php';

class LiderCalleController extends AppController {
    
    private $liderCalleModel;

    public function __construct() {
        parent::__construct();
        $this->liderCalleModel = new LiderCalle();
    }

    /**
     * Obtener todos los líderes de calle
     */
    public function index() {
        header('Content-Type: application/json');
        
        try {
            $lideres = $this->liderCalleModel->getAll();
            echo json_encode($lideres);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener líderes: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Obtener un líder específico por ID
     */
    public function show($id = null) {
        header('Content-Type: application/json');
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de líder no proporcionado']);
            exit;
        }

        try {
            $lider = $this->liderCalleModel->getById((int)$id);
            
            if ($lider) {
                echo json_encode($lider);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Líder de calle no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener líder: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Crear un nuevo líder de calle
     */
    public function store() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id_habitante']) || empty($data['id_calle'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Los campos id_habitante e id_calle son requeridos']);
            exit;
        }

        try {
            $newId = $this->liderCalleModel->create($data);

            if ($newId) {
                http_response_code(201);
                echo json_encode([
                    'id' => $newId, 
                    'message' => 'Líder de calle creado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el líder de calle']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear líder: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Actualizar un líder de calle existente
     */
    public function update($id = null) {
        header('Content-Type: application/json');
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de líder no proporcionado']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $success = $this->liderCalleModel->update((int)$id, $data);

            if ($success) {
                echo json_encode(['message' => 'Líder de calle actualizado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el líder de calle']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar líder: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Eliminar (soft delete) un líder de calle
     */
    public function destroy($id = null) {
        header('Content-Type: application/json');
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de líder no proporcionado']);
            exit;
        }
        
        try {
            $success = $this->liderCalleModel->delete((int)$id);

            if ($success) {
                echo json_encode(['message' => 'Líder de calle eliminado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el líder de calle']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar líder: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Obtener líderes por calle específica
     */
    public function getByCalle($idCalle = null) {
        header('Content-Type: application/json');
        
        if (!$idCalle) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de calle no proporcionado']);
            exit;
        }

        try {
            $lideres = $this->liderCalleModel->getCallesIdsPorUsuario((int)$idCalle);
            echo json_encode($lideres);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener líderes: ' . $e->getMessage()]);
        }
        exit;
    }
}