let modalFormTareaObj, modalHistorialTareasObj;
let cacheTitulosObjetivos = {
    1: 'Objetivo #1',
    2: 'Objetivo #2',
    3: 'Objetivo #3',
    4: 'Objetivo #4'
};

function formatearFechaTarea(dateStr) {
    if (!dateStr || !dateStr.includes('-')) return dateStr || '';
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
}

function obtenerFechaHoyString() {
    const h = new Date();
    let dd = String(h.getDate()).padStart(2, '0');
    let mm = String(h.getMonth() + 1).padStart(2, '0');
    let yyyy = h.getFullYear();
    return `${yyyy}-${mm}-${dd}`;
}

function escaparHtmlTarea(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

function fetchTareasApi(bodyData, onSuccess) {
    fetch('api/api_tareas.php', { method: 'POST', body: bodyData })
    .then(r => r.json())
    .then(res => {
        if(res.success) onSuccess(res);
        else Swal.fire('Error', res.msg, 'error');
    }).catch(err => {
        console.error(err);
        Swal.fire('Error', 'Fallo de conexion con el servidor.', 'error');
    });
}

function actualizarSelectObjetivosModal() {
    const select = document.getElementById('select_nro_objetivo');
    if (!select) return;
    const valorActual = select.value || '1';
    let html = '';
    for (let i = 1; i <= 4; i++) {
        const titulo = cacheTitulosObjetivos[i] || `Objetivo #${i}`;
        html += `<option value="${i}">Tarjeta #${i}: ${escaparHtmlTarea(titulo)}</option>`;
    }
    select.innerHTML = html;
    select.value = valorActual;
}

window.guardarTituloObjetivo = function(nro, mostrarToast = false) {
    const input = document.getElementById(`input_titulo_objetivo_${nro}`);
    if (!input) return;
    const titulo = input.value.trim() || `Objetivo #${nro}`;
    input.value = titulo;
    cacheTitulosObjetivos[nro] = titulo;
    actualizarSelectObjetivosModal();

    const fd = new URLSearchParams();
    fd.append('accion', 'guardar_titulo_objetivo');
    fd.append('nro_objetivo', nro);
    fd.append('titulo', titulo);

    fetchTareasApi(fd, () => {
        if (mostrarToast) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Objetivo #${nro} guardado`,
                showConfirmButton: false,
                timer: 1400
            });
        }
    });
};

function actualizarBarraProgresoObjetivo(nro, tareasDelObjetivo) {
    const total = tareasDelObjetivo.length;
    const completadas = tareasDelObjetivo.filter(t => Number(t.estado) === 1).length;
    const porcentaje = total > 0 ? Math.round((completadas / total) * 100) : 0;

    const barra = document.getElementById(`barra_progreso_objetivo_${nro}`);
    const badge = document.getElementById(`badge_porcentaje_${nro}`);
    const detalle = document.getElementById(`lbl_detalle_progreso_${nro}`);

    if (detalle) {
        detalle.innerText = total === 0
            ? 'Sin tareas asignadas'
            : `${completadas} de ${total} completadas`;
    }

    let colorClass = 'bg-danger';
    if (porcentaje === 100 && total > 0) colorClass = 'bg-success';
    else if (porcentaje >= 50) colorClass = 'bg-primary';
    else if (porcentaje > 0) colorClass = 'bg-warning text-dark';

    if (badge) {
        badge.className = `badge fw-bold ${colorClass}`;
        badge.innerText = `${porcentaje}%`;
    }
    if (barra) {
        barra.style.width = `${porcentaje}%`;
        barra.setAttribute('aria-valuenow', String(porcentaje));
        barra.className = `progress-bar progress-bar-striped progress-bar-animated fw-bold ${colorClass}`;
        barra.innerText = `${porcentaje}%`;
    }
}

