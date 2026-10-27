let dataEstantes = []; 
let prendasActuales = []; 

function mostrarVista(idVista) {
    $('.vista-modulo').hide();
    $('.nav-link-custom.small').removeClass('text-white fw-bold').addClass('text-white-50');
    $('#' + idVista).show();
    
    if(idVista === 'vistaStock') $('#btnMenuStock').removeClass('text-white-50').addClass('text-white fw-bold');
    if(idVista === 'vistaNotas') $('#btnMenuNotas').removeClass('text-white-50').addClass('text-white fw-bold');
    if(idVista === 'vistaClasificacion') $('#btnMenuCategorias').removeClass('text-white-50').addClass('text-white fw-bold');
    if(idVista === 'vistaReportes') $('#btnMenuReportes').removeClass('text-white-50').addClass('text-white fw-bold');

    if(idVista === 'vistaReportes') { $('#filtro_sede_reporte').trigger('change'); }
    if(idVista === 'vistaNotas') cargarHistorialNotas();
}

$(document).ready(function() {
    $('#btnHamburguesa').on('click', function() { $('#sidebar, #sidebarOverlay').addClass('active'); });
    $('#sidebarOverlay, .nav-link-custom').on('click', function() { if ($(window).width() <= 768) { $('#sidebar, #sidebarOverlay').removeClass('active'); } });

    mostrarVista('vistaStock');
    $('#filtro_sede').trigger('change');

    $('#inv_categoria').on('change', function() {
        let catId = $(this).val();
        $('#inv_subcategoria option').hide();
        $('#inv_subcategoria option[value=""]').show();
        if(catId) { $(`#inv_subcategoria option[data-cat="${catId}"]`).show(); }
        $('#inv_subcategoria').val('');
    });

    $('#inv_estante').on('change', function() {
        let id_estante = $(this).val();
        let estante = dataEstantes.find(e => e.id == id_estante);
        if(estante) {
            let htmlN = '<option value="">Seleccione...</option>';
            for(let i=1; i<=estante.niveles; i++) htmlN += `<option value="${i}">Nivel ${i}</option>`;
            $('#inv_nivel').html(htmlN);
            let htmlL = '<option value="">Seleccione...</option>';
            for(let i=1; i<=estante.columnas; i++) htmlL += `<option value="${i}">Columna ${i}</option>`;
            $('#inv_lado').html(htmlL);
        } else {
            $('#inv_nivel').html('<option value="">Seleccione Estante...</option>');
            $('#inv_lado').html('<option value="">Seleccione Estante...</option>');
        }
    });

    $('#inv_articulo, #inv_categoria, #inv_subcategoria').on('input change', function() {
        let articulo = $('#inv_articulo').val().toLowerCase();
        let catText = $('#inv_categoria option:selected').text().toLowerCase();
        let subcatText = $('#inv_subcategoria option:selected').text().toLowerCase();
        if (articulo.includes('gorra') || catText.includes('gorra') || subcatText.includes('gorra')) { $('#div_detalles_gorra').slideDown(); } 
        else { $('#div_detalles_gorra').slideUp(); }
    });

    $('#buscadorStock').on('keyup', cargarStock);
    $('#filtro_estante, #filtro_cat, #filtro_sub, #filtro_marca, #filtro_ubicacion').on('change', cargarStock);
});

function cambioSedeStock(idSede) { cargarEstantesPorSede(idSede, '#filtro_estante', function(){ cargarStock(); }); }
function cambioSedeReporte(idSede) { cargarEstantesPorSede(idSede, '#filtro_estante_reporte', function(){ cargarReportes(); }); }
function cambioSedePrenda(idSede) { cargarEstantesPorSede(idSede, '#inv_estante'); }
function cambioEstantePrenda(idEstante) {
    let estante = dataEstantes.find(e => e.id == idEstante);
    if(estante) {
        let htmlN = '<option value="">Seleccione...</option>';
        for(let i=1; i<=estante.niveles; i++) htmlN += `<option value="${i}">Nivel ${i}</option>`;
        $('#inv_nivel').html(htmlN);
        let htmlL = '<option value="">Seleccione...</option>';
        for(let i=1; i<=estante.columnas; i++) htmlL += `<option value="${i}">Columna ${i}</option>`;
        $('#inv_lado').html(htmlL);
    } else {
        $('#inv_nivel').html('<option value="">Seleccione Estante...</option>');
        $('#inv_lado').html('<option value="">Seleccione Estante...</option>');
    }
}

