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
    // Orden numérico cuando el nombre contiene números (p.ej. "Vereda 2", "10")
    // Usamos REGEXP_REPLACE para extraer dígitos y ordenar por su valor numérico, con fallback al nombre completo.
    $sql = "SELECT * FROM " . $this->table . " WHERE activo = 1 
        ORDER BY 
          (CASE WHEN nombre RLIKE '[0-9]' THEN CAST(REGEXP_REPLACE(nombre, '[^0-9]', '') AS UNSIGNED) ELSE 0 END) ASC,
          nombre ASC";
        
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