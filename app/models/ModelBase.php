<?php
// grupobrasil/app/models/ModelBase.php

require_once __DIR__ . '/../../config/Database.php';

class ModelBase {
    protected $conn;
    protected $table;
    protected $primaryKey;

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    protected function getParamType($value) {
        if (is_int($value)) return 'i';
        if (is_float($value)) return 'd';
        // 's' es el tipo más seguro para strings y NULL en MySQLi
        return 's'; 
    }

    public function find(int $id) { 
        $sql = "SELECT * FROM " . $this->table . " WHERE " . $this->primaryKey . " = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta find para " . $this->table . ": " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return $data;
        }

        $stmt->close();
        return false;
    }
    
    // Alias para compatibilidad con el código existente.
    public function getById(int $id) {
        return $this->find($id);
    }

    public function getAll() {
        $sql = "SELECT * FROM " . $this->table . " ORDER BY " . $this->primaryKey . " DESC";
        $result = $this->conn->query($sql);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al obtener todos los registros de " . $this->table . ": " . $this->conn->error);
        }
        return $data;
    }

    public function create(array $data) {
        if (empty($data)) {
            return false;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = '';
        $params = [];

        foreach ($data as $value) {
            $types .= $this->getParamType($value);
            $params[] = $value;
        }

        $sql = "INSERT INTO " . $this->table . " (" . $columns . ") VALUES (" . $placeholders . ")";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta create para " . $this->table . ": " . $this->conn->error);
            return false;
        }

        // bind_param requiere referencias, usamos call_user_func_array
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
            $new_id = $this->conn->insert_id;
            $stmt->close();
            return $new_id;
        } else {
            error_log("Error al ejecutar create para " . $this->table . ": " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function update(int $id, array $data) {
        if (empty($data)) {
            return false;
        }

        $set_clauses = [];
        $types = '';
        $params = [];

        foreach ($data as $field => $value) {
            $set_clauses[] = "$field = ?";
            $types .= $this->getParamType($value); 
            $params[] = $value;
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= 'i'; // Tipo para la clave primaria (ID)
        $params[] = $id; // Valor para la clave primaria

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta update para " . $this->table . ": " . $this->conn->error);
            return false;
        }

        // bind_param requiere referencias
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            // CORRECCIÓN CLAVE: Devuelve true si >= 0 (ya que 0 afectadas es un éxito sin cambios)
            return $rows_affected >= 0; 
        } else {
            error_log("Error al ejecutar update para " . $this->table . ": " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function delete(int $id) {
        $sql = "DELETE FROM " . $this->table . " WHERE " . $this->primaryKey . " = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta delete para " . $this->table . ": " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0; 
        } else {
            error_log("Error al ejecutar delete para " . $this->table . ": " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function rawQuery(string $sql) {
        // Ejecuta la consulta usando la conexión interna
        return $this->conn->query($sql);
    }

    public function getConnection() {
        return $this->conn;
    }
}
