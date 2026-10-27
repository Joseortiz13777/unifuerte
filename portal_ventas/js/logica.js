let carritoMemoria = [];

$(document).ready(function() {
    
    // Limpieza de estilos al cerrar modales
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length === 0) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
        }
    });

    // Toggle de la barra lateral (Menú hamburguesa)
    $('#btnHamburguesa, #sidebarOverlay').click(function() {
        $('#sidebar').toggleClass('active');
        $('#sidebarOverlay').toggleClass('active');
    });

    // ================= ENRUTAMIENTO SPA (MENÚ) =================
    $('.nav-link-custom').click(function(e) {
        e.preventDefault();
        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');
        $('#sidebar').removeClass('active'); 
        $('#sidebarOverlay').removeClass('active');
        $('.vista-spa').hide();
        
        if (this.id === 'btnMenuClientes') {
            $('#vistaClientes').fadeIn();
        } 
        else if (this.id === 'btnMenuCotizador') {
            if (!$('#idClienteSeleccionado').val()) {
                Swal.fire('Atención', 'Seleccione un cliente primero haciendo clic en "Seleccionar" en el directorio.', 'warning');
                $('#btnMenuClientes').click();
                return;
            }
            $('#vistaVentas').fadeIn();
            cargarCatalogoSPA();
        }
        else if (this.id === 'btnMenuGestion') {
            $('#vistaGestion').fadeIn();
        }
        else if (this.id === 'btnMenuCotizaciones') {
            $('#vistaCotizaciones').fadeIn();
            cargarHistorial(); 
        }
        else if (this.id === 'btnMenuDashboard') {
            $('#vistaDashboard').fadeIn();
            if(typeof inicializarDashboard === 'function') inicializarDashboard();
        }
        else if (this.id === 'btnMenuOrdenes') {
            $('#vistaOrdenes').fadeIn();
            if(typeof cargarListaOrdenes === 'function') cargarListaOrdenes();
        }
        else if (this.id === 'btnMenuUsuarios') {
            $('#vistaUsuarios').fadeIn();
            if(typeof cargarUsuarios === 'function') cargarUsuarios();
        }
        // ---> LA INSTRUCCIÓN QUE FALTABA PARA MOSTRAR AUDITORÍA <---
        else if (this.id === 'btnMenuAuditoria') {
            $('#vistaAuditoria').fadeIn();
            if(typeof cargarAuditoria === 'function') cargarAuditoria();
        }
    });

    // ================= ESTILOS DE CHECKBOX Y RADIOS =================
    $(document).on('change', 'input[name="id_forma_pago"]', function() {
        $('.custom-check-small').removeClass('active-check');
        $(this).closest('.custom-check-small').addClass('active-check');
        actualizarResumenCarrito(); 
    });
    
    $(document).on('change', '#cot_contribuyente', function() {
        if($(this).is(':checked')) $(this).closest('.custom-check-small').addClass('active-check');
        else $(this).closest('.custom-check-small').removeClass('active-check');
    });

    $(document).on('click', '.pill-cat', function(e) {
        e.preventDefault();
        let target = $(this).attr('href');
        $('html, body').animate({ scrollTop: $(target).offset().top - 80 }, 500);
    });

    const removerAcentos = (str) => { return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(); };

    // ================= DIRECTORIO Y CLIENTES =================
    $('#buscadorDirectorio').on('input', function() {
        let valorFiltro = removerAcentos($(this).val().trim());
        $('#tablaClientesBody .fila-datos').each(function() {
            let nombre = removerAcentos($(this).find('.col-nombre').text());
            let cedula = removerAcentos($(this).find('.col-cedula').text());
            $(this).toggle(nombre.indexOf(valorFiltro) > -1 || cedula.indexOf(valorFiltro) > -1);
        });
    });

    $(document).on('click', '.btn-seleccionar-cli', function() {
        $('#idClienteSeleccionado').val($(this).data('id'));
        $('#lblClienteSeleccionado').text($(this).data('nombre'));
        cancelarEdicionCliente(); 
        $('#id_edit_cotizacion').val('0');
        $('#badgeEstadoEdicion').hide();
        carritoMemoria = [];
        actualizarResumenCarrito();
        $('#btnMenuCotizador').click(); 
        Swal.fire({ icon: 'success', title: 'Cliente Vinculado', timer: 1500, showConfirmButton: false });
    });

    $(document).on('click', '.btn-editar-cli', function() {
        $('#tituloFormCliente').text('Editar Cliente');
        $('#cli_id').val($(this).data('id'));
        $('#f_nombre').val($(this).data('nombre'));
        $('#f_cedula').val($(this).data('cedula'));
        $('#f_tlf').val($(this).data('tlf'));
        $('#f_comprador').val($(this).data('comprador'));
        $('#f_email').val($(this).data('email'));
        $('#f_direccion').val($(this).data('direccion'));
        $('#btnRegistrarCliente').text('Actualizar').css('background-color', '#FF7518');
        $('#btnCancelarEdicion').removeClass('d-none');
        window.scrollTo(0,0);
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
                if(res.modo === 'editar') {
                    let btnEditar = $(`.btn-editar-cli[data-id="${res.id}"]`);
                    let fila = btnEditar.closest('tr');
                    fila.find('.col-nombre').text(res.nombre);
                    fila.find('.col-cedula').text(res.cedula);
                    fila.find('.col-tlf').text(res.tlf);
                    btnEditar.data('nombre', res.nombre).data('cedula', res.cedula).data('tlf', res.tlf)
                             .data('comprador', res.comprador).data('email', res.email).data('direccion', res.direccion);
                    fila.find('.btn-seleccionar-cli').data('nombre', res.nombre);
                    Swal.fire('Actualizado', 'Cliente editado', 'success');
                } else {
                    $('.fila-vacia').remove(); 
                    let nuevaFila = `<tr class="fila-datos" style="background-color: #d1e7dd;">
                        <td class="fw-bold col-nombre" style="color: #0B4C8C;">${res.nombre}</td>
                        <td class="col-cedula">${res.cedula}</td>
                        <td class="col-tlf">${res.tlf}</td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill me-1 btn-editar-cli" 
                                data-id="${res.id}" data-nombre="${res.nombre}" data-cedula="${res.cedula}" data-tlf="${res.tlf}" 
                                data-comprador="${res.comprador}" data-email="${res.email}" data-direccion="${res.direccion}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill btn-seleccionar-cli" data-id="${res.id}" data-nombre="${res.nombre}"><i class="bi bi-check-circle"></i> Seleccionar</button>
                        </td></tr>`;
                    $('#tablaClientesBody').prepend(nuevaFila);
                    setTimeout(() => { $('#tablaClientesBody tr:first').css('background-color', ''); }, 2000);
                    Swal.fire('Registrado', 'Cliente creado', 'success');
                }
                cancelarEdicionCliente();
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        }, 'json').always(() => $('#btnRegistrarCliente').prop('disabled', false).text('Guardar Cliente').css('background-color', '#0B4C8C'));
    });

    // ================= BUSCADOR CATÁLOGO Y ARTE =================
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
            } else {
                $(this).hide();
            }
        });
    });

    $(document).on('change', '#modal_vincular_prenda', function() {
        let cantStr = $(this).find(':selected').data('cant');
        if (cantStr) { $('#modal_cantidad').val(cantStr); }
    });

    // ================= GUARDAR COTIZACIÓN =================
    $('#formGuardarCotizacion').submit(function(e) {
        e.preventDefault();
        let idCliente = $('#idClienteSeleccionado').val();
        if(!idCliente) return Swal.fire('Error', 'Seleccione un cliente.', 'error');
        if(carritoMemoria.length === 0) return Swal.fire('Error', 'El carrito está vacío.', 'error');

        let id_forma_pago = $('input[name="id_forma_pago"]:checked').val() || '';
        if (id_forma_pago === '') return Swal.fire('Atención', 'Debe seleccionar un Método de Pago Acordado antes de generar la cotización.', 'warning');

        $('#btnGenerarCotizacion').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        let datos = {
            id_edit_cotizacion: $('#id_edit_cotizacion').val(),
            id_cliente: idCliente,
            contribuyente_especial: $('#cot_contribuyente').is(':checked') ? 1 : 0,
            id_forma_pago: id_forma_pago,
            notas: $('#cot_notas').val(),
            carrito: JSON.stringify(carritoMemoria)
        };

        $.post('api/guardar_cotizacion.php', datos, function(res) {
            if(res.success) {
                Swal.fire({ icon: 'success', title: '¡Cotización Guardada!', text: 'Nro: ' + res.nro_cotizacion, showCancelButton: true, confirmButtonText: 'Imprimir PDF', cancelButtonText: 'Cerrar' }).then((result) => {
                    if (result.isConfirmed) window.open('imprimir_cotizacion.php?id=' + res.id_cotizacion, '_blank');
                    carritoMemoria = [];
                    actualizarResumenCarrito();
                    $('#formGuardarCotizacion')[0].reset();
                    $('.custom-check-small').removeClass('active-check'); 
                    $('#id_edit_cotizacion').val('0');
                    $('#badgeEstadoEdicion').hide();
                    $('#btnMenuCotizaciones').click(); 
                });
            } else {
                Swal.fire('Error BD', res.msg, 'error');
            }
        }, 'json').fail(function(){
            Swal.fire('Error', 'Fallo servidor', 'error');
        }).always(() => $('#btnGenerarCotizacion').prop('disabled', false).html('Generar Cotización Oficial <i class="bi bi-file-pdf ms-2"></i>'));
    });

    // ================= CRUD DE CATÁLOGO =================
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
        $.ajax({
            url: 'api/api_gestion_catalogo.php', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false});
                    abrirModalGestion(formData.get('entidad')); 
                } else { Swal.fire('Error', res.msg, 'error'); }
            }
        });
    });

    $(document).on('click', '.btn-editar-crud', function() {
        let id = $(this).data('id');
        let nombre = $(this).data('nombre');
        $('#formCrudCatalogo input[name="id_edit"]').val(id);
        $('#formCrudCatalogo input[name="nombre"]').val(nombre);
        if($(this).data('precio')) $('#formCrudCatalogo input[name="precio"]').val($(this).data('precio'));
        if($(this).data('fk')) $('#formCrudCatalogo select').val($(this).data('fk'));
        $('#formCrudCatalogo button[type="submit"]').text('Actualizar').removeClass('btn-primary').addClass('btn-warning');
        $('#btnCancelarCrud').removeClass('d-none');
    });

    $(document).on('click', '#btnCancelarCrud', function() {
        $('#formCrudCatalogo')[0].reset();
        $('#formCrudCatalogo input[name="id_edit"]').val('0');
        $('#formCrudCatalogo button[type="submit"]').text('Guardar').removeClass('btn-warning').addClass('btn-primary');
        $(this).addClass('d-none');
    });

    $(document).on('click', '.btn-eliminar-crud', function() {
        let entidad = $(this).data('entidad');
        let id = $(this).data('id');
        Swal.fire({ title: '¿Eliminar definitivamente?', icon: 'warning', showCancelButton: true }).then((result) => {
            if(result.isConfirmed) {
                $.post('api/api_gestion_catalogo.php', { accion: 'eliminar', entidad: entidad, id: id }, function(res) {
                    if(res.success) abrirModalGestion(entidad); else Swal.fire('Error', res.msg, 'error');
                }, 'json');
            }
        });
    });

    $(document).on('click', '.btn-ordenar-crud', function() {
        let entidad = $(this).data('entidad');
        let id = $(this).data('id');
        let dir = $(this).data('dir');
        $.post('api/api_gestion_catalogo.php', { accion: 'ordenar', entidad: entidad, id: id, direccion: dir }, function(res) {
            if(res.success) abrirModalGestion(entidad);
        }, 'json');
    });
});

