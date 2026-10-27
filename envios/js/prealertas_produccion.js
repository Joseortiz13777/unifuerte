window.prealertasMemoria = [];
let ultimoHashPrealertas = '';
let peticionPrealertaEnCurso = false;

window.cargarPrealertasProduccion = function(silencioso = false, forzarRender = false) {
    const $lista = $('#listaPrealertasProduccion');
    if (!$lista.length || peticionPrealertaEnCurso) return;

    // Mostrar spinner solo en la primera carga o si el usuario presionó el botón manualmente y no había tarjetas
    if (!silencioso && $lista.children().length === 0) {
        $lista.html('<div class="col-12 text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando prealertas...</div>');
    }

    peticionPrealertaEnCurso = true;
    $.get('api/api_envios.php', { accion: 'listar_prealertas_produccion' }, function(res) {
        if (!res || !res.success || !Array.isArray(res.data)) {
            if (!silencioso) {
                $lista.html(`<div class="col-12"><div class="alert alert-danger mb-0">${escapePrealerta(res && res.msg ? res.msg : 'No se pudieron cargar las prealertas.')}</div></div>`);
            }
            return;
        }

        window.prealertasMemoria = res.data;
        const pendientes = res.data.filter(item => Number(item.prealerta_produccion) === 1).length;
        $('#badge_prealertas_produccion').text(pendientes).toggleClass('d-none', pendientes === 0);

        const nuevoHash = JSON.stringify(res.data);
        if (!forzarRender && nuevoHash === ultimoHashPrealertas) {
            // Si los datos no han cambiado, no tocamos el DOM para evitar cualquier parpadeo
            return;
        }
        ultimoHashPrealertas = nuevoHash;

        if (res.data.length === 0) {
            $lista.html('<div class="col-12"><div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill me-2"></i>No hay cotizaciones pendientes de prealerta.</div></div>');
            return;
        }

        $lista.html(res.data.map(renderizarPrealertaProduccion).join(''));
    }, 'json').fail(function() {
        if (!silencioso) {
            $lista.html('<div class="col-12"><div class="alert alert-danger mb-0">No se pudo conectar para cargar las prealertas de producción.</div></div>');
        }
    }).always(function() {
        peticionPrealertaEnCurso = false;
    });
};

function escapePrealerta(valor) {
    return $('<div>').text(String(valor ?? '')).html();
}

