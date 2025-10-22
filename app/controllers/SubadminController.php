<?php
// grupobrasil/app/controllers/SubadminController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Notificacion.php'; 
require_once __DIR__ . '/../models/Habitante.php';
require_once __DIR__ . '/../models/Persona.php';
require_once __DIR__ . '/../models/Vivienda.php';
require_once __DIR__ . '/../models/Calle.php';
require_once __DIR__ . '/../models/LiderCalle.php';
require_once __DIR__ . '/../models/CargaFamiliar.php';
require_once __DIR__ . '/../models/HabitanteVivienda.php';
require_once __DIR__ . '/AppController.php';

class SubadminController extends AppController { 
    private $usuarioModel;
    private $noticiaModel;
    private $comentarioModel; 
    private $notificacionModel; 
    private $habitanteModel;
    private $personaModel;
    private $viviendaModel;
    private $calleModel;
    private $liderCalleModel;
    private $cargaFamiliarModel;
    private $habitanteViviendaModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario();
        $this->notificacionModel = new Notificacion();
        $this->habitanteModel = new Habitante();
        $this->personaModel = new Persona();
        $this->viviendaModel = new Vivienda();
        $this->calleModel = new Calle();
        $this->liderCalleModel = new LiderCalle();
        $this->cargaFamiliarModel = new CargaFamiliar();
        $this->habitanteViviendaModel = new HabitanteVivienda();
        
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

    private function getCurrentHabitanteId() {
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $usuario = $this->usuarioModel->find($idUsuario);
        if (!$usuario) return null;
        
        $habitante = $this->habitanteModel->findByPersonaId($usuario['id_persona']);
        return $habitante ? $habitante['id_habitante'] : null;
    }

    private function getAssignedVeredas() {
        $habitanteId = $this->getCurrentHabitanteId();
        if (!$habitanteId) return [];
        
        return $this->liderCalleModel->getCallesIdsByHabitanteId($habitanteId);
    }

    public function dashboard() {
        $habitanteId = $this->getCurrentHabitanteId();
        $veredasAsignadas = $this->getAssignedVeredas();
        
        $totalHabitantes = 0;
        $totalViviendas = 0;
        $totalFamilias = 0;
        
        if (!empty($veredasAsignadas)) {
            // Count habitantes in assigned veredas
            $sql = "SELECT COUNT(DISTINCT h.id_habitante) as total 
                     FROM habitante h 
                     INNER JOIN persona p ON h.id_persona = p.id_persona 
                     WHERE p.id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ") 
                     AND h.activo = 1";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->habitanteModel->rawQuery($sql); 
            if ($result) {
                $row = $result->fetch_assoc();
                $totalHabitantes = $row['total'] ?? 0;
            }
            
            // Count viviendas in assigned veredas
            $sql = "SELECT COUNT(*) as total 
                     FROM vivienda 
                     WHERE id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ") 
                     AND activo = 1";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->viviendaModel->rawQuery($sql); 
            if ($result) {
                $row = $result->fetch_assoc();
                $totalViviendas = $row['total'] ?? 0;
            }
            
            // Count families (jefes de familia) in assigned veredas
            $sql = "SELECT COUNT(DISTINCT cf.id_jefe) as total 
                     FROM carga_familiar cf
                     INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                     INNER JOIN persona p ON h.id_persona = p.id_persona
                     INNER JOIN vivienda v ON p.id_calle = v.id_calle
                     WHERE p.id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ")
                     AND cf.activo = 1 AND h.activo = 1";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->cargaFamiliarModel->rawQuery($sql); 
            if ($result) {
                $row = $result->fetch_assoc();
                $totalFamilias = $row['total'] ?? 0;
            }
        }

        $data = [
            'page_title' => 'Dashboard de Líder de Vereda',
            'veredasAsignadas' => $veredasAsignadas,
            'totalHabitantes' => $totalHabitantes,
            'totalViviendas' => $totalViviendas,
            'totalFamilias' => $totalFamilias
        ];

        $this->loadView('subadmin/dashboard', $data); 
    }

