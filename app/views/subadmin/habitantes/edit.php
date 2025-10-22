<!-- grupobrasil/app/views/subadmin/habitantes/edit.php -->

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editar Habitante</h1>
        <a href="./index.php?route=subadmin/habitantes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Datos del Habitante</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="./index.php?route=subadmin/editHabitante&id=<?php echo $habitante['id_habitante']; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" 
                               value="<?php echo htmlspecialchars($persona['cedula'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nombres" class="form-label">Nombres *</label>
                        <input type="text" class="form-control" id="nombres" name="nombres" 
                               value="<?php echo htmlspecialchars($persona['nombres']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apellidos" class="form-label">Apellidos *</label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" 
                               value="<?php echo htmlspecialchars($persona['apellidos']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                               value="<?php echo htmlspecialchars($persona['fecha_nacimiento'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sexo" class="form-label">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo">
                            <option value="">Seleccionar...</option>
                            <option value="M" <?php echo ($persona['sexo'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo ($persona['sexo'] ?? '') === 'F' ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($persona['telefono'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="id_calle" class="form-label">Vereda *</label>
                        <select class="form-select" id="id_calle" name="id_calle" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($todasVeredas as $vereda): ?>
                                <?php if (in_array($vereda['id_calle'], $veredasAsignadas)): ?>
                                    <option value="<?php echo $vereda['id_calle']; ?>" 
                                            <?php echo ($persona['id_calle'] ?? 0) == $vereda['id_calle'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vereda['nombre']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="numero_casa" class="form-label">Número de Casa</label>
                        <input type="text" class="form-control" id="numero_casa" name="numero_casa" 
                               value="<?php echo htmlspecialchars($persona['numero_casa'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($persona['correo'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="condicion" class="form-label">Condición</label>
                        <select class="form-select" id="condicion" name="condicion">
                            <option value="Residente" <?php echo ($habitante['condicion'] ?? '') === 'Residente' ? 'selected' : ''; ?>>Residente</option>
                            <option value="Visitante" <?php echo ($habitante['condicion'] ?? '') === 'Visitante' ? 'selected' : ''; ?>>Visitante</option>
                            <option value="Temporal" <?php echo ($habitante['condicion'] ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($persona['direccion'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="./index.php?route=subadmin/habitantes" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
