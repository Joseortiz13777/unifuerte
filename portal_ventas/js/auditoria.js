$(document).ready(function() {
    // Inicialización
});

function cargarAuditoria() {
    $('#tablaAuditoriaBody').html('<tr><td colspan="5" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    
    let dDesde = $('#audit_desde').val();
    let dHasta = $('#audit_hasta').val();
    let dDepto = $('#audit_departamento').val();
    let dUser = $('#audit_usuario_especifico').val();

    $.get('api/api_auditoria.php', { accion: 'listar', desde: dDesde, hasta: dHasta, departamento: dDepto, usuario: dUser }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(a => {
                let colorAccion = 'text-primary';
                
                // Nuevos estilos adaptados para la Seguridad / Rastreo DLP
                if(a.accion === 'CREAR' || a.accion === 'REGISTRAR') colorAccion = 'text-success';
                else if(a.accion === 'EDITAR' || a.accion === 'ESTADO_PAGO' || a.accion === 'INGRESÓ') colorAccion = 'text-warning text-dark';
                else if(a.accion === 'ELIMINAR') colorAccion = 'text-danger';
                else if(a.accion === 'ALERTA DE ROBO') colorAccion = 'badge bg-danger text-white pulse-wa';
                else if(a.accion === 'NAVEGACIÓN') colorAccion = 'badge bg-info text-dark';
                else if(a.accion === 'SISTEMA') colorAccion = 'badge bg-success text-white';

                let modulo = a.tabla_afectada;
                if(modulo === 'licitaciones' || modulo === 'licitacion_documentos' || modulo === 'licitacion_empresas') modulo = 'Licitaciones (Portafolios)';
                else if(modulo === 'cotizacion' || modulo === 'detalle_cotizacion') modulo = 'Ventas / Cotización';
                else if(modulo === 'orden_fabrica' || modulo === 'detalle_orden_fabrica') modulo = 'Órdenes de Producción';
                else if(modulo === 'cliente') modulo = 'Directorio de Clientes';
                else if(modulo === 'usuario') modulo = 'Gestión de Usuarios';
                else if(modulo === 'productos' || modulo === 'categorias' || modulo === 'subcategorias') modulo = 'Catálogo Digital';
                else modulo = modulo ? modulo.toUpperCase() : 'SISTEMA';

                let nombreMostrado = (a.nombre || 'Usuario') + ' ' + (a.apellido || '');
                let enlaceUsuario = a.id_usuario_actor 
                    ? `<a href="#" onclick="verFichaEmpleadoAudit(${a.id_usuario_actor}); return false;" class="text-decoration-none fw-bold" style="color: #0B4C8C;" title="Clic para investigar usuario"><i class="bi bi-person-bounding-box text-danger me-1"></i> ${nombreMostrado}</a>`
                    : `<div class="fw-bold text-muted"><i class="bi bi-person-fill me-1"></i> ${nombreMostrado}</div>`;

                html += `<tr>
                    <td class="text-muted small">${a.fecha_hora}</td>
                    <td>
                        ${enlaceUsuario}
                        <br><small class="text-muted" style="font-size: 0.7rem; text-transform: uppercase;">Rol: ${a.rol_actor || 'Desconocido'}</small>
                    </td>
                    <td><span class="fw-bold ${colorAccion}">${a.accion}</span></td>
                    <td><span class="badge bg-secondary" style="font-size: 0.75rem;">${modulo}</span></td>
                    <td class="small fw-bold text-dark">${a.detalle_cambio}</td>
                </tr>`;
            });
            $('#tablaAuditoriaBody').html(html || '<tr><td colspan="5" class="text-center text-muted">No se encontraron movimientos para el filtro seleccionado.</td></tr>');
        } else {
            $('#tablaAuditoriaBody').html(`<tr><td colspan="5" class="text-center text-danger">${res.msg}</td></tr>`);
        }
    }, 'json').fail(function() {
        $('#tablaAuditoriaBody').html('<tr><td colspan="5" class="text-center text-danger">Error de conexión al cargar la auditoría.</td></tr>');
    });
}

