let fechaActual = new Date(); 
let mesSeleccionado = fechaActual.getMonth() + 1; 
let anioSeleccionado = fechaActual.getFullYear();
const mesesNombres = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

let modoHistorico = false; 
let modalMovimientoObj, modalConfigObj, modalPagarDeudaObj, modalEditarCuentaObj, modalEditarCategoriaObj, modalNuevaCuentaPagarObj, modalDetalleDeudaObj, modalHistorialProveedorObj;
let arrayItemsGasto = [];
let historialData = [];

// Variables Globales Nuevas
let tasaBcvGlobal = 0;
let lastEgresosData = [];

// Función Global Limpiadora de Cadenas Sucias (Para el Ojito)
window.limpiarSeparadoresSucios = function(texto) {
    if (!texto) return [];
    let textoLimpio = String(texto).replace(/ert\{\}/g, '|').replace(/<br\s*\/?>/gi, '|').replace(/[\r\n]+/g, '|');
    return textoLimpio.split('|').map(s => s.trim()).filter(s => s !== '');
};

// Obtener Tasa BCV automaticamente
async function obtenerTasaBCV() {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3500);
        let response = await fetch('https://ve.dolarapi.com/v1/dolares/oficial', { signal: controller.signal });
        clearTimeout(timeoutId);
        let data = await response.json();
        if(data && data.promedio) {
            tasaBcvGlobal = parseFloat(data.promedio);
            return;
        }
    } catch(e) { 
        console.warn("Fallo dolarapi, usando fallback...", e); 
    }
    
    try {
        const controller2 = new AbortController();
        const timeoutId2 = setTimeout(() => controller2.abort(), 3500);
        let res2 = await fetch('https://pydolarvenezuela-api.vercel.app/api/v1/dollar?page=bcv', { signal: controller2.signal });
        clearTimeout(timeoutId2);
        let data2 = await res2.json();
        if(data2 && data2.monitors && data2.monitors.usd && data2.monitors.usd.price) {
            tasaBcvGlobal = parseFloat(data2.monitors.usd.price);
            return;
        }
    } catch(e2) { }

    const fd = new URLSearchParams(); fd.append('accion', 'get_ultima_tasa');
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        if(res.success && res.tasa > 10) tasaBcvGlobal = res.tasa;
    }, null);
}

function actualizarEtiquetaMes() { 
    if (modoHistorico) {
        document.getElementById('lblMesAnio').innerHTML = '<span class="text-success"><i class="bi bi-calendar-range"></i> Historico Completo</span>'; 
    } else {
        document.getElementById('lblMesAnio').innerText = mesesNombres[mesSeleccionado - 1] + ' de ' + anioSeleccionado; 
    }
}

function toggleHistorico() {
    modoHistorico = !modoHistorico;
    actualizarEtiquetaMes();
    recargarTodo();
}

function formatearMoneda(monto) { 
    return (parseFloat(monto) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $'; 
}

function formatoWA(phone) {
    if(!phone) return '';
    let p = phone.replace(/\D/g, ''); 
    if(p.startsWith('0')) p = '58' + p.substring(1); 
    return p;
}

function resetSpinners() {
    document.querySelectorAll('.spinner-border').forEach(el => {
        el.parentElement.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> Error</span>';
    });
}

function fetchSeguro(url, bodyData, onSuccess, onLoadingUI) {
    if(onLoadingUI) onLoadingUI();
    let isFormData = bodyData instanceof FormData;
    let fetchOptions = { method: 'POST', body: bodyData };
    if (!isFormData) { fetchOptions.headers = { 'Content-Type': 'application/x-www-form-urlencoded' }; }

    fetch(url, fetchOptions)
    .then(r => r.text())
    .then(text => {
        try {
            let res = JSON.parse(text);
            if (res.success) onSuccess(res);
            else { Swal.fire('Error', res.msg, 'error'); resetSpinners(); }
        } catch (e) {
            console.error("Respuesta cruda del servidor:", text);
            Swal.fire('Error de Servidor', 'Revisa la consola.', 'error');
            resetSpinners();
        }
    }).catch(err => {
        console.error(err);
        Swal.fire('Error de Red', 'Fallo al conectar.', 'error');
        resetSpinners();
    });
}

function recargarTodo() {
    cargarDashboard(); cargarHistorial(); cargarDesgloseIngresos(); cargarDesgloseEgresos(); abrirListaCobrar(); abrirListaPagar();
}

function cargarDashboard() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_resumen_principal'); fd.append('mes', modoHistorico ? 0 : mesSeleccionado); fd.append('anio', anioSeleccionado); fd.append('tasa_bcv', tasaBcvGlobal);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        document.getElementById('lbl_egresos_total').innerText = formatearMoneda(res.gastos_mes) + ' -';
        document.getElementById('lbl_egresos_nomina').innerText = formatearMoneda(res.desglose_nomina);
        document.getElementById('lbl_egresos_compras').innerText = formatearMoneda(res.desglose_compras);
        document.getElementById('lbl_egresos_manual').innerText = formatearMoneda(res.desglose_gastos_man);
        document.getElementById('lbl_ingresos_total').innerText = formatearMoneda(res.ingresos_mes) + ' +';
        document.getElementById('lbl_ingresos_cotiz').innerText = formatearMoneda(res.desglose_cotizaciones);
        document.getElementById('lbl_ingresos_manual').innerText = formatearMoneda(res.desglose_ingresos_man);
        
        let utilidades = res.ingresos_mes - res.gastos_mes;
        let colorBalance = utilidades < 0 ? 'text-danger' : 'text-success';
        document.getElementById('lbl_balance_mes').className = `fw-bold mb-0 ${colorBalance}`;
        document.getElementById('lbl_balance_mes').innerText = formatearMoneda(utilidades);
        
        let htmlCuentas = [];
        if(res.cuentas.length === 0) { htmlCuentas.push('<div class="text-center text-white-50 py-2">No hay cuentas registradas</div>'); } 
        else {
            res.cuentas.forEach(c => {
                let icon = c.moneda === 'BS' || c.moneda === 'VES' ? 'bi-cash' : 'bi-currency-dollar'; let color = c.saldo < 0 ? 'text-danger' : 'text-info';
                htmlCuentas.push(`<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                    <div><span class="text-white fw-bold"><i class="bi ${icon} me-2"></i>${c.nombre} <small class="badge bg-secondary p-1">${c.moneda}</small></span><br><small class="text-muted" style="font-size:0.75rem;">Nro: ${c.nro_cuenta || 'N/D'}</small></div>
                                    <span class="fw-bold ${color} fs-5">${parseFloat(c.saldo).toLocaleString('en-US')}</span></div>`);
            });
        }
        document.getElementById('listaCuentasBoveda').innerHTML = htmlCuentas.join('');
    }, () => { document.getElementById('listaCuentasBoveda').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-info"></div></div>'; });
}

function cargarDesgloseIngresos() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_desglose_ingresos'); fd.append('mes', modoHistorico ? 0 : mesSeleccionado); fd.append('anio', anioSeleccionado);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        const tbody = document.getElementById('tbodyDesgloseIngresos');
        if(res.data.length === 0) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay cobros en este periodo.</td></tr>'; return; }
        let html = [];
        res.data.forEach(item => { html.push(`<tr><td class="ps-4 fw-bold text-primary">${item.nro_cotizacion}</td><td>${item.cliente}</td><td>${item.fecha}</td><td class="text-end fw-bold text-success pe-4">${formatearMoneda(item.monto_abonado_mes)}</td></tr>`); });
        tbody.innerHTML = html.join('');
    }, () => { document.getElementById('tbodyDesgloseIngresos').innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-success"></div></td></tr>'; });
}