function cargarEstantesPorSede(id_sede, selectId, callback=null) {
    $(selectId).html('<option value="">Cargando...</option>');
    if(id_sede === '' || id_sede == 0) {
        $(selectId).html('<option value="">Todos los Estantes</option>');
        dataEstantes = [];
        if(callback) callback();
        return;
    }
    $.post('api_inventario.php', { accion: 'get_estantes_por_sede', id_sede: id_sede }, function(res) {
        if(res.success) {
            dataEstantes = res.data;
            let html = '<option value="">Todos los Estantes</option>';
            res.data.forEach(e => { html += `<option value="${e.id}">[${e.codigo}] ${e.nombre}</option>`; });
            $(selectId).html(html);
        }
        if(callback) callback();
    }, 'json');
}

function cargarStock() {
    $('#tablaStockBody').html('<tr><td colspan="7" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success"></div> Consultando estantería...</td></tr>');
    let params = { 
        accion: 'get_prendas', search: $('#buscadorStock').val(), 
        sede: $('#filtro_sede').val(), estante: $('#filtro_estante').val(),
        cat: $('#filtro_cat').val(), sub: $('#filtro_sub').val(), 
        marca: $('#filtro_marca').val(), nivel: $('#filtro_ubicacion').val() 
    };
    
    $.post('api_inventario.php', params, function(res) {
        if(res.success) {
            prendasActuales = res.data;
            let html = '';
            if(res.data.length === 0) {
                html = '<tr><td colspan="7" class="text-center text-muted py-4">No hay prendas registradas con estos filtros.</td></tr>';
            } else {
                res.data.forEach(p => {
                    let precioFormat = parseFloat(p.precio_base).toFixed(2);
                    let nombreColor = p.color ? ` - ${p.color}` : '';
                    let extraBadges = '';
                    if(p.detalles_extra) {
                        try {
                            let d = JSON.parse(p.detalles_extra); let attr = [];
                            if(d.paneles) attr.push(`${d.paneles} Paneles`); if(d.tela) attr.push(`Tela: ${d.tela}`);
                            if(d.malla) attr.push(`Malla: ${d.malla}`); if(d.broche) attr.push(`Broche: ${d.broche}`);
                            if(attr.length > 0) extraBadges = `<br><span class="badge bg-info text-dark mt-1" style="font-size:0.65rem;"><i class="bi bi-info-circle"></i> ${attr.join(' | ')}</span>`;
                        } catch(e) {}
                    }
                    html += `<tr>
                        <td class="fw-bold text-muted">${p.codigo_lote}</td>
                        <td class="small lh-sm"><i class="bi bi-building text-info"></i> ${p.sede_nombre || 'S/Sede'}<br><i class="bi bi-archive text-primary"></i> ${p.estante_nombre || 'S/Estante'}</td>
                        <td class="fw-bold lh-sm">${p.articulo}${nombreColor}<br><span class="badge bg-secondary" style="font-size:0.65rem;">${p.marca_nombre || 'S/Marca'}</span> <span class="badge bg-dark" style="font-size:0.65rem;">${p.genero}</span>${extraBadges}</td>
                        <td class="text-center fw-bold text-primary fs-5">${p.talla}</td>
                        <td class="text-center"><span class="badge ${p.stock > 0 ? 'bg-success' : 'bg-danger'} fs-6">${p.stock}</span></td>
                        <td class="text-center fw-bold text-dark">${precioFormat} $</td>
                        <td class="text-center text-nowrap">
                            <button class="btn btn-sm btn-outline-info fw-bold" onclick="verEstante(${p.id_estante}, ${p.estante_niveles}, ${p.estante_columnas}, '${p.estante_nombre}', ${p.nivel_estante}, '${p.lado_estante}')" title="Ubicar en Estantería"><i class="bi bi-eye-fill"></i> Nvl ${p.nivel_estante} (Col ${p.lado_estante})</button>
                            <button class="btn btn-sm btn-warning text-dark fw-bold ms-1" onclick="abrirModalAddCarrito(${p.id}, '${p.articulo.replace(/'/g, "\\'")}${nombreColor} (${p.genero} - Talla: ${p.talla})', ${p.stock}, ${p.precio_base})" title="Añadir a Cotización"><i class="bi bi-cart-plus"></i></button>
                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="abrirModalFormPrenda(${p.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger ms-1" onclick="eliminarPrendaStock(${p.id})" title="Eliminar del Estante"><i class="bi bi-trash"></i></button>
                        </td></tr>`;
                });
            }
            $('#tablaStockBody').html(html);
        } else { $('#tablaStockBody').html(`<tr><td colspan="7" class="text-center text-danger py-4">Error: ${res.msg}</td></tr>`); }
    }, 'json');
}

