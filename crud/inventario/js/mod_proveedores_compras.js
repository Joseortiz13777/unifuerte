$(document).ready(function() {
    cargarFiltrosProv();

    if($('#compra_mp').length > 0) $('#compra_mp').select2({ dropdownParent: $('#modalCompra'), width: '100%', placeholder: 'Buscar Insumo...' });
    if($('#compra_proveedor').length > 0) $('#compra_proveedor').select2({ dropdownParent: $('#modalCompra'), width: '100%', placeholder: 'Seleccione Proveedor...' });
    if($('#orden_servicio_proveedor').length > 0) $('#orden_servicio_proveedor').select2({ dropdownParent: $('#modalOrdenServicio'), width: '100%', placeholder: 'Seleccione Proveedor...' });

    $('#compra_moneda').on('change', function() { window.calcularTotalesCompra(); });
    $('#compra_tasa_bcv, #compra_iva, #compra_descuento').on('input', function() { window.calcularTotalesCompra(); });

    $('#compra_costo_unit, #compra_desc_item').on('input', function() {
        let costo = window.parseNum($('#compra_costo_unit').val());
        let desc = window.parseNum($('#compra_desc_item').val());
        let neto = costo - (costo * (desc / 100));
        // Se coloca en la casilla de Neto el número crudo para no romper parseNum
        $('#compra_costo_neto').val(neto > 0 ? neto.toFixed(2).replace('.', ',') : '');
    });

    $('#pago_compra_usd, #pago_compra_tasa').on('input', function() {
        let usd = window.parseNum($('#pago_compra_usd').val());
        let tasa = window.parseNum($('#pago_compra_tasa').val());
        if (tasa > 0) $('#pago_compra_bs').val((usd * tasa).toFixed(2));
        else $('#pago_compra_bs').val('');
    });
});

// ================= 4. PROVEEDORES Y DEUDAS =================
function initSelect2Dinamico(selector, accionListar, accionGuardar) {
    $(selector).select2({
        dropdownParent: $('#modalProveedor'),
        tags: true, width: '100%', placeholder: "Seleccione o escriba uno nuevo...",
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') { return null; }
            return { id: term, text: term, newTag: true };
        }
    }).on('select2:select', function (e) {
        let data = e.params.data;
        if (data.newTag) {
            let cat = accionListar === 'listar_tipos_prov' ? 'tipo_proveedor' : (accionListar === 'listar_servicios_prov' ? 'servicio_proveedor' : 'banco');
            $.post('api/api_catalogos.php', { accion: 'agregar', catalogo: cat, nombre: data.text }, function(res) {
                if(res.success) {
                    let newOption = new Option(res.text, res.text, false, false); 
                    $(selector).find(`option[value="${data.id}"]`).replaceWith(newOption);
                    $(selector).val(res.text).trigger('change');
                    cargarFiltrosProv(); 
                }
            }, 'json');
        }
    });

    let catListar = accionListar === 'listar_tipos_prov' ? 'tipo_proveedor' : (accionListar === 'listar_servicios_prov' ? 'servicio_proveedor' : 'banco');
    $.get('api/api_catalogos.php', { accion: 'listar', catalogo: catListar }, function(data) {
        $(selector).empty().append('<option value="">Seleccione o escriba...</option>');
        if(data && data.length > 0) {
            data.forEach(item => { $(selector).append(new Option(item.text, item.text, false, false)); });
        }
    }, 'json');
}

$('#modalProveedor').on('shown.bs.modal', function () {
    if(!$('#prov_tipo').hasClass('select2-hidden-accessible')) initSelect2Dinamico('#prov_tipo', 'listar_tipos_prov', 'guardar_tipo_prov');
    if(!$('#prov_servicio').hasClass('select2-hidden-accessible')) initSelect2Dinamico('#prov_servicio', 'listar_servicios_prov', 'guardar_servicio_prov');
    if(!$('#prov_pm_banco').hasClass('select2-hidden-accessible')) initSelect2Dinamico('#prov_pm_banco', 'listar_bancos', 'guardar_banco');
});

function cargarFiltrosProv() {
    $.get('api/api_inventario.php', { accion: 'listar_tipos_prov' }, function(res) {
        let html = '<option value="">Cualquier Tipo de Prov.</option>';
        if(res.success) res.data.forEach(i => html += `<option value="${i.nombre}">${i.nombre}</option>`);
        $('#filtroTipoProvBusqueda').html(html);
    }, 'json');

    $.get('api/api_inventario.php', { accion: 'listar_servicios_prov' }, function(res) {
        let html = '<option value="">Cualquier Servicio/Rubro</option>';
        if(res.success) res.data.forEach(i => html += `<option value="${i.nombre}">${i.nombre}</option>`);
        $('#filtroServicioProvBusqueda').html(html);
    }, 'json');
}

