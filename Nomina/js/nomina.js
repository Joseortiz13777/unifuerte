let datos_nomina_actual = [];
let id_pago_seleccionado_foto = 0;
let id_lote_actual_subida = 0;
let datos_liquidacion_actual = null; 
let viendoArchivadosPrestaciones = false;
let indiceEdicionActual = -1; 

// Variables para Vista Individual por Rango de Fechas y Auto-guardado de Asistencia
let modoVistaAsistencia = 'todos'; // 'todos' (Por Día) o 'individual' (Por Empleado + Rango de Fechas)
let indiceEmpleadoIndividual = 0;
let listaEmpleadosAsistenciaDia = [];
let datosRangoEmpleadoActual = [];
let empleadoRangoActualInfo = null;
let timersAutoGuardadoAsistencia = {};

$(document).ready(function(){
    $("#buscadorArchivo").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tablaPagosArchivoBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $(document).on('change', '#liq_id_empleado', function() {
        let f_in = $(this).find(':selected').data('ingreso');
        if (f_in && f_in !== '0000-00-00' && f_in !== 'null') {
            $('#liq_fecha_ingreso').val(f_in);
        } else {
            $('#liq_fecha_ingreso').val('');
        }
    });

    inyectarBotonFeriadoUI();
});

function toggleInterfacePrestaciones() {
    if (viendoArchivadosPrestaciones) {
        $('#btnToggleArchivadosPrest').html('<i class="bi bi-eye"></i> Ver Activos').removeClass('btn-secondary').addClass('btn-info text-white');
        $('.titulo-prestaciones-tabla').text('Personal Liquidado (ARCHIVADOS)');
    } else {
        $('#btnToggleArchivadosPrest').html('<i class="bi bi-archive"></i> Ver Archivados').removeClass('btn-info text-white').addClass('btn-secondary');
        $('.titulo-prestaciones-tabla').text('Personal Liquidado / Egresado');
    }
}

function mostrarVista(vista, e) {
    $('.vista-spa').hide(); $('#vista' + vista).fadeIn(300);
    $('.nav-link-custom').removeClass('active'); $(e).addClass('active');
    if(vista === 'Registro') cargarEmpleados();
    if(vista === 'Pagos') cargarHistorialPagos();
    if(vista === 'Asistencia') {
        inyectarBotonFeriadoUI();
        if (modoVistaAsistencia === 'individual') {
            cargarAsistenciaRangoEmpleado();
        } else {
            cargarAsistencia();
        }
    }
    if(vista === 'Prestaciones') { viendoArchivadosPrestaciones = false; toggleInterfacePrestaciones(); cargarHistorialPrestaciones(); }
    if(vista === 'Prestamos') { cargarVales(); cargarDeudasNomina(); cargarPrestamosVendedores(); }
}

// ============================ REGISTRO DE PERSONAL ==============================
function abrirModalEmpleado() { $('#formEmpleado')[0].reset(); $('#emp_id').val(0); $('#div_estado_emp').hide(); $('#modalEmpleado').modal('show'); }

function asignarSalarioSugerido() {
    let cargo = $('#emp_cargo').val().toLowerCase();
    let dep = ''; let sal = 0; let ces = 0;

    if(cargo.includes('presidente') || cargo.includes('gerente de ventas')) { dep = 'Administracion'; sal = 35; ces = 20; }
    else if(cargo.includes('logística') || cargo.includes('logistica')) { dep = 'Administracion'; sal = 25; ces = 20; }
    else if(cargo.includes('diseño') || cargo.includes('diseno')) { dep = 'Sistemas / Diseño'; sal = 50; ces = 50; }
    else if(cargo.includes('costurera tipo i')) { dep = 'Produccion / Confeccion'; sal = 25; ces = 20; }
    else if(cargo.includes('costurera tipo ii')) { dep = 'Produccion / Confeccion'; sal = 20; ces = 20; }
    else if(cargo.includes('ayudante de costura')) { dep = 'Produccion / Confeccion'; sal = 15; ces = 20; }
    else if(cargo.includes('cortadora')) { dep = 'Produccion / Confeccion'; sal = 25; ces = 20; }
    else if(cargo.includes('ayudante de corte')) { dep = 'Produccion / Confeccion'; sal = 25; ces = 20; }
    else if(cargo.includes('bordador')) { dep = 'Acabado Final / Bordado'; sal = 25; ces = 20; }
    else if(cargo.includes('limpieza')) { dep = 'Personal de Apoyo'; sal = 15; ces = 20; }
    else if(cargo.includes('contador')) { dep = 'Personal de Apoyo'; sal = 30; ces = 0; }
    else if(cargo.includes('tecnico') || cargo.includes('técnico')) { dep = 'Personal de Apoyo'; sal = 30; ces = 0; }
    else if(cargo.includes('tesista')) { dep = 'Personal de Apoyo'; sal = 20; ces = 0; }
    else if(cargo.includes('pasante')) { dep = 'Personal de Apoyo'; sal = 20; ces = 0; }
    else { Swal.fire('Sin coincidencias', 'Escribe un cargo válido de la lista para auto-asignar', 'info'); return; }

    $('#emp_departamento').val(dep);
    $('#emp_salario').val(sal);
    $('#emp_cesta').val(ces);
    Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Salario y Área asignados', showConfirmButton: false, timer: 1500});
}

function cargarEmpleados() {
    $.post('Registro/api_empleados.php', { action: 'listar' }, function(res) {
        if(res.success) {
            let html = ''; let activos = 0;
            res.data.forEach(e => {
                if(e.estado === 'Activo') activos++;

                let bancoTd = (e.banco && e.banco.trim() !== '') 
                    ? `<td class="text-nowrap"><small class="text-primary fw-bold" style="font-size:0.75rem;"><i class="bi bi-bank"></i> ${e.banco}</small><br><small class="text-dark fw-bold" style="font-size:0.75rem; cursor:pointer;" onclick="navigator.clipboard.writeText('${e.nro_cuenta}'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'Cuenta copiada', showConfirmButton:false, timer:1000});" title="Click para copiar cuenta">${e.nro_cuenta} <i class="bi bi-copy text-muted"></i></small></td>`
                    : `<td><span class="badge bg-light text-muted border">Sin registrar</span></td>`;

                let btnEstado = `<span class="badge ${e.estado === 'Activo' ? 'bg-success' : 'bg-danger'}" style="cursor:pointer; padding: 6px 10px;" onclick="cambiarEstadoEmpleado(${e.id}, '${e.estado}', '${e.nombre} ${e.apellido}')" title="Clic para Activar/Desactivar"><i class="bi bi-power"></i> ${e.estado}</span>`;
                let ced_limpia = "V-" + e.cedula.replace(/^[vejgcVEJGC]\s*-\s*/, '').trim().toUpperCase();

                html += `<tr>
                    <td class="fw-bold">${ced_limpia}</td>
                    <td>
                        <div class="fw-bold" style="color:#2e1065;">${e.nombre} ${e.apellido}</div>
                        <small class="text-muted"><i class="bi bi-telephone"></i> ${e.telefono}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border">${e.cargo}</span><br><small class="text-muted" style="font-size:0.65rem;">${e.departamento || ''}</small></td>
                    ${bancoTd}
                    <td class="text-end"><span class="fw-bold text-success">$${e.salario_base}</span><br><small class="text-muted" style="font-size:0.6rem;">${e.frecuencia_pago}</small></td>
                    <td class="text-end fw-bold text-primary">$${e.cesta_ticket}</td>
                    <td class="text-end fw-bold text-warning">$${e.bonos}</td>
                    <td class="text-center">${btnEstado}</td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-outline-info rounded-pill me-1" onclick="abrirModalPerfil(${e.id})"><i class="bi bi-eye-fill"></i></button>
                        <button class="btn btn-sm btn-outline-dark rounded-pill me-1" onclick="imprimirContrato(${e.id})"><i class="bi bi-file-pdf"></i></button>
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick='editarEmpleado(${JSON.stringify(e).replace(/'/g, "&apos;")})'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="eliminarEmpleado(${e.id}, '${e.nombre}')"><i class="bi bi-trash"></i></button>
                    </td></tr>`;
            });
            $('#tablaEmpleadosBody').html(html); $('#resumen_empleados').text(activos);
        }
    }, 'json');
}

$('#formEmpleado').submit(function(e) { e.preventDefault(); $.post('Registro/api_empleados.php', $(this).serialize(), function(res) { if(res.success) { $('#modalEmpleado').modal('hide'); cargarEmpleados(); Swal.fire('Guardado','','success'); } }, 'json'); });

function editarEmpleado(e) { 
    $('#formEmpleado')[0].reset(); 
    $('#emp_id').val(e.id); 
    $('#emp_cedula').val(e.cedula); 
    $('#emp_nombre').val(e.nombre); 
    $('#emp_apellido').val(e.apellido); 
    $('#emp_cargo').val(e.cargo); 
    $('#emp_horario').val(e.horario);
    $('#emp_departamento').val(e.departamento || ''); 
    $('#emp_telefono').val(e.telefono); 
    $('#emp_banco').val(e.banco || ''); 
    $('#emp_nro_cuenta').val(e.nro_cuenta || ''); 
    $('#emp_salario').val(e.salario_base); 
    $('#emp_cesta').val(e.cesta_ticket); 
    $('#emp_bonos').val(e.bonos); 
    $('#emp_frecuencia').val(e.frecuencia_pago); 
    $('#emp_fecha_ingreso').val(e.fecha_ingreso); 
    $('#emp_estado').val(e.estado); 
    $('#div_estado_emp').show(); 
    $('#modalEmpleado').modal('show'); 
}

function eliminarEmpleado(id, n) { Swal.fire({title:'Desincorporar a '+n+'?', icon:'warning', showCancelButton:true}).then((r)=>{ if(r.isConfirmed) $.post('Registro/api_empleados.php',{action:'eliminar', id:id}, function(){cargarEmpleados();}); }); }

