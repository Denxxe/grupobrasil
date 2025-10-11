<?php
// grupobrasil/app/models/Usuario.php

require_once 'ModelBase.php';

class Usuario extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'usuarios';
        $this->primaryKey = 'id_usuario';
    }

    public function buscarPorCI($ci_usuario) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE ci_usuario = ?");
        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorCI: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("s", $ci_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function obtenerUsuarioPorId(int $id_usuario) {
        $sql = "SELECT * FROM " . $this->table . " WHERE " . $this->primaryKey . " = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta obtenerUsuarioPorId: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $id_usuario); // 'i' para entero
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false; // Usuario no encontrado
        }
    }

    public function buscarPorEmail(string $email) {
        $sql = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorEmail: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $email); // 's' para string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false; // Email no encontrado
        }
    }

    public function getTotalUsuarios(): int {
        $sql = "SELECT COUNT(*) AS total FROM " . $this->table;
        $result = $this->conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int) $row['total'];
        } else {
            error_log("Error al obtener el total de usuarios: " . $this->conn->error);
            return 0;
        }
    }

    public function getAllFiltered(array $filters = [], array $order = []) {
        $sql = "SELECT * FROM " . $this->table;
        $where_clauses = [];
        $params = [];
        $types = "";

        // Lógica de filtrado
        if (!empty($filters)) {
            if (isset($filters['search']) && $filters['search'] !== '') {
                $search = '%' . $filters['search'] . '%';
                $where_clauses[] = "(ci_usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR email LIKE ?)";
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $types .= "ssss";
            }
            if (isset($filters['id_rol']) && $filters['id_rol'] !== '' && $filters['id_rol'] !== 'all') {
                $where_clauses[] = "id_rol = ?";
                $params[] = $filters['id_rol'];
                $types .= "i";
            }
            if (isset($filters['activo']) && $filters['activo'] !== '' && $filters['activo'] !== 'all') {
                $where_clauses[] = "activo = ?";
                $params[] = $filters['activo'];
                $types .= "i";
            }
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Lógica de ordenamiento
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']); // ASC o DESC

            // Asegúrate de que la columna sea válida para evitar inyección SQL
            $valid_columns = ['ci_usuario', 'nombre', 'apellido', 'email', 'id_rol', 'activo'];
            if (in_array($order_column, $valid_columns)) {
                $sql .= " ORDER BY $order_column $order_direction";
            }
        } else {
            // Ordenamiento por defecto si no se especifica
            $sql .= " ORDER BY nombre ASC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllFiltered: " . $this->conn->error);
            return false;
        }

        if (!empty($params)) {
            // bind_param requiere referencias, así que usamos call_user_func_array
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $key => $value) {
                $refs[$key] = &$bind_names[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Error al ejecutar getAllFiltered: " . $stmt->error);
            return false;
        }
    }

    public function crearUsuario(array $data) {
        // Asegúrate de que la contraseña ya viene hasheada desde el controlador
        // Antes de insertar, verifica que los campos necesarios existan
        // Añadir 'requires_setup' a los campos requeridos si no tiene un valor por defecto en DB
        $required_fields = ['ci_usuario', 'nombre', 'apellido', 'fecha_nacimiento', 'direccion', 'telefono', 'email', 'password', 'id_rol', 'requires_setup'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                error_log("Error al crear usuario: Campo requerido '$field' no proporcionado.");
                return false;
            }
        }

        // Campos opcionales (o con valores por defecto)
        $foto_perfil = $data['foto_perfil'] ?? null;
        $biografia = $data['biografia'] ?? null;
        $activo = $data['activo'] ?? 1; // Por defecto activo
        $requires_setup = $data['requires_setup'];

        // SQL INSERT statement - AÑADIR 'requires_setup' a la lista de columnas y valores
        $sql = "INSERT INTO " . $this->table . " (ci_usuario, nombre, apellido, fecha_nacimiento, direccion, telefono, email, password, id_rol, foto_perfil, biografia, activo, requires_setup) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta crearUsuario: " . $this->conn->error);
            return false;
        }

        // Añadir 'i' para requires_setup en bind_param y el valor $requires_setup
        $stmt->bind_param(
            "ssssssssisssi", // s = string, i = integer. AÑADIDO UN 'i' AL FINAL para requires_setup (asumiendo TINYINT(1) o INT)
            $data['ci_usuario'],
            $data['nombre'],
            $data['apellido'],
            $data['fecha_nacimiento'],
            $data['direccion'],
            $data['telefono'],
            $data['email'],
            $data['password'], // Ya debe venir hasheada
            $data['id_rol'],
            $foto_perfil,
            $biografia,
            $activo,
            $requires_setup
        );

        if ($stmt->execute()) {
            return $stmt->insert_id;
        } else {
            error_log("Error al ejecutar crearUsuario: " . $stmt->error);
            return false;
        }
    }

    public function actualizarUsuario(int $id_usuario, array $data) {
        if (empty($data)) {
            return false;
        }

        $set_clauses = [];
        $params = [];
        $types = "";

        $field_types = [
            'ci_usuario' => 's', 'nombre' => 's', 'apellido' => 's', 'fecha_nacimiento' => 's',
            'direccion' => 's', 'telefono' => 's', 'email' => 's', 'password' => 's',
            'id_rol' => 'i', 'foto_perfil' => 's', 'biografia' => 's', 'activo' => 'i',
            'requires_setup' => 'i'
        ];

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $field_types)) {
                $set_clauses[] = "$field = ?";
                $params[] = $value;
                $types .= $field_types[$field];
            }
        }

        if (empty($set_clauses)) {
            return false;
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= "i";
        $params[] = $id_usuario;

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta actualizarUsuario: " . $this->conn->error);
            return false;
        }

        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
            // Retornar true si se ejecutó correctamente, independientemente de affected_rows
            // affected_rows puede ser 0 si los datos no cambiaron, pero no es un error
            return true;
        } else {
            error_log("Error al ejecutar actualizarUsuario: " . $stmt->error);
            return false;
        }
    }

    public function eliminarUsuario($id) {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        if ($stmt === false) {
            error_log("Error al preparar la consulta eliminarUsuario: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return $stmt->affected_rows > 0; 
        } else {
            error_log("Error al ejecutar eliminarUsuario: " . $stmt->error);
            return false;
        }
    }
}
