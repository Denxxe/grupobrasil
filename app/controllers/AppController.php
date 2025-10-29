<?php
// grupobrasil/app/controllers/AppController.php

class AppController {
    protected $db; 

    public function __construct() {
        // Inicializar mensajes de sesión como cadenas vacías (se usan para toasts/alerts en layouts)
        if (!isset($_SESSION['success_message'])) {
            $_SESSION['success_message'] = '';
        }
        if (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = '';
        }
    }

    public function setDb(PDO $db) {
        $this->db = $db;
    }

    protected function loadView($viewName, $data = []) {
        
        // 1. Definir la ruta del contenido
        $content_view_path = __DIR__ . '/../views/' . $viewName . '.php';
        
    // 2. INYECTAR la ruta de la vista del contenido en $data
    // Esto asegura que $content_view_path esté disponible cuando se llama a extract()
    $data['content_view_path'] = $content_view_path;
    // Algunos layouts (admin_layout.php) esperan la variable $content_view en lugar de content_view_path
    // Para compatibilidad, exponemos ambas variables al layout.
    $data['content_view'] = $content_view_path;

        $page_title = $data['page_title'] ?? 'Grupo Brasil';

        error_log("[v0] AppController::loadView() llamado");
        error_log("[v0] Vista solicitada: " . $viewName);
        error_log("[v0] id_rol: " . ($_SESSION['id_rol'] ?? 'no definido'));

        $layout = 'default_public_layout.php'; 
        if (isset($_SESSION['id_rol'])) {
            switch ($_SESSION['id_rol']) {
                case 1: $layout = 'admin_layout.php'; break;
                case 2: $layout = 'subadmin_layout.php'; break;
                case 3: $layout = 'user_layout.php'; break;
                default: $layout = 'default_public_layout.php'; break;
            }
        }

        error_log("[v0] Layout seleccionado: " . $layout);

        $layout_path = __DIR__ . '/../views/layouts/' . $layout;

        error_log("[v0] Ruta de vista: " . $content_view_path);
        error_log("[v0] Ruta de layout: " . $layout_path);

        if (!file_exists($content_view_path)) {
            error_log("[v0] ERROR: La vista no existe: " . $content_view_path);
            http_response_code(404);
            echo "Error: Vista no encontrada: " . htmlspecialchars($viewName);
            exit();
        }

        if (!file_exists($layout_path)) {
            error_log("[v0] ERROR: El layout no existe: " . $layout_path);
            http_response_code(500);
            echo "Error: Layout no encontrado: " . htmlspecialchars($layout);
            exit();
        }

        // Ahora, extract($data) hace que $content_view_path esté disponible en el layout
        extract($data);
        
        error_log("[v0] Incluyendo layout: " . $layout);
        error_log("[v0] content_view_path disponible: " . $content_view_path);
        include_once $layout_path;
        exit();
    }

    protected function setSuccessMessage($message) {
        $_SESSION['success_message'] = $message;
    }

    protected function setErrorMessage($message) {
        $_SESSION['error_message'] = $message;
    }

    protected function redirect($route, $params = []) {
        $queryString = http_build_query($params);
        $url = './index.php?route=' . urlencode($route);
        if (!empty($queryString)) {
            $url .= '&' . $queryString;
        }
        error_log("[v0] Redirigiendo a: " . $url);
        header('Location: ' . $url);
        exit();
    }
}