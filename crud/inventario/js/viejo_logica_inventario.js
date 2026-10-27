// ================= VARIABLES GLOBALES =================
let carritoCompra = [];
let carritoReceta = [];
let prendaSeleccionadaReceta = { id: 0, nombre: '' };
let listadoSubcategoriasGlobal = []; 
window.vieneDeCompra = false; 

function JS_tieneVector(vector) {
    let vectores = $('#mis_vectores').val().split(',');
    return vectores.includes(vector.toString());
}

$(document).ready(function() {
    
    // Cargar mapa de subcategorías en segundo plano para el Select Dinámico
    $.get('api/api_inventario.php', {accion:'listar_subcat'}, function(res) {
        if(res.success && res.data) {
            listadoSubcategoriasGlobal = res.data;
        }
    }, 'json');

    // Cargar Filtros de Búsqueda de Proveedores
    cargarFiltrosProv();

    // Inicializar Select2 para Proveedores e Insumos en Compras (Buscador)
    if($('#compra_mp').length > 0) {
        $('#compra_mp').select2({ dropdownParent: $('#modalCompra'), width: '100%', placeholder: 'Buscar Insumo...' });
    }
    if($('#compra_proveedor').length > 0) {
        $('#compra_proveedor').select2({ dropdownParent: $('#modalCompra'), width: '100%', placeholder: 'Seleccione Proveedor...' });
    }
    if($('#gasto_proveedor').length > 0) {
        $('#gasto_proveedor').select2({ dropdownParent: $('#modalGastoServicio'), width: '100%', placeholder: 'Seleccione Proveedor...' });
    }

    // Escuchador de Moneda para recalcular totales visuales (Compras)
    $('#compra_moneda').on('change', function() { window.actualizarTablaCompra(); });

    // Escuchador para calcular equivalente en Bs en Abonos (Compras)
    $('#pago_compra_usd, #pago_compra_tasa').on('input', function() {
        let usd = parseFloat($('#pago_compra_usd').val()) || 0;
        let tasa = parseFloat($('#pago_compra_tasa').val()) || 0;
        if (tasa > 0) {
            $('#pago_compra_bs').val((usd * tasa).toFixed(2));
        } else {
            $('#pago_compra_bs').val('');
        }
    });

    // ================= SPA ROUTING =================
    $('.nav-link-custom').click(function(e) {
        e.preventDefault();
        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');
        $('#sidebar, #sidebarOverlay').removeClass('active');
        $('.vista-spa').hide();
        window.scrollTo({ top: 0, behavior: 'instant' });

        if (this.id === 'btnMenuCatMp') { $('#vistaCatMp').fadeIn(); cargarCatMp(); }
        else if (this.id === 'btnMenuSubCatMp') { $('#vistaSubCatMp').fadeIn(); cargarSubCatMp(); }
        else if (this.id === 'btnMenuMp') { $('#vistaMp').fadeIn(); cargarMp(); }
        else if (this.id === 'btnMenuProveedores') { $('#vistaProveedores').fadeIn(); cargarProveedores(); }
        else if (this.id === 'btnMenuCompras') { $('#vistaCompras').fadeIn(); cargarCompras(); }
        else if (this.id === 'btnMenuGastos') { $('#vistaGastos').fadeIn(); cargarGastos(); }
        else if (this.id === 'btnMenuPatrones') { $('#vistaPatrones').fadeIn(); cargarPatrones(); }
        else if (this.id === 'btnMenuReceta') { $('#vistaReceta').fadeIn(); cargarRecetasAgrupadas(); }
    });

    // Menú Responsive
    $('#btnHamburguesa, #sidebarOverlay').click(function() {
        $('#sidebar').toggleClass('active');
        $('#sidebarOverlay').toggleClass('active');
    });

    // Carga inicial según vector
    if (JS_tieneVector(21)) {
        cargarCatMp();
    } else if (JS_tieneVector(27)) {
        cargarRecetasAgrupadas();
    }

    // Limpiar modals de bootstrap en SPA y control de cascada de compras
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length === 0) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
        }
        
        if ((this.id === 'modalProveedor' || this.id === 'modalMp') && window.vieneDeCompra) {
            window.vieneDeCompra = false;
            $('#modalCompra').modal('show');
        }
    });

    // ================= EVENTOS DE CATEGORÍA Y SUBCATEGORÍA DINÁMICA =================
    $('#mp_cat').change(function() {
        let idCat = $(this).val();
        let txtCat = $(this).find('option:selected').text().toLowerCase();
        let $subcat = $('#mp_subcat');

        $subcat.empty().append('<option value="">Seleccione Categoría...</option>');
        
        if(idCat) {
            $subcat.prop('disabled', false);
            let filtradas = listadoSubcategoriasGlobal.filter(s => s.id_mp_categoria == idCat);
            
            if(filtradas.length > 0) {
                $subcat.empty().append('<option value="">Seleccione Subcategoría...</option>');
                filtradas.forEach(s => {
                    $subcat.append(`<option value="${s.id}">${s.nombre}</option>`);
                });
            } else {
                $subcat.empty().append('<option value="">No hay subcategorías en esta categoría</option>');
            }
        } else {
            $subcat.prop('disabled', true);
        }

        if(txtCat.includes('tela')) {
            $('#mp_tamano').attr('placeholder', 'Ej: Rollo ancho 1.50m');
            $('#mp_und').attr('placeholder', 'Ej: Mts, Kgs');
        } else if(txtCat.includes('hilo')) {
            $('#mp_tamano').attr('placeholder', 'Ej: Cono 5000 yardas');
            $('#mp_und').attr('placeholder', 'Ej: Cono, Und');
        } else if(txtCat.includes('boton') || txtCat.includes('cierre') || txtCat.includes('botón') || txtCat.includes('cremallera')) {
            $('#mp_tamano').attr('placeholder', 'Ej: 15mm, 20cm');
            $('#mp_und').attr('placeholder', 'Ej: Docena, Und, Gruesa');
        } else {
            $('#mp_tamano').attr('placeholder', 'Ej: Rollo, Und, Paquete');
            $('#mp_und').attr('placeholder', 'Ej: M, Kg, Und, Par');
        }
    });

    // ================= EVENTOS FORMS BASICOS =================
    $('#formCatMp, #formSubCatMp, #formProveedor').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        let idForm = form.attr('id');
        let btn = form.find('button[type="submit"]');
        let textoOrig = btn.text();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.post('api/api_inventario.php', form.serialize(), function(res) {
            if(res.success) {
                Swal.fire({icon:'success', title:'Guardado exitosamente', timer:1200, showConfirmButton:false});
                if(form.find('.btn-cancelar').length > 0) form.find('.btn-cancelar').click();
                else form[0].reset();

                if(idForm === 'formCatMp') { 
                    cargarCatMp(); 
                    setTimeout(() => window.location.reload(), 1000); 
                } 
                if(idForm === 'formSubCatMp') { 
                    cargarSubCatMp(); 
                    setTimeout(() => window.location.reload(), 1000); 
                } 
                if(idForm === 'formProveedor') { 
                    $('#modalProveedor').modal('hide'); 
                    cargarProveedores(); 
                    
                    if(window.vieneDeCompra) {
                        $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(p_res) {
                            let opts = '<option value="">Seleccione Proveedor...</option>';
                            let lastId = 0;
                            if(p_res.data) {
                                p_res.data.forEach(p => { 
                                    opts += `<option value="${p.ID}">${p.nombre}</option>`; 
                                    if(parseInt(p.ID) > lastId) lastId = parseInt(p.ID);
                                });
                                $('#compra_proveedor').empty().append(opts).val(lastId).trigger('change');
                            }
                            window.vieneDeCompra = false;
                            setTimeout(() => $('#modalCompra').modal('show'), 400);
                        }, 'json');
                    }
                }
            } else { 
                Swal.fire('Error', res.msg || 'No se pudo guardar', 'error'); 
            }
        }, 'json').always(()=> btn.prop('disabled', false).text(textoOrig));
    });

    $('.btn-cancelar').click(function() {
        let form = $(this).closest('form');
        form[0].reset();
        form.find('input[type="hidden"][name="id"]').val('0');
        $(this).addClass('d-none');
    });

    // Envío de formulario de Materia Prima
    $('#formMp').off('submit').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_inventario.php',
            type: 'POST',
            data: formData,
            contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Insumo registrado', timer: 1200, showConfirmButton: false});
                    $('#modalMp').modal('hide');
                    cargarMp();
                    
                    if (window.vieneDeCompra) {
                        $.get('api/api_inventario.php', {accion:'listar_mp'}, function(resMp) {
                            let opts = '<option value="">Buscar Insumo...</option>';
                            let lastId = 0;
                            if(resMp.data) {
                                resMp.data.forEach(m => { 
                                    opts += `<option value="${m.ID}" data-und="${m.unidad_medida}">${m.nombre} (${m.unidad_medida})</option>`; 
                                    if(parseInt(m.ID) > lastId) lastId = parseInt(m.ID);
                                });
                            }
                            $('#compra_mp').empty().append(opts).val(lastId).trigger('change');
                            window.vieneDeCompra = false;
                            setTimeout(() => $('#modalCompra').modal('show'), 400);
                        }, 'json');
                    }
                } else Swal.fire('Error', res.msg, 'error');
            },
            complete: function() { btn.prop('disabled', false).text('Guardar Materia Prima'); }
        });
    });

    // Envío de formulario de Patrones
    $('#formPatron').off('submit').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_inventario.php',
            type: 'POST',
            data: formData,
            contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Patrón guardado', timer: 1200, showConfirmButton: false});
                    $('#modalPatron').modal('hide');
                    cargarPatrones();
                } else Swal.fire('Error', res.msg, 'error');
            },
            complete: function() { btn.prop('disabled', false).text('Guardar Patrón'); }
        });
    });

    // ================= EVENTOS COMPRAS =================
    $('#formCompra').off('submit').on('submit', function(e) {
        e.preventDefault();
        let idProv = $('#compra_proveedor').val();
        let fecha = $('#compra_fecha').val();
        let estado = $('#compra_estado').val();

        if(!idProv || !fecha) return Swal.fire('Error', 'Debe seleccionar Proveedor y Fecha.', 'warning');
        if(carritoCompra.length === 0) return Swal.fire('Error', 'El carrito de compra está vacío.', 'warning');

        let subtotalInsumos = carritoCompra.reduce((sum, current) => sum + current.subtotal, 0);
        let iva = parseFloat($('#compra_iva').val()) || 0;
        let totalGeneral = subtotalInsumos + iva;

        let formData = new FormData(this);
        formData.append('accion', 'guardar_compra');
        formData.append('total', totalGeneral);
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
                            cargarCompras();
                            cargarMp(); // Refrescar inventario
                            cargarProveedores(); // Refrescar para ver el punto rojo si hubo deuda
                        } else { Swal.fire('Error', res.msg, 'error'); }
                    }
                });
            }
        });
    });

    $('#compra_iva').on('input', function() { window.actualizarTablaCompra(); });

    // Filtros Compras
    $('#buscadorCompras, #filtroEstadoCompras').on('input change', function() {
        let texto = $('#buscadorCompras').val().toLowerCase();
        let estado = $('#filtroEstadoCompras').val();

        $('#tbodyCompras tr').each(function() {
            let filaTexto = $(this).text().toLowerCase();
            let filaEstado = $(this).find('td:nth-child(4)').text() + " " + $(this).find('td:nth-child(5)').text(); 

            let matchTexto = filaTexto.includes(texto);
            let matchEstado = (estado === '') || filaEstado.includes(estado);

            if (matchTexto && matchEstado) $(this).show(); else $(this).hide();
        });
    });

    // ================= PAGOS DE COMPRA =================
    $('#formPagoCompra').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $('#btnGuardarAbono');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_inventario.php',
            type: 'POST',
            data: formData,
            contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Abono Registrado', timer: 1000, showConfirmButton: false});
                    let idc = $('#pago_compra_id').val();
                    $('#formPagoCompra')[0].reset();
                    $('#pago_compra_id').val(idc);
                    document.getElementById('pago_compra_fecha').valueAsDate = new Date();
                    window.cargarListaPagosCompra(idc);
                    cargarCompras();
                    cargarProveedores();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            },
            complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Abono'); }
        });
    });

    // ================= EVENTOS RECETAS =================
    $('#receta_mp_tmp').change(function() {
        let und = $(this).find(':selected').data('und') || 'UND';
        $('#lblUndRecetaTmp').text(und);
        window.calcularCostoRecetaTemporal();
    });

    $('#receta_cant_tmp').on('input', function() {
        window.calcularCostoRecetaTemporal();
    });
});

