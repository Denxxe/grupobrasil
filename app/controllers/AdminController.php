<?php
// grupobrasil/app/controllers/AdminController.php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php'; 
require_once __DIR__ . '/../models/Notificacion.php'; 
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/AppController.php';

class AdminController extends AppController{
    private $usuarioModel;
    private $noticiaModel; 
    private $comentarioModel; 
    private $notificacionModel;
    private $categoriaModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario(); 
        $this->notificacionModel = new Notificacion(); 
        $this->categoriaModel = new Categoria(); 
   
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

    public function manageUsers() {
        $filters = [];
        $order = [];

        if (isset($_GET['search'])) { $filters['search'] = filter_var(trim($_GET['search']), FILTER_SANITIZE_FULL_SPECIAL_CHARS); }
        if (isset($_GET['id_rol']) && $_GET['id_rol'] !== 'all') { $filters['id_rol'] = (int)filter_var($_GET['id_rol'], FILTER_SANITIZE_NUMBER_INT); }
        if (isset($_GET['activo']) && $_GET['activo'] !== 'all') { $filters['activo'] = (int)filter_var($_GET['activo'], FILTER_SANITIZE_NUMBER_INT); }
        if (isset($_GET['order_column']) && isset($_GET['order_direction'])) {
            $order['column'] = filter_var($_GET['order_column'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $order['direction'] = filter_var($_GET['order_direction'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        $usuarios = $this->usuarioModel->getAllFiltered($filters, $order);

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

    public function createUser() {
        $roles = [
            1 => 'Administrador',
            2 => 'Sub-administrador',
            3 => 'Usuario Común'
        ];

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

    public function storeUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ci_usuario = filter_input(INPUT_POST, 'ci_usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_SANITIZE_NUMBER_INT);
            $id_rol = (int)$id_rol;

            $data = [
                'ci_usuario' => $ci_usuario,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'id_rol' => $id_rol,
                'fecha_nacimiento' => '1900-01-01',
                'direccion' => 'N/A',
                'telefono' => 'N/A',
                'email' => $ci_usuario . '@temp.com',
                'password' => '',
                'foto_perfil' => null,
                'biografia' => null,
                'activo' => 1,
                'requires_setup' => 1 
            ];

            $errors = [];

            if (Validator::isEmpty($data['ci_usuario'])) {
                $errors[] = 'La cédula es obligatoria.';
            } else if (!Validator::isValidCI($data['ci_usuario'])) {
                $errors[] = 'Formato de cédula inválido. (Ej: V-12345678, E-87654321)';
            } else {

                if ($this->usuarioModel->buscarPorCI($data['ci_usuario'])) {
                    $errors[] = 'Ya existe un usuario con esa cédula.';
                }
            }
            if (Validator::isEmpty($data['nombre'])) $errors[] = 'El nombre es obligatorio.';
            if (Validator::isEmpty($data['apellido'])) $errors[] = 'El apellido es obligatorio.';
            if (!in_array($data['id_rol'], [1, 2, 3])) $errors[] = 'Debe seleccionar un rol válido.';

            if (empty($errors)) {

                $data['password'] = password_hash($data['ci_usuario'], PASSWORD_DEFAULT);

                $newUserId = $this->usuarioModel->create($data); 

                if ($newUserId) {
                    $_SESSION['success_message'] = 'Usuario creado exitosamente. La contraseña inicial es la Cédula de Identidad del usuario. Se le pedirá que complete su perfil al iniciar sesión por primera vez.';
                    header('Location: ./index.php?route=admin/users');
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Error al guardar el usuario en la base de datos. Por favor, inténtelo de nuevo.';
                    $_SESSION['old_form_data'] = $_POST;
                    header('Location: ./index.php?route=admin/users/create');
                    exit();
                }
            } else {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_form_data'] = $_POST; 
                header('Location: ./index.php?route=admin/users/create');
                exit();
            }
        } else {
            header('Location: ./index.php?route=admin/users/create');
            exit();
        }
    }

    public function editUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        $user = $this->usuarioModel->getById($id);

        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        $roles = [
            ['id_rol' => 1, 'nombre_rol' => 'Administrador'],
            ['id_rol' => 2, 'nombre_rol' => 'Sub-administrador'],
            ['id_rol' => 3, 'nombre_rol' => 'Usuario Común']
        ];

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
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_usuario) || $id_usuario <= 0) {
            $_SESSION['error_message'] = "Error: ID de usuario inválido en el formulario.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

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
            'requires_setup' => isset($_POST['requires_setup']) ? 1 : 0
        ];

        $errors = [];

        if (Validator::isEmpty($data['ci_usuario'])) {
            $errors[] = "La cédula es obligatoria.";
        } else if (!Validator::isValidCI($data['ci_usuario'])) {
            $errors[] = "Formato de cédula inválido. (Ej: V-12345678, E-87654321)";
        } else {
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
            $existingUserByEmail = $this->usuarioModel->buscarPorEmail($data['email']);
            if ($existingUserByEmail && $existingUserByEmail['id_usuario'] != $id_usuario) {
                $errors[] = "El email ya está registrado por otro usuario.";
            }
        }

        if (!in_array($data['id_rol'], [1, 2, 3])) { $errors[] = "Debe seleccionar un rol válido."; }
    
        if (!Validator::isEmpty($data['fecha_nacimiento']) && !Validator::isValidDate($data['fecha_nacimiento'])) { 
            $errors[] = "Formato de fecha de nacimiento inválido (YYYY-MM-DD)."; 
        }

        $new_password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
        if (!empty($new_password)) {
            if (!Validator::isValidPassword($new_password)) {
                $errors[] = "La nueva contraseña no cumple los requisitos (mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un símbolo).";
            } else {
                $data['password'] = password_hash($new_password, PASSWORD_BCRYPT);
            }
        } else {

            unset($data['password']);
        }

        $upload_dir = __DIR__ . '/../../public/uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $current_user = $this->usuarioModel->getById($id_usuario);
        $data['foto_perfil'] = $current_user['foto_perfil'] ?? null;

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
                if ($current_user['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user['foto_perfil'])) {
                    unlink(__DIR__ . '/../../public' . $current_user['foto_perfil']);
                }
                $data['foto_perfil'] = './uploads/profile_pictures/' . $file_name;
            } else {
                $errors[] = 'Error al subir la nueva foto de perfil. Código: ' . $_FILES['foto_perfil']['error'];
            }
        } elseif (isset($_POST['remove_foto_perfil']) && $_POST['remove_foto_perfil'] === '1') {
            if ($current_user['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $current_user['foto_perfil'])) {
                unlink(__DIR__ . '/../../public' . $current_user['foto_perfil']);
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

        $result = $this->usuarioModel->update($id_usuario, $data);

        if ($result) {
            $_SESSION['success_message'] = "Usuario actualizado exitosamente.";
            header('Location: ./index.php?route=admin/users');
            exit();
        } else {
            $_SESSION['error_message'] = "Error al actualizar el usuario en la base de datos. Por favor, inténtelo de nuevo.";
            $_SESSION['old_form_data'] = $_POST;
            $_SESSION['old_form_data']['foto_perfil'] = $data['foto_perfil'];
            header('Location: ./index.php?route=admin/users/edit&id=' . $id_usuario);
            exit();
        }
    }

    public function deleteUser($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de usuario inválido o no proporcionado para la eliminación.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        if (isset($_SESSION['id_usuario']) && $id == $_SESSION['id_usuario']) {
            $_SESSION['error_message'] = "No puedes eliminar tu propia cuenta de administrador.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        $userToDelete = $this->usuarioModel->getById($id);
        if (!$userToDelete) {
            $_SESSION['error_message'] = "Usuario a eliminar no encontrado.";
            header('Location: ./index.php?route=admin/users');
            exit();
        }

        if ($userToDelete['foto_perfil'] && file_exists(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
            if (!unlink(__DIR__ . '/../../public' . $userToDelete['foto_perfil'])) {
                error_log("Error al eliminar el archivo de foto de perfil: " . __DIR__ . '/../../public' . $userToDelete['foto_perfil']);
            }
        }

        $success = $this->usuarioModel->delete($id);

        if ($success) {
            $_SESSION['success_message'] = "El usuario '" . htmlspecialchars($userToDelete['nombre'] . ' ' . $userToDelete['apellido']) . "' ha sido eliminado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Hubo un error al intentar eliminar el usuario '" . htmlspecialchars($userToDelete['nombre'] . ' ' . $userToDelete['apellido']) . "'.";
        }

        header('Location: ./index.php?route=admin/users');
        exit();
    }

  public function manageNews() {
        $noticias = $this->noticiaModel->getAllNews(false, ['column' => 'fecha_publicacion', 'direction' => 'DESC']);
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

        $errors = $_SESSION['error_error_messages'] ?? [];
        unset($_SESSION['error_error_messages']);

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

            $data = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'contenido' => trim($_POST['contenido'] ?? ''),
                'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT), 
                'activo' => isset($_POST['activo']) ? 1 : 0,
            ];

            $errors = [];

            if (empty($data['titulo'])) {
                $errors['titulo'] = 'El título es obligatorio.';
            }
            if (empty($data['contenido'])) {
                $errors['contenido'] = 'El contenido es obligatorio.';
            }
            if ($data['id_categoria'] === false || $data['id_categoria'] === null) {
                $errors['id_categoria'] = 'La categoría es obligatoria y debe ser un número válido.';
            }
            $imagen_principal_path = null;
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/noticias/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
                    }
                }

                if (empty($errors['imagen_principal'])) {
                    $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('news_') . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES['imagen_principal']['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime_type, $allowed_types)) {
                        $errors['imagen_principal'] = 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.';
                    } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) {
                        $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
                    } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                        $imagen_principal_path = 'uploads/noticias/' . $file_name;
                    } else {
                        $errors['imagen_principal'] = 'Error desconocido al mover la imagen.';
                    }
                }
            } elseif (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors['imagen_principal'] = 'Error en la subida del archivo: ' . $_FILES['imagen_principal']['error'];
            }

            if (!empty($errors)) {
                $_SESSION['old_form_data'] = $_POST;
                $_SESSION['error_error_messages'] = $errors;
                header('Location: ./index.php?route=admin/news/create');
                exit();
            }

            $news_data = [
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'],
                'imagen_principal' => $imagen_principal_path,
                'id_usuario_publicador' => $_SESSION['id_usuario'] ?? 1,
                'id_categoria' => $data['id_categoria'],
                'activo' => $data['activo']
            ];

            $new_news_id = $this->noticiaModel->createNews($news_data);
            if ($new_news_id) {
                $_SESSION['success_message'] = "Noticia creada exitosamente con ID: " . $new_news_id;
                header('Location: ./index.php?route=admin/news');
                exit();
            } else {
                $_SESSION['error_error_messages'] = ['Error al crear la noticia en la base de datos. Consulta los logs para más detalles.'];
                $_SESSION['old_form_data'] = $_POST;
                header('Location: ./index.php?route=admin/news/create');
                exit();
            }
        } else {
            header('Location: ./index.php?route=admin/news/create');
            exit();
        }
    }


public function editNews($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de noticia inválido para edición.";
            header('Location: ./index.php?route=admin/news');
            exit();
        }