function renderizarPrealertaProduccion(prealerta) {
    const revisada = Number(prealerta.prealerta_produccion) === 2;
    const modelos = Array.isArray(prealerta.modelos) ? prealerta.modelos : [];
    const insumosPrevios = Array.isArray(prealerta.insumos_pedidos) ? prealerta.insumos_pedidos : [];

    const modelosHtml = modelos.length
        ? modelos.map(modelo => {
            const tallaInfo = Array.isArray(modelo.tallas) && modelo.tallas.length
                ? modelo.tallas.map(talla => {
                    const cantidad = Number(talla.cantidad) || 0;
                    const tallaTexto = talla.talla ? `Talla ${escapePrealerta(talla.talla)}` : '';
                    const telaTexto = talla.tela ? ` · ${escapePrealerta(talla.tela)}` : '';
                    return `<span class="badge text-bg-light border me-1 mb-1">${cantidad} × ${tallaTexto}${telaTexto}</span>`;
                }).join('')
                : `<span class="small text-muted">${escapePrealerta(modelo.rango_tallas || 'Tallas por especificar')}${modelo.tela_cotizada ? ` · ${escapePrealerta(modelo.tela_cotizada)}` : ''}</span>`;

            return `<li class="mb-2">
                <div class="fw-bold">${escapePrealerta(modelo.tipo_prenda)}${modelo.genero ? ` <span class="text-muted fw-normal">(${escapePrealerta(modelo.genero)})</span>` : ''}</div>
                <div class="small text-muted mb-1">Cantidad cotizada: ${Number(modelo.cantidad) || 0}</div>
                <div>${tallaInfo}</div>
            </li>`;
        }).join('')
        : '<li class="text-muted">La cotización no contiene prendas de confección.</li>';

    const vendedor = [prealerta.vendedor_nombre, prealerta.vendedor_apellido].filter(Boolean).join(' ');
    const actionRevisada = revisada
        ? '<span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Revisada</span>'
        : `<button type="button" class="btn btn-sm btn-outline-success fw-bold btn-marcar-prealerta-revisada" data-id="${Number(prealerta.ID)}"><i class="bi bi-check2-circle me-1"></i>Marcar revisada</button>`;

    const badgeInsumosCount = insumosPrevios.length > 0
        ? ` <span class="badge bg-white text-danger ms-1">${insumosPrevios.length}</span>`
        : '';

    const btnSolicitarInsumoPre = `<button type="button" class="btn btn-sm btn-danger fw-bold btn-solicitar-insumo-prealerta" data-id="${Number(prealerta.ID)}">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>Solicitar Insumo${badgeInsumosCount}
    </button>`;

    return `<div class="col-12 col-xl-6">
        <article class="card h-100 border-${revisada ? 'success' : 'warning'} shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="fw-bold text-primary">${escapePrealerta(prealerta.nro_cotizacion || `Cotización ${prealerta.ID}`)}</div>
                    <div class="small text-muted">${escapePrealerta(prealerta.cliente || 'Cliente sin nombre')}</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    ${btnSolicitarInsumoPre}
                    ${actionRevisada}
                </div>
            </div>
            <div class="card-body">
                <div class="small text-muted mb-2">
                    <i class="bi bi-calendar3 me-1"></i>${escapePrealerta(prealerta.fecha_emision || '')}
                    ${vendedor ? ` · <i class="bi bi-person me-1"></i>${escapePrealerta(vendedor)}` : ''}
                </div>
                <div class="small fw-bold text-dark mb-2">Modelos y materiales previstos</div>
                <ul class="list-unstyled mb-0">${modelosHtml}</ul>
            </div>
        </article>
    </div>`;
}

$(document).on('click', '.btn-solicitar-insumo-prealerta', function() {
    const idCotizacion = Number($(this).data('id'));
    const prealerta = (window.prealertasMemoria || []).find(item => Number(item.ID) === idCotizacion);
    if (!prealerta) return;

    if (typeof window.abrirModalSolicitarInsumoPreorden === 'function') {
        window.abrirModalSolicitarInsumoPreorden(
            idCotizacion,
            prealerta.nro_cotizacion || ('Cotización #' + idCotizacion),
            prealerta.insumos_pedidos || []
        );
    } else {
        Swal.fire('Atención', 'El formulario de solicitud de insumos no está cargado en esta vista.', 'warning');
    }
});

$(document).on('click', '.btn-marcar-prealerta-revisada', function() {
    const $boton = $(this);
    const idCotizacion = Number($boton.data('id'));
    $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');

    $.post('api/api_envios.php', {
        accion: 'marcar_prealerta_revisada',
        id_cotizacion: idCotizacion
    }, function(res) {
        if (!res || !res.success) {
            Swal.fire('No se pudo actualizar', res && res.msg ? res.msg : 'No se registró la revisión.', 'error');
            $boton.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Marcar revisada');
            return;
        }
        window.cargarPrealertasProduccion(true, true);
    }, 'json').fail(function() {
        Swal.fire('Error de conexión', 'No se pudo registrar la revisión de la prealerta.', 'error');
        $boton.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Marcar revisada');
    });
});

$(document).ready(function() {
    window.cargarPrealertasProduccion(false, true);
    // Refresco silencioso sin parpadeo cada 30 segundos
    window.setInterval(function() {
        window.cargarPrealertasProduccion(true, false);
    }, 30000);
});