<?php
// Este layout se usa para LOGIN, o para vistas que no requieren el sidebar completo, 
// como la configuración inicial obligatoria.

// Variables pasadas por AppController (siempre disponibles con extract($data))
$title = $title ?? 'Acceso al Sistema';
$page_title = $page_title ?? 'Bienvenido';

// Nota: En este layout minimalista, solo incluimos las variables necesarias para el setup_profile
// La vista de setup_profile ya tiene sus propios includes de Bootstrap y estilos.

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Grupo Brasil</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        body {
            background-color: #e9ecef; /* Fondo suave para destacar el card */
        }
        .setup-card {
            max-width: 800px;
            margin-top: 50px;
            margin-bottom: 50px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Sombra más pronunciada */
            border: none;
        }
        .setup-card-header {
            background-color: #007bff; /* Color primario de Bootstrap */
            color: white;
            border-bottom: none;
            border-radius: 0.25rem 0.25rem 0 0;
            padding: 1.5rem;
        }
        .form-section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
    </style>
    <style>
        body {
            background-color: #f8f9fa; /* Fondo gris claro */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="content-wrapper w-full">
    <?php
    // Incluye la vista de contenido
    if (isset($content_view_path) && file_exists($content_view_path)) {
        include_once $content_view_path;
    } else {
        echo '<div style="color: red; text-align: center;">Error al cargar la vista.</div>';
    }
    ?>
    </div>
    
    </body>
</html>