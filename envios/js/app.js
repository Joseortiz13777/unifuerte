let current_tlf = '';
let current_cliente = '';
let current_nro_orden = '';
let fases_memoria = [];
let archivosEvidenciaMemoria = []; 
let vista_actual = 'proceso'; 

let lista_insumos_temporales = [];
let insumos_pedidos_actuales = []; 

function getIconoSeguro(nombreFase) {
    let n = nombreFase.toLowerCase();
    
    if (n.includes('garantia') || n.includes('garant�a')) return '<i class="bi bi-shield-fill-exclamation text-danger"></i>';
    if (n.includes('talla')) return '<i class="bi bi-rulers"></i>';
    if (n.includes('patron')) return '<i class="bi bi-bezier"></i>';
    if (n.includes('impres')) return '<i class="bi bi-printer-fill"></i>';
    if (n.includes('sublimaci')) return '<i class="bi bi-palette-fill"></i>';
    if (n.includes('corte')) return '<i class="bi bi-scissors"></i>';
    if (n.includes('confecci')) return '<i class="bi bi-intersect"></i>';
    if (n.includes('bordad')) return '<i class="bi bi-record-circle"></i>';
    
    if (n.includes('auditor') || n.includes('chequeo')) return '<i class="bi bi-search"></i>';
    if (n.includes('empacad') || n.includes('recepci�n de mercanc�a') || n.includes('recepcion de mercancia')) return '<i class="bi bi-box-seam-fill"></i>';
    if (n.includes('reempacado')) return '<i class="bi bi-tags-fill"></i>';
    if (n.includes('a�reo') || n.includes('aereo') || n.includes('mar�timo') || n.includes('maritimo') || n.includes('avion') || n.includes('barco')) return '<i class="bi bi-airplane-fill"></i>';
    if (n.includes('listo') || n.includes('entregar') || n.includes('tracking')) return '<i class="bi bi-truck"></i>';
    
    return '<i class="bi bi-check-circle-fill"></i>'; 
}

window.mostrarDetallePrendasMes = function(ordenes) {
    const lista = Array.isArray(ordenes) ? ordenes : [];
    const total = lista.reduce((sum, item) => sum + Number(item.cantidad || 0), 0);
    $('#resumenPrendasMes').text('Total del mes: ' + total + ' prendas');

    if (lista.length === 0) {
        $('#listaPrendasMes').html('<div class="text-center text-muted py-3">No hay prendas registradas en este mes.</div>');
        $('#modalDetallePrendasMes').modal('show');
        return;
    }

    let html = lista.map(item => {
        const orden = $('<div>').text(item.nro_orden_fabrica || 'Sin orden').html();
        const cantidad = Number(item.cantidad || 0);
        const cliente = $('<div>').text(item.cliente || 'Cliente no registrado').html();
        const fechaPartes = (item.fecha_terminado || '').slice(0, 10).split('-');
        const fecha = fechaPartes.length === 3 && fechaPartes[0].length === 4
            ? `${fechaPartes[2]}/${fechaPartes[1]}/${fechaPartes[0]}`
            : 'Sin fecha';

        return `
            <div class="bg-white border rounded p-3 shadow-sm">
                <div class="fw-bold text-dark">${cantidad} prendas — ${orden}</div>
                <div class="small text-muted mt-2"><i class="bi bi-person-fill me-1"></i> Cliente: ${cliente}</div>
                <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i> Finalizado: ${fecha}</div>
            </div>`;
    }).join('');

    $('#listaPrendasMes').html(html);
    $('#modalDetallePrendasMes').modal('show');
};

window.cargarPrendasMes = function() {
    $.post('api/api_envios.php', {accion: 'obtener_prendas_mes'}, function(res) {
        if(res.success) {
            const total = Number(res.total) || 0;
            const meta = 500;
            const faltantes = Math.max(0, meta - total);
            const superadas = Math.max(0, total - meta);
            $('#prendasMesCount').text(total.toLocaleString('es-VE'));
            $('#prendasMetaRestantes').text(
                superadas > 0
                    ? `Meta superada por ${superadas.toLocaleString('es-VE')} prendas`
                    : `Faltan ${faltantes.toLocaleString('es-VE')} para la meta de ${meta}`
            );
            const ordenes = Array.isArray(res.ordenes) ? res.ordenes : [];

            $('#metricPrendasMes').off('click').on('click', function() {
                if (ordenes.length === 0) {
                    Swal.fire({ icon: 'info', title: 'Sin registros', text: 'Todavía no hay prendas finalizadas para este mes.', confirmButtonColor: '#198754' });
                    return;
                }
                window.mostrarDetallePrendasMes(ordenes);
            });
            $('#metricPrendasMes').attr('title', ordenes.length ? 'Ver detalle por orden' : 'Sin registros este mes');
        }
    }, 'json');
};

window.cambiarVista = function(vista) {
    vista_actual = vista;
    $('.nav-link-custom').removeClass('active');
    
    $('#contenedor_ordenes').hide();
    $('#vistaNotasManuales').hide();
    $('#vistaDiseno').hide();
    $('#vistaGarantiasTextiles').hide();

    if (vista === 'diseno' || vista === 'diseno_archivador') {
        const esArchivadorDiseno = vista === 'diseno_archivador';
        $(esArchivadorDiseno ? '#btnNavArchivadorDiseno' : '#btnNavDiseno').addClass('active');
        $('#divBuscadorEnvios').hide();
        $('#headerPrincipal').show();
        $('#tituloPagina').html(esArchivadorDiseno
            ? '<i class="bi bi-archive-fill me-2 text-warning"></i> Archivador de Diseño'
            : '<i class="bi bi-palette-fill me-2 text-info"></i> Diseño y Acabado');
        $('#iframeDiseno').attr('src', esArchivadorDiseno
            ? 'diseno.php?embebido=1&vista=archivador'
            : 'diseno.php?embebido=1');
        $('#vistaDiseno').show();
    } else if (vista === 'proceso') {
        $('#btnNavEnProceso').addClass('active');
        $('#divBuscadorEnvios').show();
        $('#headerPrincipal').show();
        $('#tituloPagina').html('<i class="bi bi-truck me-2"></i> Control de Envios en Proceso');
        $('#contenedor_ordenes').show();
        cargarOrdenes();
    } else if (vista === 'archivador') {
        $('#btnNavArchivador').addClass('active');
        $('#divBuscadorEnvios').show();
        $('#headerPrincipal').show();
        $('#tituloPagina').html('<i class="bi bi-archive-fill me-2 text-warning"></i> Archivador de Pedidos Finalizados');
        $('#contenedor_ordenes').show();
        cargarOrdenes();
    } else if (vista === 'garantias') {
        $('#btnNavGarantiasTextiles').addClass('active');
        $('#divBuscadorEnvios').hide();
        $('#headerPrincipal').hide();
        $('#vistaGarantiasTextiles').fadeIn();
        window.cargarGarantiasTextiles();
    } else if (vista === 'notas_manuales') {
        $('#btnNavNotasManuales').addClass('active');
        $('#divBuscadorEnvios').hide();
        $('#headerPrincipal').hide();
        $('#vistaNotasManuales').fadeIn();
        if(typeof cargarNotasManuales === 'function') cargarNotasManuales();
    }
};

