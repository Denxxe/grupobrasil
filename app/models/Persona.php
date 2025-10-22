<?php
// grupobrasil/app/models/Persona.php

require_once 'ModelBase.php';

class Persona extends ModelBase {
    public function __construct() {
        // Asumiendo que $this->db (conexión) se inicializa en ModelBase
        parent::__construct();
        $this->table = 'persona';
        $this->primaryKey = 'id_persona';
    }

    /**
     * Obtiene todos los registros de personas aplicando filtros y orden.
     * Realiza un LEFT JOIN con la tabla 'usuario' para identificar si la persona
     * tiene credenciales de acceso, incluyendo el estado de actividad del usuario.
     * @param array $filters Opciones de filtro (search, activo, es_usuario).
     * @param array $order Opciones de orden (column, direction).
     * @return array Lista de personas con info de usuario si existe.
     */
    public function getAllFiltered(array $filters = [], array $order = []): array {
    // AÑADIR JOIN a 'calle' para obtener el nombre de la calle/vereda
    $sql = "
       SELECT 
    p.*, 
    u.id_usuario, 
    c.nombre AS calle_nombre, /* VITAL */
    p.numero_casa, 
    u.id_rol,
    u.activo AS usuario_activo,
    CASE WHEN u.id_usuario IS NOT NULL THEN 1 ELSE 0 END AS tiene_usuario
FROM 
    persona p
LEFT JOIN 
    usuario u ON p.id_persona = u.id_persona
LEFT JOIN 
    calle c ON p.id_calle = c.id_calle /* VITAL: La unión correcta */
WHERE 1=1
    ";
        
        $params = [];
        $types = '';
        
        // --- Aplicar Filtros ---
        
        // 1. Búsqueda general (Cédula, nombre, apellido, correo, nombre_usuario)
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (
                p.cedula LIKE ? OR 
                p.nombres LIKE ? OR 
                p.apellidos LIKE ? OR 
                p.correo LIKE ? OR
                p.cedula LIKE ? /* <<-- CORREGIDO: Usamos p.cedula en lugar de u.nombre_usuario */
            )";
            $params = array_merge($params, [$search, $search, $search, $search, $search]);
            $types .= 'sssss';
        }
        
        // 2. Filtro por estado activo de la Persona (p.activo)
        if (isset($filters['activo']) && is_numeric($filters['activo'])) {
            $sql .= " AND p.activo = ?";
            $params[] = (int)$filters['activo'];
            $types .= 'i';
        }
        
        // 3. Filtro opcional para saber si TIENE o NO un usuario asociado
        if (isset($filters['es_usuario']) && is_numeric($filters['es_usuario'])) {
            if ((int)$filters['es_usuario'] === 1) {
                $sql .= " AND u.id_usuario IS NOT NULL";
            } else {
                $sql .= " AND u.id_usuario IS NULL";
            }
        }


        // --- Aplicar Ordenamiento ---
        $allowed_columns = ['cedula', 'nombres', 'apellidos', 'id_persona'];
        $order_column = $order['column'] ?? 'p.apellidos'; // Orden por defecto
        $order_direction = strtoupper($order['direction'] ?? 'ASC');

        // Sanitación de la columna de orden
        if (!in_array($order_column, $allowed_columns)) {
            $order_column = 'p.apellidos';
        } else {
            $order_column = 'p.' . $order_column; 
        }

        // Sanitación de la dirección de orden
        if (!in_array($order_direction, ['ASC', 'DESC'])) {
            $order_direction = 'ASC';
        }

        $sql .= " ORDER BY " . $order_column . " " . $order_direction;

        // Preparar y Ejecutar (Usando mysqli)
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllFiltered en Persona: " . $this->conn->error . " | SQL: " . $sql);
            return [];
        }

        if (!empty($params)) {
            // Utilizamos call_user_func_array para bind_param
            $bind_params = array_merge([$types], $params);
            $references = [];
            foreach ($bind_params as $key => $value) {
                $references[$key] = &$bind_params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $references);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $data;
    }

    /**
     * Obtiene todos los registros de personas.
     * @param array $filters (No usado)
     */

public function findAll(): array {
    $sql = "
        SELECT 
            p.*, 
            c.nombre AS calle_nombre, /* El nombre de la calle */
            p.numero_casa,           /* El número de casa */
            u.id_usuario IS NOT NULL AS has_user_account /* Si tiene cuenta de usuario */
        FROM 
            {$this->table} p /* 'persona' */
        LEFT JOIN 
            calle c ON p.id_calle = c.id_calle  /* <--- CORREGIDO: Usando 'calle' y 'id_calle' */
        LEFT JOIN
            usuario u ON p.id_persona = u.id_persona
        ORDER BY 
            p.apellidos ASC";

    $result = $this->conn->query($sql);

    if ($result) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $data;
    } else {
        error_log("Error al obtener personas con calle: " . $this->conn->error . " SQL: " . $sql);
        return [];
    }
}

    public function getAll(array $filters = []): array {
        return $this->getAllFiltered([]);
    }

    /**
     * Busca un registro de persona por su Cédula (CI).
     * @param string $cedula La cédula a buscar.
     * @return array|false
     */
    public function buscarPorCI(string $cedula) { 
        $sql = "SELECT * FROM " . $this->table . " WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorCI: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }

        $stmt->close();
        return false;
    }

    /**
     * Busca un registro de persona por su Correo Electrónico.
     * @param string $correo El correo electrónico a buscar.
     * @return array|false
     */
    public function buscarPorCorreo(string $correo) {
        $sql = "SELECT * FROM " . $this->table . " WHERE correo = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorCorreo: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }

        $stmt->close();
        return false;
    }

    // -------------------------------------------------------------------------
    // MÉTODOS HEREDADOS (Delegación a ModelBase para CRUD básico)
    // -------------------------------------------------------------------------

    /**
     * Obtiene una persona por su ID. Delega a ModelBase::find().
     * @param int $id
     * @return array|false
     */
    public function getById(int $id) {
        return $this->find($id);
    }

    /**
     * Crea un nuevo registro de persona. Delega a ModelBase::create().
     * @param array $data
     * @return int|false ID insertado o false.
     */
    public function createPersona(array $data) {
        return $this->create($data);
    }

    /**
     * Actualiza un registro de persona. Delega a ModelBase::update().
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updatePersona(int $id, array $data): bool {
        return $this->update($id, $data);
    }

    /**
     * Elimina (o desactiva) una persona. Delega a ModelBase::delete().
     * @param int $id
     * @return bool
     */
    public function deletePersona(int $id): bool {
        return $this->delete($id);
    }

    /**
     * Cuenta el total de registros activos.
     * @param array $filters (No usado)
     * @return int
     */
    public function contar(array $filters = []): int {
        $sql = "SELECT COUNT(*) AS total FROM " . $this->table . " WHERE activo = 1";
        $result = $this->conn->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        }
        return 0;
    }
    
}