// ================= FUNCIÓN GENÉRICA ELIMINAR =================
window.eliminar = function(accion, id, callback) {
    Swal.fire({
        title: '¿Eliminar permanentemente?', 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((r) => {
        if(r.isConfirmed) {
            Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            
            $.post('api/api_inventario.php', {accion: accion, id: id}, function(res) {
                if(res.success) { 
                    Swal.fire({icon: 'success', title: 'Eliminado', timer:1000, showConfirmButton:false}); 
                    if(typeof callback === 'function') callback(); 
                    if (accion === 'eliminar_tipo_prov' || accion === 'eliminar_servicio_prov') cargarFiltrosProv();
                    if (accion === 'eliminar_gasto') cargarProveedores();
                } else { 
                    Swal.fire('No se pudo eliminar', res.msg || 'Este registro está en uso por otros módulos.', 'error'); 
                }
            }, 'json').fail(function() {
                Swal.fire('Error crítico', 'Hubo un fallo de comunicación con la base de datos.', 'error');
            });
        }
    });
};

function verFotoGrande(url, nombre) {
    Swal.fire({
        title: nombre,
        imageUrl: url,
        imageWidth: 400,
        imageAlt: 'Foto / Imagen',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { image: 'rounded shadow-sm border' }
    });
}

// ================= 1. CATEGORÍAS MP =================
function cargarCatMp() {
    $('#tbodyCatMp').html('<tr><td colspan="3" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_cat'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(c => {
            html += `<tr>
                <td class="text-muted">${c.id}</td><td class="fw-bold">${c.nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editarCat(${c.id}, '${c.nombre}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="eliminar('eliminar_cat', ${c.id}, cargarCatMp)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyCatMp').html(html || '<tr><td colspan="3" class="text-center text-muted">Sin categorías</td></tr>');
    }, 'json');
}
function editarCat(id, nombre) {
    $('#id_cat').val(id); $('#nombre_cat').val(nombre);
    $('#formCatMp .btn-cancelar').removeClass('d-none');
}

// ================= 2. SUBCATEGORÍAS MP =================
function cargarSubCatMp() {
    $('#tbodySubCatMp').html('<tr><td colspan="4" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_subcat'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(s => {
            html += `<tr>
                <td class="text-muted">${s.id}</td><td><span class="badge bg-light text-dark border">${s.categoria_padre}</span></td><td class="fw-bold text-primary">${s.nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editarSubCat(${s.id}, ${s.id_mp_categoria}, '${s.nombre}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="eliminar('eliminar_subcat', ${s.id}, cargarSubCatMp)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodySubCatMp').html(html || '<tr><td colspan="4" class="text-center text-muted">Sin subcategorías</td></tr>');
    }, 'json');
}
function editarSubCat(id, id_cat, nombre) {
    $('#id_subcat').val(id); $('#id_mp_categoria_sub').val(id_cat); $('#nombre_subcat').val(nombre);
    $('#formSubCatMp .btn-cancelar').removeClass('d-none');
}

// ================= 3. MATERIA PRIMA =================
function cargarMp() {
    $('#tbodyMp').html('<tr><td colspan="10" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_mp'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(m => {
            let infoFisica = `${m.color || '-'} / ${m.codigo_color || '-'}`;
            let tamano = m.tamano_dimension || '-';
            let fotoUrl = m.foto ? `../uploads/${m.foto}` : '../../img/camisa.jpg'; 
            
            html += `<tr>
                <td class="text-muted small">${m.ID}</td>
                <td>
                    <img src="${fotoUrl}" style="width:40px; height:40px; object-fit:cover; border-radius:4px; cursor:pointer; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" 
                         onclick="verFotoGrande('${fotoUrl}', '${m.nombre.replace(/'/g, "\\'")}')" title="Ver imagen">
                </td>
                <td class="fw-bold" style="color:#0B4C8C;">${m.nombre}</td>
                <td><small class="badge bg-light text-dark border">${m.subcategoria || '-'}</small></td>
                <td><small>${infoFisica}</small></td>
                <td><small>${tamano}</small></td>
                <td><span class="badge ${parseFloat(m.stock_actual) <= parseFloat(m.stock_minimo) ? 'bg-danger':'bg-success'}">${m.stock_actual}</span></td>
                <td><span class="badge bg-secondary">${m.unidad_medida}</span></td>
                <td class="fw-bold text-dark text-nowrap">${m.costo} $</td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick='editarMp(${JSON.stringify(m).replace(/'/g, "&#39;")})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="eliminar('eliminar_mp', ${m.ID}, cargarMp)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyMp').html(html || '<tr><td colspan="10" class="text-center text-muted">No hay materia prima registrada</td></tr>');
    }, 'json');
}

window.abrirModalMp = function() {
    $('#formMp')[0].reset();
    $('#id_mp').val('0');
    $('#mp_foto').val(''); 
    $('#mp_cat').val('').trigger('change');
    new bootstrap.Modal(document.getElementById('modalMp')).show();
}

window.editarMp = function(obj) {
    $('#id_mp').val(obj.ID); 
    $('#mp_nombre').val(obj.nombre); 

    let subcatObj = listadoSubcategoriasGlobal.find(s => s.id == obj.id_mp_subcategoria);
    if(subcatObj) {
        $('#mp_cat').val(subcatObj.id_mp_categoria).trigger('change');
        setTimeout(() => {
            $('#mp_subcat').val(obj.id_mp_subcategoria);
        }, 50);
    } else {
        $('#mp_cat').val('').trigger('change');
    }

    $('#mp_color').val(obj.color); 
    $('#mp_codigo_color').val(obj.codigo_color); 
    $('#mp_textura').val(obj.textura); 
    $('#mp_tamano').val(obj.tamano_dimension); 
    $('#mp_origen').val(obj.origen_procedencia); 
    $('#mp_comp').val(obj.composicion); 
    $('#mp_desc').val(obj.descripcion); 
    $('#mp_und').val(obj.unidad_medida); 
    $('#mp_costo').val(obj.costo);
    $('#mp_stock').val(obj.stock_actual); 
    $('#mp_stock_min').val(obj.stock_minimo); 
    $('#mp_foto').val(''); 
    new bootstrap.Modal(document.getElementById('modalMp')).show();
}

// ================= 4. PROVEEDORES DINAMICOS =================

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
            $.post('api/api_inventario.php', { accion: accionGuardar, nombre: data.text }, function(res) {
                if(res.success) {
                    let newOption = new Option(data.text, data.text, false, false); 
                    $(selector).find(`option[value="${data.id}"]`).replaceWith(newOption);
                    $(selector).val(data.text).trigger('change');
                    cargarFiltrosProv(); 
                }
            }, 'json');
        }
    });

    $.get('api/api_inventario.php', { accion: accionListar }, function(res) {
        $(selector).empty().append('<option value="">Seleccione o escriba...</option>');
        if(res.success && res.data) {
            res.data.forEach(item => {
                $(selector).append(new Option(item.nombre, item.nombre, false, false));
            });
        }
    }, 'json');
}

$('#modalProveedor').on('shown.bs.modal', function () {
    if(!$('#prov_tipo').hasClass('select2-hidden-accessible')) {
        initSelect2Dinamico('#prov_tipo', 'listar_tipos_prov', 'guardar_tipo_prov');
    }
    if(!$('#prov_servicio').hasClass('select2-hidden-accessible')) {
        initSelect2Dinamico('#prov_servicio', 'listar_servicios_prov', 'guardar_servicio_prov');
    }
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

function cargarProveedores() {
    $('#tbodyProveedores').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(p => {
            
            let deuda = parseFloat(p.deuda_total) || 0;
            let badgeDeuda = deuda > 0 ? `<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" title="Debe: ${deuda.toFixed(2)}$"><span class="visually-hidden">Deuda</span></span>` : '';
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
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick='editarProveedor(${JSON.stringify(p).replace(/'/g, "&#39;")})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="eliminar('eliminar_proveedor', ${p.ID}, cargarProveedores)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyProveedores').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay proveedores registrados</td></tr>');
        
        // Mantener los filtros activos al recargar
        $('#buscadorProveedores').trigger('change');
    }, 'json');
}

window.abrirModalProveedor = function() {
    $('#formProveedor')[0].reset();
    $('#prov_id').val('0');
    if($('#prov_tipo').hasClass('select2-hidden-accessible')) $('#prov_tipo').val('').trigger('change');
    if($('#prov_servicio').hasClass('select2-hidden-accessible')) $('#prov_servicio').val('').trigger('change');
    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
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
    $('#prov_pm_cedula').val(obj.pm_cedula || '');
    $('#prov_pm_telefono').val(obj.pm_telefono || '');

    let tipo = obj.tipo_proveedor || '';
    if (tipo !== '') {
        if ($('#prov_tipo').find("option[value='" + tipo + "']").length) {
            $('#prov_tipo').val(tipo).trigger('change');
        } else { 
            let newOption = new Option(tipo, tipo, true, true);
            $('#prov_tipo').append(newOption).trigger('change');
        }
    } else { $('#prov_tipo').val('').trigger('change'); }

    let serv = obj.servicio_ofrecido || '';
    if (serv !== '') {
        if ($('#prov_servicio').find("option[value='" + serv + "']").length) {
            $('#prov_servicio').val(serv).trigger('change');
        } else { 
            let newOption = new Option(serv, serv, true, true);
            $('#prov_servicio').append(newOption).trigger('change');
        }
    } else { $('#prov_servicio').val('').trigger('change'); }

    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
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

$(document).on('click', '.btn-ver-deuda', function() {
    let idProveedor = $(this).data('id');
    let nombreProveedor = $(this).data('nombre');
    
    $('#lbl_nombre_proveedor_deuda').text(nombreProveedor);
    $('#tabla_deudas_body').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-danger"></span> Cargando deudas...</td></tr>');
    $('#modalDeudaProveedor').modal('show');

    $.ajax({
        url: 'api/api_deudas.php',
        type: 'GET',
        data: { id_proveedor: idProveedor },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                $('#lbl_deuda_total_modal').text(res.total_deuda.toFixed(2) + ' $');
                
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
                                <td class="text-end fw-bold">${total.toFixed(2)} ${mon}</td>
                                <td class="text-end text-success">${pagado.toFixed(2)} ${mon}</td>
                                <td class="text-end text-danger fw-bold">${pend.toFixed(2)} ${mon}</td>
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
        error: function() {
            $('#tabla_deudas_body').html('<tr><td colspan="8" class="text-center text-danger">Fallo de red al buscar deudas</td></tr>');
        }
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

// ================= 5. COMPRAS Y CUENTAS POR PAGAR =================
window.crearProveedorDesdeCompra = function() {
    window.vieneDeCompra = true;
    $('#modalCompra').modal('hide');
    abrirModalProveedor();
};

window.crearMpDesdeCompra = function() {
    window.vieneDeCompra = true;
    $('#modalCompra').modal('hide');
    abrirModalMp();
};

function cargarCompras() {
    $('#tbodyCompras').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_compras'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(c => {
            let bgEstLog = c.estado === 'Recibido' ? 'bg-success' : 'bg-warning text-dark';
            let bgEstPago = c.estado_pago === 'Pagado' ? 'bg-success' : (c.estado_pago === 'Abonado' ? 'bg-info text-dark' : 'bg-danger');
            let mon = c.moneda === 'BS' ? 'BS' : '$';
            
            let btnRecibir = '';
            if(c.estado === 'Pendiente de Recepción') {
                btnRecibir = `<button class="btn btn-sm btn-success fw-bold ms-1" onclick="window.marcarCompraRecibida(${c.ID})" title="Marcar como Recibido"><i class="bi bi-check-circle"></i></button>`;
            }

            let btnPagos = `<button class="btn btn-sm btn-outline-success fw-bold ms-1" onclick="window.abrirModalPagosCompra(${c.ID})" title="Cuentas por Pagar (Abonos)"><i class="bi bi-wallet2"></i></button>`;

            html += `<tr>
                <td class="fw-bold text-muted">COMP-${c.ID}</td>
                <td class="text-nowrap">${c.fecha_compra}</td>
                <td class="fw-bold" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${c.proveedor_nombre}</td>
                <td><span class="badge ${bgEstLog}">${c.estado}</span></td>
                <td><span class="badge ${bgEstPago}">${c.estado_pago || 'Sin Pagar'}</span></td>
                <td class="fw-bold text-end fs-6 text-nowrap">${c.costo} ${mon}</td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning fw-bold" onclick="window.editarCompra(${c.ID})" title="Editar Compra"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-primary fw-bold ms-1" onclick="window.verDetalleCompra(${c.ID}, '${c.proveedor_nombre.replace(/'/g, "\\'")}')" title="Ver Detalle"><i class="bi bi-eye"></i></button>
                    ${btnPagos}
                    ${btnRecibir}
                </td></tr>`;
        });
        $('#tbodyCompras').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay compras registradas</td></tr>');
    }, 'json');
}

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

            $('#lbl_deuda_total').text(totalDeuda.toFixed(2) + ' ' + moneda);
            $('#lbl_deuda_restante').text(resta.toFixed(2) + ' ' + moneda);

            if (resta <= 0) {
                $('#lbl_deuda_restante').removeClass('text-danger').addClass('text-success');
                $('#btnGuardarAbono').prop('disabled', true).text('Deuda Saldada');
            } else {
                $('#lbl_deuda_restante').removeClass('text-success').addClass('text-danger');
                $('#btnGuardarAbono').prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Abono');
            }

            if(res.data && res.data.length > 0) {
                res.data.forEach(p => {
                    let btnSoporte = p.comprobante ? `<a href="../uploads/compras/pagos/${p.comprobante}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-image"></i> Ver</a>` : '-';
                    html += `<tr>
                        <td>${p.fecha_pago}</td>
                        <td>${p.metodo_pago}</td>
                        <td>${p.referencia || '-'}</td>
                        <td class="text-end fw-bold text-success">${parseFloat(p.monto_usd).toFixed(2)}</td>
                        <td class="text-center">${btnSoporte}</td>
                        <td class="text-end">
                            <button class="btn btn-sm text-danger" onclick="window.eliminarPagoCompra(${p.id}, ${id_compra})"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>';
            }
            $('#tbodyPagosCompra').html(html);
        }
    }, 'json');
};

window.eliminarPagoCompra = function(id_pago, id_compra) {
    Swal.fire({
        title: '¿Eliminar este abono?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_pago_compra', id: id_pago}, function(res) {
                if(res.success) {
                    window.cargarListaPagosCompra(id_compra);
                    cargarCompras();
                    cargarProveedores();
                } else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
};

window.abrirModalCompra = function() {
    $('#formCompra')[0].reset();
    carritoCompra = [];
    document.getElementById('compra_fecha').valueAsDate = new Date();
    $('#compra_estado').val('Pendiente de Recepción');
    $('#compra_es_credito').val('1').css('pointer-events', 'auto');
    $('#compra_moneda').val('USD');
    $('#compra_id_edit').val('0');
    $('#compra_archivo').val('');
    $('#compra_mp').val('').trigger('change');
    window.actualizarTablaCompra();

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#compra_proveedor').empty().append(opts).trigger('change');
        new bootstrap.Modal(document.getElementById('modalCompra')).show();
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
            $('#compra_moneda').val(res.compra.moneda || 'USD');
            $('#compra_iva').val(res.compra.iva);
            
            let pagado = parseFloat(res.compra.monto_pagado);
            let costo_total = parseFloat(res.compra.costo);
            
            if (pagado >= costo_total) {
                $('#compra_es_credito').val('0').css('pointer-events', 'none'); 
            } else if (pagado > 0) {
                $('#compra_es_credito').val('1').css('pointer-events', 'none'); 
            } else {
                $('#compra_es_credito').val('1').css('pointer-events', 'auto'); 
            }

            carritoCompra = [];
            if(res.data) {
                res.data.forEach(d => {
                    carritoCompra.push({
                        id_mp: d.id_materia,
                        nombre: d.mp_nombre,
                        cantidad: parseFloat(d.cantidad),
                        costo_u: parseFloat(d.precio_unitario),
                        subtotal: parseFloat(d.cantidad) * parseFloat(d.precio_unitario)
                    });
                });
            }

            window.actualizarTablaCompra();
            new bootstrap.Modal(document.getElementById('modalCompra')).show();
        } else {
            Swal.fire('Error', 'No se pudieron cargar los datos de la compra.', 'error');
        }
    }, 'json');
};

window.agregarItemCompra = function() {
    let idMp = $('#compra_mp').val();
    let cant = parseFloat($('#compra_cant').val());
    let costoU = parseFloat($('#compra_costo_unit').val());

    if(!idMp || isNaN(cant) || cant <= 0 || isNaN(costoU) || costoU <= 0) {
        return Swal.fire('Error', 'Seleccione un insumo y coloque cantidad y costo válido.', 'warning');
    }

    let nombreMp = $('#compra_mp option:selected').text();
    let subtotal = cant * costoU;

    let idx = carritoCompra.findIndex(x => x.id_mp == idMp);
    if(idx > -1) {
        carritoCompra[idx].cantidad += cant;
        carritoCompra[idx].costo_u = costoU; 
        carritoCompra[idx].subtotal = carritoCompra[idx].cantidad * carritoCompra[idx].costo_u;
    } else {
        carritoCompra.push({ id_mp: idMp, nombre: nombreMp, cantidad: cant, costo_u: costoU, subtotal: subtotal });
    }

    $('#compra_cant').val('');
    $('#compra_costo_unit').val('');
    $('#compra_mp').val('').trigger('change');
    window.actualizarTablaCompra();
};

window.quitarItemCompra = function(idx) {
    carritoCompra.splice(idx, 1);
    window.actualizarTablaCompra();
};

window.calcularIvaAutomatico = function() {
    let subtotalInsumos = carritoCompra.reduce((sum, current) => sum + current.subtotal, 0);
    let ivaAuto = subtotalInsumos * 0.16;
    $('#compra_iva').val(ivaAuto.toFixed(2));
    window.actualizarTablaCompra();
};

window.editarFilaCompra = function(idx, campo, valor) {
    let val = parseFloat(valor);
    if(isNaN(val) || val < 0) val = 0; 
    carritoCompra[idx][campo] = val;
    carritoCompra[idx].subtotal = carritoCompra[idx].cantidad * carritoCompra[idx].costo_u;
    window.actualizarTablaCompra();
};

window.actualizarTablaCompra = function() {
    let html = '';
    let subtotal = 0;
    
    if(carritoCompra.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted">Añade insumos a la compra.</td></tr>';
    } else {
        carritoCompra.forEach((item, idx) => {
            subtotal += item.subtotal;
            html += `<tr>
                <td class="fw-bold text-dark small align-middle">${item.nombre}</td>
                <td class="text-center align-middle" style="width: 100px;">
                    <input type="number" step="0.01" class="form-control form-control-sm text-center fw-bold border-primary shadow-sm mx-auto" value="${item.cantidad}" onchange="window.editarFilaCompra(${idx}, 'cantidad', this.value)">
                </td>
                <td class="text-end align-middle" style="width: 120px;">
                    <input type="number" step="0.01" class="form-control form-control-sm text-end fw-bold border-primary shadow-sm ms-auto" value="${item.costo_u}" onchange="window.editarFilaCompra(${idx}, 'costo_u', this.value)">
                </td>
                <td class="text-end fw-bold text-primary align-middle">${item.subtotal.toFixed(2)}</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm text-danger py-0" onclick="quitarItemCompra(${idx})"><i class="bi bi-x-circle-fill fs-5"></i></button>
                </td>
            </tr>`;
        });
    }
    
    $('#tbodyListaCompra').html(html);
    let iva = parseFloat($('#compra_iva').val()) || 0;
    let totalGen = subtotal + iva;
    let mon = $('#compra_moneda').val() === 'BS' ? 'BS' : '$';
    $('#lbl_total_compra').text(totalGen.toFixed(2) + ' ' + mon);
};

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
                    cargarCompras();
                    cargarMp();
                } else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
};

window.verDetalleCompra = function(id_compra, proveedor) {
    $('#ver_compra_prov').html(`<i class="bi bi-building text-primary"></i> ${proveedor}`);
    $('#tbodyVerDetalleCompra').html('<tr><td colspan="4" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    new bootstrap.Modal(document.getElementById('modalVerDetalleCompra')).show();

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
            $('#ver_compra_iva').text(parseFloat(c.iva || 0).toFixed(2) + ' ' + moneda);
            $('#ver_compra_total').text(parseFloat(c.costo).toFixed(2) + ' ' + moneda);

            if(c.archivo_factura) $('#ver_btn_descargar_factura').html(`<a href="../uploads/compras/${c.archivo_factura}" target="_blank" class="btn btn-outline-danger fw-bold"><i class="bi bi-file-earmark-pdf"></i> Ver Soporte</a>`);
            else $('#ver_btn_descargar_factura').empty();

            let html = '';
            if(res.data) {
                res.data.forEach(d => {
                    let sub = parseFloat(d.cantidad) * parseFloat(d.precio_unitario);
                    html += `<tr>
                        <td class="fw-bold text-dark">${d.mp_nombre}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${d.cantidad} ${d.unidad_medida}</span></td>
                        <td class="text-end text-muted">${parseFloat(d.precio_unitario).toFixed(2)}</td>
                        <td class="text-end fw-bold text-primary">${sub.toFixed(2)}</td>
                    </tr>`;
                });
            }
            $('#tbodyVerDetalleCompra').html(html || '<tr><td colspan="4" class="text-center">Sin detalles</td></tr>');
        } else {
            $('#tbodyVerDetalleCompra').html('<tr><td colspan="4" class="text-center text-danger">Error cargando detalles</td></tr>');
        }
    }, 'json');
};

// ================= GASTOS POR SERVICIOS (AIRE ACOND, ETC) =================
function cargarGastos() {
    $('#tbodyGastos').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_gastos'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(g => {
            let bgEstPago = g.estado_pago === 'Pagado' ? 'bg-success' : (g.estado_pago === 'Abonado' ? 'bg-info text-dark' : 'bg-danger');
            
            let btnPagos = `<button class="btn btn-sm btn-outline-success fw-bold ms-1" onclick="window.abrirModalPagosGasto(${g.id})" title="Cuentas por Pagar (Abonos)"><i class="bi bi-wallet2"></i></button>`;

            html += `<tr>
                <td class="fw-bold text-muted">SERV-${g.id}</td>
                <td class="text-nowrap">${g.fecha}</td>
                <td class="fw-bold" style="color:#0B4C8C;"><i class="bi bi-building"></i> ${g.proveedor_nombre}</td>
                <td>${g.factura || '-'}</td>
                <td><small>${g.descripcion}</small></td>
                <td class="fw-bold text-end fs-6 text-nowrap">${g.monto_total} $</td>
                <td><span class="badge ${bgEstPago}">${g.estado_pago || 'Sin Pagar'}</span></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning fw-bold" onclick="window.editarGasto(${g.id}, ${g.id_proveedor}, '${g.fecha}', '${g.factura.replace(/'/g, "\\'")}', '${g.descripcion.replace(/'/g, "\\'")}', ${g.monto_total})" title="Editar Gasto"><i class="bi bi-pencil"></i></button>
                    ${btnPagos}
                    <button class="btn btn-sm btn-outline-danger fw-bold ms-1" onclick="eliminar('eliminar_gasto', ${g.id}, cargarGastos)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyGastos').html(html || '<tr><td colspan="8" class="text-center text-muted">No hay gastos registrados</td></tr>');
    }, 'json');
}

window.abrirModalGasto = function() {
    $('#formGastoServicio')[0].reset();
    $('#gasto_id_edit').val('0');
    document.getElementById('gasto_fecha').valueAsDate = new Date();
    
    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#gasto_proveedor').empty().append(opts).trigger('change');
        new bootstrap.Modal(document.getElementById('modalGastoServicio')).show();
    }, 'json');
};

window.editarGasto = function(id, id_proveedor, fecha, factura, descripcion, monto_total) {
    $('#formGastoServicio')[0].reset();
    $('#gasto_id_edit').val(id);
    $('#gasto_fecha').val(fecha);
    $('#gasto_factura').val(factura);
    $('#gasto_monto_total').val(monto_total);
    $('#gasto_desc').val(descripcion);

    $.get('api/api_inventario.php', {accion:'listar_proveedores'}, function(res) {
        let opts = '<option value="">Seleccione Proveedor...</option>';
        if(res.data) res.data.forEach(p => { opts += `<option value="${p.ID}">${p.nombre}</option>`; });
        $('#gasto_proveedor').empty().append(opts).val(id_proveedor).trigger('change');
        new bootstrap.Modal(document.getElementById('modalGastoServicio')).show();
    }, 'json');
};

$('#formGastoServicio').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    let btn = $('#btnGuardarGasto');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.post('api/api_inventario.php', formData, function(res) {
        if(res.success) {
            Swal.fire({icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false});
            $('#modalGastoServicio').modal('hide');
            cargarGastos();
            cargarProveedores();
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json').always(()=> btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Gasto'));
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
            let totalDeuda = parseFloat(res.resumen.monto_total) || 0;
            let totalPagado = parseFloat(res.resumen.monto_pagado) || 0;
            let resta = totalDeuda - totalPagado;
            if (resta < 0) resta = 0;

            $('#lbl_deuda_total_gasto').text(totalDeuda.toFixed(2) + ' $');
            $('#lbl_deuda_restante_gasto').text(resta.toFixed(2) + ' $');

            if (resta <= 0) {
                $('#lbl_deuda_restante_gasto').removeClass('text-danger').addClass('text-success');
                $('#btnGuardarAbonoGasto').prop('disabled', true).text('Deuda Saldada');
            } else {
                $('#lbl_deuda_restante_gasto').removeClass('text-success').addClass('text-danger');
                $('#btnGuardarAbonoGasto').prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Pago');
            }

            if(res.data && res.data.length > 0) {
                res.data.forEach(p => {
                    let btnSoporte = p.comprobante ? `<a href="../uploads/gastos/${p.comprobante}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-image"></i> Ver</a>` : '-';
                    html += `<tr>
                        <td>${p.fecha_pago}</td>
                        <td>${p.metodo_pago}</td>
                        <td>${p.referencia || '-'}</td>
                        <td class="text-end fw-bold text-success">${parseFloat(p.monto_usd).toFixed(2)}</td>
                        <td class="text-center">${btnSoporte}</td>
                        <td class="text-end">
                            <button class="btn btn-sm text-danger" onclick="window.eliminarPagoGasto(${p.id}, ${id_gasto})"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>';
            }
            $('#tbodyPagosGasto').html(html);
        }
    }, 'json');
};

$('#formPagoGasto').on('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let btn = $('#btnGuardarAbonoGasto');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: 'api/api_inventario.php',
        type: 'POST',
        data: formData,
        contentType: false, processData: false, dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({icon: 'success', title: 'Pago Registrado', timer: 1000, showConfirmButton: false});
                let idg = $('#pago_gasto_id').val();
                $('#formPagoGasto')[0].reset();
                $('#pago_gasto_id').val(idg);
                document.getElementById('pago_gasto_fecha').valueAsDate = new Date();
                window.cargarListaPagosGasto(idg);
                cargarGastos();
                cargarProveedores();
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
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
                    cargarGastos();
                    cargarProveedores();
                } else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
};

// ================= 6. PATRONES DE COSTURA =================
function cargarPatrones() {
    $('#tbodyPatrones').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_patrones'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(p => {
            let fotoUrl = p.foto ? `../uploads/patrones/${p.foto}` : '../../img/camisa.jpg'; 
            html += `<tr>
                <td class="text-muted small">${p.ID}</td>
                <td>
                    <img src="${fotoUrl}" style="width:40px; height:40px; object-fit:cover; border-radius:4px; cursor:pointer; border: 1px solid #ccc;" 
                         onclick="verFotoGrande('${fotoUrl}', 'Patrón: ${p.codigo_patron.replace(/'/g, "\\'")}')" title="Ver imagen">
                </td>
                <td class="fw-bold" style="color:#0B4C8C;">${p.codigo_patron}</td>
                <td><span class="badge bg-light text-dark border">${p.categoria || '-'}</span></td>
                <td class="fw-bold text-success">${p.Talla || '-'}</td>
                <td><small>${p.descripcion || '-'}</small></td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick='editarPatron(${JSON.stringify(p).replace(/'/g, "&#39;")})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="eliminar('eliminar_patron', ${p.ID}, cargarPatrones)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyPatrones').html(html || '<tr><td colspan="7" class="text-center text-muted">No hay patrones registrados</td></tr>');
    }, 'json');
}