function moverOrden(id_prenda, direccion) {
    $.post('api_inventario.php', { accion: 'reordenar_estante', id_prenda: id_prenda, direccion: direccion }, function(res) {
        if(res.success) {
            if($('#modalVisualizadorEstante').is(':visible')) {
                let id_estante = $('#modalVisualizadorEstante').data('id_estante'); let niveles = $('#modalVisualizadorEstante').data('niveles');
                let columnas = $('#modalVisualizadorEstante').data('columnas'); let nombre = $('#modalVisualizadorEstante').data('nombre');
                let nivel = $('#modalVisualizadorEstante').data('nivel'); let lado = $('#modalVisualizadorEstante').data('lado');
                verEstante(id_estante, niveles, columnas, nombre, nivel, lado);
            }
            if($('#vistaReportes').is(':visible')) cargarReportes(); 
        } else { Swal.fire('Error', res.msg, 'error'); }
    }, 'json');
}

function dibujarEstanteDinamico(niveles, columnas) {
    let html = '';
    for(let n=1; n<=niveles; n++) {
        html += `<div class="shelf-level" id="nivel_${n}">`;
        for(let c=1; c<=columnas; c++) { html += `<div class="shelf-slot" id="slot_${n}_${c}"><div class="jeans-stack"></div></div>`; if(c < columnas) html += `<div class="shelf-divider"></div>`; }
        html += `</div>`;
        if(n == niveles) html += `<div class="wood-board bottom-board"></div>`; else html += `<div class="wood-board"></div>`;
    }
    $('#bookcase_dinamico').html(html);
}

function verEstante(id_estante, niveles, columnas, nombre_estante, nivel_objetivo, lado_objetivo) {
    $('#modalVisualizadorEstante').data({id_estante: id_estante, niveles: niveles, columnas: columnas, nombre: nombre_estante, nivel: nivel_objetivo, lado: lado_objetivo});
    dibujarEstanteDinamico(niveles, columnas);
    $('.shelf-slot').removeClass('slot-highlight');
    $('#lbl_titulo_estante_modal').html(`<i class="bi bi-archive-fill"></i> ${nombre_estante || 'Sin Estante'}`);
    $('#lbl_ubicacion_exacta').text(`NIVEL ${nivel_objetivo} - COLUMNA ${lado_objetivo}`);
    $(`#slot_${nivel_objetivo}_${lado_objetivo}`).addClass('slot-highlight');
    $('#lista_prendas_slot').html('<li class="list-group-item text-center text-muted"><div class="spinner-border spinner-border-sm"></div> Inspeccionando estante...</li>');
    $('#estante_contenido_detalle').show();

    $.post('api_inventario.php', { accion: 'get_slot_content', id_estante: id_estante, nivel: nivel_objetivo, lado: lado_objetivo }, function(res) {
        if(res.success) {
            let html = '';
            if(res.data.length === 0) { html = '<li class="list-group-item text-muted text-center fst-italic">El compartimento está vacío.</li>'; } else {
                res.data.forEach((p, index) => {
                    let btnUp = index > 0 ? `<button class="btn btn-ordenar mb-1" onclick="moverOrden(${p.id}, 'up')"><i class="bi bi-caret-up-fill"></i></button>` : `<button class="btn btn-ordenar mb-1" disabled><i class="bi bi-caret-up-fill"></i></button>`;
                    let btnDown = index < (res.data.length - 1) ? `<button class="btn btn-ordenar" onclick="moverOrden(${p.id}, 'down')"><i class="bi bi-caret-down-fill"></i></button>` : `<button class="btn btn-ordenar" disabled><i class="bi bi-caret-down-fill"></i></button>`;
                    let extraTxt = '';
                    if(p.detalles_extra) {
                        try {
                            let d = JSON.parse(p.detalles_extra); let attr = [];
                            if(d.paneles) attr.push(d.paneles + ' Paneles'); if(d.tela) attr.push(d.tela);
                            if(d.malla) attr.push('Malla ' + d.malla); if(d.broche) attr.push(d.broche);
                            if(attr.length > 0) extraTxt = `<br><small class="text-info" style="font-size:0.7rem;">[${attr.join(', ')}]</small>`;
                        } catch(e){}
                    }
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1 border-0 border-bottom" style="background: #fdfbf7;"><div class="d-flex align-items-center gap-3"><div class="d-flex flex-column">${btnUp}${btnDown}</div><div><span class="fw-bold" style="color:#0B4C8C; font-size:1rem;">${p.articulo}${p.color ? ' - '+p.color : ''}</span>${extraTxt}<br><small class="text-muted"><i class="bi bi-tag-fill"></i> ${p.marca_nombre} - ${p.genero}</small></div></div><div class="text-end"><span class="badge bg-primary rounded-pill mb-1 fs-6">Talla: ${p.talla}</span><br><span class="badge bg-dark rounded-pill">${p.stock} pzas</span></div></li>`;
                });
            }
            $('#lista_prendas_slot').html(html);
        } else { $('#lista_prendas_slot').html(`<li class="list-group-item text-danger">${res.msg}</li>`); }
    }, 'json');
    $('#modalVisualizadorEstante').modal('show');
}

