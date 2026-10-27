let carritoMemoria = [];
let carritoLicitacion = []; 
let prendasOrdenMemoria = [];
let artesOrdenMemoria = [];
let telasMemoria = [];
let archivosOrdenMemoria = []; // <-- CARRO PARA GUARDAR MÚLTIPLES FOTOS 

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

function JS_tieneVector(vector) {
    let vectores = $('#mis_vectores').val().split(',');
    return vectores.includes(vector.toString());
}

window.actualizarTallaModalPrenda = function(selectElem) {
    let genero = $(selectElem).val();
    let $tallaSelect = $('#modal_talla');
    let opciones = '';
    
    if(genero === 'Adulto (Dama o Caballero)') opciones = 'XS - S - M - L - XL';
    else if(genero === 'Adulto (Talla Extra 2XL - 3XL)') opciones = '2XL - 3XL';
    else if(genero === 'Adulto (Talla Extra 4XL - 5XL)') opciones = '4XL - 5XL';
    else if(genero === 'Infantil (Unisex)') opciones = '4 - 6 - 8 - 10';
    else if(genero === 'Juvenil (Unisex)') opciones = '12 - 14 - 16';
    else opciones = 'Única / No Aplica';
    
    $tallaSelect.val(opciones);
};

// ================= RENDERIZAR LISTA DE FOTOS =================
window.renderizarListaFotosOrden = function() {
    if($('#lista_fotos_seleccionadas').length === 0) {
        $('#orden_foto_referencial').after('<div id="lista_fotos_seleccionadas"></div>');
    }
    let listHtml = '';
    if (archivosOrdenMemoria.length > 0) {
        listHtml += '<div class="mt-2 p-2 border border-primary rounded bg-white shadow-sm">';
        listHtml += '<strong class="small text-primary"><i class="bi bi-paperclip"></i> Archivos en cola ('+archivosOrdenMemoria.length+'):</strong>';
        listHtml += '<ul class="list-group mb-0 mt-1" style="font-size: 0.8rem;">';
        archivosOrdenMemoria.forEach((file, index) => {
            listHtml += `<li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2 text-dark">
                <span class="text-truncate" style="max-width: 85%;"><i class="bi bi-file-earmark-image me-2 text-danger"></i>${file.name}</span>
                <button type="button" class="btn btn-sm btn-danger py-0 px-2 rounded-pill shadow-sm" onclick="window.eliminarFotoOrden(${index})" title="Quitar de la lista"><i class="bi bi-trash"></i></button>
            </li>`;
        });
        listHtml += '</ul></div>';
    }
    $('#lista_fotos_seleccionadas').html(listHtml);
};

window.eliminarFotoOrden = function(index) {
    archivosOrdenMemoria.splice(index, 1);
    window.renderizarListaFotosOrden();
};
// =============================================================