function cargarTareasHoy() {
    for (let i = 1; i <= 4; i++) {
        const c = document.getElementById(`contenedorTareasObjetivo_${i}`);
        if (c) c.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-danger"></div></div>';
    }

    const fd = new URLSearchParams(); 
    fd.append('accion', 'get_tareas_hoy');
    
    fetchTareasApi(fd, (res) => {
        if (res.objetivos) {
            for (let i = 1; i <= 4; i++) {
                if (res.objetivos[i]) {
                    cacheTitulosObjetivos[i] = res.objetivos[i];
                    const inputTit = document.getElementById(`input_titulo_objetivo_${i}`);
                    if (inputTit && document.activeElement !== inputTit) {
                        inputTit.value = res.objetivos[i];
                    }
                }
            }
            actualizarSelectObjetivosModal();
        }

        const tareasPorObjetivo = { 1: [], 2: [], 3: [], 4: [] };
        (res.data || []).forEach(t => {
            let nro = Number(t.nro_objetivo) || 1;
            if (nro < 1 || nro > 4) nro = 1;
            tareasPorObjetivo[nro].push(t);
        });

        for (let i = 1; i <= 4; i++) {
            const cont = document.getElementById(`contenedorTareasObjetivo_${i}`);
            const lista = tareasPorObjetivo[i];
            actualizarBarraProgresoObjetivo(i, lista);

            if (!cont) continue;

            if (lista.length === 0) {
                cont.innerHTML = `<div class="alert alert-light text-center border text-muted small py-3 mb-0">
                    <i class="bi bi-pin-angle d-block fs-5 mb-1 text-secondary"></i>
                    No hay tareas ancladas en este objetivo.
                </div>`;
                continue;
            }

            let html = [];
            lista.forEach(t => {
                let chk = Number(t.estado) === 1 ? 'checked' : '';
                let boxClass = Number(t.estado) === 1 ? 'tarea-caja completada' : 'tarea-caja';
                
                const safeFoto = String(t.foto || '');
                let btnFoto = safeFoto !== '' ? `<button type="button" class="btn btn-sm btn-outline-info border-0 px-1" data-action="foto" data-foto="${escaparHtmlTarea(safeFoto)}" title="Ver adjunto"><i class="bi bi-image"></i></button>` : '';
                let btnEdit = `<button type="button" class="btn btn-sm btn-outline-primary border-0 px-1" data-action="editar" data-id="${Number(t.id)}" data-obj="${i}" data-task="${escaparHtmlTarea(t.tarea)}" data-foto="${escaparHtmlTarea(safeFoto)}" title="Editar"><i class="bi bi-pencil-square"></i></button>`;
                let btnDel = `<button type="button" class="btn btn-sm btn-outline-danger border-0 px-1" data-action="eliminar" data-id="${Number(t.id)}" title="Eliminar"><i class="bi bi-trash-fill"></i></button>`;
                let btnPin = Number(t.anclada) === 1
                    ? `<button type="button" class="btn btn-sm btn-warning border-0 px-1 me-1" data-action="anclar" data-id="${Number(t.id)}" data-pinned="1" title="Tarea anclada a este objetivo"><i class="bi bi-pin-angle-fill"></i></button>`
                    : `<button type="button" class="btn btn-sm btn-outline-warning border-0 px-1 me-1" data-action="anclar" data-id="${Number(t.id)}" data-pinned="0" title="Anclar a este objetivo"><i class="bi bi-pin-angle"></i></button>`;

                html.push(`
                    <div class="${boxClass} ${Number(t.anclada) === 1 ? 'border-warning' : ''}">
                        <input type="checkbox" class="form-check-input check-tarea m-0" data-action="toggle" data-id="${Number(t.id)}" ${chk}>
                        <div class="tarea-caja-contenido">
                            <span class="fw-bold texto-tarea" style="font-size: 0.92rem; white-space:pre-wrap; overflow-wrap:anywhere;">${escaparHtmlTarea(t.tarea)}</span>
                        </div>
                        <div class="tarea-caja-acciones">
                            ${btnPin}
                            ${btnFoto}
                            ${btnEdit}
                            ${btnDel}
                        </div>
                    </div>
                `);
            });
            cont.innerHTML = html.join('');

            cont.querySelectorAll('[data-action]').forEach((button) => {
                if (button.dataset.action === 'toggle') {
                    button.addEventListener('change', () => window.toggleTareaRapido(Number(button.dataset.id), button.checked));
                } else {
                    button.addEventListener('click', () => {
                        const action = button.dataset.action;
                        const id = Number(button.dataset.id);
                        if (action === 'editar') window.abrirModalEditarTarea(id, button.dataset.task || '', button.dataset.foto || '', Number(button.dataset.obj) || i);
                        if (action === 'eliminar') window.eliminarTarea(id, true);
                        if (action === 'foto') window.verFotoTarea(button.dataset.foto || '');
                        if (action === 'anclar') window.anclarTarea(id, button.dataset.pinned !== '1');
                    });
                }
            });
        }
    });
}

