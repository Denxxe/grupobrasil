<?php
// grupobrasil/app/views/admin/dashboard.php

// No necesitas aquí head, body, script tags, etc.
// Solo el contenido que va DENTRO del <main> en admin_layout.php

// Puedes pasar variables a esta vista desde el controlador si necesitas datos dinámicos,
// por ejemplo, $total_users, $today_payments, etc.

?>

<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 flex-shrink-0">
    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
        <div class="flex items-center justify-center bg-vinotinto-100 text-vinotinto-500 rounded-full w-12 h-12 mb-4">
            <i class="fas fa-user-plus text-2xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Añadir Nuevo Usuario</h3>
        <p class="text-gray-600 mb-4">Crea nuevas cuentas de usuario para el sistema.</p>
        <a href="/grupobrasil/public/index.php?route=admin/users/create" class="inline-block bg-vinotinto-500 hover:bg-vinotinto-600 text-white px-4 py-2 rounded-md transition duration-300">Ir</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
        <div class="flex items-center justify-center bg-accentcream text-vinotinto-700 rounded-full w-12 h-12 mb-4">
            <i class="fas fa-hand-holding-usd text-2xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Gestión de pagos de beneficios</h3>
        <p class="text-gray-600 mb-4">Revisa y administra los pagos de beneficios.</p>
        <a href="#" class="inline-block bg-vinotinto-500 hover:bg-vinotinto-600 text-white px-4 py-2 rounded-md transition duration-300">Ir</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
        <div class="flex items-center justify-center bg-vinotinto-200 text-vinotinto-600 rounded-full w-12 h-12 mb-4">
            <i class="fas fa-boxes text-2xl"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Gestionar Productos</h3>
        <p class="text-gray-600 mb-4">Actualiza el inventario y la información de los productos.</p>
        <a href="#" class="inline-block bg-vinotinto-500 hover:bg-vinotinto-600 text-white px-4 py-2 rounded-md transition duration-300">Ir</a>
    </div>
</section>

<section class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-shrink-0">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Usuarios Totales</h3>
            <i class="fas fa-users text-vinotinto-500 text-3xl"></i>
        </div>
        <p class="text-4xl font-bold text-gray-900">1,234</p>
        <p class="text-gray-600 mt-2">Nuevos usuarios esta semana: <span class="text-green-500">+15%</span></p>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Pagos realizados el día de hoy</h3>
            <i class="fas fa-dollar-sign text-vinotinto-500 text-3xl"></i>
        </div>
        <p class="text-4xl font-bold text-gray-900">$5,678</p>
        <p class="text-gray-600 mt-2">Comparado con ayer: <span class="text-red-500">-5%</span></p>
    </div>
</section>

<section class="mt-8 bg-white p-6 rounded-lg shadow-md flex-shrink-0">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Actividad Reciente</h3>
    <ul class="divide-y divide-gray-200">
        <li class="py-3 flex items-center">
            <span class="text-gray-600 mr-3"><i class="fas fa-check-circle text-green-500"></i></span>
            <p class="text-gray-700">Se actualizó el perfil del usuario "Juan Pérez".</p>
            <span class="ml-auto text-sm text-gray-500">hace 5 min</span>
        </li>
        <li class="py-3 flex items-center">
            <span class="text-gray-600 mr-3"><i class="fas fa-hand-holding-usd text-vinotinto-500"></i></span>
            <p class="text-gray-700">Nuevo pago de beneficio procesado para "María García".</p>
            <span class="ml-auto text-sm text-gray-500">hace 30 min</span>
        </li>
        <li class="py-3 flex items-center">
            <span class="text-gray-600 mr-3"><i class="fas fa-exclamation-triangle text-yellow-500"></i></span>
            <p class="text-gray-700">Advertencia: Fallo en el procesamiento de un pago.</p>
            <span class="ml-auto text-sm text-gray-500">hace 1 hora</span>
        </li>
    </ul>
</section>