// ================= FUNCIONES GLOBALES =================

function formatoMoneda(valor) { return parseFloat(valor).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

function cancelarEdicionCliente() {
    let form = $('#formClienteAjax');
    form[0].reset();
    form.removeClass('was-validated'); 
    $('#cli_id').val('0');
    $('#tituloFormCliente').text('Nuevo Cliente');
    $('#btnRegistrarCliente').text('Guardar Cliente').css('background-color', '#0B4C8C');
    $('#btnCancelarEdicion').addClass('d-none');
}

function cargarCatalogoSPA() {
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
                    let catId = 'cat_' + cat.replace(/\s+/g, '_').toLowerCase();
                    pillsHtml += `<a href="#${catId}" class="pill-cat">${cat}</a>`;
                    
                    html += `<div id="${catId}" class="col-12 categoria-header mt-4"><h3 class="fw-bold" style="color: #0B4C8C; border-bottom: 2px solid #FF7518; padding-bottom: 5px;">${cat}</h3></div>`;
                    currentSub = ''; 
                }

                if (sub !== currentSub) {
                    currentSub = sub;
                    html += `<div class="col-12 subcategoria-header mt-3"><h5 class="fw-bold text-secondary"><i class="bi bi-tag-fill me-2" style="color:#FF7518;"></i>${sub}</h5></div>`;
                }

                let imgSrc = p.foto ? `images/uploads/${p.foto}` : '../img/unifuerte.jpg';
                html += `
                <div class="col-6 col-md-4 col-xl-3 tarjeta-producto" data-nombre="${p.nombre}">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; cursor: pointer; transition: 0.3s;" onclick="abrirModalPrenda(${p.id}, '${p.nombre.replace(/'/g, "\\'").replace(/"/g, "&quot;")}', ${p.precio}, '${cat.replace(/'/g, "\\'").replace(/"/g, "&quot;")}')">
                        <div style="height: 140px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f9f9f9; border-radius: 12px 12px 0 0;">
                            <img src="${imgSrc}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="card-body p-2 text-center">
                            <h6 class="fw-bold mb-1" style="font-size: 0.85rem; color: #333;">${p.nombre}</h6>
                            <h5 class="fw-bold m-0" style="color: #0B4C8C;">${formatoMoneda(p.precio)} $</h5>
                        </div>
                    </div>
                </div>`;
            });
            html += `</div>`; 
            
            $('#contenedorCatalogo').html(html);
            $('#navPillsCategorias').html(pillsHtml);
        }
    }, 'json');
}

