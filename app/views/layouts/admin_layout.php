<?php
// grupobrasil/app/views/layouts/admin_layout.php

// Asegúrate de que NO haya NADA (espacios, saltos de línea, BOM) antes de esta etiqueta PHP.

// 1. Asignar los mensajes de sesión a variables locales para usar en los data-attributes.
// Si no existen en la sesión, se asignan como cadenas vacías.
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// 2. IMPORTANTE: Limpiar las variables de sesión INMEDIATAMENTE después de haberlas capturado
// y ANTES de que cualquier otra parte del código PHP (como renderAdminView) pueda borrarlas.
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Asignar títulos por defecto si no vienen del controlador
$title = $title ?? 'Admin Dashboard';
$page_title = $page_title ?? 'Dashboard de Administración';

// La variable $content_view debe ser establecida por el controlador ANTES de que se incluya este layout.
$content_view = $content_view ?? ''; // Fallback por si acaso, aunque el controlador debería definirla.

// Lógica para determinar la ruta actual y aplicar clases "active"
$current_route = $_GET['route'] ?? 'admin/dashboard';
$is_user_management_section = str_starts_with($current_route, 'admin/users/');
$is_personas_active = $current_route === 'admin/users/personas';
$is_usuarios_active = $current_route === 'admin/users/usuarios';
// También consideramos "create" y "edit" como activos dentro de su respectiva sección
if ($current_route === 'admin/users/create') {
    $is_personas_active = true;
}
if ($current_route === 'admin/users/edit') {
    $is_usuarios_active = true;
}
$is_any_user_management_active = $is_personas_active || $is_usuarios_active;

?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($title); ?> | Grupo Brasil</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="./css/admin_styles.css?v=<?php echo time(); ?>" rel="stylesheet"> 

</head>
<body class="h-full flex"
  data-success-message="<?php echo htmlspecialchars($success_message); ?>"
  data-error-message="<?php echo htmlspecialchars($error_message); ?>">

<aside id="sidebar" class="sidebar bg-vinotinto-700 text-gray-100 flex flex-col p-4 rounded-r-lg shadow-lg">
<div class="flex items-center justify-center p-4 border-b border-vinotinto-800 overflow-hidden">
<h1 class="text-2xl font-bold text-accentgold sidebar-text"><?php echo $_SESSION['nombre_completo'] ?></h1>
</div>
<nav class="flex-grow mt-4">
<ul class="space-y-2">
<li>
<a href="./index.php?route=admin/dashboard" 
                       class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200 
                       <?php echo $current_route === 'admin/dashboard' ? 'bg-vinotinto-600 text-white font-semibold' : ''; ?>">
<i class="fas fa-tachometer-alt mr-3 sidebar-icon"></i> <span class="sidebar-text">Dashboard</span>
</a>
</li>
                
                <!-- Bloque de Gestión de Usuarios (Expandible) -->
                <li>
                    <!-- Título del Dropdown -->
                    <button type="button" id="usersDropdownToggle" 
                            class="flex items-center justify-between w-full px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200 
                            <?php echo $is_any_user_management_active ? 'bg-vinotinto-600 text-white font-semibold' : ''; ?>">
                        <span class="flex items-center">
                            <i class="fas fa-users mr-3 sidebar-icon"></i> <span class="sidebar-text">Gestión Comunitaria</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs ml-auto transition-transform duration-300 transform <?php echo $is_any_user_management_active ? 'rotate-180' : ''; ?>"></i>
                    </button>
                    
                    <!-- Submenú -->
                    <ul id="usersDropdownMenu" class="pl-4 mt-1 space-y-1 overflow-hidden transition-all duration-300 
                        <?php echo $is_any_user_management_active ? 'max-h-40' : 'max-h-0'; ?>">
                        <li>
                            <a href="./index.php?route=admin/users/personas" 
                               class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200 
                               <?php echo $is_personas_active ? 'bg-vinotinto-500 text-white font-semibold' : ''; ?>">
                                <i class="fas fa-user-friends mr-3 sidebar-icon text-sm"></i> <span class="sidebar-text">Habitantes</span>
                            </a>
                        </li>
                        <li>
                            <a href="./index.php?route=admin/users/usuarios" 
                               class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200
                               <?php echo $is_usuarios_active ? 'bg-vinotinto-500 text-white font-semibold' : ''; ?>">
                                <i class="fas fa-user-shield mr-3 sidebar-icon text-sm"></i> <span class="sidebar-text">Líderes</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Fin Bloque de Gestión de Usuarios -->
                
