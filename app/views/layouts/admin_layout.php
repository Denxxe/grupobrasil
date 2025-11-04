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
$is_user_management_section = (strpos($current_route, 'admin/users/') === 0);
$is_personas_active = $current_route === 'admin/users/personas';
$is_jefes_familia_active = $current_route === 'admin/users/jefes-familia';
$is_lideres_active = $current_route === 'admin/users/lideres';
// También consideramos "create" y "edit" como activos dentro de su respectiva sección
if ($current_route === 'admin/users/create') {
    $is_personas_active = true;
}
if ($current_route === 'admin/users/edit') {
    $is_jefes_familia_active = true;
}
$is_any_user_management_active = $is_personas_active || $is_jefes_familia_active || $is_lideres_active;
// Pagos/Beneficios
$is_pagos_active = strpos($current_route, 'admin/pagos') === 0 || strpos($current_route, 'admin/reportes/pagos') === 0;

?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Grupo Brasil</title>

    <!-- Actualizado orden de carga y configuración de Tailwind mejorada -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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
    <link href="./css/admin_styles.css?v=<?php echo time(); ?>" rel="stylesheet"> 
</head>
<body class="h-full flex"
  data-success-message="<?php echo htmlspecialchars($success_message); ?>"
  data-error-message="<?php echo htmlspecialchars($error_message); ?>">

<!-- Sidebar rediseñada con mejor espaciado y tipografía -->
<aside id="sidebar" class="sidebar bg-vinotinto-700 text-gray-100 flex flex-col p-4 rounded-r-lg shadow-lg">
<div class="flex items-center justify-center p-5 border-b border-white border-opacity-10">
<h1 class="text-xl font-semibold text-white sidebar-text tracking-tight"><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Admin'); ?></h1>
</div>

<nav class="flex-grow mt-2 px-2">
<ul class="space-y-1">
<li>
<a href="./index.php?route=admin/dashboard" 
    class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/dashboard' ? ' active-link' : ''; ?>">
<i class="fas fa-tachometer-alt sidebar-icon mr-3"></i> 
<span class="sidebar-text">Dashboard</span>
</a>
</li>

<!-- Menú desplegable mejorado con mejor UX -->
<li>
<button type="button" id="usersDropdownToggle" 
    class="flex items-center justify-between w-full px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $is_any_user_management_active ? ' active-link' : ''; ?>">
<span class="flex items-center">
<i class="fas fa-users sidebar-icon mr-3"></i> 
<span class="sidebar-text">Gestión Comunitaria</span>
</span>
<i class="fas fa-chevron-down text-xs ml-auto transition-transform duration-300 sidebar-text <?php echo $is_any_user_management_active ? 'rotate-180' : ''; ?>"></i>
</button>

<ul id="usersDropdownMenu" class="pl-3 mt-1 space-y-1 overflow-hidden transition-all duration-300 
    <?php echo $is_any_user_management_active ? 'max-h-40' : 'max-h-0'; ?>">
<li>
<a href="./index.php?route=admin/users/personas" 
    class="flex items-center px-3 py-2 text-white text-opacity-80 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 text-sm<?php echo $is_personas_active ? ' active-link' : ''; ?>">
<i class="fas fa-user-friends sidebar-icon mr-3 text-sm"></i> 
<span class="sidebar-text">Habitantes</span>
</a>
</li>
<li>
    <a href="./index.php?route=admin/users/jefes-familia" 
        class="flex items-center px-3 py-2 text-white text-opacity-80 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 text-sm<?php echo $is_jefes_familia_active ? ' active-link' : ''; ?>">
        <i class="fas fa-user-tie sidebar-icon mr-3 text-sm"></i> 
        <span class="sidebar-text">Jefes de Familias</span>
    </a>
</li>
<li>
    <a href="./index.php?route=admin/users/lideres" 
        class="flex items-center px-3 py-2 text-white text-opacity-80 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 text-sm<?php echo $is_lideres_active ? ' active-link' : ''; ?>">
        <i class="fas fa-user-shield sidebar-icon mr-3 text-sm"></i> 
        <span class="sidebar-text">Líderes</span>
    </a>
</li>
</ul>
</li>

<li>
<a href="./index.php?route=admin/viviendas" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo strpos($current_route, 'admin/viviendas') === 0 ? ' active-link' : ''; ?>">
<i class="fas fa-house sidebar-icon mr-3"></i> 
<span class="sidebar-text">Viviendas</span>
</a>
</li>

<li>
<a href="./index.php?route=admin/carga-familiar" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/carga-familiar' ? ' active-link' : ''; ?>">
<i class="fas fa-user-friends sidebar-icon mr-3"></i> 
<span class="sidebar-text">Mi Carga Familiar</span>
</a>
</li>

<li>
<a href="./index.php?route=admin/news" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo strpos($current_route, 'admin/news') === 0 ? ' active-link' : ''; ?>">
<i class="fas fa-newspaper sidebar-icon mr-3"></i> 
<span class="sidebar-text">Noticias</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/comments" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/comments' ? ' active-link' : ''; ?>">
<i class="fas fa-comments sidebar-icon mr-3"></i> 
<span class="sidebar-text">Comentarios</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/notifications" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/notifications' ? ' active-link' : ''; ?>">
<i class="fas fa-bell sidebar-icon mr-3"></i> 
<span class="sidebar-text">Notificaciones</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/pagos/periodos" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $is_pagos_active ? ' active-link' : ''; ?>">
<i class="fas fa-hand-holding-dollar sidebar-icon mr-3"></i>
<span class="sidebar-text">Pagos / Beneficios</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/reports" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/reports' ? ' active-link' : ''; ?>">
<i class="fas fa-chart-line sidebar-icon mr-3"></i> 
<span class="sidebar-text">Reportes</span>
</a>
</li>
<li>
<a href="./index.php?route=eventos" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'eventos' ? ' active-link' : ''; ?>">
<i class="fas fa-calendar-alt sidebar-icon mr-3"></i>
<span class="sidebar-text">Eventos</span>
</a>
</li>
<li>
<a href="./index.php?route=admin/indicadores" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200<?php echo $current_route === 'admin/indicadores' ? ' active-link' : ''; ?>">
<i class="fas fa-chart-pie sidebar-icon mr-3"></i>
<span class="sidebar-text">Indicadores</span>
</a>
</li>
</ul>
</nav>

