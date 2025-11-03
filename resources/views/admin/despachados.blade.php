<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Reportes del Sistema</title>
    <!-- NOTA: Esta vista está protegida con middleware AdminAccess (solo usuarios con idgroup=0) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 700px;
            margin-top: 50px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-download {
            background-color: #28a745;
            border-color: #28a745;
            padding: 12px 30px;
            font-size: 16px;
        }
        .btn-download:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .loading {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Mensajes de Error/Éxito -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 0; border-radius: 0;">
            <strong>❌ Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 0; border-radius: 0;">
            <strong>✅ Éxito:</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">📊 Descargar Reportes del Sistema</h4>
                    </div>
                    <div class="card-body">
                        <form id="downloadForm">
                            <div class="mb-3">
                                <label for="tipo_reporte" class="form-label">
                                    <strong>Tipo de Reporte:</strong>
                                </label>
                                <select class="form-select" id="tipo_reporte" name="tipo_reporte" required onchange="actualizarPeriodos()">
                                    <option value="">Seleccione un tipo de reporte...</option>
                                    <option value="despachados">📦 Códigos Despachados</option>
                                    <option value="pedidos_alcances">📋 Pedidos + Alcances</option>
                                    <option value="liquidados">💰 Liquidados</option>
                                    <option value="devoluciones">↩️ Devoluciones</option>
                                    <option value="ventas">🛒 Ventas</option>
                                    <option value="facturado">🧾 Facturado</option>
                                </select>
                                <small class="text-muted">Si experimenta problemas, use el botón "Probar Conexión" primero</small>
                            </div>

                            <div class="mb-3">
                                <label for="id_periodo" class="form-label">
                                    <strong>Período Escolar:</strong>
                                </label>
                                <select class="form-select" id="id_periodo" name="id_periodo" required>
                                    <option value="">Seleccione un período...</option>
                                    @if(isset($periodos))
                                        @foreach($periodos as $periodo)
                                            <option value="{{ $periodo->idperiodoescolar }}">{{ $periodo->periodoescolar }}</option>
                                        @endforeach
                                    @else
                                        <option value="26">2024-2025</option>
                                        <option value="25">2023-2024</option>
                                        <option value="24">2022-2023</option>
                                        <option value="23">2021-2022</option>
                                    @endif
                                </select>
                                <!-- <div id="periodo_warning" class="mt-2" style="display: none;">
                                    <small class="text-warning">
                                        ⚠️ <span id="warning_text"></span>
                                    </small>
                                </div> -->
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-download" data-format="csv">
                                    📥 Descargar CSV (Recomendado)
                                </button>
                                <!-- <button type="button" class="btn btn-primary ms-2 btn-download-excel" onclick="descargarExcel()">
                                    📊 Descargar Excel
                                </button>
                                <br><br> -->
                                <!-- <button type="button" class="btn btn-warning ms-2" onclick="descargarLegacy()">
                                    🔄 Método Antiguo (Respaldo)
                                </button>
                                <button type="button" class="btn btn-info ms-2" onclick="probarProcedimiento()">
                                    🔧 Probar Conexión
                                </button> -->
                                <div class="loading mt-3">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Procesando...</span>
                                    </div>
                                    <p class="mt-2">Generando archivo CSV, por favor espere...</p>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- <div class="card-footer text-muted text-center">
                        <small>
                            ℹ️ Los archivos se descargarán automáticamente una vez procesados.
                            <br>
                            <strong>📥 CSV (Recomendado):</strong> Más rápido, soporta 500k+ registros, menor uso de memoria.
                            <br>
                            <strong>📊 Excel:</strong> Mejor formato visual, pero más lento para grandes volúmenes.
                            <br>
                            ⚡ <strong>Para grandes volúmenes (100k+ registros):</strong> CSV toma 5-15 min, Excel 15-30 min.
                            <br>
                            🔄 No cierre la ventana hasta que termine la descarga.
                            <br>
                            <strong>📋 Pedidos + Alcances:</strong> Para períodos > 27 usa sp_pedidos_alcances_new, para ≤ 26 usa sp_pedidos_alcances_old
                        </small>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para actualizar los períodos disponibles según el tipo de reporte
        function actualizarPeriodos() {
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const periodoSelect = document.getElementById('id_periodo');
            // const warningDiv = document.getElementById('periodo_warning');
            const warningText = document.getElementById('warning_text');

            // Limpiar warning
            // warningDiv.style.display = 'none';

            // Si hay un mensaje de error visible, ocultarlo al cambiar el tipo de reporte
            limpiarMensajeError();

            if (tipoReporte === 'pedidos_alcances') {
                // Mostrar warning para pedidos_alcances
                // warningDiv.style.display = 'block';
                // warningText.innerHTML = 'Para períodos > 27 se usa sp_pedidos_alcances_new, para períodos ≤ 26 se usa sp_pedidos_alcances_old';
            }
        }

        // Función para limpiar mensajes de error cuando el usuario cambia selecciones
        // function limpiarMensajeError() {
        //     const loadingDiv = document.querySelector('.loading');
        //     const loadingText = loadingDiv.querySelector('p');
            
        //     // Solo limpiar si hay un mensaje de error, éxito o está visible
        //     if (loadingDiv.style.display === 'block' && (
        //         loadingText.innerHTML.includes('⚠️') || 
        //         loadingText.innerHTML.includes('❌') || 
        //         loadingText.innerHTML.includes('✅')
        //     )) {
        //         loadingDiv.style.display = 'none';
        //         loadingText.innerHTML = 'Generando archivo CSV, por favor espere...';
        //     }
        // }

         // Función para limpiar mensajes de error cuando el usuario cambia selecciones
        function limpiarMensajeError() {
            const loadingDiv = document.querySelector('.loading');
            const loadingText = loadingDiv.querySelector('p');
            const spinner = loadingDiv.querySelector('.spinner-border');
            
            // Solo limpiar si hay un mensaje de error, éxito o está visible
            if (loadingDiv.style.display === 'block' && (
                loadingText.innerHTML.includes('⚠️') || 
                loadingText.innerHTML.includes('❌') || 
                loadingText.innerHTML.includes('✅')
            )) {
                loadingDiv.style.display = 'none';
                loadingText.innerHTML = 'Generando archivo CSV, por favor espere...';
                spinner.style.display = 'block'; // Restaurar spinner para próxima descarga
            }
        }

        // Agregar event listener para cuando cambie el período
        document.getElementById('id_periodo').addEventListener('change', function() {
            limpiarMensajeError();
        });

        document.getElementById('downloadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            descargarArchivo('csv');
        });

        // Función para descargar Excel
        function descargarExcel() {
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const idPeriodo = document.getElementById('id_periodo').value;
            
            if (!tipoReporte || !idPeriodo) {
                alert('Por favor seleccione tipo de reporte y período');
                return;
            }

            descargarArchivo('excel');
        }

        // Función unificada para descargar archivos
        function descargarArchivo(formato) {
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const idPeriodo = document.getElementById('id_periodo').value;
            
            if (!tipoReporte) {
                alert('Por favor seleccione un tipo de reporte');
                return;
            }
            
            if (!idPeriodo) {
                alert('Por favor seleccione un período escolar');
                return;
            }

            // Validar pedidos_alcances según el período
            if (tipoReporte === 'pedidos_alcances') {
                const periodo = parseInt(idPeriodo);
                if (periodo <= 0) {
                    alert('Período inválido para el reporte de Pedidos + Alcances');
                    return;
                }
            }

            // Obtener elementos
            const btnDownload = document.querySelector('.btn-download');
            const btnExcel = document.querySelector('.btn-download-excel');
            const loadingDiv = document.querySelector('.loading');
            const loadingText = loadingDiv.querySelector('p');
            const spinner = loadingDiv.querySelector('.spinner-border');
            const selectTipoReporte = document.getElementById('tipo_reporte');
            const selectPeriodo = document.getElementById('id_periodo');

            // Limpiar cualquier mensaje anterior y preparar para nueva descarga
            loadingText.innerHTML = 'Generando archivo CSV, por favor espere...';
            spinner.style.display = 'block'; // Asegurar que el spinner esté visible
            
            // Deshabilitar botones y selects
            btnDownload.disabled = true;
            if (btnExcel) btnExcel.disabled = true;
            selectTipoReporte.disabled = true;
            selectPeriodo.disabled = true;
            btnDownload.innerHTML = '⏳ Procesando...';
            if (btnExcel) btnExcel.innerHTML = '⏳ Procesando...';
            loadingDiv.style.display = 'block';
            
            const reporteNombre = {
                'despachados': 'Códigos Despachados',
                'pedidos_alcances': 'Pedidos + Alcances',
                'liquidados': 'Liquidados',
                'devoluciones': 'Devoluciones',
                'ventas': 'Ventas',
                'facturado': 'Facturado'
            };

            const formatoTexto = formato === 'excel' ? 'Excel (.xlsx)' : 'CSV';
            loadingText.innerHTML = `Iniciando descarga de ${reporteNombre[tipoReporte]} en formato ${formatoTexto}...<br><small>Para 190k+ registros puede tomar ${formato === 'excel' ? '15-30' : '5-15'} minutos</small>`;

            // MÉTODO MEJORADO CON FETCH PARA MEJOR MANEJO DE ERRORES
            const downloadUrl = `/admin/reportes/${tipoReporte}/${idPeriodo}?formato=${formato}`;
            const extension = formato === 'excel' ? 'xlsx' : 'csv';
            
            loadingText.innerHTML = `Ejecutando consulta y generando ${formatoTexto} de ${reporteNombre[tipoReporte]}...<br><small>No cierre esta ventana</small>`;

            // Usar fetch para mejor manejo de errores
            fetch(downloadUrl)
            .then(response => {
                // Si la respuesta no es exitosa, verificar si es JSON (error del servidor)
                if (!response.ok) {
                    // Verificar si es JSON de error
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `Error HTTP: ${response.status}`);
                        });
                    }
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                }
                
                // Verificar si es una respuesta JSON (sin datos o error)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (data.empty_result) {
                            throw new Error(`No hay datos disponibles para ${reporteNombre[tipoReporte]} en el período seleccionado`);
                        } else {
                            throw new Error(data.message || 'Error desconocido del servidor');
                        }
                    });
                }
                
                // Verificar que sea el formato correcto
                const esValido = formato === 'excel' 
                    ? contentType && (contentType.includes('spreadsheet') || contentType.includes('application/vnd.openxmlformats'))
                    : contentType && (contentType.includes('text/csv') || contentType.includes('application/octet-stream'));
                
                if (!esValido) {
                    throw new Error(`El servidor no retornó un archivo ${formatoTexto} válido`);
                }
                
                // Extraer nombre del archivo de las cabeceras del servidor
                let filename = `${tipoReporte}_${idPeriodo}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.${extension}`;
                
                // Intentar obtener el nombre del archivo de las cabeceras Content-Disposition
                const contentDisposition = response.headers.get('content-disposition');
                if (contentDisposition && contentDisposition.includes('filename=')) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
                    if (matches && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                
                // Crear objeto que incluye el blob y el filename
                return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
                // Crear URL del blob
                const url = window.URL.createObjectURL(blob);
                
                // Crear enlace y descargar
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Limpiar URL del blob
                window.URL.revokeObjectURL(url);
                
                loadingText.innerHTML = `✅ Descarga completada exitosamente en formato ${formatoTexto}!<br><small>Revise su carpeta de descargas</small>`;
                
                // Rehabilitar controles inmediatamente después de la descarga exitosa
                btnDownload.disabled = false;
                if (btnExcel) btnExcel.disabled = false;
                selectTipoReporte.disabled = false;
                selectPeriodo.disabled = false;
                btnDownload.innerHTML = '📥 Descargar CSV (Recomendado)';
                if (btnExcel) btnExcel.innerHTML = '📊 Descargar Excel';
                loadingDiv.style.display = 'none';
                loadingText.innerHTML = 'Generando archivo CSV, por favor espere...';
            })
            .catch(error => {
                // Registrar error en consola para debugging (solo para desarrolladores)
                console.error('Error técnico para debugging:', error);
                
                // Ocultar el spinner pero mantener el área de mensaje visible
                const spinner = loadingDiv.querySelector('.spinner-border');
                spinner.style.display = 'none';
                
                // Mensajes amigables para el usuario final
                if (error.message.includes('No hay datos disponibles')) {
                    loadingText.innerHTML = `<span style="color: orange;">⚠️ ${error.message}</span><br><small>Intente con otro período o tipo de reporte</small>`;
                } else if (error.message.includes('Error HTTP: 500')) {
                    loadingText.innerHTML = `<span style="color: red;">❌ No se pudo procesar su solicitud</span><br><small>Por favor intente nuevamente en unos minutos</small>`;
                } else if (error.message.includes('Error HTTP: 404')) {
                    loadingText.innerHTML = `<span style="color: orange;">⚠️ No hay información disponible para este reporte</span><br><small>Verifique que haya seleccionado el período correcto</small>`;
                } else if (error.message.includes('Error HTTP:')) {
                    loadingText.innerHTML = `<span style="color: red;">❌ Problema de conexión</span><br><small>Verifique su conexión a internet e intente nuevamente</small>`;
                } else {
                    loadingText.innerHTML = `<span style="color: red;">❌ No se pudo descargar el reporte</span><br><small>Por favor intente nuevamente o contacte al administrador</small>`;
                }
                
                // Rehabilitar controles inmediatamente después del error
                btnDownload.disabled = false;
                if (btnExcel) btnExcel.disabled = false;
                selectTipoReporte.disabled = false;
                selectPeriodo.disabled = false;
                btnDownload.innerHTML = '📥 Descargar CSV (Recomendado)';
                if (btnExcel) btnExcel.innerHTML = '📊 Descargar Excel';
                
                // El mensaje de error se queda visible hasta que el usuario cambie alguna selección
            });
        }

        // Función para probar el procedimiento almacenado
        function probarProcedimiento() {
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const idPeriodo = document.getElementById('id_periodo').value;
            
            if (!tipoReporte) {
                alert('Por favor seleccione un tipo de reporte');
                return;
            }
            
            if (!idPeriodo) {
                alert('Por favor seleccione un período escolar');
                return;
            }

            const testUrl = `/admin/reportes/test/${tipoReporte}/${idPeriodo}`;

            fetch(testUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status === 1) {
                    let mensaje = `✅ PRUEBA EXITOSA!\n\n`;
                    mensaje += `📊 Reporte: ${data.tipo_reporte}\n`;
                    mensaje += `📅 Período: ${data.periodo}\n`;
                    mensaje += `📈 Total registros: ${data.total_registros_aproximado}\n`;
                    mensaje += `🏗️ Columnas disponibles: ${data.estructura_columnas.length}\n\n`;
                    mensaje += `Primeras columnas:\n${data.estructura_columnas.slice(0, 5).join(', ')}\n\n`;
                    
                    if (data.procedimiento_usado) {
                        mensaje += `🔧 Procedimiento usado: ${data.procedimiento_usado}\n\n`;
                    }
                    
                    mensaje += `El procedimiento almacenado está funcionando correctamente.\n`;
                    mensaje += `Ahora puede proceder con la descarga completa.`;

                    alert(mensaje);
                } else {
                    alert(`❌ ERROR EN LA PRUEBA:\n\n${data.message}\n\nRevise los logs del servidor para más información.`);
                }
            })
            .catch(error => {
                console.error('Error en prueba:', error);
                alert(`❌ ERROR DE CONEXIÓN:\n\n${error.message}\n\nProblemas posibles:\n1. Servidor no responde\n2. Procedimiento almacenado no existe\n3. Error de base de datos`);
            });
        }

        // Función de respaldo usando el método original
        function descargarLegacy() {
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const idPeriodo = document.getElementById('id_periodo').value;
            
            if (!tipoReporte) {
                alert('Por favor seleccione un tipo de reporte');
                return;
            }
            
            if (!idPeriodo) {
                alert('Por favor seleccione un período escolar');
                return;
            }

            // Solo funciona para despachados por ahora
            if (tipoReporte !== 'despachados') {
                alert('El método de respaldo solo está disponible para "Códigos Despachados".\nPara otros reportes, configure primero las rutas del backend.');
                return;
            }

            // Usar la ruta original
            const legacyUrl = `/admin/despachados/simple/${idPeriodo}`;
            
            // Método de descarga directa
            const link = document.createElement('a');
            link.href = legacyUrl;
            link.download = `codigos_despachados_legacy_${idPeriodo}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.csv`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            alert('🔄 Descarga iniciada usando método de respaldo.\nSi no funciona, revise la configuración del backend.');
        }
    </script>
    </script>
</body>
</html>
