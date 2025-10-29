<?php
require_once __DIR__ . '/../models/PagosPeriodos.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Notificacion.php';

class PagoController extends AppController {
    protected $periodoModel;
    protected $pagoModel;
    protected $notificacionModel;

    public function __construct() {
        parent::__construct();
        $this->periodoModel = new PagosPeriodos();
        $this->pagoModel = new Pago();
        $this->notificacionModel = new Notificacion();
    }

    // --- ADMIN: lista de periodos (activos + historial)
    public function adminPeriodos($id = null) {
        // Permiso: solo rol 1 (Jefe del Consejo Comunal)
        if (($_SESSION['id_rol'] ?? null) !== 1) {
            $_SESSION['error_message'] = 'No tienes permisos para acceder a esta sección.';
            header('Location: ./index.php?route=user/dashboard');
            return null;
        }
        $activos = $this->periodoModel->getActivos();
        $historial = $this->periodoModel->getHistorial();

        // Renderizar usando loadView (AppController) — loadView expone content_view para compatibilidad con admin_layout
        $data = ['page_title' => 'Periodos de Pago', 'activos' => $activos, 'historial' => $historial];
        $this->loadView('admin/pagos/periodos', $data);
        return null;
    }

    public function adminCrearPeriodo($id = null) {
        if (($_SESSION['id_rol'] ?? null) !== 1) {
            $_SESSION['error_message'] = 'No tienes permisos para acceder a esta sección.';
            header('Location: ./index.php?route=user/dashboard');
            return null;
        }

        $data = ['page_title' => 'Crear Periodo'];
        $this->loadView('admin/pagos/crear', $data);
        return null;
    }

