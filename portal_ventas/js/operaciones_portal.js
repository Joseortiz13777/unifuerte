// Variable global para almacenar las cotizaciones que el Master desbloquee manualmente
window.unlockedRows = window.unlockedRows || [];
window.ES_MASTER_CONFIRMADO_API = false;

window.esUsuarioMaster = function() {
    if (window.ES_MASTER_CONFIRMADO_API === true) return true;
    if (document.getElementById('usuario_es_master')?.value === '1') return true;
    if (typeof ROL_USUARIO_ACTUAL !== 'undefined' && String(ROL_USUARIO_ACTUAL).toLowerCase().trim() === 'master') return true;
    const textoSidebar = ($('.sidebar, #sidebar, .nav-profile, body').text() || '');
    if (/\bMASTER\b/.test(textoSidebar) && document.getElementById('usuario_es_master')?.value !== '0') return true;
    return false;
};

const ES_USUARIO_MASTER = window.esUsuarioMaster();
const PORCENTAJE_PAGO_LOGISTICA = ES_USUARIO_MASTER ? 50 : 60;
window.modoCambioCliente = false; // Flag para saber si estamos cambiando el cliente de una edición
window.archivosOrdenEditMemoria = []; // Memoria para previsualizar fotos nuevas al editar
let temporizadorFiltroOrdenesFabrica = null;
let solicitudFiltroOrdenesFabrica = null;
let secuenciaFiltroOrdenesFabrica = 0;

// ==============================================================================
// FUNCIÓN MAESTRA LIMPIADORA PARA ARREGLAR LA BASURA `ert{}` Y SALTOS DE LÍNEA
// ==============================================================================
window.limpiarSeparadoresSucios = function(texto) {
    if (!texto) return [];
    let textoLimpio = String(texto).replace(/ert\{\}/g, '|').replace(/<br\s*\/?>/gi, '|').replace(/[\r\n]+/g, '|');
    return textoLimpio.split('|').map(s => s.trim()).filter(s => s !== '');
};

window.extraerCodigoUnicoCotizacion = function(nroCot, fallbackId) {
    const matches = String(nroCot || '').match(/(\d+)\s*$/);
    return matches ? matches[1] : String(fallbackId || '').padStart(5, '0');
};

window.totalRetencionesPagosUsd = function(retencionesJson, tasa) {
    let retenciones;
    try {
        retenciones = JSON.parse(retencionesJson || '[]');
    } catch (error) {
        console.error('No se pudieron leer las retenciones de la cotización.', error);
        return 0;
    }
    if (!Array.isArray(retenciones)) {
        console.error('El historial de retenciones de la cotización no tiene un formato válido.');
        return 0;
    }
    const tasaFiscal = Number(tasa);
    const totalBs = retenciones.reduce((total, item) => {
        if (!item || typeof item !== 'object') {
            console.error('Se encontró un registro de retención incompleto.');
            return total;
        }
        const tasaItem = tasaFiscal > 0 ? tasaFiscal : Number(item.tasa_bcv);
        if (!Number.isFinite(tasaItem) || tasaItem <= 0) {
            console.error('No se encontró una tasa BCV válida para una retención.');
            return total;
        }
        const montoBs = ['iva_bs', 'islr_bs', 'municipal_bs']
            .reduce((suma, campo) => suma + Math.max(0, Number(item[campo]) || 0), 0);
        return total + (montoBs / tasaItem);
    }, 0);
    return Math.round(totalBs * 100) / 100;
};

// ==============================================================================
// CORRECCIÓN: INYECCIÓN FORZADA DEL TIPO DE DOCUMENTO EN TODOS LOS FORMULARIOS
// ==============================================================================
window.inyectarTipoEnFormularios = function(tipo) {
    $('form').each(function() {
        if ($(this).find('input[name="tipo_cotizacion"]').length === 0) {
            $(this).append(`<input type="hidden" name="tipo_cotizacion" value="${tipo}">`);
        } else {
            $(this).find('input[name="tipo_cotizacion"]').val(tipo);
        }
    });
};