    public function habitantes() {
        $veredasAsignadas = $this->getAssignedVeredas();
        
        if (empty($veredasAsignadas)) {
            $this->setFlash('error', 'No tienes veredas asignadas.');
            $habitantes = [];
        } else {
            // Get all habitantes in assigned veredas with their details
            $sql = "SELECT h.*, p.*, c.nombre as nombre_vereda, v.numero as numero_casa
                     FROM habitante h
                     INNER JOIN persona p ON h.id_persona = p.id_persona
                     LEFT JOIN calle c ON p.id_calle = c.id_calle
                     LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                     LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                     WHERE p.id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ")
                     AND h.activo = 1
                     ORDER BY c.nombre, p.numero_casa, p.apellidos, p.nombres";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->habitanteModel->rawQuery($sql); 
            $habitantes = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $habitantes[] = $row;
                }
            }
        }
        
        // Get all veredas for the dropdown
        $todasVeredas = $this->calleModel->getAll();
        
        $data = [
            'page_title' => 'Habitantes de Mi Vereda',
            'habitantes' => $habitantes,
            'veredasAsignadas' => $veredasAsignadas,
            'todasVeredas' => $todasVeredas
        ];
        
        $this->loadView('subadmin/habitantes/index', $data);
    }

    public function addHabitante() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        $veredasAsignadas = $this->getAssignedVeredas();
        $idCalle = (int)($_POST['id_calle'] ?? 0);
        
        // Verify the vereda is assigned to this lider
        if (!in_array($idCalle, $veredasAsignadas)) {
            $this->setFlash('error', 'No tienes permiso para agregar habitantes a esta vereda.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        // Create persona first
        $personaData = [
            'cedula' => $_POST['cedula'] ?? null,
            'nombres' => $_POST['nombres'] ?? '',
            'apellidos' => $_POST['apellidos'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'sexo' => $_POST['sexo'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'id_calle' => $idCalle,
            'numero_casa' => $_POST['numero_casa'] ?? '',
            'correo' => $_POST['correo'] ?? null,
            'activo' => 1
        ];
        
        $idPersona = $this->personaModel->create($personaData);
        
        if ($idPersona) {
            // Create habitante
            $habitanteData = [
                'id_persona' => $idPersona,
                'fecha_ingreso' => date('Y-m-d'),
                'condicion' => $_POST['condicion'] ?? 'Residente',
                'activo' => 1
            ];
            
            $idHabitante = $this->habitanteModel->create($habitanteData);
            
            if ($idHabitante) {
                $this->setFlash('success', 'Habitante agregado exitosamente.');
            } else {
                $this->setFlash('error', 'Error al crear el habitante.');
            }
        } else {
            $this->setFlash('error', 'Error al crear la persona.');
        }
        
        header('Location:./index.php?route=subadmin/habitantes');
        exit();
    }

    public function editHabitante() {
        $idHabitante = (int)($_GET['id'] ?? 0);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $veredasAsignadas = $this->getAssignedVeredas();
            $idCalle = (int)($_POST['id_calle'] ?? 0);
            
            // Verify the vereda is assigned to this lider
            if (!in_array($idCalle, $veredasAsignadas)) {
                $this->setFlash('error', 'No tienes permiso para editar habitantes de esta vereda.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }
            
            $habitante = $this->habitanteModel->find($idHabitante);
            if (!$habitante) {
                $this->setFlash('error', 'Habitante no encontrado.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }
            
            // Update persona
            $personaData = [
                'cedula' => $_POST['cedula'] ?? null,
                'nombres' => $_POST['nombres'] ?? '',
                'apellidos' => $_POST['apellidos'] ?? '',
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                'sexo' => $_POST['sexo'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'id_calle' => $idCalle,
                'numero_casa' => $_POST['numero_casa'] ?? '',
                'correo' => $_POST['correo'] ?? null
            ];
            
            $this->personaModel->update($habitante['id_persona'], $personaData);
            
            // Update habitante
            $habitanteData = [
                'condicion' => $_POST['condicion'] ?? 'Residente'
            ];
            
            $this->habitanteModel->update($idHabitante, $habitanteData);
            
            $this->setFlash('success', 'Habitante actualizado exitosamente.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        // GET request - show edit form
        $habitante = $this->habitanteModel->find($idHabitante);
        if (!$habitante) {
            $this->setFlash('error', 'Habitante no encontrado.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        $persona = $this->personaModel->find($habitante['id_persona']);
        $veredasAsignadas = $this->getAssignedVeredas();
        $todasVeredas = $this->calleModel->getAll();
        
        $data = [
            'page_title' => 'Editar Habitante',
            'habitante' => $habitante,
            'persona' => $persona,
            'veredasAsignadas' => $veredasAsignadas,
            'todasVeredas' => $todasVeredas
        ];
        
        $this->loadView('subadmin/habitantes/edit', $data);
    }

    public function deleteHabitante() {
        $idHabitante = (int)($_GET['id'] ?? 0);
        
        $habitante = $this->habitanteModel->find($idHabitante);
        if (!$habitante) {
            $this->setFlash('error', 'Habitante no encontrado.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        $persona = $this->personaModel->find($habitante['id_persona']);
        $veredasAsignadas = $this->getAssignedVeredas();
        
        // Verify the habitante is in an assigned vereda
        if (!in_array($persona['id_calle'], $veredasAsignadas)) {
            $this->setFlash('error', 'No tienes permiso para eliminar este habitante.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        if ($this->habitanteModel->deleteHabitanteWithCascade($idHabitante)) {
            $this->setFlash('success', 'Habitante eliminado exitosamente.');
        } else {
            $this->setFlash('error', 'Error al eliminar el habitante.');
        }
        
        header('Location:./index.php?route=subadmin/habitantes');
        exit();
    }

    public function familias() {
        $veredasAsignadas = $this->getAssignedVeredas();
        
        if (empty($veredasAsignadas)) {
            $this->setFlash('error', 'No tienes veredas asignadas.');
            $familias = [];
        } else {
            // Get all families (jefes de familia) in assigned veredas
            $sql = "SELECT DISTINCT cf.id_jefe, h.*, p.*, c.nombre as nombre_vereda, v.numero as numero_casa,
                     (SELECT COUNT(*) FROM carga_familiar cf2 WHERE cf2.id_jefe = cf.id_jefe AND cf2.activo = 1) as total_miembros
                     FROM carga_familiar cf
                     INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                     INNER JOIN persona p ON h.id_persona = p.id_persona
                     LEFT JOIN calle c ON p.id_calle = c.id_calle
                     LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                     LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
                     WHERE p.id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ")
                     AND cf.activo = 1 AND h.activo = 1
                     ORDER BY c.nombre, v.numero, p.apellidos, p.nombres";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->cargaFamiliarModel->rawQuery($sql);
            $familias = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $familias[] = $row;
                }
            }
        }
        
        $data = [
            'page_title' => 'Familias de Mi Vereda',
            'familias' => $familias,
            'veredasAsignadas' => $veredasAsignadas
        ];
        
        $this->loadView('subadmin/familias/index', $data);
    }

    public function verFamilia() {
        $idJefe = (int)($_GET['id'] ?? 0);
        
        $jefe = $this->habitanteModel->find($idJefe);
        if (!$jefe) {
            $this->setFlash('error', 'Jefe de familia no encontrado.');
            header('Location:./index.php?route=subadmin/familias');
            exit();
        }
        
        $personaJefe = $this->personaModel->find($jefe['id_persona']);
        $veredasAsignadas = $this->getAssignedVeredas();
        
        // Verify the jefe is in an assigned vereda
        if (!in_array($personaJefe['id_calle'], $veredasAsignadas)) {
            $this->setFlash('error', 'No tienes permiso para ver esta familia.');
            header('Location:./index.php?route=subadmin/familias');
            exit();
        }
        
        // Get family members
        $sql = "SELECT cf.*, h.*, p.*, cf.parentesco
                 FROM carga_familiar cf
                 INNER JOIN habitante h ON cf.id_habitante = h.id_habitante
                 INNER JOIN persona p ON h.id_persona = p.id_persona
                 WHERE cf.id_jefe = ? AND cf.activo = 1 AND h.activo = 1
                 ORDER BY p.apellidos, p.nombres";
        
        // USO DE getConnection()->prepare()
        $stmt = $this->cargaFamiliarModel->getConnection()->prepare($sql);
        $stmt->bind_param("i", $idJefe);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $miembros = [];
        while ($row = $result->fetch_assoc()) {
            $miembros[] = $row;
        }
        $stmt->close();
        
        $data = [
            'page_title' => 'Detalles de Familia',
            'jefe' => $jefe,
            'personaJefe' => $personaJefe,
            'miembros' => $miembros
        ];
        
        $this->loadView('subadmin/familias/ver', $data);
    }

    public function asignarLiderFamilia() {
        $idHabitante = (int)($_GET['id'] ?? 0);
        
        $habitante = $this->habitanteModel->find($idHabitante);
        if (!$habitante) {
            $this->setFlash('error', 'Habitante no encontrado.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        $persona = $this->personaModel->find($habitante['id_persona']);
        $veredasAsignadas = $this->getAssignedVeredas();
        
        // Verify the habitante is in an assigned vereda
        if (!in_array($persona['id_calle'], $veredasAsignadas)) {
            $this->setFlash('error', 'No tienes permiso para asignar roles a este habitante.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        // Check if habitante already has a user account
        $usuario = $this->usuarioModel->findByPersonId($persona['id_persona']);
        
        if ($usuario) {
            // Update role to Lider de Familia (rol 3)
            $this->usuarioModel->update($usuario['id_usuario'], ['id_rol' => 3]);
            $this->setFlash('success', 'Rol de Líder de Familia asignado exitosamente.');
        } else {
            // Create user account with Lider de Familia role
            $username = strtolower($persona['nombres']) . '_' . strtolower($persona['apellidos']);
            $username = preg_replace('/[^a-z0-9_]/', '', $username);
            $password = 'familia' . rand(1000, 9999); // Temporary password
            
            $usuarioData = [
                'id_persona' => $persona['id_persona'],
                'id_rol' => 3, // Lider de Familia
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $persona['correo'],
                'estado' => 'activo',
                'activo' => 1
            ];
            
            $idUsuario = $this->usuarioModel->create($usuarioData);
            
            if ($idUsuario) {
                $this->setFlash('success', "Líder de Familia creado. Usuario: $username, Contraseña temporal: $password");
            } else {
                $this->setFlash('error', 'Error al crear el usuario.');
            }
        }
        
        header('Location:./index.php?route=subadmin/habitantes');
        exit();
    }

    public function viviendas() {
        $veredasAsignadas = $this->getAssignedVeredas();
        
        if (empty($veredasAsignadas)) {
            $this->setFlash('error', 'No tienes veredas asignadas.');
            $viviendas = [];
        } else {
            // Get all viviendas in assigned veredas
            $sql = "SELECT v.*, c.nombre as nombre_vereda,
                     (SELECT COUNT(DISTINCT hv.id_habitante) 
                       FROM habitante_vivienda hv 
                       INNER JOIN habitante h ON hv.id_habitante = h.id_habitante
                       WHERE hv.id_vivienda = v.id_vivienda AND h.activo = 1) as total_habitantes,
                     (SELECT COUNT(DISTINCT cf.id_jefe)
                       FROM habitante_vivienda hv
                       INNER JOIN habitante h ON hv.id_habitante = h.id_habitante
                       INNER JOIN carga_familiar cf ON h.id_habitante = cf.id_jefe
                       WHERE hv.id_vivienda = v.id_vivienda AND h.activo = 1 AND cf.activo = 1) as total_familias
                     FROM vivienda v
                     LEFT JOIN calle c ON v.id_calle = c.id_calle
                     WHERE v.id_calle IN (" . implode(',', array_map('intval', $veredasAsignadas)) . ")
                     AND v.activo = 1
                     ORDER BY c.nombre, v.numero";
            
            // USO DEL NUEVO rawQuery()
            $result = $this->viviendaModel->rawQuery($sql);
            $viviendas = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $viviendas[] = $row;
                }
            }
        }
        
        $todasVeredas = $this->calleModel->getAll();
        
        $data = [
            'page_title' => 'Viviendas de Mi Vereda',
            'viviendas' => $viviendas,
            'veredasAsignadas' => $veredasAsignadas,
            'todasVeredas' => $todasVeredas
        ];
        
        $this->loadView('subadmin/viviendas/index', $data);
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
        $comentarios = $this->comentarioModel->getAllComments(false);
        $this->loadView('subadmin/comentarios/index', [
            'page_title' => 'Gestión de Comentarios (Subadmin)',
            'comentarios' => $comentarios
        ]); // **CORRECCIÓN: Eliminado 'return'**
    }

    public function manageNotifications() {
        $id_subadmin = $_SESSION['id_usuario'] ?? 0;
        
        $notificaciones = $this->notificacionModel->obtenerNotificacionesPorUsuario($id_subadmin, false); 

        $data = [
            'page_title' => 'Mis Notificaciones',
            'notificaciones' => $notificaciones,
            'success_message' => $_SESSION['flash_success'] ?? null,
            'error_message' => $_SESSION['flash_error'] ?? null,
        ];
        
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->loadView('subadmin/notifications/index', $data); // **CORRECCIÓN: Eliminado 'return'**
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
        
        $notificacion = $this->notificacionModel->find($id_notificacion);
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

        $notificacion = $this->notificacionModel->find($id_notificacion);
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
