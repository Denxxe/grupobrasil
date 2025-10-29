<?php
// grupobrasil/app/controllers/AdminController.php

// Asegúrate de que todas estas rutas de modelos son correctas
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Persona.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Calle.php';
require_once __DIR__ . '/../models/LiderCalle.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Habitante.php';
require_once __DIR__ . '/../models/Vivienda.php';
require_once __DIR__ . '/../models/CargaFamiliar.php';
require_once __DIR__ . '/AppController.php';

class AdminController extends AppController{
    private $usuarioModel;
    private $personaModel;
    private $noticiaModel;
    private $comentarioModel;
    private $notificacionModel;
    private $categoriaModel;
    private $calleModel;
    private $liderCalleModel;
    private $roleModel;
    private $habitanteModel;
    private $viviendaModel;
    private $cargaFamiliarModel;

    public function __construct() {
        // Instanciar modelos internos para uso en métodos del controlador
        $this->usuarioModel = new Usuario();
        $this->personaModel = new Persona();
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario();
        $this->notificacionModel = new Notificacion();
        $this->calleModel = new Calle();
        $this->liderCalleModel = new LiderCalle();
        $this->categoriaModel = new Categoria();
        $this->roleModel = new Role();
        $this->habitanteModel = new Habitante();
        $this->viviendaModel = new Vivienda();
        $this->cargaFamiliarModel = new CargaFamiliar();

        // Lógica de seguridad y redirección (asume que rol 1 es Administrador)
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
            $_SESSION['error_message'] = "Acceso no autorizado. Debe ser administrador.";
            header('Location: ./index.php?route=login');
            exit();
        }
    }

    public function renderAdminView($viewPath, $data = []) {
        extract($data);
        $title = $data['title'] ?? 'Panel de Administración';
        $page_title = $data['page_title'] ?? 'Dashboard de Administración';
        $content_view = __DIR__ . '/../views/admin/' . $viewPath . '.php';

        if (!file_exists($content_view)) {
            http_response_code(500);
            echo "Error: La vista '" . htmlspecialchars($viewPath) . "' no se encontró en " . htmlspecialchars($content_view) . ".";
            exit();
        }

        include_once __DIR__ . '/../views/layouts/admin_layout.php';
    }

    // Delegadores para rutas de pagos/beneficios (evitan 404 cuando el router apunta a AdminController)
    public function adminPeriodos($id = null) {
        // constructor ya verifica que el usuario es admin
        $periodosModel = new PagosPeriodos();
        // If user requested detalle via query param, show detalle
        if (isset($_GET['view']) && $_GET['view'] === 'detalle' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            return $this->adminDetallePeriodo($id);
        }

        $activos = $periodosModel->getActivos();
        $historial = $periodosModel->getHistorial();
        $data = ['page_title' => 'Periodos de Pago', 'activos' => $activos, 'historial' => $historial];
        $this->renderAdminView('pagos/periodos', $data);
    }

    // Mostrar detalle de un periodo (pagos asociados)
    public function adminDetallePeriodo($id = null) {
        // constructor ya verifica que el usuario es admin
        if (!$id) {
            if (isset($_GET['id'])) $id = intval($_GET['id']);
            else {
                $_SESSION['flash_message'] = 'Periodo no especificado';
                header('Location: /admin/pagos/periodos');
                exit;
            }
        }
    $periodosModel = new PagosPeriodos();
        $periodo = $periodosModel->getById($id);
        if (!$periodo) {
            $_SESSION['flash_message'] = 'Periodo no encontrado';
            header('Location: /admin/pagos/periodos');
            exit;
        }

    $pagoModel = new Pago();

        // Paginación simple
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        // Leer filtros opcionales desde GET
        $filters = [];
        $estado = trim($_GET['estado'] ?? '');
        $desde = trim($_GET['desde'] ?? '');
        $hasta = trim($_GET['hasta'] ?? '');
        if ($estado !== '') $filters['estado'] = $estado;
        if ($desde !== '') $filters['desde'] = $desde;
        if ($hasta !== '') $filters['hasta'] = $hasta;

        $total = $pagoModel->countPagosPorPeriodo($id); // total ignoring filters for now
        $totalPages = ($total > 0) ? ceil($total / $limit) : 1;

        // Obtener pagos aplicando filtros si existen
        if (!empty($filters)) {
            $pagos = $pagoModel->getPagosPorPeriodoFiltered($id, $filters, $limit, $offset);
        } else {
            $pagos = $pagoModel->getPagosPorPeriodo($id, $limit, $offset);
        }
        // Adjuntar evidencias para cada pago
        foreach ($pagos as &$p) {
            $p['evidencias'] = $pagoModel->getEvidencesByPago((int)($p['id_pago'] ?? 0));
        }
        unset($p);

        $this->renderAdminView('pagos/detalle', ['periodo' => $periodo, 'pagos' => $pagos, 'pagination' => ['page' => $page, 'total' => $total, 'totalPages' => $totalPages, 'limit' => $limit]]);
    }

    public function adminCrearPeriodo($id = null) {
        // Cargar tipos de beneficio para el select
        require_once __DIR__ . '/../models/TipoBeneficio.php';
        $tbModel = new TipoBeneficio();
        $tipos = $tbModel->findAll();

        $data = ['page_title' => 'Crear Periodo', 'tipos_beneficio' => $tipos];
        $this->renderAdminView('pagos/crear', $data);
    }

    public function adminStorePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        // Permiso ya verificado en constructor (rol 1)
        require_once __DIR__ . '/../models/PagosPeriodos.php';
        require_once __DIR__ . '/../models/Notificacion.php';
        require_once __DIR__ . '/../models/Usuario.php';
        $ppModel = new PagosPeriodos();
        $notModel = new Notificacion();
        $uModel = new Usuario();
        // Validar id_tipo_beneficio
        $id_tipo_beneficio = intval($_POST['id_tipo_beneficio'] ?? 0) ?: null;
        require_once __DIR__ . '/../models/TipoBeneficio.php';
        $tbModel = new TipoBeneficio();
        if (empty($id_tipo_beneficio) || !$tbModel->findById($id_tipo_beneficio)) {
            $_SESSION['error_message'] = 'Tipo de beneficio inválido. Seleccione un tipo válido.';
            header('Location: ./index.php?route=admin/pagos/crear');
            return;
        }

        $nombre = trim($_POST['nombre_periodo'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_limite = $_POST['fecha_limite'] ?? null;
        $monto = $_POST['monto'] ?? null;
        $instrucciones = $_POST['instrucciones_pago'] ?? null;

        $data = [
            'nombre_periodo' => $nombre,
            'fecha_inicio' => $fecha_inicio,
            'fecha_limite' => $fecha_limite,
            'monto' => $monto,
            'id_tipo_beneficio' => $id_tipo_beneficio,
            'instrucciones_pago' => $instrucciones,
            'creado_por' => $_SESSION['id_usuario'] ?? null
        ];

        $id_periodo = $ppModel->createPeriodo($data);
        if ($id_periodo) {
            // Notificar a jefes de familia
            $msg = "Se abrió el periodo $nombre. Fecha límite: $fecha_limite.";
            $jefes = $uModel->getAllFiltered(['id_rol' => 3]);
            foreach ($jefes as $jf) {
                $notModel->crearNotificacion($jf['id_usuario'], $_SESSION['id_usuario'] ?? null, 'periodo_abierto', $msg, $id_periodo);
            }
            $_SESSION['success_message'] = 'Periodo creado correctamente.';
        } else {
            $_SESSION['error_message'] = 'Ocurrió un error al crear el periodo.';
        }

        header('Location: ./index.php?route=admin/pagos/periodos');
        return;
    }

    // Editar periodo
    public function adminEditarPeriodo($id = null) {
        $id_periodo = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT) ?: null;
        if (!$id_periodo) {
            $_SESSION['error_message'] = 'Periodo no especificado.';
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        require_once __DIR__ . '/../models/PagosPeriodos.php';
        require_once __DIR__ . '/../models/TipoBeneficio.php';
        $ppModel = new PagosPeriodos();
        $tbModel = new TipoBeneficio();
        $periodo = $ppModel->getById($id_periodo);
        if (!$periodo) {
            $_SESSION['error_message'] = 'Periodo no encontrado.';
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        $tipos = $tbModel->findAll();
        $this->renderAdminView('pagos/editar', ['page_title' => 'Editar Periodo', 'periodo' => $periodo, 'tipos_beneficio' => $tipos]);
    }

    // Actualizar periodo
    public function adminUpdatePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        $id_periodo = intval($_POST['id_periodo'] ?? 0);
        if (!$id_periodo) {
            $_SESSION['error_message'] = 'Periodo inválido.';
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        require_once __DIR__ . '/../models/PagosPeriodos.php';
        require_once __DIR__ . '/../models/TipoBeneficio.php';
        $ppModel = new PagosPeriodos();
        $tbModel = new TipoBeneficio();

        $id_tipo_beneficio = intval($_POST['id_tipo_beneficio'] ?? 0) ?: null;
        if (empty($id_tipo_beneficio) || !$tbModel->findById($id_tipo_beneficio)) {
            $_SESSION['error_message'] = 'Tipo de beneficio inválido.';
            header('Location: ./index.php?route=admin/pagos/editar&id=' . $id_periodo);
            return;
        }

        $data = [
            'nombre_periodo' => trim($_POST['nombre_periodo'] ?? ''),
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_limite' => $_POST['fecha_limite'] ?? null,
            'monto' => $_POST['monto'] ?? null,
            'id_tipo_beneficio' => $id_tipo_beneficio,
            'instrucciones_pago' => $_POST['instrucciones_pago'] ?? null
        ];
        $ok = $ppModel->update($id_periodo, $data);
        if ($ok) {
            $_SESSION['success_message'] = 'Periodo actualizado correctamente.';
        } else {
            $_SESSION['error_message'] = 'Error al actualizar periodo.';
        }
        header('Location: ./index.php?route=admin/pagos/periodos');
        return;
    }

    // Exportar pagos de un periodo a CSV
    public function adminExportPagosPeriodo($id = null) {
        $id_periodo = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT) ?: null;
        if (!$id_periodo) {
            $_SESSION['error_message'] = 'Periodo no especificado para export.';
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }
        require_once __DIR__ . '/../models/Pago.php';
        require_once __DIR__ . '/../models/PagosPeriodos.php';
        $pModel = new Pago();
        $ppModel = new PagosPeriodos();
        // Leer filtros desde GET
        $filters = [];
        $estado = trim($_GET['estado'] ?? '');
        $desde = trim($_GET['desde'] ?? '');
        $hasta = trim($_GET['hasta'] ?? '');
        if ($estado !== '') $filters['estado'] = $estado;
        if ($desde !== '') $filters['desde'] = $desde;
        if ($hasta !== '') $filters['hasta'] = $hasta;

        if (!empty($filters)) {
            $pagos = $pModel->getPagosPorPeriodoFiltered($id_periodo, $filters, null, null);
        } else {
            $pagos = $pModel->getPagosPorPeriodo($id_periodo);
        }
        $periodo = $ppModel->getById($id_periodo);
        $filename = 'pagos_periodo_' . ($periodo['id_periodo'] ?? $id_periodo) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID Pago','Fecha Envío','Usuario','Cédula','Nombre','Monto','Método','Referencia','Vivienda','Vereda','Estado']);
        foreach ($pagos as $p) {
            $usuario = $p['id_usuario'] ?? '';
            $cedula = $p['cedula'] ?? '';
            $nombre = trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''));
            $numero = $p['numero_vivienda'] ?? '';
            $vereda = $p['vereda'] ?? '';
            fputcsv($out, [ $p['id_pago'] ?? '', $p['fecha_envio'] ?? '', $usuario, $cedula, $nombre, $p['monto'] ?? '', $p['metodo_pago'] ?? '', $p['referencia_pago'] ?? '', $numero, $vereda, $p['estado_actual'] ?? '' ]);
        }
        fclose($out);
        exit();
    }

    public function adminClosePeriodo($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/pagos/periodos');
            return;
        }

        require_once __DIR__ . '/../models/PagosPeriodos.php';
        $ppModel = new PagosPeriodos();
        $id_periodo = intval($_POST['id_periodo'] ?? 0);
        if ($id_periodo > 0 && $ppModel->closePeriodo($id_periodo)) {
            $_SESSION['success_message'] = 'Periodo cerrado.';
        } else {
            $_SESSION['error_message'] = 'No se pudo cerrar el periodo.';
        }
        header('Location: ./index.php?route=admin/pagos/periodos');
        return;
    }

    public function dashboard() {
    // 1. Cargar Modelos adicionales si no están cargados como propiedades
    // Asumo que el controlador ya incluye o puede cargar estos archivos.
    
    // NOTA: Si ya tienes $this->pagoModel y $this->logModel instanciados,
    // puedes omitir estas líneas.
    require_once __DIR__ . '/../models/Pago.php';
    require_once __DIR__ . '/../models/Log.php';
    
    $pagoModel = new Pago();
    $logModel = new Log();
    
    // Asumo que tienes un método para establecer la conexión DB en los modelos
    // si son instanciados aquí. Si son propiedades de clase, esto no es necesario.
    // $pagoModel->setDb($this->db);
    // $logModel->setDb($this->db);


    // 2. Obtener TODAS las Métricas Clave

    // Métricas de Usuarios
    $totalUsuarios = $this->usuarioModel->getTotalUsuarios(); // Existe
    $nuevosUsuariosSemana = $this->usuarioModel->countNewThisWeek(); // Debes implementarlo
    
    // Métricas de Pagos/Beneficios
    $totalPagosHoy = $pagoModel->sumPaymentsToday(); // Debes implementarlo
    $variacionPagosAyer = $pagoModel->getPaymentChangeVsYesterday(); // Debes implementarlo (ej: 15.5 o -5.2)

    // Métricas de Noticias
    $noticiasPendientes = $this->noticiaModel->countPendingReview(); // Debes implementarlo
    $lideresActivos = $this->usuarioModel->countActiveLeaders(); // Debes implementarlo (ej: rol 2)

    
    // 3. Compilar el array de estadísticas ($stats) para la vista
    $stats = [
        'total_usuarios' => $totalUsuarios,
        'nuevos_usuarios_semana' => $nuevosUsuariosSemana,
        'total_pagos_hoy' => $totalPagosHoy,
        'variacion_pagos_ayer' => $variacionPagosAyer,
        'noticias_pendientes' => $noticiasPendientes,
        'lideres_activos' => $lideresActivos,
    ];
    
    // 4. Obtener Actividad Reciente
    // Este método debe devolver un array con la estructura esperada:
    // ['icon' => 'fas fa-...', 'color' => 'text-...', 'message' => '...', 'time' => '...']
    $activity_log = $logModel->getRecentActivity(10); // Debes implementarlo

    
    // 5. Preparar y cargar la vista
    $data = [
        'title' => 'Dashboard',
        'page_title' => 'Dashboard de Administración',
        'stats' => $stats, // CLAVE: Pasar el array de estadísticas
        'activity_log' => $activity_log, // CLAVE: Pasar el log de actividad
    ];

    $this->renderAdminView('dashboard', $data);
}

    // -------------------------------------------------------------------------
    // GESTIÓN DE HABITANTES (PERSONAS)
    // -------------------------------------------------------------------------
    public function personas()
    {
        // 1. Obtener parámetros de búsqueda y filtro de la URL
        $search = $_GET['search'] ?? '';
        $activo = $_GET['activo'] ?? 'all';
        $es_usuario = $_GET['es_usuario'] ?? 'all';

        // 2. Preparar los filtros para el Modelo
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($activo !== 'all' && is_numeric($activo)) {
            $filters['activo'] = (int)$activo;
        }
        if ($es_usuario !== 'all' && is_numeric($es_usuario)) {
            $filters['es_usuario'] = (int)$es_usuario;
        }

        // 3. Obtener datos del Modelo Persona
        $personas = $this->personaModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Habitantes (Personas)',
            'personas' => $personas,
            'current_search' => $search,
            'current_activo' => $activo,
            'current_es_usuario' => $es_usuario,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null
        ];

        // Limpiar mensajes de sesión
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 5. Renderizar la vista
        $this->renderAdminView('users/personas', $data);
    }

    /**
     * Muestra el formulario de edición de un habitante
     */
    public function editHabitante() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId)) {
            $_SESSION['error_message'] = "ID de persona no especificado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Obtener datos de la persona
        $persona = $this->personaModel->getById($personId);

        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Obtener todas las calles para el selector
        $calles = $this->calleModel->findAll();

        $data = [
            'page_title' => 'Editar Habitante',
            'persona' => $persona,
            'calles' => $calles,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        unset($_SESSION['success_message'], $_SESSION['error_message']);

        $this->renderAdminView('users/edit_habitante', $data);
    }

    /**
     * Procesa la actualización de un habitante
     */
    public function updateHabitante() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $personId = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
        $errors = [];

        if (empty($personId)) {
            $_SESSION['error_message'] = "Error: ID de persona no recibido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Validar campos
        $data = $_POST;
        if (Validator::isEmpty($data['cedula'] ?? '')) $errors[] = "La cédula es obligatoria.";
        if (Validator::isEmpty($data['nombres'] ?? '')) $errors[] = "El nombre es obligatorio.";
        if (Validator::isEmpty($data['apellidos'] ?? '')) $errors[] = "El apellido es obligatorio.";
        if (empty($data['id_calle'] ?? '')) $errors[] = "Debe seleccionar una vereda de residencia.";

        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            header('Location: ./index.php?route=admin/users/edit-habitante&person_id=' . $personId);
            return;
        }

        // Preparar datos para actualización
        $personaData = [
            'cedula' => trim($data['cedula'] ?? ''),
            'nombres' => trim($data['nombres'] ?? ''),
            'apellidos' => trim($data['apellidos'] ?? ''),
            'telefono' => trim($data['telefono'] ?? null),
            'id_calle' => (int)($data['id_calle'] ?? null),
            'numero_casa' => trim($data['numero_casa'] ?? null),
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'direccion' => trim($data['direccion'] ?? null),
            'correo' => trim($data['correo'] ?? null),
        ];

        $result = $this->personaModel->update($personId, $personaData);

        if ($result) {
            $_SESSION['success_message'] = "Habitante actualizado exitosamente.";
            header('Location: ./index.php?route=admin/users/personas');
        } else {
            $_SESSION['error_message'] = "Error al actualizar el habitante.";
            header('Location: ./index.php?route=admin/users/edit-habitante&person_id=' . $personId);
        }
    }

    /**
     * Elimina un habitante y todos sus registros relacionados en cascada
     * Si es líder, también elimina su cuenta de usuario
     * Si es jefe de familia, la familia se mantiene pero sin jefe
     */
    public function deleteHabitante() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId) || !is_numeric($personId)) {
            $_SESSION['error_message'] = "ID de persona inválido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        error_log("[v0] deleteHabitante called for person ID: $personId");

        // Obtener datos de la persona para el mensaje
        $persona = $this->personaModel->getById($personId);
        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $nombreCompleto = $persona['nombres'] . ' ' . $persona['apellidos'];

        // Verificar si existe un habitante para esta persona
        $habitante = $this->habitanteModel->findByPersonaId($personId);

        if ($habitante) {
            $habitanteId = $habitante['id_habitante'];
            error_log("[v0] Found habitante ID: $habitanteId for person ID: $personId");

            // Usar el método de eliminación en cascada
            $success = $this->habitanteModel->deleteHabitanteWithCascade($habitanteId);

            if ($success) {
                // También eliminar la persona
                $this->personaModel->delete($personId);
                $_SESSION['success_message'] = "El habitante '$nombreCompleto' y todos sus registros relacionados han sido eliminados exitosamente.";
            } else {
                $_SESSION['error_message'] = "Error al eliminar el habitante '$nombreCompleto'.";
            }
        } else {
            // Si no hay habitante, solo eliminar la persona
            error_log("[v0] No habitante found, deleting only persona");
            $success = $this->personaModel->delete($personId);

            if ($success) {
                $_SESSION['success_message'] = "La persona '$nombreCompleto' ha sido eliminada exitosamente.";
            } else {
                $_SESSION['error_message'] = "Error al eliminar la persona '$nombreCompleto'.";
            }
        }

        header('Location: ./index.php?route=admin/users/personas');
    }


    // -------------------------------------------------------------------------
    // GESTIÓN DE JEFES DE FAMILIA (Rol 3)
    // -------------------------------------------------------------------------
    public function jefesFamilia()
    {
        // 1. Obtener parámetros de búsqueda
        $search = $_GET['search'] ?? '';
        $activo = $_GET['activo'] ?? 'all';

        // 2. Preparar los filtros - Solo usuarios con rol 3 (Jefe de Familia)
        $filters = ['id_rol' => 3];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($activo !== 'all') {
            $filters['activo'] = (int)$activo;
        }

        // 3. Obtener datos del Modelo Usuario
        $usuarios = $this->usuarioModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Jefes de Familia',
            'usuarios' => $usuarios,
            'current_search' => $search,
            'current_activo' => $activo,
            'tipo_usuario' => 'jefes-familia'
        ];

        // 5. Renderizar la vista específica
        $this->renderAdminView('users/jefes_familia', $data);
    }

    // -------------------------------------------------------------------------
    // GESTIÓN DE LÍDERES (Rol 2)
    // -------------------------------------------------------------------------
    public function lideres()
    {
        // 1. Obtener parámetros de búsqueda
        $search = $_GET['search'] ?? '';
        $activo = $_GET['activo'] ?? 'all';

        // 2. Preparar los filtros - Solo usuarios con rol 2 (Líder)
        $filters = ['id_rol' => 2];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($activo !== 'all') {
            $filters['activo'] = (int)$activo;
        }

        // 3. Obtener datos del Modelo Usuario
        $usuarios = $this->usuarioModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Líderes',
            'usuarios' => $usuarios,
            'current_search' => $search,
            'current_activo' => $activo,
            'tipo_usuario' => 'lideres'
        ];

        // 5. Renderizar la vista específica
        $this->renderAdminView('users/lideres', $data);
    }

    // -------------------------------------------------------------------------
    // GESTIÓN DE USUARIOS CON ACCESO (Todos los roles con acceso)
    // -------------------------------------------------------------------------
    public function usuarios()
    {
        // 1. Obtener parámetros de búsqueda y filtro de la URL
        $search = $_GET['search'] ?? '';
        $rol = $_GET['rol'] ?? 'all';
        $activo = $_GET['activo'] ?? 'all';

        // 2. Preparar los filtros para el Modelo Usuario
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($rol !== 'all') {
            $filters['id_rol'] = (int)$rol;
        }
        if ($activo !== 'all') {
            $filters['activo'] = (int)$activo;
        }

        // 3. Obtener datos del Modelo Usuario
        $usuarios = $this->usuarioModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Usuarios (Todos)',
            'usuarios' => $usuarios,
            'current_search' => $search,
            'current_rol' => $rol,
            'current_activo' => $activo,
        ];

        // 5. Renderizar la vista específica de usuarios
        $this->renderAdminView('users/usuarios', $data);
    }

    public function createUser() {
        // 1. Obtener todas las calles para el desplegable (combobox)
        $calles = $this->calleModel->findAll();

        // 2. Preparar los datos para la vista
        $data = [
            'page_title' => 'Crear Nuevo Habitante',
            'calles' => $calles,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        // Limpiar mensajes de sesión
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 3. Renderizar la vista
        $this->renderAdminView('users/create', $data);
    }

    /**
     * CORREGIDO: Crea un habitante y, opcionalmente, crea su cuenta de líder (Familia o Vereda) y asigna calles.
     */
    public function storeUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        $data = $_POST;
        $errors = [];

        // 1. Validar campos de Persona/Habitante
        if (Validator::isEmpty($data['cedula'] ?? '')) $errors[] = "La cédula es obligatoria.";
        if (Validator::isEmpty($data['nombres'] ?? '')) $errors[] = "El nombre es obligatorio.";
        if (Validator::isEmpty($data['apellidos'] ?? '')) $errors[] = "El apellido es obligatorio.";
        if (empty($data['id_calle'] ?? '')) $errors[] = "Debe seleccionar una vereda de residencia.";

        // 2. Validar campos de Liderazgo/Usuario (si se marcó la opción)
        $createUserAccount = isset($data['create_user_account']) && $data['create_user_account'] == 1;
        if ($createUserAccount) {
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';
            $rolLiderId = $data['id_rol_lider'] ?? null;
            $callesDirigidas = $data['calles_liderazgo'] ?? [];

            if (!Validator::isValidEmail($email)) $errors[] = "El email es inválido.";
            if ($this->usuarioModel->buscarPorEmail($email)) $errors[] = "El email ya está registrado.";
            if (!Validator::isValidPassword($password)) $errors[] = "La contraseña debe tener 8+ caracteres, incluyendo mayúsculas, minúsculas, números y un símbolo.";
            if ($password !== $confirmPassword) $errors[] = "Las contraseñas no coinciden.";
            if (!in_array($rolLiderId, [2, 3])) $errors[] = "Debe seleccionar un rol de liderazgo válido (Familia o Vereda).";

            // Si es Líder de Vereda, debe seleccionar al menos una calle
            if ((int)$rolLiderId === 2 && empty($callesDirigidas)) {
                // Esto puede ser una advertencia o un error, lo dejamos como advertencia si es una funcionalidad flexible.
                // $errors[] = "Debe seleccionar al menos una calle para un Líder de Vereda.";
            }
        }

        // Si hay errores, redirigir con mensajes
        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            // Opcional: guardar $data en sesión para rellenar el formulario
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        // 3. CREAR LA PERSONA/HABITANTE
        $personaData = [
            'cedula' => trim($data['cedula'] ?? ''),
            'nombres' => trim($data['nombres'] ?? ''),
            'apellidos' => trim($data['apellidos'] ?? ''),
            'telefono' => trim($data['telefono'] ?? null),
            'id_calle' => (int)($data['id_calle'] ?? null),
            'estado' => 'Residente',
            'activo' => 1,
            // Campos adicionales del formulario que no estaban mapeados:
            'numero_casa' => trim($data['numero_casa'] ?? null),
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'direccion' => trim($data['direccion'] ?? null),
            'correo' => trim($data['correo'] ?? null), // Se podría usar el email de usuario aquí, si aplica
        ];

        $personaId = $this->personaModel->create($personaData);

        if (!$personaId) {
            $_SESSION['error_message'] = "Error grave al guardar el habitante (Cédula duplicada o DB error).";
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        $successMessage = "Habitante creado exitosamente. ID: " . $personaId;

        // 4. CREAR USUARIO Y ASIGNAR ROL DE LÍDER (Si aplica)
        if ($createUserAccount) {
            $usuarioData = [
                'id_persona' => $personaId,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id_rol' => (int)$rolLiderId, // Rol 2 (Vereda) o 3 (Familia)
                'activo' => 1,
                // Si el modelo lo requiere, podrías inicializar otros campos como 'requires_setup' = 0
            ];

            $userId = $this->usuarioModel->create($usuarioData);

            if ($userId) {
                $successMessage .= " | Cuenta de Líder (Rol ID: {$rolLiderId}) creada exitosamente.";

                // 5. ASIGNAR LIDERAZGOS DE CALLE (Solo si es Líder de Vereda)
                if ((int)$rolLiderId === 2) {
                    // Asegurarse de tener un registro en habitante para enlazar con lider_calle
                    $habitanteId = $this->habitanteModel->createFromPersona($personaId);

                    if (!$habitanteId) {
                        // No bloqueamos la creación del usuario, pero avisamos
                        $successMessage .= " | Advertencia: No se pudo crear/obtener registro de habitante para asignaciones de vereda.";
                    } else {
                        if (!empty($callesDirigidas)) {
                            // Validación server-side: máximo 2 veredas por líder
                            $existing = $this->liderCalleModel->getCallesIdsByHabitanteId($habitanteId);
                            $totalAfter = count($existing) + count($callesDirigidas);
                            if ($totalAfter > 2) {
                                $_SESSION['error_message'] = "No se pueden asignar más de 2 veredas a un Líder de Vereda. (Actual: " . count($existing) . ", intentadas: " . count($callesDirigidas) . ")";
                                // Revertir creando usuario si lo deseas; por ahora detenemos y devolvemos el formulario
                                header('Location: ./index.php?route=admin/users/create');
                                return;
                            }

                            $assigned = 0;
                            foreach ($callesDirigidas as $calleId) {
                                $res = $this->liderCalleModel->create(['id_habitante' => $habitanteId, 'id_calle' => (int)$calleId]);
                                if ($res) $assigned++;
                            }
                            $successMessage .= " | Asignado a " . $assigned . " vereda(s).";
                        } else {
                            $successMessage .= " | Advertencia: Rol Líder de Vereda asignado, pero sin calles seleccionadas.";
                        }
                    }
                }

            } else {
                // Esto podría ser causado por un email duplicado
                $successMessage .= " | ERROR: No se pudo crear la cuenta de usuario (Email ya existe o DB error).";
                $_SESSION['error_message'] = "Error al crear la cuenta de usuario, el habitante fue creado. El email podría estar duplicado.";
            }
        }

        $_SESSION['success_message'] = $successMessage;
        header('Location: ./index.php?route=admin/users/personas');
        return;
    }

    public function editUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }
        $user = $this->usuarioModel->getById($id);

        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        // CORRECCIÓN: Usar el modelo de roles para obtener los roles disponibles
        $roles = $this->roleModel->findAll();

        $errors = $_SESSION['form_errors'] ?? [];
        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_form_data']);

        $user_data_to_display = !empty($old_data) ? array_merge($user, $old_data) : $user;

        $data = [
            'title' => 'Editar Usuario',
            'page_title' => 'Editar Usuario',
            'user' => $user_data_to_display,
            'roles' => $roles,
            'errors' => $errors,
        ];

        $this->renderAdminView('users/edit', $data);
    }

    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_usuario) || $id_usuario <= 0) {
            $_SESSION['error_message'] = "Error: ID de usuario inválido en el formulario.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $current_user_data = $this->usuarioModel->getById($id_usuario);
        if (!$current_user_data) {
            $_SESSION['error_message'] = "Usuario a actualizar no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }
        $current_persona_id = $current_user_data['id_persona'] ?? null;

        $data = [
            'ci_usuario' => filter_input(INPUT_POST, 'ci_usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_nacimiento' => filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'id_rol' => (int)filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'biografia' => filter_input(INPUT_POST, 'biografia', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $errors = [];

        if (Validator::isEmpty($data['ci_usuario'])) {
            $errors[] = "La cédula es obligatoria.";
        } else if (!Validator::isValidCI($data['ci_usuario'])) {
            $errors[] = "Formato de cédula inválido. (Ej: V-12345678, E-87654321)";
        } else {
            // Verificar si la CI ya existe en otra persona
            $existingPersonaByCI = $this->personaModel->buscarPorCI($data['ci_usuario']);

            if ($existingPersonaByCI) {
                // Asume que la tabla 'usuario' debe tener un JOIN con 'persona' para obtener el id_persona
                $user_persona = $this->usuarioModel->getPersonData($id_usuario);
                $current_persona_id_db = $user_persona['id_persona'] ?? null;

                if ($current_persona_id_db === null || $existingPersonaByCI['id_persona'] != $current_persona_id_db) {
                    $errors[] = "La cédula ya está registrada por otra persona/usuario.";
                }
            }
        }

        if (Validator::isEmpty($data['nombre'])) { $errors[] = "El nombre es obligatorio."; }
        if (Validator::isEmpty($data['apellido'])) { $errors[] = "El apellido es obligatorio."; }

        if (Validator::isEmpty($data['email'])) {
            $errors[] = "El email es obligatorio.";
        } else if (!Validator::isValidEmail($data['email'])) {
            $errors[] = "El email no es válido.";
        } else {
            $existingUserByEmail = $this->usuarioModel->buscarPorEmail($data['email']);
            if ($existingUserByEmail && $existingUserByEmail['id_usuario'] != $id_usuario) {
                $errors[] = "El email ya está registrado por otro usuario.";
            }
        }

        // Asume que los roles son 1, 2, 3. Revisa si $this->roleModel->findAll() te da los IDs correctos.
        if (!in_array($data['id_rol'], [1, 2, 3])) { $errors[] = "Debe seleccionar un rol válido."; }

        if (!Validator::isEmpty($data['fecha_nacimiento']) && !Validator::isValidDate($data['fecha_nacimiento'])) {
            $errors[] = "Formato de fecha de nacimiento inválido (YYYY-MM-DD).";
        }

        $new_password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        if (!empty($new_password)) {
            if (!Validator::isValidPassword($new_password)) {
                $errors[] = "La nueva contraseña no cumple los requisitos (mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un símbolo).";
            } else {
                $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        } else {
            unset($data['password']);
        }

        $upload_dir = __DIR__ . '/../../public/uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $data['foto_perfil'] = $current_user_data['foto_perfil'] ?? null;

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('profile_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
                $errors[] = 'Solo se permiten imágenes JPG, PNG y GIF para la foto de perfil.';
            } elseif ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'La foto de perfil es demasiado grande. Máximo 2MB.';
            } elseif (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                if ($current_user_data['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user_data['foto_perfil'])) {
                    unlink(__DIR__ . '/../../public' . $current_user_data['foto_perfil']);
                }
                $data['foto_perfil'] = './uploads/profile_pictures/' . $file_name;
            } else {
                $errors[] = 'Error al subir la nueva foto de perfil. Código: ' . $_FILES['foto_perfil']['error'];
            }
        } elseif (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
            if ($current_user_data['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user_data['foto_perfil'])) {
                unlink(__DIR__ . '/../../public' . $current_user_data['foto_perfil']);
            }
            $data['foto_perfil'] = null;
        }

        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            $_SESSION['old_form_data'] = $_POST;
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil'];
            header('Location: ./index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }

        // Separar data de usuario y data de persona para actualización
        $personaUpdateData = [
            'cedula' => $data['ci_usuario'],
            'nombres' => $data['nombre'],
            'apellidos' => $data['apellido'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'direccion' => $data['direccion'],
            'telefono' => $data['telefono'],
            // Asegúrate de que tu modelo Persona tiene un método updateByUserId o un método updateByPersonId
        ];

        // Asumiendo que getPersonData() devuelve el id_persona:
        $current_persona_data = $this->usuarioModel->getPersonData($id_usuario);
        $personaUpdateResult = $this->personaModel->update($current_persona_data['id_persona'], $personaUpdateData);


        // Datos para actualizar en la tabla usuario
        $usuarioUpdateData = [
            'email' => $data['email'],
            'id_rol' => $data['id_rol'],
            'activo' => $data['activo'],
            'biografia' => $data['biografia'],
            'foto_perfil' => $data['foto_perfil']
        ];

        if (isset($data['password'])) {
            $usuarioUpdateData['password'] = $data['password'];
        }

        $result = $this->usuarioModel->update($id_usuario, $usuarioUpdateData);

        if ($result || $personaUpdateResult) { // Si al menos una de las actualizaciones fue exitosa
            $_SESSION['success_message'] = "Usuario y Persona actualizados exitosamente.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        } else {
            $_SESSION['error_message'] = "Error al actualizar el usuario o la persona en la base de datos.";
            $_SESSION['old_form_data'] = $_POST;
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil'];
            header('Location: ./index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }
    }

    public function deleteUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido o no proporcionado para la eliminación.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        // CORRECCIÓN: Usar la variable de sesión correcta para el ID del usuario logueado
        if (isset($_SESSION['id_usuario']) && $id == $_SESSION['id_usuario']) {
            $_SESSION['error_message'] = "No puedes eliminar tu propia cuenta de administrador.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $userToDelete = $this->usuarioModel->getById($id);
        if (!$userToDelete) {
            $_SESSION['error_message'] = "Usuario a eliminar no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        if ($userToDelete['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
            if (!unlink(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
                error_log("Error al eliminar el archivo de foto de perfil: " . __DIR__ . '/../../public' . $userToDelete['foto_perfil']);
            }
        }

        // Opcional: Eliminar las asignaciones de calle antes de eliminar el usuario
        // Convertir id_usuario -> id_habitante si existe
        $personData = $this->usuarioModel->getPersonData($id);
        if (!empty($personData) && isset($personData['id_persona'])) {
            $habitante = $this->habitanteModel->findByPersonaId($personData['id_persona']);
            if ($habitante && isset($habitante['id_habitante'])) {
                $this->liderCalleModel->deleteByHabitanteId($habitante['id_habitante']);
            }
        }

        $success = $this->usuarioModel->delete($id);

        if ($success) {
            // Se asume que al eliminar el usuario, se debe eliminar o desactivar la persona.
            // Si tu DB usa FOREIGN KEY ON DELETE CASCADE, esto se maneja automáticamente.
            $_SESSION['success_message'] = "El usuario '" . htmlspecialchars($userToDelete['nombres'] . ' ' . $userToDelete['apellidos']) . "' ha sido eliminado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Hubo un error al intentar eliminar el usuario '" . htmlspecialchars($userToDelete['nombres'] . ' ' . $userToDelete['apellidos']) . "'.";
        }

        header('Location: ./index.php?route=admin/users/usuarios');
        exit();
    }

    public function createUserRole() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId)) {
            $_SESSION['error_message'] = "ID de persona no especificado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 1. Obtener datos del Habitante (Persona)
        $persona = $this->personaModel->getById($personId);

        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 2. Obtener cuenta de Usuario existente
        $usuarioExistente = $this->usuarioModel->findByPersonId($personId);
        $callesDirigidas = [];

        // 3. Obtener ID de habitante y Calles dirigidas si existe el usuario y es líder de vereda (rol 2)
        $habitante = $this->habitanteModel->findByPersonaId($personId);
        $habitanteId = $habitante ? $habitante['id_habitante'] : null;

        if ($usuarioExistente && (int)$usuarioExistente['id_rol'] === 2 && $habitanteId) {
             // Asume que este método existe y retorna un array de IDs de calle.
             // Call getCallesIdsByHabitanteId on liderCalleModel
             $callesDirigidas = $this->liderCalleModel->getCallesIdsByHabitanteId($habitanteId);
        }

        // LÓGICA DE RESTRICCIÓN DEL SUPERADMIN
        if ($usuarioExistente && (int)$usuarioExistente['id_rol'] === 1) {
            $_SESSION['error_message'] = "No se permite modificar los roles del Administrador Principal desde esta interfaz.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 4. Obtener datos para la vista
        $calles = $this->calleModel->findAll();

        // 4.b Obtener viviendas disponibles según el rol del usuario que está realizando la asignación
        $currentUserRole = $_SESSION['id_rol'] ?? null;
        $currentUserId = $_SESSION['id_usuario'] ?? null;
        $viviendas = [];
        if ($currentUserRole == 1) {
            // Administrador: puede ver todas las viviendas
            $viviendas = $this->viviendaModel->getAllWithCalle();
        } elseif ($currentUserRole == 2 && $currentUserId) {
            // Líder de vereda: solo viviendas en sus veredas asignadas
            $calleIdsForLeader = $this->liderCalleModel->getCallesIdsPorUsuario($currentUserId);
            if (!empty($calleIdsForLeader)) {
                $viviendas = $this->viviendaModel->getViviendasPorCalles($calleIdsForLeader);
            }
        }

        $data = [
            'page_title' => 'Asignar Usuario y Roles de Liderazgo',
            'persona' => $persona,
            'usuario' => $usuarioExistente,
            'calles' => $calles,
            'calles_dirigidas' => $callesDirigidas, // Se agrega para preselección en la vista
            'viviendas' => $viviendas,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 5. Renderizar la vista
        $this->renderAdminView('users/create_user_role', $data);
    }

    /**
     * CORREGIDO: Procesa la asignación/modificación de roles de líder.
     * // Updated to work with habitante table structure
     */
    public function storeUserRole() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 1. CAPTURAR Y SANEAMIENTO DE DATOS POST
        $personId = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
        $isLiderVereda = isset($_POST['is_lider_vereda']);
        $isLiderFamilia = isset($_POST['is_lider_familia']); // Se usa solo para definir el rol principal si no es Vereda
        $userExists = isset($_POST['user_exists']);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $callesDirigidas = $_POST['calles_liderazgo'] ?? [];

        error_log("[v0] storeUserRole called - personId: $personId, isLiderVereda: " . ($isLiderVereda ? 'true' : 'false') . ", isLiderFamilia: " . ($isLiderFamilia ? 'true' : 'false'));

        if (empty($personId)) {
            $_SESSION['error_message'] = "Error de validación: ID de persona no recibido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $persona = $this->personaModel->getById($personId);
        if (!$persona) {
            $_SESSION['error_message'] = "Error: Persona no encontrada.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 2. CREAR O OBTENER HABITANTE (requerido para la tabla lider_calle)
        $habitanteId = $this->habitanteModel->createFromPersona($personId);
        if (!$habitanteId) {
            $_SESSION['error_message'] = "Error al crear/obtener el registro de habitante.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }
        error_log("[v0] Habitante ID: $habitanteId");


        // 3. DETERMINAR EL ROL PRINCIPAL (Rol 2 tiene prioridad sobre Rol 3)
        $rolPrincipal = null;
        if ($isLiderVereda) {
            $rolPrincipal = 2; // Líder de Vereda
        } elseif ($isLiderFamilia) {
            $rolPrincipal = 3; // Líder de Familia / Miembro
        } else {
             // Si desmarcan ambos, el rol por defecto para un usuario existente debería ser el más bajo, por ejemplo '3'.
             // Si no tienen cuenta, no hay rol.
        }

        error_log("[v0] Determined rolPrincipal: " . ($rolPrincipal ?? 'null'));

        // 4. CREACIÓN / ACTUALIZACIÓN DE CUENTA DE USUARIO
        $usuarioExistente = $this->usuarioModel->findByPersonId($personId);
        $usuarioId = null;
        $successMessage = '';

        error_log("[v0] Usuario existente: " . ($usuarioExistente ? 'yes (id: ' . $usuarioExistente['id_usuario'] . ')' : 'no'));

        if ($usuarioExistente) {
            // A) ACTUALIZAR ROL
            $usuarioId = $usuarioExistente['id_usuario'];
            if ($rolPrincipal !== null && (int)$usuarioExistente['id_rol'] !== $rolPrincipal) {
                 $updateResult = $this->usuarioModel->update($usuarioId, ['id_rol' => $rolPrincipal]);
                 if (!$updateResult) {
                     $_SESSION['error_message'] = "Error al actualizar el rol del usuario existente.";
                     header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                     return;
                 }
                 error_log("[v0] Updated existing user role to: $rolPrincipal");
            }
            $successMessage = "Rol actualizado exitosamente.";

        } elseif ($rolPrincipal !== null) {
            // B) CREAR NUEVA CUENTA (si se marcó algún rol, se requieren credenciales)
            if (empty($email) || empty($password) || $password !== $confirmPassword || !Validator::isValidEmail($email)) {
                $_SESSION['error_message'] = "Error de validación: Se requiere Email válido y contraseñas coincidentes para crear la cuenta de líder.";
                header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                return;
            }

            $usuarioData = [
                'id_persona' => $personId,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id_rol' => $rolPrincipal,
                'activo' => 1,
            ];

            error_log("[v0] Creating new user with data: " . json_encode(['id_persona' => $personId, 'email' => $email, 'id_rol' => $rolPrincipal]));

            $usuarioId = $this->usuarioModel->createUserOnly($usuarioData);

            if (!$usuarioId) {
                error_log("[v0] Failed to create user - checking for duplicate email");
                $_SESSION['error_message'] = "Error al crear las credenciales de usuario (Email duplicado?).";
                header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                return;
            }
            error_log("[v0] Successfully created user with ID: $usuarioId");
            $successMessage = "Cuenta de usuario y Rol asignados exitosamente.";
        }


        // 5. ASIGNAR/LIMPIAR LIDERAZGOS DE CALLE (usando habitanteId)
        if ($habitanteId && $rolPrincipal !== null) {
            error_log("[v0] Processing calle assignments for habitante ID: $habitanteId");
            // Call deleteByHabitanteId on liderCalleModel
            $this->liderCalleModel->deleteByHabitanteId($habitanteId);

            if ($rolPrincipal === 2 && !empty($callesDirigidas)) { // Líder de Vereda
                error_log("[v0] Assigning " . count($callesDirigidas) . " calles to habitante");
                foreach ($callesDirigidas as $calleId) {
                    // Use id_habitante in lider_calle creation
                    $result = $this->liderCalleModel->create(['id_habitante' => $habitanteId, 'id_calle' => (int)$calleId]);
                    error_log("[v0] Assigned calle $calleId: " . ($result ? 'success' : 'failed'));
                }
                $successMessage .= " Y calles asignadas.";
            }
        }

        // 6. ASIGNAR VIVIENDA SI SE MARCA COMO Jefe de Familia (rol 3)
        // Permitimos asignar una vivienda al habitante que será marcado como jefe de familia.
        if ($habitanteId && $rolPrincipal === 3) {
            $idVivienda = (int)($_POST['id_vivienda'] ?? 0);
            if ($idVivienda > 0) {
                // Validar que la vivienda exista
                $v = $this->viviendaModel->getById($idVivienda);
                if (!$v) {
                    $_SESSION['error_message'] = 'La vivienda seleccionada no existe.';
                    header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                    return;
                }

                // Validar permisos: si el asignador es Líder de Vereda (rol 2), la vivienda debe pertenecer a una de sus veredas
                $currentUserRole = $_SESSION['id_rol'] ?? null;
                $currentUserId = $_SESSION['id_usuario'] ?? null;
                if ($currentUserRole == 2 && $currentUserId) {
                    $allowedCalles = $this->liderCalleModel->getCallesIdsPorUsuario($currentUserId);
                    if (!in_array((int)$v['id_calle'], $allowedCalles, true)) {
                        $_SESSION['error_message'] = 'No tienes permiso para asignar esa vivienda (no pertenece a tus veredas).';
                        header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                        return;
                    }
                }

                // Crear/actualizar habitante_vivienda: eliminamos asociaciones previas y creamos la nueva con es_jefe_familia = 1
                require_once __DIR__ . '/../models/HabitanteVivienda.php';
                $hvModel = new HabitanteVivienda();
                // Eliminar asociaciones viejas para este habitante (si existen)
                $hvModel->deleteByHabitanteId($habitanteId);

                $hvData = [
                    'id_habitante' => (int)$habitanteId,
                    'id_vivienda' => (int)$idVivienda,
                    'es_jefe_familia' => 1,
                    'fecha_ingreso' => date('Y-m-d'),
                    'activo' => 1
                ];

                $hvCreate = $hvModel->create($hvData);
                if ($hvCreate) {
                    $successMessage .= ' Y vivienda asignada como domicilio del jefe.';
                } else {
                    error_log('[v0] Error al crear habitante_vivienda para jefe de familia.');
                    $_SESSION['error_message'] = 'No fue posible asignar la vivienda seleccionada.';
                    header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                    return;
                }
            }
        }

        error_log("[v0] storeUserRole completed successfully");
        $_SESSION['success_message'] = "{$successMessage} para {$persona['nombres']} {$persona['apellidos']}.";
        header('Location: ./index.php?route=admin/users/personas');
        return;
    }

    /**
     * Revoca el rol de un usuario (por person_id).
     * - Si era Líder de Vereda (rol 2): libera/veredas (deleteByHabitanteId)
     * - Si era Jefe de Familia (rol 3): elimina las cargas familiares asociadas al jefe (solo filas de carga_familiar)
     * - Finalmente actualiza el usuario para quitarle el rol (id_rol = NULL)
     */
    public function revokeUserRole() {
        $personId = filter_input(INPUT_GET, 'person_id', FILTER_VALIDATE_INT);
        if (empty($personId)) {
            $_SESSION['error_message'] = 'ID de persona no especificado para revocar rol.';
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $usuario = $this->usuarioModel->findByPersonId($personId);
        if (!$usuario) {
            $_SESSION['error_message'] = 'No se encontró una cuenta de usuario asociada a esa persona.';
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $usuarioId = $usuario['id_usuario'];
        $rolActual = (int)($usuario['id_rol'] ?? 0);

        // Obtener habitante si existe
        $habitante = $this->habitanteModel->findByPersonaId($personId);
        $habitanteId = $habitante ? $habitante['id_habitante'] : null;

        // Si era líder de vereda, liberar veredas
        if ($rolActual === 2 && $habitanteId) {
            $this->liderCalleModel->deleteByHabitanteId($habitanteId);
        }

        // Si era jefe de familia, eliminar cargas familiares donde fue jefe (solo registros de carga_familiar)
        if ($rolActual === 3 && $habitanteId) {
            // Usar consulta preparada por seguridad
            $sql = "DELETE FROM carga_familiar WHERE id_jefe = ?";
            $stmt = $this->cargaFamiliarModel->getConnection()->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $habitanteId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Finalmente quitar el rol del usuario (establecer NULL)
        $updateResult = $this->usuarioModel->update($usuarioId, ['id_rol' => null]);

        if ($updateResult) {
            $_SESSION['success_message'] = 'Rol revocado correctamente.';
        } else {
            $_SESSION['error_message'] = 'Ocurrió un error al intentar revocar el rol.';
        }

        header('Location: ./index.php?route=admin/users/personas');
        return;
    }

  public function manageNews() {
    $noticias = $this->noticiaModel->getAllNews(false, ['column' => 'fecha_publicacion', 'direction' => 'DESC']);

    // Simplificación y estandarización de mensajes de sesión
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);

    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['error_message']);

    $data = [
        'title' => 'Gestión de Noticias',
        'page_title' => 'Gestión de Noticias',
        'noticias' => $noticias,
        'success_message' => $success_message,
        'error_message' => $error_message
    ];

    $this->renderAdminView('news/index', $data);
}

//---

public function createNews() {
    $old_data = $_SESSION['old_form_data'] ?? [];
    unset($_SESSION['old_form_data']);

    // CORRECCIÓN: Estandarizar la variable de errores a 'errors'
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    $categorias = $this->categoriaModel->getAllCategories();

    $data = [
        'title' => 'Crear Nueva Noticia',
        'page_title' => 'Crear Noticia',
        'errors' => $errors,
        'old_data' => $old_data,
        'categorias' => $categorias
    ];

    $this->renderAdminView('news/create', $data);
}

//---

public function storeNews() {
    // 1. Verificación del método y preparación de constantes
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }

    // Define la carpeta de subida
    // CORRECCIÓN: el directorio 'public' está dos niveles por encima de este controlador
    $base_public_path = realpath(__DIR__ . '/../../public');
    if (!$base_public_path) {
        // Fallback si realpath falla (evita paths vacíos)
        $base_public_path = dirname(__DIR__, 2) . '/public';
    }
    $upload_dir = rtrim($base_public_path, '/\\') . '/uploads/noticias/';
    $errors = [];

    // 2. Recolección, Saneamiento y Validación
    $data = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'contenido' => trim($_POST['contenido'] ?? ''),
        'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
        // El modelo espera 'estado' o maneja 'activo'. Usamos 'activo' y permitimos que el modelo lo traduzca.
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];

    // CRÍTICO: Inyectar el id_usuario. El modelo espera 'id_usuario'.
    // Asume que la sesión del usuario está en $_SESSION['id_usuario']
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    if (empty($id_usuario)) {
        $errors['auth'] = 'Debe iniciar sesión para publicar una noticia. ID de usuario no encontrado.';
    }
    $data['id_usuario'] = $id_usuario; // Inyectar al array de datos para el modelo

    if (empty($data['titulo'])) {
        $errors['titulo'] = 'El título es obligatorio.';
    }
    if (empty($data['contenido'])) {
        $errors['contenido'] = 'El contenido es obligatorio.';
    }
    if ($data['id_categoria'] === false || $data['id_categoria'] === null) {
        $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
    }

    // 3. Manejo de Subida de Archivos
    $imagen_principal_path = null;
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {

        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors['imagen_principal'] = 'Error al crear el directorio de subida. (Revisar permisos)';
            }
        }

        if (empty($errors['imagen_principal'])) {
            $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('news_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Verificación MIME Type (más seguro)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors['imagen_principal'] = 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.';
            } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
            } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                // Ruta relativa a la carpeta 'public' para guardar en la BD
                $imagen_principal_path = 'uploads/noticias/' . $file_name;
            } else {
                $errors['imagen_principal'] = 'Error desconocido al mover la imagen. (Revisar permisos)';
            }
        }
    } elseif (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['imagen_principal'] = 'Error en la subida del archivo: Código ' . $_FILES['imagen_principal']['error'];
    }

    // Agregar la ruta de la imagen a los datos de la noticia
    $data['imagen_principal'] = $imagen_principal_path;

    // 4. Manejo de Errores y Redirección
    if (!empty($errors)) {
        $_SESSION['old_form_data'] = $_POST;
        $_SESSION['errors'] = $errors; // CORRECCIÓN: Estandarizado a 'errors'
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }

    // 5. Inserción en el Modelo
    $new_news_id = $this->noticiaModel->createNews($data); // Usamos $data directamente

    if ($new_news_id) {
        $_SESSION['success_message'] = "Noticia creada exitosamente con ID: " . $new_news_id;
        header('Location: ./index.php?route=admin/news');
        exit();
    } else {
        // Fallo de base de datos o fallo de validación NOT NULL en el modelo
        $_SESSION['errors'] = ['Error al crear la noticia en la base de datos. Consulta los logs para más detalles.'];
        $_SESSION['old_form_data'] = $_POST;
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }
}

//---

public function editNews($id) {
    if (!is_numeric($id) || $id <= 0) {
        $_SESSION['error_message'] = "ID de noticia inválido para edición.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Obtener la noticia sin el filtro 'activo' para poder editar borradores
    $news = $this->noticiaModel->getNewsById($id, false);

    if (!$news) {
        $_SESSION['error_message'] = "Noticia no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    $old_data = $_SESSION['old_form_data'] ?? [];
    unset($_SESSION['old_form_data']);

    // CORRECCIÓN: Estandarizar la variable de errores a 'errors'
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    // Combinar datos existentes con datos antiguos si falló la última actualización
    $news_data_for_form = array_merge($news, $old_data);

    $categorias = $this->categoriaModel->getAllCategories();

    $data = [
        'title' => 'Editar Noticia',
        'page_title' => 'Editar Noticia',
        'news' => $news_data_for_form,
        'errors' => $errors,
        'categorias' => $categorias
    ];

    $this->renderAdminView('news/edit', $data);
}

//---

public function updateNews() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Define la carpeta de subida
    $base_public_path = realpath(__DIR__ . '/../../public');
    if (!$base_public_path) {
        $base_public_path = dirname(__DIR__, 2) . '/public';
    }
    $upload_dir = rtrim($base_public_path, '/\\') . '/uploads/noticias/';
    $errors = [];

    $id_noticia = filter_input(INPUT_POST, 'id_noticia', FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($id_noticia) || $id_noticia <= 0) {
        $_SESSION['error_message'] = "Error: ID de noticia inválido en el formulario de actualización.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Obtener noticia actual para tener la imagen original y actualizar el registro
    // Asumo que 'noticiaModel' ya está instanciado en el constructor del controlador
    $current_news = $this->noticiaModel->getNewsById($id_noticia, false);
    if (!$current_news) {
        $_SESSION['error_message'] = "Noticia a actualizar no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Convertir 'activo' (checkbox) a 'estado' (publicado/borrador)
    $estado_post = (isset($_POST['estado']) && $_POST['estado'] === 'publicado') ? 'publicado' : 'borrador';

    // Inicializar datos con valores actuales y sobreescribir con POST
    $data_to_update = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'contenido' => trim($_POST['contenido'] ?? ''),
        'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
        // CRÍTICO: Usamos 'estado' en lugar de 'activo' para que coincida con el modelo y la vista de edición
        'estado' => $estado_post,
        // Mantener la imagen actual por defecto (se sobreescribirá más abajo)
        'imagen_principal' => $current_news['imagen_principal']
    ];

    // Usar el ID de usuario original de la noticia, ya que solo estamos actualizando contenido.
    // Si quisieras que el usuario logueado fuera el que "modificó" la noticia, deberías añadir
    // una columna 'id_usuario_modificacion' o algo similar. Por ahora, conservamos el ID original.
    $data_to_update['id_usuario'] = $current_news['id_usuario'];


    // Validaciones
    if (empty($data_to_update['titulo'])) {
        $errors['titulo'] = 'El título de la noticia es obligatorio.';
    }
    if (empty($data_to_update['contenido'])) {
        $errors['contenido'] = 'El contenido de la noticia es obligatorio.';
    }
    // CRÍTICO: Validar id_usuario (aunque conservamos el original, es bueno verificarlo)
    if (empty($data_to_update['id_usuario'])) {
        $errors['id_usuario'] = 'No se pudo determinar el usuario publicador de la noticia original.';
    }
    if ($data_to_update['id_categoria'] === false || $data_to_update['id_categoria'] === null) {
        $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
    }

    // 6. Manejo de Subida/Actualización de Imagen (Misma lógica, sin cambios esenciales)
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
        }
    }

    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
        // Lógica de subida y validación de la nueva imagen
        $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('news_') . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $errors['imagen_principal'] = 'Tipo de archivo no permitido.';
        } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) {
            $errors['imagen_principal'] = 'La imagen es demasiado grande.';
        } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
            // Eliminar imagen antigua
            $old_image_path = $base_public_path . '/' . $current_news['imagen_principal'];
            if (!empty($current_news['imagen_principal']) && file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
            // Asignar nueva ruta relativa
            $data_to_update['imagen_principal'] = 'uploads/noticias/' . $file_name;
        } else {
            $errors['imagen_principal'] = 'Error al subir la nueva imagen.';
        }
    } elseif (isset($_POST['remove_imagen_principal']) && $_POST['remove_imagen_principal'] === '1') {
        // Lógica para eliminar la imagen
        $old_image_path = $base_public_path . '/' . $current_news['imagen_principal'];
        if (!empty($current_news['imagen_principal']) && file_exists($old_image_path)) {
            @unlink($old_image_path);
        }
        $data_to_update['imagen_principal'] = null;
    }
    // Si no se subió una nueva imagen ni se eliminó, $data_to_update['imagen_principal'] conserva la ruta original (valor de $current_news['imagen_principal']).


    // 7. Manejo de Errores y Redirección
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_form_data'] = $data_to_update; // Enviar todos los datos de vuelta
        header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
        exit();
    }

    // 8. Actualización (PUNTO CRÍTICO CORREGIDO)
    // El método updateNews devuelve TRUE si se afectó al menos una fila, y FALSE si hubo un error O no se afectaron filas.
    $result = $this->noticiaModel->updateNews($id_noticia, $data_to_update);

    // LÓGICA CORREGIDA: Si $result es TRUE (se actualizó) O si la noticia actual es igual
    // a los datos enviados (el modelo devuelve false porque 0 filas fueron afectadas).
    // Nota: El modelo devuelve true si affected_rows > 0.

    if ($result || $this->noticiaModel->checkIfDataMatches($id_noticia, $data_to_update)) {
        // Nota: Si updateNews devuelve FALSE y no hubo error de BD, significa que no hubo cambios.
        // En este caso, asumimos éxito.

        $_SESSION['success_message'] = "Noticia actualizada exitosamente.";
        header('Location: ./index.php?route=admin/news');
        exit();
    } else {
        // Si $result es FALSE y no fue por 0 filas afectadas, entonces hubo un error real de DB.
        $_SESSION['errors'] = ['Error grave al actualizar la noticia en la base de datos. Por favor, inténtelo de nuevo.'];
        $_SESSION['old_form_data'] = $data_to_update;
        header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
        exit();
    }
}

//---

public function deleteNews($id) {
    if (!is_numeric($id) || $id <= 0) {
        $_SESSION['error_message'] = "ID de noticia inválido o no proporcionado para la eliminación.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    $newsToDelete = $this->noticiaModel->getNewsById($id, false);
    if (!$newsToDelete) {
        $_SESSION['error_message'] = "Noticia a eliminar no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Define la ruta base para eliminar archivos
    $base_public_path = realpath(__DIR__ . '/../../public');
    if (!$base_public_path) { $base_public_path = dirname(__DIR__, 2) . '/public'; }

    if (!empty($newsToDelete['imagen_principal'])) {
        $image_path_on_disk = $base_public_path . '/' . $newsToDelete['imagen_principal'];
        if (file_exists($image_path_on_disk)) {
            if (!@unlink($image_path_on_disk)) { // Usar @ para manejar errores silenciosamente
                error_log("Error al eliminar el archivo de imagen: " . $image_path_on_disk);
            }
        } else {
            error_log("Advertencia: La imagen principal referenciada no existe en el disco: " . $image_path_on_disk);
        }
    }

    if ($this->noticiaModel->deleteNews($id)) {
        $_SESSION['success_message'] = "La noticia '" . htmlspecialchars($newsToDelete['titulo']) . "' ha sido eliminada exitosamente.";
    } else {
        $_SESSION['error_message'] = "Hubo un error al intentar eliminar la noticia '" . htmlspecialchars($newsToDelete['titulo']) . "'.";
    }

    header('Location: ./index.php?route=admin/news');
    exit();
}

 public function manageComments() {
        // 1. Obtener TODOS los comentarios (activos e inactivos)
        $todosLosComentarios = $this->comentarioModel->getAllComments(false);

        $noticiasConConteo = [];
        $noticiasProcesadas = [];

        // 2. Procesar para obtener noticias únicas y su conteo total de comentarios
        foreach ($todosLosComentarios as $comentario) {
            $id = $comentario['id_noticia'];

            // Si es la primera vez que vemos esta noticia, la agregamos
            if (!isset($noticiasProcesadas[$id])) {
                $noticiasProcesadas[$id] = [
                    'id_noticia' => $id,
                    'titulo_noticia' => $comentario['titulo_noticia'],
                    'conteo' => 0
                ];
            }
            // Incrementar el conteo para esta noticia
            $noticiasProcesadas[$id]['conteo']++;
        }

        // Convertir el array asociativo a un array indexado para la vista
        $noticiasConConteo = array_values($noticiasProcesadas);

        $this->renderAdminView('comentarios/index', [
            'page_title' => 'Gestión de Comentarios',
            'noticias' => $noticiasConConteo // Cambiado de 'comentarios' a 'noticias'
        ]);
    }


    public function getCommentsByNoticia() {
        header('Content-Type: application/json');

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'ID de noticia inválido']);
            exit;
        }

        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia((int)$id, false);

        $titulo = "";

        if (!empty($comentarios)) {
            $titulo = $comentarios[0]['titulo_noticia'] ?? $this->noticiaModel->getById((int)$id)['titulo'] ?? 'Noticia sin título';
        } else {
            $noticia = $this->noticiaModel->getById((int)$id);
            $titulo = $noticia['titulo'] ?? 'Noticia';
        }

        echo json_encode([
            'success' => true,
            'titulo' => $titulo,
            'comentarios' => $comentarios
        ]);
        exit;
    }

    public function softDeleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->softDeleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado lógicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar lógicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

  public function activateComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->activarComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario activado.";
        } else {
            $_SESSION['error_message'] = "Error al activar el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

 public function deleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido para eliminación física.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->deleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado físicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar físicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

    public function manageNotifications() {
        $notificaciones = $this->notificacionModel->getAllNotifications();
        $data = [
            'title' => 'Gestión de Notificaciones',
            'page_title' => 'Gestión de Notificaciones',
            'notificaciones' => $notificaciones
        ];
        $this->renderAdminView('notifications/index', $data);
    }

    public function markNotificationRead($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: ./index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->update($id, ['leida' => 1]);
        if ($result) {
            $_SESSION['success_message'] = "Notificación marcada como leída.";
        } else {
            $_SESSION['error_message'] = "Error al marcar notificación como leída.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }

    public function markAllNotificationsRead() {
        $result = $this->notificacionModel->marcarTodasComoLeidas($_SESSION['id_usuario']);
        if ($result) {
            $_SESSION['success_message'] = "Todas las notificaciones marcadas como leídas.";
        } else {
            $_SESSION['error_message'] = "Error al marcar todas las notificaciones como leídas.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }

    public function deleteNotification($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: ./index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->delete($id);
        if ($result) {
            $_SESSION['success_message'] = "Notificación eliminada.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar la notificación.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }


public function reports() {

    $usuarios = $this->usuarioModel->getAll();
    $noticias = $this->noticiaModel->getAll();

    $data = [
        'title' => 'Reportes',
        'page_title' => 'Reportes de Administración',
        'usuarios' => $usuarios,
        'noticias' => $noticias
    ];

    $this->renderAdminView('reports', $data);
}

// ==================== GESTIÓN DE VIVIENDAS ====================

public function viviendas() {
    // Obtener todas las calles para el selector
    $calles = $this->calleModel->findAll();
    
    // Renderizar la vista principal de viviendas
    $data = [
        'title' => 'Gestión de Viviendas',
        'page_title' => 'Gestión de Viviendas',
        'calles' => $calles
    ];
    $this->renderAdminView('vivienda/index', $data);
}

// API endpoints para viviendas (llamados vía AJAX)
public function viviendasIndex() {
    header('Content-Type: application/json');
    try {
        $viviendas = $this->viviendaModel->getAllWithCalle();
        echo json_encode($viviendas);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener viviendas: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Devuelve viviendas de una vereda (calle) con el conteo de familias por vivienda.
 * Acción accesible vía AJAX: ?route=admin/viviendas&action=byCalle&id=X
 */
public function viviendasByCalle() {
    header('Content-Type: application/json');
    $idCalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idCalle <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de calle inválido']);
        exit;
    }

    $sql = "SELECT v.id_vivienda, v.numero, v.tipo, v.estado, v.activo,
                   COALESCE( (
                       SELECT COUNT(DISTINCT cf.id_jefe)
                       FROM carga_familiar cf
                       INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                       INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                       WHERE hv.id_vivienda = v.id_vivienda AND cf.activo = 1
                   ), 0) AS total_familias
            FROM vivienda v
            WHERE v.id_calle = ? AND v.activo = 1
            ORDER BY v.numero ASC";

    $stmt = $this->viviendaModel->getConnection()->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al preparar la consulta']);
        exit;
    }

    $stmt->bind_param('i', $idCalle);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $result->free();
    }
    $stmt->close();

    echo json_encode($data);
    exit;
}

/**
 * Devuelve las familias (y sus miembros) que residen en una vivienda.
 * Parámetro: id (id_vivienda)
 * Acción accesible vía AJAX: ?route=admin/viviendas&action=familiasPorVivienda&id=X
 */
public function familiasPorVivienda() {
    header('Content-Type: application/json');
    $idVivienda = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idVivienda <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de vivienda inválido']);
        exit;
    }

    // Obtener los jefes de familia que viven en esta vivienda
    $sql = "SELECT DISTINCT cf.id_jefe
            FROM carga_familiar cf
            INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
            INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
            WHERE hv.id_vivienda = ? AND cf.activo = 1";

    $db = $this->cargaFamiliarModel->getConnection();
    $stmt = $db->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al preparar consulta de familias']);
        exit;
    }
    $stmt->bind_param('i', $idVivienda);
    $stmt->execute();
    $res = $stmt->get_result();
    $jefes = [];
    while ($r = $res->fetch_assoc()) {
        $jefes[] = (int)$r['id_jefe'];
    }
    $stmt->close();

    $familias = [];
    foreach ($jefes as $jefeId) {
        // Reutilizar el método del modelo para obtener los miembros de la familia
        $miembros = $this->cargaFamiliarModel->getCargaFamiliarConDatos($jefeId);
        $familias[] = [
            'id_jefe' => $jefeId,
            'miembros' => $miembros
        ];
    }

    echo json_encode($familias);
    exit;
}

public function viviendasShow($id) {
    header('Content-Type: application/json');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    $vivienda = $this->viviendaModel->getById($id);
    if (!$vivienda) {
        http_response_code(404);
        echo json_encode(['error' => 'Vivienda no encontrada']);
        exit;
    }
    echo json_encode($vivienda);
    exit;
}

public function viviendasStore() {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['numero']) || empty($input['tipo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }
    
    // Validaciones: numero numeric y hasta 3 dígitos
    $numero = trim((string)$input['numero']);
    if (!preg_match('/^\d{1,3}$/', $numero)) {
        http_response_code(400);
        echo json_encode(['error' => 'El número debe ser numérico y tener máximo 3 dígitos.']);
        exit;
    }

    $data = [
        'numero' => $numero,
        'tipo' => $input['tipo'],
        'estado' => $input['estado'] ?? 'Activo',
        'activo' => 1
    ];
    
    // Campos opcionales
    if (!empty($input['id_calle'])) $data['id_calle'] = (int)$input['id_calle'];

    // Verificar unicidad por calle
    if (!empty($data['id_calle'])) {
        $exists = $this->viviendaModel->existsNumeroEnCalle($data['numero'], (int)$data['id_calle']);
        if ($exists) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe una vivienda con ese número en la vereda seleccionada.']);
            exit;
        }
    }
    
    $id = $this->viviendaModel->createVivienda($data);
    
    if ($id) {
        echo json_encode(['message' => 'Vivienda creada exitosamente', 'id_vivienda' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear vivienda']);
    }
    exit;
}

public function viviendasUpdate($id) {
    header('Content-Type: application/json');
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }
    
    $data = [];
    if (isset($input['numero'])) {
        $numero = trim((string)$input['numero']);
        if (!preg_match('/^\d{1,3}$/', $numero)) {
            http_response_code(400);
            echo json_encode(['error' => 'El número debe ser numérico y tener máximo 3 dígitos.']);
            exit;
        }
        $data['numero'] = $numero;
    }
    if (isset($input['tipo'])) $data['tipo'] = $input['tipo'];
    if (isset($input['estado'])) $data['estado'] = $input['estado'];
    if (isset($input['id_calle'])) $data['id_calle'] = $input['id_calle'];

    // Si se actualiza número o calle, verificar unicidad
    $checkNumero = $data['numero'] ?? null;
    $checkCalle = isset($data['id_calle']) ? (int)$data['id_calle'] : (int)$this->viviendaModel->getById($id)['id_calle'];
    if ($checkNumero !== null) {
        $exists = $this->viviendaModel->existsNumeroEnCalle($checkNumero, $checkCalle, (int)$id);
        if ($exists) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe una vivienda con ese número en la vereda seleccionada.']);
            exit;
        }
    }

    $result = $this->viviendaModel->updateVivienda($id, $data);
    
    if ($result) {
        echo json_encode(['message' => 'Vivienda actualizada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar vivienda']);
    }
    exit;
}

public function viviendasDestroy($id) {
    header('Content-Type: application/json');
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    
    // Soft delete
    $result = $this->viviendaModel->softDelete($id);
    
    if ($result) {
        echo json_encode(['message' => 'Vivienda eliminada exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar vivienda']);
    }
    exit;
}

// ==================== GESTIÓN DE CARGA FAMILIAR ====================

/**
 * Muestra la carga familiar del usuario actual
 * Solo disponible si el usuario es jefe de familia
 */
public function cargaFamiliar() {
    $idUsuario = $_SESSION['id_usuario'] ?? null;
    
    if (!$idUsuario) {
        $_SESSION['error_message'] = 'Usuario no autenticado';
        header('Location: ./index.php?route=login');
        exit();
    }
    
    // Obtener carga familiar del usuario
    $cargaFamiliar = $this->cargaFamiliarModel->getCargaFamiliarPorUsuario($idUsuario);
    
    // Verificar si es jefe de familia
    $esJefeFamilia = $cargaFamiliar !== false;
    
    $data = [
        'page_title' => 'Mi Carga Familiar',
        'carga_familiar' => $cargaFamiliar ?: [],
        'es_jefe_familia' => $esJefeFamilia,
        'total_miembros' => $esJefeFamilia ? count($cargaFamiliar) : 0
    ];
    
    $this->renderAdminView('carga_familiar/index', $data);
}

/**
 * Página para el administrador: ver todas las cargas familiares de la comunidad
 */
public function cargasFamiliaresAll() {
    // Consulta para obtener familias con info de casa y vereda
    $sql = "SELECT cf.id_carga, cf.id_jefe, h.id_persona, p.cedula, p.nombres, p.apellidos,
                   v.id_vivienda, v.numero AS numero_casa, c.id_calle, c.nombre AS nombre_vereda
            FROM carga_familiar cf
            INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
            LEFT JOIN persona p ON h.id_persona = p.id_persona
            LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante AND hv.es_jefe_familia = 1
            LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
            LEFT JOIN calle c ON v.id_calle = c.id_calle
            WHERE cf.activo = 1
            ORDER BY c.nombre ASC, v.numero ASC";

    $db = $this->cargaFamiliarModel->getConnection();
    $stmt = $db->prepare($sql);
    $familias = [];
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $familias[] = $row;
        }
        $stmt->close();
    }

    $data = [
        'page_title' => 'Todas las Cargas Familiares',
        'familias' => $familias
    ];

    $this->renderAdminView('carga_familiar/all', $data);
}

/**
 * Devuelve miembros de familia por id de jefe (para AJAX desde la vista de admin)
 * Parámetro: jefe
 */
public function familiasPorViviendaByJefe() {
    header('Content-Type: application/json');
    $jefe = isset($_GET['jefe']) ? (int)$_GET['jefe'] : 0;
    if ($jefe <= 0) {
        http_response_code(400);
        echo json_encode([]);
        exit;
    }

    $miembros = $this->cargaFamiliarModel->getCargaFamiliarConDatos($jefe);
    echo json_encode($miembros ?: []);
    exit;
}

// ==================== REPORTES DETALLADOS - APIs JSON ====================

/**
 * Reporte completo de habitantes con toda su información relacionada
 */
public function reporteHabitantes() {
    header('Content-Type: application/json');

    try {
    $sql = "SELECT
            h.id_habitante,
            h.condicion,
            h.fecha_ingreso,
            h.fecha_registro AS habitante_fecha_registro,
            p.id_persona,
            p.cedula,
            p.nombres,
            p.apellidos,
            CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
            p.fecha_nacimiento,
            TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
            p.sexo,
            p.telefono,
            p.correo,
            p.direccion,
            c.id_calle,
            c.nombre AS calle_nombre,
            c.sector AS calle_sector,
            v.id_vivienda,
            v.numero AS vivienda_numero,
            v.tipo AS vivienda_tipo,
            v.estado AS vivienda_estado,
            hv.es_jefe_familia,
            hv.fecha_inicio AS fecha_asignacion_vivienda,
            u.id_usuario,
            u.email AS usuario_email,
            u.fecha_registro AS usuario_fecha_creacion,
            NULL AS usuario_ultimo_acceso,
            r.id_rol,
            r.nombre AS rol_nombre,
            (SELECT COUNT(*) FROM habitante_vivienda hv2 
             WHERE hv2.id_vivienda = hv.id_vivienda 
             AND hv2.id_habitante != h.id_habitante) AS total_familiares,
            (SELECT GROUP_CONCAT(CONCAT(pl.nombres, ' ', pl.apellidos) SEPARATOR ', ')
             FROM lider_calle lc
             INNER JOIN habitante hl ON lc.id_habitante = hl.id_habitante
             INNER JOIN persona pl ON hl.id_persona = pl.id_persona
             WHERE lc.id_calle = c.id_calle AND lc.activo = 1) AS lideres_calle
        FROM habitante h
                INNER JOIN persona p ON h.id_persona = p.id_persona
                LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                LEFT JOIN calle c ON v.id_calle = c.id_calle
                LEFT JOIN usuario u ON p.id_persona = u.id_persona
                LEFT JOIN rol r ON u.id_rol = r.id_rol
                WHERE h.activo = 1 AND p.activo = 1
                ORDER BY c.nombre ASC, v.numero ASC, p.apellidos ASC, p.nombres ASC";

        $db = $this->habitanteModel->getConnection();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception($db->error);
        }
        
        $habitantes = [];
        while ($row = $result->fetch_assoc()) {
            $habitantes[] = $row;
        }
        $result->free();

        echo json_encode($habitantes);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener habitantes: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Reporte de habitantes por calle específica
 */
public function reporteHabitantesPorCalle() {
    header('Content-Type: application/json');

    $idCalle = isset($_GET['id_calle']) ? (int)$_GET['id_calle'] : 0;

    if ($idCalle <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de calle requerido']);
        exit;
    }

    $sql = "SELECT
                h.id_habitante,
                h.condicion,
                NULL AS ocupacion,
                NULL AS nivel_educativo,
                NULL AS estado_civil,
                p.cedula,
                p.nombres,
                p.apellidos,
                CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                p.fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
                p.sexo,
                p.telefono,
                p.correo,
                c.nombre AS calle_nombre,
                c.sector AS calle_sector,
                v.numero AS vivienda_numero,
                v.tipo AS vivienda_tipo,
                v.estado AS vivienda_estado,
                hv.es_jefe_familia,
                (SELECT COUNT(*) FROM carga_familiar cf WHERE cf.id_jefe = h.id_habitante AND cf.activo = 1) AS total_familiares,
                (SELECT GROUP_CONCAT(CONCAT(pl.nombres, ' ', pl.apellidos) SEPARATOR ', ')
                 FROM lider_calle lc
                 INNER JOIN habitante hl ON lc.id_habitante = hl.id_habitante
                 INNER JOIN persona pl ON hl.id_persona = pl.id_persona
                 WHERE lc.id_calle = c.id_calle AND lc.activo = 1) AS lideres_calle
            FROM habitante h
            INNER JOIN persona p ON h.id_persona = p.id_persona
            INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
            INNER JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
            INNER JOIN calle c ON v.id_calle = c.id_calle
            WHERE h.activo = 1 AND p.activo = 1 AND c.id_calle = ?
            ORDER BY v.numero ASC, p.apellidos ASC, p.nombres ASC";

    $stmt = $this->habitanteModel->getConnection()->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al preparar la consulta']);
        exit;
    }

    $stmt->bind_param('i', $idCalle);
    $stmt->execute();
    $result = $stmt->get_result();
    $habitantes = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $habitantes[] = $row;
        }
        $result->free();
    }
    $stmt->close();

    echo json_encode($habitantes);
    exit;
}

/**
 * Reporte de viviendas con información completa
 */
public function reporteViviendas() {
    header('Content-Type: application/json');

    try {
        $sql = "SELECT
                v.id_vivienda,
                v.numero,
                v.tipo,
                v.estado,
                v.fecha_registro AS vivienda_fecha_registro,
                c.id_calle,
                c.nombre AS calle_nombre,
                c.sector AS calle_sector,
                c.descripcion AS calle_descripcion,
                (SELECT COUNT(*) FROM habitante_vivienda hv WHERE hv.id_vivienda = v.id_vivienda) AS total_habitantes,
                (SELECT COUNT(DISTINCT cf.id_jefe)
                 FROM carga_familiar cf
                 INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                 INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                 WHERE hv.id_vivienda = v.id_vivienda AND cf.activo = 1) AS total_familias,
                (SELECT GROUP_CONCAT(CONCAT(p.nombres, ' ', p.apellidos) SEPARATOR ', ')
                 FROM habitante_vivienda hv
                 INNER JOIN habitante h ON hv.id_habitante = h.id_habitante
                 INNER JOIN persona p ON h.id_persona = p.id_persona
                 WHERE hv.id_vivienda = v.id_vivienda AND hv.es_jefe_familia = 1) AS jefes_familia,
                (SELECT GROUP_CONCAT(CONCAT(pl.nombres, ' ', pl.apellidos) SEPARATOR ', ')
                 FROM lider_calle lc
                 INNER JOIN habitante hl ON lc.id_habitante = hl.id_habitante
                 INNER JOIN persona pl ON hl.id_persona = pl.id_persona
                 WHERE lc.id_calle = c.id_calle AND lc.activo = 1) AS lideres_calle,
                vd.habitaciones,
                vd.banos,
                vd.servicios,
                vd.servicios AS vivienda_observaciones
            FROM vivienda v
            INNER JOIN calle c ON v.id_calle = c.id_calle
            LEFT JOIN vivienda_detalle vd ON v.id_vivienda = vd.id_vivienda
            WHERE v.activo = 1
            ORDER BY c.nombre ASC, v.numero ASC";

        $db = $this->viviendaModel->getConnection();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception($db->error);
        }
        
        $viviendas = [];
        while ($row = $result->fetch_assoc()) {
            $viviendas[] = $row;
        }
        $result->free();

        echo json_encode($viviendas);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener viviendas: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Reporte de familias con todos sus miembros
 */
public function reporteFamilias() {
    header('Content-Type: application/json');

    try {
        $sql = "SELECT DISTINCT
                cf.id_jefe,
                pj.id_persona AS jefe_id_persona,
                pj.cedula AS jefe_cedula,
                pj.nombres AS jefe_nombres,
                pj.apellidos AS jefe_apellidos,
                CONCAT(pj.nombres, ' ', pj.apellidos) AS jefe_nombre_completo,
                TIMESTAMPDIFF(YEAR, pj.fecha_nacimiento, CURDATE()) AS jefe_edad,
                pj.telefono AS jefe_telefono,
                pj.correo AS jefe_correo,
                pj.sexo AS jefe_sexo,
                NULL AS jefe_ocupacion,
                NULL AS jefe_nivel_educativo,
                c.id_calle,
                c.nombre AS calle_nombre,
                c.sector AS calle_sector,
                v.id_vivienda,
                v.numero AS vivienda_numero,
                v.tipo AS vivienda_tipo,
                v.estado AS vivienda_estado,
                (SELECT COUNT(*) FROM carga_familiar cf2 WHERE cf2.id_jefe = cf.id_jefe AND cf2.activo = 1) AS total_miembros,
                (SELECT GROUP_CONCAT(CONCAT(pl.nombres, ' ', pl.apellidos) SEPARATOR ', ')
                 FROM lider_calle lc
                 INNER JOIN habitante hl ON lc.id_habitante = hl.id_habitante
                 INNER JOIN persona pl ON hl.id_persona = pl.id_persona
                 WHERE lc.id_calle = c.id_calle AND lc.activo = 1) AS lideres_calle
            FROM carga_familiar cf
            INNER JOIN habitante hj ON cf.id_jefe = hj.id_habitante
            INNER JOIN persona pj ON hj.id_persona = pj.id_persona
            LEFT JOIN habitante_vivienda hv ON hj.id_habitante = hv.id_habitante AND hv.es_jefe_familia = 1
            LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
            LEFT JOIN calle c ON v.id_calle = c.id_calle
            WHERE cf.activo = 1
            GROUP BY cf.id_jefe
            ORDER BY c.nombre ASC, v.numero ASC, pj.apellidos ASC";

        $db = $this->cargaFamiliarModel->getConnection();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception($db->error);
        }
        
        $familias = [];
        while ($row = $result->fetch_assoc()) {
            $idJefe = $row['id_jefe'];
            
            // Obtener miembros de la familia
            $sqlMiembros = "SELECT
                                cf.id_carga,
                                cf.parentesco,
                                p.cedula,
                                p.nombres,
                                p.apellidos,
                                CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
                                p.sexo,
                                p.telefono,
                                p.correo,
                                NULL AS ocupacion,
                                NULL AS nivel_educativo,
                                NULL AS estado_civil
                            FROM carga_familiar cf
                            INNER JOIN habitante h ON cf.id_miembro = h.id_habitante
                            INNER JOIN persona p ON h.id_persona = p.id_persona
                            WHERE cf.id_jefe = ? AND cf.activo = 1
                            ORDER BY p.apellidos ASC, p.nombres ASC";
            
            $stmtMiembros = $db->prepare($sqlMiembros);
            $stmtMiembros->bind_param('i', $idJefe);
            $stmtMiembros->execute();
            $resultMiembros = $stmtMiembros->get_result();
            
            $miembros = [];
            while ($miembro = $resultMiembros->fetch_assoc()) {
                $miembros[] = $miembro;
            }
            $stmtMiembros->close();
            
            $row['miembros'] = $miembros;
            $familias[] = $row;
        }
        $result->free();

        echo json_encode($familias);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener familias: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Reporte de usuarios del sistema con información completa
 */
public function reporteUsuarios() {
    header('Content-Type: application/json');

    try {
        $sql = "SELECT
                    u.id_usuario,
                    u.email,
                    u.fecha_registro AS fecha_creacion,
                    NULL AS ultimo_acceso,
                    u.activo AS usuario_activo,
                    p.id_persona,
                    p.cedula,
                    p.nombres,
                    p.apellidos,
                    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                    p.fecha_nacimiento,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
                    p.sexo,
                    p.telefono,
                    p.correo AS correo_personal,
                    p.direccion,
                    r.id_rol,
                    r.nombre AS rol_nombre,
                    r.descripcion AS rol_descripcion,
                    h.id_habitante,
                    h.condicion,
                    c.id_calle,
                    c.nombre AS calle_nombre,
                    c.sector AS calle_sector,
                    v.id_vivienda,
                    v.numero AS vivienda_numero,
                    v.tipo AS vivienda_tipo,
                    hv.es_jefe_familia,
                    (SELECT GROUP_CONCAT(CONCAT(ca.nombre, ' - ', ca.sector) SEPARATOR ', ')
                     FROM lider_calle lc
                     INNER JOIN calle ca ON lc.id_calle = ca.id_calle
                          WHERE lc.id_habitante = h.id_habitante AND lc.activo = 1) AS calles_asignadas,
                              (SELECT COUNT(*) FROM lider_calle lc WHERE lc.id_habitante = h.id_habitante AND lc.activo = 1) AS total_calles_asignadas
                FROM usuario u
                INNER JOIN persona p ON u.id_persona = p.id_persona
                LEFT JOIN rol r ON u.id_rol = r.id_rol
                LEFT JOIN habitante h ON p.id_persona = h.id_persona
                LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                LEFT JOIN calle c ON v.id_calle = c.id_calle
                WHERE u.activo = 1
                ORDER BY r.id_rol ASC, p.apellidos ASC, p.nombres ASC";

        $db = $this->usuarioModel->getConnection();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception($db->error);
        }
        
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        $result->free();

        echo json_encode($usuarios);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener usuarios: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Reporte de líderes de calle con sus asignaciones
 */
public function reporteLideresCalle() {
    header('Content-Type: application/json');

    $sql = "SELECT
                u.id_usuario,
                u.email,
                u.fecha_registro AS usuario_fecha_creacion,
                NULL AS ultimo_acceso,
                p.cedula,
                p.nombres,
                p.apellidos,
                CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
                p.telefono,
                p.correo,
                p.sexo,
                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
                NULL AS ocupacion,
                NULL AS nivel_educativo,
                (SELECT GROUP_CONCAT(CONCAT(ca.nombre, ' - ', ca.sector) SEPARATOR ', ')
                 FROM lider_calle lc
                 INNER JOIN calle ca ON lc.id_calle = ca.id_calle
                 WHERE lc.id_habitante = h.id_habitante AND lc.activo = 1) AS calles_asignadas,
                (SELECT COUNT(*) FROM lider_calle lc WHERE lc.id_habitante = h.id_habitante AND lc.activo = 1) AS total_calles,
                (SELECT MIN(lc.fecha_designacion) FROM lider_calle lc WHERE lc.id_habitante = h.id_habitante) AS fecha_designacion,
                (SELECT SUM(
                    (SELECT COUNT(*) FROM habitante_vivienda hv
                     INNER JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                     WHERE v.id_calle = lc2.id_calle)
                ) FROM lider_calle lc2 WHERE lc2.id_habitante = h.id_habitante AND lc2.activo = 1) AS total_habitantes_asignados
            FROM usuario u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            LEFT JOIN habitante h ON p.id_persona = h.id_persona
            WHERE u.id_rol = 2 AND u.activo = 1
            AND EXISTS (SELECT 1 FROM lider_calle lc WHERE lc.id_habitante = h.id_habitante AND lc.activo = 1)
            ORDER BY p.apellidos ASC, p.nombres ASC";

    $result = $this->usuarioModel->getConnection()->query($sql);
    $lideres = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lideres[] = $row;
        }
        $result->free();
    }

    echo json_encode($lideres);
    exit;
}

/**
 * Estadísticas generales del sistema
 */
public function reporteEstadisticas() {
    header('Content-Type: application/json');

    try {
        $db = $this->habitanteModel->getConnection();
        
        $stats = [
            'total_habitantes' => 0,
            'total_viviendas' => 0,
            'total_familias' => 0,
            'total_calles' => 0,
            'total_usuarios' => 0,
            'total_lideres' => 0
        ];
        
        // Total habitantes
        $result = $db->query("SELECT COUNT(*) as total FROM habitante WHERE activo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_habitantes'] = (int)$row['total'];
            $result->free();
        }
        
        // Total viviendas
        $result = $db->query("SELECT COUNT(*) as total FROM vivienda WHERE activo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_viviendas'] = (int)$row['total'];
            $result->free();
        }
        
        // Total familias (jefes de familia únicos)
        $result = $db->query("SELECT COUNT(DISTINCT id_habitante) as total FROM habitante_vivienda WHERE es_jefe_familia = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_familias'] = (int)$row['total'];
            $result->free();
        }
        
        // Total calles
        $result = $db->query("SELECT COUNT(*) as total FROM calle WHERE activo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_calles'] = (int)$row['total'];
            $result->free();
        }
        
        // Total usuarios
        $result = $db->query("SELECT COUNT(*) as total FROM usuario WHERE activo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_usuarios'] = (int)$row['total'];
            $result->free();
        }
        
        // Total líderes
        $result = $db->query("SELECT COUNT(DISTINCT id_habitante) as total FROM lider_calle WHERE activo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_lideres'] = (int)$row['total'];
            $result->free();
        }
        
        echo json_encode($stats);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener estadísticas: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== VISTAS DE REPORTES ====================

/**
 * Vista de reporte de habitantes
 */
public function vistaReporteHabitantes() {
    $data = [
        'title' => 'Reporte de Habitantes',
        'page_title' => 'Reporte de Habitantes'
    ];
    $this->renderAdminView('reportes/habitantes', $data);
}

/**
 * Vista de reporte de viviendas
 */
public function vistaReporteViviendas() {
    $data = [
        'title' => 'Reporte de Viviendas',
        'page_title' => 'Reporte de Viviendas'
    ];
    $this->renderAdminView('reportes/viviendas', $data);
}

/**
 * Vista de reporte de familias
 */
public function vistaReporteFamilias() {
    $data = [
        'title' => 'Reporte de Familias',
        'page_title' => 'Reporte de Familias'
    ];
    $this->renderAdminView('reportes/familias', $data);
}

/**
 * Vista de reporte de usuarios
 */
public function vistaReporteUsuarios() {
    $data = [
        'title' => 'Reporte de Usuarios',
        'page_title' => 'Reporte de Usuarios'
    ];
    $this->renderAdminView('reportes/usuarios', $data);
}

/**
 * Vista de reporte de líderes de calle
 */
public function vistaReporteLideres() {
    $data = [
        'title' => 'Reporte de Líderes de Calle',
        'page_title' => 'Reporte de Líderes de Calle'
    ];
    $this->renderAdminView('reportes/lideres', $data);
}

/**
 * Vista de reporte por calle
 */
public function vistaReportePorCalle() {
    $calles = $this->calleModel->findAll();
    // Ordenar veredas/calles por su número cuando el nombre contiene un número (p.ej. "Vereda 2", "Vereda 10")
    usort($calles, function($a, $b) {
        $nameA = $a['nombre'] ?? '';
        $nameB = $b['nombre'] ?? '';

        // Extraer primer número encontrado en el nombre
        preg_match('/(\d+)/', $nameA, $mA);
        preg_match('/(\d+)/', $nameB, $mB);

        if (!empty($mA) && !empty($mB)) {
            $nA = (int)$mA[1];
            $nB = (int)$mB[1];
            if ($nA === $nB) return strcmp($nameA, $nameB);
            return $nA - $nB;
        }

        // Si no hay número, ordenar alfabéticamente
        return strcmp($nameA, $nameB);
    });
    $data = [
        'title' => 'Reporte por Calle',
        'page_title' => 'Reporte por Calle',
        'calles' => $calles
    ];
    $this->renderAdminView('reportes/por-calle', $data);
}

}
