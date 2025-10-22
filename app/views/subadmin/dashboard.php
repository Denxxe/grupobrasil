<?php
error_log("[v0] Cargando dashboard de subadmin");
error_log("[v0] id_usuario: " . ($_SESSION['id_usuario'] ?? 'no definido'));
error_log("[v0] id_rol: " . ($_SESSION['id_rol'] ?? 'no definido'));
error_log("[v0] requires_setup: " . ($_SESSION['requires_setup'] ?? 'no definido'));
?>

<div class="container mx-auto px-6 py-8">
    <!-- Bienvenida -->
    <?php if (isset($_SESSION['nombre_usuario']) || isset($_SESSION['nombre'])): ?>
        <p class="text-lg text-gray-700 mb-4">
            Bienvenido, 
            <span class="font-semibold text-gray-900">
                <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? $_SESSION['nombre'] ?? 'Líder de Vereda'); ?>
            </span> 
            (Líder de Vereda).
        </p>
    <?php endif; ?>

    <!-- Mensaje de éxito -->
    <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded mb-6" role="alert">
        Gestiona los habitantes, familias y viviendas de tu vereda asignada.
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Habitantes</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalHabitantes ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Viviendas</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalViviendas ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Familias</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalFamilias ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Opciones en Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Habitantes -->
        <a href="./index.php?route=subadmin/habitantes" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Habitantes</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Gestionar habitantes de mi vereda.</p>
        </a>

        <!-- Familias -->
        <a href="./index.php?route=subadmin/familias" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-green-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Familias</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Ver y gestionar familias de mi vereda.</p>
        </a>

        <!-- Viviendas -->
        <a href="./index.php?route=subadmin/viviendas" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-yellow-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Viviendas</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Gestionar viviendas de mi vereda.</p>
        </a>

        <!-- Reportes -->
        <a href="./index.php?route=subadmin/reports" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-red-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 17v-6h13v6m-7-10H5a2 2 0 00-2 2v12h18V9a2 2 0 00-2-2h-6z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Reportes</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Visualiza reportes detallados.</p>
        </a>

        <!-- Comentarios -->
        <a href="./index.php?route=subadmin/comments" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-teal-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Comentarios</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Gestionar comentarios.</p>
        </a>

        <!-- Notificaciones -->
        <a href="./index.php?route=subadmin/notifications" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-orange-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Notificaciones</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Ver mis notificaciones.</p>
        </a>
    </div>
</div>
