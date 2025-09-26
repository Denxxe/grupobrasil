<?php
// grupobrasil/app/controllers/NoticiaController.php

require_once __DIR__ . '/AppController.php'; 
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../models/Usuario.php'; 

class NoticiaController extends AppController {
    private $noticiaModel;
    private $comentarioModel;
    private $likeModel;
    private $usuarioModel;

    public function __construct() {
        parent::__construct(); // Importante: inicializa AppController
        $this->noticiaModel = new Noticia();
        $this->comentarioModel = new Comentario();
        $this->likeModel = new Like();
        $this->usuarioModel = new Usuario();
    }

    /**
     * Lista todas las noticias
     */
    public function index() {
        $noticias = $this->noticiaModel->getAllNews(true, [
            'column' => 'fecha_publicacion', 
            'direction' => 'DESC'
        ]);

        $this->loadView('noticias/index', [
            'page_title' => 'Últimas Noticias',
            'noticias'   => $noticias
        ]);
    }

    /**
     * Muestra una noticia específica con sus comentarios y likes
     */
    public function show(int $id_noticia) {
        $noticia = $this->noticiaModel->getNewsById($id_noticia, true);

        if (!$noticia) {
            $this->setErrorMessage("La noticia no existe o no está disponible.");
            $this->redirect('noticias');
        }

        $comentarios = $this->comentarioModel->obtenerComentariosPorNoticia($id_noticia, true);
        $totalLikes  = $this->likeModel->contarLikesPorNoticia($id_noticia);

        $usuario_id = $_SESSION['id_usuario'] ?? null;
        $usuarioDioLike = $usuario_id 
            ? $this->likeModel->usuarioDioLike($id_noticia, $usuario_id)
            : false;

        $this->loadView('noticias/show', [
            'page_title'     => $noticia['titulo'],
            'noticia'        => $noticia,
            'comentarios'    => $comentarios,
            'totalLikes'     => $totalLikes,
            'usuarioDioLike' => $usuarioDioLike
        ]);
    }

    /**
     * Agregar comentario
     */
    public function addComment() {
        if (!isset($_SESSION['id_usuario'])) {
            $this->setErrorMessage("Debes iniciar sesión para comentar.");
            $this->redirect('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_noticia = $_POST['id_noticia'] ?? null;
            $contenido  = trim($_POST['contenido'] ?? '');
            $id_usuario = $_SESSION['id_usuario'];

            if ($id_noticia && $contenido) {
                $data = [
                    'id_noticia' => $id_noticia,
                    'id_usuario' => $id_usuario,
                    'contenido'  => $contenido
                ];

                $newCommentId = $this->comentarioModel->agregarComentario($data);

                if ($newCommentId) {
                    $this->setSuccessMessage("Comentario agregado exitosamente.");
                } else {
                    $this->setErrorMessage("Error al agregar el comentario.");
                }
            } else {
                $this->setErrorMessage("El comentario no puede estar vacío.");
            }

            $this->redirect("noticias/show/{$id_noticia}");
        }

        $this->redirect('noticias');
    }

    /**
     * Like / Unlike
     */
    public function toggleLike() {
        if (!isset($_SESSION['id_usuario'])) {
            $this->setErrorMessage("Debes iniciar sesión para dar 'Me Gusta'.");
            $this->redirect('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_noticia = $_POST['id_noticia'] ?? null;
            $id_usuario = $_SESSION['id_usuario'];

            if ($id_noticia) {
                if ($this->likeModel->usuarioDioLike($id_noticia, $id_usuario)) {
                    if ($this->likeModel->quitarLike($id_noticia, $id_usuario)) {
                        $this->setSuccessMessage("Se ha quitado el 'Me Gusta'.");
                    } else {
                        $this->setErrorMessage("Error al quitar el 'Me Gusta'.");
                    }
                } else {
                    if ($this->likeModel->darLike($id_noticia, $id_usuario)) {
                        $this->setSuccessMessage("¡'Me Gusta' añadido!");
                    } else {
                        $this->setErrorMessage("Error al añadir el 'Me Gusta'.");
                    }
                }
            } else {
                $this->setErrorMessage("ID de noticia no válido.");
            }

            $this->redirect("noticias/show/{$id_noticia}");
        }

        $this->redirect('noticias');
    }
}
