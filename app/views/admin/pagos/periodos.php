<?php
// Vista: admin/pagos/periodos.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Periodos de Pago</h1>

    <div class="mt-4">
        <a href="./index.php?route=admin/pagos/crear" class="btn btn-primary">Crear nuevo periodo</a>
    </div>

    <h2 class="mt-6 font-semibold">Activos</h2>
    <table class="table-auto w-full mt-2">
        <thead><tr><th>Nombre</th><th>Monto</th><th>Fecha Inicio</th><th>Fecha Límite</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach (($activos ?? []) as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['nombre_periodo'])?></td>
                <td><?=htmlspecialchars($p['monto'])?></td>
                <td><?=htmlspecialchars($p['fecha_inicio'])?></td>
                <td><?=htmlspecialchars($p['fecha_limite'])?></td>
                <td>
                    <form method="post" action="./index.php?route=admin/pagos/close" style="display:inline">
                        <input type="hidden" name="id_periodo" value="<?=intval($p['id_periodo'])?>">
                        <button class="btn btn-sm btn-danger" type="submit">Cerrar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mt-6 font-semibold">Historial</h2>
    <table class="table-auto w-full mt-2">
        <thead><tr><th>Nombre</th><th>Monto</th><th>Fechas</th></tr></thead>
        <tbody>
        <?php foreach (($historial ?? []) as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['nombre_periodo'])?></td>
                <td><?=htmlspecialchars($p['monto'])?></td>
                <td><?=htmlspecialchars($p['fecha_inicio']).' - '.htmlspecialchars($p['fecha_limite'])?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