window.toggleTareaRapido = function(id, checked) {
    let estado = checked ? 1 : 0;
    const fd = new URLSearchParams();
    fd.append('accion', 'toggle_tarea');
    fd.append('id', id);
    fd.append('estado', estado);
    fetchTareasApi(fd, () => { cargarTareasHoy(); });
};

window.anclarTarea = function(id, anclar) {
    const fd = new URLSearchParams();
    fd.append('accion', 'anclar_tarea');
    fd.append('id', id);
    fd.append('anclada', anclar ? '1' : '0');
    fetchTareasApi(fd, () => {
        cargarTareasHoy();
        if (document.getElementById('modalHistorialTareas')?.classList.contains('show')) {
            cargarHistorialListaTareas();
        }
    });
};

window.limpiarCompletadasObjetivo = function(nroObjetivo = 0) {
    const fd = new URLSearchParams();
    fd.append('accion', 'limpiar_completadas_objetivo');
    fd.append('nro_objetivo', nroObjetivo);
    fetchTareasApi(fd, () => {
        cargarTareasHoy();
    });
};

window.eliminarTarea = function(id, isHoy) {
    Swal.fire({ title: '¿Borrar esta tarea?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, borrar' })
    .then((result) => {
        if (result.isConfirmed) {
            const fd = new URLSearchParams(); fd.append('accion', 'eliminar_tarea'); fd.append('id', id);
            fetchTareasApi(fd, () => {
                if(isHoy) cargarTareasHoy();
                else cargarHistorialListaTareas();
            });
        }
    });
};

window.verFotoTarea = function(foto) {
    if (!/^tarea_[a-f0-9]{24}\.(jpg|png|webp)$/i.test(foto)) {
        Swal.fire('Imagen no disponible', 'El archivo adjunto no tiene un nombre válido.', 'error');
        return;
    }
    let ruta = '../inventario/uploads/tareas/' + encodeURIComponent(foto);
    Swal.fire({ imageUrl: ruta, imageAlt: 'Foto', confirmButtonText: 'Cerrar', confirmButtonColor: '#0B4C8C' });
};

window.abrirModalNuevaTarea = function(nroObjetivo = 1) {
    const form = document.getElementById('formTarea');
    if (!form) return;
    form.reset();
    actualizarSelectObjetivosModal();
    document.getElementById('accion_tarea').value = 'crear_tarea';
    document.getElementById('id_tarea_edit').value = '';
    document.getElementById('input_fecha_tarea').value = obtenerFechaHoyString();
    document.getElementById('select_nro_objetivo').value = String(nroObjetivo || 1);
    const nombreObj = cacheTitulosObjetivos[nroObjetivo] || `Objetivo #${nroObjetivo}`;
    document.getElementById('titModalTarea').innerHTML = `<i class="bi bi-pencil-square"></i> Nueva Tarea — ${escaparHtmlTarea(nombreObj)}`;
    document.getElementById('box_foto_tarea').style.display = 'block';
    document.getElementById('foto_actual_tarea').hidden = true;
    document.getElementById('quitar_foto_tarea_label').hidden = true;
    document.getElementById('quitar_foto_tarea').checked = false;
    document.getElementById('foto_nueva_preview').hidden = true;
    if(modalFormTareaObj) modalFormTareaObj.show();
};