function cargarReportes() {
    $('#contenedorReporteEstante').html('<div class="text-center py-5 text-muted"><div class="spinner-border text-success"></div><br>Cargando reporte global...</div>');
    $.post('api_inventario.php', { accion: 'get_estante_organizador', sede: $('#filtro_sede_reporte').val(), estante: $('#filtro_estante_reporte').val() }, function(res) {
        if(res.success) {
            let html = '';
            if(res.data.length === 0) { $('#contenedorReporteEstante').html('<div class="alert alert-info text-center fw-bold"><i class="bi bi-info-circle"></i> No hay prendas en esta ubicación.</div>'); return; }
            let agrupado = {};
            res.data.forEach(i => { let llave = i.sede_nombre + ' | ' + i.estante_nombre; if(!agrupado[llave]) agrupado[llave] = { sede: i.sede_nombre, estante: i.estante_nombre, items: [] }; agrupado[llave].items.push(i); });
            for (const [key, grupo] of Object.entries(agrupado)) {
                html += `<div class="bg-white p-3 rounded border shadow-sm mb-4"><h4 class="fw-bold border-bottom pb-2" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${grupo.sede} <i class="bi bi-arrow-right"></i> <i class="bi bi-archive-fill text-warning"></i> ${grupo.estante}</h4>`;
                let niveles = [...new Set(grupo.items.map(i => i.nivel_estante))].sort((a,b)=>a-b);
                niveles.forEach(nivel => {
                    html += `<h6 class="fw-bold mt-3 text-secondary">NIVEL ${nivel}</h6><div class="row g-2">`;
                    let columnas = [...new Set(grupo.items.filter(i=>i.nivel_estante == nivel).map(i => i.lado_estante))].sort((a,b)=>a-b);
                    columnas.forEach(col => {
                        html += `<div class="col-md-6"><div class="card shadow-sm border-0 h-100 bg-light"><div class="card-body py-2"><h6 class="fw-bold text-dark mb-2 border-bottom pb-1">Columna ${col}</h6><ul class="list-group list-group-flush bg-transparent">`;
                        let itemsCol = grupo.items.filter(i => i.nivel_estante == nivel && i.lado_estante == col);
                        itemsCol.forEach((item, index) => {
                            let btnUp = index > 0 ? `<button class="btn btn-ordenar" onclick="moverOrden(${item.id}, 'up')"><i class="bi bi-caret-up-fill"></i></button>` : `<button class="btn btn-ordenar" disabled><i class="bi bi-caret-up-fill"></i></button>`;
                            let btnDown = index < (itemsCol.length - 1) ? `<button class="btn btn-ordenar ms-1" onclick="moverOrden(${item.id}, 'down')"><i class="bi bi-caret-down-fill"></i></button>` : `<button class="btn btn-ordenar ms-1" disabled><i class="bi bi-caret-down-fill"></i></button>`;
                            html += `<li class="list-group-item bg-transparent p-1 border-0 border-bottom d-flex align-items-center justify-content-between"><div class="d-flex align-items-center gap-2"><div class="d-flex">${btnUp}${btnDown}</div><span class="small lh-sm"><span class="fw-bold text-dark">${item.articulo}${item.color ? ' - '+item.color : ''}</span><br><span class="text-muted"><i class="bi bi-tag"></i> ${item.marca} | Talla: ${item.talla} | ${item.genero}</span></span></div><span class="badge bg-dark rounded-pill" style="min-width:45px;">${item.stock} und</span></li>`;
                        });
                        html += `</ul></div></div></div>`;
                    });
                    html += `</div>`;
                });
                html += `</div>`;
            }
            $('#contenedorReporteEstante').html(html);
        } else { $('#contenedorReporteEstante').html(`<div class="alert alert-danger">${res.msg}</div>`); }
    }, 'json');
}

function generarCodigoLote() { return 'INV-' + (Math.floor(Math.random() * 90000) + 10000); }

