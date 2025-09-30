<?php
// grupobrasil/app/controllers/SubadminController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Noticia.php';
require_once __DIR__ . '/../models/Comentario.php';
require_once __DIR__ . '/AppController.php';

class SubadminController extends AppController { 
    private $usuarioModel;
    private $noticiaModel;  
    private $comentarioModel; 

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->noticiaModel = new Noticia();     
        $this->comentarioModel = new Comentario();
        
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
            header('Location:./index.php?route=login&error=acceso_denegado');
            exit();
        }
    }

    public function dashboard() {
        $usuariosAsignados = []; 

        $data = [
            'page_title' => 'Dashboard de Sub-Administrador',
            'usuariosAsignados' => $usuariosAsignados 
        ];

        $this->loadView('subadmin/dashboard', $data); 
    }

    public function reports() {-
    
        $noticias = $this->noticiaModel->getAll();
        $comentarios = $this->comentarioModel->getAll();

        $data = [
            'page_title' => 'Reportes de Subadministración',
            'noticias' => $noticias,
            'comentarios' => $comentarios
        ];

        $this->loadView('subadmin/reports', $data);
    }
}