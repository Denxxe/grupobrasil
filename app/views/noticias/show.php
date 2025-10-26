<!-- grupobrasil/app/views/noticias/show.php -->

<div class="container mx-auto p-6">

    <!-- Imagen -->
    <?php
        // Preparar ruta pública y ruta absoluta en disco para verificar existencia
        $imgRel = !empty($noticia['imagen_principal']) ? $noticia['imagen_principal'] : null;

        // Si no hay imagen definida en la noticia, usar la default
        if (!$imgRel) {
            $imgExists = false;
            $imgUrl = '/grupobrasil/public/img/noticias/default.jpg';
            $imgDiskPath = null;
        } else {
            // Normalizar la ruta relativa guardada en BD (usar siempre /)
            $imgRelNorm = str_replace('\\', '/', ltrim($imgRel, '/\\'));

            // Construir un conjunto de rutas candidatas en disco donde el archivo podría residir
            $candidates = [];

            // 1) ruta basada en DOCUMENT_ROOT (lo que usamos históricamente)
            $docroot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
            $candidates[] = $docroot . '/grupobrasil/public/' . $imgRelNorm;

            // 2) ruta basada en la ubicación del proyecto (realpath desde esta vista hacia /public)
            $publicPath = realpath(__DIR__ . '/../../../public');
            if ($publicPath) {
                $candidates[] = rtrim($publicPath, '/\\') . '/' . $imgRelNorm;
            }

            // 3) ruta "uploads" en la raíz del proyecto (por si se guardó fuera de public)
            $projectRoot = realpath(__DIR__ . '/../../..');
            if ($projectRoot) {
                $candidates[] = rtrim($projectRoot, '/\\') . '/' . $imgRelNorm;
            }

            // Comprobar cada candidato (normalizando separadores) y tomar el primero existente
            $found = null;
            foreach ($candidates as $cand) {
                $disk = str_replace('/', DIRECTORY_SEPARATOR, $cand);
                if (file_exists($disk)) { $found = $disk; break; }
            }

            if ($found) {
                $imgDiskPath = $found;
                // Construir URL pública relativa a DOCUMENT_ROOT
                $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
                $imgUrl = '/' . ltrim(str_replace('\\', '/', substr($imgDiskPath, strlen($base))), '/');
                $imgExists = true;
            } else {
                $imgDiskPath = $candidates[0]; // mostrar la primera candidata para debug
                $imgExists = false;
                $imgUrl = '/grupobrasil/public/img/noticias/default.jpg';
            }
        }
    ?>
    <!-- DEBUG: imagen_principal="<?= htmlspecialchars($imgRel ?? '') ?>" | disk_path="<?= htmlspecialchars($imgDiskPath ?? '') ?>" | exists=<?= $imgExists ? 'yes' : 'no' ?> -->
    <img src="<?= htmlspecialchars($imgExists ? $imgUrl : '/grupobrasil/public/img/noticias/default.jpg') ?>" 
        alt="Imagen de la noticia"
        style="width:100%; height:auto; max-height:500px; object-fit:contain; display:block; margin-bottom:1.5rem; border-radius:0.5rem; box-shadow:0 4px 6px rgba(0,0,0,0.1);">

    <!-- Contenido -->
    <div class="text-gray-700 text-lg mb-6">
        <?= nl2br(htmlspecialchars($noticia['contenido'])) ?>
    </div>

    <!-- Botones de interacción -->
    <div class="flex items-center gap-4 mb-8">
        <!-- Likes -->
        <form method="POST" action="./index.php?route=noticias/toggle-like">
            <?= \CsrfHelper::getTokenInput() ?>
            <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
            <button type="submit" 
                    class="px-4 py-2 rounded-lg shadow <?= $usuarioDioLike ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-800' ?>">
                ❤️ <?= $totalLikes ?> Me gusta
            </button>
        </form>

     <!-- Botón compartir -->
<button onclick="openShareModal()" 
        class="px-4 py-2 rounded-lg shadow bg-blue-500 text-white">
    🔗 Compartir
</button>

