<div class="container my-6">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Noticias -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Noticias por Mes</h2>
            <canvas id="subNewsChart"></canvas>
        </div>

        <!-- Comentarios -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Comentarios en el Tiempo</h2>
            <canvas id="commentsChart"></canvas>
        </div>
    </div>
</div>

<script>
// === Datos PHP → JS ===
const noticiasSub = <?php echo json_encode($noticias); ?>;
const comentariosSub = <?php echo json_encode($comentarios); ?>;

// === Procesar Noticias por Mes ===
let subNewsByMonth = {};
noticiasSub.forEach(n => {
    let mes = new Date(n.fecha_publicacion).toLocaleString('es-ES', { month: 'short' });
    subNewsByMonth[mes] = (subNewsByMonth[mes] || 0) + 1;
});

// === Procesar Comentarios por Mes ===
let commentsByMonth = {};
comentariosSub.forEach(c => {
    let mes = new Date(c.fecha_comentario).toLocaleString('es-ES', { month: 'short' });
    commentsByMonth[mes] = (commentsByMonth[mes] || 0) + 1;
});

// === Chart Noticias ===
new Chart(document.getElementById('subNewsChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(subNewsByMonth),
        datasets: [{
            label: 'Noticias',
            data: Object.values(subNewsByMonth),
            backgroundColor: '#800000'
        }]
    }
});

// === Chart Comentarios ===
new Chart(document.getElementById('commentsChart'), {
    type: 'line',
    data: {
        labels: Object.keys(commentsByMonth),
        datasets: [{
            label: 'Comentarios',
            data: Object.values(commentsByMonth),
            borderColor: '#E0A800',
            backgroundColor: 'rgba(224,168,0,0.3)',
            fill: true,
            tension: 0.3
        }]
    }
});
</script>
