<?php
// grupobrasil/app/controllers/NoticiaController.php

require_once __DIR__ . '/../controllers/AppController.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../models/NoticiaVisibilidad.php';

class NoticiaController extends AppController {
    private $noticiaModel;
    private $comentarioModel;
    private $likeModel;

    public function __construct($noticiaModel, $comentarioModel, $likeModel) {
        parent::__construct();
        $this->noticiaModel    = $noticiaModel;
        $this->comentarioModel = $comentarioModel;
        $this->likeModel       = $likeModel;
    }

    /**
     * Listado de noticias visibles para usuarios.
     */
    public function index() {
        $noticias = $this->noticiaModel->getAllNews(true);

        // Filtrar por visibilidad según el usuario actual
        $user_id = $_SESSION['id_usuario'] ?? null;
        $visModel = new NoticiaVisibilidad();
        $filtered = [];

        foreach ($noticias as $n) {
            $id_noticia = (int)($n['id_noticia'] ?? $n['id'] ?? 0);
            if ($user_id) {
                if ($visModel->canUserSeeNews($id_noticia, (int)$user_id)) {
                    $filtered[] = $n;
                }
            } else {
                // visitante anónimo: mostrar solo noticias sin reglas de visibilidad
                $vis = $visModel->getVisibilityForNews($id_noticia);
                if (empty($vis['calles']) && empty($vis['habitantes'])) {
                    $filtered[] = $n;
                }
            }
        }

        $this->loadView('noticias/index', [
            'page_title' => 'Noticias de la Comunidad',
            'noticias'   => $filtered
        ]);
    }

    /**
     * Muestra el detalle de una noticia por ID.
     */
    public function show($id_noticia) {
        $id_noticia = (int)$id_noticia;

        if ($id_noticia <= 0) {
            $this->setErrorMessage("La noticia no existe o no está activa.");
            $this->redirect('noticias');
        }

        $noticia = $this->noticiaModel->getNewsById($id_noticia, true);

        if (!$noticia) {
            $this->setErrorMessage("La noticia no existe o no está activa.");
            $this->redirect('noticias');
        }

        // Verificar visibilidad
        $user_id = $_SESSION['id_usuario'] ?? null;
        $visModel = new NoticiaVisibilidad();
        $canSee = false;
        if ($user_id) {
            $canSee = $visModel->canUserSeeNews($id_noticia, (int)$user_id);
        } else {
            $vis = $visModel->getVisibilityForNews($id_noticia);
            $canSee = (empty($vis['calles']) && empty($vis['habitantes']));
        }

        if (!$canSee) {
            $this->setErrorMessage("No tienes permisos para ver esta noticia.");
            $this->redirect('noticias');
        }

        $comentarios    = $this->comentarioModel->obtenerComentariosPorNoticia($id_noticia, true);
        $totalLikes     = $this->likeModel->contarLikesPorNoticia($id_noticia);
        $usuario_id     = $_SESSION['id_usuario'] ?? null;
        $usuarioDioLike = $usuario_id
            ? $this->likeModel->usuarioDioLike($id_noticia, $usuario_id)
            : false;

        $this->loadView('noticias/show', [
            'page_title'     => $noticia['titulo'] ?? 'Detalle Noticia',
            'noticia'        => $noticia,
            'comentarios'    => $comentarios,
            'totalLikes'     => $totalLikes,
            'usuarioDioLike' => $usuarioDioLike
        ]);
    }

