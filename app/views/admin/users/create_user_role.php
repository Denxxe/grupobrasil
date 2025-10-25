<?php
// app/views/admin/users/create_user_role.php
// Variables disponibles: $persona, $usuario, $calles, $calles_dirigidas (NUEVA: Array de IDs de calle dirigidas por el usuario)

// ----------------------------------------------------
// 1. INICIALIZACIÓN Y NORMALIZACIÓN DE VARIABLES
// ----------------------------------------------------
$hasUser = !empty($usuario);

// El rol principal del usuario es el que viene de la tabla 'usuario'
$current_id_rol = $usuario['id_rol'] ?? null; 

// Asumimos que los IDs de rol son: 2=Líder de Vereda, 3=Líder de Familia (o Miembro Base)
// Si es 2, el checkbox de Líder de Vereda estará marcado.
$isLiderVereda = $hasUser && (int)$current_id_rol === 2; 

// Si el rol es el de Miembro (3), el checkbox de Líder de Familia estará marcado.
// NOTA: Esto asume una jerarquía simple. Si pueden tener AMBOS roles, la lógica debe ser más compleja.
// Por ahora, asumimos que si es rol 3, se marca Líder de Familia (si la intención es que sea el jefe de su casa).
$isLiderFamilia = $hasUser && (int)$current_id_rol === 3; 

// El array de IDs de las calles que dirige el usuario. Si no se pasa, es un array vacío.
$callesDirigidas = $calles_dirigidas ?? [];

