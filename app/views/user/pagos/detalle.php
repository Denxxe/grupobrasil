<?php
// Vista: user/pagos/detalle.php
$periodo = $periodo ?? null;
?>
<div class="p-4">
    <?php if (!$periodo): ?>
        <div class="text-red-600">Periodo no encontrado.</div>
    <?php else: ?>
        <h1 class="text-2xl font-bold"><?=htmlspecialchars($periodo['nombre_periodo'])?></h1>
        <p>Monto: <?=htmlspecialchars($periodo['monto'])?></p>
        <p>Instrucciones: <?=nl2br(htmlspecialchars($periodo['instrucciones_pago']))?></p>

        <form id="formPago" method="post" action="./index.php?route=user/pagos/submit" enctype="multipart/form-data" class="mt-4">
            <input type="hidden" name="id_periodo" value="<?=intval($periodo['id_periodo'])?>">
            <div class="mb-2">
                <label>Método de pago</label>
                <select name="metodo_pago" required class="input">
                    <option value="transferencia">Transferencia</option>
                    <option value="pago_movil">Pago Móvil</option>
                    <option value="efectivo">Efectivo</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Referencia / ID de transferencia</label>
                <input name="referencia_pago" class="input" required>
            </div>
            <div class="mb-2">
                <label>Captura del pago (jpg/png/pdf)</label>
                <input type="file" name="captura[]" accept="image/jpeg,image/png,application/pdf" required multiple>
            </div>
            <button type="button" id="btnEnviarPago" class="btn btn-primary">Enviar pago</button>
        </form>

        <script src="./js/pagos.js"></script>
        <script>
            document.getElementById('btnEnviarPago').addEventListener('click', function(){
                submitPagoForm(document.getElementById('formPago'));
            });
        </script>
    <?php endif; ?>
</div>
