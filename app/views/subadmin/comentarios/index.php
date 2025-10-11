<!-- grupobrasil/app/views/subadmin/comentarios/index.php -->

<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Gestión de Comentarios (Subadmin)</h1>

    <?php if (!empty($comentarios)): ?>
        <table class="min-w-full border border-gray-300 bg-white rounded-lg shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-gray-600">#</th>
                    <th class="px-4 py-2 text-left text-gray-600">Noticia</th>
                    <th class="px-4 py-2 text-left text-gray-600">Usuario</th>
                    <th class="px-4 py-2 text-left text-gray-600">Comentario</th>
                    <th class="px-4 py-2 text-left text-gray-600">Fecha</th>
                    <th class="px-4 py-2 text-center text-gray-600">Estado</th>
                    <th class="px-4 py-2 text-center text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comentarios as $comentario): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2"><?= htmlspecialchars($comentario['id_comentario']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($comentario['titulo_noticia'] ?? 'Sin título') ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($comentario['nombre_usuario'] ?? 'Anónimo') ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($comentario['contenido']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($comentario['fecha_comentario']) ?></td>
                        <td class="px-4 py-2 text-center">
                            <?php if ($comentario['activo'] == 1): ?>
                                <span class="text-green-600 font-semibold">Activo</span>
                            <?php else: ?>
                                <span class="text-red-600 font-semibold">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <!-- El subadmin puede desactivar (soft delete) -->
                            <?php if ($comentario['activo'] == 1): ?>
                               <a href="./index.php?route=subadmin/softDeleteComment&id=<?= $comentario['id_comentario'] ?>"
                                   class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                   Eliminar
                                </a>
                            <?php else: ?>
                                <a href="./index.php?route=subadmin/activateComment&id=<?= $comentario['id_comentario'] ?>"

                                   class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                   Activar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-gray-500">No hay comentarios registrados.</p>
    <?php endif; ?>
</div>