window.cargarProveedores = function() {
    $('#tbodyProveedores').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(p => {
            let deuda = parseFloat(p.deuda_total) || 0;
            let badgeDeuda = deuda > 0 ? `<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" title="Debe: ${window.formatMoney(deuda)}$"></span>` : '';
            let btnDeuda = `<button class="btn btn-sm btn-outline-success rounded-pill position-relative me-1 btn-ver-deuda" data-id="${p.ID}" data-nombre="${p.nombre.replace(/'/g, "\\'")}" title="Estado de Cuenta"><i class="bi bi-wallet2"></i>${badgeDeuda}</button>`;

            html += `<tr>
                <td class="text-muted small">${p.ID}</td>
                <td class="fw-bold" style="color:#0B4C8C;">${p.nombre}</td>
                <td><span class="badge bg-secondary">${p.tipo_proveedor || 'General'}</span></td>
                <td><span class="badge bg-info text-dark">${p.servicio_ofrecido || '-'}</span></td>
                <td class="text-nowrap"><i class="bi bi-person text-secondary"></i> ${p.contacto || '-'}</td>
                <td><small>${p.ubicacion || '-'}</small></td>
                <td class="text-center text-nowrap">
                    ${btnDeuda}
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick='window.editarProveedor(${JSON.stringify(p).replace(/'/g, "&#39;")})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="window.eliminar('eliminar_proveedor', ${p.ID}, window.cargarProveedores)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyProveedores').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay proveedores registrados</td></tr>');
        $('#buscadorProveedores').trigger('change');
    }, 'json');
};

window.abrirModalProveedor = function() {
    $('#formProveedor')[0].reset();
    $('#prov_id').val('0');
    if($('#prov_tipo').hasClass('select2-hidden-accessible')) $('#prov_tipo').val('').trigger('change');
    if($('#prov_servicio').hasClass('select2-hidden-accessible')) $('#prov_servicio').val('').trigger('change');
    $('#modalProveedor').modal('show');
};

window.editarProveedor = function(obj) {
    $('#prov_id').val(obj.ID);
    $('#prov_nombre').val(obj.nombre);
    $('#prov_rif').val(obj.RIF_cedula);
    $('#prov_contacto').val(obj.contacto);
    $('#prov_tlf').val(obj.tlf);
    $('#prov_flete').val(obj.flete_internacional);
    $('#prov_ubicacion').val(obj.ubicacion);
    $('#prov_desc').val(obj.descripcion);
    $('#prov_cuenta').val(obj.nro_cuenta || '');
    $('#prov_tipo_cuenta').val(obj.tipo_cuenta || '');
    $('#prov_pm_cedula').val(obj.pm_cedula || '');
    $('#prov_pm_telefono').val(obj.pm_telefono || '');

    let tipo = obj.tipo_proveedor || '';
    if (tipo !== '') {
        if ($('#prov_tipo').find("option[value='" + tipo + "']").length) $('#prov_tipo').val(tipo).trigger('change');
        else { let newOption = new Option(tipo, tipo, true, true); $('#prov_tipo').append(newOption).trigger('change'); }
    } else { $('#prov_tipo').val('').trigger('change'); }

    let serv = obj.servicio_ofrecido || '';
    if (serv !== '') {
        if ($('#prov_servicio').find("option[value='" + serv + "']").length) $('#prov_servicio').val(serv).trigger('change');
        else { let newOption = new Option(serv, serv, true, true); $('#prov_servicio').append(newOption).trigger('change'); }
    } else { $('#prov_servicio').val('').trigger('change'); }

    $('#modalProveedor').modal('show');
};

$(document).on('input change', '#buscadorProveedores, #filtroDeudaProveedores, #filtroTipoProvBusqueda, #filtroServicioProvBusqueda', function() {
    let texto = $('#buscadorProveedores').val().toLowerCase();
    let deudaFilter = $('#filtroDeudaProveedores').val();
    let tipoFilter = $('#filtroTipoProvBusqueda').val();
    let servFilter = $('#filtroServicioProvBusqueda').val();

    $('#tbodyProveedores tr').each(function() {
        if ($(this).find('td').length === 1) return; 

        let filaTexto = $(this).text().toLowerCase();
        let filaTipo = $(this).find('td:eq(2)').text();
        let filaServ = $(this).find('td:eq(3)').text();
        let tieneDeuda = $(this).find('.btn-ver-deuda span.bg-danger').length > 0;

        let matchTexto = filaTexto.includes(texto);
        let matchDeuda = true;
        if (deudaFilter === 'Con Deuda') matchDeuda = tieneDeuda;
        if (deudaFilter === 'Sin Deuda') matchDeuda = !tieneDeuda;

        let matchTipo = (tipoFilter === '' || filaTipo === tipoFilter);
        let matchServ = (servFilter === '' || filaServ === servFilter);

        if (matchTexto && matchDeuda && matchTipo && matchServ) $(this).show(); else $(this).hide();
    });
});

$('#formProveedor').off('submit').on('submit', function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.post('api/api_inventario.php', $(this).serialize(), function(res) {
        if(res.success) {
            Swal.fire({icon:'success', title:'Guardado exitosamente', timer:1200, showConfirmButton:false});
            $('#modalProveedor').modal('hide'); 
            window.cargarProveedores(); 
            if(window.vieneDeCompra) {
                window.vieneDeCompra = false;
                setTimeout(() => $('#modalCompra').modal('show'), 400);
            }
        } else { Swal.fire('Error', res.msg || 'No se pudo guardar', 'error'); }
    }, 'json').always(()=> btn.prop('disabled', false).text('Guardar Proveedor'));
});

// Modal de Estado de Cuenta (Deudas)
$(document).on('click', '.btn-ver-deuda', function() {
    let idProveedor = $(this).data('id');
    let nombreProveedor = $(this).data('nombre');

    $('#lbl_nombre_proveedor_deuda').text(nombreProveedor);
    $('#tabla_deudas_body').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-danger"></span> Cargando deudas...</td></tr>');
    $('#modalDeudaProveedor').modal('show');

    $.ajax({
        url: 'api/api_deudas.php', type: 'GET', data: { id_proveedor: idProveedor }, dataType: 'json',
        success: function(res) {
            if(res.success) {
                $('#lbl_deuda_total_modal').text(window.formatMoney(res.total_deuda) + ' $');
                let filas = '';
                if(res.compras.length === 0) {
                    filas = '<tr><td colspan="8" class="text-center text-success fw-bold py-4"><i class="bi bi-check-circle-fill"></i> No hay deudas pendientes con este proveedor.</td></tr>';
                } else {
                    res.compras.forEach(compra => {
                        let idC = compra.id_compra;
                        let tipo = compra.tipo_deuda;
                        let ref = compra.nro_factura || compra.referencia || '-';
                        let total = parseFloat(compra.costo);
                        let pagado = parseFloat(compra.monto_pagado);
                        let pend = parseFloat(compra.monto_pendiente);
                        let mon = compra.moneda === 'BS' ? 'BS' : '$';

                        let badgeTipo = tipo === 'Insumo' ? '<span class="badge bg-success">Insumo/Tela</span>' : '<span class="badge bg-primary">Servicio/Gasto</span>';
                        let idDisplay = tipo === 'Insumo' ? `COMP-${idC}` : `SERV-${idC}`;

                        let btnAccion = '';
                        if(tipo === 'Insumo') {
                            btnAccion = `<button class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm" onclick="window.abrirModalPagosCompraDesdeDeuda(${idC})" title="Pagar Factura Insumos"><i class="bi bi-currency-dollar"></i> Pagar</button>`;
                        } else {
                            btnAccion = `<button class="btn btn-sm btn-primary fw-bold rounded-pill shadow-sm text-white" onclick="window.abrirModalPagosGastoDesdeDeuda(${idC})" title="Pagar Servicio"><i class="bi bi-currency-dollar"></i> Pagar</button>`;
                        }

                        filas += `
                            <tr>
                                <td class="fw-bold text-muted">${idDisplay}</td>
                                <td>${badgeTipo}</td>
                                <td>${compra.fecha_compra}</td>
                                <td>${ref}</td>
                                <td class="text-end fw-bold">${window.formatMoney(total)} ${mon}</td>
                                <td class="text-end text-success">${window.formatMoney(pagado)} ${mon}</td>
                                <td class="text-end text-danger fw-bold">${window.formatMoney(pend)} ${mon}</td>
                                <td class="text-center">${btnAccion}</td>
                            </tr>
                        `;
                    });
                }
                $('#tabla_deudas_body').html(filas);
            } else {
                $('#tabla_deudas_body').html('<tr><td colspan="8" class="text-center text-danger">Error al cargar deudas</td></tr>');
            }
        },
        error: function() { $('#tabla_deudas_body').html('<tr><td colspan="8" class="text-center text-danger">Fallo de red al buscar deudas</td></tr>'); }
    });
});