<li>
<a href="./index.php?route=admin/news" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
<i class="fas fa-newspaper mr-3 sidebar-icon"></i> <span class="sidebar-text">Noticias</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/comments" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
<i class="fas fa-comments mr-3 sidebar-icon"></i> <span class="sidebar-text">Comentarios</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/notifications" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
<i class="fas fa-bell mr-3 sidebar-icon"></i> <span class="sidebar-text">Notificaciones</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/reports" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
<i class="fas fa-chart-line mr-3 sidebar-icon"></i> <span class="sidebar-text">Reportes</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/settings" class="flex items-center px-4 py-2 text-vinotinto-100 hover:bg-vinotinto-600 hover:text-white rounded-md transition duration-200">
<i class="fas fa-cog mr-3 sidebar-icon"></i> <span class="sidebar-text">Configuración</span>
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
// La vista específica del contenido (ej. users/edit.php, dashboard.php)
// se incluye aquí. Asegúrate de que $content_view esté bien definido en el controlador.
if (isset($content_view) && file_exists($content_view)) {
include_once $content_view;
} else {
echo '<div class="alert alert-danger" role="alert">Error: La vista de contenido no se pudo cargar.</div>';
error_log("Error: La vista de contenido '$content_view' no existe o no está definida.");
}
?>
</main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script src="./js/admin_dashboard.js?v=<?php echo time(); ?>"></script>
<script src="./js/toast_initializer.js?v=<?php echo time(); ?>"></script>
<script src="./js/admin_news_scripts.js?v=<?php echo time(); ?>"></script>
<script src="./js/edit_user_form.js?v=<?php echo time(); ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para el colapso del menú de Gestión Humana
            const usersToggle = document.getElementById('usersDropdownToggle');
            const usersMenu = document.getElementById('usersDropdownMenu');
            const usersChevron = usersToggle.querySelector('i.fa-chevron-down');
            
            // Si el menú está abierto inicialmente (por PHP), ajusta el max-height para la transición
            if (usersMenu.classList.contains('max-h-40')) {
                usersMenu.style.maxHeight = usersMenu.scrollHeight + "px";
            }
            
            usersToggle.addEventListener('click', function() {
                const isExpanded = usersMenu.classList.contains('max-h-40');
                
                if (isExpanded) {
                    // Cerrar
                    usersMenu.style.maxHeight = '0';
                    usersChevron.classList.remove('rotate-180');
                    usersMenu.classList.remove('max-h-40');
                    usersMenu.classList.add('max-h-0');
                } else {
                    // Abrir
                    usersMenu.style.maxHeight = usersMenu.scrollHeight + "px";
                    usersChevron.classList.add('rotate-180');
                    usersMenu.classList.remove('max-h-0');
                    usersMenu.classList.add('max-h-40');
                }
            });
            
            // Mejorar el manejo de max-height después de que se complete la transición de apertura
            usersMenu.addEventListener('transitionend', function() {
                if (usersMenu.style.maxHeight !== '0px') {
                    usersMenu.style.maxHeight = 'none'; // Permitir que el contenido crezca si es necesario
                }
            });

            // Restablecer el max-height si se colapsa la barra lateral
            const sidebarToggle = document.getElementById('sidebarCollapseToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (usersMenu.classList.contains('max-h-40')) {
                        usersMenu.style.maxHeight = usersMenu.scrollHeight + "px";
                    }
                });
            }
        });
    </script>
</body>
</html>
