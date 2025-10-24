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

    /**
     * Elimina un habitante y todos sus registros relacionados en cascada
     * Si el habitante es líder, también elimina su cuenta de usuario
     *
     * @param int $id El ID del habitante a eliminar
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteHabitanteWithCascade(int $id): bool {
        error_log("[v0] Iniciando eliminación en cascada para habitante ID: $id");

        // Primero obtenemos el habitante para verificar si tiene usuario
        $habitante = $this->getById($id);
        if (!$habitante) {
            error_log("[v0] Habitante no encontrado con ID: $id");
            return false;
        }

        $idPersona = $habitante['id_persona'];
        error_log("[v0] Habitante encontrado, id_persona: $idPersona");

        // Cargar modelos necesarios
        require_once __DIR__ . '/LiderCalle.php';
        require_once __DIR__ . '/LiderComunal.php';
        require_once __DIR__ . '/CargaFamiliar.php';
        require_once __DIR__ . '/HabitanteVivienda.php';
        require_once __DIR__ . '/Usuario.php';

        $liderCalle = new LiderCalle();
        $liderComunal = new LiderComunal();
        $cargaFamiliar = new CargaFamiliar();
        $habitanteVivienda = new HabitanteVivienda();
        $usuario = new Usuario();

        // 1. Eliminar asignaciones de liderazgo de calle
        error_log("[v0] Eliminando asignaciones de lider_calle...");
        $liderCalle->deleteByHabitanteId($id);

        // 2. Eliminar registros de liderazgo comunal
        error_log("[v0] Eliminando registros de lider_comunal...");
        $liderComunal->deleteByHabitanteId($id);

        // 3. Actualizar familias donde era jefe (establecer jefe a NULL)
        error_log("[v0] Actualizando familias donde era jefe...");
        $cargaFamiliar->updateJefeToNull($id);

        // 4. Eliminar registros donde era miembro de familia
        error_log("[v0] Eliminando registros de carga_familiar...");
        $cargaFamiliar->deleteByHabitanteId($id);

        // 5. Eliminar asociaciones de vivienda
        error_log("[v0] Eliminando asociaciones de habitante_vivienda...");
        $habitanteVivienda->deleteByHabitanteId($id);

        // 6. Verificar si tiene usuario y eliminarlo
        $usuarioData = $usuario->findByPersonId($idPersona);
        if ($usuarioData) {
            error_log("[v0] Habitante tiene usuario, eliminando usuario ID: " . $usuarioData['id_usuario']);
            $usuario->delete($usuarioData['id_usuario']);
        } else {
            error_log("[v0] Habitante no tiene usuario asociado");
        }

        // 7. Finalmente, eliminar el habitante
        error_log("[v0] Eliminando registro de habitante...");
        $success = $this->delete($id);

        if ($success) {
            error_log("[v0] Habitante eliminado exitosamente con todos sus registros relacionados");
        } else {
            error_log("[v0] Error al eliminar el habitante");
        }

        return $success;
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

    /**
     * Obtiene habitantes filtrados por calles específicas
     * @param array $calleIds Array de IDs de calles
     * @return array Array de habitantes con sus datos personales
     */
    public function getHabitantesPorCalles(array $calleIds): array {
        if (empty($calleIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($calleIds), '?'));
        $sql = "SELECT DISTINCT
                    h.id_habitante,
                    h.id_persona,
                    h.fecha_ingreso,
                    h.condicion,
                    p.cedula,
                    p.nombres,
                    p.apellidos,
                    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                    p.fecha_nacimiento,
                    p.sexo,
                    p.telefono,
                    p.correo,
                    v.numero as numero_vivienda,
                    c.nombre as nombre_calle,
                    COALESCE(hv.es_jefe_familia, 0) as es_jefe_familia,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
                FROM {$this->table} h
                INNER JOIN persona p ON h.id_persona = p.id_persona
                INNER JOIN calle c ON p.id_calle = c.id_calle
                LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                WHERE h.activo = 1 AND p.id_calle IN ($placeholders)
                ORDER BY c.nombre ASC, COALESCE(v.numero, 999) ASC, p.nombres ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getHabitantesPorCalles: " . $this->conn->error);
            return [];
        }
        
        $types = str_repeat('i', count($calleIds));
        $stmt->bind_param($types, ...$calleIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        $stmt->close();
        return $data;
    }

    /**
     * Cuenta habitantes por calles específicas
     * @param array $calleIds Array de IDs de calles
     * @return int Total de habitantes
     */
    public function contarPorCalles(array $calleIds): int {
        if (empty($calleIds)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($calleIds), '?'));
        $sql = "SELECT COUNT(DISTINCT h.id_habitante) as total
                FROM {$this->table} h
                INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                INNER JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                WHERE h.activo = 1 AND v.id_calle IN ($placeholders)";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar contarPorCalles: " . $this->conn->error);
            return 0;
        }
        
        $types = str_repeat('i', count($calleIds));
        $stmt->bind_param($types, ...$calleIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $total = (int)$row['total'];
        }
        
        $stmt->close();
        return $total;
    }
}
