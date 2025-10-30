<?php
require_once 'ModelBase.php';

class CargaFamiliar extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'carga_familiar';
        $this->primaryKey = 'id_carga';
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM " . $this->table . " WHERE activo = 1";
        $result = $this->conn->query($sql);
        $data = [];
        if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
        return $data;
    }

    public function getById($id) {
        return $this->find($id);
    }

    public function createCarga($data) {
        return $this->create($data);
    }

    public function updateCarga($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteCarga($id) {
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
     * Elimina todos los registros de carga_familiar donde el habitante es miembro
     * NOTA: NO elimina registros donde el habitante es jefe, para mantener la familia
     *
     * @param int $habitanteId El ID del habitante
     * @return bool True si la operación fue exitosa
     */
    public function deleteByHabitanteId(int $habitanteId): bool {
        // Solo eliminamos donde el habitante es miembro, no donde es jefe
        $sql = "DELETE FROM {$this->table} WHERE id_habitante = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar deleteByHabitanteId en CargaFamiliar: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $habitanteId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            error_log("[v0] Error al eliminar registros de carga_familiar: " . $this->conn->error);
        } else {
            error_log("[v0] Eliminados registros de carga_familiar para habitante ID: $habitanteId");
        }

        return true;
    }

    /**
     * Actualiza los registros donde el habitante es jefe, estableciendo id_jefe a NULL
     * Esto mantiene la familia pero sin jefe asignado
     *
     * @param int $habitanteId El ID del habitante que era jefe
     * @return bool True si la operación fue exitosa
     */
    public function updateJefeToNull(int $habitanteId): bool {
        $sql = "UPDATE {$this->table} SET id_jefe = NULL WHERE id_jefe = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar updateJefeToNull en CargaFamiliar: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $habitanteId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            error_log("[v0] Error al actualizar jefe a NULL: " . $this->conn->error);
        } else {
            error_log("[v0] Actualizado jefe a NULL para familias del habitante ID: $habitanteId");
        }

        return true;   
    }

    /**
     * Agrega un miembro a un jefe (usa create internamente)
     *
     * @param int $jefeId
     * @param int $habitanteId
     * @param string|null $parentesco
     * @return int|false ID creado o false
     */
    public function addMemberToJefe(int $jefeId, int $habitanteId, ?string $parentesco = null) {
        $data = [
            'id_habitante' => $habitanteId,
            'id_jefe' => $jefeId,
            'parentesco' => $parentesco,
            'activo' => 1
        ];
        return $this->create($data);
    }

    /**
     * Comprueba si un habitante ya está asignado a una carga familiar activa
     *
     * @param int $habitanteId
     * @return bool
     */
    public function isHabitanteInAnyCarga(int $habitanteId): bool {
        $sql = "SELECT id_carga FROM {$this->table} WHERE id_habitante = ? AND activo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar isHabitanteInAnyCarga: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param('i', $habitanteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();
        return $exists;
    }

     /**
     * Cuenta la cantidad de miembros a cargo de un jefe
     *
     * @param int $jefeId
     * @return int
     */
    public function countByJefe(int $jefeId): int {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE id_jefe = ? AND activo = 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar countByJefe: " . $this->conn->error);
            return 0;
        }
        $stmt->bind_param("i", $jefeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = 0;
        if ($result) {
            $row = $result->fetch_assoc();
            $total = (int)($row['total'] ?? 0);
        }
        $stmt->close();
        return $total;
    }

    /**
     * Devuelve los miembros (filas) cuyo id_jefe = $jefeId
     *
     * @param int $jefeId
     * @return array
     */
    public function getByJefeId(int $jefeId): array {
        $sql = "SELECT * FROM {$this->table} WHERE id_jefe = ? AND activo = 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar getByJefeId: " . $this->conn->error);
            return [];
        }
        $stmt->bind_param("i", $jefeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    /**
     * Obtiene la carga familiar de un jefe con información completa de las personas
     * @param int $jefeId ID del habitante que es jefe de familia
     * @return array Array con los miembros de la familia y sus datos personales
     */
    public function getCargaFamiliarConDatos(int $jefeId): array {
        $sql = "SELECT 
                    cf.id_carga,
                    cf.id_habitante,
                    cf.id_jefe,
                    cf.parentesco,
                    cf.fecha_registro,
                    h.id_persona,
                    p.cedula,
                    p.nombres,
                    p.apellidos,
                    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                    p.fecha_nacimiento,
                    p.sexo,
                    p.telefono,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
                FROM {$this->table} cf
                INNER JOIN habitante h ON cf.id_habitante = h.id_habitante
                INNER JOIN persona p ON h.id_persona = p.id_persona
                WHERE cf.id_jefe = ? AND cf.activo = 1
                ORDER BY p.fecha_nacimiento ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getCargaFamiliarConDatos: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $jefeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        
        return $data;
    }

    /**
     * Obtiene la carga familiar de un usuario (a través de su id_usuario)
     * Primero busca si el usuario es jefe de familia
     * @param int $idUsuario ID del usuario
     * @return array|false Array con los miembros o false si no es jefe de familia
     */
    public function getCargaFamiliarPorUsuario(int $idUsuario) {
        // Primero obtenemos el id_habitante del usuario
        $sql = "SELECT h.id_habitante, hv.es_jefe_familia
                FROM usuario u
                INNER JOIN habitante h ON u.id_persona = h.id_persona
                LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                WHERE u.id_usuario = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getCargaFamiliarPorUsuario: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $idHabitante = $row['id_habitante'];
            $esJefeFamilia = $row['es_jefe_familia'];
            $stmt->close();
            
            // Solo si es jefe de familia, obtener su carga familiar
            if ($esJefeFamilia == 1) {
                return $this->getCargaFamiliarConDatos($idHabitante);
            }
        }
        
        $stmt->close();
        return false;
    }
}
