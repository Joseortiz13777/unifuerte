let archivosTicketMemoria = [];

$(document).ready(function() {
    cargarTickets();

    // ================= VISTA PREVIA EN VIVO DE FOTOS NUEVAS =================
    $('#t_fotos').on('change', function(e) {
        let files = e.target.files;
        if(files.length > 0) {
            for(let i=0; i<files.length; i++) {
                archivosTicketMemoria.push(files[i]);
            }
            renderizarFotosNuevas();
        }
        $(this).val(''); // Resetear el input para permitir elegir la misma foto si se borra
    });
});

function renderizarFotosNuevas() {
    let html = '';
    archivosTicketMemoria.forEach((file, index) => {
        let objUrl = URL.createObjectURL(file);
        html += `
        <div class="position-relative d-inline-block text-center mb-2">
            <img src="${objUrl}" class="foto-thumb" style="margin:0;">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="padding: 2px 6px; font-size: 0.6rem;" onclick="eliminarFotoNueva(${index})" title="Quitar"><i class="bi bi-x-lg"></i></button>
            <div style="font-size: 0.65rem; color: #0B4C8C; font-weight: bold; margin-top: 2px; max-width: 70px;" class="text-truncate" title="${file.name}">NUEVA</div>
        </div>`;
    });
    $('#fotos_nuevas_preview').html(html);
}

function eliminarFotoNueva(index) {
    archivosTicketMemoria.splice(index, 1);
    renderizarFotosNuevas();
}

function cargarTickets() {
    $('#contenedorTickets').html('<div class="col-12 text-center py-5 text-muted fw-bold"><div class="spinner-border text-primary" role="status"></div><br>Cargando banco de mejoras...</div>');
    
    let esMaster = $('#puede_marcar_corregido').val() === '1';

    $.get('api_ayuda.php', { accion: 'listar' }, function(res) {
        if(res.success) {
            let html = '';
            
            res.data.forEach(t => {
                let badgeEstado = t.estado === 'Corregido' ? '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Solucionado</span>' : '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> Pendiente</span>';
                let classCard = t.estado === 'Corregido' ? 'estado-corregido' : 'estado-pendiente';
                
                // Mosaico de fotos
                let fotosHtml = '';
                if(t.fotos && t.fotos.trim() !== '') {
                    let arrF = t.fotos.split(',');
                    fotosHtml += '<div class="d-flex flex-wrap mt-3 pt-3 border-top">';
                    arrF.forEach(f => {
                        if(f.trim() !== '') {
                            // RUTA CORREGIDA PARA QUE SE VEAN LAS FOTOS
                            fotosHtml += `<a href="../images/ayuda/${f}" target="_blank">
                                            <img src="../images/ayuda/${f}" class="foto-thumb" title="Ver foto">
                                          </a>`;
                        }
                    });
                    fotosHtml += '</div>';
                }

                // Botones de acción (Privacidad)
                let btnAcciones = '';
                
                // 1. Marcar Corregido (SOLO MASTER)
                if (esMaster) {
                    if (t.estado === 'Pendiente') {
                        btnAcciones += `<button class="btn btn-sm btn-success fw-bold mb-2 w-100" onclick="cambiarEstado(${t.id}, 'Corregido')"><i class="bi bi-check-circle"></i> Marcar como Solucionado</button>`;
                    } else {
                        btnAcciones += `<button class="btn btn-sm btn-warning text-dark fw-bold mb-2 w-100" onclick="cambiarEstado(${t.id}, 'Pendiente')"><i class="bi bi-arrow-counterclockwise"></i> Volver a Pendiente</button>`;
                    }
                }

                // 2. Editar y Eliminar (Solo si es dueño o es master)
                if (t.puede_editar) {
                    btnAcciones += `<button class="btn btn-sm btn-outline-primary fw-bold w-100 mb-2" onclick="editarTicket(${t.id})"><i class="bi bi-pencil"></i> Editar mi reporte</button>`;
                }
                if (t.puede_eliminar) {
                    btnAcciones += `<button class="btn btn-sm btn-outline-danger fw-bold w-100" onclick="eliminarTicket(${t.id})"><i class="bi bi-trash"></i> Eliminar</button>`;
                }
                
                if(btnAcciones === '') {
                    btnAcciones = '<small class="text-muted fst-italic text-center d-block"><i class="bi bi-lock-fill"></i> Reportado por otro usuario. Solo lectura.</small>';
                }

                html += `
                <div class="col-md-6 col-lg-4">
                    <div class="ticket-card ${classCard} p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold m-0" style="color:#0B4C8C;">${t.titulo}</h5>
                            ${badgeEstado}
                        </div>
                        <div class="text-muted small mb-3"><i class="bi bi-person-circle"></i> Creado por: <b class="text-dark">${t.autor_full}</b> <br> <i class="bi bi-clock"></i> ${t.fecha_registro}</div>
                        <div class="caja-descripcion">${t.descripcion.replace(/\n/g, '<br>')}</div>
                        
                        ${fotosHtml}
                        
                        <div class="mt-auto pt-3 border-top">
                            ${btnAcciones}
                        </div>
                    </div>
                </div>`;
            });

            if(res.data.length === 0) {
                html = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-shield-check" style="font-size: 4rem; color: #ccc;"></i><br><h4 class="fw-bold mt-3">El banco de mejoras está limpio.</h4><p>¡Buen trabajo de sistemas!</p></div>';
            }
            $('#contenedorTickets').html(html);
        }
    }, 'json');
}

