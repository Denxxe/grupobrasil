<?php
require_once 'ModelBase.php';

class Vivienda extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'vivienda';
        $this->primaryKey = 'id_vivienda';
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

    public function createVivienda($data) {
        return $this->create($data);
    }

    public function updateVivienda($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteVivienda($id) {
        return $this->delete($id);
    }

    // Soft delete - marca como inactivo
    public function softDelete($id) {
        return $this->update($id, ['activo' => 0]);
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

    // Obtener vivienda con información de calle
    public function getAllWithCalle() {
        $sql = "SELECT v.*, c.nombre as nombre_calle, c.sector
                FROM " . $this->table . " v 
                LEFT JOIN calle c ON v.id_calle = c.id_calle 
                WHERE v.activo = 1 
                ORDER BY c.nombre ASC, v.numero ASC";
        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Obtiene viviendas filtradas por calles específicas
     * @param array $calleIds Array de IDs de calles
     * @return array Array de viviendas
     */
    public function getViviendasPorCalles(array $calleIds): array {
        if (empty($calleIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($calleIds), '?'));
        $sql = "SELECT v.*, c.nombre as nombre_calle, c.sector,
                       (SELECT COUNT(*) FROM habitante_vivienda hv 
                        WHERE hv.id_vivienda = v.id_vivienda) as total_habitantes
                FROM " . $this->table . " v 
                LEFT JOIN calle c ON v.id_calle = c.id_calle 
                WHERE v.activo = 1 AND v.id_calle IN ($placeholders)
                ORDER BY c.nombre ASC, v.numero ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getViviendasPorCalles: " . $this->conn->error);
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
     * Cuenta viviendas por calles específicas
     * @param array $calleIds Array de IDs de calles
     * @return int Total de viviendas
     */
    public function contarPorCalles(array $calleIds): int {
        if (empty($calleIds)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($calleIds), '?'));
        $sql = "SELECT COUNT(*) as total 
                FROM " . $this->table . " 
                WHERE activo = 1 AND id_calle IN ($placeholders)";
        
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
     * Obtiene viviendas de una calle específica
     * @param int $idCalle ID de la calle
     * @return array Array de viviendas
     */
    public function getViviendasPorCalle(int $idCalle): array {
        $sql = "SELECT id_vivienda, numero, tipo, estado
                FROM " . $this->table . " 
                WHERE activo = 1 AND id_calle = ?
                ORDER BY numero ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getViviendasPorCalle: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $idCalle);
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
     * Verifica si existe un número de vivienda en una calle específica.
     * @param string|int $numero
     * @param int $idCalle
     * @param int|null $excludeId id_vivienda a excluir (útil en update)
     * @return bool
     */
    public function existsNumeroEnCalle($numero, int $idCalle, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE activo = 1 AND numero = ? AND id_calle = ?";
        if ($excludeId) $sql .= " AND id_vivienda != ?";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar existsNumeroEnCalle: " . $this->conn->error);
            return false;
        }

        if ($excludeId) {
            $stmt->bind_param('sii', $numero, $idCalle, $excludeId);
        } else {
            $stmt->bind_param('si', $numero, $idCalle);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $exists = false;
        if ($result && $row = $result->fetch_assoc()) {
            $exists = ((int)($row['total'] ?? 0) > 0);
        }
        $stmt->close();
        return $exists;
    }
}