<?php
// grupobrasil/app/controllers/SubadminController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Notificacion.php'; // Incluir el modelo de Notificacion
require_once __DIR__ . '/AppController.php';

class SubadminController extends AppController { 
    private $usuarioModel;
    private $noticiaModel;  
    private $comentarioModel; 
    private $notificacionModel; // Declarar el modelo de notificaciones

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->noticiaModel = new Noticia();     
        $this->comentarioModel = new Comentario();
        $this->notificacionModel = new Notificacion(); // Inicializar el modelo de notificaciones
        
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
            header('Location:./index.php?route=login&error=acceso_denegado');
            exit();
        }
    }

    private function setFlash($type, $message) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_' . $type] = $message;
    }

    public function dashboard() {
        $usuariosAsignados = []; 

        $data = [
            'page_title' => 'Dashboard de Sub-Administrador',
            'usuariosAsignados' => $usuariosAsignados 
        ];

        $this->loadView('subadmin/dashboard', $data); 
    }

    public function reports() {
    
        $noticias = $this->noticiaModel->getAll();
        $comentarios = $this->comentarioModel->getAll();

        $data = [
            'page_title' => 'Reportes de Subadministración',
            'noticias' => $noticias,
            'comentarios' => $comentarios
        ];

        $this->loadView('subadmin/reports', $data);
    }

    public function manageComments() {
        $comentarios = $this->comentarioModel->getAllComments(false); // Ver activos e inactivos
        return $this->loadView('subadmin/comentarios/index', [
            'page_title' => 'Gestión de Comentarios (Subadmin)',
            'comentarios' => $comentarios
        ]);
    }

    public function manageNotifications() {
        // Obtenemos el ID del usuario logueado (el Subadmin)
        $id_subadmin = $_SESSION['id_usuario'] ?? 0;
        
        // Obtener todas las notificaciones (leídas y no leídas) para este subadmin
        // El modelo Notificacion solo obtiene el usuario de origen, no el de destino (porque ya está implícito)
        $notificaciones = $this->notificacionModel->obtenerNotificacionesPorUsuario($id_subadmin, false); 

        // Limpiamos los mensajes flash para pasarlos a la vista
        $data = [
            'page_title' => 'Mis Notificaciones',
            'notificaciones' => $notificaciones,
            'success_message' => $_SESSION['flash_success'] ?? null,
            'error_message' => $_SESSION['flash_error'] ?? null,
        ];
        
        // Limpiar la sesión de flash después de recuperarlos
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        // Cargamos la vista de subadmin/notifications/index
        return $this->loadView('subadmin/notifications/index', $data);
    }
    
    /**
     * Marca una notificación específica como leída.
     */
    public function markNotificationRead() {
        $id_subadmin = $_SESSION['id_usuario'] ?? 0;

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->setFlash('error', 'ID de notificación inválido.');
            header('Location:./index.php?route=subadmin/notifications');
            exit();
        }
        $id_notificacion = (int)$_GET['id'];
        
        // VERIFICACIÓN DE SEGURIDAD: 
        // 1. Buscamos la notificación
        $notificacion = $this->notificacionModel->find($id_notificacion);
        // 2. Comprobamos que exista Y que pertenezca al Subadmin actual
        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_subadmin) {
            $this->setFlash('error', 'Acceso denegado a esta notificación o no existe.');
            header('Location:./index.php?route=subadmin/notifications');
            exit();
        }

        if ($this->notificacionModel->marcarComoLeida($id_notificacion)) {
            $this->setFlash('success', 'Notificación marcada como leída.');
        } else {
            $this->setFlash('error', 'Error al marcar la notificación.');
        }
        header('Location:./index.php?route=subadmin/notifications');
        exit();
    }

    /**
     * Marca todas las notificaciones no leídas del Subadmin como leídas.
     */
    public function markAllNotificationsRead() {
        $id_subadmin = $_SESSION['id_usuario'] ?? 0;

        if ($this->notificacionModel->marcarTodasComoLeidas($id_subadmin)) {
            $this->setFlash('success', 'Todas tus notificaciones han sido marcadas como leídas.');
        } else {
            $this->setFlash('error', 'Error al marcar todas las notificaciones.');
        }
        header('Location:./index.php?route=subadmin/notifications');
        exit();
    }

    /**
     * Elimina una notificación específica.
     */
    public function deleteNotification() {
        $id_subadmin = $_SESSION['id_usuario'] ?? 0;

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->setFlash('error', 'ID de notificación inválido.');
            header('Location:./index.php?route=subadmin/notifications');
            exit();
        }
        $id_notificacion = (int)$_GET['id'];

        // VERIFICACIÓN DE SEGURIDAD: 
        // 1. Buscamos la notificación
        $notificacion = $this->notificacionModel->find($id_notificacion);
        // 2. Comprobamos que exista Y que pertenezca al Subadmin actual
        if (!$notificacion || $notificacion['id_usuario_destino'] != $id_subadmin) {
            $this->setFlash('error', 'Acceso denegado a esta notificación o no existe.');
            header('Location:./index.php?route=subadmin/notifications');
            exit();
        }
        
        if ($this->notificacionModel->eliminarNotificacion($id_notificacion)) {
            $this->setFlash('success', 'Notificación eliminada correctamente.');
        } else {
            $this->setFlash('error', 'Error al eliminar la notificación.');
        }
        header('Location:./index.php?route=subadmin/notifications');
        exit();
    }

}