let chartCat, chartProd, chartVend, chartMetas;

$(document).ready(function() {
    // Escuchar cuando se abre el modal de Cierre para actualizar las fechas visualmente
    $('#modalCierreGerencial').on('show.bs.modal', function () {
        $('#lblCierreDesde').text($('#dash_desde').val());
        $('#lblCierreHasta').text($('#dash_hasta').val());
    });
});

function inicializarDashboard() {
    setRangosRapidos('mes'); // Por defecto carga este mes
}

function abrirFiltrosDashboard() {
    $('#modalFiltrosDashboard').modal('show');
}

function setRangosRapidos(tipo) {
    let hoy = new Date();
    let dDesde = new Date(), dHasta = new Date();

    if (tipo === 'mes') {
        dDesde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        dHasta = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    } else if (tipo === 'mes_pasado') {
        dDesde = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
        dHasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
    } else if (tipo === 'año') {
        dDesde = new Date(hoy.getFullYear(), 0, 1);
        dDesde.setHours(0,0,0,0);
        dHasta = new Date(hoy.getFullYear(), 11, 31);
    } else if (tipo === 'todo') {
        dDesde = new Date(2020, 0, 1);
        dHasta = new Date(2040, 11, 31);
    }
    
    // Ajustar formato para el input type="date"
    $('#dash_desde').val(dDesde.toLocaleDateString('en-CA'));
    $('#dash_hasta').val(dHasta.toLocaleDateString('en-CA'));
    aplicarFiltros(); 
}

function formatoMonedaDash(valor) { 
    return parseFloat(valor).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
}

