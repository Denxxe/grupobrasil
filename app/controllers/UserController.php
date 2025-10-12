<?php
// grupobrasil/app/controllers/UserController.php

require_once __DIR__ . '/AppController.php'; // Asegúrate de incluir AppController
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/Notificacion.php';

// Hereda de AppController para usar loadView y redirect
class UserController extends AppController {
    private $usuarioModel;
    private $noticiaModel;
    private $notificacionModel;

    // Añade los modelos como parámetros para inyección de dependencias
    public function __construct($usuarioModel, $noticiaModel, $notificacionModel) {
        parent::__construct(); 
        
        $this->usuarioModel = $usuarioModel;
        $this->noticiaModel = $noticiaModel;
        $this->notificacionModel = $notificacionModel; // Asignar el modelo
        
        // CORRECCIÓN: Usar $this->redirect() para seguridad de cabeceras
        // NOTA: Si el setupProfile es obligatorio para TODOS los roles, esta restricción debe
        // ir en el router (index.php) o en AppController, no solo aquí para rol 3.
        // Asumiendo que esta clase solo maneja funciones del Rol 3 (Usuario Común).
        if (!isset($_SESSION['id_rol']) || ($_SESSION['id_rol'] != 3 && $_SESSION['id_rol'] != 2)) {
             $this->redirect('login', ['error' => 'acceso_denegado']);
        }
    }

    public function dashboard() {
        // La lógica de forzar setupProfile para CUALQUIER rol ya está en AppController::loadView()
        $data = [
            'page_title' => 'Mi Dashboard',
        ];
        return $this->loadView('user/dashboard', $data); 
    }

   public function setupProfile() {
       // Eliminamos la restricción de 'requires_setup' aquí, ya que AppController::loadView()
       // se encarga de forzar la vista de setup para cualquier usuario que lo necesite.
       // Esto permite que la vista se cargue si la ruta es llamada.

        if (!isset($_SESSION['id_usuario'])) {
            $this->redirect('login');
        }

        $user_data = $this->usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);

        if (!$user_data) {
            // CORRECCIÓN: Usar $this->redirect()
            $this->redirect('login/logout', ['error' => 'datos_usuario_no_encontrados']);
        }

        $temp_message = $_SESSION['temp_message'] ?? 
                        ($_SESSION['welcome_message'] ?? null); 

        unset($_SESSION['welcome_message']);

        // Recuperar errores y datos antiguos de la sesión
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
   
        $this->loadView('user/setup_profile', $data); 
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if (!isset($_SESSION['id_usuario'])) {
                $this->redirect('login'); 
            }

            error_log("[v0] updateProfile iniciado para usuario ID: " . $_SESSION['id_usuario']);
            error_log("[v0] requires_setup actual: " . ($_SESSION['requires_setup'] ?? 'no definido'));
            error_log("[v0] id_rol actual: " . ($_SESSION['id_rol'] ?? 'no definido'));

            if (!isset($_SESSION['requires_setup']) || $_SESSION['requires_setup'] != 1) {
                error_log("[v0] Usuario no requiere setup, redirigiendo a dashboard");
                if ($_SESSION['id_rol'] == 2) {
                    $this->redirect('subadmin/dashboard');
                } else {
                    $this->redirect('user/dashboard');
                }
                return;
            }

            $id_usuario = $_SESSION['id_usuario'];

            $data = [
                'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'biografia' => trim($_POST['biografia'] ?? ''),
                'password_actual' => trim($_POST['password_actual'] ?? ''),
                'new_password' => $_POST['new_password'] ?? '',
                'confirm_new_password' => $_POST['confirm_new_password'] ?? '',
                'requires_setup' => 0
            ];

            error_log("[v0] Datos recibidos del formulario: " . json_encode(array_keys($data)));
            error_log("[v0] Longitud de new_password: " . strlen($data['new_password']));

            $errors = [];

            $user = $this->usuarioModel->obtenerUsuarioPorId($id_usuario);
            if (!$user) {
                error_log("[v0] Usuario no encontrado en BD");
                $this->setErrorMessage('Error: Datos de usuario logueado no encontrados.');
                $this->redirect('login/logout');
            } else {
                if (!password_verify($data['password_actual'], $user['password'])) {
                    $errors[] = 'La contraseña actual (su Cédula) es incorrecta.';
                    error_log("[v0] Contraseña actual incorrecta");
                }
            }

