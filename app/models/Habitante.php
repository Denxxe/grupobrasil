<?php
require_once 'ModelBase.php';

class Habitante extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'habitante';
        $this->primaryKey = 'id_habitante';
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM " . $this->table . " WHERE activo = 1";
        $params = [];
        $types = "";

        if (!empty($filters['condicion'])) {
            $sql .= " AND condicion = ?";
            $params[] = $filters['condicion'];
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return [];
        if (!empty($params)) {
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $k => $v) $refs[$k] = &$bind_names[$k];
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
        $stmt->close();
        return $data;
    }

    public function getById($id) {
        return $this->find($id);
    }

    public function createHabitante($data) {
        return $this->create($data);
    }

    public function updateHabitante($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteHabitante($id) {
        return $this->delete($id);
    }

    public function contar($filters = []) {
        $sql = "SELECT COUNT(*) AS total FROM " . $this->table . " WHERE activo = 1";
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        }
        return 0;
    }

    /**
     * Busca un habitante por su id_persona
     * // Added method to find habitante by persona ID
     *
     * @param int $personaId El ID de la persona
     * @return array|null Los datos del habitante o null si no existe
     */
    public function findByPersonaId(int $personaId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id_persona = ? AND activo = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar findByPersonaId: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("i", $personaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $habitante = null;
        if ($result && $result->num_rows > 0) {
            $habitante = $result->fetch_assoc();
        }

        $stmt->close();
        return $habitante;
    }

    /**
     * Crea un habitante desde una persona existente, o devuelve el ID si ya existe
     * // Added method to create habitante from persona or return existing ID
     *
     * @param int $personaId El ID de la persona
     * @return int|null El ID del habitante creado o existente, null en caso de error
     */
    public function createFromPersona(int $personaId): ?int {
        // Primero verificar si ya existe un habitante para esta persona
        $existingHabitante = $this->findByPersonaId($personaId);
        
        if ($existingHabitante) {
            error_log("[v0] Habitante already exists for persona $personaId, returning existing ID: " . $existingHabitante['id_habitante']);
            return (int)$existingHabitante['id_habitante'];
        }

        // Si no existe, crear uno nuevo
        $habitanteData = [
            'id_persona' => $personaId,
            'fecha_ingreso' => date('Y-m-d'),
            'condicion' => 'Residente',
            'activo' => 1
        ];

        error_log("[v0] Creating new habitante for persona $personaId");
        $habitanteId = $this->create($habitanteData);
        
        if ($habitanteId) {
            error_log("[v0] Successfully created habitante with ID: $habitanteId");
        } else {
            error_log("[v0] Failed to create habitante for persona $personaId");
        }

        return $habitanteId;
    }
}