function escaparHtmlDashboard(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function aplicarFiltros() {
    let fDesde = $('#dash_desde').val();
    let fHasta = $('#dash_hasta').val();
    let tipoReporte = $('#filtro_tipo_reporte').val();
    
    // Solo captura el filtro del vendedor si existe en el DOM
    let fVend = $('#dash_vendedor').length ? $('#dash_vendedor').val() : '';

    Chart.defaults.animation = false;

    // Deshabilitar botón mientras carga
    let btnAplicar = $('#modalFiltrosDashboard .btn-warning, #modalFiltrosDashboard button:contains("Aplicar")');
    let originalText = btnAplicar.html();
    btnAplicar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.get('api/api_dashboard.php', { accion: 'cargar', desde: fDesde, hasta: fHasta, vendedor: fVend })
    .done(function(res) {
        if(res.success) {
            $('#view-general, #view-clientes, #view-vendedores').hide();
            
            // =========================================================
            // CÁLCULOS EXACTOS DE DINERO REAL ABONADO Y USUARIOS
            // =========================================================
            let totalIngresosReales = 0;
            let ventasCerradasReales = 0;
            let vendedoresMap = {};
            let clientesMap = {};
            let vendedoresConPago = {};
            
            if(res.vendedores) {
                res.vendedores.forEach(v => {
                    vendedoresConPago[String(v.ID || v.id)] = v;
                });
            }

            let cotizacionesContadas = new Set();
            let cotizacionesPorVendedor = {};
            let cotizacionesPorCliente = {};
            (res.pagos_periodo || []).forEach(pago => {
                let monto = parseFloat(pago.monto) || 0;
                let idVendedor = pago.id_vendedor || 'desc_general';
                let nombreVendedor = pago.vendedor_nombre || 'Desconocido';
                let nombreCliente = pago.cliente_nombre || 'Desconocido';
                let idCotizacion = String(pago.id_cotizacion);

                totalIngresosReales += monto;
                cotizacionesContadas.add(idCotizacion);

                if(!vendedoresMap[idVendedor]) {
                    let vendedor = vendedoresConPago[String(idVendedor)] || {};
                    vendedoresMap[idVendedor] = {
                        id: idVendedor,
                        nombre: nombreVendedor,
                        ventas: 0,
                        monto: 0,
                        meta: parseFloat(vendedor.meta_ventas || 0),
                        tiene_meta: vendedor.tiene_meta == 1 || vendedor.tiene_meta == '1' ? 1 : 0
                    };
                }
                vendedoresMap[idVendedor].monto += monto;
                if(!cotizacionesPorVendedor[idVendedor]) cotizacionesPorVendedor[idVendedor] = new Set();
                cotizacionesPorVendedor[idVendedor].add(idCotizacion);

                if(!clientesMap[nombreCliente]) {
                    clientesMap[nombreCliente] = { nombre: nombreCliente, compras: 0, gastado: 0 };
                }
                clientesMap[nombreCliente].gastado += monto;
                if(!cotizacionesPorCliente[nombreCliente]) cotizacionesPorCliente[nombreCliente] = new Set();
                cotizacionesPorCliente[nombreCliente].add(idCotizacion);
            });
            ventasCerradasReales = cotizacionesContadas.size;
            Object.keys(cotizacionesPorVendedor).forEach(id => {
                vendedoresMap[id].ventas = cotizacionesPorVendedor[id].size;
            });
            Object.keys(cotizacionesPorCliente).forEach(nombre => {
                clientesMap[nombre].compras = cotizacionesPorCliente[nombre].size;
            });

            let selectVendedor = $('#dash_vendedor');
            if (selectVendedor.length) {
                let currentVal = selectVendedor.val();
                selectVendedor.empty();
                selectVendedor.append('<option value="">Todos (Desempeño General de Empresa)</option>');
                (res.vendedores || []).forEach(v => {
                    let id = String(v.ID || v.id);
                    let nombre = v.nombre || 'Desconocido';
                    let sel = (currentVal === id) ? 'selected' : '';
                    selectVendedor.append(`<option value="${id}" ${sel}>${nombre}</option>`);
                });
            }

            let metaMostrar = parseFloat(res.meta);
            $('#dash_titulo_meta').text(res.titulo_meta);

            if (res.modo_general && $('#dash_vendedor').length) {
                $('#meta_container').html(`
                    <div class="input-group input-group-sm mb-0">
                        <span class="input-group-text border-0 fw-bold bg-white text-dark">$</span>
                        <input type="number" id="input_meta_dash" class="form-control border-0 bg-white text-dark fw-bold px-2 py-0" style="max-width: 100px;" value="${metaMostrar}">
                        <button class="btn btn-warning fw-bold py-0 ms-1" onclick="fijarMetaGlobal()"><i class="bi bi-floppy-fill"></i></button>
                    </div>
                `);
            } else {
                $('#meta_container').html(`<span class="fw-bold text-white fs-5">$${formatoMonedaDash(metaMostrar)}</span>`);
            }

            $('#dash_ingresos').text('$' + formatoMonedaDash(totalIngresosReales));$('#dash_ventas').text(ventasCerradasReales);
            $('#dash_prendas').text(Number(res.prendas_mes || 0).toLocaleString('es-ES'));
            $('#dash_pedidos_finalizados').text(Number(res.pedidos_finalizados_mes || 0).toLocaleString('es-ES'));
            let pagosHtml = (res.pagos_periodo || []).map(pago => {
                let fecha = new Date(pago.fecha_pago + 'T00:00:00').toLocaleDateString('es-VE');
                let referenciaBanco = [pago.referencia, pago.banco].filter(Boolean).map(escaparHtmlDashboard).join(' / ');
                return `<tr>
                    <td class="text-nowrap">${fecha}</td>
                    <td class="text-nowrap">${escaparHtmlDashboard(pago.nro_cotizacion)}</td>
                    <td>${escaparHtmlDashboard(pago.cliente_nombre)}</td>
                    <td>${escaparHtmlDashboard(pago.vendedor_nombre)}</td>
                    <td>${escaparHtmlDashboard(pago.estado_pago)}</td>
                    <td>${referenciaBanco || '—'}</td>
                    <td class="text-end fw-bold text-success">${formatoMonedaDash(pago.monto)}</td>
                </tr>`;
            }).join('');
            $('#tablaPagosDashboard').html(pagosHtml || '<tr><td colspan="7" class="text-center text-muted py-3">No hay pagos registrados en este período.</td></tr>');
            
            if (metaMostrar > 0) {
                let porc = (totalIngresosReales / metaMostrar) * 100;
                if (totalIngresosReales >= metaMostrar) {
                    $('#dash_meta_status').html(`<i class="bi bi-check-circle-fill"></i> ¡Meta superada! (${porc.toFixed(1)}%)`).css('color', '#a7f3d0'); 
                } else {
                    let falta = metaMostrar - totalIngresosReales;
                    $('#dash_meta_status').html(`<i class="bi bi-exclamation-circle-fill"></i> Faltan $${formatoMonedaDash(falta)} (${porc.toFixed(1)}%)`).css('color', '#fecaca'); 
                }
            } else {
                $('#dash_meta_status').html('Meta no definida').css('color', '#fde047');
            }

            // =========================================================
            // VISTAS DEL DASHBOARD
            // =========================================================
            if (tipoReporte === 'general') {
                $('#titulo-reporte-actual').text('Resumen General Estadístico (' + fDesde + ' al ' + fHasta + ')');
                $('#view-general').fadeIn();

                if (chartCat) chartCat.destroy();
                chartCat = new Chart(document.getElementById('chartCategorias'), {
                    type: 'bar', data: { labels: res.categorias.slice(0, 5).map(c => c.nombre), datasets: [{ label: 'Ingresos Estimados ($)', data: res.categorias.slice(0, 5).map(c => parseFloat(c.monto)), backgroundColor: '#0B4C8C', borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false }
                });

                if (chartProd) chartProd.destroy();
                chartProd = new Chart(document.getElementById('chartProductos'), {
                    type: 'doughnut', data: { labels: res.productos.slice(0, 5).map(p => p.nombre), datasets: [{ data: res.productos.slice(0, 5).map(p => parseInt(p.cantidad)), backgroundColor: ['#FF7518', '#0B4C8C', '#198754', '#dc3545', '#ffc107'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                });

                let arrVendedoresGlobal = Object.values(vendedoresMap).sort((a,b) => b.monto - a.monto);
                if(res.modo_general && arrVendedoresGlobal.length > 0) {
                    $('#col_chart_vendedores').show();
                    if (chartVend) chartVend.destroy();
                    chartVend = new Chart(document.getElementById('chartVendedores'), {
                        type: 'pie', data: { labels: arrVendedoresGlobal.map(v => v.nombre), datasets: [{ data: arrVendedoresGlobal.map(v => v.monto), backgroundColor: ['#0B4C8C', '#FF7518', '#17a2b8', '#6c757d', '#6610f2'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                    });
                } else {
                    $('#col_chart_vendedores').hide();
                }

            } else if (tipoReporte === 'vendedores' && res.modo_general) {
                $('#titulo-reporte-actual').text('Seguimiento y Asignación de Metas (' + fDesde + ' al ' + fHasta + ')');
                
                let vendedoresConMeta = Object.values(vendedoresMap).filter(v => v.monto > 0);
                let htmlMetas = '';
                
                if (vendedoresConMeta.length === 0) {
                    htmlMetas = '<tr><td colspan="4" class="text-center text-muted py-3">No hay vendedores con pagos recaudados en este período.</td></tr>';
                } else {
                    vendedoresConMeta.forEach(v => {
                        let meta = parseFloat(v.meta);
                        let recaudado = parseFloat(v.monto);
                        let porc = meta > 0 ? Math.min((recaudado / meta) * 100, 100) : (recaudado > 0 ? 100 : 0);
                        let colorBar = porc >= 100 ? 'bg-success' : (porc >= 50 ? 'bg-warning' : 'bg-danger');
                        
                        let btnAjustar = `
                            <div class="input-group input-group-sm mb-0 justify-content-center">
                                <span class="input-group-text bg-light fw-bold">$</span>
                                <input type="number" class="form-control text-center" id="meta_vend_${v.id}" value="${meta}" style="max-width:90px; font-weight:bold;">
                                <button class="btn btn-primary" onclick="fijarMetaVendedor(${v.id})"><i class="bi bi-check2"></i></button>
                            </div>`;

                        htmlMetas += `<tr>
                            <td class="fw-bold text-dark">${v.nombre}</td>
                            <td class="text-center fw-bold text-success">$${formatoMonedaDash(recaudado)}</td>
                            <td class="text-center align-middle" style="min-width: 150px;">
                                <div class="progress" style="height: 18px; background-color: #e9ecef; border-radius: 10px; border: 1px solid #ccc;">
                                    <div class="progress-bar ${colorBar} progress-bar-striped progress-bar-animated" role="progressbar" style="width: ${porc}%; color: #000; font-weight: bold; text-shadow: 1px 1px 2px #fff;" aria-valuenow="${porc}" aria-valuemin="0" aria-valuemax="100">${porc.toFixed(1)}%</div>
                                </div>
                            </td>
                            <td class="text-center">${btnAjustar}</td>
                        </tr>`;
                    });
                }
                
                $('#tablaVendedoresBody').html(htmlMetas);
                $('#view-vendedores').fadeIn();

            } else if (tipoReporte === 'clientes') {
                $('#titulo-reporte-actual').text('Ranking de Clientes Mayores Pagadores (' + fDesde + ' al ' + fHasta + ')');
                
                let arrClientes = Object.values(clientesMap).sort((a,b) => b.gastado - a.gastado).slice(0, 50); 
                let htmlCli = ''; 
                arrClientes.forEach((c, idx) => { 
                    htmlCli += `<tr>
                        <td class="fw-bold text-muted">${idx+1}</td>
                        <td class="fw-bold text-dark">${c.nombre}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${c.compras}</span></td>
                        <td class="text-end fw-bold text-primary">$${formatoMonedaDash(c.gastado)}</td>
                    </tr>`; 
                });
                $('#tablaTopClientesBody').html(htmlCli || '<tr><td colspan="4" class="text-center text-muted py-4">No hay clientes con pagos en este rango de fecha.</td></tr>');
                $('#view-clientes').fadeIn();
            }

            $('#modalFiltrosDashboard').modal('hide');
        } else {
            Swal.fire('Error', 'Fallo al procesar los datos de ingresos', 'error');
        }
        btnAplicar.prop('disabled', false).html(originalText);
    }).fail(function() {
        Swal.fire('Error', 'Error de red al conectar con el servidor', 'error');
        btnAplicar.prop('disabled', false).html(originalText);
    });
}

function fijarMetaGlobal() {
    let meta = $('#input_meta_dash').val();
    $.post('api/api_dashboard.php', { accion: 'guardar_meta_global', meta: meta }, function(res) {
        if(res.success) {
            Swal.fire({icon: 'success', title: 'Meta Global Actualizada', timer:1500, showConfirmButton:false});
            aplicarFiltros();
        } else {
            Swal.fire({icon: 'error', title: 'Error', text: res.msg});
        }
    }, 'json');
}

function fijarMetaVendedor(id_vend) {
    let meta = $('#meta_vend_' + id_vend).val();
    $.post('api/api_dashboard.php', { accion: 'guardar_meta_vendedor', id_vendedor: id_vend, meta: meta }, function(res) {
        if(res.success) {
            Swal.fire({icon: 'success', title: 'Meta Asignada al Usuario', timer:1500, showConfirmButton:false});
            aplicarFiltros();
        } else {
            Swal.fire({icon: 'error', title: 'Error', text: res.msg});
        }
    }, 'json');
}

function imprimirDashboard() {
    $('#modalFiltrosDashboard').modal('hide');
    setTimeout(() => {
        Swal.fire({ title: 'Generando Reporte PDF...', text: 'Procesando gráficos y tablas, por favor espere.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        
        $('.dash-card').css('box-shadow', 'none');

        let element = document.getElementById('reporte-pdf-container');
        let opt = { 
            margin: 10, 
            filename: 'Reporte_Estadistico_Ventas.pdf', 
            image: { type: 'jpeg', quality: 0.98 }, 
            html2canvas: { scale: 2, useCORS: true, logging: false }, 
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } 
        };
        
        html2pdf().set(opt).from(element).save().then(() => { 
            Swal.close(); 
            $('.dash-card').css('box-shadow', '');
        });
    }, 500);
}

// ==========================================
// NUEVO: FUNCIONES DEL CIERRE GERENCIAL
// ==========================================
window.abrirModalCierre = function() {
    $('#lblCierreDesde').text($('#dash_desde').val());
    $('#lblCierreHasta').text($('#dash_hasta').val());
    
    // Cargar la configuración guardada del robot
    $.post('api/api_dashboard.php', {accion: 'cargar_config_cierre'}, function(res){
        if(res.success && res.data) {
            $('#auto_email_cierre').val(res.data.correo_cierre || '');
            $('#auto_dias_cierre').val(res.data.freq_dias || '1');
            $('#auto_hora_cierre').val(res.data.freq_hora || '18:00');
        }
    }, 'json');

    $('#modalCierreGerencial').modal('show');
};

window.enviarCierreGerencial = function() {
    let email = $('#email_cierre_gerencial').val().trim();
    if (!email) return Swal.fire('Atención', 'Ingrese un correo electrónico válido', 'warning');
    
    let fDesde = $('#dash_desde').val();
    let fHasta = $('#dash_hasta').val();
    
    let btn = $('#btnEnviarCierreM');
    let txtOriginal = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando Enlace...');
    
    $.post('api/api_dashboard.php', {
        accion: 'enviar_cierre_gerencial',
        email: email,
        desde: fDesde,
        hasta: fHasta
    }, function(res) {
        if(res.success) {
            $('#modalCierreGerencial').modal('hide');
            Swal.fire('¡Enviado!', 'Se ha enviado un enlace de descarga seguro al correo proporcionado.', 'success');
            $('#email_cierre_gerencial').val('');
        } else {
            Swal.fire('Error', res.msg || 'No se pudo enviar el correo', 'error');
        }
    }, 'json').fail(function() {
        Swal.fire('Error', 'Fallo de red al enviar el reporte. Verifica tu conexión.', 'error');
    }).always(function() {
        btn.prop('disabled', false).html(txtOriginal);
    });
};

window.guardarAutomatizacionCierre = function() {
    let email = $('#auto_email_cierre').val().trim();
    let dias = $('#auto_dias_cierre').val();
    let hora = $('#auto_hora_cierre').val();

    if (!email || !hora) return Swal.fire('Atención', 'Debes completar el correo y la hora', 'warning');

    let btn = $('#btnGuardarAuto');
    let txtOriginal = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.post('api/api_dashboard.php', {
        accion: 'guardar_config_cierre',
        correo: email,
        dias: dias,
        hora: hora
    }, function(res) {
        if(res.success) {
            Swal.fire('¡Robot Programado!', 'El sistema enviará el enlace de cierre automáticamente a la hora especificada.', 'success');
        } else {
            Swal.fire('Error', res.msg || 'No se pudo guardar la configuración', 'error');
        }
    }, 'json').fail(function() {
        Swal.fire('Error', 'Fallo de red al guardar configuración.', 'error');
    }).always(function() {
        btn.prop('disabled', false).html(txtOriginal);
    });
};