function escapeHtmlGarantia(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

window.cargarGarantiasTextiles = function() {
    const $lista = $('#listaGarantiasTextiles');
    $lista.html('<div class="col-12 text-center py-5"><span class="spinner-border text-primary"></span></div>');
    $.post('api/api_envios.php', { accion: 'listar_garantias_textiles' }, function(res) {
        if (!res.success) {
            $lista.html(`<div class="col-12"><div class="alert alert-danger">${escapeHtmlGarantia(res.msg || 'No se pudieron cargar las garantías.')}</div></div>`);
            return;
        }

        const estados = Array.isArray(res.estados) ? res.estados : [];
        const garantias = Array.isArray(res.data) ? res.data : [];
        const abiertas = garantias.filter((garantia) => garantia.estado !== 'Finalizada').length;
        $('#badge_garantias_textiles').text(abiertas).toggleClass('d-none', abiertas === 0);
        if (garantias.length === 0) {
            $lista.html('<div class="col-12"><div class="alert alert-info">No hay solicitudes de garantía registradas.</div></div>');
            return;
        }

        const html = garantias.map((garantia) => {
            const id = Number(garantia.id) || 0;
            const finalized = garantia.estado === 'Finalizada';
            const evidence = (garantia.evidencias || []).map((item) => {
                const nombre = String(item.nombre || '');
                if (!/^[a-f0-9]{40}\.(jpg|png|webp)$/i.test(nombre)) return '';
                const url = `https://cliente.soyunifuerte.com/index.php?img=${encodeURIComponent(nombre)}`;
                return `<a href="${escapeHtmlGarantia(url)}" target="_blank" rel="noopener"><img src="${escapeHtmlGarantia(url)}" alt="Evidencia de garantía" class="img-thumbnail m-1" style="width:90px;height:90px;object-fit:cover"></a>`;
            }).join('');
            const history = (garantia.historial || []).map((item) =>
                `<li><b>${escapeHtmlGarantia(item.estado || '')}</b> — ${escapeHtmlGarantia(item.fecha || '')} (${escapeHtmlGarantia(item.actor || '')})</li>`
            ).join('');
            const currentStageIndex = estados.indexOf(garantia.estado);
            const options = estados.map((estado, index) =>
                `<option value="${escapeHtmlGarantia(estado)}" ${estado === garantia.estado ? 'selected' : ''} ${index < currentStageIndex || index > currentStageIndex + 1 ? 'disabled' : ''}>${escapeHtmlGarantia(estado)}</option>`
            ).join('');
            const tracking = garantia.hash_publico
                ? `<a class="btn btn-sm btn-outline-primary fw-bold" target="_blank" rel="noopener" href="tracking.php?h=${encodeURIComponent(garantia.hash_publico)}"><i class="bi bi-box-arrow-up-right"></i> Tracking oficial</a>`
                : '';
            return `<div class="col-12">
                <article class="card shadow-sm ${finalized ? '' : 'border-danger'}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <h5 class="fw-bold mb-1">Garantía #${id} · Orden ${escapeHtmlGarantia(garantia.nro_orden_fabrica || garantia.id_orden)}</h5>
                                <div class="small text-muted">Cotización ${escapeHtmlGarantia(garantia.nro_cotizacion || '')} · ${escapeHtmlGarantia(garantia.cliente || '')} · Recibida ${escapeHtmlGarantia(garantia.creado_en || '')}</div>
                            </div>
                            <span class="badge ${finalized ? 'bg-success' : 'bg-danger'} align-self-start">${escapeHtmlGarantia(garantia.estado)}</span>
                        </div>
                        <p class="mt-3 mb-2">${escapeHtmlGarantia(garantia.motivo).replace(/\r?\n/g, '<br>')}</p>
                        <div class="mb-3">${evidence || '<span class="text-muted small">Sin imágenes adjuntas.</span>'}</div>
                        <details class="small mb-3"><summary class="fw-bold">Historial de fases</summary><ul class="mt-2">${history || '<li>Sin movimientos registrados.</li>'}</ul></details>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            ${tracking}
                            <button class="btn btn-sm btn-outline-secondary fw-bold" onclick="abrirTracking(${Number(garantia.id_orden) || 0})"><i class="bi bi-list-check"></i> Abrir fases de Envíos</button>
                            <select class="form-select form-select-sm garantia-estado" data-id="${id}" ${finalized ? 'disabled' : ''} style="max-width:190px">${options}</select>
                            <button class="btn btn-sm btn-danger fw-bold js-guardar-estado-garantia" data-id="${id}" ${finalized ? 'disabled' : ''}>Guardar fase</button>
                        </div>
                    </div>
                </article>
            </div>`;
        }).join('');
        $lista.html(html);
        $lista.find('.js-guardar-estado-garantia').on('click', function() {
            const button = $(this);
            const idGarantia = button.data('id');
            const estado = $lista.find(`.garantia-estado[data-id="${idGarantia}"]`).val();
            button.prop('disabled', true);
            $.post('api/api_envios.php', {
                accion: 'actualizar_garantia_textil',
                id_garantia: idGarantia,
                estado: estado
            }, function(updateRes) {
                if (!updateRes.success) {
                    Swal.fire('No se pudo actualizar', updateRes.msg || 'Inténtalo de nuevo.', 'error');
                    return;
                }
                Swal.fire('Garantía actualizada', updateRes.msg, 'success');
                window.cargarGarantiasTextiles();
            }, 'json').fail(function() {
                Swal.fire('Error', 'No se pudo comunicar con Envíos.', 'error');
            }).always(function() {
                button.prop('disabled', false);
            });
        });
    }, 'json').fail(function() {
        $lista.html('<div class="col-12"><div class="alert alert-danger">No se pudo comunicar con la bandeja de garantías.</div></div>');
    });
};

window.renderizarFotosNuevas = function() {
    let html = '';
    archivosEvidenciaMemoria.forEach((item, index) => {
        html += `<div class="mt-2 p-2 rounded bg-white shadow-sm border-start border-primary border-4 d-flex align-items-center gap-2" style="border: 1px solid #dee2e6;">
            <div class="flex-grow-1">
                <label class="small text-muted mb-1"><i class="bi bi-image text-primary"></i> Archivo: <b class="text-dark">${item.file.name}</b></label>
                <input type="text" class="form-control form-control-sm border-primary fw-bold input-leyenda-mem" data-index="${index}" value="${item.leyenda}" placeholder="Escribe la descripcion de esta foto...">
            </div>
            <button type="button" class="btn btn-sm btn-danger h-100 px-3" onclick="eliminarFotoMemoria(${index})" title="Quitar de la lista"><i class="bi bi-trash fs-6"></i></button>
        </div>`;
    });
    $('#leyendas_nuevas_container').html(html);
};

window.eliminarFotoMemoria = function(index) {
    archivosEvidenciaMemoria.splice(index, 1);
    window.renderizarFotosNuevas();
};

window.notificarGarantia = function(e, tipoNoti) {
    e.stopPropagation(); 
    let id_orden = $('#current_id_orden').val();
    let btn = $(e.target).closest('button');
    let textoOriginal = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Avisando...');
    $.post('api/api_envios.php', {accion: 'notificar_vendedor_garantia', id_orden: id_orden, tipo: tipoNoti}, function(res) {
        if(res.success) Swal.fire('Aviso de Garantia', 'Se ha notificado a Ventas.', 'success');
    }, 'json').always(() => btn.prop('disabled', false).html(textoOriginal));
};

// ================= L�GICA DE LISTA DE INSUMOS =================

window.origenModalInsumo = 'orden';

window.pintarHistorialInsumosModal = function(listaHistorial) {
    if ($('#historial_insumos_orden').length === 0) {
        $('#lista_items_insumos_ul').after('<div class="mt-4 pt-3 border-top"><label class="fw-bold small text-primary mb-2"><i class="bi bi-clock-history"></i> Historial de Solicitudes:</label><ul class="list-group shadow-sm" id="historial_insumos_orden"></ul></div>');
    }

    let htmlHist = '';
    if (!Array.isArray(listaHistorial) || listaHistorial.length === 0) {
        htmlHist = '<li class="list-group-item text-muted small py-3 text-center bg-light">No has solicitado insumos previamente para este pedido.</li>';
    } else {
        listaHistorial.forEach(ins => {
            let isEntregado = (parseInt(ins.estado) === 1);
            let badge = isEntregado ? '<span class="badge bg-success">Entregado al Taller</span>' : '<span class="badge bg-warning text-dark">Pendiente en Compras</span>';
            
            let items = [];
            try {
                let p = JSON.parse(ins.descripcion);
                if (Array.isArray(p)) items = p; else items = [ins.descripcion];
            } catch(e) {
                items = String(ins.descripcion || '').split('\n').filter(i => i.trim() !== '');
            }
            
            let listaItems = items.map((itemStr, indexItem) => `
                <div class="mb-1 d-flex align-items-center bg-white border rounded px-2 py-1 shadow-sm mt-1 border-start border-primary border-4">
                    <i class="bi bi-pencil-square text-muted me-2" style="font-size: 0.8rem;"></i>
                    <input type="text" class="form-control form-control-sm border-0 fw-bold text-dark input-edit-insumo-hist" 
                           data-id-row="${ins.id}" 
                           data-index="${indexItem}" 
                           value="${String(itemStr).replace(/"/g, '&quot;')}" 
                           style="box-shadow: none; background: transparent; padding: 0;">
                    <button type="button" class="btn btn-sm text-success btn-save-insumo-hist ms-1" style="display:none;" data-id-row="${ins.id}" title="Guardar cambio"><i class="bi bi-check-circle-fill fs-5"></i></button>
                </div>`).join('');
            
            htmlHist += `
            <li class="list-group-item bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                    <small class="text-muted fw-bold">${ins.fecha_solicitud}</small>
                    ${badge}
                </div>
                <div class="fw-bold text-dark small">${listaItems}</div>
            </li>`;
        });
    }
    $('#historial_insumos_orden').html(htmlHist);
};

window.abrirModalSolicitarInsumo = function() {
    window.origenModalInsumo = 'orden';
    $('#current_id_cotizacion').val('0');
    $('#lbl_orden_insumo').text(current_nro_orden);
    $('#input_nuevo_insumo').val('');
    lista_insumos_temporales = []; 
    renderizarListaInsumos();
    window.pintarHistorialInsumosModal(insumos_pedidos_actuales);

    $('#modalTimeline').modal('hide');
    setTimeout(() => { $('#modalSolicitarInsumo').modal('show'); }, 400);
};

window.abrirModalSolicitarInsumoPreorden = function(id_cotizacion, nro_cotizacion, historial_previo) {
    window.origenModalInsumo = 'prealerta';
    $('#current_id_orden').val('0');
    $('#current_id_cotizacion').val(id_cotizacion);
    $('#lbl_orden_insumo').text('Pre-orden ' + nro_cotizacion);
    $('#input_nuevo_insumo').val('');
    lista_insumos_temporales = [];
    insumos_pedidos_actuales = Array.isArray(historial_previo) ? historial_previo : [];
    renderizarListaInsumos();
    window.pintarHistorialInsumosModal(insumos_pedidos_actuales);

    $('#modalSolicitarInsumo').modal('show');
};

window.renderizarListaInsumos = function() {
    let html = '';
    if(lista_insumos_temporales.length === 0) {
        html = '<li class="list-group-item text-center text-muted small py-3">La nueva lista esta vacia. Agrega insumos arriba.</li>';
    } else {
        lista_insumos_temporales.forEach((ins, idx) => {
            html += `
            <li class="list-group-item d-flex justify-content-between align-items-center fw-bold text-dark border-danger">
                <span><i class="bi bi-dot text-danger fs-5"></i> ${ins}</span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="quitarInsumoLista(${idx})"><i class="bi bi-trash fs-5"></i></button>
            </li>`;
        });
    }
    $('#lista_items_insumos_ul').html(html);
};

window.quitarInsumoLista = function(idx) {
    lista_insumos_temporales.splice(idx, 1);
    renderizarListaInsumos();
};

window.abrirBandejaInsumos = function() {
    if (typeof PERMISO_VER_INSUMOS === 'undefined' || !PERMISO_VER_INSUMOS) return;

    vista_actual = 'insumos';
    $('.nav-link-custom').removeClass('active');
    $('#btnNavInsumos').addClass('active');
    $('#headerPrincipal').show();
    $('#tituloPagina').html('<i class="bi bi-basket-fill text-danger me-2"></i> Bandeja de Insumos Faltantes').show();
    $('#divBuscadorEnvios').hide(); 
    $('#vistaNotasManuales').hide();
    
    $('#contenedor_ordenes').show().html('<div class="text-center w-100 py-5"><div class="spinner-border text-danger"></div></div>');
    
    $.post('api/api_envios.php', { accion: 'listar_insumos' }, function(res) {
        if(res.success) {
            let htmlPendientes = '';
            let htmlCompletados = '';

            if(res.data.length === 0) {
                $('#contenedor_ordenes').html('<div class="alert alert-success w-100 text-center fw-bold fs-5 shadow-sm"><i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i> No hay solicitudes de insumos en el sistema.</div>');
                return;
            }

            res.data.forEach(ins => {
                let listaHtml = '';
                let itemsArray = [];
                let checkEstados = {};
                try { checkEstados = JSON.parse(ins.check_estados || '{}'); } catch(e){}
                
                try {
                    let parsed = JSON.parse(ins.descripcion);
                    if(Array.isArray(parsed)) { itemsArray = parsed; } else { itemsArray = [ins.descripcion]; }
                } catch(e) {
                    itemsArray = ins.descripcion.split('\n').filter(i => i.trim() !== '');
                }

                let isEntregado = (parseInt(ins.estado) === 1);

                if(itemsArray.length > 0) {
                    itemsArray.forEach((item, i) => {
                        let cid = `chk_${ins.id}_${i}`;
                        let isChecked = checkEstados[i] || isEntregado ? 'checked' : '';
                        let textClass = (checkEstados[i] || isEntregado) ? 'text-decoration-line-through text-muted' : 'text-dark';
                        let disabled = isEntregado ? 'disabled' : '';

                        listaHtml += `
                        <div class="form-check text-start mb-2 bg-white p-2 rounded border border-secondary shadow-sm d-flex align-items-center transition-all">
                            <input class="form-check-input border-secondary ms-1 chk-insumo-item" type="checkbox" id="${cid}" data-idinsumo="${ins.id}" data-index="${i}" ${isChecked} ${disabled} style="transform: scale(1.4); cursor: pointer;">
                            <label class="form-check-label ms-3 fw-bold w-100 transition-all ${textClass}" for="${cid}" style="cursor:pointer; user-select: none;">
                                ${item}
                            </label>
                        </div>`;
                    });
                } else {
                    listaHtml = `<div class="p-2 bg-white border border-danger border-opacity-50 shadow-sm rounded small mb-3 fw-bold text-dark">No hay insumos especificados.</div>`;
                }

                let btnAccion = isEntregado ? 
                    `<div class="text-center w-100 p-2 bg-success text-white fw-bold rounded mt-auto shadow-sm"><i class="bi bi-check-circle-fill"></i> ENTREGADO AL TALLER</div>` : 
                    `<button class="btn btn-sm btn-success w-100 fw-bold shadow mt-auto" onclick="atenderInsumo(${ins.id})"><i class="bi bi-check2-all fs-6 align-middle"></i> MARCAR COMO ENTREGADO</button>`;

                let cardClass = isEntregado ? 'border-success' : 'border-danger';
                let headerClass = isEntregado ? 'bg-success' : 'bg-danger';
                let cardStyle = isEntregado ? 'background-color: #f2fcf5;' : 'background-color: #f8f9fa;';

                let cardHtml = `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card ${cardClass} shadow-sm h-100" style="border-width: 2px; ${cardStyle}">
                        <div class="card-header ${headerClass} text-white fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="bi ${isEntregado ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i> ${ins.nro_orden_fabrica}</span>
                            <span class="small" style="font-size: 0.75rem;">${ins.fecha_format}</span>
                        </div>
                        <div class="card-body d-flex flex-column" style="background: transparent;">
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;"><i class="bi bi-person-fill"></i> Cliente: <span class="text-primary">${ins.cliente}</span></h6>
                            <p class="small text-muted mb-3 border-bottom pb-2"><i class="bi bi-person-badge"></i> Solicitado por: <b>${ins.solicitante}</b></p>
                            
                            <div class="mb-3 flex-grow-1">
                                <label class="small fw-bold ${isEntregado ? 'text-success' : 'text-danger'} mb-2">Insumos solicitados:</label>
                                ${listaHtml}
                            </div>
                            ${btnAccion}
                        </div>
                    </div>
                </div>`;

                if(isEntregado) {
                    htmlCompletados += cardHtml;
                } else {
                    htmlPendientes += cardHtml;
                }
            });

            if(htmlPendientes === '') htmlPendientes = '<div class="col-12"><div class="alert alert-light border text-center text-muted fw-bold"><i class="bi bi-emoji-sunglasses fs-2 d-block mb-2"></i> No hay insumos pendientes por comprar.</div></div>';
            if(htmlCompletados === '') htmlCompletados = '<div class="col-12"><div class="alert alert-light border text-center text-muted fw-bold">El historial de insumos entregados esta vacio.</div></div>';

            let tabsHtml = `
            <ul class="nav nav-pills mb-4 border-bottom pb-3 w-100" id="pills-insumos" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-ins-pend" type="button" style="border-radius: 50px;"><i class="bi bi-exclamation-triangle-fill"></i> Pendientes por Comprar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 ms-2 text-success" data-bs-toggle="pill" data-bs-target="#tab-ins-hist" type="button" style="border-radius: 50px; background-color: #f2fcf5; border: 1px solid #198754;"><i class="bi bi-check-circle-fill"></i> Historial Entregados</button>
                </li>
            </ul>
            <div class="tab-content w-100">
                <div class="tab-pane fade show active" id="tab-ins-pend"><div class="row">${htmlPendientes}</div></div>
                <div class="tab-pane fade" id="tab-ins-hist"><div class="row">${htmlCompletados}</div></div>
            </div>`;

            $('#contenedor_ordenes').html(tabsHtml);
        }
    }, 'json');
    
    window.actualizarBadgeInsumos();
};

window.atenderInsumo = function(id_insumo) {
    if (!PERMISO_VER_INSUMOS) return; 
    
    Swal.fire({
        title: 'Confirmar Entrega',
        text: 'Ya entregaste todos estos insumos al taller? Pasaran al Historial verde.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Si, ya se entrego',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed) {
            $.post('api/api_envios.php', { accion: 'atender_insumo', id: id_insumo }, function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Atendido', timer: 1000, showConfirmButton: false});
                    window.abrirBandejaInsumos();
                }
            }, 'json');
        }
    });
};

