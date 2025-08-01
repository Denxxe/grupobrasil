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

    /**
     * Obtiene un registro por su clave primaria.
     * @param int $id El ID del registro.
     * @return array|false Un array asociativo con los datos del registro o false si no se encuentra.
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE " . $this->primaryKey . " = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getById para " . $this->table . ": " . $this->conn->error);
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

    /**
     * Obtiene todos los registros de la tabla.
     * @return array Un array de arrays asociativos con todos los registros.
     */
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

    /**
     * Crea un nuevo registro en la base de datos.
     * @param array $data Array asociativo con los datos a insertar (clave => valor).
     * @return int|bool El ID del nuevo registro insertado si fue exitoso, false en caso contrario.
     */
   public function create(array $data) {
    if (empty($data)) {
        return false;
    }

    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $types = '';
    $params = [];

    foreach ($data as $value) {
        // Corrección importante: Si el valor es NULL, su tipo debe ser 's' (string)
        // ya que MySQLi trata los NULLs como strings para bind_param.
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_string($value) || $value === null) { // Agrega la verificación de null aquí
            $types .= 's';
        } else {
            // Manejar otros tipos si es necesario, o un tipo por defecto
            $types .= 's'; // Por seguridad, si no es ninguno de los anteriores, asumimos string
        }
        $params[] = $value;
    }

    $sql = "INSERT INTO " . $this->table . " (" . $columns . ") VALUES (" . $placeholders . ")";
    $stmt = $this->conn->prepare($sql);

    if ($stmt === false) {
        error_log("Error al preparar la consulta create para " . $this->table . ": " . $this->conn->error);
        return false;
    }

    // bind_param requiere referencias, así que usamos call_user_func_array
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

    /**
     * Actualiza un registro existente por su clave primaria.
     * @param int $id El ID del registro a actualizar.
     * @param array $data Array asociativo con los datos a actualizar (clave => valor).
     * @return bool True si fue exitoso, false en caso contrario.
     */
    public function update(int $id, array $data) {
        if (empty($data)) {
            return false;
        }

        $set_clauses = [];
        $types = '';
        $params = [];

        foreach ($data as $field => $value) {
            $set_clauses[] = "$field = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $value;
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= 'i'; // Tipo para la clave primaria
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
            return $rows_affected > 0;
        } else {
            error_log("Error al ejecutar update para " . $this->table . ": " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Elimina un registro de la base de datos por su clave primaria.
     * @param int $id El ID del registro a eliminar.
     * @return bool True si fue exitoso, false en caso contrario.
     */
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

    // Asegúrate de cerrar la conexión cuando el objeto ModelBase ya no sea necesario
    // aunque en un Singleton, la conexión se cierra al final de la ejecución del script.
    // public function __destruct() {
    //     if ($this->conn) {
    //         $this->conn->close();
    //     }
    // }
}