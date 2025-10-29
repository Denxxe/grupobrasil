<?php
// app/views/admin/indicadores.php
// Variables esperadas: byMonth, byYear, byCategory, year, totalUsuarios, totalPagosHoy, variacionPagosAyer

$year = $year ?? date('Y');
$byMonth = $byMonth ?? [];
$byYear = $byYear ?? [];
$byCategory = $byCategory ?? [];
$totalUsuarios = $totalUsuarios ?? 0;
$totalPagosHoy = $totalPagosHoy ?? 0.0;
$variacionPagosAyer = $variacionPagosAyer ?? 0.0;
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="p-4 bg-white rounded shadow-sm">
        <h3 class="text-lg font-semibold">Usuarios</h3>
        <p class="text-3xl font-bold mt-2"><?php echo htmlspecialchars($totalUsuarios); ?></p>
        <p class="text-sm text-gray-500">Total de usuarios registrados</p>
    </div>
    <div class="p-4 bg-white rounded shadow-sm">
        <h3 class="text-lg font-semibold">Pagos hoy</h3>
        <p class="text-3xl font-bold mt-2">$<?php echo number_format((float)$totalPagosHoy, 2, ',', '.'); ?></p>
        <p class="text-sm text-gray-500">Variación vs ayer: <?php echo number_format((float)$variacionPagosAyer, 1); ?>%</p>
    </div>
    <div class="p-4 bg-white rounded shadow-sm">
        <h3 class="text-lg font-semibold">Eventos (año <?php echo htmlspecialchars($year); ?>)</h3>
        <p class="text-3xl font-bold mt-2"><?php echo array_sum(array_column($byMonth ?? [], 'count')) ?: 0; ?></p>
        <p class="text-sm text-gray-500">Eventos registrados en el año</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="p-4 bg-white rounded shadow-sm">
        <h4 class="font-semibold mb-2">Eventos por mes</h4>
        <ul class="list-disc pl-5 text-sm text-gray-700">
            <?php foreach (($byMonth ?? []) as $m): ?>
                <li><?php echo htmlspecialchars($m['month'] . ': ' . $m['count']); ?></li>
            <?php endforeach; ?>
            <?php if (empty($byMonth)) echo '<li>No hay datos.</li>'; ?>
        </ul>
    </div>
    <div class="p-4 bg-white rounded shadow-sm">
        <h4 class="font-semibold mb-2">Eventos por categoría</h4>
        <ul class="list-disc pl-5 text-sm text-gray-700">
            <?php foreach (($byCategory ?? []) as $c): ?>
                <li><?php echo htmlspecialchars($c['categoria'] . ': ' . $c['count']); ?></li>
            <?php endforeach; ?>
            <?php if (empty($byCategory)) echo '<li>No hay datos.</li>'; ?>
        </ul>
    </div>
</div>

<div class="mt-6">
    <a href="./index.php?route=eventos" class="btn btn-primary">Ver calendario de eventos</a>
</div>
