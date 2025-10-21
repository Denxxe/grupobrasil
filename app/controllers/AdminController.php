<?php
// grupobrasil/app/controllers/AdminController.php

// Asegúrate de que todas estas rutas de modelos son correctas
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Persona.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Calle.php';
require_once __DIR__ . '/../models/LiderCalle.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Habitante.php';
require_once __DIR__ . '/AppController.php';

class AdminController extends AppController{
    private $usuarioModel;
    private $personaModel;
    private $noticiaModel;
    private $comentarioModel;
    private $notificacionModel;
    private $categoriaModel;
    private $calleModel;
    private $liderCalleModel;
    private $roleModel;
    private $habitanteModel;

    public function __construct(
        Usuario $usuarioModel,
        Persona $personaModel,
        Noticia $noticiaModel,
        Comentario $comentarioModel,
        Notificacion $notificacionModel,
        Calle $calleModel,
        LiderCalle $liderCalleModel,
        Categoria $categoriaModel,
        Role $roleModel,
        Habitante $habitanteModel
    ) {
        $this->usuarioModel = $usuarioModel;
        $this->personaModel = $personaModel;
        $this->noticiaModel = $noticiaModel;
        $this->comentarioModel = $comentarioModel;
        $this->notificacionModel = $notificacionModel;
        $this->calleModel = $calleModel;
        $this->liderCalleModel = $liderCalleModel;
        $this->categoriaModel = $categoriaModel;
        $this->roleModel = $roleModel;
        $this->habitanteModel = $habitanteModel;

        // Lógica de seguridad y redirección (asume que rol 1 es Administrador)
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
            $_SESSION['error_message'] = "Acceso no autorizado. Debe ser administrador.";
            header('Location: ./index.php?route=login');
            exit();
        }
    }

    private function renderAdminView($viewPath, $data = []) {
        extract($data);
        $title = $data['title'] ?? 'Panel de Administración';
        $page_title = $data['page_title'] ?? 'Dashboard de Administración';
        $content_view = __DIR__ . '/../views/admin/' . $viewPath . '.php';

        if (!file_exists($content_view)) {
            http_response_code(500);
            echo "Error: La vista '" . htmlspecialchars($viewPath) . "' no se encontró en " . htmlspecialchars($content_view) . ".";
            exit();
        }

        include_once __DIR__ . '/../views/layouts/admin_layout.php';
    }

    public function dashboard() {
        $totalUsuarios = $this->usuarioModel->getTotalUsuarios();
        $totalNoticias = $this->noticiaModel->getTotalNoticias();

        $data = [
            'title' => 'Dashboard',
            'page_title' => 'Dashboard de Administración',
            'totalUsuarios' => $totalUsuarios,
            'totalNoticias' => $totalNoticias,
        ];
        $this->renderAdminView('dashboard', $data);
    }

    // -------------------------------------------------------------------------
    // GESTIÓN DE HABITANTES (PERSONAS)
    // -------------------------------------------------------------------------
    public function personas()
    {
        // 1. Obtener parámetros de búsqueda y filtro de la URL
        $search = $_GET['search'] ?? '';
        $activo = $_GET['activo'] ?? 'all';
        $es_usuario = $_GET['es_usuario'] ?? 'all';

        // 2. Preparar los filtros para el Modelo
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($activo !== 'all' && is_numeric($activo)) {
            $filters['activo'] = (int)$activo;
        }
        if ($es_usuario !== 'all' && is_numeric($es_usuario)) {
            $filters['es_usuario'] = (int)$es_usuario;
        }

        // 3. Obtener datos del Modelo Persona
        $personas = $this->personaModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Habitantes (Personas)',
            'personas' => $personas,
            'current_search' => $search,
            'current_activo' => $activo,
            'current_es_usuario' => $es_usuario,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null
        ];

        // Limpiar mensajes de sesión
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 5. Renderizar la vista
        $this->renderAdminView('users/personas', $data);
    }

    /**
     * Muestra el formulario de edición de un habitante
     */
    public function editHabitante() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId)) {
            $_SESSION['error_message'] = "ID de persona no especificado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Obtener datos de la persona
        $persona = $this->personaModel->getById($personId);

        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Obtener todas las calles para el selector
        $calles = $this->calleModel->findAll();

        $data = [
            'page_title' => 'Editar Habitante',
            'persona' => $persona,
            'calles' => $calles,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        unset($_SESSION['success_message'], $_SESSION['error_message']);

        $this->renderAdminView('users/edit_habitante', $data);
    }

    /**
     * Procesa la actualización de un habitante
     */
    public function updateHabitante() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $personId = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
        $errors = [];

        if (empty($personId)) {
            $_SESSION['error_message'] = "Error: ID de persona no recibido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // Validar campos
        $data = $_POST;
        if (Validator::isEmpty($data['cedula'] ?? '')) $errors[] = "La cédula es obligatoria.";
        if (Validator::isEmpty($data['nombres'] ?? '')) $errors[] = "El nombre es obligatorio.";
        if (Validator::isEmpty($data['apellidos'] ?? '')) $errors[] = "El apellido es obligatorio.";
        if (empty($data['id_calle'] ?? '')) $errors[] = "Debe seleccionar una vereda de residencia.";

        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            header('Location: ./index.php?route=admin/users/edit-habitante&person_id=' . $personId);
            return;
        }

        // Preparar datos para actualización
        $personaData = [
            'cedula' => trim($data['cedula'] ?? ''),
            'nombres' => trim($data['nombres'] ?? ''),
            'apellidos' => trim($data['apellidos'] ?? ''),
            'telefono' => trim($data['telefono'] ?? null),
            'id_calle' => (int)($data['id_calle'] ?? null),
            'numero_casa' => trim($data['numero_casa'] ?? null),
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'direccion' => trim($data['direccion'] ?? null),
            'correo' => trim($data['correo'] ?? null),
        ];

        $result = $this->personaModel->update($personId, $personaData);

        if ($result) {
            $_SESSION['success_message'] = "Habitante actualizado exitosamente.";
            header('Location: ./index.php?route=admin/users/personas');
        } else {
            $_SESSION['error_message'] = "Error al actualizar el habitante.";
            header('Location: ./index.php?route=admin/users/edit-habitante&person_id=' . $personId);
        }
    }

    /**
     * Elimina un habitante y todos sus registros relacionados en cascada
     * Si es líder, también elimina su cuenta de usuario
     * Si es jefe de familia, la familia se mantiene pero sin jefe
     */
    public function deleteHabitante() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId) || !is_numeric($personId)) {
            $_SESSION['error_message'] = "ID de persona inválido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        error_log("[v0] deleteHabitante called for person ID: $personId");

        // Obtener datos de la persona para el mensaje
        $persona = $this->personaModel->getById($personId);
        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $nombreCompleto = $persona['nombres'] . ' ' . $persona['apellidos'];

        // Verificar si existe un habitante para esta persona
        $habitante = $this->habitanteModel->findByPersonaId($personId);

        if ($habitante) {
            $habitanteId = $habitante['id_habitante'];
            error_log("[v0] Found habitante ID: $habitanteId for person ID: $personId");

            // Usar el método de eliminación en cascada
            $success = $this->habitanteModel->deleteHabitanteWithCascade($habitanteId);

            if ($success) {
                // También eliminar la persona
                $this->personaModel->delete($personId);
                $_SESSION['success_message'] = "El habitante '$nombreCompleto' y todos sus registros relacionados han sido eliminados exitosamente.";
            } else {
                $_SESSION['error_message'] = "Error al eliminar el habitante '$nombreCompleto'.";
            }
        } else {
            // Si no hay habitante, solo eliminar la persona
            error_log("[v0] No habitante found, deleting only persona");
            $success = $this->personaModel->delete($personId);

            if ($success) {
                $_SESSION['success_message'] = "La persona '$nombreCompleto' ha sido eliminada exitosamente.";
            } else {
                $_SESSION['error_message'] = "Error al eliminar la persona '$nombreCompleto'.";
            }
        }

        header('Location: ./index.php?route=admin/users/personas');
    }


    // -------------------------------------------------------------------------
    // GESTIÓN DE USUARIOS CON ACCESO (Líderes)
    // -------------------------------------------------------------------------
    public function usuarios()
    {
        // 1. Obtener parámetros de búsqueda y filtro de la URL
        $search = $_GET['search'] ?? '';
        $rol = $_GET['rol'] ?? 'all';
        $activo = $_GET['activo'] ?? 'all';

        // 2. Preparar los filtros para el Modelo Usuario
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if ($rol !== 'all') {
            $filters['id_rol'] = (int)$rol;
        }
        if ($activo !== 'all') {
            $filters['activo'] = (int)$activo;
        }

        // 3. Obtener datos del Modelo Usuario
        $usuarios = $this->usuarioModel->getAllFiltered($filters);

        // 4. Preparar los datos para la vista
        $data = [
            'page_title' => 'Gestión de Usuarios (Líderes)',
            'usuarios' => $usuarios,
            'current_search' => $search,
            'current_rol' => $rol,
            'current_activo' => $activo,
        ];

        // 5. Renderizar la vista específica de usuarios
        $this->renderAdminView('users/usuarios', $data);
    }

    public function createUser() {
        // 1. Obtener todas las calles para el desplegable (combobox)
        $calles = $this->calleModel->findAll();

        // 2. Preparar los datos para la vista
        $data = [
            'page_title' => 'Crear Nuevo Habitante',
            'calles' => $calles,
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        // Limpiar mensajes de sesión
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 3. Renderizar la vista
        $this->renderAdminView('users/create', $data);
    }

    /**
     * CORREGIDO: Crea un habitante y, opcionalmente, crea su cuenta de líder (Familia o Vereda) y asigna calles.
     */
    public function storeUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        $data = $_POST;
        $errors = [];

        // 1. Validar campos de Persona/Habitante
        if (Validator::isEmpty($data['cedula'] ?? '')) $errors[] = "La cédula es obligatoria.";
        if (Validator::isEmpty($data['nombres'] ?? '')) $errors[] = "El nombre es obligatorio.";
        if (Validator::isEmpty($data['apellidos'] ?? '')) $errors[] = "El apellido es obligatorio.";
        if (empty($data['id_calle'] ?? '')) $errors[] = "Debe seleccionar una vereda de residencia.";

        // 2. Validar campos de Liderazgo/Usuario (si se marcó la opción)
        $createUserAccount = isset($data['create_user_account']) && $data['create_user_account'] == 1;
        if ($createUserAccount) {
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';
            $rolLiderId = $data['id_rol_lider'] ?? null;
            $callesDirigidas = $data['calles_liderazgo'] ?? [];

            if (!Validator::isValidEmail($email)) $errors[] = "El email es inválido.";
            if ($this->usuarioModel->buscarPorEmail($email)) $errors[] = "El email ya está registrado.";
            if (!Validator::isValidPassword($password)) $errors[] = "La contraseña debe tener 8+ caracteres, incluyendo mayúsculas, minúsculas, números y un símbolo.";
            if ($password !== $confirmPassword) $errors[] = "Las contraseñas no coinciden.";
            if (!in_array($rolLiderId, [2, 3])) $errors[] = "Debe seleccionar un rol de liderazgo válido (Familia o Vereda).";

            // Si es Líder de Vereda, debe seleccionar al menos una calle
            if ((int)$rolLiderId === 2 && empty($callesDirigidas)) {
                // Esto puede ser una advertencia o un error, lo dejamos como advertencia si es una funcionalidad flexible.
                // $errors[] = "Debe seleccionar al menos una calle para un Líder de Vereda.";
            }
        }

        // Si hay errores, redirigir con mensajes
        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            // Opcional: guardar $data en sesión para rellenar el formulario
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        // 3. CREAR LA PERSONA/HABITANTE
        $personaData = [
            'cedula' => trim($data['cedula'] ?? ''),
            'nombres' => trim($data['nombres'] ?? ''),
            'apellidos' => trim($data['apellidos'] ?? ''),
            'telefono' => trim($data['telefono'] ?? null),
            'id_calle' => (int)($data['id_calle'] ?? null),
            'estado' => 'Residente',
            'activo' => 1,
            // Campos adicionales del formulario que no estaban mapeados:
            'numero_casa' => trim($data['numero_casa'] ?? null),
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'direccion' => trim($data['direccion'] ?? null),
            'correo' => trim($data['correo'] ?? null), // Se podría usar el email de usuario aquí, si aplica
        ];

        $personaId = $this->personaModel->create($personaData);

        if (!$personaId) {
            $_SESSION['error_message'] = "Error grave al guardar el habitante (Cédula duplicada o DB error).";
            header('Location: ./index.php?route=admin/users/create');
            return;
        }

        $successMessage = "Habitante creado exitosamente. ID: " . $personaId;

        // 4. CREAR USUARIO Y ASIGNAR ROL DE LÍDER (Si aplica)
        if ($createUserAccount) {
            $usuarioData = [
                'id_persona' => $personaId,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id_rol' => (int)$rolLiderId, // Rol 2 (Vereda) o 3 (Familia)
                'activo' => 1,
                // Si el modelo lo requiere, podrías inicializar otros campos como 'requires_setup' = 0
            ];

            $userId = $this->usuarioModel->create($usuarioData);

            if ($userId) {
                $successMessage .= " | Cuenta de Líder (Rol ID: {$rolLiderId}) creada exitosamente.";

                // 5. ASIGNAR LIDERAZGOS DE CALLE (Solo si es Líder de Vereda)
                if ((int)$rolLiderId === 2 && !empty($callesDirigidas)) {
                    foreach ($callesDirigidas as $calleId) {
                        $this->liderCalleModel->create(['id_usuario' => $userId, 'id_calle' => (int)$calleId]);
                    }
                    $successMessage .= " | Asignado a " . count($callesDirigidas) . " vereda(s).";
                } elseif ((int)$rolLiderId === 2 && empty($callesDirigidas)) {
                     $successMessage .= " | Advertencia: Rol Líder de Vereda asignado, pero sin calles seleccionadas.";
                }

            } else {
                // Esto podría ser causado por un email duplicado
                $successMessage .= " | ERROR: No se pudo crear la cuenta de usuario (Email ya existe o DB error).";
                $_SESSION['error_message'] = "Error al crear la cuenta de usuario, el habitante fue creado. El email podría estar duplicado.";
            }
        }

        $_SESSION['success_message'] = $successMessage;
        header('Location: ./index.php?route=admin/users/personas');
        return;
    }

    public function editUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }
        $user = $this->usuarioModel->getById($id);

        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        // CORRECCIÓN: Usar el modelo de roles para obtener los roles disponibles
        $roles = $this->roleModel->findAll();

        $errors = $_SESSION['form_errors'] ?? [];
        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_form_data']);

        $user_data_to_display = !empty($old_data) ? array_merge($user, $old_data) : $user;

        $data = [
            'title' => 'Editar Usuario',
            'page_title' => 'Editar Usuario',
            'user' => $user_data_to_display,
            'roles' => $roles,
            'errors' => $errors,
        ];

        $this->renderAdminView('users/edit', $data);
    }

    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_usuario) || $id_usuario <= 0) {
            $_SESSION['error_message'] = "Error: ID de usuario inválido en el formulario.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $current_user_data = $this->usuarioModel->getById($id_usuario);
        if (!$current_user_data) {
            $_SESSION['error_message'] = "Usuario a actualizar no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }
        $current_persona_id = $current_user_data['id_persona'] ?? null;

        $data = [
            'ci_usuario' => filter_input(INPUT_POST, 'ci_usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_nacimiento' => filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'id_rol' => (int)filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'biografia' => filter_input(INPUT_POST, 'biografia', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $errors = [];

        if (Validator::isEmpty($data['ci_usuario'])) {
            $errors[] = "La cédula es obligatoria.";
        } else if (!Validator::isValidCI($data['ci_usuario'])) {
            $errors[] = "Formato de cédula inválido. (Ej: V-12345678, E-87654321)";
        } else {
            // Verificar si la CI ya existe en otra persona
            $existingPersonaByCI = $this->personaModel->buscarPorCI($data['ci_usuario']);

            if ($existingPersonaByCI) {
                // Asume que la tabla 'usuario' debe tener un JOIN con 'persona' para obtener el id_persona
                $user_persona = $this->usuarioModel->getPersonData($id_usuario);
                $current_persona_id_db = $user_persona['id_persona'] ?? null;

                if ($current_persona_id_db === null || $existingPersonaByCI['id_persona'] != $current_persona_id_db) {
                    $errors[] = "La cédula ya está registrada por otra persona/usuario.";
                }
            }
        }

        if (Validator::isEmpty($data['nombre'])) { $errors[] = "El nombre es obligatorio."; }
        if (Validator::isEmpty($data['apellido'])) { $errors[] = "El apellido es obligatorio."; }

        if (Validator::isEmpty($data['email'])) {
            $errors[] = "El email es obligatorio.";
        } else if (!Validator::isValidEmail($data['email'])) {
            $errors[] = "El email no es válido.";
        } else {
            $existingUserByEmail = $this->usuarioModel->buscarPorEmail($data['email']);
            if ($existingUserByEmail && $existingUserByEmail['id_usuario'] != $id_usuario) {
                $errors[] = "El email ya está registrado por otro usuario.";
            }
        }

        // Asume que los roles son 1, 2, 3. Revisa si $this->roleModel->findAll() te da los IDs correctos.
        if (!in_array($data['id_rol'], [1, 2, 3])) { $errors[] = "Debe seleccionar un rol válido."; }

        if (!Validator::isEmpty($data['fecha_nacimiento']) && !Validator::isValidDate($data['fecha_nacimiento'])) {
            $errors[] = "Formato de fecha de nacimiento inválido (YYYY-MM-DD).";
        }

        $new_password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        if (!empty($new_password)) {
            if (!Validator::isValidPassword($new_password)) {
                $errors[] = "La nueva contraseña no cumple los requisitos (mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un símbolo).";
            } else {
                $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        } else {
            unset($data['password']);
        }

        $upload_dir = __DIR__ . '/../../public/uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $data['foto_perfil'] = $current_user_data['foto_perfil'] ?? null;

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('profile_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
                $errors[] = 'Solo se permiten imágenes JPG, PNG y GIF para la foto de perfil.';
            } elseif ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'La foto de perfil es demasiado grande. Máximo 2MB.';
            } elseif (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                if ($current_user_data['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user_data['foto_perfil'])) {
                    unlink(__DIR__ . '/../../public' . $current_user_data['foto_perfil']);
                }
                $data['foto_perfil'] = './uploads/profile_pictures/' . $file_name;
            } else {
                $errors[] = 'Error al subir la nueva foto de perfil. Código: ' . $_FILES['foto_perfil']['error'];
            }
        } elseif (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
            if ($current_user_data['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user_data['foto_perfil'])) {
                unlink(__DIR__ . '/../../public' . $current_user_data['foto_perfil']);
            }
            $data['foto_perfil'] = null;
        }

        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            $_SESSION['old_form_data'] = $_POST;
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil'];
            header('Location: ./index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }

        // Separar data de usuario y data de persona para actualización
        $personaUpdateData = [
            'cedula' => $data['ci_usuario'],
            'nombres' => $data['nombre'],
            'apellidos' => $data['apellido'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'direccion' => $data['direccion'],
            'telefono' => $data['telefono'],
            // Asegúrate de que tu modelo Persona tiene un método updateByUserId o un método updateByPersonId
        ];

        // Asumiendo que getPersonData() devuelve el id_persona:
        $current_persona_data = $this->usuarioModel->getPersonData($id_usuario);
        $personaUpdateResult = $this->personaModel->update($current_persona_data['id_persona'], $personaUpdateData);


        // Datos para actualizar en la tabla usuario
        $usuarioUpdateData = [
            'email' => $data['email'],
            'id_rol' => $data['id_rol'],
            'activo' => $data['activo'],
            'biografia' => $data['biografia'],
            'foto_perfil' => $data['foto_perfil']
        ];

        if (isset($data['password'])) {
            $usuarioUpdateData['password'] = $data['password'];
        }

        $result = $this->usuarioModel->update($id_usuario, $usuarioUpdateData);

        if ($result || $personaUpdateResult) { // Si al menos una de las actualizaciones fue exitosa
            $_SESSION['success_message'] = "Usuario y Persona actualizados exitosamente.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        } else {
            $_SESSION['error_message'] = "Error al actualizar el usuario o la persona en la base de datos.";
            $_SESSION['old_form_data'] = $_POST;
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil'];
            header('Location: ./index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }
    }

    public function deleteUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido o no proporcionado para la eliminación.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        // CORRECCIÓN: Usar la variable de sesión correcta para el ID del usuario logueado
        if (isset($_SESSION['id_usuario']) && $id == $_SESSION['id_usuario']) {
            $_SESSION['error_message'] = "No puedes eliminar tu propia cuenta de administrador.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        $userToDelete = $this->usuarioModel->getById($id);
        if (!$userToDelete) {
            $_SESSION['error_message'] = "Usuario a eliminar no encontrado.";
            header('Location: ./index.php?route=admin/users/usuarios');
            exit();
        }

        if ($userToDelete['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
            if (!unlink(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
                error_log("Error al eliminar el archivo de foto de perfil: " . __DIR__ . '/../../public' . $userToDelete['foto_perfil']);
            }
        }

        // Opcional: Eliminar las asignaciones de calle antes de eliminar el usuario
        $this->liderCalleModel->deleteByHabitanteId($id);

        $success = $this->usuarioModel->delete($id);

        if ($success) {
            // Se asume que al eliminar el usuario, se debe eliminar o desactivar la persona.
            // Si tu DB usa FOREIGN KEY ON DELETE CASCADE, esto se maneja automáticamente.
            $_SESSION['success_message'] = "El usuario '" . htmlspecialchars($userToDelete['nombres'] . ' ' . $userToDelete['apellidos']) . "' ha sido eliminado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Hubo un error al intentar eliminar el usuario '" . htmlspecialchars($userToDelete['nombres'] . ' ' . $userToDelete['apellidos']) . "'.";
        }

        header('Location: ./index.php?route=admin/users/usuarios');
        exit();
    }

    public function createUserRole() {
        $personId = $_GET['person_id'] ?? null;

        if (empty($personId)) {
            $_SESSION['error_message'] = "ID de persona no especificado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 1. Obtener datos del Habitante (Persona)
        $persona = $this->personaModel->getById($personId);

        if (!$persona) {
            $_SESSION['error_message'] = "Habitante no encontrado.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 2. Obtener cuenta de Usuario existente
        $usuarioExistente = $this->usuarioModel->findByPersonId($personId);
        $callesDirigidas = [];

        // 3. Obtener ID de habitante y Calles dirigidas si existe el usuario y es líder de vereda (rol 2)
        $habitante = $this->habitanteModel->findByPersonaId($personId);
        $habitanteId = $habitante ? $habitante['id_habitante'] : null;

        if ($usuarioExistente && (int)$usuarioExistente['id_rol'] === 2 && $habitanteId) {
             // Asume que este método existe y retorna un array de IDs de calle.
             // Call getCallesIdsByHabitanteId on liderCalleModel
             $callesDirigidas = $this->liderCalleModel->getCallesIdsByHabitanteId($habitanteId);
        }

        // LÓGICA DE RESTRICCIÓN DEL SUPERADMIN
        if ($usuarioExistente && (int)$usuarioExistente['id_rol'] === 1) {
            $_SESSION['error_message'] = "No se permite modificar los roles del Administrador Principal desde esta interfaz.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 4. Obtener datos para la vista
        $calles = $this->calleModel->findAll();

        $data = [
            'page_title' => 'Asignar Usuario y Roles de Liderazgo',
            'persona' => $persona,
            'usuario' => $usuarioExistente,
            'calles' => $calles,
            'calles_dirigidas' => $callesDirigidas, // Se agrega para preselección en la vista
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null,
        ];

        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // 5. Renderizar la vista
        $this->renderAdminView('users/create_user_role', $data);
    }

    /**
     * CORREGIDO: Procesa la asignación/modificación de roles de líder.
     * // Updated to work with habitante table structure
     */
    public function storeUserRole() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 1. CAPTURAR Y SANEAMIENTO DE DATOS POST
        $personId = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
        $isLiderVereda = isset($_POST['is_lider_vereda']);
        $isLiderFamilia = isset($_POST['is_lider_familia']); // Se usa solo para definir el rol principal si no es Vereda
        $userExists = isset($_POST['user_exists']);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $callesDirigidas = $_POST['calles_liderazgo'] ?? [];

        error_log("[v0] storeUserRole called - personId: $personId, isLiderVereda: " . ($isLiderVereda ? 'true' : 'false') . ", isLiderFamilia: " . ($isLiderFamilia ? 'true' : 'false'));

        if (empty($personId)) {
            $_SESSION['error_message'] = "Error de validación: ID de persona no recibido.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        $persona = $this->personaModel->getById($personId);
        if (!$persona) {
            $_SESSION['error_message'] = "Error: Persona no encontrada.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }

        // 2. CREAR O OBTENER HABITANTE (requerido para la tabla lider_calle)
        $habitanteId = $this->habitanteModel->createFromPersona($personId);
        if (!$habitanteId) {
            $_SESSION['error_message'] = "Error al crear/obtener el registro de habitante.";
            header('Location: ./index.php?route=admin/users/personas');
            return;
        }
        error_log("[v0] Habitante ID: $habitanteId");


        // 3. DETERMINAR EL ROL PRINCIPAL (Rol 2 tiene prioridad sobre Rol 3)
        $rolPrincipal = null;
        if ($isLiderVereda) {
            $rolPrincipal = 2; // Líder de Vereda
        } elseif ($isLiderFamilia) {
            $rolPrincipal = 3; // Líder de Familia / Miembro
        } else {
             // Si desmarcan ambos, el rol por defecto para un usuario existente debería ser el más bajo, por ejemplo '3'.
             // Si no tienen cuenta, no hay rol.
        }

        error_log("[v0] Determined rolPrincipal: " . ($rolPrincipal ?? 'null'));

        // 4. CREACIÓN / ACTUALIZACIÓN DE CUENTA DE USUARIO
        $usuarioExistente = $this->usuarioModel->findByPersonId($personId);
        $usuarioId = null;
        $successMessage = '';

        error_log("[v0] Usuario existente: " . ($usuarioExistente ? 'yes (id: ' . $usuarioExistente['id_usuario'] . ')' : 'no'));

        if ($usuarioExistente) {
            // A) ACTUALIZAR ROL
            $usuarioId = $usuarioExistente['id_usuario'];
            if ($rolPrincipal !== null && (int)$usuarioExistente['id_rol'] !== $rolPrincipal) {
                 $updateResult = $this->usuarioModel->update($usuarioId, ['id_rol' => $rolPrincipal]);
                 if (!$updateResult) {
                     $_SESSION['error_message'] = "Error al actualizar el rol del usuario existente.";
                     header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                     return;
                 }
                 error_log("[v0] Updated existing user role to: $rolPrincipal");
            }
            $successMessage = "Rol actualizado exitosamente.";

        } elseif ($rolPrincipal !== null) {
            // B) CREAR NUEVA CUENTA (si se marcó algún rol, se requieren credenciales)
            if (empty($email) || empty($password) || $password !== $confirmPassword || !Validator::isValidEmail($email)) {
                $_SESSION['error_message'] = "Error de validación: Se requiere Email válido y contraseñas coincidentes para crear la cuenta de líder.";
                header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                return;
            }

            $usuarioData = [
                'id_persona' => $personId,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id_rol' => $rolPrincipal,
                'activo' => 1,
            ];

            error_log("[v0] Creating new user with data: " . json_encode(['id_persona' => $personId, 'email' => $email, 'id_rol' => $rolPrincipal]));

            $usuarioId = $this->usuarioModel->createUserOnly($usuarioData);

            if (!$usuarioId) {
                error_log("[v0] Failed to create user - checking for duplicate email");
                $_SESSION['error_message'] = "Error al crear las credenciales de usuario (Email duplicado?).";
                header('Location: ./index.php?route=admin/users/create-user-role&person_id=' . $personId);
                return;
            }
            error_log("[v0] Successfully created user with ID: $usuarioId");
            $successMessage = "Cuenta de usuario y Rol asignados exitosamente.";
        }


        // 5. ASIGNAR/LIMPIAR LIDERAZGOS DE CALLE (usando habitanteId)
        if ($habitanteId && $rolPrincipal !== null) {
            error_log("[v0] Processing calle assignments for habitante ID: $habitanteId");
            // Call deleteByHabitanteId on liderCalleModel
            $this->liderCalleModel->deleteByHabitanteId($habitanteId);

            if ($rolPrincipal === 2 && !empty($callesDirigidas)) { // Líder de Vereda
                error_log("[v0] Assigning " . count($callesDirigidas) . " calles to habitante");
                foreach ($callesDirigidas as $calleId) {
                    // Use id_habitante in lider_calle creation
                    $result = $this->liderCalleModel->create(['id_habitante' => $habitanteId, 'id_calle' => (int)$calleId]);
                    error_log("[v0] Assigned calle $calleId: " . ($result ? 'success' : 'failed'));
                }
                $successMessage .= " Y calles asignadas.";
            }
        }

        error_log("[v0] storeUserRole completed successfully");
        $_SESSION['success_message'] = "{$successMessage} para {$persona['nombres']} {$persona['apellidos']}.";
        header('Location: ./index.php?route=admin/users/personas');
        return;
    }

  public function manageNews() {
    $noticias = $this->noticiaModel->getAllNews(false, ['column' => 'fecha_publicacion', 'direction' => 'DESC']);

    // Simplificación y estandarización de mensajes de sesión
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);

    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['error_message']);

    $data = [
        'title' => 'Gestión de Noticias',
        'page_title' => 'Gestión de Noticias',
        'noticias' => $noticias,
        'success_message' => $success_message,
        'error_message' => $error_message
    ];

    $this->renderAdminView('news/index', $data);
}

//---

public function createNews() {
    $old_data = $_SESSION['old_form_data'] ?? [];
    unset($_SESSION['old_form_data']);

    // CORRECCIÓN: Estandarizar la variable de errores a 'errors'
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    $categorias = $this->categoriaModel->getAllCategories();

    $data = [
        'title' => 'Crear Nueva Noticia',
        'page_title' => 'Crear Noticia',
        'errors' => $errors,
        'old_data' => $old_data,
        'categorias' => $categorias
    ];

    $this->renderAdminView('news/create', $data);
}

//---

public function storeNews() {
    // 1. Verificación del método y preparación de constantes
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }

    // Define la carpeta de subida
    // CORRECCIÓN DE RUTA: Asume que el directorio 'public' está dos niveles por encima del controlador
    $base_public_path = realpath(__DIR__ . '/../../../public');
    $upload_dir = $base_public_path . '/uploads/noticias/';
    $errors = [];

    // 2. Recolección, Saneamiento y Validación
    $data = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'contenido' => trim($_POST['contenido'] ?? ''),
        'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
        // El modelo espera 'estado' o maneja 'activo'. Usamos 'activo' y permitimos que el modelo lo traduzca.
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];

    // CRÍTICO: Inyectar el id_usuario. El modelo espera 'id_usuario'.
    // Asume que la sesión del usuario está en $_SESSION['id_usuario']
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    if (empty($id_usuario)) {
        $errors['auth'] = 'Debe iniciar sesión para publicar una noticia. ID de usuario no encontrado.';
    }
    $data['id_usuario'] = $id_usuario; // Inyectar al array de datos para el modelo

    if (empty($data['titulo'])) {
        $errors['titulo'] = 'El título es obligatorio.';
    }
    if (empty($data['contenido'])) {
        $errors['contenido'] = 'El contenido es obligatorio.';
    }
    if ($data['id_categoria'] === false || $data['id_categoria'] === null) {
        $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
    }

    // 3. Manejo de Subida de Archivos
    $imagen_principal_path = null;
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {

        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors['imagen_principal'] = 'Error al crear el directorio de subida. (Revisar permisos)';
            }
        }

        if (empty($errors['imagen_principal'])) {
            $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('news_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Verificación MIME Type (más seguro)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors['imagen_principal'] = 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.';
            } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
            } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                // Ruta relativa a la carpeta 'public' para guardar en la BD
                $imagen_principal_path = 'uploads/noticias/' . $file_name;
            } else {
                $errors['imagen_principal'] = 'Error desconocido al mover la imagen. (Revisar permisos)';
            }
        }
    } elseif (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['imagen_principal'] = 'Error en la subida del archivo: Código ' . $_FILES['imagen_principal']['error'];
    }

    // Agregar la ruta de la imagen a los datos de la noticia
    $data['imagen_principal'] = $imagen_principal_path;

    // 4. Manejo de Errores y Redirección
    if (!empty($errors)) {
        $_SESSION['old_form_data'] = $_POST;
        $_SESSION['errors'] = $errors; // CORRECCIÓN: Estandarizado a 'errors'
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }

    // 5. Inserción en el Modelo
    $new_news_id = $this->noticiaModel->createNews($data); // Usamos $data directamente

    if ($new_news_id) {
        $_SESSION['success_message'] = "Noticia creada exitosamente con ID: " . $new_news_id;
        header('Location: ./index.php?route=admin/news');
        exit();
    } else {
        // Fallo de base de datos o fallo de validación NOT NULL en el modelo
        $_SESSION['errors'] = ['Error al crear la noticia en la base de datos. Consulta los logs para más detalles.'];
        $_SESSION['old_form_data'] = $_POST;
        header('Location: ./index.php?route=admin/news/create');
        exit();
    }
}

//---

public function editNews($id) {
    if (!is_numeric($id) || $id <= 0) {
        $_SESSION['error_message'] = "ID de noticia inválido para edición.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Obtener la noticia sin el filtro 'activo' para poder editar borradores
    $news = $this->noticiaModel->getNewsById($id, false);

    if (!$news) {
        $_SESSION['error_message'] = "Noticia no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    $old_data = $_SESSION['old_form_data'] ?? [];
    unset($_SESSION['old_form_data']);

    // CORRECCIÓN: Estandarizar la variable de errores a 'errors'
    $errors = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);

    // Combinar datos existentes con datos antiguos si falló la última actualización
    $news_data_for_form = array_merge($news, $old_data);

    $categorias = $this->categoriaModel->getAllCategories();

    $data = [
        'title' => 'Editar Noticia',
        'page_title' => 'Editar Noticia',
        'news' => $news_data_for_form,
        'errors' => $errors,
        'categorias' => $categorias
    ];

    $this->renderAdminView('news/edit', $data);
}

//---

public function updateNews() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Define la carpeta de subida
    $base_public_path = realpath(__DIR__ . '/../../../public');
    $upload_dir = $base_public_path . '/uploads/noticias/';
    $errors = [];

    $id_noticia = filter_input(INPUT_POST, 'id_noticia', FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($id_noticia) || $id_noticia <= 0) {
        $_SESSION['error_message'] = "Error: ID de noticia inválido en el formulario de actualización.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Obtener noticia actual para tener la imagen original y actualizar el registro
    // Asumo que 'noticiaModel' ya está instanciado en el constructor del controlador
    $current_news = $this->noticiaModel->getNewsById($id_noticia, false);
    if (!$current_news) {
        $_SESSION['error_message'] = "Noticia a actualizar no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Convertir 'activo' (checkbox) a 'estado' (publicado/borrador)
    $estado_post = (isset($_POST['estado']) && $_POST['estado'] === 'publicado') ? 'publicado' : 'borrador';

    // Inicializar datos con valores actuales y sobreescribir con POST
    $data_to_update = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'contenido' => trim($_POST['contenido'] ?? ''),
        'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
        // CRÍTICO: Usamos 'estado' en lugar de 'activo' para que coincida con el modelo y la vista de edición
        'estado' => $estado_post,
        // Mantener la imagen actual por defecto (se sobreescribirá más abajo)
        'imagen_principal' => $current_news['imagen_principal']
    ];

    // Usar el ID de usuario original de la noticia, ya que solo estamos actualizando contenido.
    // Si quisieras que el usuario logueado fuera el que "modificó" la noticia, deberías añadir
    // una columna 'id_usuario_modificacion' o algo similar. Por ahora, conservamos el ID original.
    $data_to_update['id_usuario'] = $current_news['id_usuario'];


    // Validaciones
    if (empty($data_to_update['titulo'])) {
        $errors['titulo'] = 'El título de la noticia es obligatorio.';
    }
    if (empty($data_to_update['contenido'])) {
        $errors['contenido'] = 'El contenido de la noticia es obligatorio.';
    }
    // CRÍTICO: Validar id_usuario (aunque conservamos el original, es bueno verificarlo)
    if (empty($data_to_update['id_usuario'])) {
        $errors['id_usuario'] = 'No se pudo determinar el usuario publicador de la noticia original.';
    }
    if ($data_to_update['id_categoria'] === false || $data_to_update['id_categoria'] === null) {
        $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
    }

    // 6. Manejo de Subida/Actualización de Imagen (Misma lógica, sin cambios esenciales)
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
        }
    }

    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
        // Lógica de subida y validación de la nueva imagen
        $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('news_') . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $errors['imagen_principal'] = 'Tipo de archivo no permitido.';
        } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) {
            $errors['imagen_principal'] = 'La imagen es demasiado grande.';
        } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
            // Eliminar imagen antigua
            $old_image_path = $base_public_path . '/' . $current_news['imagen_principal'];
            if (!empty($current_news['imagen_principal']) && file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
            // Asignar nueva ruta relativa
            $data_to_update['imagen_principal'] = 'uploads/noticias/' . $file_name;
        } else {
            $errors['imagen_principal'] = 'Error al subir la nueva imagen.';
        }
    } elseif (isset($_POST['remove_imagen_principal']) && $_POST['remove_imagen_principal'] === '1') {
        // Lógica para eliminar la imagen
        $old_image_path = $base_public_path . '/' . $current_news['imagen_principal'];
        if (!empty($current_news['imagen_principal']) && file_exists($old_image_path)) {
            @unlink($old_image_path);
        }
        $data_to_update['imagen_principal'] = null;
    }
    // Si no se subió una nueva imagen ni se eliminó, $data_to_update['imagen_principal'] conserva la ruta original (valor de $current_news['imagen_principal']).


    // 7. Manejo de Errores y Redirección
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_form_data'] = $data_to_update; // Enviar todos los datos de vuelta
        header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
        exit();
    }

    // 8. Actualización (PUNTO CRÍTICO CORREGIDO)
    // El método updateNews devuelve TRUE si se afectó al menos una fila, y FALSE si hubo un error O no se afectaron filas.
    $result = $this->noticiaModel->updateNews($id_noticia, $data_to_update);

    // LÓGICA CORREGIDA: Si $result es TRUE (se actualizó) O si la noticia actual es igual
    // a los datos enviados (el modelo devuelve false porque 0 filas fueron afectadas).
    // Nota: El modelo devuelve true si affected_rows > 0.

    if ($result || $this->noticiaModel->checkIfDataMatches($id_noticia, $data_to_update)) {
        // Nota: Si updateNews devuelve FALSE y no hubo error de BD, significa que no hubo cambios.
        // En este caso, asumimos éxito.

        $_SESSION['success_message'] = "Noticia actualizada exitosamente.";
        header('Location: ./index.php?route=admin/news');
        exit();
    } else {
        // Si $result es FALSE y no fue por 0 filas afectadas, entonces hubo un error real de DB.
        $_SESSION['errors'] = ['Error grave al actualizar la noticia en la base de datos. Por favor, inténtelo de nuevo.'];
        $_SESSION['old_form_data'] = $data_to_update;
        header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
        exit();
    }
}

//---

public function deleteNews($id) {
    if (!is_numeric($id) || $id <= 0) {
        $_SESSION['error_message'] = "ID de noticia inválido o no proporcionado para la eliminación.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    $newsToDelete = $this->noticiaModel->getNewsById($id, false);
    if (!$newsToDelete) {
        $_SESSION['error_message'] = "Noticia a eliminar no encontrada.";
        header('Location: ./index.php?route=admin/news');
        exit();
    }

    // Define la ruta base para eliminar archivos
    $base_public_path = realpath(__DIR__ . '/../../../public');

    if (!empty($newsToDelete['imagen_principal'])) {
        $image_path_on_disk = $base_public_path . '/' . $newsToDelete['imagen_principal'];
        if (file_exists($image_path_on_disk)) {
            if (!@unlink($image_path_on_disk)) { // Usar @ para manejar errores silenciosamente
                error_log("Error al eliminar el archivo de imagen: " . $image_path_on_disk);
            }
        } else {
            error_log("Advertencia: La imagen principal referenciada no existe en el disco: " . $image_path_on_disk);
        }
    }

    if ($this->noticiaModel->deleteNews($id)) {
        $_SESSION['success_message'] = "La noticia '" . htmlspecialchars($newsToDelete['titulo']) . "' ha sido eliminada exitosamente.";
    } else {
        $_SESSION['error_message'] = "Hubo un error al intentar eliminar la noticia '" . htmlspecialchars($newsToDelete['titulo']) . "'.";
    }

    header('Location: ./index.php?route=admin/news');
    exit();
}

 public function manageComments() {
        // 1. Obtener TODOS los comentarios (activos e inactivos)
        $todosLosComentarios = $this->comentarioModel->getAllComments(false);

        $noticiasConConteo = [];
        $noticiasProcesadas = [];

        // 2. Procesar para obtener noticias únicas y su conteo total de comentarios
        foreach ($todosLosComentarios as $comentario) {
            $id = $comentario['id_noticia'];

            // Si es la primera vez que vemos esta noticia, la agregamos
            if (!isset($noticiasProcesadas[$id])) {
                $noticiasProcesadas[$id] = [
                    'id_noticia' => $id,
                    'titulo_noticia' => $comentario['titulo_noticia'],
                    'conteo' => 0
                ];
            }
            // Incrementar el conteo para esta noticia
            $noticiasProcesadas[$id]['conteo']++;
        }

        // Convertir el array asociativo a un array indexado para la vista
        $noticiasConConteo = array_values($noticiasProcesadas);

        $this->renderAdminView('comentarios/index', [
            'page_title' => 'Gestión de Comentarios',
            'noticias' => $noticiasConConteo // Cambiado de 'comentarios' a 'noticias'
        ]);
    }


    public function getCommentsByNoticia() {
        header('Content-Type: application/json');

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'ID de noticia inválido']);
            exit;
        }

        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia((int)$id, false);

        $titulo = "";

        if (!empty($comentarios)) {
            $titulo = $comentarios[0]['titulo_noticia'] ?? $this->noticiaModel->getById((int)$id)['titulo'] ?? 'Noticia sin título';
        } else {
            $noticia = $this->noticiaModel->getById((int)$id);
            $titulo = $noticia['titulo'] ?? 'Noticia';
        }

        echo json_encode([
            'success' => true,
            'titulo' => $titulo,
            'comentarios' => $comentarios
        ]);
        exit;
    }

    public function softDeleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->softDeleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado lógicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar lógicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

  public function activateComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->activarComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario activado.";
        } else {
            $_SESSION['error_message'] = "Error al activar el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

 public function deleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido para eliminación física.";
            header('Location: ./index.php?route=admin/Comments');
            exit();
        }
        $result = $this->comentarioModel->deleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado físicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar físicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/Comments');
        exit();
    }

    public function manageNotifications() {
        $notificaciones = $this->notificacionModel->getAllNotifications();
        $data = [
            'title' => 'Gestión de Notificaciones',
            'page_title' => 'Gestión de Notificaciones',
            'notificaciones' => $notificaciones
        ];
        $this->renderAdminView('notifications/index', $data);
    }

    public function markNotificationRead($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: ./index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->update($id, ['leida' => 1]);
        if ($result) {
            $_SESSION['success_message'] = "Notificación marcada como leída.";
        } else {
            $_SESSION['error_message'] = "Error al marcar notificación como leída.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }

    public function markAllNotificationsRead() {
        $result = $this->notificacionModel->marcarTodasComoLeidas($_SESSION['id_usuario']);
        if ($result) {
            $_SESSION['success_message'] = "Todas las notificaciones marcadas como leídas.";
        } else {
            $_SESSION['error_message'] = "Error al marcar todas las notificaciones como leídas.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }

    public function deleteNotification($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: ./index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->delete($id);
        if ($result) {
            $_SESSION['success_message'] = "Notificación eliminada.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar la notificación.";
        }
        header('Location: ./index.php?route=admin/notifications');
        exit();
    }


public function reports() {

    $usuarios = $this->usuarioModel->getAll();
    $noticias = $this->noticiaModel->getAll();

    $data = [
        'title' => 'Reportes',
        'page_title' => 'Reportes de Administración',
        'usuarios' => $usuarios,
        'noticias' => $noticias
    ];

    $this->renderAdminView('reports', $data);
}

}
