<?php
require_once 'ModelBase.php';

class Log extends ModelBase {
    
    public function __construct() {
        parent::__construct();
        $this->table = 'log_actividad'; // Ajusta el nombre de tu tabla de logs
        $this->primaryKey = 'id_log'; // Ajusta la llave primaria
    }

    /**
     * Obtiene los registros de actividad más recientes formateados para el dashboard.
     */
    public function getRecentActivity(int $limit = 10): array {
        // Asume columnas: 'mensaje', 'fecha', 'tipo'
        $sql = "SELECT mensaje, fecha, tipo FROM {$this->table} ORDER BY fecha DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $logs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = [
                    'icon' => $this->getIconByType($row['tipo']),
                    'color' => $this->getColorByType($row['tipo']),
                    'message' => $row['mensaje'],
                    'time' => $this->timeElapsedString($row['fecha']) // Usa una función auxiliar para el tiempo
                ];
            }
        }
        $stmt->close();
        return $logs;
    }
    
    // --- Funciones Auxiliares para el Log ---

    private function getIconByType(string $type): string {
        switch (strtolower($type)) {
            case 'pago_ok': return 'fas fa-hand-holding-usd';
            case 'usuario_creado': return 'fas fa-user-plus';
            case 'perfil_editado': return 'fas fa-user-edit';
            case 'error': return 'fas fa-exclamation-triangle';
            default: return 'fas fa-info-circle';
        }
    }

    private function getColorByType(string $type): string {
        switch (strtolower($type)) {
            case 'pago_ok': return 'text-yellow-500';
            case 'usuario_creado': return 'text-green-500';
            case 'error': return 'text-red-600';
            default: return 'text-gray-500';
        }
    }

    /**
     * Convierte una fecha/hora en una cadena de tiempo transcurrido (ej: "hace 5 min").
     */
    private function timeElapsedString(string $datetime, bool $full = false): string {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        // Se excluye 'w' de la lista para resolver el error de linter.
        $string = [
            'y' => 'año', 
            'm' => 'mes', 
            'd' => 'día', 
            'h' => 'hora', 
            'i' => 'minuto', 
            's' => 'segundo',
        ];

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                // Usamos directamente las propiedades base (y, m, d, h, i, s)
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        
        if (empty($string)) {
            return 'justo ahora';
        }
        
        return 'hace ' . implode(', ', $string);
    }
}
