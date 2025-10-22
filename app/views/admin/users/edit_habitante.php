<?php 
// app/views/admin/users/edit_habitante.php
// Variables disponibles: $page_title, $persona, $calles, $success_message, $error_message
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 text-gray-800"><?= $page_title ?></h1>
                <a href="./index.php?route=admin/users/personas" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Habitante</h6>
                </div>
                <div class="card-body">
                    <form action="./index.php?route=admin/users/update-habitante" method="POST">
                        <input type="hidden" name="person_id" value="<?= htmlspecialchars($persona['id_persona'] ?? '') ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cédula <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cedula" name="cedula" 
                                    value="<?= htmlspecialchars($persona['cedula'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                    value="<?= htmlspecialchars($persona['fecha_nacimiento'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombres" name="nombres" 
                                    value="<?= htmlspecialchars($persona['nombres'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                    value="<?= htmlspecialchars($persona['apellidos'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sexo" class="form-label">Sexo</label>
                                <select class="form-control" id="sexo" name="sexo">
                                    <option value="">Seleccione...</option>
                                    <option value="M" <?= ($persona['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= ($persona['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" 
                                    value="<?= htmlspecialchars($persona['telefono'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" 
                                value="<?= htmlspecialchars($persona['correo'] ?? '') ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="id_calle" class="form-label">Vereda de Residencia <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_calle" name="id_calle" required>
                                    <option value="">Seleccione una vereda...</option>
                                    <?php if (isset($calles) && is_array($calles)): ?>
                                        <?php foreach ($calles as $calle): ?>
                                            <option value="<?= htmlspecialchars($calle['id_calle']) ?>" 
                                                <?= ($persona['id_calle'] ?? '') == $calle['id_calle'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($calle['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="numero_casa" class="form-label">Número de Casa</label>
                                <input type="text" class="form-control" id="numero_casa" name="numero_casa" 
                                    value="<?= htmlspecialchars($persona['numero_casa'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección Completa</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= htmlspecialchars($persona['direccion'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="./index.php?route=admin/users/personas" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
