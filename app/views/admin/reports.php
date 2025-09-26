<div class="container my-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo htmlspecialchars($page_title); ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Usuarios -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Usuarios Registrados</h2>
            <p class="text-4xl font-bold text-vinotinto-600"><?php echo count($usuarios); ?></p>
            <p class="text-gray-500">Total de usuarios en el sistema</p>
        </div>

        <!-- Noticias -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Noticias Publicadas</h2>
            <p class="text-4xl font-bold text-vinotinto-600"><?php echo count($noticias); ?></p>
            <p class="text-gray-500">Noticias creadas por admins/subadmins</p>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="mt-8">
        <h3 class="text-2xl font-semibold mb-4">Listado de Usuarios</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow">
                <thead class="bg-vinotinto-600 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Rol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($usuarios as $u): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($u['id_usuario']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($u['nombre']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="px-4 py-2">
                                <?php
                                    switch ($u['id_rol']) {
                                        case 1: echo 'Admin'; break;
                                        case 2: echo 'Subadmin'; break;
                                        default: echo 'Usuario'; break;
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
