<?php
require_once 'ModelBase.php';

class TipoBeneficio extends ModelBase {
    protected $table = 'tipos_beneficio';
    protected $primaryKey = 'id_tipo_beneficio';

    public function __construct() {
        parent::__construct();
    }

    public function findAll(): array {
        $sql = "SELECT id_tipo_beneficio, nombre, descripcion FROM {$this->table} ORDER BY nombre ASC";
        $result = $this->conn->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function findById(int $id) {
        $stmt = $this->conn->prepare("SELECT id_tipo_beneficio, nombre, descripcion FROM {$this->table} WHERE id_tipo_beneficio = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) { $stmt->close(); return $row; }
        $stmt->close();
        return false;
    }
}
