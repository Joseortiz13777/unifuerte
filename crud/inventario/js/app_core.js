// ================= VARIABLES GLOBALES Y UTILIDADES =================
if (typeof window.formatMoney === 'undefined') {
    window.formatMoney = function(val) {
        return parseFloat(val || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };
}

if (typeof window.parseNum === 'undefined') {
    window.parseNum = function(val) {
        if (typeof val === 'number') return val;
        if (val === undefined || val === null || val === '') return 0;
        let str = String(val).trim();
        // Permite entender formatos con comas y puntos (Ej: 1.234,56 o 1234,56)
        if (str.includes('.') && str.includes(',')) {
            if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
                str = str.replace(/\./g, '').replace(',', '.');
            } else { str = str.replace(/\,/g, ''); }
        } else if (str.includes(',')) {
            str = str.replace(',', '.');
        }
        let n = parseFloat(str.replace(/[^0-9\.\-]/g, ''));
        return isNaN(n) ? 0 : n;
    };
}

var carritoReceta = [];
var prendaSeleccionadaReceta = { id: 0, nombre: '' };
var listadoSubcategoriasGlobal = []; 
var vieneDeCompra = false; 
var ordenActivaParaConsumo = 0;
var idRecetaMasterGlobal = 0;

function JS_tieneVector(vector) {
    let vectores = $('#mis_vectores').val().split(',');
    return vectores.includes(vector.toString());
}