function cargarDesgloseEgresos() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_desglose_egresos'); fd.append('mes', modoHistorico ? 0 : mesSeleccionado); fd.append('anio', anioSeleccionado);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        lastEgresosData = res.data;
        actualizarOpcionesFiltroProveedor();
        window.renderEgresosFiltrados();
    }, () => { document.getElementById('tbodyDesgloseEgresos').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-danger"></div></td></tr>'; });
}

function actualizarOpcionesFiltroProveedor() {
    const select = document.getElementById('filtroProveedorEgresos');
    if (!select) return;
    let actual = select.value;
    let proveedores = new Set();
    lastEgresosData.forEach(item => {
        if(item.proveedor && item.proveedor.trim() !== '') {
            proveedores.add(item.proveedor);
        }
    });
    let html = '<option value="ALL">Todos los Proveedores / Conceptos</option>';
    Array.from(proveedores).sort().forEach(p => {
        html += `<option value="${p}">${p}</option>`;
    });
    select.innerHTML = html;
    if(proveedores.has(actual)) select.value = actual;
}

window.renderEgresosFiltrados = function() {
    const tbody = document.getElementById('tbodyDesgloseEgresos');
    const filtro = document.getElementById('filtroProveedorEgresos') ? document.getElementById('filtroProveedorEgresos').value : 'ALL';
    
    let filtrados = lastEgresosData;
    if(filtro !== 'ALL') {
        filtrados = lastEgresosData.filter(item => item.proveedor === filtro);
    }

    if(filtrados.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay egresos que mostrar para este filtro.</td></tr>'; return; }
    let html = []; let baseURL = window.location.origin + window.location.pathname.replace('index.php', '').split('?')[0];

    filtrados.forEach(item => {
        let badge = item.tipo.includes('Nomina') ? 'bg-warning text-dark' : 'bg-primary';
        let link_recibo = `${baseURL}documentos/recibo_pago.php?tipo=${item.tipo_bd}&id=${item.id_bd}`;
        let btnWA = '';
        if (item.telefono && item.telefono.trim() !== '') {
            let fPhone = formatoWA(item.telefono);
            let msgTexto1 = "Hola *" + item.proveedor + "*, nos comunicamos del departamento de administracion de Unifuerte. Le escribimos cordialmente para notificarle que su pago ha sido procesado con exito.\n\nPuede ver y descargar su comprobante formal de pago en el siguiente enlace:\n" + link_recibo + "\n\nMuchas gracias por sus servicios!";
            let msgEncoded = encodeURIComponent(msgTexto1);
            btnWA = `<a href="https://wa.me/${fPhone}?text=${msgEncoded}" target="_blank" class="btn btn-sm btn-outline-success border-0" title="Notificar pago"><i class="bi bi-whatsapp"></i></a>`;
        }
        
        let monto_str = formatearMoneda(item.monto);

        let linkHistorial = `<a href="#" onclick="abrirHistorialProveedor('${item.proveedor.replace(/'/g, "\\'")}')" class="text-decoration-none text-primary fw-bold" title="Ver historial crediticio de este proveedor">${item.proveedor}</a>`;
        if (item.tipo_bd === 'nomina' || item.tipo_bd === 'nomina_empleado') linkHistorial = `<span class="text-dark">${item.proveedor}</span>`;

        let btnRecibo = `<a href="${link_recibo}" target="_blank" class="btn btn-sm btn-outline-danger border-0" title="Descargar PDF Oficial"><i class="bi bi-file-earmark-pdf-fill"></i></a>`;

        let btnEditar = '';
        if(item.tipo_bd === 'gasto' || item.tipo_bd === 'os') {
            btnEditar = `<button class="btn btn-sm btn-outline-warning border-0 me-1" onclick="abrirModalEditarGasto(${item.id_bd}, '${item.tipo_bd}')" title="Editar"><i class="bi bi-pencil-fill"></i></button>`;
        }
        html.push(`<tr><td class="ps-4 fw-bold">${item.documento}</td><td><span class="badge ${badge}">${item.tipo}</span></td><td>${linkHistorial}</td><td>${item.fecha}</td><td class="text-end fw-bold text-danger">${monto_str}</td><td class="text-center pe-4 text-nowrap">${btnWA} ${btnEditar} ${btnRecibo}</td></tr>`);
    });
    tbody.innerHTML = html.join('');
};

window.abrirHistorialProveedor = function(nombreProveedor) {
    if(!modalHistorialProveedorObj) return;
    document.getElementById('hp_nombre_proveedor').innerText = nombreProveedor;
    document.getElementById('tbodyHistorialProveedor').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';
    document.getElementById('hp_total_pagado').innerText = '0.00 $';
    document.getElementById('hp_total_deuda').innerText = '0.00 $';
    modalHistorialProveedorObj.show();

    const fd = new URLSearchParams(); 
    fd.append('accion', 'get_historial_proveedor'); 
    fd.append('proveedor', nombreProveedor);

    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        let tbody = document.getElementById('tbodyHistorialProveedor');
        if(res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay registros para este proveedor.</td></tr>';
            return;
        }
        let html = [];
        let tPagado = 0;
        let tDeuda = 0;
        res.data.forEach(item => {
            let colorSt = item.estado === 'Pagado' ? 'text-success' : (item.estado === 'Sin Pagar' ? 'text-danger' : 'text-warning text-dark');
            let mtoTotalStr = formatearMoneda(item.monto_total_usd);
            let mtoPagadoStr = formatearMoneda(item.pagado_usd);
            
            tPagado += item.pagado_usd;
            tDeuda += Math.max(0, item.monto_total_usd - item.pagado_usd);

            html.push(`<tr>
                <td class="text-secondary small">${item.fecha}</td>
                <td class="fw-bold">${item.documento}</td>
                <td><span class="badge bg-secondary">${item.tipo}</span></td>
                <td class="text-end fw-bold">${mtoTotalStr}</td>
                <td class="text-end text-success">${mtoPagadoStr}</td>
                <td class="text-center fw-bold ${colorSt}">${item.estado}</td>
            </tr>`);
        });
        tbody.innerHTML = html.join('');
        document.getElementById('hp_total_pagado').innerText = formatearMoneda(tPagado);
        document.getElementById('hp_total_deuda').innerText = formatearMoneda(tDeuda);
    });
};

