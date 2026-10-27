$(document).ready(function() {
    $('#btnNuevoUsuario').click(function() {
        $('#formUsuario')[0].reset();
        $('#id_usuario_modal').val('0');
        $('.check-permiso').prop('checked', false); // Limpiar vectores
        $('#u_tiene_meta').prop('checked', false);
        $('#modalUsuarioLabel').html('<i class="bi bi-person-plus-fill"></i> Registrar Usuario');
        new bootstrap.Modal(document.getElementById('modalUsuario')).show();
    });

    $('#formUsuario').submit(function(e) {
        e.preventDefault();
        let formData = $(this).serialize() + '&accion=guardar';
        $('#btnGuardarUser').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.post('api/api_usuarios.php', formData, function(res) {
            if(res.success) {
                Swal.fire('¡Éxito!', 'Usuario guardado correctamente.', 'success');
                $('#modalUsuario').modal('hide');
                cargarUsuarios();
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        }, 'json').always(() => $('#btnGuardarUser').prop('disabled', false).text('Guardar Datos'));
    });
});

function cargarUsuarios() {
    $('#tablaUsuariosBody').html('<tr><td colspan="6" class="text-center py-4"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_usuarios.php', { accion: 'listar' }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(u => {
                let badgeColor = u.rol === 'master' ? 'bg-dark' : (u.rol === 'admin' ? 'bg-primary' : 'bg-info text-dark');
                let metaBadge = u.tiene_meta == 1 ? '<span class="badge bg-success mt-1" style="font-size:0.6rem;">Tiene Metas</span>' : '';
                
                // Mostrar Vectores y revisar si está bloqueado
                let isBlocked = false;
                let vectoresHtml = '<span class="text-muted small">Ninguno</span>';
                
                if (u.permisos) {
                    let permArr = u.permisos.split(',');
                    isBlocked = permArr.includes('BLOCKED');
                    
                    vectoresHtml = permArr.map(v => {
                        if (v === 'BLOCKED') return `<span class="badge bg-danger ms-1">BLOQUEADO</span>`;
                        return `<span class="badge bg-secondary me-1">[${v}]</span>`;
                    }).join('');
                }

                html += `<tr ${isBlocked ? 'style="background-color: #ffe5e5; opacity: 0.9;"' : ''}>
                    <td class="fw-bold text-muted">${u.cedula}</td>
                    <td><div class="fw-bold text-dark">${u.nombre} ${u.apellido}</div>${metaBadge}</td>
                    <td>${u.nombre_usuario}</td>
                    <td><span class="badge ${badgeColor}">${u.rol.toUpperCase()}</span></td>
                    <td>${vectoresHtml}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning" onclick="editarUsuario(${u.ID})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="eliminarUsuario(${u.ID})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            $('#tablaUsuariosBody').html(html || '<tr><td colspan="6" class="text-center">No hay usuarios.</td></tr>');
        }
    }, 'json');
}

function editarUsuario(id) {
    $.get('api/api_usuarios.php', { accion: 'obtener', id: id }, function(res) {
        if(res.success) {
            let u = res.data;
            $('#id_usuario_modal').val(u.ID);
            $('#u_nombre_usuario').val(u.nombre_usuario);
            $('#u_contrasena').val(''); 
            $('#u_rol').val(u.rol);
            $('#u_nombre').val(u.nombre);
            $('#u_apellido').val(u.apellido);
            $('#u_cedula').val(u.cedula);
            $('#u_tlf').val(u.tlf);
            $('#u_profesion').val(u.profesion);
            $('#u_email').val(u.email);
            $('#u_tiene_meta').prop('checked', u.tiene_meta == 1);

            $('.check-permiso').prop('checked', false);
            if(u.permisos) {
                let p_arr = u.permisos.split(',');
                p_arr.forEach(num => {
                    // Marcamos los checkboxes correspondientes, incluyendo el "BLOCKED"
                    $(`input[name="permisos[]"][value="${num}"]`).prop('checked', true);
                });
            }

            $('#modalUsuarioLabel').html('<i class="bi bi-pencil-square"></i> Editar Usuario');
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        }
    }, 'json');
}

function eliminarUsuario(id) {
    Swal.fire({ title: '¿Eliminar Usuario?', text: "Se revocará su acceso al sistema de forma permanente.", icon: 'warning', showCancelButton: true }).then((result) => {
        if(result.isConfirmed) {
            $.post('api/api_usuarios.php', { accion: 'eliminar', id: id }, function(res) {
                if(res.success) cargarUsuarios(); else Swal.fire('Error', res.msg, 'error');
            }, 'json');
        }
    });
}