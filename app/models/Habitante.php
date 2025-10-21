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
}