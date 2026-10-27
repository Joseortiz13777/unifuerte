let prendasOrdenMemoria = [];
let artesOrdenMemoria = [];
let telasMemoria = [];
let temporizadorBusquedaOrdenes = null;
let solicitudListaOrdenes = null;
let secuenciaListaOrdenes = 0;

// Evaluación para deshabilitar e ignorar por completo las celdas que no corresponden
function evaluarPrendaSastreria(prendaStr) {
    let p = (prendaStr || '').toLowerCase();
    
    let esPantalon = p.includes('pantalón') || p.includes('pantalon') || p.includes('mono') || p.includes('short') || p.includes('falda');
    let esCamisa = p.includes('camis') || p.includes('chemis') || p.includes('chaqueta') || p.includes('jersey') || p.includes('filipina') || p.includes('bata') || p.includes('abrigo') || p.includes('chaleco');
    let esAmbos = p.includes('braga') || p.includes('conjunto') || (!esPantalon && !esCamisa);

    return {
        sup: (esCamisa || esAmbos),
        inf: (esPantalon || esAmbos)
    };
}

// Generador de celdas. Si no aplica, pone un guión.
function generarCeldasSastreria(prenda, pechoTxt) {
    let p = evaluarPrendaSastreria(prenda);
    let noAplica = `<td class="bg-light text-muted text-center align-middle dinamica-td">-</td>`;
    
    let c_hom = p.sup ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-hom text-center px-1" placeholder="0"></td>` : noAplica;
    let c_bus = p.sup ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-bus text-center px-1" placeholder="0" title="${pechoTxt}"></td>` : noAplica;
    let c_cin = `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-cin text-center px-1" placeholder="0"></td>`;
    let c_cad = `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-cad text-center px-1" placeholder="0"></td>`;
    let c_lcam = p.sup ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-lcam text-center px-1" placeholder="0"></td>` : noAplica;
    let c_lbra = p.sup ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-lbra text-center px-1" placeholder="0"></td>` : noAplica;
    
    let c_lpan = p.inf ? `<td class="dinamica-td" style="border-left: 2px solid #FF7518;"><input type="number" step="0.1" class="form-control form-control-sm sas-lpan text-center px-1" placeholder="0"></td>` : `<td style="border-left: 2px solid #FF7518;" class="bg-light text-muted text-center align-middle dinamica-td">-</td>`;
    let c_tiro = p.inf ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-tiro text-center px-1" placeholder="0"></td>` : noAplica;
    let c_mus = p.inf ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-mus text-center px-1" placeholder="0"></td>` : noAplica;
    let c_rod = p.inf ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-rod text-center px-1" placeholder="0"></td>` : noAplica;
    let c_tal = p.inf ? `<td class="dinamica-td"><input type="number" step="0.1" class="form-control form-control-sm sas-tal text-center px-1" placeholder="0"></td>` : noAplica;
    
    return c_hom + c_bus + c_cin + c_cad + c_lcam + c_lbra + c_lpan + c_tiro + c_mus + c_rod + c_tal;
}

