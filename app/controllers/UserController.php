<?php
// grupobrasil/app/controllers/UserController.php

require_once __DIR__ . '/AppController.php'; 
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Persona.php'; // NUEVO: Incluir el modelo Persona
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/Notificacion.php';

// Hereda de AppController para usar loadView y redirect
class UserController extends AppController {
    private $usuarioModel;
    private $personaModel; // NUEVO: Propiedad para el modelo Persona
    private $noticiaModel;
    private $notificacionModel;

    /**
     * @param Usuario $usuarioModel 
     * @param Noticia $noticiaModel 
     * @param Notificacion $notificacionModel 
     * @param Persona $personaModel 
     */
    public function __construct($usuarioModel, $noticiaModel, $notificacionModel, $personaModel) {
        parent::__construct(); 
        
        $this->usuarioModel = $usuarioModel;
        $this->noticiaModel = $noticiaModel;
        $this->notificacionModel = $notificacionModel;
        $this->personaModel = $personaModel; // NUEVO: Asignar el modelo Persona
        
        // Restricción de acceso general (se asume que AppController maneja el setupProfile)
        if (!isset($_SESSION['id_rol']) || ($_SESSION['id_rol'] != 3 && $_SESSION['id_rol'] != 2)) {
             $this->redirect('login', ['error' => 'acceso_denegado']);
        }
    }

    public function dashboard() {
        $data = [
            'page_title' => 'Mi Dashboard',
        ];
        return $this->loadView('user/dashboard', $data); 
    }

    public function setupProfile() {
        if (!isset($_SESSION['id_usuario'])) {
            $this->redirect('login');
        }

        // Se espera que este método en el modelo Usuario realice el JOIN y devuelva todos los datos.
        $user_data = $this->usuarioModel->obtenerUsuarioCompleto($_SESSION['id_usuario']);

        if (!$user_data) {
            $this->redirect('login/logout', ['error' => 'datos_usuario_no_encontrados']);
        }

        $temp_message = $_SESSION['temp_message'] ?? 
                             ($_SESSION['welcome_message'] ?? null); 

        unset($_SESSION['welcome_message']);

        // Recuperar errores y datos antiguos de la sesión
        $form_errors = $_SESSION['form_errors'] ?? [];
        // CRUCIAL: Se usa $user_data como base para los datos antiguos.
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

            $id_usuario = $_SESSION['id_usuario'];
            
            // 1. Obtener datos completos para verificación de contraseña y ID de Persona
            $user = $this->usuarioModel->obtenerUsuarioCompleto($id_usuario);
            
            if (!$user) {
                $this->setErrorMessage('Error: Datos de usuario logueado no encontrados.');
                $this->redirect('login/logout');
            }

            // CRUCIAL: Obtenemos el ID de la tabla 'persona'
            $id_persona = $user['id_persona']; 

            // Verificar si el setup es requerido
            if (!isset($_SESSION['requires_setup']) || $_SESSION['requires_setup'] != 1) {
                if ($_SESSION['id_rol'] == 2) {
                    $this->redirect('subadmin/dashboard');
                } else {
                    $this->redirect('user/dashboard');
                }
                return;
            }

            // 2. Recolección de datos
            $data = [
                'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'biografia' => trim($_POST['biografia'] ?? ''), // Asumimos en tabla usuario
                'password_actual' => trim($_POST['password_actual'] ?? ''),
                'new_password' => $_POST['new_password'] ?? '',
                'confirm_new_password' => $_POST['confirm_new_password'] ?? '',
            ];

            $errors = [];

            // 3. Validación de la Contraseña Actual
            if (!password_verify($data['password_actual'], $user['password'])) {
                $errors[] = 'La contraseña actual (su Cédula) es incorrecta.';
            }
            
            // 4. Validación de datos de Persona
            if (Validator::isEmpty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es obligatoria.';
            else if (!Validator::isValidDate($data['fecha_nacimiento'])) $errors[] = 'Formato de fecha de nacimiento inválido (YYYY-MM-DD).';

            if (Validator::isEmpty($data['direccion'])) $errors[] = 'La dirección es obligatoria.';
            if (Validator::isEmpty($data['telefono'])) $errors[] = 'El teléfono es obligatorio.';

            // 5. Validación del Email (Correo)
            // CRUCIAL: El correo está en la tabla Persona, la clave es 'correo'
            if (Validator::isEmpty($data['email'])) $errors[] = 'El email es obligatorio.';
            else if (!Validator::isValidEmail($data['email'])) $errors[] = 'Formato de email inválido.';
            // Si el nuevo email es diferente al actual del usuario, verificar si ya existe
            else if ($data['email'] !== $user['correo']) { 
                if ($this->personaModel->buscarPorCorreo($data['email'])) {
                    $errors[] = 'Ya existe un usuario con este correo electrónico.';
                }
            }

            // 6. Validación de la Nueva Contraseña (obligatoria en setup)
            if (Validator::isEmpty($data['new_password'])) {
                $errors[] = 'La nueva contraseña es obligatoria.';
            } else if (!Validator::isValidPassword($data['new_password'])) {
                $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } else if ($data['new_password'] !== $data['confirm_new_password']) {
                $errors[] = 'La nueva contraseña y la confirmación no coinciden.';
            }

            // 7. Procesamiento de la actualización
            if (empty($errors)) {
                
                // --- Datos para la tabla PERSONA ---
                $persona_update_data = [
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'direccion' => $data['direccion'],
                    'telefono' => $data['telefono'],
                    'correo' => $data['email'],
                ];
                
                // --- Datos para la tabla USUARIO ---
                $usuario_update_data = [
                    'password' => password_hash($data['new_password'], PASSWORD_DEFAULT),
                    'requires_setup' => 0,
                    'biografia' => $data['biografia']
                ];
                
                // Ejecutar las dos actualizaciones
                $persona_resultado = $this->personaModel->updatePersona($id_persona, $persona_update_data);
                $usuario_resultado = $this->usuarioModel->update($id_usuario, $usuario_update_data); 
                
                if ($persona_resultado && $usuario_resultado) {
                    
                    // Actualizar la sesión
                    $_SESSION['email'] = $data['email'];
                    $_SESSION['requires_setup'] = 0;
                    $_SESSION['telefono'] = $data['telefono'];
                    $_SESSION['direccion'] = $data['direccion'];
                    $_SESSION['biografia'] = $data['biografia'];
                    $_SESSION['success_message'] = '¡Perfil completado y contraseña actualizada exitosamente!';
                    
                    // Redirección basada en el rol
                    if ($_SESSION['id_rol'] == 2) {
                        $this->redirect('subadmin/dashboard');
                    } else {
                        $this->redirect('user/dashboard');
                    }
                    exit(); 
                } else {
                    // Si falla una de las dos actualizaciones
                    $_SESSION['error_message'] = 'Error al actualizar el perfil en la base de datos.';
                    $_SESSION['form_errors'] = ['Error de Base de Datos al actualizar'];
                    $_SESSION['old_form_data'] = $data;
                    
                    $this->redirect('user/setupProfile');
                }
            } else {
                // Errores de validación
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $data;
                
                $this->redirect('user/setupProfile');
            }

        } else {
            $this->redirect('user/setupProfile');
        }
    }

