<?php
// grupobrasil/app/models/Comentario.php

require_once 'ModelBase.php';

class Comentario extends ModelBase {

    public function __construct() {
        parent::__construct();
        $this->table = 'comentarios';
        $this->primaryKey = 'id_comentario';
    }

    /**
     * Agrega un nuevo comentario a una noticia.
     * @param array $data Array asociativo con los datos del comentario (id_noticia, id_usuario, contenido).
     * @return int|false El ID del nuevo comentario insertado o false en caso de error.
     */
    public function agregarComentario(array $data) {
        $id_noticia = $data['id_noticia'] ?? null;
        $id_usuario = $data['id_usuario'] ?? null;
        $contenido = $data['contenido'] ?? null;
        $activo = $data['activo'] ?? 1; // Por defecto activo al crearse

        if (!$id_noticia || !$id_usuario || !$contenido) {
            error_log("Error al agregar comentario: Datos incompletos.");
            return false;
        }

        $sql = "INSERT INTO " . $this->table . " (id_noticia, id_usuario, contenido, activo, fecha_comentario) 
                  VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta agregarComentario: " . $this->conn->error);
            return false;
        }

        // 'iisi' significa: i=id_noticia (int), i=id_usuario (int), s=contenido (string), i=activo (int)
        $stmt->bind_param("iisi", $id_noticia, $id_usuario, $contenido, $activo);

        if ($stmt->execute()) {
            $new_id = $this->conn->insert_id;
            $stmt->close();
            return $new_id;
        } else {
            error_log("Error al ejecutar agregarComentario: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Obtiene todos los comentarios para una noticia específica.
     * Incluye información básica del usuario que hizo el comentario.
     *
     * @param int $id_noticia El ID de la noticia.
     * @param bool $onlyActive Si es true, solo devuelve comentarios activos. Por defecto es true.
     * @param array $order Array de ordenamiento (ej: ['column' => 'fecha_comentario', 'direction' => 'DESC']).
     * @return array Un array de comentarios.
     */
    public function obtenerComentariosPorNoticia(int $id_noticia, bool $onlyActive = true, array $order = ['column' => 'fecha_comentario', 'direction' => 'ASC']) {
        $sql = "SELECT c.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.foto_perfil
                FROM " . $this->table . " c
                JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_noticia = ?";
        
        $params = [$id_noticia];
        $types = "i";

        if ($onlyActive) {
            $sql .= " AND c.activo = ?";
            $params[] = 1;
            $types .= "i";
        }

        // Lógica de ordenamiento
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            // Validar columnas para evitar inyección SQL
            $valid_columns = ['fecha_comentario', 'contenido']; 
            if (in_array($order_column, $valid_columns)) {
                $sql .= " ORDER BY c.$order_column $order_direction";
            }
        } else {
            $sql .= " ORDER BY c.fecha_comentario ASC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta obtenerComentariosPorNoticia: " . $this->conn->error);
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

        $comentarios = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $comentarios[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al ejecutar obtenerComentariosPorNoticia: " . $stmt->error);
        }
        $stmt->close();
        return $comentarios;
    }

    /**
     * Realiza una eliminación lógica de un comentario (lo marca como inactivo).
     * Esto es útil para los sub-administradores que pueden "eliminar" comentarios ofensivos.
     * @param int $id_comentario El ID del comentario a eliminar lógicamente.
     * @return bool True si la eliminación lógica fue exitosa, false en caso contrario.
     */
    public function softDeleteComentario(int $id_comentario) {
        $data = ['activo' => 0]; // Marcamos el comentario como inactivo
        return $this->update($id_comentario, $data); // Usamos el método update de ModelBase
    }

    /**
     * Elimina físicamente un comentario de la base de datos.
     * Este método solo debe ser usado por el administrador principal si es necesario.
     * @param int $id_comentario El ID del comentario a eliminar físicamente.
     * @return bool True si la eliminación física fue exitosa, false en caso contrario.
     */
    public function deleteComentario(int $id_comentario) {
        // Usamos el método delete de ModelBase
        return $this->delete($id_comentario);
    }

    /**
     * Obtiene un comentario por su ID.
     * @param int $id_comentario ID del comentario a buscar.
     * @param bool $onlyActive Si es true, solo devuelve comentarios activos. Por defecto es false.
     * @return array|false Un array asociativo con los datos del comentario o false si no se encuentra.
     */
    public function getComentarioById(int $id_comentario, bool $onlyActive = false) {
        // Puedes usar el método getById de ModelBase
        // Sin embargo, para incluir el filtro 'activo' y uniones, es mejor un método específico
        $sql = "SELECT c.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.foto_perfil
                FROM " . $this->table . " c
                JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c." . $this->primaryKey . " = ?";
        
        $params = [$id_comentario];
        $types = "i";

        if ($onlyActive) {
            $sql .= " AND c.activo = ?";
            $params[] = 1;
            $types .= "i";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error al preparar la consulta getComentarioById: " . $this->conn->error);
            return false;
        }

        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $comentario_data = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return $comentario_data;
        }
        error_log("Comentario con ID $id_comentario no encontrado, inactivo o error: " . $stmt->error);
        $stmt->close();
        return false;
    }
}