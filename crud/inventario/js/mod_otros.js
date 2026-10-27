window.cargarGastos = function() {
    $('#tbodyGastos').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_gastos'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(g => {
            let bgEstPago = g.estado_pago === 'Pagado' ? 'bg-success' : (g.estado_pago === 'Abonado' ? 'bg-info text-dark' : 'bg-danger');
            let btnPagos = `<button class="btn btn-sm btn-outline-success fw-bold ms-1" onclick="window.abrirModalPagosGasto(${g.id})" title="Cuentas por Pagar (Abonos)"><i class="bi bi-wallet2"></i></button>`;
            let mon = g.moneda === 'BS' ? 'BS' : '$';
            let gastoData = encodeURIComponent(JSON.stringify({
                id: g.id,
                id_proveedor: g.id_proveedor,
                fecha: g.fecha || '',
                factura: g.factura || '',
                descripcion: g.descripcion || '',
                monto_total: g.monto_total,
                moneda: g.moneda || 'USD',
                tasa_bcv: g.tasa_bcv || 0,
                estado_pago: g.estado_pago || 'Sin Pagar'
            }));

            html += `<tr>
                <td class="fw-bold text-muted">GASTO-${g.id}</td>
                <td class="text-nowrap">${window.escapeHtmlInventario(g.fecha || '')}</td>
                <td class="fw-bold" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${window.escapeHtmlInventario(g.proveedor_nombre || '')}</td>
                <td>${window.escapeHtmlInventario(g.factura || '-')}</td>
                <td><small>${window.escapeHtmlInventario(g.descripcion || '')}</small></td>
                <td class="fw-bold text-end fs-6 text-nowrap">${window.formatMoney(g.monto_total)} ${mon}</td>
                <td><span class="badge ${bgEstPago}">${window.escapeHtmlInventario(g.estado_pago || 'Sin Pagar')}</span></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning fw-bold btn-editar-gasto" data-gasto="${gastoData}" title="Editar Gasto"><i class="bi bi-pencil"></i></button>
                    <a class="btn btn-sm btn-outline-primary fw-bold ms-1" href="imprimir_orden_compra_gasto.php?id=${Number(g.id)}" target="_blank" rel="noopener" title="Emitir orden de compra"><i class="bi bi-printer"></i></a>
                    ${btnPagos}
                    <button class="btn btn-sm btn-outline-danger fw-bold ms-1" onclick="window.eliminar('eliminar_gasto', ${g.id}, window.cargarGastos)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyGastos').html(html || '<tr><td colspan="8" class="text-center text-muted">No hay gastos registrados</td></tr>');
    }, 'json');
};

window.abrirModalGasto = function() {
    $('#formGastoServicio')[0].reset();
    $('#gasto_id_edit').val('0');
    document.getElementById('gasto_fecha').valueAsDate = new Date();
    $('#lbl_equivalente_gasto').text('Equivalente: 0.00');
    $('#gasto_es_credito').val('1').css('pointer-events', 'auto');
    
    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#gasto_proveedor').empty().append(opts).trigger('change');
        $('#modalGastoServicio').modal('show');
    }, 'json');
};

window.escapeHtmlInventario = function(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[char]);
};

$(document).on('click', '.btn-editar-gasto', function() {
    try {
        window.editarGasto(JSON.parse(decodeURIComponent(this.dataset.gasto)));
    } catch (error) {
        Swal.fire('Error', 'No se pudieron cargar los datos de este gasto.', 'error');
    }
});

window.editarGasto = function(gasto) {
    $('#formGastoServicio')[0].reset();
    $('#gasto_id_edit').val(gasto.id);
    $('#gasto_fecha').val(gasto.fecha || '');
    $('#gasto_factura').val(gasto.factura || '');
    $('#gasto_monto_total').val(gasto.monto_total ? String(gasto.monto_total).replace('.',',') : '');
    $('#gasto_desc').val(gasto.descripcion || '');
    $('#gasto_moneda').val(gasto.moneda || 'USD');
    $('#gasto_tasa_bcv').val(gasto.tasa_bcv ? String(gasto.tasa_bcv).replace('.',',') : '');
    $('#gasto_monto_total').trigger('change');

    if (gasto.estado_pago === 'Pagado') {
        $('#gasto_es_credito').val('0').css('pointer-events', 'none');
    } else if (gasto.estado_pago === 'Abonado') {
        $('#gasto_es_credito').val('1').css('pointer-events', 'none');
    } else {
        $('#gasto_es_credito').val('1').css('pointer-events', 'auto');
    }

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${window.escapeHtmlInventario(p.nombre)}</option>`; });
        $('#gasto_proveedor').empty().append(opts).val(gasto.id_proveedor).trigger('change');
        $('#modalGastoServicio').modal('show');
    }, 'json');
};