$(document).ready(function() {
    
    if($('#modal_genero').length > 0) {
        $('#modal_genero').html(`
            <option value="Adulto (Dama o Caballero)">Adulto (Dama o Caballero)</option>
            <option value="Adulto (Talla Extra 2XL - 3XL)">Adulto (Talla Extra 2XL - 3XL)</option>
            <option value="Adulto (Talla Extra 4XL - 5XL)">Adulto (Talla Extra 4XL - 5XL)</option>
            <option value="Infantil (Unisex)">Infantil (Unisex)</option>
            <option value="Juvenil (Unisex)">Juvenil (Unisex)</option>
        `);
        window.actualizarTallaModalPrenda(document.getElementById('modal_genero'));
    }

    // LISTENER PARA AGREGAR MÚLTIPLES FOTOS A LA ORDEN (CARGA SECUENCIAL)
    $(document).on('change', '#orden_foto_referencial', function(e) {
        let files = e.target.files;
        if(files.length > 0) {
            for(let i=0; i<files.length; i++) {
                archivosOrdenMemoria.push(files[i]);
            }
            window.renderizarListaFotosOrden();
        }
        $(this).val(''); // Reseteamos el input para permitir elegir de nuevo
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length === 0) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
        }
    });

    $('#btnHamburguesa, #sidebarOverlay').click(function() {
        $('#sidebar').toggleClass('active');
        $('#sidebarOverlay').toggleClass('active');
    });

    $('.nav-link-custom').click(function(e) {
        if($(this).attr('data-bs-toggle') === 'modal') return;

        const destino = $(this).attr('href');
        if (destino && destino !== '#') return;

        e.preventDefault();
        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');
        $('#sidebar').removeClass('active'); 
        $('#sidebarOverlay').removeClass('active');
        $('.vista-spa').hide();
        window.scrollTo({ top: 0, behavior: 'instant' });

        if (this.id === 'btnMenuClientes') {
            $('#vistaClientes').fadeIn();
        }
        else if (this.id === 'btnMenuClientesCotizar') {
            $('#vistaClientesCotizar').fadeIn();
            if (typeof window.renderClientesCotizar === 'function') {
                window.renderClientesCotizar();
            }
        }
        else if (this.id === 'btnMenuCotizador') {
            if (!$('#idClienteSeleccionado').val()) {
                Swal.fire('Atención', 'Seleccione un cliente primero haciendo clic en "Cotizar" en el directorio.', 'warning');
                $('#btnMenuClientes').click();
                return;
            }
            $('#vistaVentas').fadeIn();
            cargarCatalogoSPA();
        }
        else if (this.id === 'btnMenuCotizadorLicitaciones') { 
            if (!$('#idClienteSeleccionado').val()) {
                Swal.fire('Atención', 'Seleccione un cliente primero en el directorio para la licitación.', 'warning');
                $('#btnMenuClientes').click();
                return;
            }
            $('#lblClienteLicitacion').text($('#lblClienteSeleccionado').text());
            $('#vistaCotizadorLicitaciones').fadeIn();
        }
        else if (this.id === 'btnMenuGestion') {
            $('#vistaGestion').fadeIn();
        }
        else if (this.id === 'btnMenuCotizaciones') {
            $('#vistaCotizaciones').fadeIn();
            cargarHistorial(); 
        }
        else if (this.id === 'btnMenuCotizacionesEliminadas') {
            $('#vistaCotizacionesEliminadas').fadeIn();
            if (typeof window.cargarCotizacionesEliminadas === 'function') {
                window.cargarCotizacionesEliminadas();
            }
        }
        else if (this.id === 'btnMenuDashboard') {
            $('#vistaDashboard').fadeIn();
            if(typeof inicializarDashboard === 'function') inicializarDashboard();
        }
        else if (this.id === 'btnMenuOrdenes') {
            $('#vistaOrdenes').fadeIn();
            if(typeof window.cargarListaOrdenes === 'function') window.cargarListaOrdenes();
        }
        else if (this.id === 'btnMenuUsuarios') {
            $('#vistaUsuarios').fadeIn();
            if(typeof cargarUsuarios === 'function') cargarUsuarios();
        }
        else if (this.id === 'btnMenuAuditoria') {
            $('#vistaAuditoria').fadeIn();
            if(typeof cargarAuditoria === 'function') cargarAuditoria();
        }
        else if (this.id === 'btnMenuLicitaciones') {
            $('#vistaLicitaciones').fadeIn();
            if(typeof cargarLicitaciones === 'function') cargarLicitaciones();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const moduloURL = urlParams.get('modulo');
    
    if (moduloURL === 'licitaciones' && $('#btnMenuLicitaciones').length > 0) {
        $('#btnMenuLicitaciones').click();
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if ($('.nav-link-custom:not([data-bs-toggle="modal"])').length > 0) {
        $('.nav-link-custom:not([data-bs-toggle="modal"])').first().click();
    }

    if($('#badge_pendientes_menu').length > 0) {
        chequearCotizacionesPendientes();
        setInterval(chequearCotizacionesPendientes, 30000); 
    }

    $(document).on('change', 'input[name="id_forma_pago"]', function() {
        $('.custom-check-small').removeClass('active-check');
        $(this).closest('.custom-check-small').addClass('active-check');
        actualizarResumenCarrito(); 
    });

    $(document).on('change', 'input[name="lic_forma_pago"]', function() {
        $('.custom-check-small').removeClass('active-check');
        $(this).closest('.custom-check-small').addClass('active-check');
        actualizarTablaLicitacion(); 
    });

    $(document).on('change', '#cot_contribuyente, #lic_contribuyente', function() {
        if($(this).is(':checked')) $(this).closest('.custom-check-small').addClass('active-check');
        else $(this).closest('.custom-check-small').removeClass('active-check');
    });

    $(document).on('change', '#cot_aplica_iva', function() {
        if($(this).is(':checked')) $(this).closest('.custom-check-small').addClass('active-check');
        else $(this).closest('.custom-check-small').removeClass('active-check');
        actualizarResumenCarrito();
    });

    $(document).on('change', '#lic_aplica_iva', function() {
        if($(this).is(':checked')) $(this).closest('.custom-check-small').addClass('active-check');
        else $(this).closest('.custom-check-small').removeClass('active-check');
        actualizarTablaLicitacion();
    });

    $(document).on('input', '#cot_desc_manual', function() { actualizarResumenCarrito(); });
    $(document).on('input', '#cot_desc_logistica', function() { actualizarResumenCarrito(); });
    $(document).on('input', '#cot_flete', function() { actualizarResumenCarrito(); });
    
    $(document).on('input', '#lic_desc_manual', function() { actualizarTablaLicitacion(); });
    $(document).on('input', '#lic_desc_logistica', function() { actualizarTablaLicitacion(); });
    $(document).on('input', '#lic_flete', function() { actualizarTablaLicitacion(); });

    $(document).on('click', '.pill-cat', function(e) {
    e.preventDefault();
    let target = $(this).attr('href');
    let $container = $('.main-content');
    let $target = $(target);
    
    if ($target.length) {
        $container.animate({ 
            scrollTop: $target.offset().top - $container.offset().top + $container.scrollTop() - 20 
        }, 500);
    }
});
    const removerAcentos = (str) => { return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(); };

    $('#buscadorDirectorio').on('input', function() {
        let valorFiltro = removerAcentos($(this).val().trim());
        $('#tablaClientesBody .fila-datos').each(function() {
            let nombre = removerAcentos($(this).find('.col-nombre').text());
            let cedula = removerAcentos($(this).find('.col-cedula').text());
            $(this).toggle(nombre.indexOf(valorFiltro) > -1 || cedula.indexOf(valorFiltro) > -1);
        });
    });
    
$(document).on('click', '.btn-seleccionar-cli', function() {
        let idCli = $(this).data('id');
        let nomCli = $(this).data('nombre');
        
        let tieneCotNormal = JS_tieneVector(4);
        let tieneCotLicitacion = JS_tieneVector(26);

        if(tieneCotNormal && tieneCotLicitacion) {
            Swal.fire({
                title: '¿Qué tipo de cotización deseas crear?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-shop"></i> Normal (Catálogo)',
                cancelButtonText: '<i class="bi bi-briefcase"></i> Especial (Licitación)',
                confirmButtonColor: '#0B4C8C',
                cancelButtonColor: '#FF7518',
            }).then((result) => {
                if (result.isConfirmed) {
                    activarClienteNormal(idCli, nomCli);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    activarClienteLicitacion(idCli, nomCli);
                }
            });
        } else if (tieneCotNormal) {
            activarClienteNormal(idCli, nomCli);
        } else if (tieneCotLicitacion) {
            activarClienteLicitacion(idCli, nomCli);
        }
    });

    function activarClienteNormal(id, nombre) {
        $('#idClienteSeleccionado').val(id);
        $('#lblClienteSeleccionado').text(nombre);
        cancelarEdicionCliente(); 
        $('#id_edit_cotizacion').val('0');
        $('#badgeEstadoEdicion').hide();
        $('.btn-bloqueable').prop('disabled', false);
        carritoMemoria = [];
        
        if($('#cot_aplica_iva').length) $('#cot_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
        if($('#cot_desc_manual').length) $('#cot_desc_manual').val(0);
        if($('#cot_desc_logistica').length) $('#cot_desc_logistica').val(0);
        if($('#cot_flete').length) $('#cot_flete').val(0);

        actualizarResumenCarrito();
        $('#btnMenuCotizador').click(); 
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    function activarClienteLicitacion(id, nombre) {
        $('#idClienteSeleccionado').val(id);
        $('#lblClienteSeleccionado').text(nombre);
        cancelarEdicionCliente(); 
        $('#id_edit_cotizacion').val('0');
        $('#lic_nro_cotizacion').val(''); 
        $('#badgeEstadoEdicion').hide();
        $('.btn-bloqueable').prop('disabled', false); 
        carritoLicitacion = [];

        if($('#lic_aplica_iva').length) $('#lic_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
        if($('#lic_desc_manual').length) $('#lic_desc_manual').val(0);
        if($('#lic_desc_logistica').length) $('#lic_desc_logistica').val(0);
        if($('#lic_flete').length) $('#lic_flete').val(0);

        actualizarTablaLicitacion();
        $('#btnMenuCotizadorLicitaciones').click(); 
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    $(document).on('click', '.btn-editar-cli', function() {
        $('#tituloFormCliente').text('Editar Cliente');
        $('#cli_id').val($(this).data('id'));
        $('#f_nombre').val($(this).data('nombre'));
        $('#f_cedula').val($(this).data('cedula'));
        $('#f_tlf').val($(this).data('tlf'));
        $('#f_comprador').val($(this).data('comprador'));
        $('#f_email').val($(this).data('email'));
        $('#f_direccion').val($(this).data('direccion'));
        $('#f_redes').val($(this).data('redes'));
        if($(this).data('veces')) $('#f_veces').val($(this).data('veces')); 
        if($(this).data('personas')) $('#f_personas').val($(this).data('personas'));
        $('#btnRegistrarCliente').text('Actualizar').css('background-color', '#FF7518');
        $('#btnCancelarEdicion').removeClass('d-none');
        window.scrollTo(0,0);
    });

    $(document).on('click', '.btn-eliminar-cli', function() {
        let idCliente = $(this).data('id');
        let fila = $(this).closest('tr');
        Swal.fire({
            title: '¿Eliminar Cliente?',
            text: "Esto es irreversible. Si el cliente tiene cotizaciones u órdenes, podrían borrarse o verse afectadas.",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#0B4C8C',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('index.php', { accion_eliminar_cliente: 1, id_cliente: idCliente }, function(res) {
                    if (res.success) {
                        fila.fadeOut(400, function() { $(this).remove(); });
                        Swal.fire('Eliminado', 'Cliente eliminado del sistema.', 'success');
                        if($('#idClienteSeleccionado').val() == idCliente) {
                            $('#idClienteSeleccionado').val('');
                            $('#lblClienteSeleccionado').text('Nadie');
                        }
                    } else { Swal.fire('Error', res.msg, 'error'); }
                }, 'json');
            }
        });
    });

    $('#formClienteAjax').submit(function(e) {
        e.preventDefault();
        let form = this;
        if (!form.checkValidity()) {
            e.stopPropagation();
            $(form).addClass('was-validated');
            Swal.fire('Atención', 'Por favor complete todos los campos obligatorios.', 'warning');
            return false;
        }

        let formData = $(this).serialize();
        $('#btnRegistrarCliente').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post('index.php', formData, function(res) {
            if(res.success) {
                
                let freq = "Anual";
                if(res.veces_uniforma == 2) freq = "Semestral";
                else if(res.veces_uniforma == 3) freq = "Cuatrimestral";
                else if(res.veces_uniforma == 4) freq = "Trimestral";

                if(res.modo === 'editar') {
                    let btnEditar = $(`.btn-editar-cli[data-id="${res.id}"]`);
                    let fila = btnEditar.closest('tr');
                    fila.find('.col-nombre').text(res.nombre);
                    fila.find('.col-cedula').text(res.cedula);
                    fila.find('.col-freq').text(freq);
                    fila.find('.col-personal').html(`<span class="badge bg-secondary">${res.cantidad_personas}</span>`); 
                    fila.find('.col-tlf').text(res.tlf);
                    btnEditar.data('nombre', res.nombre).data('cedula', res.cedula).data('tlf', res.tlf)
                             .data('comprador', res.comprador).data('email', res.email).data('direccion', res.direccion).data('redes', res.redes).data('veces', res.veces_uniforma).data('personas', res.cantidad_personas);
                    fila.find('.btn-seleccionar-cli').data('nombre', res.nombre);
                    Swal.fire('Actualizado', 'Cliente editado', 'success');
                } else {
                    $('.fila-vacia').remove(); 
                    
                    let btnEditarHtml = JS_tieneVector(2) ? `<button type="button" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill me-1 btn-editar-cli" data-id="${res.id}" data-nombre="${res.nombre}" data-cedula="${res.cedula}" data-tlf="${res.tlf}" data-comprador="${res.comprador}" data-email="${res.email}" data-direccion="${res.direccion}" data-redes="${res.redes}" data-veces="${res.veces_uniforma}" data-personas="${res.cantidad_personas}"><i class="bi bi-pencil"></i></button>` : '';
                    let btnEliminarHtml = JS_tieneVector(3) ? `<button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill me-1 btn-eliminar-cli" data-id="${res.id}" title="Eliminar Cliente"><i class="bi bi-trash"></i></button>` : '';
                    let btnSeleccionarHtml = JS_tieneVector(4) || JS_tieneVector(26) ? `<button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill btn-seleccionar-cli" data-id="${res.id}" data-nombre="${res.nombre}"><i class="bi bi-check-circle"></i> Cotizar</button>` : '';
                    
                    let nuevaFila = `<tr class="fila-datos" style="background-color: #d1e7dd;">
                        <td class="fw-bold col-nombre" style="color: #0B4C8C; min-width: 200px;">${res.nombre}</td>
                        <td class="col-cedula text-nowrap">${res.cedula}</td>
                        <td class="text-nowrap"><span class="badge bg-info text-dark col-freq">${freq}</span></td>
                        <td class="col-personal text-nowrap"><span class="badge bg-secondary">${res.cantidad_personas}</span></td>
                        <td class="col-tlf text-nowrap">${res.tlf}</td>
                        <td class="text-center text-nowrap">${btnEditarHtml}${btnEliminarHtml}${btnSeleccionarHtml}</td>
                        </tr>`;
                    $('#tablaClientesBody').prepend(nuevaFila);
                    setTimeout(() => { $('#tablaClientesBody tr:first').css('background-color', ''); }, 2000);
                    Swal.fire('Registrado', 'Cliente creado', 'success');
                }
                cancelarEdicionCliente();
            } else { Swal.fire('Error', res.msg, 'error'); }
        }, 'json').always(() => $('#btnRegistrarCliente').prop('disabled', false).text('Guardar Cliente').css('background-color', '#0B4C8C'));
    });

    $('#buscadorCatalogo').on('input', function() {
        let texto = $(this).val().trim();
        let filtro = removerAcentos(texto);
        if (filtro === '') {
            $('.tarjeta-producto').show().css('order', '0');
            $('.categoria-header, .subcategoria-header').show();
            return;
        }
        $('.categoria-header, .subcategoria-header').hide();
        let palabras = filtro.split(' ');
        $('.tarjeta-producto').each(function() {
            let nombreReal = $(this).data('nombre');
            let nombreLimpio = removerAcentos(nombreReal);
            let coincide = palabras.every(palabra => nombreLimpio.includes(palabra));
            if (coincide) {
                $(this).show();
                if (nombreLimpio.startsWith(filtro)) $(this).css('order', '-2');
                else $(this).css('order', '-1');
            } else { $(this).hide(); }
        });
    });

    // ================= GUARDAR COTIZACIÓN ESTÁNDAR =================
    $('#formGuardarCotizacion').submit(function(e) {
        e.preventDefault();
        let idCliente = $('#idClienteSeleccionado').val();
        if(!idCliente) return Swal.fire('Error', 'Seleccione un cliente.', 'error');
        if(carritoMemoria.length === 0) return Swal.fire('Error', 'El carrito está vacío.', 'error');

        let id_forma_pago = $('input[name="id_forma_pago"]:checked').val() || '';
        if (id_forma_pago === '') return Swal.fire('Atención', 'Debe seleccionar un Método de Pago Acordado antes de generar la cotización.', 'warning');

        let metodo_entrega = $('input[name="metodo_entrega"]:checked').val() || '';
        if (metodo_entrega === '') return Swal.fire('Atención', 'Debe seleccionar el Método de Entrega de la orden.', 'warning');

        $('#btnGenerarCotizacion').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        let datos = {
            tipo_cotizacion: 'ESTANDAR',
            nro_ssst: '',
            id_edit_cotizacion: $('#id_edit_cotizacion').val(),
            id_cliente: idCliente,
            contribuyente_especial: $('#cot_contribuyente').is(':checked') ? 1 : 0,
            aplica_iva: $('#cot_aplica_iva').length ? ($('#cot_aplica_iva').is(':checked') ? 1 : 0) : 1,
            porcentaje_descuento_manual: $('#cot_desc_manual').length ? (parseFloat($('#cot_desc_manual').val()) || 0) : 0,
            descuento_logistica: $('#cot_desc_logistica').length ? (parseFloat($('#cot_desc_logistica').val()) || 0) : 0,
            flete: $('#cot_flete').length ? (parseFloat($('#cot_flete').val()) || 0) : 0,
            id_forma_pago: id_forma_pago,
            metodo_entrega: metodo_entrega,
            notas: $('#cot_notas').val(),
            carrito: JSON.stringify(carritoMemoria)
        };

        $.post('api/guardar_cotizacion.php', datos, function(res) {
            if(res.success) {
                Swal.fire({ icon: 'success', title: '¡Cotización Guardada!', text: 'Nro: ' + res.nro_cotizacion, showCancelButton: true, confirmButtonText: 'Imprimir PDF', cancelButtonText: 'Cerrar' }).then((result) => {
                    let llaveSegura = btoa(res.id_cotizacion + '-' + res.nro_cotizacion);
                    if (result.isConfirmed) window.open('imprimir_cotizacion.php?id=' + res.id_cotizacion + '&k=' + llaveSegura, '_blank');
                    
                    carritoMemoria = [];
                    if($('#cot_desc_manual').length) $('#cot_desc_manual').val(0);
                    if($('#cot_desc_logistica').length) $('#cot_desc_logistica').val(0);
                    if($('#cot_flete').length) $('#cot_flete').val(0);
                    if($('#cot_aplica_iva').length) $('#cot_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
                    actualizarResumenCarrito();
                    $('#formGuardarCotizacion')[0].reset();
                    $('.custom-check-small').removeClass('active-check'); 
                    $('#id_edit_cotizacion').val('0');
                    $('#badgeEstadoEdicion').hide();
                    $('#btnMenuCotizaciones').click(); 
                });
            } else { Swal.fire('Error BD', res.msg, 'error'); }
        }, 'json').fail(function(){
            Swal.fire('Error', 'Fallo servidor', 'error');
        }).always(() => $('#btnGenerarCotizacion').prop('disabled', false).html('Generar Cotización Oficial <i class="bi bi-file-pdf ms-2"></i>'));
    });

    // ================= FUNCIONES LICITACIÓN MANUAL =================
    $('#formAgregarItemLicitacion').on('submit', function(e) {
        e.preventDefault();
        
        let desc = $('#lic_desc').val().trim();
        let precio = parseFloat($('#lic_precio').val());
        let cant = parseInt($('#lic_cant').val());

        if (desc === '' || isNaN(precio) || isNaN(cant) || cant < 1) {
            Swal.fire('Atención', 'Debe llenar la descripción, el precio y una cantidad válida.', 'warning');
            return;
        }

        let nuevoItem = {
            idUnico: 'lic_' + Date.now() + Math.floor(Math.random() * 1000),
            id_producto: 0,
            nombre: desc, 
            genero: 'No especificado',
            precio: precio,
            cantidad: cant,
            subtotal: precio * cant,
            detalle_diseno: '', 
            categoria: 'Licitacion',
            specs: [] 
        };

        carritoLicitacion.push(nuevoItem);
        actualizarTablaLicitacion();
        
        this.reset();
        $('#lic_cant').val('1');
        $('#lic_desc').focus();
    });

    window.actualizarTablaLicitacion = function() {
        let html = '';
        let subtotalBruto = 0;
        
        let aplica_iva = $('#lic_aplica_iva').length ? $('#lic_aplica_iva').is(':checked') : true;
        let desc_manual = $('#lic_desc_manual').length ? (parseFloat($('#lic_desc_manual').val()) || 0) : 0;
        let descLogisticaVal = $('#lic_desc_logistica').length ? (parseFloat($('#lic_desc_logistica').val()) || 0) : 0;
        let fleteVal = $('#lic_flete').length ? (parseFloat($('#lic_flete').val()) || 0) : 0;

        if (carritoLicitacion.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted py-4">No hay elementos agregados.</td></tr>';
        } else {
            carritoLicitacion.forEach((item, index) => {
                let nroStr = String(index + 1).padStart(4, '0');
                subtotalBruto += item.subtotal;
                
                let checkSpecsHtml = '';
                if (item.specs && item.specs.length > 0) {
                    checkSpecsHtml = '<br><span class="badge bg-success" style="font-size: 0.65rem;"><i class="bi bi-check-circle"></i> Especificado</span>';
                }

                html += `<tr>
                    <td class="text-muted fw-bold">${nroStr}</td>
                    <td class="small">${item.nombre} ${checkSpecsHtml}</td>
                    <td class="text-center fw-bold">${item.cantidad}</td>
                    <td class="text-end">$${formatoMoneda(item.precio)}</td>
                    <td class="text-end fw-bold text-dark">$${formatoMoneda(item.subtotal)}</td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm text-info btn-bloqueable px-1" onclick="window.abrirModalEspecificarCarrito(${index}, 'LICITACION')" title="Especificar Tallas y Telas"><i class="bi bi-rulers"></i></button>
                        <button type="button" class="btn btn-sm text-primary btn-bloqueable px-1" onclick="window.editarItemLicitacion(${index})"><i class="bi bi-pencil-fill"></i></button>
                        <button type="button" class="btn btn-sm text-danger btn-bloqueable px-1" onclick="window.eliminarItemLicitacion(${index})"><i class="bi bi-x-circle-fill"></i></button>
                    </td>
                </tr>`;
            });
        }
        
        let montoDescLogistica = descLogisticaVal;
        let montoFlete = fleteVal;
        
        let montoDescuentoPorc = subtotalBruto * (desc_manual / 100);
        let baseImponible = subtotalBruto - montoDescuentoPorc + montoDescLogistica + montoFlete;
        
        if (baseImponible < 0) baseImponible = 0;

        let id_forma_pago = $('input[name="lic_forma_pago"]:checked').val() || '';
        let nombre_pago = '';
        if(id_forma_pago !== '') {
            nombre_pago = $('input[name="lic_forma_pago"]:checked').closest('label').text().toLowerCase();
        }
        
        let iva = aplica_iva ? (baseImponible * 0.16) : 0;
        let igtf = (nombre_pago.indexOf('divisa') !== -1) ? (baseImponible * 0.03) : 0;
        let total = baseImponible + iva + igtf;

        $('#tbodyLicitacionItems').html(html);
        $('#lic_lbl_subtotal').text(formatoMoneda(subtotalBruto) + ' $');
        
        if(montoDescLogistica > 0) {
            if ($('#lic_cajaDescLogistica').length === 0) {
                $('#lic_lbl_iva').parent().before(`<div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="lic_cajaDescLogistica"><span>Serv. Logístico de Envío:</span> <span id="lic_lbl_descLogistica">0,00 $</span></div>`);
            }
            $('#lic_lbl_descLogistica').text(formatoMoneda(montoDescLogistica) + ' $');
            $('#lic_cajaDescLogistica').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaDescLogistica').remove();
        }

        if(montoFlete > 0) {
            if ($('#lic_cajaFlete').length === 0) {
                $('#lic_lbl_iva').parent().before(`<div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="lic_cajaFlete"><span>Flete:</span> <span id="lic_lbl_flete">0,00 $</span></div>`);
            }
            $('#lic_lbl_flete').text(formatoMoneda(montoFlete) + ' $');
            $('#lic_cajaFlete').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaFlete').remove();
        }

        if(montoDescuentoPorc > 0) {
            if ($('#lic_cajaDescuento').length === 0) {
                $('#lic_lbl_iva').parent().before(`<div class="d-flex justify-content-between small text-danger fw-bold mb-1" id="lic_cajaDescuento"><span>Descuento (<span id="lic_lbl_porcentajeDesc">0</span>):</span> <span id="lic_lbl_montoDesc">-0,00 $</span></div>`);
            }
            $('#lic_lbl_porcentajeDesc').text(desc_manual + '%');
            $('#lic_lbl_montoDesc').text('-' + formatoMoneda(montoDescuentoPorc) + ' $');
            $('#lic_cajaDescuento').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaDescuento').remove();
        }

        if(montoDescLogistica > 0 || montoDescuentoPorc > 0 || montoFlete > 0) {
            if ($('#lic_cajaBaseImponible').length === 0) {
                $('#lic_lbl_iva').parent().before(`<div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="lic_cajaBaseImponible"><span>Base Imponible:</span> <span id="lic_lbl_baseImponible">0,00 $</span></div>`);
            }
            $('#lic_lbl_baseImponible').text(formatoMoneda(baseImponible) + ' $');
            $('#lic_cajaBaseImponible').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaBaseImponible').remove();
        }

        if(iva > 0) {
            if ($('#lic_cajaIva').length === 0) {
                $('#lic_lbl_total').parent().before(`<div class="d-flex justify-content-between small text-muted mb-1" id="lic_cajaIva"><span>I.V.A (16%):</span> <span id="lic_lbl_iva">0,00 $</span></div>`);
            }
            $('#lic_lbl_iva').text(formatoMoneda(iva) + ' $');
            $('#lic_cajaIva').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaIva').remove();
        }
        
        if(igtf > 0) {
            if ($('#lic_cajaIgtf').length === 0) {
                $('#lic_lbl_total').parent().before(`<div class="d-flex justify-content-between small text-warning fw-bold mb-2" id="lic_cajaIgtf"><span>IGTF (3% - Divisas Efectivo):</span> <span id="lic_lbl_igtf">0,00 $</span></div>`);
            }
            $('#lic_lbl_igtf').text(formatoMoneda(igtf) + ' $');
            $('#lic_cajaIgtf').attr('style', 'display: flex !important;');
        } else {
            $('#lic_cajaIgtf').remove();
        }

        $('#lic_lbl_total').text(formatoMoneda(total) + ' $');
    }

    window.eliminarItemLicitacion = function(index) {
        carritoLicitacion.splice(index, 1);
        actualizarTablaLicitacion();
    };

    window.editarItemLicitacion = function(index) {
        let item = carritoLicitacion[index];
        $('#lic_desc').val(item.nombre);
        $('#lic_precio').val(item.precio);
        $('#lic_cant').val(item.cantidad);
        carritoLicitacion.splice(index, 1);
        actualizarTablaLicitacion();
        $('#lic_desc').focus();
    };

    $('#formGuardarLicitacion').submit(function(e) {
        e.preventDefault();
        
        let idCliente = $('#idClienteSeleccionado').val();
        if(!idCliente) return Swal.fire('Error', 'Debe seleccionar un cliente en el directorio antes de iniciar la cotización.', 'error');
        if(carritoLicitacion.length === 0) return Swal.fire('Error', 'Añada al menos un ítem a la lista.', 'error');

        let id_forma_pago = $('input[name="lic_forma_pago"]:checked').val() || '';
        if (id_forma_pago === '') return Swal.fire('Atención', 'Debe seleccionar un Método de Pago.', 'warning');
        
        let metodo_entrega = $('input[name="lic_metodo_entrega"]:checked').val() || '';
        if (metodo_entrega === '') return Swal.fire('Atención', 'Debe seleccionar el Método de Entrega.', 'warning');

        $('#btnVisualGuardarLicitacion').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        let datos = {
            tipo_cotizacion: 'LICITACION',
            nro_cotizacion: $('#lic_nro_cotizacion').val().trim(),
            id_edit_cotizacion: $('#id_edit_cotizacion').val() || 0, 
            id_cliente: idCliente,
            contribuyente_especial: $('#lic_contribuyente').is(':checked') ? 1 : 0,
            aplica_iva: $('#lic_aplica_iva').length ? ($('#lic_aplica_iva').is(':checked') ? 1 : 0) : 1,
            porcentaje_descuento_manual: $('#lic_desc_manual').length ? (parseFloat($('#lic_desc_manual').val()) || 0) : 0,
            descuento_logistica: $('#lic_desc_logistica').length ? (parseFloat($('#lic_desc_logistica').val()) || 0) : 0,
            flete: $('#lic_flete').length ? (parseFloat($('#lic_flete').val()) || 0) : 0,
            id_forma_pago: id_forma_pago,
            metodo_entrega: metodo_entrega,
            notas: $('#lic_notas').val(),
            carrito: JSON.stringify(carritoLicitacion)
        };

        $.post('api/guardar_cotizacion.php', datos, function(res) {
            if(res.success) {
                Swal.fire({ icon: 'success', title: 'Petición Guardada', text: 'Nro: ' + res.nro_cotizacion, showCancelButton: true, confirmButtonText: 'Imprimir PDF Especial', cancelButtonText: 'Cerrar' }).then((result) => {
                    let llaveSegura = btoa(res.id_cotizacion + '-' + res.nro_cotizacion);
                    if (result.isConfirmed) window.open('imprimir_licitacion.php?id=' + res.id_cotizacion + '&k=' + llaveSegura, '_blank');
                    
                    carritoLicitacion = [];
                    if($('#lic_desc_manual').length) $('#lic_desc_manual').val(0);
                    if($('#lic_desc_logistica').length) $('#lic_desc_logistica').val(0);
                    if($('#lic_flete').length) $('#lic_flete').val(0);
                    if($('#lic_aplica_iva').length) $('#lic_aplica_iva').prop('checked', true).closest('.custom-check-small').addClass('active-check');
                    actualizarTablaLicitacion();
                    $('#formGuardarLicitacion')[0].reset();
                    $('#lic_nro_cotizacion').val('');
                    $('.custom-check-small').removeClass('active-check'); 
                    $('#id_edit_cotizacion').val('0');
                    $('#btnMenuCotizaciones').click(); 
                });
            } else { Swal.fire('Atención', res.msg, 'error'); }
        }, 'json').fail(function(){
            Swal.fire('Error', 'Fallo servidor', 'error');
        }).always(() => $('#btnVisualGuardarLicitacion').prop('disabled', false).html('Generar Cotización Oficial <i class="bi bi-file-pdf ms-2"></i>'));
    });

    // ================= CRUD DE CATÁLOGO =================
    $(document).on('change', '#filtro_cat_gestion', function() {
        let idCat = $(this).val();
        if(idCat) {
            $('#filtro_subcat_gestion option').hide();
            $('#filtro_subcat_gestion option[value=""]').show();
            $('#filtro_subcat_gestion option[data-padre="'+idCat+'"]').show();
            $('#filtro_subcat_gestion').val('');
        } else {
            $('#filtro_subcat_gestion option').show();
        }
        filtrarTablaProductos();
    });

    $(document).on('change', '#filtro_subcat_gestion', function() {
        filtrarTablaProductos();
    });

    function filtrarTablaProductos() {
        let idCat = $('#filtro_cat_gestion').val();
        let idSubcat = $('#filtro_subcat_gestion').val();
        
        $('.fila-prod').each(function() {
            let show = true;
            if (idCat && $(this).data('idcat') != idCat) show = false;
            if (idSubcat && $(this).data('idsubcat') != idSubcat) show = false;
            $(this).toggle(show);
        });
    }

    $('#buscadorGestion').on('input', function() {
        let filtro = removerAcentos($(this).val().trim());
        $('.fila-gestion').each(function() {
            let texto = removerAcentos($(this).text());
            $(this).toggle(texto.indexOf(filtro) > -1);
        });
    });

    $(document).on('submit', '#formCrudCatalogo', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let entidad = formData.get('entidad');
        let botonSubmit = $(this).find('.btn-submit-crud');
        
        botonSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_gestion_catalogo.php', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false});
                    abrirModalGestion(entidad); 
                } else { 
                    Swal.fire('Error', res.msg, 'error'); 
                    botonSubmit.prop('disabled', false).text('Guardar');
                }
            }
        });
    });

    $(document).on('click', '.btn-editar-crud', function() {
        let id = $(this).data('id');
        let nombre = $(this).data('nombre');
        $('#formCrudCatalogo input[name="id_edit"]').val(id);
        $('#formCrudCatalogo input[name="nombre"]').val(nombre);
        if($(this).data('precio')) $('#formCrudCatalogo input[name="precio"]').val($(this).data('precio'));
        if($(this).data('fk')) $('#formCrudCatalogo select[name="id_subcategoria"], #formCrudCatalogo select[name="id_categoria"]').val($(this).data('fk'));
        $('#formCrudCatalogo button[type="submit"]').text('Actualizar').removeClass('btn-primary').addClass('btn-warning');
        $('#btnCancelarCrud').removeClass('d-none');
        
        $('#modalGestionCatalogo .modal-body').animate({ scrollTop: 0 }, 400);
        let form = $('#formCrudCatalogo');
        form.css({'transition': 'all 0.4s ease', 'background-color': '#fff3cd', 'transform': 'scale(1.02)'});
        setTimeout(() => { form.css({'background-color': '#fff', 'transform': 'scale(1)'}); }, 800);
    });

    $(document).on('click', '#btnCancelarCrud', function() {
        $('#formCrudCatalogo')[0].reset();
        $('#formCrudCatalogo input[name="id_edit"]').val('0');
        $('#formCrudCatalogo button[type="submit"]').text('Guardar').removeClass('btn-warning').addClass('btn-primary');
        $(this).addClass('d-none');
    });

    $(document).on('click', '.btn-eliminar-crud', function() {
        let btn = $(this);
        let fila = btn.closest('tr');
        let entidad = btn.data('entidad');
        let id = btn.data('id');
        
        Swal.fire({ title: '¿Eliminar definitivamente?', icon: 'warning', showCancelButton: true }).then((result) => {
            if(result.isConfirmed) {
                fila.css('transition', 'background-color 0.5s').css('background-color', '#f8d7da');
                $.post('api/api_gestion_catalogo.php', { accion: 'eliminar', entidad: entidad, id: id }, function(res) {
                    if(res.success) {
                        fila.fadeOut(400, function() { $(this).remove(); });
                    } else { 
                        fila.css('background-color', ''); 
                        Swal.fire('Error', res.msg, 'error'); 
                    }
                }, 'json');
            }
        });
    });

    $(document).on('click', '.btn-ordenar-crud', function() {
        let entidad = $(this).data('entidad');
        let id = $(this).data('id');
        let dir = $(this).data('dir');
        
        let fCat = $('#filtro_cat_gestion').val();
        let fSub = $('#filtro_subcat_gestion').val();

        $.post('api/api_gestion_catalogo.php', { accion: 'ordenar', entidad: entidad, id: id, direccion: dir }, function(res) {
            if(res.success) {
                $.get('api/api_gestion_catalogo.php', { accion: 'ui_' + entidad.toLowerCase() }, function(html) {
                    $('#cuerpoModalGestion').html(html);
                    if (entidad === 'Producto') {
                        if (fCat) { $('#filtro_cat_gestion').val(fCat).trigger('change'); }
                        if (fSub) { setTimeout(()=> { $('#filtro_subcat_gestion').val(fSub).trigger('change'); }, 50); }
                    }
                });
            }
        }, 'json');
    });

    $(document).on('click', '#btnGuardarOrdenFinal', function(e) {
        e.preventDefault();
        if ($('#select_cotizacion_orden').val() === '') {
            return Swal.fire('Error', 'Debe seleccionar una cotización base.', 'warning');
        }

        const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Emitiendo...');
        const formData = new FormData();
        formData.append('accion', 'guardar_orden');
        formData.append('id_cotizacion', $('#select_cotizacion_orden').val());

        $.ajax({
            url: 'api/api_ordenes.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire('¡Éxito!', 'Orden de Fábrica Generada', 'success').then(() => {
                        $('#modalNuevaOrden').modal('hide');
                        if (typeof window.cargarListaOrdenes === 'function') window.cargarListaOrdenes();
                        if (typeof window.chequearCotizacionesPendientes === 'function') window.chequearCotizacionesPendientes();
                        window.open('imprimir_orden.php?id=' + res.id_orden, '_blank');
                    });
                } else {
                    Swal.fire('No se pudo emitir la orden', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor. No se emitió la orden.', 'error');
            },
            complete: function() {
                btn.html('<i class="bi bi-save"></i> Emitir Orden de Fábrica');
                btn.prop('disabled', !$('#ordenEstadoValidacion').hasClass('alert-success'));
            }
        });
    });
}); // FIN DOCUMENT READY

