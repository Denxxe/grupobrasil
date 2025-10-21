<?php
// NOTA: Este archivo asume que $usuarios, $noticias, y $page_title
// están definidos y cargados en el scope de la vista.

// Definición de colores base para la marca "Grupo Brasil" (Rojo Oscuro y Dorado/Amarillo)
$primaryColor = '#800000';  // Rojo Oscuro (Bordeaux)
$secondaryColor = '#FFC107'; // Amarillo/Dorado
$neutralColor = '#495057';  // Gris para el resto
?>

<div class="container mx-auto p-4 md:p-8">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-8 border-b-4 border-red-700 pb-2">
       Reportes
    </h1>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Tarjeta de Resumen 1: Total de Usuarios -->
        <div class="bg-white shadow-xl rounded-2xl p-6 border-l-8 border-red-700 transition duration-300 hover:shadow-2xl">
            <div class="flex items-center">
                <span class="text-4xl text-red-700 mr-4">👥</span>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total de Usuarios</p>
                    <p class="text-3xl font-bold text-gray-900" id="totalUsuarios"><?php echo count($usuarios); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tarjeta de Resumen 2: Total de Noticias -->
        <div class="bg-white shadow-xl rounded-2xl p-6 border-l-8 border-yellow-500 transition duration-300 hover:shadow-2xl">
            <div class="flex items-center">
                <span class="text-4xl text-yellow-500 mr-4">📰</span>
                <div>
                    <p class="text-sm font-medium text-gray-500">Noticias Publicadas</p>
                    <p class="text-3xl font-bold text-gray-900" id="totalNoticias"><?php echo count($noticias); ?></p>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Resumen 3: Total de Roles Únicos -->
        <?php
            // Calcular roles únicos para el resumen
            $rolesUnicos = array_unique(array_column($usuarios, 'id_rol'));
        ?>
        <div class="bg-white shadow-xl rounded-2xl p-6 border-l-8 border-gray-500 transition duration-300 hover:shadow-2xl">
            <div class="flex items-center">
                <span class="text-4xl text-gray-500 mr-4">🔑</span>
                <div>
                    <p class="text-sm font-medium text-gray-500">Roles Activos</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo count($rolesUnicos); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <!-- Gráfico 1: Distribución de Roles (Torta/Dona) -->
        <div class="bg-white shadow-2xl rounded-2xl p-6 transition duration-300 hover:shadow-3xl">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4 border-b pb-2">Distribución de Roles</h2>
            <div class="h-80 flex items-center justify-center">
                <canvas id="rolesChart"></canvas>
            </div>
            <p class="text-xs text-gray-500 mt-4">Proporción actual de la base de usuarios por nivel de permiso.</p>
        </div>

        <!-- Gráfico 2: Noticias por Mes (Barras) -->
        <div class="bg-white shadow-2xl rounded-2xl p-6 transition duration-300 hover:shadow-3xl">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4 border-b pb-2">Noticias Publicadas por Mes</h2>
            <div class="h-80">
                <canvas id="newsChart"></canvas>
            </div>
            <p class="text-xs text-gray-500 mt-4">Tendencia de publicaciones en los últimos 12 meses (si los datos lo permiten).</p>
        </div>
    </div>

</div>

<!-- Carga de Chart.js y Script de Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    // Colores basados en la marca
    const PRIMARY_COLOR = '<?php echo $primaryColor; ?>';
    const SECONDARY_COLOR = '<?php echo $secondaryColor; ?>';
    const NEUTRAL_COLOR = '<?php echo $neutralColor; ?>';

    // === Datos PHP → JS ===
    const usuarios = <?php echo json_encode($usuarios); ?>;
    const noticias = <?php echo json_encode($noticias); ?>;

    /**
     * Procesa los datos de usuarios para la distribución de roles.
     * @returns {object} {labels: string[], data: number[], colors: string[]}
     */
    function processRolesData(users) {
        let counts = {};
        users.forEach(u => {
            let roleName;
            switch (parseInt(u.id_rol)) {
                case 1: roleName = 'Administrador'; break;
                case 2: roleName = 'Líder (Vereda/Familia)'; break;
                default: roleName = 'Habitante Comunitario'; break; // Asumo rol 3 o default
            }
            counts[roleName] = (counts[roleName] || 0) + 1;
        });

        const labels = Object.keys(counts);
        const data = Object.values(counts);
        
        // Asigna colores de marca (Administrador/Líder) y neutral (Habitante)
        const colors = labels.map(label => {
            if (label === 'Administrador') return PRIMARY_COLOR;
            if (label.includes('Líder')) return SECONDARY_COLOR;
            return NEUTRAL_COLOR;
        });

        return { labels, data, colors };
    }

    /**
     * Procesa los datos de noticias para la publicación mensual.
     * @returns {object} {labels: string[], data: number[]}
     */
    function processNewsData(news) {
        let newsByMonth = {};
        
        // 1. Contar las noticias por mes
        news.forEach(n => {
            // Aseguramos que la fecha sea válida antes de intentar formatear
            const date = new Date(n.fecha_publicacion);
            if (!isNaN(date)) {
                // Formato 'MMM' (ej: 'ene', 'feb'). Usamos 'es-ES' para español
                let mes = date.toLocaleString('es-ES', { month: 'short', year: 'numeric' });
                newsByMonth[mes] = (newsByMonth[mes] || 0) + 1;
            }
        });
        
        // 2. Ordenar los meses cronológicamente (opcional pero recomendado)
        // Por simplicidad en este ejemplo, mantenemos el orden de aparición, 
        // pero en un entorno real deberías ordenar por año/mes.

        const labels = Object.keys(newsByMonth);
        const data = Object.values(newsByMonth);
        
        return { labels, data };
    }

    // Ejecución y Dibujo de Gráficos
    document.addEventListener('DOMContentLoaded', function() {
        const roleData = processRolesData(usuarios);
        const newsData = processNewsData(noticias);

        // === Chart 1: Roles (Torta/Dona) ===
        new Chart(document.getElementById('rolesChart'), {
            type: 'doughnut', // Cambiado a dona para mejor estética
            data: {
                labels: roleData.labels,
                datasets: [{
                    label: 'Usuarios por Rol',
                    data: roleData.data,
                    backgroundColor: roleData.colors,
                    borderColor: '#ffffff', // Borde blanco para separar las secciones
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right', // Leyenda a la derecha
                    },
                    title: {
                        display: false
                    }
                }
            }
        });

        // === Chart 2: Noticias (Barras) ===
        new Chart(document.getElementById('newsChart'), {
            type: 'bar',
            data: {
                labels: newsData.labels,
                datasets: [{
                    label: 'Noticias Publicadas',
                    data: newsData.data,
                    backgroundColor: PRIMARY_COLOR,
                    borderRadius: 4, // Bordes redondeados
                    maxBarThickness: 50 // Limita el ancho de la barra
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Asegura que las etiquetas del eje Y sean enteros
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>