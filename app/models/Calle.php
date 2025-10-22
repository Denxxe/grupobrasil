<?php
// grupobrasil/app/models/Calle.php

require_once 'ModelBase.php';

class Calle extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'calle'; // Nombre de la tabla
        $this->primaryKey = 'id_calle'; // Clave primaria
    }

    /**
     * Obtiene todas las calles activas.
     * @return array
     */
    public function findAll(): array {
        $sql = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY nombre ASC";
        
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        return $data;
    }
    
    
    // Aquí puedes añadir otros métodos como findById(), create(), update(), etc.
    // Aunque, muchos se delegan a ModelBase.
}