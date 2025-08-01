<?php
// grupobrasil/app/controllers/UserController.php

require_once __DIR__ . '/AppController.php'; // Asegúrate de incluir AppController
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Validator.php';

// Hereda de AppController para usar loadView
class UserController extends AppController {
    private $usuarioModel;

    public function __construct() {
        parent::__construct(); // Llama al constructor del padre
        $this->usuarioModel = new Usuario();
        
        // Esta verificación es redundante si AppController ya lo hace, pero puede quedarse
        // como una capa de seguridad extra específica del controlador si lo deseas.
        if (!isset($_SESSION['id_usuario'])) {
            header('Location:./index.php?route=login&error=sesion_requerida');
            exit();
        }
        // Además, verifica el rol si es necesario
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3) { // 3 para usuario común
             header('Location:./index.php?route=login&error=acceso_denegado');
             exit();
        }
    }

    public function dashboard() {
        $data = [
            'page_title' => 'Mi Dashboard',
        ];
        // Usa la función loadView del AppController
        $this->loadView('user/dashboard', $data); 
    }

    public function setupProfile() {
        if (!isset($_SESSION['requires_setup']) || $_SESSION['requires_setup'] != 1) {
            header('Location:./index.php?route=user/dashboard');
            exit();
        }

        $user_data = $this->usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);

        if (!$user_data) {
            header('Location:./index.php?route=login/logout&error=datos_usuario_no_encontrados');
            exit();
        }

        $temp_message = $_SESSION['temp_message'] ?? null;
        $form_errors = $_SESSION['form_errors'] ?? [];
        $old_form_data = $_SESSION['old_form_data'] ?? $user_data; 

        unset($_SESSION['temp_message'], $_SESSION['form_errors'], $_SESSION['old_form_data']);

        $data = [
            'page_title' => 'Completar Perfil',
            'user_data' => $user_data,
            'temp_message' => $temp_message,
            'form_errors' => $form_errors,
            'old_form_data' => $old_form_data
        ];
        // Usa la función loadView del AppController
        $this->loadView('user/setup_profile', $data); 
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Asegurarse de que el usuario está logueado y necesita configurar su perfil
            if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['requires_setup']) || $_SESSION['requires_setup'] != 1) {
                header('Location:./index.php?route=user/dashboard'); // Redirigir si no aplica
                exit();
            }

            $id_usuario = $_SESSION['id_usuario'];

            $data = [
                'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'biografia' => trim($_POST['biografia'] ?? ''),
                'password_actual' => trim($_POST['password_actual'] ?? ''), // Su CI
                'new_password' => $_POST['new_password'] ?? '',
                'confirm_new_password' => $_POST['confirm_new_password'] ?? '',
                'requires_setup' => 0 // Asumimos que se completará el setup
            ];

            $errors = [];

            // Obtener datos actuales del usuario para validar la contraseña anterior
            $user = $this->usuarioModel->obtenerUsuarioPorId($id_usuario);
            if (!$user) {
                $errors[] = 'Error: Datos de usuario no encontrados.';
            } else {
                // Validar la contraseña actual (su CI hasheada)
                if (!password_verify($data['password_actual'], $user['password'])) {
                    $errors[] = 'La contraseña actual (su Cédula) es incorrecta.';
                }
            }


            // Validaciones de campos de perfil
            if (Validator::isEmpty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es obligatoria.';
            else if (!Validator::isValidDate($data['fecha_nacimiento'])) $errors[] = 'Formato de fecha de nacimiento inválido (YYYY-MM-DD).';

            if (Validator::isEmpty($data['direccion'])) $errors[] = 'La dirección es obligatoria.';
            if (Validator::isEmpty($data['telefono'])) $errors[] = 'El teléfono es obligatorio.';

            if (Validator::isEmpty($data['email'])) $errors[] = 'El email es obligatorio.';
            else if (!Validator::isValidEmail($data['email'])) $errors[] = 'Formato de email inválido.';
            else if ($data['email'] !== $user['email']) { // Solo verificar si el email cambió
                if ($this->usuarioModel->buscarPorEmail($data['email'])) {
                    $errors[] = 'Ya existe un usuario con este correo electrónico.';
                }
            }

            // Validaciones de nueva contraseña
            if (Validator::isEmpty($data['new_password'])) {
                $errors[] = 'La nueva contraseña es obligatoria.';
            } else if (!Validator::isValidPassword($data['new_password'])) {
                $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } else if ($data['new_password'] !== $data['confirm_new_password']) {
                $errors[] = 'La nueva contraseña y la confirmación no coinciden.';
            }


            if (empty($errors)) {
                // Hashear la nueva contraseña
                $data['password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);

                // Preparar datos para actualización (excluyendo password_actual, new_password, confirm_new_password)
                $update_data = [
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'direccion' => $data['direccion'],
                    'telefono' => $data['telefono'],
                    'email' => $data['email'],
                    'biografia' => $data['biografia'],
                    'password' => $data['password'],
                    'requires_setup' => 0 // Marcar como completado
                ];

                if ($this->usuarioModel->actualizarUsuario($id_usuario, $update_data)) {
                    // Actualizar la sesión para reflejar los cambios
                    $_SESSION['email'] = $data['email'];
                    $_SESSION['requires_setup'] = 0; // Marcar como setup completado en sesión

                    $_SESSION['success_message'] = '¡Perfil completado y contraseña actualizada exitosamente!';
                    header('Location:./index.php?route=user/dashboard');
                    exit();
                } else {
                    $errors[] = 'Error al actualizar el perfil en la base de datos.';
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['old_form_data'] = $data;
                    header('Location:./index.php?route=user/setup_profile');
                    exit();
                }
            } else {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $data;
                header('Location:./index.php?route=user/setup_profile');
                exit();
            }

        } else {
            header('Location:./index.php?route=user/setup_profile');
            exit();
        }
    }

    // Puedes añadir aquí otros métodos relacionados con el perfil del usuario, si los necesitaras más adelante
    // public function viewProfile() { ... }
    // public function editProfile() { ... }
}