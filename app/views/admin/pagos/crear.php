<?php
// Vista: admin/pagos/crear.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Crear Periodo de Pago</h1>

    <div class="mt-4 card shadow-sm">
        <div class="card-body">
            <form id="crearPeriodoForm" method="post" action="./index.php?route=admin/pagos/store" class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nombre del periodo <span class="text-danger">*</span></label>
                    <input id="nombre_periodo" name="nombre_periodo" class="form-control" required maxlength="150">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de beneficio <span class="text-danger">*</span></label>
                    <select id="id_tipo_beneficio" name="id_tipo_beneficio" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach (($tipos_beneficio ?? []) as $tipo): ?>
                            <option value="<?=intval($tipo['id_tipo_beneficio'])?>"><?=htmlspecialchars($tipo['nombre'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Monto <span class="text-danger">*</span></label>
                    <input id="monto" name="monto" class="form-control" type="number" step="0.01" min="0" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha inicio <span class="text-danger">*</span></label>
                    <input id="fecha_inicio" name="fecha_inicio" class="form-control" type="date" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha límite <span class="text-danger">*</span></label>
                    <input id="fecha_limite" name="fecha_limite" class="form-control" type="date" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Instrucciones / datos de pago</label>
                    <textarea id="instrucciones_pago" name="instrucciones_pago" class="form-control" rows="4" maxlength="2000"></textarea>
                    <div class="form-text">Opcional. Incluye instrucciones de transferencia o cuentas a usar.</div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <a href="./index.php?route=admin/pagos/periodos" class="btn btn-secondary">&larr; Volver</a>
                    <button id="crearBtn" class="btn btn-primary" type="submit">Crear periodo</button>
                    <button id="limpiarBtn" type="button" class="btn btn-outline-secondary ms-auto">Limpiar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        const form = document.getElementById('crearPeriodoForm');
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaLimite = document.getElementById('fecha_limite');
        const crearBtn = document.getElementById('crearBtn');
        const limpiarBtn = document.getElementById('limpiarBtn');

        function validateDates(){
            if (!fechaInicio.value || !fechaLimite.value) return true;
            const f1 = new Date(fechaInicio.value);
            const f2 = new Date(fechaLimite.value);
            if (f2 < f1) {
                crearBtn.disabled = true;
                return false;
            }
            crearBtn.disabled = false;
            return true;
        }

        fechaInicio.addEventListener('change', validateDates);
        fechaLimite.addEventListener('change', validateDates);

        limpiarBtn.addEventListener('click', function(){
            form.reset();
            crearBtn.disabled = false;
        });

        form.addEventListener('submit', function(e){
            if (!validateDates()){
                e.preventDefault();
                // Mostrar toast de error si está disponible
                if (window.showToast) window.showToast('La fecha límite no puede ser anterior a la fecha de inicio.', 'error');
                else alert('La fecha límite no puede ser anterior a la fecha de inicio.');
            }
        });
    })();
    </script>
</div>
