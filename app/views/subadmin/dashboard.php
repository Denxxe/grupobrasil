<div class="container mx-auto px-6 py-8">
    <!-- Header -->
   
    <!-- Bienvenida -->
    <?php if (isset($_SESSION['nombre_usuario'])): ?>
        <p class="text-lg text-gray-700 mb-4">
            Bienvenido, 
            <span class="font-semibold text-gray-900">
                <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>
            </span> 
            (Sub-Administrador).
        </p>
    <?php endif; ?>

    <!-- Info -->
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded mb-6" role="alert">
        Aquí podrás gestionar los usuarios que te han sido asignados y acceder a tus herramientas específicas.
    </div>

    <!-- Opciones en Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Usuarios asignados -->
        <a href="./index.php?route=subadmin/users" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M5.121 17.804A9 9 0 1118.364 4.56M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Usuarios Asignados</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Ver y editar los usuarios que tienes bajo tu gestión.</p>
        </a>

        <!-- Pagos -->
        <a href="./index.php?route=subadmin/payments" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-green-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 8c-1.104 0-2 .896-2 2v8h4v-8c0-1.104-.896-2-2-2zm0-6c-3.313 0-6 2.687-6 6v2h12V8c0-3.313-2.687-6-6-6z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Pagos</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Consultar los pagos de los usuarios de tu lista.</p>
        </a>

        <!-- Reportes -->
        <a href="./index.php?route=subadmin/reports" 
           class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center hover:shadow-lg transition">
            <svg class="w-12 h-12 text-yellow-600 mb-4" xmlns="http://www.w3.org/2000/svg" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 17v-6h13v6m-7-10H5a2 2 0 00-2 2v12h18V9a2 2 0 00-2-2h-6z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Reportes</h3>
            <p class="text-sm text-gray-600 text-center mt-2">Visualiza estadísticas de noticias y comentarios.</p>
        </a>
    </div>
</div>
