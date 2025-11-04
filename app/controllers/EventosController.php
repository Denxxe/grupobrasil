<?php
// grupobrasil/app/controllers/EventosController.php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/LiderCalle.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
// AdminController se usa para renderizar vistas dentro del layout admin cuando el usuario es admin
require_once __DIR__ . '/AdminController.php';

class EventosController extends AppController {
    private $eventModel;

    public function __construct() {
        parent::__construct();
        $this->eventModel = new Event();
    }

    // Vista principal con calendario
    public function index($id = null) {
        $data = ['page_title' => 'Eventos de la Comunidad'];
        // Si el usuario es admin, renderizamos con el layout de admin
        if (\AuthHelper::esAdmin()) {
            $admin = new AdminController();
            $admin->renderAdminView('events/index', $data);
            exit();
        }

        // Si el usuario es Líder/Subadmin (rol 2), renderizamos con el layout de subadmin
        if (\AuthHelper::esLider()) {
            require_once __DIR__ . '/SubadminController.php';
            $sub = new SubadminController();
            if (method_exists($sub, 'renderSubadminView')) {
                $sub->renderSubadminView('events/index', $data);
                exit();
            }
        }

        // Usuarios comunes usan la vista pública
        return ['view' => 'events/index', 'data' => $data];
    }

    // Endpoint JSON para listado de eventos (para FullCalendar)
    public function list($id = null) {
        header('Content-Type: application/json');
        $start = $_GET['start'] ?? null; // YYYY-MM-DD
        $end = $_GET['end'] ?? null;
        $events = $this->eventModel->getEventsBetween($start, $end);
        echo json_encode($events);
        exit();
    }