function abrirListaCobrar() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_lista_cobrar');
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        const tbody = document.getElementById('tbodyCobrar'); let html = []; let total = 0;
        if(res.data.length === 0) {
            html.push('<tr><td colspan="4" class="text-center py-4">Sin deudas a favor.</td></tr>');
            document.getElementById('tfootCobrar').innerHTML = '';
        } else { 
            res.data.forEach(item => { 
                total += item.deuda; 
                let wa_link = formatoWA(item.telefono) !== '' ? `<a href="https://wa.me/${formatoWA(item.telefono)}?text=Hola,%20nos%20comunicamos%20del%20departamento%20de%20cobranza%20de%20Unifuerte..." target="_blank" class="btn btn-sm btn-success fw-bold shadow-sm" title="Contactar por WhatsApp"><i class="bi bi-whatsapp"></i></a>` : '<span class="badge bg-light text-muted border">S/N</span>';
                
                // Procesar variables seguras para el ojito
                let safeRef = window.limpiarSeparadoresSucios(item.referencias || '').join('|').replace(/'/g, "\\'");
                let safeFecha = window.limpiarSeparadoresSucios(item.fecha || '').join('|').replace(/'/g, "\\'");
                let safeTasa = window.limpiarSeparadoresSucios(item.tasa || '').join('|').replace(/'/g, "\\'");
                let safeUsd = window.limpiarSeparadoresSucios(item.usd || '').join('|').replace(/'/g, "\\'");
                let safeBanco = window.limpiarSeparadoresSucios(item.banco || '').join('|').replace(/'/g, "\\'");
                let safeFotos = window.limpiarSeparadoresSucios(item.fotos || '').join(',').replace(/'/g, "\\'");
                let safeCliName = (item.cliente || '').replace(/'/g, "\\'");

                let btnOjito = `<button class="btn btn-sm btn-outline-primary fw-bold ms-1 shadow-sm" onclick="window.verDetalleHistorialCotizacion(${item.id}, ${item.sub_total}, ${item.descuento}, ${item.iva}, ${item.total}, '${safeRef}', '${safeFotos}', '${safeFecha}', '${safeTasa}', '${safeUsd}', '${safeBanco}', '${item.tipo_cot}', '${item.telefono}', '${safeCliName}')" title="Ver Resumen y Pagos"><i class="bi bi-eye"></i></button>`;

                html.push(`<tr><td class="ps-4 fw-bold text-primary">${item.cotizacion}</td><td>${item.cliente}</td><td class="text-end text-danger fw-bold fs-6 align-middle">${formatearMoneda(item.deuda)}</td><td class="text-center pe-4 text-nowrap">${wa_link} ${btnOjito}</td></tr>`); 
            }); 
            document.getElementById('tfootCobrar').innerHTML = `<tr><td colspan="2" class="text-end fw-bold text-secondary">TOTAL A EXIGIR:</td><td class="text-end fw-bold text-danger fs-5">${formatearMoneda(total)}</td><td></td></tr>`; 
        }
        tbody.innerHTML = html.join('');
    }, () => { document.getElementById('tbodyCobrar').innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-success"></div></td></tr>'; });
}

// =====================================
// FUNCIONALIDAD DEL OJITO DE LA LISTA DE COBRANZA
// =====================================
window.verDetalleHistorialCotizacion = function(id, subtotal, descuento, iva, total, referencias, fotos, fecha, tasa, usd, banco, tipo_cot, tlf_cliente, nombre_cliente) {
    let formato = function(val) { return parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    
    $('#det_modal_subtotal').text(tipo_cot === 'MUESTRA' ? '0.00 $' : formato(subtotal) + ' $');
    $('#det_modal_descuento').text(tipo_cot === 'MUESTRA' ? '-0.00 $' : '-' + formato(descuento) + ' $');
    $('#det_modal_iva').text(tipo_cot === 'MUESTRA' ? '0.00 $' : formato(iva) + ' $');
    $('#det_modal_total').text(tipo_cot === 'MUESTRA' ? '0.00 $' : formato(total) + ' $');

    $('#box_comprobantes_vista').show();
    let arrRef = window.limpiarSeparadoresSucios(referencias);
    let arrFecha = window.limpiarSeparadoresSucios(fecha);
    let arrTasa = window.limpiarSeparadoresSucios(tasa);
    let arrUsd = window.limpiarSeparadoresSucios(usd);
    let arrBanco = window.limpiarSeparadoresSucios(banco);
    
    let arrFotos = (fotos && fotos.trim() !== '') ? fotos.split(',') : [];
    let htmlFotosGral = arrFotos.map(f => `<a href="../images/pagos/${f.trim()}" target="_blank" class="btn btn-sm btn-outline-primary m-1" title="Ver Comprobante"><i class="bi bi-image"></i> Ver</a>`).join('');

    let htmlPagos = '';
    let maxLen = Math.max(arrRef.length, arrFecha.length, arrUsd.length);
    let totalAbonado = 0;

    if (maxLen > 0) {
        for(let i=0; i<maxLen; i++) {
            let f = arrFecha[i] || '-';
            let t = arrTasa[i] ? arrTasa[i] + ' Bs' : '-';
            let numLimpioUsd = arrUsd[i] ? arrUsd[i].replace(/[^\d.-]/g, '') : 0;
            totalAbonado += parseFloat(numLimpioUsd) || 0;
            let u = arrUsd[i] ? '$ ' + parseFloat(numLimpioUsd).toFixed(2) : '-';
            let b = arrBanco[i] || '-';
            let r = arrRef[i] || '-';
            let imgCell = (i === 0) ? `<td rowspan="${maxLen}" class="align-middle">${htmlFotosGral || 'Sin fotos'}</td>` : '';
            htmlPagos += `<tr><td>${f}</td><td>${t}</td><td class="text-success fw-bold">${u}</td><td>${b}</td><td>${r}</td>${imgCell}</tr>`;
        }
    } else {
        htmlPagos += `<tr><td colspan="6" class="text-center text-muted py-2">Enviada a Logística sin abono inicial (Autorización Master)</td></tr>`;
    }
    
    let deudaRestante = parseFloat(total) - totalAbonado;
    if (deudaRestante < 0) deudaRestante = 0;
    
    htmlPagos += `<tr class="table-secondary"><td colspan="2" class="text-end fw-bold text-dark">SUMA PAGADA:</td><td class="text-success fw-bold fs-6">$ ${totalAbonado.toFixed(2)}</td><td colspan="3"></td></tr>`;
    htmlPagos += `<tr class="table-danger"><td colspan="2" class="text-end fw-bold text-danger">DEUDA ACTUAL:</td><td class="text-danger fw-bold fs-5">$ ${deudaRestante.toFixed(2)}</td><td colspan="3"></td></tr>`;

    $('#tbody_pagos_multiples').html(htmlPagos);
    
    let base_url = window.location.origin + window.location.pathname.replace('administracion/index.php', '').split('?')[0] + 'portal_ventas/';
    let url_recibo_general = `recibo_pago.php?id=${id}&abono=total`;
    let full_url_recibo = base_url + url_recibo_general;
    
    let msg = `Hola *${nombre_cliente}*, puedes descargar o ver tu recibo general de pagos actualizado en *Unifuerte C.A.* ingresando al siguiente enlace: ${full_url_recibo}`;
    let waLink = `window.abrirWhatsAppDesdeOjito('${tlf_cliente}', '${encodeURIComponent(msg)}')`;

    $('#box_comprobantes_vista .d-flex.justify-content-between').html(`
        <h6 class="fw-bold text-dark m-0 d-flex align-items-center"><i class="bi bi-receipt me-2"></i> Historial de Pagos y Deuda</h6>
        <div>
            <button type="button" onclick="${waLink}" class="btn btn-sm fw-bold text-white me-1 shadow-sm" style="background-color: #25D366;"><i class="bi bi-whatsapp"></i> Enviar WA</button>
            <a href="${full_url_recibo}" target="_blank" class="btn btn-sm btn-success fw-bold text-white shadow-sm"><i class="bi bi-printer"></i> Imprimir Recibo</a>
        </div>
    `);

    const fd = new URLSearchParams(); fd.append('accion', 'get_detalles_cotizacion'); fd.append('id_cotizacion', id);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        if(res.success) {
            let html = '';
            res.data.forEach(d => {
                let detalleText = d.detalle_diseno ? `<span class="badge bg-light text-secondary border border-secondary">${d.detalle_diseno}</span>` : `<span class="text-muted small">-</span>`;
                let stLinea = tipo_cot === 'MUESTRA' ? '0.00' : formato(d.subtotal_linea);
                let pUnit = tipo_cot === 'MUESTRA' ? '0.00' : formato(d.precio_unitario);
                
                html += `<tr>
                            <td><span class="fw-bold">${d.tipo_prenda}</span><br><small class="text-muted">${d.genero}</small></td>
                            <td>${detalleText}</td>
                            <td class="text-center fw-bold">${d.cantidad}</td>
                            <td class="text-end">${pUnit} $</td>
                            <td class="text-end fw-bold text-dark">${stLinea} $</td>
                        </tr>`;
            });
            $('#detallesCotizacionTablaBody').html(html);
            new bootstrap.Modal(document.getElementById('modalVerDetalleCotizacionAdmin')).show();
        } else { Swal.fire('Error', 'No se pudieron cargar los detalles', 'error'); }
    });
};

window.abrirWhatsAppDesdeOjito = function(tlfRaw, encodedMsg) {
    if (!tlfRaw || tlfRaw.trim() === '' || tlfRaw === 'undefined') {
        return Swal.fire('Error', 'El cliente no tiene un número registrado.', 'warning');
    }
    let num = tlfRaw.replace(/\D/g, ''); 
    if (num.startsWith('0')) { num = '58' + num.substring(1); } 
    else if (!num.startsWith('58') && num.length === 10) { num = '58' + num; }
    window.open(`https://api.whatsapp.com/send?phone=${num}&text=${encodedMsg}`, '_blank');
};

function abrirListaPagar() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_lista_pagar'); fd.append('tasa_bcv', tasaBcvGlobal);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        const tbodyNomina = document.getElementById('tbodyPagarNomina');
        const tbodyCompras = document.getElementById('tbodyPagarCompras');
        const tbodyServicios = document.getElementById('tbodyPagarServicios');
        
        let htmlNomina = [], htmlCompras = [], htmlServicios = [];
        let totNomina = 0, totCompras = 0, totServicios = 0, totalGlobal = 0;

        if(res.data.length === 0) { 
            htmlNomina.push('<tr><td colspan="4" class="text-center py-3">Sin deudas de nomina.</td></tr>');
            htmlCompras.push('<tr><td colspan="4" class="text-center py-3">Sin compras pendientes.</td></tr>');
            htmlServicios.push('<tr><td colspan="4" class="text-center py-3">Sin servicios pendientes.</td></tr>');
        } else { 
            res.data.forEach(item => { 
                let deuda_str = formatearMoneda(item.deuda_usd);
                
                let btnPagar = `<button class="btn btn-sm btn-success fw-bold shadow-sm" onclick="abrirModalPagarDeuda(${item.id_bd}, '${item.tipo_bd}', ${item.deuda_usd}, 'USD')" title="Abonar / Pagar"><i class="bi bi-currency-dollar"></i> Pagar</button>`;

                let linksRecibos = [];
                if (item.tipo_bd === 'gasto' || item.tipo_bd === 'compra') {
                    linksRecibos = String(item.pago_ids || '').split(',').filter(id => /^\d+$/.test(id)).map((pagoId, index) => ({
                        url: `documentos/recibo_pago.php?tipo=${item.tipo_bd}&id=${item.id_bd}&pago_id=${pagoId}`,
                        label: `Recibo del abono ${index + 1}`
                    }));
                } else if (item.tipo_bd === 'os') {
                    linksRecibos = Array.from({length: Number(item.cantidad_pagos) || 0}, (_, index) => ({
                        url: `documentos/recibo_pago.php?tipo=os&id=${item.id_bd}&pago_numero=${index + 1}`,
                        label: `Recibo del abono ${index + 1}`
                    }));
                }
                let btnRecibos = linksRecibos.length ? `
                    <div class="btn-group dropup me-1">
                        <button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Imprimir recibos de pagos parciales"><i class="bi bi-receipt"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">${linksRecibos.map(link => `<li><a class="dropdown-item" href="${link.url}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-2"></i>${link.label}</a></li>`).join('')}</ul>
                    </div>` : '';
                
                let btnEditar = '';
                if(item.tipo_bd === 'gasto' || item.tipo_bd === 'os') {
                    btnEditar = `<button class="btn btn-sm btn-outline-warning border-0 me-1" onclick="abrirModalEditarGasto(${item.id_bd}, '${item.tipo_bd}')" title="Editar"><i class="bi bi-pencil-fill"></i></button>`;
                }

                let btnOrdenCompra = item.tipo_bd === 'gasto' || item.tipo_bd === 'compra'
                    ? `<a class="btn btn-sm btn-outline-primary border-0 me-1" href="documentos/orden_compra.php?tipo=${encodeURIComponent(item.tipo_bd)}&id=${encodeURIComponent(item.id_bd)}" target="_blank" rel="noopener" title="Emitir orden de compra"><i class="bi bi-file-earmark-text"></i></a>`
                    : '';

                let wa_link = formatoWA(item.telefono) !== '' ? `<a href="https://wa.me/${formatoWA(item.telefono)}" target="_blank" class="btn btn-sm btn-outline-secondary border-0 shadow-sm me-1"><i class="bi bi-whatsapp text-success"></i></a>` : '';
                let linkHistorial = item.tipo_bd !== 'nomina_empleado' ? `<a href="#" onclick="abrirHistorialProveedor('${item.proveedor.replace(/'/g, "\\'")}')" class="text-decoration-none text-dark">${item.proveedor}</a>` : `<span class="text-muted small">${item.proveedor}</span>`;

                let subdoc = item.tipo_bd === 'nomina_empleado' ? `<small class="text-muted fw-bold">${item.tipo}</small>` : `<small class="text-muted fw-normal">${item.tipo}</small>`;
                let rowHTML = `<tr><td class="ps-4 fw-bold text-secondary">${item.documento} <br>${subdoc}</td><td class="fw-bold text-dark">${linkHistorial}</td><td class="text-end text-danger fw-bold fs-6 align-middle">${deuda_str}</td><td class="text-center pe-4 text-nowrap align-middle">${wa_link} ${btnOrdenCompra} ${btnEditar} ${btnRecibos} ${btnPagar}</td></tr>`;
                
                if (item.tipo_bd === 'nomina_empleado') {
                    totNomina += item.deuda_usd;
                    htmlNomina.push(rowHTML);
                } else if (item.tipo_bd === 'compra') {
                    totCompras += item.deuda_usd;
                    htmlCompras.push(rowHTML);
                } else {
                    totServicios += item.deuda_usd;
                    htmlServicios.push(rowHTML);
                }
            }); 
        }
        
        if(htmlNomina.length === 0) htmlNomina.push('<tr><td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-check-circle text-success me-2"></i>Todos los pagos de nomina estan solventes.</td></tr>');
        if(htmlCompras.length === 0) htmlCompras.push('<tr><td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-check-circle text-success me-2"></i>No hay insumos pendientes por pagar.</td></tr>');
        if(htmlServicios.length === 0) htmlServicios.push('<tr><td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-check-circle text-success me-2"></i>No hay servicios pendientes.</td></tr>');

        if(tbodyNomina) tbodyNomina.innerHTML = htmlNomina.join('');
        if(tbodyCompras) tbodyCompras.innerHTML = htmlCompras.join('');
        if(tbodyServicios) tbodyServicios.innerHTML = htmlServicios.join('');
        
        if(document.getElementById('badge_nomina')) document.getElementById('badge_nomina').innerText = formatearMoneda(totNomina);
        if(document.getElementById('badge_compras')) document.getElementById('badge_compras').innerText = formatearMoneda(totCompras);
        if(document.getElementById('badge_servicios')) document.getElementById('badge_servicios').innerText = formatearMoneda(totServicios);
        
        totalGlobal = totNomina + totCompras + totServicios;
        if(document.getElementById('lblTotalPasivos')) document.getElementById('lblTotalPasivos').innerText = formatearMoneda(totalGlobal);
        
    }, () => { 
        let err = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-danger"></div></td></tr>';
        if(document.getElementById('tbodyPagarNomina')) document.getElementById('tbodyPagarNomina').innerHTML = err;
        if(document.getElementById('tbodyPagarCompras')) document.getElementById('tbodyPagarCompras').innerHTML = err;
        if(document.getElementById('tbodyPagarServicios')) document.getElementById('tbodyPagarServicios').innerHTML = err;
    });
}

// =====================================
// PAGO UNIVERSAL PARCIAL/TOTAL
// =====================================
function evaluarTipoPagoDeuda() {
    let inputDeuda = document.getElementById('pd_deuda_actual');
    let inputAbono = document.getElementById('pd_monto');
    
    if(!inputDeuda || !inputAbono) return;

    let deuda = parseFloat(inputDeuda.value) || 0;
    let abonoTexto = inputAbono.value;
    let abono = parseFloat(abonoTexto);
    
    let box = document.getElementById('box_tipo_pago');
    let lbl = document.getElementById('lbl_tipo_pago');

    if (abonoTexto.trim() === '' || isNaN(abono) || abono <= 0) {
        box.className = "alert alert-secondary text-center fw-bold mb-3 py-2"; box.style.border = "2px dashed #6c757d"; box.style.backgroundColor = "#f8f9fa";
        lbl.className = "fs-4 text-secondary"; lbl.innerText = "INGRESE UN MONTO";
    } else if (abono >= (deuda - 0.01)) {
        box.className = "alert alert-success text-center fw-bold mb-3 py-2"; box.style.border = "2px dashed #198754"; box.style.backgroundColor = "#e8f5e9";
        lbl.className = "fs-4 text-success"; lbl.innerText = "PAGO TOTAL";
    } else {
        box.className = "alert alert-warning text-center fw-bold mb-3 py-2"; box.style.border = "2px dashed #ffc107"; box.style.backgroundColor = "#fff8e1";
        lbl.className = "fs-4 text-danger"; lbl.innerText = "ABONO / PAGO PARCIAL";
    }
}

// Calculo en vivo del equivalente a Bolivares o Dolares
window.calcularEquivalentePagarDeuda = function() {
    let form = document.getElementById('formPagarDeuda');
    let moneda = form ? form.dataset.moneda : 'USD';
    let monto = parseFloat(document.getElementById('pd_monto').value) || 0;
    let tasa = parseFloat(document.getElementById('pd_tasa').value) || 0;
    let lbl = document.getElementById('lblEquivalenteBs');
    if(!lbl) return;
    
    if (moneda === 'BS' || moneda === 'VES') {
        let usd = tasa > 0 ? (monto / tasa) : 0;
        lbl.parentElement.innerHTML = 'Equivalente a pagar: <span id="lblEquivalenteBs" class="text-primary fs-5">' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' USD</span>';
    } else {
        let bs = monto * tasa;
        lbl.parentElement.innerHTML = 'Equivalente a pagar: <span id="lblEquivalenteBs" class="text-primary fs-5">' + bs.toLocaleString('es-VE', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Bs</span>';
    }
};

// Se ejecuta al hacer clic en el boton Pagar de la tabla
window.abrirModalPagarDeuda = function(id, tipo_deuda, deuda, moneda) {
    if(!modalPagarDeudaObj) return;
    moneda = moneda || 'USD';
    document.getElementById('formPagarDeuda').reset();
    document.getElementById('pd_id').value = id;
    document.getElementById('pd_tipo_deuda').value = tipo_deuda;
    document.getElementById('pd_deuda_actual').value = deuda;
    
    let inputMonto = document.getElementById('pd_monto');
    inputMonto.value = deuda; 
    inputMonto.max = deuda;
    
    // Asignar tasa automatica del dia
    let inputTasa = document.getElementById('pd_tasa');
    if(inputTasa && tasaBcvGlobal > 0) {
        inputTasa.value = tasaBcvGlobal.toFixed(4);
    }
    
    let deuda_str = formatearMoneda(deuda);
    document.getElementById('lbl_pd_deuda').innerText = deuda_str;

    // Ajustar etiquetas segun moneda
    let lblMonto = document.getElementById('lbl_monto_pagar');
    if(lblMonto) {
        lblMonto.innerText = 'Monto a Pagar (USD) *';
    }

    // Almacenar moneda para calculos
    document.getElementById('formPagarDeuda').dataset.moneda = moneda;
    
    evaluarTipoPagoDeuda(); 
    window.calcularEquivalentePagarDeuda(); // Calcula los Bs de inmediato
    modalPagarDeudaObj.show();
};


// =====================================
// REGISTRO Y EDICIÓN DE CUENTA POR PAGAR MANUAL (MULTISERVICIOS)
// =====================================
window.abrirModalNuevaCuentaPagar = function() {
    if(!modalNuevaCuentaPagarObj) return;
    document.getElementById('formNuevaCuentaPagar').reset();
    document.querySelector('#formNuevaCuentaPagar input[name="accion"]').value = 'crear_cuenta_pagar';
    
    // Eliminar inputs ocultos de edición si existen
    let inId = document.getElementById('ncp_id_gasto'); if(inId) inId.remove();
    let inTipo = document.getElementById('ncp_tipo_gasto'); if(inTipo) inTipo.remove();

    document.getElementById('modalNuevaCuentaPagar').querySelector('.modal-title').innerHTML = '<i class="bi bi-file-earmark-plus me-2"></i> Registrar Cuenta por Pagar / Gasto';
    document.getElementById('btnGuardarNCP').innerHTML = '<i class="bi bi-save"></i> Registrar Deuda';

    document.getElementById('ncp_fecha').value = fechaActual.toISOString().split('T')[0];
    document.getElementById('temp_desc_gasto').value = ''; 
    document.getElementById('temp_costo_gasto').value = '';
    arrayItemsGasto = [];
    renderItemsGasto();
    
    const fd = new URLSearchParams(); fd.append('accion', 'get_proveedores');
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        let html = []; html.push('<option value="">Seleccione Proveedor/Beneficiario...</option>');
        res.data.forEach(p => { html.push(`<option value="${p.ID}">${p.nombre}</option>`); });
        document.getElementById('ncp_proveedor').innerHTML = html.join('');
        modalNuevaCuentaPagarObj.show();
    });
};

window.abrirModalEditarGasto = function(id, tipo) {
    if(!modalNuevaCuentaPagarObj) return;
    document.getElementById('formNuevaCuentaPagar').reset();
    document.querySelector('#formNuevaCuentaPagar input[name="accion"]').value = 'editar_cuenta_pagar';
    
    let inputId = document.getElementById('ncp_id_gasto');
    if(!inputId) {
        inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id_gasto';
        inputId.id = 'ncp_id_gasto';
        document.getElementById('formNuevaCuentaPagar').appendChild(inputId);
    }
    inputId.value = id;
    
    let inputTipo = document.getElementById('ncp_tipo_gasto');
    if(!inputTipo) {
        inputTipo = document.createElement('input');
        inputTipo.type = 'hidden';
        inputTipo.name = 'tipo_gasto';
        inputTipo.id = 'ncp_tipo_gasto';
        document.getElementById('formNuevaCuentaPagar').appendChild(inputTipo);
    }
    inputTipo.value = tipo;

    document.getElementById('modalNuevaCuentaPagar').querySelector('.modal-title').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Cuenta por Pagar / Gasto';
    document.getElementById('btnGuardarNCP').innerHTML = '<i class="bi bi-save"></i> Guardar Cambios';

    document.getElementById('temp_desc_gasto').value = ''; 
    document.getElementById('temp_costo_gasto').value = '';
    arrayItemsGasto = [];
    renderItemsGasto();

    const fd = new URLSearchParams(); 
    fd.append('accion', 'get_gasto_editar');
    fd.append('id', id);
    fd.append('tipo', tipo);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        let html = []; html.push('<option value="">Seleccione Proveedor/Beneficiario...</option>');
        res.proveedores.forEach(p => { 
            let sel = (p.ID == res.data.id_proveedor) ? 'selected' : '';
            html.push(`<option value="${p.ID}" ${sel}>${p.nombre}</option>`); 
        });
        document.getElementById('ncp_proveedor').innerHTML = html.join('');
        document.getElementById('ncp_fecha').value = res.data.fecha;
        document.getElementById('formNuevaCuentaPagar').querySelector('input[name="factura"]').value = res.data.factura || '';

        if(res.data.items) {
            arrayItemsGasto = res.data.items;
        }
        renderItemsGasto();

        modalNuevaCuentaPagarObj.show();
    });
};

