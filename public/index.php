<?php
// grupobrasil/public/index.php

// Inicia la sesión al principio de todo si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Definición de Constantes de Ruta ---
define('APP_ROOT', __DIR__ . '/../');
define('CONFIG_PATH', APP_ROOT . 'config/');
define('MODELS_PATH', APP_ROOT . 'app/models/');
define('CONTROLLERS_PATH', APP_ROOT . 'app/controllers/');
define('VIEWS_PATH', APP_ROOT . 'app/views/');
define('UTILS_PATH', APP_ROOT . 'app/utils/');

// --- Carga de archivos de configuración y base de datos ---
$db = require_once CONFIG_PATH . 'database.php'; // Asume que database.php devuelve la conexión $db

// --- Carga de modelos (asegúrate de que ModelBase se cargue primero si es una clase base) ---
require_once MODELS_PATH . 'ModelBase.php';
require_once MODELS_PATH . 'Usuario.php';
require_once MODELS_PATH . 'Noticia.php';
require_once MODELS_PATH . 'Comentario.php';
require_once MODELS_PATH . 'Like.php';
require_once MODELS_PATH . 'Notificacion.php';
require_once MODELS_PATH . 'Categoria.php';

// --- Carga de controladores ---
require_once CONTROLLERS_PATH . 'LoginController.php';
require_once CONTROLLERS_PATH . 'AdminController.php';
require_once CONTROLLERS_PATH . 'SubadminController.php';
require_once CONTROLLERS_PATH . 'NoticiaController.php';
require_once CONTROLLERS_PATH . 'UserController.php';
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
        case '': // Si no se especifica ruta, llevar al login
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

    $userRole = $_SESSION['id_rol']; // Obtener el rol del usuario de la sesión

    // Redirección por defecto a dashboard si se accede a la raíz o a 'login' estando autenticado
    if ($route === 'login' || $route === '') {
        switch ($userRole) {
            case 1: // Administrador
                header('Location: ./index.php?route=admin/dashboard');
                exit();
            case 2: // Sub-administrador
                header('Location: ./index.php?route=subadmin/dashboard');
                exit();
            case 3: // Usuario Común
                header('Location: ./index.php?route=user/dashboard');
                exit();
            default:
                $_SESSION['error_message'] = "Rol de usuario desconocido. Sesión cerrada.";
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
        // Extraer segmentos de la ruta
        $controllerSegment = array_shift($routeParts); // Ej: 'admin', 'noticias', 'user'
        $actionSegment = array_shift($routeParts) ?? 'index'; // Ej: 'dashboard', 'show', 'create'
        $id = array_shift($routeParts); // El tercer segmento, que podría ser un ID (ej: noticias/show/123)

        // Asignar el nombre del controlador inicial, puede ser sobrescrito en el switch
        $controllerName = ucfirst($controllerSegment) . 'Controller';

        // --- Manejo Específico de Controladores y Acciones ---
        switch ($controllerSegment) {
            case 'admin':
                if ($userRole !== 1) {
                    $_SESSION['error_message'] = "No tienes permisos para acceder al panel de administración.";
                    header('Location: ./index.php?route=user/dashboard');
                    exit();
                }
                $controllerName = 'AdminController';
                if ($actionSegment === 'users') {
                    if ($id === 'create') { $actionName = 'createUser'; }
                    elseif ($id === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') { $actionName = 'storeUser'; $id = null; }
                    elseif ($id === 'edit') { $actionName = 'editUser'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') { $actionName = 'updateUser'; $id = null; }
                    elseif ($id === 'delete') { $actionName = 'deleteUser'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageUsers'; }
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
                    if ($id === 'soft-delete') { $actionName = 'softDeleteComment'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'activate') { $actionName = 'activateComment'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    elseif ($id === 'delete') { $actionName = 'deleteComment'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageComments'; }
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
                    if ($id === 'soft-delete') { $actionName = 'requestSoftDeleteNews'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageNews'; }
                } elseif ($actionSegment === 'comments') {
                    if ($id === 'soft-delete') { $actionName = 'softDeleteComment'; $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT); }
                    else { $actionName = 'manageComments'; }
                }
elseif ($actionSegment === 'reports') {
    $actionName = 'reports';
}
                elseif ($actionSegment === 'dashboard' || $actionSegment === 'index' || empty($actionSegment)) {
                    $actionName = 'dashboard';
                }
                break;

            case 'noticias':
                $controllerName = 'NoticiaController';
                if ($actionSegment === 'show') {
                    $actionName = 'show';
                    $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                } elseif ($actionSegment === 'add-comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'addComment'; $id = null;
                } elseif ($actionSegment === 'toggle-like' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $actionName = 'toggleLike'; $id = null;
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
                if ($actionSegment === 'dashboard' || $actionSegment === 'index' || empty($actionSegment)) {
                    $actionName = 'dashboard';
                }
                break;

            default:
                // Si la ruta no coincide con ningún controlador conocido para usuarios autenticados
                http_response_code(404);
                $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Página No Encontrada', 'message' => "La página solicitada '" . htmlspecialchars($route) . "' no existe para tu rol."]];
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
            $viewData = ['view' => 'error/500', 'data' => ['page_title' => 'Error Interno', 'message' => "Error 500: Clase de controlador '" . htmlspecialchars($controllerName) . "' no encontrada en el archivo."]];
        } else {
            // Instancia de los modelos y se pasan al controlador
            // Esto es crucial para la inyección de dependencias
            $usuarioModel = new Usuario($db);
            $noticiaModel = new Noticia($db);
            $comentarioModel = new Comentario($db);
            $likeModel = new Like($db);
            $notificacionModel = new Notificacion($db);
            $categoriaModel = new Categoria($db);

            // Determinar qué controlador instanciar con qué dependencias
            switch ($controllerName) {
                case 'LoginController':
                    $controller = new LoginController($usuarioModel);
                    break;
                case 'AdminController':
                    $controller = new AdminController($usuarioModel, $noticiaModel, $comentarioModel, $notificacionModel, $categoriaModel, new Validator());
                    break;
                case 'SubadminController':
                    $controller = new SubadminController($usuarioModel, $noticiaModel, $comentarioModel, $notificacionModel, $categoriaModel, new Validator());
                    break;
                case 'NoticiaController':
                    $controller = new NoticiaController($noticiaModel, $comentarioModel, $likeModel); // Pasa solo los necesarios
                    break;
                case 'UserController':
                    $controller = new UserController($usuarioModel, $noticiaModel, $notificacionModel);
                    break;
                default:
                    // Si el controlador no tiene una asignación explícita de dependencias
                    $controller = new $controllerName();
                    break;
            }

            if (method_exists($controller, $actionName)) {
                // Llama a la acción y espera que devuelva un array con 'view' y 'data'
                // O null si el controlador maneja una redirección internamente
                $result = $controller->{$actionName}($id); // Pasa el ID siempre, el controlador decidirá si lo usa

                if (is_array($result) && isset($result['view'])) {
                    $viewData = $result; // Actualiza $viewData con lo que el controlador devolvió
                } elseif ($result === null) {
                    // Si el controlador hizo un redirect, no hay nada más que hacer en este script.
                    // El exit() ya fue llamado dentro del controlador.
                    return;
                } else {
                    // Si el controlador no devuelve el formato esperado o algo inesperado
                    http_response_code(500);
                    $viewData = ['view' => 'error/500', 'data' => ['page_title' => 'Error Interno', 'message' => "Error 500: La acción '" . htmlspecialchars($actionName) . "' del controlador " . htmlspecialchars($controllerName) . " no devolvió un formato de vista válido."]];
                }
            } else {
                http_response_code(404);
                $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Acción No Encontrada', 'message' => "Error 404: Acción '" . htmlspecialchars($actionName) . "' no encontrada para el controlador " . htmlspecialchars($controllerName) . "."]];
            }
        }
    } else {
        http_response_code(404);
        $viewData = ['view' => 'error/404', 'data' => ['page_title' => 'Controlador No Encontrado', 'message' => "Error 404: Archivo de controlador '" . htmlspecialchars($controllerName) . "' no encontrado."]];
    }

    
}


// --- Renderizado Final de la Vista ---
// Extrae los datos para que estén disponibles como variables en el ámbito del include
// $data contendrá lo que el controlador haya devuelto, más los mensajes flash
$data = $viewData['data'] ?? [];
$data['success_message'] = $success_message;
$data['error_message'] = $error_message;
extract($data); // Hace que todas las claves de $data sean variables ($page_title, $noticias, etc.)

$page_title = $page_title ?? 'Grupo Brasil'; // Asegura un título por defecto

// Captura el contenido de la vista específica
ob_start();
$view_file = VIEWS_PATH . $viewData['view'] . '.php';
if (file_exists($view_file)) {
    include $view_file;
} else {
    // Si la vista especificada por el controlador no existe, muestra una vista de error genérica
    http_response_code(500);
    include VIEWS_PATH . 'error/500.php'; // Asegúrate de tener un error/500.php
    error_log("Error: La vista especificada '" . htmlspecialchars($viewData['view']) . ".php' no fue encontrada.");
}
$page_content = ob_get_clean(); // Captura el HTML de la vista

// Incluye el layout principal, que debe tener una variable $page_content
// y las variables $success_message, $error_message, $page_title disponibles
include VIEWS_PATH . 'layouts/admin_layout.php'; // O un layout diferente según el rol/ruta si lo necesitas

?>