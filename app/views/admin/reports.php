<div class="container my-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo htmlspecialchars($page_title); ?></h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Roles -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Distribución de Roles</h2>
            <canvas id="rolesChart"></canvas>
        </div>

        <!-- Noticias por mes -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Noticias Publicadas por Mes</h2>
            <canvas id="newsChart"></canvas>
        </div>
    </div>
</div>

<script>
// === Datos PHP → JS ===
const usuarios = <?php echo json_encode($usuarios); ?>;
const noticias = <?php echo json_encode($noticias); ?>;

// === Procesar Roles ===
let rolesCount = { Admin: 0, Subadmin: 0, Usuario: 0 };
usuarios.forEach(u => {
    switch (parseInt(u.id_rol)) {
        case 1: rolesCount.Admin++; break;
        case 2: rolesCount.Subadmin++; break;
        default: rolesCount.Usuario++; break;
    }
});

// === Procesar Noticias por Mes ===
let newsByMonth = {};
noticias.forEach(n => {
    let mes = new Date(n.fecha_publicacion).toLocaleString('es-ES', { month: 'short' });
    newsByMonth[mes] = (newsByMonth[mes] || 0) + 1;
});

// === Chart Roles (Torta) ===
new Chart(document.getElementById('rolesChart'), {
    type: 'pie',
    data: {
        labels: Object.keys(rolesCount),
        datasets: [{
            data: Object.values(rolesCount),
            backgroundColor: ['#800000', '#E0A800', '#6C757D']
        }]
    }
});

// === Chart Noticias (Barras) ===
new Chart(document.getElementById('newsChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(newsByMonth),
        datasets: [{
            label: 'Noticias',
            data: Object.values(newsByMonth),
            backgroundColor: '#800000'
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