window.agregarItemGasto = function() {
    let desc = document.getElementById('temp_desc_gasto').value.trim();
    let costo = parseFloat(document.getElementById('temp_costo_gasto').value) || 0;
    if(desc === '' || costo <= 0) { Swal.fire('Error', 'Ingrese una descripcion y un costo valido', 'warning'); return; }
    
    arrayItemsGasto.push({ descripcion: desc, costo: costo });
    document.getElementById('temp_desc_gasto').value = ''; document.getElementById('temp_costo_gasto').value = '';
    renderItemsGasto();
}

window.editarItemGasto = function(index) {
    let item = arrayItemsGasto[index];
    document.getElementById('temp_desc_gasto').value = item.descripcion;
    document.getElementById('temp_costo_gasto').value = item.costo;
    eliminarItemGasto(index);
    document.getElementById('temp_desc_gasto').focus();
}

window.eliminarItemGasto = function(index) {
    arrayItemsGasto.splice(index, 1);
    renderItemsGasto();
}

function renderItemsGasto() {
    let tbody = document.getElementById('tbodyItemsGasto');
    let total = 0;
    if (arrayItemsGasto.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-2"></i>Agrega servicios a la cuenta.</td></tr>';
    } else {
        let html = [];
        arrayItemsGasto.forEach((item, index) => {
            total += item.costo;
            html.push(`<tr>
                <td class="ps-2 text-dark fw-bold" style="font-size:0.9rem;">${item.descripcion}</td>
                <td class="text-end fw-bold text-success">${formatearMoneda(item.costo)}</td>
                <td class="text-center text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-primary border-0 me-1" onclick="editarItemGasto(${index})" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="eliminarItemGasto(${index})" title="Eliminar"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`);
        });
        tbody.innerHTML = html.join('');
    }
    document.getElementById('lbl_ncp_total').innerText = formatearMoneda(total).replace('$', '').trim();
    document.getElementById('ncp_monto_total_hidden').value = total;
    document.getElementById('ncp_detalles_json').value = JSON.stringify(arrayItemsGasto);
}

