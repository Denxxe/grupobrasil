<?php
// grupobrasil/public/index.php
ob_start(); 
// --- Seguridad de sesión: configurar cookies antes de iniciar sesión ---
$appEnv = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? 'production');
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
];
// session_start() requiere parámetros de cookie vía session_set_cookie_params en PHP < 7.3
if (PHP_VERSION_ID < 70300) {
    session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
} else {
    session_set_cookie_params($cookieParams);
}

// Inicia la sesión al principio de todo si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de errores según entorno (desactivar display en producción)
error_reporting(E_ALL);
if ($appEnv === 'development') {
    ini_set('display_errors', 1);
    ini_set('log_errors', 0);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// --- Definición de Constantes de Ruta ---
define('APP_ROOT', __DIR__ . '/../');
define('CONFIG_PATH', APP_ROOT . 'config/');
define('MODELS_PATH', APP_ROOT . 'app/models/');
define('CONTROLLERS_PATH', APP_ROOT . 'app/controllers/');
define('VIEWS_PATH', APP_ROOT . 'app/views/');
define('UTILS_PATH', APP_ROOT . 'app/utils/');

// --- Carga de archivos de configuración y base de datos ---
require_once CONFIG_PATH . 'database.php'; // Solo cargar la clase, no asignar a $db

// --- Carga de modelos (asegúrate de que ModelBase se cargue primero si es una clase base) ---
require_once MODELS_PATH . 'ModelBase.php';
require_once MODELS_PATH . 'Usuario.php';
require_once MODELS_PATH . 'Persona.php'; // Asegúrate de incluir Persona.php aquí
require_once MODELS_PATH . 'Noticia.php';
require_once MODELS_PATH . 'Comentario.php';
require_once MODELS_PATH . 'Like.php';
require_once MODELS_PATH . 'Notificacion.php';
require_once MODELS_PATH . 'Categoria.php';
require_once MODELS_PATH . 'Calle.php'; 
require_once MODELS_PATH . 'LiderCalle.php';
require_once MODELS_PATH . 'Habitante.php'; // Added Habitante model
require_once MODELS_PATH . 'Vivienda.php'; // Added Vivienda model

// --- Carga de controladores ---
require_once CONTROLLERS_PATH . 'LoginController.php';
require_once CONTROLLERS_PATH . 'AdminController.php';
require_once CONTROLLERS_PATH . 'SubadminController.php';
require_once CONTROLLERS_PATH . 'NoticiaController.php';
require_once CONTROLLERS_PATH . 'UserController.php';
require_once CONTROLLERS_PATH . 'PagoController.php';
require_once UTILS_PATH . 'Validator.php'; // Tu clase de validación

// --- Manejo de Mensajes Flash de Sesión ---
// Recupera y borra los mensajes de la sesión al inicio de cada solicitud
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// --- Lógica para obtener la Ruta Solicitada ---
$route = filter_input(INPUT_GET, 'route', FILTER_SANITIZE_URL) ?? 'login';
$routeParts = explode('/', trim($route, '/'));

// --- Inicialización de Variables de Controlador y Acción ---
$controllerName = '';
$actionName = '';
$id = null;
$viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Página No Encontrada']]; // Valor predeterminado para la vista y datos

// --- Lógica de Enrutamiento Basada en Autenticación y Rol ---

// 1. Rutas para usuarios NO autenticados (Login/Registro)
if (!isset($_SESSION['id_usuario'])) {
    switch ($route) {
        case 'login':
        case '':
            $controllerName = 'LoginController';
            $actionName = 'index';
            break;
        case 'login/authenticate':
            $controllerName = 'LoginController';
            $actionName = 'login';
            break;
            
        default:
            $_SESSION['error_message'] = "Debes iniciar sesión para acceder a esta página.";
            header('Location: ./index.php?route=login');
            exit();
    }
} else { // 2. USUARIO AUTENTICADO

    $userRole = $_SESSION['id_rol'];

    // Redirección por defecto a dashboard si se accede a la raíz o a 'login' estando autenticado
    if ($route === 'login' || $route === '') {
        switch ($userRole) {
            case 1:
                header('Location: ./index.php?route=admin/dashboard');
                exit();
            case 2:
                header('Location: ./index.php?route=subadmin/dashboard');
                exit();
            case 3:
                header('Location: ./index.php?route=user/dashboard');
                exit();
            default:
                $_SESSION['error_message'] = "Rol de usuario desconocido. Sesión cerrada.";
                session_destroy();
                header('Location: ./index.php?route=login/logout');
                exit();
        }
    }

    // Lógica para logout (disponible para todos los roles autenticados)
    if ($route === 'login/logout') {
        $controllerName = 'LoginController';
        $actionName = 'logout';
    }
    // --- Lógica de Enrutamiento de APPs Autenticadas (ADMIN, SUBADMIN, USER) ---
    else {
        $controllerSegment = array_shift($routeParts);
        $actionSegment = array_shift($routeParts) ?? 'index';
        $id = array_shift($routeParts); // TERCER SEGMENTO (puede ser 'personas', 'create', o un ID)

        $controllerName = ucfirst($controllerSegment) . 'Controller';

        switch ($controllerSegment) {
            case 'admin':
                if ($userRole !== 1) {
                    $_SESSION['error_message'] = "No tienes permisos para acceder al panel de administración.";
                    header('Location: ./index.php?route=user/dashboard');
                    exit();
                }
                $controllerName = 'AdminController';

                // CORRECCIÓN CLAVE: Usar la estructura admin/users/{sub_segment}
                if ($actionSegment === 'users') { 
                    
                    $subSegment = $id; 
                    $id = null; // Reiniciamos $id, lo reasignamos más abajo si aplica (ej. para 'edit?id=X')
                    
                    // Lógica para listados de USUARIOS (Líderes) y PERSONAS (Habitantes)
                    if ($subSegment === 'personas') {
                        $actionName = 'personas'; 
                    } 
                    elseif ($subSegment === 'jefes-familia') {
                        $actionName = 'jefesFamilia'; 
                    }
                    elseif ($subSegment === 'lideres') {
                        $actionName = 'lideres'; 
                    }
                    elseif ($subSegment === 'usuarios') {
                        $actionName = 'usuarios'; 
                    }
                    // Lógica para CRUD de Usuario (Afecta Usuario y Persona)
                    elseif ($subSegment === 'create') { 
                        $actionName = 'createUser'; 
                    }
                    elseif ($subSegment === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
                        $actionName = 'storeUser'; 
                    }
                    elseif ($subSegment === 'edit') { 
                        $actionName = 'editUser'; 
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
                    }
                    elseif ($subSegment === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
                        $actionName = 'updateUser'; 
                    }
                    elseif ($subSegment === 'edit-habitante') { 
                        $actionName = 'editHabitante'; 
                        $id = filter_input(INPUT_GET, 'person_id', FILTER_SANITIZE_NUMBER_INT); 
                    }
                    elseif ($subSegment === 'update-habitante' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
                        $actionName = 'updateHabitante'; 
                    }
                    elseif ($subSegment === 'delete-habitante') { 
                        $actionName = 'deleteHabitante'; 
                        $id = filter_input(INPUT_GET, 'person_id', FILTER_SANITIZE_NUMBER_INT); 
                    }
                    elseif ($subSegment === 'create-user-role') { 
                        // RUTA: admin/users/create-user-role?person_id=X
                        $actionName = 'createUserRole'; 
                        $id = filter_input(INPUT_GET, 'person_id', FILTER_SANITIZE_NUMBER_INT); 
                    }
                    elseif ($subSegment === 'revoke-role') {
                        // RUTA: admin/users/revoke-role?person_id=X
                        $actionName = 'revokeUserRole';
                        $id = filter_input(INPUT_GET, 'person_id', FILTER_SANITIZE_NUMBER_INT);
                    }
                    elseif ($subSegment === 'store-user-role' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
                        // RUTA: admin/users/store-user-role
                        $actionName = 'storeUserRole'; 
                    }

                    elseif ($subSegment === 'delete') { 
                        $actionName = 'deleteUser'; 
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
                    }
                    // Si la ruta es solo admin/users, por defecto va a usuarios (Líderes)
                    else { 
                        $actionName = 'usuarios'; 
                    }

                }
                // Si el segmento es 'usuarios' o 'personas' directamente (ej. admin/usuarios)
                elseif ($actionSegment === 'usuarios') {
                    $actionName = 'usuarios';
                }
                elseif ($actionSegment === 'personas') {
                    $actionName = 'personas';
                }
                
                // Resto de la lógica de Admin
                elseif ($actionSegment === 'viviendas' || $actionSegment === 'vivienda') {
                    // Manejo de API para viviendas
                    $action = $_GET['action'] ?? null;
                    if ($action === 'index') {
                        $actionName = 'viviendasIndex';
                    } elseif ($action === 'byCalle') {
                        // API: listar viviendas por id_calle con conteo de familias
                        $actionName = 'viviendasByCalle';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'familiasPorVivienda') {
                        // API: listar familias por id_vivienda
                        $actionName = 'familiasPorVivienda';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'familiasPorViviendaByJefe') {
                        // API: listar miembros por id_jefe (usado en cargas familiares)
                        $actionName = 'familiasPorViviendaByJefe';
                        // parámetro 'jefe' será leído por el controlador
                    } elseif ($action === 'show') {
                        $actionName = 'viviendasShow';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'store') {
                        $actionName = 'viviendasStore';
                    } elseif ($action === 'update') {
                        $actionName = 'viviendasUpdate';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'destroy') {
                        $actionName = 'viviendasDestroy';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } else {
                        // Vista principal
                        $actionName = 'viviendas';
                    }
                }
                // Página global de indicadores en AdminController
                elseif ($actionSegment === 'indicadores') {
                    $actionName = 'indicadores';
                }
                elseif ($actionSegment === 'carga-familiar') {
                    // Permitir a admin ver todas las cargas familiares usando ?action=all
                    $action = $_GET['action'] ?? null;
                    if ($action === 'all') {
                        $actionName = 'cargasFamiliaresAll';
                    } else {
                        $actionName = 'cargaFamiliar';
                    }
                }
                elseif ($actionSegment === 'news') {
                    if ($id === 'create') { $actionName = 'createNews'; }
                    elseif ($id === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') { $actionName = 'storeNews'; $id = null; }
                    elseif ($id === 'edit') { $actionName = 'editNews'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') { $actionName = 'updateNews'; $id = null; }
                    elseif ($id === 'delete') { $actionName = 'deleteNews'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'soft-delete') { $actionName = 'softDeleteNews'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageNews'; }
                }
                
                elseif ($actionSegment === 'comments') {
                    if ($id === 'soft-delete') { 
                        $actionName = 'softDeleteComment'; 
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
                    } elseif ($id === 'activate') { 
                        $actionName = 'activateComment'; 
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
                    } elseif ($id === 'delete') { 
                        $actionName = 'deleteComment'; 
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); 
                    } else { 
                        $actionName = 'manageComments'; 
                    }
                }
                elseif ($actionSegment === 'getCommentsByNoticia') {
                    $actionName = 'getCommentsByNoticia';
                }

                elseif ($actionSegment === 'notifications') {
                    if ($id === 'mark-read') { $actionName = 'markNotificationRead'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'mark-all-read') { $actionName = 'markAllNotificationsRead'; $id = null; }
                    elseif ($id === 'delete') { $actionName = 'deleteNotification'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageNotifications'; }
                }
                elseif ($actionSegment === 'reports') {
                    $actionName = 'reports';
                }
                // Rutas para reportes específicos
                elseif ($actionSegment === 'reportes') {
                    $subSegment = $id; // El tercer segmento ya fue extraído en $id
                    if ($subSegment === 'habitantes') {
                        $actionName = 'vistaReporteHabitantes';
                    } elseif ($subSegment === 'viviendas') {
                        $actionName = 'vistaReporteViviendas';
                    } elseif ($subSegment === 'familias') {
                        $actionName = 'vistaReporteFamilias';
                    } elseif ($subSegment === 'usuarios') {
                        $actionName = 'vistaReporteUsuarios';
                    } elseif ($subSegment === 'lideres') {
                        $actionName = 'vistaReporteLideres';
                    } elseif ($subSegment === 'por-calle') {
                        $actionName = 'vistaReportePorCalle';
                    } elseif ($subSegment === 'pagos') {
                        $actionName = 'adminPeriodos';
                    } else {
                        $actionName = 'reports';
                    }
                    $id = null; // Resetear $id ya que no se usa como parámetro en estos métodos
                }
                elseif ($actionSegment === 'pagos') {
                    $subSegment = $id;
                    if ($subSegment === 'periodos') {
                        $actionName = 'adminPeriodos';
                    } elseif ($subSegment === 'crear') {
                        $actionName = 'adminCrearPeriodo';
                    } elseif ($subSegment === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'adminStorePeriodo';
                    } elseif ($subSegment === 'close' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'adminClosePeriodo';
                    } elseif ($subSegment === 'editar') {
                        $actionName = 'adminEditarPeriodo';
                    } elseif ($subSegment === 'detalle') {
                        $actionName = 'adminDetallePeriodo';
                    } elseif ($subSegment === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'adminUpdatePeriodo';
                    } elseif ($subSegment === 'export') {
                        $actionName = 'adminExportPagosPeriodo';
                    } else {
                        $actionName = 'adminPeriodos';
                    }
                    $id = null;
                }
                // APIs de reportes (JSON)
                elseif ($actionSegment === 'reporteHabitantes') {
                    $actionName = 'reporteHabitantes';
                }
                elseif ($actionSegment === 'reporteHabitantesPorCalle') {
                    $actionName = 'reporteHabitantesPorCalle';
                }
                elseif ($actionSegment === 'reporteViviendas') {
                    $actionName = 'reporteViviendas';
                }
                elseif ($actionSegment === 'reporteFamilias') {
                    $actionName = 'reporteFamilias';
                }
                elseif ($actionSegment === 'reporteUsuarios') {
                    $actionName = 'reporteUsuarios';
                }
                elseif ($actionSegment === 'reporteLideresCalle') {
                    $actionName = 'reporteLideresCalle';
                }
                elseif ($actionSegment === 'reporteEstadisticas') {
                    $actionName = 'reporteEstadisticas';
                }

                elseif ($actionSegment === 'dashboard' || $actionSegment === 'index' || empty($actionSegment)) {
                    $actionName = 'dashboard';
                }
                break;

            case 'subadmin':
                if ($userRole !== 2 && $userRole !== 1) { // Admin también puede acceder
                    $_SESSION['error_message'] = "No tienes permisos para acceder a esta sección.";
                    header('Location: ./index.php?route=user/dashboard');
                    exit();
                }

                $controllerName = 'SubadminController';

                if ($actionSegment === 'news') {
                    if ($id === 'soft-delete') {
                        $actionName = 'requestSoftDeleteNews';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } else {
                        $actionName = 'manageNews';
                    }
                } elseif ($actionSegment === 'comments') {
                    if ($id === 'soft-delete') {
                        $actionName = 'softDeleteComment';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($id === 'delete') {
                        $actionName = 'deleteComment';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } else {
                        $actionName = 'manageComments';
                    }
                } elseif ($actionSegment === 'getCommentsByNoticia') {
                    $actionName = 'getCommentsByNoticia';
                    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'getVisibilityForNews') {
                    $actionName = 'getVisibilityForNews';
                    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'saveVisibilityForNews') {
                    $actionName = 'saveVisibilityForNews';
                } elseif ($actionSegment === 'reports') {
                    $actionName = 'reports';
                } elseif ($actionSegment === 'reportes') {
                    // Rutas para reportes específicos de subadmin
                    $subSegment = $id;
                    if ($subSegment === 'habitantes') {
                        $actionName = 'vistaReporteHabitantes';
                    } elseif ($subSegment === 'viviendas') {
                        $actionName = 'vistaReporteViviendas';
                    } elseif ($subSegment === 'familias') {
                        $actionName = 'vistaReporteFamilias';
                    } else {
                        $actionName = 'reports';
                    }
                    $id = null;
                } elseif ($actionSegment === 'reporteHabitantes') {
                    $actionName = 'reporteHabitantes';
                } elseif ($actionSegment === 'pagos') {
                    // Líder de vereda: lista de pagos y acciones
                    $subSegment = $id;
                    if ($subSegment === 'lista') {
                        $actionName = 'liderListaPagos';
                    } elseif ($subSegment === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'liderVerifyPago';
                    } else {
                        $actionName = 'liderListaPagos';
                    }
                    $id = null;
                } elseif ($actionSegment === 'reporteViviendas') {
                    $actionName = 'reporteViviendas';
                } elseif ($actionSegment === 'reporteFamilias') {
                    $actionName = 'reporteFamilias';
                } elseif ($actionSegment === 'reporteEstadisticas') {
                    $actionName = 'reporteEstadisticas';
                } elseif ($actionSegment === 'dashboard' || $actionSegment === 'index' || empty($actionSegment)) {
                    $actionName = 'dashboard';
                } elseif ($actionSegment === 'habitantes') {
                    $actionName = 'habitantes';
                } elseif ($actionSegment === 'addHabitante') {
                    $actionName = 'addHabitante';
                    $id = null;
                } elseif ($actionSegment === 'editHabitante') {
                    $actionName = 'editHabitante';
                    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'deleteHabitante') {
                    $actionName = 'deleteHabitante';
                    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'viviendas') {
                    // Manejo de API para viviendas en subadmin (líder de vereda)
                    $action = $_GET['action'] ?? null;
                    if ($action === 'byCalle') {
                        $actionName = 'viviendasByCalle';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'familiasPorVivienda') {
                        $actionName = 'familiasPorVivienda';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'show') {
                        $actionName = 'viviendasShow';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'viviendasStore';
                    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'viviendasUpdate';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($action === 'destroy' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'viviendasDestroy';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } else {
                        $actionName = 'viviendas';
                    }
                } elseif ($actionSegment === 'familias') {
                    $subaction = $_GET['action'] ?? null;
                    if ($subaction === 'miembros') {
                        $actionName = 'miembrosFamilia';
                    } elseif ($id === 'ver') {
                        $actionName = 'verFamilia';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } else {
                        $actionName = 'familias';
                    }
                } elseif ($actionSegment === 'notifications') {
                    if ($id === 'mark-read') { $actionName = 'markNotificationRead'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'mark-all-read') { $actionName = 'markAllNotificationsRead'; $id = null; }
                    elseif ($id === 'delete') { $actionName = 'deleteNotification'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageNotifications'; }
                }

                break;

            case 'api':
                // API endpoints para AJAX
                if ($actionSegment === 'viviendas-por-calle') {
                    header('Content-Type: application/json');
                    require_once __DIR__ . '/../app/models/Vivienda.php';
                    $viviendaModel = new Vivienda();
                    $idCalle = filter_input(INPUT_GET, 'id_calle', FILTER_SANITIZE_NUMBER_INT);
                    
                    if ($idCalle) {
                        $viviendas = $viviendaModel->getViviendasPorCalle($idCalle);
                        echo json_encode(['success' => true, 'viviendas' => $viviendas]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'ID de calle no válido']);
                    }
                    exit();
                }
                break;

            case 'noticias':
                $controllerName = 'NoticiaController';
                if ($actionSegment === 'show') {
                    $actionName = 'show';
                    $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'add-comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'addComment'; $id = null;
                } elseif ($actionSegment === 'edit-comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'editComment'; $id = null;
                } elseif ($actionSegment === 'delete-comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'deleteComment'; $id = null;
                } elseif ($actionSegment === 'toggle-like' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'toggleLike'; $id = null;
                } else {
                    $actionName = 'index';
                }
                break;

            case 'eventos':
                // Rutas para la sección de eventos
                $controllerName = 'EventosController';
                if ($actionSegment === 'list') {
                    $actionName = 'list';
                } elseif ($actionSegment === 'create') {
                    $actionName = 'create';
                } elseif ($actionSegment === 'edit') {
                    $actionName = 'edit';
                    // el ID se pasa como tercer segmento o via GET
                    $id = $id ?: filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'delete') {
                    $actionName = 'delete';
                } elseif ($actionSegment === 'metrics') {
                    $actionName = 'metrics';
                } else {
                    $actionName = 'index';
                }
                break;

            case 'user':
                if ($userRole !== 3 && $userRole !== 2 && $userRole !== 1) {
                    $_SESSION['error_message'] = "No tienes permisos para acceder a esta sección.";
                    header('Location: ./index.php?route=login');
                    exit();
                }
                $controllerName = 'UserController';
                
                if ($actionSegment === 'notifications') {
                    if ($id === 'mark-read') { $actionName = 'markNotificationRead'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'mark-all-read') { $actionName = 'markAllNotificationsRead'; $id = null; }
                    elseif ($id === 'delete') { $actionName = 'deleteNotification'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageNotifications'; }
                }
    
                elseif ($actionSegment === 'dashboard' || $actionSegment === 'index' || empty($actionSegment)) {
                    $actionName = 'dashboard';
                }
                
                elseif ($actionSegment === 'setupProfile') {
                    $actionName = 'setupProfile';
                }
                elseif ($actionSegment === 'updateProfile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'updateProfile';
                }
                // Rutas para Jefe de Familia: carga familiar y detalles de vivienda
                elseif ($actionSegment === 'carga_familiar') {
                    $actionName = 'cargaFamiliar';
                } elseif ($actionSegment === 'addMember' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'addMember';
                } elseif ($actionSegment === 'deleteMember' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'deleteMember';
                }
                elseif ($actionSegment === 'vivienda_details') {
                    $actionName = 'viviendaDetails';
                } elseif ($actionSegment === 'updateViviendaDetails' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'updateViviendaDetails';
                }
                elseif ($actionSegment === 'pagos') {
                    $subSegment = $id;
                    if ($subSegment === 'detalle') {
                        $actionName = 'userDetallePeriodo';
                        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                    } elseif ($subSegment === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $actionName = 'userSubmitPago';
                    } elseif ($subSegment === 'historial') {
                        $actionName = 'userHistorial';
                    } else {
                        $actionName = 'userIndexPeriodos';
                    }
                }

                break;
            default:
                // Si la ruta no coincide con ningún controlador conocido para usuarios autenticados
                http_response_code(404);
                $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Página No Encontrada', 'message' => "La página solicitada '" . htmlspecialchars($route) . "' no existe para tu rol."]] ;
                $controllerName = null; // No intentar cargar un controlador inexistente
                break;
        }
    }
}

// --- Ejecución del Controlador y Acción ---
// Solo intentamos ejecutar el controlador si se ha asignado un nombre de controlador válido.
if ($controllerName) {
    $controllerPath = CONTROLLERS_PATH . $controllerName . '.php';

    if (file_exists($controllerPath)) {
        require_once $controllerPath;

        if (!class_exists($controllerName)) {
            http_response_code(500);
            $viewData = ['view' => 'error/500', 'data' => ['page_title' => 'Error Interno', 'message' => "Error 500: Clase de controlador '" . htmlspecialchars($controllerName) . "' no encontrada en el archivo."]] ;
        } else {
            // Cargar dependencias necesarias
            $usuarioModel = new Usuario();
            $personaModel = new Persona(); // Añadir el modelo Persona
            $noticiaModel = new Noticia();
            $comentarioModel = new Comentario();
            $likeModel = new Like();
            $notificacionModel = new Notificacion();
            $categoriaModel = new Categoria();
            $calleModel = new Calle();
            $liderCalleModel = new LiderCalle();
            $roleModel = new Role();
            $habitanteModel = new Habitante(); // Added Habitante model instantiation
            $viviendaModel = new Vivienda(); // Added Vivienda model instantiation
            $validator = new Validator();

            // Determinar qué controlador instanciar con qué dependencias
            switch ($controllerName) {
                case 'LoginController':
                    $controller = new LoginController($usuarioModel);
                    break;
                case 'AdminController':
                    $cargaFamiliarModel = new CargaFamiliar();
                    $controller = new AdminController($usuarioModel, $personaModel, $noticiaModel, $comentarioModel, $notificacionModel, $calleModel, $liderCalleModel, $categoriaModel, $roleModel, $habitanteModel, $viviendaModel, $cargaFamiliarModel); 
                    break;
                case 'SubadminController':
                    $controller = new SubadminController();
                    break;
                case 'NoticiaController':
                    $controller = new NoticiaController($noticiaModel, $comentarioModel, $likeModel); // Pasa solo los necesarios
                    break;
                case 'UserController':
                    $controller = new UserController($usuarioModel, $noticiaModel, $notificacionModel, $personaModel); 
                    break;
                default:
                    // Si el controlador no tiene una asignación explícita de dependencias
                    $controller = new $controllerName();
                    break;
            }

            if (method_exists($controller, $actionName)) {
                // Llama a la acción y espera que devuelva un array con 'view' y 'data'
                $result = $controller->{$actionName}($id); // Pasa el ID siempre, el controlador decidirá si lo usa

                if (is_array($result) && isset($result['view'])) {
                    $viewData = $result; // Actualiza $viewData con lo que el controlador devolvió
                } elseif ($result === null) {
                    // Si el controlador hizo un redirect o renderizó directamente, no hay nada más que hacer.
                    return; 
                } else {
                    // Si el controlador no devuelve el formato esperado o algo inesperado
                    http_response_code(500);
                    $viewData = ['view' => 'error/500', 'data' => ['page_title' => 'Error Interno', 'message' => "Error 500: La acción '" . htmlspecialchars($actionName) . "' del controlador " . htmlspecialchars($controllerName) . " no devolvió un formato de vista válido."]] ;
                }
            } else {
                http_response_code(404);
                $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Acción No Encontrada', 'message' => "Error 404: Acción '" . htmlspecialchars($actionName) . "' no encontrada para el controlador " . htmlspecialchars($controllerName) . "."]] ;
            }
        }
    } else {
        http_response_code(404);
        $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Controlador No Encontrado', 'message' => "Error 404: Archivo de controlador '" . htmlspecialchars($controllerName) . "' no encontrado."]] ;
    }

}
$data = $viewData['data'] ?? [];
$data['success_message'] = $success_message;
$data['error_message'] = $error_message;
extract($data); // Hace que todas las claves de $data sean variables ($page_title, $noticias, etc.)

$page_title = $page_title ?? 'Grupo Brasil'; // Asegura un título por defecto

// Preparar ruta de la vista específica para que los layouts la incluyan
$view_file = VIEWS_PATH . $viewData['view'] . '.php';
$content_view_path = $view_file; // usado por subadmin/user layouts

if (!file_exists($view_file)) {
    // Si la vista especificada por el controlador no existe, mostrar error 500
    http_response_code(500);
    error_log("Error: La vista especificada '" . htmlspecialchars($viewData['view']) . ".php' no fue encontrada.");
    // Fallback a vista de error 500
    $content_view_path = VIEWS_PATH . 'error/500.php';
}

// Para compatibilidad, también exponemos $page_content (algunas vistas/layouts podrían usarla)
ob_start();
include $content_view_path;
$page_content = ob_get_clean();

// Incluye el layout principal, que debe tener acceso a $content_view_path o $page_content
if (isset($_SESSION['id_rol'])) {
    switch ($_SESSION['id_rol']) {
        case 1:
            $layout = 'layouts/admin_layout.php';
            break;
        case 2:
            $layout = 'layouts/subadmin_layout.php';
            break;
        case 3:
            $layout = 'layouts/user_layout.php';
            break;
        default:
            $layout = 'layouts/admin_layout.php';
            break;
    }
} else {
    // Para login y vistas públicas
    $layout = 'layouts/login_layout.php';
}

include VIEWS_PATH . $layout;
?>
