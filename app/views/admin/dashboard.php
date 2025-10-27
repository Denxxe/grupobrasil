<?php
// Este es el contenido de la vista admin/dashboard.php
// Variables esperadas: $stats (array), $activity_log (array), $page_title (string)

// Asumiendo una estructura mínima para $stats si no viene del controlador
$stats = $stats ?? [
    'total_usuarios' => 0,
    'nuevos_usuarios_semana' => 0,
    'total_pagos_hoy' => 0,
    'variacion_pagos_ayer' => 0, // En porcentaje, ej: 15.5 o -5.2
];

// Asumiendo una estructura mínima para $activity_log
$activity_log = $activity_log ?? [
    ['icon' => 'fas fa-check-circle', 'color' => 'text-green-500', 'message' => 'El sistema está en línea.', 'time' => 'hace 1 min'],
];

// Colores de la paleta (Tailwind customizado: vinotinto, crema)
$vinotinto = 'bg-[#800000]';
$vinotinto_text = 'text-[#800000]';
$accent_cream = 'bg-[#E0A800]';
$accent_cream_text = 'text-[#E0A800]';

// Función para formatear el valor de pagos
$formatCurrency = fn($value) => '$' . number_format($value, 0, ',', '.');
?>

<div class="p-4 sm:p-6 lg:p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Tablero Principal</h1>

    <!-- 1. Tarjetas de Métricas Clave (Stats Cards) -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total de Usuarios -->
        <div class="bg-white p-6 rounded-xl shadow-xl border-l-4 border-red-700 hover:shadow-2xl transition duration-300 transform hover:scale-[1.02]">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Usuarios Totales</h3>
                <i class="fas fa-users <?php echo $vinotinto_text; ?> text-2xl"></i>
            </div>
            <p class="mt-1 text-4xl font-extrabold text-gray-900">
                <?php echo number_format($stats['total_usuarios'], 0, ',', '.'); ?>
            </p>
            
            <?php 
            $change_class = $stats['nuevos_usuarios_semana'] > 0 ? 'text-green-600' : 'text-gray-500';
            $change_sign = $stats['nuevos_usuarios_semana'] > 0 ? '+' : '';
            ?>
            <p class="text-sm text-gray-600 mt-2">
                Nuevos esta semana: <span class="<?php echo $change_class; ?> font-semibold"><?php echo $change_sign . number_format($stats['nuevos_usuarios_semana'], 0); ?></span>
            </p>
        </div>

        <!-- Pagos Hoy -->
        <div class="bg-white p-6 rounded-xl shadow-xl border-l-4 border-yellow-500 hover:shadow-2xl transition duration-300 transform hover:scale-[1.02]">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pagos Hoy</h3>
                <i class="fas fa-money-bill-wave <?php echo $accent_cream_text; ?> text-2xl"></i>
            </div>
            <p class="mt-1 text-4xl font-extrabold text-gray-900">
                <?php echo $formatCurrency($stats['total_pagos_hoy']); ?>
            </p>
            
            <?php 
            $pagos_change = $stats['variacion_pagos_ayer'];
            $change_class = $pagos_change > 0 ? 'text-green-600' : ($pagos_change < 0 ? 'text-red-600' : 'text-gray-500');
            $change_sign = $pagos_change > 0 ? '+' : '';
            ?>
            <p class="text-sm text-gray-600 mt-2">
                Vs. Ayer: <span class="<?php echo $change_class; ?> font-semibold"><?php echo $change_sign . number_format($pagos_change, 1) . '%'; ?></span>
            </p>
        </div>

        <!-- Placeholder 3: (Ejemplo: Noticias Pendientes de Revisión) -->
        <div class="bg-white p-6 rounded-xl shadow-xl border-l-4 border-blue-500 hover:shadow-2xl transition duration-300 transform hover:scale-[1.02]">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Noticias Pendientes</h3>
                <i class="fas fa-bell text-blue-500 text-2xl"></i>
            </div>
            <p class="mt-1 text-4xl font-extrabold text-gray-900">
                0
            </p>
            <p class="text-sm text-gray-600 mt-2">
                <span class="text-blue-600 font-semibold">Ver Revisión</span>
            </p>
        </div>

        <!-- Placeholder 4: (Ejemplo: Líderes Activos) -->
        <div class="bg-white p-6 rounded-xl shadow-xl border-l-4 border-indigo-500 hover:shadow-2xl transition duration-300 transform hover:scale-[1.02]">
            <div class="flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Líderes Activos</h3>
                <i class="fas fa-handshake text-indigo-500 text-2xl"></i>
            </div>
            <p class="mt-1 text-4xl font-extrabold text-gray-900">
                2
            </p>
            <p class="text-sm text-gray-600 mt-2">
                <span class="text-indigo-600 font-semibold">Gestión de Zonas</span>
            </p>
        </div>

    </section>

    <!-- 2. Enlaces Rápidos y Actividad Reciente -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Columna 1 & 2: Enlaces de Acceso Rápido -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Tarjeta 1: Añadir Usuario -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-8 border-red-700">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center <?php echo $vinotinto; ?> bg-opacity-10 <?php echo $vinotinto_text; ?> rounded-full w-12 h-12 mr-4">
                        <i class="fas fa-user-plus text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Añadir Nuevo Usuario</h3>
                </div>
                <p class="text-gray-600 mb-4">Crea nuevas cuentas de usuario para el sistema, incluyendo líderes y habitantes.</p>
                <a href="./index.php?route=admin/users/create" class="inline-block <?php echo $vinotinto; ?> hover:bg-red-800 text-white px-5 py-2 rounded-lg font-medium transition duration-300 shadow-md">
                    Crear Cuenta
                </a>
            </div>

            <!-- Tarjeta 2: Gestión de Pagos -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-8 border-yellow-500">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center <?php echo $accent_cream; ?> bg-opacity-30 <?php echo $accent_cream_text; ?> rounded-full w-12 h-12 mr-4">
                        <i class="fas fa-hand-holding-usd text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Gestión de Beneficios</h3>
                </div>
                <p class="text-gray-600 mb-4">Revisa, administra y autoriza los pagos de beneficios a la comunidad.</p>
                <a href="./index.php?route=admin/payments" class="inline-block <?php echo $vinotinto; ?> hover:bg-red-800 text-white px-5 py-2 rounded-lg font-medium transition duration-300 shadow-md">
                    Ver Pagos
                </a>
            </div>

            <!-- Tarjeta 3: Noticias / Comunicación -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-8 border-cyan-500">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center bg-cyan-100 text-cyan-500 rounded-full w-12 h-12 mr-4">
                        <i class="fas fa-newspaper text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Centro de Noticias</h3>
                </div>
                <p class="text-gray-600 mb-4">Crea, edita y publica comunicados y noticias importantes para la comunidad.</p>
                <a href="./index.php?route=admin/news" class="inline-block <?php echo $vinotinto; ?> hover:bg-red-800 text-white px-5 py-2 rounded-lg font-medium transition duration-300 shadow-md">
                    Administrar
                </a>
            </div>
            
            <!-- Tarjeta 4: Gestión de Productos -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-8 border-indigo-500">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center bg-indigo-100 text-indigo-500 rounded-full w-12 h-12 mr-4">
                        <i class="fas fa-boxes text-xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Gestionar Reportes</h3>
                </div>
                <p class="text-gray-600 mb-4">Accede a las métricas clave, tendencias y análisis detallados del rendimiento del sistema.</p>
                <a href="./index.php?route=admin/reports" class="inline-block <?php echo $vinotinto; ?> hover:bg-red-800 text-white px-5 py-2 rounded-lg font-medium transition duration-300 shadow-md">
                    Ver Reportes
                </a>
            </div>
        </div>

        <!-- Columna 3: Log de Actividad Reciente -->
        <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-2xl overflow-hidden">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Actividad Reciente</h3>
            <div class="max-h-[500px] overflow-y-auto">
                <ul class="divide-y divide-gray-100">
                    <?php foreach ($activity_log as $activity): ?>
                        <li class="py-3 flex items-start space-x-3 transition duration-150 hover:bg-gray-50 px-2 -mx-2 rounded-md">
                            <span class="text-lg pt-1 <?php echo htmlspecialchars($activity['color']); ?> flex-shrink-0">
                                <i class="<?php echo htmlspecialchars($activity['icon']); ?>"></i>
                            </span>
                            <div class="flex-grow">
                                <p class="text-gray-800 leading-snug"><?php echo htmlspecialchars($activity['message']); ?></p>
                                <span class="text-xs text-gray-500 block mt-1"><?php echo htmlspecialchars($activity['time']); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

</div>