// =====================================
// LIBRO MAYOR Y GESTOR (CRUD)
// =====================================
function renderHistorialFiltrado() {
    const tbody = document.getElementById('tbodyHistorial');
    const filtro = document.getElementById('historialFiltroTipo') ? document.getElementById('historialFiltroTipo').value : 'ALL';
    const items = filtro === 'ALL' ? historialData : historialData.filter(item => item.tipo === filtro);

    let ingresos = 0;
    let egresos = 0;
    items.forEach(item => {
        if (item.tipo === 'INGRESO') ingresos += Number(item.monto || 0);
        else if (item.tipo === 'EGRESO') egresos += Number(item.monto || 0);
    });

    const totalNeto = ingresos - egresos;
    const ingresosEl = document.getElementById('historialTotalIngresos');
    const egresosEl = document.getElementById('historialTotalEgresos');
    const netoEl = document.getElementById('historialTotalNeto');
    if (ingresosEl) ingresosEl.innerText = formatearMoneda(ingresos);
    if (egresosEl) egresosEl.innerText = formatearMoneda(egresos);
    if (netoEl) {
        netoEl.innerText = formatearMoneda(totalNeto);
        netoEl.className = `fw-bold fs-6 ${totalNeto >= 0 ? 'text-success' : 'text-danger'}`;
    }

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay operaciones para este filtro.</td></tr>';
        return;
    }

    let html = [];
    items.forEach(item => {
        let badge = item.tipo === 'INGRESO' ? '<span class="badge bg-success bg-opacity-10 text-success border border-success">Ingreso</span>' : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Egreso</span>';
        let cM = item.tipo === 'INGRESO' ? 'text-success' : 'text-danger';
        let monto = Math.abs(parseFloat(item.monto) || 0);
        let accion = item.editable
            ? `<button class="btn btn-sm btn-outline-danger" onclick="eliminarMovimiento(${item.id})"><i class="bi bi-arrow-counterclockwise"></i></button>`
            : `<span class="text-muted small">-</span>`;
        html.push(`<tr><td class="ps-4 fw-bold text-secondary">${item.fecha}</td><td>${badge}</td><td><span class="text-dark fw-bold">${item.categoria}</span><br><small class="text-muted"><i class="bi bi-bank"></i> ${item.cuenta}</small></td><td>${item.descripcion}</td><td class="text-end fw-bold fs-6 ${cM}">${formatearMoneda(monto)}</td><td class="text-center pe-4">${accion}</td></tr>`);
    });
    tbody.innerHTML = html.join('');
}