window.actualizarBadgeInsumos = function() {
    if (typeof PERMISO_VER_INSUMOS === 'undefined' || !PERMISO_VER_INSUMOS || $('#badge_insumos_pendientes').length === 0) return;
    $.post('api/api_envios.php', { accion: 'contar_insumos' }, function(res) {
        if(res.success && res.cantidad > 0) {
            $('#badge_insumos_pendientes').text(res.cantidad).removeClass('d-none');
        } else {
            $('#badge_insumos_pendientes').addClass('d-none');
        }
    }, 'json');
};

// ================= FICHA DE PAGO ADMINISTRATIVO =================
window.formatoMonedaBs = function(valor) { return parseFloat(valor).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
window.formatoMonedaUsd = function(valor) { return parseFloat(valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

window.abrirFichaPago = function(id_orden) {
    $('#formFichaPago')[0].reset();
    $('#tbody_consumos').empty();
    
    $.post('api/api_envios.php', { accion: 'obtener_ficha_pago', id_orden: id_orden }, function(res) {
        if(res.success) {
            let ord = res.orden;
            let f = res.ficha;

            $('#ficha_id_orden').val(ord.id_orden);
            $('#ficha_nro_orden').text(ord.nro_orden);
            $('#ficha_nro_cotizacion').text(ord.nro_cotizacion);
            $('#ficha_cliente').text(ord.cliente);
            $('#ficha_vendedor').text(ord.vendedor);
            
            $('#ficha_cotizacion').val(ord.monto_cotizacion);
            $('#ficha_porc_oficina').val(f.porc_oficina);
            $('#ficha_porc_vendedor').val(f.porc_vendedor);
            $('#ficha_tasa').val(f.tasa_bcv);
            $('#ficha_abono').val(f.abonos);
            $('#ficha_estado').val(f.estado_ficha);

            let isSaved = f.consumos.some(c => parseFloat(c.monto) > 0) || f.estado_ficha === 'Liquidado';
            
            if (isSaved) {
                $('#ficha_porc_oficina, #ficha_porc_vendedor, #ficha_tasa').prop('readonly', true).addClass('bg-light text-muted');
                $('#ficha_tasa').next('button').hide();
                $('#btnImprimirFicha').remove();
                $('#btnGuardarFicha').before(`<button type="button" class="btn btn-outline-dark fw-bold me-auto ms-3" id="btnImprimirFicha" onclick="window.open('imprimir_ficha.php?id=${ord.id_orden}', '_blank')"><i class="bi bi-printer"></i> Imprimir PDF</button>`);
            } else {
                $('#ficha_porc_oficina, #ficha_porc_vendedor, #ficha_tasa').prop('readonly', false).removeClass('bg-light text-muted');
                $('#ficha_tasa').next('button').show();
                $('#btnImprimirFicha').remove();
            }

            if (f.consumos && f.consumos.length > 0) {
                f.consumos.forEach(c => {
                    let btnBorrar = c.auto === 'diezmo' || c.auto === 'vendedor' 
                        ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0" disabled><i class="bi bi-lock-fill"></i></button>` 
                        : `<button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove(); window.calcularFichaPago();"><i class="bi bi-trash"></i></button>`;
                    
                    let html = `
                    <tr class="fila-consumo" data-auto="${c.auto}">
                        <td><input type="text" class="form-control form-control-sm cons-item fw-bold text-dark border-0 bg-transparent" value="${c.item}" ${c.auto !== '' ? 'readonly' : ''} placeholder="Ej: Flete"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm cons-monto text-end border-primary fw-bold" value="${c.monto}" oninput="window.calcularFichaPago()"></td>
                        <td class="text-center">${btnBorrar}</td>
                    </tr>`;
                    $('#tbody_consumos').append(html);
                });
            }
            
            window.calcularFichaPago();
            $('#modalFichaPago').modal('show');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json');
};

window.consultarTasaBCV = function() {
    let btn = $('#ficha_tasa').next('button');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    
    $.ajax({
        url: 'https://ve.dolarapi.com/v1/dolares/oficial',
        type: 'GET',
        success: function(data) {
            if(data && data.promedio) {
                $('#ficha_tasa').val(data.promedio);
                window.calcularFichaPago();
            }
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i>');
        }
    });
};

window.agregarFilaConsumo = function() {
    let html = `
    <tr class="fila-consumo" data-auto="">
        <td><input type="text" class="form-control form-control-sm cons-item fw-bold text-dark border-primary" placeholder="Ej: Nuevo Gasto"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm cons-monto text-end border-primary fw-bold" value="0" oninput="window.calcularFichaPago()"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove(); window.calcularFichaPago();"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    $('#tbody_consumos').append(html);
};

window.calcularFichaPago = function() {
    let baseUsd = parseFloat($('#ficha_cotizacion').val()) || 0;
    let abonoUsd = parseFloat($('#ficha_abono').val()) || 0;
    let tasa = parseFloat($('#ficha_tasa').val()) || 1;
    let porcOfi = parseFloat($('#ficha_porc_oficina').val()) || 0;
    let porcVen = parseFloat($('#ficha_porc_vendedor').val()) || 0;

    let comOfiUsd = baseUsd * (porcOfi / 100);
    let comVenUsd = baseUsd * (porcVen / 100);
    let admUsd = baseUsd - comOfiUsd - comVenUsd;

    let comOfiBs = comOfiUsd * tasa;
    let comVenBs = comVenUsd * tasa;
    let admBs = admUsd * tasa;
    let totalComprobacionBs = comOfiBs + comVenBs + admBs;

    $('#res_ofi_usd').text('$ ' + window.formatoMonedaUsd(comOfiUsd));
    $('#res_ven_usd').text('$ ' + window.formatoMonedaUsd(comVenUsd));
    $('#res_adm_usd').text('$ ' + window.formatoMonedaUsd(admUsd));

    $('#res_ofi_bs').text('Bs. ' + window.formatoMonedaBs(comOfiBs));
    $('#res_ven_bs').text('Bs. ' + window.formatoMonedaBs(comVenBs));
    $('#res_adm_bs').text('Bs. ' + window.formatoMonedaBs(admBs));
    $('#res_comprobacion_bs').text('Bs. ' + window.formatoMonedaBs(totalComprobacionBs));

    $('.fila-consumo').each(function() {
        let auto = $(this).data('auto');
        if (auto === 'vendedor') { $(this).find('.cons-monto').val(comVenBs.toFixed(2)); }
        if (auto === 'diezmo') { $(this).find('.cons-monto').val(comOfiBs.toFixed(2)); } 
    });

    let totalConsumosBs = 0;
    $('.fila-consumo').each(function() {
        totalConsumosBs += parseFloat($(this).find('.cons-monto').val()) || 0;
    });

    $('#total_consumos_bs').text('Bs. ' + window.formatoMonedaBs(totalConsumosBs));

    let abonoBs = abonoUsd * tasa;
    let saldoPendiente = abonoBs - totalConsumosBs;
    $('#res_saldo_pendiente').text('Bs. ' + window.formatoMonedaBs(saldoPendiente));
};

$('#ficha_porc_oficina, #ficha_porc_vendedor, #ficha_tasa, #ficha_abono').on('input', function() {
    window.calcularFichaPago();
});

$('#btnGuardarFicha').click(function(e) {
    e.preventDefault();
    let btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    let consumos_arr = [];
    $('.fila-consumo').each(function() {
        consumos_arr.push({
            item: $(this).find('.cons-item').val(),
            monto: parseFloat($(this).find('.cons-monto').val()) || 0,
            auto: $(this).data('auto') || ''
        });
    });

    let datos = {
        accion: 'guardar_ficha_pago',
        id_orden: $('#ficha_id_orden').val(),
        tasa_bcv: $('#ficha_tasa').val(),
        porc_oficina: $('#ficha_porc_oficina').val(),
        porc_vendedor: $('#ficha_porc_vendedor').val(),
        abonos: $('#ficha_abono').val(),
        estado_ficha: $('#ficha_estado').val(),
        consumos_json: JSON.stringify(consumos_arr)
    };

    $.post('api/api_envios.php', datos, function(res) {
        if(res.success) {
            Swal.fire({icon: 'success', title: 'Ficha Guardada', text: 'Abriendo PDF...', timer: 1500, showConfirmButton: false}).then(() => {
                window.open('imprimir_ficha.php?id=' + $('#ficha_id_orden').val(), '_blank');
            });
            $('#modalFichaPago').modal('hide');
            cargarOrdenes(); 
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Guardar Ficha de Pago'));
});


// ============================ CARGA DE ORDENES PRINCIPAL ============================
// ============================ CARGA DE ORDENES PRINCIPAL CON CONTROL DE DÍAS DE ENTREGA ============================
function calcularBadgeTiempoEntrega(o, isArchivado) {
    if (!o.fecha_entrega || o.fecha_entrega === '0000-00-00') {
        return '<div class="small text-muted mb-2"><i class="bi bi-calendar-event"></i> Entrega: Sin fecha asignada</div>';
    }

    const partesEnt = String(o.fecha_entrega).slice(0, 10).split('-');
    if (partesEnt.length !== 3) return '';
    const fechaLimite = new Date(Number(partesEnt[0]), Number(partesEnt[1]) - 1, Number(partesEnt[2]));
    fechaLimite.setHours(0, 0, 0, 0);
    const fechaLimiteFormat = `${partesEnt[2]}/${partesEnt[1]}/${partesEnt[0]}`;

    if (isArchivado) {
        if (o.fecha_terminado && !String(o.fecha_terminado).startsWith('0000')) {
            const pTerm = String(o.fecha_terminado).slice(0, 10).split('-');
            const fechaFin = new Date(Number(pTerm[0]), Number(pTerm[1]) - 1, Number(pTerm[2]));
            fechaFin.setHours(0, 0, 0, 0);
            const diffDias = Math.round((fechaFin - fechaLimite) / 86400000);
            if (diffDias <= 0) {
                return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-success-subtle text-success border border-success rounded px-2 py-1 fw-bold">
                    <span><i class="bi bi-calendar-check-fill"></i> Meta: ${fechaLimiteFormat}</span>
                    <span><i class="bi bi-award-fill"></i> Entregada a tiempo</span>
                </div>`;
            } else {
                return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-danger-subtle text-danger border border-danger rounded px-2 py-1 fw-bold">
                    <span><i class="bi bi-calendar-x-fill"></i> Meta: ${fechaLimiteFormat}</span>
                    <span>+${diffDias} ${diffDias === 1 ? 'día' : 'días'} de retraso</span>
                </div>`;
            }
        }
        return `<div class="small text-muted mb-2"><i class="bi bi-calendar-check"></i> Fecha meta: <b>${fechaLimiteFormat}</b></div>`;
    }

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const diasRestantes = Math.round((fechaLimite - hoy) / 86400000);

    if (diasRestantes < 0) {
        const atraso = Math.abs(diasRestantes);
        return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-danger text-white rounded px-2 py-1 fw-bold shadow-sm">
            <span><i class="bi bi-alarm-fill"></i> Entrega: ${fechaLimiteFormat}</span>
            <span>Vencida hace ${atraso} ${atraso === 1 ? 'día' : 'días'}</span>
        </div>`;
    } else if (diasRestantes === 0) {
        return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-danger text-white rounded px-2 py-1 fw-bold shadow-sm">
            <span><i class="bi bi-alarm-fill"></i> Entrega: ${fechaLimiteFormat}</span>
            <span>¡Entrega HOY!</span>
        </div>`;
    } else if (diasRestantes <= 3) {
        return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-warning text-dark border border-warning rounded px-2 py-1 fw-bold">
            <span><i class="bi bi-hourglass-split"></i> Entrega: ${fechaLimiteFormat}</span>
            <span>Faltan ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}</span>
        </div>`;
    } else {
        return `<div class="d-flex justify-content-between align-items-center mb-2 small bg-info-subtle text-dark border border-info rounded px-2 py-1 fw-bold">
            <span><i class="bi bi-calendar3"></i> Entrega: ${fechaLimiteFormat}</span>
            <span class="text-primary">Faltan ${diasRestantes} días</span>
        </div>`;
    }
}

function cargarOrdenes() {
    $('#contenedor_ordenes').html('<div class="text-center w-100 py-5"><div class="spinner-border text-primary"></div></div>');
    $.post('api/api_envios.php', {accion: 'listar_ordenes'}, function(res) {
        if(res.success) {
            let html = '';
            let cont = 0;
            res.data.forEach(o => {
                let isTerminado = (o.estado_envio === 'Terminado');
                let isEntregado = (o.estado_envio === 'Entregado');
                let isArchivado = isTerminado || isEntregado;
                
                let esUrgente = (o.prioridad == 1 && !isArchivado);

                if (vista_actual === 'proceso' && isArchivado) return;
                if (vista_actual === 'archivador' && !isArchivado) return;
                if (vista_actual === 'insumos' || vista_actual === 'notas_manuales') return;

                let badge = '';
                let borderStyle = '';
                let bgCard = '';
                let opacityStyle = '';

                if (isEntregado) {
                    badge = '<span class="badge bg-success"><i class="bi bi-check-all"></i> Entregado al Cliente</span>';
                    borderStyle = 'border: 2px solid #198754 !important;';
                    bgCard = 'background-color: #f2fcf5 !important;';
                    opacityStyle = 'opacity: 0.95;';
                } else if (isTerminado) {
                    badge = '<span class="badge bg-dark"><i class="bi bi-archive-fill"></i> Finalizado (En Stock)</span>';
                    borderStyle = 'border: 2px solid #6c757d !important;';
                    bgCard = 'background-color: #f8f9fa !important;';
                } else {
                    if (esUrgente) {
                        badge = '<span class="badge bg-danger pulse-wa text-white"><i class="bi bi-exclamation-triangle-fill"></i> URGENTE</span>';
                        borderStyle = 'border: 2px solid #dc3545 !important;';
                        bgCard = 'background-color: #fff5f5 !important;'; 
                    } else {
                        badge = '<span class="badge bg-warning text-dark">En Proceso</span>';
                        borderStyle = 'border: 1px solid #ced4da !important;';
                        bgCard = 'background-color: #ffffff !important;';
                    }
                }

                let clseUrgenteHTML = esUrgente ? 'card-urgente' : '';
                let prendasBadge = `<span class="badge bg-light text-dark border border-secondary shadow-sm ms-2" style="font-size:0.75rem;"><i class="bi bi-tag-fill text-primary"></i> ${o.prendas_totales} Prendas</span>`;
                let garantiaBadge = Number(o.garantias_abiertas) > 0
                    ? `<span class="badge bg-danger shadow-sm ms-1"><i class="bi bi-shield-exclamation"></i> Garantía abierta (${Number(o.garantias_abiertas)})</span>`
                    : '';

                let infoTiempoEntrega = calcularBadgeTiempoEntrega(o, isArchivado);

                cont++;
                
                let btnEntregado = '';
                if (isTerminado && !isEntregado) {
                    btnEntregado = `<button class="btn btn-sm btn-success w-100 fw-bold mt-2 shadow-sm" onclick="marcarEntregado(${o.id_orden})"><i class="bi bi-box-seam"></i> Marcar como Entregado</button>`;
                }

                let btnSemaforo = '';
                if (typeof PERMISO_PRIORIDAD !== 'undefined' && PERMISO_PRIORIDAD && !isArchivado) {
                    if (esUrgente) {
                        btnSemaforo = `<button class="btn btn-sm btn-danger w-100 fw-bold mt-2 shadow-sm" onclick="window.togglePrioridad(${o.id_orden})"><i class="bi bi-stoplights-fill"></i> Quitar Urgencia</button>`;
                    } else {
                        btnSemaforo = `<button class="btn btn-sm btn-outline-danger w-100 fw-bold mt-2 shadow-sm" onclick="window.togglePrioridad(${o.id_orden})"><i class="bi bi-stoplights"></i> Marcar Urgente</button>`;
                    }
                }

                let isPagoAprobado = (o.estado_pago.toLowerCase() === 'pagado' || o.estado_pago.toLowerCase().includes('muestra'));

                html += `
                <div class="col-md-6 col-lg-4 mb-4 d-flex card-orden-wrap">
                    <div class="card card-orden w-100 shadow-sm ${clseUrgenteHTML}" style="${borderStyle} ${bgCard} ${opacityStyle}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold ${isEntregado ? 'text-success' : 'text-primary'} m-0" style="cursor: pointer;" title="Abrir Orden" onclick="window.open('../portal_ventas/imprimir_orden.php?id=${o.id_orden}', '_blank')">
                                    ${o.nro_orden_fabrica}
                                </h5>
                                ${badge}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0 text-muted small text-truncate pe-2"><i class="bi bi-person-fill"></i> ${o.cliente}</p>
                                <div class="text-end">${prendasBadge}${garantiaBadge}</div>
                            </div>
                            ${infoTiempoEntrega}
                            <p class="mb-3 text-muted small"><i class="bi bi-wallet-fill"></i> Pago: <b class="${isPagoAprobado ? 'text-success' : 'text-danger'}">${o.estado_pago.toUpperCase()}</b></p>
                            
                            <div class="d-flex flex-column gap-1">
                                <button class="btn ${isEntregado ? 'btn-outline-success' : 'btn-outline-primary'} w-100 fw-bold" onclick="abrirTracking(${o.id_orden})">
                                    <i class="bi bi-gear-wide-connected"></i> Gestionar Fases
                                </button>
                                ${btnEntregado}
                                ${btnSemaforo}
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            if(cont === 0) html = `<div class="text-center w-100 text-muted mt-5 fw-bold"><i class="bi bi-inbox fs-1 d-block mb-2"></i> No hay ordenes en esta vista.</div>`;
            if(vista_actual === 'proceso' || vista_actual === 'archivador') $('#contenedor_ordenes').html(html);
        }
    }, 'json');
}

window.togglePrioridad = function(id_orden) {
    $.post('api/api_envios.php', {accion: 'toggle_prioridad', id_orden: id_orden}, function(res) {
        if(res.success) {
            cargarOrdenes();
        }
    }, 'json');
};

window.marcarEntregado = function(id_orden) {
    Swal.fire({
        title: 'Confirmar Entrega',
        text: 'La orden se marcara de color verde confirmando que el cliente ya tiene su mercancia.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Si, Entregado',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/api_envios.php', {accion: 'entregar_envio', id_orden: id_orden}, function(res) {
                if (res.success) {
                    Swal.fire({icon: 'success', title: 'Exito', showConfirmButton: false, timer: 1500});
                    cargarOrdenes();
                    window.cargarPrendasMes();
                }
            }, 'json');
        }
    });
};

window.abrirTracking = function(id_orden, silencioso = false) {
    if(!silencioso) $('#cuerpoTimeline').html('<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>');
    $('#current_id_orden').val(id_orden);

    $.post('api/api_envios.php', {accion: 'cargar_fases', id_orden: id_orden}, function(res) {
        if(res.success) {
            $('#current_hash').val(res.hash);
            $('#current_id_tracking').val(res.id_tracking);
            current_tlf = res.tlf;
            current_cliente = res.cliente;
            current_nro_orden = res.nro_orden;
            fases_memoria = res.fases; 
            insumos_pedidos_actuales = res.insumos_pedidos || []; 

            $('#btnFinalizarEnvio, #btnAvisarCobro, #btnGarantiaTextil, #btnFaltanInsumos, #msgFinalizarRestringido').addClass('d-none');
            $('#btnAvisarCobro').html('<i class="bi bi-bell-fill"></i> Avisar a Ventas (Cobro)');
            
            if (vista_actual === 'archivador') {
                $('#btnGarantiaTextil').removeClass('d-none');
            } else {
                if (typeof PERMISO_SOLICITAR_INSUMO !== 'undefined' && PERMISO_SOLICITAR_INSUMO === true) {
                    $('#btnFaltanInsumos').removeClass('d-none');
                } else {
                    $('#btnFaltanInsumos').addClass('d-none');
                }

                if (res.all_completed) {
                    if (res.is_pagado) {
                        $('#btnFinalizarEnvio').removeClass('d-none');
                    } else {
                        $('#btnAvisarCobro').removeClass('d-none');
                        $('#msgFinalizarRestringido').html(`<i class="bi bi-exclamation-triangle-fill"></i> Requiere pago total`).removeClass('d-none');
                    }
                } else {
                    $('#msgFinalizarRestringido').html(`<i class="bi bi-info-circle-fill"></i> Faltan fases por terminar`).removeClass('d-none');
                }
            }

            let html = '';
            res.fases.forEach(f => {
                let statusText = f.estado == 2 ? 'Terminado' : (f.estado == 1 ? 'En Proceso' : 'Pendiente');
                let isGarantia = f.fase_nombre.toLowerCase().includes('garantia') || f.fase_nombre.toLowerCase().includes('garant�a');
                
                let iconoSeguro = getIconoSeguro(f.fase_nombre);
                let borderColor = isGarantia ? 'border-danger' : '';
                let bgStyle = isGarantia ? 'background-color: #fff5f5; border: 2px solid #dc3545;' : '';

                let headerAdicional = isGarantia ? `<div class="bg-danger text-white text-center fw-bold p-2 mb-2 rounded" style="font-size: 1.1rem; letter-spacing: 1px;"><i class="bi bi-shield-exclamation"></i> FASE DE GARANTIA TEXTIL</div>` : '';
                let tituloCustom = isGarantia ? `<h5 class="fw-bold m-0 text-danger" style="line-height: 1.2;">${f.fase_nombre}</h5>` : `<h6 class="fw-bold m-0 text-dark">${f.fase_nombre}</h6>`;
                
                let btnAvisarVentasGar = '';
                if (isGarantia) {
                    if (f.estado == 1) { 
                        btnAvisarVentasGar = `<button class="btn btn-sm btn-outline-warning mt-3 w-100 fw-bold" onclick="notificarGarantia(event, 4)"><i class="bi bi-bell-fill"></i> Avisar a Ventas (Garantia en Proceso)</button>`;
                    } else if (f.estado == 2) {
                        btnAvisarVentasGar = `<button class="btn btn-sm btn-outline-danger mt-3 w-100 fw-bold" onclick="notificarGarantia(event, 5)"><i class="bi bi-bell-fill"></i> Avisar a Ventas (Garantia Lista)</button>`;
                    }
                }

                let imgHtml = '';
                if(f.foto) {
                    let fotos = f.foto.split(',');
                    let leyendas = {};
                    try { leyendas = JSON.parse(f.foto_leyendas || '{}'); } catch(e){}
                    
                    imgHtml = '<div class="d-flex flex-wrap gap-3 mt-3">';
                    fotos.forEach(img => {
                        if(img.trim() !== '') {
                            let imgName = img.trim();
                            let isPdf = imgName.includes('.pdf');
                            let cap = leyendas[imgName] || '';
                            let capHtml = cap ? `<div class="small text-center mt-2 text-dark fw-bold lh-sm p-1 rounded" style="max-width:120px; white-space:pre-wrap; overflow-wrap:anywhere; background-color:rgba(255,117,24,0.1); border-bottom: 2px solid #FF7518;">${escapeHtmlGarantia(cap)}</div>` : '';
                            
                            let fileVisual = isPdf 
                                ? `<a href="../../images/envios/${imgName}" target="_blank" class="shadow-sm d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark bg-white" style="width: 120px; height: 120px; border-radius: 8px; border: 1px solid #ddd;"><i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i><br>PDF</a>` 
                                : `<a href="../../images/envios/${imgName}" target="_blank"><img src="../../images/envios/${imgName}" class="shadow-sm" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;"></a>`;
                            
                            imgHtml += `<div class="d-flex flex-column align-items-center">${fileVisual}${capHtml}</div>`;
                        }
                    });
                    imgHtml += '</div>';
                }

                let obsHtml = f.observacion ? `<div class="small text-dark mt-2 p-2 bg-light rounded" style="border-left: 3px solid #FF7518; white-space:pre-wrap; overflow-wrap:anywhere;"><i>"${escapeHtmlGarantia(f.observacion)}"</i></div>` : '';
                let fechas = (f.fecha_inicio || f.fecha_fin) ? `<div class="small text-muted mt-2"><i class="bi bi-calendar-range"></i> ${f.fecha_inicio || '?'} al ${f.fecha_fin || '?'}</div>` : '';

                html += `
                <div class="timeline-item estado-${f.estado}">
                    <div class="timeline-icon shadow-sm ${borderColor} d-flex align-items-center justify-content-center fs-5 text-dark" style="background:#fff;">${iconoSeguro}</div>
                    <div class="timeline-content ${borderColor}" style="cursor:pointer; ${bgStyle}" onclick="editarFaseDesdeMemoria(${f.id})">
                        ${headerAdicional}
                        <div class="d-flex justify-content-between align-items-center">
                            ${tituloCustom}
                            <span class="badge ${isGarantia ? 'bg-danger' : 'bg-secondary'}">${statusText}</span>
                        </div>
                        ${fechas}
                        ${obsHtml}
                        ${imgHtml}
                        ${btnAvisarVentasGar}
                    </div>
                </div>`;
            });
            $('#cuerpoTimeline').html(html);
            if(!silencioso) $('#modalTimeline').modal('show');
        }
    }, 'json');
}

window.editarFaseDesdeMemoria = function(id_fase) {
    let f = fases_memoria.find(x => x.id == id_fase);
    if(!f) return;

    $('#formFase')[0].reset();
    $('#fase_id').val(f.id);
    $('#fase_nombre_oculto').val(f.fase_nombre);
    $('#fase_estado').val(f.estado);
    $('#fase_inicio').val(f.fecha_inicio || '');
    $('#fase_fin').val(f.fecha_fin || '');
    $('#fase_observacion').val(f.observacion || '');
    
    archivosEvidenciaMemoria = [];
    $('#leyendas_nuevas_container').empty();
    $('#input_fotos_fase').val('');

    if (f.fase_nombre.toLowerCase().includes('listo') || f.fase_nombre.toLowerCase().includes('entregar')) {
        $('#div_factura_fase').removeClass('d-none');
    } else {
        $('#div_factura_fase').addClass('d-none');
    }

    if(f.foto && f.foto.trim() !== '') {
        let fotos = f.foto.split(',');
        let leyendas = {};
        try { leyendas = JSON.parse(f.foto_leyendas || '{}'); } catch(e){}

        let htmlGal = '<div class="d-flex flex-column gap-2">';
        fotos.forEach(img => {
            let imgName = img.trim();
            if(imgName !== '') {
                let boxId = 'box_img_' + imgName.replace(/\./g, '_');
                let isPdf = imgName.includes('.pdf');
                let contenidoMuestra = isPdf ? '<div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light border rounded"><i class="bi bi-file-pdf text-danger fs-3"></i></div>' : `<img src="../../images/envios/${imgName}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">`;
                let cap = leyendas[imgName] || '';
                
                htmlGal += `
                <div class="d-flex align-items-center gap-2 p-2 border rounded bg-white shadow-sm" id="${boxId}">
                    <div style="width: 60px; height: 60px; flex-shrink: 0;">${contenidoMuestra}</div>
                    <div class="flex-grow-1">
                        <label class="small text-muted mb-1 d-block fw-bold" style="font-size: 10px;">Descripcion actual de la foto:</label>
                        <input type="text" name="leyenda_existente[${imgName}]" class="form-control form-control-sm border-secondary fw-bold text-dark input-leyenda-mem" value="${cap}" placeholder="Describe el detalle...">
                    </div>
                    <button type="button" class="btn btn-sm btn-danger h-100 btn-delete-foto-existente" style="padding: 10px;" data-idfase="${f.id}" data-foto="${imgName}" title="Borrar evidencia"><i class="bi bi-trash fs-5"></i></button>
                </div>`;
            }
        });
        htmlGal += '</div>';
        $('#galeria_fotos_fase').html(htmlGal);
        $('#contenedor_galeria_fase').show();
    } else {
        $('#galeria_fotos_fase').empty();
        $('#contenedor_galeria_fase').hide();
    }

    $('#modalTimeline').modal('hide');
    setTimeout(() => { $('#modalEditarFase').modal('show'); }, 400);

    $('#modalEditarFase').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        if (!$('body').hasClass('modal-open')) {
            abrirTracking($('#current_id_orden').val(), true);
            $('#modalTimeline').modal('show');
        }
    });
};