window.formatearNumeroWA = function(numero) {
    if (!numero) return "";
    let num = numero.replace(/\D/g, ''); 
    if (num.startsWith('0')) { num = '58' + num.substring(1); } 
    else if (!num.startsWith('58') && num.length === 10) { num = '58' + num; }
    return num;
};

window.enviarWASaludo = function(tlfRaw, nombreCliente) {
    let tlf = window.formatearNumeroWA(tlfRaw);
    if(tlf === "") return Swal.fire('Error', 'El cliente no tiene un número válido.', 'warning');
    let mensaje = `Estimado(a) *${nombreCliente}*, reciba un cordial y profesional saludo de parte del equipo de *Unifuerte, C.A.*\n\nEsperamos que se encuentre excelente. Estamos a su entera disposición para cualquier requerimiento corporativo o industrial que necesite.`;
    let url = `https://api.whatsapp.com/send?phone=${tlf}&text=${encodeURIComponent(mensaje)}`;
    window.open(url, '_blank');
};

window.enviarWACotizacion = function(tlfRaw, nombreCliente, nroCotizacion, idCotizacion, tipoCotizacion) {
    let tlf = window.formatearNumeroWA(tlfRaw);
    if(tlf === "") return Swal.fire('Error', 'El cliente no tiene un número válido.', 'warning');
    const ventanaWhatsApp = window.open('about:blank', '_blank');
    if (!ventanaWhatsApp) {
        return Swal.fire('Permite las ventanas emergentes', 'Habilita las ventanas emergentes para preparar el mensaje de WhatsApp.', 'warning');
    }
    $.post('api/api_portal_clientes.php', {
        accion: 'credenciales_cotizacion',
        id_cotizacion: idCotizacion,
        csrf: $('#csrf_portal_cliente').val()
    }, function(respuesta) {
        if (!respuesta || !respuesta.success) {
            ventanaWhatsApp.close();
            Swal.fire('No se pudo preparar el acceso', respuesta && respuesta.msg ? respuesta.msg : 'Intenta nuevamente.', 'error');
            return;
        }
        tlf = window.formatearNumeroWA(respuesta.telefono);
        if (tlf === '') {
            ventanaWhatsApp.close();
            Swal.fire('Teléfono no válido', 'El registro del cliente no tiene un WhatsApp válido; no se preparó el mensaje con sus credenciales.', 'warning');
            return;
        }
        let llaveSegura = btoa(idCotizacion + '-' + nroCotizacion);
        let baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
        let scriptDestino = (tipoCotizacion === 'LICITACION') ? 'imprimir_licitacion.php' : 'imprimir_cotizacion.php';
        let linkPdf = `${baseUrl}${scriptDestino}?id=${idCotizacion}&k=${llaveSegura}`;
        let accesoPortal = '';
        if (respuesta.portal_disponible) {
            const datosAcceso = respuesta.contrasena
                ? `*Usuario:* ${respuesta.usuario}\n*Contraseña:* ${respuesta.contrasena}`
                : `*Usuario:* ${respuesta.usuario}\n*Contraseña:* Usa la contraseña que te enviamos anteriormente; no ha cambiado.`;
            accesoPortal = `\n\nTambién puede consultar sus cotizaciones, pagos, facturas y avances de producción en el Portal Privado:\n🔗 ${respuesta.portal_url}\n${datosAcceso}\n\nPor seguridad, conserve estas credenciales y no las comparta. No vencen y no se cambian al reenviar una cotización.`;
        }
        let mensaje = `Estimado(a) *${respuesta.nombre || nombreCliente}*, reciba un cordial saludo de parte de la fábrica textil *Unifuerte, C.A.*\n\nPor este medio le hacemos entrega formal de su cotización *Nro. ${nroCotizacion}*.\n\nPuede visualizarla y descargarla aquí:\n🔗 ${linkPdf}${accesoPortal}\n\nQuedamos atentos a sus comentarios. Bendiciones.`;
        ventanaWhatsApp.location.href = `https://api.whatsapp.com/send?phone=${tlf}&text=${encodeURIComponent(mensaje)}`;
    }, 'json').fail(function(xhr) {
        ventanaWhatsApp.close();
        const respuesta = xhr.responseJSON;
        Swal.fire('No se pudo preparar el acceso', respuesta && respuesta.msg ? respuesta.msg : 'Error de comunicación con el servidor.', 'error');
    });
};