function cargarHistorial() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_historial_movimientos'); fd.append('mes', modoHistorico ? 0 : mesSeleccionado); fd.append('anio', anioSeleccionado);
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        historialData = Array.isArray(res.data) ? res.data : [];
        renderHistorialFiltrado();
    }, () => { document.getElementById('tbodyHistorial').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>'; });
}

function eliminarMovimiento(id) {
    Swal.fire({ title: '¿Revertir Operacion?', text: "El dinero se descontara/reintegrara a tu Boveda automaticamente.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Si, revertir' })
    .then((result) => {
        if (result.isConfirmed) {
            const fd = new URLSearchParams(); fd.append('accion', 'eliminar_movimiento'); fd.append('id', id);
            fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => { Swal.fire('Hecho', 'Saldo revertido con exito.', 'success'); recargarTodo(); });
        }
    });
}

function abrirConfiguracion() { if(modalConfigObj) { modalConfigObj.show(); cargarDatosConfiguracion(); } }

function cargarDatosConfiguracion() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_configuracion');
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        
        const tbCat = document.getElementById('tbodyCategoriasAdmin');
        let htmlCat = []; 
        res.categorias.forEach(c => {
            let badge = c.tipo === 'INGRESO' ? '<span class="badge bg-success">Ingreso</span>' : '<span class="badge bg-danger">Gasto</span>';
            htmlCat.push(`<tr><td class="ps-3 text-muted">${c.id}</td><td class="fw-bold">${c.nombre}</td><td>${badge}</td><td class="text-center pe-3"><button class="btn btn-sm btn-outline-primary me-1" onclick="editarCategoria(${c.id}, '${c.nombre.replace(/'/g, "\\'")}')"><i class="bi bi-pencil"></i> Editar</button><button class="btn btn-sm btn-outline-danger" onclick="eliminarCategoria(${c.id})"><i class="bi bi-trash"></i></button></td></tr>`);
        }); 
        tbCat.innerHTML = htmlCat.join('') || '<tr><td colspan="4" class="text-center py-3 text-muted">No hay categorias</td></tr>';

        const tbCta = document.getElementById('tbodyCuentasAdmin');
        let htmlCta = []; 
        res.cuentas.forEach(c => {
            let col = c.saldo < 0 ? 'text-danger' : 'text-success';
            htmlCta.push(`<tr><td class="ps-3 fw-bold">${c.nombre}</td><td class="text-muted small">${c.nro_cuenta || 'S/N'}</td><td><span class="badge bg-secondary">${c.moneda}</span></td><td class="text-end fw-bold ${col}">${formatearMoneda(c.saldo)}</td><td class="text-center pe-3"><button class="btn btn-sm btn-outline-primary me-1" onclick="editarCuenta(${c.id}, '${c.nombre.replace(/'/g, "\\'")}', '${(c.nro_cuenta || '').replace(/'/g, "\\'")}', '${(c.pm_banco || '').replace(/'/g, "\\'")}', '${(c.pm_cedula || '').replace(/'/g, "\\'")}', '${(c.pm_tlf || '').replace(/'/g, "\\'")}')"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" onclick="eliminarCuenta(${c.id})"><i class="bi bi-trash"></i></button></td></tr>`);
        }); 
        tbCta.innerHTML = htmlCta.join('') || '<tr><td colspan="5" class="text-center py-3 text-muted">No hay cuentas</td></tr>';
        
    }, () => {
        document.getElementById('tbodyCategoriasAdmin').innerHTML = '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border text-primary"></div></td></tr>';
        document.getElementById('tbodyCuentasAdmin').innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border text-primary"></div></td></tr>';
    });
}

