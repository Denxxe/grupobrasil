/**
 * Funciones compartidas para los reportes del sistema
 * Grupo Brasil
 */

// Función para exportar tabla a Excel
function exportarExcel(nombreArchivo, idTabla = 'tablaReporte') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) {
        alert('No se encontró la tabla para exportar');
        return;
    }

    // Crear libro de trabajo
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(tabla);
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
    
    // Descargar archivo
    XLSX.writeFile(wb, `${nombreArchivo}_${obtenerFechaActual()}.xlsx`);
}

// Función para imprimir reporte
function imprimirReporte() {
    window.print();
}

// Función para obtener fecha actual formateada
function obtenerFechaActual() {
    const fecha = new Date();
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Función para formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const d = new Date(fecha);
    if (isNaN(d)) return 'N/A';
    
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return d.toLocaleDateString('es-ES', opciones);
}

// Función para formatear teléfono
function formatearTelefono(telefono) {
    if (!telefono) return 'N/A';
    return telefono;
}

// Función para mostrar loading
function mostrarLoading(contenedorId = 'contenidoReporte') {
    const contenedor = document.getElementById(contenedorId);
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-red-700"></div>
                <p class="mt-4 text-gray-600">Cargando datos...</p>
            </div>
        `;
    }
}

// Función para mostrar error
function mostrarError(mensaje, contenedorId = 'contenidoReporte') {
    const contenedor = document.getElementById(contenedorId);
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="text-center py-12">
                <div class="text-red-600 text-5xl mb-4">⚠️</div>
                <p class="text-red-600 font-semibold">${mensaje}</p>
            </div>
        `;
    }
}

// Función para mostrar mensaje de sin datos
function mostrarSinDatos(contenedorId = 'contenidoReporte') {
    const contenedor = document.getElementById(contenedorId);
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="text-center py-12">
                <div class="text-gray-400 text-5xl mb-4">📋</div>
                <p class="text-gray-600">No hay datos para mostrar</p>
            </div>
        `;
    }
}

// Función para filtrar tabla
function filtrarTabla(inputId, tablaId) {
    const input = document.getElementById(inputId);
    const tabla = document.getElementById(tablaId);
    
    if (!input || !tabla) return;
    
    const filtro = input.value.toUpperCase();
    const filas = tabla.getElementsByTagName('tr');
    
    for (let i = 1; i < filas.length; i++) { // Empezar en 1 para saltar el header
        const fila = filas[i];
        const celdas = fila.getElementsByTagName('td');
        let encontrado = false;
        
        for (let j = 0; j < celdas.length; j++) {
            const celda = celdas[j];
            if (celda) {
                const texto = celda.textContent || celda.innerText;
                if (texto.toUpperCase().indexOf(filtro) > -1) {
                    encontrado = true;
                    break;
                }
            }
        }
        
        fila.style.display = encontrado ? '' : 'none';
    }
}

// Función para ordenar tabla
function ordenarTabla(tablaId, columna) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    
    const tbody = tabla.getElementsByTagName('tbody')[0];
    const filas = Array.from(tbody.getElementsByTagName('tr'));
    
    // Determinar dirección de ordenamiento
    const direccion = tabla.dataset.ordenDireccion === 'asc' ? 'desc' : 'asc';
    tabla.dataset.ordenDireccion = direccion;
    
    // Ordenar filas
    filas.sort((a, b) => {
        const celdaA = a.getElementsByTagName('td')[columna];
        const celdaB = b.getElementsByTagName('td')[columna];
        
        if (!celdaA || !celdaB) return 0;
        
        const valorA = celdaA.textContent.trim();
        const valorB = celdaB.textContent.trim();
        
        // Intentar comparar como números
        const numA = parseFloat(valorA);
        const numB = parseFloat(valorB);
        
        if (!isNaN(numA) && !isNaN(numB)) {
            return direccion === 'asc' ? numA - numB : numB - numA;
        }
        
        // Comparar como texto
        return direccion === 'asc' 
            ? valorA.localeCompare(valorB) 
            : valorB.localeCompare(valorA);
    });
    
    // Reordenar en el DOM
    filas.forEach(fila => tbody.appendChild(fila));
}

// Función para crear badge de estado
function crearBadge(texto, color = 'gray') {
    const colores = {
        'green': 'bg-green-100 text-green-800',
        'red': 'bg-red-100 text-red-800',
        'yellow': 'bg-yellow-100 text-yellow-800',
        'blue': 'bg-blue-100 text-blue-800',
        'gray': 'bg-gray-100 text-gray-800',
        'purple': 'bg-purple-100 text-purple-800'
    };
    
    const claseColor = colores[color] || colores['gray'];
    return `<span class="px-2 py-1 text-xs font-semibold rounded-full ${claseColor}">${texto}</span>`;
}

// Función para actualizar contador de registros
function actualizarContador(total, tablaId = 'tablaReporte') {
    const contador = document.getElementById('contadorRegistros');
    if (contador) {
        // Contar filas visibles
        const tabla = document.getElementById(tablaId);
        if (tabla) {
            const tbody = tabla.getElementsByTagName('tbody')[0];
            const filasVisibles = Array.from(tbody.getElementsByTagName('tr'))
                .filter(fila => fila.style.display !== 'none').length;
            
            contador.textContent = `Mostrando ${filasVisibles} de ${total} registros`;
        } else {
            contador.textContent = `Total: ${total} registros`;
        }
    }
}