$(document).ready(function() {
    $.get('api/api_inventario.php', {accion:'listar_subcat'}, function(res) {
        if(res.success && res.data) { listadoSubcategoriasGlobal = res.data; }
    }, 'json');

    if($('#receta_prod_master').length > 0) $('#receta_prod_master').select2({ width: '100%', placeholder: '--- Seleccione la Prenda del Catálogo ---' });
    if($('#receta_mp_tmp').length > 0) $('#receta_mp_tmp').select2({ width: '100%', placeholder: 'Buscar Insumo (Tela, Hilo, Cierre, etc)...' });

    $('#receta_prod_master').on('select2:select', function(e) {
        let nombre_prod = $(this).find('option:selected').text();
        idRecetaMasterGlobal = 0; 
        prendaSeleccionadaReceta = { id: $(this).val(), nombre: nombre_prod };
        carritoReceta = [];
        $('#receta_nombre_variante').val(nombre_prod);
        $('#receta_talla').val('');
        $('#receta_genero').val('');
        $('#receta_foto').val('');
        $('#receta_mp_tmp').val('').trigger('change');
        $('#receta_cant_tmp').val('');
        $('#receta_costo_tmp').val('');
        window.actualizarTablaRecetaTemporal();
        $('#panel_constructor_receta').fadeIn();
    });

    $('.nav-link-custom').click(function(e) {
        e.preventDefault();
        $('.nav-link-custom').removeClass('active');
        $(this).addClass('active');
        $('#sidebar, #sidebarOverlay').removeClass('active');
        $('.vista-spa').hide();
        window.scrollTo({ top: 0, behavior: 'instant' });

        if (this.id === 'btnMenuCatMp') { $('#vistaCatMp').fadeIn(); if(typeof window.cargarCatMp === 'function') window.cargarCatMp(); }
        else if (this.id === 'btnMenuSubCatMp') { $('#vistaSubCatMp').fadeIn(); if(typeof window.cargarSubCatMp === 'function') window.cargarSubCatMp(); }
        else if (this.id === 'btnMenuMp') { $('#vistaMp').fadeIn(); if(typeof window.cargarMp === 'function') window.cargarMp(); }
        else if (this.id === 'btnMenuProveedores') { $('#vistaProveedores').fadeIn(); if(typeof window.cargarProveedores === 'function') window.cargarProveedores(); }
        else if (this.id === 'btnMenuCompras') { $('#vistaCompras').fadeIn(); if(typeof window.cargarCompras === 'function') window.cargarCompras(); }
        else if (this.id === 'btnMenuGastos') { $('#vistaGastos').fadeIn(); if(typeof window.cargarGastos === 'function') window.cargarGastos(); }
        else if (this.id === 'btnMenuPatrones') { $('#vistaPatrones').fadeIn(); if(typeof window.cargarPatrones === 'function') window.cargarPatrones(); }
        else if (this.id === 'btnMenuReceta') { $('#vistaReceta').fadeIn(); if(typeof window.cargarRecetasAgrupadas === 'function') window.cargarRecetasAgrupadas(); }
        else if (this.id === 'btnMenuConsumos') { $('#vistaConsumos').fadeIn(); if(typeof window.cargarOrdenesConsumo === 'function') window.cargarOrdenesConsumo(); }
    });

    $('#btnHamburguesa, #sidebarOverlay').click(function() {
        $('#sidebar').toggleClass('active');
        $('#sidebarOverlay').toggleClass('active');
    });

    if (JS_tieneVector(21)) { if(typeof window.cargarCatMp === 'function') window.cargarCatMp(); } 
    else if (JS_tieneVector(27)) { if(typeof window.cargarRecetasAgrupadas === 'function') window.cargarRecetasAgrupadas(); }

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
                filtradas.forEach(s => { $subcat.append(`<option value="${s.id}">${s.nombre}</option>`); });
            } else {
                $subcat.empty().append('<option value="">No hay subcategorías en esta categoría</option>');
            }
        } else {
            $subcat.prop('disabled', true);
        }

        if(txtCat.includes('tela')) { $('#mp_tamano').attr('placeholder', 'Ej: Rollo ancho 1.50m'); $('#mp_und').attr('placeholder', 'Ej: Mts, Kgs'); } 
        else if(txtCat.includes('hilo')) { $('#mp_tamano').attr('placeholder', 'Ej: Cono 5000 yardas'); $('#mp_und').attr('placeholder', 'Ej: Cono, Und'); } 
        else if(txtCat.includes('boton') || txtCat.includes('cierre')) { $('#mp_tamano').attr('placeholder', 'Ej: 15mm, 20cm'); $('#mp_und').attr('placeholder', 'Ej: Docena, Und, Gruesa'); } 
        else { $('#mp_tamano').attr('placeholder', 'Ej: Rollo, Und, Paquete'); $('#mp_und').attr('placeholder', 'Ej: M, Kg, Und, Par'); }
    });

    $('#formCatMp, #formSubCatMp').off('submit').on('submit', function(e) {
        e.preventDefault();
        let form = $(this); let idForm = form.attr('id'); let btn = form.find('button[type="submit"]'); let textoOrig = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.post('api/api_inventario.php', form.serialize(), function(res) {
            if(res.success) {
                Swal.fire({icon:'success', title:'Guardado exitosamente', timer:1200, showConfirmButton:false});
                if(form.find('.btn-cancelar').length > 0) form.find('.btn-cancelar').click(); else form[0].reset();
                if(idForm === 'formCatMp') { if(typeof window.cargarCatMp === 'function') window.cargarCatMp(); setTimeout(() => window.location.reload(), 1000); } 
                if(idForm === 'formSubCatMp') { if(typeof window.cargarSubCatMp === 'function') window.cargarSubCatMp(); setTimeout(() => window.location.reload(), 1000); } 
            } else { Swal.fire('Error', res.msg || 'No se pudo guardar', 'error'); }
        }, 'json').always(()=> btn.prop('disabled', false).text(textoOrig));
    });

    $('.btn-cancelar').click(function() {
        let form = $(this).closest('form'); form[0].reset(); form.find('input[type="hidden"][name="id"]').val('0'); $(this).addClass('d-none');
    });

    $('#formMp').off('submit').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this); let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Insumo registrado', timer: 1200, showConfirmButton: false});
                    $('#modalMp').modal('hide');
                    if(typeof window.cargarMp === 'function') window.cargarMp();
                    if (window.vieneDeCompra) {
                        $.get('api/api_inventario.php', {accion:'listar_mp'}, function(resMp) {
                            let opts = '<option value="">Buscar Insumo...</option>'; let lastId = 0;
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

    $('#formPatron').off('submit').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this); let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({icon: 'success', title: 'Patrón guardado', timer: 1200, showConfirmButton: false});
                    $('#modalPatron').modal('hide');
                    if(typeof window.cargarPatrones === 'function') window.cargarPatrones();
                } else Swal.fire('Error', res.msg, 'error');
            },
            complete: function() { btn.prop('disabled', false).text('Guardar Patrón'); }
        });
    });

    $('#receta_mp_tmp').change(function() {
        let und = $(this).find(':selected').data('und') || 'UND'; $('#lblUndRecetaTmp').text(und); window.calcularCostoRecetaTemporal();
    });
    $('#receta_cant_tmp').on('input', function() { window.calcularCostoRecetaTemporal(); });

    $(document).on('input', '#buscadorRecetas', function() {
        let texto = $(this).val().toLowerCase();
        $('#tbodyRecetasAgrupadas tr').each(function() {
            if ($(this).find('td').length === 1) return; 
            let nombreVariante = $(this).find('td:eq(1)').text().toLowerCase();
            if (nombreVariante.includes(texto)) $(this).show(); else $(this).hide();
        });
    });
});

