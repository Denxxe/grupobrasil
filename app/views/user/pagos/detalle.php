<?php
// Vista: user/pagos/detalle.php
$periodo = $periodo ?? null;
?>
<div class="p-4">
    <?php if (!$periodo): ?>
        <div class="text-red-600">Periodo no encontrado.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title"><?=htmlspecialchars($periodo['nombre_periodo'])?></h3>
                <p class="mb-1"><strong>Monto:</strong> <?=htmlspecialchars($periodo['monto'])?></p>
                <p><strong>Instrucciones:</strong><br><?=nl2br(htmlspecialchars($periodo['instrucciones_pago']))?></p>

                <form id="formPago" method="post" action="./index.php?route=user/pagos/submit" enctype="multipart/form-data" class="mt-3">
            <input type="hidden" name="id_periodo" value="<?=intval($periodo['id_periodo'])?>">
            <input type="hidden" name="id_tipo_beneficio" value="<?=intval($periodo['id_tipo_beneficio'] ?? 0)?>">
            <!-- Enviar monto del periodo para evitar insert NULL en la tabla pagos -->
            <input type="hidden" name="monto" value="<?=htmlspecialchars($periodo['monto'])?>">
            <div class="mb-2">
                <label>Método de pago</label>
                <select name="metodo_pago" required class="form-select bg-white text-dark">
                    <option value="transferencia">Transferencia</option>
                    <option value="pago_movil">Pago Móvil</option>
                    <option value="efectivo">Efectivo</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Referencia / ID de transferencia</label>
                <input name="referencia_pago" id="referencia_pago" class="form-control bg-white text-dark" maxlength="20" inputmode="numeric" pattern="[0-9]{1,20}"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,20);">
                <div class="form-text">Máx. 20 dígitos. Solo números. (No requerido si el pago es en efectivo)</div>
            </div>
            <div class="mb-2">
                <label>Captura del pago (jpg/png/pdf)</label>
                <input type="file" name="captura[]" accept="image/jpeg,image/png,application/pdf" multiple class="form-control bg-white text-dark" id="captura_input">
                <div class="form-text">Adjuntar comprobante salvo que el pago sea en efectivo.</div>
            </div>
                    <div class="mt-3">
                        <button type="button" id="btnEnviarPago" class="btn btn-primary">Enviar pago</button>
                        <a href="./index.php?route=user/pagos" class="btn btn-outline-secondary ms-2">Volver</a>
                    </div>
                </form>
            </div>
        </div>

        <script src="./js/pagos.js"></script>
        <script>
            // Toggle required fields depending on payment method
            (function(){
                const form = document.getElementById('formPago');
                const metodoEl = form.querySelector('select[name="metodo_pago"]');
                const refEl = document.getElementById('referencia_pago');
                const fileEl = document.getElementById('captura_input');

                function updateRequirements(){
                    const metodo = metodoEl.value;
                    if (metodo === 'efectivo'){
                        refEl.removeAttribute('required');
                        fileEl.removeAttribute('required');
                    } else {
                        refEl.setAttribute('required', 'required');
                        fileEl.setAttribute('required', 'required');
                    }
                }

                metodoEl.addEventListener('change', updateRequirements);
                // Inicializar estado
                updateRequirements();

                document.getElementById('btnEnviarPago').addEventListener('click', function(){
                    submitPagoForm(form);
                });
            })();
        </script>
    <?php endif; ?>
</div>