            if (Validator::isEmpty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es obligatoria.';
            else if (!Validator::isValidDate($data['fecha_nacimiento'])) $errors[] = 'Formato de fecha de nacimiento inválido (YYYY-MM-DD).';

            if (Validator::isEmpty($data['direccion'])) $errors[] = 'La dirección es obligatoria.';
            if (Validator::isEmpty($data['telefono'])) $errors[] = 'El teléfono es obligatorio.';

            if (Validator::isEmpty($data['email'])) $errors[] = 'El email es obligatorio.';
            else if (!Validator::isValidEmail($data['email'])) $errors[] = 'Formato de email inválido.';
            else if ($data['email'] !== $user['email']) { 
                if ($this->usuarioModel->buscarPorEmail($data['email'])) {
                    $errors[] = 'Ya existe un usuario con este correo electrónico.';
                }
            }

            if (Validator::isEmpty($data['new_password'])) {
                $errors[] = 'La nueva contraseña es obligatoria.';
                error_log("[v0] Nueva contraseña está vacía");
            } else if (!Validator::isValidPassword($data['new_password'])) {
                $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
                error_log("[v0] Nueva contraseña no cumple validación: " . strlen($data['new_password']) . " caracteres");
            } else if ($data['new_password'] !== $data['confirm_new_password']) {
                $errors[] = 'La nueva contraseña y la confirmación no coinciden.';
                error_log("[v0] Las contraseñas no coinciden");
            }

            if (empty($errors)) {
                error_log("[v0] Validación exitosa, procediendo a actualizar");
                
                $data['password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);

                $update_data = [
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'direccion' => $data['direccion'],
                    'telefono' => $data['telefono'],
                    'email' => $data['email'],
                    'biografia' => $data['biografia'],
                    'password' => $data['password'],
                    'requires_setup' => 0
                ];
                
                error_log("[v0] Intentando actualizar usuario en BD");
                $resultado = $this->usuarioModel->actualizarUsuario($id_usuario, $update_data);
                
                if ($resultado === true) {
                    error_log("[v0] Actualización exitosa en BD");
                    
                    $_SESSION['email'] = $data['email'];
                    $_SESSION['requires_setup'] = 0;
                    $_SESSION['telefono'] = $data['telefono'];
                    $_SESSION['direccion'] = $data['direccion'];
                    $_SESSION['biografia'] = $data['biografia'];
                    $_SESSION['success_message'] = '¡Perfil completado y contraseña actualizada exitosamente!';
                    
                    error_log("[v0] Sesión actualizada completamente");
                    error_log("[v0] requires_setup ahora es: " . $_SESSION['requires_setup']);
                    error_log("[v0] id_rol es: " . $_SESSION['id_rol']);
                    error_log("[v0] Redirigiendo al dashboard...");
                    
                    if ($_SESSION['id_rol'] == 2) {
                        error_log("[v0] Redirigiendo a subadmin/dashboard");
                        $this->redirect('subadmin/dashboard');
                    } else {
                        error_log("[v0] Redirigiendo a user/dashboard");
                        $this->redirect('user/dashboard');
                    }
                    exit(); // Asegurar que no se ejecute más código
                } else {
                    error_log("[v0] Error al actualizar perfil del usuario ID: $id_usuario");
                    $_SESSION['error_message'] = 'Error al actualizar el perfil. Por favor, intenta nuevamente.';
                    $_SESSION['form_errors'] = ['Error de Base de Datos al actualizar'];
                    $_SESSION['old_form_data'] = $data;
                    
                    $this->redirect('user/setupProfile');
                }
            } else {
                error_log("[v0] Errores de validación: " . json_encode($errors));
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $data;
                
                $this->redirect('user/setupProfile');
            }

        } else {
            $this->redirect('user/setupProfile');
        }
    }

    public function manageNotifications() {
        $id_usuario = $_SESSION['id_usuario'];
        
        $notificaciones = $this->notificacionModel->obtenerNotificacionesPorUsuario(
            $id_usuario, 
            false, 
            ['column' => 'fecha_creacion', 'direction' => 'DESC']
        );
        
        $data = [
            'page_title' => 'Mis Notificaciones',
            'notificaciones' => $notificaciones,
        ];
        
        return $this->loadView('user/notifications/index', $data);
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markNotificationRead($id = null) {
        if (!is_numeric($id) || $id <= 0) {
            $this->setErrorMessage('ID de notificación inválido.');
            $this->redirect('user/notifications'); // CORRECCIÓN
        }

        $id_notificacion = (int)$id;
        $id_usuario_destino = $_SESSION['id_usuario'];
        
        $notificacion = $this->notificacionModel->find($id_notificacion);

        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_usuario_destino) {
            $this->setErrorMessage('Acceso denegado a esta notificación o no existe.');
            $this->redirect('user/notifications'); // CORRECCIÓN
        }
        
        if (!$this->notificacionModel->marcarComoLeida($id_notificacion)) {
            $this->setErrorMessage('Error al marcar la notificación como leída.');
        }
        
        $this->redirect('user/notifications'); // CORRECCIÓN
    }
    
    /**
     * Marca todas las notificaciones no leídas del usuario como leídas.
     */
    public function markAllNotificationsRead() {
        $id_usuario = $_SESSION['id_usuario'];
        
        if ($this->notificacionModel->marcarTodasComoLeidas($id_usuario)) {
            $this->setSuccessMessage('Todas las notificaciones han sido marcadas como leídas.');
        } else {
            $this->setErrorMessage('No fue posible marcar todas las notificaciones como leídas.');
        }

        $this->redirect('user/notifications'); // CORRECCIÓN
    }
    
    /**
     * Elimina una notificación específica.
     */
    public function deleteNotification($id = null) {
        if (!is_numeric($id) || $id <= 0) {
            $this->setErrorMessage('ID de notificación inválido.');
            $this->redirect('user/notifications'); // CORRECCIÓN
        }

        $id_notificacion = (int)$id;
        $id_usuario_destino = $_SESSION['id_usuario'];
        
        $notificacion = $this->notificacionModel->find($id_notificacion);

        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_usuario_destino) {
            $this->setErrorMessage('Acceso denegado a esta notificación o no existe.');
            $this->redirect('user/notifications'); // CORRECCIÓN
        }
        
        if ($this->notificacionModel->eliminarNotificacion($id_notificacion)) {
            $this->setSuccessMessage('Notificación eliminada exitosamente.');
        } else {
            $this->setErrorMessage('Error al eliminar la notificación.');
        }
        
        $this->redirect('user/notifications'); // CORRECCIÓN
    }
}
