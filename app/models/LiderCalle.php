<?php
// grupobrasil/app/models/LiderCalle.php

require_once 'ModelBase.php'; // Asegúrate de incluir la base

class LiderCalle extends ModelBase {
protected $table = 'lider_calle';
// Esta tabla usa clave compuesta, por lo que no definimos $primaryKey para los métodos base

public function __construct() {
parent::__construct();
}

    /**
     * Busca una asignación de líder de calle activa para un habitante.
     * Este método es el que requería LoginController.
     *
     * @param int $habitanteId El ID del habitante.
     * @return array|null La asignación activa encontrada o null si no existe.
     */
    public function findByHabitanteId(int $habitanteId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id_habitante = ? AND activo = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar findByHabitanteId: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("i", $habitanteId);
        $stmt->execute();
        $result = $stmt->get_result();

        $asignacion = $result ? $result->fetch_assoc() : null;
        
        $stmt->close();
        return $asignacion;
    }


/**
 * Crea una nueva asignación de calle para un líder (habitante).
 * // Cambiado para usar id_habitante en lugar de id_usuario
 *
 * @param array $data Debe contener 'id_habitante' y 'id_calle'.
 * @return bool True si se insertó o actualizó (ON DUPLICATE KEY UPDATE), false en caso de error.
 */
public function create(array $data): bool {
$idHabitante = $data['id_habitante'] ?? null;
$idCalle = $data['id_calle'] ?? null;

if (!$idHabitante || !$idCalle) {
error_log("LiderCalle::create - Falta id_habitante o id_calle en los datos.");
return false;
}

// Usamos ON DUPLICATE KEY UPDATE para asegurar que si la asignación ya existe,
// simplemente se actualiza a 'activo' y se refresca la fecha.
$sql = "
INSERT INTO {$this->table} 
(id_habitante, id_calle, fecha_designacion, activo) 
VALUES 
(?, ?, NOW(), 1)
ON DUPLICATE KEY UPDATE 
activo = 1, fecha_designacion = NOW()
";

$stmt = $this->conn->prepare($sql);
if ($stmt === false) {
error_log("Error al preparar create: " . $this->conn->error);
return false;
}

// Asumiendo que ambas son de tipo entero 'i'
$stmt->bind_param("ii", $idHabitante, $idCalle);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
error_log("Error de ejecución al crear asignación de líder: " . $this->conn->error);
}

return $success;
}

/**
 * Elimina todas las asignaciones de calle activas para un habitante específico.
 * // Cambiado para usar id_habitante en lugar de id_usuario
 *
 * @param int $habitanteId El ID del habitante.
 * @return bool True si la operación fue exitosa.
 */
public function deleteByHabitanteId(int $habitanteId): bool {
$sql = "DELETE FROM {$this->table} WHERE id_habitante = ?";

$stmt = $this->conn->prepare($sql);
if ($stmt === false) {
error_log("Error al preparar deleteByHabitanteId: " . $this->conn->error);
return false;
}

$stmt->bind_param("i", $habitanteId);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
error_log("Error de ejecución al eliminar asignaciones de líder: " . $this->conn->error);
}

// Retorna true incluso si no se eliminaron filas.
return true; 
}

/**
 * Obtiene los IDs de calles asignadas a un habitante.
 * // Cambiado para usar id_habitante en lugar de id_usuario
 *
 * @param int $habitanteId El ID del habitante.
 * @return array Array de IDs de calle.
 */
public function getCallesIdsByHabitanteId(int $habitanteId): array {
$sql = "SELECT id_calle FROM {$this->table} WHERE id_habitante = ? AND activo = 1";

$stmt = $this->conn->prepare($sql);

if ($stmt === false) {
error_log("Error al preparar la consulta getCallesIdsByHabitanteId: " . $this->conn->error);
return [];
}

$stmt->bind_param("i", $habitanteId);
$stmt->execute();
$result = $stmt->get_result();

$calle_ids = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$calle_ids[] = (int) $row['id_calle'];
}
$result->free();
}
$stmt->close();
return $calle_ids;
}

/**
 * Obtiene las calles asignadas a un habitante.
 * // Cambiado para usar id_habitante en lugar de id_usuario
 *
 * @param int $habitanteId El ID del habitante.
 * @return array Array de IDs de calle.
 */
public function getCallesByHabitanteId(int $habitanteId): array {
$sql = "SELECT id_calle FROM {$this->table} WHERE id_habitante = ? AND activo = 1";

$stmt = $this->conn->prepare($sql);
if ($stmt === false) {
error_log("Error al preparar getCallesByHabitanteId: " . $this->conn->error);
return [];
}

$stmt->bind_param("i", $habitanteId);
$stmt->execute();
$result = $stmt->get_result();

$calles = [];
while ($row = $result->fetch_assoc()) {
$calles[] = (int) $row['id_calle'];
}

$stmt->close();
return $calles;
}
}
