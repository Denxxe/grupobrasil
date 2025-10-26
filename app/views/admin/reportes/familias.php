<?php
// Vista de Reporte de Familias - Grupo Brasil
?>

<div class="container mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">👨‍👩‍👧‍👦 Reporte de Familias</h1>
            <p class="text-gray-600 mt-2">Familias registradas con jefes de hogar y miembros</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            ← Volver
        </a>
    </div>

    <!-- Filtros y Acciones -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" id="filtroFamilias" onkeyup="filtrarFamilias()" 
                    placeholder="Buscar por nombre del jefe, calle, vivienda..." 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>
            
            <div class="flex items-end gap-2">
                <button onclick="exportarExcelFamilias()" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>📊</span> Excel
                </button>
                <button onclick="imprimirReporte()" 
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>🖨️</span> Imprimir
                </button>
            </div>
        </div>
        
        <div class="mt-4 text-sm text-gray-600">
            <span id="contadorRegistros">Cargando...</span>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div id="contenidoReporte" class="space-y-4">
        <!-- Los datos se cargarán aquí -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="./public/js/reportes.js"></script>
<script>
let datosFamilias = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarReporteFamilias();
});

function cargarReporteFamilias() {
    mostrarLoading();
    
    fetch('index.php?route=admin/reporteFamilias')
        .then(res => res.json())
        .then(datos => {
            if (!datos || datos.length === 0) {
                mostrarSinDatos();
                return;
            }
            
            datosFamilias = datos;
            mostrarFamilias(datos);
            actualizarContadorFamilias(datos.length);
        })
        .catch(err => {
            console.error('Error:', err);
            mostrarError('Error al cargar el reporte');
        });
}

function mostrarFamilias(datos) {
    let html = '';
    
    datos.forEach((familia, index) => {
        const miembros = familia.miembros || [];
        const totalMiembros = miembros.length;
        
        html += `
            <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-green-600 familia-card">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">
                            👤 ${familia.jefe_nombre_completo}
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="font-medium">Cédula:</span> ${familia.jefe_cedula || 'N/A'} | 
                            <span class="font-medium">Edad:</span> ${familia.jefe_edad || 'N/A'} años | 
                            <span class="font-medium">Teléfono:</span> ${formatearTelefono(familia.jefe_telefono)}
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Ubicación:</span> ${familia.calle_nombre || 'N/A'} - Casa ${familia.vivienda_numero || 'N/A'}
                        </p>
                    </div>
                    <div class="text-right">
                        ${crearBadge('Jefe de Familia', 'green')}
                        <p class="text-sm text-gray-600 mt-2">
                            <span class="font-semibold text-lg text-green-700">${totalMiembros}</span> miembros
                        </p>
                    </div>
                </div>
                
                ${totalMiembros > 0 ? `
                    <div class="mt-4 border-t pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Miembros de la Familia:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            ${miembros.map(miembro => `
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <p class="font-medium text-gray-900">${miembro.nombre_completo}</p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span class="font-medium">Parentesco:</span> ${miembro.parentesco || 'N/A'}
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <span class="font-medium">Edad:</span> ${miembro.edad || 'N/A'} años | 
                                        <span class="font-medium">Sexo:</span> ${miembro.sexo || 'N/A'}
                                    </p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : '<p class="text-sm text-gray-500 italic mt-4">No hay miembros registrados</p>'}
            </div>
        `;
    });
    
    document.getElementById('contenidoReporte').innerHTML = html;
}

function filtrarFamilias() {
    const filtro = document.getElementById('filtroFamilias').value.toUpperCase();
    const cards = document.querySelectorAll('.familia-card');
    let visibles = 0;
    
    cards.forEach(card => {
        const texto = card.textContent || card.innerText;
        if (texto.toUpperCase().indexOf(filtro) > -1) {
            card.parentElement.style.display = '';
            visibles++;
        } else {
            card.parentElement.style.display = 'none';
        }
    });
    
    actualizarContadorFamilias(visibles, datosFamilias.length);
}

function actualizarContadorFamilias(visibles, total = null) {
    const contador = document.getElementById('contadorRegistros');
    if (contador) {
        if (total !== null) {
            contador.textContent = `Mostrando ${visibles} de ${total} familias`;
        } else {
            contador.textContent = `Total: ${visibles} familias`;
        }
    }
}

function exportarExcelFamilias() {
    // Crear datos para Excel
    const datosExcel = [];
    
    datosFamilias.forEach(familia => {
        // Agregar jefe de familia
        datosExcel.push({
            'Jefe de Familia': familia.jefe_nombre_completo,
            'Cédula Jefe': familia.jefe_cedula || 'N/A',
            'Edad Jefe': familia.jefe_edad || 'N/A',
            'Teléfono': familia.jefe_telefono || 'N/A',
            'Calle': familia.calle_nombre || 'N/A',
            'Vivienda': familia.vivienda_numero || 'N/A',
            'Total Miembros': familia.miembros ? familia.miembros.length : 0,
            'Miembro': 'JEFE',
            'Parentesco': '-',
            'Edad Miembro': '-',
            'Sexo': '-'
        });
        
        // Agregar miembros
        if (familia.miembros && familia.miembros.length > 0) {
            familia.miembros.forEach(miembro => {
                datosExcel.push({
                    'Jefe de Familia': '',
                    'Cédula Jefe': '',
                    'Edad Jefe': '',
                    'Teléfono': '',
                    'Calle': '',
                    'Vivienda': '',
                    'Total Miembros': '',
                    'Miembro': miembro.nombre_completo,
                    'Parentesco': miembro.parentesco || 'N/A',
                    'Edad Miembro': miembro.edad || 'N/A',
                    'Sexo': miembro.sexo || 'N/A'
                });
            });
        }
        
        // Línea en blanco entre familias
        datosExcel.push({});
    });
    
    // Crear libro y hoja
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(datosExcel);
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Familias');
    
    // Descargar
    XLSX.writeFile(wb, `reporte_familias_${obtenerFechaActual()}.xlsx`);
}
</script>

<style media="print">
    @media print {
        .no-print {
            display: none !important;
        }
        
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