function renderizarFasesConfig() {
    let html = '';
    fases_memoria.forEach((f, idx) => {
        let btnUp = idx > 0 ? `<button class="btn btn-sm btn-light py-0" onclick="moverFase(${f.id}, 'up')"><i class="bi bi-arrow-up"></i></button>` : '';
        let btnDown = idx < fases_memoria.length - 1 ? `<button class="btn btn-sm btn-light py-0" onclick="moverFase(${f.id}, 'down')"><i class="bi bi-arrow-down"></i></button>` : '';

        let iconoAUsar = getIconoSeguro(f.fase_nombre);

        html += `
        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <span><span class="me-2 text-primary">${iconoAUsar}</span> <span class="small fw-bold">${f.fase_nombre}</span></span>
            <div class="d-flex gap-1">
                ${btnUp}
                ${btnDown}
                <button class="btn btn-sm btn-danger py-0 ms-2" onclick="eliminarFase(${f.id})"><i class="bi bi-x"></i></button>
            </div>
        </li>`;
    });
    $('#listaFasesConfig').html(html);
}

window.agregarFase = function() {
    let id_t = $('#current_id_tracking').val();
    let nom = $('#nueva_fase_nombre').val().trim();
    
    if(!nom) return;
    $.post('api/api_envios.php', {accion: 'agregar_fase', id_tracking: id_t, nombre: nom, icono: 'G'}, function(res) {
        if(res.success) {
            $('#nueva_fase_nombre').val('');
            $('#nueva_fase_icono').val('');
            recargarFasesConfig();
        }
    }, 'json');
};

