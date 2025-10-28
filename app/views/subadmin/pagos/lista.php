<?php
// Vista: subadmin/pagos/lista.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Pagos en veredas asignadas</h1>

    <table class="table-auto w-full mt-2">
        <thead><tr><th>Jefe</th><th>Vereda</th><th>Referencia</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach (($pagos ?? []) as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['nombres'] ?? ($p['nombre_completo'] ?? ''))?></td>
                <td><?=htmlspecialchars($p['vereda'] ?? '')?></td>
                <td><?=htmlspecialchars($p['referencia_pago'] ?? $p['referencia'] ?? '')?></td>
                <td><?=htmlspecialchars($p['estado_actual'] ?? $p['estado'] ?? '')?></td>
                <td>
                    <button class="btn btn-sm" onclick="verifyPago(<?=intval($p['id_pago'])?>,'aprobar')">Aprobar</button>
                    <button class="btn btn-sm btn-danger" onclick="verifyPago(<?=intval($p['id_pago'])?>,'rechazar')">Rechazar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script src="./js/pagos.js"></script>
    <script>
    function verifyPago(id, accion){
        const payload = new FormData();
        payload.append('id_pago', id);
        payload.append('accion', accion);
        fetch('./index.php?route=subadmin/pagos/verify', { method: 'POST', body: payload, credentials: 'same-origin' })
        .then(r => r.json()).then(j => { alert(j.message); if(j.ok) location.reload(); });
    }
    </script>
</div>
