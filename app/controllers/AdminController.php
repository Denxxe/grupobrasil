<?php
// grupobrasil/app/controllers/AdminController.php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php'; // Asegúrate de cargar si vas a gestionar comentarios
require_once __DIR__ . '/../models/Notificacion.php'; // Asegúrate de cargar si vas a gestionar notificaciones
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../utils/Validator.php';

class AdminController {
    private $usuarioModel;
    private $noticiaModel; 
    private $comentarioModel; // Añadido para futura gestión de comentarios
    private $notificacionModel; // Añadido para futura gestión de notificaciones
    private $categoriaModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario(); // Instanciar
        $this->notificacionModel = new Notificacion(); // Instanciar
        $this->categoriaModel = new Categoria(); 
   
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
            // Redirigir si el usuario no es un administrador
            $_SESSION['error_message'] = "Acceso no autorizado. Debe ser administrador.";
            header('Location: /grupobrasil/public/index.php?route=login');
            exit();
        }
    }

    /**
     * Renderiza una vista de administración dentro del layout principal.
     * @param string $viewPath La ruta de la vista a incluir (ej. 'dashboard', 'users/index').
     * @param array $data Los datos a pasar a la vista.
     */
    private function renderAdminView($viewPath, $data = []) {
        // Extrae los datos para que estén disponibles como variables en la vista
        extract($data);

        // Define títulos por defecto si no se proporcionan
        $title = $data['title'] ?? 'Panel de Administración';
        $page_title = $data['page_title'] ?? 'Dashboard de Administración';

        // Construye la ruta completa a la vista de contenido
        $content_view = __DIR__ . '/../views/admin/' . $viewPath . '.php';

        // Verifica si la vista existe
        if (!file_exists($content_view)) {
            http_response_code(500); // Internal Server Error
            echo "Error: La vista '" . htmlspecialchars($viewPath) . "' no se encontró en " . htmlspecialchars($content_view) . ".";
            exit();
        }

        include_once __DIR__ . '/../views/layouts/admin_layout.php';
    }


    /**
     * Muestra el dashboard de administración.
     */
    public function dashboard() {
        $totalUsuarios = $this->usuarioModel->getTotalUsuarios(); 
        $totalNoticias = $this->noticiaModel->getTotalNoticias(); 
        // Puedes añadir más estadísticas aquí
        // $totalComentarios = $this->comentarioModel->getTotalComentarios();
        // $noticiasPendientesRevision = $this->noticiaModel->getPendingReviewNewsCount();

        $data = [
            'title' => 'Dashboard', 
            'page_title' => 'Dashboard de Administración', 
            'totalUsuarios' => $totalUsuarios,
            'totalNoticias' => $totalNoticias, 
            // 'totalComentarios' => $totalComentarios,
            // 'noticiasPendientesRevision' => $noticiasPendientesRevision,
        ];
        $this->renderAdminView('dashboard', $data);
    }

    // --- Métodos para Gestión de Usuarios ---

    /**
     * Muestra la lista de usuarios con filtros y ordenación.
     */
    public function manageUsers() {
        $filters = [];
        $order = [];

        // Recoge y sanitiza los filtros de la URL
        if (isset($_GET['search'])) { $filters['search'] = filter_var(trim($_GET['search']), FILTER_SANITIZE_FULL_SPECIAL_CHARS); }
        if (isset($_GET['id_rol']) && $_GET['id_rol'] !== 'all') { $filters['id_rol'] = (int)filter_var($_GET['id_rol'], FILTER_SANITIZE_NUMBER_INT); }
        if (isset($_GET['activo']) && $_GET['activo'] !== 'all') { $filters['activo'] = (int)filter_var($_GET['activo'], FILTER_SANITIZE_NUMBER_INT); }
        if (isset($_GET['order_column']) && isset($_GET['order_direction'])) {
            $order['column'] = filter_var($_GET['order_column'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $order['direction'] = filter_var($_GET['order_direction'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        // Asegúrate de que el método `getAllFiltered` exista en tu modelo `Usuario.php`
        $usuarios = $this->usuarioModel->getAllFiltered($filters, $order);

        // Prepara los valores actuales para los campos del formulario de filtro
        $current_search = $filters['search'] ?? '';
        $current_id_rol = $filters['id_rol'] ?? 'all';
        $current_activo = $filters['activo'] ?? 'all';
        $current_order_column = $order['column'] ?? '';
        $current_order_direction = $order['direction'] ?? '';

        $data = [
            'title' => 'Gestión de Usuarios',
            'page_title' => 'Gestión de Usuarios',
            'usuarios' => $usuarios,
            'current_search' => $current_search,
            'current_id_rol' => $current_id_rol,
            'current_activo' => $current_activo,
            'current_order_column' => $current_order_column,
            'current_order_direction' => $current_order_direction,
        ];

        $this->renderAdminView('users/index', $data);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function createUser() {
        $roles = [
            1 => 'Administrador',
            2 => 'Sub-administrador',
            3 => 'Usuario Común'
        ];

        // Recupera errores y datos antiguos del formulario desde la sesión
        $errors = $_SESSION['form_errors'] ?? [];
        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_form_data']); 

        $data = [
            'title' => 'Crear Usuario',
            'page_title' => 'Crear Nuevo Usuario',
            'roles' => $roles,
            'errors' => $errors,
            'old_data' => $old_data 
        ];

        $this->renderAdminView('users/create', $data);
    }

    /**
     * Procesa la creación de un nuevo usuario.
     */
    public function storeUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ci_usuario = filter_input(INPUT_POST, 'ci_usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);
            $id_rol = (int)$id_rol;

            // Datos por defecto para campos no obligatorios en la creación inicial
            $data = [
                'ci_usuario' => $ci_usuario,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'id_rol' => $id_rol,
                'fecha_nacimiento' => '1900-01-01', // Valor por defecto
                'direccion' => 'N/A', // Valor por defecto
                'telefono' => 'N/A', // Valor por defecto
                'email' => $ci_usuario . '@temp.com', // Email temporal
                'password' => '', // Se establecerá la CI como contraseña inicial
                'foto_perfil' => null,
                'biografia' => null,
                'activo' => 1, // Por defecto activo
                'requires_setup' => 1 // Requerirá completar perfil al primer login
            ];

            $errors = [];

            // Validaciones
            if (Validator::isEmpty($data['ci_usuario'])) {
                $errors[] = 'La cédula es obligatoria.';
            } else if (!Validator::isValidCI($data['ci_usuario'])) {
                $errors[] = 'Formato de cédula inválido. (Ej: V-12345678, E-87654321)';
            } else {
                // Verifica si la CI ya existe
                if ($this->usuarioModel->buscarPorCI($data['ci_usuario'])) {
                    $errors[] = 'Ya existe un usuario con esa cédula.';
                }
            }
            if (Validator::isEmpty($data['nombre'])) $errors[] = 'El nombre es obligatorio.';
            if (Validator::isEmpty($data['apellido'])) $errors[] = 'El apellido es obligatorio.';
            if (!in_array($data['id_rol'], [1, 2, 3])) $errors[] = 'Debe seleccionar un rol válido.';

            if (empty($errors)) {
                // Establece la contraseña inicial como la CI y la hashea
                $data['password'] = password_hash($data['ci_usuario'], PASSWORD_DEFAULT);

                // Llama al método genérico `create` de ModelBase (heredado por Usuario)
                $newUserId = $this->usuarioModel->create($data); // <--- CORRECCIÓN AQUÍ

                if ($newUserId) {
                    $_SESSION['success_message'] = 'Usuario creado exitosamente. La contraseña inicial es la Cédula de Identidad del usuario. Se le pedirá que complete su perfil al iniciar sesión por primera vez.';
                    header('Location: /grupobrasil/public/index.php?route=admin/users');
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Error al guardar el usuario en la base de datos. Por favor, inténtelo de nuevo.';
                    $_SESSION['old_form_data'] = $_POST;
                    header('Location: /grupobrasil/public/index.php?route=admin/users/create');
                    exit();
                }
            } else {
                // Si hay errores, guardarlos en sesión y redirigir al formulario
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $_POST; 
                header('Location: /grupobrasil/public/index.php?route=admin/users/create');
                exit();
            }
        } else {
            // Si no es un POST, redirigir al formulario de creación
            header('Location: /grupobrasil/public/index.php?route=admin/users/create');
            exit();
        }
    }

    /**
     * Muestra el formulario para editar un usuario existente.
     * @param int $id El ID del usuario a editar.
     */
    public function editUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        $user = $this->usuarioModel->getById($id); // Usar el método genérico getById

        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        // Definición de roles para el dropdown
        $roles = [
            ['id_rol' => 1, 'nombre_rol' => 'Administrador'],
            ['id_rol' => 2, 'nombre_rol' => 'Sub-administrador'],
            ['id_rol' => 3, 'nombre_rol' => 'Usuario Común']
        ];

        // Recupera errores y datos antiguos del formulario desde la sesión
        $errors = $_SESSION['form_errors'] ?? [];
        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_form_data']);

        // Si hay datos antiguos del formulario (por un error de validación), úsalos. De lo contrario, usa los datos del usuario.
        $user_data_to_display = !empty($old_data) ? array_merge($user, $old_data) : $user; // Mezclar para conservar campos no enviados en old_data

        $data = [
            'title' => 'Editar Usuario',
            'page_title' => 'Editar Usuario',
            'user' => $user_data_to_display,
            'roles' => $roles,
            'errors' => $errors,
        ];

        $this->renderAdminView('users/edit', $data);
    }

    /**
     * Procesa la actualización de un usuario existente.
     */
    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_usuario) || $id_usuario <= 0) {
            $_SESSION['error_message'] = "Error: ID de usuario inválido en el formulario.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        // Recoge y sanitiza todos los datos del formulario
        $data = [
            'ci_usuario' => filter_input(INPUT_POST, 'ci_usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'fecha_nacimiento' => filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_FULL_SPECIAL_CHARS), // Se valida el formato después
            'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'id_rol' => (int)filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'biografia' => filter_input(INPUT_POST, 'biografia', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            // 'foto_perfil' se maneja por separado si hay subida de archivo
            'requires_setup' => isset($_POST['requires_setup']) ? 1 : 0
        ];

        $errors = [];

        // Validaciones
        if (Validator::isEmpty($data['ci_usuario'])) {
            $errors[] = "La cédula es obligatoria.";
        } else if (!Validator::isValidCI($data['ci_usuario'])) {
            $errors[] = "Formato de cédula inválido. (Ej: V-12345678, E-87654321)";
        } else {
            // Verifica que la CI no esté duplicada por otro usuario (excepto el actual)
            $existingUserByCI = $this->usuarioModel->buscarPorCI($data['ci_usuario']);
            if ($existingUserByCI && $existingUserByCI['id_usuario'] != $id_usuario) {
                $errors[] = "La cédula ya está registrada por otro usuario.";
            }
        }

        if (Validator::isEmpty($data['nombre'])) { $errors[] = "El nombre es obligatorio."; }
        if (Validator::isEmpty($data['apellido'])) { $errors[] = "El apellido es obligatorio."; }

        if (Validator::isEmpty($data['email'])) {
            $errors[] = "El email es obligatorio.";
        } else if (!Validator::isValidEmail($data['email'])) {
            $errors[] = "El email no es válido.";
        } else {
            // Verifica que el email no esté duplicado por otro usuario (excepto el actual)
            $existingUserByEmail = $this->usuarioModel->buscarPorEmail($data['email']);
            if ($existingUserByEmail && $existingUserByEmail['id_usuario'] != $id_usuario) {
                $errors[] = "El email ya está registrado por otro usuario.";
            }
        }

        if (!in_array($data['id_rol'], [1, 2, 3])) { $errors[] = "Debe seleccionar un rol válido."; }
        
        // La validación de fecha de nacimiento si no es un campo vacío, porque si es opcional y se deja vacío, no debería generar error
        if (!Validator::isEmpty($data['fecha_nacimiento']) && !Validator::isValidDate($data['fecha_nacimiento'])) { 
            $errors[] = "Formato de fecha de nacimiento inválido (YYYY-MM-DD)."; 
        }

        // Manejo de la contraseña (solo si se ingresa una nueva)
        $new_password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW); // No sanitizar la contraseña para no cambiarla antes de hashear
        if (!empty($new_password)) {
            if (!Validator::isValidPassword($new_password)) {
                $errors[] = "La nueva contraseña no cumple los requisitos (mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un símbolo).";
            } else {
                $data['password'] = password_hash($new_password, PASSWORD_BCRYPT);
            }
        } else {
            // Si no se ingresa una nueva contraseña, asegúrate de no intentar actualizarla en el modelo
            unset($data['password']);
        }

        // --- Manejo de la subida de foto de perfil ---
        $upload_dir = __DIR__ . '/../../public/uploads/profile_pictures/'; // Directorio para fotos de perfil
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $current_user = $this->usuarioModel->getById($id_usuario); // Obtener datos actuales del usuario para la imagen
        $data['foto_perfil'] = $current_user['foto_perfil'] ?? null; // Mantener la imagen actual por defecto

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            // Se subió una nueva imagen
            $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('profile_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['foto_perfil']['type'], $allowed_types)) {
                $errors[] = 'Solo se permiten imágenes JPG, PNG y GIF para la foto de perfil.';
            } elseif ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) { // Límite de 2 MB
                $errors[] = 'La foto de perfil es demasiado grande. Máximo 2MB.';
            } elseif (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                // Eliminar imagen antigua si existe y es diferente a la nueva
                if ($current_user['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user['foto_perfil'])) {
                    unlink(__DIR__ . '/../../public' . $current_user['foto_perfil']);
                }
                $data['foto_perfil'] = '/grupobrasil/public/uploads/profile_pictures/' . $file_name;
            } else {
                $errors[] = 'Error al subir la nueva foto de perfil. Código: ' . $_FILES['foto_perfil']['error'];
            }
        } elseif (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
            // Si se marcó para eliminar la imagen existente
            if ($current_user['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user['foto_perfil'])) {
                unlink(__DIR__ . '/../../public' . $current_user['foto_perfil']);
            }
            $data['foto_perfil'] = null; // Establecer a NULL en la base de datos
        }
        // Si no se sube una nueva imagen y no se marca para eliminar, $data['foto_perfil'] mantiene el valor existente.
        // --- FIN Manejo de la subida de foto de perfil ---


        if (!empty($errors)) {
            $_SESSION['error_message'] = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errors);
            $_SESSION['old_form_data'] = $_POST; 
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil']; // Conservar la URL de la imagen si se subió
            header('Location: /grupobrasil/public/index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }

        // Llama al método genérico `update` de ModelBase (heredado por Usuario)
        $result = $this->usuarioModel->update($id_usuario, $data); // <--- CORRECCIÓN AQUÍ

        if ($result) {
            $_SESSION['success_message'] = "Usuario actualizado exitosamente.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        } else {
            $_SESSION['error_message'] = "Error al actualizar el usuario en la base de datos. Por favor, inténtelo de nuevo.";
            $_SESSION['old_form_data'] = $_POST; // Preserva los datos para rellenar el formulario
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil']; // Conservar la URL de la imagen si se subió
            header('Location: /grupobrasil/public/index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }
    }

    /**
     * Elimina un usuario.
     * @param int $id El ID del usuario a eliminar.
     */
    public function deleteUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido o no proporcionado para la eliminación.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        // Previene que un administrador se elimine a sí mismo
        if (isset($_SESSION['id_usuario']) && $id == $_SESSION['id_usuario']) {
            $_SESSION['error_message'] = "No puedes eliminar tu propia cuenta de administrador.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        $userToDelete = $this->usuarioModel->getById($id); // Usar getById
        if (!$userToDelete) {
            $_SESSION['error_message'] = "Usuario a eliminar no encontrado.";
            header('Location: /grupobrasil/public/index.php?route=admin/users');
            exit();
        }

        // Eliminar la foto de perfil asociada si existe en el sistema de archivos
        if ($userToDelete['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
            if (!unlink(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
                error_log("Error al eliminar el archivo de foto de perfil: " . __DIR__ . '/../../public' . $userToDelete['foto_perfil']);
            }
        }

        // Llama al método genérico `delete` de ModelBase (heredado por Usuario)
        $success = $this->usuarioModel->delete($id); // <--- CORRECCIÓN AQUÍ

        if ($success) {
            $_SESSION['success_message'] = "El usuario '" . htmlspecialchars($userToDelete['nombre'] . ' ' . $userToDelete['apellido']) . "' ha sido eliminado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Hubo un error al intentar eliminar el usuario '" . htmlspecialchars($userToDelete['nombre'] . ' ' . $userToDelete['apellido']) . "'.";
        }

        header('Location: /grupobrasil/public/index.php?route=admin/users');
        exit();
    }

    // --- Métodos para Gestión de Noticias ---

  public function manageNews() {
        // Obtenemos todas las noticias, sin importar si están activas o no, para la vista de administración
        // Pasamos 'false' a getAllNews para que NO filtre solo por activas
        $noticias = $this->noticiaModel->getAllNews(false, ['column' => 'fecha_publicacion', 'direction' => 'DESC']);

        // Recuperar mensajes de éxito y error de la sesión
        // Usar un nombre de variable más específico para evitar colisiones
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


public function createNews() {
        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['old_form_data']);

        // Asegúrate de que $errors sea un array para un manejo consistente en la vista.
        $errors = $_SESSION['error_error_messages'] ?? []; // Usar un nombre de variable diferente para errores de validación
        unset($_SESSION['error_error_messages']); // Limpia después de leer

        // Obtener todas las categorías para el select
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

public function storeNews() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recopilar y sanear datos directamente de $_POST
            $data = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'contenido' => trim($_POST['contenido'] ?? ''),
                'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT), // Validar como INT
                'activo' => isset($_POST['activo']) ? 1 : 0,
            ];

            $errors = [];

            // Validaciones
            if (empty($data['titulo'])) {
                $errors['titulo'] = 'El título es obligatorio.';
            }
            if (empty($data['contenido'])) {
                $errors['contenido'] = 'El contenido es obligatorio.';
            }
            // Validar que id_categoria sea un entero y no esté vacío
            if ($data['id_categoria'] === false || $data['id_categoria'] === null) {
                $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
            }

            // Manejo de la imagen principal
            $imagen_principal_path = null;
            // Verifica que el 'name' del input file en el HTML sea 'imagen_principal'
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/noticias/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) { // Asegúrate de que mkdir sea exitoso
                        $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
                    }
                }

                if (empty($errors['imagen_principal'])) { // Si no hubo error al crear el directorio
                    $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('news_') . '.' . $file_extension; // Usar prefijo para evitar colisiones
                    $target_file = $upload_dir . $file_name;

                    // Validación de tipo MIME y tamaño
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime_type, $allowed_types)) {
                        $errors['imagen_principal'] = 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.';
                    } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) { // 5MB
                        $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
                    } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                        $imagen_principal_path = 'uploads/noticias/' . $file_name; // Guarda la ruta relativa a 'public/'
                    } else {
                        $errors['imagen_principal'] = 'Error desconocido al mover la imagen.';
                    }
                }
            } elseif (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Captura otros errores de subida (ej. tamaño excedido por php.ini)
                $errors['imagen_principal'] = 'Error en la subida del archivo: ' . $_FILES['imagen_principal']['error'];
            }

            // Si hay errores de validación o subida de imagen, guardar y redirigir
            if (!empty($errors)) {
                $_SESSION['old_form_data'] = $_POST;
                // Si la imagen se subió a pesar de otros errores, podrías querer conservarla o limpiarla.
                // Aquí, si hay errores, no la guardamos en la DB, pero ya está en el servidor.
                // Una opción sería moverla a una carpeta temporal hasta que el formulario se envíe correctamente.
                // Por ahora, simplemente no la asignamos a $news_data si hay errores.

                $_SESSION['error_error_messages'] = $errors; // Usar el array de errores
                header('Location: /grupobrasil/public/index.php?route=admin/news/create');
                exit();
            }

            // Si todo está bien, preparar los datos para el modelo
            $news_data = [
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'],
                'imagen_principal' => $imagen_principal_path, // Será null si no se subió imagen
                'id_usuario_publicador' => $_SESSION['id_usuario'] ?? 1, // Asegúrate de tener un ID de usuario o un valor por defecto.
                'id_categoria' => $data['id_categoria'],
                'activo' => $data['activo']
            ];

            // Llamar al método createNews del modelo Noticia
            $new_news_id = $this->noticiaModel->createNews($news_data);

            if ($new_news_id) {
                $_SESSION['success_message'] = "Noticia creada exitosamente con ID: " . $new_news_id;
                header('Location: /grupobrasil/public/index.php?route=admin/news');
                exit();
            } else {
                $_SESSION['error_error_messages'] = ['Error al crear la noticia en la base de datos. Consulta los logs para más detalles.'];
                $_SESSION['old_form_data'] = $_POST;
                header('Location: /grupobrasil/public/index.php?route=admin/news/create');
                exit();
            }
        } else {
            header('Location: /grupobrasil/public/index.php?route=admin/news/create');
            exit();
        }
    }


