<?php
require_once 'ModelBase.php';

class ViviendaDetalle extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'vivienda_detalle';
        $this->primaryKey = 'id_detalle';
    }

    public function getByViviendaId(int $idVivienda) {
        $sql = "SELECT * FROM {$this->table} WHERE id_vivienda = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('i', $idVivienda);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res && $res->num_rows ? $res->fetch_assoc() : false;
        $stmt->close();
        return $data;
    }

    /**
     * Crea o actualiza detalle por id_vivienda
     */
    public function createOrUpdateByVivienda(int $idVivienda, array $data) {
        // Verificar existencia
        $existing = $this->getByViviendaId($idVivienda);
        if ($existing) {
            return $this->update((int)$existing[$this->primaryKey], $data);
        }
        $data['id_vivienda'] = $idVivienda;
        return $this->create($data);
    }
}