window.chequearCotizacionesPendientes = function() {
    $.get('api/api_ordenes.php', { accion: 'listar_cotizaciones_aptas' }, function(res) {
        if(res.success) {
            let total = res.data.length;
            if (total > 0) {
                $('#badge_pendientes_menu').text(total).show();
                let html = '';
                res.data.forEach(c => {
                    html += `<li class="list-group-item"><a class="text-decoration-none text-dark d-block" href="#" onclick="window.abrirOrdenDesdeNotificacion(${c.ID})">
                        <strong class="text-primary">${c.nro_cotizacion}</strong><br>
                        <span class="small text-muted"><i class="bi bi-person"></i> ${c.cliente}</span>
                    </a></li>`;
                });
                $('#listaCotizacionesProduccion').html(html);
            } else {
                $('#badge_pendientes_menu').hide();
                $('#listaCotizacionesProduccion').html('<li class="list-group-item text-muted text-center py-3">No hay trabajos pendientes.</li>');
            }
        }
    }, 'json');
};

window.abrirOrdenDesdeNotificacion = function(idCotizacion) {
    $('#modalNotifLogistica').modal('hide');
    $('#btnMenuOrdenes').click();
    window.abrirModalNuevaOrden();
    setTimeout(() => { $('#select_cotizacion_orden').val(idCotizacion).trigger('change'); }, 500);
};

window.formatoMoneda = function(valor) { return parseFloat(valor).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

window.cancelarEdicionCliente = function() {
    let form = $('#formClienteAjax');
    form[0].reset();
    form.removeClass('was-validated'); 
    $('#cli_id').val('0');
    $('#tituloFormCliente').text('Nuevo Cliente');
    $('#btnRegistrarCliente').text('Guardar Cliente').css('background-color', '#0B4C8C');
    $('#btnCancelarEdicion').addClass('d-none');
};

window.cargarCatalogoSPA = function() {
    $.get('api/api_gestion_catalogo.php', { accion: 'listar_productos_venta' }, function(res) {
        if(res.success) {
            let html = '<div class="row g-3 w-100" style="display:flex;">';
            let pillsHtml = '';
            let currentCat = '';
            let currentSub = '';

            res.data.forEach(p => {
                let cat = p.categoria || 'Sin Categoría';
                let sub = p.subcategoria || 'Generales';

                if (cat !== currentCat) {
                    currentCat = cat;
                    let catId = 'cat_' + cat.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, '_').toLowerCase();
                    pillsHtml += `<a href="#${catId}" class="pill-cat">${cat}</a>`;
                    html += `<div id="${catId}" class="col-12 categoria-header mt-4"><h3 class="fw-bold" style="color: #0B4C8C; border-bottom: 2px solid #FF7518; padding-bottom: 5px;">${cat}</h3></div>`;
                    currentSub = ''; 
                }

                if (sub !== currentSub) {
                    currentSub = sub;
                    html += `<div class="col-12 subcategoria-header mt-3"><h5 class="fw-bold text-secondary"><i class="bi bi-tag-fill me-2" style="color:#FF7518;"></i>${sub}</h5></div>`;
                }

                let imgSrc = p.foto ? `../images/uploads/${p.foto}` : '../img/unifuerte.jpg';
                html += `
                <div class="col-6 col-md-4 col-xl-3 tarjeta-producto" data-nombre="${p.nombre}">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; cursor: pointer; transition: 0.3s;" onclick="window.abrirModalPrenda(${p.id}, '${p.nombre.replace(/'/g, "\\'").replace(/"/g, "&quot;")}', ${p.precio}, '${cat.replace(/'/g, "\\'").replace(/"/g, "&quot;")}')">
                        <div style="height: 140px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f9f9f9; border-radius: 12px 12px 0 0;">
                            <img src="${imgSrc}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="card-body p-2 text-center">
                            <h6 class="fw-bold mb-1" style="font-size: 0.85rem; color: #333;">${p.nombre}</h6>
                            <h5 class="fw-bold m-0" style="color: #0B4C8C;">${window.formatoMoneda(p.precio)} $</h5>
                        </div>
                    </div>
                </div>`;
            });
            html += `</div>`; 
            $('#contenedorCatalogo').html(html);
            $('#navPillsCategorias').html(pillsHtml);
        }
    }, 'json');
};

window.abrirModalPrenda = function(id, nombre, precio, categoria) {
    $('#modal_edit_index').val(-1);
    $('#modal_id_prod').val(id);
    $('#modal_nombre_prod').text(nombre);
    $('#modal_precio_prod').val(precio);
    $('#modal_precio_display').text(window.formatoMoneda(precio) + ' $Unit.');$('#modal_cantidad').val(1);
    $('#modal_categoria_prod').val(categoria);

    // [PARCHE INYECTADO] Insertar dinámicamente el selector de tela si no existe en el HTML
    if ($('#wrapper_modal_tela').length === 0) {
        $('#wrapper_modal_talla').after(`
            <div class="mb-3 px-2" id="wrapper_modal_tela">
                <label class="form-label fw-bold small text-muted" style="font-size: 0.85rem;">Tipo de Tela / Material</label>
                <input type="text" id="modal_tela" list="lista_telas_inteligente" class="form-control form-control-sm border-primary fw-bold" placeholder="Escriba o seleccione..." autocomplete="off">
            </div>
        `);
    }

    $('#modal_tela').val('');

    if (categoria === 'Arte Final') {
        $('#wrapper_modal_genero').hide();
        $('#wrapper_modal_talla').hide();
        $('#wrapper_modal_tela').hide();
        $('#modal_genero').val('No especificado');
        $('#wrapper_modal_detalle').show();
        $('#modal_detalle_diseno').val(''); 
        let opciones = '<option value="" data-cant="9999">No vincular (Arte suelto)</option>';
        carritoMemoria.forEach((item) => {
            if (item.categoria !== 'Arte Final') {
                opciones += `<option value="${item.nombre} (${item.genero})" data-cant="${item.cantidad}">Vincular a: ${item.nombre} (${item.genero}) - Hay ${item.cantidad}</option>`;
            }
        });
        $('#modal_vincular_prenda').html(opciones);
        $('#wrapper_modal_vincular').show();
    } else {
        $('#wrapper_modal_genero').show();
        $('#wrapper_modal_talla').show();
        $('#wrapper_modal_tela').show();
        $('#modal_genero').val('Adulto (Dama o Caballero)');
        if(typeof window.actualizarTallaModalPrenda === 'function') window.actualizarTallaModalPrenda(document.getElementById('modal_genero'));
        $('#wrapper_modal_detalle').hide();
        $('#modal_detalle_diseno').val(''); 
        $('#wrapper_modal_vincular').hide();
        $('#modal_vincular_prenda').val('');
    }
    new bootstrap.Modal(document.getElementById('modalAgregarPrenda')).show();
};