$(document).ready(function() {
    $('#btnMenuOrdenes').click(function(e) {
        e.preventDefault();
        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');
        $('#sidebar').removeClass('active'); 
        $('#sidebarOverlay').removeClass('active');
        $('.vista-spa').hide();
        $('#vistaOrdenes').fadeIn();
        cargarListaOrdenes();
    });

    $(document).off('input.ordenesBusqueda', '#buscarOrdenesFabrica')
        .on('input.ordenesBusqueda', '#buscarOrdenesFabrica', function() {
        clearTimeout(temporizadorBusquedaOrdenes);
        temporizadorBusquedaOrdenes = setTimeout(cargarListaOrdenes, 250);
    });
    $(document).off('click.ordenesBusqueda', '#btnFiltrarOrdenesFabrica')
        .on('click.ordenesBusqueda', '#btnFiltrarOrdenesFabrica', function() {
            clearTimeout(temporizadorBusquedaOrdenes);
            cargarListaOrdenes();
        });

    if ($('#badge_pendientes_bell').length > 0 || $('#badge_pendientes_menu').length > 0) {
        chequearCotizacionesPendientes();
        setInterval(chequearCotizacionesPendientes, 30000); 
    }

    $('#check_asignar_tallas').change(function() {
        if($(this).is(':checked')) $('#seccion_asignacion_tallas').slideDown();
        else { $('#seccion_asignacion_tallas').slideUp(); $('#tbody_medidas_personales').empty(); }
    });

    $('#check_sastreria').change(function() {
        if($(this).is(':checked')) $('#seccion_sastreria').slideDown();
        else { $('#seccion_sastreria').slideUp(); $('#tbody_sastreria').empty(); }
    });

    window.actualizarTallaMedida = function(elem) {
        let gen = $(elem).val();
        let $selectTalla = $(elem).closest('tr').find('.med-talla');
        $selectTalla.html(obtenerOpcionesTalla(gen));
    };

    $(document).on('change', '.sas-prenda-select', function() {
        let prenda = $(this).val();
        let tr = $(this).closest('tr');
        tr.find('.dinamica-td').remove(); 
        let celdas = generarCeldasSastreria(prenda, 'Pecho');
        $(celdas).insertBefore(tr.find('.btn-eliminar-td'));
    });

    $('#formGuardarOrden').submit(function(e) {
        e.preventDefault();
        if($('#select_cotizacion_orden').val() === "") return Swal.fire('Error', 'Debe seleccionar una cotización base.', 'warning');

        let prendasConfiguradas = [];
        let validado = true;
        
        $('.fila-prenda-orden').each(function() {
            let tipo = $(this).find('.orden-tipo').val();
            let cant = $(this).find('.orden-cant').val();
            let tela = $(this).find('.orden-tela').val();
            
            const telaEspecificada = $(this).find('.orden-tela').attr('data-tela-especificada') === '1';
            if(!tela && !telaEspecificada) { validado = false; $(this).find('.orden-tela').addClass('border-danger'); } 
            else { $(this).find('.orden-tela').removeClass('border-danger'); prendasConfiguradas.push({ tipo: tipo, cantidad: cant, id_materia: tela }); }
        });

        if(!validado) return Swal.fire('Faltan Datos', 'Debe seleccionar la tela para todas las prendas.', 'error');

        let artesConfigurados = [];
        $('.fila-arte-orden').each(function() {
            artesConfigurados.push({
                elemento: $(this).find('.orden-arte-nombre').val(),
                cantidad: $(this).find('.orden-arte-cant').val(),
                vinculo: $(this).find('.orden-arte-vinculo').val(),
                ubicacion: $(this).find('.orden-arte-ubi').val()
            });
        });

        let medidasConfiguradas = [];
        if($('#check_asignar_tallas').is(':checked')) {
            let faltaTalla = false;
            $('.fila-medida-personal').each(function() {
                let tallaVal = $(this).find('.med-talla').val();
                if (!tallaVal || tallaVal === '') { faltaTalla = true; $(this).find('.med-talla').addClass('border-danger'); }
                else { $(this).find('.med-talla').removeClass('border-danger'); }

                medidasConfiguradas.push({
                    tipo_prenda: $(this).find('.med-prenda').val(),
                    nombre: $(this).find('.med-nombre').val().trim(),
                    genero: $(this).find('.med-genero').val(),
                    talla: tallaVal,
                    obs: $(this).find('.med-obs').val(),
                    cantidad: parseInt($(this).find('.med-cantidad').val(), 10) || 1,
                    tela: $(this).find('.med-tela').val() || '',
                    medidas_personales: (function($row) {
                        const medidas = {};
                        $row.find('.med-medidas-personales').each(function() {
                            medidas[$(this).data('medida')] = $(this).val() || 0;
                        });
                        return medidas;
                    })($(this))
                });
            });
            if(faltaTalla) return Swal.fire('Faltan Datos', 'Debe especificar la TALLA en todas las filas agregadas.', 'error');
        }

        let sastreriaConfigurada = [];
        if($('#check_sastreria').is(':checked')) {
            $('.fila-sastreria').each(function() {
                sastreriaConfigurada.push({
                    tipo_prenda: $(this).find('.sas-prenda, .sas-prenda-select').val(),
                    nombre: $(this).find('.sas-nombre').val().trim(),
                    hombro: $(this).find('.sas-hom').val() || 0,
                    busto: $(this).find('.sas-bus').val() || 0,
                    cintura: $(this).find('.sas-cin').val() || 0,
                    cadera: $(this).find('.sas-cad').val() || 0,
                    l_camisa: $(this).find('.sas-lcam').val() || 0,
                    l_brazo: $(this).find('.sas-lbra').val() || 0,
                    l_pantalon: $(this).find('.sas-lpan').val() || 0,
                    tiro: $(this).find('.sas-tiro').val() || 0,
                    muslo: $(this).find('.sas-mus').val() || 0,
                    rodilla: $(this).find('.sas-rod').val() || 0,
                    talon: $(this).find('.sas-tal').val() || 0
                });
            });
        }

        $('#btnGuardarOrdenFinal').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        let formData = new FormData();
        formData.append('accion', 'guardar_orden');
        formData.append('id_cotizacion', $('#select_cotizacion_orden').val());
        formData.append('id_cliente', $('#id_cliente_orden').val());
        formData.append('fecha_entrega', $('#orden_fecha_entrega').val());
        formData.append('es_express', $('#orden_express').is(':checked') ? 1 : 0);
        formData.append('acepta_parciales', $('#orden_parciales').is(':checked') ? 1 : 0);
        formData.append('descripcion_global', $('#orden_notas_globales').val());
        formData.append('prendas', JSON.stringify(prendasConfiguradas));
        formData.append('artes', JSON.stringify(artesConfigurados));
        formData.append('medidas', JSON.stringify(medidasConfiguradas));
        formData.append('sastreria', JSON.stringify(sastreriaConfigurada));
        
        let archivosFotos = $('#orden_foto_referencial')[0].files;
        if (archivosFotos && archivosFotos.length > 0) {
            for (let i = 0; i < archivosFotos.length; i++) {
                formData.append('orden_foto[]', archivosFotos[i]);
            }
        }

        $.ajax({
            url: 'api/api_ordenes.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire('¡Éxito!', 'Orden de Fábrica Generada', 'success').then(() => {
                        $('#modalNuevaOrden').modal('hide');
                        cargarListaOrdenes();
                        chequearCotizacionesPendientes(); 
                        window.open('imprimir_orden.php?id=' + res.id_orden, '_blank');
                    });
                } else { Swal.fire('Error', res.msg, 'error'); }
            },
            complete: function() {
                $('#btnGuardarOrdenFinal').prop('disabled', false).html('<i class="bi bi-save"></i> Emitir Orden de Fábrica');
            }
        });
    });
});

