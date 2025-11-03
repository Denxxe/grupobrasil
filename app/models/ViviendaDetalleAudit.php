<?php
require_once 'ModelBase.php';

class ViviendaDetalleAudit extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'vivienda_detalle_audit';
        $this->primaryKey = 'id_audit';
    }

    /**
     * Inserta un registro de auditoría
     * @param array $data keys: id_vivienda, id_usuario, cambios (JSON/string)
     * @return int|false id insertado o false
     */
    public function createAudit(array $data) {
        $sql = "INSERT INTO {$this->table} (id_vivienda, id_usuario, cambios) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar createAudit: " . $this->conn->error);
            return false;
        }
        $idV = $data['id_vivienda'] ?? null;
        $idU = $data['id_usuario'] ?? null;
        $camb = $data['cambios'] ?? '';
        $stmt->bind_param('iis', $idV, $idU, $camb);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("Error al ejecutar createAudit: " . $this->conn->error);
            $stmt->close();
            return false;
        }
        $insertId = $stmt->insert_id;
        $stmt->close();
        return $insertId;
    }
}