window.abrirModalEditarPrenda = function(index) {
    let item = carritoMemoria[index];
    $('#modal_edit_index').val(index);
    $('#modal_id_prod').val(item.id_producto);
    $('#modal_nombre_prod').text(item.nombre);
    $('#modal_precio_prod').val(item.precio);
    $('#modal_precio_display').text(window.formatoMoneda(item.precio) + ' $Unit.');$('#modal_cantidad').val(item.cantidad);
    $('#modal_categoria_prod').val(item.categoria);

    // [PARCHE INYECTADO] Insertar dinámicamente el selector de tela si no existe en el HTML
    if ($('#wrapper_modal_tela').length === 0) {
        $('#wrapper_modal_talla').after(`
            <div class="mb-3 px-2" id="wrapper_modal_tela">
                <label class="form-label fw-bold small text-muted" style="font-size: 0.85rem;">Tipo de Tela / Material</label>
                <input type="text" id="modal_tela" list="lista_telas_inteligente" class="form-control form-control-sm border-primary fw-bold" placeholder="Escriba o seleccione..." autocomplete="off">
            </div>
        `);
    }

    $('#modal_tela').val(item.tela_guardada || '');

    if (item.categoria === 'Arte Final') {
        $('#wrapper_modal_genero').hide();
        $('#wrapper_modal_talla').hide();
        $('#wrapper_modal_tela').hide();
        $('#modal_genero').val('No especificado');
        $('#wrapper_modal_detalle').show();
        $('#modal_detalle_diseno').val(item.posicion_pura || ''); 
        let opciones = '<option value="" data-cant="9999">No vincular (Arte suelto)</option>';
        carritoMemoria.forEach((p, idx) => {
            if (p.categoria !== 'Arte Final') {
                let selected = (p.nombre + ' (' + p.genero + ')' === item.vinculo_puro) ? 'selected' : '';
                opciones += `<option value="${p.nombre} (${p.genero})" data-cant="${p.cantidad}" ${selected}>Vincular a: ${p.nombre} (${p.genero}) - Hay ${p.cantidad}</option>`;
            }
        });
        $('#modal_vincular_prenda').html(opciones);
        $('#wrapper_modal_vincular').show();
    } else {
        $('#wrapper_modal_genero').show();
        $('#wrapper_modal_talla').show();
        $('#wrapper_modal_tela').show();
        $('#modal_genero').val(item.genero);
        if(typeof window.actualizarTallaModalPrenda === 'function') window.actualizarTallaModalPrenda(document.getElementById('modal_genero'));
        setTimeout(() => { $('#modal_talla').val(item.talla_guardada || ''); }, 50);
        $('#wrapper_modal_detalle').hide();
        $('#modal_detalle_diseno').val(''); 
        $('#wrapper_modal_vincular').hide();
        $('#modal_vincular_prenda').val('');
    }
    new bootstrap.Modal(document.getElementById('modalAgregarPrenda')).show();
};

window.ajustarCant = function(val) { 
    let cant = parseInt($('#modal_cantidad').val()) || 1; 
    let nueva = cant + val; 
    if(nueva >= 1) $('#modal_cantidad').val(nueva); 
};

window.confirmarAgregarAlCarrito = function() {
    let editIndex = parseInt($('#modal_edit_index').val());
    let id = $('#modal_id_prod').val(), 
        nombre = $('#modal_nombre_prod').text(), 
        precio = parseFloat($('#modal_precio_prod').val()), 
        genero = $('#modal_genero').val(), 
        talla = $('#modal_talla').val(),
        tela = $('#modal_tela').val(),
        cantidad = parseInt($('#modal_cantidad').val()),
        posicion_pura = $('#modal_detalle_diseno').val(),
        categoria = $('#modal_categoria_prod').val(),
        vinculo_puro = $('#modal_vincular_prenda').val();

    let detalleFinal = '';
    if (categoria === 'Arte Final') {
        if (vinculo_puro) { detalleFinal = `[Para: ${vinculo_puro}] - Ubicación: ${posicion_pura}`; } 
        else { detalleFinal = posicion_pura ? `Ubicación: ${posicion_pura}` : ''; }
    } else {
        detalleFinal = `Talla: ${talla || 'N/A'} | Tela: ${tela || 'N/A'}`;
    }

    let idUnico = id + '_' + genero + '_' + talla + '_' + tela + '_' + (detalleFinal.replace(/\s+/g, ''));
    let nuevoItem = { 
        idUnico: idUnico, id_producto: id, nombre: nombre, genero: genero, 
        talla_guardada: talla, tela_guardada: tela, precio: precio, cantidad: cantidad, 
        subtotal: precio * cantidad, detalle_diseno: detalleFinal, posicion_pura: posicion_pura,
        vinculo_puro: vinculo_puro, categoria: categoria
    };

    if (editIndex > -1) {
        carritoMemoria[editIndex] = nuevoItem;
    } else {
        let index = carritoMemoria.findIndex(i => i.idUnico === idUnico);
        if(index > -1) {
            carritoMemoria[index].cantidad += cantidad;
            carritoMemoria[index].subtotal = carritoMemoria[index].cantidad * carritoMemoria[index].precio;
        } else { carritoMemoria.push(nuevoItem); }
    }
    
    window.actualizarResumenCarrito();
    $('#modalAgregarPrenda').modal('hide');
};

window.fijarCantCarrito = function(index, val) {
    let item = carritoMemoria[index];
    let nuevaCant = parseInt(val);
    if(isNaN(nuevaCant) || nuevaCant < 1) { nuevaCant = 1; }
    item.cantidad = nuevaCant;
    item.subtotal = item.cantidad * item.precio;
    window.actualizarResumenCarrito();
};

window.ajustarCantCarrito = function(index, val) {
    let item = carritoMemoria[index];
    let nuevaCant = item.cantidad + val;
    if(nuevaCant >= 1) {
        item.cantidad = nuevaCant;
        item.subtotal = item.cantidad * item.precio;
        window.actualizarResumenCarrito();
    }
};

window.eliminarDelCarrito = function(index) { carritoMemoria.splice(index, 1); window.actualizarResumenCarrito(); };

window.actualizarResumenCarrito = function() {
    let html = '', subtotalBruto = 0, totalPrendas = 0;
    
    let aplica_iva = $('#cot_aplica_iva').length ? $('#cot_aplica_iva').is(':checked') : true;
    let desc_manual = $('#cot_desc_manual').length ? (parseFloat($('#cot_desc_manual').val()) || 0) : 0;
    let descLogisticaVal = $('#cot_desc_logistica').length ? (parseFloat($('#cot_desc_logistica').val()) || 0) : 0;
    let fleteVal = $('#cot_flete').length ? (parseFloat($('#cot_flete').val()) || 0) : 0;

    if(carritoMemoria.length === 0) {
        html = '<div class="text-center text-muted small py-4">No hay prendas agregadas.</div>';
    } else {
        carritoMemoria.forEach((item, index) => {
            subtotalBruto += item.subtotal; 
            totalPrendas += item.cantidad; 

            let generoText = item.genero && item.genero !== 'No especificado' ? ` (${item.genero})` : '';
            let detalleHtml = item.detalle_diseno ? `<div class="text-secondary mt-1" style="font-size: 0.75rem;"><i class="bi bi-info-circle-fill text-primary"></i> <b>${item.detalle_diseno}</b></div>` : '';

            html += `<div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom bg-white p-2 rounded shadow-sm">
                <div style="flex:1;">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${item.nombre}${generoText}</div>
                    ${detalleHtml}
                    <div class="d-flex align-items-center mt-2">
                        <button type="button" class="btn-qty btn-bloqueable" onclick="window.ajustarCantCarrito(${index}, -1)">-</button>
                        <input type="number" value="${item.cantidad}" class="text-center fw-bold mx-1 border btn-bloqueable" style="width: 45px; height: 30px; border-radius: 6px; border-color: #ddd !important; outline: none;" onchange="window.fijarCantCarrito(${index}, this.value)" onfocus="this.select()" min="1">
                        <button type="button" class="btn-qty btn-bloqueable" onclick="window.ajustarCantCarrito(${index}, 1)">+</button>
                        <span class="ms-3 fw-bold text-primary">${window.formatoMoneda(item.precio)}$ <small class="text-muted fw-normal">c/u</small></span>
                    </div>
                </div>
                <div class="text-end" style="min-width: 80px;">
                    <div class="fw-bold mb-2 fs-6 text-dark">${window.formatoMoneda(item.subtotal)}$</div>
                    <div class="d-flex justify-content-end mt-1">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-bloqueable px-2" onclick="window.abrirModalEditarPrenda(${index})" title="Editar Detalles"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-bloqueable px-2" onclick="window.eliminarDelCarrito(${index})"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>`;
        });
    }
    
    $('#listaCarritoBubble').html(html);
    $('#resumenCheckoutBox').html(html);

    let montoDescLogistica = descLogisticaVal;
    let montoFlete = fleteVal;
    let montoDescuentoPorc = subtotalBruto * (desc_manual / 100);
    let baseImponible = subtotalBruto - montoDescuentoPorc + montoDescLogistica + montoFlete;
    if (baseImponible < 0) baseImponible = 0;

    let iva = aplica_iva ? (baseImponible * 0.16) : 0;
    
    let igtf = 0;
    if($('input[name="id_forma_pago"]:checked').length > 0 && $('input[name="id_forma_pago"]:checked').closest('label').text().toLowerCase().indexOf('divisa') !== -1) {
        igtf = baseImponible * 0.03;
    }
    
    let total = baseImponible + iva + igtf;

    $('#lblBurbujaItems').text(totalPrendas);
    $('#lblSubtotal, #lblSubtotalCheck').text(window.formatoMoneda(subtotalBruto) + ' $');
    
    if (montoDescLogistica > 0) {
        $('#lblDescLogistica, #lblDescLogisticaCheck').text(window.formatoMoneda(montoDescLogistica) + ' $'); 
        $('#cajaDescLogistica, #cajaDescLogisticaCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaDescLogistica, #cajaDescLogisticaCheck').attr('style', 'display: none !important;'); }

    if (montoFlete > 0) {
        $('#lblFlete, #lblFleteCheck').text(window.formatoMoneda(montoFlete) + ' $'); 
        $('#cajaFlete, #cajaFleteCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaFlete, #cajaFleteCheck').attr('style', 'display: none !important;'); }

    if(montoDescuentoPorc > 0) { 
        $('#lblPorcentajeDesc, #lblPorcentajeDescCheck').text(desc_manual + '%'); 
        $('#lblMontoDesc, #lblMontoDescCheck').text('-' + window.formatoMoneda(montoDescuentoPorc) + ' $'); 
        $('#cajaDescuento, #cajaDescuentoCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaDescuento, #cajaDescuentoCheck').attr('style', 'display: none !important;'); }

    if (montoDescLogistica > 0 || montoDescuentoPorc > 0 || montoFlete > 0) {
        $('#lblBaseImponible, #lblBaseImponibleCheck').text(window.formatoMoneda(baseImponible) + ' $');
        $('#cajaBaseImponible, #cajaBaseImponibleCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaBaseImponible, #cajaBaseImponibleCheck').attr('style', 'display: none !important;'); }
    
    if (iva > 0) {
        $('#lblIva, #lblIvaCheck').text(window.formatoMoneda(iva) + ' $');
        $('#cajaIva, #cajaIvaCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaIva, #cajaIvaCheck').attr('style', 'display: none !important;'); }
    
    if(igtf > 0) { 
        $('#lblIgtf, #lblIgtfCheck').text(window.formatoMoneda(igtf) + ' $'); 
        $('#cajaIgtf, #cajaIgtfCheck').attr('style', 'display: flex !important;');
    } else { $('#cajaIgtf, #cajaIgtfCheck').attr('style', 'display: none !important;'); }

    $('#lblTotalGeneral, #lblTotalGeneralCheck').text(window.formatoMoneda(total) + ' $');
};

window.irAPagar = function() {
    $('#modalCarritoBubble').modal('hide');
    let $container = $('.main-content');
    let $target = $("#seccionCheckout");
    if($target.length) {
        $container.animate({ scrollTop: $target.offset().top - $container.offset().top + $container.scrollTop() - 20 }, 600);
    }
    $('#seccionCheckout').css('transform', 'scale(1.02)');
    setTimeout(() => { $('#seccionCheckout').css('transform', 'scale(1)'); }, 1000);
};

window.abrirModalGestion = function(tipo) {
    $('#tituloModalGestion').text('Gestión de ' + tipo + 's');
    $('#buscadorGestion').val('');
    $('#cuerpoModalGestion').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('modalGestionCatalogo')).show();
    $.get('api/api_gestion_catalogo.php', { accion: 'ui_' + tipo.toLowerCase() }, function(html) {
        $('#cuerpoModalGestion').html(html);
    });
};

