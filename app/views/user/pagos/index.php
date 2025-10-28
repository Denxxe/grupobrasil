<?php
// Vista: user/pagos/index.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Pagos / Beneficios disponibles</h1>

    <div class="mt-4 grid grid-cols-1 gap-4">
        <?php foreach (($activos ?? []) as $p): ?>
            <div class="border p-3 rounded">
                <h3 class="font-semibold"><?=htmlspecialchars($p['nombre_periodo'])?></h3>
                <p>Monto: <?=htmlspecialchars($p['monto'])?></p>
                <p>Fecha límite: <?=htmlspecialchars($p['fecha_limite'])?></p>
                <a class="btn btn-sm" href="./index.php?route=user/pagos/detalle&id=<?=intval($p['id_periodo'])?>">Ver / Pagar</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
