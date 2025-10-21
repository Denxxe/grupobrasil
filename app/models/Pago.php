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
}