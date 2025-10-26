<?php
// grupobrasil/app/models/Like.php

require_once 'ModelBase.php';

class Like extends ModelBase {

    public function __construct() {
        parent::__construct();
        $this->table = 'likes';
        $this->primaryKey = 'id_like';
    }

    public function darLike(int $id_noticia, int $id_usuario) {
        // Si la tabla no existe, evitar excepción y registrar el caso
        if (!$this->tableExists()) {
            error_log("Like::darLike - tabla '" . $this->table . "' no existe. Operación abortada.");
            return false;
        }

        // Primero, verifica si el usuario ya dio like a esta noticia para evitar duplicados
        if ($this->usuarioDioLike($id_noticia, $id_usuario)) {
            error_log("El usuario $id_usuario ya dio 'Me Gusta' a la noticia $id_noticia.");
            return false; // Ya existe un like de este usuario para esta noticia
        }

        $data = [
            'id_noticia' => $id_noticia,
            'id_usuario' => $id_usuario
            // fecha_like se establece automáticamente en la base de datos con CURRENT_TIMESTAMP
        ];

        // Usamos el método create de ModelBase
        $new_id = $this->create($data);

        if ($new_id === false) {
            error_log("Error al dar 'Me Gusta' en noticia $id_noticia por usuario $id_usuario.");
        }
        return $new_id;
    }

    public function quitarLike(int $id_noticia, int $id_usuario) {
        if (!$this->tableExists()) {
            error_log("Like::quitarLike - tabla '" . $this->table . "' no existe. Operación abortada.");
            return false;
        }
        $sql = "DELETE FROM " . $this->table . " WHERE id_noticia = ? AND id_usuario = ?";
        
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta quitarLike: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("ii", $id_noticia, $id_usuario); // 'ii' para dos enteros

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0; // True si se eliminó al menos una fila
        } else {
            error_log("Error al ejecutar quitarLike: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function contarLikesPorNoticia(int $id_noticia): int {
        if (!$this->tableExists()) {
            // Tabla no existe => asumimos 0 likes
            error_log("Like::contarLikesPorNoticia - tabla '" . $this->table . "' no existe. Devolviendo 0.");
            return 0;
        }
        $sql = "SELECT COUNT(*) AS total_likes FROM " . $this->table . " WHERE id_noticia = ?";
        
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta contarLikesPorNoticia: " . $this->conn->error);
            return 0;
        }

        $stmt->bind_param("i", $id_noticia);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return (int) $row['total_likes'];
        }
        
        error_log("Error al ejecutar contarLikesPorNoticia: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    public function usuarioDioLike(int $id_noticia, int $id_usuario): bool {
        if (!$this->tableExists()) {
            error_log("Like::usuarioDioLike - tabla '" . $this->table . "' no existe. Devolviendo false.");
            return false;
        }
        $sql = "SELECT COUNT(*) AS liked FROM " . $this->table . " WHERE id_noticia = ? AND id_usuario = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta usuarioDioLike: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("ii", $id_noticia, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return (bool) $row['liked'];
        }
        
        error_log("Error al ejecutar usuarioDioLike: " . $stmt->error);
        $stmt->close();
        return false;
    }

    // Añadimos método privado para verificar que la tabla exista y así evitar excepciones
    private function tableExists(): bool {
        $tbl = $this->conn->real_escape_string($this->table);
        $res = $this->conn->query("SHOW TABLES LIKE '" . $tbl . "'");
        return ($res && $res->num_rows > 0);
    }

}