function abrirModalTicket() {
    $('#formTicket')[0].reset();
    $('#t_id_ticket').val('0');
    $('#tituloModalTicket').html('<i class="bi bi-bug"></i> Reportar Nuevo Caso');
    $('#fotos_existentes_container').hide();
    
    archivosTicketMemoria = [];
    renderizarFotosNuevas();
    
    $('#modalTicket').modal('show');
}

function editarTicket(id) {
    $('#formTicket')[0].reset();
    $('#tituloModalTicket').html('<i class="bi bi-pencil"></i> Editar mi Reporte');
    $('#fotos_existentes_container').hide();
    
    archivosTicketMemoria = [];
    renderizarFotosNuevas();
    
    $.get('api_ayuda.php', { accion: 'obtener', id: id }, function(res) {
        if(res.success) {
            let t = res.data;
            $('#t_id_ticket').val(t.id);
            $('#t_titulo').val(t.titulo);
            $('#t_descripcion').val(t.descripcion);
            
            // Mostrar fotos guardadas para eliminarlas
            if(t.fotos && t.fotos.trim() !== '') {
                let htmlF = '';
                t.fotos.split(',').forEach(f => {
                    if(f.trim() !== '') {
                        // RUTA CORREGIDA
                        htmlF += `
                        <div class="position-relative d-inline-block text-center mb-2">
                            <a href="../images/ayuda/${f}" target="_blank">
                                <img src="../images/ayuda/${f}" class="foto-thumb" style="margin:0;">
                            </a>
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="padding: 2px 6px; font-size: 0.6rem;" onclick="eliminarFotoTicket(${t.id}, '${f}', this)" title="Eliminar Foto"><i class="bi bi-x-lg"></i></button>
                            <div style="font-size: 0.65rem; color: #6c757d; margin-top: 2px;">Guardada</div>
                        </div>`;
                    }
                });
                $('#t_fotos_preview').html(htmlF);
                $('#fotos_existentes_container').show();
            }
            
            $('#modalTicket').modal('show');
        } else {
            Swal.fire('Atención', res.msg, 'error');
        }
    }, 'json');
}

$('#formTicket').submit(function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    
    // Adjuntamos las fotos que el usuario tiene en la memoria temporal (Vista previa)
    if (archivosTicketMemoria && archivosTicketMemoria.length > 0) {
        for (let i = 0; i < archivosTicketMemoria.length; i++) {
            fd.append('fotos[]', archivosTicketMemoria[i]);
        }
    }

    $('#btnGuardarTicket').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Subiendo...');
    
    $.ajax({
        url: 'api_ayuda.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                $('#modalTicket').modal('hide');
                Swal.fire({icon: 'success', title: '¡Reporte Guardado!', timer: 1500, showConfirmButton: false});
                cargarTickets();
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        },
        complete: function() { $('#btnGuardarTicket').prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Guardar Reporte'); }
    });
});

function eliminarFotoTicket(id_ticket, foto_nombre, btn) {
    Swal.fire({
        title: '¿Eliminar Evidencia?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, borrar'
    }).then((result) => {
        if (result.isConfirmed) {
            $(btn).prop('disabled', true);$.post('api_ayuda.php', { accion: 'eliminar_foto', id_ticket: id_ticket, foto: foto_nombre }, function(res) {
                if (res.success) {
                    $(btn).parent().remove();
                    if ($('#t_fotos_preview').children().length === 0) {
                        $('#fotos_existentes_container').hide();
                    }
                    cargarTickets(); // refrescar fondo
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}

function cambiarEstado(id, nuevo_estado) {
    let titulo = nuevo_estado === 'Corregido' ? '¿Marcar como Solucionado?' : '¿Devolver a Pendiente?';
    let color = nuevo_estado === 'Corregido' ? '#198754' : '#ffc107';
    
    Swal.fire({
        title: titulo,
        text: 'Esta acción notificará visualmente al equipo que el problema fue atendido.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: color,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aplicar'
    }).then((result) => {
        if(result.isConfirmed) {
            $.post('api_ayuda.php', { accion: 'marcar_corregido', id: id, estado: nuevo_estado }, function(res) {
                if(res.success) {
                    cargarTickets();
                    Swal.fire({icon: 'success', title: 'Estado Actualizado', timer: 1000, showConfirmButton: false});
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}

function eliminarTicket(id) {
    Swal.fire({
        title: '¿Borrar Reporte?',
        text: "Las fotos adjuntas también se eliminarán del servidor.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if(result.isConfirmed) {
            $.post('api_ayuda.php', { accion: 'eliminar_ticket', id: id }, function(res) {
                if(res.success) {
                    cargarTickets();
                    Swal.fire({icon: 'success', title: 'Borrado', timer: 1000, showConfirmButton: false});
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}