$(document).ready(function() {
        let clientesFiltroHistorial = [];

        function renderResultadosFiltroClienteHistorial(termino) {
            const $resultados = $('#resultados_filtro_cliente_hist');
            if (!$resultados.length) return;
            const busqueda = String(termino || '').trim().toLocaleLowerCase();
            const coincidencias = clientesFiltroHistorial.filter(cliente =>
                String(cliente.nombre || '').toLocaleLowerCase().includes(busqueda)
            ).slice(0, 20);

            $resultados.empty();
            if (!coincidencias.length) {
                $resultados.html('<div class="list-group-item small text-muted">No se encontraron clientes.</div>');
            } else {
                coincidencias.forEach(cliente => {
                    $('<button>', {
                        type: 'button',
                        class: 'list-group-item list-group-item-action btn-seleccionar-cliente-hist',
                        text: cliente.nombre
                    }).attr('data-id', cliente.ID).appendTo($resultados);
                });
            }
            $resultados.removeClass('d-none');
        }

        $(document).on('focus input', '#filtro_cliente_hist_busqueda', function() {
            renderResultadosFiltroClienteHistorial($(this).val());
        });
        $(document).on('click', '.btn-seleccionar-cliente-hist', function() {
            const id = String($(this).attr('data-id'));
            const cliente = clientesFiltroHistorial.find(item => String(item.ID) === id);
            if (!cliente) return;
            $('#filtro_cliente_hist').val(id);
            $('#filtro_cliente_hist_busqueda').val(cliente.nombre);
            $('#resultados_filtro_cliente_hist').addClass('d-none').empty();
        });
        $(document).on('keydown', '#filtro_cliente_hist_busqueda', function(event) {
            if (event.key === 'Escape') {
                $('#resultados_filtro_cliente_hist').addClass('d-none').empty();
            } else if (event.key === 'Enter') {
                const $first = $('#resultados_filtro_cliente_hist .btn-seleccionar-cliente-hist').first();
                if ($first.length) {
                    event.preventDefault();
                    $first.trigger('click');
                }
            }
        });
        $(document).on('input', '#filtro_cliente_hist_busqueda', function() {
            $('#filtro_cliente_hist').val('');
        });
        $(document).on('click', function(event) {
            if (!$(event.target).closest('#filtro_cliente_hist_busqueda, #resultados_filtro_cliente_hist').length) {
                $('#resultados_filtro_cliente_hist').addClass('d-none');
            }
        });

        // Inyectar campo oculto para el tipo de cotización si no existe
        if ($('#id_edit_tipo_cotizacion').length === 0) {
            $('body').append('<input type="hidden" id="id_edit_tipo_cotizacion" value="ESTANDAR">');
        }
        
        // CORRECCIÓN: PREVENIR PANTALLA GRIS EN MUESTRAS FÍSICAS Y GUIAR AL USUARIO
        $(document).on('click', '#btnMenuMuestras', function(e) {
            e.preventDefault();
            e.stopPropagation();
            Swal.fire({
                icon: 'info',
                title: 'Módulo de Muestras Físicas',
                text: 'Para solicitar una Muestra Física a costo $0.00, busca al cliente en el Directorio, presiona "Cotizar" y selecciona la opción "Muestra Física".',
                confirmButtonColor: '#0B4C8C',
                confirmButtonText: '<i class="bi bi-people-fill"></i> Ir al Directorio'
            }).then((res) => {
                if (res.isConfirmed) $('#btnMenuClientes').click();
            });
        });

        // SUGERIR 20 DÍAS EN EL MODAL DE NUEVA ORDEN PERO PERMITIENDO AJUSTARLA SI ES NECESARIO
        $(document).on('shown.bs.modal', '#modalNuevaOrden', function () {
            if (!$('#orden_fecha_entrega').val()) {
                let d = new Date();
                d.setDate(d.getDate() + 20);
                let fecha20Dias = d.toISOString().split('T')[0];
                $('#orden_fecha_entrega').val(fecha20Dias);
            }
            $('#orden_fecha_entrega').prop('readonly', false).removeClass('bg-light').addClass('text-danger fw-bold');
        });

        // EVENTO PARA PAUSAR COTIZACION DESDE EL HISTORIAL
        $(document).on('click', '.btn-pausar-cot', function() {
            let btn = $(this);
            let id = btn.data('id');
            let estado_actual = btn.data('estado');
            let estado_nuevo = estado_actual == 1 ? 0 : 1;
            let accionTexto = estado_nuevo == 1 ? 'Congelar' : 'Descongelar';
            let textoInfo = estado_nuevo == 1 ? 'La cotización se marcará como pausada y se le pondrá un icono de hielo.' : 'La cotización volverá a su estado normal activo.';

            Swal.fire({
                title: '¿' + accionTexto + ' Cotización?',
                text: textoInfo,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: estado_nuevo == 1 ? '#0dcaf0' : '#dc3545',
                confirmButtonText: 'Sí, ' + accionTexto,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.prop('disabled', true);
                    $.post('api/api_cotizaciones.php', { accion: 'pausar_cotizacion', id_cotizacion: id, estado_nuevo: estado_nuevo }, function(res) {
                        if(res.success) {
                            window.cargarHistorial(); 
                            Swal.fire({icon: 'success', title: 'Actualizado', timer: 1000, showConfirmButton: false});
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                            btn.prop('disabled', false);
                        }
                    }, 'json').fail(function() {
                        Swal.fire('Error', 'Fallo de conexión', 'error');
                        btn.prop('disabled', false);
                    });
                }
            });
        });

        // ==============================================================================
        // CORRECCIÓN: FILTRO AJAX A PRUEBA DE BALAS PARA GARANTIZAR EL TIPO (MUESTRA)
        // ==============================================================================
        $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
            let urlTarget = options.url || "";
            let dataStr = typeof options.data === 'string' ? options.data : "";
            
            if (urlTarget.includes('guardar_cotizacion') || dataStr.includes('accion=guardar')) {
                let tipo = $('#id_edit_tipo_cotizacion').val() || 'ESTANDAR';
                
                if (options.data instanceof FormData) {
                    if (!options.data.has('tipo_cotizacion')) {
                        options.data.append('tipo_cotizacion', tipo);
                    } else {
                        options.data.set('tipo_cotizacion', tipo);
                    }
                } else if (typeof options.data === 'string') {
                    if (!options.data.includes('tipo_cotizacion=')) {
                        options.data += '&tipo_cotizacion=' + encodeURIComponent(tipo);
                    } else {
                        options.data = options.data.replace(/(tipo_cotizacion=)[^\&]+/, '$1' + encodeURIComponent(tipo));
                    }
                }
            }
        });

        $(document).on('change', '#pago_banco', function() {
            let banco = $(this).val();
            let bancos_divisas = ['Binance', 'Zelle', 'Banesco Panamá', 'Divisas Efectivo'];
            let inputTasa = $('#pago_tasa_bcv');
            if (bancos_divisas.includes(banco)) {
                inputTasa.attr('type', 'text');
                inputTasa.val('No aplica');
                inputTasa.prop('readonly', true).css('background-color', '#e9ecef');
            } else {
                if (inputTasa.attr('type') === 'text') {
                    inputTasa.attr('type', 'number');
                    inputTasa.val('');
                    inputTasa.prop('readonly', false).css('background-color', '#fff');
                }
            }
        });
        
        $(document).on('hidden.bs.modal', '#modalPagoCotizacion', function () {
            let inputTasa = $('#pago_tasa_bcv');
            inputTasa.attr('type', 'number').val('').prop('readonly', false).css('background-color', '#fff');
            $('#pago_banco').val('');
            $('#pago_valor_usd').removeClass('is-invalid border-danger');
        });

        $(document).on('change', '#com_banco_emi', function() {
            let banco = $(this).val();
            let bancos_divisas = ['Binance', 'Zelle', 'Banesco Panamá', 'Divisas Efectivo'];
            let inputTasa = $('#com_tasa');
            let inputBs = $('#com_monto_bs'); 
            if (bancos_divisas.includes(banco)) {
                inputTasa.attr('type', 'text');
                inputTasa.val('No aplica');
                inputTasa.prop('readonly', true).css('background-color', '#e9ecef');
                inputBs.val('No aplica');
            } else {
                if (inputTasa.attr('type') === 'text') {
                    inputTasa.attr('type', 'number');
                    inputTasa.val('');
                    inputTasa.prop('readonly', false).css('background-color', '#fff');
                    if(typeof window.calcularComisionBsGlobal === 'function') window.calcularComisionBsGlobal(); 
                }
            }
        });

        $(document).on('hidden.bs.modal', '#modalComision', function () {
            let inputTasa = $('#com_tasa');
            inputTasa.attr('type', 'number').val('').prop('readonly', false).css('background-color', '#fff');
            $('#com_banco_emi').val('');
        });

        let originalAjaxSubmit = $.ajax;
        $.ajax = function(options) {
            if (options.url && options.url.includes('api_cotizaciones') && options.data && typeof options.data === 'string' && options.data.includes('accion=guardar')) {
                let originalSuccess = options.success;
                options.success = function(res) {
                    let idCot = res.id_cotizacion || res.id;
                    let files = $('#lic_anexos_files')[0]?.files;
                    if (idCot && files && files.length > 0 && $('#vistaCotizadorLicitaciones').is(':visible')) {
                        let fd = new FormData();
                        fd.append('accion_fantasma_subir_anexos', '1');
                        fd.append('id_cotizacion', idCot);
                        for(let i=0; i<files.length; i++) fd.append('anexos_licitacion[]', files[i]);
                        $.ajax({ url: window.location.href, type: 'POST', data: fd, processData: false, contentType: false, async: false });
                        $('#lic_anexos_files').val(''); 
                    }
                    if (originalSuccess) originalSuccess.apply(this, arguments);
                };
            }
            return originalAjaxSubmit.apply(this, arguments);
        };

        // ==============================================================================
        // OPCIONES DE CREACIÓN DESDE EL DIRECTORIO DE CLIENTES
        // ==============================================================================
        $(document).on('click', '.btn-seleccionar-cli', function() {
            let idCli = $(this).data('id');
            let nomCli = $(this).data('nombre');
            
            if (window.modoCambioCliente) {
                $('#idClienteSeleccionado').val(idCli);
                
                let tipoActual = $('#id_edit_tipo_cotizacion').val() || 'ESTANDAR'; 
                let badgeExtra = tipoActual === 'MUESTRA' ? ' <span class="badge bg-info text-dark ms-2"><i class="bi bi-box2-heart"></i> MUESTRA FÍSICA</span>' : '';
                
                $('#lblClienteSeleccionado').html(`${nomCli} ${badgeExtra} <button type="button" class="btn btn-sm btn-outline-dark ms-2 btn-cambiar-cliente btn-bloqueable" title="Cambiar Cliente"><i class="bi bi-arrow-repeat"></i> Cambiar</button>`);
                window.modoCambioCliente = false;
                
                if (tipoActual === 'LICITACION') { 
                    window.inyectarTipoEnFormularios('LICITACION');
                    $('#btnMenuCotizadorLicitaciones').click(); 
                } 
                else if (tipoActual === 'MUESTRA') { 
                    window.inyectarTipoEnFormularios('MUESTRA');
                    $('#btnMenuCotizador').click(); 
                } 
                else { 
                    window.inyectarTipoEnFormularios('ESTANDAR');
                    $('#btnMenuCotizador').click(); 
                }
                
                Swal.fire({icon: 'success', title: 'Cliente Actualizado', text: 'Has cambiado el cliente de esta edición exitosamente.', timer: 2000, showConfirmButton: false});
                return;
            }

            let tieneCotNormal = JS_tieneVector(4);
            let tieneCotLicitacion = JS_tieneVector(26);
            let tieneMuestra = JS_tieneVector(52); 

            let opcionesHTML = '<div class="d-flex flex-column gap-2 mt-3">';
            if (tieneCotNormal) opcionesHTML += `<button id="btnOptNormal" class="btn text-white py-3 fw-bold fs-5 shadow-sm" style="background-color: #0B4C8C; border: none;"><i class="bi bi-shop me-2"></i> Normal (Catálogo)</button>`;
            if (tieneCotLicitacion) opcionesHTML += `<button id="btnOptLicitacion" class="btn text-white py-3 fw-bold fs-5 shadow-sm" style="background-color: #FF7518; border: none;"><i class="bi bi-briefcase me-2"></i> Especial (Licitación)</button>`;
            if (tieneMuestra) opcionesHTML += `<button id="btnOptMuestra" class="btn text-white py-3 fw-bold fs-5 shadow-sm" style="background-color: #17a2b8; border: none;"><i class="bi bi-box2-heart me-2"></i> Muestra Física</button>`;
            opcionesHTML += '</div>';

            if (tieneCotNormal || tieneCotLicitacion || tieneMuestra) {
                Swal.fire({
                    title: '¿Qué deseas generar?',
                    html: opcionesHTML,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar'
                });

                $(document).off('click', '#btnOptNormal').on('click', '#btnOptNormal', function() { Swal.close(); activarClienteNormal(idCli, nomCli); });
                $(document).off('click', '#btnOptLicitacion').on('click', '#btnOptLicitacion', function() { Swal.close(); activarClienteLicitacion(idCli, nomCli); });
                $(document).off('click', '#btnOptMuestra').on('click', '#btnOptMuestra', function() { Swal.close(); activarClienteMuestra(idCli, nomCli); });
            } else {
                Swal.fire('Acceso Denegado', 'No tienes permisos para crear documentos.', 'error');
            }
        });

        $(document).on('click', '.btn-cambiar-cliente', function() {
            window.modoCambioCliente = true;
            Swal.fire({
                title: 'Cambiar Cliente',
                text: 'Serás redirigido al Directorio. Busca el nuevo cliente y haz clic en "Cotizar" para reemplazar al actual.',
                icon: 'info',
                confirmButtonColor: '#0B4C8C',
                confirmButtonText: 'Ir al Directorio'
            }).then((res) => {
                if(res.isConfirmed) $('#btnMenuClientes').click();
            });
        });

        function activarClienteNormal(id, nombre) {
            $('#idClienteSeleccionado').val(id);
            $('#lblClienteSeleccionado').text(nombre);
            $('#id_edit_tipo_cotizacion').val('ESTANDAR'); 
            window.inyectarTipoEnFormularios('ESTANDAR');

            if(typeof cancelarEdicionCliente === 'function') cancelarEdicionCliente(); 
            $('#id_edit_cotizacion').val('0');
            $('#badgeEstadoEdicion').hide();
            $('.btn-bloqueable').prop('disabled', false);
            carritoMemoria = [];
            
            $('[name="id_forma_pago"], [name="metodo_entrega"]').attr('required', true);

            if($('#cot_aplica_iva').length) $('#cot_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
            if($('#cot_desc_manual').length) $('#cot_desc_manual').val(0);
            if($('#cot_desc_logistica').length) $('#cot_desc_logistica').val(0);
            if($('#cot_flete').length) $('#cot_flete').val(0);

            if(typeof actualizarResumenCarrito === 'function') actualizarResumenCarrito();
            $('#btnMenuCotizador').click(); 
            window.scrollTo({ top: 0, behavior: 'instant' });
        }

        function activarClienteLicitacion(id, nombre) {
            $('#idClienteSeleccionado').val(id);
            $('#lblClienteSeleccionado').text(nombre);
            $('#id_edit_tipo_cotizacion').val('LICITACION'); 
            window.inyectarTipoEnFormularios('LICITACION');

            if(typeof cancelarEdicionCliente === 'function') cancelarEdicionCliente(); 
            $('#id_edit_cotizacion').val('0');
            $('#lic_nro_cotizacion').val(''); 
            $('#badgeEstadoEdicion').hide();
            $('.btn-bloqueable').prop('disabled', false); 
            carritoLicitacion = [];

            $('[name="lic_forma_pago"], [name="lic_metodo_entrega"]').attr('required', true);

            if($('#lic_aplica_iva').length) $('#lic_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
            if($('#lic_desc_manual').length) $('#lic_desc_manual').val(0);
            if($('#lic_desc_logistica').length) $('#lic_desc_logistica').val(0);
            if($('#lic_flete').length) $('#lic_flete').val(0);

            if(typeof actualizarTablaLicitacion === 'function') actualizarTablaLicitacion();
            $('#btnMenuCotizadorLicitaciones').click(); 
            window.scrollTo({ top: 0, behavior: 'instant' });
        }

        function activarClienteMuestra(id, nombre) {
            $('#idClienteSeleccionado').val(id);
            $('#lblClienteSeleccionado').html(`${nombre} <span class="badge bg-info text-dark ms-2"><i class="bi bi-box2-heart"></i> MUESTRA FÍSICA</span>`);
            $('#id_edit_tipo_cotizacion').val('MUESTRA');
            window.inyectarTipoEnFormularios('MUESTRA');
            
            if(typeof cancelarEdicionCliente === 'function') cancelarEdicionCliente(); 
            $('#id_edit_cotizacion').val('0');
            $('#badgeEstadoEdicion').hide();
            $('.btn-bloqueable').prop('disabled', false);
            carritoMemoria = [];
            
            $('[name="id_forma_pago"], [name="metodo_entrega"], [name="lic_forma_pago"], [name="lic_metodo_entrega"]').removeAttr('required');

            if($('#cot_aplica_iva').length) $('#cot_aplica_iva').prop('checked', false).closest('.custom-check-small').removeClass('active-check');
            if($('#cot_desc_manual').length) $('#cot_desc_manual').val(0);
            if($('#cot_desc_logistica').length) $('#cot_desc_logistica').val(0);
            if($('#cot_flete').length) $('#cot_flete').val(0);

            if(typeof actualizarResumenCarrito === 'function') actualizarResumenCarrito();
            
            $('#btnMenuCotizador').click(); 
            window.scrollTo({ top: 0, behavior: 'instant' });
            
            Swal.fire({
                title: 'Modo Muestra Física',
                text: 'El sistema guardará la solicitud a $0.00 y no te exigirá métodos de pago.',
                icon: 'info',
                confirmButtonColor: '#0B4C8C'
            });
        }

        // ==============================================================================
        // LÓGICA PRINCIPAL: CARGAR HISTORIAL Y ESTADOS 
        // ==============================================================================
        window.cargarHistorial = function() {
            $('#tablaHistorialBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
            $('#tablaPausadasBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border text-info"></span></td></tr>');
            window.cargarGarantiasTextiles();
            
            let f_desde = $('#filtro_desde_hist').val();
            let f_hasta = $('#filtro_hasta_hist').val();
            let f_estado = $('#filtro_estado_hist').val(); 
            let f_tracking = $('#filtro_tracking_hist').length ? $('#filtro_tracking_hist').val() : ''; 
            let f_vendedor = $('#filtro_vendedor_hist').length ? $('#filtro_vendedor_hist').val() : '';
            let f_cliente = $('#filtro_cliente_hist').length ? $('#filtro_cliente_hist').val() : '';

            $.get('api/api_cotizaciones.php', { accion: 'listar', desde: f_desde, hasta: f_hasta, estado: f_estado, tracking: f_tracking, vendedor: f_vendedor, cliente: f_cliente }, function(res) {
                if(res.success) {
                    if (res.es_master_actual !== undefined) {
                        window.ES_MASTER_CONFIRMADO_API = Boolean(res.es_master_actual);
                    }
                    const soyMaster = window.esUsuarioMaster();
                    const porcMinimoActual = soyMaster ? 50 : 60;

                    if (res.hay_notificaciones) $('#puntoRojoVentas').show(); else $('#puntoRojoVentas').hide();

                    if ($('#filtro_cliente_hist').length && Array.isArray(res.clientes)) {
                        const clienteSeleccionado = String(f_cliente || '');
                        const $filtroCliente = $('#filtro_cliente_hist');
                        const nombreSeleccionado = $('#filtro_cliente_hist_busqueda').val();
                        clientesFiltroHistorial = res.clientes;
                        $filtroCliente.empty().append(new Option('Todos los clientes', ''));
                        res.clientes.forEach(cliente => {
                            $filtroCliente.append(new Option(cliente.nombre, String(cliente.ID)));
                        });
                        if (clienteSeleccionado && !$filtroCliente.find(`option[value="${clienteSeleccionado}"]`).length) {
                            $filtroCliente.append(new Option(nombreSeleccionado || 'Cliente seleccionado', clienteSeleccionado));
                        }
                        $filtroCliente.val(clienteSeleccionado);
                        const clienteActual = clientesFiltroHistorial.find(cliente => String(cliente.ID) === clienteSeleccionado);
                        if (clienteActual) {
                            $('#filtro_cliente_hist_busqueda').val(clienteActual.nombre);
                        } else if (!clienteSeleccionado) {
                            $('#filtro_cliente_hist_busqueda').val('');
                        }
                    }

                    let htmlActivas = '';
                    let htmlPausadas = '';
                    let contAct = 1; 
                    let contPaus = 1;

                    res.data.forEach(c => {
                        let badgeClass = 'bg-danger'; 
                        let estadoLC = c.estado_pago ? c.estado_pago.toLowerCase() : '';
                        let esTerminadoTracking = (c.tracking_estado === 'Terminado');
                        let esMuestra = (c.tipo_cotizacion === 'MUESTRA');

                        if (esMuestra) badgeClass = 'bg-info text-dark';
                        else if(estadoLC === 'pago inicial' || estadoLC === 'pago medio') badgeClass = 'bg-warning text-dark'; 
                        else if(estadoLC === 'pagado') badgeClass = 'bg-success'; 

                        let puedeEditar = (c.tipo_cotizacion === 'LICITACION') ? JS_tieneVector(30) : JS_tieneVector(8);
                        puedeEditar = puedeEditar && Number(c.enviado_logistica) === 0;
                        let btnEditar = puedeEditar ? `<button class="btn btn-sm btn-outline-warning fw-bold" onclick="window.cargarCotizacionParaEditar(${c.ID}, ${c.id_cliente}, '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Editar"><i class="bi bi-pencil"></i></button>` : '';
                        let btnEliminar = JS_tieneVector(9) ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="window.eliminarCotizacion(${c.ID})"><i class="bi bi-trash"></i></button>` : '';

                        let llaveSegura = btoa(c.ID + '-' + c.nro_cotizacion);

                        let btnWhatsApp = '';
                        if(JS_tieneVector(22)) {
                            let btnColor = 'btn-success text-white';
                            let labelBoton = '<i class="bi bi-whatsapp"></i> WA';
                            let alertaIcono = '';
                            
                            if(c.noti_wa == 1) {
                                btnColor = 'btn-danger pulse-wa text-white'; labelBoton = '<i class="bi bi-bell-fill"></i> Avance'; alertaIcono = '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>';
                            } else if(c.noti_wa == 2) {
                                btnColor = 'btn-danger pulse-wa text-white'; labelBoton = '<i class="bi bi-check-circle-fill"></i> Listo'; alertaIcono = '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>';
                            } else if (c.noti_wa == 3) {
                                btnColor = 'btn-danger pulse-wa text-white'; labelBoton = '<i class="bi bi-cash-coin"></i> Cobrar'; alertaIcono = '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>';
                            } else if (c.noti_wa == 4) {
                                btnColor = 'btn-warning pulse-wa text-dark'; labelBoton = '<i class="bi bi-tools"></i> Gar. Proceso'; alertaIcono = '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>';
                            } else if (c.noti_wa == 5) {
                                btnColor = 'btn-danger pulse-wa text-white'; labelBoton = '<i class="bi bi-shield-check"></i> Gar. Lista'; alertaIcono = '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>';
                            }

                            let opcionesWA = `
                                <li><a class="dropdown-item fw-bold text-dark" href="#" onclick="window.enviarWASaludo('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}')"><i class="bi bi-chat-dots text-primary me-2"></i> Saludo General</a></li>
                                <li><a class="dropdown-item fw-bold text-dark" href="#" onclick="window.enviarWACotizacion('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.nro_cotizacion}', ${c.ID}, '${c.tipo_cotizacion}')"><i class="bi bi-file-pdf text-danger me-2"></i> Enviar Archivo de Cotización</a></li>
                            `;

                            if (c.hash_publico) {
                                let styleAvance = (c.noti_wa == 1) ? 'background-color: #ffea75 !important; border-radius: 5px; margin: 2px 5px; width: calc(100% - 10px);' : '';
                                let styleFin = (c.noti_wa == 2) ? 'background-color: #ffea75 !important; border-radius: 5px; margin: 2px 5px; width: calc(100% - 10px);' : '';
                                let styleCobro = (c.noti_wa == 3) ? 'background-color: #ffea75 !important; border-radius: 5px; margin: 2px 5px; width: calc(100% - 10px);' : '';
                                let styleGarProc = (c.noti_wa == 4) ? 'background-color: #ffea75 !important; border-radius: 5px; margin: 2px 5px; width: calc(100% - 10px);' : '';
                                let styleGarFin = (c.noti_wa == 5) ? 'background-color: #ffea75 !important; border-radius: 5px; margin: 2px 5px; width: calc(100% - 10px);' : '';
                                
                                let btnCobro = '';
                                let btnFinalizadoFuerte = '';
                                
                                let txtPagosLimpios = window.limpiarSeparadoresSucios(c.pago_valor_usd).join('|').replace(/'/g, "\\'");

                                if ((estadoLC === 'pago inicial' || estadoLC === 'pago medio') && !esMuestra) {
                                    btnCobro = `<li><a class="dropdown-item fw-bold text-dark" style="${styleCobro}" href="#" onclick="window.abrirModalCobranza('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.nro_cotizacion}', ${c.total}, '${txtPagosLimpios}')"><i class="bi bi-cash-coin text-danger me-2"></i> Cobrar Saldo Pendiente</a></li>`;
                                }
                                
                                if (esTerminadoTracking && (estadoLC === 'pagado' || esMuestra)) {
                                    btnFinalizadoFuerte = `<li><a class="dropdown-item fw-bold text-dark" style="${styleFin}" href="#" onclick="window.enviarWAFinalizado('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.hash_publico}', ${c.ID})"><i class="bi bi-check-circle-fill text-success me-2"></i> Notificar Pedido Finalizado</a></li>`;
                                }

                                let btnGarProceso = `<li><a class="dropdown-item fw-bold text-dark" style="${styleGarProc}" href="#" onclick="window.enviarWAGarantiaProceso('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.nro_cotizacion}', '${c.hash_publico}', ${c.ID})"><i class="bi bi-tools text-warning me-2"></i> Notificar Garantía en Proceso</a></li>`;
                                let btnGarLista = `<li><a class="dropdown-item fw-bold text-dark" style="${styleGarFin}" href="#" onclick="window.enviarWAGarantiaLista('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.nro_cotizacion}', '${c.hash_publico}', ${c.ID})"><i class="bi bi-shield-check text-success me-2"></i> Notificar Garantía Terminada</a></li>`;

                                opcionesWA += `
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item fw-bold text-dark" style="${styleAvance}" href="#" onclick="window.enviarWAAvance('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.hash_publico}', ${c.ID})"><i class="bi bi-truck text-primary me-2"></i> Notificar Avance de Tracking</a></li>
                                ${btnCobro}
                                ${btnFinalizadoFuerte}
                                <li><hr class="dropdown-divider"></li>
                                ${btnGarProceso}
                                ${btnGarLista}
                                `;
                            }

                            btnWhatsApp = `
                            <div class="dropdown d-inline-block position-relative">
                                <button class="btn btn-sm ${btnColor} rounded-pill dropdown-toggle shadow-sm fw-bold px-3" data-bs-toggle="dropdown" title="Comunicaciones con el Cliente">
                                    ${labelBoton}
                                </button>
                                ${alertaIcono}
                                <ul class="dropdown-menu shadow-sm" style="font-size: 0.85rem;">
                                    ${opcionesWA}
                                </ul>
                            </div>`;
                        }

                        let textMontoColumna = esMuestra ? '<span class="text-info fw-bold">MUESTRA ($0)</span>' : `${window.formatoMoneda(c.total)} $`;
                        
                        let igtfNum = parseFloat(c.igtf) || 0;
                        let ivaNum = parseFloat(c.iva) || 0;
                        let baseImpParaComision = parseFloat(c.total) - ivaNum - igtfNum;

                        let totalAbonado = 0;
                        if (c.pago_valor_usd && c.pago_valor_usd.trim() !== '') {
                            let pagosArr = window.limpiarSeparadoresSucios(c.pago_valor_usd);
                            pagosArr.forEach(p => { 
                                let numLimpio = p.replace(/[^\d.-]/g, '');
                                totalAbonado += parseFloat(numLimpio) || 0; 
                            });
                        }
                        const retencionesPreviasUsd = window.totalRetencionesPagosUsd(c.pago_retenciones, c.tasa_factura_fiscal);
                        totalAbonado += retencionesPreviasUsd;

                        let minimoReq = baseImpParaComision * (porcMinimoActual / 100);
                        let estaBloqueadaPorPago = (totalAbonado < (minimoReq - 0.10));
                        
                        if (esMuestra) estaBloqueadaPorPago = false;
                        else if (window.unlockedRows.includes(c.ID)) estaBloqueadaPorPago = false;
                        if (c.estado_pago === 'Revisar retenciones') estaBloqueadaPorPago = true;

                        let safeRef = window.limpiarSeparadoresSucios(c.referencias_pago).join('|').replace(/'/g, "\\'");
                        let safeFecha = window.limpiarSeparadoresSucios(c.pago_fecha).join('|').replace(/'/g, "\\'");
                        let safeTasa = window.limpiarSeparadoresSucios(c.pago_tasa_bcv).join('|').replace(/'/g, "\\'");
                        let safeUsd = window.limpiarSeparadoresSucios(c.pago_valor_usd).join('|').replace(/'/g, "\\'");
                        let safeRetencionesPreviasUsd = Number(retencionesPreviasUsd).toFixed(2);
                        let tasaFacturaFiscal = Number(c.tasa_factura_fiscal) || 0;
                        let safeBanco = window.limpiarSeparadoresSucios(c.pago_banco).join('|').replace(/'/g, "\\'");
                        let arrFotosLimpias = window.limpiarSeparadoresSucios(c.fotos_pagos);
                        let safeFotos = arrFotosLimpias.join(',').replace(/'/g, "\\'");

                        window.cotizacionesCachePagos = window.cotizacionesCachePagos || {};
                        window.cotizacionesCachePagos[c.ID] = c;

                        let itemEditarPagoMaster = '';
                        if (soyMaster && safeUsd !== '') {
                            itemEditarPagoMaster = `
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item fw-bold text-primary" href="#" onclick="window.abrirSelectorEdicionPagoMaster(${c.ID})"><i class="bi bi-pencil-square text-primary"></i> Editar Pago Registrado (Master)</a></li>
                            `;
                        }

                        let comboPagosHTML = '';
                        if (esMuestra) {
                            comboPagosHTML = `<span class="badge bg-info text-dark" style="padding:6px 10px;"><i class="bi bi-box2-heart"></i> MUESTRA FÍSICA</span>`;
                        } else if ((JS_tieneVector(8) || JS_tieneVector(30) || soyMaster)) {
                            comboPagosHTML = `
                            <span class="badge ${badgeClass} dropdown-toggle" style="cursor:pointer; padding:6px 10px;" data-bs-toggle="dropdown">${c.estado_pago.toUpperCase()}</span>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item fw-bold text-danger" href="#" onclick="window.cambiarEstadoDirecto(${c.ID}, 'sin pagar')"><i class="bi bi-circle-fill text-danger"></i> Sin Pagar</a></li>
                                <li><a class="dropdown-item fw-bold text-warning" href="#" onclick="window.abrirModalPago(${c.ID}, 'Pago Inicial', '${c.total}', '${baseImpParaComision}', '${c.nro_cotizacion}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${safeUsd}', '${c.cliente_tlf}', ${safeRetencionesPreviasUsd}, ${tasaFacturaFiscal}, ${Number(c.iva) || 0}, ${Number(c.sub_total) || 0}, ${Number(c.descuento) || 0}, ${Number(c.servicio_logistico) || 0}, ${Number(c.flete) || 0})"><i class="bi bi-circle-fill text-warning"></i> Registrar Abono / Pago</a></li>
                                <li><a class="dropdown-item fw-bold text-success" href="#" onclick="window.abrirModalPago(${c.ID}, 'Pagado', '${c.total}', '${baseImpParaComision}', '${c.nro_cotizacion}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${safeUsd}', '${c.cliente_tlf}', ${safeRetencionesPreviasUsd}, ${tasaFacturaFiscal}, ${Number(c.iva) || 0}, ${Number(c.sub_total) || 0}, ${Number(c.descuento) || 0}, ${Number(c.servicio_logistico) || 0}, ${Number(c.flete) || 0})"><i class="bi bi-circle-fill text-success"></i> Pagado Total</a></li>
                                ${itemEditarPagoMaster}
                            </ul>`;
                        } else {
                            comboPagosHTML = `<span class="badge ${badgeClass}" style="padding:6px 10px;">${c.estado_pago.toUpperCase()}</span>`;
                        }

                        // ================= REGLA AZUL Y CAMIÓN (CON PASE DIRECTO SIN PAGO EXCLUSIVO PARA MASTER) =================
                        let btnEspecificar = '';
                        if (c.enviado_logistica == 1 || c.enviado_logistica == 2) {
                            let tituloCaja = c.enviado_logistica == 2 ? "Enviado Directo a Envíos" : "Enviado a Logística";
                            btnEspecificar = `<button class="btn btn-sm btn-success fw-bold ms-1" title="${tituloCaja}" disabled><i class="bi bi-check2-all"></i></button>`;
                        } else {
                            // 1. REGLA AZUL: Siempre libre
                            let btnRegla = (JS_tieneVector(28) || soyMaster) ? `<button class="btn btn-sm btn-outline-info fw-bold ms-1" onclick="window.abrirModalEspecificarDetalles(${c.ID}, '${c.nro_cotizacion}')" title="Especificar Tallas y Telas para Taller"><i class="bi bi-rulers"></i></button>` : '';
                            
                            // 2. CAMIÓN Y BLOQUEO:
                            let btnLogistica = '';
                            const safeCliNombre = String(c.cliente_nombre || '').replace(/'/g, "\\'");
                            const safeNroCot = String(c.nro_cotizacion || '').replace(/'/g, "\\'");

                            if (!estaBloqueadaPorPago && (estadoLC === 'pago inicial' || estadoLC === 'pago medio' || estadoLC === 'pagado' || esMuestra)) {
                                // Cumple el pago normal o es muestra física
                                let btnCamion = (JS_tieneVector(29) || soyMaster) ? `<button class="btn btn-sm btn-outline-dark fw-bold ms-1" onclick="window.enviarALogistica(${c.ID})" title="Enviar a Logística"><i class="bi bi-truck"></i></button>` : '';
                                let btnEnvioDir = (JS_tieneVector(44) || soyMaster) ? `<button class="btn btn-sm btn-outline-success fw-bold ms-1" onclick="window.enviarDirectoEnvios(${c.ID})" title="Enviar Directo a Envíos (Saltar Fábrica)"><i class="bi bi-box-seam-fill"></i></button>` : '';
                                btnLogistica = btnCamion + btnEnvioDir;
                            } else if (soyMaster) {
                                // EXCLUSIVO MASTER: Puede enviar directo a logística sin pago y generar Cuenta por Cobrar
                                btnLogistica = `<button class="btn btn-sm btn-warning text-dark fw-bold ms-1 shadow-sm" onclick="window.autorizarEnvioSinPagoMaster(${c.ID}, '${safeNroCot}', '${safeCliNombre}', '${c.total}', '${totalAbonado}')" title="Enviar a Logística Sin Pago + Generar Cuenta por Cobrar (Exclusivo Master)"><i class="bi bi-truck"></i> Sin Pago</button>`;
                            } else {
                                // Vendedores normales: Botón Bloqueada
                                let falta = minimoReq - totalAbonado;
                                if(falta < 0) falta = 0;
                                btnLogistica = `<button class="btn btn-sm btn-danger fw-bold ms-1 pulse-wa" onclick="window.mostrarAlertaBloqueo(${baseImpParaComision}, ${minimoReq}, ${totalAbonado}, ${falta}, ${c.ID}, '${c.total}', '${baseImpParaComision}', '${safeNroCot}', '${safeCliNombre}', '${window.limpiarSeparadoresSucios(c.pago_valor_usd).join('|').replace(/'/g, "\\'")}', '${c.cliente_tlf || ''}', ${safeRetencionesPreviasUsd}, ${tasaFacturaFiscal}, ${Number(c.iva) || 0}, ${Number(c.sub_total) || 0}, ${Number(c.descuento) || 0}, ${Number(c.servicio_logistico) || 0}, ${Number(c.flete) || 0})" title="Cotización Bloqueada por falta de pago"><i class="bi bi-lock-fill"></i> Bloqueada</button>`;
                            }
                            
                            btnEspecificar = btnRegla + btnLogistica;
                        }
                        // =================================================================================

                        let btnComision = '';
                        if (!esMuestra && (estadoLC === 'pago inicial' || estadoLC === 'pago medio' || estadoLC === 'pagado')) {
                            if (c.comision_pagada == 1) {
                                btnComision = `<button class="btn btn-sm btn-success text-white fw-bold ms-1" onclick="window.verComision(${c.ID}, '${c.nro_cotizacion}', '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Comisión Pagada (Ver Recibo)"><i class="bi bi-coin"></i></button>`;
                            } else if (JS_tieneVector(35)) {
                                btnComision = `<button class="btn btn-sm btn-warning text-dark fw-bold ms-1" onclick="window.abrirModalComision(${c.ID}, ${baseImpParaComision}, '${c.nro_cotizacion}', '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Pagar y Registrar Comisión"><i class="bi bi-coin"></i></button>`;
                            }
                        }

                        let scriptVistaPdf = c.tipo_cotizacion === 'LICITACION' ? 'imprimir_licitacion.php' : 'imprimir_cotizacion.php';
                        if(esMuestra) scriptVistaPdf = 'imprimir_muestra.php';
                        let btnFacturaFiscal = ['ESTANDAR', 'LICITACION'].includes(String(c.tipo_cotizacion || '').toUpperCase())
                            ? `<button class="btn btn-sm btn-outline-success ms-1" onclick="window.abrirFacturaFiscal(${c.ID})" aria-label="Generar factura fiscal${c.tipo_cotizacion === 'LICITACION' ? ' de licitación' : ''}" title="Generar factura fiscal${c.tipo_cotizacion === 'LICITACION' ? ' de licitación' : ''}"><i class="bi bi-receipt-cutoff"></i>${c.tipo_cotizacion === 'LICITACION' ? ' Fiscal' : ''}</button>`
                            : '';

                        let is_pausado = (c.cotizacion_pausada == 1);
                        let badgeHielo = is_pausado ? `<span class="badge bg-info text-dark ms-1" title="Cotización Pausada/Congelada"><i class="bi bi-snow"></i></span>` : '';
                        let rowStyle = is_pausado ? 'background-color:#f1f5f9; opacity:0.85;' : '';
                        
                        let icon_hielo = is_pausado ? 'bi-fire text-danger' : 'bi-snow text-info';
                        let title_hielo = is_pausado ? 'Descongelar Cotización' : 'Congelar / Pausar Cotización';
                        let btnPausarCot = JS_tieneVector(8) ? `<button class="btn btn-sm btn-outline-dark fw-bold ms-1 btn-pausar-cot" data-id="${c.ID}" data-estado="${is_pausado ? 1 : 0}" title="${title_hielo}"><i class="bi ${icon_hielo}"></i></button>` : '';

                        let badgeFacturas = '';
                        if (c.numero_factura_fiscal) {
                            badgeFacturas += `<br><span class="badge bg-success-subtle text-success border border-success" style="font-size:0.65rem;"><i class="bi bi-receipt"></i> Factura ${c.numero_factura_fiscal}</span>`;
                        }
                        if (c.facturas_anuladas_lista) {
                            badgeFacturas += `<br><span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.65rem;" title="Facturas anuladas conservadas en el registro"><i class="bi bi-x-octagon"></i> Anulada: ${c.facturas_anuladas_lista}</span>`;
                        }

                        let rowHtml = `<tr style="${rowStyle}">
                            <td class="fw-bold text-muted">${is_pausado ? contPaus++ : contAct++}</td>
                            <td class="fw-bold text-nowrap">
                                ${c.nro_cotizacion}
                                ${badgeHielo}
                                ${c.tipo_cotizacion === 'LICITACION' ? '<br><span class="badge bg-dark" style="font-size:0.65rem;">Licitación</span>' : ''}
                                ${badgeFacturas}
                            </td>
                            <td style="min-width: 200px;">${c.cliente_nombre}</td>
                            <td style="min-width:170px;max-width:240px;white-space:normal;">${escapeHtmlGarantiaVenta(c.notas || '—')}</td>
                            <td class="text-nowrap">${c.fecha_emision}</td>
                            <td class="text-nowrap">${textMontoColumna}</td>
                            <td class="text-nowrap">
                                ${comboPagosHTML}
                            </td>
                            <td class="text-center">${btnWhatsApp}</td>
                            <td class="text-center text-nowrap" style="min-width: 200px;">
                                ${btnEditar} ${btnEspecificar} ${btnPausarCot} ${btnComision}
                                <button class="btn btn-sm btn-outline-secondary fw-bold ms-1" onclick="window.verDetalleHistorialCotizacion(${c.ID}, ${c.sub_total}, ${c.descuento}, ${c.iva}, ${c.total}, '${safeRef}', '${safeFotos}', '${safeFecha}', '${safeTasa}', '${safeUsd}', '${safeBanco}', '${c.tipo_cotizacion}', '${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Ver Resumen"><i class="bi bi-eye"></i></button>
                                <a href="${scriptVistaPdf}?id=${c.ID}&k=${llaveSegura}" target="_blank" class="btn btn-sm btn-outline-primary ms-1" title="Ver PDF"><i class="bi bi-file-pdf"></i></a>
                                ${btnFacturaFiscal}
                                ${btnEliminar}
                            </td>
                        </tr>`;

                        if(is_pausado) htmlPausadas += rowHtml; else htmlActivas += rowHtml;
                    });
                    
                    $('#tablaHistorialBody').html(htmlActivas || '<tr><td colspan="9" class="text-center text-muted fw-bold py-4">No hay cotizaciones activas en el historial.</td></tr>');
                    $('#tablaPausadasBody').html(htmlPausadas || '<tr><td colspan="9" class="text-center text-muted fw-bold py-4">El archivo de cotizaciones congeladas está vacío.</td></tr>');
                }
            }, 'json').fail(function() {
                $('#tablaHistorialBody').html('<tr><td colspan="9" class="text-center text-danger">Error de conexión al cargar historial.</td></tr>');
            });
        };

        function escapeHtmlGarantiaVenta(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char]);
        }

        window.cargarGarantiasTextiles = function() {
            const $tbody = $('#tablaGarantiasTextiles');
            if (!$tbody.length) return;
            $tbody.html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-danger"></span></td></tr>');
            $.get('api/api_cotizaciones.php', { accion: 'listar_garantias_textiles' }, function(res) {
                if (!res.success) {
                    $tbody.html(`<tr><td colspan="8" class="text-center text-danger">${escapeHtmlGarantiaVenta(res.msg || 'No se pudo cargar el historial de garantías.')}</td></tr>`);
                    return;
                }
                const garantias = Array.isArray(res.data) ? res.data : [];
                const abiertas = garantias.filter((garantia) => garantia.estado !== 'Finalizada' && garantia.aprobacion_estado !== 'Rechazada').length;
                $('#badgeGarantiasVentas').text(abiertas).toggleClass('d-none', abiertas === 0);
                if (!garantias.length) {
                    $tbody.html('<tr><td colspan="8" class="text-center text-muted py-4">No hay garantías textiles registradas para este vendedor.</td></tr>');
                    return;
                }
                const rows = garantias.map((garantia, index) => {
                    const evidence = (garantia.evidencias || []).map((item) => {
                        const nombre = String(item.nombre || '');
                        if (!/^[a-f0-9]{40}\.(jpg|png|webp)$/i.test(nombre)) return '';
                        const url = `https://cliente.soyunifuerte.com/index.php?img=${encodeURIComponent(nombre)}`;
                        return `<a class="btn btn-sm btn-outline-secondary me-1 mb-1" href="${escapeHtmlGarantiaVenta(url)}" target="_blank" rel="noopener"><i class="bi bi-image"></i> Evidencia</a>`;
                    }).join('');
                    const tracking = garantia.hash_publico
                        ? `<a class="btn btn-sm btn-outline-primary mb-1" href="https://sistema.soyunifuerte.com/envios/tracking.php?h=${encodeURIComponent(garantia.hash_publico)}" target="_blank" rel="noopener"><i class="bi bi-truck"></i> Tracking</a>`
                        : '';
                    const statusClass = garantia.aprobacion_estado === 'Rechazada' ? 'bg-secondary' : (garantia.estado === 'Finalizada' ? 'bg-success' : (garantia.aprobacion_estado === 'Aprobada' ? 'bg-primary' : 'bg-warning text-dark'));
                    const approvalActions = garantia.aprobacion_estado === 'Pendiente'
                        ? `<div class="mt-2 d-flex gap-1">
                            <button class="btn btn-sm btn-success js-aprobar-garantia" data-id="${Number(garantia.id) || 0}"><i class="bi bi-check-lg"></i> Aprobar y enviar a Logística</button>
                            <button class="btn btn-sm btn-outline-danger js-rechazar-garantia" data-id="${Number(garantia.id) || 0}"><i class="bi bi-x-lg"></i> Rechazar</button>
                           </div>`
                        : (garantia.nota_aprobacion ? `<div class="small text-muted mt-1">${escapeHtmlGarantiaVenta(garantia.nota_aprobacion)}</div>` : '');
                    const approvalLabel = garantia.aprobacion_estado === 'Pendiente'
                        ? 'Pendiente de aprobación'
                        : (garantia.aprobacion_estado === 'Aprobada' ? garantia.estado : 'Rechazada');
                    return `<tr>
                        <td>${index + 1}</td>
                        <td><strong>#${Number(garantia.id) || 0}</strong><div class="small text-muted">${escapeHtmlGarantiaVenta(garantia.motivo || '')}</div></td>
                        <td>${escapeHtmlGarantiaVenta(garantia.nro_orden_fabrica || '')}<div class="small text-muted">Cot. ${escapeHtmlGarantiaVenta(garantia.nro_cotizacion || '')}</div></td>
                        <td>${escapeHtmlGarantiaVenta(garantia.cliente_nombre || '')}</td>
                        <td>${escapeHtmlGarantiaVenta(garantia.vendedor || '')}</td>
                        <td><span class="badge ${statusClass}">${escapeHtmlGarantiaVenta(approvalLabel || '')}</span>${approvalActions}</td>
                        <td>${escapeHtmlGarantiaVenta(garantia.creado_en || '')}</td>
                        <td>${evidence}${tracking}</td>
                    </tr>`;
                }).join('');
                $tbody.html(rows);
                $tbody.find('.js-aprobar-garantia').on('click', function() {
                    actualizarAprobacionGarantia($(this).data('id'), 'Aprobada', '');
                });
                $tbody.find('.js-rechazar-garantia').on('click', function() {
                    const id = $(this).data('id');
                    Swal.fire({
                        title: 'Rechazar solicitud',
                        input: 'textarea',
                        inputLabel: 'Indica el motivo del rechazo',
                        inputPlaceholder: 'Motivo obligatorio para informar al cliente',
                        inputAttributes: { maxlength: 1000 },
                        showCancelButton: true,
                        confirmButtonText: 'Rechazar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const nota = String(result.value || '').trim();
                            if (!nota) {
                                Swal.fire('Falta el motivo', 'Escribe el motivo del rechazo.', 'warning');
                                return;
                            }
                            actualizarAprobacionGarantia(id, 'Rechazada', nota);
                        }
                    });
                });
            }, 'json').fail(function() {
                $tbody.html('<tr><td colspan="8" class="text-center text-danger">Error de conexión al cargar las garantías textiles.</td></tr>');
            });
        };

        function actualizarAprobacionGarantia(id, decision, nota) {
            $.post('api/api_cotizaciones.php', {
                accion: 'actualizar_aprobacion_garantia',
                id_garantia: id,
                decision: decision,
                nota: nota
            }, function(res) {
                if (!res.success) {
                    Swal.fire('No se pudo procesar', res.msg || 'Inténtalo de nuevo.', 'error');
                    return;
                }
                Swal.fire('Garantía actualizada', res.msg, 'success');
                window.cargarGarantiasTextiles();
            }, 'json').fail(function() {
                Swal.fire('Error', 'No se pudo comunicar con Ventas.', 'error');
            });
        }

        window.autorizarEnvioSinPagoMaster = function(id_cot, nro_cot, cliente, totalCot, abonadoActual) {
            const saldoPendiente = Math.max(0, (parseFloat(totalCot) || 0) - (parseFloat(abonadoActual) || 0));
            Swal.fire({
                title: '<i class="bi bi-shield-lock-fill text-warning"></i> Autorización Master (Sin Pago)',
                html: `
                    <div class="text-start small">
                        <p class="mb-2">Estás autorizando enviar la cotización <b>${nro_cot}</b> del cliente <b>${cliente}</b> a Logística sin el pago inicial requerido.</p>
                        <div class="alert alert-success py-2 mb-2">
                            <div><b>Total Cotización:</b> $${(parseFloat(totalCot) || 0).toFixed(2)}</div>
                            <div><b>Deuda Restante (Por Cobrar):</b> <span class="text-danger fw-bold">$${saldoPendiente.toFixed(2)}</span></div>
                        </div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" id="swal_chk_generar_cxc" checked>
                            <label class="form-check-label fw-bold text-success" for="swal_chk_generar_cxc">Registrar en "Por Cobrar" (Cuentas Exigibles a Clientes)</label>
                        </div>
                        <small class="text-muted d-block">La cotización aparecerá automáticamente en Administración &rarr; Por Cobrar con deuda restante de <b>$${saldoPendiente.toFixed(2)} $</b>.</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-truck"></i> Revisar Reglas y Enviar a Logística',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return {
                        generarCxc: $('#swal_chk_generar_cxc').is(':checked'),
                        monto: saldoPendiente
                    };
                }
            }).then((resModal) => {
                if (resModal.isConfirmed) {
                    if (!window.unlockedRows.includes(id_cot)) {
                        window.unlockedRows.push(id_cot);
                    }
                    const datosCxc = resModal.value && resModal.value.generarCxc ? resModal.value : null;
                    window.abrirModalEspecificarDetalles(id_cot, nro_cot, true, true, datosCxc);
                }
            });
        };

        window.mostrarAlertaBloqueo = function(base_imponible, minimoReq, total_abonado, falta, id_cot, totalStr, subtotalStr, nro_cot, cliente, pagos_str, telefono, retenciones_previas_usd, tasa_factura, iva_factura, sub_total, descuento, servicio_logistico, flete) {
            let btnMasterHtml = '';
            if (window.esUsuarioMaster()) {
                const safeCli = String(cliente || '').replace(/'/g, "\\'");
                const safeNro = String(nro_cot || '').replace(/'/g, "\\'");
                btnMasterHtml = `<hr><button type="button" class="btn btn-warning fw-bold w-100 shadow-sm text-dark" onclick="Swal.close(); window.autorizarEnvioSinPagoMaster(${id_cot}, '${safeNro}', '${safeCli}', '${totalStr}', '${total_abonado}')"><i class="bi bi-unlock-fill me-1"></i> Enviar a Logística Sin Pago + Cuenta por Cobrar (Master)</button>`;
            }

            let htmlContent = `Para iniciar su proceso de confección debe abonar <b>${window.esUsuarioMaster() ? 50 : 60}% del Subtotal</b>.<br><br>
                   Requerido: <b class="text-primary">${window.formatoMoneda(minimoReq)} $</b><br>
                   Abonado: <b class="text-success">${window.formatoMoneda(total_abonado)} $</b><br><br>
                   Para iniciar faltan: <br><b class="text-danger fs-3">${window.formatoMoneda(falta)} $</b>
                   ${btnMasterHtml}`;

            Swal.fire({
                title: '<span style="color:#dc3545; font-size: 1.5rem;"><i class="bi bi-lock-fill"></i> Cotización Bloqueada</span>',
                html: htmlContent,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#0B4C8C',
                confirmButtonText: '<i class="bi bi-cash-coin"></i> Pagar Diferencia',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if(result.isConfirmed) {
                    window.abrirModalPago(id_cot, 'Pago Inicial', totalStr, subtotalStr, nro_cot, cliente, pagos_str, telefono, retenciones_previas_usd, tasa_factura, iva_factura, sub_total, descuento, servicio_logistico, flete);
                }
            });
        };

        window.abrirSelectorEdicionPagoMaster = function(id_cot) {
            const c = (window.cotizacionesCachePagos || {})[id_cot];
            if (!c) return;
            const arrUsd = window.limpiarSeparadoresSucios(c.pago_valor_usd);
            const arrFecha = window.limpiarSeparadoresSucios(c.pago_fecha);
            const arrRef = window.limpiarSeparadoresSucios(c.referencias_pago);
            const arrBanco = window.limpiarSeparadoresSucios(c.pago_banco);

            if (arrUsd.length <= 1) {
                window.abrirModalEditarPagoMaster(id_cot, 0);
                return;
            }

            let botonesHtml = '<div class="d-flex flex-column gap-2 mt-2">';
            arrUsd.forEach((monto, i) => {
                const f = arrFecha[i] || '-';
                const b = arrBanco[i] || '-';
                const r = arrRef[i] || '-';
                botonesHtml += `<button type="button" class="btn btn-outline-primary text-start fw-bold" onclick="Swal.close(); window.abrirModalEditarPagoMaster(${id_cot}, ${i});">
                    <i class="bi bi-pencil-square me-1"></i> Pago #${i + 1}: $${parseFloat(String(monto).replace(/[^\d.-]/g, '') || 0).toFixed(2)} — ${f} (${b} · Ref: ${r})
                </button>`;
            });
            botonesHtml += '</div>';

            Swal.fire({
                title: 'Selecciona el pago a editar',
                html: botonesHtml,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancelar'
            });
        };

        window.abrirModalEditarPagoMaster = function(id_cot, pagoIndex) {
            if (!window.esUsuarioMaster()) {
                Swal.fire('Acceso denegado', 'Solo el usuario Master puede editar pagos.', 'error');
                return;
            }
            const c = (window.cotizacionesCachePagos || {})[id_cot];
            if (!c) return;

            $('#modalVerDetalleCotizacion').modal('hide');
            $('#formPagoCotizacion')[0].reset();

            $('#pago_accion_form').val('editar_pago_individual');
            $('#pago_edit_index').val(pagoIndex);
            $('#modalPagoCotizacion .modal-title').html(`<i class="bi bi-pencil-square"></i> Editar Pago #${pagoIndex + 1} (Modo Master)`);
            $('#btnGuardarPago').text('Guardar Cambios del Pago');

            $('#pago_id_cotizacion').val(c.ID);
            $('#pago_lbl_nro_cot').text(c.nro_cotizacion || 'N/A');
            $('#pago_lbl_cliente').text(c.cliente_nombre || 'N/A').data('tlf', c.cliente_tlf || '');

            const totalVal = parseFloat(c.total) || 0;
            const ivaNum = parseFloat(c.iva) || 0;
            const igtfNum = parseFloat(c.igtf) || 0;
            const subtotalVal = totalVal - ivaNum - igtfNum;

            $('#pago_total_cotizacion').val(totalVal);
            $('#pago_subtotal_cotizacion').val(subtotalVal);
            $('#pago_tasa_factura').val(Number(c.tasa_factura_fiscal) || 0);
            $('#pago_retencion_base_iva_usd').val(Math.max(0, ivaNum));
            $('#pago_retencion_base_imponible_usd').val(Math.max(
                0,
                (Number(c.sub_total) || 0)
                + (Number(c.servicio_logistico) || 0)
                + (Number(c.flete) || 0)
                - (Number(c.descuento) || 0)
            ));

            const arrUsd = window.limpiarSeparadoresSucios(c.pago_valor_usd);
            const arrFecha = window.limpiarSeparadoresSucios(c.pago_fecha);
            const arrTasa = window.limpiarSeparadoresSucios(c.pago_tasa_bcv);
            const arrRef = window.limpiarSeparadoresSucios(c.referencias_pago);
            const arrBanco = window.limpiarSeparadoresSucios(c.pago_banco);

            let retencionesArr = [];
            try {
                retencionesArr = JSON.parse(c.pago_retenciones || '[]');
                if (!Array.isArray(retencionesArr)) retencionesArr = [];
            } catch (e) { retencionesArr = []; }

            let abonadoOtrosPagos = 0;
            arrUsd.forEach((p, idx) => {
                if (idx !== pagoIndex) {
                    const num = String(p).replace(/[^\d.-]/g, '');
                    abonadoOtrosPagos += parseFloat(num) || 0;
                }
            });
            const tasaFiscal = Number(c.tasa_factura_fiscal) || 0;
            retencionesArr.forEach((ret, idx) => {
                if (idx !== pagoIndex && ret && typeof ret === 'object') {
                    const tItem = tasaFiscal > 0 ? tasaFiscal : (Number(ret.tasa_bcv) || 0);
                    const mBs = (Number(ret.iva_bs) || 0) + (Number(ret.islr_bs) || 0) + (Number(ret.municipal_bs) || 0);
                    if (mBs > 0 && tItem > 0) abonadoOtrosPagos += (mBs / tItem);
                }
            });
            window.abonadoPrevioEnModal = Math.round(abonadoOtrosPagos * 100) / 100;

            const fechaStr = arrFecha[pagoIndex] || '';
            if (fechaStr.includes('/')) {
                const pF = fechaStr.split('/');
                if (pF.length === 3) $('#pago_fecha').val(`${pF[2]}-${pF[1].padStart(2, '0')}-${pF[0].padStart(2, '0')}`);
            } else if (fechaStr) {
                $('#pago_fecha').val(fechaStr);
            } else {
                document.getElementById('pago_fecha').valueAsDate = new Date();
            }

            const bancoVal = arrBanco[pagoIndex] && arrBanco[pagoIndex] !== '-' ? arrBanco[pagoIndex] : '';
            $('#pago_banco').val(bancoVal).trigger('change');

            const tasaStr = arrTasa[pagoIndex] || '';
            const tasaNum = parseFloat(String(tasaStr).replace(/[^\d.-]/g, '')) || 0;
            if (tasaStr.toLowerCase().includes('no aplica')) {
                $('#pago_tasa_bcv').attr('type', 'text').val('No aplica').prop('readonly', true).css('background-color', '#e9ecef');
            } else {
                $('#pago_tasa_bcv').attr('type', 'number').val(tasaNum > 0 ? tasaNum : '').prop('readonly', false).css('background-color', '#fff');
            }

            const usdActual = parseFloat(String(arrUsd[pagoIndex] || '0').replace(/[^\d.-]/g, '')) || 0;
            $('#pago_valor_usd').val(usdActual.toFixed(2));
            if (tasaNum > 0 && usdActual > 0) {
                $('#pago_valor_bs').val((usdActual * tasaNum).toFixed(2));
            } else {
                $('#pago_valor_bs').val('');
            }

            $('#pago_referencia').val(arrRef[pagoIndex] && arrRef[pagoIndex] !== '-' ? arrRef[pagoIndex] : '');

            const retActual = retencionesArr[pagoIndex] || {};
            const tieneRet = ((Number(retActual.iva_pct) || 0) + (Number(retActual.islr_pct) || 0) + (Number(retActual.municipal_pct) || 0) + (Number(retActual.iva_bs) || 0) + (Number(retActual.islr_bs) || 0) + (Number(retActual.municipal_bs) || 0)) > 0;
            $('#pago_con_retenciones').prop('checked', tieneRet);
            $('#pago_retenciones_panel').toggle(tieneRet);
            $('#retencion_iva_pct').val(Number(retActual.iva_pct) || 0);
            $('#retencion_islr_pct').val(Number(retActual.islr_pct) || 0);
            $('#retencion_municipal_pct').val(Number(retActual.municipal_pct) || 0);
            $('#retencion_iva_bs').val(Number(retActual.iva_bs || 0).toFixed(2));
            $('#retencion_islr_bs').val(Number(retActual.islr_bs || 0).toFixed(2));
            $('#retencion_municipal_bs').val(Number(retActual.municipal_bs || 0).toFixed(2));

            $('#alerta_pago_inicial').html(`<i class="bi bi-shield-lock-fill text-primary"></i> Editando el <b>Pago #${pagoIndex + 1}</b>. Al guardar se actualizará este registro y el estado de la cotización.`).show();

            actualizarEstadoPagoModal();
            $('#modalPagoCotizacion').modal('show');
        };

        window.abrirModalPago = function(id, estado, totalStr, subtotalStr, nro_cot, cliente, pagos_str, telefono_cliente, retenciones_previas_usd, tasa_factura, iva_factura, sub_total, descuento, servicio_logistico, flete) {
            $('#formPagoCotizacion')[0].reset();
            $('#pago_accion_form').val('cambiar_estado');
            $('#pago_edit_index').val('-1');
            $('#modalPagoCotizacion .modal-title').html('<i class="bi bi-cash-coin"></i> Registrar Pago');
            $('#btnGuardarPago').text('Guardar Pago');
            
            document.getElementById('pago_fecha').valueAsDate = new Date();
            $('#pago_id_cotizacion').val(id);
            $('#pago_estado_cotizacion').val(estado);
            $('#pago_lbl_estado').text(estado);
            
            $('#pago_lbl_nro_cot').text(nro_cot ? nro_cot : 'N/A');
            $('#pago_lbl_cliente').text(cliente ? cliente : 'N/A');
            $('#pago_lbl_cliente').data('tlf', telefono_cliente || '');
            
            let totalVal = parseFloat(String(totalStr).replace(',', '.')) || 0;
            let subtotalVal = parseFloat(String(subtotalStr).replace(',', '.')) || 0;
            
            $('#pago_total_cotizacion').val(totalVal);
            $('#pago_subtotal_cotizacion').val(subtotalVal); 
            $('#pago_tasa_factura').val(Number(tasa_factura) || 0);
            $('#pago_retencion_base_iva_usd').val(Math.max(0, Number(iva_factura) || 0));
            $('#pago_retencion_base_imponible_usd').val(Math.max(
                0,
                (Number(sub_total) || 0)
                + (Number(servicio_logistico) || 0)
                + (Number(flete) || 0)
                - (Number(descuento) || 0)
            ));
            $('#pago_retenciones_panel').hide();
            $('#pago_con_retenciones').prop('checked', false);
            $('#retencion_iva_pct').val('0');
            $('#retencion_islr_pct').val('0');
            $('#retencion_municipal_pct').val('0');
            $('#retencion_iva_bs, #retencion_islr_bs, #retencion_municipal_bs').val('0');

            let totalAbonado = 0;
            if (pagos_str && pagos_str.trim() !== '') {
                let pagosArr = window.limpiarSeparadoresSucios(pagos_str);
                pagosArr.forEach(p => { 
                    let numLimpio = p.replace(/[^\d.-]/g, '');
                    totalAbonado += parseFloat(numLimpio) || 0; 
                });
            }
            totalAbonado += Number(retenciones_previas_usd) || 0;
            window.retencionesPreviasEnModal = Number(retenciones_previas_usd) || 0;
            window.abonadoPrevioEnModal = totalAbonado;

            const porcMin = window.esUsuarioMaster() ? 50 : 60;
            let minimoReq = subtotalVal * (porcMin / 100);
            let faltaParaMinimo = minimoReq - totalAbonado;
            if (faltaParaMinimo < 0) faltaParaMinimo = 0;

            if (estado === 'Pago Inicial' || estado === 'Pago Medio') {
                $('#alerta_pago_inicial').html(`
                    Para iniciar su proceso de confección debe abonar ${porcMin}% del Subtotal.<br>
                    ${porcMin}% Requerido: <span id="pago_minimo_req" class="text-primary fs-6">${window.formatoMoneda(minimoReq)} $</span><br>
                    Abonado: <span id="pago_abonado_actual" class="text-success fs-6">${window.formatoMoneda(totalAbonado)} $</span><br>
                    Para iniciar faltan: <span id="pago_falta_req" class="text-danger fs-5">${window.formatoMoneda(faltaParaMinimo)} $</span>
                `).show();
                $('#pago_valor_usd').val('').removeAttr('min');
            } else if (estado === 'Pagado') {
                $('#alerta_pago_inicial').hide();
                let faltaFinal = totalVal - totalAbonado;
                if(faltaFinal < 0) faltaFinal = 0;
                $('#pago_valor_usd').val(faltaFinal.toFixed(2)).removeAttr('min');
            } else {
                $('#alerta_pago_inicial').hide();
                $('#pago_valor_usd').val('').removeAttr('min');
            }

            actualizarEstadoPagoModal();
            $('#modalPagoCotizacion').modal('show');
        };

function actualizarEstadoPagoModal() {
    let totalGeneral = parseFloat($('#pago_total_cotizacion').val()) || 0;
    const pagoNuevo = parseFloat($('#pago_valor_usd').val()) || 0;
    const tasaFactura = parseFloat($('#pago_tasa_factura').val()) || parseFloat($('#pago_tasa_bcv').val()) || 0;
    let retencionesBs = 0;
    const porcentajesRetencion = ['#retencion_iva_pct', '#retencion_islr_pct', '#retencion_municipal_pct']
        .map(selector => parseFloat($(selector).val()) || 0);
    const hayPorcentajesRetencion = porcentajesRetencion.some(porcentaje => porcentaje > 0);
    if ($('#pago_con_retenciones').is(':checked')) {
        const baseIvaBs = (parseFloat($('#pago_retencion_base_iva_usd').val()) || 0) * tasaFactura;
        const baseImponibleBs = (parseFloat($('#pago_retencion_base_imponible_usd').val()) || 0) * tasaFactura;
        const montos = {
            iva: baseIvaBs * (parseFloat($('#retencion_iva_pct').val()) || 0) / 100,
            islr: baseImponibleBs * (parseFloat($('#retencion_islr_pct').val()) || 0) / 100,
            municipal: baseImponibleBs * (parseFloat($('#retencion_municipal_pct').val()) || 0) / 100
        };
        Object.keys(montos).forEach(function(tipo) {
            const montoRedondeado = Math.round((montos[tipo] + Number.EPSILON) * 100) / 100;
            $('#retencion_' + tipo + '_bs').val(montoRedondeado.toFixed(2));
            retencionesBs += montoRedondeado;
        });
    }
    const retencionesUsd = tasaFactura > 0 ? retencionesBs / tasaFactura : 0;
    $('#pago_retenciones_equivalente').text(
        $('#pago_con_retenciones').is(':checked') && hayPorcentajesRetencion && tasaFactura <= 0
            ? 'Ingresa una tasa BCV válida para calcular los montos.'
            : `Retenciones: Bs ${retencionesBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} · USD ${retencionesUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    );
    let sumaTotal = window.abonadoPrevioEnModal + pagoNuevo + retencionesUsd;

    if (sumaTotal <= 0) {
        $('#pago_lbl_estado').text('SIN PAGAR').removeClass('text-success text-warning').addClass('text-danger');
        $('#pago_estado_cotizacion').val('sin pagar');
    } else if (sumaTotal >= (totalGeneral - 0.50)) {
        $('#pago_lbl_estado').text('PAGADO').removeClass('text-danger text-warning').addClass('text-success');
        $('#pago_estado_cotizacion').val('Pagado');
    } else {
        $('#pago_lbl_estado').text('PAGO INICIAL').removeClass('text-success text-danger').addClass('text-warning');
        $('#pago_estado_cotizacion').val('Pago Inicial');
    }
}

$(document).off('input change', '#pago_valor_usd, #pago_tasa_bcv, #pago_con_retenciones, #retencion_iva_pct, #retencion_islr_pct, #retencion_municipal_pct')
    .on('input change', '#pago_valor_usd, #pago_tasa_bcv, #pago_con_retenciones, #retencion_iva_pct, #retencion_islr_pct, #retencion_municipal_pct', function() {
        if (this.id === 'pago_con_retenciones') {
            $('#pago_retenciones_panel').toggle(this.checked);
        }
        actualizarEstadoPagoModal();
    });

        $(document).off('submit', '#formPagoCotizacion').on('submit', '#formPagoCotizacion', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            $('#pago_valor_usd').removeClass('is-invalid border-danger');

            let formData = new FormData(this);
            let abonoHecho = $('#pago_valor_usd').val();
            if ($('#pago_con_retenciones').is(':checked') && String(abonoHecho || '').trim() === '') {
                abonoHecho = '0';
                formData.set('pago_valor_usd', abonoHecho);
            }
            let tasaPagoHecho = $('#pago_tasa_bcv').val();
            let refHecha = $('#pago_referencia').val() || 'Efectivo/Zelle';
            let tlfCliente = $('#pago_lbl_cliente').data('tlf');
            
            $('#btnGuardarPago').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            $.ajax({
                url: 'api/api_cotizaciones.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#modalPagoCotizacion').modal('hide');
                        window.cargarHistorial();
                        
                        let base_url = window.location.href.split('portal_ventas')[0] + 'portal_ventas/';
                        let url_recibo = `${base_url}recibo_pago.php?id=${res.id_cotizacion}&abono=${encodeURIComponent(abonoHecho)}&tasa=${encodeURIComponent(tasaPagoHecho)}&ref=${encodeURIComponent(refHecha)}&pago_idx=${encodeURIComponent(res.pago_index)}`;

                        Swal.fire({
                            title: 'Pago Registrado',
                            text: 'El estado se ha auto-calculado correctamente.',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonColor: '#0B4C8C',
                            cancelButtonColor: '#25D366',
                            confirmButtonText: '<i class="bi bi-printer"></i> Ver Recibo',
                            cancelButtonText: '<i class="bi bi-whatsapp"></i> Enviar por WA'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.open(url_recibo, '_blank');
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                let msg = `Hola, hemos recibido tu pago en *Unifuerte C.A.* exitosamente. Puedes descargar o ver tu recibo ingresando al siguiente enlace: ${url_recibo}`;
                                let num = tlfCliente.replace(/\D/g, ''); 
                                if (num.startsWith('0')) { num = '58' + num.substring(1); } 
                                else if (!num.startsWith('58') && num.length === 10) { num = '58' + num; }
                                window.open(`https://api.whatsapp.com/send?phone=${num}&text=${encodeURIComponent(msg)}`, '_blank');
                            }
                        });
                    } else { Swal.fire('Error', res.msg, 'error'); }
                },
                complete: function() { $('#btnGuardarPago').prop('disabled', false).text('Guardar Pago'); }
            });
        });

        window.abrirModalCobranza = function(tlf, nombre, nro_cotizacion, total, pagos_str) {
            let totalAbonado = 0;
            if (pagos_str && pagos_str.trim() !== '') {
                let pagosArr = window.limpiarSeparadoresSucios(pagos_str);
                pagosArr.forEach(p => { 
                    let numLimpio = p.replace(/[^\d.-]/g, '');
                    totalAbonado += parseFloat(numLimpio) || 0; 
                });
            }
            let restante = parseFloat(total) - totalAbonado;
            if (restante < 0) restante = 0;

            $('#cobranza_lbl_total').text(parseFloat(total).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $');
            $('#cobranza_lbl_abonado').text(totalAbonado.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $');
            $('#cobranza_lbl_restante').text(restante.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $');
            $('#cobranza_tlf_cliente').val(tlf);

            let mensaje = `Estimado(a) *${nombre}*, reciba un cordial saludo de parte de *Unifuerte, C.A.*\n\nNos comunicamos para informarle que su pedido bajo la cotización *${nro_cotizacion}* está avanzando exitosamente.\n\nLe recordamos amablemente que presenta un saldo pendiente de *${restante.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} $*. Agradecemos su pronta gestión para continuar brindándole el mejor servicio.\n\nQuedamos a su entera disposición. ¡Feliz día!`;
            $('#cobranza_mensaje').val(mensaje);
            $('#modalCobranza').modal('show');
        };

        window.copiarMensajeCobranza = function() {
            let texto = document.getElementById("cobranza_mensaje");
            texto.select();
            document.execCommand("copy");
            Swal.fire({icon: 'success', title: 'Copiado', text: 'Mensaje copiado al portapapeles', timer: 1500, showConfirmButton: false});
        };

        window.enviarWhatsAppCobranza = function() {
            let tlfRaw = $('#cobranza_tlf_cliente').val();
            let mensaje = $('#cobranza_mensaje').val();
            if (!tlfRaw || tlfRaw.trim() === '') return Swal.fire('Error', 'El cliente no tiene un número registrado.', 'warning');
            
            let num = tlfRaw.replace(/\D/g, ''); 
            if (num.startsWith('0')) { num = '58' + num.substring(1); } 
            else if (!num.startsWith('58') && num.length === 10) { num = '58' + num; }
            window.open(`https://api.whatsapp.com/send?phone=${num}&text=${encodeURIComponent(mensaje)}`, '_blank');
            $('#modalCobranza').modal('hide');
        };

        window.actualizarResumenPrestamoComision = function() {
            let comision = parseFloat($('#com_monto_calculado').val()) || 0;
            let deuda = parseFloat($('#com_resumen_prestamo').attr('data-deuda')) || 0;
            let descuento = Math.min(comision, deuda);
            let neto = Math.max(0, comision - descuento);
            let listo = $('#com_resumen_prestamo').attr('data-vinculo') === 'si';

            $('#com_descuento_prestamo').text('$' + descuento.toFixed(2));
            $('#com_neto_pagar').text('$' + neto.toFixed(2));
            $('#com_banco_emi, #com_banco_rec, #com_ref, #com_tasa').prop('required', neto > 0.009);
            $('#btnGuardarComision').prop('disabled', !listo);
            if (typeof window.calcularComisionBsGlobal === 'function') {
                window.calcularComisionBsGlobal();
            }
        };

        function cargarResumenPrestamoComision(id_cot) {
            $('#com_resumen_prestamo').attr('data-vinculo', 'pendiente').attr('data-deuda', '0');
            $('#com_deuda_pendiente').text('Consultando...');
            $('#com_aviso_vinculo').text('');
            $('#btnGuardarComision').prop('disabled', true);
            $.get('api/api_cotizaciones.php', { accion: 'previsualizar_comision', id: id_cot }, function(res) {
                if (!res.success) {
                    $('#com_aviso_vinculo').html(`<span class="text-danger">${res.msg || 'No se pudo consultar el préstamo.'}</span>`);
                    return;
                }

                let vinculado = !!res.vendedor_encontrado;
                $('#com_resumen_prestamo')
                    .attr('data-vinculo', vinculado ? 'si' : 'no')
                    .attr('data-deuda', parseFloat(res.deuda_pendiente) || 0);
                $('#com_deuda_pendiente').text('$' + (parseFloat(res.deuda_pendiente) || 0).toFixed(2));
                $('#com_aviso_vinculo').html(vinculado
                    ? '<span class="text-muted">Los abonos se aplicarán al préstamo pendiente más antiguo.</span>'
                    : '<span class="text-danger">La cotización no pertenece a un usuario Vendedor o Jefe de Ventas; no se puede registrar esta comisión.</span>');
                window.actualizarResumenPrestamoComision();
            }, 'json').fail(function() {
                $('#com_aviso_vinculo').html('<span class="text-danger">No se pudo consultar el saldo del préstamo. Intenta nuevamente.</span>');
            });
        }

        window.abrirModalComision = function(id_cot, base_imp, nro_cot, cliente) {
            $('#formPagoComision')[0].reset();
            document.getElementById('com_fecha').valueAsDate = new Date();
            $('#com_id_cotizacion').val(id_cot);
            $('#com_lbl_base_imponible').text(window.formatoMoneda(base_imp) + ' $');
            $('#com_lbl_nro_cot').text(nro_cot ? nro_cot : 'N/A');
            $('#com_lbl_cliente').text(cliente ? cliente : 'N/A');
            $('#com_monto_calculado').val('');
            $('#formPagoComision input').prop('readonly', false);
            $('#com_monto_calculado').prop('readonly', true);
            $('#com_foto').prop('disabled', false);
            $('#btnGuardarComision').show();
            $('#div_com_foto_input').show();
            $('#div_com_foto_ver').hide();
            $('#com_banco_emi, #com_banco_rec, #com_ref').prop('required', true);
            $('#com_resumen_prestamo').attr('data-vinculo', 'pendiente').attr('data-deuda', '0');
            $('#com_deuda_pendiente').text('$0.00');
            $('#com_descuento_prestamo').text('$0.00');
            $('#com_neto_pagar').text('$0.00');

            $('#com_porc').off('input').on('input', function() {
                let porc = parseFloat($(this).val()) || 0;
                let monto = base_imp * (porc / 100);
                $('#com_monto_calculado').val(monto.toFixed(2));
                window.actualizarResumenPrestamoComision();
            });

            $('#modalComision').modal('show');
            cargarResumenPrestamoComision(id_cot);
        };

        window.verComision = function(id_cot, nro_cot, cliente) {
            $('#formPagoComision input').prop('readonly', true);
            $('#com_foto').prop('disabled', true);
            $('#btnGuardarComision').hide();
            $('#div_com_foto_input').hide();
            
            $.get('api/api_cotizaciones.php', { accion: 'obtener_comision', id: id_cot }, function(res) {
                if(res.success) {
                    let d = res.data;
                    $('#com_id_cotizacion').val(id_cot);
                    $('#com_lbl_base_imponible').text(window.formatoMoneda(d.base_imponible) + ' $');
                    $('#com_lbl_nro_cot').text(nro_cot ? nro_cot : 'N/A');
                    $('#com_lbl_cliente').text(cliente ? cliente : 'N/A');
                    $('#com_porc').val(d.comision_porc);
                    $('#com_monto_calculado').val(d.comision_monto);
                    $('#com_resumen_prestamo')
                        .attr('data-vinculo', 'si')
                        .attr('data-deuda', parseFloat(d.comision_descuento_prestamo) || 0);
                    $('#com_deuda_pendiente').text('—');
                    $('#com_descuento_prestamo').text('$' + (parseFloat(d.comision_descuento_prestamo) || 0).toFixed(2));
                    let netoRegistrado = parseFloat(d.comision_pago_neto) || 0;
                    if (netoRegistrado === 0 && (parseFloat(d.comision_descuento_prestamo) || 0) === 0) {
                        netoRegistrado = parseFloat(d.comision_monto) || 0;
                    }
                    $('#com_neto_pagar').text('$' + netoRegistrado.toFixed(2));
                    $('#com_aviso_vinculo').text('Descuento aplicado al registrar esta comisión.');
                    
                    if(d.comision_fecha) {
                        let parts = d.comision_fecha.split('/');
                        if(parts.length === 3) { $('#com_fecha').val(`${parts[2]}-${parts[1]}-${parts[0]}`); } 
                        else { $('#com_fecha').val(d.comision_fecha); }
                    }
                    
                    $('#com_tasa').val(d.comision_tasa);
                    $('#com_banco_emi').val(d.comision_banco_emi);
                    $('#com_banco_rec').val(d.comision_banco_rec);
                    $('#com_ref').val(d.comision_ref);
                    
                    if(d.comision_foto && d.comision_foto !== '') {
                        $('#link_com_foto').attr('href', '../images/pagos/' + d.comision_foto);
                        $('#div_com_foto_ver').show();
                    } else { $('#div_com_foto_ver').hide(); }
                    $('#modalComision').modal('show');
                } else { Swal.fire('Error', 'No se pudo cargar la comisión', 'error'); }
            }, 'json');
        };

        $(document).off('submit.comisionPago', '#formPagoComision').on('submit.comisionPago', '#formPagoComision', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $('#btnGuardarComision').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: 'api/api_cotizaciones.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        let detalle = `Comisión: $${Number(res.comision_bruta).toFixed(2)} · Descuento préstamo: $${Number(res.descuento_prestamo).toFixed(2)} · Neto: $${Number(res.neto_pagar).toFixed(2)} · Saldo restante: $${Number(res.saldo_prestamo).toFixed(2)}`;
                        Swal.fire({icon: 'success', title: 'Comisión registrada', text: detalle, confirmButtonText: 'Aceptar'});
                        $('#modalComision').modal('hide');
                        window.cargarHistorial();
                    } else { Swal.fire('Error', res.msg, 'error'); }
                },
                complete: function() { $('#btnGuardarComision').prop('disabled', false).text('Registrar Comisión'); }
            });
        });

        window.eliminarCotizacion = function(id) {
            Swal.fire({ title: '¿Eliminar Cotización?', text: "Esta acción es irreversible", icon: 'warning', showCancelButton: true }).then((result) => {
                if(result.isConfirmed) {
                    $.post('api/api_cotizaciones.php', { accion: 'eliminar', id: id }, function(res) {
                        if(res.success) window.cargarHistorial(); else Swal.fire('Error', res.msg, 'error');
                    }, 'json');
                }
            });
        };

        window.verDetalleHistorialCotizacion = function(id, subtotal, descuento, iva, total, referencias, fotos, fecha, tasa, usd, banco, tipo_cot, tlf_cliente, nombre_cliente) {
            $('#det_modal_subtotal').text(tipo_cot === 'MUESTRA' ? '0.00 $' : window.formatoMoneda(subtotal) + ' $');
            $('#det_modal_descuento').text(tipo_cot === 'MUESTRA' ? '-0.00 $' : '-' + window.formatoMoneda(descuento) + ' $');
            $('#det_modal_iva').text(tipo_cot === 'MUESTRA' ? '0.00 $' : window.formatoMoneda(iva) + ' $');
            $('#det_modal_total').text(tipo_cot === 'MUESTRA' ? '0.00 $' : window.formatoMoneda(total) + ' $');

            if (referencias !== '' || fecha !== '') {
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
                if (maxLen === 0) maxLen = 1;

                for(let i=0; i<maxLen; i++) {
                    let f = arrFecha[i] || '-';
                    let t = arrTasa[i] ? arrTasa[i] + ' Bs' : '-';
                    let numLimpioUsd = arrUsd[i] ? arrUsd[i].replace(/[^\d.-]/g, '') : 0;
                    let u = arrUsd[i] ? '$ ' + parseFloat(numLimpioUsd).toFixed(2) : '-';
                    let b = arrBanco[i] || '-';
                    let r = arrRef[i] || '-';
                    let btnEditMaster = window.esUsuarioMaster()
                        ? `<button type="button" class="btn btn-sm btn-warning fw-bold py-0 px-2 ms-1" onclick="window.abrirModalEditarPagoMaster(${id}, ${i})" title="Editar este pago en la pasarela"><i class="bi bi-pencil-square"></i> Editar</button>`
                        : '';
                    let imgCell = (i === 0) ? `<td rowspan="${maxLen}" class="align-middle">${htmlFotosGral || 'Sin fotos'}</td>` : '';
                    htmlPagos += `<tr><td>${f}</td><td>${t}</td><td class="text-success fw-bold">${u}</td><td>${b}</td><td>${r} ${btnEditMaster}</td>${imgCell}</tr>`;
                }
                $('#tbody_pagos_multiples').html(htmlPagos);
                
                let base_url = window.location.href.split('portal_ventas')[0] + 'portal_ventas/';
                let url_recibo_general = `recibo_pago.php?id=${id}&abono=total`;
                let full_url_recibo = base_url + url_recibo_general;
                
                let msg = `Hola *${nombre_cliente}*, puedes descargar o ver tu recibo general de pagos actualizado en *Unifuerte C.A.* ingresando al siguiente enlace: ${full_url_recibo}`;
                let waLink = `window.abrirWhatsAppDesdeOjito('${tlf_cliente}', '${encodeURIComponent(msg)}')`;

                $('#box_comprobantes_vista .d-flex.justify-content-between').html(`
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center"><i class="bi bi-receipt me-2"></i> Historial de Pagos</h6>
                    <div>
                        <button type="button" onclick="${waLink}" class="btn btn-sm fw-bold text-white me-1 shadow-sm" style="background-color: #25D366;"><i class="bi bi-whatsapp"></i> Enviar WA</button>
                        <a href="${url_recibo_general}" target="_blank" class="btn btn-sm btn-success fw-bold text-white shadow-sm"><i class="bi bi-printer"></i> Imprimir Recibo General</a>
                    </div>
                `);
            } else { $('#box_comprobantes_vista').hide(); }

            $.get('api/api_cotizaciones.php', { accion: 'detalles', id_cotizacion: id }, function(res) {
                if(res.success) {
                    let html = '';
                    res.data.forEach(d => {
                        let detalleText = d.detalle_diseno ? `<span class="badge bg-light text-secondary border border-secondary">${d.detalle_diseno}</span>` : `<span class="text-muted small">-</span>`;
                        let stLinea = tipo_cot === 'MUESTRA' ? '0.00' : window.formatoMoneda(d.subtotal_linea);
                        let pUnit = tipo_cot === 'MUESTRA' ? '0.00' : window.formatoMoneda(d.precio_unitario);
                        
                        html += `<tr>
                                    <td><span class="fw-bold">${d.tipo_prenda}</span><br><small class="text-muted">${d.genero}</small></td>
                                    <td>${detalleText}</td>
                                    <td class="text-center fw-bold">${d.cantidad}</td>
                                    <td class="text-end">${pUnit} $</td>
                                    <td class="text-end fw-bold text-dark">${stLinea} $</td>
                                </tr>`;
                    });
                    $('#detallesCotizacionTablaBody').html(html);
                    new bootstrap.Modal(document.getElementById('modalVerDetalleCotizacion')).show();
                } else { Swal.fire('Error', 'No se pudieron cargar los detalles', 'error'); }
            }, 'json');
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

        window.cargarCotizacionParaEditar = function(id_cot, id_cli, nombre_cli) {
            $.get('api/api_cotizaciones.php', { accion: 'obtener', id: id_cot }, function(res) {
                if(res.success) {
                    $('#idClienteSeleccionado').val(id_cli);
                    
                    let tipoActual = res.data.tipo_cotizacion;
                    let badgeExtra = tipoActual === 'MUESTRA' ? ' <span class="badge bg-info text-dark ms-2"><i class="bi bi-box2-heart"></i> MUESTRA FÍSICA</span>' : '';
                    
                    $('#lblClienteSeleccionado').html(`${nombre_cli} ${badgeExtra} <button type="button" class="btn btn-sm btn-outline-dark ms-2 btn-cambiar-cliente btn-bloqueable" title="Cambiar Cliente"><i class="bi bi-arrow-repeat"></i> Cambiar</button>`);
                    $('#id_edit_cotizacion').val(id_cot);
                    $('#badgeEstadoEdicion').show();

                    $('#cot_contribuyente').prop('checked', res.data.contribuyente_especial == 1);
                    $('#lic_contribuyente').prop('checked', res.data.contribuyente_especial == 1);

                    if(res.data.aplica_iva !== undefined) {
                        if($('#cot_aplica_iva').length) $('#cot_aplica_iva').prop('checked', res.data.aplica_iva == 1);
                        if($('#lic_aplica_iva').length) $('#lic_aplica_iva').prop('checked', res.data.aplica_iva == 1);
                    }
                    if(res.data.porcentaje_descuento_manual !== undefined) {
                        if($('#cot_desc_manual').length) $('#cot_desc_manual').val(res.data.porcentaje_descuento_manual);
                        if($('#lic_desc_manual').length) $('#lic_desc_manual').val(res.data.porcentaje_descuento_manual);
                    }
                    if(res.data.servicio_logistico !== undefined) {
                        if($('#cot_desc_logistica').length) $('#cot_desc_logistica').val(res.data.servicio_logistico);
                        if($('#lic_desc_logistica').length) $('#lic_desc_logistica').val(res.data.servicio_logistico);
                    }
                    if(res.data.flete !== undefined) {
                        if($('#cot_flete').length) $('#cot_flete').val(res.data.flete);
                        if($('#lic_flete').length) $('#lic_flete').val(res.data.flete);
                    }

                    $('#cot_notas').val(res.data.notas);
                    $('#lic_notas').val(res.data.notas);
                    
                    $('.custom-check-small').removeClass('active-check');
                    if(res.data.id_forma_pago) {
                        $(`input[name="id_forma_pago"][value="${res.data.id_forma_pago}"]`).prop('checked', true).closest('.custom-check-small').addClass('active-check');
                        $(`input[name="lic_forma_pago"][value="${res.data.id_forma_pago}"]`).prop('checked', true).closest('.custom-check-small').addClass('active-check');
                    }
                    if(res.data.metodo_entrega) {
                        $(`input[name="metodo_entrega"][value="${res.data.metodo_entrega}"]`).prop('checked', true).closest('.custom-check-small').addClass('active-check');
                        $(`input[name="lic_metodo_entrega"][value="${res.data.metodo_entrega}"]`).prop('checked', true).closest('.custom-check-small').addClass('active-check');
                    }
                    
                    $('#id_edit_tipo_cotizacion').val(tipoActual);
                    window.inyectarTipoEnFormularios(tipoActual);
                    
                    if (tipoActual === 'LICITACION') {
                        carritoLicitacion = res.detalles.map(d => {
                            let specsGuardadas = [];
                            try { specsGuardadas = JSON.parse(d.especificaciones_tallas); } catch(e) {}
                            return {
                                id_detalle: d.ID, id_producto: 0, nombre: d.tipo_prenda, genero: 'No especificado',
                                precio: parseFloat(d.precio_unitario), cantidad: parseInt(d.cantidad), subtotal: parseFloat(d.subtotal_linea), 
                                detalle_diseno: '', categoria: 'Licitacion', specs: specsGuardadas
                            };
                        });
                        $('#lic_ssst').val(res.data.nro_ssst || '');
                        $('#lic_nro_cotizacion').val(res.data.nro_cotizacion || ''); 
                        window.actualizarTablaLicitacion();
                        $('#btnMenuCotizadorLicitaciones').click();

                    } else {
                        carritoMemoria = res.detalles.map(d => {
                            let catAsumida = (d.detalle_diseno && d.detalle_diseno.includes('Ubicación:')) ? 'Arte Final' : (d.categoria || 'Prenda');
                            let vinculoPuroGuardado = ''; let posicionPuraGuardada = d.detalle_diseno;
                            let telaExtraida = ''; let tallaExtraida = '';
                            
                            if (catAsumida === 'Arte Final' && d.detalle_diseno.includes('[Para: ')) {
                                let partes = d.detalle_diseno.split('] - Ubicación: ');
                                vinculoPuroGuardado = partes[0].replace('[Para: ', '');
                                posicionPuraGuardada = partes[1] || '';
                            } else if (catAsumida !== 'Arte Final') {
                                let partes = d.detalle_diseno.split(' | ');
                                partes.forEach(p => {
                                    if (p.startsWith('Talla: ')) tallaExtraida = p.replace('Talla: ', '');
                                    if (p.startsWith('Tela: ')) telaExtraida = p.replace('Tela: ', '');
                                });
                            }
                            let specsGuardadas = [];
                            try { specsGuardadas = JSON.parse(d.especificaciones_tallas); } catch(e) {}

                            return {
                                id_detalle: d.ID, idUnico: d.id_producto + '_' + d.genero + '_' + (d.detalle_diseno ? d.detalle_diseno.replace(/\s+/g, '') : ''),
                                id_producto: d.id_producto, nombre: d.tipo_prenda, genero: d.genero, talla_guardada: tallaExtraida,
                                tela_guardada: telaExtraida, precio: parseFloat(d.precio_unitario), cantidad: parseInt(d.cantidad),
                                subtotal: parseFloat(d.subtotal_linea), detalle_diseno: d.detalle_diseno || '', posicion_pura: posicionPuraGuardada,
                                vinculo_puro: vinculoPuroGuardado, categoria: catAsumida, specs: specsGuardadas
                            };
                        });
                        window.actualizarResumenCarrito();
                        $('#btnMenuCotizador').click();
                    }
                    
                    window.scrollTo({ top: 0, behavior: 'instant' });
                    
                    let estadoLC = res.data.estado_pago.toLowerCase();
                    let bloqueado = (estadoLC === 'pago inicial' || estadoLC === 'pago medio' || estadoLC === 'pagado');
                    
                    if (tipoActual === 'MUESTRA') bloqueado = false; 

                    if (bloqueado && (typeof PERMISO_EDITAR_BLOQUEADAS === 'undefined' || !PERMISO_EDITAR_BLOQUEADAS)) {
                        $('.btn-bloqueable').prop('disabled', true); 
                        Swal.fire('Modo Edición Parcial', 'La cotización tiene un pago registrado. Solo puedes editar notas, datos de cliente y envío.', 'info');
                    } else {
                        if (bloqueado && PERMISO_EDITAR_BLOQUEADAS) {
                            Swal.fire('Modo Edición Forzada', 'La cotización tiene pagos, pero posees el privilegio especial para editarla sin límites.', 'warning');
                        }
                        $('.btn-bloqueable').prop('disabled', false); 
                    }
                }
            }, 'json');
        };

        window.enviarALogistica = function(id_cot) {
            if (typeof window.abrirModalEspecificarDetalles !== 'function') {
                return Swal.fire('Error', 'No se pudo abrir la regla azul. Recarga el portal e intenta nuevamente.', 'error');
            }
            window.abrirModalEspecificarDetalles(id_cot, 'Cotización', true);
        };

        // ==============================================================================
        // OPCIONES DE TALLA CON SOPORTE AUTOMÁTICO PARA PANTALONES (CABALLERO Y DAMA)
        // ==============================================================================
        window.obtenerOpcionesTalla = function(genero, tipoPrenda = '') {
            const n = String(tipoPrenda || '').toLowerCase();
            if (n.includes('pantalon') || n.includes('pantalón') || n.includes('jean')) {
                if (genero === 'Caballero') {
                    return '<optgroup label="Normales Caballero (28-36)"><option value="28">28</option><option value="30">30</option><option value="32" selected>32</option><option value="34">34</option><option value="36">36</option></optgroup><optgroup label="Extras Caballero (38-44)"><option value="38">38</option><option value="40">40</option><option value="42">42</option><option value="44">44</option></optgroup>';
                }
                if (genero === 'Dama') {
                    return '<optgroup label="Normales Dama (8-14)"><option value="8">8</option><option value="10">10</option><option value="12" selected>12</option><option value="14">14</option></optgroup><optgroup label="Extras Dama (16-22)"><option value="16">16</option><option value="18">18</option><option value="20">20</option><option value="22">22</option></optgroup>';
                }
            }

            if(genero === 'Adulto (Dama o Caballero)') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option>';
            if(genero === 'Infantil (Unisex)') return '<option value="4">4</option><option value="6" selected>6</option><option value="8">8</option><option value="10">10</option>';
            if(genero === 'Juvenil (Unisex)') return '<option value="12">12</option><option value="14" selected>14</option><option value="16">16</option>';
            
            if(genero === 'Dama') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option>';
            if(genero === 'Caballero') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option>';
            if(genero === 'Niña' || genero === 'Niño') return '<option value="4">4</option><option value="6" selected>6</option><option value="8">8</option><option value="10">10</option><option value="12">12</option><option value="14">14</option><option value="16">16</option>';
            
            return '<option value="U" selected>Única / Ajustable</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option>';
        };

        window.generarInputTelas = function(telaActual) {
            const safeTela = String(telaActual || '').replace(/"/g, '&quot;');
            return `<input type="text" list="lista_telas_inteligente" class="form-control form-control-sm med-tela spec-tela border-primary" value="${safeTela}" placeholder="Buscar tela..." required>`;
        };

        window.chequearCotizacionesPendientes = function() {
            $.get('api/api_ordenes.php', { accion: 'listar_cotizaciones_aptas' }, function(res) {
                if(res && res.success) {
                    let count = res.data.length;
                    if(count > 0) {
                        $('#badge_pendientes_bell, #badge_pendientes_menu').text(count).removeClass('d-none');
                        let html = '';
                        res.data.forEach(c => {
                            let numLimpiado = window.extraerCodigoUnicoCotizacion(c.nro_cotizacion, c.ID);
                            let nroOF = 'OF-' + numLimpiado;

                            html += `<li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div><h6 class="m-0 fw-bold" style="color: #0B4C8C;"><i class="bi bi-file-earmark-check text-success"></i> ${nroOF} <small class="text-muted">(${c.nro_cotizacion})</small></h6><small class="text-muted">Cliente: <b>${c.cliente}</b></small></div>
                                <button class="btn btn-sm text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="window.procesarDesdeNotificacion(${c.ID})"><i class="bi bi-hammer"></i> Crear Orden</button>
                            </li>`;
                        });
                        $('#lista_cotizaciones_pendientes').html(html);
                    } else {
                        $('#badge_pendientes_bell, #badge_pendientes_menu').addClass('d-none');
                        $('#lista_cotizaciones_pendientes').html('<li class="list-group-item text-center text-muted py-4">No hay cotizaciones pendientes por procesar. Todo está al día. <i class="bi bi-check-all text-success fs-5 d-block mt-1"></i></li>');
                    }
                }
            }, 'json');
        };

        window.cargarListaOrdenes = function() {
            const secuenciaActual = ++secuenciaFiltroOrdenesFabrica;
            if (solicitudFiltroOrdenesFabrica) solicitudFiltroOrdenesFabrica.abort();
            $('#tablaOrdenesBody').html('<tr><td colspan="6" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
            
            let idMiUsuario = $('#id_usuario_actual_global').val();
            let permisoVerTodas = $('#permiso_ver_todas_ordenes').val() === '1' || window.esUsuarioMaster() || JS_tieneVector(54);
            let toggleVerTodas = $('#switch_ver_todas_ordenes').length > 0 ? $('#switch_ver_todas_ordenes').is(':checked') : true;

            let reqData = {
                accion: 'listar_ordenes',
                busqueda: $('#buscarOrdenesFabrica').val() || ''
            };
            if (!permisoVerTodas || !toggleVerTodas) {
                reqData.id_vendedor = idMiUsuario;
            }

            solicitudFiltroOrdenesFabrica = $.get('api/api_ordenes.php', reqData, function(res) {
                if (secuenciaActual !== secuenciaFiltroOrdenesFabrica) return;
                if(res && res.success) {
                    let html = '';
                    let ordenesFiltradas = res.data;
                    
                    if (!permisoVerTodas || !toggleVerTodas) {
                        ordenesFiltradas = res.data.filter(o => {
                            let idVend = o.id_usuario_vendedor || o.id_vendedor || o.vendedor_id;
                            if (idVend !== undefined && idVend !== null) {
                                return String(idVend) === String(idMiUsuario);
                            }
                            
                            let miNombre = $('.user-box .nav-text').first().text().trim().toLowerCase();
                            let nombreVendApi = o.vendedor_nombre || o.vendedor || o.asesor || o.cliente_vendedor || '';
                            
                            if (nombreVendApi !== '' && miNombre !== '') {
                                return nombreVendApi.toLowerCase().includes(miNombre) || miNombre.includes(nombreVendApi.toLowerCase());
                            }
                            return false;
                        });
                    }

                    ordenesFiltradas.forEach(o => {
                        let badgeExp = (o.es_express == 1 || o.es_express == '1') ? '<span class="badge bg-warning text-dark ms-1">Express</span>' : '';
                        
                        let btnEditarOrden = (o.puede_alterar || window.esUsuarioMaster() || JS_tieneVector(53))
                            ? `<button class="btn btn-sm btn-outline-primary fw-bold ms-1" onclick="window.abrirModalEditarOrden(${o.ID})" title="Editar Orden de Fábrica"><i class="bi bi-pencil-square"></i> Editar</button>`
                            : '';

                        let btnDescargarFotos = '';
                        if (o.tiene_fotos) {
                            btnDescargarFotos = `<a href="api/api_ordenes.php?accion=descargar_fotos&id=${o.ID}" target="_blank" class="btn btn-sm btn-info text-dark fw-bold ms-1" title="Descargar Fotos Originales"><i class="bi bi-file-zip"></i></a>`;
                        }
                        
                        let btnRevertir = JS_tieneVector(43) ? `<button class="btn btn-sm btn-outline-warning ms-1" onclick="window.revertirOrden(${o.ID})" title="Revertir Orden hacia Ventas"><i class="bi bi-arrow-counterclockwise"></i></button>` : '';
                        let btnEliminar = !o.id_cotizacion && JS_tieneVector(12) ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="window.eliminarOrden(${o.ID})"><i class="bi bi-trash"></i></button>` : '';
                        
                        html += `<tr>
                            <td class="fw-bold text-primary">${o.nro_orden_fabrica}</td>
                            <td>${o.nro_cotizacion || '-'}</td>
                            <td>${o.fecha_emision}</td>
                            <td class="fw-bold">${o.fecha_entrega}</td>
                            <td>${o.cliente} ${badgeExp}</td>
                            <td class="text-center text-nowrap">
                                <a href="imprimir_orden.php?id=${o.ID}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-printer"></i> Imprimir</a>
                                ${btnEditarOrden}
                                ${btnDescargarFotos}
                                ${btnRevertir}
                                ${btnEliminar}
                            </td>
                        </tr>`;
                    });
                    
                    if (ordenesFiltradas.length === 0) {
                        html = '<tr><td colspan="6" class="text-center text-muted fw-bold">No hay órdenes que coincidan con la búsqueda.</td></tr>';
                    }
                    
                    $('#tablaOrdenesBody').html(html);
                } else {
                    $('#tablaOrdenesBody').html('<tr><td colspan="6" class="text-center text-danger">' + (res ? res.msg : 'Error al cargar') + '</td></tr>');
                }
            }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                if (textStatus === 'abort' || secuenciaActual !== secuenciaFiltroOrdenesFabrica) return;
                $('#tablaOrdenesBody').html('<tr><td colspan="6" class="text-center text-danger">Error de red al filtrar órdenes.</td></tr>');
                console.error("Error cargando órdenes:", textStatus, errorThrown, jqXHR.responseText);
            }).always(function() {
                if (secuenciaActual === secuenciaFiltroOrdenesFabrica) {
                    solicitudFiltroOrdenesFabrica = null;
                }
            });
        };

        $(document).off('input.filtroOrdenesFabrica', '#buscarOrdenesFabrica')
            .on('input.filtroOrdenesFabrica', '#buscarOrdenesFabrica', function() {
                clearTimeout(temporizadorFiltroOrdenesFabrica);
                temporizadorFiltroOrdenesFabrica = setTimeout(window.cargarListaOrdenes, 300);
            });
        $(document).off('click.filtroOrdenesFabrica', '#btnFiltrarOrdenesFabrica')
            .on('click.filtroOrdenesFabrica', '#btnFiltrarOrdenesFabrica', function() {
                clearTimeout(temporizadorFiltroOrdenesFabrica);
                window.cargarListaOrdenes();
            });
        $(document).off('keydown.filtroOrdenesFabrica', '#buscarOrdenesFabrica')
            .on('keydown.filtroOrdenesFabrica', '#buscarOrdenesFabrica', function(evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                    clearTimeout(temporizadorFiltroOrdenesFabrica);
                    window.cargarListaOrdenes();
                }
            });

        window.abrirModalNuevaOrden = function() {
            $('#formGuardarOrden')[0].reset();
            $('#btnGuardarOrdenFinal').prop('disabled', true);
            if ($('#ordenEstadoValidacion').length) {
                $('#ordenEstadoValidacion').removeClass('alert-danger alert-success').addClass('alert-info')
                    .text('Selecciona una cotización para cargar sus prendas, reglas, telas y referencias.');
            }
            window.archivosOrdenMemoria = [];
            $('#lista_fotos_seleccionadas').empty(); 
            $('#fotos_previas_container').empty();
            $('#ordenFotosPreview').empty();
            $('#tbody_prendas_orden').empty();
            $('#tbody_artes_orden').empty();
            $('#tbody_medidas_personales').empty();
            $('#tbody_sastreria').empty();
            $('#seccion_asignacion_tallas').hide();
            $('#seccion_sastreria').hide();
            $('#seccion_artes_posiciones').hide();
            
            $.get('api/api_ordenes.php', { accion: 'listar_cotizaciones_aptas' }, function(res) {
                let opciones = '<option value="">Seleccione una Cotización...</option>';
                if(res && res.data) {
                    res.data.forEach(c => { opciones += `<option value="${c.ID}">${c.nro_cotizacion} - ${c.cliente}</option>`; });
                }
                $('#select_cotizacion_orden').html(opciones);
                new bootstrap.Modal(document.getElementById('modalNuevaOrden')).show();
            }, 'json');
        };

        window.abrirModalEditarOrden = function(id_orden) {
            Swal.fire({ title: 'Cargando orden...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
            $.get('api/api_ordenes.php', { accion: 'obtener_orden_completa', id_orden: id_orden }, function(res) {
                Swal.close();
                if(res.success) {
                    let o = res.orden;
                    window.archivosOrdenEditMemoria = [];
                    $('#edit_fotos_nuevas_container').empty();
                    $('input[name="orden_foto_extra[]"]').val('');

                    $('#edit_id_orden').val(o.ID);
                    $('#edit_id_cotizacion').val(o.id_cotizacion);
                    $('#edit_orden_cliente').val(o.cliente_nombre + ' (Cot: ' + o.nro_cotizacion + ')');
                    
                    if(o.fecha_entrega && !o.fecha_entrega.includes('0000')) {
                        $('#edit_orden_fecha_entrega').val(o.fecha_entrega);
                    }
                    
                    $('#edit_orden_express').prop('checked', o.es_express == 1);
                    $('#edit_orden_parciales').prop('checked', o.acepta_entregas_parciales == 1);
                    $('#edit_orden_notas_globales').val(o.descripcion);
                    
                    $('#edit_fotos_previas_container').empty();
                    if (o.foto && o.foto.trim() !== '') {
                        let fArr = o.foto.split(',');
                        let htmlFotos = '';
                        fArr.forEach(f => {
                            if(f.trim() !== '') {
                                htmlFotos += `
                                <div class="position-relative d-inline-block me-3 mb-3 foto-wrapper">
                                    <a href="../images/ordenes/${f}" target="_blank">
                                        <img src="../images/ordenes/${f}" style="height: 60px; border-radius: 4px; border: 1px solid #ccc; object-fit: cover;">
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="padding: 2px 6px; font-size: 0.6rem;" onclick="window.eliminarFotoOrden(this, ${o.ID}, '${f}')" title="Eliminar Foto"><i class="bi bi-x-lg"></i></button>
                                </div>`;
                            }
                        });
                        $('#edit_fotos_previas_container').html(htmlFotos);
                    }

                    let htmlPrendas = '';
                    res.detalles.forEach(d => {
                        let opcionesTela = '<option value="">Seleccionar Tela...</option>';
                        res.telas.forEach(t => { 
                            let nombreTelaStr = t.nombre + ' (' + t.color + ')';
                            let selected = (d.id_materia == t.ID) ? 'selected' : '';
                            opcionesTela += `<option value="${t.ID}" ${selected}>${nombreTelaStr}</option>`; 
                        });
                        htmlPrendas += `<tr class="fila-prenda-edit">
                            <td><input type="text" class="form-control form-control-sm fw-bold edit-tipo bg-light" value="${d.tipo_prenda}" readonly></td>
                            <td><input type="number" class="form-control form-control-sm text-center edit-cant border-primary fw-bold" value="${d.cantidad}"></td>
                            <td><select class="form-select form-select-sm edit-tela border-primary">${opcionesTela}</select></td>
                        </tr>`;
                    });
                    $('#edit_tbody_prendas_orden').html(htmlPrendas);

                    let htmlArtes = '';
                    if(res.posiciones && res.posiciones.length > 0) {
                        $('#edit_seccion_artes_posiciones').show();
                        res.posiciones.forEach(pos => {
                            let opcionesVinculoArte = '<option value="">Sin vincular</option>';
                            res.detalles.forEach(d => {
                                let selected = (d.tipo_prenda === pos.tipo_prenda) ? 'selected' : '';
                                opcionesVinculoArte += `<option value="${d.tipo_prenda}" ${selected}>${d.tipo_prenda}</option>`;
                            });
                            
                            let cantMatch = pos.observacion.match(/Cant:\s*(\d+)/);
                            let posCant = cantMatch ? cantMatch[1] : 1;

                            htmlArtes += `<tr class="fila-arte-edit">
                                <td><input type="text" class="form-control form-control-sm fw-bold edit-arte-nombre border-danger" value="${pos.elemento}"></td>
                                <td><input type="number" class="form-control form-control-sm text-center edit-arte-cant border-danger fw-bold" value="${posCant}"></td>
                                <td><select class="form-select form-select-sm edit-arte-vinculo border-danger">${opcionesVinculoArte}</select></td>
                                <td><input type="text" class="form-control form-control-sm edit-arte-ubi border-danger fw-bold" value="${pos.ubicacion}" placeholder="Ej: Espalda Alta"></td>
                            </tr>`;
                        });
                        $('#edit_tbody_artes_orden').html(htmlArtes);
                    } else { $('#edit_seccion_artes_posiciones').hide(); }

                    let htmlMedidas = '';
                    let opcionesPrendaGral = '';
                    res.detalles.forEach(d => { opcionesPrendaGral += `<option value="${d.tipo_prenda}">${d.tipo_prenda}</option>`; });

                    window.editPrendasOpciones = opcionesPrendaGral;

                    res.medidas.forEach(m => {
                        let gen = m.genero || 'Dama';
                        let opcionesTallas = window.obtenerOpcionesTalla(gen, m.tipo_prenda);
                        if (m.talla_asignada && !opcionesTallas.includes(`value="${m.talla_asignada}"`)) {
                            opcionesTallas += `<option value="${m.talla_asignada}" selected>${m.talla_asignada}</option>`;
                        } else {
                            opcionesTallas = opcionesTallas.replace(`value="${m.talla_asignada}"`, `value="${m.talla_asignada}" selected`);
                        }
                        
                        let currPrendaOpt = opcionesPrendaGral.replace(`value="${m.tipo_prenda}"`, `value="${m.tipo_prenda}" selected`);

                        htmlMedidas += `<tr class="fila-medida-edit">
                            <td><select class="form-select form-select-sm edit-med-prenda bg-light" style="width: 150px;" onchange="window.actualizarTallaMedidaEdit($(this).closest('tr').find('.edit-med-genero')[0])">${currPrendaOpt}</select></td>
                            <td><input type="number" class="form-control form-control-sm text-center edit-med-cant border-primary fw-bold" value="${m.cantidad}" min="1" required></td>
                            <td><input type="text" class="form-control form-control-sm edit-med-nombre border-primary fw-bold" value="${m.nombre === '-' ? '' : m.nombre}" placeholder="S/N"></td>
                            <td>
                                <select class="form-select form-select-sm edit-med-genero bg-light" onchange="window.actualizarTallaMedidaEdit(this)">
                                    <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
                                    <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
                                    <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
                                    <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>
                                    <option value="U" ${gen==='U'?'selected':''}>U</option>
                                </select>
                            </td>
                            <td><select class="form-select form-select-sm edit-med-talla border-primary fw-bold" required>${opcionesTallas}</select></td>
                            <td><input type="text" class="form-control form-control-sm edit-med-tela bg-light" value="${m.tela}" placeholder="Tela..."></td>
                            <td><input type="text" class="form-control form-control-sm edit-med-obs" value="${m.observacion}" placeholder="Ej: Con bolsillo"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                        </tr>`;
                    });
                    $('#edit_tbody_medidas_personales').html(htmlMedidas);

                    let htmlSas = '';
                    res.medidas.forEach(m => {
                        if(parseFloat(m.hombro) > 0 || parseFloat(m.cintura) > 0 || parseFloat(m.largo_pantalon) > 0) {
                            let textoPecho = (m.genero === 'Caballero' || m.genero === 'Niño') ? 'Tórax' : 'Busto';
                            let currPrendaOpt = opcionesPrendaGral.replace(`value="${m.tipo_prenda}"`, `value="${m.tipo_prenda}" selected`);
                            
                            htmlSas += `<tr class="fila-sastreria-edit">
                                <td><select class="form-select form-select-sm edit-sas-prenda bg-light" style="width: 140px;">${currPrendaOpt}</select></td>
                                <td><input type="text" class="form-control form-control-sm edit-sas-nombre border-primary fw-bold" value="${m.nombre}" required></td>
                                ${generarCeldasSastreriaExistente(m.tipo_prenda, textoPecho, m)}
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                            </tr>`;
                        }
                    });
                    $('#edit_tbody_sastreria').html(htmlSas);

                    new bootstrap.Modal(document.getElementById('modalEditarOrden')).show();
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        };

        // PREVISUALIZAR FOTOS AL EDITAR ORDEN
        window.renderizarFotosNuevasEdit = function() {
            if ($('#edit_fotos_nuevas_container').length === 0) {
                $('#edit_fotos_previas_container').after('<div id="edit_fotos_nuevas_container" class="mt-2 d-flex flex-wrap gap-2 border-top pt-2"></div>');
            }
            let html = '';
            window.archivosOrdenEditMemoria.forEach((file, index) => {
                let isPdf = file.name.toLowerCase().endsWith('.pdf');
                let content = '';
                
                if (isPdf) {
                    content = `<div class="d-flex align-items-center justify-content-center" style="height: 60px; width: 60px; border-radius: 4px; border: 2px dashed #198754; background:#f8f9fa;"><i class="bi bi-file-pdf-fill text-danger fs-3"></i></div>`;
                } else {
                    let objUrl = URL.createObjectURL(file);
                    content = `<img src="${objUrl}" style="height: 60px; width: 60px; border-radius: 4px; border: 2px dashed #198754; object-fit: cover;">`;
                }
                
                html += `
                <div class="position-relative d-inline-block me-3 mb-3 foto-nueva-wrapper text-center">
                    ${content}
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="padding: 2px 6px; font-size: 0.6rem;" onclick="window.eliminarFotoNuevaEdit(${index})" title="Quitar de la lista"><i class="bi bi-x-lg"></i></button>
                    <div style="font-size: 0.6rem; color: #198754; font-weight: bold; margin-top: 2px; max-width: 60px;" class="text-truncate" title="${file.name}">NUEVA</div>
                </div>`;
            });
            $('#edit_fotos_nuevas_container').html(html);
        };

        window.eliminarFotoNuevaEdit = function(index) {
            window.archivosOrdenEditMemoria.splice(index, 1);
            window.renderizarFotosNuevasEdit();
        };

        $(document).on('change', 'input[name="orden_foto_extra[]"]', function(e) {
            let files = e.target.files;
            if(files.length > 0) {
                for(let i=0; i<files.length; i++) {
                    window.archivosOrdenEditMemoria.push(files[i]);
                }
                window.renderizarFotosNuevasEdit();
            }
            $(this).val(''); 
        });

        // ELIMINAR FOTO ESPECÍFICA EN EDICIÓN
        window.eliminarFotoOrden = function(btn, id_orden, nombre_foto) {
            Swal.fire({
                title: '¿Eliminar foto?',
                text: "Se borrará permanentemente de la orden.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let $btn = $(btn);
                    $btn.prop('disabled', true);
                    $.post('api/api_ordenes.php', { accion: 'eliminar_foto_orden', id_orden: id_orden, foto: nombre_foto }, function(res) {
                        if (res.success) {
                            $btn.closest('.foto-wrapper').remove();
                        } else {
                            Swal.fire('Error', res.msg, 'error');
                            $btn.prop('disabled', false);
                        }
                    }, 'json');
                }
            });
        };

        window.actualizarTallaMedidaEdit = function(selectElem) {
            let $tr = $(selectElem).closest('tr');
            let genero = $tr.find('.edit-med-genero').val();
            let prenda = $tr.find('.edit-med-prenda').val() || '';
            let $tallaSelect = $tr.find('.edit-med-talla');
            $tallaSelect.html(window.obtenerOpcionesTalla(genero, prenda));
        };

        window.agregarFilaMedidaEdit = function() {
            let primeraPrenda = $($('<div>').html(window.editPrendasOpciones || '').find('option')[0]).val() || '';
            let html = `<tr class="fila-medida-edit">
                <td><select class="form-select form-select-sm edit-med-prenda bg-light" style="width: 150px;" onchange="window.actualizarTallaMedidaEdit(this)">${window.editPrendasOpciones}</select></td>
                <td><input type="number" class="form-control form-control-sm text-center edit-med-cant border-primary fw-bold" value="1" min="1" required></td>
                <td><input type="text" class="form-control form-control-sm edit-med-nombre border-primary fw-bold" placeholder="Nombre"></td>
                <td><select class="form-select form-select-sm edit-med-genero bg-light" onchange="window.actualizarTallaMedidaEdit(this)"><option value="Dama">Dama</option><option value="Caballero">Caballero</option><option value="Niña">Niña</option><option value="Niño">Niño</option><option value="U">U</option></select></td>
                <td><select class="form-select form-select-sm edit-med-talla border-primary fw-bold" required>${window.obtenerOpcionesTalla('Dama', primeraPrenda)}</select></td>
                <td><input type="text" class="form-control form-control-sm edit-med-tela bg-light" placeholder="Tela..."></td>
                <td><input type="text" class="form-control form-control-sm edit-med-obs" placeholder="Ej: Con bolsillo"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
            </tr>`;
            $('#edit_tbody_medidas_personales').append(html);
        };

        window.agregarFilaSastreriaEdit = function() {
            let html = `<tr class="fila-sastreria-edit">
                <td><select class="form-select form-select-sm edit-sas-prenda bg-light" style="width: 140px;">${window.editPrendasOpciones}</select></td>
                <td><input type="text" class="form-control form-control-sm edit-sas-nombre border-primary fw-bold" placeholder="Nombre exacto" required></td>
                ${generarCeldasSastreriaExistente('', 'Pecho', {})}
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
            </tr>`;
            $('#edit_tbody_sastreria').append(html);
        };

        function generarCeldasSastreriaExistente(tipo, txtPecho, m) {
            let isInf = (tipo.toLowerCase().includes('pantalón') || tipo.toLowerCase().includes('pantalon') || tipo.toLowerCase().includes('mono') || tipo.toLowerCase().includes('short'));
            let isSup = (tipo.toLowerCase().includes('camisa') || tipo.toLowerCase().includes('chemise') || tipo.toLowerCase().includes('chaqueta') || tipo.toLowerCase().includes('franela'));
            
            let bgSup = isInf && !isSup ? 'background-color:#f8f9fa;' : 'background-color:#fff;';
            let bgInf = isSup && !isInf ? 'background-color:#f8f9fa;' : 'background-color:#fff;';
            let roSup = isInf && !isSup ? 'readonly' : '';
            let roInf = isSup && !isInf ? 'readonly' : '';

            return `
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-hom" value="${m.hombro||''}" style="${bgSup}" ${roSup}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-bus" value="${m.busto_torax||''}" style="${bgSup}" ${roSup} placeholder="${txtPecho}"></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-cin" value="${m.cintura||''}"></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-cad" value="${m.cadera||''}"></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-lcam" value="${m.largo_camisa||''}" style="${bgSup}" ${roSup}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-lbra" value="${m.largo_brazo||''}" style="${bgSup}" ${roSup}></td>
                <td style="border-left: 2px solid #FF7518;"><input type="number" step="0.5" class="form-control form-control-sm edit-sas-lpan" value="${m.largo_pantalon||''}" style="${bgInf}" ${roInf}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-tiro" value="${m.tiro_total||''}" style="${bgInf}" ${roInf}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-mus" value="${m.ancho_muslo||''}" style="${bgInf}" ${roInf}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-rod" value="${m.ancho_rodilla||''}" style="${bgInf}" ${roInf}></td>
                <td><input type="number" step="0.5" class="form-control form-control-sm edit-sas-tal" value="${m.ancho_talon||''}" style="${bgInf}" ${roInf}></td>
            `;
        }

        $(document).off('click', '#btnGuardarEdicionOrdenFinal').on('click', '#btnGuardarEdicionOrdenFinal', function(e) {
            e.preventDefault();
            let fd = new FormData($('#formEditarOrden')[0]);
            fd.append('accion', 'guardar_edicion_orden');
            
            let prendas = [];
            $('.fila-prenda-edit').each(function() {
                prendas.push({
                    tipo: $(this).find('.edit-tipo').val(),
                    cantidad: $(this).find('.edit-cant').val(),
                    id_materia: $(this).find('.edit-tela').val() || 0
                });
            });
            fd.append('prendas', JSON.stringify(prendas));

            let artes = [];
            $('.fila-arte-edit').each(function() {
                artes.push({
                    elemento: $(this).find('.edit-arte-nombre').val(),
                    cantidad: $(this).find('.edit-arte-cant').val(),
                    vinculo: $(this).find('.edit-arte-vinculo').val(),
                    ubicacion: $(this).find('.edit-arte-ubi').val()
                });
            });
            fd.append('artes', JSON.stringify(artes));

            let medidas = [];
            $('.fila-medida-edit').each(function() {
                medidas.push({
                    tipo_prenda: $(this).find('.edit-med-prenda').val(),
                    cantidad: $(this).find('.edit-med-cant').val(),
                    nombre: $(this).find('.edit-med-nombre').val(),
                    genero: $(this).find('.edit-med-genero').val(),
                    talla: $(this).find('.edit-med-talla').val(),
                    tela: $(this).find('.edit-med-tela').val(),
                    obs: $(this).find('.edit-med-obs').val()
                });
            });
            fd.append('medidas', JSON.stringify(medidas));

            let sastreria = [];
            $('.fila-sastreria-edit').each(function() {
                sastreria.push({
                    tipo_prenda: $(this).find('.edit-sas-prenda').val(),
                    nombre: $(this).find('.edit-sas-nombre').val(),
                    hombro: $(this).find('.edit-sas-hom').val() || 0,
                    busto: $(this).find('.edit-sas-bus').val() || 0,
                    cintura: $(this).find('.edit-sas-cin').val() || 0,
                    cadera: $(this).find('.edit-sas-cad').val() || 0,
                    l_camisa: $(this).find('.edit-sas-lcam').val() || 0,
                    l_brazo: $(this).find('.edit-sas-lbra').val() || 0,
                    l_pantalon: $(this).find('.edit-sas-lpan').val() || 0,
                    tiro: $(this).find('.edit-sas-tiro').val() || 0,
                    muslo: $(this).find('.edit-sas-mus').val() || 0,
                    rodilla: $(this).find('.edit-sas-rod').val() || 0,
                    talon: $(this).find('.edit-sas-tal').val() || 0
                });
            });
            fd.append('sastreria', JSON.stringify(sastreria));

            if (window.archivosOrdenEditMemoria && window.archivosOrdenEditMemoria.length > 0) {
                for (let i = 0; i < window.archivosOrdenEditMemoria.length; i++) {
                    fd.append('orden_foto_extra[]', window.archivosOrdenEditMemoria[i]);
                }
            }

            let btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            $.ajax({
                url: 'api/api_ordenes.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        $('#modalEditarOrden').modal('hide');
                        Swal.fire('¡Alterada!', 'La orden ha sido modificada y sobreescrita exitosamente.', 'success');
                        window.cargarListaOrdenes();
                    } else { Swal.fire('Error', res.msg, 'error'); }
                },
                complete: function() { btn.prop('disabled', false).html('<i class="bi bi-check-circle"></i> Guardar Alteración'); }
            });
        });

        // ==============================================================================
        // CARGA COMPLETA DESDE LA COTIZACIÓN HACIA EL MODAL DE NUEVA ORDEN DE FÁBRICA
        // ==============================================================================
        window.actualizarTallaMedida = function(selectElem, init = false) {
            let $tr = $(selectElem).closest('tr');
            let genero = $tr.find('.med-genero').val();
            let prenda = $tr.find('.med-prenda').val() || '';
            let $tallaSelect = $tr.find('.med-talla');
            let tallaActual = $tallaSelect.data('talla-actual');
            $tallaSelect.html(window.obtenerOpcionesTalla(genero, prenda));
            if (init && tallaActual) { $tallaSelect.val(tallaActual); }
        };

        window.cargarDatosCotizacionOrden = function(id_cot) {
            const $botonEmitir = $('#btnGuardarOrdenFinal').prop('disabled', true);
            const $resumen = $('#tbody_prendas_orden');
            const escapar = valor => $('<div>').text(String(valor ?? '')).html();

            if (!id_cot) {
                $resumen.html('<tr><td colspan="4" class="text-center text-muted">Selecciona una cotización.</td></tr>');
                $('#tbody_artes_orden').empty();
                $('#tbody_medidas_personales').empty();
                $('#tbody_sastreria').empty();
                $('#fotos_previas_container').empty();
                $('#ordenFotosPreview').empty();
                return;
            }

            $resumen.html('<tr><td colspan="4" class="text-center">Cargando prendas y reglas guardadas...</td></tr>');
            if ($('#ordenEstadoValidacion').length) {
                $('#ordenEstadoValidacion').removeClass('alert-danger alert-success').addClass('alert-info')
                    .text('Cargando cantidades, tallas y telas guardadas en la cotización...');
            }

            $.get('api/api_ordenes.php', { accion: 'obtener_datos_cotizacion', id_cotizacion: id_cot }, function(res) {
                if (!res || !res.success) {
                    if ($('#ordenEstadoValidacion').length) {
                        $('#ordenEstadoValidacion').removeClass('alert-info').addClass('alert-danger')
                            .text(res && res.msg ? res.msg : 'No se pudieron cargar los datos de la cotización.');
                    }
                    $resumen.empty();
                    return;
                }

                $('#id_cliente_orden').val(res.id_cliente);
                $('#orden_notas_globales').val(res.notas_cotizacion || '');
                telasMemoria = res.telas || [];
                prendasOrdenMemoria = [];
                artesOrdenMemoria = [];

                const normalizarTela = valor => String(valor || '')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();

                let htmlPrendas = '';
                (res.detalles || []).forEach(d => {
                    if (d.es_cargo_talla_extra || /^tallas?\s+extras?\b/i.test(String(d.tipo_prenda || '').trim())) return;
                    const esArte = Boolean(d.es_arte) || (d.detalle_diseno && d.detalle_diseno.includes('Ubicación:')) || /^(bordado|serigraf|dtf|vinil)/i.test(String(d.tipo_prenda || '').trim());
                    if (esArte) {
                        artesOrdenMemoria.push(d);
                        return;
                    }
                    prendasOrdenMemoria.push(d);

                    let nombreCompleto = d.tipo_prenda + (d.genero && d.genero !== 'No especificado' ? ` (${d.genero})` : '');
                    let specs = [];
                    try {
                        const parsed = typeof d.especificaciones_tallas === 'string'
                            ? JSON.parse(d.especificaciones_tallas || '[]')
                            : d.especificaciones_tallas;
                        if (Array.isArray(parsed)) specs = parsed;
                    } catch(e) {}

                    const telasEspecificadas = [...new Set(specs.map(s => String(s.tela || '').trim()).filter(Boolean))];
                    const telaCotizadaStr = d.tela_cotizada || (telasEspecificadas[0] || '');
                    const telaNorm = normalizarTela(telaCotizadaStr);

                    let opcionesTela = `<option value="0">${telaCotizadaStr ? escapar(telaCotizadaStr) : 'Seleccionar Tela...'}</option>`;
                    telasMemoria.forEach(t => {
                        let nombreTelaStr = `${t.nombre} (${t.color})`;
                        let sel = (telaNorm !== '' && (normalizarTela(nombreTelaStr) === telaNorm || normalizarTela(t.nombre) === telaNorm)) ? 'selected' : '';
                        opcionesTela += `<option value="${t.ID}" ${sel}>${escapar(nombreTelaStr)}</option>`;
                    });

                    htmlPrendas += `<tr class="fila-prenda-orden">
                        <td><input type="text" class="form-control form-control-sm fw-bold orden-tipo bg-light" value="${escapar(nombreCompleto)}" readonly></td>
                        <td><input type="number" class="form-control form-control-sm text-center orden-cant bg-light" value="${Number(d.cantidad) || 0}" readonly></td>
                        <td><select class="form-select form-select-sm orden-tela border-primary" data-tela-especificada="1">${opcionesTela}</select></td>
                    </tr>`;
                });
                $resumen.html(htmlPrendas || '<tr><td colspan="3" class="text-center text-muted">No hay prendas físicas</td></tr>');

                // Artes y ubicaciones
                let htmlArtes = '';
                if (artesOrdenMemoria.length > 0) {
                    $('#seccion_artes_posiciones').show();
                    artesOrdenMemoria.forEach(a => {
                        let vinculoGuardado = '';
                        let ubicacionGuardada = '';
                        let disenoStr = String(a.detalle_diseno || '');
                        if (disenoStr.includes('[Para: ')) {
                            let partes = disenoStr.split('] - Ubicación: ');
                            vinculoGuardado = partes[0].replace('[Para: ', '').trim();
                            ubicacionGuardada = (partes[1] || '').trim();
                        } else if (disenoStr.includes('Ubicación:')) {
                            ubicacionGuardada = disenoStr.split('Ubicación:')[1].trim();
                        }

                        let opcionesVinculoArte = '';
                        prendasOrdenMemoria.forEach(p => {
                            let nombreCompleto = p.tipo_prenda + (p.genero && p.genero !== 'No especificado' ? ` (${p.genero})` : '');
                            let selected = (nombreCompleto === vinculoGuardado) ? 'selected' : '';
                            opcionesVinculoArte += `<option value="${escapar(nombreCompleto)}" ${selected}>${escapar(nombreCompleto)}</option>`;
                        });

                        htmlArtes += `<tr class="fila-arte-orden">
                            <td><input type="text" class="form-control form-control-sm fw-bold orden-arte-nombre text-danger bg-light" value="${escapar(a.tipo_prenda)}" readonly></td>
                            <td><input type="number" class="form-control form-control-sm text-center orden-arte-cant bg-light" value="${Number(a.cantidad) || 1}" readonly></td>
                            <td><select class="form-select form-select-sm orden-arte-vinculo border-danger">${opcionesVinculoArte}</select></td>
                            <td><input type="text" class="form-control form-control-sm orden-arte-ubi border-danger fw-bold" value="${escapar(ubicacionGuardada)}" placeholder="Ej: Pecho Izquierdo"></td>
                        </tr>`;
                    });
                    $('#tbody_artes_orden').html(htmlArtes);
                } else {
                    $('#seccion_artes_posiciones').hide();
                }

                // Fotos heredadas de la cotización
                const archivos = String(res.fotos_diseno || '').split(',').map(nombre => nombre.trim()).filter(Boolean);
                const htmlFotosRef = archivos.length
                    ? `<div class="mt-2 small fw-bold text-success"><i class="bi bi-check-circle-fill"></i> Referencias adjuntas a la cotización:</div><div class="d-flex flex-wrap gap-2 mt-1">${archivos.map(nombre => `<a href="../images/ordenes/${encodeURIComponent(nombre)}" target="_blank" rel="noopener"><img src="../images/ordenes/${encodeURIComponent(nombre)}" alt="Diseño" style="height:55px;border-radius:4px;border:1px solid #ccc"></a>`).join('')}</div>`
                    : '<div class="small text-muted mt-2">La cotización no tiene archivos de diseño adjuntos.</div>';
                $('#fotos_previas_container').html(htmlFotosRef);
                $('#ordenFotosPreview').html(htmlFotosRef);

                // Cargar tallas y sastrería desde la cotización
                $('#check_asignar_tallas').prop('checked', true);
                $('#seccion_asignacion_tallas').show();
                window.autogenerarFilasTallas(true);

                if ($('#ordenEstadoValidacion').length) {
                    $('#ordenEstadoValidacion').removeClass('alert-info alert-danger').addClass('alert-success')
                        .text('Datos cargados desde la cotización. Puedes revisarlos o emitir directamente la Orden de Fábrica.');
                }
                $botonEmitir.prop('disabled', false);
            }, 'json').fail(function() {
                if ($('#ordenEstadoValidacion').length) {
                    $('#ordenEstadoValidacion').removeClass('alert-info').addClass('alert-danger')
                        .text('No se pudo conectar para cargar la cotización. Intenta nuevamente.');
                }
                $resumen.empty();
            });
        };

        window.autogenerarFilasTallas = function(silencioso = false) {
            if(prendasOrdenMemoria.length === 0) {
                if(!silencioso) Swal.fire('Error', 'Seleccione una cotización base primero', 'warning');
                return;
            }

            $('#seccion_asignacion_tallas thead tr').html('<th>Prenda</th><th style="width:70px;">Cant.</th><th>Nombre (Opcional)</th><th>Género</th><th>Talla</th><th>Tela Asignada</th><th>Observación</th><th></th>');
            $('#tbody_medidas_personales').empty();
            $('#tbody_sastreria').empty();
            
            let opcionesPrenda = '';
            prendasOrdenMemoria.forEach(p => {
                let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
                opcionesPrenda += `<option value="${nombreCompleto}">${nombreCompleto}</option>`;
            });

            // Mapa de bordados relacionados para que aparezcan en la observación de la camisa/prenda
            const obtenerBordadosPrenda = function(nombreCompleto, tipoBase) {
                let lista = [];
                (artesOrdenMemoria || []).forEach(a => {
                    let disenoStr = String(a.detalle_diseno || '');
                    let vinculo = '';
                    let ubi = '';
                    if (disenoStr.includes('[Para: ')) {
                        let partes = disenoStr.split('] - Ubicación: ');
                        vinculo = partes[0].replace('[Para: ', '').trim();
                        ubi = (partes[1] || '').trim();
                    } else if (disenoStr.includes('Ubicación:')) {
                        ubi = disenoStr.split('Ubicación:')[1].trim();
                    }
                    let txt = ubi ? `${a.tipo_prenda} (${ubi})` : a.tipo_prenda;
                    if (vinculo && (vinculo.toLowerCase() === nombreCompleto.toLowerCase() || vinculo.toLowerCase() === String(tipoBase || '').toLowerCase())) {
                        lista.push(txt);
                    } else if (!vinculo && (prendasOrdenMemoria.length === 1 || /(camisa|chemis|chemise|franela|jersey|filipina|bata|chaqueta|chaleco|delantal)/i.test(nombreCompleto))) {
                        lista.push(txt);
                    }
                });
                return [...new Set(lista)].join(' + ');
            };

            $('#seccion_asignacion_tallas').show();
            let htmlMedidas = '';
            let hayMedidasSastreria = false;

            prendasOrdenMemoria.forEach(p => {
                let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
                let bordadoRelacionado = obtenerBordadosPrenda(nombreCompleto, p.tipo_prenda);
                let specs = [];
                try {
                    let specs_raw = p.especificaciones_tallas || p.specs;
                    if (typeof specs_raw === 'string' && specs_raw.trim() !== '') {
                        specs = JSON.parse(specs_raw);
                    } else if (Array.isArray(specs_raw)) {
                        specs = specs_raw;
                    }
                } catch(e) { console.error("Error parseando tallas:", e); }

                if (specs && specs.length > 0) {
                    specs.forEach(s => {
                        let gen = s.genero || 'Dama';
                        let opcionesTallas = window.obtenerOpcionesTalla(gen, nombreCompleto);
                        if (s.talla && !opcionesTallas.includes(`value="${s.talla}"`)) {
                            opcionesTallas += `<option value="${s.talla}" selected>${s.talla}</option>`;
                        } else {
                            opcionesTallas = opcionesTallas.replace(`value="${s.talla}"`, `value="${s.talla}" selected`);
                        }
                        
                        let currPrendaOpt = opcionesPrenda.replace(`value="${nombreCompleto}"`, `value="${nombreCompleto}" selected`);

                        let obsLinea = String(s.obs || '').trim();
                        if (bordadoRelacionado && !obsLinea.toLowerCase().includes('bordado') && !obsLinea.toLowerCase().includes(bordadoRelacionado.toLowerCase())) {
                            obsLinea = obsLinea ? `${obsLinea} | ${bordadoRelacionado}` : bordadoRelacionado;
                        }

                        const mp = s.medidas_personales || {};
                        const camposMedidas = ['hombro', 'busto_torax', 'cintura', 'cadera', 'largo_camisa', 'largo_brazo', 'largo_pantalon', 'tiro_total', 'ancho_muslo', 'ancho_rodilla', 'ancho_talon'];
                        const inputsMedidas = camposMedidas.map(campo => `<input type="hidden" class="med-medidas-personales" data-medida="${campo}" value="${Number(mp[campo]) > 0 ? Number(mp[campo]) : 0}">`).join('');

                        htmlMedidas += `<tr class="fila-medida fila-medida-personal">
                            <td><select name="med_prenda[]" class="form-select form-select-sm med-prenda bg-light" style="width: 150px;" onchange="window.actualizarTallaMedida(this)">${currPrendaOpt}</select>${inputsMedidas}</td>
                            <td><input type="number" name="med_cant[]" class="form-control form-control-sm text-center med-cant med-cantidad border-primary fw-bold" value="${s.cantidad || 1}" min="1" required></td>
                            <td><input type="text" name="med_nombre[]" class="form-control form-control-sm med-nombre border-primary fw-bold" value="${s.nombre || ''}" placeholder="S/N"></td>
                            <td>
                                <select name="med_genero[]" class="form-select form-select-sm med-genero bg-light" onchange="window.actualizarTallaMedida(this)">
                                    <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
                                    <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
                                    <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
                                    <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>
                                    <option value="U" ${gen==='U'?'selected':''}>U</option>
                                    <option value="Accesorio" ${gen==='Accesorio'?'selected':''}>Accesorio</option>
                                    <option value="No aplica" ${gen==='No aplica'?'selected':''}>No aplica</option>
                                </select>
                            </td>
                            <td><select name="med_talla[]" class="form-select form-select-sm med-talla border-primary fw-bold" required>${opcionesTallas}</select></td>
                            <td><input type="text" name="med_tela[]" list="lista_telas_inteligente" class="form-control form-control-sm med-tela bg-light" value="${s.tela || p.tela_cotizada || ''}" placeholder="Tela..."></td>
                            <td><input type="text" name="med_obs[]" class="form-control form-control-sm med-obs" value="${obsLinea.replace(/"/g, '&quot;')}" placeholder="Ej: Con bolsillo"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                        </tr>`;

                        const tieneAlgunaMedida = camposMedidas.some(c => Number(mp[c]) > 0);
                        if (tieneAlgunaMedida) {
                            hayMedidasSastreria = true;
                            let textoPecho = (gen === 'Caballero' || gen === 'Niño') ? 'Tórax' : 'Busto';
                            let htmlSas = `<tr class="fila-sastreria">
                                <td><input type="text" class="form-control form-control-sm sas-prenda bg-light" value="${nombreCompleto}" readonly></td>
                                <td><input type="text" class="form-control form-control-sm sas-nombre border-primary fw-bold" value="${s.nombre || '-'}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-hom text-center px-1" value="${mp.hombro || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-bus text-center px-1" value="${mp.busto_torax || ''}" placeholder="${textoPecho}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-cin text-center px-1" value="${mp.cintura || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-cad text-center px-1" value="${mp.cadera || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-lcam text-center px-1" value="${mp.largo_camisa || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-lbra text-center px-1" value="${mp.largo_brazo || ''}"></td>
                                <td style="border-left: 2px solid #FF7518;"><input type="number" step="0.1" class="form-control form-control-sm sas-lpan text-center px-1" value="${mp.largo_pantalon || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-tiro text-center px-1" value="${mp.tiro_total || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-mus text-center px-1" value="${mp.ancho_muslo || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-rod text-center px-1" value="${mp.ancho_rodilla || ''}"></td>
                                <td><input type="number" step="0.1" class="form-control form-control-sm sas-tal text-center px-1" value="${mp.ancho_talon || ''}"></td>
                                <td class="text-center btn-eliminar-td"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                            </tr>`;
                            $('#tbody_sastreria').append(htmlSas);
                        }
                    });
                } else {
                    let gen = 'Dama';
                    if (nombreCompleto.toLowerCase().includes('caballero')) gen = 'Caballero';
                    else if (nombreCompleto.toLowerCase().includes('infantil') || nombreCompleto.toLowerCase().includes('niñ')) gen = 'Niño';
                    else if (nombreCompleto.toLowerCase().includes('unisex')) gen = 'U';
                    
                    let opcionesTallas = window.obtenerOpcionesTalla(gen, nombreCompleto);
                    let currPrendaOpt = opcionesPrenda.replace(`value="${nombreCompleto}"`, `value="${nombreCompleto}" selected`);

                    htmlMedidas += `<tr class="fila-medida fila-medida-personal">
                        <td><select name="med_prenda[]" class="form-select form-select-sm med-prenda bg-light" style="width: 150px;" onchange="window.actualizarTallaMedida(this)">${currPrendaOpt}</select></td>
                        <td><input type="number" name="med_cant[]" class="form-control form-control-sm text-center med-cant med-cantidad border-primary fw-bold" value="${p.cantidad}" min="1" required></td>
                        <td><input type="text" name="med_nombre[]" class="form-control form-control-sm med-nombre border-primary fw-bold" placeholder="S/N"></td>
                        <td>
                            <select name="med_genero[]" class="form-select form-select-sm med-genero bg-light" onchange="window.actualizarTallaMedida(this)">
                                <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
                                <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
                                <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
                                <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>
                                <option value="U" ${gen==='U'?'selected':''}>U</option>
                            </select>
                        </td>
                        <td><select name="med_talla[]" class="form-select form-select-sm med-talla border-primary fw-bold" required>${opcionesTallas}</select></td>
                        <td><input type="text" name="med_tela[]" list="lista_telas_inteligente" class="form-control form-control-sm med-tela bg-light" value="${p.tela_cotizada || ''}" placeholder="Tela..."></td>
                        <td><input type="text" name="med_obs[]" class="form-control form-control-sm med-obs" value="${bordadoRelacionado.replace(/"/g, '&quot;')}" placeholder="Ej: Con bolsillo"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                    </tr>`;
                }
            });

            $('#tbody_medidas_personales').html(htmlMedidas);
            if (hayMedidasSastreria) {
                $('#check_sastreria').prop('checked', true);
                $('#seccion_sastreria').show();
            }
        };

        // ASEGURAR QUE EL BOTÓN #btnGuardarOrdenFinal DISPARE EL ENVÍO DE LA ORDEN
        $(document).off('click', '#btnGuardarOrdenFinal').on('click', '#btnGuardarOrdenFinal', function(e) {
            e.preventDefault();
            if($('#select_cotizacion_orden').val() === "") return Swal.fire('Error', 'Debe seleccionar una cotización base.', 'warning');

            let prendasConfiguradas = [];
            $('.fila-prenda-orden').each(function() {
                prendasConfiguradas.push({
                    tipo: $(this).find('.orden-tipo').val(),
                    cantidad: $(this).find('.orden-cant').val(),
                    id_materia: $(this).find('.orden-tela').val() || 0
                });
            });

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
            $('.fila-medida-personal, .fila-medida').each(function() {
                const medidas = {};
                $(this).find('.med-medidas-personales').each(function() {
                    medidas[$(this).data('medida')] = $(this).val() || 0;
                });
                medidasConfiguradas.push({
                    tipo_prenda: $(this).find('.med-prenda').val(),
                    cantidad: parseInt($(this).find('.med-cantidad, .med-cant').val(), 10) || 1,
                    nombre: ($(this).find('.med-nombre').val() || '').trim(),
                    genero: $(this).find('.med-genero').val(),
                    talla: $(this).find('.med-talla').val(),
                    tela: $(this).find('.med-tela').val() || '',
                    obs: $(this).find('.med-obs').val() || '',
                    medidas_personales: medidas
                });
            });

            let sastreriaConfigurada = [];
            $('.fila-sastreria').each(function() {
                sastreriaConfigurada.push({
                    tipo_prenda: $(this).find('.sas-prenda, .sas-prenda-select').val(),
                    nombre: ($(this).find('.sas-nombre').val() || '').trim(),
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

            let formData = new FormData();
            formData.append('accion', 'guardar_orden');
            formData.append('id_cotizacion', $('#select_cotizacion_orden').val());
            formData.append('id_cliente', $('#id_cliente_orden').val());
            formData.append('fecha_entrega', $('#orden_fecha_entrega').val());
            formData.append('es_express', $('#orden_express').is(':checked') ? 1 : 0);
            formData.append('acepta_parciales', $('#orden_parciales').is(':checked') ? 1 : 0);
            formData.append('descripcion_global', $('#orden_notas_globales').val() || '');
            formData.append('prendas', JSON.stringify(prendasConfiguradas));
            formData.append('artes', JSON.stringify(artesConfigurados));
            formData.append('medidas', JSON.stringify(medidasConfiguradas));
            formData.append('sastreria', JSON.stringify(sastreriaConfigurada));

            let inputFotosEl = document.getElementById('orden_foto_referencial');
            if (inputFotosEl && inputFotosEl.files && inputFotosEl.files.length > 0) {
                for (let i = 0; i < inputFotosEl.files.length; i++) {
                    formData.append('orden_foto[]', inputFotosEl.files[i]);
                }
            }

            let $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

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
                            window.cargarListaOrdenes();
                            if (typeof window.chequearCotizacionesPendientes === 'function') window.chequearCotizacionesPendientes();
                            window.open('imprimir_orden.php?id=' + res.id_orden, '_blank');
                        });
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bi bi-save"></i> Emitir Orden de Fábrica');
                }
            });
        });
});