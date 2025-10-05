<?php
// grupobrasil/app/controllers/NoticiaController.php

require_once __DIR__ . '/../controllers/AppController.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../models/Usuario.php';

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

        $this->loadView('noticias/index', [
            'page_title' => 'Noticias de la Comunidad',
            'noticias'   => $noticias
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

        $id_noticia = (int)($_POST['id_noticia'] ?? 0);
        $contenido  = trim($_POST['contenido'] ?? '');
        $usuario_id = $_SESSION['id_usuario'] ?? null;

        if ($id_noticia <= 0 || !$usuario_id || empty($contenido)) {
            $this->setErrorMessage("Datos inválidos para el comentario.");
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
        } else {
            $this->setErrorMessage("No se pudo agregar el comentario.");
        }

        $this->redirect('noticias/show/' . $id_noticia);
    }

    /**
     * Alterna el "like" de una noticia por parte del usuario autenticado.
     */
    public function toggleLike() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
            } else {
                $this->setErrorMessage("Error al dar 'Me Gusta'.");
            }
        }

        $this->redirect('noticias/show/' . $id_noticia);
    }


}
