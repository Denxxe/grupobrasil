<!-- grupobrasil/app/views/subadmin/habitantes/index.php -->

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Habitantes de Mi Vereda</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHabitanteModal">
            <i class="fas fa-plus"></i> Agregar Habitante
        </button>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['flash_success']); 
                unset($_SESSION['flash_success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['flash_error']); 
                unset($_SESSION['flash_error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Habitantes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Habitantes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Vereda</th>
                            <th>Casa</th>
                            <th>Teléfono</th>
                            <th>Condición</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($habitantes)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay habitantes registrados en tus veredas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($habitantes as $habitante): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($habitante['cedula'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($habitante['nombres']); ?></td>
                                    <td><?php echo htmlspecialchars($habitante['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($habitante['nombre_vereda'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($habitante['numero_casa'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($habitante['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($habitante['condicion'] ?? 'Residente'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="./index.php?route=subadmin/editHabitante&id=<?php echo $habitante['id_habitante']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="./index.php?route=subadmin/asignarLiderFamilia&id=<?php echo $habitante['id_habitante']; ?>" 
                                           class="btn btn-sm btn-success" title="Asignar como Líder de Familia"
                                           onclick="return confirm('¿Deseas asignar a este habitante como Líder de Familia?');">
                                            <i class="fas fa-user-tie"></i>
                                        </a>
                                        <a href="./index.php?route=subadmin/deleteHabitante&id=<?php echo $habitante['id_habitante']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de eliminar este habitante?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Habitante -->
<div class="modal fade" id="addHabitanteModal" tabindex="-1" aria-labelledby="addHabitanteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="./index.php?route=subadmin/addHabitante">
                <div class="modal-header">
                    <h5 class="modal-title" id="addHabitanteModalLabel">Agregar Nuevo Habitante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control" id="cedula" name="cedula">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-select" id="sexo" name="sexo">
                                <option value="">Seleccionar...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="id_calle" class="form-label">Vereda *</label>
                            <select class="form-select" id="id_calle" name="id_calle" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($todasVeredas as $vereda): ?>
                                    <?php if (in_array($vereda['id_calle'], $veredasAsignadas)): ?>
                                        <option value="<?php echo $vereda['id_calle']; ?>">
                                            <?php echo htmlspecialchars($vereda['nombre']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numero_casa" class="form-label">Número de Casa</label>
                            <input type="text" class="form-control" id="numero_casa" name="numero_casa">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="condicion" class="form-label">Condición</label>
                            <select class="form-select" id="condicion" name="condicion">
                                <option value="Residente">Residente</option>
                                <option value="Visitante">Visitante</option>
                                <option value="Temporal">Temporal</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Habitante</button>
                </div>
            </form>
        </div>
    </div>
</div>
