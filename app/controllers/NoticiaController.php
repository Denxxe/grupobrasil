<?php
// grupobrasil/app/controllers/NoticiaController.php

// Asegúrate de que las rutas a tus modelos sean correctas
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Like.php';
// Asume que también tienes un modelo para usuarios o acceso a su información de sesión
require_once __DIR__ . '/../models/Usuario.php'; 

class NoticiaController {
    private $noticiaModel;
    private $comentarioModel;
    private $likeModel;
    private $usuarioModel; // Para obtener datos del usuario si es necesario

    public function __construct() {
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario();
        $this->likeModel = new Like();
        $this->usuarioModel = new Usuario(); // Instancia el modelo de Usuario
    }

    /**
     * Muestra la página principal con un listado de todas las noticias activas.
     */
    public function index() {
        // Obtener todas las noticias activas, ordenadas por fecha de publicación
        $noticias = $this->noticiaModel->getAllNews(true, ['column' => 'fecha_publicacion', 'direction' => 'DESC']);
        
        // Incluir la vista que mostrará las noticias
        // Asegúrate de que la ruta a tu vista sea correcta, puede variar según tu estructura
        include __DIR__ . '/../views/noticias/index.php';
    }

    /**
     * Muestra una noticia específica y sus comentarios.
     * También maneja la lógica de "Me Gusta" y agregar comentarios.
     * @param int $id_noticia El ID de la noticia a mostrar.
     */
    public function show(int $id_noticia) {
        // Obtener la noticia activa
        $noticia = $this->noticiaModel->getNewsById($id_noticia, true);

        if (!$noticia) {
            // Manejar caso donde la noticia no existe o no está activa
            // Podrías redirigir a una página 404 o al índice de noticias
            header("Location: /noticias"); 
            exit();
        }

        // Obtener comentarios de la noticia (solo activos para usuarios comunes)
        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia($id_noticia, true);

        // Obtener el conteo de likes
        $totalLikes = $this->likeModel->contarLikesPorNoticia($id_noticia);

        // Verificar si el usuario actual ya dio like (asumiendo que tienes una sesión de usuario)
        $usuario_id = $_SESSION['user_id'] ?? null; // Obtén el ID del usuario de la sesión
        $usuarioDioLike = false;
        if ($usuario_id) {
            $usuarioDioLike = $this->likeModel->usuarioDioLike($id_noticia, $usuario_id);
        }
        
        // Incluir la vista que mostrará la noticia y sus detalles
        include __DIR__ . '/../views/noticias/show.php';
    }

    /**
     * Maneja la acción de agregar un comentario a una noticia.
     */
    public function addComment() {
        // Asegúrate de que solo usuarios autenticados puedan comentar
        if (!isset($_SESSION['user_id'])) {
            // Redirigir al login o mostrar error
            $_SESSION['error_message'] = "Debes iniciar sesión para comentar.";
            header("Location: /login"); 
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_noticia = $_POST['id_noticia'] ?? null;
            $contenido = trim($_POST['contenido'] ?? '');
            $id_usuario = $_SESSION['user_id']; // El ID del usuario logueado

            if ($id_noticia && $contenido) {
                $data = [
                    'id_noticia' => $id_noticia,
                    'id_usuario' => $id_usuario,
                    'contenido' => $contenido
                ];

                $newCommentId = $this->comentarioModel->agregarComentario($data);

                if ($newCommentId) {
                    $_SESSION['success_message'] = "Comentario agregado exitosamente.";
                } else {
                    $_SESSION['error_message'] = "Error al agregar el comentario.";
                }
            } else {
                $_SESSION['error_message'] = "El comentario no puede estar vacío.";
            }
            // Redirigir de vuelta a la noticia
            header("Location: /noticias/show/{$id_noticia}");
            exit();
        }
        // Si no es un POST, redirigir al índice de noticias o a una página de error
        header("Location: /noticias");
        exit();
    }

    /**
     * Maneja la acción de dar o quitar "Me Gusta".
     */
    public function toggleLike() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = "Debes iniciar sesión para dar 'Me Gusta'.";
            header("Location: /login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_noticia = $_POST['id_noticia'] ?? null;
            $id_usuario = $_SESSION['user_id'];

            if ($id_noticia) {
                if ($this->likeModel->usuarioDioLike($id_noticia, $id_usuario)) {
                    // Si ya dio like, quitarlo
                    if ($this->likeModel->quitarLike($id_noticia, $id_usuario)) {
                        $_SESSION['success_message'] = "Se ha quitado el 'Me Gusta'.";
                    } else {
                        $_SESSION['error_message'] = "Error al quitar el 'Me Gusta'.";
                    }
                } else {
                    // Si no ha dado like, añadirlo
                    if ($this->likeModel->darLike($id_noticia, $id_usuario)) {
                        $_SESSION['success_message'] = "¡'Me Gusta' añadido!";
                    } else {
                        $_SESSION['error_message'] = "Error al añadir el 'Me Gusta'.";
                    }
                }
            } else {
                $_SESSION['error_message'] = "ID de noticia no válido.";
            }
            header("Location: /noticias/show/{$id_noticia}");
            exit();
        }
        header("Location: /noticias");
        exit();
    }
}