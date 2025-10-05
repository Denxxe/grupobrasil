<?php
// grupobrasil/app/views/user/dashboard.php

// Verificar si el usuario está autenticado y es un usuario común
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
    header('Location: ./index.php?route=login&error=acceso_denegado');
    exit();
}
?>

<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario'); ?> 👋
        </h1>

    </div>

    <!-- Mensaje de éxito -->
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6" role="alert">
      Aquí podrás ver y gestionar la información relevante para tu perfil y las actividades de la comunidad.
    </div>

    <!-- Opciones en Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Perfil -->
        <a href="./index.php?route=user/view_profile" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M5.121 17.804A9 9 0 1118.364 4.56M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Mi Perfil</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Consulta y actualiza tus datos personales.</p>
        </a>

        <!-- Noticias -->
        <a href="./index.php?route=noticias" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Noticias de la Comunidad</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Mantente al tanto de avisos y eventos importantes.</p>
        </a>

        <!-- Notificaciones -->
        <a href="./index.php?route=user/notifications" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-yellow-500 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Notificaciones</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Consulta tus alertas y mensajes importantes.</p>
        </a>
    </div>
</div>