window.abrirModalPagosCompraDesdeDeuda = function(id_compra) {
    $('#modalDeudaProveedor').modal('hide');
    setTimeout(() => { window.abrirModalPagosCompra(id_compra); }, 400);
};

window.abrirModalPagosGastoDesdeDeuda = function(id_gasto) {
    $('#modalDeudaProveedor').modal('hide');
    setTimeout(() => { window.abrirModalPagosGasto(id_gasto); }, 400);
};


// ================= 5. COMPRAS (INVENTARIO) =================
window.cargarCompras = function() {
    $('#tbodyCompras').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_compras'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(c => {
            let bgEstLog = c.estado === 'Recibido' ? 'bg-success' : 'bg-warning text-dark';
            let bgEstPago = c.estado_pago === 'Pagado' ? 'bg-success' : (c.estado_pago === 'Abonado' ? 'bg-info text-dark' : 'bg-danger');
            let mon = c.moneda === 'BS' ? 'BS' : '$';

            let btnRecibir = c.estado === 'Pendiente de Recepción' ? `<button class="btn btn-sm btn-success fw-bold ms-1" onclick="window.marcarCompraRecibida(${c.ID})" title="Marcar como Recibido"><i class="bi bi-check-circle"></i></button>` : '';

            html += `<tr>
                <td class="fw-bold text-muted">COMP-${c.ID}</td>
                <td class="text-nowrap">${c.fecha_compra}</td>
                <td class="fw-bold" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${c.proveedor_nombre}</td>
                <td><span class="badge ${bgEstLog}">${c.estado}</span></td>
                <td><span class="badge ${bgEstPago}">${c.estado_pago || 'Sin Pagar'}</span></td>
                <td class="fw-bold text-end fs-6 text-nowrap">${window.formatMoney(c.costo)} ${mon}</td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning fw-bold" onclick="window.editarCompra(${c.ID})" title="Editar Compra"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-primary fw-bold ms-1" onclick="window.verDetalleCompra(${c.ID}, '${c.proveedor_nombre.replace(/'/g, "\\'")}')" title="Ver Detalle"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-success fw-bold ms-1" onclick="window.abrirModalPagosCompra(${c.ID})" title="Cuentas por Pagar"><i class="bi bi-wallet2"></i></button>
                    ${btnRecibir}
                    <button class="btn btn-sm btn-outline-danger fw-bold ms-1" onclick="window.eliminarCompra(${c.ID})" title="Eliminar Compra"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyCompras').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay compras registradas</td></tr>');
    }, 'json');
};

window.abrirModalCompra = function() {
    $('#formCompra')[0].reset();
    carritoCompra = [];
    document.getElementById('compra_fecha').valueAsDate = new Date();
    $('#compra_estado').val('Pendiente de Recepción');
    $('#compra_es_credito').val('1').css('pointer-events', 'auto');
    $('#compra_moneda').val('BS');
    $('#compra_tasa_bcv').val('');
    $('#compra_id_edit').val('0');
    $('#compra_archivo').val('');
    $('#compra_descuento').val('');
    $('#compra_iva').val('');
    $('#compra_mp').val('').trigger('change');
    window.actualizarTablaCompra();

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#compra_proveedor').empty().append(opts).trigger('change');
        $('#modalCompra').modal('show');
    }, 'json');
};

window.editarCompra = function(id_compra) {
    $.post('api/api_inventario.php', {accion: 'ver_detalle_compra', id: id_compra}, function(res) {
        if(res.success && res.compra) {
            $('#formCompra')[0].reset();
            $('#compra_archivo').val('');

            $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(p_res) {
                let opts = '<option value="">Seleccione Proveedor...</option>';
                if(p_res.data) p_res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
                $('#compra_proveedor').empty().append(opts).val(res.compra.id_provedor).trigger('change');
            }, 'json');

            $('#compra_id_edit').val(res.compra.ID);
            $('#compra_fecha').val(res.compra.fecha_compra);
            $('#compra_estado').val(res.compra.estado);
            $('#compra_factura').val(res.compra.nro_factura);
            $('#compra_control').val(res.compra.nro_control);
            $('#compra_banco').val(res.compra.banco);
            $('#compra_referencia').val(res.compra.referencia);
            $('#compra_moneda').val(res.compra.moneda || 'BS');
            $('#compra_tasa_bcv').val(res.compra.tasa_bcv ? res.compra.tasa_bcv.toString().replace('.',',') : '');
            $('#compra_iva').val(res.compra.iva ? res.compra.iva.toString().replace('.',',') : '');
            $('#compra_descuento').val(res.compra.descuento ? res.compra.descuento.toString().replace('.',',') : '');

            let pagado = parseFloat(res.compra.monto_pagado);
            let costo_total = parseFloat(res.compra.costo);

            if (pagado >= costo_total) $('#compra_es_credito').val('0').css('pointer-events', 'none'); 
            else if (pagado > 0) $('#compra_es_credito').val('1').css('pointer-events', 'none'); 
            else $('#compra_es_credito').val('1').css('pointer-events', 'auto'); 

            carritoCompra = [];
            if(res.data) {
                res.data.forEach(d => {
                    let cost = parseFloat(d.precio_unitario);
                    let dp = parseFloat(d.descuento_porc || 0);
                    let cn = parseFloat(d.costo_neto || cost);
                    carritoCompra.push({
                        id_mp: d.id_materia,
                        nombre: d.mp_nombre,
                        cantidad: parseFloat(d.cantidad),
                        costo_base: cost,
                        desc_porc: dp,
                        costo_neto: cn,
                        subtotal: parseFloat(d.cantidad) * cn
                    });
                });
            }

            window.actualizarTablaCompra();
            $('#modalCompra').modal('show');
        } else Swal.fire('Error', 'No se pudieron cargar los datos de la compra.', 'error');
    }, 'json');
};

window.crearProveedorDesdeCompra = function() {
    window.vieneDeCompra = true;
    $('#modalCompra').modal('hide');
    window.abrirModalProveedor();
};

window.crearMpDesdeCompra = function() {
    window.vieneDeCompra = true;
    $('#modalCompra').modal('hide');
    if(typeof window.abrirModalMp === 'function') window.abrirModalMp();
};

window.agregarItemCompra = function() {
    let idMp = $('#compra_mp').val();
    let cant = window.parseNum($('#compra_cant').val());
    let costoBase = window.parseNum($('#compra_costo_unit').val());
    let descPorc = window.parseNum($('#compra_desc_item').val());
    let costoNeto = window.parseNum($('#compra_costo_neto').val()) || costoBase;

    if(!idMp || cant <= 0 || costoBase <= 0) return Swal.fire('Error', 'Seleccione un insumo y coloque cantidad y costo válido.', 'warning');

    let nombreMp = $('#compra_mp option:selected').text();
    costoNeto = costoBase - (costoBase * (descPorc / 100)); // Calcular real aquí por si falla el front
    let subtotal = cant * costoNeto;

    let idx = carritoCompra.findIndex(x => x.id_mp == idMp && x.costo_base == costoBase && x.desc_porc == descPorc);
    if(idx > -1) {
        carritoCompra[idx].cantidad += cant;
        carritoCompra[idx].subtotal = carritoCompra[idx].cantidad * carritoCompra[idx].costo_neto;
    } else {
        carritoCompra.push({ id_mp: idMp, nombre: nombreMp, cantidad: cant, costo_base: costoBase, desc_porc: descPorc, costo_neto: costoNeto, subtotal: subtotal });
    }

    $('#compra_cant').val('');
    $('#compra_costo_unit').val('');
    $('#compra_desc_item').val('0');
    $('#compra_costo_neto').val('');
    $('#compra_mp').val('').trigger('change');
    window.actualizarTablaCompra();
};

window.quitarItemCompra = function(idx) {
    carritoCompra.splice(idx, 1);
    window.actualizarTablaCompra();
};

window.calcularIvaAutomatico = function() {
    let subtotalInsumos = carritoCompra.reduce((sum, current) => sum + current.subtotal, 0);
    let descuento = window.parseNum($('#compra_descuento').val());
    
    let baseImponible = subtotalInsumos - descuento;
    if(baseImponible < 0) baseImponible = 0;

    let ivaAuto = baseImponible * 0.16;
    $('#compra_iva').val(ivaAuto.toFixed(2).replace('.', ','));
    window.actualizarTablaCompra();
};

// ESTA FUNCION FUE AISLADA DEL ONINPUT DIRECTO PARA NO CORTAR EL FOCO,
// AHORA USA UN MÉTODO DE RE-ASIGNACIÓN MANUAL AL DOM EN VEZ DE ACTUALIZAR TODA LA TABLA
window.editarFilaCompra = function(input, idx, campo) {
    let val = window.parseNum($(input).val());
    if(val < 0) val = 0; 
    carritoCompra[idx][campo] = val;

    let costoBase = carritoCompra[idx].costo_base;
    let descPorc = carritoCompra[idx].desc_porc;
    let costoNeto = costoBase - (costoBase * (descPorc / 100));
    
    carritoCompra[idx].costo_neto = costoNeto;
    carritoCompra[idx].subtotal = carritoCompra[idx].cantidad * costoNeto;

    $(`#neto_${idx}`).text(window.formatMoney(costoNeto));
    $(`#subtotal_${idx}`).text(window.formatMoney(carritoCompra[idx].subtotal));
    
    window.calcularTotalesCompra();
};

