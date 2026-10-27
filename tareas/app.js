$(document).ready(function() {

    cargarEmpleadosActivos();
    cargarTareas();

    // Filtro por Departamento
    $('#filtroDepartamento').change(function() {
        let deptoFiltro = $(this).val();
        if (deptoFiltro === 'Todos') {
            $('.task-card').fadeIn(200);
        } else {
            $('.task-card').hide();$(`.task-card[data-depto="${deptoFiltro}"]`).fadeIn(200);
        }
    });

    // Inicializar Drag & Drop
    let columnas = document.querySelectorAll('.task-list');
    columnas.forEach(col => {
        new Sortable(col, {
            group: 'kanban', 
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function (evt) {
                let itemEl = evt.item; 
                let id_tarea = itemEl.getAttribute('data-id');
                let nuevo_estado = evt.to.getAttribute('data-estado'); 
                
                if (evt.from !== evt.to) {
                    actualizarEstadoTarea(id_tarea, nuevo_estado);
                }
            }
        });
    });

    // Formulario Nueva Tarea
    $('#formNuevaTarea').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        let data = $(this).serialize() + '&accion=guardar_tarea';

        $.ajax({
            url: 'api_tareas.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#modalNuevaTarea').modal('hide');
                    $('#formNuevaTarea')[0].reset();
                    cargarTareas();
                    Swal.fire({icon: 'success', title: 'Misión Asignada', timer: 1500, showConfirmButton: false, background: '#1e293b', color: '#fff'});
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            },
            error: function(jqXHR) {
                Swal.fire('Error', 'Fallo de conexión. ' + jqXHR.responseText, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('Autorizar Despliegue');
            }
        });
    });

});

function cargarEmpleadosActivos() {
    $.ajax({
        url: 'api_tareas.php',
        type: 'GET',
        data: { accion: 'listar_empleados' },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                let html = '<option value="">Seleccione Operador...</option>';
                if (res.data.length === 0) {
                    html = '<option value="">No hay empleados Activos</option>';
                } else {
                    res.data.forEach(e => {
                        html += `<option value="${e.id}">${e.nombre} ${e.apellido} - [${e.cargo}]</option>`;
                    });
                }
                $('#select_empleados').html(html);
            }
        }
    });
}

function cargarTareas() {
    $('#col_0, #col_1, #col_2').empty();

    $.ajax({
        url: 'api_tareas.php',
        type: 'GET',
        data: { accion: 'listar_tareas' },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                res.data.forEach(t => {
                    let clasePrioridad = 'priority-media';
                    let iconoPrio = '🟡';
                    if(t.prioridad === 'Alta') { clasePrioridad = 'priority-alta'; iconoPrio = '🔴'; }
                    if(t.prioridad === 'Baja') { clasePrioridad = 'priority-baja'; iconoPrio = '🔵'; }

                    let iniciales = '?';
                    let nombreCompleto = 'Sin asignar';
                    if(t.emp_nombre) {
                        iniciales = t.emp_nombre.charAt(0) + (t.emp_apellido ? t.emp_apellido.charAt(0) : '');
                        nombreCompleto = t.emp_nombre + ' ' + (t.emp_apellido || '');
                    }

                    // Se añade data-depto para que funcione el filtro del menú
                    let htmlCard = `
                        <div class="task-card ${clasePrioridad}" data-id="${t.id}" data-depto="${t.departamento_tarea}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark shadow-sm border border-secondary" style="font-size: 0.65rem;">${t.departamento_tarea}</span>
                                <span title="Prioridad ${t.prioridad}" style="font-size: 0.8rem;">${iconoPrio}</span>
                            </div>
                            <p class="mb-3 small fw-bold text-white" style="line-height: 1.4;">${t.tarea}</p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary">
                                <div class="d-flex align-items-center" title="Operador: ${nombreCompleto}">
                                    <div class="emp-avatar me-2">${iniciales}</div>
                                    <span style="font-size: 0.75rem; color: #cbd5e1; font-weight:bold;">${nombreCompleto}</span>
                                </div>
                                <button onclick="eliminarTarea(${t.id})" class="btn btn-sm text-danger p-0" title="Eliminar"><i class="bi bi-trash-fill fs-5"></i></button>
                            </div>
                        </div>
                    `;
                    $(`#col_${t.estado}`).append(htmlCard);
                });
                
                // Aplicar filtro si ya hay uno seleccionado
                $('#filtroDepartamento').trigger('change');
            }
        }
    });
}

function actualizarEstadoTarea(id_tarea, nuevo_estado) {
    $.post('api_tareas.php', { accion: 'actualizar_estado', id_tarea: id_tarea, estado: nuevo_estado }, function(res) {
        if(!res.success) {
            Swal.fire('Error', 'Fallo al mover la tarea', 'error');
            cargarTareas(); 
        }
    }, 'json');
}

function eliminarTarea(id_tarea) {
    Swal.fire({
        title: '¿Eliminar Misión?',
        icon: 'warning',
        showCancelButton: true,
        background: '#1e293b',
        color: '#fff',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Eliminar'
    }).then((result) => {
        if(result.isConfirmed) {
            $.post('api_tareas.php', { accion: 'eliminar_tarea', id_tarea: id_tarea }, function(res) {
                if(res.success) {
                    $(`.task-card[data-id="${id_tarea}"]`).fadeOut(300, function() { $(this).remove(); });
                }
            }, 'json');
        }
    });
}