        $news = $this->noticiaModel->getNewsById($id, false);

        if (!$news) {
            $_SESSION['error_message'] = "Noticia no encontrada.";
            header('Location: ./index.php?route=admin/news');
            exit();
        }

        $old_data = $_SESSION['old_form_data'] ?? [];
        unset($_SESSION['old_form_data']);

        $errors = $_SESSION['error_error_messages'] ?? [];
        unset($_SESSION['error_error_messages']);

        $news_data_for_form = array_merge($news, $old_data);

        $categorias = $this->categoriaModel->getAllCategories();

        $data = [
            'title' => 'Editar Noticia',
            'page_title' => 'Editar Noticia',
            'news' => $news_data_for_form,
            'errors' => $errors,
            'old_data' => $old_data,
            'categorias' => $categorias
        ];

        $this->renderAdminView('news/edit', $data);
    }

    public function updateNews() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ./index.php?route=admin/news');
            exit();
        }

        $id_noticia = filter_input(INPUT_POST, 'id_noticia', FILTER_SANITIZE_NUMBER_INT);

        if (!is_numeric($id_noticia) || $id_noticia <= 0) {
            $_SESSION['error_message'] = "Error: ID de noticia inválido en el formulario de actualización.";
            header('Location: ./index.php?route=admin/news');
            exit();
        }

        $current_news = $this->noticiaModel->getNewsById($id_noticia, false);
        if (!$current_news) {
            $_SESSION['error_message'] = "Noticia a actualizar no encontrada.";
            header('Location: ./index.php?route=admin/news');
            exit();
        }

        $data_to_update = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'contenido' => trim($_POST['contenido'] ?? ''),
            'id_categoria' => filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT),
            'id_usuario_publicador' => $_SESSION['id_usuario'] ?? null,
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'imagen_principal' => $current_news['imagen_principal']
        ];

        $errors = [];

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

        $upload_dir = __DIR__ . '/../../public/uploads/noticias/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors['imagen_principal'] = 'Error al crear el directorio de subida.';
            }
        }

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
            } elseif ($_FILES['imagen_principal']['size'] > 5 * 1024 * 1024) {
                $errors['imagen_principal'] = 'La imagen es demasiado grande. Máximo 5MB.';
            } elseif (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $target_file)) {
                if (!empty($current_news['imagen_principal']) && file_exists(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                    unlink(__DIR__ . '/../../public/' . $current_news['imagen_principal']);
                }
                $data_to_update['imagen_principal'] = 'uploads/noticias/' . $file_name;
            } else {
                $errors['imagen_principal'] = 'Error al subir la nueva imagen.';
            }
        } elseif (isset($_POST['remove_imagen_principal']) && $_POST['remove_imagen_principal'] === '1') {
            if (!empty($current_news['imagen_principal']) && file_exists(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                if (!unlink(__DIR__ . '/../../public/' . $current_news['imagen_principal'])) {
                    error_log("Error al eliminar imagen antigua: " . __DIR__ . '/../../public/' . $current_news['imagen_principal']);
                }
            }
            $data_to_update['imagen_principal'] = null;
        }

        if (!empty($errors)) {
            $_SESSION['error_error_messages'] = $errors;
            $_SESSION['old_form_data'] = $_POST;
            if (isset($data_to_update['imagen_principal'])) {
                 $_SESSION['old_form_data']['imagen_principal'] = $data_to_update['imagen_principal'];
            }
            header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
            exit();
        }

        $result = $this->noticiaModel->updateNews($id_noticia, $data_to_update);

        if ($result) {
            $_SESSION['success_message'] = "Noticia actualizada exitosamente.";
            header('Location: ./index.php?route=admin/news');
            exit();
        } else {
            $_SESSION['error_error_messages'] = ['Error al actualizar la noticia en la base de datos. Por favor, inténtelo de nuevo.'];
            $_SESSION['old_form_data'] = $_POST;
            if (isset($data_to_update['imagen_principal'])) {
                $_SESSION['old_form_data']['imagen_principal'] = $data_to_update['imagen_principal'];
            }
            header('Location: ./index.php?route=admin/news/edit&id=' . $id_noticia);
            exit();
        }
    }

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

        if (!empty($newsToDelete['imagen_principal'])) {
            $image_path_on_disk = __DIR__ . '/../../public/' . $newsToDelete['imagen_principal'];
            if (file_exists($image_path_on_disk)) {
                if (!unlink($image_path_on_disk)) {
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
            header('Location: ./index.php?route=admin/manageComments');
            exit();
        }
        $result = $this->comentarioModel->softDeleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado lógicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar lógicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/manageComments');
        exit();
    }

  public function activateComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido.";
            header('Location: ./index.php?route=admin/manageComments'); // Ruta corregida
            exit();
        }
        $result = $this->comentarioModel->activarComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario activado.";
        } else {
            $_SESSION['error_message'] = "Error al activar el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/manageComments');
        exit();
    }

 public function deleteComment($id) {
        if (!is_numeric($id) || $id <= 0) {
            $_SESSION['error_message'] = "ID de comentario inválido para eliminación física.";
            header('Location: ./index.php?route=admin/manageComments'); // Ruta corregida
            exit();
        }
        $result = $this->comentarioModel->deleteComentario((int)$id);
        if ($result) {
            $_SESSION['success_message'] = "Comentario eliminado físicamente.";
        } else {
            $_SESSION['error_message'] = "Error al eliminar físicamente el comentario.";
        }
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? './index.php?route=admin/manageComments');
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