window.cargarHistorial = function() {
    $('#tablaHistorialBody').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    let f_desde = $('#filtro_desde_hist').val();
    let f_hasta = $('#filtro_hasta_hist').val();
    let f_estado = $('#filtro_estado_hist').val(); 
    let f_vendedor = $('#filtro_vendedor_hist').length ? $('#filtro_vendedor_hist').val() : '';

    $.get('api/api_cotizaciones.php', { accion: 'listar', desde: f_desde, hasta: f_hasta, estado: f_estado, vendedor: f_vendedor }, function(res) {
        if(res.success) {
            let html = '';
            let contador = 1; 
            res.data.forEach(c => {
                let badgeClass = 'bg-danger'; 
                let estadoLC = c.estado_pago.toLowerCase();
                if(estadoLC === 'pago inicial' || estadoLC === 'pago medio') badgeClass = 'bg-warning text-dark'; 
                else if(estadoLC === 'pagado') badgeClass = 'bg-success'; 

                let puedeEditar = (c.tipo_cotizacion === 'LICITACION') ? JS_tieneVector(30) : JS_tieneVector(8);
                let btnEditar = puedeEditar ? `<button class="btn btn-sm btn-outline-warning fw-bold" onclick="window.cargarCotizacionParaEditar(${c.ID}, ${c.id_cliente}, '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Editar"><i class="bi bi-pencil"></i></button>` : '';
                let btnEliminar = JS_tieneVector(9) ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="window.eliminarCotizacion(${c.ID})"><i class="bi bi-trash"></i></button>` : '';

                let llaveSegura = btoa(c.ID + '-' + c.nro_cotizacion);

                let btnWhatsApp = JS_tieneVector(22) ? `
                <div class="dropdown d-inline-block ms-2">
                    <button class="btn btn-sm btn-success rounded-pill dropdown-toggle" data-bs-toggle="dropdown" title="Contactar por WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </button>
                    <ul class="dropdown-menu shadow-sm" style="font-size: 0.85rem;">
                        <li><a class="dropdown-item fw-bold text-dark" href="#" onclick="window.enviarWASaludo('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}')"><i class="bi bi-chat-dots text-primary me-2"></i> Enviar Saludo</a></li>
                        <li><a class="dropdown-item fw-bold text-dark" href="#" onclick="window.enviarWACotizacion('${c.cliente_tlf}', '${c.cliente_nombre.replace(/'/g, "\\'")}', '${c.nro_cotizacion}', ${c.ID}, '${c.tipo_cotizacion}')"><i class="bi bi-file-pdf text-danger me-2"></i> Enviar Cotización</a></li>
                    </ul>
                </div>` : '';

                let btnEspecificar = '';
                if (estadoLC === 'pago inicial' || estadoLC === 'pago medio' || estadoLC === 'pagado') {
                    if (c.enviado_logistica == 1) {
                        btnEspecificar = `<button class="btn btn-sm btn-success fw-bold ms-1" title="Enviado a Logística" disabled><i class="bi bi-check2-all"></i></button>`;
                    } else {
                        let btnRegla = JS_tieneVector(28) ? `<button class="btn btn-sm btn-outline-info fw-bold ms-1" onclick="window.abrirModalEspecificarDetalles(${c.ID}, '${c.nro_cotizacion}')" title="Especificar Tallas y Telas para Taller"><i class="bi bi-rulers"></i></button>` : '';
                        let btnCamion = JS_tieneVector(29) ? `<button class="btn btn-sm btn-outline-dark fw-bold ms-1" onclick="window.enviarALogistica(${c.ID})" title="Enviar a Logística"><i class="bi bi-truck"></i></button>` : '';
                        btnEspecificar = btnRegla + btnCamion;
                    }
                }

                let btnComision = '';
                let igtfNum = parseFloat(c.igtf) || 0;
                let ivaNum = parseFloat(c.iva) || 0;
                let baseImpParaComision = parseFloat(c.total) - ivaNum - igtfNum;

                if (estadoLC === 'pago inicial' || estadoLC === 'pago medio' || estadoLC === 'pagado') {
                    if (c.comision_pagada == 1) {
                        btnComision = `<button class="btn btn-sm btn-success text-white fw-bold ms-1" onclick="window.verComision(${c.ID})" title="Comisión Pagada (Ver Recibo)"><i class="bi bi-coin"></i></button>`;
                    } else if (JS_tieneVector(35)) {
                        btnComision = `<button class="btn btn-sm btn-warning text-dark fw-bold ms-1" onclick="window.abrirModalComision(${c.ID}, ${baseImpParaComision})" title="Pagar y Registrar Comisión"><i class="bi bi-coin"></i></button>`;
                    }
                }

                let scriptVistaPdf = c.tipo_cotizacion === 'LICITACION' ? 'imprimir_licitacion.php' : 'imprimir_cotizacion.php';

                html += `<tr>
                    <td class="fw-bold text-muted">${contador}</td>
                    <td class="fw-bold text-nowrap">
                        ${c.nro_cotizacion}
                        ${c.tipo_cotizacion === 'LICITACION' ? '<br><span class="badge bg-dark" style="font-size:0.65rem;">Licitación</span>' : ''}
                        ${c.numero_factura_fiscal ? `<br><span class="badge bg-success-subtle text-success border" style="font-size:0.65rem;">Factura ${c.numero_factura_fiscal}</span>` : ''}
                        ${c.facturas_anuladas_lista ? `<br><span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.65rem;">Anulada: ${c.facturas_anuladas_lista}</span>` : ''}
                    </td>
                    <td style="min-width: 200px;">${c.cliente_nombre} ${btnWhatsApp}</td>
                    <td style="min-width:170px;max-width:240px;white-space:normal;">${String(c.notas || '—').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]))}</td>
                    <td class="text-nowrap">${c.fecha_emision}</td>
                    <td class="fw-bold text-nowrap" style="color: #0B4C8C;">${window.formatoMoneda(c.total)} $</td>
                    <td class="text-nowrap">
                        ${(JS_tieneVector(8) || JS_tieneVector(30)) ? `
                        <span class="badge ${badgeClass} dropdown-toggle" style="cursor:pointer; padding:6px 10px;" data-bs-toggle="dropdown">${c.estado_pago.toUpperCase()}</span>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item fw-bold text-danger" href="#" onclick="window.cambiarEstadoDirecto(${c.ID}, 'sin pagar')"><i class="bi bi-circle-fill text-danger"></i> Sin Pagar</a></li>
                            <li><a class="dropdown-item fw-bold text-warning" href="#" onclick="window.abrirModalPago(${c.ID}, 'Pago Inicial', '${c.total}', '${c.sub_total}', '${c.nro_cotizacion}', '${String(c.cliente_nombre || '').replace(/'/g, "\\'")}', '${c.pago_valor_usd || ''}', '${c.cliente_tlf || ''}', ${window.totalRetencionesPagosUsd(c.pago_retenciones, c.tasa_factura_fiscal)}, ${Number(c.tasa_factura_fiscal) || 0}, ${Number(c.iva) || 0}, ${Number(c.sub_total) || 0}, ${Number(c.descuento) || 0}, ${Number(c.servicio_logistico) || 0}, ${Number(c.flete) || 0})"><i class="bi bi-circle-fill text-warning"></i> Pago Inicial</a></li>
                            <li><a class="dropdown-item fw-bold text-success" href="#" onclick="window.abrirModalPago(${c.ID}, 'Pagado', '${c.total}', '${c.sub_total}', '${c.nro_cotizacion}', '${String(c.cliente_nombre || '').replace(/'/g, "\\'")}', '${c.pago_valor_usd || ''}', '${c.cliente_tlf || ''}', ${window.totalRetencionesPagosUsd(c.pago_retenciones, c.tasa_factura_fiscal)}, ${Number(c.tasa_factura_fiscal) || 0}, ${Number(c.iva) || 0}, ${Number(c.sub_total) || 0}, ${Number(c.descuento) || 0}, ${Number(c.servicio_logistico) || 0}, ${Number(c.flete) || 0})"><i class="bi bi-circle-fill text-success"></i> Pagado</a></li>
                        </ul>` : `<span class="badge ${badgeClass}" style="padding:6px 10px;">${c.estado_pago.toUpperCase()}</span>`}
                    </td>
                    <td class="text-center text-nowrap" style="min-width: 200px;">
                        ${btnEditar} ${btnEspecificar} ${btnComision}
                        <button class="btn btn-sm btn-outline-secondary fw-bold ms-1" onclick="window.verDetalleHistorialCotizacion(${c.ID}, ${c.sub_total}, ${c.descuento}, ${c.iva}, ${c.total}, '${c.referencias_pago || ''}', '${c.fotos_pagos || ''}', '${c.pago_fecha || ''}', '${c.pago_tasa_bcv || ''}', '${c.pago_valor_usd || ''}', '${c.pago_banco || ''}')" title="Ver Resumen"><i class="bi bi-eye"></i></button>
                        <a href="${scriptVistaPdf}?id=${c.ID}&k=${llaveSegura}" target="_blank" class="btn btn-sm btn-outline-primary ms-1" title="Ver PDF"><i class="bi bi-file-pdf"></i></a>
                        ${['ESTANDAR', 'LICITACION'].includes(String(c.tipo_cotizacion || '').toUpperCase()) ? `<button class="btn btn-sm btn-outline-success ms-1" onclick="window.abrirFacturaFiscal(${c.ID})" aria-label="${c.tipo_cotizacion === 'LICITACION' ? 'Generar o ver factura fiscal de licitación' : (c.numero_factura_fiscal ? 'Ver factura fiscal ' + c.numero_factura_fiscal : 'Generar factura fiscal')}" title="${c.tipo_cotizacion === 'LICITACION' ? 'Generar o ver factura fiscal de licitación' : (c.numero_factura_fiscal ? 'Ver factura fiscal ' + c.numero_factura_fiscal : 'Generar factura fiscal')}"><i class="bi bi-receipt-cutoff"></i>${c.tipo_cotizacion === 'LICITACION' ? ' Fiscal' : ''}</button>` : ''}
                        ${btnEliminar}
                    </td>
                </tr>`;
                contador++;
            });
            $('#tablaHistorialBody').html(html || '<tr><td colspan="8" class="text-center">No hay cotizaciones registradas.</td></tr>');
        }
    }, 'json').fail(function() {
        $('#tablaHistorialBody').html('<tr><td colspan="8" class="text-center text-danger">Error de conexión al cargar historial.</td></tr>');
    });
};

window.abrirFacturaFiscal = function(idCotizacion) {
    $.post('api/api_cotizaciones.php', {
        accion: 'obtener_factura_fiscal',
        id: idCotizacion
    }, function(res) {
        if (!res.success) {
            Swal.fire('Error', res.msg || 'No se pudieron cargar los datos de facturación.', 'error');
            return;
        }

        const facturaExistente = res.data;
        const facturasAnuladas = Array.isArray(res.anuladas) ? res.anuladas : [];

        let htmlAnuladas = '';
        if (facturasAnuladas.length > 0) {
            const itemsAnuladas = facturasAnuladas.map(function(fa) {
                const motivoTxt = fa.motivo_anulacion ? `Motivo: ${$('<div>').text(fa.motivo_anulacion).html()}` : 'Sin motivo especificado';
                return `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <div class="text-start">
                            <span class="badge bg-danger">ANULADA N° ${fa.numero}</span>
                            <span class="text-muted ms-1">Ctrl: ${$('<div>').text(fa.control || '').html()} | Tasa: ${fa.tasa}</span>
                            <div class="text-muted" style="font-size: 0.72rem;">${motivoTxt} (${fa.fecha_anulacion || fa.fecha_emision})</div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0 ms-2">
                            <a href="imprimir_factura_fiscal.php?id=${encodeURIComponent(idCotizacion)}&factura=${encodeURIComponent(fa.numero_raw)}" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2" title="Ver Factura Fiscal Anulada">Fiscal</a>
                            <a href="imprimir_factura_final.php?id=${encodeURIComponent(idCotizacion)}&factura=${encodeURIComponent(fa.numero_raw)}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Ver Factura Final Anulada">Final</a>
                        </div>
                    </div>
                `;
            }).join('');

            htmlAnuladas = `
                <hr class="my-3">
                <div class="text-start bg-light p-2 rounded border border-danger-subtle">
                    <div class="fw-bold text-danger small mb-1"><i class="bi bi-archive"></i> Registro de Facturas Anuladas en esta Cotización (${facturasAnuladas.length})</div>
                    <div style="max-height: 150px; overflow-y: auto;">
                        ${itemsAnuladas}
                    </div>
                </div>
            `;
        }

        Swal.fire({
            title: facturaExistente ? 'Gestión de Factura Fiscal' : 'Generar Factura Fiscal',
            width: '600px',
            html: `
                <label for="facturaFiscalNumero" class="form-label text-start w-100 fw-bold">Número de factura activa (correlativo automático)</label>
                <input id="facturaFiscalNumero" class="swal2-input mt-0 w-100 mx-0" value="${facturaExistente ? facturaExistente.numero : 'Se asignará el siguiente correlativo al emitir'}" readonly>
                <label for="facturaFiscalControl" class="form-label text-start w-100 fw-bold mt-3">Número de control (manual)</label>
                <input id="facturaFiscalControl" class="swal2-input mt-0 w-100 mx-0" maxlength="60" placeholder="Escribe el número de control">
                <label for="facturaFiscalTasa" class="form-label text-start w-100 fw-bold mt-3">Tasa BCV del día del pago (Bs por USD)</label>
                <input id="facturaFiscalTasa" type="number" min="0.0001" step="0.0001" class="swal2-input mt-0 w-100 mx-0" placeholder="Ej: 852.42">
                <div class="small text-muted mt-2">El correlativo se asigna al emitir y queda registrado para esta cotización.</div>
                ${facturaExistente ? `
                    <hr class="my-3">
                    <div class="small text-start text-muted">La opción final usa el mismo número, control y tasa ya emitidos; imprime sobre formato libre con espacios en blanco arriba y abajo.</div>
                    <label for="facturaFinalOrdenCompra" class="form-label text-start w-100 fw-bold mt-2">Orden de compra (opcional)</label>
                    <input id="facturaFinalOrdenCompra" class="swal2-input mt-0 w-100 mx-0" maxlength="100" placeholder="Si no tiene, se indicará S/N">
                    <div class="mt-3 text-end">
                        <button type="button" id="btnAnularFacturaActiva" class="btn btn-sm btn-danger fw-bold w-100 py-2">
                            <i class="bi bi-x-octagon-fill"></i> Anular Factura Actual (N° ${facturaExistente.numero}) y liberar cotización
                        </button>
                    </div>
                ` : ''}
                ${htmlAnuladas}
            `,
            confirmButtonText: facturaExistente ? 'Guardar e imprimir Fiscal' : 'Emitir nueva factura',
            confirmButtonColor: '#0B4C8C',
            showDenyButton: Boolean(facturaExistente),
            denyButtonText: 'Imprimir Factura Final',
            denyButtonColor: '#FF7518',
            showCancelButton: true,
            cancelButtonText: 'Cerrar',
            focusConfirm: false,
            didOpen: function() {
                if (facturaExistente) {
                    document.getElementById('facturaFiscalControl').value = facturaExistente.control;
                    document.getElementById('facturaFiscalTasa').value = facturaExistente.tasa;
                    document.getElementById('facturaFinalOrdenCompra').value = facturaExistente.orden_compra || '';

                    const btnAnular = document.getElementById('btnAnularFacturaActiva');
                    if (btnAnular) {
                        btnAnular.addEventListener('click', function() {
                            window.confirmarAnularFacturaFiscal(idCotizacion, facturaExistente.numero_raw, facturaExistente.numero);
                        });
                    }
                }
            },
            preConfirm: function() {
                const numeroControl = document.getElementById('facturaFiscalControl').value.trim();
                const tasa = Number(document.getElementById('facturaFiscalTasa').value);
                if (!numeroControl) {
                    Swal.showValidationMessage('Escribe el número de control manual.');
                    return false;
                }
                if (!Number.isFinite(tasa) || tasa <= 0) {
                    Swal.showValidationMessage('Ingresa una tasa de cambio mayor que cero.');
                    return false;
                }

                const ventana = window.open('about:blank', '_blank');
                if (!ventana) {
                    Swal.showValidationMessage('El navegador bloqueó la ventana. Permite las ventanas emergentes e inténtalo de nuevo.');
                    return false;
                }

                return $.post('api/api_cotizaciones.php', {
                    accion: 'guardar_factura_fiscal',
                    id: idCotizacion,
                    control: numeroControl,
                    tasa: tasa
                }, null, 'json').then(function(respuesta) {
                    if (!respuesta.success) {
                        ventana.close();
                        Swal.showValidationMessage(respuesta.msg || 'No se pudo emitir la factura.');
                        return false;
                    }
                    ventana.opener = null;
                    ventana.location.replace('imprimir_factura_fiscal.php?id=' + encodeURIComponent(idCotizacion));
                    return true;
                }, function() {
                    ventana.close();
                    Swal.showValidationMessage('Error de conexión al guardar los datos de la factura.');
                    return false;
                });
            },
            preDeny: function() {
                const ordenCompra = document.getElementById('facturaFinalOrdenCompra').value.trim() || 'S/N';
                const ventana = window.open('about:blank', '_blank');
                if (!ventana) {
                    Swal.showValidationMessage('El navegador bloqueó la ventana. Permite las ventanas emergentes e inténtalo de nuevo.');
                    return false;
                }
                return $.post('api/api_cotizaciones.php', {
                    accion: 'guardar_orden_compra_factura_final',
                    id: idCotizacion,
                    orden_compra: ordenCompra
                }, null, 'json').then(function(respuesta) {
                    if (!respuesta.success) {
                        ventana.close();
                        Swal.showValidationMessage(respuesta.msg || 'No se pudo guardar la orden de compra.');
                        return false;
                    }
                    ventana.opener = null;
                    ventana.location.replace('imprimir_factura_final.php?id=' + encodeURIComponent(idCotizacion));
                    return true;
                }, function() {
                    ventana.close();
                    Swal.showValidationMessage('Error de conexión al guardar la orden de compra.');
                    return false;
                });
            }
        }).then(function(resultado) {
            if (resultado.isConfirmed || resultado.isDenied) {
                window.cargarHistorial();
            }
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'No se pudieron cargar los datos de facturación.', 'error');
    });
};

window.confirmarAnularFacturaFiscal = function(idCotizacion, numeroRaw, numeroFormateado) {
    Swal.fire({
        title: `¿Anular Factura N° ${numeroFormateado}?`,
        html: `
            <div class="small text-muted mb-2">
                La factura <b>N° ${numeroFormateado}</b> quedará guardada e intacta como <b>ANULADA</b> en el historial de esta cotización (tanto Fiscal como Final), y la cotización quedará libre para continuar o emitir una factura nueva con el siguiente correlativo.
            </div>
        `,
        input: 'text',
        inputLabel: 'Motivo de la anulación (opcional)',
        inputPlaceholder: 'Ej: Error en datos fiscales / ajuste de tasa...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular factura',
        cancelButtonText: 'Volver'
    }).then(function(resAnular) {
        if (!resAnular.isConfirmed) {
            window.abrirFacturaFiscal(idCotizacion);
            return;
        }
        $.post('api/api_cotizaciones.php', {
            accion: 'anular_factura_fiscal',
            id: idCotizacion,
            numero_factura: numeroRaw,
            motivo: resAnular.value || 'Anulada por el usuario'
        }, function(resp) {
            if (resp.success) {
                window.cargarHistorial();
                Swal.fire({
                    icon: 'success',
                    title: 'Factura Anulada',
                    text: resp.msg,
                    confirmButtonColor: '#0B4C8C',
                    confirmButtonText: 'Volver al panel de facturación'
                }).then(function() {
                    window.abrirFacturaFiscal(idCotizacion);
                });
            } else {
                Swal.fire('Error', resp.msg || 'No se pudo anular la factura.', 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Fallo de conexión al intentar anular la factura.', 'error');
        });
    });
};

window.cambiarEstadoDirecto = function(id, estado) {
    $.post('api/api_cotizaciones.php', { accion: 'cambiar_estado', id: id, estado: estado }, function(res) {
        if(res.success) window.cargarHistorial(); else Swal.fire('Error', res.msg, 'error');
    }, 'json');
};

window.abrirModalPago = function(id, estado) {
    $('#formPagoCotizacion')[0].reset();
    document.getElementById('pago_fecha').valueAsDate = new Date();
    $('#pago_id_cotizacion').val(id);
    $('#pago_estado_cotizacion').val(estado);
    $('#pago_lbl_estado').text(estado);
    $('#modalPagoCotizacion').modal('show');
};

$(document).on('submit', '#formPagoCotizacion', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
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
                
                if (JS_tieneVector(28)) {
                    Swal.fire({
                        title: 'Pago Registrado',
                        text: '¿Deseas especificar las tallas y telas de este pedido ahora para el taller?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, especificar ahora',
                        cancelButtonText: 'Hacerlo más tarde',
                        confirmButtonColor: '#0B4C8C',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let id_cot = formData.get('id');
                            window.abrirModalEspecificarDetalles(id_cot, 'Cotización');
                        }
                    });
                } else {
                    Swal.fire({icon: 'success', title: 'Pago Registrado', timer: 1500, showConfirmButton: false});
                }
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        complete: function() { $('#btnGuardarPago').prop('disabled', false).text('Guardar Pago'); }
    });
});

window.abrirModalComision = function(id_cot, base_imp) {
    $('#formPagoComision')[0].reset();
    document.getElementById('com_fecha').valueAsDate = new Date();
    $('#com_id_cotizacion').val(id_cot);
    $('#com_lbl_base_imponible').text(window.formatoMoneda(base_imp) + ' $');
    $('#com_monto_calculado').val('');
    
    $('#formPagoComision input').prop('readonly', false);
    $('#com_monto_calculado').prop('readonly', true);
    $('#com_foto').prop('disabled', false);
    $('#btnGuardarComision').show();
    $('#div_com_foto_input').show();
    $('#div_com_foto_ver').hide();

    $('#com_porc').off('input').on('input', function() {
        let porc = parseFloat($(this).val()) || 0;
        let monto = base_imp * (porc / 100);
        $('#com_monto_calculado').val(monto.toFixed(2));
    });

    $('#modalComision').modal('show');
};

window.verComision = function(id_cot) {
    $('#formPagoComision input').prop('readonly', true);
    $('#com_foto').prop('disabled', true);
    $('#btnGuardarComision').hide();
    $('#div_com_foto_input').hide();
    
    $.get('api/api_cotizaciones.php', { accion: 'obtener_comision', id: id_cot }, function(res) {
        if(res.success) {
            let d = res.data;
            $('#com_id_cotizacion').val(id_cot);
            $('#com_lbl_base_imponible').text(window.formatoMoneda(d.base_imponible) + ' $');
            $('#com_porc').val(d.comision_porc);
            $('#com_monto_calculado').val(d.comision_monto);
            
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
                Swal.fire({icon: 'success', title: 'Comisión Registrada', timer: 1500, showConfirmButton: false});
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

window.verDetalleHistorialCotizacion = function(id, subtotal, descuento, iva, total, referencias, fotos, fecha, tasa, usd, banco) {
    $('#det_modal_subtotal').text(window.formatoMoneda(subtotal) + ' $');
    $('#det_modal_descuento').text('-' + window.formatoMoneda(descuento) + ' $');
    $('#det_modal_iva').text(window.formatoMoneda(iva) + ' $');
    $('#det_modal_total').text(window.formatoMoneda(total) + ' $');

    if (referencias !== '' || fecha !== '') {
        $('#box_comprobantes_vista').show();
        let arrRef = referencias ? referencias.split('|').map(s=>s.trim()) : [];
        let arrFecha = fecha ? fecha.split('|').map(s=>s.trim()) : [];
        let arrTasa = tasa ? tasa.split('|').map(s=>s.trim()) : [];
        let arrUsd = usd ? usd.split('|').map(s=>s.trim()) : [];
        let arrBanco = banco ? banco.split('|').map(s=>s.trim()) : [];
        
        let arrFotos = fotos !== '' ? fotos.split(',') : [];
        let htmlFotosGral = arrFotos.map(f => `<a href="../images/pagos/${f.trim()}" target="_blank" class="btn btn-sm btn-outline-primary m-1"><i class="bi bi-image"></i> Ver</a>`).join('');

        let htmlPagos = '';
        let maxLen = Math.max(arrRef.length, arrFecha.length);
        if (maxLen === 0) maxLen = 1;

        for(let i=0; i<maxLen; i++) {
            let f = arrFecha[i] || '-';
            let t = arrTasa[i] ? arrTasa[i] + ' Bs' : '-';
            let u = arrUsd[i] ? '$ ' + arrUsd[i] : '-';
            let b = arrBanco[i] || '-';
            let r = arrRef[i] || '-';
            let imgCell = (i === 0) ? `<td rowspan="${maxLen}" class="align-middle">${htmlFotosGral || 'Sin fotos'}</td>` : '';
            
            htmlPagos += `<tr><td>${f}</td><td>${t}</td><td class="text-success fw-bold">${u}</td><td>${b}</td><td>${r}</td>${imgCell}</tr>`;
        }
        $('#tbody_pagos_multiples').html(htmlPagos);
    } else { $('#box_comprobantes_vista').hide(); }

    $.get('api/api_cotizaciones.php', { accion: 'detalles', id_cotizacion: id }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(d => {
                let detalleText = d.detalle_diseno ? `<span class="badge bg-light text-secondary border border-secondary">${d.detalle_diseno}</span>` : `<span class="text-muted small">-</span>`;
                html += `<tr>
                            <td><span class="fw-bold">${d.tipo_prenda}</span><br><small class="text-muted">${d.genero}</small></td>
                            <td>${detalleText}</td>
                            <td class="text-center fw-bold">${d.cantidad}</td>
                            <td class="text-end">${window.formatoMoneda(d.precio_unitario)} $</td>
                            <td class="text-end fw-bold text-dark">${window.formatoMoneda(d.subtotal_linea)} $</td>
                        </tr>`;
            });
            $('#detallesCotizacionTablaBody').html(html);
            new bootstrap.Modal(document.getElementById('modalVerDetalleCotizacion')).show();
        } else { Swal.fire('Error', 'No se pudieron cargar los detalles', 'error'); }
    }, 'json');
};

window.cargarCotizacionParaEditar = function(id_cot, id_cli, nombre_cli) {
    $.get('api/api_cotizaciones.php', { accion: 'obtener', id: id_cot }, function(res) {
        if(res.success) {
            $('#idClienteSeleccionado').val(id_cli);
            $('#lblClienteSeleccionado').text(nombre_cli);
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
            
            if (res.data.tipo_cotizacion === 'LICITACION') {
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
            if (bloqueado) {
                $('.btn-bloqueable').prop('disabled', true); 
                Swal.fire('Modo Edición Parcial', 'La cotización tiene un pago registrado. Solo puedes editar notas, datos de cliente y envío.', 'info');
            } else {
                $('.btn-bloqueable').prop('disabled', false); 
            }
        }
    }, 'json');
};

window.enviarALogistica = function(id_cot) {
    Swal.fire({
        title: '¿Enviar a Producción?',
        text: "La cotización aparecerá en las alertas de Logística. Verifica que hayas llenado las especificaciones de tallas y telas con la regla azul.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, enviar ahora',
        cancelButtonText: 'Revisar primero'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
            $.post('api/api_cotizaciones.php', { accion: 'enviar_logistica', id_cotizacion: id_cot }, function(res) {
                if(res.success) {
                    Swal.fire('¡Enviado!', 'El departamento de logística ha sido notificado.', 'success');
                    window.cargarHistorial(); 
                } else { Swal.fire('Atención', res.msg, 'warning'); }
            }, 'json').fail(function(jqXHR) {
                console.error("Error servidor:", jqXHR.responseText);
                Swal.fire('Error', 'Hubo un fallo de comunicación con el servidor. Revisa la consola.', 'error');
            });
        }
    });
};

window.obtenerOpcionesTalla = function(genero) {
    // Para el Cotizador del catálogo (Opciones combinadas):
    if(genero === 'Adulto (Dama o Caballero)') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option>';
    if(genero === 'Infantil (Unisex)') return '<option value="4">4</option><option value="6" selected>6</option><option value="8">8</option><option value="10">10</option>';
    if(genero === 'Juvenil (Unisex)') return '<option value="12">12</option><option value="14" selected>14</option><option value="16">16</option>';
    
    // Para las reglas de tallas (Separadas para corte de sastrería):
    if(genero === 'Dama') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option>';
    if(genero === 'Caballero') return '<option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option>';
    if(genero === 'Niña' || genero === 'Niño') return '<option value="2">2</option><option value="4">4</option><option value="6" selected>6</option><option value="8">8</option><option value="10">10</option><option value="12">12</option><option value="14">14</option><option value="16">16</option>';
    
    return '<option value="U" selected>Única / Ajustable</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option>';
};

window.generarInputTelas = function(telaActual) {
    let listInput = `<input type="text" list="lista_telas_inteligente" class="form-control form-control-sm spec-tela border-primary" value="${telaActual}" placeholder="Buscar tela..." required>`;
    return listInput;
};

window.cargarListaOrdenes = function() {
    $('#tablaOrdenesBody').html('<tr><td colspan="5" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_ordenes.php', { accion: 'listar_ordenes' }, function(res) {
        if(res && res.success) {
            let html = '';
            res.data.forEach(o => {
                let badgeExp = (o.es_express == 1 || o.es_express == '1') ? '<span class="badge bg-warning text-dark ms-1">Express</span>' : '';
                html += `<tr>
                    <td class="fw-bold text-primary">${o.nro_orden_fabrica}</td>
                    <td>${o.fecha_emision}</td>
                    <td class="fw-bold">${o.fecha_entrega}</td>
                    <td>${o.cliente} ${badgeExp}</td>
                    <td class="text-center text-nowrap">
                        <a href="imprimir_orden.php?id=${o.ID}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-printer"></i> Imprimir</a>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="window.eliminarOrden(${o.ID})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            $('#tablaOrdenesBody').html(html || '<tr><td colspan="5" class="text-center">No hay órdenes registradas.</td></tr>');
        } else {
            $('#tablaOrdenesBody').html('<tr><td colspan="5" class="text-center text-danger">' + (res ? res.msg : 'Error al cargar') + '</td></tr>');
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        $('#tablaOrdenesBody').html('<tr><td colspan="5" class="text-center text-danger">Error de red al cargar órdenes.</td></tr>');
        console.error("Error cargando órdenes:", textStatus, errorThrown, jqXHR.responseText);
    });
};

