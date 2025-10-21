<?php
require_once 'ModelBase.php';

class LiderComunal extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'lider_comunal';
        $this->primaryKey = 'id_habitante';
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

    public function createLider($data) {
        return $this->create($data);
    }

    public function updateLider($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteLider($id) {
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
     * Elimina todos los registros de lider_comunal para un habitante específico
     *
     * @param int $habitanteId El ID del habitante
     * @return bool True si la operación fue exitosa
     */
    public function deleteByHabitanteId(int $habitanteId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id_habitante = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("[v0] Error al preparar deleteByHabitanteId en LiderComunal: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $habitanteId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            error_log("[v0] Error al eliminar registros de lider_comunal: " . $this->conn->error);
        } else {
            error_log("[v0] Eliminados registros de lider_comunal para habitante ID: $habitanteId");
        }

        return true;
    }
}