window.actualizarTablaCompra = function() {
    let html = '';
    
    if(carritoCompra.length === 0) {
        html = '<tr><td colspan="7" class="text-center text-muted">Añade insumos a la compra.</td></tr>';
    } else {
        carritoCompra.forEach((item, idx) => {
            html += `<tr>
                <td class="fw-bold text-dark small align-middle">${item.nombre}</td>
                <td class="text-center align-middle" style="width: 80px;">
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-center fw-bold border-primary shadow-sm mx-auto" value="${item.cantidad.toString().replace('.',',')}" oninput="window.editarFilaCompra(this, ${idx}, 'cantidad')">
                </td>
                <td class="text-end align-middle" style="width: 100px;">
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-end fw-bold border-primary shadow-sm ms-auto" value="${item.costo_base.toString().replace('.',',')}" oninput="window.editarFilaCompra(this, ${idx}, 'costo_base')">
                </td>
                <td class="text-center align-middle" style="width: 70px;">
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-center text-danger shadow-sm mx-auto" value="${item.desc_porc.toString().replace('.',',')}" oninput="window.editarFilaCompra(this, ${idx}, 'desc_porc')">
                </td>
                <td class="text-end fw-bold text-dark align-middle" id="neto_${idx}">${window.formatMoney(item.costo_neto)}</td>
                <td class="text-end fw-bold text-primary align-middle" id="subtotal_${idx}">${window.formatMoney(item.subtotal)}</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm text-danger py-0" onclick="window.quitarItemCompra(${idx})"><i class="bi bi-x-circle-fill fs-5"></i></button>
                </td>
            </tr>`;
        });
    }

    $('#tbodyListaCompra').html(html);
    window.calcularTotalesCompra();
};

