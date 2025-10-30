<?php
// grupobrasil/app/controllers/UserController.php

require_once __DIR__ . '/AppController.php'; 
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Persona.php'; // NUEVO: Incluir el modelo Persona
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/CargaFamiliar.php';
require_once __DIR__ . '/../models/Habitante.php';
require_once __DIR__ . '/../models/HabitanteVivienda.php';
require_once __DIR__ . '/../models/Vivienda.php';
require_once __DIR__ . '/../models/ViviendaDetalle.php';

// Hereda de AppController para usar loadView y redirect
class UserController extends AppController {
    private $usuarioModel;
    private $personaModel; // NUEVO: Propiedad para el modelo Persona
    private $noticiaModel;
    private $notificacionModel;
    private $cargaFamiliarModel;
    private $habitanteModel;
    private $habitanteViviendaModel;
    private $viviendaModel;
    private $viviendaDetalleModel;

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

    // Modelos adicionales para funcionalidades de Jefe de Familia
    $this->cargaFamiliarModel = new CargaFamiliar();
    $this->habitanteModel = new Habitante();
    $this->habitanteViviendaModel = new HabitanteVivienda();
    $this->viviendaModel = new Vivienda();
    $this->viviendaDetalleModel = new ViviendaDetalle();
        
        // Restricción de acceso general (se asume que AppController maneja el setupProfile)
        if (!isset($_SESSION['id_rol']) || ($_SESSION['id_rol'] != 3 && $_SESSION['id_rol'] != 2)) {
             $this->redirect('login', ['error' => 'acceso_denegado']);
        }
    }

    private function getCurrentHabitanteId() {
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $usuario = $this->usuarioModel->find($idUsuario);
        if (!$usuario) return null;
        $habitante = $this->habitanteModel->findByPersonaId($usuario['id_persona']);
        return $habitante ? $habitante['id_habitante'] : null;
    }

    // Mostrar la carga familiar del usuario (si es jefe)
    public function cargaFamiliar() {
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $carga = $this->cargaFamiliarModel->getCargaFamiliarPorUsuario($idUsuario);
        $data = [
            'page_title' => 'Mi Carga Familiar',
            'carga_familiar' => $carga ?: []
        ];
        return $this->loadView('user/carga_familiar/index', $data);
    }

    // Agregar miembro a la carga (POST)
    public function addMember() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('user/carga_familiar'); }
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $idHabitanteJefe = $this->getCurrentHabitanteId();
    if (!$idHabitanteJefe) { $this->setErrorMessage('No se pudo determinar tu registro como habitante.'); $this->redirect('user/carga_familiar'); }
        // ÚNICO FLUJO: agregar por id_habitante existente (no permitimos crear nuevos desde aquí)
        $existingHabitanteId = (int)($_POST['existing_habitante_id'] ?? 0);
        $parentesco = trim($_POST['parentesco'] ?? null);

        // Validaciones servidor para parentesco
        if ($parentesco !== null && $parentesco !== '') {
            if (mb_strlen($parentesco) > 20) {
                $this->setErrorMessage('El parentesco no puede tener más de 20 caracteres.');
                $this->redirect('user/carga_familiar');
            }
            if (preg_match('/\d/', $parentesco)) {
                $this->setErrorMessage('El parentesco no puede contener números.');
                $this->redirect('user/carga_familiar');
            }
        }

        if ($existingHabitanteId <= 0) {
            $this->setErrorMessage('Selecciona un habitante existente para agregar a la carga familiar.');
            $this->redirect('user/carga_familiar');
        }

        // Verificar que el habitante no pertenezca ya a otra carga
        if ($this->cargaFamiliarModel->isHabitanteInAnyCarga($existingHabitanteId)) {
            $this->setErrorMessage('La persona seleccionada ya pertenece a otra carga familiar.');
            $this->redirect('user/carga_familiar');
        }

        $newId = $this->cargaFamiliarModel->addMemberToJefe($idHabitanteJefe, $existingHabitanteId, $parentesco);
        if ($newId) { $this->setSuccessMessage('Miembro agregado.'); } else { $this->setErrorMessage('Error al agregar miembro.'); }
    $this->redirect('user/carga_familiar');
    }

    /**
     * Endpoint AJAX para buscar habitantes por cédula o nombre
     * Devuelve JSON
     */
    public function searchHabitante() {
        $q = trim($_GET['q'] ?? '');
        header('Content-Type: application/json; charset=utf-8');
        if (strlen($q) < 2) {
            echo json_encode(['success' => false, 'message' => 'Consulta muy corta', 'data' => []]);
            exit();
        }

        $results = $this->habitanteModel->searchByQuery($q, 20);
        echo json_encode(['success' => true, 'data' => $results]);
        exit();
    }

    // Eliminar miembro (POST)
    public function deleteMember() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('user/carga_familiar'); }
        $id_comun = (int)($_POST['id_carga'] ?? 0);
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $idHabitanteJefe = $this->getCurrentHabitanteId();
    if (!$idHabitanteJefe) { $this->setErrorMessage('No autorizado'); $this->redirect('user/carga_familiar'); }

        // Verificar que el registro exista
        $reg = $this->cargaFamiliarModel->getById($id_comun);
        if (!$reg) { $this->setErrorMessage('Registro no encontrado.'); $this->redirect('user/carga_familiar'); }

        // Permisos: el dueño (jefe) puede eliminar; además Admin (rol 1) puede eliminar cualquiera;
        // Líder (rol 2) puede eliminar si el miembro pertenece a una calle bajo su responsabilidad.
        $currentRole = $_SESSION['id_rol'] ?? null;

        $allowed = false;
        if ((int)$reg['id_jefe'] === (int)$idHabitanteJefe) {
            $allowed = true;
        } elseif ($currentRole == 1) {
            // Admin puede eliminar cualquiera
            $allowed = true;
        } elseif ($currentRole == 2) {
            // Verificar alcance del líder
            require_once __DIR__ . '/../models/LiderCalle.php';
            $liderCalle = new LiderCalle();

            // Obtener calles asignadas al líder (a partir del id_usuario)
            $callesIds = $liderCalle->getCallesIdsPorUsuario($idUsuario);
            if (!empty($callesIds)) {
                // Obtener la persona del miembro
                $memberHabitante = $this->habitanteModel->getById((int)$reg['id_habitante']);
                if ($memberHabitante && !empty($memberHabitante['id_persona'])) {
                    $memberPersona = $this->personaModel->getById((int)$memberHabitante['id_persona']);
                    $memberCalle = $memberPersona['id_calle'] ?? null;
                    if ($memberCalle && in_array((int)$memberCalle, $callesIds, true)) {
                        $allowed = true;
                    }
                }
            }
        }

        if (!$allowed) {
            $this->setErrorMessage('No tienes permiso para eliminar este miembro.');
            $this->redirect('user/carga_familiar');
        }

        if ($this->cargaFamiliarModel->delete($id_comun)) $this->setSuccessMessage('Miembro eliminado.'); else $this->setErrorMessage('Error al eliminar miembro.');
        $this->redirect('user/carga_familiar');
    }

    // Mostrar/editar detalles de la vivienda del usuario
    public function viviendaDetails() {
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $usuario = $this->usuarioModel->find($idUsuario);
        if (!$usuario) { $this->setErrorMessage('Usuario no encontrado'); $this->redirect('user/dashboard'); }

        // Obtener habitante y vivienda
        $habitante = $this->habitanteModel->findByPersonaId($usuario['id_persona']);
        $detalle = false;
        $vivienda = false;
        if ($habitante) {
            // buscar vivienda asignada al habitante
            $sql = "SELECT hv.id_vivienda FROM habitante_vivienda hv WHERE hv.id_habitante = ? AND hv.es_jefe_familia = 1 LIMIT 1";
            $stmt = $this->habitanteViviendaModel->getConnection()->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $habitante['id_habitante']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    $idVivienda = (int)$row['id_vivienda'];
                    $vivienda = $this->viviendaModel->getById($idVivienda);
                    $detalle = $this->viviendaDetalleModel->getByViviendaId($idVivienda);
                }
                $stmt->close();
            }
        }

        // Obtener residentes (antes estaba en la vista)
        $residents = [];
        if ($vivienda) {
            $residents = $this->habitanteModel->getByViviendaId((int)$vivienda['id_vivienda']);
        }

        $data = ['page_title' => 'Detalles de mi Vivienda', 'vivienda' => $vivienda, 'detalle' => $detalle, 'residents' => $residents];
        return $this->loadView('user/vivienda/details', $data);
    }

    public function updateViviendaDetails() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('user/vivienda_details'); }
        $idVivienda = (int)($_POST['id_vivienda'] ?? 0);
        if ($idVivienda <= 0) { $this->setErrorMessage('Vivienda inválida'); $this->redirect('user/vivienda_details'); }

        // Normalizar servicios: convertir a array, trim y unir sin duplicados
        $rawServicios = trim($_POST['servicios'] ?? '');
        $parts = array_filter(array_map('trim', explode(',', $rawServicios)), function($v){ return $v !== ''; });
        // eliminar duplicados y normalizar espacios
        $parts = array_values(array_unique($parts));
        $normalizedServicios = implode(',', $parts);

        // Validaciones servidor: habitaciones y banos deben ser enteros 0-99
        $habitaciones = (int)($_POST['habitaciones'] ?? 0);
        $banos = (int)($_POST['banos'] ?? 0);
        if ($habitaciones < 0 || $habitaciones > 99 || $banos < 0 || $banos > 99) {
            $this->setErrorMessage('Habitaciones y baños deben ser números entre 0 y 99.');
            $this->redirect('user/vivienda_details');
        }

        // Validación servicios: longitud máxima 70 y no pueden contener dígitos
        if (strlen($normalizedServicios) > 70) {
            $this->setErrorMessage('El campo servicios no puede superar los 70 caracteres.');
            $this->redirect('user/vivienda_details');
        }
        if (preg_match('/\d/', $normalizedServicios)) {
            $this->setErrorMessage('El campo servicios no puede contener números.');
            $this->redirect('user/vivienda_details');
        }

        $newData = [
            'habitaciones' => $habitaciones,
            'banos' => $banos,
            'servicios' => $normalizedServicios
        ];

        // Obtener valores antiguos para auditoría
        $old = $this->viviendaDetalleModel->getByViviendaId($idVivienda);

        $ok = $this->viviendaDetalleModel->createOrUpdateByVivienda($idVivienda, $newData);

        if ($ok) {
            // Registrar auditoría si hay cambios
            $changes = [];
            if (!$old) {
                $changes['created'] = $newData;
            } else {
                foreach ($newData as $k => $v) {
                    $oldVal = isset($old[$k]) ? $old[$k] : null;
                    if ((string)$oldVal !== (string)$v) {
                        $changes[$k] = ['old' => $oldVal, 'new' => $v];
                    }
                }
            }

            if (!empty($changes)) {
                require_once __DIR__ . '/../models/ViviendaDetalleAudit.php';
                $auditModel = new ViviendaDetalleAudit();
                $auditData = [
                    'id_vivienda' => $idVivienda,
                    'id_usuario' => $_SESSION['id_usuario'] ?? null,
                    'cambios' => json_encode($changes, JSON_UNESCAPED_UNICODE)
                ];
                $auditModel->createAudit($auditData);
            }

            $this->setSuccessMessage('Detalles actualizados.');
        } else {
            $this->setErrorMessage('Error al guardar detalles.');
        }

        $this->redirect('user/vivienda_details');
    }

    public function dashboard() {
        $data = [
            'page_title' => 'Mi Dashboard',
        ];
        return $this->loadView('user/dashboard', $data); 
    }

    // Delegadores para rutas de pagos (usuario/jefe de familia)
    public function userIndexPeriodos($id = null) {
        require_once __DIR__ . '/PagoController.php';
        $pc = new PagoController();
        return $pc->userIndexPeriodos($id);
    }

    public function userDetallePeriodo($id = null) {
        require_once __DIR__ . '/PagoController.php';
        $pc = new PagoController();
        return $pc->userDetallePeriodo($id);
    }

    public function userSubmitPago($id = null) {
        require_once __DIR__ . '/PagoController.php';
        $pc = new PagoController();
        return $pc->userSubmitPago($id);
    }

    public function userHistorial($id = null) {
        require_once __DIR__ . '/PagoController.php';
        $pc = new PagoController();
        return $pc->userHistorial($id);
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
