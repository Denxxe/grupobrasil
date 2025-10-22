<!-- grupobrasil/app/views/subadmin/viviendas/index.php -->

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Viviendas de Mi Vereda</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Viviendas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Vereda</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Total Habitantes</th>
                            <th>Total Familias</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($viviendas)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay viviendas registradas en tus veredas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($viviendas as $vivienda): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vivienda['numero'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($vivienda['nombre_vereda'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vivienda['tipo'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($vivienda['estado'] ?? '') === 'Bueno' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($vivienda['estado'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $vivienda['total_habitantes']; ?> habitante(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $vivienda['total_familias']; ?> familia(s)
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
