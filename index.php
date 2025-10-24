<?php
   session_start();
   
   if (isset($_SESSION['id_usuario'])) {
       header('Location: public/dashboard.php');
       exit();
   } else {
        session_destroy();
        header('Location: public/login.php');
        exit();
   }