window.abrirModalNuevaOrden = function() {
    $('#formGuardarOrden')[0].reset();
    archivosOrdenMemoria = [];
    $('#lista_fotos_seleccionadas').empty(); 
    $('#fotos_previas_container').empty();
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

window.actualizarTallaMedida = function(selectElem, init = false) {
    let genero = $(selectElem).val();
    let $tallaSelect = $(selectElem).closest('tr').find('.med-talla');
    let tallaActual = $tallaSelect.data('talla-actual'); 
    $tallaSelect.html(window.obtenerOpcionesTalla(genero));
    if (init && tallaActual) { $tallaSelect.val(tallaActual); }
};

window.cargarDatosCotizacionOrden = function(id_cot) {
    if(!id_cot) { $('#tbody_prendas_orden').empty(); $('#tbody_artes_orden').empty(); return; }
    $('#tbody_prendas_orden').html('<tr><td colspan="3" class="text-center">Cargando...</td></tr>');
    
    $.get('api/api_ordenes.php', { accion: 'obtener_datos_cotizacion', id_cotizacion: id_cot }, function(res) {
        if(res.success) {
            $('#id_cliente_orden').val(res.id_cliente);
            $('#orden_notas_cotizacion').val(res.notas_cotizacion || 'No hay notas adicionales en la cotización base.');
            
            archivosOrdenMemoria = [];
            $('#lista_fotos_seleccionadas').empty();
            if(res.fotos_diseno && res.fotos_diseno !== '') {
                let fArr = res.fotos_diseno.split(',');
                let htmlFotos = '<div class="mt-2 p-2 border border-info rounded bg-light shadow-sm"><strong class="small text-info"><i class="bi bi-images"></i> Diseños de la Regla Azul (Se enviarán a la orden):</strong><div class="d-flex flex-wrap gap-2 mt-2">';
                fArr.forEach(f => {
                    if(f.trim() !== '') {
                        htmlFotos += `<div class="position-relative"><a href="../images/ordenes/${f}" target="_blank"><img src="../images/ordenes/${f}" style="height: 60px; border-radius: 4px; border: 1px solid #ccc;"></a><input type="hidden" name="fotos_previas[]" value="${f}"></div>`;
                    }
                });
                htmlFotos += '</div></div>';
                $('#fotos_previas_container').html(htmlFotos);
            } else {
                $('#fotos_previas_container').empty();
            }

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
                
                let telaPreseleccionada = '';
                try {
                    let specs = JSON.parse(p.especificaciones_tallas);
                    if (specs && specs.length > 0) { telaPreseleccionada = specs[0].tela; }
                } catch(e) {}

                let opcionesTela = '<option value="">Seleccionar Tela...</option>';
                telasMemoria.forEach(t => { 
                    let nombreTelaStr = t.nombre + ' (' + t.color + ')';
                    let selected = (telaPreseleccionada === nombreTelaStr) ? 'selected' : '';
                    opcionesTela += `<option value="${t.ID}" ${selected}>${nombreTelaStr}</option>`; 
                });

                htmlPrendas += `<tr class="fila-prenda-orden">
                    <td><input type="text" class="form-control form-control-sm fw-bold orden-tipo bg-light" value="${nombreCompleto}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm text-center orden-cant bg-light" value="${p.cantidad}" readonly></td>
                    <td><select class="form-select form-select-sm orden-tela border-primary">${opcionesTela}</select></td>
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
                    } else if (a.detalle_diseno.includes('Ubicación:')) {
                        ubicacionGuardada = a.detalle_diseno.replace('Ubicación: ', '').trim();
                    }

                    let opcionesVinculoArte = '<option value="">Sin vincular</option>';
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
            } else { $('#seccion_artes_posiciones').hide(); }

            setTimeout(() => {
                if (prendasOrdenMemoria.length > 0) {
                    $('#check_asignar_tallas').prop('checked', true).trigger('change');
                    window.autogenerarFilasTallas(true); 
                }
            }, 300);
        }
    }, 'json');
};

window.autogenerarFilasTallas = function(silencioso = false) {
    if(prendasOrdenMemoria.length === 0) {
        if(!silencioso) Swal.fire('Error', 'Seleccione una cotización base primero', 'warning');
        return;
    }

    $('#seccion_asignacion_tallas thead tr').html('<th>Prenda</th><th style="width:70px;">Cant.</th><th>Nombre (Opcional)</th><th>Género</th><th>Talla</th><th>Tela Asignada</th><th>Observación</th><th></th>');
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
                let gen = s.genero || 'Dama';
                let opcionesTallas = window.obtenerOpcionesTalla(gen);
                opcionesTallas = opcionesTallas.replace(`value="${s.talla}"`, `value="${s.talla}" selected`);
                
                let cantItem = parseInt(s.cantidad) || 1; 
                let telaItem = s.tela || '';
                
                let html = `<tr class="fila-medida-personal">
                    <td><select class="form-select form-select-sm med-prenda bg-light" style="width: 150px;">${opcionesPrenda}</select></td>
                    <td><input type="number" class="form-control form-control-sm text-center med-cant border-primary fw-bold" value="${cantItem}" min="1" required></td>
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
                    <td><input type="text" class="form-control form-control-sm med-tela bg-light" value="${telaItem}" placeholder="Tela..."></td>
                    <td><input type="text" class="form-control form-control-sm med-obs" value="${s.obs || ''}" placeholder="Ej: Con bolsillo"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
                </tr>`;
                $('#tbody_medidas_personales').append(html);
                $('#tbody_medidas_personales tr:last').find('.med-prenda').val(nombreCompleto);
            });
        } else {
            let cant = parseInt(p.cantidad);
            let gen = 'Dama';
            if(p.genero === 'Caballero') gen = 'Caballero';
            let opcionesTallas = window.obtenerOpcionesTalla(gen);
            
            let html = `<tr class="fila-medida-personal">
                <td><select class="form-select form-select-sm med-prenda bg-light" style="width: 150px;">${opcionesPrenda}</select></td>
                <td><input type="number" class="form-control form-control-sm text-center med-cant border-primary fw-bold" value="${cant}" min="1" required></td>
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
                <td><input type="text" class="form-control form-control-sm med-tela bg-light" placeholder="Tela..."></td>
                <td><input type="text" class="form-control form-control-sm med-obs" placeholder="Ej: Con bolsillo"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
            </tr>`;
            $('#tbody_medidas_personales').append(html);
            $('#tbody_medidas_personales tr:last').find('.med-prenda').val(nombreCompleto);
        }
    });
    
    if(!silencioso) { Swal.fire('¡Filas Generadas!', 'Se han cargado los renglones agrupados según la regla azul.', 'success'); }
};

window.agregarFilaMedida = function() {
    if(prendasOrdenMemoria.length === 0) return Swal.fire('Error', 'Seleccione una cotización', 'warning');
    
    $('#seccion_asignacion_tallas thead tr').html('<th>Prenda</th><th style="width:70px;">Cant.</th><th>Nombre (Opcional)</th><th>Género</th><th>Talla</th><th>Tela Asignada</th><th>Observación</th><th></th>');

    let opcionesPrenda = '';
    prendasOrdenMemoria.forEach(p => {
        let nombreCompleto = p.tipo_prenda + (p.genero !== 'No especificado' ? ` (${p.genero})` : '');
        opcionesPrenda += `<option value="${nombreCompleto}">${nombreCompleto}</option>`;
    });

    let html = `<tr class="fila-medida-personal">
        <td><select class="form-select form-select-sm med-prenda bg-light" style="width: 150px;">${opcionesPrenda}</select></td>
        <td><input type="number" class="form-control form-control-sm text-center med-cant border-primary fw-bold" value="1" min="1" required></td>
        <td><input type="text" class="form-control form-control-sm med-nombre border-primary fw-bold" placeholder="Nombre"></td>
        <td><select class="form-select form-select-sm med-genero" onchange="window.actualizarTallaMedida(this)"><option value="Dama">Dama</option><option value="Caballero">Caballero</option><option value="Niña">Niña</option><option value="Niño">Niño</option><option value="U">U</option></select></td>
        <td><select class="form-select form-select-sm med-talla border-primary fw-bold" required>${window.obtenerOpcionesTalla('Dama')}</select></td>
        <td><input type="text" class="form-control form-control-sm med-tela bg-light" placeholder="Tela..."></td>
        <td><input type="text" class="form-control form-control-sm med-obs" placeholder="Ej: Con bolsillo"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="$(this).closest('tr').remove()"><i class="bi bi-x"></i></button></td>
    </tr>`;
    $('#tbody_medidas_personales').append(html);
};

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
};

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
};

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
};

// ==============================================================================
// CARGA DEL MÓDULO DE PORTAFOLIOS Y LICITACIONES (PDF)
// ==============================================================================
window.cargarLicitaciones = function() {
    // Usamos la URL absoluta desde la raíz de tu dominio
    $('#vistaLicitaciones').html(`
        <div class="w-100 h-100 d-flex flex-column">
            <iframe src="/Licitaciones/index.php" style="width:100%; height:88vh; border:none; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"></iframe>
        </div>
    `);
};

window.enviarDirectoEnvios = function(id_cot) {
    Swal.fire({
        title: '¿Enviar Directo a Envíos?',
        text: "Esta cotización pasará directamente a la bandeja de envíos sin generar una orden de fábrica. ¿Estás seguro?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, enviar a Envíos',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
            $.post('api/api_cotizaciones.php', { accion: 'enviar_directo_envios', id_cotizacion: id_cot }, function(res) {
                if(res.success) {
                    Swal.fire('¡Enviado!', 'El pedido pasó directamente a la logística de envíos.', 'success');
                    window.cargarHistorial(); 
                } else { Swal.fire('Atención', res.msg, 'warning'); }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Hubo un fallo de comunicación con el servidor.', 'error');
            });
        }
    });
};

// ==============================================================================
// CÁLCULO DINÁMICO DE PAGO EN MODAL (TASA / BS / USD)
// ==============================================================================
$(document).on('input', '#pago_tasa_bcv, #pago_valor_bs', function() {
    let tasa = parseFloat($('#pago_tasa_bcv').val()) || 0;
    let bs = parseFloat($('#pago_valor_bs').val()) || 0;
    
    if (tasa > 0 && bs > 0) {
        let usd = bs / tasa;
        $('#pago_valor_usd').val(usd.toFixed(2));
    } else if (bs === 0) {
        $('#pago_valor_usd').val('');
    }
});

$(document).on('input', '#pago_valor_usd', function() {
    let tasa = parseFloat($('#pago_tasa_bcv').val()) || 0;
    let usd = parseFloat($('#pago_valor_usd').val()) || 0;
    
    if (tasa > 0 && usd > 0) {
        let bs = usd * tasa;
        $('#pago_valor_bs').val(bs.toFixed(2));
    } else if (usd === 0) {
        $('#pago_valor_bs').val('');
    }
});