<?php

require_once 'ModelBase.php';

class LiderCalle extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'lider_calle';
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

    public function createLiderCalle($data) {
        return $this->create($data);
    }

    public function updateLiderCalle($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteLiderCalle($id) {
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