<?php
// Vista: user/pagos/index.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Pagos / Beneficios disponibles</h1>

    <div class="mt-4">
        <a href="./index.php?route=user/pagos/historial" class="btn btn-outline-secondary mb-3">Ver mi historial de pagos</a>

        <div class="row g-3">
        <?php foreach (($activos ?? []) as $p): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-2"><?=htmlspecialchars($p['nombre_periodo'])?></h5>
                        <p class="mb-1"><strong>Monto:</strong> <?=htmlspecialchars($p['monto'])?></p>
                        <p class="mb-3 text-muted"><strong>Fecha límite:</strong> <?=htmlspecialchars($p['fecha_limite'])?></p>
                        <div class="mt-auto d-flex gap-2">
                            <a class="btn btn-primary btn-sm" href="./index.php?route=user/pagos/detalle&id=<?=intval($p['id_periodo'])?>">Ver / Pagar</a>
                            <a class="btn btn-outline-secondary btn-sm" href="./index.php?route=user/pagos/detalle&id=<?=intval($p['id_periodo'])?>">Más info</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