function abrirModalPrenda(id, nombre, precio, categoria) {
    $('#modal_edit_index').val(-1);
    $('#modal_id_prod').val(id);
    $('#modal_nombre_prod').text(nombre);
    $('#modal_precio_prod').val(precio);
    $('#modal_precio_display').text(formatoMoneda(precio) + ' $ Unit.');
    $('#modal_cantidad').val(1);
    $('#modal_categoria_prod').val(categoria);

    if (categoria === 'Arte Final') {
        $('#wrapper_modal_genero').hide();
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
        $('#modal_genero').val('No especificado');
        $('#wrapper_modal_detalle').hide();
        $('#modal_detalle_diseno').val(''); 
        $('#wrapper_modal_vincular').hide();
        $('#modal_vincular_prenda').val('');
    }

    new bootstrap.Modal(document.getElementById('modalAgregarPrenda')).show();
}

function abrirModalEditarPrenda(index) {
    let item = carritoMemoria[index];
    
    $('#modal_edit_index').val(index);
    $('#modal_id_prod').val(item.id_producto);
    $('#modal_nombre_prod').text(item.nombre);
    $('#modal_precio_prod').val(item.precio);
    $('#modal_precio_display').text(formatoMoneda(item.precio) + ' $ Unit.');
    $('#modal_cantidad').val(item.cantidad);
    $('#modal_categoria_prod').val(item.categoria);

    if (item.categoria === 'Arte Final') {
        $('#wrapper_modal_genero').hide();
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
        $('#modal_genero').val(item.genero);
        $('#wrapper_modal_detalle').hide();
        $('#modal_detalle_diseno').val(''); 
        $('#wrapper_modal_vincular').hide();
        $('#modal_vincular_prenda').val('');
    }

    new bootstrap.Modal(document.getElementById('modalAgregarPrenda')).show();
}

