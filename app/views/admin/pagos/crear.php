<?php
// Vista: admin/pagos/crear.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Crear Periodo de Pago</h1>

    <form method="post" action="./index.php?route=admin/pagos/store" class="mt-4">
        <div class="mb-2">
            <label>Nombre del periodo</label>
            <input name="nombre_periodo" class="input" required>
        </div>
        <div class="mb-2">
            <label>Monto</label>
            <input name="monto" class="input" type="number" step="0.01" required>
        </div>
        <div class="mb-2">
            <label>Fecha inicio</label>
            <input name="fecha_inicio" class="input" type="date" required>
        </div>
        <div class="mb-2">
            <label>Fecha límite</label>
            <input name="fecha_limite" class="input" type="date" required>
        </div>
        <div class="mb-2">
            <label>Instrucciones / datos de pago</label>
            <textarea name="instrucciones_pago" class="input" rows="4"></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Crear</button>
    </form>
</div>
