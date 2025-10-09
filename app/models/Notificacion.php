<?php
// grupobrasil/app/models/Notificacion.php

require_once 'ModelBase.php';

class Notificacion extends ModelBase {

    public function __construct() {
        parent::__construct();
        $this->table = 'notificaciones';
        $this->primaryKey = 'id_notificacion';
    }

    public function crearNotificacion(
        int $id_usuario_destino,
        ?int $id_usuario_origen, // Nullable int
        string $tipo,
        string $mensaje,
        ?int $id_referencia = null // Nullable int con valor por defecto null
    ) {
        $data = [
            'id_usuario_destino' => $id_usuario_destino,
            'id_usuario_origen' => $id_usuario_origen,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'id_referencia' => $id_referencia,
            'leido' => 0 // Por defecto, una nueva notificación no está leída
            // fecha_creacion se establece automáticamente en la base de datos con CURRENT_TIMESTAMP
        ];

        // Usamos el método create de ModelBase para insertar la notificación
        $new_id = $this->create($data);

        if ($new_id === false) {
            error_log("Error al crear notificación para usuario $id_usuario_destino de tipo $tipo.");
        }
        return $new_id;
    }

    public function obtenerNotificacionesPorUsuario(
        int $id_usuario_destino,
        bool $unreadOnly = true,
        array $order = ['column' => 'fecha_creacion', 'direction' => 'DESC']
    ) {
        $sql = "SELECT n.*, 
                       u_origen.nombre AS origen_nombre, 
                       u_origen.apellido AS origen_apellido
                FROM " . $this->table . " n
                LEFT JOIN usuarios u_origen ON n.id_usuario_origen = u_origen.id_usuario
                WHERE n.id_usuario_destino = ?";
        
        $params = [$id_usuario_destino];
        $types = "i";

        if ($unreadOnly) {
            $sql .= " AND n.leido = ?";
            $params[] = 0; // 0 = no leído
            $types .= "i";
        }

        // Lógica de ordenamiento
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            // Validar columnas para evitar inyección SQL
            $valid_columns = ['fecha_creacion', 'tipo']; 
            if (in_array($order_column, $valid_columns)) {
                $sql .= " ORDER BY n.$order_column $order_direction";
            }
        } else {
            $sql .= " ORDER BY n.fecha_creacion DESC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta obtenerNotificacionesPorUsuario: " . $this->conn->error);
            return [];
        }

        // Usar call_user_func_array para bind_param
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();

        $notificaciones = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notificaciones[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al ejecutar obtenerNotificacionesPorUsuario: " . $stmt->error);
        }
        $stmt->close();
        return $notificaciones;
    }

    public function marcarComoLeida(int $id_notificacion): bool {
        $data = ['leido' => 1]; // 1 = leído
        // Usamos el método update de ModelBase
        return $this->update($id_notificacion, $data);
    }

    public function marcarTodasComoLeidas(int $id_usuario_destino): bool {
        $sql = "UPDATE " . $this->table . " SET leido = 1 WHERE id_usuario_destino = ? AND leido = 0";
        $stmt = $this->conn->prepare($sql);
    
        if ($stmt === false) {
            error_log("Error al preparar la consulta marcarTodasComoLeidas: " . $this->conn->error);
            return false;
        }
    
        $stmt->bind_param("i", $id_usuario_destino);
    
        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0 || $rows_affected === 0; // Retorna true incluso si no hay filas afectadas (significa que ya estaban leídas)
        } else {
            error_log("Error al ejecutar marcarTodasComoLeidas: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function eliminarNotificacion(int $id_notificacion): bool {
        // Usamos el método delete de ModelBase
        return $this->delete($id_notificacion);
    }

    public function getUnreadNotificationCount(int $id_usuario_destino): int {
        $sql = "SELECT COUNT(*) AS total_unread FROM " . $this->table . " WHERE id_usuario_destino = ? AND leido = 0";
        
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getUnreadNotificationCount: " . $this->conn->error);
            return 0;
        }

        $stmt->bind_param("i", $id_usuario_destino);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return (int) $row['total_unread'];
        }
        
        error_log("Error al ejecutar getUnreadNotificationCount: " . $stmt->error);
        $stmt->close();
        return 0;
    }

  public function getAllNotifications(
        array $order = ['column' => 'fecha_creacion', 'direction' => 'DESC']
    ) {
        $sql = "SELECT n.*, 
                       u_origen.nombre AS origen_nombre, 
                       u_origen.apellido AS origen_apellido,
                       u_destino.nombre AS destino_nombre,
                       u_destino.apellido AS destino_apellido
                FROM " . $this->table . " n
                LEFT JOIN usuarios u_origen ON n.id_usuario_origen = u_origen.id_usuario
                LEFT JOIN usuarios u_destino ON n.id_usuario_destino = u_destino.id_usuario"; // Unimos también por destino
        
        $params = [];
        $types = "";

        // Lógica de ordenamiento
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            // Validar columnas para evitar inyección SQL
            $valid_columns = ['fecha_creacion', 'tipo', 'leido']; 
            if (in_array($order_column, $valid_columns)) {
                $sql .= " ORDER BY n.$order_column $order_direction";
            }
        } else {
            $sql .= " ORDER BY n.fecha_creacion DESC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllNotifications: " . $this->conn->error);
            return [];
        }

        // Si hay parámetros (aunque en este caso no hay, se deja la estructura por si se extiende)
        if (!empty($params)) {
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $key => $value) {
                $refs[$key] = &$bind_names[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $notificaciones = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $notificaciones[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al ejecutar getAllNotifications: " . $stmt->error);
        }
        $stmt->close();
        return $notificaciones;
    }

    public function find(int $id): ?array {
        return parent::find($id); 
    }


}