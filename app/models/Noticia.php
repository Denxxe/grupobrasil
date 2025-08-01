<?php
// grupobrasil/app/models/Noticia.php

require_once 'ModelBase.php';

class Noticia extends ModelBase {

    public function __construct() {
        parent::__construct();
        $this->table = 'noticias';
        $this->primaryKey = 'id_noticia';
    }

    /**
     * Obtiene todas las noticias.
     * @param bool $onlyActive Si es true, solo retorna noticias activas.
     * @param array $order Define la columna y dirección de ordenación.
     * @return array Un array de arrays asociativos de noticias.
     */
    public function getAllNews(bool $onlyActive = true, array $order = ['column' => 'fecha_publicacion', 'direction' => 'DESC']) {
        $sql = "SELECT id_noticia, titulo, contenido, imagen_principal, fecha_publicacion, id_usuario_publicador, id_categoria, activo 
                FROM " . $this->table; // Añadir id_categoria para getAllNews

        $where_clauses = [];
        $params = [];
        $types = "";

        if ($onlyActive) {
            $where_clauses[] = "activo = ?";
            $params[] = 1;
            $types .= "i";
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            // Añadir 'id_categoria' a las columnas válidas si se necesita ordenar por ella.
            $valid_columns = ['fecha_publicacion', 'titulo', 'id_noticia', 'id_categoria'];
            if (in_array($order_column, $valid_columns)) {
                $sql .= " ORDER BY $order_column $order_direction";
            } else {
                $sql .= " ORDER BY fecha_publicacion DESC"; // Orden por defecto si la columna no es válida
            }
        } else {
            $sql .= " ORDER BY fecha_publicacion DESC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllNews: " . $this->conn->error);
            return [];
        }

        if (!empty($params)) {
            // Unpack params for bind_param using a helper function if you don't have it in ModelBase
            $this->bindParams($stmt, $types, $params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $news = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $news[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al ejecutar getAllNews: " . $stmt->error);
        }
        $stmt->close();
        return $news;
    }

    /**
     * Obtiene una noticia por su ID.
     * @param int $id El ID de la noticia.
     * @param bool $onlyActive Si es true, solo busca noticias activas.
     * @return array|false Un array asociativo de la noticia o false si no se encuentra.
     */
    public function getNewsById($id, bool $onlyActive = true) {
        $sql = "SELECT id_noticia, titulo, contenido, imagen_principal, fecha_publicacion, id_usuario_publicador, id_categoria, activo
                FROM " . $this->table . "
                WHERE id_noticia = ?";

        $types = "i";
        $params = [$id];

        if ($onlyActive) {
            $sql .= " AND activo = ?";
            $params[] = 1;
            $types .= "i";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getNewsById: " . $this->conn->error);
            return false;
        }

        $this->bindParams($stmt, $types, $params); // Usar la función helper

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $news_data = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return $news_data;
        }

        error_log("Noticia con ID $id no encontrada o inactiva (getNewsById): " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Crea una nueva noticia en la base de datos.
     * @param array $data Los datos de la noticia a crear.
     * @return int|false El ID de la nueva noticia o false en caso de error.
     */
    public function createNews(array $data) {
        $titulo = $data['titulo'] ?? null;
        $contenido = $data['contenido'] ?? null;
        $imagen_principal = $data['imagen_principal'] ?? null;
        $id_usuario_publicador = $data['id_usuario_publicador'] ?? null;
        $id_categoria = $data['id_categoria'] ?? null;
        $activo = $data['activo'] ?? 1;

        $sql = "INSERT INTO " . $this->table . " (titulo, contenido, imagen_principal, id_usuario_publicador, id_categoria, activo, fecha_publicacion)
                 VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta createNews: " . $this->conn->error);
            return false;
        }

        // Los tipos de bind_param deben coincidir con los tipos de columna en la DB:
        // s: string, i: integer, d: double, b: blob
        // Suponiendo: titulo(s), contenido(s), imagen_principal(s), id_usuario_publicador(i), id_categoria(i), activo(i)
        $stmt->bind_param("sssiii", $titulo, $contenido, $imagen_principal, $id_usuario_publicador, $id_categoria, $activo);

        if ($stmt->execute()) {
            $new_id = $this->conn->insert_id;
            $stmt->close();
            return $new_id;
        } else {
            error_log("Error al ejecutar createNews: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Actualiza una noticia existente en la base de datos.
     * @param int $id El ID de la noticia a actualizar.
     * @param array $data Los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false de lo contrario.
     */
    public function updateNews(int $id, array $data) {
        if (empty($data)) {
            return false;
        }

        $set_clauses = [];
        $params = [];
        $types = "";

        // Definir los tipos de datos esperados para cada campo.
        // Asegúrate de incluir 'id_categoria' aquí.
        $field_types = [
            'titulo' => 's',
            'contenido' => 's',
            'imagen_principal' => 's',
            'id_usuario_publicador' => 'i',
            'id_categoria' => 'i', // Agregado
            'activo' => 'i'
        ];

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $field_types)) {
                $set_clauses[] = "$field = ?";
                $params[] = $value;
                $types .= $field_types[$field];
            }
        }

        if (empty($set_clauses)) {
            return false; // No hay campos válidos para actualizar
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= "i"; // El tipo para el id_noticia
        $params[] = $id; // Añadir el ID al final de los parámetros

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta updateNews: " . $this->conn->error);
            return false;
        }

        $this->bindParams($stmt, $types, $params); // Usar la función helper

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0; // Retorna true si al menos una fila fue afectada (o ya tenía los mismos datos)
        } else {
            error_log("Error al ejecutar updateNews: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Realiza una "soft delete" (desactivación) de una noticia.
     * @param int $id_noticia El ID de la noticia a desactivar.
     * @return bool True si la operación fue exitosa, false de lo contrario.
     */
    public function softDeleteNews(int $id_noticia) {
        $data = ['activo' => 0]; // Marcamos la noticia como inactiva
        return $this->updateNews($id_noticia, $data);
    }

    /**
     * Elimina físicamente una noticia de la base de datos.
     * @param int $id_noticia El ID de la noticia a eliminar.
     * @return bool True si la eliminación fue exitosa, false de lo contrario.
     */
    public function deleteNews(int $id_noticia) {
        $query = "DELETE FROM " . $this->table . " WHERE " . $this->primaryKey . " = ?";

        $stmt = $this->conn->prepare($query);

        if ($stmt === false) {
            error_log("Error al preparar la consulta deleteNews (física): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $id_noticia);

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0; // Retorna true si al menos una fila fue afectada
        }

        error_log("Error al ejecutar deleteNews (física): " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Obtiene el número total de noticias.
     * @param bool $onlyActive Si es true, solo cuenta noticias activas.
     * @return int El número total de noticias o 0 en caso de error.
     */
    public function getTotalNoticias(bool $onlyActive = false) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table;
        $where_clauses = [];
        $params = [];
        $types = "";

        if ($onlyActive) {
            $where_clauses[] = "activo = ?";
            $params[] = 1;
            $types .= "i";
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getTotalNoticias: " . $this->conn->error);
            return 0;
        }

        if (!empty($params)) {
            $this->bindParams($stmt, $types, $params); // Usar la función helper
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();
            $result->free();
            $stmt->close();
            return $row['total'];
        }

        error_log("Error al obtener el total de noticias: " . $stmt->error);
        $stmt->close();
        return 0; // En caso de error
    }

    protected function bindParams(mysqli_stmt $stmt, string $types, array $params) {
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}