<!-- Modal de Compartir -->
<div id="shareModal" 
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div id="shareCard" 
         class="bg-white w-[600px] h-[600px] rounded-xl shadow-lg relative flex flex-col overflow-hidden">

        <!-- Botón Cerrar -->
        <button onclick="closeShareModal()" 
                class="absolute top-2 right-2 text-gray-600 hover:text-red-500 text-xl font-bold z-10">
            ✖
        </button>

        <!-- Imagen principal -->
            <div style="height:50%; width:100%; display:flex; align-items:center; justify-content:center;">
                <img src="<?= htmlspecialchars($imgExists ? $imgUrl : '/grupobrasil/public/img/noticias/default.jpg') ?>" 
                      alt="Imagen de la noticia"
                      style="max-height:100%; width:auto; max-width:100%; object-fit:contain; display:block;">
          </div>

        <!-- Contenido -->
        <div class="flex-1 p-4 flex flex-col justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-800 mb-2">
                    <?= htmlspecialchars($noticia['titulo']) ?>
                </h1>
                <p class="text-gray-600 text-sm">
                    <?php
                        $excerpt = '';
                        if (!empty($noticia['contenido'])) {
                            if (function_exists('mb_substr')) {
                                $excerpt = mb_substr($noticia['contenido'], 0, 180);
                            } else {
                                $excerpt = substr($noticia['contenido'], 0, 180);
                            }
                        }
                    ?>
                    <?= htmlspecialchars($excerpt) ?>...
                </p>
            </div>

            <div class="text-center mt-4">
                <p class="text-xs text-gray-500">
                    📌 Noticia extraída de la <br>
                    <span class="font-semibold">Plataforma Comunitaria del Consejo Comunal Brasil Sector 3</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="absolute bottom-10 flex gap-4">
        <button onclick="downloadShareCard()" 
                class="px-4 py-2 bg-green-500 text-white rounded-lg shadow">
            ⬇️ Descargar como Imagen
        </button>
        <button onclick="copyShareCard()" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow">
            📋 Copiar al Portapapeles
        </button>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function openShareModal() {
        document.getElementById('shareModal').classList.remove('hidden');
        document.getElementById('shareModal').classList.add('flex');
    }

    function closeShareModal() {
        document.getElementById('shareModal').classList.remove('flex');
        document.getElementById('shareModal').classList.add('hidden');
    }

    function downloadShareCard() {
        const shareCard = document.getElementById('shareCard');
        html2canvas(shareCard, { useCORS: true }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'noticia.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
        });
    }

    async function copyShareCard() {
        const shareCard = document.getElementById('shareCard');
        const canvas = await html2canvas(shareCard, { useCORS: true });
        canvas.toBlob(async function(blob) {
            try {
                await navigator.clipboard.write([
                    new ClipboardItem({ "image/png": blob })
                ]);
                alert("✅ Imagen copiada al portapapeles, ahora puedes pegarla en WhatsApp Web, Messenger, etc.");
            } catch (err) {
                console.error("Error al copiar:", err);
                alert("❌ Tu navegador no soporta copiar imágenes al portapapeles.");
            }
        }, "image/png");
    }
</script>
   
    </div>

    <!-- Comentarios -->
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Comentarios</h2>

      <?php if (!empty($comentarios)): ?>
    <ul class="space-y-4">
        <?php foreach ($comentarios as $comentario): ?>
            <li class="bg-gray-100 p-4 rounded-lg shadow">
                <div class="comment-content" id="comment-content-<?= $comentario['id_comentario'] ?>">
                    <p class="text-gray-800"><?= nl2br(htmlspecialchars($comentario['contenido'])) ?></p>
                    <small class="text-gray-500">
                        Por <?= htmlspecialchars($comentario['nombre_usuario'] ?? 'Anónimo') ?>
                        el <?= htmlspecialchars($comentario['fecha_comentario']) ?>
                    </small>
                </div>

                <?php if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $comentario['id_usuario']): ?>
                    <div class="mt-2 flex gap-2">
                        <button class="px-3 py-1 bg-yellow-400 text-white rounded btn-edit-comment" data-id="<?= $comentario['id_comentario'] ?>">✏️ Editar</button>
                        <form method="POST" action="./index.php?route=noticias/delete-comment" style="display:inline;">
                            <?= \CsrfHelper::getTokenInput() ?>
                            <input type="hidden" name="id_comentario" value="<?= $comentario['id_comentario'] ?>">
                            <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded" onclick="return confirm('¿Eliminar comentario?')">🗑 Eliminar</button>
                        </form>
                    </div>

                    <!-- Formulario inline oculto para editar -->
                    <form method="POST" action="./index.php?route=noticias/edit-comment" class="mt-2 edit-form" id="edit-form-<?= $comentario['id_comentario'] ?>" style="display:none;">
                        <?= \CsrfHelper::getTokenInput() ?>
                        <input type="hidden" name="id_comentario" value="<?= $comentario['id_comentario'] ?>">
                        <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
                        <textarea name="contenido" rows="3" class="w-full border border-gray-300 rounded-lg p-2 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200" minlength="3" maxlength="800"><?= htmlspecialchars($comentario['contenido']) ?></textarea>
                        <div class="text-right text-xs text-gray-500 mt-1"> <span class="char-count">0</span>/800</div>
                        <div class="mt-2 flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Guardar</button>
                            <button type="button" class="px-4 py-2 bg-gray-300 rounded btn-cancel-edit" data-id="<?= $comentario['id_comentario'] ?>">Cancelar</button>
                        </div>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="text-gray-500">Aún no hay comentarios.</p>
