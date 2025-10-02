<!-- grupobrasil/app/views/noticias/show.php -->

<div class="container mx-auto p-6">
    <!-- Título -->
    <h1 class="text-3xl font-bold text-gray-800 mb-4">
        <?= htmlspecialchars($noticia['titulo']) ?>
    </h1>

    <!-- Imagen -->
    <?php if (!empty($noticia['imagen'])): ?>
        <img src="<?= htmlspecialchars($noticia['imagen']) ?>" 
             alt="Imagen de la noticia"
             class="w-full h-64 object-cover rounded-lg shadow mb-6">
    <?php endif; ?>

    <!-- Contenido -->
    <div class="text-gray-700 text-lg mb-6">
        <?= nl2br(htmlspecialchars($noticia['contenido'])) ?>
    </div>

    <!-- Botones de interacción -->
    <div class="flex items-center gap-4 mb-8">
        <!-- Likes -->
        <form method="POST" action="./index.php?route=noticias/toggle-like">
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
        <div class="h-1/2 w-full">
            <?php if (!empty($noticia['imagen'])): ?>
                <img src="<?= htmlspecialchars($noticia['imagen']) ?>" 
                     alt="Imagen de la noticia"
                     class="w-full h-full object-cover">
            <?php else: ?>
                <img src="/public/img/noticias/default.jpg"
                     alt="Imagen por defecto"
                     class="w-full h-full object-cover">
            <?php endif; ?>
        </div>

        <!-- Contenido -->
        <div class="flex-1 p-4 flex flex-col justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-800 mb-2">
                    <?= htmlspecialchars($noticia['titulo']) ?>
                </h1>
                <p class="text-gray-600 text-sm">
                    <?= htmlspecialchars(mb_substr($noticia['contenido'], 0, 180)) ?>...
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
                <p class="text-gray-800"><?= nl2br(htmlspecialchars($comentario['contenido'])) ?></p>
                <small class="text-gray-500">
                    Por <?= htmlspecialchars($comentario['nombre_usuario'] ?? 'Anónimo') ?>
                    el <?= htmlspecialchars($comentario['fecha_creacion']) ?>
                </small>
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
                <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
                <textarea name="contenido" rows="3"
                          class="w-full border border-gray-300 rounded-lg p-2"
                          placeholder="Escribe tu comentario..." required></textarea>
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
