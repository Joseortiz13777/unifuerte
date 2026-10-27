(function ($) {
    'use strict';

    function escaparHtml(valor) {
        return $('<div>').text(valor == null ? '' : String(valor)).html();
    }

    function mostrarFecha(valor) {
        if (!valor) return '—';
        const fecha = new Date(String(valor).replace(' ', 'T'));
        return Number.isNaN(fecha.getTime()) ? escaparHtml(valor) : fecha.toLocaleString();
    }

    window.cargarCotizacionesEliminadas = function () {
        const $tbody = $('#tablaCotizacionesEliminadas');
        if (!$tbody.length) return;
        $tbody.html('<tr><td colspan="7" class="text-center text-muted">Cargando archivador...</td></tr>');

        $.get('api/api_cotizaciones_eliminadas.php', { accion: 'listar' })
            .done(function (res) {
                if (!res || !res.success || !Array.isArray(res.data)) {
                    $tbody.html('<tr><td colspan="7" class="text-center text-danger">No se pudo cargar el archivador.</td></tr>');
                    Swal.fire('Error', (res && res.msg) || 'Respuesta no válida del servidor.', 'error');
                    return;
                }
                if (res.data.length === 0) {
                    $tbody.html('<tr><td colspan="7" class="text-center text-muted">Todavía no hay cotizaciones archivadas.</td></tr>');
                    return;
                }

                const filas = res.data.map(function (fila) {
                    const restaurada = Boolean(fila.fecha_restauracion);
                    const usuarioElimino = [fila.nombre_elimino, fila.apellido_elimino].filter(Boolean).join(' ') || 'Usuario #' + fila.id_usuario_elimino;
                    const usuarioRestauro = [fila.nombre_restauro, fila.apellido_restauro].filter(Boolean).join(' ');
                    const estado = restaurada
                        ? '<span class="badge bg-success">Restaurada' + (usuarioRestauro ? ' por ' + escaparHtml(usuarioRestauro) : '') + '</span><div class="small text-muted">' + mostrarFecha(fila.fecha_restauracion) + '</div>'
                        : '<span class="badge bg-secondary">Archivada</span>';
                    const accion = restaurada
                        ? '<button class="btn btn-sm btn-outline-secondary" disabled>Restaurada</button>'
                        : '<button type="button" class="btn btn-sm btn-success fw-bold js-restaurar-cotizacion" data-id="' + Number(fila.id) + '" data-nro="' + escaparHtml(fila.nro_cotizacion) + '"><i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar</button>';

                    return '<tr>' +
                        '<td class="fw-bold">' + escaparHtml(fila.nro_cotizacion) + '<div class="small text-muted">ID original: ' + Number(fila.cotizacion_id_original) + '</div></td>' +
                        '<td>' + escaparHtml(fila.cliente_nombre || 'Sin cliente') + '</td>' +
                        '<td>' + mostrarFecha(fila.fecha_eliminacion) + '</td>' +
                        '<td>' + escaparHtml(usuarioElimino) + '</td>' +
                        '<td>' + Number(fila.cantidad_detalles || 0) + ' detalles / ' + Number(fila.cantidad_ordenes || 0) + ' órdenes</td>' +
                        '<td>' + estado + '</td>' +
                        '<td class="text-end">' + accion + '</td>' +
                        '</tr>';
                });
                $tbody.html(filas.join(''));
            })
            .fail(function (xhr) {
                $tbody.html('<tr><td colspan="7" class="text-center text-danger">No se pudo cargar el archivador.</td></tr>');
                const mensaje = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error de comunicación con el servidor.';
                Swal.fire('Error', mensaje, 'error');
            });
    };

    $(document).on('click', '#btnRecargarCotizacionesEliminadas', function () {
        window.cargarCotizacionesEliminadas();
    });

    $(document).on('click', '.js-restaurar-cotizacion', function () {
        const idArchivo = Number($(this).data('id'));
        const nroCotizacion = String($(this).data('nro') || '');
        Swal.fire({
            title: '¿Restaurar cotización?',
            text: 'Se restaurará ' + nroCotizacion + ' con sus detalles. No se reemplazará ninguna cotización existente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (!resultado.isConfirmed) return;
            $.post('api/api_cotizaciones_eliminadas.php', { accion: 'restaurar', id_archivo: idArchivo })
                .done(function (res) {
                    if (!res || !res.success) {
                        Swal.fire('No se restauró', (res && res.msg) || 'Respuesta no válida del servidor.', 'error');
                        return;
                    }
                    const ordenes = Number(res.ordenes_vinculadas || 0);
                    Swal.fire('Restaurada', res.msg + ' Órdenes vinculadas: ' + ordenes + '.', 'success');
                    window.cargarCotizacionesEliminadas();
                })
                .fail(function (xhr) {
                    const mensaje = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'Error de comunicación con el servidor.';
                    Swal.fire('Error', mensaje, 'error');
                });
        });
    });
})(jQuery);
