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
 * Obtiene todos los roles.
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
    
    // --- MÉTODOS CRUD AÑADIDOS PARA EL CONTROLADOR ---

/**
 * Crea un nuevo rol.
 * @param string $nombre
 * @param string $descripcion
 * @return bool True si se insertó correctamente, false en caso contrario.
 */
public function createRol(string $nombre, string $descripcion): bool {
$sql = "INSERT INTO {$this->table} (nombre, descripcion) VALUES (?, ?)";

$stmt = $this->conn->prepare($sql);
if (!$stmt) {
error_log("Error de preparación (createRol): " . $this->conn->error);
return false;
}

// 'ss' indica que ambos parámetros son strings
$stmt->bind_param("ss", $nombre, $descripcion);
$result = $stmt->execute();

if (!$result) {
error_log("Error de ejecución (createRol): " . $stmt->error);
}

$stmt->close();
return $result;
}

/**
 * Actualiza un rol existente.
 * @param int $id_rol
 * @param string $nombre
 * @param string $descripcion
 * @return bool True si se actualizó correctamente, false en caso contrario.
 */
public function updateRol(int $id_rol, string $nombre, string $descripcion): bool {
$sql = "UPDATE {$this->table} SET nombre = ?, descripcion = ? WHERE {$this->primaryKey} = ?";

$stmt = $this->conn->prepare($sql);
if (!$stmt) {
error_log("Error de preparación (updateRol): " . $this->conn->error);
return false;
}

// 'ssi' -> string, string, integer
$stmt->bind_param("ssi", $nombre, $descripcion, $id_rol);
$result = $stmt->execute();

if (!$result) {
error_log("Error de ejecución (updateRol): " . $stmt->error);
}

$stmt->close();
return $result;
}

/**
 * Elimina un rol por su ID.
 * @param int $id_rol
 * @return bool True si se eliminó correctamente, false en caso contrario.
 */
public function deleteRol(int $id_rol): bool {
$sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";

$stmt = $this->conn->prepare($sql);
if (!$stmt) {
error_log("Error de preparación (deleteRol): " . $this->conn->error);
return false;
}

$stmt->bind_param("i", $id_rol); // 'i' -> integer
$result = $stmt->execute();

if (!$result) {
error_log("Error de ejecución (deleteRol): " . $stmt->error);
}

$stmt->close();
return $result;
}
}
