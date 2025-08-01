<?php
// grupobrasil/app/models/Like.php

require_once 'ModelBase.php';

class Like extends ModelBase {

    public function __construct() {
        parent::__construct();
        $this->table = 'likes';
        $this->primaryKey = 'id_like';
    }

    /**
     * Registra un "Me Gusta" de un usuario a una noticia.
     * Se asegura de que un usuario no pueda dar más de un like por noticia gracias a la clave UNIQUE en la DB.
     *
     * @param int $id_noticia El ID de la noticia a la que se le dará "Me Gusta".
     * @param int $id_usuario El ID del usuario que da el "Me Gusta".
     * @return int|false El ID del nuevo like si fue exitoso, o false si ya existía o hubo un error.
     */
    public function darLike(int $id_noticia, int $id_usuario) {
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

    /**
     * Elimina un "Me Gusta" de un usuario a una noticia (deshacer el like).
     *
     * @param int $id_noticia El ID de la noticia.
     * @param int $id_usuario El ID del usuario.
     * @return bool True si el like fue eliminado, false en caso contrario.
     */
    public function quitarLike(int $id_noticia, int $id_usuario) {
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

    /**
     * Cuenta el número total de "Me Gusta" para una noticia específica.
     *
     * @param int $id_noticia El ID de la noticia.
     * @return int El número total de likes para esa noticia.
     */
    public function contarLikesPorNoticia(int $id_noticia): int {
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

    /**
     * Verifica si un usuario específico ya dio "Me Gusta" a una noticia.
     *
     * @param int $id_noticia El ID de la noticia.
     * @param int $id_usuario El ID del usuario.
     * @return bool True si el usuario ya dio like, false en caso contrario.
     */
    public function usuarioDioLike(int $id_noticia, int $id_usuario): bool {
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
}