    public function adminStorePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return null;
        }
        if (($_SESSION['id_rol'] ?? null) !== 1) {
            $_SESSION['error_message'] = 'No tienes permisos para realizar esta acción.';
            header('Location: ./index.php?route=user/dashboard');
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
        $data = ['page_title' => 'Pagos Disponibles', 'activos' => $activos];
        $this->loadView('user/pagos/index', $data);
        return null;
    }

    public function userDetallePeriodo($id = null) {
        $id_periodo = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        if (!$id_periodo) {
            $_SESSION['error_message'] = 'Periodo no especificado.';
            header('Location: ./index.php?route=user/pagos');
            return null;
        }
        $periodo = $this->periodoModel->getById($id_periodo);
        $data = ['page_title' => 'Detalle Periodo', 'periodo' => $periodo];
        $this->loadView('user/pagos/detalle', $data);
        return null;
    }

    // Historial de pagos del jefe de familia (mis pagos)
    public function userHistorial($id = null) {
        $userId = $_SESSION['id_usuario'] ?? null;
        if (!$userId) {
            $this->redirect('login');
            return null;
        }

        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $usuario = $uModel->getById($userId);

        if (empty($usuario['id_persona'])) {
            $this->loadView('user/pagos/historial', ['page_title' => 'Mi Historial de Pagos', 'pagos' => []]);
            return null;
        }

        require_once __DIR__ . '/../models/Habitante.php';
        $hModel = new Habitante();
        $hab = $hModel->findByPersonaId($usuario['id_persona']);
        if (!$hab) {
            $this->loadView('user/pagos/historial', ['page_title' => 'Mi Historial de Pagos', 'pagos' => []]);
            return null;
        }

        $pagos = $this->pagoModel->getPagosPorHabitante($hab['id_habitante']);
        // Adjuntar evidencias a cada pago
        foreach ($pagos as &$p) {
            $p['evidencias'] = $this->pagoModel->getEvidencesByPago((int)($p['id_pago'] ?? 0));
        }
        unset($p);
        $this->loadView('user/pagos/historial', ['page_title' => 'Mi Historial de Pagos', 'pagos' => $pagos]);
        return null;
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
            if ($hab) {
                $id_habitante = $hab['id_habitante'];
            } else {
                // Intentar crear un habitante mínimo desde la persona (fallback seguro)
                // Esto ayuda cuando la persona existe como usuario pero no tiene aún registro en 'habitante'
                $created = $hModel->createFromPersona((int)$usuario['id_persona']);
                if ($created) {
                    $id_habitante = $created;
                    error_log("[PagoController::userSubmitPago] Habitante creado automaticamente para persona " . $usuario['id_persona'] . ", id_habitante=" . $created);
                } else {
                    error_log("[PagoController::userSubmitPago] No existe habitante para persona " . $usuario['id_persona'] . " y no pudo crearse");
                }
            }
        }

    // Validaciones básicas
        $id_periodo = intval($_POST['id_periodo'] ?? 0);
        $metodo = $_POST['metodo_pago'] ?? 'transferencia';
        $referencia = trim($_POST['referencia_pago'] ?? '');
        $id_vivienda = intval($_POST['id_vivienda'] ?? 0) ?: null;

    // Depuración: registrar valores clave para diagnosticar problemas de envío
    error_log("[PagoController::userSubmitPago] userId=" . json_encode($userId) . ", id_periodo=" . json_encode($id_periodo) . ", referencia='" . $referencia . "'");
    if (!empty($usuario)) error_log("[PagoController::userSubmitPago] usuario.id_persona=" . json_encode($usuario['id_persona'] ?? null));

        if (!$id_periodo || !$userId || !$id_habitante) {
            // Detallar el motivo en el log para facilitar debugging
            error_log("[PagoController::userSubmitPago] Falta id_periodo/userId/id_habitante (id_periodo=" . json_encode($id_periodo) . ", userId=" . json_encode($userId) . ", id_habitante=" . json_encode($id_habitante) . ")");
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos o permisos insuficientes']);
            return null;
        }

        // Validar referencia: solo dígitos y máximo 20 caracteres
        // Si el método es 'efectivo' no es obligatorio ni referencia ni comprobante
        if ($metodo !== 'efectivo') {
            $ref_sanitized = preg_replace('/[^0-9]/', '', $referencia);
            if ($ref_sanitized === '' || strlen($ref_sanitized) > 20) {
                echo json_encode(['ok' => false, 'message' => 'Referencia inválida: debe contener solo números y hasta 20 dígitos']);
                return null;
            }
            $referencia = $ref_sanitized;
        } else {
            // Pago en efectivo: limpiar referencia y permitir ausencia de comprobantes
            $referencia = null;
        }

        // Verificar que el habitante sea jefe de familia (habitante_vivienda.es_jefe_familia = 1)
        require_once __DIR__ . '/../models/HabitanteVivienda.php';
        $hvModel = new HabitanteVivienda();
        $esJefe = $hvModel->isJefeFamilia($id_habitante);
        // Permitir envío si es jefe (habitante_vivienda.es_jefe_familia = 1)
        // o si el usuario tiene rol 3 (Jefe de Familia) como excepción práctica
        $userIsJefeRole = (!empty($usuario['id_rol']) && intval($usuario['id_rol']) === 3);
        if (!$esJefe && !$userIsJefeRole) {
            echo json_encode(['ok' => false, 'message' => 'Solo Jefes de Familia pueden enviar pagos.']);
            return null;
        }

        // Verificar que el periodo exista y esté activo
        $periodoInfo = $this->periodoModel->getById($id_periodo);
        if (!$periodoInfo || ($periodoInfo['estado'] ?? '') !== 'activo') {
            echo json_encode(['ok' => false, 'message' => 'El periodo especificado no está activo o no existe.']);
            return null;
        }

        // Validar y normalizar monto: debe ser numérico y no nulo
        $monto_raw = trim((string)($_POST['monto'] ?? ''));
        if ($monto_raw === '') {
            echo json_encode(['ok' => false, 'message' => 'Monto inválido o no especificado']);
            return null;
        }
        // Aceptar separador decimal por coma o punto
        $monto_normalized = str_replace(',', '.', $monto_raw);
        if (!is_numeric($monto_normalized)) {
            echo json_encode(['ok' => false, 'message' => 'Monto inválido']);
            return null;
        }
        $monto = (float)$monto_normalized;

        $data = [
            'id_usuario' => $userId,
            'id_tipo_beneficio' => $_POST['id_tipo_beneficio'] ?? null,
            'id_periodo' => $id_periodo,
            'monto' => $monto,
            'metodo_pago' => $metodo,
            'referencia_pago' => $referencia,
            'id_habitante' => $id_habitante,
            'id_vivienda' => $id_vivienda,
            'registrado_por_id' => $userId,
            'estado_actual' => 'en_espera'
        ];

        // Intentar insertar y capturar excepciones para devolver JSON legible
        try {
            $id_pago = $this->pagoModel->submitPago($data);
        } catch (\Throwable $e) {
            error_log("[PagoController::userSubmitPago] Excepción al crear pago: " . $e->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Error interno al registrar el pago']);
            return null;
        }

        if (!$id_pago) {
            echo json_encode(['ok' => false, 'message' => 'No se pudo registrar el pago']);
            return null;
        }

        // Procesar archivos con validación de tipo y tamaño
        $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5 MB por archivo
        $upload_base = __DIR__ . '/../../public/uploads/pagos/' . $id_periodo . '/' . $id_pago . '/';
        if (!is_dir($upload_base)) mkdir($upload_base, 0777, true);
        $saved = [];

        if (!empty($_FILES['captura'])) {
            $filesField = $_FILES['captura'];

            // Normalizar estructura para un solo archivo o múltiples
            $fileCount = 0;
            if (is_array($filesField['name'])) {
                $fileCount = count($filesField['name']);
            } else {
                $fileCount = 1;
                // Convertir a estructura de array para procesar de forma homogénea
                $filesField = [
                    'name' => [$filesField['name']],
                    'type' => [$filesField['type']],
                    'tmp_name' => [$filesField['tmp_name']],
                    'error' => [$filesField['error']],
                    'size' => [$filesField['size']],
                ];
            }

            for ($i = 0; $i < $fileCount; $i++) {
                if ($filesField['error'][$i] !== UPLOAD_ERR_OK) continue;

                $mime = $filesField['type'][$i] ?? mime_content_type($filesField['tmp_name'][$i]);
                $size = intval($filesField['size'][$i] ?? 0);

                if (!in_array($mime, $allowed_mimes, true)) {
                    // Borrar archivos temporales si aplica
                    echo json_encode(['ok' => false, 'message' => 'Tipo de archivo no permitido. Tipos permitidos: jpg, png, pdf']);
                    return null;
                }

                if ($size > $max_size) {
                    echo json_encode(['ok' => false, 'message' => 'Archivo demasiado grande. Máximo 5 MB por archivo.']);
                    return null;
                }

                $orig = basename($filesField['name'][$i]);
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $upload_base . $name;

                if (move_uploaded_file($filesField['tmp_name'][$i], $target)) {
                    $rel = 'uploads/pagos/' . $id_periodo . '/' . $id_pago . '/' . $name;
                    $this->pagoModel->addEvidence($id_pago, $rel, $mime, $size, $userId);
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
                // Construir mensaje incluyendo el periodo si está disponible
                $periodoInfo = $this->periodoModel->getById($id_periodo);
                $periodoNombre = $periodoInfo['nombre_periodo'] ?? null;
                $msg = "Pago enviado por {$usuario['nombre_completo']}";
                if (!empty($referencia)) {
                    $msg .= " (referencia: {$referencia})";
                } else {
                    $msg .= " (sin referencia)";
                }
                if ($periodoNombre) $msg .= " — Periodo: {$periodoNombre}";

                require_once __DIR__ . '/../models/Usuario.php';
                $uModel = new Usuario();
                require_once __DIR__ . '/../models/Habitante.php';
                $hModel2 = new Habitante();

                $notified = 0;
                foreach ($lideres as $ld) {
                    // ld expected to have id_habitante
                    $habL = $hModel2->getById($ld['id_habitante']);
                    if ($habL && !empty($habL['id_persona'])) {
                        $userDest = $uModel->findByPersonId($habL['id_persona']);
                        if ($userDest) {
                            $this->notificacionModel->crearNotificacion($userDest['id_usuario'], $_SESSION['id_usuario'] ?? null, 'pago_enviado', $msg, $id_pago);
                            $notified++;
                        }
                    }
                }

                // Si no se notificó a ningún líder (no hay líderes asignados), notificar a administradores por defecto
                if ($notified === 0) {
                    $admins = $uModel->getAllFiltered(['id_rol' => 1]);
                    foreach ($admins as $adm) {
                        $this->notificacionModel->crearNotificacion($adm['id_usuario'], $_SESSION['id_usuario'] ?? null, 'pago_enviado', $msg, $id_pago);
                    }
                }
            }
        } else {
            // No hay vivienda asociada: notificar directamente a Jefes (rol 1)
            require_once __DIR__ . '/../models/Usuario.php';
            $uModel2 = new Usuario();
            $admins = $uModel2->getAllFiltered(['id_rol' => 1]);
            $periodoInfo = $this->periodoModel->getById($id_periodo);
            $periodoNombre = $periodoInfo['nombre_periodo'] ?? null;
            $msg = "Pago enviado por {$usuario['nombre_completo']}";
            if (!empty($referencia)) $msg .= " (referencia: {$referencia})"; else $msg .= " (sin referencia)";
            if ($periodoNombre) $msg .= " — Periodo: {$periodoNombre}";
            foreach ($admins as $adm) {
                $this->notificacionModel->crearNotificacion($adm['id_usuario'], $_SESSION['id_usuario'] ?? null, 'pago_enviado', $msg, $id_pago);
            }
        }

        echo json_encode(['ok' => true, 'id_pago' => $id_pago, 'message' => 'Transacción realizada espere su respuesta']);
        return null;
    }

    // --- LIDER: lista de pagos por vereda asignada
    public function liderListaPagos($id = null) {
        // Validar que el usuario esté autenticado
        $userId = $_SESSION['id_usuario'] ?? null;
        if (!$userId) {
            // No autenticado: redirigir al login
            $this->redirect('login');
            return null;
        }
        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $usuario = $uModel->getById($userId);
        $data = [];
        // Intentar resolver habitante y obtener veredas asignadas
        require_once __DIR__ . '/../models/Habitante.php';
        $hModel = new Habitante();
        $hab = $hModel->findByPersonaId($usuario['id_persona'] ?? null);
        if (!$hab) {
            $this->loadView('subadmin/pagos/lista', ['page_title' => 'Pagos', 'pagos' => []]);
            return null;
        }
        require_once __DIR__ . '/../models/LiderCalle.php';
        $lcModel = new LiderCalle();
        $callesIds = $lcModel->getCallesIdsByHabitanteId($hab['id_habitante']);
        if (empty($callesIds)) {
            $this->loadView('subadmin/pagos/lista', ['page_title' => 'Pagos por Vereda', 'pagos' => []]);
            return null;
        }

    // Leer filtros desde GET (estado, desde, hasta)
    $filters = [];
    $estado = trim($_GET['estado'] ?? '');
    $desde = trim($_GET['desde'] ?? '');
    $hasta = trim($_GET['hasta'] ?? '');
    if ($estado !== '') $filters['estado'] = $estado;
    if ($desde !== '') $filters['desde'] = $desde;
    if ($hasta !== '') $filters['hasta'] = $hasta;

    // Paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 25;
    $offset = ($page - 1) * $limit;

    // Conteo (nota: countPagosPorCalles no soporta filtros en esta versión)
    $total = $this->pagoModel->countPagosPorCalles($callesIds);
    $totalPages = ($total > 0) ? ceil($total / $limit) : 1;

    $pagos = $this->pagoModel->getPagosPorCalles($callesIds, $filters, $limit, $offset);

        // Adjuntar evidencias a cada pago para que el líder pueda revisarlas
        foreach ($pagos as &$pp) {
            $pp['evidencias'] = $this->pagoModel->getEvidencesByPago((int)($pp['id_pago'] ?? 0));
        }
        unset($pp);

        $this->loadView('subadmin/pagos/lista', ['page_title' => 'Pagos por Vereda', 'pagos' => $pagos, 'pagination' => ['page' => $page, 'total' => $total, 'totalPages' => $totalPages, 'limit' => $limit]]);
        return null;
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
        // Permiso: verificar que el usuario sea líder asignado a la vereda del pago
        require_once __DIR__ . '/../models/Pago.php';
        $pago = $this->pagoModel->find($id_pago);
        if (!$pago) { echo json_encode(['ok' => false, 'message' => 'Pago no encontrado']); return null; }

        $id_vivienda = $pago['id_vivienda'] ?? null;
        if (!$id_vivienda) { echo json_encode(['ok' => false, 'message' => 'Pago no tiene vivienda asociada']); return null; }

        require_once __DIR__ . '/../models/Vivienda.php';
        $vModel = new Vivienda();
        $v = $vModel->getById($id_vivienda);
        if (!$v || empty($v['id_calle'])) { echo json_encode(['ok' => false, 'message' => 'No se pudo determinar la vereda del pago']); return null; }

        // obtener habitante del usuario actual
        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $usuario = $uModel->getById($userId);
        require_once __DIR__ . '/../models/Habitante.php';
        $hModel = new Habitante();
        $hab = $hModel->findByPersonaId($usuario['id_persona'] ?? null);
        if (!$hab) { echo json_encode(['ok' => false, 'message' => 'No eres líder asignado']); return null; }

        require_once __DIR__ . '/../models/LiderCalle.php';
        $lcModel = new LiderCalle();
        $callesIds = $lcModel->getCallesIdsByHabitanteId($hab['id_habitante']);
        if (!in_array((int)$v['id_calle'], $callesIds, true)) {
            echo json_encode(['ok' => false, 'message' => 'No tienes permisos para verificar pagos en esta vereda']);
            return null;
        }

        $nuevo_estado = $accion === 'aprobar' ? 'cancelado' : 'rechazado';
        $ok = $this->pagoModel->verifyPago($id_pago, $nuevo_estado, $userId, $comentario);
        if ($ok) {
            // Notificar al usuario que registró el pago sobre el nuevo estado
            $destUserId = $pago['id_usuario'] ?? null;
            if ($destUserId) {
                $estadoLabel = $nuevo_estado === 'cancelado' ? 'Aprobado' : 'Rechazado';
                $msg = "Tu pago (ID: {$id_pago}) ha sido {$estadoLabel}.";
                if (!empty($comentario)) $msg .= " Comentario: {$comentario}";
                $this->notificacionModel->crearNotificacion($destUserId, $userId, 'pago_verificacion', $msg, $id_pago);
            }
            echo json_encode(['ok' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Error al actualizar estado']);
        }
        return null;
    }
}
