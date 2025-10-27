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
require_once __DIR__ . '/../helpers/CsrfHelper.php';

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
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        
        // Obtener calles asignadas al líder
        $callesAsignadas = $this->liderCalleModel->getCallesConDetallesPorUsuario($idUsuario);
        $calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);
        
        if (empty($calleIds)) {
            $this->setFlash('error', 'No tienes calles asignadas.');
            $habitantes = [];
            $totalHabitantes = 0;
        } else {
            // Obtener habitantes filtrados por las calles del líder
            $habitantes = $this->habitanteModel->getHabitantesPorCalles($calleIds);
            $totalHabitantes = $this->habitanteModel->contarPorCalles($calleIds);
        }
        
        // Get all calles for the dropdown
        $todasVeredas = $this->calleModel->getAll();
        
        $data = [
            'page_title' => 'Habitantes de Mis Calles',
            'habitantes' => $habitantes,
            'calles_asignadas' => $callesAsignadas,
            'veredasAsignadas' => $calleIds,  // Para compatibilidad con la vista
            'todasVeredas' => $todasVeredas,  // Para compatibilidad con la vista
            'total_habitantes' => $totalHabitantes
        ];
        
        $this->loadView('subadmin/habitantes/index', $data);
    }

    public function addHabitante() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
        
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);
        $idCalle = (int)($_POST['id_calle'] ?? 0);
        $idVivienda = (int)($_POST['id_vivienda'] ?? 0);
        $cedula = trim($_POST['cedula'] ?? '');
        
        // Validaciones
        $errores = [];
        
        // Verificar que la calle esté asignada al líder
        if (!in_array($idCalle, $calleIds)) {
            $errores[] = 'No tienes permiso para agregar habitantes a esta calle. Solo puedes registrar en tus calles asignadas.';
        }
        
        // Validar campos requeridos
        if (empty($_POST['nombres'])) {
            $errores[] = 'El nombre es obligatorio.';
        }
        
        if (empty($_POST['apellidos'])) {
            $errores[] = 'Los apellidos son obligatorios.';
        }
        
        // Verificar si la cédula ya existe (si se proporcionó)
        if (!empty($cedula)) {
            $personaExistente = $this->personaModel->buscarPorCI($cedula);
            if ($personaExistente) {
                $errores[] = 'La cédula ' . htmlspecialchars($cedula) . ' ya está registrada en el sistema.';
            }
        }

        // Validaciones de longitudes (servidor)
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        if (mb_strlen($cedula) > 9) $errores[] = 'La cédula no puede tener más de 9 caracteres.';
        if (mb_strlen($nombres) > 50) $errores[] = 'Nombres no puede tener más de 50 caracteres.';
        if (mb_strlen($apellidos) > 50) $errores[] = 'Apellidos no puede tener más de 50 caracteres.';
        if (!empty($telefono) && mb_strlen($telefono) > 11) $errores[] = 'Teléfono no puede tener más de 11 caracteres.';

        // Si se solicita crear usuario (Líder) validar email y contraseñas
        $createUser = isset($_POST['create_user']) && $_POST['create_user'] == '1';
        $user_email = trim($_POST['user_email'] ?? '');
        $user_password = $_POST['user_password'] ?? '';
        $user_password_confirm = $_POST['user_password_confirm'] ?? '';
        if ($createUser) {
            if (empty($user_email)) {
                $errores[] = 'El Email es requerido para crear la cuenta de usuario.';
            } elseif (mb_strlen($user_email) > 30) {
                $errores[] = 'El Email no puede tener más de 30 caracteres.';
            } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El Email proporcionado no tiene un formato válido.';
            }

            if (mb_strlen($user_password) < 8 || mb_strlen($user_password) > 16) {
                $errores[] = 'La contraseña debe tener entre 8 y 16 caracteres.';
            }
            if ($user_password !== $user_password_confirm) {
                $errores[] = 'Las contraseñas no coinciden.';
            }
        }
        
        // Si hay errores, mostrarlos y redirigir
        if (!empty($errores)) {
            $_SESSION['flash_error'] = implode('<br>', $errores);
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
            'correo' => $_POST['correo'] ?? null,
            'id_calle' => $idCalle,
            'activo' => 1
        ];
        
        error_log("Datos de persona a crear: " . print_r($personaData, true));
        
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
                // Asignar a vivienda si se seleccionó
                if ($idVivienda > 0) {
                    $esJefeFamilia = isset($_POST['es_jefe_familia']) ? 1 : 0;
                    $habitanteViviendaData = [
                        'id_habitante' => (int)$idHabitante,
                        'id_vivienda' => (int)$idVivienda,
                        'es_jefe_familia' => (int)$esJefeFamilia,
                        'fecha_ingreso' => date('Y-m-d'),
                        'activo' => 1
                    ];
                    
                    error_log("Intentando crear habitante_vivienda: " . print_r($habitanteViviendaData, true));
                    
                    $resultado = $this->habitanteViviendaModel->create($habitanteViviendaData);
                    
                    if (!$resultado) {
                        error_log("Error al crear habitante_vivienda");
                    }
                }
                // Si se solicitó crear usuario, intentarlo ahora
                if ($createUser) {
                    // Crear usuario solo (persona ya creada)
                    $usuarioData = [
                        'id_persona' => $idPersona,
                        'email' => $user_email,
                        'password' => password_hash($user_password, PASSWORD_DEFAULT),
                        'id_rol' => 2, // Líder de vereda
                        'activo' => 1
                    ];

                    $usuarioId = $this->usuarioModel->createUserOnly($usuarioData);
                    if (!$usuarioId) {
                        // Intentamos continuar pero informamos
                        $_SESSION['flash_success'] = 'Habitante agregado, pero no se pudo crear la cuenta de usuario (email posiblemente duplicado).';
                        header('Location:./index.php?route=subadmin/habitantes');
                        exit();
                    }
                    $_SESSION['flash_success'] = 'Habitante y cuenta de usuario creados exitosamente.';
                } else {
                    $_SESSION['flash_success'] = 'Habitante agregado exitosamente.';
                }
            } else {
                $_SESSION['flash_error'] = 'Error al crear el habitante.';
            }
        } else {
            $_SESSION['flash_error'] = 'Error al crear la persona.';
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

    /**
     * API: devuelve miembros de la familia por id de jefe (AJAX)
     * GET: ?route=subadmin/familias&action=miembros&jefe=ID
     */
    public function miembrosFamilia() {
        header('Content-Type: application/json');
        $jefe = isset($_GET['jefe']) ? (int)$_GET['jefe'] : 0;
        if ($jefe <= 0) { http_response_code(400); echo json_encode([]); exit; }

        $jefeHabitante = $this->habitanteModel->find($jefe);
        if (!$jefeHabitante) { http_response_code(404); echo json_encode([]); exit; }

        $personaJefe = $this->personaModel->find($jefeHabitante['id_persona']);
        $assigned = $this->getAssignedVeredas();
        if (!in_array((int)$personaJefe['id_calle'], $assigned)) { http_response_code(403); echo json_encode([]); exit; }

        $miembros = $this->cargaFamiliarModel->getCargaFamiliarConDatos($jefe);
        echo json_encode($miembros ?: []);
        exit;
    }

    public function asignarLiderFamilia() {
        // Este método soporta GET para mostrar el formulario de asignación y POST para procesarlo.
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {
            $idHabitante = (int)($_GET['id'] ?? 0);
            $habitante = $this->habitanteModel->find($idHabitante);
            if (!$habitante) {
                $this->setFlash('error', 'Habitante no encontrado.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }

            $persona = $this->personaModel->find($habitante['id_persona']);
            $veredasAsignadas = $this->getAssignedVeredas();
            if (!in_array($persona['id_calle'], $veredasAsignadas)) {
                $this->setFlash('error', 'No tienes permiso para asignar roles a este habitante.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }

            // Obtener viviendas disponibles solo en las veredas asignadas al líder
            $viviendas = [];
            if (!empty($veredasAsignadas)) {
                $viviendas = $this->viviendaModel->getViviendasPorCalles($veredasAsignadas);
            }

            $data = [
                'page_title' => 'Asignar como Jefe de Familia',
                'habitante' => $habitante,
                'persona' => $persona,
                'viviendas' => $viviendas
            ];

            $this->loadView('subadmin/habitantes/asignar_lider_familia', $data);
            return;
        }

        // POST: procesar la asignación
        if ($method === 'POST') {
            $idHabitante = (int)($_POST['id_habitante'] ?? 0);
            $idVivienda = (int)($_POST['id_vivienda'] ?? 0);

            $habitante = $this->habitanteModel->find($idHabitante);
            if (!$habitante) {
                $this->setFlash('error', 'Habitante no encontrado.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }

            $persona = $this->personaModel->find($habitante['id_persona']);
            $veredasAsignadas = $this->getAssignedVeredas();
            if (!in_array($persona['id_calle'], $veredasAsignadas)) {
                $this->setFlash('error', 'No tienes permiso para asignar roles a este habitante.');
                header('Location:./index.php?route=subadmin/habitantes');
                exit();
            }

            // Check/Create user and set role to 3 (Jefe de Familia)
            $usuario = $this->usuarioModel->findByPersonId($persona['id_persona']);
            if ($usuario) {
                $this->usuarioModel->update($usuario['id_usuario'], ['id_rol' => 3]);
            } else {
                $username = strtolower($persona['nombres']) . '_' . strtolower($persona['apellidos']);
                $username = preg_replace('/[^a-z0-9_]/', '', $username);
                $password = 'familia' . rand(1000, 9999);
                $usuarioData = [
                    'id_persona' => $persona['id_persona'],
                    'id_rol' => 3,
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
                    header('Location:./index.php?route=subadmin/habitantes');
                    exit();
                }
            }

            // Si se seleccionó vivienda, crear habitante_vivienda con es_jefe_familia = 1
            if ($idVivienda > 0) {
                // Verificar que la vivienda pertenezca a una vereda asignada
                $v = $this->viviendaModel->getById($idVivienda);
                if (!$v || !in_array((int)$v['id_calle'], $veredasAsignadas)) {
                    $this->setFlash('error', 'La vivienda seleccionada no pertenece a tus veredas asignadas.');
                    header('Location:./index.php?route=subadmin/habitantes');
                    exit();
                }

                require_once __DIR__ . '/../models/HabitanteVivienda.php';
                $hvModel = new HabitanteVivienda();
                $hvModel->deleteByHabitanteId($idHabitante);
                $hvData = [
                    'id_habitante' => $idHabitante,
                    'id_vivienda' => $idVivienda,
                    'es_jefe_familia' => 1,
                    'fecha_ingreso' => date('Y-m-d'),
                    'activo' => 1
                ];
                $hvModel->create($hvData);
            }

            $this->setFlash('success', 'Asignación de Jefe de Familia completada.');
            header('Location:./index.php?route=subadmin/habitantes');
            exit();
        }
    }

    public function viviendas() {
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        
        // Obtener calles asignadas al líder con detalles
        $callesAsignadas = $this->liderCalleModel->getCallesConDetallesPorUsuario($idUsuario);
        $calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);
        
        if (empty($calleIds)) {
            $this->setFlash('error', 'No tienes calles asignadas.');
            $viviendas = [];
            $totalViviendas = 0;
        } else {
            // Obtener viviendas filtradas por las calles del líder
            $viviendas = $this->viviendaModel->getViviendasPorCalles($calleIds);
            $totalViviendas = $this->viviendaModel->contarPorCalles($calleIds);
        }
        
        // Obtener todas las calles para el dropdown
        $todasVeredas = $this->calleModel->getAll();
        
        $data = [
            'page_title' => 'Viviendas de Mis Calles',
            'viviendas' => $viviendas,
            'calles_asignadas' => $callesAsignadas,
            'veredasAsignadas' => $calleIds,  // Para compatibilidad con la vista
            'todasVeredas' => $todasVeredas,  // Para compatibilidad con la vista
            'total_viviendas' => $totalViviendas
        ];

        // DEBUG: volcar en el log las calles asignadas para este líder (ayuda a depurar vistas que esperan 'nombre' vs 'nombre_calle')
        error_log("DEBUG Subadmin::viviendas - callesAsignadas: " . print_r($callesAsignadas, true));

        $this->loadView('subadmin/viviendas/index', $data);
    }

    /**
     * Endpoint de diagnóstico: devuelve en JSON las calles asignadas y sus IDs
     * Accesible sólo para el role 2 (subadmin) ya que el constructor verifica el rol.
     * URL: ?route=subadmin/debugCallesAsignadas
     */
    public function debugCallesAsignadas() {
        header('Content-Type: application/json');
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        if (!$idUsuario) { http_response_code(401); echo json_encode(['error' => 'No autenticado']); exit; }

        $callesAsignadas = $this->liderCalleModel->getCallesConDetallesPorUsuario($idUsuario);
        $calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);

        // Loguear para revisión en logs de Apache/PHP
        error_log("DEBUG Subadmin::debugCallesAsignadas - callasAsignadas: " . print_r($callesAsignadas, true));

        echo json_encode([
            'calles_asignadas' => $callesAsignadas,
            'veredasAsignadas' => $calleIds
        ]);
        exit;
    }

    /**
     * API: devuelve viviendas de una vereda asignada al líder con conteo de familias
     * GET: ?route=subadmin/viviendas&action=byCalle&id=X
     */
    public function viviendasByCalle() {
        header('Content-Type: application/json');
        $idCalle = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idCalle <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de calle inválido']);
            exit;
        }

        $assigned = $this->getAssignedVeredas();
        if (!in_array($idCalle, $assigned)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para ver estas viviendas']);
            exit;
        }

        $sql = "SELECT v.id_vivienda, v.numero, v.tipo, v.estado, v.activo,
                       COALESCE( (
                           SELECT COUNT(DISTINCT cf.id_jefe)
                           FROM carga_familiar cf
                           INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                           INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                           WHERE hv.id_vivienda = v.id_vivienda AND cf.activo = 1
                       ), 0) AS total_familias
                FROM vivienda v
                WHERE v.id_calle = ? AND v.activo = 1
                ORDER BY v.numero ASC";

        $stmt = $this->viviendaModel->getConnection()->prepare($sql);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al preparar la consulta']);
            exit;
        }

        $stmt->bind_param('i', $idCalle);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
        }
        $stmt->close();

        echo json_encode($data);
        exit;
    }

    /**
     * API: devuelve familias que residen en una vivienda (jefes + miembros)
     * GET: ?route=subadmin/viviendas&action=familiasPorVivienda&id=X
     */
    public function familiasPorVivienda() {
        header('Content-Type: application/json');
        $idVivienda = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idVivienda <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de vivienda inválido']);
            exit;
        }

        $v = $this->viviendaModel->getById($idVivienda);
        if (!$v) { http_response_code(404); echo json_encode(['error'=>'Vivienda no encontrada']); exit; }

        $assigned = $this->getAssignedVeredas();
        if (!in_array((int)$v['id_calle'], $assigned)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para ver estas familias']);
            exit;
        }

        $sql = "SELECT DISTINCT cf.id_jefe
                FROM carga_familiar cf
                INNER JOIN habitante h ON cf.id_jefe = h.id_habitante
                INNER JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
                WHERE hv.id_vivienda = ? AND cf.activo = 1";

        $db = $this->cargaFamiliarModel->getConnection();
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al preparar consulta de familias']);
            exit;
        }
        $stmt->bind_param('i', $idVivienda);
        $stmt->execute();
        $res = $stmt->get_result();
        $jefes = [];
        while ($r = $res->fetch_assoc()) { $jefes[] = (int)$r['id_jefe']; }
        $stmt->close();

        $familias = [];
        foreach ($jefes as $jefeId) {
            $miembros = $this->cargaFamiliarModel->getCargaFamiliarConDatos($jefeId);
            $familias[] = ['id_jefe' => $jefeId, 'miembros' => $miembros];
        }

        echo json_encode($familias);
        exit;
    }

    public function viviendasShow($id) {
        header('Content-Type: application/json');
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID requerido']); exit; }
        $v = $this->viviendaModel->getById($id);
        if (!$v) { http_response_code(404); echo json_encode(['error'=>'Vivienda no encontrada']); exit; }
        $assigned = $this->getAssignedVeredas();
        if (!in_array((int)$v['id_calle'], $assigned)) { http_response_code(403); echo json_encode(['error'=>'No autorizado']); exit; }
        echo json_encode($v); exit;
    }

    public function viviendasStore() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['numero']) || empty($input['tipo'])) { http_response_code(400); echo json_encode(['error'=>'Datos incompletos']); exit; }
        $idCalle = isset($input['id_calle']) ? (int)$input['id_calle'] : 0;
        $assigned = $this->getAssignedVeredas();
        if ($idCalle <=0 || !in_array($idCalle, $assigned)) { http_response_code(403); echo json_encode(['error'=>'No tienes permiso para crear en esta vereda']); exit; }

        // Validaciones: numero numérico y max 3 dígitos
        $numero = trim((string)$input['numero']);
        if (!preg_match('/^\d{1,3}$/', $numero)) { http_response_code(400); echo json_encode(['error'=>'El número debe ser numérico y tener máximo 3 dígitos.']); exit; }

        // Verificar unicidad en la vereda
        if ($this->viviendaModel->existsNumeroEnCalle($numero, $idCalle)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe una vivienda con ese número en la vereda seleccionada.']);
            exit;
        }

        $data = ['numero'=>$numero, 'tipo'=>$input['tipo'], 'estado'=>$input['estado'] ?? 'Activo', 'activo'=>1, 'id_calle'=>$idCalle];
        $id = $this->viviendaModel->createVivienda($data);
        if ($id) echo json_encode(['message'=>'Vivienda creada exitosamente','id_vivienda'=>$id]); else { http_response_code(500); echo json_encode(['error'=>'Error al crear vivienda']); }
        exit;
    }

    public function viviendasUpdate($id) {
        header('Content-Type: application/json');
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID requerido']); exit; }
        $v = $this->viviendaModel->getById($id);
        if (!$v) { http_response_code(404); echo json_encode(['error'=>'Vivienda no encontrada']); exit; }
        $assigned = $this->getAssignedVeredas();
        if (!in_array((int)$v['id_calle'], $assigned)) { http_response_code(403); echo json_encode(['error'=>'No tienes permiso para editar esta vivienda']); exit; }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) { http_response_code(400); echo json_encode(['error'=>'Datos inválidos']); exit; }

        $data = [];
        if (isset($input['numero'])) {
            $numero = trim((string)$input['numero']);
            if (!preg_match('/^\d{1,3}$/', $numero)) { http_response_code(400); echo json_encode(['error'=>'El número debe ser numérico y tener máximo 3 dígitos.']); exit; }
            $data['numero'] = $numero;
        }
        if (isset($input['tipo'])) $data['tipo'] = $input['tipo'];
        if (isset($input['estado'])) $data['estado'] = $input['estado'];
        if (isset($input['id_calle'])) {
            $newCalle = (int)$input['id_calle'];
            if (!in_array($newCalle, $assigned)) { http_response_code(403); echo json_encode(['error'=>'No tienes permiso para asignar a esa vereda']); exit; }
            $data['id_calle'] = $newCalle;
        }

        // Si se actualiza número o calle, verificar unicidad
        $checkNumero = $data['numero'] ?? null;
        $checkCalle = isset($data['id_calle']) ? (int)$data['id_calle'] : (int)$this->viviendaModel->getById($id)['id_calle'];
        if ($checkNumero !== null) {
            $exists = $this->viviendaModel->existsNumeroEnCalle($checkNumero, $checkCalle, (int)$id);
            if ($exists) { http_response_code(400); echo json_encode(['error'=>'Ya existe una vivienda con ese número en la vereda seleccionada.']); exit; }
        }

        $result = $this->viviendaModel->updateVivienda($id, $data);
        if ($result) echo json_encode(['message'=>'Vivienda actualizada exitosamente']); else { http_response_code(500); echo json_encode(['error'=>'Error al actualizar vivienda']); }
        exit;
    }

    public function viviendasDestroy($id) {
        header('Content-Type: application/json');
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'ID requerido']); exit; }
        $v = $this->viviendaModel->getById($id);
        if (!$v) { http_response_code(404); echo json_encode(['error'=>'Vivienda no encontrada']); exit; }
        $assigned = $this->getAssignedVeredas();
        if (!in_array((int)$v['id_calle'], $assigned)) { http_response_code(403); echo json_encode(['error'=>'No tienes permiso para eliminar esta vivienda']); exit; }

        $result = $this->viviendaModel->softDelete($id);
        if ($result) echo json_encode(['message'=>'Vivienda eliminada exitosamente']); else { http_response_code(500); echo json_encode(['error'=>'Error al eliminar vivienda']); }
        exit;
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
        // Para la vista de gestión preferimos mostrar las noticias con conteo de comentarios
        // y permitir al subadmin seleccionar una noticia para ver sus comentarios (modal AJAX)
        $sql = "SELECT n.id_noticia, n.titulo, COUNT(c.id_comentario) AS conteo
                FROM noticias n
                LEFT JOIN comentarios c ON n.id_noticia = c.id_noticia
                GROUP BY n.id_noticia, n.titulo
                ORDER BY conteo DESC, n.titulo ASC";

        $res = $this->comentarioModel->rawQuery($sql);
        $noticias = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) { $noticias[] = $r; }
            $res->free();
        }

        // Obtener detalles de veredas asignadas para mostrar en modal de visibilidad
        $idUsuario = $_SESSION['id_usuario'] ?? 0;
        $callesDetalles = $this->liderCalleModel->getCallesConDetallesPorUsuario($idUsuario);

        $this->loadView('subadmin/comentarios/index', [
            'page_title' => 'Gestión de Comentarios (Subadmin)',
            'noticias' => $noticias,
            'callesAsignadas' => $callesDetalles
        ]);
    }

    /**
     * GET (AJAX) - devuelve visibilidad actual para una noticia
     */
    public function getVisibilityForNews() {
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }

        require_once __DIR__ . '/../models/NoticiaVisibilidad.php';
        $nv = new NoticiaVisibilidad();
        $vis = $nv->getVisibilityForNews($id);
        echo json_encode(['success'=>true,'visibilidad'=>$vis]);
        exit;
    }

    /**
     * POST (AJAX) - guarda la visibilidad (calles y/o habitantes) para una noticia
     */
    public function saveVisibilityForNews() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Método inválido']); exit; }

        // CSRF validation
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\CsrfHelper::validateToken($csrf)) {
            echo json_encode(['success'=>false,'message'=>'Token CSRF inválido']); exit;
        }

        $id_noticia = isset($_POST['id_noticia']) ? (int)$_POST['id_noticia'] : 0;
        $calles = $_POST['calles'] ?? [];
        $habitantes = $_POST['habitantes'] ?? [];
        if ($id_noticia <= 0) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }

        // Validar que las calles seleccionadas pertenezcan al líder
        $assignedIds = $this->getAssignedVeredas();
        foreach ($calles as $c) {
            if (!in_array((int)$c, $assignedIds, true)) {
                echo json_encode(['success'=>false,'message'=>'Intento de asignar vereda no autorizada']); exit;
            }
        }

        require_once __DIR__ . '/../models/NoticiaVisibilidad.php';
        $nv = new NoticiaVisibilidad();

        // Limpiar visibilidad previa
        $nv->clearVisibilityForNews($id_noticia);

        // Insertar nuevas reglas por calle
        foreach ($calles as $c) { $nv->assignVisibilityByCalle($id_noticia, (int)$c); }
        // Insertar por habitante (opcional)
        foreach ($habitantes as $h) { $nv->assignVisibilityByHabitante($id_noticia, (int)$h); }

        echo json_encode(['success'=>true]);
        exit;
    }

    /**
     * Devuelve comentarios para una noticia (AJAX) - usado por el modal en la vista
     */
    public function getCommentsByNoticia() {
        header('Content-Type: application/json');

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'ID de noticia inválido']);
            exit;
        }

        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia((int)$id, false);

        // Filtrar comentarios según veredas asignadas del subadmin
        $assigned = $this->getAssignedVeredas(); // array de id_calle
        $filtered = [];

        // Si no hay veredas asignadas, denegar (salvo admin)
        if (empty($assigned) && ($_SESSION['id_rol'] ?? 0) == 2) {
            echo json_encode(['success' => false, 'message' => 'No tienes veredas asignadas.']);
            exit;
        }

        foreach ($comentarios as $c) {
            // Si es admin (1) permitimos ver todo
            if (($_SESSION['id_rol'] ?? 0) == 1) {
                $filtered[] = $c; continue;
            }

            $id_usuario = $c['id_usuario'] ?? null;
            if (!$id_usuario) continue;

            $usuario = $this->usuarioModel->find((int)$id_usuario);
            if (!$usuario) continue;
            $persona = $this->personaModel->find($usuario['id_persona'] ?? 0);
            if (!$persona) continue;

            if (in_array((int)$persona['id_calle'], $assigned, true)) {
                $filtered[] = $c;
            }
        }

        $titulo = "";
        if (!empty($filtered)) {
            $titulo = $filtered[0]['titulo_noticia'] ?? $this->noticiaModel->getById((int)$id)['titulo'] ?? 'Noticia sin título';
        } else {
            $noticia = $this->noticiaModel->getById((int)$id);
            $titulo = $noticia['titulo'] ?? 'Noticia';
        }

        echo json_encode([
            'success' => true,
            'titulo' => $titulo,
            'comentarios' => $filtered
        ]);
        exit;
    }

    public function softDeleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID de comentario inválido.']); exit;
            }
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=subadmin/comments');
            exit();
        }

        // Verificar CSRF si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!\CsrfHelper::validateToken($csrf)) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
                }
                $_SESSION['error_message'] = 'Token CSRF inválido.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
                exit();
            }
        }

        // Verificar permiso: el comentario debe pertenecer a un usuario en las veredas asignadas
        $coment = $this->comentarioModel->getComentarioById((int)$id, false);
        $assigned = $this->getAssignedVeredas();
        $authorized = false;
        if (($_SESSION['id_rol'] ?? 0) == 1) $authorized = true; // admin
        if (!$authorized && $coment) {
            $usuario = $this->usuarioModel->find($coment['id_usuario']);
            $persona = $this->personaModel->find($usuario['id_persona'] ?? 0);
            if ($persona && in_array((int)$persona['id_calle'], $assigned, true)) $authorized = true;
        }

        if (!$authorized) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar este comentario.']); exit;
            }
            $_SESSION['error_message'] = 'No tienes permiso para eliminar este comentario.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
            exit();
        }

        $result = $this->comentarioModel->softDeleteComentario((int)$id);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$result]); exit;
        }

        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado lógicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar lógicamente el comentario.";
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
        exit();
    }

    public function activateComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID de comentario inválido.']); exit;
            }
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=subadmin/comments');
            exit();
        }

        // Permisos (mismo patrón que softDeleteComment)
        $coment = $this->comentarioModel->getComentarioById((int)$id, false);
        $assigned = $this->getAssignedVeredas();
        $authorized = false;
        if (($_SESSION['id_rol'] ?? 0) == 1) $authorized = true;
        if (!$authorized && $coment) {
            $usuario = $this->usuarioModel->find($coment['id_usuario']);
            $persona = $this->personaModel->find($usuario['id_persona'] ?? 0);
            if ($persona && in_array((int)$persona['id_calle'], $assigned, true)) $authorized = true;
        }

        if (!$authorized) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para activar este comentario.']); exit;
            }
            $_SESSION['error_message'] = 'No tienes permiso para activar este comentario.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
            exit();
        }

        $result = $this->comentarioModel->activarComentario((int)$id);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$result]); exit;
        }

        if ($result) {
            $_SESSION['success_message'] = "Comentario activado.";
        } else {
            $_SESSION['error_message'] = "Error al activar el comentario.";
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
        exit();
    }

    public function deleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID de comentario inválido.']); exit;
            }
            $_SESSION['error_message'] = "ID de comentario inválido para eliminación física.";
            header('Location: ./index.php?route=subadmin/comments');
            exit();
        }

        // Verificar CSRF si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? null;
            if (!\CsrfHelper::validateToken($csrf)) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
                }
                $_SESSION['error_message'] = 'Token CSRF inválido.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
                exit();
            }
        }

        $coment = $this->comentarioModel->getComentarioById((int)$id, false);
        $assigned = $this->getAssignedVeredas();
        $authorized = false;
        if (($_SESSION['id_rol'] ?? 0) == 1) $authorized = true;
        if (!$authorized && $coment) {
            $usuario = $this->usuarioModel->find($coment['id_usuario']);
            $persona = $this->personaModel->find($usuario['id_persona'] ?? 0);
            if ($persona && in_array((int)$persona['id_calle'], $assigned, true)) $authorized = true;
        }

        if (!$authorized) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar este comentario.']); exit;
            }
            $_SESSION['error_message'] = 'No tienes permiso para eliminar este comentario.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
            exit();
        }

        $result = $this->comentarioModel->deleteComentario((int)$id);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$result]); exit;
        }

        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado físicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar físicamente el comentario.";
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? './index.php?route=subadmin/comments'));
        exit();
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