    // RSVP / Confirmar asistencia (POST AJAX)
    public function rsvp($id = null) {
        // requiere usuario autenticado
        $usuario_id = $_SESSION['id_usuario'] ?? null;
        if (!$usuario_id) {
            header('Content-Type: application/json'); http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json'); http_response_code(405); echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit();
        }

        $id_evento = (int)($_POST['id_evento'] ?? 0);
        if ($id_evento <= 0) { header('Content-Type: application/json'); http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID de evento inválido']); exit(); }

        $res = $this->eventModel->toggleAttendance($id_evento, $usuario_id);
        if (!$res['success']) { header('Content-Type: application/json'); http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error interno']); exit(); }

        // Notificar al creador del evento si alguien confirma
        try {
            $event = $this->eventModel->getEventById($id_evento);
            if ($event && !empty($event['creado_por']) && (int)$event['creado_por'] !== (int)$usuario_id && $res['attending']) {
                $not = new Notificacion();
                $msg = ($_SESSION['nombre_completo'] ?? 'Alguien') . ' dijo que asistirá al evento: ' . ($event['titulo'] ?? 'Evento');
                $not->crearNotificacion((int)$event['creado_por'], (int)$usuario_id, 'event_rsvp', $msg, $id_evento);
            }
        } catch (\Throwable $e) { error_log('Error notificando RSVP: ' . $e->getMessage()); }

        header('Content-Type: application/json'); echo json_encode(['success'=>true,'attending'=>$res['attending']]); exit();
    }

    // Mostrar formulario crear / procesar POST
    public function create($id = null) {
        // Permitir solo roles 1 (Admin/Jefe) y 2 (Líder)
        AuthHelper::requiereAlgunRol([1,2], './index.php?route=user/dashboard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF
            $csrf = $_POST['csrf_token'] ?? null;
            require_once __DIR__ . '/../helpers/CsrfHelper.php';
            if (!\CsrfHelper::validateToken($csrf)) {
                // Si es AJAX devolver JSON
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                if ($isAjax) {
                    header('Content-Type: application/json'); http_response_code(400); echo json_encode(['success'=>false,'message'=>'Token CSRF inválido']); exit();
                }
                $this->setErrorMessage('Token CSRF inválido.');
                $this->redirect('eventos');
            }
            $data = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? null),
                'ubicacion' => trim($_POST['ubicacion'] ?? null),
                'fecha' => trim($_POST['fecha'] ?? null),
                'hora_inicio' => trim($_POST['hora_inicio'] ?? null),
                'hora_fin' => trim($_POST['hora_fin'] ?? null),
                'categoria_edad' => $_POST['categoria_edad'] ?? 'todos',
                'alcance' => $_POST['alcance'] ?? 'comunidad',
                'id_calle' => !empty($_POST['id_calle']) ? (int)$_POST['id_calle'] : null,
                'creado_por' => $_SESSION['id_usuario'] ?? null,
                'activo' => 1
            ];

            // Validaciones mínimas y sanitización
            $errors = [];
            if (empty($data['titulo'])) $errors[] = 'El título es obligatorio.';
            if (empty($data['fecha']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha'])) $errors[] = 'Fecha inválida.';
            if (!empty($data['titulo']) && mb_strlen($data['titulo']) > 255) $errors[] = 'Título demasiado largo (máx 255).';
            if (!empty($data['ubicacion']) && mb_strlen($data['ubicacion']) > 255) $errors[] = 'Ubicación demasiado larga (máx 255).';
            $allowedCats = ['ninos','jovenes','adultos','adultos_mayores','todos'];
            if (!in_array($data['categoria_edad'], $allowedCats)) $data['categoria_edad'] = 'todos';
            $allowedAlc = ['comunidad','vereda'];
            if (!in_array($data['alcance'], $allowedAlc)) $data['alcance'] = 'comunidad';
            if (!empty($data['hora_inicio']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['hora_inicio'])) $errors[] = 'Hora inicio inválida.';
            if (!empty($data['hora_fin']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['hora_fin'])) $errors[] = 'Hora fin inválida.';
            if (!empty($data['id_calle'])) $data['id_calle'] = (int)$data['id_calle']; else $data['id_calle'] = null;

            if (!empty($errors)) {
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                if ($isAjax) { header('Content-Type: application/json'); http_response_code(400); echo json_encode(['success'=>false,'errors'=>$errors]); exit(); }
                $this->setErrorMessage(implode(' ', $errors)); $this->redirect('eventos');
            }

            $newId = $this->eventModel->createEvent($data);
            if ($newId) {
                // Si es AJAX devolver JSON
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'id'=>$newId]); exit(); }
                $this->setSuccessMessage('Evento creado.');
                // Notificar a los usuarios sobre el nuevo evento (excepto el creador)
                try {
                    $usuarioModel = new Usuario();
                    $notModel = new Notificacion();
                    $allUsers = $usuarioModel->getAllFiltered([], ['column' => 'p.nombres', 'direction' => 'ASC']);
                    foreach ($allUsers as $u) {
                        if (isset($u['id_usuario']) && (int)$u['id_usuario'] !== (int)$data['creado_por']) {
                            $msg = 'Nuevo evento: ' . ($data['titulo'] ?? 'Sin título');
                            $notModel->crearNotificacion((int)$u['id_usuario'], (int)$data['creado_por'], 'event', $msg, (int)$newId);
                        }
                    }
                } catch (\Throwable $e) { error_log('Error creando notificaciones de evento: ' . $e->getMessage()); }
            } else {
                $this->setErrorMessage('No se pudo crear el evento.');
            }
            $this->redirect('eventos');
        }