function abrirModalFormPrenda(id = 0) {
    $('#inv_id_prenda').val(id);
    $('#gorra_paneles, #gorra_tela, #gorra_malla, #gorra_broche').val('');
    if(id === 0) {
        $('#inv_lote').val(generarCodigoLote()); $('#inv_sede, #inv_categoria, #inv_marca, #inv_artic, #inv_color, #inv_talla, #inv_stock, #inv_precio').val('');
        $('#inv_genero').val('Unisex'); $('#tituloModalPrenda').html('<i class="bi bi-box-seam"></i> Registrar Prenda Física');
        setTimeout(() => { $('#inv_articulo').trigger('change'); }, 200);
    } else {
        let p = prendasActuales.find(x => x.id == id); if(!p) return;
        $('#inv_lote').val(p.codigo_lote); $('#inv_sede').val(p.id_sede || '');
        cargarEstantesPorSede(p.id_sede || 0, '#inv_estante', function(){ $('#inv_estante').val(p.id_estante || '').trigger('change'); setTimeout(() => { $('#inv_nivel').val(p.nivel_estante || 1); $('#inv_lado').val(p.lado_estante || '1'); }, 300); });
        $('#inv_categoria').val(p.id_categoria || '').trigger('change'); setTimeout(() => { $('#inv_subcategoria').val(p.id_subcategoria || ''); }, 200);
        $('#inv_marca').val(p.id_marca || ''); $('#inv_articulo').val(p.articulo); $('#inv_color').val(p.color); $('#inv_genero').val(p.genero); $('#inv_talla').val(p.talla); $('#inv_stock').val(p.stock); $('#inv_precio').val(p.precio_base);
        $('#tituloModalPrenda').html('<i class="bi bi-pencil"></i> Editar Prenda Física');
        if (p.detalles_extra) { try { let d = JSON.parse(p.detalles_extra); if(d.paneles) $('#gorra_paneles').val(d.paneles); if(d.tela) $('#gorra_tela').val(d.tela); if(d.malla) $('#gorra_malla').val(d.malla); if(d.broche) $('#gorra_broche').val(d.broche); } catch(e) {} }
        setTimeout(() => { $('#inv_articulo').trigger('change'); }, 200);
    }
    $('#modalFormPrenda').modal('show');
}