function cambiarEstadoEmpleado(id, estadoActual, nombre) {
    let nuevoEstado = estadoActual === 'Activo' ? 'Inactivo' : 'Activo';
    Swal.fire({
        title: `¿Cambiar estado de ${nombre}?`,
        text: `Pasará de ${estadoActual} a ${nuevoEstado}. Al estar Inactivo, no aparecerá en la próxima Nómina.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e1065',
        confirmButtonText: `Sí, marcar como ${nuevoEstado}`,
        cancelButtonText: 'Cancelar'
    }).then((r) => {
        if(r.isConfirmed) {
            $.post('Registro/api_empleados.php', { action: 'cambiar_estado', id: id, estado: nuevoEstado }, function(res){
                if(res.success) {
                    cargarEmpleados();
                    Swal.fire({toast:true, position:'top-end', icon:'success', title:'Estado actualizado', showConfirmButton:false, timer:1500});
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
}

function imprimirContrato(id) { window.open('Registro/imprimir_contrato.php?id=' + id, '_blank'); }

function abrirModalPerfil(id) {
    $.post('Registro/api_empleados.php', { action: 'ver_perfil', id: id }, function(res){
        if(res.success) {
            let p = res.data; 
            $('#perfil_nombre').text(p.nombre + ' ' + p.apellido); 
            $('#perfil_cargo').text(p.cargo); 

            let bancoInfoProfile = p.banco ? `<br><small class="text-muted mt-1 d-block"><i class="bi bi-bank"></i> ${p.banco} - ${p.nro_cuenta}</small>` : '';
            let ced_perfil = "V-" + p.cedula.replace(/^[vejgcVEJGC]\s*-\s*/, '').trim().toUpperCase();
            
            $('#perfil_cedula').html(`${ced_perfil} ${bancoInfoProfile}`);

            let partes = p.fecha_ingreso.split('-'); $('#perfil_ingreso').text(`${partes[2]}/${partes[1]}/${partes[0]}`);
            $('#perfil_dias_trabajados').text(p.dias_trabajados); $('#perfil_total_pagado').text('$' + parseFloat(p.total_pagado_historico).toFixed(2));
            let html_pagos = '';
            if(p.pagos_historial.length === 0) html_pagos = '<tr><td colspan="6" class="text-center text-muted">Sin historial</td></tr>';
            else {
                p.pagos_historial.forEach(pg => {
                    html_pagos += `<tr><td class="fw-bold">${pg.fecha_pago_fmt}</td><td><span class="badge bg-secondary">${pg.periodo_fmt}</span></td><td class="text-success">$${parseFloat(pg.salario_base_periodo).toFixed(2)}</td><td class="text-info">+$${parseFloat(pg.asignaciones_extras).toFixed(2)}</td><td class="text-danger">-$${parseFloat(pg.deducciones).toFixed(2)}</td><td class="fw-bold" style="background-color: #f0fdfa; color: #0f766e;">$${parseFloat(pg.total_pagado).toFixed(2)}</td></tr>`;
                });
            }
            $('#tbody_perfil_pagos').html(html_pagos); $('#modalPerfilEmpleado').modal('show');
        }
    }, 'json');
}

// ============================ MÓDULO DE ASISTENCIA (POR DÍA GENERAL + POR EMPLEADO Y RANGO DE FECHAS) ==============================

function inyectarBotonFeriadoUI() {
    if ($('#chk_dia_feriado').length === 0 && $('#filtro_fecha_asistencia').length > 0) {
        const htmlFeriado = `
            <div id="contenedor_feriado_ui" class="mt-2 p-2 rounded border bg-warning bg-opacity-10 border-warning d-inline-block shadow-sm">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="chk_dia_feriado" style="cursor:pointer; transform: scale(1.15);" onchange="toggleDiaFeriadoGlobal()">
                    <label class="form-check-label fw-bold text-dark ms-1" for="chk_dia_feriado" style="cursor:pointer; font-size:0.88rem;">
                        🎉 Día Feriado <small class="text-muted fw-normal">(Presente = Pago Doble sin horario | Falta = Sin descuento)</small>
                    </label>
                </div>
            </div>
        `;
        if ($('#fechaAsistenciaEtiqueta').length > 0) {
            $('#fechaAsistenciaEtiqueta').after('<br>' + htmlFeriado);
        } else {
            $('#filtro_fecha_asistencia').after(htmlFeriado);
        }
    }
}

function accionBotonGuardarPrincipalAsistencia() {
    if (modoVistaAsistencia === 'individual') {
        guardarTodoRangoEmpleado(false);
    } else {
        guardarAsistencia(false);
    }
}

function cambiarModoVistaAsistencia(modo) {
    modoVistaAsistencia = (modo === 'individual') ? 'individual' : 'todos';

    if (modoVistaAsistencia === 'individual') {
        $('#btnModoTodosAsis').removeClass('active');
        $('#btnModoIndividualAsis').addClass('active');
        $('#bloqueFiltroDiaUnico').hide();
        $('#panelModoIndividualAsistencia').slideDown(200);
        $('#thColumnaPrincipalAsistencia').text('Fecha y Horario del Día');
        $('#lblBotonGuardarPrincipalAsis').text('Guardar Rango del Empleado');
        cargarAsistenciaRangoEmpleado();
    } else {
        $('#btnModoIndividualAsis').removeClass('active');
        $('#btnModoTodosAsis').addClass('active');
        $('#panelModoIndividualAsistencia').slideUp(200);
        $('#bloqueFiltroDiaUnico').show();
        $('#thColumnaPrincipalAsistencia').text('Trabajador y Horario');
        $('#lblBotonGuardarPrincipalAsis').text('Guardar Asistencia del Día');
        cargarAsistencia();
    }
}

function imprimirHojaAsistencia() {
    let desde = modoVistaAsistencia === 'individual'
        ? $('#filtro_fecha_inicio_ind').val()
        : $('#filtro_fecha_asistencia').val();
    let hasta = modoVistaAsistencia === 'individual'
        ? $('#filtro_fecha_fin_ind').val()
        : desde;
    const empleado = modoVistaAsistencia === 'individual'
        ? listaEmpleadosAsistenciaDia[indiceEmpleadoIndividual]
        : null;
    if (!desde || !hasta || (modoVistaAsistencia === 'individual' && !empleado)) {
        Swal.fire('Selecciona un período válido', 'Indica las fechas y, en vista individual, el empleado.', 'warning');
        return;
    }
    const query = new URLSearchParams({ desde, hasta });
    if (empleado) query.set('id_empleado', String(empleado.id_empleado));
    window.open(`Asistencia/imprimir_asistencia.php?${query.toString()}`, '_blank', 'noopener');
}

function setRangoRapidoAsistencia(tipo) {
    let baseStr = $('#filtro_fecha_inicio_ind').val() || $('#filtro_fecha_asistencia').val();
    let d = baseStr ? new Date(baseStr + 'T12:00:00') : new Date();
    let y = d.getFullYear();
    let m = String(d.getMonth() + 1).padStart(2, '0');
    let ultimoDia = new Date(y, d.getMonth() + 1, 0).getDate();

    if (tipo === 'q1') {
        $('#filtro_fecha_inicio_ind').val(`${y}-${m}-01`);
        $('#filtro_fecha_fin_ind').val(`${y}-${m}-15`);
    } else if (tipo === 'q2') {
        $('#filtro_fecha_inicio_ind').val(`${y}-${m}-16`);
        $('#filtro_fecha_fin_ind').val(`${y}-${m}-${String(ultimoDia).padStart(2, '0')}`);
    } else if (tipo === 'mes') {
        $('#filtro_fecha_inicio_ind').val(`${y}-${m}-01`);
        $('#filtro_fecha_fin_ind').val(`${y}-${m}-${String(ultimoDia).padStart(2, '0')}`);
    }
    cargarAsistenciaRangoEmpleado();
}

function seleccionarEmpleadoIndividual(index) {
    let idx = parseInt(index, 10);
    if (isNaN(idx) || idx < 0) idx = 0;
    if (listaEmpleadosAsistenciaDia.length > 0 && idx >= listaEmpleadosAsistenciaDia.length) {
        idx = listaEmpleadosAsistenciaDia.length - 1;
    }
    indiceEmpleadoIndividual = idx;
    if (modoVistaAsistencia !== 'individual') {
        cambiarModoVistaAsistencia('individual');
    } else {
        cargarAsistenciaRangoEmpleado();
    }
}

function cambiarEmpleadoIndividualPaso(delta) {
    if (listaEmpleadosAsistenciaDia.length === 0) return;
    let nuevoIdx = indiceEmpleadoIndividual + delta;
    if (nuevoIdx < 0) nuevoIdx = 0;
    if (nuevoIdx >= listaEmpleadosAsistenciaDia.length) nuevoIdx = listaEmpleadosAsistenciaDia.length - 1;
    indiceEmpleadoIndividual = nuevoIdx;
    $('#select_empleado_individual').val(String(indiceEmpleadoIndividual));
    cargarAsistenciaRangoEmpleado();
}

function actualizarControlesNavegacionIndividual() {
    const total = listaEmpleadosAsistenciaDia.length;
    if (total === 0) {
        $('#contador_empleado_individual').text('0 de 0');
        $('#btnAsisPrevEmp, #btnAsisNextEmp').prop('disabled', true);
        return;
    }
    if (indiceEmpleadoIndividual >= total) indiceEmpleadoIndividual = total - 1;
    if (indiceEmpleadoIndividual < 0) indiceEmpleadoIndividual = 0;

    $('#select_empleado_individual').val(String(indiceEmpleadoIndividual));
    $('#contador_empleado_individual').text(`${indiceEmpleadoIndividual + 1} de ${total}`);
    $('#btnAsisPrevEmp').prop('disabled', indiceEmpleadoIndividual <= 0);
    $('#btnAsisNextEmp').prop('disabled', indiceEmpleadoIndividual >= total - 1);
}

function cargarAsistenciaRangoEmpleado() {
    if (listaEmpleadosAsistenciaDia.length === 0) {
        let fechaRef = $('#filtro_fecha_asistencia').val() || new Date().toISOString().slice(0, 10);
        $.post('Asistencia/api_asistencia.php', { action: 'listar_dia', fecha: fechaRef }, function(res) {
            if (res.success && res.data.length > 0) {
                listaEmpleadosAsistenciaDia = res.data;
                let optsIndividual = '';
                res.data.forEach((a, idx) => {
                    optsIndividual += `<option value="${idx}">${idx + 1}. ${a.nombre} ${a.apellido} (H${a.horario})</option>`;
                });
                $('#select_empleado_individual').html(optsIndividual);
                ejecutarConsultaRangoEmpleado();
            } else {
                $('#tablaAsistenciaBody').html('<tr><td colspan="6" class="text-muted py-4">No hay empleados activos registrados.</td></tr>');
            }
        }, 'json');
    } else {
        ejecutarConsultaRangoEmpleado();
    }
}

function ejecutarConsultaRangoEmpleado() {
    actualizarControlesNavegacionIndividual();
    let empActual = listaEmpleadosAsistenciaDia[indiceEmpleadoIndividual];
    if (!empActual) return;

    let f_ini = $('#filtro_fecha_inicio_ind').val();
    let f_fin = $('#filtro_fecha_fin_ind').val();
    if (!f_ini || !f_fin) return;

    if (f_ini > f_fin) {
        Swal.fire('Atención', 'La Fecha Desde no puede ser mayor que la Fecha Hasta.', 'warning');
        return;
    }

    $('#tablaAsistenciaBody').html('<tr><td colspan="6" class="text-muted py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando rango de fechas del trabajador...</td></tr>');

    $.post('Asistencia/api_asistencia.php', {
        action: 'listar_rango_empleado',
        id_empleado: empActual.id_empleado,
        fecha_inicio: f_ini,
        fecha_fin: f_fin
    }, function(res) {
        if (res.success) {
            empleadoRangoActualInfo = res.empleado;
            datosRangoEmpleadoActual = res.dias || [];
            renderizarTablaRangoEmpleado();
        } else {
            $('#tablaAsistenciaBody').html(`<tr><td colspan="6" class="text-danger py-4 fw-bold">${res.msg || 'Error al cargar el rango.'}</td></tr>`);
        }
    }, 'json').fail(function() {
        $('#tablaAsistenciaBody').html('<tr><td colspan="6" class="text-danger py-4 fw-bold">Error de conexión al consultar el rango de fechas.</td></tr>');
    });
}

function formatearFechaCortaEs(fechaIso) {
    const partes = String(fechaIso || '').split('-');
    if (partes.length !== 3) return fechaIso;
    const fechaLocal = new Date(`${fechaIso}T12:00:00`);
    const nombresDias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return `${nombresDias[fechaLocal.getDay()]} ${partes[2]}/${partes[1]}/${partes[0]}`;
}

function renderizarTablaRangoEmpleado() {
    if (!empleadoRangoActualInfo) return;
    const incluirFinde = $('#chk_incluir_finde_ind').is(':checked');
    const horario = intval_js(empleadoRangoActualInfo.horario);
    const id_emp = empleadoRangoActualInfo.id;
    const nombreCompleto = `${empleadoRangoActualInfo.nombre} ${empleadoRangoActualInfo.apellido}`;

    let html = '';
    let filasVisibles = 0;

    datosRangoEmpleadoActual.forEach((d, index) => {
        if (!incluirFinde && intval_js(d.es_fin_semana) === 1 && intval_js(d.guardado_bd) === 0) {
            return;
        }
        filasVisibles++;

        let estadoActual = d.estado_vista || d.estado;
        let selectEstado = `
            <select class="form-select form-select-sm fw-bold asis-estado" data-index="${index}" onchange="calcularFilaAsistencia(${index}); programarAutoGuardadoFila(${index}, 150);">
                <option value="Presente" ${estadoActual === 'Presente' ? 'selected' : ''}>Presente</option>
                <option value="Feriado" ${estadoActual === 'Feriado' ? 'selected' : ''}>🎉 Feriado (Pago Doble)</option>
                <option value="Falta" ${(estadoActual === 'Falta' || d.estado === 'Falta Feriado') ? 'selected' : ''}>Falta</option>
                <option value="Permiso" ${estadoActual === 'Permiso' ? 'selected' : ''}>Permiso Remunerado</option>
            </select>
        `;

        let limitesDia = getHorarioLimites(horario, d.fecha);
        let hor_texto = horario == 1
            ? `7:00 AM - ${limitesDia.fin === 900 ? '3:00 PM (Viernes)' : '4:00 PM'}`
            : (horario == 2 ? '8:00 AM - 12:00 PM' : '7:00 AM - 2:00 PM');

        let badgeFinde = intval_js(d.es_fin_semana) === 1
            ? `<span class="badge bg-warning text-dark ms-1">Fin de Semana</span>`
            : '';

        let badgeGuardado = intval_js(d.guardado_bd) === 1
            ? `<small class="d-block mt-1 text-success fw-bold" id="status_save_${index}"><i class="bi bi-check2-all"></i> Guardado</small>`
            : `<small class="d-block mt-1 text-muted" id="status_save_${index}"><i class="bi bi-dash-circle"></i> Sin guardar</small>`;

        html += `
        <tr id="row_asis_${index}" class="${intval_js(d.es_fin_semana) === 1 ? 'table-warning' : ''}">
            <td class="text-start fw-bold">
                <div class="text-primary">${formatearFechaCortaEs(d.fecha)} ${badgeFinde}</div>
                <span class="badge bg-secondary border shadow-sm">H${horario} (${hor_texto})</span>
                <span class="asis-nombre-emp d-none">${nombreCompleto} (${d.fecha})</span>
                <input type="hidden" class="asis-id-emp" value="${id_emp}">
                <input type="hidden" class="asis-fecha-row" value="${d.fecha}">
                <input type="hidden" class="asis-horario" value="${horario}">
            </td>
            <td style="width: 210px;">${selectEstado}</td>
            <td style="width: 150px;">
                <div class="input-group input-group-sm position-relative">
                    <input type="text" class="form-control asis-entrada fw-bold" data-index="${index}" value="${convertirHora24a12(d.hora_entrada)}" placeholder="7:30 AM" inputmode="text" autocomplete="off" onchange="calcularFilaAsistencia(${index})" title="Escriba 730 para 7:30 o seleccione el reloj">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" class="asis-selector-hora position-absolute top-0 end-0 h-100" lang="en-US" step="60" value="${convertirHora24aSelector(d.hora_entrada)}" style="width: 42px; opacity: 0; z-index: 3; cursor: pointer;">
                </div>
            </td>
            <td style="width: 150px;">
                <div class="input-group input-group-sm position-relative">
                    <input type="text" class="form-control asis-salida fw-bold" data-index="${index}" value="${convertirHora24a12(d.hora_salida)}" placeholder="3:45 PM" inputmode="text" autocomplete="off" onchange="calcularFilaAsistencia(${index})" title="Escriba 330 para 3:30 o seleccione el reloj">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="time" class="asis-selector-hora position-absolute top-0 end-0 h-100" lang="en-US" step="60" value="${convertirHora24aSelector(d.hora_salida)}" style="width: 42px; opacity: 0; z-index: 3; cursor: pointer;">
                </div>
            </td>
            <td style="width: 240px;">
                <span class="badge bg-light text-success border fw-bold asis-resultado w-100 p-2 fs-6 shadow-sm" id="res_asis_${index}">A Tiempo</span>
            </td>
            <td style="width: 170px;">
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 shadow-sm" id="btn_save_row_${index}" onclick="guardarAsistenciaIndividual(${index}, false)">
                    <i class="bi bi-save"></i> Guardar
                </button>
                ${badgeGuardado}
            </td>
        </tr>`;
    });

    if (filasVisibles === 0) {
        html = '<tr><td colspan="6" class="text-muted py-4">No hay días hábiles en este rango (active "Ver Sáb/Dom" si desea ver fines de semana).</td></tr>';
    }

    $('#tablaAsistenciaBody').html(html);
    datosRangoEmpleadoActual.forEach((d, idx) => {
        if ($(`#row_asis_${idx}`).length > 0) {
            calcularFilaAsistencia(idx);
        }
    });
    recalcularTotalesRangoUI();
}

