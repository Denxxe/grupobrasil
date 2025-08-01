<?php
// grupobrasil/app/controllers/AppController.php

// Asegúrate de que las sesiones estén iniciadas al principio de tu index.php
// session_start(); 

class AppController {
    protected $db; // Propiedad para almacenar la conexión a la base de datos

    public function __construct() {
        // Inicializa los mensajes flash para que estén disponibles en todas las vistas
        // Si no existen, los inicializamos como arrays vacíos
        if (!isset($_SESSION['success_message'])) {
            $_SESSION['success_message'] = [];
        }
        if (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = [];
        }
    }

    /**
     * Establece la conexión a la base de datos para que los modelos la utilicen.
     * Esto se llamará desde index.php al instanciar el controlador.
     * @param PDO $db Objeto PDO de la conexión a la base de datos.
     */
    public function setDb(PDO $db) {
        $this->db = $db;
    }

    /**
     * Carga una vista y la envuelve en el layout adecuado según el rol del usuario.
     * También pasa datos a la vista y gestiona los mensajes flash.
     *
     * @param string $viewName El nombre de la vista a cargar (ej. 'admin/dashboard', 'login').
     * @param array $data Un array asociativo de datos a pasar a la vista.
     */
    protected function loadView($viewName, $data = []) {
        // Extrae los datos para que estén disponibles como variables en la vista
        extract($data);

        // Define el título de la página por defecto
        $page_title = $data['page_title'] ?? 'Grupo Brasil';

        // Recupera y limpia los mensajes flash de la sesión
        $success_message = $_SESSION['success_message'] ?? null;
        $error_message = $_SESSION['error_message'] ?? null;
        unset($_SESSION['success_message']); // Elimina el mensaje después de recuperarlo
        unset($_SESSION['error_message']);   // Elimina el mensaje después de recuperarlo

        // Determina el layout a usar basado en el rol del usuario
        // O si no hay sesión, usa un layout público por defecto.
        $layout = 'default_public_layout.php'; // Layout por defecto para no-logueados o login
        if (isset($_SESSION['id_rol'])) {
            switch ($_SESSION['id_rol']) {
                case 1: // Administrador
                    $layout = 'admin_layout.php';
                    break;
                case 2: // Sub-administrador
                    $layout = 'subadmin_layout.php';
                    break;
                case 3: // Usuario Común
                    $layout = 'user_layout.php';
                    break;
                default:
                    $layout = 'default_public_layout.php'; // Fallback
                    break;
            }
        }
        
        // Rutas completas a la vista de contenido y al layout
        $content_view_path = __DIR__ . '/../views/' . $viewName . '.php';
        $layout_path = __DIR__ . '/../views/layouts/' . $layout;

        // Verifica si la vista de contenido existe
        if (!file_exists($content_view_path)) {
            error_log("Error: La vista '" . htmlspecialchars($viewName) . "' no se encontró en " . htmlspecialchars($content_view_path));
            http_response_code(404); // Not Found
            echo "Error: Vista no encontrada.";
            exit();
        }

        // Verifica si el layout existe
        if (!file_exists($layout_path)) {
            error_log("Error: El layout '" . htmlspecialchars($layout) . "' no se encontró en " . htmlspecialchars($layout_path));
            http_response_code(500); // Internal Server Error
            echo "Error: Layout no encontrado.";
            exit();
        }

        // Incluye el layout, que a su vez incluirá la vista de contenido
        include_once $layout_path;
        exit(); // Termina la ejecución después de renderizar la vista
    }

    /**
     * Agrega un mensaje de éxito a la sesión para mostrar en la siguiente solicitud.
     * @param string $message
     */
    protected function setSuccessMessage($message) {
        // Almacena los mensajes como un array si quieres permitir múltiples mensajes
        // O simplemente sobrescribe si solo quieres el último
        $_SESSION['success_message'] = $message; // Simplificamos para un solo mensaje por tipo
    }

    /**
     * Agrega un mensaje de error a la sesión para mostrar en la siguiente solicitud.
     * @param string $message
     */
    protected function setErrorMessage($message) {
        $_SESSION['error_message'] = $message; // Simplificamos para un solo mensaje por tipo
    }

    /**
     * Redirige a una ruta específica y termina la ejecución.
     * Usa esto para redirecciones HTTP después de acciones POST, etc.
     *
     * @param string $route La ruta a la que redirigir (ej. 'login', 'admin/dashboard').
     * @param array $params Parámetros adicionales para la URL (ej. ['id' => 1]).
     */
    protected function redirect($route, $params = []) {
        $queryString = http_build_query($params);
        $url = '/grupobrasil/public/index.php?route=' . urlencode($route);
        if (!empty($queryString)) {
            $url .= '&' . $queryString;
        }
        header('Location: ' . $url);
        exit();
    }
}