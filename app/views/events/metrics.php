<?php
// Vista: app/views/events/metrics.php
?>
<div class="container p-4">
  <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($page_title) ?></h1>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card p-4">
      <h3 class="font-semibold">Eventos por Año</h3>
      <ul>
        <?php foreach ($byYear as $year => $cnt): ?>
          <li><?= htmlspecialchars($year) ?>: <?= (int)$cnt ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card p-4">
      <h3 class="font-semibold">Eventos por Mes (<?= htmlspecialchars($year) ?>)</h3>
      <ul>
        <?php for ($m=1;$m<=12;$m++): ?>
          <li><?= str_pad($m,2,'0',STR_PAD_LEFT) ?>: <?= isset($byMonth[$m]) ? (int)$byMonth[$m] : 0 ?></li>
        <?php endfor; ?>
      </ul>
    </div>

    <div class="card p-4">
      <h3 class="font-semibold">Eventos por Categoría</h3>
      <ul>
        <?php foreach ($byCategory as $cat => $cnt): ?>
          <li><?= htmlspecialchars($cat) ?>: <?= (int)$cnt ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

</div>
