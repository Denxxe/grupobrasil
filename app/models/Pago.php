<?php
require_once 'ModelBase.php';

class Pago extends ModelBase {
    
    public function __construct() {
        parent::__construct();
        $this->table = 'pagos'; // Tabla de pagos
        $this->primaryKey = 'id_pago'; 
    }

    /**
     * Registra un nuevo pago/entrega de beneficio.
     * @param array $data Debe incluir id_usuario, id_tipo_beneficio, id_periodo, monto, registrado_por_id
     * @return int|bool El ID del nuevo pago o false si falla.
     */
    public function registrarPago(array $data) {
        // Validación básica de campos requeridos
        if (empty($data['id_usuario']) || empty($data['id_tipo_beneficio']) || empty($data['id_periodo']) || !isset($data['monto'])) {
            error_log("Faltan datos requeridos para registrar pago.");
            return false;
        }
        
        // Se establece el estado por defecto
        $data['estado'] = $data['estado'] ?? 'procesado';
        
        // Usamos el método create de ModelBase
        return $this->create($data);
    }
    
    /**
     * Suma el monto total de los pagos registrados hoy, filtrado opcionalmente por beneficio.
     * @param int|null $id_tipo_beneficio Opcional, para sumar solo un tipo de beneficio.
     */
    public function sumPaymentsToday(?int $id_tipo_beneficio = null): float {
        $today = date('Y-m-d') . ' 00:00:00';
        $sql = "SELECT SUM(monto) FROM {$this->table} WHERE fecha_pago >= ?";
        $params = [$today];
        $types = "s";
        
        if ($id_tipo_beneficio !== null) {
            $sql .= " AND id_tipo_beneficio = ?";
            $params[] = $id_tipo_beneficio;
            $types .= "i";
        }
        
        // Usar call_user_func_array para bind_param (como en tu modelo de Notificación)
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return 0.0;
        
        if (!empty($params)) {
            $bind_names = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_names as $key => $value) { $refs[$key] = &$bind_names[$key]; }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_row()) {
            $stmt->close();
            return (float) $row[0];
        }
        $stmt->close();
        return 0.0;
    }

    /**
     * Calcula la variación porcentual de pagos de hoy vs. el día anterior.
     * (La lógica sigue siendo la misma, ya que opera sobre las sumas totales).
     */
    public function getPaymentChangeVsYesterday(): float {
        $todayStart = date('Y-m-d', strtotime('today')) . ' 00:00:00';
        $yesterdayStart = date('Y-m-d', strtotime('yesterday')) . ' 00:00:00';
        $yesterdayEnd = date('Y-m-d', strtotime('today')) . ' 00:00:00';

        $sql = "
            SELECT 
                SUM(CASE WHEN fecha_pago >= ? THEN monto ELSE 0 END) AS today_sum,
                SUM(CASE WHEN fecha_pago >= ? AND fecha_pago < ? THEN monto ELSE 0 END) AS yesterday_sum
            FROM {$this->table}
            WHERE estado = 'procesado'"; // Solo pagos procesados

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $todayStart, $yesterdayStart, $yesterdayEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result && $row = $result->fetch_assoc()) {
            $todaySum = (float) $row['today_sum'];
            $yesterdaySum = (float) $row['yesterday_sum'];

            if ($yesterdaySum == 0) {
                return $todaySum > 0 ? 100.0 : 0.0;
            }

            return (($todaySum - $yesterdaySum) / $yesterdaySum) * 100.0;
        }

        return 0.0;
    }

    /**
     * Inserta un pago enviado por un jefe de familia.
     * @param array $data Campos esperados: id_usuario, id_tipo_beneficio, id_periodo, monto, metodo_pago, referencia_pago, id_habitante, id_vivienda, registrado_por_id
     * @return int|false id_pago o false
     */
    public function submitPago(array $data) {
        // Campos mínimos
        if (empty($data['id_usuario']) || empty($data['id_tipo_beneficio']) || empty($data['id_periodo'])) {
            error_log('submitPago: faltan campos');
            return false;
        }

        // Normalizar estado inicial
        $data['estado'] = $data['estado'] ?? 'en_espera';
        $data['fecha_envio'] = $data['fecha_envio'] ?? date('Y-m-d H:i:s');

        // Insert usando create del modelo base
        $id = $this->create($data);
        return $id;
    }

    public function addEvidence(int $id_pago, string $ruta, ?string $mime, ?int $size, ?int $creado_por = null) {
        $sql = "INSERT INTO pagos_evidencias (id_pago, ruta, mime, tamano, creado_por) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('issii', $id_pago, $ruta, $mime, $size, $creado_por);
        $ok = $stmt->execute();
        if ($ok) {
            $insertId = $this->conn->insert_id;
            $stmt->close();
            return $insertId;
        }
        $stmt->close();
        return false;
    }

    public function verifyPago(int $id_pago, string $nuevo_estado, int $verificado_por, ?string $comentario = null) {
        // obtener estado anterior
        $stmt = $this->conn->prepare("SELECT estado_actual FROM pagos WHERE id_pago = ? LIMIT 1");
        $stmt->bind_param('i', $id_pago);
        $stmt->execute();
        $res = $stmt->get_result();
        $estado_anterior = null;
        if ($res && $row = $res->fetch_assoc()) $estado_anterior = $row['estado_actual'];
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE pagos SET estado_actual = ?, verificado_por = ?, fecha_verificacion = ?, comentario_rechazo = ? WHERE id_pago = ?");
        $fecha = date('Y-m-d H:i:s');
        $stmt->bind_param('sissi', $nuevo_estado, $verificado_por, $fecha, $comentario, $id_pago);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            // insert log
            $stmt2 = $this->conn->prepare("INSERT INTO pagos_estado_log (id_pago, estado_anterior, estado_nuevo, id_usuario, comentario) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param('issis', $id_pago, $estado_anterior, $nuevo_estado, $verificado_por, $comentario);
            $stmt2->execute();
            $stmt2->close();
            return true;
        }
        return false;
    }

    public function getPagosPorVereda(int $id_calle) {
        $sql = "SELECT p.*, per.cedula, per.nombres, per.apellidos, v.numero AS numero_vivienda, c.nombre AS vereda
                FROM pagos p
                LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
                LEFT JOIN habitante h ON u.id_persona = h.id_persona
                LEFT JOIN persona per ON h.id_persona = per.id_persona
                LEFT JOIN vivienda v ON p.id_vivienda = v.id_vivienda
                LEFT JOIN calle c ON v.id_calle = c.id_calle
                WHERE v.id_calle = ? ORDER BY p.fecha_envio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_calle);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    public function getPagosPorHabitante(int $id_habitante) {
        $sql = "SELECT p.*, pp.nombre_periodo FROM pagos p LEFT JOIN pagos_periodos pp ON p.id_periodo = pp.id_periodo WHERE p.id_habitante = ? ORDER BY p.fecha_envio DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_habitante);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }
}