<?php
// grupobrasil/app/views/admin/news/edit.php

// --- Lógica de Repoblación y Errores ---
$display_errors = [];
if (isset($errors) && is_array($errors)) {
    $display_errors = $errors;
} elseif (isset($errors) && is_string($errors)) {
    $display_errors[] = $errors;
}

// Para repoblar el formulario, priorizamos old_data sobre $news en caso de errores de validación.
$news_data = !empty($old_data) ? $old_data : ($news ?? []);

// Definimos variables predeterminadas para evitar warnings si no se definen en el controlador
$categorias = $categorias ?? []; 

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($page_title ?? 'Editar Noticia'); ?></h1>
        <a href="./index.php?route=admin/news" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Noticias
        </a>
    </div>

    <!-- Mensajes de Error de Validación -->
    <?php if (!empty($display_errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al guardar la noticia!</h4>
            <ul class="mb-0">
                <?php foreach ($display_errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-vinotinto-700">Formulario de Edición de Noticia</h6>
        </div>
        <div class="card-body">
            <form action="./index.php?route=admin/news/update" method="POST" enctype="multipart/form-data">
                
                <!-- ID Oculto para la actualización -->
                <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($news_data['id_noticia'] ?? ''); ?>">

                <!-- BLOQUE DE METADATOS: Muestra Quién Publicó -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded border">
                            <label class="form-label text-muted">Publicado por:</label>
                            <!-- ESTO AHORA DEBERÍA FUNCIONAR SI CORRIGES EL MODELO (VER INSTRUCCIONES ABAJO) -->
                            <p class="form-control-plaintext fw-bold">
                                <?php echo htmlspecialchars($news_data['nombre_usuario'] ?? 'Usuario Desconocido'); ?>
                            </p>
                            <!-- El ID de usuario se mantiene oculto por si se requiere para la actualización -->
                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($news_data['id_usuario'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded border">
                            <label class="form-label text-muted">Fecha de Publicación:</label>
                            <p class="form-control-plaintext">
                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($news_data['fecha_publicacion'] ?? 'now'))); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Título -->
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título de la Noticia:</label>
                    <input type="text" class="form-control" id="titulo" name="titulo"
                           value="<?php echo htmlspecialchars($news_data['titulo'] ?? ''); ?>" required>
                </div>

                <!-- Contenido (Textarea) -->
                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido:</label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="10"
                              required><?php echo htmlspecialchars($news_data['contenido'] ?? ''); ?></textarea>
                </div>

                <!-- Categoría (Select) -->
                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría:</label>
                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <!-- CORRECCIÓN DEL BUCLE: Aseguramos que $categorias sea iterable -->
                        <?php 
                        // El error de 'string' ocurre si $categorias no es un array iterable. 
                        // Asumimos que $categorias es un array de arrays asociativos.
                        if (is_array($categorias)):
                            foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['id_categoria'] ?? ''); ?>"
                                    <?php 
                                    $selected_cat_id = $news_data['id_categoria'] ?? null;
                                    $current_cat_id = $categoria['id_categoria'] ?? null;
                                    if ($selected_cat_id !== null && $selected_cat_id == $current_cat_id) {
                                        echo 'selected';
                                    }
                                    ?>>
                                    <?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'Sin Nombre'); ?>
                                </option>
                            <?php endforeach; 
                        endif; ?>
                    </select>
                </div>

                <!-- Imagen Principal -->
                <div class="mb-3">
                    <label for="imagen_principal" class="form-label">Imagen Principal:</label>
                    <?php if (!empty($news_data['imagen_principal'])): ?>
                        <div class="mb-2 p-3 border rounded bg-white">
                            <p class="mb-1 fw-bold">Imagen actual:</p>
                            <img src="./<?php echo htmlspecialchars($news_data['imagen_principal']); ?>"
                                 alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; height: auto;">
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_imagen_principal" name="remove_imagen_principal" value="1">
                                <label class="form-check-label text-danger" for="remove_imagen_principal">
                                    Eliminar imagen actual
                                </label>
                            </div>
                            <input type="hidden" name="current_imagen_principal" value="<?php echo htmlspecialchars($news_data['imagen_principal']); ?>">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*">
                    <div class="form-text">Sube una nueva imagen para reemplazar la actual (opcional).</div>
                </div>

                <!-- Estado (Select: Publicado/Borrador) -->
                <div class="mb-3">
                    <label for="estado" class="form-label">Estado de la Noticia:</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <?php $current_estado = $news_data['estado'] ?? 'borrador'; ?>
                        <option value="publicado" <?php echo ($current_estado === 'publicado') ? 'selected' : ''; ?>>Publicado (Visible)</option>
                        <option value="borrador" <?php echo ($current_estado === 'borrador') ? 'selected' : ''; ?>>Borrador (Oculto)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-vinotinto-600 text-white mt-3"><i class="fas fa-save"></i> Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>

<!-- Script para el TinyMCE (Contenido) si estás usándolo -->
<?php /* Se mantiene el bloque de script para referencia */ ?>