window.calcularTotalesCompra = function() {
    let subtotal = carritoCompra.reduce((sum, current) => sum + current.subtotal, 0);
    let descuento = window.parseNum($('#compra_descuento').val());
    let iva = window.parseNum($('#compra_iva').val());
    
    let baseImponible = subtotal - descuento;
    if(baseImponible < 0) baseImponible = 0;

    let totalGen = baseImponible + iva;
    let mon = $('#compra_moneda').val() === 'BS' ? 'BS' : '$';
    let tasa = window.parseNum($('#compra_tasa_bcv').val());
    
    $('#lbl_subtotal_insumos').text(window.formatMoney(subtotal) + ' ' + mon);
    $('#lbl_base_imponible').text(window.formatMoney(baseImponible) + ' ' + mon);
    $('#lbl_total_compra').text(window.formatMoney(totalGen) + ' ' + mon);

    if (tasa > 0) {
        let equiv = (mon === 'BS') ? (totalGen / tasa) : (totalGen * tasa);
        let sim = (mon === 'BS') ? '$' : 'BS';
        $('#lbl_equivalente_compra').text('Equivalente: ' + window.formatMoney(equiv) + ' ' + sim);
    } else {
        $('#lbl_equivalente_compra').text('Equivalente: 0.00');
    }
};

