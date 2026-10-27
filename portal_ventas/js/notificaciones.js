$(document).ready(function() {
    // Inicializar notificaciones de Auditoría/Alertas
    if ($('#btnNotificaciones, [data-bs-target="#modalNotificacionesAuditoria"]').length > 0) {
        cargarNotificacionesAuditoria();
        setInterval(cargarNotificacionesAuditoria, 30000); 
    }
});

let ultimaActividad = '';

function cargarNotificacionesAuditoria() {
    let deptoSeleccionado = $('#filtro_departamento_notif').length ? $('#filtro_departamento_notif').val() : 'todos';

    $.get('api/api_notificaciones.php', { departamento: deptoSeleccionado }, function(res) {
        if(res.success) {
            let html = '';
            let huboCambios = false;

            if(res.data.length > 0) {
                let registroMasReciente = res.data[0].fecha_format + res.data[0].detalle_cambio;
                if(ultimaActividad !== '' && ultimaActividad !== registroMasReciente) {
                    huboCambios = true;
                }
                ultimaActividad = registroMasReciente;

                res.data.forEach(n => {
                    let color = 'text-primary';
                    let icono = 'bi-info-circle-fill';
                    
                    if(n.accion === 'CREAR' || n.accion === 'REGISTRAR') { color = 'text-success'; icono = 'bi-plus-circle-fill'; }
                    else if(n.accion === 'EDITAR' || n.accion === 'ESTADO_PAGO') { color = 'text-warning'; icono = 'bi-pencil-fill'; }
                    else if(n.accion === 'ELIMINAR') { color = 'text-danger'; icono = 'bi-trash-fill'; }

                    html += `
                    <li>
                        <a class="dropdown-item py-2 border-bottom" href="#" style="white-space: normal;">
                            <div class="d-flex align-items-start">
                                <i class="bi ${icono} ${color} fs-5 me-2 mt-1"></i>
                                <div>
                                    <strong class="d-block text-dark small" style="line-height: 1.1;">${n.nombre} ${n.apellido} <span class="text-muted fw-normal">(${n.rol})</span></strong>
                                    <span class="small text-muted d-block mt-1">${n.detalle_cambio}</span>
                                    <span class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock"></i> ${n.fecha_format}</span>
                                </div>
                            </div>
                        </a>
                    </li>`;
                });
            } else {
                html = '<li><a class="dropdown-item text-muted text-center py-3">No hay actividad reciente para este filtro.</a></li>';
            }

            $('#contenedorNotificaciones').html(html);

            if(huboCambios) {
                $('#puntoRojoNotif').show();
                $('#puntoRojoNotifMenu').show();
            }
        }
    }, 'json');
}

// Ocultar el punto rojo cuando se abre el modal/dropdown
$('#btnNotificaciones, [data-bs-target="#modalNotificacionesAuditoria"]').on('click', function() {
    $('#puntoRojoNotif').hide();
    $('#puntoRojoNotifMenu').hide();
});