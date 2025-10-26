<?php
// app/views/admin/users/create.php
// Variables disponibles: $page_title, $success_message, $error_message, $calles
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">

            <h1 class="h3 mb-4 text-gray-800"><?= $page_title ?></h1>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos del Nuevo Habitante</h6>
                </div>
                <div class="card-body">
                    <form action="./index.php?route=admin/users/store" method="POST">
                        <input type="hidden" name="user_type" value="persona">
                        
                        <div class="form-group row">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="cedula">Cédula de Identidad <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cedula" name="cedula" required maxlength="9"
                        pattern="[0-9]{1,9}" 
                        title="La cédula debe contener solo números (máximo 9 dígitos).">
                            </div>
                            <div class="col-sm-6">
                                <label for="nombres">Nombres <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombres" name="nombres" required maxlength="50"
                        pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" 
                        title="Solo se permiten letras y espacios en los nombres (máximo 50 caracteres).">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label for="apellidos">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="apellidos" name="apellidos" required maxlength="50"
                        pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" 
                        title="Solo se permiten letras y espacios en los apellidos (máximo 50 caracteres).">
                            </div>
                            <div class="col-sm-6">
                                <label for="telefono">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" maxlength="11"
                        pattern="[0-9\s-]{1,11}" 
                        title="Ingrese un teléfono válido (solo números, espacios o guiones, máximo 11 caracteres).">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="id_calle">Vereda/Sector de Residencia <span class="text-danger">*</span></label>
                            <select class="form-control" id="id_calle_residencia" name="id_calle" required>
                                <option value="">Seleccione una vereda</option>
                                <?php 
                                if (isset($calles) && is_array($calles)):
                                    foreach ($calles as $calle): 
                                        $calle_id = htmlspecialchars($calle['id_calle'] ?? '');
                                        $calle_nombre = htmlspecialchars($calle['nombre'] ?? 'Sin Nombre');
                                ?>
                                    <option value="<?= $calle_id ?>">
                                        <?= $calle_nombre ?>
                                    </option>
                                <?php endforeach; 
                                endif; ?>
                            </select>
                        </div>
                        
                        <hr class="mt-4 mb-4">
                        
                        <h6 class="m-0 font-weight-bold text-primary mb-3">Configuración de Cuenta y Rol de Liderazgo</h6>

                        <div class="form-group form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="create_user_account" name="create_user_account" value="1">
                            <label class="form-check-label font-weight-bold" for="create_user_account">Asignar Rol de Líder y Crear Cuenta</label>
                        </div>

                        <div id="user_fields" style="display: none;" class="p-3 border rounded">
                            
                            <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <label for="email">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="30">
                                </div>
                                <div class="col-sm-6">
                                    <label for="password">Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" maxlength="16">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-6">
                                    <label for="confirm_password">Confirmar Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="16">
                                </div>
                            </div>

                            <hr>
                            
                            <div class="form-group">
                                <label for="id_rol_lider">Tipo de Liderazgo a Asignar <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_rol_lider" name="id_rol_lider">
                                    <option value="">Seleccione el Rol de Liderazgo</option>
                                    <option value="3">Líder de Familia</option>
                                    <option value="2">Líder de Vereda</option>
                                </select>
                            </div>

                            <div id="vereda_management" style="display: none;" class="mt-3 p-3 bg-white border rounded">
                                <label class="font-weight-bold">Veredas a Dirigir:</label>
                                <p class="small text-muted">Marque las calles que este líder administrará.</p>
                                <div class="row">
                                    <?php 
                                    if (isset($calles) && is_array($calles)):
                                        foreach ($calles as $calle): 
                                            $calleId = htmlspecialchars($calle['id_calle'] ?? '');
                                            $calleNombre = htmlspecialchars($calle['nombre'] ?? 'Sin Nombre');
                                    ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="calles_liderazgo[]" 
                                                        value="<?= $calleId ?>" 
                                                        id="calle_lider_<?= $calleId ?>">
                                                    <label class="form-check-label" for="calle_lider_<?= $calleId ?>">
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
                            Guardar Habitante y Rol de Líder
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const createAccountCheckbox = document.getElementById('create_user_account');
    const userFieldsDiv = document.getElementById('user_fields');
    const rolLiderSelect = document.getElementById('id_rol_lider');
    const veredaManagementDiv = document.getElementById('vereda_management');
    
    // Campos
    const cedulaInput = document.getElementById('cedula');
    const nombresInput = document.getElementById('nombres');
    const apellidosInput = document.getElementById('apellidos');
    const telefonoInput = document.getElementById('telefono');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    // === 1. FUNCIONES DE FILTRADO DE ENTRADA EN TIEMPO REAL (INPUT) ===

    /**
     * Filtra la entrada del campo para permitir solo números.
     */
    function filterNumeric(event) {
        // Expresión regular que solo permite dígitos (0-9).
        // También permite el guion (-) y el espacio (\s) en el teléfono.
        let regex = (event.target.id === 'telefono') ? /[^0-9\s-]/g : /[^0-9]/g;
        
        const originalValue = event.target.value;
        const filteredValue = originalValue.replace(regex, '');
        
        if (originalValue !== filteredValue) {
            event.target.value = filteredValue;
        }
    }

    /**
     * Filtra la entrada del campo para permitir solo letras y espacios.
     */
    function filterAlphabetic(event) {
        // Expresión regular que solo permite letras (incluyendo acentos y ñ) y espacios.
        const regex = /[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g;
        
        const originalValue = event.target.value;
        const filteredValue = originalValue.replace(regex, '');
        
        if (originalValue !== filteredValue) {
            event.target.value = filteredValue;
        }
    }

    // Aplicar los filtros a los campos
    if (cedulaInput) cedulaInput.addEventListener('input', filterNumeric);
    if (telefonoInput) telefonoInput.addEventListener('input', filterNumeric);
    if (nombresInput) nombresInput.addEventListener('input', filterAlphabetic);
    if (apellidosInput) apellidosInput.addEventListener('input', filterAlphabetic);
    
    // === 2. LÓGICA DE VISIBILIDAD DE CUENTA/ROL ===

    // Función para alternar la visibilidad y requerimiento de los campos de la cuenta
    function toggleUserFields(checked) {
        userFieldsDiv.style.display = checked ? 'block' : 'none';
        
        // Hacemos que los campos de cuenta sean requeridos si el checkbox está marcado
        const requiredAttr = checked ? 'required' : null;
        
        [emailInput, passwordInput, confirmPasswordInput, rolLiderSelect].forEach(input => {
             if (requiredAttr) {
                input.setAttribute('required', requiredAttr);
             } else {
                input.removeAttribute('required');
             }
        });

        if (!checked) {
            veredaManagementDiv.style.display = 'none';
        }
    }

    // Función para alternar la visibilidad de la gestión de veredas
    function toggleVeredaManagement() {
        // ID 2 es Líder de Vereda
        const isLiderVereda = rolLiderSelect.value === '2' && createAccountCheckbox.checked;
        veredaManagementDiv.style.display = isLiderVereda ? 'block' : 'none';
    }

    // Event Listeners para la lógica de visibilidad
    createAccountCheckbox.addEventListener('change', function() {
        toggleUserFields(this.checked);
        if (!this.checked) {
            rolLiderSelect.value = ''; // Resetear el rol si se desmarca
        }
        toggleVeredaManagement();
    });

    rolLiderSelect.addEventListener('change', toggleVeredaManagement);

    // Inicialización al cargar la página 
    toggleUserFields(createAccountCheckbox.checked);
    toggleVeredaManagement();

    // Limitar selección de veredas a máximo 2 en el formulario de creación
    const veredaCheckboxesCreate = veredaManagementDiv.querySelectorAll('input[type="checkbox"][name="calles_liderazgo[]"]');
    function limitVeredasCreate(e) {
        const checked = document.querySelectorAll('input[type="checkbox"][name="calles_liderazgo[]"]:checked');
        if (checked.length > 2) {
            e.target.checked = false;
            alert('Solo puedes asignar un máximo de 2 veredas a un Líder de Vereda.');
        }
    }
    veredaCheckboxesCreate.forEach(cb => cb.addEventListener('change', limitVeredasCreate));

    // === 3. VALIDACIÓN FINAL ANTES DE ENVIAR (submit) ===
    form.addEventListener('submit', function(event) {
        if (createAccountCheckbox.checked) {
            
            // Validación de longitud de contraseña (mínimo 6)
            if (passwordInput.value.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres.');
                event.preventDefault(); 
                passwordInput.focus();
                return;
            }

            // Validación de coincidencia de contraseñas
            if (passwordInput.value !== confirmPasswordInput.value) {
                alert('Las contraseñas no coinciden. Por favor, revísalas.');
                event.preventDefault(); 
                confirmPasswordInput.focus();
                return;
            }
            
            // Validación de que se seleccione un rol de liderazgo
            if (!rolLiderSelect.value) {
                alert('Debe seleccionar un Rol de Liderazgo para la nueva cuenta.');
                event.preventDefault();
                rolLiderSelect.focus();
                return;
            }

            // Validación condicional de Veredas a Dirigir para Líder de Vereda (Rol 2)
            if (rolLiderSelect.value === '2') {
                const checkedVeredas = document.querySelectorAll('input[name="calles_liderazgo[]"]:checked');
                if (checkedVeredas.length === 0) {
                    alert('Como Líder de Vereda, debe seleccionar al menos una vereda a dirigir.');
                    event.preventDefault();
                    veredaManagementDiv.scrollIntoView();
                    return;
                }
            }
        }
        // Las validaciones HTML5 (pattern) se ejecutan automáticamente aquí.
    });
});
</script>