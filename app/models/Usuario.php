<?php
// grupobrasil/app/models/Usuario.php

// Asegúrate de que el autoloading esté configurado. Si no, usa require_once.
require_once 'ModelBase.php';
require_once 'Persona.php'; // Necesario para la lógica transaccional

class Usuario extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'usuario'; 
        $this->primaryKey = 'id_usuario';
    }

    /**
     * Define la consulta base para obtener todos los campos de usuario y persona.
     * Renombra p.correo a persona_correo para evitar conflictos de campo.
     * @return string
     */
    private function getBaseQuery(): string {
        return "SELECT 
             u.*, 
             p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo, p.telefono, p.direccion, 
             p.correo AS persona_correo,
             CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
             r1.nombre AS nombre_rol,
             r2.nombre AS nombre_rol_secundario
             FROM " . $this->table . " u
             INNER JOIN persona p ON u.id_persona = p.id_persona
             LEFT JOIN rol r1 ON u.id_rol = r1.id_rol
             LEFT JOIN rol r2 ON u.id_rol_secundario = r2.id_rol";
    }

    /**
     * Obtiene un registro de usuario completo (incluyendo datos de Persona) por su ID de Usuario.
     * @param int $id_usuario El ID de la tabla 'usuario'.
     * @return array|false
     */
    public function getById(int $id_usuario) {
        $sql = $this->getBaseQuery() . " WHERE u." . $this->primaryKey . " = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getById: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $id_usuario); 
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
     * Alias para compatibilidad con el código anterior.
     */
    public function obtenerUsuarioCompleto(int $id_usuario) {
        return $this->getById($id_usuario);
    }


    /**
     * Busca un usuario por su número de cédula (CI) en la tabla persona.
     * @param string $ci_usuario La cédula de la persona.
     * @return array|false
     */
    public function buscarPorCI(string $ci_usuario) {
        $sql = $this->getBaseQuery() . " WHERE p.cedula = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorCI: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $ci_usuario);
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
     * Busca un usuario por su correo electrónico de LOGIN (u.email).
     * @param string $email El correo electrónico de la tabla usuario.
     * @return array|false
     */
    public function buscarPorEmail(string $email) {
        $sql = $this->getBaseQuery() . " WHERE u.email = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta buscarPorEmail: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $email);
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
     * Obtiene el conteo total de usuarios (usando la tabla 'usuario').
     * @return int
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
     * Obtiene la lista de usuarios filtrada y ordenada (incluyendo datos de Persona).
     * @param array $filters Filtros de búsqueda (search, id_rol, activo).
     * @param array $order Columna y dirección de ordenamiento.
     * @return array|false
     */
    public function getAllFiltered(array $filters = [], array $order = []) {
        $sql = $this->getBaseQuery();
        $where_clauses = [];
        $params = [];
        $types = "";

        // Lógica de filtrado
        if (!empty($filters)) {
            // Busqueda por campos de PERSONA y USUARIO (cedula, nombres, apellidos, u.email)
            if (isset($filters['search']) && $filters['search'] !== '') {
                $search = '%' . $filters['search'] . '%';
                $where_clauses[] = "(p.cedula LIKE ? OR p.nombres LIKE ? OR p.apellidos LIKE ? OR u.email LIKE ?)";
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $types .= "ssss";
            }
            // Filtro por Rol (campo de USUARIO)
            if (isset($filters['id_rol']) && $filters['id_rol'] !== '' && $filters['id_rol'] !== 'all') {
                $where_clauses[] = "u.id_rol = ?";
                $params[] = $filters['id_rol'];
                $types .= "i";
            }
            // Filtro por Estado Activo (campo de USUARIO)
            if (isset($filters['activo']) && $filters['activo'] !== '' && $filters['activo'] !== 'all') {
                $where_clauses[] = "u.activo = ?";
                $params[] = $filters['activo'];
                $types .= "i";
            }
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Lógica de ordenamiento
        $order_sql = "p.nombres ASC"; // Ordenamiento por defecto
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            // Validamos solo columnas permitidas
            $valid_columns = ['p.cedula', 'p.nombres', 'p.apellidos', 'u.email', 'u.id_rol', 'u.activo', 'u.id_usuario'];
            if (in_array($order_column, $valid_columns) && in_array($order_direction, ['ASC', 'DESC'])) {
                $order_sql = "$order_column $order_direction";
            }
        } 
        $sql .= " ORDER BY " . $order_sql;


        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllFiltered: " . $this->conn->error);
            return false;
        }

        if (!empty($params)) {
            // Técnica para bind_param dinámico
            $bind_names = [$types];
            foreach ($params as $key => $value) {
                $bind_names[] = &$params[$key]; // Referencia necesaria para bind_param
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } else {
            error_log("Error al ejecutar getAllFiltered: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Crea un nuevo registro de usuario, insertando primero en 'persona' y luego en 'usuario'.
     * @param array $data Los datos completos del usuario (incluye datos de persona). 
     * Debe contener: ci_usuario, nombre, apellido, email, password, etc.
     * @return int|false El ID del nuevo registro de usuario o false.
     */
    public function create(array $data) {
        if (!$this->conn) {
             error_log("No hay conexión a la base de datos.");
             return false;
        }
        
        $this->conn->begin_transaction(); // Iniciar Transacción

        try {
            // Instanciar el modelo Persona
            $personaModel = new Persona();

            // 1. Preparar e insertar en la tabla 'persona'
            $persona_data = [
                'cedula' => $data['ci_usuario'] ?? null,
                'nombres' => $data['nombre'] ?? null,
                'apellidos' => $data['apellido'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? '1900-01-01', 
                'sexo' => $data['sexo'] ?? 'N/A', // Asumido: Añadir campo 'sexo' si aplica
                'direccion' => $data['direccion'] ?? 'N/A',
                'telefono' => $data['telefono'] ?? 'N/A',
                'correo' => $data['email'] ?? null, // Usamos el mismo email para persona_correo
                'activo' => 1,
            ];

            // Se asume que Persona::createPersona existe y funciona con ModelBase::create
            $id_persona = $personaModel->createPersona($persona_data); 

            if (!$id_persona) {
                throw new \Exception("Error al crear el registro de persona. Detalles: " . $this->conn->error);
            }

            $usuario_data = [
                'id_persona' => $id_persona,
                'nombre_usuario' => $data['nombre_usuario'] ?? $data['ci_usuario'], // Nombre de usuario para login
                'password' => $data['password'] ?? null,
                'id_rol' => $data['id_rol'] ?? 3, // Rol por defecto
                'email' => $data['email'] ?? $data['ci_usuario'] . '@temp.com', // Email de login
                'foto_perfil' => $data['foto_perfil'] ?? null,
                'biografia' => $data['biografia'] ?? null,
                'activo' => $data['activo'] ?? 1,
            ];

            // Usamos el método 'create' de la clase padre (ModelBase)
            $id_usuario = parent::create($usuario_data); 

            if (!$id_usuario) {
                throw new \Exception("Error al crear el registro de usuario. Detalles: " . $this->conn->error);
            }

            $this->conn->commit(); // Confirmar transacción
            return $id_usuario;

        } catch (\Exception $e) {
            $this->conn->rollback(); // Revertir si algo falla
            error_log("Transacción de creación de usuario fallida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo registro de usuario SOLAMENTE (sin crear persona).
     * Útil cuando la persona ya existe y solo necesitamos crear las credenciales de acceso.
     * @param array $data Debe contener: id_persona, email, password, id_rol
     * @return int|false El ID del nuevo registro de usuario o false.
     */
    public function createUserOnly(array $data) {
        if (!$this->conn) {
             error_log("[v0] No hay conexión a la base de datos.");
             return false;
        }

        // Validar que los campos requeridos estén presentes
        if (empty($data['id_persona']) || empty($data['email']) || empty($data['password']) || empty($data['id_rol'])) {
            error_log("[v0] createUserOnly: Faltan campos requeridos (id_persona, email, password, id_rol)");
            return false;
        }

        error_log("[v0] createUserOnly: Preparando datos para usuario");

        $usuario_data = [
            'id_persona' => $data['id_persona'],
            'email' => $data['email'],
            'password' => $data['password'],
            'id_rol' => $data['id_rol'],
            'activo' => $data['activo'] ?? 1,
        ];

        error_log("[v0] createUserOnly: Llamando a parent::create con datos: " . json_encode($usuario_data));

        $id_usuario = parent::create($usuario_data);

        if (!$id_usuario) {
            error_log("[v0] Error al crear el registro de usuario (solo): " . $this->conn->error);
            return false;
        }

        error_log("[v0] createUserOnly: Usuario creado exitosamente con ID: $id_usuario");
        return $id_usuario;
    }

    /**
     * Actualiza el registro de usuario y el registro de persona asociado.
     * @param int $id_usuario El ID del usuario.
     * @param array $data Campos y valores a actualizar en ambas tablas.
     * @return bool
     */
    public function update(int $id_usuario, array $data): bool {
        if (!$this->conn) {
             error_log("No hay conexión a la base de datos.");
             return false;
        }
        
        $this->conn->begin_transaction(); // Iniciar Transacción

        try {
            // 1. Obtener el id_persona
            $current_user = $this->getById($id_usuario);
            if (!$current_user || !isset($current_user['id_persona'])) {
                throw new \Exception("Usuario con ID $id_usuario no encontrado.");
            }
            $id_persona = (int) $current_user['id_persona'];

            $personaModel = new Persona();

            // 2. Preparar y actualizar en la tabla 'persona'
            $persona_data_update = [];
            // Mapeo de keys de entrada a campos de persona
            if (isset($data['ci_usuario'])) $persona_data_update['cedula'] = $data['ci_usuario'];
            if (isset($data['nombre'])) $persona_data_update['nombres'] = $data['nombre'];
            if (isset($data['apellido'])) $persona_data_update['apellidos'] = $data['apellido'];
            if (isset($data['fecha_nacimiento'])) $persona_data_update['fecha_nacimiento'] = $data['fecha_nacimiento'];
            if (isset($data['sexo'])) $persona_data_update['sexo'] = $data['sexo'];
            if (isset($data['direccion'])) $persona_data_update['direccion'] = $data['direccion'];
            if (isset($data['telefono'])) $persona_data_update['telefono'] = $data['telefono'];
            if (isset($data['email'])) $persona_data_update['correo'] = $data['email']; // Actualizar correo de persona

            $persona_updated = true;
            if (!empty($persona_data_update)) {
                $persona_updated = $personaModel->updatePersona($id_persona, $persona_data_update);
            }
            if ($persona_updated === false) {
                throw new \Exception("Error al actualizar el registro de persona.");
            }

            $usuario_fields = ['nombre_usuario', 'id_rol', 'password', 'foto_perfil', 'biografia', 'activo', 'email'];
            $usuario_data_update = array_intersect_key($data, array_flip($usuario_fields));
            if (isset($data['email'])) $usuario_data_update['email'] = $data['email'];
            if (isset($data['activo'])) $usuario_data_update['activo'] = $data['activo'];
            
            $usuario_updated = true;
            if (!empty($usuario_data_update)) {
                // Usamos el método 'update' de la clase padre (ModelBase)
                $usuario_updated = parent::update($id_usuario, $usuario_data_update);
            }
            if ($usuario_updated === false) {
                throw new \Exception("Error al actualizar el registro de usuario.");
            }

            $this->conn->commit(); // Confirmar transacción
            return true;

        } catch (\Exception $e) {
            $this->conn->rollback(); // Revertir si algo falla
            error_log("Transacción de actualización de usuario fallida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina el registro de la tabla 'usuario' y la 'persona' asociada.
     * Nota: En un entorno real, solo se debería INACTIVAR (update activo=0).
     * @param int $id_usuario El ID del usuario.
     * @return bool
     */
    public function delete($id_usuario): bool {
        if (!$this->conn) {
             error_log("No hay conexión a la base de datos.");
             return false;
        }
        
        $this->conn->begin_transaction(); // Iniciar Transacción

        try {
            // 1. Obtener el id_persona
            $user_to_delete = $this->getById($id_usuario);
            if (!$user_to_delete || !isset($user_to_delete['id_persona'])) {
                // Si el usuario ya no existe, consideramos la eliminación exitosa.
                $this->conn->rollback(); 
                return true; 
            }
            $id_persona = (int) $user_to_delete['id_persona'];

            // 2. Eliminar de la tabla 'usuario'
            $usuario_deleted = parent::delete($id_usuario);

            if (!$usuario_deleted) {
                throw new \Exception("Error al eliminar el registro de usuario.");
            }

            // 3. Eliminar de la tabla 'persona'
            $personaModel = new Persona(); 
            $persona_deleted = $personaModel->deletePersona($id_persona);

            if (!$persona_deleted) {
                throw new \Exception("Error al eliminar el registro de persona.");
            }

            $this->conn->commit(); // Confirmar transacción
            return true;

        } catch (\Exception $e) {
            $this->conn->rollback(); // Revertir si algo falla
            error_log("Transacción de eliminación de usuario fallida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un usuario por su id_persona
     */
    public function findByPersonId(int $id_persona) {
        $sql = $this->getBaseQuery() . " WHERE u.id_persona = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta findByPersonId: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $id_persona);
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

    public function getPersonData(int $id_usuario): ?array {
        $user = $this->getById($id_usuario);
        if ($user && isset($user['id_persona'])) {
            return ['id_persona' => $user['id_persona']];
        }
        return null;
    }

    public function countNewThisWeek(): int {
    $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    
    // Asume que la columna de registro es 'fecha_registro'
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE fecha_registro >= ?";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $sevenDaysAgo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_row()) {
        $stmt->close();
        return (int) $row[0];
    }
    $stmt->close();
    return 0;
}


public function countActiveLeaders(): int {
    // Ajusta el ID de Rol (2) y el estado ('activo') según tu DB
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id_rol = 2 AND estado = 'activo'";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_row()) {
        $stmt->close();
        return (int) $row[0];
    }
    $stmt->close();
    return 0;
}

    /**
     * Verifica si un usuario tiene un rol específico (primario o secundario)
     * @param int $id_usuario ID del usuario
     * @param int $id_rol ID del rol a verificar
     * @return bool True si el usuario tiene ese rol (primario o secundario)
     */
    public function tieneRol(int $id_usuario, int $id_rol): bool {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE id_usuario = ? 
                AND (id_rol = ? OR id_rol_secundario = ?)";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar consulta tieneRol: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("iii", $id_usuario, $id_rol, $id_rol);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)$row['count'] > 0;
        }
        
        $stmt->close();
        return false;
    }

    /**
     * Obtiene todos los roles de un usuario (primario y secundario)
     * @param int $id_usuario ID del usuario
     * @return array Array con los IDs de roles del usuario
     */
    public function getRolesUsuario(int $id_usuario): array {
        $sql = "SELECT id_rol, id_rol_secundario FROM {$this->table} WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar consulta getRolesUsuario: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $roles = [];
        if ($result && $row = $result->fetch_assoc()) {
            if (!empty($row['id_rol'])) {
                $roles[] = (int)$row['id_rol'];
            }
            if (!empty($row['id_rol_secundario'])) {
                $roles[] = (int)$row['id_rol_secundario'];
            }
        }
        
        $stmt->close();
        return $roles;
    }

    /**
     * Asigna un rol secundario a un usuario
     * @param int $id_usuario ID del usuario
     * @param int|null $id_rol_secundario ID del rol secundario (null para remover)
     * @return bool True si se actualizó correctamente
     */
    public function asignarRolSecundario(int $id_usuario, ?int $id_rol_secundario): bool {
        $sql = "UPDATE {$this->table} SET id_rol_secundario = ? WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar consulta asignarRolSecundario: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ii", $id_rol_secundario, $id_usuario);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
}
