<?php
   session_start();
   if (isset($_SESSION['id_usuario'])) {
       header('Location: public/dashboard.php');
       exit();
   }
   
   if(!isset($_SESSION['id_usuario'])) {
        session_destroy();
        header('Location: public/login.php');
        exit();
   }

