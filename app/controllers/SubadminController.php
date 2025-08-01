<?php
// grupobrasil/app/controllers/SubadminController.php

require_once __DIR__ . '/AppController.php'; // Asegúrate de incluir AppController
require_once __DIR__ . '/../models/Usuario.php';

// Hereda de AppController para usar loadView
class SubadminController extends AppController { 
    private $usuarioModel;

    public function __construct() {
        parent::__construct(); // Llama al constructor del padre
        $this->usuarioModel = new Usuario();
        
        // Verificación de rol aquí o en un middleware/router centralizado.
        // Si no tienes un router con middleware, puedes añadir una verificación simple:
        if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) { // 2 para sub-administrador
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

        // Usa la función loadView del AppController
        $this->loadView('subadmin/dashboard', $data); 
    }
}