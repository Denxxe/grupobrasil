<!-- grupobrasil/app/views/subadmin/familias/index.php -->

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Familias de Mi Vereda</h1>
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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Familias</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Jefe de Familia</th>
                            <th>Cédula</th>
                            <th>Vereda</th>
                            <th>Casa</th>
                            <th>Teléfono</th>
                            <th>Total Miembros</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($familias)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay familias registradas en tus veredas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($familias as $familia): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($familia['nombres'] . ' ' . $familia['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($familia['cedula'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['nombre_vereda'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['numero_casa'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $familia['total_miembros']; ?> miembro(s)
                                        </span>
                                    </td>
                                    <td>
                                        <a href="./index.php?route=subadmin/verFamilia&id=<?php echo $familia['id_jefe']; ?>" 
                                           class="btn btn-sm btn-info" title="Ver Detalles">
                                            <i class="fas fa-eye"></i> Ver Familia
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
