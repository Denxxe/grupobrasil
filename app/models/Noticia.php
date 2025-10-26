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
     * Obtiene todas las noticias, incluyendo el nombre del usuario que las publicó.
     */
    public function getAllNews(bool $onlyActive = true, array $order = ['column' => 'fecha_publicacion', 'direction' => 'DESC']) {
    
        // CORRECCIÓN 1: Usamos 'usuarios' (asumo plural) y 'u.nombre' (la columna que creaste)
        $sql = "SELECT n.id_noticia, n.titulo, n.contenido, n.imagen_principal, n.fecha_publicacion, 
                     n.id_usuario, n.id_categoria, n.estado, 
                     u.username AS nombre_usuario  
                 FROM " . $this->table . " n
                 JOIN usuario u ON n.id_usuario = u.id_usuario";

        $where_clauses = [];
        $params = [];
        $types = "";

        if ($onlyActive) {
            // Filtra por estado = 'publicado'
            $where_clauses[] = "n.estado = ?"; // Especificamos la tabla 'n' por si acaso
            $params[] = 'publicado';
            $types .= "s"; // Tipo 's' para string ('publicado')
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        if (!empty($order) && isset($order['column']) && isset($order['direction'])) {
            $order_column = $order['column'];
            $order_direction = strtoupper($order['direction']);

            $valid_columns = ['fecha_publicacion', 'titulo', 'id_noticia', 'id_categoria'];
            if (in_array($order_column, $valid_columns)) {
                // Aseguramos que la columna de ordenamiento pertenece a la tabla de noticias (n)
                $sql .= " ORDER BY n.$order_column $order_direction"; 
            } else {
                $sql .= " ORDER BY n.fecha_publicacion DESC"; // Orden por defecto
            }
        } else {
            $sql .= " ORDER BY n.fecha_publicacion DESC";
        }

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllNews: " . $this->conn->error);
            return [];
        }

        if (!empty($params)) {
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
     * Obtiene una noticia por su ID, incluyendo el nombre del usuario.
     */
    public function getNewsById($id, bool $onlyActive = true) {
        // CORRECCIÓN 2: Incluimos el JOIN para obtener el nombre de usuario
        $sql = "SELECT n.*, u.username AS nombre_usuario
                 FROM " . $this->table . " n
                 JOIN usuario u ON n.id_usuario = u.id_usuario
                 WHERE n.id_noticia = ?";

        $types = "i";
        $params = [$id];

        if ($onlyActive) {
            // Filtra por estado = 'publicado'
            $sql .= " AND n.estado = ?";
            $params[] = 'publicado';
            $types .= "s";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta getNewsById: " . $this->conn->error);
            return false;
        }

        $this->bindParams($stmt, $types, $params);

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
     */
    public function createNews(array $data) {
        $titulo = $data['titulo'] ?? null;
        $contenido = $data['contenido'] ?? null;
        $imagen_principal = $data['imagen_principal'] ?? null;
        $id_usuario = $data['id_usuario'] ?? null; // Debe venir del controlador
        $id_categoria = $data['id_categoria'] ?? null;
        $estado_value = ($data['estado'] ?? 'borrador'); // Por defecto 'borrador'

        if (is_null($id_usuario)) {
            error_log("Fallo de integridad: id_usuario es NULL en createNews.");
            return false; 
        }

    // Generar slug único a partir del título para evitar errores por constraint UNIQUE
    $slug = $this->generateUniqueSlug($titulo ?? '');

    $sql = "INSERT INTO " . $this->table . " (titulo, contenido, imagen_principal, slug, id_usuario, id_categoria, estado, fecha_publicacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta createNews: " . $this->conn->error);
            return false;
        }

    // bind_param: ssss i i s (titulo, contenido, imagen_principal, slug, id_usuario, id_categoria, estado_value)
    $stmt->bind_param("ssssiis", $titulo, $contenido, $imagen_principal, $slug, $id_usuario, $id_categoria, $estado_value);

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
     * Genera un slug amigable y lo hace único en la tabla 'noticias'.
     */
    private function generateUniqueSlug(string $title): string {
        // Slug básico: translit + lowercase + reemplazo de no-alfa-num por guiones
        $slug = $title;
        if (function_exists('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        }
        $slug = preg_replace('/[^a-zA-Z0-9\s-_]/', '', $slug);
        $slug = preg_replace('/[\s-_]+/', '-', $slug);
        $slug = strtolower(trim($slug, " -_"));

        if (empty($slug)) {
            $slug = 'noticia-' . time();
        }

        $base = $slug;
        $i = 1;
        // Comprobar existencia en BD
        while (true) {
            $sql = "SELECT COUNT(*) as cnt FROM " . $this->table . " WHERE slug = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) break;
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $count = $row ? (int)$row['cnt'] : 0;
            $stmt->close();
            if ($count === 0) break;
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Actualiza una noticia existente en la base de datos.
     */
    public function updateNews(int $id, array $data) {
        if (empty($data)) {
            return false;
        }

        $set_clauses = [];
        $params = [];
        $types = "";

        // Aseguramos la lista de campos permitidos y sus tipos
        $field_types = [
            'titulo' => 's',
            'contenido' => 's',
            'imagen_principal' => 's',
            'id_usuario' => 'i', 
            'id_categoria' => 'i',
            'estado' => 's'
        ];
        
        // Maneja la posible traducción de un flag 'activo' (si viniera del formulario) a 'estado'
        if (isset($data['activo'])) {
            $data['estado'] = $data['activo'] ? 'publicado' : 'borrador';
            unset($data['activo']);
        }
        
        // Si no se envió 'estado', y la vista usa 'estado', no es necesario este bloque:
        /*
        if (isset($data['estado_select'])) {
            $data['estado'] = $data['estado_select'];
            unset($data['estado_select']);
        }
        */

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $field_types)) {
                $set_clauses[] = "$field = ?";
                $params[] = $value;
                $types .= $field_types[$field];
            }
        }

        if (empty($set_clauses)) {
            return false;
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $set_clauses) . " WHERE " . $this->primaryKey . " = ?";
        $types .= "i";
        $params[] = $id;

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error al preparar la consulta updateNews: " . $this->conn->error);
            return false;
        }

        $this->bindParams($stmt, $types, $params);

        if ($stmt->execute()) {
            $rows_affected = $stmt->affected_rows;
            $stmt->close();
            return $rows_affected > 0;
        } else {
            error_log("Error al ejecutar updateNews: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Realiza una "soft delete" (desactivación) de una noticia.
     */
    public function softDeleteNews(int $id_noticia) {
        $data = ['estado' => 'borrador']; 
        return $this->updateNews($id_noticia, $data);
    }
    
    /**
     * Elimina físicamente una noticia de la base de datos.
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
            return $rows_affected > 0;
        }

        error_log("Error al ejecutar deleteNews (física): " . $stmt->error);
        $stmt->close();
        return false;
    }

    /**
     * Obtiene el número total de noticias.
     */
    public function getTotalNoticias(bool $onlyActive = false) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table;
        $where_clauses = [];
        $params = [];
        $types = "";

        if ($onlyActive) {
            $where_clauses[] = "estado = ?";
            $params[] = 'publicado';
            $types .= "s";
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
            $this->bindParams($stmt, $types, $params);
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
        return 0;
    }

 public function checkIfDataMatches(int $id_noticia, array $new_data): bool {
    // 1. Obtener la noticia actual desde la DB (sin formateo)
    $current_news = $this->getNewsById($id_noticia, false); // Asumiendo que getNewsById existe

    if (!$current_news) {
        return false; // Noticia no existe
    }

    // 2. Limpiar/formatear los datos actuales para comparar
    // Es CRÍTICO que los índices y tipos coincidan con $new_data
    $data_to_compare = [
        'titulo' => $current_news['titulo'],
        'contenido' => $current_news['contenido'],
        'id_categoria' => (int)$current_news['id_categoria'],
        'id_usuario' => (int)$current_news['id_usuario'],
        'estado' => $current_news['estado'],
        'imagen_principal' => $current_news['imagen_principal'],
    ];

    // 3. Comparar valores
    foreach ($new_data as $key => $value) {
        // Ignoramos la comparación si la clave no existe en los datos actuales, aunque no debería pasar.
        if (!isset($data_to_compare[$key])) continue;

        // Nota: Se usa == en lugar de === para permitir la comparación de "1" (string) con 1 (int)
        if ($data_to_compare[$key] != $value) {
            return false; // Se encontró una diferencia, la data NO coincide
        }
    }

    return true; // Todos los campos coinciden
}

    protected function bindParams(mysqli_stmt $stmt, string $types, array $params) {
        $bind_names = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_names as $key => $value) {
            $refs[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    public function countPendingReview(): int {
    // Asume que la columna de estado es 'estado' y el valor de pendiente es 'pendiente'
    $sql = "SELECT COUNT(*) FROM {$this->table} WHERE estado = 'pendiente'";
    
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
}
