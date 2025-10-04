<!-- grupobrasil/app/views/comentarios/index.php -->

<div class="container mx-auto p-6">


    <!-- Listado de noticias -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($comentarios as $comentario): ?>
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">
                    <?= htmlspecialchars($comentario['titulo_noticia']) ?>
                </h2>
                <p class="text-sm text-gray-600 mb-3">
                    Comentarios totales: 
                    <span class="font-bold text-blue-600">
                        <?php 
                        // cuenta cuántos comentarios tiene esta noticia
                        $total = array_reduce($comentarios, function($carry, $c) use ($comentario) {
                            return $carry + ($c['id_noticia'] === $comentario['id_noticia'] ? 1 : 0);
                        }, 0);
                        echo $total;
                        ?>
                    </span>
                </p>
                <button 
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 open-modal"
                    data-noticia="<?= $comentario['id_noticia'] ?>">
                    Ver Comentarios
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de comentarios -->
<div id="modalComentarios" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 lg:w-1/2 p-6 relative">
        <!-- Botón cerrar -->
        <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-800" id="closeModal">
            ✖
        </button>

        <h2 class="text-2xl font-bold mb-4 text-gray-800" id="modalTitulo">Comentarios</h2>

        <!-- Aquí se cargan los comentarios dinámicamente -->
        <div id="comentariosContainer" class="space-y-4 max-h-96 overflow-y-auto">
            <p class="text-gray-500">Cargando comentarios...</p>
        </div>
    </div>
</div>

<script>
// abrir modal
document.querySelectorAll('.open-modal').forEach(button => {
    button.addEventListener('click', async () => {
        const idNoticia = button.getAttribute('data-noticia');
        const modal = document.getElementById('modalComentarios');
        const container = document.getElementById('comentariosContainer');
        const titulo = document.getElementById('modalTitulo');

        modal.classList.remove('hidden');
        container.innerHTML = "<p class='text-gray-500'>Cargando comentarios...</p>";

        // petición AJAX
        try {
            const response = await fetch(`./index.php?route=admin/getCommentsByNoticia&id=${idNoticia}`);
            const data = await response.json();

            if (data.success) {
                titulo.textContent = `Comentarios de: ${data.titulo}`;
                if (data.comentarios.length > 0) {
                    container.innerHTML = "";
                    data.comentarios.forEach(com => {
                        container.innerHTML += `
                            <div class="bg-gray-100 p-4 rounded-lg shadow">
                                <p class="text-gray-800">${com.contenido}</p>
                                <small class="text-gray-500">Por ${com.nombre_usuario} el ${com.fecha_creacion}</small>
                                <div class="mt-2 flex gap-2">
                                    ${com.activo == 1 
                                        ? `<a href="./index.php?route=admin/softDeleteComment/${com.id_comentario}" class="px-3 py-1 bg-red-500 text-white text-sm rounded">Desactivar</a>`
                                        : `<a href="./index.php?route=admin/activateComment/${com.id_comentario}" class="px-3 py-1 bg-green-500 text-white text-sm rounded">Activar</a>`
                                    }
                                    <a href="./index.php?route=admin/deleteComment/${com.id_comentario}" class="px-3 py-1 bg-gray-700 text-white text-sm rounded">Eliminar</a>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    container.innerHTML = "<p class='text-gray-500'>No hay comentarios para esta noticia.</p>";
                }
            } else {
                container.innerHTML = "<p class='text-red-500'>Error al cargar comentarios.</p>";
            }
        } catch (err) {
            console.error(err);
            container.innerHTML = "<p class='text-red-500'>Error de conexión.</p>";
        }
    });
});

// cerrar modal
document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('modalComentarios').classList.add('hidden');
});
</script>
