<?php
require_once 'ModelBase.php';

class PagosPeriodos extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'pagos_periodos';
        $this->primaryKey = 'id_periodo';
    }

    public function createPeriodo(array $data) {
        // Campos esperados: nombre_periodo, fecha_inicio, fecha_limite, id_tipo_beneficio, monto, instrucciones_pago, creado_por
        return $this->create($data);
    }

    public function getActivos() {
        $sql = "SELECT pp.*, tb.nombre AS nombre_beneficio FROM {$this->table} pp LEFT JOIN tipos_beneficio tb ON pp.id_tipo_beneficio = tb.id_tipo_beneficio WHERE pp.estado = 'activo' ORDER BY pp.fecha_inicio DESC";
        $result = $this->conn->query($sql);
        if ($result) return $result->fetch_all(MYSQLI_ASSOC);
        return [];
    }

    public function getHistorial() {
        $sql = "SELECT pp.*, tb.nombre AS nombre_beneficio FROM {$this->table} pp LEFT JOIN tipos_beneficio tb ON pp.id_tipo_beneficio = tb.id_tipo_beneficio WHERE pp.estado <> 'activo' ORDER BY pp.fecha_limite DESC";
        $result = $this->conn->query($sql);
        if ($result) return $result->fetch_all(MYSQLI_ASSOC);
        return [];
    }

    public function getById(int $id) {
        $stmt = $this->conn->prepare("SELECT pp.*, tb.nombre AS nombre_beneficio FROM {$this->table} pp LEFT JOIN tipos_beneficio tb ON pp.id_tipo_beneficio = tb.id_tipo_beneficio WHERE pp.id_periodo = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        $stmt->close();
        return false;
    }

    public function closePeriodo(int $id) {
        return $this->update($id, ['estado' => 'cerrado']);
    }
}