window.eliminarFase = function(id) {
    $.post('api/api_envios.php', {accion: 'eliminar_fase', id_fase: id}, function(res) {
        if(res.success) recargarFasesConfig();
    }, 'json');
};

window.moverFase = function(id, dir) {
    $.post('api/api_envios.php', {accion: 'mover_fase', id_fase: id, direccion: dir}, function(res) {
        if(res.success) recargarFasesConfig();
    }, 'json');
};

function recargarFasesConfig() {
    let id_o = $('#current_id_orden').val();
    $.post('api/api_envios.php', {accion: 'cargar_fases', id_orden: id_o}, function(res) {
        fases_memoria = res.fases;
        renderizarFasesConfig();
        abrirTracking(id_o, true);
    }, 'json');
}

// RESTO DE EVENTOS (FOTOS Y OTHERS)
$(document).ready(function() {
    cargarOrdenes();
    window.actualizarBadgeInsumos();
    window.cargarPrendasMes(); // <--- Llamada a la Carga Inicial de M�tricas

    // AUTO-MAYUSCULA EN OBSERVACIONES, INSUMOS, LEYENDAS E HISTORIAL EDITABLE
    $(document).on('input', '#fase_observacion, #input_nuevo_insumo, .input-leyenda-mem, .input-edit-insumo-hist', function() {
        let val = $(this).val();
        if (val.length > 0) {
            val = val.charAt(0).toUpperCase() + val.slice(1);
            $(this).val(val);
        }
        
        if ($(this).hasClass('input-leyenda-mem') &&$(this).data('index') !== undefined) {
            let idx = $(this).data('index');
            archivosEvidenciaMemoria[idx].leyenda = val;
        }

        if ($(this).hasClass('input-edit-insumo-hist')) {$(this).closest('div').find('.btn-save-insumo-hist').fadeIn(200);
        }
    });

    $(document).on('click', '.btn-save-insumo-hist', function() {
        let btn = $(this);
        let id_row = btn.data('id-row');
        
        let nuevos_items = [];
        $(`.input-edit-insumo-hist[data-id-row="${id_row}"]`).each(function() {
            let val = $(this).val().trim();
            if(val !== '') nuevos_items.push(val);
        });

        if (nuevos_items.length === 0) {
            Swal.fire('Atenci�n', 'No puedes dejar la lista vac�a.', 'warning');
            return;
        }

        btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);

        $.post('api/api_envios.php', {
            accion: 'editar_insumo_historial',
            id: id_row,
            descripcion: JSON.stringify(nuevos_items)
        }, function(res) {
            if(res.success) {
                btn.hide().html('<i class="bi bi-check-circle-fill fs-5"></i>').prop('disabled', false);
                
                let insumoEnMemoria = insumos_pedidos_actuales.find(i => i.id == id_row);
                if (insumoEnMemoria) {
                    insumoEnMemoria.descripcion = JSON.stringify(nuevos_items);
                }
            } else {
                Swal.fire('Error', res.msg, 'error');
                btn.html('<i class="bi bi-check-circle-fill fs-5"></i>').prop('disabled', false);
            }
        }, 'json');
    });

    $(document).on('change', '.chk-insumo-item', function() {
        let chk = $(this);
        let isChecked = chk.is(':checked');
        
        chk.closest('div').find('label').toggleClass('text-decoration-line-through text-muted', isChecked).toggleClass('text-dark', !isChecked);

        let id_insumo = chk.data('idinsumo');
        let estados = {};
        $(`.chk-insumo-item[data-idinsumo="${id_insumo}"]`).each(function() {
            estados[$(this).data('index')] =$(this).is(':checked');
        });

        $.post('api/api_envios.php', { 
            accion: 'actualizar_checks_insumo', 
            id: id_insumo, 
            checks: JSON.stringify(estados) 
        });
    });

    $('#buscadorEnvios').on('input', function() {
        let filtro = $(this).val().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
        $('.card-orden-wrap').each(function() {
            let texto = $(this).text().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
            $(this).toggle(texto.includes(filtro));
        });
    });

    $('#input_fotos_fase').removeAttr('name');
    $('#input_fotos_fase').on('change', function(e) {
        let files = e.target.files;
        for(let i=0; i<files.length; i++) {
            archivosEvidenciaMemoria.push({ file: files[i], leyenda: '' });
        }
        $(this).val(''); 
        window.renderizarFotosNuevas();
    });

    $('#formFase').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let id_orden = $('#current_id_orden').val();

        for(let i=0; i<archivosEvidenciaMemoria.length; i++) {
            formData.append('foto_fase[]', archivosEvidenciaMemoria[i].file);
            formData.append('leyenda_nueva['+i+']', archivosEvidenciaMemoria[i].leyenda);
        }

        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: 'api/api_envios.php', type: 'POST', data: formData, processData: false, contentType: false,
            success: function(res) {
                if(res.success) {
                    $('#modalEditarFase').modal('hide');
                    abrirTracking(id_orden, true);
                } else {
                    Swal.fire('Error', res.msg || 'No se pudo actualizar', 'error');
                }
            },
            complete: function() { btn.prop('disabled', false).text('Guardar Avance'); }
        });
    });

    $(document).on('click', '.btn-delete-foto-existente', function(e) {
        e.preventDefault();
        let btn = $(this);
        let id_fase = btn.data('idfase');
        let nombre_foto = btn.data('foto');

        Swal.fire({
            title: 'Eliminar Evidencia',
            text: 'Seguro que desea borrar permanentemente esta foto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
                
                $.post('api/api_envios.php', {accion: 'eliminar_foto_fase', id_fase: id_fase, foto: nombre_foto}, function(res) {
                    if(res.success) {
                        let boxId = '#box_img_' + nombre_foto.replace(/\./g, '_');
                        $(boxId).fadeOut(300, function(){$(this).remove(); });
                        abrirTracking($('#current_id_orden').val(), true); 
                    } else {
                        Swal.fire('Error', 'No se pudo borrar la foto', 'error');
                        btn.prop('disabled', false).html('<i class="bi bi-trash fs-5"></i>');
                    }
                }, 'json');
            }
        });
    });

    $('#btnAvisarAvance').click(function() {
        let id_orden = $('#current_id_orden').val();
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Notificando...');
        $.post('api/api_envios.php', {accion: 'notificar_vendedor_avance', id_orden: id_orden}, function(res) {
            if(res.success) Swal.fire('Aviso Enviado', 'Se ha notificado a Ventas.', 'success');
        }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-bell-fill"></i> Notificar a Ventas'));
    });

    $('#btnFinalizarEnvio').click(function() {
        let id_orden = $('#current_id_orden').val();
        Swal.fire({
            title: 'Finalizar Produccion',
            text: 'La orden se movera al Archivador y se avisara a Ventas.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Si, Finalizar', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api/api_envios.php', {accion: 'finalizar_envio', id_orden: id_orden}, function(res) {
                    if (res.success) {
                        $('#modalTimeline').modal('hide');
                        cargarOrdenes();
                        window.cargarPrendasMes(); 
                        Swal.fire('Terminado', 'Orden archivada.', 'success');
                    }
                }, 'json');
            }
        });
    });

    $('#btnAvisarCobro').click(function() {
        let id_orden = $('#current_id_orden').val();
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Avisando...');
        $.post('api/api_envios.php', {accion: 'notificar_vendedor_cobro', id_orden: id_orden}, function(res) {
            if(res.success) Swal.fire('Aviso Enviado', 'Se notifico a Ventas.', 'success');
        }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-bell-fill"></i> Avisar a Ventas (Cobro)'));
    });

    $('#btnNotaEntrega').click(function() {
        let id_orden = $('#current_id_orden').val();
        window.open('nota_entrega.php?id=' + id_orden, '_blank');
    });

    $('#btnConfigurarFases').click(function() {
        renderizarFasesConfig();
        $('#modalConfigurarFases').modal('show');
    });

    $('#btnGarantiaTextil').click(function() {
        $('#modalTimeline').one('hidden.bs.modal', function () {
            Swal.fire({
                title: 'Garantia Textil',
                text: 'Describa el motivo general de la garantia:',
                input: 'text',
                inputPlaceholder: 'Ej: Ajuste de mangas...',
                showCancelButton: true,
                confirmButtonText: 'Abrir Garantia',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && result.value.trim() !== '') {
                    let id_t = $('#current_id_tracking').val();
                    let nomFase = 'Garantia Textil: ' + result.value.trim();
                    $.post('api/api_envios.php', {accion: 'agregar_fase', id_tracking: id_t, nombre: nomFase, icono: 'G'}, function(res) {
                        if(res.success) {
                            Swal.fire('Garantia Abierta', 'Haga clic en la nueva tarjeta roja para describir fotos y notificar.', 'success').then(() => {
                                abrirTracking($('#current_id_orden').val(), false);
                            });
                        }
                    }, 'json');
                } else {
                    $('#modalTimeline').modal('show');
                }
            });
        }).modal('hide');
    });

    $('#modalSolicitarInsumo').off('hidden.bs.modal').on('hidden.bs.modal', function() {
        if (window.origenModalInsumo === 'orden' && Number($('#current_id_orden').val()) > 0) {
            $('#modalTimeline').modal('show');
        }
    });

    $('#btn_add_insumo').click(function() {
        let val = $('#input_nuevo_insumo').val().trim();
        if(val !== '') {
            val = val.charAt(0).toUpperCase() + val.slice(1);
            lista_insumos_temporales.push(val);
            $('#input_nuevo_insumo').val('');
            renderizarListaInsumos();
            $('#input_nuevo_insumo').focus();
        }
    });

    $('#input_nuevo_insumo').keypress(function(e) {
        if(e.which == 13) {
            e.preventDefault();
            $('#btn_add_insumo').click();
        }
    });

    $('#btn_enviar_lista_insumos').click(function(e) {
        e.preventDefault();
        
        if(lista_insumos_temporales.length === 0) {
            Swal.fire('Lista Vacía', 'Agrega al menos un insumo a la lista usando el botón Añadir.', 'warning');
            return;
        }

        let btn = $(this);
        let texto_json = JSON.stringify(lista_insumos_temporales);
        let id_orden = $('#current_id_orden').val() || 0;
        let id_cotizacion = $('#current_id_cotizacion').val() || 0;
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');
        
        $.post('api/api_envios.php', {
            accion: 'solicitar_insumo',
            id_orden: id_orden,
            id_cotizacion: id_cotizacion,
            descripcion: texto_json,
            solicitante: USUARIO_ACTUAL
        }, function(res) {
            if(res.success) {
                Swal.fire('Solicitud Enviada', 'Los insumos faltantes han sido reportados a Compras.', 'success');
                $('#modalSolicitarInsumo').modal('hide');
                window.actualizarBadgeInsumos();
                if (typeof window.cargarPrealertasProduccion === 'function') {
                    window.cargarPrealertasProduccion();
                }
            } else {
                Swal.fire('Error', res.msg || 'No se pudo enviar la solicitud.', 'error');
            }
        }, 'json').always(() => btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Enviar a Compras'));
    });
});