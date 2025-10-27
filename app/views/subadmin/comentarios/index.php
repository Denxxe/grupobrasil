<!-- grupobrasil/app/views/subadmin/comentarios/index.php -->

<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Gestión de Comentarios (Subadmin)</h1>

    <?php 
    $noticias = $noticias ?? [];
    if (empty($noticias)): ?>
        <p class="text-gray-600">No hay noticias con comentarios registrados.</p>
    <?php endif; ?>

    <!-- Listado de noticias en cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($noticias as $noticia): ?>
            <div class="bg-white shadow-xl rounded-xl p-6 border border-gray-100 hover:shadow-2xl transition duration-300">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">
                    <?= htmlspecialchars($noticia['titulo'] ?? 'Noticia sin título') ?>
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    Comentarios totales: 
                    <span class="font-extrabold text-blue-600 text-lg">
                        <?= htmlspecialchars($noticia['conteo'] ?? 0) ?>
                    </span>
                </p>
                <button 
                    class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg shadow-md hover:bg-blue-700 transition duration-150 transform hover:scale-[1.02] open-modal"
                    data-noticia="<?= htmlspecialchars($noticia['id_noticia'] ?? '') ?>">
                    Gestionar Comentarios
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// Incluimos CSRF token para las llamadas AJAX
require_once __DIR__ . '/../../../helpers/CsrfHelper.php';
$csrfToken = \CsrfHelper::getToken();
?>

<!-- Modal de comentarios -->
<div id="modalComentarios" class="hidden fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative">
        <!-- Botón cerrar -->
        <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl" id="closeModal">
            &times;
        </button>

        <h2 class="text-2xl font-bold mb-4 text-gray-800 border-b pb-2" id="modalTitulo">Comentarios</h2>

        <!-- Aquí se cargan los comentarios dinámicamente -->
        <div id="comentariosContainer" class="space-y-4 max-h-96 overflow-y-auto pr-2">
            <p class="text-gray-500">Cargando comentarios...</p>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
// Manejar la apertura del modal y la carga de datos
document.querySelectorAll('.open-modal').forEach(button => {
    button.addEventListener('click', async () => {
        const idNoticia = button.getAttribute('data-noticia');
        const modal = document.getElementById('modalComentarios');
        const container = document.getElementById('comentariosContainer');
        const titulo = document.getElementById('modalTitulo');
        
        // Mostrar modal y loading
        modal.classList.remove('hidden');
        container.innerHTML = `
            <div class="flex justify-center items-center py-8">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class='text-gray-500'>Cargando comentarios...</p>
            </div>
        `;
        
        // Petición AJAX al controlador Subadmin
        try {
            const response = await fetch(`./index.php?route=subadmin/getCommentsByNoticia&id=${encodeURIComponent(idNoticia)}`);
            if (!response.ok) { throw new Error(`Error HTTP: ${response.status}`); }

            const data = await response.json();

            if (data.success) {
                titulo.textContent = `Comentarios de: ${data.titulo}`;
                container.innerHTML = ""; // Limpiar
                if (data.comentarios.length > 0) {
                    data.comentarios.forEach(com => {
                        container.innerHTML += `
                            <div class="comment-card bg-gray-50 p-4 rounded-xl shadow-sm border border-gray-200" data-id="${com.id_comentario}">
                                <p class="text-gray-800 break-words mb-2">${com.contenido}</p>
                                <small class="text-gray-500 block mb-3 text-xs">
                                    Por <strong>${com.nombre_usuario}</strong> el 
                                    ${com.fecha_comentario}
                                </small>
                                <div class="mt-2 flex gap-3 text-sm">
                                    <button data-action="deleteComment" data-id="${com.id_comentario}" class="action-btn px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-medium rounded transition duration-150">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    // Attach handlers a los botones recién creados
                    container.querySelectorAll('.action-btn').forEach(btn => {
                        btn.addEventListener('click', async (ev) => {
                            const action = btn.getAttribute('data-action');
                            const cid = btn.getAttribute('data-id');
                            if (!action || !cid) return;

                            // Confirmación para eliminación física
                            if (action === 'deleteComment') {
                                if (!confirm('¿Estás seguro de ELIMINAR FÍSICAMENTE este comentario? Esta acción es irreversible.')) return;
                            }

                            // Determinar la ruta correcta según el enrutador (subadmin expects comments/{subaction})
                            let routePath = '';
                            if (action === 'deleteComment') {
                                routePath = `subadmin/comments/delete&id=${encodeURIComponent(cid)}`;
                            } else if (action === 'softDeleteComment') {
                                routePath = `subadmin/comments/soft-delete&id=${encodeURIComponent(cid)}`;
                            } else if (action === 'activateComment') {
                                routePath = `subadmin/comments/activate&id=${encodeURIComponent(cid)}`;
                            } else {
                                // fallback: try action name directly
                                routePath = `subadmin/${action}&id=${encodeURIComponent(cid)}`;
                            }

                            // Llamada AJAX (POST) al endpoint correspondiente con CSRF
                            try {
                                const body = new URLSearchParams();
                                body.append('csrf_token', CSRF_TOKEN);

                                const url = `./index.php?route=${routePath}`;
                                const res = await fetch(url, {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: body.toString()
                                });

                                // Intentar parsear JSON de forma segura
                                const contentType = res.headers.get('content-type') || '';
                                if (!res.ok) {
                                    const text = await res.text();
                                    console.error('Respuesta no OK:', res.status, text);
                                    alert('Error del servidor al ejecutar la acción (ver consola).');
                                    return;
                                }

                                if (contentType.indexOf('application/json') === -1) {
                                    const text = await res.text();
                                    console.error('Respuesta inesperada (no JSON):', text);
                                    alert('Respuesta inesperada del servidor (ver consola).');
                                    return;
                                }

                                const data = await res.json();
                                if (data.success) {
                                    // Remover tarjeta del DOM
                                    const card = btn.closest('.comment-card');
                                    if (card) card.remove();
                                } else {
                                    alert(data.message || 'Error al procesar la acción');
                                }
                            } catch (err) {
                                console.error('Error en AJAX:', err);
                                alert('Error de red al ejecutar la acción');
                            }
                        });
                    });

                } else {
                    container.innerHTML = "<p class='text-gray-600 py-4'>No hay comentarios para esta noticia.</p>";
                }
            } else {
                container.innerHTML = `<p class='text-red-600 py-4'>Error: ${data.message || 'Error al cargar comentarios.'}</p>`;
            }
        } catch (err) {
            console.error('Error de red o procesamiento:', err);
            container.innerHTML = "<p class='text-red-600 py-4'>Error de conexión al servidor. Revisa la consola para más detalles.</p>";
        }
    });
});

// cerrar modal
document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('modalComentarios').classList.add('hidden');
});

// Cerrar modal al presionar ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('modalComentarios').classList.add('hidden');
    }
});
</script>