function recalcularTotalesRangoUI() {
    if (modoVistaAsistencia !== 'individual') return;
    let totPresentes = 0;
    let totFaltas = 0;
    let totMinRetardo = 0;
    let totMinExtra = 0;

    $('#tablaAsistenciaBody tr').each(function() {
        let $row = $(this);
        if (!$row.find('.asis-id-emp').val()) return;
        let estado = $row.find('.asis-estado').val();
        let fechaFila = $row.find('.asis-fecha-row').val() || $('#filtro_fecha_asistencia').val();
        let horario = parseInt($row.find('.asis-horario').val(), 10);

        if (estado === 'Presente' || estado === 'Feriado') {
            totPresentes++;
        } else if (estado === 'Falta') {
            totFaltas++;
        }

        if (estado === 'Presente') {
            const balance = calcularBalanceMinutosAsistencia(
                $row.find('.asis-entrada').val(),
                $row.find('.asis-salida').val(),
                horario,
                fechaFila
            );
            totMinRetardo += balance.retardo;
            totMinExtra += balance.extra;
        }
    });

    const fmtMin = (m) => {
        let h = Math.floor(m / 60);
        let r = m % 60;
        return h > 0 ? `${h}h ${r}m` : `${r}m`;
    };

    $('#ind_tot_presentes').text(totPresentes);
    $('#ind_tot_faltas').text(totFaltas);
    $('#ind_tot_retardo').text(fmtMin(totMinRetardo));
    $('#ind_tot_extra').text(fmtMin(totMinExtra));
}

function guardarTodoRangoEmpleado(silencioso = false) {
    if (!empleadoRangoActualInfo) return;
    let registros = [];
    let horaInvalida = false;

    $('#tablaAsistenciaBody tr').each(function() {
        let idx = $(this).find('.asis-estado').data('index');
        if (idx === undefined) return;
        let info = construirObjetoAsistenciaFila(idx);
        if (!info.valido) {
            horaInvalida = true;
        } else {
            registros.push(info.datos);
        }
    });

    if (horaInvalida) {
        Swal.fire('Formato de hora inválido', 'Verifique las horas escritas (ej. 7:30 AM o 4:00 PM).', 'warning');
        return;
    }

    if (!silencioso) {
        Swal.fire({ title: 'Guardando rango del empleado...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
    }

    $.post('Asistencia/api_asistencia.php', {
        action: 'guardar_rango_empleado',
        id_empleado: empleadoRangoActualInfo.id,
        registros: JSON.stringify(registros)
    }, function(res) {
        if (res.success) {
            let horaTxt = res.hora_guardado || 'Ahora';
            $('[id^="status_save_"]').removeClass('text-primary text-danger text-muted').addClass('text-success fw-bold').html(`<i class="bi bi-check2-all"></i> Guardado (${horaTxt})`);
            $('#indicador_autoguardado_global').removeClass('text-primary text-danger').addClass('text-success').html(`<i class="bi bi-cloud-check-fill"></i> Rango guardado (${horaTxt})`);
            if (!silencioso) {
                Swal.fire('¡Rango Guardado!', `Se guardó toda la asistencia del período para ${empleadoRangoActualInfo.nombre} ${empleadoRangoActualInfo.apellido}.`, 'success');
            }
        } else {
            if (!silencioso) Swal.fire('Error', res.msg || 'No se pudo guardar el rango.', 'error');
        }
    }, 'json');
}

function toggleDiaFeriadoGlobal() {
    const esFeriado = $('#chk_dia_feriado').is(':checked');
    $('#tablaAsistenciaBody tr').each(function(index) {
        let $select = $(this).find('.asis-estado');
        if ($select.length > 0) {
            let valActual = $select.val();
            if (esFeriado) {
                if (valActual === 'Presente') {
                    $select.val('Feriado');
                }
            } else {
                if (valActual === 'Feriado') {
                    $select.val('Presente');
                }
            }
            calcularFilaAsistencia(index);
        }
    });
    guardarAsistencia(true);
}

function convertirHoraAsistenciaAMinutos(hora) {
    const coincidencia = String(hora || '').trim().match(/^(1[0-2]|[1-9]):([0-5]\d)\s*(AM|PM)$/i);
    if (!coincidencia) return null;

    let horas = Number(coincidencia[1]) % 12;
    if (coincidencia[3].toUpperCase() === 'PM') horas += 12;
    return horas * 60 + Number(coincidencia[2]);
}

function convertirHora24a12(hora) {
    const coincidencia = String(hora || '').match(/^([01]\d|2[0-3]):([0-5]\d)/);
    if (!coincidencia) return '';

    const horas24 = Number(coincidencia[1]);
    const sufijo = horas24 >= 12 ? 'PM' : 'AM';
    const horas12 = horas24 % 12 || 12;
    return `${horas12}:${coincidencia[2]} ${sufijo}`;
}

function convertirHora24aSelector(hora) {
    const coincidencia = String(hora || '').match(/^([01]\d|2[0-3]):([0-5]\d)/);
    return coincidencia ? `${coincidencia[1]}:${coincidencia[2]}` : '';
}

function convertirHora12a24(hora) {
    const minutos = convertirHoraAsistenciaAMinutos(hora);
    if (minutos === null) return null;
    const horas = String(Math.floor(minutos / 60)).padStart(2, '0');
    const resto = String(minutos % 60).padStart(2, '0');
    return `${horas}:${resto}:00`;
}

function formatearHoraEscrita(hora, sufijoPorDefecto) {
    const texto = String(hora || '').trim().toUpperCase();
    if (!texto) return '';
    if (convertirHoraAsistenciaAMinutos(texto) !== null) return texto;

    const coincidencia = texto.match(/^(\d{1,4})\s*(AM|PM)?$/);
    if (!coincidencia) return texto;

    const digitos = coincidencia[1];
    let horas;
    let minutos;
    if (digitos.length <= 2) {
        horas = Number(digitos);
        minutos = 0;
    } else {
        horas = Number(digitos.slice(0, digitos.length - 2));
        minutos = Number(digitos.slice(-2));
    }

    if (minutos > 59) return texto;
    let sufijo = coincidencia[2] || sufijoPorDefecto;
    if (horas > 12 && horas <= 23) {
        horas -= 12;
        sufijo = coincidencia[2] || 'PM';
    }
    if (horas < 1 || horas > 12) return texto;

    return `${horas}:${String(minutos).padStart(2, '0')} ${sufijo}`;
}

function getHorarioLimites(horario, fecha) {
    if(horario === 2) return { inicio: 480, inicio_descuento: 480, fin: 720 };
    if(horario === 3) return { inicio: 420, inicio_descuento: 450, fin: 840 };
    const esViernes = /^\d{4}-\d{2}-\d{2}$/.test(fecha || '') &&
        new Date(`${fecha}T12:00:00`).getDay() === 5;
    return { inicio: 420, inicio_descuento: 450, fin: esViernes ? 900 : 960 };
}

function calcularBalanceMinutosAsistencia(entrada, salida, horario, fecha) {
    const limites = getHorarioLimites(horario, fecha);
    const minutosEntrada = convertirHoraAsistenciaAMinutos(entrada);
    const minutosSalida = convertirHoraAsistenciaAMinutos(salida);
    const tardanzaEntrada = minutosEntrada !== null
        ? Math.max(0, minutosEntrada - limites.inicio_descuento)
        : 0;
    const salidaTemprana = minutosSalida !== null
        ? Math.max(0, limites.fin - minutosSalida)
        : 0;
    const tiempoExtra = minutosSalida !== null
        ? Math.max(0, minutosSalida - limites.fin)
        : 0;
    const minutosPorCompensar = tardanzaEntrada + salidaTemprana;

    return {
        retardo: Math.max(0, minutosPorCompensar - tiempoExtra),
        extra: Math.max(0, tiempoExtra - minutosPorCompensar)
    };
}

function actualizarEtiquetaFechaAsistencia() {
    const fecha = $('#filtro_fecha_asistencia').val();
    const coincidencia = String(fecha || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!coincidencia) {
        $('#fechaAsistenciaEtiqueta').text('');
        return;
    }

    const fechaLocal = new Date(`${fecha}T12:00:00`);
    const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const [, anio, mes, dia] = coincidencia;
    $('#fechaAsistenciaEtiqueta').text(
        `${dias[fechaLocal.getDay()]} ${dia}/${mes}/${anio.slice(-2)}`
    );
}

function cargarAsistencia() {
    inyectarBotonFeriadoUI();
    let fecha = $('#filtro_fecha_asistencia').val();
    if(!fecha) return;
    actualizarEtiquetaFechaAsistencia();
    $('#tablaAsistenciaBody').html('<tr><td colspan="6" class="text-muted py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</td></tr>');
    
    $.ajax({
        url: 'Asistencia/api_asistencia.php',
        type: 'POST',
        data: { action: 'listar_dia', fecha: fecha },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                $('#chk_dia_feriado').prop('checked', intval_js(res.es_feriado) === 1);
                listaEmpleadosAsistenciaDia = res.data || [];

                let html = '';
                let optsIndividual = '';

                res.data.forEach((a, index) => {
                    let estadoActual = a.estado_vista || a.estado;
                    if (intval_js(res.es_feriado) === 1 && estadoActual === 'Presente') {
                        estadoActual = 'Feriado';
                    }

                    optsIndividual += `<option value="${index}">${index + 1}. ${a.nombre} ${a.apellido} (H${a.horario})</option>`;

                    let selectEstado = `
                        <select class="form-select form-select-sm fw-bold asis-estado" data-index="${index}" onchange="calcularFilaAsistencia(${index}); programarAutoGuardadoFila(${index}, 150);">
                            <option value="Presente" ${estadoActual === 'Presente' ? 'selected' : ''}>Presente</option>
                            <option value="Feriado" ${estadoActual === 'Feriado' ? 'selected' : ''}>🎉 Feriado (Pago Doble)</option>
                            <option value="Falta" ${(estadoActual === 'Falta' || a.estado === 'Falta Feriado') ? 'selected' : ''}>Falta</option>
                            <option value="Permiso" ${estadoActual === 'Permiso' ? 'selected' : ''}>Permiso Remunerado</option>
                        </select>
                    `;
                    
                    let hor_texto = a.horario == 1
                        ? `7:00 AM - ${getHorarioLimites(1, fecha).fin === 900 ? '3:00 PM' : '4:00 PM'}`
                        : (a.horario == 2 ? '8:00 AM - 12:00 PM' : '7:00 AM - 2:00 PM');

                    let badgeGuardado = intval_js(a.guardado_bd) === 1
                        ? `<small class="d-block mt-1 text-success fw-bold" id="status_save_${index}"><i class="bi bi-check2-all"></i> Guardado</small>`
                        : `<small class="d-block mt-1 text-muted" id="status_save_${index}"><i class="bi bi-dash-circle"></i> Sin cambios</small>`;
                    
                    html += `
                    <tr id="row_asis_${index}">
                        <td class="text-start fw-bold">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="asis-nombre-emp">${a.nombre} ${a.apellido}</span> <br>
                                    <span class="badge bg-secondary border shadow-sm">H${a.horario} (${hor_texto})</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border text-primary ms-2" onclick="seleccionarEmpleadoIndividual(${index})" title="Ver rango de fechas de este empleado">
                                    <i class="bi bi-calendar-range"></i> Rango
                                </button>
                            </div>
                            <input type="hidden" class="asis-id-emp" value="${a.id_empleado}">
                            <input type="hidden" class="asis-fecha-row" value="${fecha}">
                            <input type="hidden" class="asis-horario" value="${a.horario}">
                        </td>
                        <td style="width: 210px;">${selectEstado}</td>
                        <td style="width: 150px;">
                            <div class="input-group input-group-sm position-relative">
                                <input type="text" class="form-control asis-entrada fw-bold" data-index="${index}" value="${convertirHora24a12(a.hora_entrada)}" placeholder="7:30 AM" inputmode="text" autocomplete="off" onchange="calcularFilaAsistencia(${index})" title="Escriba 730 para 7:30 o seleccione el reloj">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <input type="time" class="asis-selector-hora position-absolute top-0 end-0 h-100" lang="en-US" step="60" value="${convertirHora24aSelector(a.hora_entrada)}" aria-label="Seleccionar hora de entrada" style="width: 42px; opacity: 0; z-index: 3; cursor: pointer;">
                            </div>
                        </td>
                        <td style="width: 150px;">
                            <div class="input-group input-group-sm position-relative">
                                <input type="text" class="form-control asis-salida fw-bold" data-index="${index}" value="${convertirHora24a12(a.hora_salida)}" placeholder="3:45 PM" inputmode="text" autocomplete="off" onchange="calcularFilaAsistencia(${index})" title="Escriba 330 para 3:30 o seleccione el reloj">
                                <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                <input type="time" class="asis-selector-hora position-absolute top-0 end-0 h-100" lang="en-US" step="60" value="${convertirHora24aSelector(a.hora_salida)}" aria-label="Seleccionar hora de salida" style="width: 42px; opacity: 0; z-index: 3; cursor: pointer;">
                            </div>
                        </td>
                        <td style="width: 240px;">
                            <span class="badge bg-light text-success border fw-bold asis-resultado w-100 p-2 fs-6 shadow-sm" id="res_asis_${index}">A Tiempo</span>
                        </td>
                        <td style="width: 170px;">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 shadow-sm" id="btn_save_row_${index}" onclick="guardarAsistenciaIndividual(${index}, false)">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                            ${badgeGuardado}
                        </td>
                    </tr>`;
                });

                $('#select_empleado_individual').html(optsIndividual);

                if(res.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-muted py-4">No hay empleados activos en el sistema.</td></tr>';
                }

                $('#tablaAsistenciaBody').html(html);
                res.data.forEach((a, i) => calcularFilaAsistencia(i));
            } else {
                $('#tablaAsistenciaBody').html(`<tr><td colspan="6" class="text-danger py-4 fw-bold">Error: ${res.msg}</td></tr>`);
            }
        },
        error: function(err) {
            console.error(err.responseText);
            $('#tablaAsistenciaBody').html('<tr><td colspan="6" class="text-danger py-4 fw-bold">Error de red o Servidor (Verifica la conexión)</td></tr>');
        }
    });
}