function editarCategoria(id, nombre) {
    if(modalConfigObj) modalConfigObj.hide();
    document.getElementById('edit_cat_id').value = id; document.getElementById('edit_cat_nombre').value = nombre;
    setTimeout(() => { if(modalEditarCategoriaObj) modalEditarCategoriaObj.show(); }, 400);
}

function editarCuenta(id, nombre, nro_cuenta, pm_banco, pm_cedula, pm_tlf) {
    if(modalConfigObj) modalConfigObj.hide();
    document.getElementById('edit_cta_id').value = id; document.getElementById('edit_cta_nombre').value = nombre; document.getElementById('edit_cta_nro').value = (nro_cuenta === 'null' || nro_cuenta === 'undefined') ? '' : nro_cuenta; document.getElementById('edit_cta_pmb').value = (pm_banco === 'null' || pm_banco === 'undefined') ? '' : pm_banco; document.getElementById('edit_cta_pmc').value = (pm_cedula === 'null' || pm_cedula === 'undefined') ? '' : pm_cedula; document.getElementById('edit_cta_pmt').value = (pm_tlf === 'null' || pm_tlf === 'undefined') ? '' : pm_tlf;
    setTimeout(() => { if(modalEditarCuentaObj) modalEditarCuentaObj.show(); }, 400);
}

