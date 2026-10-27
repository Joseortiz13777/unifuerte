// =========================================================================
// MÓDULO: NOTAS DE ENTREGA MANUALES (PERSONALIZADAS)
// =========================================================================

function cargarNotasManuales() {
    $('#tbodyNotasManuales').html('<tr><td colspan="6" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.post('api/api_envios.php', { accion: 'listar_notas_manuales' }, function(res) {
        let html = '';
        if (res.success && res.data.length > 0) {
            res.data.forEach(n => {
                html += `
                <tr>
                    <td class="fw-bold text-primary">${n.nro_nota}</td>
                    <td>${n.fecha_format}</td>
                    <td class="fw-bold text-dark">${n.cliente_nombre}</td>
                    <td><small class="text-muted"><i class="bi bi-person"></i> ${n.comprador || '-'}<br><i class="bi bi-card-text"></i> ${n.cedula_rif}</small></td>
                    <td class="text-center"><span class="badge bg-secondary">${n.total_prendas || 0} Unds.</span></td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-outline-dark fw-bold rounded-pill" onclick="imprimirNotaManual(${n.id})" title="Imprimir / Ver PDF"><i class="bi bi-printer"></i> Imprimir</button>
                        <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill ms-1" onclick="eliminarNotaManual(${n.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
        } else {
            html = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-2 d-block mb-2"></i> No hay notas manuales registradas.</td></tr>';
        }
        $('#tbodyNotasManuales').html(html);
    }, 'json');
}

window.abrirModalNotaManual = function() {
    $('#formNotaManual')[0].reset();
    document.getElementById('nota_manual_fecha').valueAsDate = new Date();
    $('#tbodyItemsNotaManual').empty();
    agregarFilaNotaManual(); // Añade la primera fila vacía por defecto
    $('#modalNotaManual').modal('show');
};

window.agregarFilaNotaManual = function() {
    let html = `
    <tr>
        <td><input type="text" name="prenda[]" class="form-control form-control-sm border-primary" placeholder="Ej: Camisa Ejecutiva Blanca" required></td>
        <td><input type="text" name="talla[]" class="form-control form-control-sm" placeholder="Ej: Talla L (Dama)" required></td>
        <td><input type="number" name="cantidad[]" class="form-control form-control-sm text-center fw-bold border-primary" value="1" min="1" required></td>
        <td class="text-center"><button type="button" class="btn btn-sm text-danger py-0 px-1" onclick="$(this).closest('tr').remove()"><i class="bi bi-x-circle-fill fs-5"></i></button></td>
    </tr>`;
    $('#tbodyItemsNotaManual').append(html);
};

$('#formNotaManual').submit(function(e) {
    e.preventDefault();
    
    if ($('#tbodyItemsNotaManual tr').length === 0) {
        Swal.fire('Atención', 'Debe agregar al menos un artículo a la nota.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Generar Nota de Entrega',
        text: '¿Confirmas que los datos son correctos?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#FF7518',
        confirmButtonText: 'Sí, Generar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            $.post('api/api_envios.php', $(this).serialize(), function(res) {
                if (res.success) {
                    $('#modalNotaManual').modal('hide');
                    cargarNotasManuales();
                    Swal.fire({icon: 'success', title: 'Nota Generada', text: 'Abriendo documento...', timer: 1500, showConfirmButton: false}).then(() => {
                        imprimirNotaManual(res.id_nota);
                    });
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
});

window.imprimirNotaManual = function(id) {
    window.open('nota_entrega_manual.php?id=' + id, '_blank');
};

window.eliminarNotaManual = function(id) {
    Swal.fire({
        title: '¿Eliminar Nota Manual?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, Eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/api_envios.php', { accion: 'eliminar_nota_manual', id: id }, function(res) {
                if (res.success) {
                    Swal.fire({icon: 'success', title: 'Eliminada', timer: 1000, showConfirmButton: false});
                    cargarNotasManuales();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
};