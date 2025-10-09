<?php
// grupobrasil/app/controllers/LoginController.php

require_once __DIR__ . '/../models/Usuario.php';

class LoginController {
    private $usuarioModel;

    public function __construct($usuarioModel) {
        $this->usuarioModel = $usuarioModel;
    }

    public function index() { // <--- ESTE ES EL MÉTODO QUE MUESTRA EL FORMULARIO DE LOGIN
        // Si ya hay una sesión activa, redirigir al dashboard adecuado
        if (isset($_SESSION['id_usuario'])) {
            if ($_SESSION['id_rol'] == 1) { // Administrador
                header('Location:./index.php?route=admin/dashboard');
            } elseif ($_SESSION['id_rol'] == 2) { // Sub-administrador
                header('Location:./index.php?route=subadmin/dashboard');
            } else { // Usuario Común
                header('Location:./index.php?route=user/dashboard');
            }
            exit();
        }
        $error = $_GET['error'] ?? '';
        $success = $_GET['success'] ?? '';
        require_once __DIR__ . '/../views/login.php'; // <--- ESTO CARGA LA VISTA
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ci_usuario = trim($_POST['ci_usuario'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $this->usuarioModel->buscarPorCI($ci_usuario);

            if ($user && password_verify($password, $user['password'])) {
                // Iniciar sesión
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['ci_usuario'] = $user['ci_usuario'];
                $_SESSION['nombre_completo'] = $user['nombre'] . ' ' . $user['apellido'];
                $_SESSION['id_rol'] = $user['id_rol'];
                $_SESSION['activo'] = $user['activo'];
                $_SESSION['requires_setup'] = $user['requires_setup']; // Capturar el estado de setup

                // Verificar si el usuario está activo
                if ($user['activo'] == 0) {
                    $this->logout('Usuario inactivo. Contacte al administrador.');
                    exit();
                }

                if ($user['requires_setup'] == 1) {
                    $_SESSION['temp_message'] = "¡Bienvenido por primera vez! Por favor, completa tu perfil y establece una nueva contraseña.";
                    header('Location:./index.php?route=user/setupProfile');
                    exit();
                }

                // Redirección normal según el rol si no necesita setup
                if ($user['id_rol'] == 1) { // Administrador
                    header('Location:./index.php?route=admin/dashboard');
                } elseif ($user['id_rol'] == 2) { // Sub-administrador
                    header('Location:./index.php?route=subadmin/dashboard');
                } else { // Usuario Común
                    header('Location:./index.php?route=user/dashboard');
                }
                exit();

            } else {
                // Credenciales inválidas
                header('Location:./index.php?route=login&error=credenciales_invalidas');
                exit();
            }
        } else {
            // Si no es un POST, redirigir a la página de login
            header('Location:./index.php?route=login');
            exit();
        }
    }

    public function logout($message = null) {
        session_unset();
        session_destroy();
        if ($message) {
            header('Location:./index.php?route=login&error=' . urlencode($message));
        } else {
            header('Location:./index.php?route=login&success=sesion_cerrada');
        }
        exit();
    }
}
