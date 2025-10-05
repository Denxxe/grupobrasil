<?php
// grupobrasil/app/views/layouts/subadmin_layout.php

// Asegúrate de que NO haya NADA (espacios, saltos de línea, BOM) antes de esta etiqueta PHP.

// Asignar los mensajes de sesión a variables locales para usar en los data-attributes.
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// IMPORTANTE: Limpiar las variables de sesión INMEDIATAMENTE después de haberlas capturado.
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Asignar títulos por defecto si no vienen del controlador
$title = $title ?? 'Sub-Admin Dashboard';
$page_title = $page_title ?? 'Dashboard de Sub-Administración';

$content_view = $content_view ?? ''; // Fallback por si acaso, aunque el controlador debería definirla.

?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Grupo Brasil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        vinotinto: {
                            50: '#FDF0F0', 100: '#FAE3E3', 200: '#E6B8B8', 300: '#D28E8E',
                            400: '#BE6464', 500: '#A52A2A', 600: '#8F2424', 700: '#6D071A',
                            800: '#4D0512', 900: '#2E0309',
                        },
                        accentgold: '#D4AF37',
                        accentcream: '#F5F5DC',
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link href="./css/admin_styles.css?v=<?php echo time(); ?>" rel="stylesheet"> 

</head>
<body class="h-full flex"
      data-success-message="<?php echo htmlspecialchars($success_message); ?>"
      data-error-message="<?php echo htmlspecialchars($error_message); ?>">

    <aside id="sidebar" class="sidebar bg-vinotinto-700 text-gray-100 flex flex-col p-4 rounded-r-lg shadow-lg">
        <div class="flex items-center justify-center p-4 border-b border-vinotinto-800 overflow-hidden">
            <h1 class="text-2xl font-bold text-accentgold sidebar-text">Sub-AdminPanel</h1>
        </div>
        <nav class="flex-grow mt-4">
            <ul class="space-y-2">
                <li>
                    <a href="./index.php?route=subadmin/dashboard" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                        <i class="fas fa-tachometer-alt mr-3 sidebar-icon"></i> <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="./index.php?route=noticias" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                        <i class="fas fa-newspaper mr-3 sidebar-icon"></i> <span class="sidebar-text">Noticias</span>
                    </a>
                </li>
                <li>
                    <a href="./index.php?route=subadmin/comments" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                        <i class="fas fa-comments mr-3 sidebar-icon"></i> <span class="sidebar-text">Comentarios</span>
                    </a>
                </li>
                <li>
                    <a href="./index.php?route=subadmin/notifications" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                        <i class="fas fa-bell mr-3 sidebar-icon"></i> <span class="sidebar-text">Notificaciones</span>
                    </a>
                </li>
                <li>
                    <a href="./index.php?route=subadmin/reports" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                        <i class="fas fa-chart-line mr-3 sidebar-icon"></i> <span class="sidebar-text">Reportes</span>
                    </a>
                </li>
                </ul>
        </nav>
        <div class="mt-auto p-4 border-t border-vinotinto-800">
            <button id="sidebarCollapseToggle" class="hidden lg:flex items-center justify-center w-full text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200 py-2 mb-2">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>
            <a href="./index.php?route=login/logout" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
                <i class="fas fa-sign-out-alt mr-3 sidebar-icon"></i> <span class="sidebar-text">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <div id="content" class="content flex-grow p-6 flex flex-col">
        <header class="flex items-center justify-between bg-white p-4 shadow-md rounded-lg mb-6 lg:hidden flex-shrink-0">
            <button id="sidebarToggle" class="text-gray-600 focus:outline-none focus:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h2 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h2>
            <div class="flex items-center">
                <span class="text-gray-700 mr-2">
                    <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>
                </span>
                <i class="fas fa-user-circle text-gray-500 text-2xl"></i>
            </div>
        </header>

        <h2 class="text-3xl font-semibold text-gray-800 mb-6 hidden lg:block flex-shrink-0"><?php echo htmlspecialchars($page_title); ?></h2>

        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
            <div id="successToast" class="toast align-items-center text-bg-success border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="successToastBody"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <div id="errorToast" class="toast align-items-center text-bg-danger border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="errorToastBody"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <main class="flex-grow">
    <?php
    if (isset($content_view_path) && file_exists($content_view_path)) {
        include_once $content_view_path;
    } else {
        echo '<div class="alert alert-danger" role="alert">
                Error: La vista de contenido no se pudo cargar.<br>
                Ruta: ' . htmlspecialchars($content_view_path ?? 'N/A') . '
              </div>';
        error_log("Error: La vista de contenido '$content_view_path' no existe o no está definida.");
    }
    ?>
</main>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script src="./js/admin_dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="./js/toast_initializer.js?v=<?php echo time(); ?>"></script>
    </body>
</html>