function cargarListaOrdenes() {
    const secuenciaActual = ++secuenciaListaOrdenes;
    if (solicitudListaOrdenes) solicitudListaOrdenes.abort();
    $('#tablaOrdenesBody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
    solicitudListaOrdenes = $.get('api/api_ordenes.php', {
        accion: 'listar_ordenes',
        busqueda: $('#buscarOrdenesFabrica').val() || ''
    }, function(res) {
        if (secuenciaActual !== secuenciaListaOrdenes) return;
        if(res.success) {
            let html = '';
            res.data.forEach(o => {
                let badgeExp = o.es_express == 1 ? '<span class="badge bg-warning text-dark ms-1">Express</span>' : '';
                html += `<tr>
                    <td class="fw-bold text-primary">${o.nro_orden_fabrica}</td>
                    <td>${escaparHtmlOrdenes(o.nro_cotizacion || '-')}</td>
                    <td>${o.fecha_emision}</td>
                    <td class="fw-bold">${o.fecha_entrega}</td>
                    <td>${escaparHtmlOrdenes(o.cliente)} ${badgeExp}</td>
                    <td class="text-center">
                        <a href="imprimir_orden.php?id=${o.ID}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-printer"></i> Imprimir</a>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="eliminarOrden(${o.ID})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            $('#tablaOrdenesBody').html(html || '<tr><td colspan="6" class="text-center">No hay órdenes registradas para esa búsqueda.</td></tr>');
        } else {
            $('#tablaOrdenesBody').html(`<tr><td colspan="6" class="text-center text-danger">${escaparHtmlOrdenes(res.msg || 'No se pudieron cargar las órdenes.')}</td></tr>`);
        }
    }, 'json').fail(function(xhr, estado) {
        if (estado === 'abort' || secuenciaActual !== secuenciaListaOrdenes) return;
        const mensaje = xhr.responseJSON && xhr.responseJSON.msg
            ? xhr.responseJSON.msg
            : 'Error de comunicación al buscar órdenes.';
        $('#tablaOrdenesBody').html(`<tr><td colspan="6" class="text-center text-danger">${escaparHtmlOrdenes(mensaje)}</td></tr>`);
    }).always(function() {
        if (secuenciaActual === secuenciaListaOrdenes) solicitudListaOrdenes = null;
    });
}

function escaparHtmlOrdenes(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function abrirModalNuevaOrden() {
    $('#formGuardarOrden')[0].reset();
    $('#tbody_prendas_orden').empty();
    $('#tbody_artes_orden').empty();
    $('#tbody_medidas_personales').empty();
    $('#tbody_sastreria').empty();
    $('#seccion_asignacion_tallas').hide();
    $('#seccion_sastreria').hide();
    $('#seccion_artes_posiciones').hide();
    
    $.get('api/api_ordenes.php', { accion: 'listar_cotizaciones_aptas' }, function(res) {
        let opciones = '<option value="">Seleccione una Cotización...</option>';
        res.data.forEach(c => { opciones += `<option value="${c.ID}">${c.nro_cotizacion} - ${c.cliente}</option>`; });
        $('#select_cotizacion_orden').html(opciones);
        new bootstrap.Modal(document.getElementById('modalNuevaOrden')).show();
    }, 'json');
}

window.cargarDatosCotizacionOrden = function(id_cot) {
    if(!id_cot) { $('#tbody_prendas_orden').empty(); $('#tbody_artes_orden').empty(); return; }
    $('#tbody_prendas_orden').html('<tr><td colspan="3" class="text-center">Cargando...</td></tr>');
    
    $.get('api/api_ordenes.php', { accion: 'obtener_datos_cotizacion', id_cotizacion: id_cot }, function(res) {
        if(res.success) {
            $('#id_cliente_orden').val(res.id_cliente);
            telasMemoria = res.telas; 
            
            prendasOrdenMemoria = [];
            artesOrdenMemoria = [];

            res.detalles.forEach(d => {
                let catAsumida = (d.detalle_diseno && d.detalle_diseno.includes('Ubicación:')) ? 'Arte Final' : (d.categoria || 'Prenda');
                if (catAsumida === 'Arte Final') artesOrdenMemoria.push(d);
                else prendasOrdenMemoria.push(d);
            });

            let htmlPrendas = '';
            prendasOrdenMemoria.forEach(p => {
                let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
                
                let specs = [];
                try {
                    const parsed = JSON.parse(p.especificaciones_tallas || '[]');
                    if (Array.isArray(parsed)) specs = parsed;
                } catch(e) {}

                const normalizarTela = valor => String(valor || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
                const telasEspecificadas = [...new Set(specs.map(s => String(s.tela || '').trim()).filter(Boolean))];
                let opcionesTela = '';

                if (telasEspecificadas.length === 1) {
                    const telaCotizada = telasEspecificadas[0];
                    const telaNormalizada = normalizarTela(telaCotizada);
                    const materiaCoincidente = telasMemoria.find(t => {
                        const nombreColor = `${t.nombre || ''} (${t.color || ''})`;
                        return normalizarTela(nombreColor) === telaNormalizada ||
                            normalizarTela(t.nombre) === telaNormalizada;
                    });
                    if (materiaCoincidente) {
                        opcionesTela = `<option value="${materiaCoincidente.ID}" selected>${$('<div>').text(telaCotizada).html()}</option>`;
                    } else {
                        opcionesTela = `<option value="0" selected>${$('<div>').text(telaCotizada).html()}</option>`;
                    }
                } else if (telasEspecificadas.length > 1) {
                    opcionesTela = '<option value="0" selected>Varias telas (ver especificaciones por talla)</option>';
                } else {
                    opcionesTela = '<option value="">Seleccionar Tela...</option>';
                }

                if (telasEspecificadas.length === 0) {
                    telasMemoria.forEach(t => { 
                        let nombreTelaStr = t.nombre + ' (' + t.color + ')';
                        opcionesTela += `<option value="${t.ID}">${nombreTelaStr}</option>`; 
                    });
                }
                const telaBloqueada = telasEspecificadas.length > 0;

                htmlPrendas += `<tr class="fila-prenda-orden">
                    <td><input type="text" class="form-control form-control-sm fw-bold orden-tipo bg-light" value="${nombreCompleto}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm text-center orden-cant bg-light" value="${p.cantidad}" readonly></td>
                    <td><select class="form-select form-select-sm orden-tela border-primary" ${telaBloqueada ? 'disabled' : ''} data-tela-especificada="${telaBloqueada ? '1' : '0'}">${opcionesTela}</select></td>
                </tr>`;
            });
            $('#tbody_prendas_orden').html(htmlPrendas || '<tr><td colspan="3" class="text-center text-muted">No hay prendas físicas</td></tr>');

            let htmlArtes = '';
            if (artesOrdenMemoria.length > 0) {
                $('#seccion_artes_posiciones').show();
                artesOrdenMemoria.forEach(a => {
                    let vinculoGuardado = '';
                    let ubicacionGuardada = '';
                    if (a.detalle_diseno.includes('[Para: ')) {
                        let partes = a.detalle_diseno.split('] - Ubicación: ');
                        vinculoGuardado = partes[0].replace('[Para: ', '');
                        ubicacionGuardada = partes[1] || '';
                    }

                    let opcionesVinculoArte = '';
                    prendasOrdenMemoria.forEach(p => {
                        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
                        let selected = (nombreCompleto === vinculoGuardado) ? 'selected' : '';
                        opcionesVinculoArte += `<option value="${nombreCompleto}" ${selected}>${nombreCompleto}</option>`;
                    });

                    htmlArtes += `<tr class="fila-arte-orden">
                        <td><input type="text" class="form-control form-control-sm fw-bold orden-arte-nombre text-danger bg-light" value="${a.tipo_prenda}" readonly></td>
                        <td><input type="number" class="form-control form-control-sm text-center orden-arte-cant bg-light" value="${a.cantidad}" readonly></td>
                        <td><select class="form-select form-select-sm orden-arte-vinculo border-danger">${opcionesVinculoArte}</select></td>
                        <td><input type="text" class="form-control form-control-sm orden-arte-ubi border-danger fw-bold" value="${ubicacionGuardada}" placeholder="Ej: Espalda Alta"></td>
                    </tr>`;
                });
                $('#tbody_artes_orden').html(htmlArtes);
            } else {
                $('#seccion_artes_posiciones').hide();
            }

            const tieneEspecificacionesGuardadas = prendasOrdenMemoria.some(p => {
                try {
                    const specs = JSON.parse(p.especificaciones_tallas || '[]');
                    return Array.isArray(specs) && specs.length > 0;
                } catch(e) {
                    return false;
                }
            });
            if (tieneEspecificacionesGuardadas) {
                $('#check_asignar_tallas').prop('checked', true).trigger('change');
            }
        }
    }, 'json');
}

window.autogenerarFilasTallas = function() {
    if(prendasOrdenMemoria.length === 0) return Swal.fire('Error', 'Seleccione una cotización base primero', 'warning');
    $('#tbody_medidas_personales').empty();
    
    let opcionesPrenda = '';
    prendasOrdenMemoria.forEach(p => {
        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
        opcionesPrenda += `<option value="${nombreCompleto}">${nombreCompleto}</option>`;
    });

    prendasOrdenMemoria.forEach(p => {
        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
        
        let specs = [];
        try { specs = JSON.parse(p.especificaciones_tallas); } catch(e) {}
        
        if (specs && specs.length > 0) {
            specs.forEach(s => {
                let gen = s.genero || p.genero || 'U';
                let opcionesTallas = obtenerOpcionesTalla(gen);
                opcionesTallas = opcionesTallas.replace(`value="${s.talla}"`, `value="${s.talla}" selected`);
                const medidasPersonales = s.medidas_personales || {};
                const camposMedidas = ['hombro', 'busto_torax', 'cintura', 'cadera', 'largo_camisa', 'largo_brazo', 'largo_pantalon', 'tiro_total', 'ancho_muslo', 'ancho_rodilla', 'ancho_talon'];
                const inputsMedidas = [
                    `<input type="hidden" class="med-cantidad" value="${Number(s.cantidad) > 0 ? Number(s.cantidad) : 1}">`,
                    `<input type="hidden" class="med-tela" value="${$('<div>').text(s.tela || '').html()}">`,
                    ...camposMedidas.map(campo => `<input type="hidden" class="med-medidas-personales" data-medida="${campo}" value="${Number(medidasPersonales[campo]) > 0 ? Number(medidasPersonales[campo]) : 0}">`)
                ].join('');
                
                let html = `<tr class="fila-medida-personal">
                    <td><select class="form-select form-select-sm med-prenda bg-light" style="width: 160px;">${opcionesPrenda}</select>${inputsMedidas}</td>
                    <td><input type="text" class="form-control form-control-sm med-nombre border-primary fw-bold" value="${s.nombre || ''}" placeholder="S/N (Recomendado)"></td>
                    <td>
                        <select class="form-select form-select-sm med-genero bg-light" onchange="window.actualizarTallaMedida(this)">
                            <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
                            <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
                            <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
                            <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>
                            <option value="U" ${gen==='U'?'selected':''}>U</option>
                        </select>
                    </td>
                    <td><select class="form-select form-select-sm med-talla border-primary fw-bold" required>${opcionesTallas}</select></td>
                    <td><input type="text" class="form-control form-control-sm med-obs" value="${s.obs || ''}" placeholder="Ej: Con bolsillo"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                </tr>`;
                $('#tbody_medidas_personales').append(html);
                $('#tbody_medidas_personales tr:last').find('.med-prenda').val(nombreCompleto);
            });
        } else {
            let cant = parseInt(p.cantidad);
            let gen = p.genero !== 'No especificado' ? p.genero : 'Dama';
            let opcionesTallas = obtenerOpcionesTalla(gen);
            
            for(let i=0; i<cant; i++) {
                let html = `<tr class="fila-medida-personal">
                    <td><select class="form-select form-select-sm med-prenda bg-light" style="width: 160px;">${opcionesPrenda}</select></td>
                    <td><input type="text" class="form-control form-control-sm med-nombre border-primary fw-bold" placeholder="S/N (Recomendado)"></td>
                    <td>
                        <select class="form-select form-select-sm med-genero bg-light" onchange="window.actualizarTallaMedida(this)">
                            <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
                            <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
                            <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
                            <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>
                            <option value="U" ${gen==='U'?'selected':''}>U</option>
                        </select>
                    </td>
                    <td><select class="form-select form-select-sm med-talla border-primary fw-bold" required>${opcionesTallas}</select></td>
                    <td><input type="text" class="form-control form-control-sm med-obs" placeholder="Ej: Con bolsillo"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                </tr>`;
                $('#tbody_medidas_personales').append(html);
                $('#tbody_medidas_personales tr:last').find('.med-prenda').val(nombreCompleto);
            }
        }
    });
    Swal.fire('¡Filas Generadas!', 'Se han cargado las tallas, nombres y telas desde la cotización.', 'success');
}

window.agregarFilaMedida = function() {
    if(prendasOrdenMemoria.length === 0) return Swal.fire('Error', 'Seleccione una cotización', 'warning');
    let opcionesPrenda = '';
    prendasOrdenMemoria.forEach(p => {
        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
        opcionesPrenda += `<option value="${nombreCompleto}">${nombreCompleto}</option>`;
    });

    let html = `<tr class="fila-medida-personal">
        <td><select class="form-select form-select-sm med-prenda bg-light">${opcionesPrenda}</select></td>
        <td><input type="text" class="form-control form-control-sm med-nombre border-primary fw-bold" placeholder="Nombre"></td>
        <td><select class="form-select form-select-sm med-genero" onchange="window.actualizarTallaMedida(this)"><option value="Dama">Dama</option><option value="Caballero">Caballero</option><option value="Niña">Niña</option><option value="Niño">Niño</option></select></td>
        <td><select class="form-select form-select-sm med-talla border-primary fw-bold" required>${obtenerOpcionesTalla('Dama')}</select></td>
        <td><input type="text" class="form-control form-control-sm med-obs" placeholder="Ej: Con bolsillo"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
    </tr>`;
    $('#tbody_medidas_personales').append(html);
}

window.autogenerarSastreria = function() {
    let tieneNombres = false;
    $('#tbody_sastreria').empty();
    
    $('.fila-medida-personal').each(function() {
        let prenda = $(this).find('.med-prenda').val() || '';
        let nombre = $(this).find('.med-nombre').val().trim();
        let genero = $(this).find('.med-genero').val();
        
        if (nombre !== '') {
            tieneNombres = true;
            let textoPecho = (genero === 'Caballero' || genero === 'Niño') ? 'Tórax' : 'Busto';
            
            let html = `<tr class="fila-sastreria">
                <td><input type="text" class="form-control form-control-sm sas-prenda bg-light" value="${prenda}" readonly></td>
                <td><input type="text" class="form-control form-control-sm sas-nombre border-primary fw-bold" value="${nombre}" readonly></td>
                ${generarCeldasSastreria(prenda, textoPecho)}
                <td class="text-center btn-eliminar-td"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
            </tr>`;
            $('#tbody_sastreria').append(html);
        }
    });
    
    if(!tieneNombres) {
        Swal.fire('Atención', 'Debe colocar nombres a las prendas en el Paso 4 para poder cargar automáticamente las filas de sastrería.', 'warning');
    } else {
        Swal.fire('¡Éxito!', 'Listado de personal cargado. Se bloquearon y ocultaron las casillas irrelevantes según el tipo de prenda.', 'success');
    }
}

window.agregarFilaSastreria = function() {
    if(prendasOrdenMemoria.length === 0) return Swal.fire('Error', 'Seleccione una cotización', 'warning');
    let opcionesPrenda = '';
    prendasOrdenMemoria.forEach(p => {
        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
        opcionesPrenda += `<option value="${nombreCompleto}">${nombreCompleto}</option>`;
    });

    let html = `<tr class="fila-sastreria">
        <td><select class="form-select form-select-sm sas-prenda-select bg-light" style="width: 140px;">${opcionesPrenda}</select></td>
        <td><input type="text" class="form-control form-control-sm sas-nombre border-primary fw-bold" placeholder="Nombre exacto" required></td>
        ${generarCeldasSastreria('', 'Pecho')}
        <td class="text-center btn-eliminar-td"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
    </tr>`;
    $('#tbody_sastreria').append(html);
    $('#tbody_sastreria tr:last').find('.sas-prenda-select').trigger('change');
}

window.eliminarOrden = function(id) {
    Swal.fire({ title: '¿Eliminar Orden?', text: "Se borrarán también las medidas personales y fotos.", icon: 'warning', showCancelButton: true }).then((result) => {
        if(result.isConfirmed) {
            $.post('api/api_ordenes.php', { accion: 'eliminar', id: id }, function(res) {
                if(res.success) {
                    cargarListaOrdenes(); 
                    chequearCotizacionesPendientes(); 
                } else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
}