$('#gasto_monto_total, #gasto_tasa_bcv, #gasto_moneda').on('input change', function() {
    let total = window.parseNum($('#gasto_monto_total').val());
    let tasa = window.parseNum($('#gasto_tasa_bcv').val());
    let mon = $('#gasto_moneda').val();
    
    if (tasa > 0) {
        let equiv = (mon === 'BS') ? (total / tasa) : (total * tasa);
        let sim = (mon === 'BS') ? '$' : 'BS';$('#lbl_equivalente_gasto').text('Equivalente: ' + window.formatMoney(equiv) + ' ' + sim);
    } else {
        $('#lbl_equivalente_gasto').text('Equivalente: 0.00');
    }
});

$('#formGastoServicio').off('submit').on('submit', function(e) {
    e.preventDefault();

    let total = window.parseNum($('#gasto_monto_total').val());
    let tasa = window.parseNum($('#gasto_tasa_bcv').val());
    let mon = $('#gasto_moneda').val();
    let monto_bs = (mon === 'BS') ? total : (total * tasa);
    let monto_usd = (mon === 'USD') ? total : (tasa > 0 ? total / tasa : 0);
    if(tasa === 0) { monto_bs = 0; monto_usd = 0; }

    let formData = new FormData(this);
    formData.set('monto_total', total);
    formData.set('tasa_bcv', tasa);
    formData.append('monto_bs', monto_bs);
    formData.append('monto_usd', monto_usd);

    let btn = $('#btnGuardarGasto');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false});
                $('#modalGastoServicio').modal('hide');
                window.cargarGastos();
                if(typeof window.cargarProveedores === 'function') window.cargarProveedores();
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Gasto'); }
    });
});

window.abrirModalPagosGasto = function(id_gasto) {
    $('#formPagoGasto')[0].reset();
    $('#pago_gasto_id').val(id_gasto);
    document.getElementById('pago_gasto_fecha').valueAsDate = new Date();
    window.cargarListaPagosGasto(id_gasto);
    $('#modalPagosGasto').modal('show');
};