$('#formCompra').off('submit').on('submit', function(e) {
    e.preventDefault();
    let idProv = $('#compra_proveedor').val();
    let fecha = $('#compra_fecha').val();
    let estado = $('#compra_estado').val();

    if(!idProv || !fecha) return Swal.fire('Error', 'Debe seleccionar Proveedor y Fecha.', 'warning');
    if(carritoCompra.length === 0) return Swal.fire('Error', 'El carrito de compra está vacío.', 'warning');

    let subtotalInsumos = carritoCompra.reduce((sum, current) => sum + current.subtotal, 0);
    let descuento = window.parseNum($('#compra_descuento').val());
    let iva = window.parseNum($('#compra_iva').val());
    
    let baseImponible = subtotalInsumos - descuento;
    if(baseImponible < 0) baseImponible = 0;
    let totalGeneral = baseImponible + iva;

    let mon = $('#compra_moneda').val();
    let tasa = window.parseNum($('#compra_tasa_bcv').val());
    let monto_bs = (mon === 'BS') ? totalGeneral : (totalGeneral * tasa);
    let monto_usd = (mon === 'USD') ? totalGeneral : (tasa > 0 ? totalGeneral / tasa : 0);
    if(tasa === 0) { monto_bs = 0; monto_usd = 0; }

    let formData = new FormData(this);
    formData.set('descuento', descuento);
    formData.set('iva', iva);
    formData.set('tasa_bcv', tasa);
    formData.append('accion', 'guardar_compra');
    formData.append('total', totalGeneral);
    formData.append('monto_bs', monto_bs);
    formData.append('monto_usd', monto_usd);
    formData.append('insumos', JSON.stringify(carritoCompra));

    Swal.fire({
        title: '¿Confirmar Registro?', text: 'La orden será guardada en el sistema.', icon: 'question', showCancelButton: true, confirmButtonColor: '#0B4C8C', confirmButtonText: 'Sí, guardar'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({title: 'Guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            $.ajax({
                url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('¡Éxito!', 'Compra registrada correctamente.', 'success');
                        $('#modalCompra').modal('hide');
                        window.cargarCompras();
                        if(typeof window.cargarMp === 'function') window.cargarMp(); 
                        window.cargarProveedores(); 
                    } else { Swal.fire('Error', res.msg, 'error'); }
                }
            });
        }
    });
});

