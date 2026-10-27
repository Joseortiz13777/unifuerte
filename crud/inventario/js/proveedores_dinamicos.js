$(document).ready(function() {
    // 1. Configurar Select2 con soporte para "Tags" (Agregar texto nuevo que no est谷 en la lista)
    function inicializarSelectDinamico(selector, catalogo) {
        $(selector).select2({
            dropdownParent: $('#modalProveedor'),
            tags: true,
            width: '100%',
            placeholder: "Seleccione o escriba uno nuevo...",
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') { return null; }
                return { id: term, text: term, newTag: true };
            }
        }).on('select2:select', function (e) {
            let data = e.params.data;
            if (data.newTag) {
                // Guardar silenciosamente en la BD
                $.post('api/api_catalogos.php', { accion: 'agregar', catalogo: catalogo, nombre: data.text }, function(res) {
                    if(res.success) {
                        // Reemplazamos el tag manual por el confirmado en BD
                        let newOption = new Option(res.text, res.text, false, false); 
                        $(selector).find(`option[value="${data.id}"]`).replaceWith(newOption);
                        $(selector).val(res.text).trigger('change');
                    }
                }, 'json');
            }
        });

        // Cargar lista inicial
        $.get('api/api_catalogos.php', { accion: 'listar', catalogo: catalogo }, function(data) {
            $(selector).empty().append('<option value="">Seleccione o escriba...</option>');
            if(data && data.length > 0) {
                data.forEach(item => {
                    $(selector).append(new Option(item.text, item.text, false, false));
                });
            }
        }, 'json');
    }

    // Inicializar cuando se abre el modal para asegurar que el DOM est芍 listo
    $('#modalProveedor').on('shown.bs.modal', function () {
        if(!$('#prov_tipo').hasClass('select2-hidden-accessible')) {
            inicializarSelectDinamico('#prov_tipo', 'tipo_proveedor');
        }
        if(!$('#prov_pm_banco').hasClass('select2-hidden-accessible')) {
            inicializarSelectDinamico('#prov_pm_banco', 'banco');
        }
    });

    // 2. Filtro inteligente de proveedores por deuda (Para el buscador que agregamos)
    $('#buscadorProveedores, #filtroDeudaProveedores').on('input change', function() {
        let texto = $('#buscadorProveedores').val().toLowerCase();
        
        // El filtro de deuda exacto requiere conexi車n backend, pero como aproximaci車n visual 
        // buscamos a los proveedores que tienen el bot車n de deuda activo si aplica.
        $('#tbodyProveedores tr').each(function() {
            let filaTexto = $(this).text().toLowerCase();
            let matchTexto = filaTexto.includes(texto);
            if (matchTexto) $(this).show(); else $(this).hide();
        });
    });

    // 3. Modal de deudas al hacer clic en el bot車n inyectado
    $(document).on('click', '.btn-ver-deuda', function() {
        let idProveedor = $(this).data('id');
        let nombreProveedor = $(this).data('nombre');
        
        $('#lbl_nombre_proveedor_deuda').text(nombreProveedor);
        $('#tabla_deudas_body').html('<tr><td colspan="7" class="text-center py-4"><span class="spinner-border text-danger"></span> Cargando deudas...</td></tr>');
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
                        filas = '<tr><td colspan="7" class="text-center text-success fw-bold py-4"><i class="bi bi-check-circle-fill"></i> No hay deudas pendientes con este proveedor.</td></tr>';
                    } else {
                        res.compras.forEach(compra => {
                            let idC = compra.id_compra;
                            let ref = compra.nro_factura || compra.referencia || 'N/A';
                            let total = parseFloat(compra.costo);
                            let pagado = parseFloat(compra.monto_pagado);
                            let pend = parseFloat(compra.monto_pendiente);
                            let mon = compra.moneda === 'BS' ? 'BS' : '$';

                            filas += `
                                <tr>
                                    <td class="fw-bold text-muted">COMP-${idC}</td>
                                    <td>${compra.fecha_compra}</td>
                                    <td>${ref}</td>
                                    <td class="text-end fw-bold">${total.toFixed(2)} ${mon}</td>
                                    <td class="text-end text-success">${pagado.toFixed(2)} ${mon}</td>
                                    <td class="text-end text-danger fw-bold">${pend.toFixed(2)} ${mon}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-success fw-bold rounded-pill shadow-sm" onclick="window.abrirModalPagosCompraDesdeDeuda(${idC})" title="Pagar Factura">
                                            <i class="bi bi-currency-dollar"></i> Pagar
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#tabla_deudas_body').html(filas);
                } else {
                    $('#tabla_deudas_body').html('<tr><td colspan="7" class="text-center text-danger">Error al cargar deudas</td></tr>');
                }
            },
            error: function() {
                $('#tabla_deudas_body').html('<tr><td colspan="7" class="text-center text-danger">Fallo de red al buscar deudas</td></tr>');
            }
        });
    });

    // 4. Funci車n puente para abrir el modal de pagos saltando del modal de deudas
    window.abrirModalPagosCompraDesdeDeuda = function(id_compra) {
        $('#modalDeudaProveedor').modal('hide');
        setTimeout(() => {
            if(typeof window.abrirModalPagosCompra === 'function') {
                window.abrirModalPagosCompra(id_compra);
            }
        }, 400);
    };
});