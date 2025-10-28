<!-- grupobrasil/app/views/subadmin/habitantes/edit.php -->
<?php 
// Variables esperadas: $habitante, $persona, $todasVeredas, $veredasAsignadas
?>

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
                    <!-- Cédula: 9 dígitos, solo numérico -->
                    <div class="col-md-6 mb-3">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" 
                                value="<?php echo htmlspecialchars($persona['cedula'] ?? ''); ?>"
                                maxlength="9" 
                                inputmode="numeric" 
                                pattern="[0-9]{1,9}"
                                title="Solo se permiten números (máximo 9 dígitos)."
                                required>
                    </div>
                    
                    <!-- Fecha de Nacimiento -->
                    <div class="col-md-6 mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                value="<?php echo htmlspecialchars($persona['fecha_nacimiento'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <!-- Nombres: 50 caracteres, solo letras/espacios -->
                    <div class="col-md-6 mb-3">
                        <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombres" name="nombres" 
                                value="<?php echo htmlspecialchars($persona['nombres'] ?? ''); ?>" required
                                maxlength="50"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios (máximo 50 caracteres).">
                    </div>
                    
                    <!-- Apellidos: 50 caracteres, solo letras/espacios -->
                    <div class="col-md-6 mb-3">
                        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                value="<?php echo htmlspecialchars($persona['apellidos'] ?? ''); ?>" required
                                maxlength="50"
                                pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+"
                                title="Solo se permiten letras y espacios (máximo 50 caracteres).">
                    </div>
                </div>

                <div class="row">
                    <!-- Sexo -->
                    <div class="col-md-6 mb-3">
                        <label for="sexo" class="form-label">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo">
                            <option value="">Seleccionar...</option>
                            <option value="M" <?php echo ($persona['sexo'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo ($persona['sexo'] ?? '') === 'F' ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                    
                    <!-- Teléfono: 11 dígitos, solo numérico -->
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" 
                                value="<?php echo htmlspecialchars($persona['telefono'] ?? ''); ?>"
                                maxlength="11" 
                                inputmode="numeric" 
                                pattern="[0-9]{11}"
                                title="El teléfono debe tener exactamente 11 dígitos numéricos.">
                    </div>
                </div>

                <div class="row">
                    <!-- Correo Electrónico: Máximo 50 caracteres -->
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" 
                                value="<?php echo htmlspecialchars($persona['correo'] ?? ''); ?>"
                                maxlength="50"
                                title="Máximo 50 caracteres para el correo electrónico.">
                    </div>

                    <!-- Condición -->
                    <div class="col-md-6 mb-3">
                        <label for="condicion" class="form-label">Condición</label>
                        <select class="form-select" id="condicion" name="condicion">
                            <option value="Residente" <?php echo ($habitante['condicion'] ?? '') === 'Residente' ? 'selected' : ''; ?>>Residente</option>
                            <option value="Visitante" <?php echo ($habitante['condicion'] ?? '') === 'Visitante' ? 'selected' : ''; ?>>Visitante</option>
                            <option value="Temporal" <?php echo ($habitante['condicion'] ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <!-- Vereda -->
                    <div class="col-md-6 mb-3">
                        <label for="id_calle" class="form-label">Vereda <span class="text-danger">*</span></label>
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
                    
                    <!-- Número de Casa (select dependiente de Vereda) -->
                    <div class="col-md-6 mb-3">
                        <label for="numero_casa" class="form-label">Número de Casa</label>
                        <select class="form-select" id="numero_casa" name="numero_casa">
                            <option value="">Seleccione una vereda primero...</option>
                            <!-- Opciones serán cargadas por JS al elegir una vereda -->
                        </select>
                    </div>
                </div>

                <!-- Dirección: Máximo 200 caracteres -->
                <div class="col-12 mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control" id="direccion" name="direccion" rows="2"
                        maxlength="200" 
                        title="Máximo 200 caracteres."><?php echo htmlspecialchars($persona['direccion'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="./index.php?route=subadmin/habitantes" class="btn btn-secondary">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Campos que solo aceptan números (Cédula, Teléfono)
        const numericFields = ['cedula', 'telefono'];
        numericFields.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                // Previene la entrada de caracteres que no sean dígitos
                field.addEventListener('keypress', function(event) {
                    // Permitir solo dígitos (0-9)
                    if (event.charCode < 48 || event.charCode > 57) {
                        event.preventDefault();
                    }
                });
            }
        });

        // 3. Select dependiente: cargar viviendas (casas) al elegir vereda
        const veredaSelect = document.getElementById('id_calle');
        const casaSelect = document.getElementById('numero_casa');

        // Valor guardado (si existe) para pre-seleccionar la casa cuando estemos en edición
        const selectedCasa = <?= json_encode($persona['numero_casa'] ?? '') ?>;

        function setCasaPlaceholder() {
            casaSelect.innerHTML = '<option value="">Seleccione una vereda primero...</option>';
            casaSelect.disabled = true;
        }

        async function loadViviendasByCalle(idCalle, preselectNumero) {
            if (!idCalle) {
                setCasaPlaceholder();
                return;
            }

            casaSelect.innerHTML = '<option value="">Cargando casas...</option>';
            casaSelect.disabled = true;

            try {
                const res = await fetch(`./index.php?route=api/viviendas-por-calle&id_calle=${encodeURIComponent(idCalle)}`);
                const data = await res.json();
                if (data.success && Array.isArray(data.viviendas)) {
                    // Construir opciones: el value será el número de la vivienda para mantener compatibilidad
                    casaSelect.innerHTML = '<option value="">Seleccione un número de casa...</option>';
                    data.viviendas.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.numero;
                        opt.textContent = v.numero;
                        // Guardamos id_vivienda como atributo data para usos futuros
                        opt.dataset.idVivienda = v.id_vivienda || '';
                        if (preselectNumero && String(preselectNumero) === String(v.numero)) {
                            opt.selected = true;
                        }
                        casaSelect.appendChild(opt);
                    });
                    casaSelect.disabled = false;
                } else {
                    casaSelect.innerHTML = '<option value="">No hay casas para la vereda seleccionada</option>';
                    casaSelect.disabled = true;
                }
            } catch (err) {
                console.error('Error cargando viviendas:', err);
                casaSelect.innerHTML = '<option value="">Error cargando casas</option>';
                casaSelect.disabled = true;
            }
        }

        // Evento cuando el usuario cambia la vereda
        if (veredaSelect && casaSelect) {
            veredaSelect.addEventListener('change', function() {
                const idCalle = this.value || null;
                loadViviendasByCalle(idCalle, null);
            });

            // Si la vereda ya está seleccionada (edición), cargar viviendas y pre-seleccionar
            const initialCalle = veredaSelect.value || null;
            if (initialCalle) {
                loadViviendasByCalle(initialCalle, selectedCasa);
            } else {
                setCasaPlaceholder();
            }
        }

        // 2. Campos que solo aceptan letras y espacios (Nombres, Apellidos)
        const alphaFields = ['nombres', 'apellidos'];
        alphaFields.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.addEventListener('input', function() {
                    // Reemplaza cualquier caracter que no sea una letra (incluyendo ñ, acentos) o espacio
                    // El `input` se usa para que funcione en pegado y en teclados virtuales
                    this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '');
                });
            }
        });
    });
</script>