// ----------------------------------------------------
// 2. CONTENIDO HTML Y FORMULARIO
// ----------------------------------------------------
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="h3 mb-4 text-gray-800"><?= $page_title ?></h1>
            <p class="mb-4">Gestionando roles de liderazgo para: 
                <strong>
                    <?= htmlspecialchars($persona['nombres'] ?? '') . ' ' . htmlspecialchars($persona['apellidos'] ?? '') ?> 
                    (C.I: <?= htmlspecialchars($persona['cedula'] ?? 'N/A') ?>)
                </strong>
            </p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Configuración de Liderazgo</h6>
                </div>
                <div class="card-body">
                    <form action="./index.php?route=admin/users/store-user-role" method="POST">
                        <input type="hidden" name="person_id" value="<?= htmlspecialchars($persona['id_persona'] ?? '') ?>">

                        <?php if (!$hasUser): ?>
                            <div class="alert alert-info">
                                Esta persona no tiene una cuenta de usuario. Es necesario crear una para asignarle roles de Líder.
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <label for="email">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-sm-6">
                                    <label for="password">Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <hr>
                        <?php else: ?>
                            <div class="alert alert-success">
                                Esta persona ya tiene una cuenta de usuario con el email: 
                                <strong><?= htmlspecialchars($usuario['email'] ?? 'N/A') ?></strong>.
                            </div>
                            <input type="hidden" name="user_exists" value="1"> 
                            <hr>
                        <?php endif; ?>

                        <div class="form-group mb-4 p-3 border rounded shadow-sm">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_lider_familia" 
                                    name="is_lider_familia" value="3" <?= $isLiderFamilia ? 'checked' : '' ?>>
                                <label class="form-check-label font-weight-bold text-dark" for="is_lider_familia">
                                    Líder de Familia
                                </label>
                                <p class="small text-muted mb-0">Esta persona es el jefe de familia y gestiona la información de su núcleo.</p>
                            </div>
                            <div id="vivienda_assignment" style="<?= $isLiderFamilia ? 'display: block;' : 'display: none;' ?>" class="mt-3 p-3 bg-light rounded">
                                <label class="font-weight-bold">Asignar Vivienda (Opcional)</label>
                                <?php if (!empty($viviendas) && is_array($viviendas)): ?>
                                    <select name="id_vivienda" class="form-control">
                                        <option value="">-- Seleccionar vivienda --</option>
                                        <?php foreach ($viviendas as $vv): ?>
                                            <?php $label = htmlspecialchars(($vv['nombre_calle'] ?? $vv['nombre'] ?? '')) . ' - Nº ' . htmlspecialchars($vv['numero'] ?? ''); ?>
                                            <option value="<?= (int)$vv['id_vivienda'] ?>"><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="small text-muted mt-2">Si seleccionas vivienda, esta persona será marcada como jefe de familia en esa vivienda.</p>
                                <?php else: ?>
                                    <div class="alert alert-warning">No hay viviendas disponibles para asignar desde tu cuenta.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group mb-4 p-3 border rounded shadow-sm">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_lider_vereda" 
                                    name="is_lider_vereda" value="2" <?= $isLiderVereda ? 'checked' : '' ?>>
                                <label class="form-check-label font-weight-bold text-dark" for="is_lider_vereda">
                                    Líder de Vereda
                                </label>
                                <p class="small text-muted mb-2">Esta persona dirige y administra una o más veredas/calles.</p>
                            </div>
                            
                            <div id="vereda_management" style="<?= $isLiderVereda ? 'display: block;' : 'display: none;' ?>" class="mt-3 p-3 bg-light rounded">
                                <label class="font-weight-bold">Veredas a Dirigir:</label>
                                <div class="row">
                                    <?php 
                                    if (isset($calles) && is_array($calles)):
                                        foreach ($calles as $calle): 
                                            $calleId = htmlspecialchars($calle['id_calle'] ?? '');
                                            $calleNombre = htmlspecialchars($calle['nombre'] ?? 'Sin Nombre');
                                            
                                            // LÓGICA DE PRESELECCIÓN CORREGIDA Y COMPLETADA
                                            // Comprueba si el ID de la calle está en el array de calles dirigidas.
                                            $isAssigned = in_array($calle['id_calle'], $callesDirigidas);
                                    ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="calles_liderazgo[]" 
                                                        value="<?= $calleId ?>" 
                                                        id="calle_<?= $calleId ?>"
                                                        <?= $isAssigned ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="calle_<?= $calleId ?>">
                                                        <?= $calleNombre ?>
                                                    </label>
                                                </div>
                                            </div>
                                    <?php 
                                        endforeach; 
                                    endif; 
                                    ?>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block mt-4">
                            Guardar Configuración de Liderazgo
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center mt-3">
                <a href="./index.php?route=admin/users/personas" class="text-secondary"><i class="fas fa-arrow-left"></i> Volver al listado de habitantes</a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const liderVeredaCheckbox = document.getElementById('is_lider_vereda');
    const veredaManagementDiv = document.getElementById('vereda_management');

    liderVeredaCheckbox.addEventListener('change', function() {
        if (this.checked) {
            veredaManagementDiv.style.display = 'block';
        } else {
            veredaManagementDiv.style.display = 'none';
        }
    });
    
    // Asegurar que el estado inicial del div de manejo de veredas sea correcto al cargar la página
    if (liderVeredaCheckbox.checked) {
        veredaManagementDiv.style.display = 'block';
    }

    // Limitar selección de veredas a máximo 2
    const veredaCheckboxes = veredaManagementDiv.querySelectorAll('input[type="checkbox"][name="calles_liderazgo[]"]');
    function limitVeredas(e) {
        const checked = veredaManagementDiv.querySelectorAll('input[type="checkbox"][name="calles_liderazgo[]"]:checked');
        if (checked.length > 2) {
            // Desmarcar la casilla recién marcada
            e.target.checked = false;
            alert('Solo puedes asignar un máximo de 2 veredas a un Líder de Vereda.');
        }
    }
    veredaCheckboxes.forEach(cb => cb.addEventListener('change', limitVeredas));
    // Toggle vivienda assignment visibility
    const liderFamiliaCheckbox = document.getElementById('is_lider_familia');
    const viviendaAssignmentDiv = document.getElementById('vivienda_assignment');
    if (liderFamiliaCheckbox) {
        liderFamiliaCheckbox.addEventListener('change', function() {
            if (this.checked) viviendaAssignmentDiv.style.display = 'block';
            else viviendaAssignmentDiv.style.display = 'none';
        });
        // Estado inicial
        if (liderFamiliaCheckbox.checked) viviendaAssignmentDiv.style.display = 'block';
    }
});
</script>