window.abrirModalEditarTarea = function(id, texto, foto, nroObjetivo = 1) {
    const form = document.getElementById('formTarea');
    if (!form) return;
    form.reset();
    actualizarSelectObjetivosModal();
    document.getElementById('accion_tarea').value = 'editar_tarea';
    document.getElementById('id_tarea_edit').value = id;
    document.getElementById('select_nro_objetivo').value = String(nroObjetivo || 1);
    document.getElementById('txt_tarea_desc').value = texto;
    document.getElementById('titModalTarea').innerHTML = '<i class="bi bi-pencil-fill"></i> Editar Tarea';
    document.getElementById('box_foto_tarea').style.display = 'block';
    const currentPhoto = document.getElementById('foto_actual_tarea');
    const removePhotoLabel = document.getElementById('quitar_foto_tarea_label');
    const hasPhoto = Boolean(foto);
    currentPhoto.hidden = !hasPhoto;
    currentPhoto.innerHTML = hasPhoto
        ? `<div class="d-flex align-items-center gap-3"><img class="tarea-adjunto-preview" src="../inventario/uploads/tareas/${encodeURIComponent(foto)}" alt="Imagen actual de la tarea"><span class="small text-muted">Imagen actual</span></div>`
        : '';
    removePhotoLabel.hidden = !hasPhoto;
    document.getElementById('quitar_foto_tarea').checked = false;
    document.getElementById('foto_nueva_preview').hidden = true;
    if(modalFormTareaObj) modalFormTareaObj.show();
};

window.abrirHistorialTareas = function() {
    if(!modalHistorialTareasObj) return;
    document.getElementById('tbodyHistorialTareas').innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-dark"></div></td></tr>';
    modalHistorialTareasObj.show();
    cargarHistorialListaTareas();
};

function cargarHistorialListaTareas() {
    const fd = new URLSearchParams(); fd.append('accion', 'get_historial');
    fetchTareasApi(fd, (res) => {
        const tb = document.getElementById('tbodyHistorialTareas');
        if(res.data.length === 0) {
            tb.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay tareas en el historial.</td></tr>';
            return;
        }
        let html = [];
        res.data.forEach(t => {
            let bgEstado = Number(t.estado) === 1 ? '<span class="badge bg-success">Lista</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
            let txt = Number(t.estado) === 1 ? `<span class="text-secondary" style="text-decoration:line-through;">${escaparHtmlTarea(t.tarea)}</span>` : `<span class="fw-bold text-dark">${escaparHtmlTarea(t.tarea)}</span>`;
            let btnPin = `<button type="button" class="btn btn-sm btn-outline-warning border-0" data-action="anclar" data-id="${Number(t.id)}" data-pinned="0" title="Restaurar y anclar a su objetivo"><i class="bi bi-pin-angle"></i></button>`;
            let btnFoto = t.foto ? `<button type="button" class="btn btn-sm btn-outline-info border-0" data-action="foto" data-foto="${escaparHtmlTarea(t.foto)}" title="Ver imagen"><i class="bi bi-image"></i></button>` : '';
            
            html.push(`<tr>
                <td class="text-muted small ps-3">${formatearFechaTarea(t.fecha_tarea)}</td>
                <td><span class="badge bg-primary">${escaparHtmlTarea(t.nombre_objetivo || ('Objetivo #' + (t.nro_objetivo || 1)))}</span></td>
                <td>${txt}${t.foto ? '<div class="small text-info mt-1"><i class="bi bi-paperclip"></i> Tiene imagen adjunta</div>' : ''}</td>
                <td class="text-center">${bgEstado}</td>
                <td class="text-center pe-3 text-nowrap">
                    ${btnPin}
                    ${btnFoto}
                    <button type="button" class="btn btn-sm btn-outline-primary border-0" data-action="editar" data-id="${Number(t.id)}" data-obj="${Number(t.nro_objetivo) || 1}" data-task="${escaparHtmlTarea(t.tarea)}" data-foto="${escaparHtmlTarea(t.foto || '')}" title="Editar"><i class="bi bi-pencil-square"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" data-action="eliminar" data-id="${Number(t.id)}" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                </td>
            </tr>`);
        });
        tb.innerHTML = html.join('');
        tb.querySelectorAll('[data-action]').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.action === 'editar') window.abrirModalEditarTarea(Number(button.dataset.id), button.dataset.task || '', button.dataset.foto || '', Number(button.dataset.obj) || 1);
                if (button.dataset.action === 'eliminar') window.eliminarTarea(Number(button.dataset.id), false);
                if (button.dataset.action === 'anclar') window.anclarTarea(Number(button.dataset.id), true);
                if (button.dataset.action === 'foto') window.verFotoTarea(button.dataset.foto || '');
            });
        });
    });
}