function intval_js(v) {
    let n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
}

function calcularFilaAsistencia(index) {
    let $row = $(`#row_asis_${index}`);
    if ($row.length === 0) return;

    let esDiaFeriadoGlobal = (modoVistaAsistencia === 'todos' && $('#chk_dia_feriado').is(':checked'));
    let estado = $row.find('.asis-estado').val();
    let entrada = $row.find('.asis-entrada').val();
    let salida = $row.find('.asis-salida').val();
    let horario = parseInt($row.find('.asis-horario').val(), 10);
    let fechaFila = $row.find('.asis-fecha-row').val() || $('#filtro_fecha_asistencia').val();
    let resSpan = $(`#res_asis_${index}`);

    let esFeriadoFila = (esDiaFeriadoGlobal || estado === 'Feriado');

    let deshabilitarHoras = (esFeriadoFila || estado !== 'Presente');
    $row.find('.asis-entrada, .asis-salida, .asis-selector-hora').prop('disabled', deshabilitarHoras);

    if (esFeriadoFila) {
        if (estado === 'Falta') {
            resSpan.removeClass('text-danger border-danger text-success border-success text-warning border-warning')
                   .addClass('text-secondary border-secondary')
                   .html('<i class="bi bi-calendar2-check"></i> Libre Feriado (Sin Descuento)');
        } else if (estado === 'Permiso') {
            resSpan.removeClass('text-danger text-dark border-danger border-dark text-warning border-warning')
                   .addClass('text-success border-success')
                   .html('<i class="bi bi-shield-check"></i> Permiso / Reposo');
        } else {
            resSpan.removeClass('text-danger border-danger text-secondary border-secondary text-success border-success')
                   .addClass('text-warning border-warning text-dark')
                   .html('<i class="bi bi-stars"></i> Feriado Trabajado (Pago Doble)');
        }
        recalcularTotalesRangoUI();
        return;
    }

    if(estado === 'Falta') {
        resSpan.removeClass('text-success text-dark border-success border-dark text-warning border-warning text-secondary border-secondary').addClass('text-danger border-danger').html('<i class="bi bi-x-circle"></i> Día perdido (Falta)');
        recalcularTotalesRangoUI();
        return;
    } else if (estado === 'Permiso') {
        resSpan.removeClass('text-danger text-dark border-danger border-dark text-warning border-warning text-secondary border-secondary').addClass('text-success border-success').html('<i class="bi bi-shield-check"></i> Permiso / Reposo');
        recalcularTotalesRangoUI();
        return;
    }

    const balance = calcularBalanceMinutosAsistencia(entrada, salida, horario, fechaFila);
    const min_retardo = balance.retardo;
    const min_extra = balance.extra;

    if ((entrada && convertirHoraAsistenciaAMinutos(entrada) === null) ||
        (salida && convertirHoraAsistenciaAMinutos(salida) === null)) {
        resSpan.removeClass('text-success text-warning border-success border-warning').addClass('text-danger border-danger').text('Hora inválida (use AM/PM)');
        return;
    }

    if(min_retardo > 0) {
        let h = Math.floor(min_retardo / 60);
        let m = min_retardo % 60;
        let txt = h > 0 ? `<i class="bi bi-exclamation-triangle"></i> ${h}h ${m}m de retardo` : `<i class="bi bi-exclamation-triangle"></i> ${m} min de retardo`;
        resSpan.removeClass('text-success border-success text-warning border-warning text-secondary border-secondary').addClass('text-danger border-danger').html(txt);
    } else if(min_extra > 0) {
        let h = Math.floor(min_extra / 60);
        let m = min_extra % 60;
        let txt = h > 0 ? `<i class="bi bi-plus-circle"></i> ${h}h ${m}m Extras` : `<i class="bi bi-plus-circle"></i> ${m} min Extras`;
        resSpan.removeClass('text-success border-success text-danger border-danger text-secondary border-secondary').addClass('text-warning border-warning text-dark').html(txt);
    } else {
        resSpan.removeClass('text-danger border-danger text-warning border-warning text-secondary border-secondary').addClass('text-success border-success').html('<i class="bi bi-check-circle"></i> A tiempo');
    }

    recalcularTotalesRangoUI();
}

function construirObjetoAsistenciaFila(index) {
    let $row = $(`#row_asis_${index}`);
    if ($row.length === 0) return { valido: false, error: 'Fila no encontrada' };

    let fecha = $row.find('.asis-fecha-row').val() || $('#filtro_fecha_asistencia').val();
    let esDiaFeriadoGlobal = (modoVistaAsistencia === 'todos' && $('#chk_dia_feriado').is(':checked')) ? 1 : 0;
    let id_emp = $row.find('.asis-id-emp').val();
    let nombre_emp = $row.find('.asis-nombre-emp').text().trim();
    let estado = $row.find('.asis-estado').val();
    let esFeriadoFila = (esDiaFeriadoGlobal === 1 || estado === 'Feriado');

    const entradaTexto = String($row.find('.asis-entrada').val() || '').trim();
    const salidaTexto = String($row.find('.asis-salida').val() || '').trim();
    let entrada = (!esFeriadoFila && estado === 'Presente' && entradaTexto) ? convertirHora12a24(entradaTexto) : null;
    let salida = (!esFeriadoFila && estado === 'Presente' && salidaTexto) ? convertirHora12a24(salidaTexto) : null;
    let horario = parseInt($row.find('.asis-horario').val(), 10);

    if (!esFeriadoFila && estado === 'Presente' && ((entradaTexto && !entrada) || (salidaTexto && !salida))) {
        return { valido: false, error: 'Hora inválida', $row: $row };
    }

    const balance = (!esFeriadoFila && estado === 'Presente')
        ? calcularBalanceMinutosAsistencia(entradaTexto, salidaTexto, horario, fecha)
        : { retardo: 0, extra: 0 };
    const min_retardo = balance.retardo;
    const min_extra = balance.extra;

    let estadoEnviar = estado;
    if (esDiaFeriadoGlobal === 1) {
        if (estado === 'Presente' || estado === 'Feriado') {
            estadoEnviar = 'Feriado';
        } else if (estado === 'Falta') {
            estadoEnviar = 'Falta Feriado';
        }
    }

    return {
        valido: true,
        fecha: fecha,
        es_feriado_global: esDiaFeriadoGlobal,
        nombre_emp: nombre_emp,
        datos: {
            id_empleado: id_emp,
            fecha: fecha,
            estado: estadoEnviar,
            es_feriado: esFeriadoFila ? 1 : 0,
            hora_entrada: entrada || null,
            hora_salida: salida || null,
            minutos_retardo: min_retardo,
            minutos_extra: min_extra
        }
    };
}

function programarAutoGuardadoFila(index, delay = 450) {
    if (timersAutoGuardadoAsistencia[index]) {
        clearTimeout(timersAutoGuardadoAsistencia[index]);
    }
    $(`#status_save_${index}`).removeClass('text-success text-danger text-muted').addClass('text-primary fw-bold').html('<i class="bi bi-arrow-repeat"></i> Guardando...');
    timersAutoGuardadoAsistencia[index] = setTimeout(function() {
        guardarAsistenciaIndividual(index, true);
    }, delay);
}