function ajustarCant(val) { 
    let cant = parseInt($('#modal_cantidad').val()) || 1; 
    let nueva = cant + val; 
    let max_cant = 9999;
    
    if ($('#modal_categoria_prod').val() === 'Arte Final' && $('#modal_vincular_prenda').val() !== '') {
        max_cant = parseInt($('#modal_vincular_prenda option:selected').data('cant')) || 9999;
    }
    
    if(nueva >= 1 && nueva <= max_cant) {
        $('#modal_cantidad').val(nueva); 
    } else if (nueva > max_cant) {
        Swal.fire('Atención', 'La cantidad máxima de artes permitidos para esta prenda es ' + max_cant, 'warning');
    }
}

function confirmarAgregarAlCarrito() {
    let editIndex = parseInt($('#modal_edit_index').val());
    let id = $('#modal_id_prod').val(), 
        nombre = $('#modal_nombre_prod').text(), 
        precio = parseFloat($('#modal_precio_prod').val()), 
        genero = $('#modal_genero').val(), 
        cantidad = parseInt($('#modal_cantidad').val()),
        posicion_pura = $('#modal_detalle_diseno').val(),
        categoria = $('#modal_categoria_prod').val(),
        vinculo_puro = $('#modal_vincular_prenda').val();

    let detalleFinal = posicion_pura;
    let max_cant = parseInt($('#modal_vincular_prenda option:selected').data('cant')) || 9999;
    
    if (categoria === 'Arte Final') {
        if (vinculo_puro) {
            detalleFinal = `[Para: ${vinculo_puro}] - Ubicación: ${posicion_pura}`;
            if (cantidad > max_cant) {
                Swal.fire('Atención', 'La cantidad del arte (' + cantidad + ') no puede exceder las prendas base disponibles (' + max_cant + ').', 'warning');
                return;
            }
        } else {
            detalleFinal = posicion_pura ? `Ubicación: ${posicion_pura}` : '';
        }
    }

    let idUnico = id + '_' + genero + '_' + (detalleFinal.replace(/\s+/g, ''));
    
    let nuevoItem = { 
        idUnico: idUnico, 
        id_producto: id, 
        nombre: nombre, 
        genero: genero, 
        precio: precio, 
        cantidad: cantidad, 
        subtotal: precio * cantidad,
        detalle_diseno: detalleFinal,
        posicion_pura: posicion_pura,
        vinculo_puro: vinculo_puro,
        categoria: categoria
    };

    if (editIndex > -1) {
        carritoMemoria[editIndex] = nuevoItem;
    } else {
        let index = carritoMemoria.findIndex(i => i.idUnico === idUnico);
        if(index > -1) {
            carritoMemoria[index].cantidad += cantidad;
            carritoMemoria[index].subtotal = carritoMemoria[index].cantidad * carritoMemoria[index].precio;
        } else {
            carritoMemoria.push(nuevoItem);
        }
    }
    
    actualizarResumenCarrito();
    $('#modalAgregarPrenda').modal('hide');
}