<div class="mt-auto p-3 border-t border-white border-opacity-10">
<button id="sidebarCollapseToggle" class="hidden lg:flex items-center justify-center w-full text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200 py-2.5 mb-2">
<i class="fas fa-chevron-left text-lg"></i>
</button>
<a href="./index.php?route=login/logout" class="flex items-center px-3 py-2.5 text-white text-opacity-90 hover:bg-white hover:bg-opacity-10 rounded-lg transition-all duration-200">
<i class="fas fa-sign-out-alt sidebar-icon mr-3"></i> 
<span class="sidebar-text">Cerrar Sesión</span>
</a>
</div>
</aside>

<!-- Contenido principal con mejor espaciado y diseño -->
<div id="content" class="content flex-grow p-6 flex flex-col">
<header class="flex items-center justify-between bg-white p-4 shadow-sm rounded-lg mb-6 lg:hidden flex-shrink-0 border border-gray-200">
<button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none transition-colors">
<i class="fas fa-bars text-xl"></i>
</button>
<h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h2>
<div class="flex items-center">
<span class="text-gray-700 text-sm mr-2 font-medium">
<?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>
</span>
<i class="fas fa-user-circle text-gray-500 text-2xl"></i>
</div>
</header>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block flex-shrink-0 tracking-tight"><?php echo htmlspecialchars($page_title); ?></h2>

    <?php
    // Ruta hacia la página de notificaciones según rol
    $notifRoute = 'user/notifications';
    if (isset($_SESSION['id_rol'])) {
        if ($_SESSION['id_rol'] == 1) $notifRoute = 'admin/notifications';
        elseif ($_SESSION['id_rol'] == 2) $notifRoute = 'subadmin/notifications';
    }
    ?>

    <div id="notifWrapper" class="relative">
        <button id="notifBell" class="relative btn btn-sm btn-light me-2" title="Notificaciones" type="button">
            <i class="fas fa-bell fa-lg"></i>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
        </button>

        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white shadow-lg rounded z-50" style="min-width:280px;">
            <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                <strong>Notificaciones</strong>
                <button id="notifMarkAll" class="btn btn-sm btn-outline-primary">Marcar todas leídas</button>
            </div>
            <div id="notifList" class="max-h-72 overflow-auto p-2"></div>
            <div class="p-2 text-center border-top"><a href="./index.php?route=<?php echo $notifRoute; ?>">Ver todas</a></div>
        </div>
    </div>
</div>

<!-- Toasts con mejor diseño -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
<div id="successToast" class="toast align-items-center text-bg-success border-0 d-none shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
<div class="d-flex">
<div class="toast-body fw-medium" id="successToastBody"></div>
<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
</div>
<div id="errorToast" class="toast align-items-center text-bg-danger border-0 d-none shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
<div class="d-flex">
<div class="toast-body fw-medium" id="errorToastBody"></div>
<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
</div>
</div>

<main class="flex-grow fade-in">
<?php
if (isset($content_view) && file_exists($content_view)) {
include_once $content_view;
} else {
echo '<div class="alert alert-danger" role="alert">Error: La vista de contenido no se pudo cargar.</div>';
error_log("Error: La vista de contenido '$content_view' no existe o no está definida.");
}
?>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="./js/admin_dashboard.js?v=<?php echo time(); ?>"></script>
<script src="./js/toast_initializer.js?v=<?php echo time(); ?>"></script>
<script src="./js/admin_news_scripts.js?v=<?php echo time(); ?>"></script>
<script src="./js/edit_user_form.js?v=<?php echo time(); ?>"></script>
<script src="./js/input_maxlength.js?v=<?php echo time(); ?>"></script>
<script src="./js/pagos.js?v=<?php echo time(); ?>"></script>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usersToggle = document.getElementById('usersDropdownToggle');
    const usersMenu = document.getElementById('usersDropdownMenu');
    const usersChevron = usersToggle.querySelector('i.fa-chevron-down');
    
    if (usersMenu.classList.contains('max-h-40')) {
        usersMenu.style.maxHeight = usersMenu.scrollHeight + "px";
    }
    
    usersToggle.addEventListener('click', function() {
        const isExpanded = usersMenu.classList.contains('max-h-40');
        
        if (isExpanded) {
            usersMenu.style.maxHeight = '0';
            usersChevron.classList.remove('rotate-180');
            usersMenu.classList.remove('max-h-40');
            usersMenu.classList.add('max-h-0');
        } else {
            usersMenu.style.maxHeight = usersMenu.scrollHeight + "px";
            usersChevron.classList.add('rotate-180');
            usersMenu.classList.remove('max-h-0');
            usersMenu.classList.add('max-h-40');
        }
    });
    
    usersMenu.addEventListener('transitionend', function() {
        if (usersMenu.style.maxHeight !== '0px') {
            usersMenu.style.maxHeight = 'none';
        }
    });

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
<script src="./js/notifications.js?v=<?php echo time(); ?>"></script>
</body>
</html>
