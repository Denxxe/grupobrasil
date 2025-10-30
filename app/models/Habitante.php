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
        try {
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
            $res1 = $liderCalle->deleteByHabitanteId($id);
            if ($res1 === false) {
                $msg = "[v0] liderCalle->deleteByHabitanteId returned false; DB error: " . ($liderCalle->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            // 2. Eliminar registros de liderazgo comunal
            error_log("[v0] Eliminando registros de lider_comunal...");
            $res2 = $liderComunal->deleteByHabitanteId($id);
            if ($res2 === false) {
                $msg = "[v0] liderComunal->deleteByHabitanteId returned false; DB error: " . ($liderComunal->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            // 3. Actualizar familias donde era jefe (establecer jefe a NULL)
            error_log("[v0] Actualizando familias donde era jefe...");
            $res3 = $cargaFamiliar->updateJefeToNull($id);
            if ($res3 === false) {
                $msg = "[v0] cargaFamiliar->updateJefeToNull returned false; DB error: " . ($cargaFamiliar->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            // 4. Eliminar registros donde era miembro de familia
            error_log("[v0] Eliminando registros de carga_familiar...");
            $res4 = $cargaFamiliar->deleteByHabitanteId($id);
            if ($res4 === false) {
                $msg = "[v0] cargaFamiliar->deleteByHabitanteId returned false; DB error: " . ($cargaFamiliar->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            // 5. Eliminar asociaciones de vivienda
            error_log("[v0] Eliminando asociaciones de habitante_vivienda...");
            $res5 = $habitanteVivienda->deleteByHabitanteId($id);
            if ($res5 === false) {
                $msg = "[v0] habitanteVivienda->deleteByHabitanteId returned false; DB error: " . ($habitanteVivienda->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            // 6. Verificar si tiene usuario y eliminarlo
            $usuarioData = $usuario->findByPersonId($idPersona);
            if ($usuarioData) {
                error_log("[v0] Habitante tiene usuario, eliminando usuario ID: " . $usuarioData['id_usuario']);
                $uDel = $usuario->delete($usuarioData['id_usuario']);
                if ($uDel === false) {
                    $msg = "[v0] usuario->delete returned false; DB error: " . ($usuario->getConnection()->error ?? 'unknown');
                    error_log($msg);
                    @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
                }
            } else {
                error_log("[v0] Habitante no tiene usuario asociado");
            }

            // 7. Finalmente, eliminar el habitante
            error_log("[v0] Eliminando registro de habitante...");
            $success = $this->delete($id);

            if ($success) {
                error_log("[v0] Habitante eliminado exitosamente con todos sus registros relacionados");
            } else {
                $msg = "[v0] Error al eliminar el habitante; DB error: " . ($this->getConnection()->error ?? 'unknown');
                error_log($msg);
                @file_put_contents(__DIR__ . '/../../storage/delete_debug.log', "[".date('Y-m-d H:i:s')."] " . $msg . "\n", FILE_APPEND | LOCK_EX);
            }

            return $success;
        } catch (\Throwable $t) {
            error_log("[v0] Exception in deleteHabitanteWithCascade: " . $t->getMessage());
            error_log($t->getTraceAsString());
            return false;
        }
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
    public function getHabitantesPorCalles(array $calleIds, array $filters = []): array {
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
                WHERE p.id_calle IN ($placeholders)
                ";

        $where = [];
        $params = [];
        $types = '';

        // filtro por activo (habitante)
        if (isset($filters['activo']) && $filters['activo'] !== 'all') {
            $where[] = 'h.activo = ?';
            $params[] = (int)$filters['activo'];
            $types .= 'i';
        } else {
            // Por defecto mostrar solo activos
            $where[] = 'h.activo = 1';
        }

        // filtro de búsqueda por cédula/nombre/apellidos
        if (!empty($filters['search'])) {
            $where[] = "(p.cedula LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ?)";
            $q = '%' . $filters['search'] . '%';
            $params[] = $q; $params[] = $q; $params[] = $q;
            $types .= 'sss';
        }

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY c.nombre ASC, COALESCE(v.numero, 999) ASC, p.nombres ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getHabitantesPorCalles: " . $this->conn->error);
            return [];
        }

        // Bind dinámico: primero los ids de calles, luego params adicionales
        $allParams = array_merge($calleIds, $params);
        $typesAll = str_repeat('i', count($calleIds)) . $types;
        if (!empty($allParams)) {
            $stmt->bind_param($typesAll, ...$allParams);
        }
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
     * Obtiene los habitantes asignados a una vivienda específica con datos de persona
     * @param int $idVivienda
     * @return array
     */
    public function getByViviendaId(int $idVivienda): array {
        $sql = "SELECT h.id_habitante, h.id_persona, p.cedula, p.nombres, p.apellidos, CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo, p.fecha_nacimiento, p.telefono, COALESCE(hv.es_jefe_familia,0) as es_jefe_familia
                FROM {$this->table} h
                INNER JOIN persona p ON h.id_persona = p.id_persona
                INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                WHERE hv.id_vivienda = ? AND h.activo = 1
                ORDER BY hv.es_jefe_familia DESC, p.apellidos ASC, p.nombres ASC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getByViviendaId: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $idVivienda);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $data[] = $row;
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
                WHERE v.id_calle IN ($placeholders)";

        // Por defecto contamos solo activos
        $sql .= " AND h.activo = 1";

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

    /**
     * Busca habitantes por cédula o nombre/apellidos (limita resultados)
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchByQuery(string $query, int $limit = 20): array {
        $q = '%' . $query . '%';
    // Excluir habitantes que ya pertenecen a una carga_familiar activa
    $sql = "SELECT h.id_habitante, h.id_persona, p.cedula, p.nombres, p.apellidos, CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo, p.fecha_nacimiento, p.telefono
        FROM {$this->table} h
        INNER JOIN persona p ON h.id_persona = p.id_persona
        LEFT JOIN carga_familiar cf ON h.id_habitante = cf.id_habitante AND cf.activo = 1
        WHERE h.activo = 1 AND cf.id_carga IS NULL
          AND (p.cedula LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ?)
        ORDER BY p.apellidos ASC, p.nombres ASC
        LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar searchByQuery: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param('sssi', $q, $q, $q, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $data[] = $row;
        }
        $stmt->close();
        return $data;
    }
}