window.cargarListaPagosGasto = function(id_gasto) {
    $('#tbodyPagosGasto').html('<tr><td colspan="6" class="text-center"><span class="spinner-border text-success"></span></td></tr>');
    $.get('api/api_inventario.php', { accion: 'listar_pagos_gasto', id_gasto: id_gasto }, function(res) {
        if(res.success) {
            let html = '';
            let mon = res.resumen.moneda === 'BS' ? 'BS' : '$';
            let totalDeuda = parseFloat(res.resumen.monto_total) || 0;
            let totalPagado = parseFloat(res.resumen.monto_pagado) || 0;
            let resta = totalDeuda - totalPagado;
            if (resta < 0) resta = 0;

            $('#lbl_deuda_total_gasto').text(window.formatMoney(totalDeuda) + ' ' + mon);
            $('#lbl_deuda_restante_gasto').text(window.formatMoney(resta) + ' ' + mon);

            if (resta <= 0) {
                $('#lbl_deuda_restante_gasto').removeClass('text-danger').addClass('text-success');
                $('#btnGuardarAbonoGasto').prop('disabled', true).text('Deuda Saldada');
            } else {
                $('#lbl_deuda_restante_gasto').removeClass('text-success').addClass('text-danger');
                $('#btnGuardarAbonoGasto').prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Pago');
            }

            if(res.data && res.data.length > 0) {
                res.data.forEach(p => {
                    let btnSoporte = p.comprobante ? `<a href="uploads/gastos/${p.comprobante}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-image"></i> Ver</a>` : '-';
                    html += `<tr>
                        <td>${p.fecha_pago}</td>
                        <td>${p.metodo_pago}</td>
                        <td>${p.referencia || '-'}</td>
                        <td class="text-end fw-bold text-success">${window.formatMoney(p.monto_usd)}</td>
                        <td class="text-center">${btnSoporte}</td>
                        <td class="text-end"><button class="btn btn-sm text-danger" onclick="window.eliminarPagoGasto(${p.id}, ${id_gasto})"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
                });
            } else { html = '<tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>'; }
            $('#tbodyPagosGasto').html(html);
        }
    }, 'json');
};

$('#formPagoGasto').off('submit').on('submit', function(e) {
    e.preventDefault();
    let usd = window.parseNum($('#pago_gasto_usd').val());
    let formData = new FormData(this);
    formData.set('monto_usd', usd);
    let btn = $('#btnGuardarAbonoGasto');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({icon: 'success', title: 'Pago Registrado', timer: 1000, showConfirmButton: false});
                let idg = $('#pago_gasto_id').val();
                $('#formPagoGasto')[0].reset();
                $('#pago_gasto_id').val(idg);
                document.getElementById('pago_gasto_fecha').valueAsDate = new Date();
                window.cargarListaPagosGasto(idg);
                window.cargarGastos();
                if(typeof window.cargarProveedores === 'function') window.cargarProveedores();
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Pago'); }
    });
});

window.eliminarPagoGasto = function(id_pago, id_gasto) {
    Swal.fire({
        title: '¿Eliminar este pago?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_pago_gasto', id: id_pago}, function(res) {
                if(res.success) {
                    window.cargarListaPagosGasto(id_gasto);
                    window.cargarGastos();
                    if(typeof window.cargarProveedores === 'function') window.cargarProveedores();
                } else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
};

// ================= ÓRDENES DE SERVICIO (TIPO FACTURA) =================
var carritoOS = [];

window.agregarItemOS = function() {
    let desc = $('#os_item_desc').val().trim();
    let costo = window.parseNum($('#os_item_costo').val());

    if(!desc || costo <= 0) return Swal.fire('Error', 'Ingrese una descripción y un costo válido.', 'warning');

    carritoOS.push({ desc: desc, costo: costo });
    $('#os_item_desc').val('');
    $('#os_item_costo').val('');
    window.actualizarTablaOS();
};

window.quitarItemOS = function(idx) {
    carritoOS.splice(idx, 1);
    window.actualizarTablaOS();
};

window.editarFilaOS = function(input, idx, campo) {
    if(campo === 'costo') {
        let val = window.parseNum($(input).val());
        if(val < 0) val = 0; 
        carritoOS[idx][campo] = val;
    } else {
        carritoOS[idx][campo] = $(input).val();
    }
    window.calcularTotalesOS();
};

window.actualizarTablaOS = function() {
    let html = '';
    if(carritoOS.length === 0) {
        html = '<tr><td colspan="3" class="text-center text-muted">Añade servicios a la orden.</td></tr>';
    } else {
        carritoOS.forEach((item, idx) => {
            html += `<tr>
                <td><input type="text" class="form-control form-control-sm border-success shadow-sm" value="${item.desc}" oninput="window.editarFilaOS(this, ${idx}, 'desc')"></td>
                <td class="text-end" style="width: 120px;">
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-end fw-bold border-success shadow-sm" value="${item.costo.toString().replace('.',',')}" oninput="window.editarFilaOS(this, ${idx}, 'costo')">
                </td>
                <td class="text-center" style="width: 40px;">
                    <button type="button" class="btn btn-sm text-danger py-0" onclick="window.quitarItemOS(${idx})"><i class="bi bi-x-circle-fill fs-5"></i></button>
                </td>
            </tr>`;
        });
    }
    $('#tbodyListaOS').html(html);
    window.calcularTotalesOS();
};

window.calcularTotalesOS = function() {
    let total = carritoOS.reduce((sum, current) => sum + current.costo, 0);
    $('#lbl_total_os').text(window.formatMoney(total) + ' $');$('#orden_servicio_costo').val(total); 
    $('#orden_servicio_desc').val(JSON.stringify(carritoOS));
};

window.cargarOrdenesServicio = function() {
    $('#tbodyOrdenesServicio').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_ordenes_servicio'}, function(res) {
        let html = '';
        if(res.data) {
            res.data.forEach(o => {
                let bgEstPago = o.estado_pago === 'Pagado' ? 'bg-success' : 'bg-danger';

                let hoy = new Date(); hoy.setHours(0,0,0,0);
                let partes = o.fecha_pago_limite.split('-');
                let limite = new Date(partes[0], partes[1] - 1, partes[2]);
                let diffTime = limite.getTime() - hoy.getTime();
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                let badgeDias = '';
                if (o.estado_pago === 'Pagado') { badgeDias = `<span class="badge bg-secondary"><i class="bi bi-check-all"></i> Solvente</span>`; } 
                else {
                    if (diffDays > 0) { badgeDias = `<span class="badge bg-info text-dark fw-bold">${diffDays} días restantes</span><br><small class="text-muted" style="font-size: 0.7rem;">Vence: ${o.fecha_pago_limite}</small>`; } 
                    else if (diffDays === 0) { badgeDias = `<span class="badge bg-warning text-dark fw-bold">¡Vence Hoy!</span>`; } 
                    else { badgeDias = `<span class="badge bg-danger fw-bold">Vencida (hace ${Math.abs(diffDays)} días)</span><br><small class="text-muted" style="font-size: 0.7rem;">Venció: ${o.fecha_pago_limite}</small>`; }
                }

                let tlfLimpio = o.proveedor_tlf ? o.proveedor_tlf.replace(/\D/g,'') : '';
                let btnWp = '';
                if (tlfLimpio) {
                    if(tlfLimpio.startsWith('04')) { tlfLimpio = '58' + tlfLimpio.substring(1); }
                    let msj = encodeURIComponent(`Hola ${o.proveedor_nombre}, le escribo referente a la orden de servicio OS-${o.id}`);
                    btnWp = `<a href="https://wa.me/${tlfLimpio}?text=${msj}" target="_blank" class="btn btn-sm btn-success rounded-pill ms-1 shadow-sm" title="Contactar por WhatsApp"><i class="bi bi-whatsapp"></i></a>`;
                } else { btnWp = `<button class="btn btn-sm btn-secondary rounded-pill ms-1 shadow-sm" disabled title="Proveedor sin teléfono registrado"><i class="bi bi-whatsapp"></i></button>`; }

                let btnPagar = o.estado_pago === 'Pagado' ? '' : `<button class="btn btn-sm btn-outline-primary rounded-pill fw-bold ms-1" onclick="window.pagarOrdenServicio(${o.id})" title="Marcar como Pagado"><i class="bi bi-check2-circle"></i> Pagar</button>`;

                let servicioDisplay = '';
                try {
                    let parsed = JSON.parse(o.servicio);
                    if(Array.isArray(parsed)) {
                        parsed.forEach(s => { servicioDisplay += `<li class="text-truncate" style="max-width:200px;">${s.desc} <b class="text-success">${window.formatMoney(s.costo)}$</b></li>`; });
                        servicioDisplay = `<ul class="mb-0 ps-3 small">${servicioDisplay}</ul>`;
                    } else { servicioDisplay = `<span class="small">${o.servicio}</span>`; }
                } catch(e) { servicioDisplay = `<span class="small">${o.servicio}</span>`; }

                let dataStr = JSON.stringify(o).replace(/"/g, '&quot;').replace(/'/g, '&#39;');

                html += `<tr>
                    <td class="fw-bold text-muted">OS-${o.id}</td>
                    <td class="fw-bold" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${o.proveedor_nombre}</td>
                    <td>${servicioDisplay}</td>
                    <td class="fw-bold text-end fs-6 text-nowrap">${window.formatMoney(o.costo)} $</td>
                    <td class="text-center align-middle">${badgeDias}</td>
                    <td class="text-center align-middle"><span class="badge ${bgEstPago}">${o.estado_pago}</span></td>
                    <td class="text-center text-nowrap align-middle">
                        <button class="btn btn-sm btn-outline-warning rounded-pill shadow-sm" onclick="window.editarOrdenServicio(this)" data-orden="${dataStr}"><i class="bi bi-pencil"></i></button>
                        ${btnPagar} ${btnWp}
                        <button class="btn btn-sm btn-outline-danger rounded-pill ms-1 shadow-sm" onclick="window.eliminar('eliminar_orden_servicio', ${o.id}, window.cargarOrdenesServicio)"><i class="bi bi-trash"></i></button>
                    </td></tr>`;
            });
        }
        $('#tbodyOrdenesServicio').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay órdenes de servicio registradas</td></tr>');
    }, 'json');
};

window.abrirModalOrdenServicio = function() {
    $('#formOrdenServicio')[0].reset();
    $('#orden_servicio_id').val('0');
    document.getElementById('orden_servicio_fecha').valueAsDate = new Date();
    carritoOS = []; window.actualizarTablaOS();

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#orden_servicio_proveedor').empty().append(opts).trigger('change');
        $('#modalOrdenServicio').modal('show');
    }, 'json');
};

window.editarOrdenServicio = function(btn) {
    let obj = JSON.parse($(btn).attr('data-orden'));$('#formOrdenServicio')[0].reset();
    $('#orden_servicio_id').val(obj.id);
    $('#orden_servicio_fecha').val(obj.fecha_orden);
    $('#orden_servicio_limite').val(obj.fecha_pago_limite);
    
    carritoOS = [];
    try {
        let parsed = JSON.parse(obj.servicio);
        if(Array.isArray(parsed)) { carritoOS = parsed; } 
        else { carritoOS.push({desc: obj.servicio, costo: parseFloat(obj.costo)}); }
    } catch(e) { carritoOS.push({desc: obj.servicio, costo: parseFloat(obj.costo)}); }
    
    window.actualizarTablaOS();

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#orden_servicio_proveedor').empty().append(opts).val(obj.id_proveedor).trigger('change');
        $('#modalOrdenServicio').modal('show');
    }, 'json');
};

$('#formOrdenServicio').on('submit', function(e) {
    e.preventDefault();
    if(carritoOS.length === 0) return Swal.fire('Error', 'La orden debe tener al menos un servicio agregado en la lista.', 'warning');

    let btn = $('#btnGuardarOrdenServ');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    let formData = $(this).serialize() + '&accion=guardar_orden_servicio';$.post('api/api_inventario.php', formData, function(res) {
        if(res.success) {
            Swal.fire({icon:'success', title:'Orden Registrada', timer:1000, showConfirmButton:false});
            $('#modalOrdenServicio').modal('hide');
            window.cargarOrdenesServicio();
        } else { Swal.fire('Error', res.msg, 'error'); }
    }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-save"></i> Registrar Orden'));
});

window.pagarOrdenServicio = function(id) {
    Swal.fire({
        title: '¿Confirmar Pago?', text: 'La orden será marcada como Solvente.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Sí, confirmar pago'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'pagar_orden_servicio', id: id}, function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: '¡Pagado!', timer:1000, showConfirmButton:false});
                    window.cargarOrdenesServicio();
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
};

// ================= CONSUMO DE ÓRDENES (INVENTARIO) =================

window.cargarOrdenesConsumo = function() {
    $('#tbodyOrdenesConsumo').html('<tr><td colspan="5" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_ordenes_produccion'}, function(res) {
        let html = '';
        if(res.data && res.data.length > 0) {
            res.data.forEach(o => {
                let descontado = o.inventario_descontado == 1;
                let badgeEstado = descontado ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Descontado</span>' : '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Por Descontar</span>';
                let btnAccion = descontado ?
                    `<button class="btn btn-sm btn-secondary fw-bold" disabled><i class="bi bi-calculator"></i> Ver Consumo</button>` :
                    `<button class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="window.verConsumoOrden(${o.ID}, '${o.nro_orden_fabrica}')"><i class="bi bi-calculator"></i> Ver Consumo</button>`;

                html += `<tr>
                    <td class="fw-bold text-muted">${o.nro_orden_fabrica || 'N/A'}</td>
                    <td>${o.fecha_emision || '-'}</td>
                    <td class="fw-bold" style="color:#0B4C8C;">${o.cliente_nombre || 'Desconocido'}</td>
                    <td>${badgeEstado}</td>
                    <td class="text-center">${btnAccion}</td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="5" class="text-center text-muted">No hay órdenes de producción recientes.</td></tr>';
        }
        $('#tbodyOrdenesConsumo').html(html);
    }, 'json');
};

window.verConsumoOrden = function(id_orden, nro_orden) {
    window.ordenActivaParaConsumo = id_orden;
    $('#lbl_orden_consumo').text(nro_orden);
    $('#tbodyDetalleConsumo').html('<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span> Calculando...</td></tr>');
    $('#btnConfirmarDescuento').prop('disabled', true);
    $('#modalConsumoOrden').modal('show');

    $.post('api/api_inventario.php', {accion: 'ver_consumo_orden', id_orden: id_orden}, function(res) {
        let html = '';
        if(res.success) {
            if(res.data && res.data.length > 0) {
                let todoOk = true;
                res.data.forEach(item => {
                    let stock = parseFloat(item.stock_actual);
                    let requerido = parseFloat(item.consumo_total);
                    let falta = requerido > stock;
                    if(falta) todoOk = false;

                    let badgeStock = falta ? `<span class="badge bg-danger">${window.formatMoney(stock)}</span>` : `<span class="badge bg-success">${window.formatMoney(stock)}</span>`;
                    let iconFalta = falta ? `<i class="bi bi-x-circle text-danger" title="Stock Insuficiente"></i>` : `<i class="bi bi-check-circle text-success" title="Stock Suficiente"></i>`;

                    html += `<tr>
                        <td class="fw-bold text-dark">${item.mp_nombre}</td>
                        <td class="text-center">${badgeStock} ${item.unidad_medida}</td>
                        <td class="text-end fw-bold text-danger">-${window.formatMoney(requerido)} ${item.unidad_medida}</td>
                        <td class="text-center">${iconFalta}</td>
                    </tr>`;
                });

                if(todoOk) {
                    $('#btnConfirmarDescuento').prop('disabled', false);
                } else {
                    html += `<tr><td colspan="4" class="text-center text-danger fw-bold py-2"><i class="bi bi-exclamation-triangle"></i> No hay suficiente stock para cubrir todo el consumo. Ajuste el inventario primero.</td></tr>`;
                }
            } else {
                html = '<tr><td colspan="4" class="text-center text-warning fw-bold py-3"><i class="bi bi-info-circle"></i> No se encontraron recetas asociadas a las prendas de esta orden.</td></tr>';
                $('#btnConfirmarDescuento').prop('disabled', false); // Permite marcar como descontado aunque no haya nada que descontar para quitarlo de la lista
            }
        } else {
            html = `<tr><td colspan="4" class="text-center text-danger">Error: ${res.msg}</td></tr>`;
        }
        $('#tbodyDetalleConsumo').html(html);
    }, 'json');
};

window.confirmarDescuentoInventario = function() {
    if(!window.ordenActivaParaConsumo) return;

    Swal.fire({
        title: '¿Confirmar Descuento?',
        text: 'Esto restará los materiales del inventario de forma irreversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, descontar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            $.post('api/api_inventario.php', {accion: 'aplicar_descuento_orden', id_orden: window.ordenActivaParaConsumo}, function(res) {
                if(res.success) {
                    Swal.fire('¡Éxito!', 'El inventario ha sido actualizado.', 'success');
                    $('#modalConsumoOrden').modal('hide');
                    window.cargarOrdenesConsumo();
                    if(typeof window.cargarMp === 'function') window.cargarMp();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
};