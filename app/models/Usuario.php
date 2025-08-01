<?php
// grupobrasil/app/models/Usuario.php

require_once 'ModelBase.php';

class Usuario extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'usuarios';
        $this->primaryKey = 'id_usuario';
    }

    /**
     * Método para buscar un usuario por su CI.
     * @param string $ci_usuario La cédula de identidad del usuario.
     * @return array|false Un array asociativo con los datos del usuario o false si no se encuentra.
     */
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

    /**
     * Obtiene un usuario por su ID de usuario.
     * @param int $id_usuario ID del usuario a buscar.
     * @return array|false Un array asociativo con los datos del usuario o false si no se encuentra o hay un error.
     */
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

    /**
     * Busca un usuario por su dirección de correo electrónico.
     * @param string $email El correo electrónico a buscar.
     * @return array|false Un array asociativo con los datos del usuario o false si no se encuentra o hay un error.
     */
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

    /**
     * Obtiene el número total de usuarios en la base de datos.
     * @return int El número total de usuarios, o 0 si hay un error.
     */
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

    /**
     * Obtiene todos los registros de la tabla, con opciones de filtro y ordenamiento.
     * @param array $filters Array asociativo de filtros (e.g., ['search' => 'texto', 'id_rol' => 1]).
     * @param array $order Array asociativo de ordenamiento (e.g., ['column' => 'nombre', 'direction' => 'ASC']).
     * @return array|false Un array de registros o false si hay un error.
     */
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


    /**
     * Crea un nuevo usuario en la base de datos.
     * @param array $data Array asociativo con los datos del usuario.
     * Debe contener 'ci_usuario', 'nombre', 'apellido', 'password', 'id_rol', etc.
     * La contraseña debe venir ya hasheada.
     * @return int|bool ID del nuevo usuario si fue exitoso, false en caso contrario.
     */
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

    /**
     * Actualiza un usuario existente por su ID.
     * @param int $id_usuario ID del usuario a actualizar.
     * @param array $data Array asociativo con los datos a actualizar.
     * La contraseña debe venir ya hasheada si se va a actualizar.
     * @return bool True si fue exitoso, false en caso contrario.
     */
    public function actualizarUsuario(int $id_usuario, array $data) {
        if (empty($data)) {
            return false; // No hay datos para actualizar
        }

        $set_clauses = [];
        $params = [];
        $types = "";

        // Mapeo de campos a tipos para bind_param
        $field_types = [
            'ci_usuario' => 's', 'nombre' => 's', 'apellido' => 's', 'fecha_nacimiento' => 's',
            'direccion' => 's', 'telefono' => 's', 'email' => 's', 'password' => 's',
            'id_rol' => 'i', 'foto_perfil' => 's', 'biografia' => 's', 'activo' => 'i',
            'requires_setup' => 'i'
        ];

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $field_types)) { // Solo actualizar campos válidos
                $set_clauses[] = "$field = ?";
                $params[] = $value;
                $types .= $field_types[$field];
            }
        }

        if (empty($set_clauses)) {
            return false; // No hay campos válidos para actualizar
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= "i"; // El tipo para el id_usuario
        $params[] = $id_usuario; // Añadir el ID al final de los parámetros

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta actualizarUsuario: " . $this->conn->error);
            return false;
        }

        // Usar call_user_func_array para bind_param debido a un número variable de parámetros
        // Necesitamos pasar la referencia de los parámetros
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);


        if ($stmt->execute()) {
            return $stmt->affected_rows > 0;
        } else {
            error_log("Error al ejecutar actualizarUsuario: " . $stmt->error);
            return false;
        }
    }

    /**
     * Elimina un usuario por su ID.
     * @param int $id ID del usuario a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
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