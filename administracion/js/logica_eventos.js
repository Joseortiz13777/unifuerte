(function () {
    const tbody = document.getElementById('tbodyEventos');
    const csrf = document.getElementById('csrfEventos')?.value || '';
    if (!tbody) return;

    function escaparHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[char]);
    }

    function numeroWhatsApp(raw) {
        let numero = String(raw || '').replace(/\D/g, '');
        if (numero.startsWith('0')) numero = `58${numero.slice(1)}`;
        return numero;
    }

    async function solicitar(datos) {
        const response = await fetch('api/api_eventos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams(datos)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.msg || 'No se pudo completar la operación.');
        }
        return result;
    }

    function fechaLegible(value) {
        const fecha = new Date(String(value || '').replace(' ', 'T'));
        return Number.isNaN(fecha.getTime()) ? escaparHtml(value) : fecha.toLocaleString('es-VE');
    }

    function renderizar(solicitudes) {
        if (!solicitudes.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay solicitudes de asesoría.</td></tr>';
            return;
        }
        tbody.innerHTML = solicitudes.map((evento) => {
            const pendiente = evento.estado === 'Pendiente';
            const estadoClass = evento.estado === 'Aceptada' ? 'bg-success' : (evento.estado === 'Rechazada' ? 'bg-secondary' : 'bg-warning text-dark');
            const telefono = numeroWhatsApp(evento.telefono_cliente);
            const mensaje = evento.estado === 'Aceptada'
                ? `Hola ${evento.nombre_cliente}, confirmamos tu asesoría para el ${evento.fecha_preferida} (${evento.modalidad}). Motivo: ${evento.motivo}. Unifuerte.`
                : `Hola ${evento.nombre_cliente}, respecto a tu solicitud de asesoría para el ${evento.fecha_preferida}, no podemos confirmar ese horario. Por favor responde para coordinar otra fecha. Unifuerte.`;
            const enlaceWhatsApp = telefono
                ? `<a class="btn btn-sm btn-success" href="https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Responder</a>`
                : '<span class="small text-danger">Cliente sin teléfono</span>';
            return `<tr>
                <td>#${Number(evento.id)}<div class="small text-muted">${escaparHtml(evento.creado_en)}</div></td>
                <td><strong>${escaparHtml(evento.nombre_cliente)}</strong><div class="small">${escaparHtml(evento.telefono_cliente)}</div></td>
                <td>${escaparHtml(evento.motivo)}<div class="small text-muted">${escaparHtml(evento.modalidad)}</div></td>
                <td class="text-nowrap">${fechaLegible(evento.fecha_preferida)}</td>
                <td style="min-width:180px;white-space:pre-wrap">${escaparHtml(evento.necesidad || '—')}</td>
                <td><span class="badge ${estadoClass}">${escaparHtml(evento.estado)}</span>
                    <div class="d-flex gap-1 mt-2 flex-wrap">
                        ${pendiente ? `<button class="btn btn-sm btn-primary js-resolver-evento" data-id="${Number(evento.id)}" data-estado="Aceptada">Aceptar</button>
                        <button class="btn btn-sm btn-outline-secondary js-resolver-evento" data-id="${Number(evento.id)}" data-estado="Rechazada">Rechazar</button>` : enlaceWhatsApp}
                        <button class="btn btn-sm btn-outline-danger js-eliminar-evento" data-id="${Number(evento.id)}">Eliminar</button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    async function cargarEventos() {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>';
        try {
            const result = await solicitar({ accion: 'listar', csrf });
            renderizar(result.data || []);
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escaparHtml(error.message)}</td></tr>`;
        }
    }

    document.getElementById('btnRecargarEventos')?.addEventListener('click', cargarEventos);
    tbody.addEventListener('click', async (event) => {
        const botonEliminar = event.target.closest('.js-eliminar-evento');
        if (botonEliminar) {
            if (!window.confirm('¿Eliminar definitivamente esta solicitud? Esta acción no se puede deshacer.')) return;
            botonEliminar.disabled = true;
            try {
                const result = await solicitar({
                    accion: 'eliminar',
                    id: botonEliminar.dataset.id,
                    csrf
                });
                if (window.Swal) {
                    await Swal.fire({ icon: 'success', title: 'Solicitud eliminada', text: result.msg });
                }
                await cargarEventos();
            } catch (error) {
                window.alert(error.message);
                botonEliminar.disabled = false;
            }
            return;
        }
        const boton = event.target.closest('.js-resolver-evento');
        if (!boton) return;
        const estado = boton.dataset.estado;
        const confirmacion = estado === 'Aceptada'
            ? '¿Confirmar la fecha solicitada? Se validará que no exista otra asesoría aceptada a esa hora.'
            : '¿Rechazar la solicitud?';
        if (!window.confirm(confirmacion)) return;
        boton.disabled = true;
        try {
            const result = await solicitar({
                accion: 'resolver',
                id: boton.dataset.id,
                estado,
                csrf
            });
            if (window.Swal) {
                await Swal.fire({ icon: 'success', title: 'Solicitud actualizada', text: result.msg });
            }
            await cargarEventos();
        } catch (error) {
            window.alert(error.message);
            boton.disabled = false;
        }
    });

    document.querySelector('[data-bs-target="#tab-eventos"]')?.addEventListener('shown.bs.tab', cargarEventos);
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('#tab-eventos.active')) cargarEventos();
    });
})();