function ajustarCantCarrito(index, val) {
    let item = carritoMemoria[index];
    let nuevaCant = item.cantidad + val;
    
    if (val > 0 && item.categoria === 'Arte Final' && item.vinculo_puro) {
        let prendaVinculada = carritoMemoria.find(p => p.categoria !== 'Arte Final' && `${p.nombre} (${p.genero})` === item.vinculo_puro);
        let maxCant = prendaVinculada ? prendaVinculada.cantidad : 9999;
        if (nuevaCant > maxCant) {
            Swal.fire('Atención', 'No puedes exceder la cantidad de la prenda base vinculada (' + maxCant + ').', 'warning');
            return;
        }
    }
    
    if(nuevaCant >= 1) {
        item.cantidad = nuevaCant;
        item.subtotal = item.cantidad * item.precio;
        actualizarResumenCarrito();
    }
}

function eliminarDelCarrito(index) { carritoMemoria.splice(index, 1); actualizarResumenCarrito(); }

function cambiarGeneroCarrito(index, nuevoGenero) {
    let item = carritoMemoria[index];
    item.genero = nuevoGenero;
    item.idUnico = item.id_producto + '_' + nuevoGenero + '_' + (item.detalle_diseno.replace(/\s+/g, ''));
    actualizarResumenCarrito();
}