document.addEventListener("DOMContentLoaded", function() {
    let hd = new Date();
    let opts = { year: 'numeric', month: '2-digit', day: '2-digit' };
    let textoFechaHora = hd.toLocaleDateString('es-ES', opts) + ' ' + hd.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'});
    document.querySelectorAll('.lbl-fecha-tareas-card').forEach(el => { el.innerText = textoFechaHora; });

    // Guardar automáticamente el título de cada objetivo al salir del input o presionar Enter
    document.querySelectorAll('.input-titulo-objetivo').forEach(input => {
        input.addEventListener('change', function() {
            window.guardarTituloObjetivo(Number(this.dataset.nro), true);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });

    if(document.getElementById('modalFormTarea')) modalFormTareaObj = new bootstrap.Modal(document.getElementById('modalFormTarea'));
    if(document.getElementById('modalHistorialTareas')) modalHistorialTareasObj = new bootstrap.Modal(document.getElementById('modalHistorialTareas'));

    let formT = document.getElementById('formTarea');
    if(formT) {
        const imageInput = document.getElementById('foto_tarea');
        let previewUrl = '';
        if (imageInput) {
            imageInput.addEventListener('change', function() {
                const preview = document.getElementById('foto_nueva_preview');
                const file = this.files && this.files[0];
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = '';
                if (!file) {
                    preview.hidden = true;
                    preview.innerHTML = '';
                    return;
                }
                previewUrl = URL.createObjectURL(file);
                preview.innerHTML = `<img class="tarea-adjunto-preview" src="${previewUrl}" alt="Vista previa de la imagen seleccionada"><div class="small text-muted mt-1">${escaparHtmlTarea(file.name)}</div>`;
                preview.hidden = false;
            });
        }
        document.getElementById('modalFormTarea')?.addEventListener('hidden.bs.modal', () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = '';
            const prev = document.getElementById('foto_nueva_preview');
            if (prev) prev.hidden = true;
        });
        formT.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarTarea'); 
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
            
            fetch('api/api_tareas.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(res => {
                btn.disabled = false; btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
                if(res.success) {
                    modalFormTareaObj.hide();
                    document.getElementById('foto_nueva_preview').hidden = true;
                    cargarTareasHoy();
                    if (document.getElementById('modalHistorialTareas')?.classList.contains('show')) {
                        cargarHistorialListaTareas();
                    }
                } else Swal.fire('Error', res.msg, 'error');
            }).catch(() => {
                btn.disabled = false; btn.innerHTML = '<i class="bi bi-save"></i> Guardar';
                Swal.fire('Error', 'Fallo de conexion', 'error');
            });
        });
    }

    let tabT = document.querySelector('button[data-bs-target="#tab-tareas"]'); 
    if(tabT) {
        tabT.addEventListener('shown.bs.tab', cargarTareasHoy);
    }

    // Si estamos en lista_tareas.php independiente (donde no hay pestañas sino vista directa), cargar de inmediato
    if (!tabT && document.getElementById('contenedorTareasObjetivo_1')) {
        cargarTareasHoy();
    }
});