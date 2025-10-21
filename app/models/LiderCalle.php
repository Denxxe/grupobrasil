<?php
// grupobrasil/app/models/LiderCalle.php

class LiderCalle extends ModelBase {
    protected $table = 'lider_calle';
    // Esta tabla usa clave compuesta, por lo que no definimos $primaryKey para los métodos base

    public function __construct() {
        parent::__construct();
    }

    /**
     * Crea una nueva asignación de calle para un líder de usuario.
     * Reemplaza el método assignLeader antiguo y usa id_usuario para la coherencia con el sistema de roles.
     *
     * @param array $data Debe contener 'id_usuario' y 'id_calle'.
     * @return bool True si se insertó o actualizó (ON DUPLICATE KEY UPDATE), false en caso de error.
     */
    public function create(array $data): bool {
        $idUsuario = $data['id_usuario'] ?? null;
        $idCalle = $data['id_calle'] ?? null;
        
        if (!$idUsuario || !$idCalle) {
            error_log("LiderCalle::create - Falta id_usuario o id_calle en los datos.");
            return false;
        }

        // Usamos ON DUPLICATE KEY UPDATE para asegurar que si la asignación ya existe,
        // simplemente se actualiza a 'activo' y se refresca la fecha.
        $sql = "
            INSERT INTO {$this->table} 
                (id_usuario, id_calle, fecha_designacion, activo) 
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
        $stmt->bind_param("ii", $idUsuario, $idCalle);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
             error_log("Error de ejecución al crear asignación de líder: " . $this->conn->error);
        }

        return $success;
    }

    /**
     * Elimina todas las asignaciones de calle activas para un usuario específico.
     * Método esencial para la lógica de actualización en el controlador (limpiar y re-insertar).
     *
     * @param int $usuarioId El ID del usuario.
     * @return bool True si la operación fue exitosa.
     */
    public function deleteByUsuarioId(int $usuarioId): bool {
        $sql = "DELETE FROM {$this->table} WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar deleteByUsuarioId: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $usuarioId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            error_log("Error de ejecución al eliminar asignaciones de líder: " . $this->conn->error);
        }

        // Retorna true incluso si no se eliminaron filas.
        return true; 
    }
public function getCallesIdsByUsuarioId(int $id_usuario): array {
    // Implementación asumiendo una tabla intermedia 'usuario_calle':
    $sql = "SELECT id_calle FROM usuario_calle WHERE id_usuario = ?";
    
    $stmt = $this->conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Error al preparar la consulta getCallesIdsByUsuarioId: " . $this->conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $id_usuario);
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
    public function getCallesByUsuarioId(int $usuarioId): array {
        $sql = "SELECT id_calle FROM {$this->table} WHERE id_usuario = ? AND activo = 1";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar getCallesByUsuarioId: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("i", $usuarioId);
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
