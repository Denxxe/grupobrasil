<?php
require_once __DIR__ . '/../models/PagosPeriodos.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Notificacion.php';

class PagoController {
    protected $periodoModel;
    protected $pagoModel;
    protected $notificacionModel;

    public function __construct() {
        $this->periodoModel = new PagosPeriodos();
        $this->pagoModel = new Pago();
        $this->notificacionModel = new Notificacion();
    }

    // --- ADMIN: lista de periodos (activos + historial)
    public function adminPeriodos($id = null) {
        $activos = $this->periodoModel->getActivos();
        $historial = $this->periodoModel->getHistorial();
        return ['view' => 'admin/pagos/periodos', 'data' => ['page_title' => 'Periodos de Pago', 'activos' => $activos, 'historial' => $historial]];
    }

    public function adminCrearPeriodo($id = null) {
        return ['view' => 'admin/pagos/crear', 'data' => ['page_title' => 'Crear Periodo']];
    }

    public function adminStorePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return null;
        }
        $nombre = trim($_POST['nombre_periodo'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_limite = $_POST['fecha_limite'] ?? null;
        $monto = $_POST['monto'] ?? null;
        $id_tipo_beneficio = $_POST['id_tipo_beneficio'] ?? null;
        $instrucciones = $_POST['instrucciones_pago'] ?? null;
        $creado_por = $_SESSION['id_usuario'] ?? null;

        $data = [
            'nombre_periodo' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_limite' => $fecha_limite,
            'monto' => $monto,
            'id_tipo_beneficio' => $id_tipo_beneficio,
            'instrucciones_pago' => $instrucciones,
            'creado_por' => $creado_por
        ];

        $id_periodo = $this->periodoModel->createPeriodo($data);
        if ($id_periodo) {
            // Notificar a jefes de familia - obtener usuarios con rol 3
            $msg = "Se abrió el periodo $nombre. Fecha límite: $fecha_limite.";
            require_once __DIR__ . '/../models/Usuario.php';
            $uModel = new Usuario();
            $jefes = $uModel->getAllFiltered(['id_rol' => 3]);
            foreach ($jefes as $jf) {
                $this->notificacionModel->crearNotificacion($jf['id_usuario'], $_SESSION['id_usuario'] ?? null, 'periodo_abierto', $msg, $id_periodo);
            }
            $_SESSION['success_message'] = 'Periodo creado correctamente.';
        } else {
            $_SESSION['error_message'] = 'Ocurrió un error al crear el periodo.';
        }

        header('Location: ./index.php?route=admin/pagos/periodos');
        return null;
    }

    public function adminClosePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return null;
        }
        $id_periodo = intval($_POST['id_periodo'] ?? 0);
        if ($id_periodo > 0 && $this->periodoModel->closePeriodo($id_periodo)) {
            $_SESSION['success_message'] = 'Periodo cerrado.';
        } else {
            $_SESSION['error_message'] = 'No se pudo cerrar el periodo.';
        }
        header('Location: ./index.php?route=admin/pagos/periodos');
        return null;
    }

    // --- USER: ver periodos y detalle + submit
    public function userIndexPeriodos($id = null) {
        $activos = $this->periodoModel->getActivos();
        return ['view' => 'user/pagos/index', 'data' => ['page_title' => 'Pagos Disponibles', 'activos' => $activos]];
    }

    public function userDetallePeriodo($id = null) {
        $id_periodo = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id_periodo) {
            $_SESSION['error_message'] = 'Periodo no especificado.';
            header('Location: ./index.php?route=user/pagos');
            return null;
        }
        $periodo = $this->periodoModel->getById($id_periodo);
        return ['view' => 'user/pagos/detalle', 'data' => ['page_title' => 'Detalle Periodo', 'periodo' => $periodo]];
    }

    public function userSubmitPago($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            return null;
        }
        // Asumir autenticado
        $userId = $_SESSION['id_usuario'] ?? null;
        // Intentar resolver id_habitante por persona -> habitante
        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $usuario = $uModel->getById($userId);
        $id_habitante = null;
            if (!empty($usuario['id_persona'])) {
            require_once __DIR__ . '/../models/Habitante.php';
            $hModel = new Habitante();
            $hab = $hModel->findByPersonaId($usuario['id_persona']);
            if ($hab) $id_habitante = $hab['id_habitante'];
        }

        // Validaciones básicas
        $id_periodo = intval($_POST['id_periodo'] ?? 0);
        $metodo = $_POST['metodo_pago'] ?? 'transferencia';
        $referencia = trim($_POST['referencia_pago'] ?? '');
        $id_vivienda = intval($_POST['id_vivienda'] ?? 0) ?: null;

        if (!$id_periodo || !$userId || !$id_habitante) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos o permisos insuficientes']);
            return null;
        }

        $data = [
            'id_usuario' => $userId,
            'id_tipo_beneficio' => $_POST['id_tipo_beneficio'] ?? null,
            'id_periodo' => $id_periodo,
            'monto' => $_POST['monto'] ?? null,
            'metodo_pago' => $metodo,
            'referencia_pago' => $referencia,
            'id_habitante' => $id_habitante,
            'id_vivienda' => $id_vivienda,
            'registrado_por_id' => $userId,
            'estado_actual' => 'en_espera'
        ];

        $id_pago = $this->pagoModel->submitPago($data);
        if (!$id_pago) {
            echo json_encode(['ok' => false, 'message' => 'No se pudo registrar el pago']);
            return null;
        }

        // Procesar archivos
        $upload_base = __DIR__ . '/../../public/uploads/pagos/' . $id_periodo . '/' . $id_pago . '/';
        if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
        $saved = [];
        if (!empty($_FILES['captura'])) {
            $files = $_FILES['captura'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $orig = basename($files['name'][$i]);
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $upload_base . $name;
                if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                    $rel = 'uploads/pagos/' . $id_periodo . '/' . $id_pago . '/' . $name;
                    $this->pagoModel->addEvidence($id_pago, $rel, $files['type'][$i], $files['size'][$i], $userId);
                    $saved[] = $rel;
                }
            }
        }

        // Notificar a líderes de la vereda de la vivienda si es posible
        if ($id_vivienda) {
            require_once __DIR__ . '/../models/Vivienda.php';
            $vModel = new Vivienda();
            $v = $vModel->getById($id_vivienda);
            if ($v && !empty($v['id_calle'])) {
                // obtener líderes de la calle
                require_once __DIR__ . '/../models/LiderCalle.php';
                $lcModel = new LiderCalle();
                $lideres = $lcModel->getLeadersByCalle($v['id_calle']);
                $msg = "Pago enviado por {$usuario['nombre_completo']} (referencia: {$referencia})";
                require_once __DIR__ . '/../models/Usuario.php';
                $uModel = new Usuario();
                require_once __DIR__ . '/../models/Habitante.php';
                $hModel2 = new Habitante();
                foreach ($lideres as $ld) {
                    // ld expected to have id_habitante
                    $habL = $hModel2->getById($ld['id_habitante']);
                    if ($habL && !empty($habL['id_persona'])) {
                        $userDest = $uModel->findByPersonId($habL['id_persona']);
                        if ($userDest) {
                            $this->notificacionModel->crearNotificacion($userDest['id_usuario'], $_SESSION['id_usuario'] ?? null, 'pago_enviado', $msg, $id_pago);
                        }
                    }
                }
            }
        }

        echo json_encode(['ok' => true, 'id_pago' => $id_pago, 'message' => 'Transacción realizada espere su respuesta']);
        return null;
    }

    // --- LIDER: lista de pagos por vereda asignada
    public function liderListaPagos($id = null) {
        // Asumir que hay un helper que retorna el habitante actual
        $userId = $_SESSION['id_usuario'] ?? null;
        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $usuario = $uModel->getById($userId);
        $data = [];
        // Intentar resolver habitante y obtener veredas asignadas
        require_once __DIR__ . '/../models/Habitante.php';
        $hModel = new Habitante();
        $hab = $hModel->findByPersonaId($usuario['id_persona'] ?? null);
        if (!$hab) return ['view' => 'subadmin/pagos/lista', 'data' => ['page_title' => 'Pagos', 'pagos' => []]];
        require_once __DIR__ . '/../models/LiderCalle.php';
        $lcModel = new LiderCalle();
        $callesIds = $lcModel->getCallesIdsByHabitanteId($hab['id_habitante']);
        $pagos = [];
        foreach ($callesIds as $cId) {
            $p = $this->pagoModel->getPagosPorVereda($cId);
            $pagos = array_merge($pagos, $p);
        }
        return ['view' => 'subadmin/pagos/lista', 'data' => ['page_title' => 'Pagos por Vereda', 'pagos' => $pagos]];
    }

    // Verificar (aprobar/rechazar)
    public function liderVerifyPago($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            return null;
        }
        $id_pago = intval($_POST['id_pago'] ?? 0);
        $accion = $_POST['accion'] ?? 'rechazar';
        $comentario = $_POST['comentario'] ?? null;
        $userId = $_SESSION['id_usuario'] ?? null;
        if (!$id_pago || !$userId) {
            echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
            return null;
        }
        $nuevo_estado = $accion === 'aprobar' ? 'cancelado' : 'rechazado';
        $ok = $this->pagoModel->verifyPago($id_pago, $nuevo_estado, $userId, $comentario);
        if ($ok) {
            echo json_encode(['ok' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Error al actualizar estado']);
        }
        return null;
    }
}