public function editNews($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de noticia inválido para edición.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        // Usar getNewsById para compatibilidad con Noticia.php
        $news = $this->noticiaModel->getNewsById($id, false); // Obtenerla sin importar si está activa o no

        if (!$news) {
            $_SESSION['error_message'] = "Noticia no encontrada.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['old_form_data']);

        $errors = $_SESSION['error_error_messages'] ?? []; // Usar el array de errores
        unset($_SESSION['error_error_messages']);

        // Si hay datos antiguos de un intento fallido, combinarlos con los datos actuales de la noticia
        // Esto asegura que los datos que el usuario ya ingresó (aunque con errores) se mantengan
        $news_data_for_form = array_merge($news, $old_data);

        // Obtener todas las categorías
        $categorias = $this->categoriaModel->getAllCategories();

        $data = [
            'title' => 'Editar Noticia',
            'page_title' => 'Editar Noticia',
            'news' => $news_data_for_form, // Usar este para pre-rellenar el formulario
            'errors' => $errors,
            'old_data' => $old_data, // Mantener old_data por si la lógica de merge no es exhaustiva
            'categorias' => $categorias
        ];

        $this->renderAdminView('news/edit', $data);
    }

  
    public function updateNews() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        $id_noticia = filter_input(INPUT_POST, 'id_noticia', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_noticia) || $id_noticia <= 0) {
            $_SESSION['error_message'] = "Error: ID de noticia inválido en el formulario de actualización.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        // Obtener la noticia actual para comparar y mantener la imagen existente si no se cambia
        $current_news = $this->noticiaModel->getNewsById($id_noticia, false); // Obtenerla sin importar si está activa o no
        if (!$current_news) {
            $_SESSION['error_message'] = "Noticia a actualizar no encontrada.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        $data_to_update = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'contenido' => trim($_POST['contenido'] ?? ''),
            'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
            'id_usuario_publicador' => $_SESSION['id_usuario'] ?? null, // El publicador siempre es el que edita (administrador)
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'imagen_principal' => $current_news['imagen_principal'] // Mantener la imagen actual por defecto
        ];

        $errors = [];

        // Validaciones
        if (empty($data_to_update['titulo'])) {
            $errors['titulo'] = 'El título de la noticia es obligatorio.';
        }
        if (empty($data_to_update['contenido'])) {
            $errors['contenido'] = 'El contenido de la noticia es obligatorio.';
        }
        if (empty($data_to_update['id_usuario_publicador'])) {
            $errors['id_usuario_publicador'] = 'No se pudo determinar el usuario publicador. Por favor, inicie sesión de nuevo.';
        }
        if ($data_to_update['id_categoria'] === false || $data_to_update['id_categoria'] === null) {
            $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
        }

        // Manejo de la subida o eliminación de imagen
        $upload_dir = __DIR__ . '/../../public/uploads/noticias/'; // Directorio consistente con storeNews
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
            }
        }

        // Si se subió una nueva imagen
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('news_') . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors['imagen_principal'] = 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.';
            } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) { // 5MB
                $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
            } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                // Eliminar imagen antigua solo si existe y es diferente de la nueva
                if (!empty($current_news['imagen_principal']) && file_exists(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                    unlink(__DIR__ . '/../../public/' . $current_news['imagen_principal']);
                }
                $data_to_update['imagen_principal'] = 'uploads/noticias/' . $file_name; // Guarda la ruta relativa a 'public/'
            } else {
                $errors['imagen_principal'] = 'Error al subir la nueva imagen.';
            }
        } elseif (isset($_POST['remove_imagen_principal']) && $_POST['remove_imagen_principal'] === '1') {
            // Si se marcó para eliminar la imagen existente
            if (!empty($current_news['imagen_principal']) && file_exists(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                if (!unlink(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                    error_log("Error al eliminar imagen antigua: " . __DIR__ . '/../../public/' . $current_news['imagen_principal']);
                }
            }
            $data_to_update['imagen_principal'] = null; // Establecer a NULL en la base de datos
        }
        // Si no se sube una nueva imagen y no se marca para eliminar, $data_to_update['imagen_principal'] mantiene el valor existente.


        if (!empty($errors)) {
            $_SESSION['error_error_messages'] = $errors; // Usar el array de errores
            $_SESSION['old_form_data'] = $_POST;
            // Podrías añadir la ruta de la imagen actual a old_form_data si es necesario para repoblar la vista con la imagen
            if (isset($data_to_update['imagen_principal'])) {
                 $_SESSION['old_form_data']['imagen_principal'] = $data_to_update['imagen_principal'];
            }
            header('Location: /grupobrasil/public/index.php?route=admin/news/edit&id=' . $id_noticia);
            exit();
        }

        // Llamar a updateNews del modelo
        $result = $this->noticiaModel->updateNews($id_noticia, $data_to_update); // Usar updateNews

        if ($result) {
            $_SESSION['success_message'] = "Noticia actualizada exitosamente.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        } else {
            $_SESSION['error_error_messages'] = ['Error al actualizar la noticia en la base de datos. Por favor, inténtelo de nuevo.'];
            $_SESSION['old_form_data'] = $_POST;
            if (isset($data_to_update['imagen_principal'])) {
                $_SESSION['old_form_data']['imagen_principal'] = $data_to_update['imagen_principal'];
            }
            header('Location: /grupobrasil/public/index.php?route=admin/news/edit&id=' . $id_noticia);
            exit();
        }
    }

    /**
     * Elimina una noticia.
     * @param int $id El ID de la noticia a eliminar.
     */
    public function deleteNews($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de noticia inválido o no proporcionado para la eliminación.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        $newsToDelete = $this->noticiaModel->getNewsById($id, false); // Obtenerla sin importar si está activa o no
        if (!$newsToDelete) {
            $_SESSION['error_message'] = "Noticia a eliminar no encontrada.";
            header('Location: /grupobrasil/public/index.php?route=admin/news');
            exit();
        }

        // Eliminar la imagen asociada si existe en el sistema de archivos
        if (!empty($newsToDelete['imagen_principal'])) { // Mejorar verificación de si existe imagen
            $image_path_on_disk = __DIR__ . '/../../public/' . $newsToDelete['imagen_principal'];
            if (file_exists($image_path_on_disk)) {
                if (!unlink($image_path_on_disk)) {
                    error_log("Error al eliminar el archivo de imagen: " . $image_path_on_disk);
                    // Opcional: No bloquear la eliminación de la noticia si la imagen no se puede eliminar.
                    // Si prefieres bloquear, podrías añadir un error a la sesión aquí.
                }
            } else {
                error_log("Advertencia: La imagen principal referenciada no existe en el disco: " . $image_path_on_disk);
            }
        }

        // Llamar a deleteNews del modelo
        if ($this->noticiaModel->deleteNews($id)) { // Cambiado a deleteNews para mayor claridad
            $_SESSION['success_message'] = "La noticia '" . htmlspecialchars($newsToDelete['titulo']) . "' ha sido eliminada exitosamente.";
        } else {
            $_SESSION['error_message'] = "Hubo un error al intentar eliminar la noticia '" . htmlspecialchars($newsToDelete['titulo']) . "'.";
        }

        header('Location: /grupobrasil/public/index.php?route=admin/news');
        exit();
    }


    // --- Métodos para Gestión de Comentarios (Ejemplo, si los necesitas) ---
    public function manageComments() {
        // Asegúrate de tener un método para obtener comentarios en Comentario.php
        $comentarios = $this->comentarioModel->getAllComments(); // O getAllFiltered

        $data = [
            'title' => 'Gestión de Comentarios',
            'page_title' => 'Gestión de Comentarios',
            'comentarios' => $comentarios
        ];
        $this->renderAdminView('comments/index', $data); // Asume views/admin/comments/index.php
    }

    // Método para soft-delete de comentarios
    public function softDeleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: /grupobrasil/public/index.php?route=admin/comments');
            exit();
        }
        $result = $this->comentarioModel->softDelete($id); // Asume que tienes softDelete en Comentario.php
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado lógicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar lógicamente el comentario.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/comments');
        exit();
    }

    // Método para activar comentarios (si tienes un campo para eso)
    public function activateComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: /grupobrasil/public/index.php?route=admin/comments');
            exit();
        }
        // Asume un método como updateStatus en Comentario.php
        $result = $this->comentarioModel->update($id, ['activo' => 1]); 
        if ($result) {
            $_SESSION['success_message'] = "Comentario activado.";
        } else {
            $_SESSION['error_message'] = "Error al activar el comentario.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/comments');
        exit();
    }

    // Método para eliminación física de comentarios
    public function deleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido para eliminación física.";
            header('Location: /grupobrasil/public/index.php?route=admin/comments');
            exit();
        }
        $result = $this->comentarioModel->delete($id); // Asume que tienes delete en Comentario.php
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado físicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar físicamente el comentario.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/comments');
        exit();
    }


    // --- Métodos para Gestión de Notificaciones (Ejemplo, si los necesitas) ---
    public function manageNotifications() {
        $notificaciones = $this->notificacionModel->getAllNotifications(); // O getUnreadNotifications

        $data = [
            'title' => 'Gestión de Notificaciones',
            'page_title' => 'Gestión de Notificaciones',
            'notificaciones' => $notificaciones
        ];
        $this->renderAdminView('notifications/index', $data); // Asume views/admin/notifications/index.php
    }

    public function markNotificationRead($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: /grupobrasil/public/index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->update($id, ['leida' => 1]);
        if ($result) {
            $_SESSION['success_message'] = "Notificación marcada como leída.";
        } else {
            $_SESSION['error_message'] = "Error al marcar notificación como leída.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/notifications');
        exit();
    }

    public function markAllNotificationsRead() {
        $result = $this->notificacionModel->markAllAsRead($_SESSION['id_usuario']); 
        if ($result) {
            $_SESSION['success_message'] = "Todas las notificaciones marcadas como leídas.";
        } else {
            $_SESSION['error_message'] = "Error al marcar todas las notificaciones como leídas.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/notifications');
        exit();
    }

    public function deleteNotification($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de notificación inválido.";
            header('Location: /grupobrasil/public/index.php?route=admin/notifications');
            exit();
        }
        $result = $this->notificacionModel->delete($id);
        if ($result) {
            $_SESSION['success_message'] = "Notificación eliminada.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar la notificación.";
        }
        header('Location: /grupobrasil/public/index.php?route=admin/notifications');
        exit();
    }
}