function actualizarResumenCarrito() {
    let html = '', subtotalBruto = 0, totalPrendas = 0;
    let montoDescuentoTotal = 0;
    let porcentajesUsados = new Set();

    if(carritoMemoria.length === 0) {
        html = '<div class="text-center text-muted small py-4">No hay prendas agregadas.</div>';
    } else {
        carritoMemoria.forEach((item, index) => {
            let descLinea = 0;
            let porcLinea = 0;
            
            if(item.cantidad >= 13 && item.cantidad <= 50) porcLinea = 10;
            else if(item.cantidad > 50) porcLinea = 15;

            if(porcLinea > 0) {
                descLinea = item.subtotal * (porcLinea / 100);
                porcentajesUsados.add(porcLinea);
            }

            subtotalBruto += item.subtotal; 
            totalPrendas += item.cantidad;
            montoDescuentoTotal += descLinea;

            let badgeDescuentoHtml = porcLinea > 0 ? `<div class="text-danger mt-1" style="font-size: 0.7rem; font-weight: bold;">¡Aplica Descuento ${porcLinea}%!</div>` : '';
            let tachadoHtml = porcLinea > 0 ? `<div class="text-muted text-decoration-line-through mb-1" style="font-size: 0.75rem;">${formatoMoneda(item.subtotal)}$</div>` : '';
            let detalleHtml = item.detalle_diseno ? `<div class="text-secondary mt-1" style="font-size: 0.75rem;"><i class="bi bi-geo-alt-fill text-danger"></i> <b>${item.detalle_diseno}</b></div>` : '';

            let selectGeneroHtml = '';
            if (item.categoria !== 'Arte Final') {
                selectGeneroHtml = `
                    <select class="form-select form-select-sm d-inline-block fw-bold mt-1 mb-1 shadow-sm" 
                            style="width: auto; font-size: 0.75rem; color: #FF7518; border: 1px solid #FF7518; background-color: #fffaf0; padding: 2px 20px 2px 8px; border-radius: 6px; cursor: pointer;" 
                            onchange="cambiarGeneroCarrito(${index}, this.value)">
                        <option value="Dama" ${item.genero === 'Dama' ? 'selected' : ''}>Dama</option>
                        <option value="Caballero" ${item.genero === 'Caballero' ? 'selected' : ''}>Caballero</option>
                        <option value="Niña" ${item.genero === 'Niña' ? 'selected' : ''}>Niña</option>
                        <option value="Niño" ${item.genero === 'Niño' ? 'selected' : ''}>Niño</option>
                        <option value="No especificado" ${item.genero === 'No especificado' ? 'selected' : ''}>No especificado</option>
                    </select>
                `;
            } else {
                item.genero = 'No especificado';
            }

            html += `<div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom bg-white p-2 rounded shadow-sm">
                <div style="flex:1;">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${item.nombre}</div>
                    ${selectGeneroHtml}
                    ${detalleHtml}
                    <div class="d-flex align-items-center mt-1">
                        <button type="button" class="btn-qty" onclick="ajustarCantCarrito(${index}, -1)">-</button>
                        <span class="mx-2 fw-bold">${item.cantidad}</span>
                        <button type="button" class="btn-qty" onclick="ajustarCantCarrito(${index}, 1)">+</button>
                        <span class="ms-3 fw-bold text-primary">${formatoMoneda(item.precio)}$ <small class="text-muted fw-normal">c/u</small></span>
                    </div>
                    ${badgeDescuentoHtml}
                </div>
                <div class="text-end" style="min-width: 80px;">
                    ${tachadoHtml}
                    <div class="fw-bold mb-2 fs-6 text-dark">${formatoMoneda(item.subtotal - descLinea)}$</div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="abrirModalEditarPrenda(${index})" title="Editar Detalles"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${index})"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>`;
        });
    }
    
    $('#listaCarritoBubble').html(html);
    $('#resumenCheckoutBox').html(html);

    let subtotalNeto = subtotalBruto - montoDescuentoTotal;
    let iva = subtotalNeto * 0.16;
    
    let igtf = 0;
    if($('input[name="id_forma_pago"]:checked').closest('label').text().toLowerCase().indexOf('divisa') !== -1) {
        igtf = subtotalNeto * 0.03;
    }
    
    let total = subtotalNeto + iva + igtf;

    $('#lblBurbujaItems').text(totalPrendas);
    $('#lblSubtotal, #lblSubtotalCheck').text(formatoMoneda(subtotalBruto) + ' $');
    
    if(montoDescuentoTotal > 0) { 
        let lblPorc = Array.from(porcentajesUsados).map(p => p + '%').join(' y ');
        $('#lblPorcentajeDesc, #lblPorcentajeDescCheck').text(lblPorc); 
        $('#lblMontoDesc, #lblMontoDescCheck').text('-' + formatoMoneda(montoDescuentoTotal) + ' $'); 
        $('#cajaDescuento, #cajaDescuentoCheck').attr('style', 'display: flex !important;');
    } else { 
        $('#cajaDescuento, #cajaDescuentoCheck').attr('style', 'display: none !important;'); 
    }
    
    $('#lblIva, #lblIvaCheck').text(formatoMoneda(iva) + ' $');
    
    if(igtf > 0) { 
        $('#lblIgtf, #lblIgtfCheck').text(formatoMoneda(igtf) + ' $'); 
        $('#cajaIgtf, #cajaIgtfCheck').attr('style', 'display: flex !important;');
    } else { 
        $('#cajaIgtf, #cajaIgtfCheck').attr('style', 'display: none !important;'); 
    }

    $('#lblTotalGeneral, #lblTotalGeneralCheck').text(formatoMoneda(total) + ' $');
}