window.marcarCompraRecibida = function(id_compra) {
    Swal.fire({
        title: '¿Marcar como Recibido?', text: 'Esto sumará automáticamente todos los insumos al stock.', icon: 'info',
        showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Sí, ya llegaron', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            $.post('api/api_inventario.php', {accion: 'marcar_compra_recibida', id: id_compra}, function(res) {
                if(res.success) {
                    Swal.fire('¡Stock Actualizado!', 'Los insumos se sumaron a tu inventario correctamente.', 'success');
                    window.cargarCompras();
                    if(typeof window.cargarMp === 'function') window.cargarMp();
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
};

window.eliminarCompra = function(id_compra) {
    Swal.fire({
        title: '¿Eliminar esta compra?',
        text: 'Se borrarán los detalles, pagos asociados y se revertirá el stock si ya estaba recibido. ¡Esta acción no se puede deshacer!',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({title: 'Eliminando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            $.post('api/api_inventario.php', {accion: 'eliminar_compra', id: id_compra}, function(res) {
                if(res.success) {
                    Swal.fire('¡Eliminado!', 'La compra ha sido eliminada y los cambios revertidos correctamente.', 'success');
                    window.cargarCompras();
                    if(typeof window.cargarMp === 'function') window.cargarMp();
                    window.cargarProveedores();
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
};

window.verDetalleCompra = function(id_compra, proveedor) {
    $('#ver_compra_prov').html(`<i class="bi bi-building text-primary"></i> ${proveedor}`);
    $('#tbodyVerDetalleCompra').html('<tr><td colspan="6" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    $('#modalVerDetalleCompra').modal('show');

    $.post('api/api_inventario.php', { accion: 'ver_detalle_compra', id: id_compra }, function(res) {
        if(res.success && res.compra) {
            let c = res.compra;
            $('#ver_compra_fechas').text(c.fecha_compra + ' | ' + c.estado);
            $('#ver_factura').text(c.nro_factura || '-');
            $('#ver_control').text(c.nro_control || '-');
            $('#ver_banco').text(c.banco || '-');
            $('#ver_ref').text(c.referencia || '-');

            if(c.nro_factura || c.nro_control || c.banco || c.referencia) $('#ver_compra_datos_factura').show();
            else $('#ver_compra_datos_factura').hide();

            let moneda = c.moneda === 'BS' ? 'BS' : '$';
            $('#ver_compra_descuento').text(window.formatMoney(c.descuento) + ' ' + moneda);
            $('#ver_compra_iva').text(window.formatMoney(c.iva) + ' ' + moneda);
            $('#ver_compra_total').text(window.formatMoney(c.costo) + ' ' + moneda);

            if(c.archivo_factura) $('#ver_btn_descargar_factura').html(`<a href="uploads/compras/${c.archivo_factura}" target="_blank" class="btn btn-outline-danger fw-bold"><i class="bi bi-file-earmark-pdf"></i> Ver Soporte</a>`);
            else $('#ver_btn_descargar_factura').empty();

            let html = '';
            if(res.data) {
                res.data.forEach(d => {
                    let costBase = parseFloat(d.precio_unitario);
                    let dPorc = parseFloat(d.descuento_porc || 0);
                    let costNeto = parseFloat(d.costo_neto || costBase);
                    let sub = parseFloat(d.cantidad) * costNeto;

                    html += `<tr>
                        <td class="fw-bold text-dark">${d.mp_nombre}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${d.cantidad} ${d.unidad_medida}</span></td>
                        <td class="text-end text-muted">${window.formatMoney(costBase)}</td>
                        <td class="text-center text-danger">${dPorc}%</td>
                        <td class="text-end fw-bold text-dark">${window.formatMoney(costNeto)}</td>
                        <td class="text-end fw-bold text-primary">${window.formatMoney(sub)}</td>
                    </tr>`;
                });
            }
            $('#tbodyVerDetalleCompra').html(html || '<tr><td colspan="6" class="text-center">Sin detalles</td></tr>');
        } else {
            $('#tbodyVerDetalleCompra').html('<tr><td colspan="6" class="text-center text-danger">Error cargando detalles</td></tr>');
        }
    }, 'json');
};

window.abrirModalPagosCompra = function(id_compra) {
    $('#formPagoCompra')[0].reset();
    $('#pago_compra_id').val(id_compra);
    document.getElementById('pago_compra_fecha').valueAsDate = new Date();
    window.cargarListaPagosCompra(id_compra);
    $('#modalPagosCompra').modal('show');
};

window.cargarListaPagosCompra = function(id_compra) {
    $('#tbodyPagosCompra').html('<tr><td colspan="6" class="text-center"><span class="spinner-border text-success"></span></td></tr>');
    $.get('api/api_inventario.php', { accion: 'listar_pagos_compra', id_compra: id_compra }, function(res) {
        if(res.success) {
            let html = '';
            let moneda = res.resumen.moneda === 'BS' ? 'BS' : '$';
            let totalDeuda = parseFloat(res.resumen.costo) || 0;
            let totalPagado = parseFloat(res.resumen.monto_pagado) || 0;
            let resta = totalDeuda - totalPagado;
            if (resta < 0) resta = 0;

            $('#lbl_deuda_total').text(window.formatMoney(totalDeuda) + ' ' + moneda);
            $('#lbl_deuda_restante').text(window.formatMoney(resta) + ' ' + moneda);

            if (resta <= 0) {
                $('#lbl_deuda_restante').removeClass('text-danger').addClass('text-success');
                $('#btnGuardarAbono').prop('disabled', true).text('Deuda Saldada');
            } else {
                $('#lbl_deuda_restante').removeClass('text-success').addClass('text-danger');
                $('#btnGuardarAbono').prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Abono');
            }

            if(res.data && res.data.length > 0) {
                res.data.forEach(p => {
                    let btnSoporte = p.comprobante ? `<a href="uploads/compras/pagos/${p.comprobante}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-image"></i> Ver</a>` : '-';
                    html += `<tr>
                        <td>${p.fecha_pago}</td>
                        <td>${p.metodo_pago}</td>
                        <td>${p.referencia || '-'}</td>
                        <td class="text-end fw-bold text-success">${window.formatMoney(p.monto_usd)}</td>
                        <td class="text-center">${btnSoporte}</td>
                        <td class="text-end"><button class="btn btn-sm text-danger" onclick="window.eliminarPagoCompra(${p.id}, ${id_compra})"><i class="bi bi-trash"></i></button></td>
                    </tr>`;
                });
            } else { html = '<tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>'; }
            $('#tbodyPagosCompra').html(html);
        }
    }, 'json');
};

$('#formPagoCompra').off('submit').on('submit', function(e) {
    e.preventDefault();
    let usd = window.parseNum($('#pago_compra_usd').val());
    let bs = window.parseNum($('#pago_compra_bs').val());
    let tasa = window.parseNum($('#pago_compra_tasa').val());
    
    let formData = new FormData(this);
    formData.set('monto_usd', usd);
    formData.set('monto_bs', bs);
    formData.set('tasa_bcv', tasa);
    let btn = $('#btnGuardarAbono');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({icon: 'success', title: 'Abono Registrado', timer: 1000, showConfirmButton: false});
                let idc = $('#pago_compra_id').val();
                $('#formPagoCompra')[0].reset();
                $('#pago_compra_id').val(idc);
                document.getElementById('pago_compra_fecha').valueAsDate = new Date();
                window.cargarListaPagosCompra(idc);
                window.cargarCompras();
                window.cargarProveedores();
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Abono'); }
    });
});

window.eliminarPagoCompra = function(id_pago, id_compra) {
    Swal.fire({
        title: '¿Eliminar este abono?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_pago_compra', id: id_pago}, function(res) {
                if(res.success) {
                    window.cargarListaPagosCompra(id_compra);
                    window.cargarCompras();
                    window.cargarProveedores();
                } else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
};