<?php endif; ?>

    </div>

    <!-- Agregar comentario -->
    <?php if (isset($_SESSION['id_usuario'])): ?>
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Agregar comentario</h3>
            <form method="POST" action="./index.php?route=noticias/add-comment" class="space-y-3">
                <?= \CsrfHelper::getTokenInput() ?>
                <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
                <textarea name="contenido" rows="3"
                          class="w-full border border-gray-300 rounded-lg p-2 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
                          placeholder="Escribe tu comentario..." required minlength="3" maxlength="800"></textarea>
                <div class="text-right text-xs text-gray-500 mt-1"> <span class="char-count">0</span>/800</div>
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg shadow">
                    💬 Comentar
                </button>
            </form>
        </div>

        

<a href="./index.php?route=noticias" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver</a>

    <?php else: ?>
        <p class="text-gray-500">Debes <a href="./index.php?route=login" class="text-blue-600">iniciar sesión</a> para comentar.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const form = document.getElementById('edit-form-' + id);
            if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const form = document.getElementById('edit-form-' + id);
            if (form) form.style.display = 'none';
        });
    });
});
</script>

<script>
// AJAX handlers para editar y eliminar comentarios sin recargar
document.addEventListener('DOMContentLoaded', function() {
    // Interceptar envíos de formularios de edición inline
    document.querySelectorAll('.edit-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const idComentario = form.querySelector('input[name="id_comentario"]').value;
            const idNoticia = form.querySelector('input[name="id_noticia"]').value;
            const contenido = form.querySelector('textarea[name="contenido"]').value.trim();
            const len = contenido.length;
            if (len < 3 || len > 800) { alert('El comentario debe tener entre 3 y 800 caracteres.'); return; }

            const action = form.getAttribute('action');
            const fd = new FormData(form);

            try {
                const res = await fetch(action, { method: 'POST', credentials: 'same-origin', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (res.ok && data.success) {
                    // Actualizar el contenido en el DOM
                    const contentDiv = document.getElementById('comment-content-' + idComentario);
                    if (contentDiv) {
                        contentDiv.innerHTML = '<p class="text-gray-800">' + escapeHtml(data.contenido).replace(/\n/g, '<br>') + '</p>' + contentDiv.querySelector('small').outerHTML;
                    }
                    form.style.display = 'none';
                    alert(data.message || 'Comentario actualizado.');
                } else {
                    alert((data && data.message) ? data.message : 'Error al actualizar comentario');
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al actualizar comentario');
            }
        });
    });

    // Interceptar envíos de formularios de eliminación
    document.querySelectorAll('form[action*="delete-comment"]').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!confirm('¿Eliminar comentario?')) return;
            const action = form.getAttribute('action');
            const fd = new FormData(form);
            try {
                const res = await fetch(action, { method: 'POST', credentials: 'same-origin', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (res.ok && data.success) {
                    // Remover el elemento li contenedor
                    const idComentario = fd.get('id_comentario');
                    const btn = form.querySelector('button');
                    let li = btn ? btn.closest('li') : null;
                    if (li) li.remove();
                    alert(data.message || 'Comentario eliminado.');
                } else {
                    alert((data && data.message) ? data.message : 'Error al eliminar comentario');
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al eliminar comentario');
            }
        });
    });

    function escapeHtml(unsafe) { return String(unsafe).replace(/[&<"'>]/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]; }); }
});
</script>

<script src="/grupobrasil/public/js/comment_counter.js"></script>