function irAPagar() {
    $('#modalCarritoBubble').modal('hide');
    $('html, body').animate({ scrollTop: $("#seccionCheckout").offset().top - 40 }, 600);
    $('#seccionCheckout').css('transform', 'scale(1.02)');
    setTimeout(() => { $('#seccionCheckout').css('transform', 'scale(1)'); }, 1000);
}

function abrirModalGestion(tipo) {
    $('#tituloModalGestion').text('Gestión de ' + tipo + 's');
    $('#buscadorGestion').val('');
    $('#cuerpoModalGestion').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('modalGestionCatalogo')).show();
    $.get('api/api_gestion_catalogo.php', { accion: 'ui_' + tipo.toLowerCase() }, function(html) {
        $('#cuerpoModalGestion').html(html);
    });
}

// ============== FILTRO DE HISTORIAL (SEMÁFORO Y VENDEDOR) ==============
function cargarHistorial() {
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
                if(c.estado_pago === 'Pago Medio') badgeClass = 'bg-warning text-dark'; 
                else if(c.estado_pago === 'Pagado') badgeClass = 'bg-success'; 

                html += `<tr>
                    <td class="fw-bold text-muted">${contador}</td>
                    <td class="fw-bold">${c.nro_cotizacion}</td>
                    <td>${c.cliente_nombre}</td>
                    <td><span class="badge bg-light text-dark border"><i class="bi bi-person"></i> ${c.vendedor_nombre}</span></td>
                    <td>${c.fecha_emision}</td>
                    <td class="fw-bold" style="color: #0B4C8C;">${formatoMoneda(c.total)} $</td>
                    <td><span class="badge ${badgeClass} dropdown-toggle" style="cursor:pointer; padding:6px 10px;" data-bs-toggle="dropdown">${c.estado_pago.toUpperCase()}</span>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item fw-bold text-danger" href="#" onclick="cambiarEstado(${c.ID}, 'sin pagar')"><i class="bi bi-circle-fill text-danger"></i> Sin Pagar</a></li>
                            <li><a class="dropdown-item fw-bold text-warning" href="#" onclick="cambiarEstado(${c.ID}, 'Pago Medio')"><i class="bi bi-circle-fill text-warning"></i> Pago Medio</a></li>
                            <li><a class="dropdown-item fw-bold text-success" href="#" onclick="cambiarEstado(${c.ID}, 'Pagado')"><i class="bi bi-circle-fill text-success"></i> Pagado</a></li>
                        </ul>
                    </td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-outline-warning fw-bold" onclick="cargarCotizacionParaEditar(${c.ID}, ${c.id_cliente}, '${c.cliente_nombre.replace(/'/g, "\\'")}')" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-info fw-bold ms-1" onclick="verDetalleHistorialCotizacion(${c.ID}, ${c.sub_total}, ${c.descuento}, ${c.iva}, ${c.total})" title="Ver Resumen"><i class="bi bi-eye"></i></button>
                        <a href="imprimir_cotizacion.php?id=${c.ID}" target="_blank" class="btn btn-sm btn-outline-primary ms-1" title="Ver PDF"><i class="bi bi-file-pdf"></i></a>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="eliminarCotizacion(${c.ID})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
                contador++;
            });
            $('#tablaHistorialBody').html(html || '<tr><td colspan="8" class="text-center">No hay cotizaciones registradas.</td></tr>');
        }
    }, 'json').fail(function() {
        $('#tablaHistorialBody').html('<tr><td colspan="8" class="text-center text-danger">Error de conexión.</td></tr>');
    });
}

function cambiarEstado(id, estado) {
    $.post('api/api_cotizaciones.php', { accion: 'cambiar_estado', id: id, estado: estado }, function(res) {
        if(res.success) cargarHistorial(); else Swal.fire('Error', res.msg, 'error');
    }, 'json');
}

function eliminarCotizacion(id) {
    Swal.fire({ title: '¿Eliminar Cotización?', text: "Esta acción es irreversible", icon: 'warning', showCancelButton: true }).then((result) => {
        if(result.isConfirmed) {
            $.post('api/api_cotizaciones.php', { accion: 'eliminar', id: id }, function(res) {
                if(res.success) cargarHistorial(); else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
}

function verDetalleHistorialCotizacion(id, subtotal, descuento, iva, total) {
    $('#det_modal_subtotal').text(formatoMoneda(subtotal) + ' $');
    $('#det_modal_descuento').text('-' + formatoMoneda(descuento) + ' $');
    $('#det_modal_iva').text(formatoMoneda(iva) + ' $');
    $('#det_modal_total').text(formatoMoneda(total) + ' $');

    $.get('api/api_cotizaciones.php', { accion: 'detalles', id_cotizacion: id }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(d => {
                let detalleText = d.detalle_diseno ? `<span class="badge bg-light text-secondary border border-secondary">${d.detalle_diseno}</span>` : `<span class="text-muted small">-</span>`;
                html += `<tr>
                            <td><span class="fw-bold">${d.tipo_prenda}</span><br><small class="text-muted">${d.genero}</small></td>
                            <td>${detalleText}</td>
                            <td class="text-center fw-bold">${d.cantidad}</td>
                            <td class="text-end">${formatoMoneda(d.precio_unitario)} $</td>
                            <td class="text-end fw-bold text-dark">${formatoMoneda(d.subtotal_linea)} $</td>
                        </tr>`;
            });
            $('#detallesCotizacionTablaBody').html(html);
            new bootstrap.Modal(document.getElementById('modalVerDetalleCotizacion')).show();
        } else {
            Swal.fire('Error', 'No se pudieron cargar los detalles', 'error');
        }
    }, 'json');
}

function cargarCotizacionParaEditar(id_cot, id_cli, nombre_cli) {
    $.get('api/api_cotizaciones.php', { accion: 'obtener', id: id_cot }, function(res) {
        if(res.success) {
            $('#idClienteSeleccionado').val(id_cli);
            $('#lblClienteSeleccionado').text(nombre_cli);
            $('#id_edit_cotizacion').val(id_cot);
            $('#badgeEstadoEdicion').show();

            $('#cot_contribuyente').prop('checked', res.data.contribuyente_especial == 1);
            $('#cot_notas').val(res.data.notas);
            
            $('.custom-check-small').removeClass('active-check');
            if(res.data.id_forma_pago) {
                $(`input[name="id_forma_pago"][value="${res.data.id_forma_pago}"]`).prop('checked', true).closest('.custom-check-small').addClass('active-check');
            }
            if(res.data.contribuyente_especial == 1) $('#cot_contribuyente').closest('.custom-check-small').addClass('active-check');

            carritoMemoria = res.detalles.map(d => {
                let catAsumida = (d.detalle_diseno && d.detalle_diseno.includes('Ubicación:')) ? 'Arte Final' : (d.categoria || 'Prenda');
                let vinculoPuroGuardado = '';
                let posicionPuraGuardada = d.detalle_diseno;
                
                if (catAsumida === 'Arte Final' && d.detalle_diseno.includes('[Para: ')) {
                    let partes = d.detalle_diseno.split('] - Ubicación: ');
                    vinculoPuroGuardado = partes[0].replace('[Para: ', '');
                    posicionPuraGuardada = partes[1] || '';
                }

                return {
                    idUnico: d.id_producto + '_' + d.genero + '_' + (d.detalle_diseno ? d.detalle_diseno.replace(/\s+/g, '') : ''),
                    id_producto: d.id_producto,
                    nombre: d.tipo_prenda,
                    genero: d.genero,
                    precio: parseFloat(d.precio_unitario),
                    cantidad: parseInt(d.cantidad),
                    subtotal: parseFloat(d.precio_unitario) * parseInt(d.cantidad),
                    detalle_diseno: d.detalle_diseno || '',
                    posicion_pura: posicionPuraGuardada,
                    vinculo_puro: vinculoPuroGuardado,
                    categoria: catAsumida
                };
            });

            actualizarResumenCarrito();
            $('#btnMenuCotizador').click();
            Swal.fire({ icon: 'info', title: 'Modo Edición', text: 'Cotización cargada al carrito', timer: 1500, showConfirmButton: false });
        }
    }, 'json');
}