window.abrirModalPatron = function() {
    $('#formPatron')[0].reset();
    $('#patron_id').val('0');
    $('#patron_foto').val('');
    new bootstrap.Modal(document.getElementById('modalPatron')).show();
};

window.editarPatron = function(obj) {
    $('#patron_id').val(obj.ID);
    $('#patron_codigo').val(obj.codigo_patron);
    $('#patron_categoria').val(obj.categoria);
    $('#patron_talla').val(obj.Talla);
    $('#patron_medidas').val(obj.medidas);
    $('#patron_desc').val(obj.descripcion);
    $('#patron_foto').val('');
    new bootstrap.Modal(document.getElementById('modalPatron')).show();
};

// ================= 7. RECETAS MÚLTIPLES (CARRITO DE INSUMOS) =================
function cargarRecetasAgrupadas() {
    $('#tbodyRecetasAgrupadas').html('<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_recetas_agrupadas'}, function(res) {
        let html = '';
        if(res.data && res.data.length > 0) {
            res.data.forEach(r => {
                html += `<tr>
                    <td class="fw-bold" style="color: #0B4C8C;"><i class="bi bi-tag-fill text-warning me-1"></i> ${r.producto_nombre}</td>
                    <td class="text-center"><span class="badge bg-secondary">${r.cant_insumos} Elementos</span></td>
                    <td class="fw-bold text-success text-end">${parseFloat(r.costo_total).toFixed(2)} $</td>
                    <td class="text-center">
                        <button class="btn btn-sm text-white fw-bold shadow-sm" style="background-color: #FF7518; border-radius: 20px;" onclick="verReceta(${r.id_producto}, '${r.producto_nombre.replace(/'/g, "\\'")}', ${r.costo_total})">
                            <i class="bi bi-eye"></i> Ver
                        </button>
                    </td></tr>`;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center text-muted py-4">Aún no hay prendas con recetas configuradas. Selecciona una prenda en el panel de la izquierda.</td></tr>';
        }
        $('#tbodyRecetasAgrupadas').html(html);
    }, 'json');
}

window.cambiarPrendaReceta = function(id_prod, nombre_prod) {
    if(!id_prod) {
        $('#panel_constructor_receta').hide();
        return;
    }
    prendaSeleccionadaReceta = { id: id_prod, nombre: nombre_prod };
    carritoReceta = [];
    $('#receta_mp_tmp').val('').trigger('change');
    $('#receta_cant_tmp').val('');
    $('#receta_costo_tmp').val('');
    window.actualizarTablaRecetaTemporal();
    $('#panel_constructor_receta').fadeIn();
};

window.calcularCostoRecetaTemporal = function() {
    let costo = parseFloat($('#receta_mp_tmp option:selected').data('costo')) || 0;
    let cant = parseFloat($('#receta_cant_tmp').val()) || 0;
    $('#receta_costo_tmp').val((costo * cant).toFixed(2) + ' $');
};

window.agregarItemAListaReceta = function() {
    let idMp = $('#receta_mp_tmp').val();
    let cant = parseFloat($('#receta_cant_tmp').val());
    
    if(!idMp || isNaN(cant) || cant <= 0) return Swal.fire('Error','Seleccione insumo y coloque una cantidad válida','warning');
    
    let nombreMp = $('#receta_mp_tmp option:selected').text();
    let costoU = parseFloat($('#receta_mp_tmp option:selected').data('costo')) || 0;
    
    let existe = carritoReceta.findIndex(x => x.id_mp == idMp);
    if(existe > -1) {
        carritoReceta[existe].cantidad += cant;
        carritoReceta[existe].costo_linea = carritoReceta[existe].cantidad * costoU;
    } else {
        carritoReceta.push({
            id_mp: idMp,
            nombre: nombreMp,
            cantidad: cant,
            costo_u: costoU,
            costo_linea: cant * costoU
        });
    }

    $('#receta_mp_tmp').val('').trigger('change');
    $('#receta_cant_tmp').val('');
    $('#receta_costo_tmp').val('');
    window.actualizarTablaRecetaTemporal();
};

window.quitarItemListaReceta = function(idx) {
    carritoReceta.splice(idx, 1);
    window.actualizarTablaRecetaTemporal();
};

window.actualizarTablaRecetaTemporal = function() {
    let html = '';
    let total = 0;
    if(carritoReceta.length === 0) {
        html = '<li class="list-group-item text-center text-muted small pb-0 border-0">Añade insumos a la lista.</li>';
    } else {
        carritoReceta.forEach((item, idx) => {
            total += item.costo_linea;
            html += `<li class="list-group-item d-flex justify-content-between align-items-center bg-white mb-1 border rounded shadow-sm">
                <div class="small w-100">
                    <span class="fw-bold text-dark d-block text-truncate" style="max-width: 90%;"><i class="bi bi-box-seam text-secondary me-1"></i> ${item.nombre}</span>
                    <span class="text-muted" style="font-size: 0.75rem;">Requiere: <b class="text-dark">${item.cantidad}</b> × ${item.costo_u}$ = <span class="fw-bold text-success">${item.costo_linea.toFixed(2)}$</span></span>
                </div>
                <button class="btn btn-sm text-danger py-0 px-2" onclick="window.quitarItemListaReceta(${idx})"><i class="bi bi-trash fs-5"></i></button>
            </li>`;
        });
    }
    $('#ul_lista_receta').html(html);
    $('#lbl_costo_total_receta').text(total.toFixed(2) + ' $');
};

window.guardarRecetaCompleta = function() {
    if(carritoReceta.length === 0) return Swal.fire('Atención','La lista de insumos está vacía. Añade elementos primero.','warning');
    
    Swal.fire({
        title: '¿Guardar Receta Completa?', 
        text: 'Esto guardará todos los insumos agrupados para la prenda: ' + prendaSeleccionadaReceta.nombre, 
        icon: 'question', 
        showCancelButton: true,
        confirmButtonColor: '#0B4C8C'
    }).then((r) => {
        if(r.isConfirmed) {
            Swal.fire({title:'Estructurando...', allowOutsideClick:false, didOpen:()=>{Swal.showLoading();}});
            
            $.post('api/api_inventario.php', {
                accion: 'guardar_receta_multiple',
                id_producto: prendaSeleccionadaReceta.id,
                tipo_prenda: prendaSeleccionadaReceta.nombre,
                insumos: JSON.stringify(carritoReceta)
            }, function(res) {
                if(res.success) {
                    Swal.fire('¡Éxito!', 'Receta construida y guardada correctamente.', 'success');
                    $('#receta_prod_master').val('').trigger('change');
                    cargarRecetasAgrupadas();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
};

window.verReceta = function(id_producto, nombre_producto, costo_total) {
    $('#lbl_receta_prenda_nombre').text(nombre_producto);
    $('#lbl_receta_costo_total').text(parseFloat(costo_total).toFixed(2) + ' $');
    $('#tbodyDetalleReceta').html('<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>');
    
    new bootstrap.Modal(document.getElementById('modalVerReceta')).show();

    $.get('api/api_inventario.php', {accion:'listar_detalle_receta', id_prod: id_producto}, function(res) {
        let html = '';
        if(res.data) {
            res.data.forEach(item => {
                html += `<tr>
                    <td><span class="fw-bold text-dark">${item.mp_nombre}</span> <br> <small class="text-muted">Costo Base: ${item.mp_costo}$ x ${item.unidad_medida}</small></td>
                    <td class="text-center"><span class="badge bg-light text-dark border border-secondary">${item.cantidad_por_unidad} ${item.unidad_medida}</span></td>
                    <td class="fw-bold text-end" style="color: #0B4C8C;">${parseFloat(item.costo_linea).toFixed(2)} $</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarItemReceta(${item.id_receta}, ${id_producto}, '${nombre_producto.replace(/'/g, "\\'")}')" title="Quitar insumo"><i class="bi bi-x-circle"></i></button>
                    </td>
                </tr>`;
            });
        }
        $('#tbodyDetalleReceta').html(html);
    }, 'json');
};

window.eliminarItemReceta = function(id_item, id_producto, nombre_producto) {
    Swal.fire({title:'¿Quitar este insumo de la receta?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sí, quitar'})
    .then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_item_receta', id: id_item}, function(res) {
                if(res.success) { 
                    cargarRecetasAgrupadas();
                    $('#modalVerReceta').modal('hide');
                    setTimeout(() => { Swal.fire('Insumo quitado', '', 'success'); }, 400);
                }
                else { Swal.fire('Error', res.msg, 'error'); }
            }, 'json');
        }
    });
};