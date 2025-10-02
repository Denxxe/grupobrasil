<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Gestión de Comentarios</h1>

    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2">ID</th>
                <th class="border p-2">Noticia</th>
                <th class="border p-2">Usuario</th>
                <th class="border p-2">Comentario</th>
                <th class="border p-2">Fecha</th>
                <th class="border p-2">Estado</th>
                <th class="border p-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comentarios as $comentario): ?>
                <tr>
                    <td class="border p-2"><?= $comentario['id_comentario'] ?></td>
                    <td class="border p-2"><?= htmlspecialchars($comentario['titulo_noticia']) ?></td>
                    <td class="border p-2"><?= htmlspecialchars($comentario['nombre_usuario']) ?></td>
                    <td class="border p-2"><?= htmlspecialchars($comentario['contenido']) ?></td>
                    <td class="border p-2"><?= $comentario['fecha_comentario'] ?></td>
                    <td class="border p-2"><?= $comentario['activo'] ? 'Activo' : 'Inactivo' ?></td>
                    <td class="border p-2">
                        <a href="./index.php?route=admin/delete-comment&id=<?= $comentario['id_comentario'] ?>" class="text-red-500">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