    /**
     * Agregar comentario a una noticia.
     */
    public function addComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('noticias');
        }

        // Verificar token CSRF
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\CsrfHelper::validateToken($csrf)) {
            $this->setErrorMessage('Token CSRF inválido. Por favor actualiza la página e intenta de nuevo.');
            $this->redirect('noticias');
        }

        $id_noticia = (int)($_POST['id_noticia'] ?? 0);
        $contenido  = trim($_POST['contenido'] ?? '');
        $usuario_id = $_SESSION['id_usuario'] ?? null;

        // Validaciones de contenido: mínimo 3 caracteres, máximo 800
        $len = function_exists('mb_strlen') ? mb_strlen($contenido) : strlen($contenido);
        if ($id_noticia <= 0 || !$usuario_id || $len < 3 || $len > 800) {
            $this->setErrorMessage("El comentario debe tener entre 3 y 800 caracteres.");
            $this->redirect('noticias/show/' . $id_noticia);
        }

        $data = [
            'id_noticia' => $id_noticia,
            'id_usuario' => $usuario_id,
            'contenido'  => $contenido
        ];

        $exito = $this->comentarioModel->agregarComentario($data);

        if ($exito) {
            $this->setSuccessMessage("Comentario agregado exitosamente.");
            // Crear notificación al autor de la noticia si no es el mismo que comenta
            try {
                $noticia = $this->noticiaModel->getNewsById($id_noticia, false);
                if ($noticia && isset($noticia['id_usuario'])) {
                    $autor = (int)$noticia['id_usuario'];
                    if ($autor !== (int)$usuario_id) {
                        $notModel = new Notificacion();
                        $mensaje = 'Nuevo comentario en tu noticia: ' . ($noticia['titulo'] ?? 'Sin título');
                        $notModel->crearNotificacion($autor, $usuario_id, 'comment', $mensaje, $id_noticia);
                    }
                }
                // Si el que comenta es Líder (2) o Jefe Familiar (3), notificar también a Jefes del consejo comunal (rol 1)
                $rolActual = $_SESSION['id_rol'] ?? null;
                if ($rolActual && in_array($rolActual, [2,3])) {
                    $usuarioModel = new Usuario();
                    $admins = $usuarioModel->getAllFiltered(['id_rol' => 1]);
                    foreach ($admins as $a) {
                        if (!empty($a['id_usuario'])) {
                            $notModel->crearNotificacion((int)$a['id_usuario'], (int)$usuario_id, 'comment', 'Se ha hecho un comentario en una noticia', $id_noticia);
                        }
                    }
                }
            } catch (\Throwable $e) { error_log('Error creando notificación de comentario: ' . $e->getMessage()); }
                // Además, si el usuario que dio like es rol 2 o 3, notificar a administradores
                $rolActual = $_SESSION['id_rol'] ?? null;
                if ($rolActual && in_array($rolActual, [2,3])) {
                    try {
                        $usuarioModel = new Usuario();
                        $admins = $usuarioModel->getAllFiltered(['id_rol' => 1]);
                        $notModel2 = new Notificacion();
                        foreach ($admins as $a) {
                            if (!empty($a['id_usuario'])) {
                                $notModel2->crearNotificacion((int)$a['id_usuario'], (int)$usuario_id, 'like', 'Se ha dado un Me Gusta en una noticia', $id_noticia);
                            }
                        }
                    } catch (\Throwable $e) { error_log('Error notificando likes a admins: ' . $e->getMessage()); }
                }
        } else {
            $this->setErrorMessage("No se pudo agregar el comentario.");
        }

        $this->redirect('noticias/show/' . $id_noticia);
    }

    public function toggleLike() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('noticias');
        }

        // Verificar token CSRF
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\CsrfHelper::validateToken($csrf)) {
            $this->setErrorMessage('Token CSRF inválido. Por favor actualiza la página e intenta de nuevo.');
            $this->redirect('noticias');
        }

        $id_noticia = (int)($_POST['id_noticia'] ?? 0);
        $usuario_id = $_SESSION['id_usuario'] ?? null;

        if ($id_noticia <= 0 || !$usuario_id) {
            $this->setErrorMessage("Datos inválidos para el like.");
            $this->redirect('noticias');
        }

        $usuarioDioLike = $this->likeModel->usuarioDioLike($id_noticia, $usuario_id);

        if ($usuarioDioLike) {
            $exito = $this->likeModel->quitarLike($id_noticia, $usuario_id);
            if ($exito) {
                $this->setSuccessMessage("Has quitado tu 'Me Gusta'.");
            } else {
                $this->setErrorMessage("Error al quitar el 'Me Gusta'.");
            }
        } else {
            $exito = $this->likeModel->darLike($id_noticia, $usuario_id);
            if ($exito) {
                $this->setSuccessMessage("Te ha gustado esta noticia.");
                    // Notificar al autor de la noticia
                    try {
                        $noticia = $this->noticiaModel->getNewsById($id_noticia, false);
                        if ($noticia && isset($noticia['id_usuario'])) {
                            $autor = (int)$noticia['id_usuario'];
                            if ($autor !== (int)$usuario_id) {
                                $notModel = new Notificacion();
                                $mensaje = 'Tu noticia recibió un Me Gusta de ' . ($_SESSION['nombre_usuario'] ?? 'alguien');
                                $notModel->crearNotificacion($autor, $usuario_id, 'like', $mensaje, $id_noticia);
                            }
                        }
                    } catch (\Throwable $e) { error_log('Error creando notificación de like: ' . $e->getMessage()); }
            } else {
                $this->setErrorMessage("Error al dar 'Me Gusta'.");
            }
        }

        $this->redirect('noticias/show/' . $id_noticia);
    }

    /**
     * Editar un comentario (solo autor) - POST
     * Ruta esperada: ?route=noticias/edit-comment (POST)
     */
    public function editComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('noticias');
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!\CsrfHelper::validateToken($csrf)) {
            $this->setErrorMessage('Token CSRF inválido.');
            $this->redirect('noticias');
        }

        $id_comentario = (int)($_POST['id_comentario'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');
        $usuario_id = $_SESSION['id_usuario'] ?? null;
        $id_noticia = (int)($_POST['id_noticia'] ?? 0);

        if ($id_comentario <= 0 || !$usuario_id || $id_noticia <= 0) {
            $this->setErrorMessage('Datos inválidos para editar el comentario.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        $comentario = $this->comentarioModel->obtenerComentariosPorNoticia($id_noticia, true);
        // Obtener el comentario especifico para verificar autor
        $found = null;
        foreach ($comentario as $c) { if ((int)$c['id_comentario'] === $id_comentario) { $found = $c; break; } }

        if (!$found) {
            $this->setErrorMessage('Comentario no encontrado o ya no está activo.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        if ((int)$found['id_usuario'] !== (int)$usuario_id) {
            $this->setErrorMessage('No tienes permiso para editar este comentario.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        // Validación de longitud: 3..800
    $len = function_exists('mb_strlen') ? mb_strlen($contenido) : strlen($contenido);
    if ($len < 3 || $len > 800) {
            // Si es AJAX responder JSON con error
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El comentario debe tener entre 3 y 800 caracteres.']);
                exit;
            }
            $this->setErrorMessage('El comentario debe tener entre 3 y 800 caracteres.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        $ok = $this->comentarioModel->updateComentario($id_comentario, $contenido);

        // Detectar petición AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Comentario actualizado.', 'id_comentario' => $id_comentario, 'contenido' => $contenido]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el comentario.']);
            }
            exit;
        }

        if ($ok) $this->setSuccessMessage('Comentario actualizado.'); else $this->setErrorMessage('Error al actualizar el comentario.');
        $this->redirect('noticias/show/' . $id_noticia);
    }

    /**
     * Eliminar (soft-delete) un comentario (solo autor) - POST
     * Ruta: ?route=noticias/delete-comment (POST)
     */
    public function deleteComment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('noticias');
        }
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\CsrfHelper::validateToken($csrf)) {
            $this->setErrorMessage('Token CSRF inválido.');
            $this->redirect('noticias');
        }

        $id_comentario = (int)($_POST['id_comentario'] ?? 0);
        $usuario_id = $_SESSION['id_usuario'] ?? null;
        $id_noticia = (int)($_POST['id_noticia'] ?? 0);

        if ($id_comentario <= 0 || !$usuario_id || $id_noticia <= 0) {
            $this->setErrorMessage('Datos inválidos para eliminar el comentario.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        // Verificar autor
        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia($id_noticia, true);
        $found = null;
        foreach ($comentarios as $c) { if ((int)$c['id_comentario'] === $id_comentario) { $found = $c; break; } }

        if (!$found) {
            $this->setErrorMessage('Comentario no encontrado o ya inactivo.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        if ((int)$found['id_usuario'] !== (int)$usuario_id) {
            $this->setErrorMessage('No tienes permiso para eliminar este comentario.');
            $this->redirect('noticias/show/' . $id_noticia);
        }

        $ok = $this->comentarioModel->softDeleteComentario($id_comentario);

        // Detectar petición AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Comentario eliminado.', 'id_comentario' => $id_comentario]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el comentario.']);
            }
            exit;
        }

        if ($ok) $this->setSuccessMessage('Comentario eliminado.'); else $this->setErrorMessage('Error al eliminar el comentario.');
        $this->redirect('noticias/show/' . $id_noticia);
    }


}