function guardarAsistenciaIndividual(index, silencioso = false) {
    let infoFila = construirObjetoAsistenciaFila(index);

    if (!infoFila.valido) {
        if (!silencioso) {
            Swal.fire('Formato de hora inválido', 'Escriba la hora en formato de 12 horas, por ejemplo 7:30 AM o 3:45 PM.', 'warning');
        } else {
            $(`#status_save_${index}`).removeClass('text-primary text-success').addClass('text-danger fw-bold').html('<i class="bi bi-exclamation-circle"></i> Hora incompleta');
        }
        return;
    }

    let $btn = $(`#btn_save_row_${index}`);
    let $status = $(`#status_save_${index}`);
    $btn.prop('disabled', true);
    $status.removeClass('text-success text-danger text-muted').addClass('text-primary fw-bold').html('<i class="bi bi-cloud-arrow-up"></i> Guardando...');
    $('#indicador_autoguardado_global').removeClass('text-success text-danger').addClass('text-primary').html('<i class="bi bi-arrow-repeat"></i> Guardando cambios automáticamente...');

    $.post('Asistencia/api_asistencia.php', {
        action: 'guardar_individual',
        fecha: infoFila.fecha,
        es_feriado: infoFila.es_feriado_global,
        registro: JSON.stringify(infoFila.datos)
    }, function(res) {
        $btn.prop('disabled', false);
        if (res.success) {
            let horaTxt = res.hora_guardado || 'Ahora';
            $status.removeClass('text-primary text-danger text-muted').addClass('text-success fw-bold').html(`<i class="bi bi-check2-all"></i> Guardado (${horaTxt})`);
            $('#indicador_autoguardado_global').removeClass('text-primary text-danger').addClass('text-success').html(`<i class="bi bi-cloud-check-fill"></i> Auto-guardado (${infoFila.nombre_emp} - ${horaTxt})`);

            if (!silencioso) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Asistencia guardada (${infoFila.fecha})`,
                    showConfirmButton: false,
                    timer: 1600
                });
            }
        } else {
            $status.removeClass('text-primary text-success text-muted').addClass('text-danger fw-bold').html('<i class="bi bi-x-circle"></i> Error al guardar');
            $('#indicador_autoguardado_global').removeClass('text-primary text-success').addClass('text-danger').html('<i class="bi bi-exclamation-triangle-fill"></i> Error en auto-guardado');
            if (!silencioso) {
                Swal.fire('Error', res.msg || 'No se pudo guardar la asistencia del empleado.', 'error');
            }
        }
    }, 'json').fail(function() {
        $btn.prop('disabled', false);
        $status.removeClass('text-primary text-success text-muted').addClass('text-danger fw-bold').html('<i class="bi bi-wifi-off"></i> Sin conexión');
        if (!silencioso) {
            Swal.fire('Error', 'Fallo de red al intentar guardar.', 'error');
        }
    });
}

function guardarAsistencia(silencioso = false) {
    let fecha = $('#filtro_fecha_asistencia').val();
    let esDiaFeriadoGlobal = $('#chk_dia_feriado').is(':checked') ? 1 : 0;
    let registros = [];
    let horaInvalida = false;

    $('#tablaAsistenciaBody tr').each(function() {
        let id_emp = $(this).find('.asis-id-emp').val();
        if(id_emp) {
            let estado = $(this).find('.asis-estado').val();
            let esFeriadoFila = (esDiaFeriadoGlobal === 1 || estado === 'Feriado');

            const entradaTexto = $(this).find('.asis-entrada').val();
            const salidaTexto = $(this).find('.asis-salida').val();
            let entrada = (!esFeriadoFila && estado === 'Presente' && entradaTexto) ? convertirHora12a24(entradaTexto) : null;
            let salida = (!esFeriadoFila && estado === 'Presente' && salidaTexto) ? convertirHora12a24(salidaTexto) : null;
            let horario = parseInt($(this).find('.asis-horario').val());

            if (!esFeriadoFila && estado === 'Presente' && ((entradaTexto && !entrada) || (salidaTexto && !salida))) {
                horaInvalida = true;
                $(this).find('.asis-entrada, .asis-salida').each(function() {
                    const valor = $(this).val();
                    $(this).toggleClass('is-invalid', Boolean(valor && !convertirHora12a24(valor)));
                });
            }
            
            const balance = (!esFeriadoFila && estado === 'Presente')
                ? calcularBalanceMinutosAsistencia(entradaTexto, salidaTexto, horario, fecha)
                : { retardo: 0, extra: 0 };
            const min_retardo = balance.retardo;
            const min_extra = balance.extra;

            let estadoEnviar = estado;
            if (esDiaFeriadoGlobal === 1) {
                if (estado === 'Presente' || estado === 'Feriado') {
                    estadoEnviar = 'Feriado';
                } else if (estado === 'Falta') {
                    estadoEnviar = 'Falta Feriado';
                }
            }

            registros.push({
                id_empleado: id_emp,
                estado: estadoEnviar,
                hora_entrada: entrada || null,
                hora_salida: salida || null,
                minutos_retardo: min_retardo,
                minutos_extra: min_extra
            });
        }
    });

    if (horaInvalida) {
        if (!silencioso) {
            Swal.fire('Formato de hora inválido', 'Escriba la hora en formato de 12 horas, por ejemplo 7:30 AM o 3:45 PM.', 'warning');
        }
        return;
    }

    if (!silencioso) {
        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
    } else {
        $('#indicador_autoguardado_global').removeClass('text-success text-danger').addClass('text-primary').html('<i class="bi bi-arrow-repeat"></i> Guardando todo el día...');
    }
    
    $.post('Asistencia/api_asistencia.php', {
        action: 'guardar_lote',
        fecha: fecha,
        es_feriado: esDiaFeriadoGlobal,
        registros: JSON.stringify(registros)
    }, function(res) {
        if(res.success) {
            let horaTxt = res.hora_guardado || 'Ahora';
            $('[id^="status_save_"]').removeClass('text-primary text-danger text-muted').addClass('text-success fw-bold').html(`<i class="bi bi-check2-all"></i> Guardado (${horaTxt})`);
            $('#indicador_autoguardado_global').removeClass('text-primary text-danger').addClass('text-success').html(`<i class="bi bi-cloud-check-fill"></i> Todo el día guardado (${horaTxt})`);

            if (!silencioso) {
                Swal.fire('¡Asistencia Guardada!', 'Los registros se han actualizado con éxito. Los días feriados, faltas y extras se calcularán automáticamente en la nómina.', 'success');
                cargarAsistencia();
            }
        } else {
            if (!silencioso) {
                Swal.fire('Error', res.msg, 'error');
            }
        }
    }, 'json');
}

$(document).on('input change', '.asis-entrada, .asis-salida', function() {
    $(this).removeClass('is-invalid');
});

$(document).on('input', '.asis-entrada, .asis-salida', function() {
    const texto = String($(this).val() || '').trim();
    const index = $(this).data('index');

    if (texto === '') {
        $(this).siblings('.asis-selector-hora').val('');
        calcularFilaAsistencia(index);
        programarAutoGuardadoFila(index, 400);
        return;
    }

    if (!/^\d{3,4}$/.test(texto)) return;

    const esEntrada = $(this).hasClass('asis-entrada');
    const horario = parseInt($(this).closest('tr').find('.asis-horario').val(), 10);
    const sufijoPorDefecto = esEntrada || horario === 2 ? 'AM' : 'PM';
    const horaFormateada = formatearHoraEscrita(texto, sufijoPorDefecto);
    if (horaFormateada === texto) return;

    $(this).val(horaFormateada);
    const minutos = convertirHoraAsistenciaAMinutos(horaFormateada);
    if (minutos !== null) {
        $(this).siblings('.asis-selector-hora').val(
            `${String(Math.floor(minutos / 60)).padStart(2, '0')}:${String(minutos % 60).padStart(2, '0')}`
        );
    }
    calcularFilaAsistencia(index);
    programarAutoGuardadoFila(index, 400);
});

$(document).on('change', '.asis-selector-hora', function() {
    const $horaTexto = $(this).siblings('.asis-entrada, .asis-salida');
    const index = $horaTexto.data('index');
    $horaTexto.val(convertirHora24a12($(this).val())).removeClass('is-invalid');
    calcularFilaAsistencia(index);
    programarAutoGuardadoFila(index, 150);
});

$(document).on('blur', '.asis-entrada, .asis-salida', function() {
    const esEntrada = $(this).hasClass('asis-entrada');
    const index = $(this).data('index');
    const horario = parseInt($(this).closest('tr').find('.asis-horario').val(), 10);
    const sufijoPorDefecto = esEntrada || horario === 2 ? 'AM' : 'PM';
    const horaFormateada = formatearHoraEscrita($(this).val(), sufijoPorDefecto);
    $(this).val(horaFormateada);
    const minutos = convertirHoraAsistenciaAMinutos(horaFormateada);
    if (minutos !== null) {
        $(this).siblings('.asis-selector-hora').val(
            `${String(Math.floor(minutos / 60)).padStart(2, '0')}:${String(minutos % 60).padStart(2, '0')}`
        );
    }
    calcularFilaAsistencia(index);
    if (horaFormateada === '' || minutos !== null) {
        programarAutoGuardadoFila(index, 200);
    }
});

$(document).on('change', '#filtro_fecha_asistencia', actualizarEtiquetaFechaAsistencia);
$(document).ready(actualizarEtiquetaFechaAsistencia);

// ============================ PAGOS / GENERAR NÓMINA ==============================
function abrirModalGenerarNomina() { 
    $('#formFiltroNomina')[0].reset(); 
    $('#nom_id_lote_edit').val(0); 
    document.getElementById('nom_fecha_pago').valueAsDate = new Date(); 
    $('#box_previa_nomina').hide(); 
    $('#btnProcesarNomina').hide(); 
    $('#modalGenerarNomina').modal('show'); 
}

$('#formFiltroNomina').submit(function(e) {
    e.preventDefault();
    let f_i = $('#nom_fecha_inicio').val();
    let f_f = $('#nom_fecha_fin').val();
    let t = parseFloat($('#nom_tasa_bcv').val());
    let id_lote_edit = parseInt($('#nom_id_lote_edit').val() || '0', 10);

    if(f_i > f_f) { Swal.fire('Error', 'Fechas inválidas', 'error'); return; }
    if(t <= 0) { Swal.fire('Atención', 'Ingrese la Tasa BCV para calcular en Bolívares', 'warning'); return; }

    $('#tbody_previa_nomina').html('<tr><td colspan="20" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');
    $('#box_previa_nomina').slideDown();

    $.post('Pagos/api_pagos.php', {
        action: 'generar_previa',
        fecha_inicio: f_i,
        fecha_fin: f_f,
        id_lote_edit: id_lote_edit
    }, function(res) {
        if(res.success) {
            datos_nomina_actual = res.data;
            renderTablaNomina();
        }
    }, 'json');
});

function renderTablaNomina() {
    let html = ''; let cargo_actual = '';
    let t_sue=0, t_ces=0, t_bon=0, t_per=0, t_he=0, t_prod=0, t_pend=0, t_asig=0;
    let t_df=0, t_hf=0, t_pres=0, t_deduc=0, t_neto=0, t_bs=0;
    let tasa = parseFloat($('#nom_tasa_bcv').val()) || 0;
    const valorHoraNomina = 2.88;
    const redondearMonto = valor => Math.round((valor + Number.EPSILON) * 100) / 100;

    datos_nomina_actual.forEach((n, i) => {
        if (n.cargo !== cargo_actual) { 
            html += `<tr><td colspan="20" class="bg-light fw-bold text-start text-dark text-uppercase border-bottom border-top border-2" style="font-size:0.85rem; letter-spacing: 1px;">${n.cargo}</td></tr>`; 
            cargo_actual = n.cargo; 
        }

        let s_q = parseFloat(n.sueldo_quincena) || 0;
        let s_diario = Math.round((s_q / 30 + Number.EPSILON) * 10000) / 10000;
        let s_hora = valorHoraNomina;
        let c_t = parseFloat(n.cesta_ticket) || 0;
        let b_a = parseFloat(n.bono_ayuda) || 0;
        let percibir = redondearMonto(s_q + c_t + b_a);

        let qty_sab = parseFloat(n.qty_sabados) || 0;
        let qty_hed = parseFloat(n.qty_he_diurna) || 0;
        let qty_hen = parseFloat(n.qty_he_noct) || 0;
        let bono_p = parseFloat(n.bono_prod) || 0;
        let pago_p = parseFloat(n.pago_pendiente) || 0;
        let dias_f = parseFloat(n.dias_falta) || 0;
        let horas_f = parseFloat(n.horas_falta) || 0;
        let vales_d = parseFloat(n.vales) || 0;

        let val_sabados = redondearMonto(qty_sab * s_diario);
        let val_he_diurna = redondearMonto(qty_hed * valorHoraNomina);
        let val_he_noct = redondearMonto(qty_hen * valorHoraNomina);
        let total_he_usd = val_sabados + val_he_diurna + val_he_noct;

        let asig_total = redondearMonto(percibir + total_he_usd + bono_p + pago_p);
        
        let desc_dias = redondearMonto(dias_f * s_diario);
        let desc_horas = redondearMonto(horas_f * valorHoraNomina);
        let deduc_total = redondearMonto(desc_dias + desc_horas + vales_d);

        let pagar_usd = redondearMonto(asig_total - deduc_total);
        if(pagar_usd < 0) pagar_usd = 0;
        let pagar_bs = pagar_usd * tasa;

        datos_nomina_actual[i].qty_sabados = qty_sab;
        datos_nomina_actual[i].qty_he_diurna = qty_hed;
        datos_nomina_actual[i].qty_he_noct = qty_hen;
        datos_nomina_actual[i].bono_prod = bono_p;
        datos_nomina_actual[i].pago_pendiente = pago_p;
        datos_nomina_actual[i].dias_falta = dias_f;
        datos_nomina_actual[i].horas_falta = horas_f;
        datos_nomina_actual[i].vales = vales_d;

        datos_nomina_actual[i].total_percibir = percibir;
        datos_nomina_actual[i].salario_diario = s_diario;
        datos_nomina_actual[i].valor_hora = s_hora;
        datos_nomina_actual[i].valor_sabados = val_sabados;
        datos_nomina_actual[i].valor_he_diurna = val_he_diurna;
        datos_nomina_actual[i].valor_he_noct = val_he_noct;
        datos_nomina_actual[i].monto_descuento_dias = desc_dias;
        datos_nomina_actual[i].monto_descuento_horas = desc_horas;
        datos_nomina_actual[i].total_asignaciones = asig_total;
        datos_nomina_actual[i].total_deducciones = deduc_total;
        datos_nomina_actual[i].total_pagar = pagar_usd;

        t_sue+=s_q; t_ces+=c_t; t_bon+=b_a; t_per+=percibir; t_he+=total_he_usd; t_prod+=bono_p; t_pend+=pago_p; t_asig+=asig_total;
        t_df+=desc_dias; t_hf+=desc_horas; t_pres+=vales_d; t_deduc+=deduc_total; t_neto+=pagar_usd; t_bs+=pagar_bs;

        html += `<tr>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-primary rounded shadow-sm" onclick="abrirModalEdicionEmpleado(${i})">
                    <i class="bi bi-pencil-square"></i> Editar
                </button>
            </td>
            <td class="text-start fw-bold text-dark text-nowrap">${n.nombre_completo}<br><small class="text-muted fw-normal">${n.cedula}</small></td>
            <td class="text-center">$${s_q.toFixed(2)}</td>
            <td class="text-center">$${c_t.toFixed(2)}</td>
            <td class="text-center text-warning fw-bold">$${b_a.toFixed(2)}</td>
            <td class="col-ingreso">$${percibir.toFixed(2)}</td>
            <td class="text-center">${qty_sab}</td>
            <td class="text-center">${qty_hed}</td>
            <td class="text-center">${qty_hen}</td>
            <td class="fw-bold" style="color:#0369a1;">$${total_he_usd.toFixed(2)}</td>
            <td class="text-center">$${bono_p.toFixed(2)}</td>
            <td class="fw-bold" style="color:#0284c7;">$${pago_p.toFixed(2)}</td>
            <td class="bg-primary text-white fw-bold">$${asig_total.toFixed(2)}</td>
            <td class="text-center text-danger fw-bold">${dias_f}</td>
            <td class="text-center text-danger fw-bold">${horas_f}</td>
            <td class="col-deduc">-$${desc_dias.toFixed(2)}</td>
            <td class="col-deduc">-$${desc_horas.toFixed(2)}</td>
            <td class="fw-bold" style="color:#9a3412;">-$${vales_d.toFixed(2)}</td>
            <td class="bg-danger text-white fw-bold">-$${deduc_total.toFixed(2)}</td>
            <td class="bg-success text-white fw-bold fs-6">$${pagar_usd.toFixed(2)}</td>
            <td class="bg-dark text-white fw-bold">Bs ${pagar_bs.toFixed(2)}</td>
        </tr>`;
    });
    $('#tbody_previa_nomina').html(html); 
    
    $('#tot_sueldo').text('$'+t_sue.toFixed(2)); $('#tot_cesta').text('$'+t_ces.toFixed(2)); $('#tot_bono').text('$'+t_bon.toFixed(2)); $('#tot_percibir').text('$'+t_per.toFixed(2));
    $('#tot_he').text('$'+t_he.toFixed(2)); $('#tot_prod').text('$'+t_prod.toFixed(2)); $('#tot_pend').text('$'+t_pend.toFixed(2)); $('#tot_asig').text('$'+t_asig.toFixed(2));
    $('#tot_df').text('-$'+t_df.toFixed(2)); $('#tot_hf').text('-$'+t_hf.toFixed(2)); $('#tot_pres').text('-$'+t_pres.toFixed(2)); $('#tot_deduc').text('-$'+t_deduc.toFixed(2));
    $('#tot_neto_usd').text('$'+t_neto.toFixed(2)); $('#tot_neto_bs').text('Bs '+t_bs.toFixed(2));
    $('#btnProcesarNomina').show();
}

function abrirModalEdicionEmpleado(index) {
    indiceEdicionActual = index;
    let emp = datos_nomina_actual[index];

    $('#editNom_nombre').text(emp.nombre_completo);
    $('#editNom_cargo').text(emp.cargo);
    
    $('#editNom_sueldo').val(emp.sueldo_quincena);
    $('#editNom_cesta').val(emp.cesta_ticket);
    $('#editNom_bono').val(emp.bono_ayuda);
    
    $('#editNom_sabados').val(emp.qty_sabados);
    $('#editNom_he_diurna').val(emp.qty_he_diurna);
    $('#editNom_he_noct').val(emp.qty_he_noct);
    $('#editNom_bono_prod').val(emp.bono_prod);
    $('#editNom_pago_pendiente').val(emp.pago_pendiente);
    
    $('#editNom_dias_falta').val(emp.dias_falta);
    $('#editNom_horas_falta').val(emp.horas_falta);
    $('#editNom_vales').val(emp.vales);

    $('#modalEditarValoresNomina').modal('show');
}

function guardarEdicionNomina() {
    if (indiceEdicionActual > -1) {
        let n = datos_nomina_actual[indiceEdicionActual];
        
        n.sueldo_quincena = parseFloat($('#editNom_sueldo').val()) || 0;
        n.cesta_ticket = parseFloat($('#editNom_cesta').val()) || 0;
        n.bono_ayuda = parseFloat($('#editNom_bono').val()) || 0;
        
        n.qty_sabados = parseFloat($('#editNom_sabados').val()) || 0;
        n.qty_he_diurna = parseFloat($('#editNom_he_diurna').val()) || 0;
        n.qty_he_noct = parseFloat($('#editNom_he_noct').val()) || 0;
        n.bono_prod = parseFloat($('#editNom_bono_prod').val()) || 0;
        if ($('#editNom_pago_pendiente').length > 0) {
            n.pago_pendiente = parseFloat($('#editNom_pago_pendiente').val()) || 0;
        }
        
        n.dias_falta = parseFloat($('#editNom_dias_falta').val()) || 0;
        n.horas_falta = parseFloat($('#editNom_horas_falta').val()) || 0;
        if ($('#editNom_vales').length > 0) {
            n.vales = parseFloat($('#editNom_vales').val()) || 0;
        }

        $('#modalEditarValoresNomina').modal('hide');
        renderTablaNomina(); 
        Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Valores actualizados', showConfirmButton: false, timer: 1000});
    }
}

function procesarNominaDefinitiva() { 
    Swal.fire({ title: '¿Guardar Nómina?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, Guardar' }).then((r) => { 
        if (r.isConfirmed) { 
            Swal.fire({ title: 'Procesando y guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            $.post('Pagos/api_pagos.php', { 
                action: 'procesar_nomina', 
                id_lote_edit: $('#nom_id_lote_edit').val(), 
                fecha_inicio: $('#nom_fecha_inicio').val(), 
                fecha_fin: $('#nom_fecha_fin').val(), 
                fecha_pago: $('#nom_fecha_pago').val(), 
                tasa_bcv: $('#nom_tasa_bcv').val(), 
                datos_nomina: JSON.stringify(datos_nomina_actual) 
            }, function(res) { 
                if(res.success) { 
                    Swal.fire('Nómina Procesada', 'Se ha guardado el listín con éxito', 'success'); 
                    $('#modalGenerarNomina').modal('hide'); 
                    cargarHistorialPagos(); 
                } else {
                    Swal.fire('Error del Servidor', res.msg, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Fallo de conexión al intentar guardar la nómina.', 'error');
            }); 
        } 
    }); 
}

function editarLote(id_lote) {
    $('#formFiltroNomina')[0].reset();
    $('#nom_id_lote_edit').val(id_lote);
    $('#box_previa_nomina').hide();
    $('#btnProcesarNomina').hide();

    Swal.fire({ title: 'Cargando Nómina...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    $.post('Pagos/api_pagos.php', { action: 'cargar_lote_editar', id_lote: id_lote }, function(res) {
        Swal.close();
        if(res.success) {
            $('#nom_fecha_inicio').val(res.lote.fecha_inicio);
            $('#nom_fecha_fin').val(res.lote.fecha_fin);
            $('#nom_fecha_pago').val(res.lote.fecha_pago);
            $('#nom_tasa_bcv').val(parseFloat(res.lote.tasa_bcv).toFixed(2));

            datos_nomina_actual = res.data;
            $('#box_previa_nomina').slideDown();
            renderTablaNomina();
            $('#modalGenerarNomina').modal('show');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json');
}

function cargarHistorialPagos() {
    $.post('Pagos/api_pagos.php', { action: 'listar_historial_lotes' }, function(res) {
        if(res.success) {
            let htmlPendientes = '';
            let htmlArchivo = '';

            res.data.forEach(l => {
                let isFinalizada = (l.estado === 'Finalizada');
                let badgeEstado = isFinalizada ? 
                    `<span class="badge bg-success mb-1 d-block"><i class="bi bi-check2-all"></i> Finalizada</span>` : 
                    `<span class="badge bg-warning text-dark mb-1 d-block"><i class="bi bi-clock"></i> Pendiente</span>`;

                let btnFinalizar = isFinalizada ? '' : `
                    <button class="btn btn-sm btn-warning mb-1 rounded-pill" onclick="editarLote(${l.id})"><i class="bi bi-pencil"></i> Editar</button>
                    <button class="btn btn-sm btn-success mb-1 rounded-pill" onclick="finalizarLote(${l.id})"><i class="bi bi-check-circle"></i> Finalizar</button>
                `;

                let row = `<tr>
                    <td class="fw-bold text-center">#${l.id}</td>
                    <td class="fw-bold"><i class="bi bi-calendar-check"></i> ${l.fecha_pago_fmt}</td>
                    <td><span class="badge bg-light text-dark border">${l.periodo_fmt}</span></td>
                    <td class="text-end fw-bold text-success">$${parseFloat(l.total_usd).toFixed(2)}</td>
                    <td class="text-end fw-bold text-primary">Bs ${parseFloat(l.total_bs).toFixed(2)}</td>
                    <td class="text-center">${badgeEstado}</td>
                    <td class="text-center"><button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="abrirModalSubidaIndividual(${l.id})"><i class="bi bi-camera"></i> Subir Recibos</button></td>
                    <td class="text-center">
                        <div class="btn-group-vertical w-100">
                            <button class="btn btn-sm btn-dark mb-1 rounded-pill" onclick="imprimirListinGuardado('${l.fecha_pago}')"><i class="bi bi-list-columns-reverse"></i> Nómina Gral</button>
                            <button class="btn btn-sm btn-outline-dark mb-1 rounded-pill" onclick="imprimirRecibosLote(${l.id})"><i class="bi bi-file-person"></i> Recibos</button>
                            ${btnFinalizar}
                            <button class="btn btn-sm btn-danger mt-1 rounded-pill" onclick="eliminarLote(${l.id})"><i class="bi bi-trash"></i> Eliminar</button>
                        </div>
                    </td>
                </tr>`;

                if(isFinalizada) htmlArchivo += row; else htmlPendientes += row;
            });

            if(htmlPendientes === '') htmlPendientes = '<tr><td colspan="8" class="text-center text-muted py-4">No hay nóminas pendientes.</td></tr>';
            if(htmlArchivo === '') htmlArchivo = '<tr><td colspan="8" class="text-center text-muted py-4">El archivo está vacío.</td></tr>';

            $('#tablaPagosPendientesBody').html(htmlPendientes);
            $('#tablaPagosArchivoBody').html(htmlArchivo);
        }
    }, 'json');
}

function finalizarLote(id_lote) { Swal.fire({ title: '¿Mover al Archivo Histórico?', text: "La nómina pasará a estado Finalizada.", icon: 'info', showCancelButton: true, confirmButtonText: 'Sí, Archivar' }).then((r) => { if (r.isConfirmed) { $.post('Pagos/api_pagos.php', { action: 'finalizar_lote', id_lote: id_lote }, function(res) { if(res.success) { cargarHistorialPagos(); Swal.fire('Archivada', '', 'success'); } }); } }); }
function eliminarLote(id_lote) { Swal.fire({ title: '¿Eliminar Nómina?', text: 'Los vales y deudas procesados en este lote volverán a quedar Pendientes.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, Eliminar' }).then((result) => { if (result.isConfirmed) { $.post('Pagos/api_pagos.php', { action: 'eliminar_lote', id_lote: id_lote }, function(res) { if (res.success) { Swal.fire('¡Eliminado!', '', 'success'); cargarHistorialPagos(); } }, 'json'); } }); }

function imprimirListinGuardado(fecha_pago) { window.open('Pagos/imprimir_nomina.php?fecha=' + fecha_pago, '_blank'); }
function imprimirRecibosLote(id_lote) { window.open('Pagos/imprimir_todos_recibos.php?id_lote=' + id_lote, '_blank'); }

function abrirModalSubidaIndividual(id_lote) { 
    id_lote_actual_subida = id_lote; 
    $('#lbl_lote_subida').text(id_lote); 
    cargarTablaSubidaIndividual(id_lote); 
    $('#modalSubidaIndividual').modal('show'); 
}

function cargarTablaSubidaIndividual(id_lote) {
    $('#tbody_subida_individual').html('<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>');
    $.post('Pagos/api_pagos.php', { action: 'listar_detalles_lote', id_lote: id_lote }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(p => {
                let btnSubir = `<button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="seleccionarFotoReciboIndiv(${p.id})"><i class="bi bi-upload"></i> Adjuntar</button>`;
                let estadoAval = `<span class="badge bg-warning text-dark">Sin Archivo</span>`;
                
                if(p.comprobante_foto && p.comprobante_foto.trim() !== '') {
                    let fotos = p.comprobante_foto.split(',');
                    let totalFotos = fotos.length;
                    
                    estadoAval = `<span class="badge bg-success shadow-sm" style="cursor:pointer;" onclick="seleccionarFotoReciboIndiv(${p.id})" title="Click para añadir otro comprobante"><i class="bi bi-upload"></i> ${totalFotos} Cargado(s)</span>`;
                    
                    btnSubir = `<button class="btn btn-sm btn-success rounded-pill px-3 me-1" onclick="abrirModalVerComprobantes('${p.comprobante_foto}')"><i class="bi bi-eye"></i> Ver Recibos</button>`;
                    btnSubir += `<button class="btn btn-sm btn-outline-primary rounded-pill px-2 me-1" onclick="seleccionarFotoReciboIndiv(${p.id})" title="Añadir otro recibo"><i class="bi bi-plus-lg"></i></button>`;
                    btnSubir += `<button class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="limpiarComprobantes(${p.id})" title="Borrar todos los recibos"><i class="bi bi-trash"></i></button>`;
                }
                
                html += `<tr><td class="fw-bold">${p.nombre} ${p.apellido} <br><small class="text-muted">${p.cedula}</small></td><td class="text-end fw-bold text-success">$${parseFloat(p.total_pagado).toFixed(2)}</td><td class="text-center align-middle">${estadoAval}</td><td class="text-center align-middle text-nowrap">${btnSubir}</td></tr>`;
            });
            $('#tbody_subida_individual').html(html);
        }
    }, 'json');
}

function abrirModalVerComprobantes(nombres_fotos) {
    let fotos = nombres_fotos.split(',');
    let html = '';
    fotos.forEach((foto, index) => {
        let ext = foto.split('.').pop().toLowerCase();
        html += `<div class="card mb-3 shadow-sm border-0">
                    <div class="card-header bg-secondary text-white fw-bold py-1">Recibo de Pago ${index + 1}</div>
                    <div class="card-body p-2">`;
        if(ext === 'pdf') {
            html += `<iframe src="../uploads/${foto}" width="100%" height="400px" style="border:none;"></iframe>
                     <a href="../uploads/${foto}" target="_blank" class="btn btn-sm btn-primary mt-2">Abrir PDF en otra pestaña</a>`;
        } else {
            html += `<img src="../uploads/${foto}" class="img-fluid rounded" style="max-height: 500px; object-fit: contain;">
                     <br><a href="../uploads/${foto}" target="_blank" class="btn btn-sm btn-primary mt-3">Ver imagen completa</a>`;
        }
        html += `   </div>
                 </div>`;
    });
    $('#contenedor_comprobantes').html(html);
    $('#modalVerComprobantes').modal('show');
}

function seleccionarFotoReciboIndiv(id_pago) { id_pago_seleccionado_foto = id_pago; $('#input_foto_recibo_indiv').click(); }

$('#input_foto_recibo_indiv').change(function() {
    if(this.files.length > 0) {
        let formData = new FormData(); 
        formData.append('action', 'subir_comprobante'); formData.append('id_pago', id_pago_seleccionado_foto); 
        for(let i = 0; i < this.files.length; i++) { formData.append('comprobantes[]', this.files[i]); }
        Swal.fire({ title: 'Cargando documento(s)...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
        $.ajax({ 
            url: 'Pagos/api_pagos.php', type: 'POST', data: formData, contentType: false, processData: false, dataType: 'json',
            success: function(res) { 
                if(res.success) { Swal.fire({icon: 'success', title: 'Guardado', timer: 1000, showConfirmButton: false}); cargarTablaSubidaIndividual(id_lote_actual_subida); }
                else { Swal.fire('Error', res.msg, 'error'); }
                $('#input_foto_recibo_indiv').val(''); 
            }
        });
    }
});

function limpiarComprobantes(id_pago) { Swal.fire({ title: '¿Borrar comprobantes?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sí, borrar' }).then((result) => { if (result.isConfirmed) { $.post('Pagos/api_pagos.php', { action: 'limpiar_comprobantes', id_pago: id_pago }, function(res) { if(res.success) { cargarTablaSubidaIndividual(id_lote_actual_subida); } }, 'json'); } }); }

// ============================ PRESTACIONES Y LIQUIDACIONES ==============================
function toggleArchivadosPrestaciones() {
    viendoArchivadosPrestaciones = !viendoArchivadosPrestaciones;
    toggleInterfacePrestaciones();
    cargarHistorialPrestaciones();
}

function cargarHistorialPrestaciones() {
    let action = viendoArchivadosPrestaciones ? 'listar_archivados' : 'listar_historial';
    $.post('Prestaciones/api_prestaciones.php', { action: action }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(pr => {
                let badge = pr.estado === 'Archivado' ? `<span class="badge bg-secondary">${pr.estado}</span>` : `<span class="badge bg-success"><i class="bi bi-check-circle"></i> ${pr.estado}</span>`;
                let btnImprimir = `<button class="btn btn-sm btn-outline-dark rounded-pill me-1" onclick="imprimirLiquidacion(${pr.id})"><i class="bi bi-printer"></i></button>`;
                let btnEditar = pr.estado === 'Archivado' ? '' : `<button class="btn btn-sm btn-info text-white rounded-pill me-1" onclick="editarLiquidacion(${pr.id})"><i class="bi bi-pencil"></i></button>`;
                let btnArchivar = pr.estado === 'Archivado' ? `<button class="btn btn-sm btn-outline-success rounded-pill me-1" onclick="desarchivarLiquidacion(${pr.id})"><i class="bi bi-arrow-up-circle"></i></button>` : `<button class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="archivarLiquidacion(${pr.id})"><i class="bi bi-archive"></i></button>`;
                let btnEliminar = pr.estado === 'Archivado' ? '' : `<button class="btn btn-sm btn-outline-danger rounded-pill" onclick="eliminarLiquidacion(${pr.id})"><i class="bi bi-trash"></i></button>`;
                
                html += `<tr><td class="fw-bold"><i class="bi bi-calendar2-x text-danger"></i> ${pr.fecha_fmt}</td><td class="fw-bold text-dark">${pr.nombre} ${pr.apellido} <br><small class="text-muted">${pr.cedula}</small></td><td><span class="badge bg-secondary">${pr.motivo}</span></td><td class="text-center fw-bold">${pr.dias_acumulados} Días</td><td class="text-end text-primary fw-bold">$${parseFloat(pr.salario_promedio).toFixed(2)}</td><td class="text-end text-danger fw-bold fs-6">$${parseFloat(pr.monto_total).toFixed(2)}</td><td class="text-center">${badge}</td><td class="text-center text-nowrap">${btnImprimir}${btnEditar}${btnArchivar}${btnEliminar}</td></tr>`;
            });
            if(html === '') html = `<tr><td colspan="8" class="text-center text-muted py-4">No hay liquidaciones ${viendoArchivadosPrestaciones ? 'archivadas' : 'registradas'}.</td></tr>`;
            $('#tablaPrestacionesBody').html(html);
        }
    }, 'json');
}

function abrirModalLiquidacion() { $('#formSimularLiquidacion')[0].reset(); document.getElementById('liq_fecha_egreso').valueAsDate = new Date(); $('#liq_resultados').slideUp(); $('#modalLiquidacion').modal('show'); }

$('#formSimularLiquidacion').submit(function(e) {
    e.preventDefault();
    let id = $('#liq_id_empleado').val(); let fecha_ingreso = $('#liq_fecha_ingreso').val(); let fecha_egreso = $('#liq_fecha_egreso').val(); let motivo = $('#liq_motivo').val();
    if(!id || !fecha_ingreso || !fecha_egreso) { Swal.fire('Atención', 'Seleccione un empleado y verifique las fechas', 'warning'); return; }
    Swal.fire({ title: 'Calculando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    $.ajax({ url: 'Prestaciones/api_prestaciones.php', type: 'POST', data: { action: 'simular_liquidacion', id_empleado: id, fecha_ingreso: fecha_ingreso, fecha_egreso: fecha_egreso, motivo: motivo }, dataType: 'text',
        success: function(responseRaw) {
            Swal.close();
            try {
                let res = JSON.parse(responseRaw);
                if(res.success) {
                    datos_liquidacion_actual = res.data; let d = res.data;
                    $('#res_tiempo').text(d.tiempo_servicio); $('#res_salario_mensual').text('$' + parseFloat(d.salario_mensual).toFixed(2));$('#res_salario_integral').text('$' + parseFloat(d.salario_integral_diario).toFixed(2));$('#res_dias_cal').text(d.dias_calendario); $('#res_dias_falta').text(d.dias_falta); $('#res_dias_efectivos').text(d.dias_efectivos);
                    $('#res_dias_prest').text(d.dias_prestaciones); $('#res_monto_prest').text('+$' + parseFloat(d.monto_prestaciones).toFixed(2)); $('#res_vac_frac').text('+$' + parseFloat(d.vacaciones_fraccionadas).toFixed(2)); $('#res_bvac_frac').text('+$' + parseFloat(d.bono_vacacional_fraccionado).toFixed(2)); $('#res_uti_frac').text('+$' + parseFloat(d.utilidades_fraccionadas).toFixed(2));
                    if(d.indemnizacion_despido > 0) { $('#fila_indemnizacion').show(); $('#res_indemnizacion').text('+$' + parseFloat(d.indemnizacion_despido).toFixed(2)); } else {$('#fila_indemnizacion').hide();}
                    $('#res_deuda').text('-$' + parseFloat(d.deducciones_pendientes).toFixed(2));$('#res_total_liquidar').text('$' + parseFloat(d.total_liquidar).toFixed(2));$('#liq_resultados').slideDown();
                } else { Swal.fire('Atención', res.msg, 'warning'); }
            } catch (e) { Swal.fire({ title: 'Error', html: '<textarea class="form-control" rows="6" readonly>' + responseRaw + '</textarea>', icon: 'error' }); }
        }
    });
});

function guardarLiquidacionDefinitiva() { Swal.fire({ title: '¿Confirmar Liquidación?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, Ejecutar' }).then((r) => { if (r.isConfirmed) { $.post('Prestaciones/api_prestaciones.php', { action: 'guardar_liquidacion', id_empleado: $('#liq_id_empleado').val(), fecha_egreso: $('#liq_fecha_egreso').val(), motivo: $('#liq_motivo').val(), datos_json: JSON.stringify(datos_liquidacion_actual) }, function(res) { if(res.success) { Swal.fire('Completado', '', 'success'); $('#modalLiquidacion').modal('hide'); cargarHistorialPrestaciones(); cargarEmpleados(); } else { Swal.fire('Error', res.msg, 'error'); } }, 'json'); } }); }
function archivarLiquidacion(id) { Swal.fire({ title: '¿Archivar liquidación?', icon: 'info', showCancelButton: true, confirmButtonText: 'Sí, Archivar' }).then((r) => { if(r.isConfirmed) { $.post('Prestaciones/api_prestaciones.php', {action: 'archivar', id_prestacion: id}, function(res) { if(res.success) { cargarHistorialPrestaciones(); } }, 'json'); } }); }
function desarchivarLiquidacion(id) { $.post('Prestaciones/api_prestaciones.php', {action: 'desarchivar', id_prestacion: id}, function(res) { if(res.success) { cargarHistorialPrestaciones(); } }, 'json'); }
function eliminarLiquidacion(id) { Swal.fire({ title: '¿Eliminar y Revertir?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, Eliminar' }).then((r) => { if(r.isConfirmed) { $.post('Prestaciones/api_prestaciones.php', {action: 'eliminar', id_prestacion: id}, function(res) { if(res.success) { cargarHistorialPrestaciones(); cargarEmpleados(); } }, 'json'); } }); }
function editarLiquidacion(id) { Swal.fire({ title: '¿Editar Liquidación?', text: 'Para editar debes eliminarla primero y volverla a crear. ¿Eliminarla ahora?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, Eliminar' }).then((result) => { if(result.isConfirmed) { eliminarLiquidacion(id); } }) }
function imprimirLiquidacion(id) { window.open('Prestaciones/imprimir_liquidacion.php?id=' + id, '_blank'); }

// ============================ VALES, DEUDAS DE NÓMINA Y PRÉSTAMOS ==============================
function cargarVales() {
    $.post('Prestamos/api_prestamos.php', { action: 'listar' }, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(v => {
                let esPendiente = (v.estado === 'Pendiente');
                let badge = esPendiente
                    ? `<span class="badge bg-warning text-dark" style="cursor:pointer;" onclick="cambiarEstadoCuentaNomina(${v.id}, 'Procesado')" title="Click para marcar como Procesado"><i class="bi bi-hourglass-split"></i> Por Descontar</span>`
                    : `<span class="badge bg-success" style="cursor:pointer;" onclick="cambiarEstadoCuentaNomina(${v.id}, 'Pendiente')" title="Click para reactivar como Por Descontar en la próxima nómina"><i class="bi bi-check-circle"></i> Procesado</span>`;

                let btnEliminar = `<button class="btn btn-sm btn-outline-danger rounded-pill" onclick="eliminarVale(${v.id})" title="Eliminar"><i class="bi bi-trash"></i></button>`;
                let btnArchivar = !esPendiente
                    ? `<button class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="archivarVale(${v.id})" title="Archivar (Ocultar de la lista)"><i class="bi bi-archive"></i></button>`
                    : '';
                let cedula_limpia = "V-" + v.cedula.replace(/^[vejgcVEJGC]\s*-\s*/, '').trim().toUpperCase();

                html += `<tr>
                    <td class="fw-bold">${v.fecha_fmt}</td>
                    <td><div class="fw-bold text-dark">${v.nombre} ${v.apellido}</div><small class="text-muted">${cedula_limpia}</small></td>
                    <td>${v.concepto}</td>
                    <td class="text-end fw-bold text-danger">-${v.monto_mostrar}</td>
                    <td class="text-center">${badge}</td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-info text-white me-1 rounded-pill" onclick="editarVale(${v.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-dark rounded-pill me-1" onclick="imprimirVale(${v.id})" title="Imprimir"><i class="bi bi-printer"></i></button>
                        ${btnArchivar}${btnEliminar}
                    </td>
                </tr>`;
            });
            if(html === '') html = '<tr><td colspan="6" class="text-center text-muted py-4">No hay vales registrados.</td></tr>';
            $('#tablaValesBody').html(html);
        }
    }, 'json');
}

function cambiarEstadoCuentaNomina(id_cuenta, nuevoEstado) {
    let textoAccion = nuevoEstado === 'Pendiente'
        ? 'Pasará a estado PENDIENTE para ser tomado en cuenta automáticamente al generar la nómina.'
        : 'Pasará a estado PROCESADO y ya no se sumará/restará en próximas nóminas.';
    Swal.fire({
        title: `¿Cambiar a ${nuevoEstado}?`,
        text: textoAccion,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((r) => {
        if (r.isConfirmed) {
            $.post('Prestamos/api_prestamos.php', { action: 'cambiar_estado_cuenta', id_cuenta: id_cuenta, estado: nuevoEstado }, function(res) {
                if (res.success) {
                    cargarVales();
                    cargarDeudasNomina();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Estado actualizado', showConfirmButton: false, timer: 1400 });
                } else {
                    Swal.fire('Error', res.msg || 'No se pudo cambiar el estado.', 'error');
                }
            }, 'json');
        }
    });
}

function abrirModalVale() { $('#formNuevoVale')[0].reset(); $('#vale_id').val('0'); document.getElementById('vale_fecha').valueAsDate = new Date(); $('#vale_equivalencia').html('Ingrese Tasa BCV y Monto para ver la equivalencia'); $('#modalNuevoVale').modal('show'); }

function editarVale(id) {
    $.post('Prestamos/api_prestamos.php', { action: 'obtener', id_vale: id }, function(response) {
        let res = typeof response === 'string' ? JSON.parse(response) : response;
        if (res.success) {
            let v = res.data; $('#vale_id').val(v.id); $('#vale_id_empleado').val(v.id_empleado); $('#vale_fecha').val(v.fecha); $('#vale_moneda').val(v.moneda); $('#vale_tasa').val(v.tasa_bcv); $('#vale_monto').val(v.monto_original > 0 ? v.monto_original : v.monto); $('#vale_concepto').val(v.concepto);
            if (typeof calcularEquivalenteVale === 'function') calcularEquivalenteVale();
            $('#modalNuevoVale').modal('show');
        } else { Swal.fire('No se puede editar', res.msg || 'No se encontró el vale.', 'error'); }
    }, 'json');
}

function calcularEquivalenteVale() {
    let moneda = $('#vale_moneda').val(); let tasa = parseFloat($('#vale_tasa').val()) || 0; let monto = parseFloat($('#vale_monto').val()) || 0;
    if(tasa > 0 && monto > 0) {
        if(moneda === 'BS') { let usd = monto / tasa; $('#vale_equivalencia').html('Descuento en Nómina: <span class="text-danger">$' + usd.toFixed(2) + ' USD</span>'); } 
        else { let bs = monto * tasa; $('#vale_equivalencia').html('Entregado al trabajador: <span class="text-primary">Bs ' + bs.toFixed(2) + '</span>'); }
    } else { $('#vale_equivalencia').html('Ingrese Tasa BCV y Monto para ver la equivalencia'); }
}

$('#formNuevoVale').submit(function(e) {
    e.preventDefault(); Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
    $.post('Prestamos/api_prestamos.php', $(this).serialize(), function(res) {
        if(res.success) {
            $('#modalNuevoVale').modal('hide'); cargarVales();
            Swal.fire({ title: '¡Vale Registrado!', icon: 'success', showCancelButton: true, confirmButtonText: '<i class="bi bi-printer"></i> Imprimir', cancelButtonText: 'Cerrar' }).then((r) => { if(r.isConfirmed) imprimirVale(res.id_vale); });
        } else { Swal.fire('Error', res.msg, 'error'); }
    }, 'json');
});

function archivarVale(id) { $.post('Prestamos/api_prestamos.php', {action: 'archivar', id_cuenta: id}, function(res) { if(res.success) { cargarVales(); cargarDeudasNomina(); } }, 'json'); }
function eliminarVale(id) { Swal.fire({ title: '¿Eliminar registro?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, Eliminar' }).then((r) => { if(r.isConfirmed) { $.post('Prestamos/api_prestamos.php', {action: 'eliminar', id_cuenta: id}, function(res) { if(res.success) { cargarVales(); cargarDeudasNomina(); } else Swal.fire('No se pudo eliminar', res.msg || 'Error al eliminar.', 'error'); }, 'json'); } }); }
function imprimirVale(id) { window.open('Prestamos/imprimir_vale.php?id=' + id, '_blank'); }

// ============================ NUEVO APARTADO: DEUDAS DE NÓMINA A TRABAJADOR (EN USD) ==============================
function cargarDeudasNomina() {
    if ($('#tablaDeudasNominaBody').length === 0) return;
    $('#tablaDeudasNominaBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Cargando deudas de nómina...</td></tr>');

    $.post('Prestamos/api_prestamos.php', { action: 'listar_deudas_nomina' }, function(res) {
        if (!res.success) {
            $('#tablaDeudasNominaBody').html(`<tr><td colspan="6" class="text-center text-danger py-4">${textoSeguroNomina(res.msg || 'Error al cargar deudas.')}</td></tr>`);
            return;
        }
        let html = '';
        res.data.forEach(d => {
            let esPendiente = (d.estado === 'Pendiente');
            let badge = esPendiente
                ? `<span class="badge bg-warning text-dark" style="cursor:pointer;" onclick="cambiarEstadoCuentaNomina(${d.id}, 'Procesado')" title="Click para marcar como Pagado/Procesado"><i class="bi bi-hourglass-split"></i> Por Pagar en Nómina</span>`
                : `<span class="badge bg-success" style="cursor:pointer;" onclick="cambiarEstadoCuentaNomina(${d.id}, 'Pendiente')" title="Click para volver a poner Pendiente de Pago"><i class="bi bi-check-circle"></i> Pagado en Nómina</span>`;

            let btnArchivar = !esPendiente
                ? `<button class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="archivarVale(${d.id})" title="Archivar"><i class="bi bi-archive"></i></button>`
                : '';

            html += `<tr>
                <td class="fw-bold">${textoSeguroNomina(d.fecha_fmt)}</td>
                <td>
                    <div class="fw-bold text-dark">${textoSeguroNomina(d.nombre)} ${textoSeguroNomina(d.apellido)}</div>
                    <small class="text-muted">${textoSeguroNomina(d.cedula)} · ${textoSeguroNomina(d.cargo || '')}</small>
                </td>
                <td>${textoSeguroNomina(d.concepto)}</td>
                <td class="text-end fw-bold text-success fs-6">+${textoSeguroNomina(d.monto_usd_fmt)}</td>
                <td class="text-center">${badge}</td>
                <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-info text-white me-1 rounded-pill" onclick="editarDeudaNomina(${d.id})" title="Editar Deuda"><i class="bi bi-pencil"></i></button>
                    ${btnArchivar}
                    <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="eliminarVale(${d.id})" title="Eliminar Deuda"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`;
        });
        if (html === '') {
            html = '<tr><td colspan="6" class="text-center text-muted py-4">No hay deudas pendientes registradas con el personal.</td></tr>';
        }
        $('#tablaDeudasNominaBody').html(html);
    }, 'json').fail(function() {
        $('#tablaDeudasNominaBody').html('<tr><td colspan="6" class="text-center text-danger py-4">Error de conexión al cargar las deudas de nómina.</td></tr>');
    });
}

function abrirModalDeudaNomina() {
    $('#formDeudaNomina')[0].reset();
    $('#deuda_nomina_id').val('0');
    document.getElementById('deuda_fecha').valueAsDate = new Date();
    $('#modalDeudaNomina').modal('show');
}

function editarDeudaNomina(id) {
    $.post('Prestamos/api_prestamos.php', { action: 'obtener', id_vale: id }, function(res) {
        if (res.success) {
            let d = res.data;
            $('#formDeudaNomina')[0].reset();
            $('#deuda_nomina_id').val(d.id);
            $('#deuda_id_empleado').val(d.id_empleado);
            $('#deuda_concepto').val(d.concepto);
            $('#deuda_fecha').val(d.fecha);
            $('#deuda_monto').val(parseFloat(d.monto).toFixed(2));
            $('#modalDeudaNomina').modal('show');
        } else {
            Swal.fire('Error', res.msg || 'No se encontró la deuda.', 'error');
        }
    }, 'json');
}

$(document).on('submit', '#formDeudaNomina', function(e) {
    e.preventDefault();
    let $form = $(this);$form.find('button[type="submit"]').prop('disabled', true);
    $.post('Prestamos/api_prestamos.php', $form.serialize(), function(res) {
        if (res.success) {
            $('#modalDeudaNomina').modal('hide');
            cargarDeudasNomina();
            Swal.fire({
                icon: 'success',
                title: '¡Deuda Registrada!',
                text: 'Se sumará automáticamente en dólares a favor del trabajador al generar la nómina.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Atención', res.msg || 'No se pudo guardar la deuda.', 'warning');
        }
    }, 'json').fail(function() {
        Swal.fire('Error', 'Fallo de conexión con el servidor.', 'error');
    }).always(function() {
        $form.find('button[type="submit"]').prop('disabled', false);
    });
});

function textoSeguroNomina(valor) {
    return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function cargarPrestamosVendedores() {
    $('#tablaPrestamosVendedoresBody').html('<tr><td colspan="8" class="text-center text-muted py-4">Cargando préstamos...</td></tr>');
    $.post('Prestamos/api_prestamos.php', {action: 'listar_prestamos_vendedores'}, function(res) {
        if (!res.success) {
            $('#tablaPrestamosVendedoresBody').html(`<tr><td colspan="8" class="text-center text-danger py-4">${textoSeguroNomina(res.msg || 'No se pudieron cargar los préstamos.')}</td></tr>`);
            return;
        }
        let html = '';
        res.data.forEach(p => {
            let nombre = textoSeguroNomina(`${p.nombre || ''} ${p.apellido || ''}`.trim());
            let cedula = textoSeguroNomina(p.cedula || '');
            let concepto = textoSeguroNomina(p.concepto || '');
            let estado = p.estado === 'Pendiente'
                ? '<span class="badge bg-warning text-dark">Deuda pendiente</span>'
                : '<span class="badge bg-success">Solvente</span>';
            let tieneAbonos = parseFloat(p.total_abonado) > 0;
            let btnEditar = p.estado === 'Pendiente' && !tieneAbonos
                ? `<button class="btn btn-sm btn-info text-white me-1 rounded-pill" onclick="editarPrestamoVendedor(${p.id})" title="Editar"><i class="bi bi-pencil"></i></button>`
                : `<button class="btn btn-sm btn-info text-white me-1 rounded-pill" disabled title="No se puede editar después de aplicar abonos"><i class="bi bi-pencil"></i></button>`;
            let btnEliminar = tieneAbonos
                ? `<button class="btn btn-sm btn-outline-danger rounded-pill" disabled title="No se puede eliminar un préstamo con abonos"><i class="bi bi-trash"></i></button>`
                : `<button class="btn btn-sm btn-outline-danger rounded-pill" onclick="eliminarPrestamoVendedor(${p.id})" title="Eliminar"><i class="bi bi-trash"></i></button>`;
            let btnArchivar = p.estado === 'Procesado'
                ? `<button class="btn btn-sm btn-outline-secondary rounded-pill me-1" onclick="archivarPrestamoVendedor(${p.id})" title="Archivar préstamo pagado"><i class="bi bi-archive"></i></button>`
                : '';

            html += `<tr>
                <td class="fw-bold">${textoSeguroNomina(p.fecha_fmt)}</td>
                <td><div class="fw-bold text-dark">${nombre}</div><small class="text-muted">${cedula} · ${textoSeguroNomina(p.rol)}</small></td>
                <td>${concepto}</td>
                <td class="text-end fw-bold">${textoSeguroNomina(p.monto_original_fmt)}</td>
                <td class="text-end text-success fw-bold">$${textoSeguroNomina(p.total_abonado_fmt)}</td>
                <td class="text-end fw-bold ${parseFloat(p.saldo_pendiente) > 0 ? 'text-danger' : 'text-success'}">$${textoSeguroNomina(p.saldo_pendiente_fmt)}</td>
                <td class="text-center">${estado}</td>
                <td class="text-center text-nowrap">${btnEditar}${btnArchivar}${btnEliminar}</td>
            </tr>`;
        });
        if (!html) html = '<tr><td colspan="8" class="text-center text-muted py-4">No hay préstamos registrados para vendedores.</td></tr>';
        $('#tablaPrestamosVendedoresBody').html(html);
    }, 'json').fail(function() {
        $('#tablaPrestamosVendedoresBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error de conexión al cargar préstamos de vendedores.</td></tr>');
    });
}

function cargarOpcionesVendedores(idSeleccionado = 0) {
    return $.post('Prestamos/api_prestamos.php', {action: 'listar_vendedores'}, function(res) {
        if (!res.success) {
            $('#prestamo_id_vendedor').html(`<option value="">${textoSeguroNomina(res.msg || 'No se pudieron cargar vendedores')}</option>`);
            return;
        }
        let html = '<option value="">Seleccione vendedor...</option>';
        res.data.forEach(v => {
            let nombre = textoSeguroNomina(`${v.nombre || ''} ${v.apellido || ''}`.trim());
            let cedula = textoSeguroNomina(v.cedula || '');
            let rol = v.rol === 'jefe_ventas' ? 'Jefe de Ventas' : 'Vendedor';
            let selected = Number(idSeleccionado) === Number(v.id) ? 'selected' : '';
            html += `<option value="${Number(v.id)}" ${selected}>${nombre} (${cedula}) — ${rol}</option>`;
        });
        $('#prestamo_id_vendedor').html(html);
    }, 'json').fail(function() {
        $('#prestamo_id_vendedor').html('<option value="">Error cargando vendedores del Portal</option>');
    });
}

function abrirModalPrestamoVendedor() {
    $('#formPrestamoVendedor')[0].reset();
    $('#prestamo_vendedor_id').val('0');
    document.getElementById('prestamo_vendedor_fecha').valueAsDate = new Date();
    $('#prestamo_vendedor_moneda').val('USD');
    $('#prestamo_vendedor_tasa').val('0');
    $('#prestamo_vendedor_equivalencia').text('El saldo de la deuda se registra en dólares y se descontará de futuras comisiones.');
    cargarOpcionesVendedores();
    $('#modalPrestamoVendedor').modal('show');
}

function editarPrestamoVendedor(id) {
    $.post('Prestamos/api_prestamos.php', {action: 'obtener_prestamo_vendedor', id_prestamo: id}, function(res) {
        if (!res.success) {
            Swal.fire('No se pudo cargar', res.msg || 'No se encontró el préstamo.', 'error');
            return;
        }
        let p = res.data;
        if (p.estado !== 'Pendiente') {
            Swal.fire('Préstamo no editable', 'Este préstamo ya tiene abonos o fue liquidado.', 'info');
            return;
        }
        $('#formPrestamoVendedor')[0].reset();
        $('#prestamo_vendedor_id').val(p.id);
        $('#prestamo_vendedor_concepto').val(p.concepto);
        $('#prestamo_vendedor_fecha').val(p.fecha);
        $('#prestamo_vendedor_moneda').val(p.moneda);
        $('#prestamo_vendedor_monto').val(p.monto_original);
        $('#prestamo_vendedor_tasa').val(p.tasa_bcv);
        cargarOpcionesVendedores(p.id_vendedor).done(function() {
            calcularEquivalentePrestamoVendedor();
            $('#modalPrestamoVendedor').modal('show');
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'No se pudo consultar el préstamo.', 'error');
    });
}

function calcularEquivalentePrestamoVendedor() {
    let moneda = $('#prestamo_vendedor_moneda').val();
    let monto = parseFloat($('#prestamo_vendedor_monto').val()) || 0;
    let tasa = parseFloat($('#prestamo_vendedor_tasa').val()) || 0;
    let usd = moneda === 'BS' ? (tasa > 0 ? monto / tasa : 0) : monto;
    if (usd > 0) {
        $('#prestamo_vendedor_equivalencia').html(`Saldo a descontar de comisiones: <span class="text-danger">$${usd.toFixed(2)} USD</span>`);
    } else {
        $('#prestamo_vendedor_equivalencia').text('El saldo de la deuda se registra en dólares y se descontará de futuras comisiones.');
    }
}

$('#formPrestamoVendedor').on('submit', function(e) {
    e.preventDefault();
    let $form = $(this);$form.find('button[type="submit"]').prop('disabled', true);
    $.post('Prestamos/api_prestamos.php', $form.serialize(), function(res) {
        if (!res.success) {
            Swal.fire('No se pudo guardar', res.msg || 'Error al guardar el préstamo.', 'error');
            return;
        }
        $('#modalPrestamoVendedor').modal('hide');
        cargarPrestamosVendedores();
        Swal.fire({icon: 'success', title: 'Préstamo registrado', text: 'Se descontará de las próximas comisiones del vendedor.', timer: 2200, showConfirmButton: false});
    }, 'json').fail(function() {
        Swal.fire('Error', 'No se pudo conectar con Nómina.', 'error');
    }).always(function() {
        $form.find('button[type="submit"]').prop('disabled', false);
    });
});

function eliminarPrestamoVendedor(id) {
    Swal.fire({title: '¿Eliminar este préstamo?', text: 'Solo se puede eliminar si aún no tiene abonos de comisiones.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar'}).then(result => {
        if (!result.isConfirmed) return;
        $.post('Prestamos/api_prestamos.php', {action: 'eliminar_prestamo_vendedor', id_prestamo: id}, function(res) {
            if (res.success) cargarPrestamosVendedores();
            else Swal.fire('No se pudo eliminar', res.msg || 'Error al eliminar.', 'error');
        }, 'json');
    });
}

function archivarPrestamoVendedor(id) {
    $.post('Prestamos/api_prestamos.php', {action: 'archivar_prestamo_vendedor', id_prestamo: id}, function(res) {
        if (res.success) cargarPrestamosVendedores();
        else Swal.fire('No se pudo archivar', res.msg || 'Error al archivar.', 'error');
    }, 'json');
}