    // -------------------------------------------------------------------------
    // MÉTODOS DE NOTIFICACIONES (no necesitan cambios, usan notificacionModel)
    // -------------------------------------------------------------------------

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

    public function markNotificationRead($id = null) {
        if (!is_numeric($id) || $id <= 0) {
            $this->setErrorMessage('ID de notificación inválido.');
            $this->redirect('user/notifications');
        }

        $id_notificacion = (int)$id;
        $id_usuario_destino = $_SESSION['id_usuario'];
        
        $notificacion = $this->notificacionModel->find($id_notificacion);

        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_usuario_destino) {
            $this->setErrorMessage('Acceso denegado a esta notificación o no existe.');
            $this->redirect('user/notifications');
        }
        
        if (!$this->notificacionModel->marcarComoLeida($id_notificacion)) {
            $this->setErrorMessage('Error al marcar la notificación como leída.');
        }
        
        $this->redirect('user/notifications');
    }
    
    public function markAllNotificationsRead() {
        $id_usuario = $_SESSION['id_usuario'];
        
        if ($this->notificacionModel->marcarTodasComoLeidas($id_usuario)) {
            $this->setSuccessMessage('Todas las notificaciones han sido marcadas como leídas.');
        } else {
            $this->setErrorMessage('No fue posible marcar todas las notificaciones como leídas.');
        }

        $this->redirect('user/notifications');
    }
    
    public function deleteNotification($id = null) {
        if (!is_numeric($id) || $id <= 0) {
            $this->setErrorMessage('ID de notificación inválido.');
            $this->redirect('user/notifications');
        }

        $id_notificacion = (int)$id;
        $id_usuario_destino = $_SESSION['id_usuario'];
        
        $notificacion = $this->notificacionModel->find($id_notificacion);

        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_usuario_destino) {
            $this->setErrorMessage('Acceso denegado a esta notificación o no existe.');
            $this->redirect('user/notifications');
        }
        
        if ($this->notificacionModel->eliminarNotificacion($id_notificacion)) {
            $this->setSuccessMessage('Notificación eliminada exitosamente.');
        } else {
            $this->setErrorMessage('Error al eliminar la notificación.');
        }
        
        $this->redirect('user/notifications');
    }
}
