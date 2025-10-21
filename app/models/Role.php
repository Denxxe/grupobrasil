<?php
// grupobrasil/app/models/Role.php

require_once 'ModelBase.php';

class Role extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'rol'; // Asume que la tabla de roles se llama 'rol'
        $this->primaryKey = 'id_rol'; // Asume que la clave primaria es 'id_rol'
    }

    /**
     * Obtiene todos los roles, excluyendo el rol de Superadmin (id_rol = 1).
     * @return array
     */
    public function findAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY id_rol ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Obtiene los roles de liderazgo (todos los roles excepto el Superadmin/Rol 1).
     * Asume que id_rol = 1 es el Superadmin.
     * @return array
     */
    public function findLeadershipRoles(): array {
        $sql = "SELECT * FROM {$this->table} WHERE id_rol > 1 ORDER BY id_rol ASC";
        
        $result = $this->conn->query($sql);

        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        }
        
        error_log("Error al obtener roles de liderazgo: " . $this->conn->error);
        return [];
    }
}