function verFichaEmpleadoAudit(id_usuario) {
    Swal.fire({ title: 'Rastreando datos...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    
    $.get('api/api_auditoria.php', { accion: 'obtener_usuario', id_usuario: id_usuario }, function(res) {
        
        if(res.success && res.data) {
            let u = res.data;
            
            if ($('#modalVerUsuarioAudit').length === 0) {
                $('body').append(`
                <div class="modal fade" id="modalVerUsuarioAudit" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                            <div class="modal-header border-0" style="background-color: #0B4C8C;">
                                <h5 class="modal-title fw-bold text-white"><i class="bi bi-person-badge me-2"></i> Ficha de Empleado</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body bg-light text-center p-4">
                                <div class="mb-3">
                                    <div class="text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 70px; height: 70px; font-size: 28px; background-color: #FF7518;" id="audit_usr_iniciales"></div>
                                </div>
                                <h5 class="fw-bold text-dark mb-0" id="audit_usr_nombrecompleto"></h5>
                                <p class="text-muted small fw-bold mb-4" style="text-transform: uppercase; letter-spacing: 1px;" id="audit_usr_rol"></p>
                                
                                <ul class="list-group list-group-flush text-start shadow-sm border rounded" style="font-size: 0.85rem;">
                                    <li class="list-group-item bg-white border-bottom"><i class="bi bi-person-vcard text-primary me-2"></i> <strong>Cédula:</strong> <span id="audit_usr_cedula" class="float-end text-muted"></span></li>
                                    <li class="list-group-item bg-white border-bottom"><i class="bi bi-telephone-fill text-success me-2"></i> <strong>Teléfono:</strong> <span id="audit_usr_tlf" class="float-end text-muted"></span></li>
                                    <li class="list-group-item bg-white border-bottom"><i class="bi bi-envelope-at-fill text-warning me-2"></i> <strong>Email:</strong> <span id="audit_usr_email" class="float-end text-muted text-truncate" style="max-width: 120px;"></span></li>
                                    <li class="list-group-item bg-white border-bottom"><i class="bi bi-briefcase-fill text-secondary me-2"></i> <strong>Cargo:</strong> <span id="audit_usr_profesion" class="float-end text-muted"></span></li>
                                    <li class="list-group-item bg-white"><i class="bi bi-shield-lock-fill text-danger me-2"></i> <strong>User:</strong> <span id="audit_usr_login" class="float-end fw-bold" style="color: #0B4C8C;"></span></li>
                                </ul>
                            </div>
                            <div class="modal-footer bg-white border-top-0 justify-content-center pb-4">
                                <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-5" data-bs-dismiss="modal">Cerrar Ficha</button>
                            </div>
                        </div>
                    </div>
                </div>
                `);
            }

            let nombreLimpio = u.nombre ? u.nombre : '';
            let apellidoLimpio = u.apellido ? u.apellido : '';
            
            let iniNombre = nombreLimpio.length > 0 ? nombreLimpio.charAt(0) : '';
            let iniApellido = apellidoLimpio.length > 0 ? apellidoLimpio.charAt(0) : '';
            let iniciales = (iniNombre + iniApellido).toUpperCase();
            
            if(iniciales === '') iniciales = 'U';
            
            $('#audit_usr_iniciales').text(iniciales);
            $('#audit_usr_nombrecompleto').text(nombreLimpio + ' ' + apellidoLimpio);
            $('#audit_usr_rol').text((u.rol || 'SIN ROL').replace('_', ' '));
            
            $('#audit_usr_cedula').text(u.cedula || 'No Registrado');
            $('#audit_usr_tlf').text(u.tlf || 'No Registrado');
            $('#audit_usr_email').text(u.email || 'No Registrado');
            $('#audit_usr_email').attr('title', u.email || ''); 
            $('#audit_usr_profesion').text(u.profesion || 'No Registrado');
            $('#audit_usr_login').text(u.nombre_usuario || 'Desconocido');

            Swal.close(); 
            setTimeout(() => {
                let myModal = new bootstrap.Modal(document.getElementById('modalVerUsuarioAudit'));
                myModal.show();
            }, 200);

        } else {
            Swal.close();
            setTimeout(() => { Swal.fire('Error', res.msg || 'No se pudo localizar al usuario.', 'error'); }, 200);
        }
    }, 'json').fail(function() {
        Swal.close();
        setTimeout(() => { Swal.fire('Error', 'Fallo de conexión al buscar los datos.', 'error'); }, 200);
    });
}