function eliminarCategoria(id) { Swal.fire({ title: '¿Borrar Categoria?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Si, borrar' }).then((result) => { if (result.isConfirmed) { const fd = new URLSearchParams(); fd.append('accion', 'eliminar_categoria'); fd.append('id', id); fetchSeguro('api/api_dashboard.php', fd.toString(), () => cargarDatosConfiguracion()); }});}
function eliminarCuenta(id) { Swal.fire({ title: '¿Borrar Cuenta?', text: "Desaparecera de la Boveda.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Si, borrar' }).then((result) => { if (result.isConfirmed) { const fd = new URLSearchParams(); fd.append('accion', 'eliminar_cuenta'); fd.append('id', id); fetchSeguro('api/api_dashboard.php', fd.toString(), () => { cargarDatosConfiguracion(); cargarDashboard(); }); }});}

function validarMoneda() {
    const moneda = document.getElementById('movMoneda').value; const tasa = document.getElementById('movTasa');
    if(moneda === 'USD') { 
        tasa.value = "1.0000"; 
        tasa.readOnly = true; 
    } else { 
        tasa.readOnly = false; 
        if(tasaBcvGlobal > 0) tasa.value = tasaBcvGlobal.toFixed(4);
    }
    calcularEquivalente();
}

function calcularEquivalente() {
    const monto = parseFloat(document.getElementById('movMonto').value) || 0; const tasa = parseFloat(document.getElementById('movTasa').value) || 1;
    const moneda = document.getElementById('movMoneda').value; let usd = moneda === 'USD' ? monto : (monto / tasa);
    document.getElementById('lblEquivalente').innerText = usd.toLocaleString('en-US', {minimumFractionDigits:2}) + ' USD';
}

function abrirModalMovimiento(tipo) {
    if(!modalMovimientoObj) return;
    document.getElementById('formMovimiento').reset(); 
    document.getElementById('movTipo').value = tipo; 
    document.getElementById('movFecha').value = fechaActual.toISOString().split('T')[0]; 
    validarMoneda();
    
    const t = document.getElementById('movTitle'); const h = document.getElementById('movHeader'); const b = document.getElementById('btnGuardarMov');
    if (tipo === 'INGRESO') { t.innerHTML = '<i class="bi bi-plus-circle"></i> Nuevo Ingreso Extra'; h.className = 'modal-header bg-success text-white'; b.className = 'btn btn-success px-4 fw-bold'; } 
    else { t.innerHTML = '<i class="bi bi-dash-circle"></i> Nuevo Gasto Extra'; h.className = 'modal-header bg-danger text-white'; b.className = 'btn btn-danger px-4 fw-bold'; }

    const fd = new URLSearchParams(); fd.append('accion', 'get_configuracion');
    fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
        let htmlCta = []; htmlCta.push('<option value="">Seleccione Cuenta...</option>'); res.cuentas.forEach(c => htmlCta.push(`<option value="${c.id}">${c.nombre} (${c.moneda})</option>`)); document.getElementById('movCuenta').innerHTML = htmlCta.join('');
        let htmlCat = []; htmlCat.push('<option value="">Seleccione Categoria...</option>'); res.categorias.filter(c => c.tipo === tipo).forEach(c => htmlCat.push(`<option value="${c.id}">${c.nombre}</option>`)); document.getElementById('movCategoria').innerHTML = htmlCat.join('');
        modalMovimientoObj.show();
    });
}

// ARRANQUE PRINCIPAL
document.addEventListener("DOMContentLoaded", async function() {
    
    await obtenerTasaBCV();

    if(document.getElementById('modalMovimiento')) modalMovimientoObj = new bootstrap.Modal(document.getElementById('modalMovimiento'));
    if(document.getElementById('modalConfig')) modalConfigObj = new bootstrap.Modal(document.getElementById('modalConfig'));
    if(document.getElementById('modalEditarCuenta')) modalEditarCuentaObj = new bootstrap.Modal(document.getElementById('modalEditarCuenta'));
    if(document.getElementById('modalEditarCategoria')) modalEditarCategoriaObj = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));
    if(document.getElementById('modalHistorialProveedor')) modalHistorialProveedorObj = new bootstrap.Modal(document.getElementById('modalHistorialProveedor'));
    
    if(document.getElementById('modalPagarDeuda')) {
        modalPagarDeudaObj = new bootstrap.Modal(document.getElementById('modalPagarDeuda'));
        
        const inputMontoDeuda = document.getElementById('pd_monto');
        if(inputMontoDeuda) {
            inputMontoDeuda.addEventListener('input', () => { evaluarTipoPagoDeuda(); window.calcularEquivalentePagarDeuda(); });
            inputMontoDeuda.addEventListener('keyup', () => { evaluarTipoPagoDeuda(); window.calcularEquivalentePagarDeuda(); });
            inputMontoDeuda.addEventListener('change', () => { evaluarTipoPagoDeuda(); window.calcularEquivalentePagarDeuda(); });
        }
        
        const inputTasaDeuda = document.getElementById('pd_tasa');
        if(inputTasaDeuda) {
            inputTasaDeuda.addEventListener('input', window.calcularEquivalentePagarDeuda);
            inputTasaDeuda.addEventListener('keyup', window.calcularEquivalentePagarDeuda);
            inputTasaDeuda.addEventListener('change', window.calcularEquivalentePagarDeuda);
        }
        
        document.getElementById('formPagarDeuda').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarPagoDeuda'); btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
            const formData = new FormData(this);
            fetchSeguro('api/api_dashboard.php', formData, (res) => {
                btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pago';
                modalPagarDeudaObj.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Abono registrado',
                    text: 'El pago se agregó al historial. El recibo mostrará este abono y el acumulado pagado.',
                    showCancelButton: Boolean(res.recibo_url),
                    showConfirmButton: Boolean(res.recibo_url),
                    confirmButtonText: 'Ver recibo de este abono',
                    cancelButtonText: 'Cerrar',
                    timer: res.recibo_url ? undefined : 1500,
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed && res.recibo_url) {
                        window.open(new URL(res.recibo_url, window.location.href).href, '_blank', 'noopener');
                    }
                });
                recargarTodo();
            }, () => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pago'; });
        });
    }
    
    if(document.getElementById('modalNuevaCuentaPagar')) {
        modalNuevaCuentaPagarObj = new bootstrap.Modal(document.getElementById('modalNuevaCuentaPagar'));
        document.getElementById('formNuevaCuentaPagar').addEventListener('submit', function(e) {
            e.preventDefault();
            if(arrayItemsGasto.length === 0) { Swal.fire('Faltan datos', 'Debes añadir al menos un servicio a la tabla', 'warning'); return; }
            
            const btn = document.getElementById('btnGuardarNCP'); 
            let isEdit = document.querySelector('#formNuevaCuentaPagar input[name="accion"]').value === 'editar_cuenta_pagar';
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
            
            const fd = new URLSearchParams(new FormData(this));
            fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => {
                btn.disabled = false; btn.innerHTML = isEdit ? '<i class="bi bi-save"></i> Guardar Cambios' : '<i class="bi bi-save"></i> Registrar Deuda';
                modalNuevaCuentaPagarObj.hide();
                Swal.fire({icon: 'success', title: isEdit ? 'Gasto Actualizado' : 'Deuda Registrada', timer: 1200, showConfirmButton: false});
                recargarTodo();
            }, () => { 
                btn.disabled = false; btn.innerHTML = isEdit ? '<i class="bi bi-save"></i> Guardar Cambios' : '<i class="bi bi-save"></i> Registrar Deuda'; 
            });
        });
    }

    if(document.getElementById('formEditarCuenta')) {
        document.getElementById('modalEditarCuenta').addEventListener('hidden.bs.modal', function () { if(modalConfigObj) modalConfigObj.show(); });
        document.getElementById('formEditarCuenta').addEventListener('submit', function(e) { e.preventDefault(); const fd = new URLSearchParams(new FormData(this)); fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => { modalEditarCuentaObj.hide(); cargarDatosConfiguracion(); cargarDashboard(); Swal.fire({icon: 'success', title: 'Actualizada', timer: 1000, showConfirmButton: false}); }); });
    }

    if(document.getElementById('formEditarCategoria')) {
        document.getElementById('modalEditarCategoria').addEventListener('hidden.bs.modal', function () { if(modalConfigObj) modalConfigObj.show(); });
        document.getElementById('formEditarCategoria').addEventListener('submit', function(e) { e.preventDefault(); const fd = new URLSearchParams(new FormData(this)); fetchSeguro('api/api_dashboard.php', fd.toString(), (res) => { modalEditarCategoriaObj.hide(); cargarDatosConfiguracion(); cargarHistorial(); Swal.fire({icon: 'success', title: 'Actualizada', timer: 1000, showConfirmButton: false}); }); });
    }

    const btnHam = document.getElementById('btnHamburguesa');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if(btnHam && sidebar && overlay) {
        btnHam.addEventListener('click', (e) => {
            e.stopPropagation();
            const isActive = sidebar.classList.contains('active');
            if (isActive) {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            } else {
                sidebar.classList.add('active');
                overlay.style.display = 'block';
            }
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.style.display = 'none';
        });
    }
    
    if(document.getElementById('btnMesAnt')) {
        document.getElementById('btnMesAnt').addEventListener('click', () => { if(!modoHistorico) { mesSeleccionado--; if(mesSeleccionado < 1) { mesSeleccionado = 12; anioSeleccionado--; } actualizarEtiquetaMes(); recargarTodo(); } });
        document.getElementById('btnMesSig').addEventListener('click', () => { if(!modoHistorico) { mesSeleccionado++; if(mesSeleccionado > 12) { mesSeleccionado = 1; anioSeleccionado++; } actualizarEtiquetaMes(); recargarTodo(); } });
    }

    if(document.getElementById('formMovimiento')) document.getElementById('formMovimiento').addEventListener('submit', function(e) { e.preventDefault(); const btn = document.getElementById('btnGuardarMov'); btn.disabled = true; const fd = new URLSearchParams(new FormData(this)); fetchSeguro('api/api_dashboard.php', fd.toString(), () => { btn.disabled = false; modalMovimientoObj.hide(); Swal.fire('Registrado!', 'Saldos actualizados.', 'success'); recargarTodo(); }); });
    if(document.getElementById('formAddCuenta')) document.getElementById('formAddCuenta').addEventListener('submit', function(e) { e.preventDefault(); const fd = new URLSearchParams(new FormData(this)); fetchSeguro('api/api_dashboard.php', fd.toString(), () => { this.reset(); cargarDatosConfiguracion(); cargarDashboard(); }); });
    if(document.getElementById('formAddCat')) document.getElementById('formAddCat').addEventListener('submit', function(e) { e.preventDefault(); const fd = new URLSearchParams(new FormData(this)); fetchSeguro('api/api_dashboard.php', fd.toString(), () => { this.reset(); cargarDatosConfiguracion(); }); });

    let tabHistorial = document.querySelector('button[data-bs-target="#tab-historial"]'); if(tabHistorial) tabHistorial.addEventListener('shown.bs.tab', cargarHistorial);
    let tabIngresos = document.querySelector('button[data-bs-target="#tab-ingresos"]'); if(tabIngresos) tabIngresos.addEventListener('shown.bs.tab', cargarDesgloseIngresos);
    let tabEgresos = document.querySelector('button[data-bs-target="#tab-egresos"]'); if(tabEgresos) tabEgresos.addEventListener('shown.bs.tab', cargarDesgloseEgresos);
    let tabCobrar = document.querySelector('button[data-bs-target="#tab-cobrar"]'); if(tabCobrar) tabCobrar.addEventListener('shown.bs.tab', abrirListaCobrar);
    let tabPagar = document.querySelector('button[data-bs-target="#tab-pagar"]'); if(tabPagar) tabPagar.addEventListener('shown.bs.tab', abrirListaPagar);

    actualizarEtiquetaMes(); 
    recargarTodo();
});