window.eliminar = function(accion, id, callback) {
    Swal.fire({
        title: '¿Eliminar permanentemente?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then((r) => {
        if(r.isConfirmed) {
            Swal.fire({title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
            $.post('api/api_inventario.php', {accion: accion, id: id}, function(res) {
                if(res.success) { 
                    Swal.fire({icon: 'success', title: 'Eliminado', timer:1000, showConfirmButton:false}); 
                    if(typeof callback === 'function') callback(); 
                    if (accion === 'eliminar_tipo_prov' || accion === 'eliminar_servicio_prov') { if(typeof window.cargarFiltrosProv === 'function') window.cargarFiltrosProv(); }
                    if (accion === 'eliminar_gasto') { if(typeof window.cargarProveedores === 'function') window.cargarProveedores(); }
                } else { Swal.fire('No se pudo eliminar', res.msg || 'Este registro está en uso.', 'error'); }
            }, 'json').fail(function() { Swal.fire('Error crítico', 'Hubo un fallo de comunicación con la base de datos.', 'error'); });
        }
    });
};

window.verFotoGrande = function(url, nombre) {
    Swal.fire({ title: nombre, imageUrl: url, imageWidth: 400, imageAlt: 'Foto / Imagen', showConfirmButton: false, showCloseButton: true, customClass: { image: 'rounded shadow-sm border' } });
};

window.cargarRecetasAgrupadas = function() {
    $('#tbodyRecetasAgrupadas').html('<tr><td colspan="5" class="text-center"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_recetas_agrupadas'}, function(res) {
        let html = '';
        if(res.data && res.data.length > 0) {
            res.data.forEach(r => {
                let fotoUrl = r.foto ? `uploads/recetas/${r.foto}` : '../../img/camisa.jpg'; 
                html += `<tr>
                    <td><img src="${fotoUrl}" style="width:40px; height:40px; object-fit:cover; border-radius:4px; cursor:pointer; border: 1px solid #ccc;" onclick="window.verFotoGrande('${fotoUrl}', 'Ficha: ${r.nombre_receta.replace(/'/g, "\\'")}')" title="Ver imagen" onerror="this.src='../../img/camisa.jpg'"></td>
                    <td><span class="fw-bold d-block" style="color: #0B4C8C;"><i class="bi bi-tag-fill text-warning me-1"></i> ${r.producto_nombre}</span><small class="text-muted"><i class="bi bi-arrow-return-right"></i> ${r.nombre_receta}</small></td>
                    <td class="text-center"><span class="badge bg-secondary">${r.cant_insumos} Insumos</span></td>
                    <td class="fw-bold text-success text-end">${window.formatMoney(r.costo_total)} $</td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-primary fw-bold shadow-sm me-1 rounded-pill" onclick="window.editarRecetaPrenda(${r.id_receta_master}, ${r.id_producto}, '${r.nombre_receta.replace(/'/g, "\\'")}')" title="Editar Receta"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm text-white fw-bold shadow-sm me-1 rounded-pill" style="background-color: #FF7518;" onclick="window.verReceta(${r.id_receta_master}, '${r.producto_nombre.replace(/'/g, "\\'")}', '${r.nombre_receta.replace(/'/g, "\\'")}', ${r.costo_total}, '${fotoUrl}')" title="Ver Detalles"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill shadow-sm" onclick="window.eliminarRecetaCompleta(${r.id_receta_master})" title="Eliminar Variante"><i class="bi bi-trash"></i></button>
                    </td></tr>`;
            });
        } else { html = '<tr><td colspan="5" class="text-center text-muted py-4">Aún no hay variantes configuradas.</td></tr>'; }
        $('#tbodyRecetasAgrupadas').html(html);
    }, 'json');
};

window.editarRecetaPrenda = function(id_receta_master, id_prod, nombre_variante) {
    if ($('#receta_prod_master').find("option[value='" + id_prod + "']").length) { $('#receta_prod_master').val(id_prod).trigger('change.select2'); }
    Swal.fire({title: 'Cargando receta...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
    $.get('api/api_inventario.php', {accion:'listar_detalle_receta', id_receta_master: id_receta_master}, function(res) {
        idRecetaMasterGlobal = id_receta_master;
        prendaSeleccionadaReceta = { id: id_prod, nombre: $('#receta_prod_master option:selected').text() };
        carritoReceta = [];
        $('#receta_nombre_variante').val(nombre_variante);
        $('#receta_talla').val(''); $('#receta_genero').val(''); $('#receta_foto').val('');

        if(res.success && res.data.length > 0) {
            res.data.forEach(item => {
                carritoReceta.push({ id_mp: item.id_materia_prima, nombre: item.mp_nombre, cantidad: parseFloat(item.cantidad_por_unidad), costo_u: parseFloat(item.mp_costo), costo_linea: parseFloat(item.costo_linea) });
            });
            Swal.fire({icon:'info', title:'Modo Edición', text:'La ficha técnica se cargó en el panel izquierdo.', timer: 2000, showConfirmButton: false});
        } else { Swal.close(); }
        
        $('#receta_mp_tmp').val('').trigger('change'); $('#receta_cant_tmp').val(''); $('#receta_costo_tmp').val('');
        window.actualizarTablaRecetaTemporal();
        $('#panel_constructor_receta').fadeIn(); window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 'json');
};

window.calcularCostoRecetaTemporal = function() {
    let costo = parseFloat($('#receta_mp_tmp option:selected').data('costo')) || 0;
    let cant = window.parseNum($('#receta_cant_tmp').val());
    $('#receta_costo_tmp').val(window.formatMoney(costo * cant) + ' $');
};

window.agregarItemAListaReceta = function() {
    let idMp = $('#receta_mp_tmp').val(); let cant = window.parseNum($('#receta_cant_tmp').val());
    if(!idMp || cant <= 0) return Swal.fire('Error','Seleccione insumo y coloque una cantidad válida','warning');
    let nombreMp = $('#receta_mp_tmp option:selected').text();
    let costoU = parseFloat($('#receta_mp_tmp option:selected').data('costo')) || 0;
    
    let existe = carritoReceta.findIndex(x => x.id_mp == idMp);
    if(existe > -1) {
        carritoReceta[existe].cantidad += cant;
        carritoReceta[existe].costo_linea = carritoReceta[existe].cantidad * costoU;
    } else {
        carritoReceta.push({ id_mp: idMp, nombre: nombreMp, cantidad: cant, costo_u: costoU, costo_linea: cant * costoU });
    }
    $('#receta_mp_tmp').val('').trigger('change'); $('#receta_cant_tmp').val(''); $('#receta_costo_tmp').val('');
    window.actualizarTablaRecetaTemporal();
};

window.quitarItemListaReceta = function(idx) { carritoReceta.splice(idx, 1); window.actualizarTablaRecetaTemporal(); };

window.actualizarTablaRecetaTemporal = function() {
    let html = ''; let total = 0;
    if(carritoReceta.length === 0) { html = '<li class="list-group-item text-center text-muted small pb-0 border-0">Añade insumos a la lista.</li>'; } 
    else {
        carritoReceta.forEach((item, idx) => {
            total += item.costo_linea;
            html += `<li class="list-group-item d-flex justify-content-between align-items-center bg-white mb-1 border rounded shadow-sm">
                <div class="small w-100">
                    <span class="fw-bold text-dark d-block text-truncate" style="max-width: 90%;"><i class="bi bi-box-seam text-secondary me-1"></i> ${item.nombre}</span>
                    <span class="text-muted" style="font-size: 0.75rem;">Requiere: <b class="text-dark">${item.cantidad}</b> × ${window.formatMoney(item.costo_u)}$ = <span class="fw-bold text-success">${window.formatMoney(item.costo_linea)}$</span></span>
                </div><button class="btn btn-sm text-danger py-0 px-2" onclick="window.quitarItemListaReceta(${idx})"><i class="bi bi-trash fs-5"></i></button></li>`;
        });
    }
    $('#ul_lista_receta').html(html); $('#lbl_costo_total_receta').text(window.formatMoney(total) + ' $');
};

window.guardarRecetaCompleta = function() {
    if(carritoReceta.length === 0) return Swal.fire('Atención','La lista de insumos está vacía.','warning');
    let nombreBase = $('#receta_nombre_variante').val().trim();
    if(!nombreBase) return Swal.fire('Atención','El nombre base de la variante es obligatorio.','warning');

    let talla = $('#receta_talla').val().trim(); let genero = $('#receta_genero').val();
    let nombreVarianteFinal = nombreBase;
    if (talla !== '') nombreVarianteFinal += ' - Talla ' + talla;
    if (genero !== '') nombreVarianteFinal += ' - ' + genero;

    Swal.fire({
        title: '¿Guardar Ficha / Variante?', text: 'Se guardará como: ' + nombreVarianteFinal, icon: 'question', showCancelButton: true, confirmButtonColor: '#0B4C8C'
    }).then((r) => {
        if(r.isConfirmed) {
            Swal.fire({title:'Estructurando...', allowOutsideClick:false, didOpen:()=>{Swal.showLoading();}});
            let formData = new FormData();
            formData.append('accion', 'guardar_receta_multiple'); formData.append('id_receta_master', idRecetaMasterGlobal);
            formData.append('id_producto', prendaSeleccionadaReceta.id); formData.append('nombre_receta', nombreVarianteFinal);
            formData.append('insumos', JSON.stringify(carritoReceta));
            let fotoInput = document.getElementById('receta_foto');
            if(fotoInput.files.length > 0) formData.append('foto', fotoInput.files[0]);

            $.ajax({
                url: 'api/api_inventario.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('¡Éxito!', 'Ficha técnica construida.', 'success');
                        $('#receta_prod_master').val('').trigger('change'); $('#panel_constructor_receta').hide();
                        window.cargarRecetasAgrupadas();
                    } else { Swal.fire('Error', res.msg, 'error'); }
                }
            });
        }
    });
};

window.verReceta = function(id_receta_master, nombre_producto, nombre_variante, costo_total, fotoUrl) {
    $('#lbl_receta_prenda_nombre').text(nombre_producto); $('#lbl_receta_variante_nombre').text(nombre_variante);
    $('#lbl_receta_costo_total').text(window.formatMoney(costo_total) + ' $');
    $('#img_receta_preview').attr('src', fotoUrl).on('error', function() { $(this).attr('src', '../../img/camisa.jpg'); });
    $('#tbodyDetalleReceta').html('<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>');
    $('#modalVerReceta').modal('show');

    $.get('api/api_inventario.php', {accion:'listar_detalle_receta', id_receta_master: id_receta_master}, function(res) {
        let html = '';
        if(res.data) {
            res.data.forEach(item => {
                html += `<tr>
                    <td><span class="fw-bold text-dark">${item.mp_nombre}</span> <br> <small class="text-muted">Costo Base: ${window.formatMoney(item.mp_costo)}$ x ${item.unidad_medida}</small></td>
                    <td class="text-center"><span class="badge bg-light text-dark border border-secondary">${item.cantidad_por_unidad} ${item.unidad_medida}</span></td>
                    <td class="fw-bold text-end" style="color: #0B4C8C;">${window.formatMoney(item.costo_linea)} $</td>
                    <td class="text-center"><button class="btn btn-sm btn-outline-danger" onclick="window.eliminarItemReceta(${item.id_receta}, ${id_receta_master}, '${nombre_variante.replace(/'/g, "\\'")}')"><i class="bi bi-x-circle"></i></button></td>
                </tr>`;
            });
        }
        $('#tbodyDetalleReceta').html(html);
    }, 'json');
};

window.eliminarItemReceta = function(id_item, id_receta_master, nombre_variante) {
    Swal.fire({title:'¿Quitar insumo?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sí'})
    .then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_item_receta', id: id_item}, function(res) {
                if(res.success) { window.cargarRecetasAgrupadas(); $('#modalVerReceta').modal('hide'); Swal.fire('Quitado', '', 'success'); }
            }, 'json');
        }
    });
};

window.eliminarRecetaCompleta = function(id_receta_master) {
    Swal.fire({
        title: '¿Eliminar Ficha Técnica Completa?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, eliminar todo'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('api/api_inventario.php', {accion: 'eliminar_receta_completa', id_receta_master: id_receta_master}, function(res) {
                if(res.success) { 
                    window.cargarRecetasAgrupadas(); Swal.fire('Eliminada', '', 'success');
                    if(idRecetaMasterGlobal == id_receta_master) { $('#panel_constructor_receta').hide(); carritoReceta = []; $('#receta_prod_master').val('').trigger('change'); }
                }
            }, 'json');
        }
    });
};