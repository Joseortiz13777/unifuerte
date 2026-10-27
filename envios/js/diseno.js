(function () {
    const apiUrl = 'api/api_diseno.php';
    let dataDiseno = { asignaciones: [], ordenes: [], disenadores: [] };
    let filtroAreaJefe = 'TODAS';

    function escapar(valor) {
        return $('<div>').text(valor == null ? '' : String(valor)).html();
    }

    function fechaCorta(valor) {
        if (!valor) return 'Sin fecha';
        const partes = String(valor).slice(0, 10).split('-');
        return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : 'Sin fecha';
    }

    function fechaHora(valor) {
        if (!valor) return 'Sin registrar';
        const partes = String(valor).split(' ');
        const hora = partes[1] ? partes[1].slice(0, 5) : '';
        return `${fechaCorta(partes[0])}${hora ? ` ${hora}` : ''}`;
    }

    function formatoDuracion(segundos) {
        if (segundos == null || !Number.isFinite(Number(segundos))) return 'Sin datos';
        let restante = Math.max(0, Number(segundos));
        const dias = Math.floor(restante / 86400);
        restante %= 86400;
        const horas = Math.floor(restante / 3600);
        const minutos = Math.floor((restante % 3600) / 60);
        if (dias) return `${dias} d ${horas} h`;
        if (horas) return `${horas} h ${minutos} min`;
        if (minutos) return `${minutos} min`;
        return 'Menos de 1 min';
    }

    function etiquetaEstado(estado) {
        if (estado === 'Finalizado') return '<span class="badge text-bg-success">Finalizado</span>';
        if (estado === 'En Proceso') return '<span class="badge text-bg-primary">En proceso</span>';
        return '<span class="badge text-bg-secondary">Pendiente</span>';
    }

    function etiquetaArea(area) {
        const a = String(area || 'Diseño').trim();
        if (a === 'Corte') return '<span class="badge badge-area-corte"><i class="bi bi-scissors me-1"></i>Corte</span>';
        if (a === 'Bordado') return '<span class="badge badge-area-bordado"><i class="bi bi-record-circle me-1"></i>Bordado</span>';
        return '<span class="badge badge-area-diseno"><i class="bi bi-palette-fill me-1"></i>Diseño</span>';
    }

    function renderizarAvances(avances, editable = false) {
        if (!Array.isArray(avances) || !avances.length) {
            return '<div class="small text-muted mt-2">Todavía no hay fases registradas.</div>';
        }
        return `<div class="mt-3 border-top pt-2">
            <div class="small fw-bold text-dark mb-2">Historial de fases escritas</div>
            ${avances.map(avance => {
                const imagenes = Array.isArray(avance.imagenes) ? avance.imagenes : [];
                const limiteImagenes = imagenes.length >= 20;
                const miniaturas = imagenes.map(imagen => {
                    const url = `${apiUrl}?accion=ver_imagen_avance&id=${Number(imagen.id)}`;
                    return `<span class="d-inline-flex align-items-start me-1 mb-1">
                        <a href="${url}" target="_blank" rel="noopener" title="Ver imagen del avance">
                            <img src="${url}" alt="Imagen del avance" loading="lazy" class="rounded border" style="width:72px;height:72px;object-fit:cover;">
                        </a>
                        ${editable ? `<button type="button" class="btn btn-sm btn-danger js-eliminar-imagen p-0 ms-1" style="width:22px;height:22px;" data-id="${Number(imagen.id)}" title="Eliminar esta imagen"><i class="bi bi-x"></i></button>` : ''}
                    </span>`;
                }).join('');
                return `<div class="border rounded bg-light p-2 mb-2">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="badge text-bg-info">${escapar(avance.fase)}</span>
                        <small class="text-muted">${fechaHora(avance.fecha)}</small>
                    </div>
                    ${avance.comentario ? `<div class="small mt-1 text-break">${escapar(avance.comentario)}</div>` : ''}
                    ${miniaturas ? `<div class="mt-2">${miniaturas}</div>` : ''}
                    <small class="text-muted">${imagenes.length} imagen${imagenes.length === 1 ? '' : 'es'}${avance.disenador ? ` · ${escapar(avance.disenador)}` : ''}</small>
                    ${editable ? `<div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary js-editar-avance" data-id="${Number(avance.id)}"><i class="bi bi-pencil me-1"></i>Editar fase / detalle</button>
                        <button type="button" class="btn btn-sm btn-outline-danger js-eliminar-avance" data-id="${Number(avance.id)}"><i class="bi bi-trash me-1"></i>Eliminar fase</button>
                        <label class="btn btn-sm btn-outline-success mb-0">
                            <i class="bi bi-plus-lg me-1"></i>Agregar imágenes (${imagenes.length}/20)
                            <input type="file" class="d-none js-agregar-imagenes" data-id="${Number(avance.id)}" accept="image/jpeg,image/png,image/webp" multiple ${limiteImagenes ? 'disabled' : ''}>
                        </label>
                    </div>` : ''}
                </div>`;
            }).join('')}
        </div>`;
    }

    function solicitar(datos, done, complete) {
        $.post(apiUrl, datos, function (respuesta) {
            if (!respuesta || !respuesta.success) {
                window.alert(respuesta && respuesta.msg ? respuesta.msg : 'No se pudo completar la operación.');
                return;
            }
            if (done) done(respuesta);
        }, 'json').fail(function (xhr) {
            const respuesta = xhr.responseJSON;
            window.alert(respuesta && respuesta.msg ? respuesta.msg : 'Error de comunicación con el servidor.');
        }).always(function () {
            if (complete) complete();
        });
    }

    function poblarSelectoresPersonal() {
        const lista = Array.isArray(dataDiseno.disenadores) ? dataDiseno.disenadores : [];
        const opcionesHtml = (areaPreferida, permitirVacio = false) => {
            const textoVacio = permitirVacio ? 'Sin asignar en esta área...' : 'Selecciona un encargado...';
            const ordenados = [...lista].sort((a, b) => {
                const aMatch = (a.area_sugerida === areaPreferida) ? 0 : 1;
                const bMatch = (b.area_sugerida === areaPreferida) ? 0 : 1;
                return aMatch - bMatch;
            });
            return `<option value="">${textoVacio}</option>` + ordenados.map(item => {
                const nombre = escapar(`${item.nombre || ''} ${item.apellido || ''}`.trim());
                const rolTxt = escapar(String(item.rol || '').toUpperCase());
                return `<option value="${Number(item.ID)}">${nombre} (${rolTxt})</option>`;
            }).join('');
        };

        const areaSeleccionada = $('#disenoArea').val() || 'Diseño';
        $('#disenoUsuario').html(opcionesHtml(areaSeleccionada, false));
        $('#disenoUsuarioMulti_Diseno').html(opcionesHtml('Diseño', true));
        $('#disenoUsuarioMulti_Corte').html(opcionesHtml('Corte', true));
        $('#disenoUsuarioMulti_Bordado').html(opcionesHtml('Bordado', true));
    }

    function renderizarOpciones() {
        // Mapa de áreas ya asignadas por cada orden
        const areasPorOrden = {};
        (dataDiseno.asignaciones || []).forEach(item => {
            const idOrd = String(item.id_orden);
            if (!areasPorOrden[idOrd]) areasPorOrden[idOrd] = [];
            if (!areasPorOrden[idOrd].includes(item.area || 'Diseño')) {
                areasPorOrden[idOrd].push(item.area || 'Diseño');
            }
        });

        const areaElegida = $('#disenoArea').val() || 'Diseño';
        // Mostrar órdenes que aún no tengan asignada el área seleccionada (o que no tengan las 3 áreas completas)
        const disponibles = (dataDiseno.ordenes || []).filter(item => {
            const yaAsignadas = areasPorOrden[String(item.ID)] || [];
            if (areaElegida === 'TODAS') {
                return yaAsignadas.length < 3;
            }
            return !yaAsignadas.includes(areaElegida);
        });

        const $orden = $('#disenoOrden');
        const seleccionActual = $orden.val();
        const filtro = String($('#buscarOrdenDiseno').val() || '').trim().toLocaleLowerCase();
        const resultados = disponibles.filter(item => {
            const datos = `${item.nro_orden_fabrica || ''} ${item.cliente || ''}`.toLocaleLowerCase();
            return datos.includes(filtro);
        });

        if (!disponibles.length) {
            $orden.html('<option value="">Todas las órdenes ya fueron asignadas en esta área</option>');
            $('#resultadoBusquedaOrdenDiseno').text('No hay órdenes pendientes por asignar en el área seleccionada.');
            return;
        }
        if (!resultados.length) {
            $orden.html('<option value="">No hay coincidencias para la búsqueda</option>');
            $('#resultadoBusquedaOrdenDiseno').text(`Sin resultados para "${filtro}".`);
            return;
        }

        $orden.html('<option value="">Selecciona una Orden de Fábrica...</option>' + resultados.map(item => {
            const numero = escapar(item.nro_orden_fabrica);
            const cliente = escapar(item.cliente || 'Cliente sin nombre');
            const ya = areasPorOrden[String(item.ID)] || [];
            const infoAreas = ya.length ? ` [Ya en: ${ya.join(', ')}]` : ' [Sin asignar]';
            return `<option value="${Number(item.ID)}">${numero} — ${cliente}${escapar(infoAreas)}</option>`;
        }).join(''));

        if (resultados.some(item => String(item.ID) === String(seleccionActual))) {
            $orden.val(seleccionActual);
        }
        $('#resultadoBusquedaOrdenDiseno').text(`${resultados.length} órdenes disponibles para emitir a ${areaElegida === 'TODAS' ? 'Diseño, Corte y Bordado' : areaElegida}.`);
    }

    function renderizarJefe() {
        const asignacionesVisibles = dataDiseno.asignaciones.filter(item => {
            const cumpleEstado = window.DISENO_VISTA_ARCHIVADOR
                ? item.estado === 'Finalizado'
                : item.estado !== 'Finalizado';
            const cumpleArea = filtroAreaJefe === 'TODAS' || (item.area || 'Diseño') === filtroAreaJefe;
            return cumpleEstado && cumpleArea;
        });

        const filas = asignacionesVisibles.map(item => {
            const estadoEnvio = escapar(item.estado_envio || 'Pendiente');
            const faseTotal = Number(item.total_fases || 0);
            const faseHechas = Number(item.fases_completadas || 0);
            const avanceFases = faseTotal ? `${faseHechas} / ${faseTotal} fases` : 'Sin iniciar';
            const detalleFases = faseTotal
                ? (item.fases_finalizadas ? `Listas: ${escapar(item.fases_finalizadas)}` : 'Sin fases listas')
                : '';

            const estadoDiseno = `${etiquetaEstado(item.estado)}
                ${item.fase_actual ? `<span class="badge text-bg-info ms-1">${escapar(item.fase_actual)}</span>` : ''}
                <small class="d-block text-muted mt-1">Inicio: ${fechaHora(item.fecha_inicio)}</small>
                <small class="d-block text-muted">Fin: ${fechaHora(item.fecha_finalizado)}</small>
                <small class="d-block text-muted">Tiempo: ${formatoDuracion(item.duracion_segundos)}</small>
                ${renderizarAvances(item.avances, true)}`;

            const tareasTexto = item.tareas_asignadas
                ? `<div class="box-tarea-asignada small text-dark mb-1" style="white-space:pre-wrap;">${escapar(item.tareas_asignadas)}</div>`
                : '<div class="small text-muted fst-italic mb-1">Sin tareas específicas</div>';
            const btnEditarTareas = `<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-editar-tareas" data-id="${Number(item.id_asignacion)}"><i class="bi bi-card-checklist me-1"></i>Asignar / Editar Tareas</button>`;

            const archivos = item.tiene_fotos
                ? `<a class="btn btn-sm btn-outline-info" href="${apiUrl}?accion=descargar_zip&id=${Number(item.id_orden)}" title="Descargar imágenes comprimidas"><i class="bi bi-file-zip"></i> ZIP</a>`
                : '';
            const verOrden = `<a class="btn btn-sm btn-outline-dark fw-bold" href="../portal_ventas/imprimir_orden.php?id=${Number(item.id_orden)}" target="_blank" rel="noopener" title="Ver Orden de Fábrica completa"><i class="bi bi-file-earmark-text"></i> Orden Fábrica</a>`;

            return `<tr>
                <td><div class="input-group input-group-sm">
                    <input class="form-control priority-input js-prioridad" type="number" min="0" value="${Number(item.prioridad)}" data-id="${Number(item.id_asignacion)}" aria-label="Prioridad">
                    <span class="input-group-text">#</span>
                </div></td>
                <td class="fw-bold text-primary">${escapar(item.nro_orden_fabrica)}</td>
                <td>${etiquetaArea(item.area)}</td>
                <td>${escapar(item.cliente || 'Cliente no registrado')}</td>
                <td class="fw-bold">${escapar(`${item.nombre_disenador || ''} ${item.apellido_disenador || ''}`.trim())}</td>
                <td style="min-width:210px;">${tareasTexto}${btnEditarTareas}</td>
                <td style="min-width:240px;">${estadoDiseno}</td>
                <td><div>${escapar(avanceFases)}</div><small class="text-muted">Envío: ${estadoEnvio}</small>${detalleFases ? `<small class="d-block text-muted mt-1">${detalleFases}</small>` : ''}</td>
                <td><div class="d-flex flex-wrap gap-1">${verOrden}${archivos}</div></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger js-quitar-asignacion" data-id="${Number(item.id_asignacion)}" title="Quitar asignación"><i class="bi bi-person-dash"></i></button></td>
            </tr>`;
        }).join('');

        $('#tablaAsignacionesDiseno').html(filas || `<tr><td colspan="10" class="text-center py-4 text-muted">${window.DISENO_VISTA_ARCHIVADOR ? 'Todavía no hay órdenes finalizadas en esta área.' : 'Todavía no hay órdenes activas asignadas en esta área.'}</td></tr>`);
        renderizarOpciones();
    }

    function renderizarDisenador() {
        const asignacionesVisibles = dataDiseno.asignaciones.filter(item =>
            window.DISENO_VISTA_ARCHIVADOR
                ? item.estado === 'Finalizado'
                : item.estado !== 'Finalizado'
        );
        const tarjetas = asignacionesVisibles.map(item => {
            const areaNombre = item.area || 'Diseño';
            let accionEstado = '';
            if (item.estado === 'Pendiente') {
                accionEstado = `<button class="btn btn-primary fw-bold js-estado-diseno" data-id="${Number(item.id_asignacion)}" data-estado="En Proceso"><i class="bi bi-play-fill me-1"></i>Iniciar ${escapar(areaNombre)}</button>`;
            } else if (item.estado === 'En Proceso') {
                accionEstado = `<button class="btn btn-success fw-bold js-estado-diseno" data-id="${Number(item.id_asignacion)}" data-estado="Finalizado"><i class="bi bi-check-circle-fill me-1"></i>Marcar ${escapar(areaNombre)} finalizado</button>`;
            } else {
                accionEstado = `<button class="btn btn-outline-primary fw-bold js-estado-diseno" data-id="${Number(item.id_asignacion)}" data-estado="En Proceso"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir proceso</button>`;
            }

            const descarga = item.tiene_fotos
                ? `<a class="btn btn-outline-info fw-bold" href="${apiUrl}?accion=descargar_zip&id=${Number(item.id_orden)}"><i class="bi bi-file-zip me-1"></i>Descargar archivos (ZIP)</a>`
                : '';
            const verOrden = `<a class="btn btn-dark fw-bold" href="../portal_ventas/imprimir_orden.php?id=${Number(item.id_orden)}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Abrir Orden de Fábrica</a>`;

            const bloqueTareas = item.tareas_asignadas
                ? `<div class="box-tarea-asignada mb-3">
                    <div class="small fw-bold text-danger mb-1"><i class="bi bi-card-checklist me-1"></i>Tareas asignadas para ${escapar(areaNombre)}:</div>
                    <div class="small fw-bold text-dark" style="white-space:pre-wrap;">${escapar(item.tareas_asignadas)}</div>
                </div>`
                : `<div class="alert alert-secondary py-2 px-3 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>Revisa las especificaciones de tallas, telas y observaciones directamente en el botón <b>Abrir Orden de Fábrica</b>.
                </div>`;

            // FASE POR ESCRITO (INPUT DE TEXTO LIBRE CON DATALIST DE SUGERENCIAS)
            const formularioAvance = !window.DISENO_VISTA_ARCHIVADOR && item.estado !== 'Finalizado'
                ? `<form class="form-avance-diseno border rounded bg-light p-3 mt-3" data-id="${Number(item.id_asignacion)}" enctype="multipart/form-data">
                    <div class="fw-bold small mb-2 text-primary"><i class="bi bi-pencil-square me-1"></i>Registrar fase por escrito (${escapar(areaNombre)})</div>
                    <label class="form-label small fw-bold mb-1" for="fase-diseno-${Number(item.id_asignacion)}">Escribe la Fase Actual *</label>
                    <input type="text"
                           id="fase-diseno-${Number(item.id_asignacion)}"
                           name="fase"
                           list="sugerencias_fases_area"
                           class="form-control form-control-sm border-primary fw-bold mb-2"
                           maxlength="120"
                           placeholder="Escribe la fase (ej: Boceto, Corte de piezas, Bordado pecho...)"
                           required>
                    <label class="form-label small fw-bold mb-1" for="comentario-diseno-${Number(item.id_asignacion)}">Detalle / Observación de la tarea</label>
                    <textarea id="comentario-diseno-${Number(item.id_asignacion)}" name="comentario" class="form-control form-control-sm mb-2" rows="2" maxlength="2000" placeholder="Describe qué se realizó o qué falta..."></textarea>
                    <label class="form-label small mb-1" for="imagenes-diseno-${Number(item.id_asignacion)}">Imágenes de evidencia (Opcional — máx. 5 fotos)</label>
                    <input id="imagenes-diseno-${Number(item.id_asignacion)}" name="imagenes[]" type="file" class="form-control form-control-sm mb-2" accept="image/jpeg,image/png,image/webp" multiple>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold w-100"><i class="bi bi-check2-circle me-1"></i>Guardar Fase Escrita</button>
                </form>`
                : '';

            return `<div class="col-md-6 col-xl-4">
                <article class="card card-order shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h2 class="h5 fw-bold order-title mb-1">${escapar(item.nro_orden_fabrica)}</h2>
                                ${etiquetaArea(areaNombre)}
                            </div>
                            ${etiquetaEstado(item.estado)}
                        </div>
                        ${item.fase_actual ? `<div class="small mb-2">Última fase escrita: <span class="badge text-bg-info">${escapar(item.fase_actual)}</span></div>` : ''}
                        <div class="text-muted small mb-1"><i class="bi bi-person-fill me-1"></i>Cliente: <b>${escapar(item.cliente || 'Cliente no registrado')}</b></div>
                        <div class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i>Entrega estimada: <b>${fechaCorta(item.fecha_entrega)}</b></div>
                        
                        ${bloqueTareas}

                        <div class="d-flex flex-wrap gap-2 mt-auto">
                            ${verOrden}
                            ${descarga}
                            ${accionEstado}
                        </div>
                        <div class="small text-muted mt-2">Inicio: ${fechaHora(item.fecha_inicio)} · Fin: ${fechaHora(item.fecha_finalizado)}</div>
                        <div class="small text-muted">Tiempo transcurrido: ${formatoDuracion(item.duracion_segundos)}</div>
                        ${formularioAvance}
                        ${renderizarAvances(item.avances, !window.DISENO_VISTA_ARCHIVADOR && item.estado !== 'Finalizado')}
                    </div>
                </article>
            </div>`;
        }).join('');

        const mensajeVacio = window.DISENO_VISTA_ARCHIVADOR
            ? 'No tienes órdenes finalizadas en tu archivador.'
            : 'No tienes órdenes de fábrica ni tareas activas asignadas en este momento.';
        $('#listaMisOrdenesDiseno').html(tarjetas || `<div class="col-12"><div class="alert alert-light border text-center text-muted mb-0">${mensajeVacio}</div></div>`);
    }

    function cargarDatos() {
        $('#btnActualizarDiseno').prop('disabled', true);
        $('#cargandoOrdenesDiseno').removeClass('d-none');
        solicitar({ accion: 'listar' }, function (respuesta) {
            dataDiseno = respuesta;
            if (window.DISENO_ES_JEFE) {
                poblarSelectoresPersonal();
                renderizarJefe();
            } else {
                renderizarDisenador();
            }
        }, function () {
            $('#cargandoOrdenesDiseno').addClass('d-none');
            $('#btnActualizarDiseno').prop('disabled', false);
        });
    }

    $(function () {
        cargarDatos();
        $('#btnActualizarDiseno').on('click', cargarDatos);
        $('#buscarOrdenDiseno').on('input', renderizarOpciones);

        // Cambiar entre selector único o selectores de las 3 áreas simultáneas
        $('#disenoArea').on('change', function () {
            const val = $(this).val();
            if (val === 'TODAS') {
                $('#boxSelectorUnicoOperador').addClass('d-none');
                $('#disenoUsuario').prop('required', false);
                $('#boxSelectoresMultiplesAreas').removeClass('d-none');
            } else {
                $('#boxSelectoresMultiplesAreas').addClass('d-none');
                $('#boxSelectorUnicoOperador').removeClass('d-none');
                $('#disenoUsuario').prop('required', true);
                poblarSelectoresPersonal();
            }
            renderizarOpciones();
        });

        // Pestañas de filtro por área para el Jefe de Diseño
        $('#tabsFiltroAreaJefe').on('click', 'button[data-area]', function () {
            $('#tabsFiltroAreaJefe button').removeClass('active');
            $(this).addClass('active');
            filtroAreaJefe = $(this).data('area') || 'TODAS';
            renderizarJefe();
        });

        $('#formAsignarDiseno').on('submit', function (evento) {
            evento.preventDefault();
            const area = $('#disenoArea').val() || 'Diseño';
            const payload = {
                accion: 'asignar',
                id_orden: $('#disenoOrden').val(),
                area: area,
                prioridad: $('#disenoPrioridad').val(),
                tareas_asignadas: $('#disenoTareasAsignadas').val() || ''
            };

            if (area === 'TODAS') {
                payload.id_disenador_diseno = $('#disenoUsuarioMulti_Diseno').val() || 0;
                payload.id_disenador_corte = $('#disenoUsuarioMulti_Corte').val() || 0;
                payload.id_disenador_bordado = $('#disenoUsuarioMulti_Bordado').val() || 0;
            } else {
                payload.id_disenador = $('#disenoUsuario').val() || 0;
            }

            solicitar(payload, function (res) {
                $('#disenoTareasAsignadas').val('');
                if (res && res.msg) window.alert(res.msg);
                cargarDatos();
            });
        });

        $('#tablaAsignacionesDiseno').on('change', '.js-prioridad', function () {
            solicitar({
                accion: 'actualizar_prioridad',
                id_asignacion: $(this).data('id'),
                prioridad: $(this).val()
            }, cargarDatos);
        });

        // Editar las tareas asignadas a Diseño, Corte o Bordado desde la tabla del Jefe
        $('#tablaAsignacionesDiseno').on('click', '.js-editar-tareas', function () {
            const idAsignacion = Number($(this).data('id'));
            const item = (dataDiseno.asignaciones || []).find(x => Number(x.id_asignacion) === idAsignacion);
            if (!item) return;

            const nuevasTareas = window.prompt(
                `Tareas e instrucciones para ${item.nro_orden_fabrica} (${item.area || 'Diseño'}):`,
                item.tareas_asignadas || ''
            );
            if (nuevasTareas === null) return;

            solicitar({
                accion: 'editar_tareas_asignadas',
                id_asignacion: idAsignacion,
                tareas_asignadas: nuevasTareas
            }, function () {
                cargarDatos();
            });
        });

        $('#tablaAsignacionesDiseno').on('click', '.js-quitar-asignacion', function () {
            if (!window.confirm('¿Quitar esta asignación de área?')) return;
            solicitar({ accion: 'quitar_asignacion', id_asignacion: $(this).data('id') }, cargarDatos);
        });

        $('#listaMisOrdenesDiseno').on('click', '.js-estado-diseno', function () {
            const boton = $(this);
            boton.prop('disabled', true);
            solicitar({
                accion: 'actualizar_estado',
                id_asignacion: boton.data('id'),
                estado: boton.data('estado')
            }, cargarDatos);
        });

        // Editar fase por escrito (libre) tanto para el operador como para el jefe
        $(document).on('click', '.js-editar-avance', function () {
            const idAvance = Number($(this).data('id'));
            const avance = dataDiseno.asignaciones
                .flatMap(item => item.avances || [])
                .find(item => Number(item.id) === idAvance);
            if (!avance) {
                window.alert('No se encontró la fase para editar.');
                return;
            }
            const faseEscrita = window.prompt('Escribe el nombre de la fase:', avance.fase || '');
            if (faseEscrita === null) return;
            const faseLimpia = faseEscrita.trim();
            if (!faseLimpia || faseLimpia.length > 120) {
                window.alert('Escribe un nombre de fase válido (máximo 120 caracteres).');
                return;
            }
            const comentario = window.prompt('Detalle u observación de la fase:', avance.comentario || '');
            if (comentario === null) return;
            if (comentario.length > 2000) {
                window.alert('El detalle no puede superar 2000 caracteres.');
                return;
            }
            solicitar({
                accion: 'editar_avance',
                id_avance: idAvance,
                fase: faseLimpia,
                comentario: comentario
            }, function (respuesta) {
                window.alert(respuesta.msg || 'Fase actualizada.');
                cargarDatos();
            });
        });

        $(document).on('click', '.js-eliminar-imagen', function () {
            const idImagen = Number($(this).data('id'));
            if (!window.confirm('¿Eliminar esta imagen del historial de la fase?')) return;
            solicitar({
                accion: 'eliminar_imagen_avance',
                id_imagen: idImagen
            }, function (respuesta) {
                window.alert(respuesta.msg || 'Imagen eliminada.');
                cargarDatos();
            });
        });

        $(document).on('click', '.js-eliminar-avance', function () {
            const idAvance = Number($(this).data('id'));
            const avance = dataDiseno.asignaciones
                .flatMap(item => item.avances || [])
                .find(item => Number(item.id) === idAvance);
            if (!avance) {
                window.alert('No se encontró la fase que deseas eliminar.');
                return;
            }
            if (!window.confirm(`¿Eliminar la fase "${avance.fase}" y sus imágenes?`)) return;
            solicitar({
                accion: 'eliminar_avance',
                id_avance: idAvance
            }, function (respuesta) {
                window.alert(respuesta.msg || 'Fase eliminada.');
                cargarDatos();
            });
        });

        $(document).on('change', '.js-agregar-imagenes', function () {
            const input = this;
            const archivos = Array.from(input.files || []);
            if (!archivos.length) return;
            if (archivos.length > 5) {
                window.alert('Puedes agregar hasta 5 imágenes por operación.');
                input.value = '';
                return;
            }
            const formData = new FormData();
            formData.append('accion', 'agregar_imagenes_avance');
            formData.append('id_avance', $(input).data('id'));
            archivos.forEach(archivo => formData.append('imagenes[]', archivo));
            $(input).prop('disabled', true);
            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    window.alert(respuesta && respuesta.msg ? respuesta.msg : 'No se pudieron agregar las imágenes.');
                    return;
                }
                window.alert(respuesta.msg || 'Imágenes agregadas.');
                cargarDatos();
            }).fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                window.alert(respuesta && respuesta.msg ? respuesta.msg : 'Error de comunicación al agregar imágenes.');
            }).always(function () {
                $(input).prop('disabled', false);
                input.value = '';
            });
        });

        $('#listaMisOrdenesDiseno').on('submit', '.form-avance-diseno', function (evento) {
            evento.preventDefault();
            const formulario = this;
            const $boton = $(formulario).find('[type="submit"]');
            const formData = new FormData(formulario);
            formData.append('accion', 'guardar_avance');
            formData.append('id_asignacion', $(formulario).data('id'));
            $boton.prop('disabled', true).text('Guardando...');
            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    window.alert(respuesta && respuesta.msg ? respuesta.msg : 'No se pudo guardar la fase.');
                    return;
                }
                window.alert(respuesta.msg || 'Fase registrada.');
                cargarDatos();
            }).fail(function (xhr) {
                const respuesta = xhr.responseJSON;
                window.alert(respuesta && respuesta.msg ? respuesta.msg : 'Error de comunicación al guardar la fase.');
            }).always(function () {
                $boton.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Guardar Fase Escrita');
            });
        });
    });
})();