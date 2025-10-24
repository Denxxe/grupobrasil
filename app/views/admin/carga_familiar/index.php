<?php 
// app/views/admin/carga_familiar/index.php
// Vista para mostrar la carga familiar del usuario

$carga_familiar = $data['carga_familiar'] ?? [];
$es_jefe_familia = $data['es_jefe_familia'] ?? false;
$total_miembros = $data['total_miembros'] ?? 0;
?>

<div class="container-fluid">
    <?php if (!$es_jefe_familia): ?>
        <!-- Mensaje si no es jefe de familia -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>No eres jefe de familia</strong>
            <p class="mb-0">Esta sección solo está disponible para usuarios que son jefes de familia. Si crees que esto es un error, contacta al administrador.</p>
        </div>
    <?php else: ?>
        <!-- Header con estadísticas -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1"><i class="fas fa-users text-primary"></i> Mi Carga Familiar</h4>
                                <p class="text-muted mb-0">Miembros de tu grupo familiar registrados en el sistema</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-inline-block p-3 bg-primary bg-opacity-10 rounded">
                                    <h2 class="mb-0 text-primary"><?= $total_miembros ?></h2>
                                    <small class="text-muted">Miembros</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($carga_familiar)): ?>
            <!-- Sin miembros registrados -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-friends fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No tienes miembros registrados en tu carga familiar</h5>
                    <p class="text-muted">Los miembros de tu familia aparecerán aquí una vez sean registrados por el administrador.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Listado de miembros -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Listado de Miembros
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">#</th>
                                    <th class="border-0">Cédula</th>
                                    <th class="border-0">Nombre Completo</th>
                                    <th class="border-0">Parentesco</th>
                                    <th class="border-0">Edad</th>
                                    <th class="border-0">Sexo</th>
                                    <th class="border-0">Teléfono</th>
                                    <th class="border-0">Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carga_familiar as $index => $miembro): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($miembro['cedula'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($miembro['nombre_completo']) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($miembro['parentesco'] ?? 'No especificado') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($miembro['edad'] ?? 'N/A') ?> años</td>
                                        <td>
                                            <?php if ($miembro['sexo'] == 'M'): ?>
                                                <i class="fas fa-mars text-primary"></i> Masculino
                                            <?php elseif ($miembro['sexo'] == 'F'): ?>
                                                <i class="fas fa-venus text-danger"></i> Femenino
                                            <?php else: ?>
                                                <i class="fas fa-question-circle text-muted"></i> No especificado
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($miembro['telefono'])): ?>
                                                <i class="fas fa-phone text-success"></i> <?= htmlspecialchars($miembro['telefono']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sin teléfono</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($miembro['fecha_registro'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resumen estadístico -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0"><?= $total_miembros ?></h5>
                            <small class="text-muted">Total Miembros</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-mars fa-2x text-info mb-2"></i>
                            <h5 class="mb-0">
                                <?= count(array_filter($carga_familiar, fn($m) => $m['sexo'] == 'M')) ?>
                            </h5>
                            <small class="text-muted">Hombres</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-venus fa-2x text-danger mb-2"></i>
                            <h5 class="mb-0">
                                <?= count(array_filter($carga_familiar, fn($m) => $m['sexo'] == 'F')) ?>
                            </h5>
                            <small class="text-muted">Mujeres</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.avatar-circle {
    width: 35px;
    height: 35px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
