<?php
require_once 'ModelBase.php';

class HabitanteVivienda extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'habitante_vivienda';
        // Esta tabla usa clave compuesta, no definimos primaryKey
    }

    /**
     * Elimina todas las asociaciones de vivienda para un habitante específico
     *
     * @param int $habitanteId El ID del habitante
     * @return bool True si la operación fue exitosa
     */
    public function deleteByHabitanteId(int $habitanteId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id_habitante = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar deleteByHabitanteId en HabitanteVivienda: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $habitanteId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            error_log("[v0] Error al eliminar registros de habitante_vivienda: " . $this->conn->error);
        } else {
            error_log("[v0] Eliminados registros de habitante_vivienda para habitante ID: $habitanteId");
        }

        return true;
    }

    /**
     * Comprueba si un habitante es jefe de familia (es_jefe_familia = 1)
     * @param int $id_habitante
     * @return bool
     */
    public function isJefeFamilia(int $id_habitante): bool {
        $sql = "SELECT es_jefe_familia FROM {$this->table} WHERE id_habitante = ? AND es_jefe_familia = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('i', $id_habitante);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        return $ok;
    }
}
