<?php
// grupobrasil/app/controllers/AppController.php

class AppController {
    protected $db; 

    public function __construct() {
        if (!isset($_SESSION['success_message'])) {
            $_SESSION['success_message'] = [];
        }
        if (!isset($_SESSION['error_message'])) {
            $_SESSION['error_message'] = [];
        }
    }

    public function setDb(PDO $db) {
        $this->db = $db;
    }

    protected function loadView($viewName, $data = []) {
        $content_view_path = __DIR__ . '/../views/' . $viewName . '.php';
        $page_title = $data['page_title'] ?? 'Grupo Brasil';

        $requires_setup = isset($_SESSION['requires_setup']) && $_SESSION['requires_setup'] == 1;

        error_log("[v0] AppController::loadView() llamado");
        error_log("[v0] Vista solicitada: " . $viewName);
        error_log("[v0] requires_setup: " . ($requires_setup ? 'true' : 'false'));
        error_log("[v0] id_rol: " . ($_SESSION['id_rol'] ?? 'no definido'));

        $layout = 'default_public_layout.php'; 
        if (isset($_SESSION['id_rol'])) {
            switch ($_SESSION['id_rol']) {
                case 1: $layout = 'admin_layout.php'; break;
                case 2: 
                    $layout = $requires_setup ? 'default_public_layout.php' : 'subadmin_layout.php';
                    break;
                case 3: 
                    $layout = $requires_setup ? 'default_public_layout.php' : 'user_layout.php';
                    break;
                default: $layout = 'default_public_layout.php'; break;
            }
        }

        error_log("[v0] Layout seleccionado: " . $layout);

        if ($requires_setup && $viewName !== 'user/setup_profile') {
            error_log("[v0] Usuario requiere setup, forzando vista setup_profile");
            
            // Cargar datos del usuario para el setup
            require_once __DIR__ . '/../models/Usuario.php';
            $usuarioModel = new Usuario();
            $user_data = $usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);
            
            // Preparar datos para la vista de setup
            $data = [
                'page_title' => 'Configuración Obligatoria',
                'user_data' => $user_data,
                'temp_message' => $_SESSION['temp_message'] ?? ($_SESSION['welcome_message'] ?? null),
                'form_errors' => $_SESSION['form_errors'] ?? [],
                'old_form_data' => $_SESSION['old_form_data'] ?? $user_data
            ];
            
            // Limpiar mensajes temporales
            unset($_SESSION['welcome_message'], $_SESSION['temp_message'], $_SESSION['form_errors'], $_SESSION['old_form_data']);
            
            $page_title = $data['page_title'];
            $layout = 'default_public_layout.php'; 
            $viewName = 'user/setup_profile';
            $content_view_path = __DIR__ . '/../views/' . $viewName . '.php';
        }

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
