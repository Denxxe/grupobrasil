<?php
// grupobrasil/app/models/NoticiaVisibilidad.php

require_once 'ModelBase.php';

class NoticiaVisibilidad extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'noticia_visibilidad';
        $this->primaryKey = 'id';
    }

    public function getVisibilityForNews(int $id_noticia): array {
        $sql = "SELECT id_calle, id_habitante, visible FROM " . $this->table . " WHERE id_noticia = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return [];
        $stmt->bind_param('i', $id_noticia);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = ['calles'=>[], 'habitantes'=>[]];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if (!empty($r['id_calle'])) $out['calles'][] = (int)$r['id_calle'];
                if (!empty($r['id_habitante'])) $out['habitantes'][] = (int)$r['id_habitante'];
            }
            $res->free();
        }
        $stmt->close();
        return $out;
    }

    public function clearVisibilityForNews(int $id_noticia) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_noticia = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('i', $id_noticia);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function assignVisibilityByCalle(int $id_noticia, int $id_calle) {
        $sql = "INSERT INTO " . $this->table . " (id_noticia, id_calle, visible) VALUES (?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('ii', $id_noticia, $id_calle);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function assignVisibilityByHabitante(int $id_noticia, int $id_habitante) {
        $sql = "INSERT INTO " . $this->table . " (id_noticia, id_habitante, visible) VALUES (?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('ii', $id_noticia, $id_habitante);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Verifica si un usuario (id_usuario) puede ver la noticia.
     * Internamente resuelve el id_habitante y su id_calle.
     */
    public function canUserSeeNews(int $id_noticia, int $id_usuario): bool {
        // Admin (rol 1) handled by controller; here we implement generic check
        // Resolvemos persona->habitante->id_calle
        $sql = "SELECT p.id_calle, h.id_habitante
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                LEFT JOIN habitante h ON h.id_persona = p.id_persona
                WHERE u.id_usuario = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('i', $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $id_calle = $row['id_calle'] ?? null;
        $id_habitante = $row['id_habitante'] ?? null;

        // Si no hay reglas de visibilidad para la noticia, devolvemos true (compatibilidad)
        $sql = "SELECT COUNT(*) as cnt FROM " . $this->table . " WHERE id_noticia = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return true;
        $stmt->bind_param('i', $id_noticia);
        $stmt->execute();
        $res = $stmt->get_result();
        $cnt = ($res && $r = $res->fetch_assoc()) ? (int)$r['cnt'] : 0;
        $stmt->close();
        if ($cnt === 0) return true; // no visibility rows => visible by default

        // Check explicit habitante
        if ($id_habitante) {
            $sql = "SELECT 1 FROM " . $this->table . " WHERE id_noticia = ? AND id_habitante = ? AND visible = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ii', $id_noticia, $id_habitante);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) { $stmt->close(); return true; }
                $stmt->close();
            }
        }

        // Check calle
        if ($id_calle) {
            $sql = "SELECT 1 FROM " . $this->table . " WHERE id_noticia = ? AND id_calle = ? AND visible = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ii', $id_noticia, $id_calle);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) { $stmt->close(); return true; }
                $stmt->close();
            }
        }

        return false;
    }
}