        // GET -> mostrar formulario
        $viewData = ['page_title' => 'Crear Evento'];
        if (\AuthHelper::esAdmin()) {
            $admin = new AdminController();
            $admin->renderAdminView('events/create', $viewData);
            exit();
        }
        if (\AuthHelper::esLider()) {
            require_once __DIR__ . '/SubadminController.php';
            $sub = new SubadminController();
            if (method_exists($sub, 'renderSubadminView')) {
                $sub->renderSubadminView('events/create', $viewData);
                exit();
            }
        }
        return ['view' => 'events/create', 'data' => $viewData];
    }

    // Editar evento (GET form / POST update)
    public function edit($id = null) {
        AuthHelper::requiereAlgunRol([1,2], './index.php?route=user/dashboard');
        $id = (int)$id;
        if ($id <= 0) {
            $this->setErrorMessage('Evento no válido.');
            $this->redirect('eventos');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF
            $csrf = $_POST['csrf_token'] ?? null;
            require_once __DIR__ . '/../helpers/CsrfHelper.php';
            if (!\CsrfHelper::validateToken($csrf)) { $this->setErrorMessage('Token CSRF inválido.'); $this->redirect('eventos'); }
            $data = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? null),
                'ubicacion' => trim($_POST['ubicacion'] ?? null),
                'fecha' => trim($_POST['fecha'] ?? null),
                'hora_inicio' => trim($_POST['hora_inicio'] ?? null),
                'hora_fin' => trim($_POST['hora_fin'] ?? null),
                'categoria_edad' => $_POST['categoria_edad'] ?? 'todos',
                'alcance' => $_POST['alcance'] ?? 'comunidad',
                'id_calle' => !empty($_POST['id_calle']) ? (int)$_POST['id_calle'] : null,
                'actualizado_por' => $_SESSION['id_usuario'] ?? null
            ];

            $ok = $this->eventModel->updateEvent($id, $data);
            if ($ok) $this->setSuccessMessage('Evento actualizado.'); else $this->setErrorMessage('No se pudo actualizar evento.');
            $this->redirect('eventos');
        }

        $event = $this->eventModel->getEventById($id);
        if (!$event) {
            $this->setErrorMessage('Evento no encontrado.');
            $this->redirect('eventos');
        }
        $viewData = ['page_title' => 'Editar Evento', 'event' => $event];
        if (\AuthHelper::esAdmin()) {
            $admin = new AdminController();
            $admin->renderAdminView('events/create', $viewData);
            exit();
        }
        if (\AuthHelper::esLider()) {
            require_once __DIR__ . '/SubadminController.php';
            $sub = new SubadminController();
            if (method_exists($sub, 'renderSubadminView')) {
                $sub->renderSubadminView('events/create', $viewData);
                exit();
            }
        }
        return ['view' => 'events/create', 'data' => $viewData];
    }

    // Eliminar (POST)
    public function delete($id = null) {
        AuthHelper::requiereAlgunRol([1,2], './index.php?route=user/dashboard');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('eventos');
        }
        // CSRF
        $csrf = $_POST['csrf_token'] ?? null;
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!\CsrfHelper::validateToken($csrf)) { $this->setErrorMessage('Token CSRF inválido.'); $this->redirect('eventos'); }
        $id = (int)($_POST['id_evento'] ?? 0);
        if ($id <= 0) {
            $this->setErrorMessage('ID de evento inválido.');
            $this->redirect('eventos');
        }
        $ok = $this->eventModel->deleteEvent($id);
        if ($ok) $this->setSuccessMessage('Evento eliminado.'); else $this->setErrorMessage('No se pudo eliminar evento.');
        $this->redirect('eventos');
    }

    // Métricas - accesible a admin y lider (1 y 2)
    public function metrics($id = null) {
        AuthHelper::requiereAlgunRol([1,2], './index.php?route=user/dashboard');
        $year = (int)($_GET['year'] ?? date('Y'));
        // Si es Admin redirigimos a la página global de Indicadores en AdminController
        if (\AuthHelper::esAdmin()) {
            header('Location: ./index.php?route=admin/indicadores');
            exit();
        }
        // Líder (subadmin) debería ver indicadores en su layout (subadmin)
        if (\AuthHelper::esLider()) {
            require_once __DIR__ . '/SubadminController.php';
            $sub = new SubadminController();
            if (method_exists($sub, 'renderSubadminView')) {
                $sub->renderSubadminView('events/metrics', ['page_title' => 'Indicadores - Eventos', 'byMonth'=>$byMonth, 'byYear'=>$byYear, 'byCategory'=>$byCategory, 'year'=>$year]);
                exit();
            }
        }

        $byMonth = $this->eventModel->countEventsByMonth($year);
        $byYear = $this->eventModel->countEventsByYear();
        $byCategory = $this->eventModel->countEventsByCategory();
        return ['view' => 'events/metrics', 'data' => ['page_title' => 'Indicadores - Eventos', 'byMonth'=>$byMonth, 'byYear'=>$byYear, 'byCategory'=>$byCategory, 'year'=>$year]];
    }
}
