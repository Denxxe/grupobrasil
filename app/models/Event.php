<?php
// grupobrasil/app/models/Event.php

require_once __DIR__ . '/ModelBase.php';

class Event extends ModelBase {
    protected $table = 'eventos';
    protected $primaryKey = 'id_evento';

    public function __construct() {
        parent::__construct();
    }

    public function getAllEvents() {
        return $this->getAll();
    }

    public function getEventById($id) {
        return $this->find($id);
    }

    /**
     * Toggle attendance for a user on an event. If the user is already marked, it removes the record; otherwise it inserts.
     * @param int $id_evento
     * @param int $id_usuario
     * @return array ['success' => bool, 'attending' => bool]
     */
    public function toggleAttendance($id_evento, $id_usuario) {
        // verificar si existe
        $sql = "SELECT id_asistencia FROM evento_asistentes WHERE id_evento = ? AND id_usuario = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) { error_log('toggleAttendance prepare error: ' . $this->conn->error); return ['success'=>false,'attending'=>false]; }
        $stmt->bind_param('ii', $id_evento, $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt->close();

        if ($exists) {
            // eliminar asistencia
            $sql = "DELETE FROM evento_asistentes WHERE id_evento = ? AND id_usuario = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) return ['success'=>false,'attending'=>true];
            $stmt->bind_param('ii', $id_evento, $id_usuario);
            $ok = $stmt->execute();
            $stmt->close();
            return ['success' => (bool)$ok, 'attending' => false];
        } else {
            $sql = "INSERT INTO evento_asistentes (id_evento, id_usuario, fecha_confirmacion) VALUES (?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) return ['success'=>false,'attending'=>false];
            $stmt->bind_param('ii', $id_evento, $id_usuario);
            $ok = $stmt->execute();
            $stmt->close();
            return ['success' => (bool)$ok, 'attending' => (bool)$ok];
        }
    }

    /**
     * Devuelve eventos en un rango (start/end) en formato listo para FullCalendar
     * start/end son strings en formato YYYY-MM-DD o YYYY-MM-DDTHH:MM:SS
     */
    public function getEventsBetween($start = null, $end = null) {
        $sql = "SELECT id_evento, titulo, descripcion, ubicacion, fecha, hora_inicio, hora_fin, categoria_edad, alcance, id_calle, creado_por FROM " . $this->table;
        $params = [];
        $where = [];

        if ($start) {
            $where[] = "fecha >= ?";
            $params[] = $start;
        }
        if ($end) {
            $where[] = "fecha <= ?";
            $params[] = $end;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY fecha ASC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Event::getEventsBetween prepare error: " . $this->conn->error);
            return [];
        }

        if (!empty($params)) {
            // bind params dynamically
            $types = '';
            foreach ($params as $p) { $types .= $this->getParamType($p); }
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $key => $value) { $refs[$key] = &$bind_names[$key]; }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $out = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Construir start/end ISO
                $startDt = $row['fecha'];
                if (!empty($row['hora_inicio'])) $startDt .= 'T' . substr($row['hora_inicio'],0,8);
                $endDt = null;
                if (!empty($row['hora_fin'])) $endDt = $row['fecha'] . 'T' . substr($row['hora_fin'],0,8);

                $out[] = [
                    'id' => (int)$row['id_evento'],
                    'title' => $row['titulo'],
                    'start' => $startDt,
                    'end' => $endDt,
                    'allDay' => empty($row['hora_inicio']),
                    'extendedProps' => [
                        'descripcion' => $row['descripcion'],
                        'ubicacion' => $row['ubicacion'],
                        'categoria_edad' => $row['categoria_edad'],
                        'alcance' => $row['alcance'],
                        'id_calle' => $row['id_calle'],
                        'creado_por' => $row['creado_por']
                    ]
                ];
            }
            $result->free();
        }
        $stmt->close();
        return $out;
    }

    // create / update / delete delegan a ModelBase
    public function createEvent(array $data) {
        return $this->create($data);
    }

    public function updateEvent($id, array $data) {
        return $this->update($id, $data);
    }

    public function deleteEvent($id) {
        return $this->delete($id);
    }

    // Métricas/indicadores
    public function countEventsByMonth($year) {
        $sql = "SELECT MONTH(fecha) as mes, COUNT(*) as total FROM " . $this->table . " WHERE YEAR(fecha) = ? GROUP BY MONTH(fecha)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return [];
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) { $out[(int)$r['mes']] = (int)$r['total']; }
        $stmt->close();
        return $out;
    }

    public function countEventsByYear() {
        $sql = "SELECT YEAR(fecha) as anio, COUNT(*) as total FROM " . $this->table . " GROUP BY YEAR(fecha)";
        $res = $this->conn->query($sql);
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) { $out[(int)$r['anio']] = (int)$r['total']; }
            $res->free();
        }
        return $out;
    }

    public function countEventsByCategory() {
        $sql = "SELECT categoria_edad, COUNT(*) as total FROM " . $this->table . " GROUP BY categoria_edad";
        $res = $this->conn->query($sql);
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) { $out[$r['categoria_edad']] = (int)$r['total']; }
            $res->free();
        }
        return $out;
    }
}