$('#formGuardarPrenda').submit(function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Guardando...');
    let detalles_extra = '';
    if ($('#div_detalles_gorra').is(':visible')) {
        let obj = {}; if ($('#gorra_paneles').val()) obj.paneles = $('#gorra_paneles').val(); if ($('#gorra_tela').val()) obj.tela = $('#gorra_tela').val(); if ($('#gorra_malla').val()) obj.malla = $('#gorra_malla').val(); if ($('#gorra_broche').val()) obj.broche = $('#gorra_broche').val();
        if (Object.keys(obj).length > 0) detalles_extra = JSON.stringify(obj);
    }
    let datosForm = { accion: 'save_prenda', id: $('#inv_id_prenda').val(), codigo_lote: $('#inv_lote').val(), id_sede: $('#inv_sede').val(), id_estante: $('#inv_estante').val(), id_categoria: $('#inv_categoria').val(), id_subcategoria: $('#inv_subcategoria').val(), id_marca: $('#inv_marca').val(), articulo: $('#inv_articulo').val(), color: $('#inv_color').val(), genero: $('#inv_genero').val(), talla: $('#inv_talla').val(), stock: $('#inv_stock').val(), precio_base: $('#inv_precio').val(), nivel_estante: $('#inv_nivel').val(), lado_estante: $('#inv_lado').val(), detalles_extra: detalles_extra };
    $.post('api_inventario.php', datosForm, function(res) {
        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar en Estante');
        if(res.success) { Swal.fire({icon: 'success', title: 'Guardado', timer: 1500, showConfirmButton: false}); $('#modalFormPrenda').modal('hide'); cargarStock(); } else { Swal.fire('Error BDD', res.msg, 'error'); }
    }, 'json').fail(function(xhr) { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar en Estante'); Swal.fire('Error del Servidor', xhr.responseText, 'error'); });
});

function eliminarPrendaStock(id) { Swal.fire({ title: '¿Eliminar del inventario?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, eliminar' }).then((result) => { if (result.isConfirmed) { $.post('api_inventario.php', { accion: 'delete_prenda', id: id }, function(res) { if(res.success) { Swal.fire('Eliminado', '', 'success'); cargarStock(); } else { Swal.fire('Error BDD', res.msg, 'error'); } }, 'json'); } }); }

// ==========================================
// MANTENIMIENTO
// ==========================================
let mant_edit_id = 0; 
function abrirModalMantenimiento(tipo) {
    $('#mant_tipo').val(tipo); $('#tituloMantenimientoCat').html(`<i class="bi bi-gear"></i> Gestión de ${tipo}`);
    $('#mant_nombre').val(''); $('#mant_select_cat').val(''); $('#mant_select_sede').val(''); $('#mant_codigo').val(''); $('#mant_niveles').val(5); $('#mant_columnas').val(2); mant_edit_id = 0; $('#lbl_mant_nombre').text('Registrar Nuevo:');
    $('#mant_select_cat, #mant_select_sede, #mant_codigo, #mant_niveles, #mant_columnas, .label-cat, .label-sede, .label-estantes').hide().prop('required', false);
    if(tipo === 'Subcategorías') { $('#mant_select_cat, .label-cat').show().prop('required', true); } else if (tipo === 'Estantes') { $('#mant_select_sede, #mant_codigo, #mant_niveles, #mant_columnas, .label-sede, .label-estantes').show().prop('required', true); }
    cargarTablaMantenimiento(tipo); $('#modalMantenimientoCat').modal('show');
}
function cargarTablaMantenimiento(tipo) {
    $('#tbodyMantenimiento').html('<tr><td colspan="2" class="text-center text-muted"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</td></tr>');
    $.post('api_inventario.php', { accion: 'get_mantenimiento', tipo: tipo }, function(res) {
        if(res.success) {
            let html = '';
            if(res.data.length === 0) { html = '<tr><td colspan="2" class="text-center text-muted">No hay registros.</td></tr>'; } else {
                res.data.forEach(item => {
                    let textExtra = ''; let paramsExtra = '0';
                    if(tipo === 'Subcategorías') { textExtra = `<br><small class="text-muted"><i class="bi bi-arrow-return-right"></i> Padre: ${item.categoria_nombre}</small>`; paramsExtra = item.id_categoria; }
                    else if (tipo === 'Estantes') { textExtra = `<br><small class="text-muted"><i class="bi bi-building"></i> ${item.sede_nombre} | Cód: ${item.codigo} | Estructura: ${item.niveles} x ${item.columnas}</small>`; paramsExtra = `${item.id_sede}, '${item.codigo}', ${item.niveles}, ${item.columnas}`; }
                    html += `<tr><td class="fw-bold text-dark lh-sm">${item.nombre} ${textExtra}</td><td class="text-center text-nowrap"><button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editarMantenimiento('${tipo}', ${item.id}, '${item.nombre.replace(/'/g, "\\'")}', ${paramsExtra})"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-outline-danger py-0 px-1 ms-1" onclick="eliminarMantenimiento('${tipo}', ${item.id})"><i class="bi bi-trash"></i></button></td></tr>`;
                });
            }
            $('#tbodyMantenimiento').html(html);
        }
    }, 'json');
}
function editarMantenimiento(tipo, id, nombre, p1=0, p2='', p3=5, p4=2) { mant_edit_id = id; $('#lbl_mant_nombre').text('Editando registro:'); $('#mant_nombre').val(nombre); if(tipo === 'Subcategorías') $('#mant_select_cat').val(p1); if(tipo === 'Estantes') { $('#mant_select_sede').val(p1); $('#mant_codigo').val(p2); $('#mant_niveles').val(p3); $('#mant_columnas').val(p4); } $('#mant_nombre').focus(); }
$('#formNuevaClasificacion').submit(function(e) { e.preventDefault(); let btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>'); $.post('api_inventario.php', { accion: 'save_mantenimiento', tipo: $('#mant_tipo').val(), id: mant_edit_id, nombre: $('#mant_nombre').val(), id_categoria: $('#mant_select_cat').val(), id_sede: $('#mant_select_sede').val(), codigo: $('#mant_codigo').val(), niveles: $('#mant_niveles').val(), columnas: $('#mant_columnas').val() }, function(res) { btn.prop('disabled', false).html('<i class="bi bi-plus"></i> Guardar'); if(res.success) { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Guardado', showConfirmButton: false, timer: 1500 }); $('#mant_nombre').val(''); $('#mant_select_cat').val(''); $('#mant_codigo').val(''); mant_edit_id = 0; $('#lbl_mant_nombre').text('Registrar Nuevo:'); cargarTablaMantenimiento($('#mant_tipo').val()); $('#modalMantenimientoCat').data('necesita_recarga', true); } }, 'json'); });
function eliminarMantenimiento(tipo, id) { Swal.fire({ title: `¿Eliminar?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, eliminar' }).then((result) => { if (result.isConfirmed) { $.post('api_inventario.php', { accion: 'delete_mantenimiento', tipo: tipo, id: id }, function(res) { if(res.success) { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Eliminado', showConfirmButton: false, timer: 1500 }); cargarTablaMantenimiento(tipo); $('#modalMantenimientoCat').data('necesita_recarga', true); } else { Swal.fire('Atención', res.msg, 'warning'); } }, 'json'); } }); }
$('#modalMantenimientoCat').on('hidden.bs.modal', function () { if ($(this).data('necesita_recarga')) { location.reload(); } });

// ==========================================
// CARRITO, COTIZACIONES Y NOTAS DE ENTREGA
// ==========================================
let carritoPrendas = []; let totalGlobalCarrito = 0;
function abrirModalAddCarrito(id, nombreFormat, stockMaximo, precioBase) { $('#add_id_prenda').val(id); $('#add_nombre_prenda').val(nombreFormat); $('#add_lbl_prenda').text(nombreFormat); $('#add_lbl_stock').text(stockMaximo); $('#add_cantidad').val('').attr('max', stockMaximo); $('#add_precio').val(precioBase); $('#modalAddCarrito').modal('show'); }
$('#formAddCarrito').submit(function(e) { e.preventDefault(); let id = parseInt($('#add_id_prenda').val()), nombre = $('#add_nombre_prenda').val(), cantidad = parseInt($('#add_cantidad').val()), precio = parseFloat($('#add_precio').val()), max = parseInt($('#add_cantidad').attr('max')); if(cantidad > max) { Swal.fire('Atención', `Quedan ${max} unidades.`, 'warning'); return; } let existe = carritoPrendas.find(p => p.id === id); if(existe) { if((existe.cantidad + cantidad) > max) { Swal.fire('Atención', `Excede stock de ${max}.`, 'warning'); return; } existe.cantidad += cantidad; existe.subtotal = existe.cantidad * existe.precio; } else { carritoPrendas.push({ id: id, nombre: nombre, cantidad: cantidad, precio: precio, subtotal: cantidad * precio }); } renderizarCarrito(); $('#modalAddCarrito').modal('hide'); Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Añadido al carrito', showConfirmButton: false, timer: 1500 }); });
function renderizarCarrito() { let tbody = $('#tbodyCarritoInv'); tbody.empty(); totalGlobalCarrito = 0; let cantTotal = 0; if(carritoPrendas.length === 0) { tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">Carrito vacío</td></tr>'); $('#lblBurbujaInv').text('0'); $('#lblTotalCarritoInv').text('0.00 $'); return; } carritoPrendas.forEach((item, index) => { totalGlobalCarrito += item.subtotal; cantTotal += item.cantidad; tbody.append(`<tr><td class="fw-bold" style="font-size:0.8rem;">${item.nombre}</td><td class="text-center fw-bold text-primary">${item.cantidad}</td><td class="text-end">${item.precio.toFixed(2)}</td><td class="text-end fw-bold">${item.subtotal.toFixed(2)}</td><td class="text-center"><button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="quitarDelCarrito(${index})"><i class="bi bi-x"></i></button></td></tr>`); }); $('#lblBurbujaInv').text(cantTotal); $('#lblTotalCarritoInv').text(totalGlobalCarrito.toFixed(2) + ' $'); }
function quitarDelCarrito(index) { carritoPrendas.splice(index, 1); renderizarCarrito(); }
function toggleFormularioCliente() { let checked = $('#checkNuevoCliente').is(':checked'); if(checked) { $('#div_seleccionar_cliente').hide(); $('#cart_cliente_existente').prop('required', false); $('#div_crear_cliente').show(); $('#cart_cli_nombre').prop('required', true); $('#cart_cli_cedula').prop('required', true); } else { $('#div_crear_cliente').hide(); $('#cart_cli_nombre').prop('required', false); $('#cart_cli_cedula').prop('required', false); $('#div_seleccionar_cliente').show(); $('#cart_cliente_existente').prop('required', true); } }

function generarNotaEntrega() {
    if(carritoPrendas.length === 0) { Swal.fire('Atención', 'El carrito está vacío.', 'warning'); return; }
    let esNuevoCli = $('#checkNuevoCliente').is(':checked'), id_cliente = $('#cart_cliente_existente').val(), cli_nombre = $('#cart_cli_nombre').val(), cli_cedula = $('#cart_cli_cedula').val(), cli_tlf = $('#cart_cli_tlf').val();
    if(!esNuevoCli && id_cliente === '') { Swal.fire('Error', 'Seleccione cliente.', 'error'); return; }
    if(esNuevoCli && (cli_nombre === '' || cli_cedula === '')) { Swal.fire('Error', 'Complete Nombre y Cédula.', 'error'); return; }

    Swal.fire({ title: '¿Generar Cotización?', text: "Se apartará la mercancía en el sistema.", icon: 'question', showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Sí, Generar' }).then((result) => {
        if (result.isConfirmed) {
            $('#btnGenerarFacturaInv').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Procesando...');
            $.post('api_inventario.php', { accion: 'checkout_carrito', nuevo_cli: esNuevoCli, id_cliente: id_cliente, cli_nombre: cli_nombre, cli_cedula: cli_cedula, cli_tlf: cli_tlf, total_usd: totalGlobalCarrito, carrito: JSON.stringify(carritoPrendas) }, function(res) {
                $('#btnGenerarFacturaInv').prop('disabled', false).html('<i class="bi bi-file-earmark-text"></i> Generar Cotización');
                if(res.success) {
                    Swal.fire({title: '¡Cotización Generada!', text: 'Mercancía apartada en inventario.', icon: 'success', timer: 1500, showConfirmButton: false});
                    carritoPrendas = []; renderizarCarrito(); $('#modalCarritoInv').modal('hide'); cargarStock(); cargarReportes(); 
                    window.open(`imprimir_cotizacion_inv.php?id=${res.id_nota}`, '_blank');
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
}

function cargarHistorialNotas() {
    $('#tablaNotasActivasBody').html('<tr><td colspan="7" class="text-center text-muted"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</td></tr>');
    $('#tablaNotasFinalizadasBody').html('<tr><td colspan="7" class="text-center text-muted"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</td></tr>');
    $.post('api_inventario.php', { accion: 'get_notas', estado: 'activas' }, function(res) { if(res.success) renderTablaNotas(res.data, 'tablaNotasActivasBody'); }, 'json');
    $.post('api_inventario.php', { accion: 'get_notas', estado: 'finalizadas' }, function(res) { if(res.success) renderTablaNotas(res.data, 'tablaNotasFinalizadasBody'); }, 'json');
}

function renderTablaNotas(data, targetId) {
    let tbody = $('#' + targetId);
    if(data.length === 0) { tbody.html('<tr><td colspan="7" class="text-center text-muted py-4">No hay documentos en este estado.</td></tr>'); return; }
    let html = '';
    data.forEach(n => {
        let badgeColor = n.estado === 'Pendiente' ? 'bg-danger' : (n.estado === 'Pagado' ? 'bg-success' : 'bg-secondary');
        let fechaArr = n.fecha_emision.split(' ');
        
        let btnCotizacion = `<a href="imprimir_cotizacion_inv.php?id=${n.id}" target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimir Cotización"><i class="bi bi-file-pdf"></i></a>`;
        let btnNota = '';
        let btnAcciones = '';
        
        if(n.estado === 'Pendiente') {
            btnAcciones += `<button class="btn btn-sm btn-warning text-dark fw-bold ms-1" onclick="abrirModalPagarNota(${n.id})" title="Pagar y Generar Nota"><i class="bi bi-currency-dollar"></i> Pagar</button>`;
        } else if (n.estado === 'Pagado' || n.estado === 'Finalizada') {
            // SOLO APARECE LA NOTA DE ENTREGA SI ESTÁ PAGADO
            btnNota = `<a href="imprimir_nota_inv.php?id=${n.id}" target="_blank" class="btn btn-sm btn-outline-success ms-1" title="Imprimir Nota de Entrega"><i class="bi bi-truck"></i> Nota</a>`;
            if(n.comprobante) {
                // RUTA CORREGIDA: Apunta a /uploads/ en la raíz del sistema
                btnAcciones += `<a href="../uploads/${n.comprobante}" target="_blank" class="btn btn-sm btn-outline-info fw-bold ms-1" title="Ver Comprobante"><i class="bi bi-image"></i> Pago</a>`;
            }
        }
        
        let btnEliminar = `<button class="btn btn-sm btn-outline-danger fw-bold ms-1" onclick="eliminarNota(${n.id})" title="Anular"><i class="bi bi-trash"></i></button>`;

        html += `<tr><td class="fw-bold text-dark">${n.nro_nota.replace('NE-INV-','COT-INV-')}</td><td>${fechaArr[0]}</td><td class="fw-bold" style="color:#0B4C8C;">${n.cliente_nombre}</td><td><span class="badge bg-light text-dark border"><i class="bi bi-person"></i> ${n.vendedor_nombre} ${n.vendedor_apellido || ''}</span></td><td class="text-end fw-bold">${parseFloat(n.total_usd).toFixed(2)} $</td><td class="text-center"><span class="badge ${badgeColor}">${n.estado}</span></td><td class="text-center text-nowrap">${btnCotizacion}${btnNota}${btnAcciones}${btnEliminar}</td></tr>`;
    });
    tbody.html(html);
}

// LOGICA DE PAGO CON COMPROBANTE
function abrirModalPagarNota(id) {
    $('#pagar_id_nota').val(id); $('#pagar_metodo').val(''); $('#pagar_ref').val(''); $('#pagar_comprobante').val('');
    $('#modalPagarNota').modal('show');
}

$('#formPagarNota').submit(function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Procesando...');
    
    let formData = new FormData(this);
    formData.append('accion', 'pagar_nota_inventario');

    $.ajax({
        url: 'api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Confirmar Pago');
            if(res.success) {
                Swal.fire({title: 'Pago registrado', text: 'Ahora puedes imprimir la Nota de Entrega.', icon: 'success', timer: 2000, showConfirmButton: false});
                $('#modalPagarNota').modal('hide');
                cargarHistorialNotas();
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        error: function(xhr) { btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Confirmar Pago'); Swal.fire('Error del Servidor', xhr.responseText, 'error'); }
    });
});

function eliminarNota(id) {
    Swal.fire({ title: '¿Anular Documento?', text: "El stock regresará al estante físico.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, anular' }).then((result) => {
        if (result.isConfirmed) { $.post('api_inventario.php', { accion: 'delete_nota', id: id }, function(res) { if(res.success) { Swal.fire('Anulado', 'Documento borrado y stock devuelto.', 'success'); cargarHistorialNotas(); cargarStock(); } else { Swal.fire('Error', res.msg, 'error'); } }, 'json'); }
    });
}