<?php if (in_array($rol_actual, ['master', 'ventas', 'jefe_ventas'], true)): ?>
<div id="vistaClientesCotizar" class="vista-spa" style="display: none;">
    <style>
        #vistaClientesCotizar .clientes-cotizar-shell {
            padding: 4px 0;
        }

        #vistaClientesCotizar .panel-cotizar {
            background: linear-gradient(180deg, #ffffff 0%, #f7fafd 100%);
            border: 1px solid rgba(11, 76, 140, 0.12);
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(15, 92, 145, 0.08);
            padding: 20px;
        }

        #vistaClientesCotizar .panel-cotizar h5,
        #vistaClientesCotizar .panel-cotizar h4,
        #vistaClientesCotizar .titulo-seccion {
            color: #0B4C8C;
            margin-bottom: 0;
        }

        #vistaClientesCotizar .stat-card {
            border-radius: 16px;
            padding: 14px 16px;
            background: #fff;
            border: 1px solid rgba(11, 76, 140, 0.1);
            box-shadow: 0 6px 16px rgba(11, 76, 140, 0.06);
            min-height: 92px;
        }

        #vistaClientesCotizar .stat-card .label {
            color: #6b7280;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        #vistaClientesCotizar .stat-card .value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0B4C8C;
            line-height: 1.1;
        }

        #vistaClientesCotizar .stat-card.primary { border-left: 4px solid #0B4C8C; }
        #vistaClientesCotizar .stat-card.warning { border-left: 4px solid #FF7518; }
        #vistaClientesCotizar .stat-card.success { border-left: 4px solid #198754; }
        #vistaClientesCotizar .stat-card.secondary { border-left: 4px solid #6c757d; }

        #vistaClientesCotizar .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 18px;
        }

        #vistaClientesCotizar .search-box {
            position: relative;
            flex: 1;
            min-width: 230px;
        }

        #vistaClientesCotizar .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        #vistaClientesCotizar .search-box input,
        #vistaClientesCotizar .toolbar select,
        #vistaClientesCotizar .panel-cotizar input,
        #vistaClientesCotizar .panel-cotizar select,
        #vistaClientesCotizar .panel-cotizar textarea {
            border-radius: 12px;
            border: 1px solid rgba(11, 76, 140, 0.18);
            background: #f8fafc;
            box-shadow: none;
        }

        #vistaClientesCotizar .search-box input {
            padding-left: 38px;
        }

        #vistaClientesCotizar .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 18px;
        }

        #vistaClientesCotizar .cliente-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(11, 76, 140, 0.12);
            box-shadow: 0 10px 20px rgba(15, 92, 145, 0.06);
            padding: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        #vistaClientesCotizar .cliente-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(15, 92, 145, 0.12);
        }

        #vistaClientesCotizar .cliente-card.status-pendiente { border-top: 5px solid #FF7518; }
        #vistaClientesCotizar .cliente-card.status-en_revision { border-top: 5px solid #0d6efd; }
        #vistaClientesCotizar .cliente-card.status-cotizado { border-top: 5px solid #0B4C8C; }
        #vistaClientesCotizar .cliente-card.status-cerrado { border-top: 5px solid #198754; }
        #vistaClientesCotizar .cliente-card.status-archivado { border-top: 5px solid #6c757d; }

        #vistaClientesCotizar .cliente-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }

        #vistaClientesCotizar .cliente-card-header h5 {
            margin: 0;
            color: #0B4C8C;
            font-weight: 800;
            line-height: 1.2;
        }

        #vistaClientesCotizar .badge-estado {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #vistaClientesCotizar .badge-pendiente { background: rgba(255, 117, 24, 0.12); color: #b75400; }
        #vistaClientesCotizar .badge-en_revision { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        #vistaClientesCotizar .badge-cotizado { background: rgba(11, 76, 140, 0.1); color: #0B4C8C; }
        #vistaClientesCotizar .badge-cerrado { background: rgba(25, 135, 84, 0.12); color: #198754; }
        #vistaClientesCotizar .badge-archivado { background: rgba(108, 117, 125, 0.12); color: #6c757d; }

        #vistaClientesCotizar .cliente-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: #334155;
        }

        #vistaClientesCotizar .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.3;
        }

        #vistaClientesCotizar .meta-row i {
            width: 18px;
            color: #FF7518;
        }

        #vistaClientesCotizar .mini-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        #vistaClientesCotizar .mini-badges .chip {
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            background: #eef5ff;
            color: #0B4C8C;
        }

        #vistaClientesCotizar .cliente-card .notes-box {
            background: #f8fafc;
            border: 1px solid rgba(11, 76, 140, 0.08);
            border-radius: 12px;
            padding: 10px;
            margin-top: 12px;
        }

        #vistaClientesCotizar .note-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }

        #vistaClientesCotizar .note-item {
            background: #fff;
            border: 1px solid rgba(11, 76, 140, 0.08);
            border-radius: 10px;
            padding: 8px 9px;
            font-size: 0.78rem;
            color: #364152;
        }

        #vistaClientesCotizar .note-item .small-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.68rem;
            font-weight: 700;
            color: #0B4C8C;
            margin-bottom: 4px;
        }

        #vistaClientesCotizar .client-images {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        #vistaClientesCotizar .client-images img {
            width: 62px;
            height: 62px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(11, 76, 140, 0.12);
        }

        #vistaClientesCotizar .client-photo-item {
            position: relative;
            width: 62px;
            height: 62px;
        }

        #vistaClientesCotizar .client-photo-item > a {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 10px;
            background: #f1f5f9;
            color: #0B4C8C;
        }

        #vistaClientesCotizar .client-photo-item .btn-quitar-foto-cotizar {
            position: absolute;
            top: -7px;
            right: -7px;
            width: 23px;
            height: 23px;
            padding: 0;
            border-radius: 50%;
            line-height: 21px;
        }

        #vistaClientesCotizar .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        #vistaClientesCotizar .archivador-box {
            background: linear-gradient(180deg, #fff 0%, #f9fafb 100%);
            border: 1px dashed rgba(11, 76, 140, 0.2);
            border-radius: 16px;
            padding: 18px;
            margin-top: 16px;
        }

        #vistaClientesCotizar #listaClienteCotizarArchivado .empty-box,
        #vistaClientesCotizar #listaClienteCotizar .empty-box {
            background: #f8fafc;
            border: 1px dashed rgba(11, 76, 140, 0.12);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 767px) {
            #vistaClientesCotizar .card-actions {
                flex-direction: column;
            }
            #vistaClientesCotizar .card-actions button,
            #vistaClientesCotizar .btn-save {
                width: 100%;
            }
        }
    </style>

    <div class="clientes-cotizar-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-person-lines-fill me-2"></i> Clientes a Cotizar</h3>
                <small class="text-muted"><?= $rol_actual === 'master' ? 'Panel maestro para seguimiento, notas y evidencias de clientes prospectados.' : 'Seguimiento de los clientes a cotizar asignados a tu usuario.' ?></small>
            </div>
            <?php if ($rol_actual === 'master'): ?>
                <button type="button" id="btnAbrirFormClienteCotizar" class="btn text-white fw-bold" style="background-color: #FF7518; border-radius: 12px;">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo cliente
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php if ($rol_actual === 'master'): ?>
            <div class="col-xl-4 col-lg-5">
                <div class="panel-cotizar">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold" id="tituloFormClienteCotizar">Nuevo cliente</h5>
                        <button type="button" id="btnCancelarClienteCotizar" class="btn btn-sm btn-outline-secondary d-none">Cancelar</button>
                    </div>

                    <form id="formClienteCotizar" autocomplete="off" onsubmit="return window.enviarClienteCotizar(event);">
                        <input type="hidden" id="clienteCotizarId" value="">
                        <input type="hidden" id="clienteCotizarDirectorioId" value="">

                        <div class="mb-3 p-3 rounded border border-primary bg-light">
                            <label for="clienteCotizarYaRegistrado" class="small fw-bold text-primary mb-1">¿El cliente ya está registrado en el directorio?</label>
                            <select id="clienteCotizarYaRegistrado" class="form-select">
                                <option value="no" selected>No, registrar como nuevo</option>
                                <option value="si">Sí, buscar cliente existente</option>
                            </select>
                            <div id="panelBusquedaDirectorioCotizar" class="mt-3 d-none">
                                <label for="busquedaDirectorioCotizar" class="small fw-bold text-muted mb-1">Buscar por nombre, RIF o teléfono</label>
                                <input type="search" id="busquedaDirectorioCotizar" class="form-control" placeholder="Escribe al menos 2 caracteres..." autocomplete="off">
                                <div id="resultadosDirectorioCotizar" class="list-group mt-2" style="max-height: 240px; overflow-y: auto;"></div>
                                <div id="clienteDirectorioSeleccionado" class="alert alert-success py-2 px-3 mt-2 mb-0 d-none"></div>
                                <button type="button" id="btnQuitarClienteDirectorio" class="btn btn-sm btn-outline-secondary mt-2 d-none">Quitar selección</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Nombre</label>
                            <input type="text" id="clienteCotizarNombre" class="form-control" placeholder="Ej: Maria Pérez" required>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Empresa / Negocio</label>
                            <input type="text" id="clienteCotizarEmpresa" class="form-control" placeholder="Ej: Uniformes Los Leones">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Teléfono / WhatsApp</label>
                            <input type="text" id="clienteCotizarTelefono" class="form-control" placeholder="0414-1234567">
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Producto / Servicio</label>
                            <input type="text" id="clienteCotizarProducto" class="form-control" placeholder="Ej: Uniformes corporativos">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted mb-1">Estado</label>
                                <select id="clienteCotizarEstado" class="form-select">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_revision">En revisión</option>
                                    <option value="cotizado">Cotizado</option>
                                    <option value="cerrado">Cerrado</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted mb-1">Notificación a WA / Fecha de contacto</label>
                                <input type="date" id="clienteCotizarFechaContactoWa" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Asignado a</label>
                            <input type="hidden" id="clienteCotizarAsignadoId" value="">
                            <input type="search" id="clienteCotizarAsignadoBusqueda" class="form-control" placeholder="Escribe para buscar vendedor activo..." autocomplete="off">
                            <div id="resultadosVendedoresCotizar" class="list-group mt-1" style="max-height: 180px; overflow-y: auto;"></div>
                            <small class="text-muted">Solo aparecen usuarios de ventas/jefatura no bloqueados.</small>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Notas</label>
                            <textarea id="clienteCotizarNotas" class="form-control" rows="4" placeholder="Describe detalles del lead, requerimientos o comentarios..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Evidencias / fotos</label>
                            <input type="file" id="clienteCotizarFotos" class="form-control" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf">
                            <small class="text-muted">Se adjuntan al registro del cliente para su seguimiento.</small>
                        </div>

                        <button type="submit" class="btn text-white fw-bold w-100" style="background-color: #0B4C8C; border-radius: 12px;">
                            <i class="bi bi-floppy-fill me-2"></i> Guardar cliente
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="<?= $rol_actual === 'master' ? 'col-xl-8 col-lg-7' : 'col-12' ?>">
                <?php if ($rol_actual !== 'master'): ?>
                    <div class="alert alert-info border-0 shadow-sm mb-3">
                        <i class="bi bi-info-circle-fill me-2"></i> Aquí aparecen tus clientes asignados. Puedes agregar notas y evidencias; los datos principales los administra gerencia.
                    </div>
                <?php endif; ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="stat-card primary">
                            <div class="label">Total</div>
                            <div class="value" id="clientesCotizarTotal">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card warning">
                            <div class="label">Pendientes</div>
                            <div class="value" id="clientesCotizarPendientes">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card success">
                            <div class="label">Cerrados</div>
                            <div class="value" id="clientesCotizarCerrados">0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card secondary">
                            <div class="label">Archivados</div>
                            <div class="value" id="clientesCotizarArchivados">0</div>
                        </div>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="search" id="buscadorClienteCotizar" class="form-control" placeholder="Buscar nombre, empresa o producto...">
                    </div>
                    <select id="filtroEstadoClienteCotizar" class="form-select" style="max-width: 220px;">
                        <option value="todos">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_revision">En revisión</option>
                        <option value="cotizado">Cotizado</option>
                        <option value="cerrado">Cerrado</option>
                        <option value="archivado">Archivado</option>
                    </select>
                </div>

                <div>
                    <h5 class="titulo-seccion mb-3"><i class="bi bi-kanban-fill me-2"></i> Seguimiento / Tarjetas</h5>
                    <div id="listaClienteCotizar" class="card-grid"></div>
                </div>

                <div class="archivador-box mt-4">
                    <h5 class="titulo-seccion mb-3"><i class="bi bi-archive-fill me-2"></i> Archivador</h5>
                    <div id="listaClienteCotizarArchivado" class="card-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const STORAGE_KEY = 'portal_clientes_cotizar_master';
            const ES_MASTER = <?= json_encode($rol_actual === 'master') ?>;
            let directorioSearchTimer = null;
            let directorioSearchRequest = null;
            let directorioSearchSequence = 0;
            const clientesDirectorioCotizar = {};
            let vendedoresActivosCotizar = [];

            function getLocalData() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    if (!raw) return [];
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            }

            function saveLocalData(data) {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                } catch (error) {
                    console.warn('No se pudo guardar en localStorage', error);
                }
            }

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[char];
                });
            }

            function formatDate(value) {
                if (!value) return 'Sin fecha';
                const date = new Date(String(value).slice(0, 10) + 'T12:00:00');
                if (Number.isNaN(date.getTime())) return value;
                return date.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            }

            function getEstadoClass(estado) {
                return {
                    pendiente: 'badge-pendiente',
                    en_revision: 'badge-en_revision',
                    cotizado: 'badge-cotizado',
                    cerrado: 'badge-cerrado',
                    archivado: 'badge-archivado'
                }[estado] || 'badge-pendiente';
            }

            function getEstadoLabel(estado) {
                return {
                    pendiente: 'Pendiente',
                    en_revision: 'En revisión',
                    cotizado: 'Cotizado',
                    cerrado: 'Cerrado',
                    archivado: 'Archivado'
                }[estado] || 'Pendiente';
            }

            function normalizeLead(item) {
                const normalized = { ...item };
                normalized.id = item.id ?? item.ID ?? item.lead_id;
                normalized.nombre = item.nombre || '';
                normalized.empresa = item.empresa || '';
                normalized.telefono = item.telefono || '';
                normalized.producto = item.producto || '';
                normalized.estado = item.estado || 'pendiente';
                normalized.fecha_contacto_wa = item.fecha_contacto_wa || '';
                normalized.asignado = item.asignado || '';
                normalized.vendedor_id = item.vendedor_id || '';
                normalized.directorio_cliente_id = item.directorio_cliente_id || '';
                normalized.notas = Array.isArray(item.notas) ? item.notas : [];
                normalized.fotos = Array.isArray(item.fotos) ? item.fotos : [];
                normalized.archivado = item.archivado === true || item.archivado === 1 || item.archivado === '1';
                normalized.created_at = item.created_at || item.createdAt || null;
                return normalized;
            }

            function mergePendingLocalData(serverItems) {
                const merged = serverItems.map(normalizeLead);
                if (!ES_MASTER) return merged;
                const ids = new Set(merged.map(item => String(item.id)));
                const pending = getLocalData()
                    .map(normalizeLead)
                    .filter(item => item.localOnly || !/^\d+$/.test(String(item.id)));

                pending.forEach(item => {
                    if (!ids.has(String(item.id))) {
                        merged.push(item);
                        ids.add(String(item.id));
                    }
                });

                return merged;
            }

            function getCurrentData() {
                return $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { action: 'listar' }
                }).then(function (res) {
                    if (!res || !res.success) {
                        return ES_MASTER ? getLocalData().map(normalizeLead) : [];
                    }
                    return mergePendingLocalData(Array.isArray(res.items) ? res.items : []);
                }).fail(function () {
                    return ES_MASTER ? getLocalData().map(normalizeLead) : [];
                });
            }

            function resetForm() {
                $('#formClienteCotizar')[0].reset();
                $('#clienteCotizarId').val('');
                $('#clienteCotizarDirectorioId').val('');
                $('#clienteCotizarAsignadoId').val('');
                $('#clienteCotizarAsignadoBusqueda').val('');
                $('#clienteCotizarAsignadoBusqueda').removeData('selected-name');
                $('#resultadosVendedoresCotizar').empty();
                $('#panelBusquedaDirectorioCotizar').addClass('d-none');
                $('#busquedaDirectorioCotizar').val('');
                $('#resultadosDirectorioCotizar').empty();
                $('#clienteDirectorioSeleccionado').addClass('d-none').empty();
                $('#btnQuitarClienteDirectorio').addClass('d-none');
                $('#tituloFormClienteCotizar').text('Nuevo cliente');
                $('#btnCancelarClienteCotizar').addClass('d-none');
            }

            function renderResultadosDirectorio(items) {
                const $resultados = $('#resultadosDirectorioCotizar');
                if (!items.length) {
                    $resultados.html('<div class="small text-muted px-2 py-2">No se encontraron clientes con esos datos.</div>');
                    return;
                }

                $resultados.html(items.map(function (cliente) {
                    clientesDirectorioCotizar[String(cliente.id)] = cliente;
                    return `<button type="button" class="list-group-item list-group-item-action btn-elegir-directorio-cotizar" data-id="${Number(cliente.id)}">
                        <span class="fw-bold text-primary">${escapeHtml(cliente.nombre)}</span>
                        <span class="small text-muted d-block">${escapeHtml(cliente.cedula_rif || 'Sin RIF')} · ${escapeHtml(cliente.telefono || 'Sin teléfono')}${cliente.comprador ? ` · Contacto: ${escapeHtml(cliente.comprador)}` : ''}</span>
                    </button>`;
                }).join(''));
            }

            function buscarClienteDirectorio(termino) {
                const $resultados = $('#resultadosDirectorioCotizar');
                if (directorioSearchRequest) {
                    directorioSearchRequest.abort();
                    directorioSearchRequest = null;
                }
                const sequence = ++directorioSearchSequence;
                if (termino.trim().length < 2) {
                    $resultados.empty();
                    return;
                }

                $resultados.html('<div class="small text-muted px-2 py-2">Buscando en el directorio...</div>');
                directorioSearchRequest = $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { action: 'buscar_directorio', q: termino.trim() }
                }).done(function (res) {
                    if (sequence !== directorioSearchSequence) return;
                    if (res && res.success && Array.isArray(res.items)) {
                        renderResultadosDirectorio(res.items);
                    } else {
                        $resultados.html(`<div class="small text-danger px-2 py-2">${escapeHtml(res && res.message ? res.message : 'No se pudo consultar el directorio.')}</div>`);
                    }
                }).fail(function (xhr, status) {
                    if (status === 'abort' || sequence !== directorioSearchSequence) return;
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'No se pudo conectar con el directorio de clientes.';
                    $resultados.html(`<div class="small text-danger px-2 py-2">${escapeHtml(message)}</div>`);
                }).always(function () {
                    if (sequence === directorioSearchSequence) directorioSearchRequest = null;
                });
            }

            function seleccionarClienteDirectorio(cliente) {
                $('#clienteCotizarDirectorioId').val(cliente.id);
                $('#clienteCotizarNombre').val(cliente.comprador || cliente.nombre || '');
                $('#clienteCotizarEmpresa').val(cliente.nombre || '');
                $('#clienteCotizarTelefono').val(cliente.telefono || '');
                $('#clienteDirectorioSeleccionado')
                    .html(`<i class="bi bi-link-45deg me-1"></i> Vinculado: <strong>${escapeHtml(cliente.nombre)}</strong> (${escapeHtml(cliente.cedula_rif || 'sin RIF')})`)
                    .removeClass('d-none');
                $('#btnQuitarClienteDirectorio').removeClass('d-none');
                $('#resultadosDirectorioCotizar').empty();
                $('#busquedaDirectorioCotizar').val('');
            }

            function mostrarModuloClientesCotizar() {
                $('.vista-spa').hide();
                $('#vistaClientesCotizar').show();
                $('.nav-link-custom').removeClass('active');
                $('#btnMenuClientesCotizar').addClass('active');
            }

            function renderStats(data) {
                const total = data.length;
                const pendientes = data.filter(item => !item.archivado && item.estado === 'pendiente').length;
                const cerrados = data.filter(item => !item.archivado && item.estado === 'cerrado').length;
                const archivados = data.filter(item => item.archivado).length;

                $('#clientesCotizarTotal').text(total);
                $('#clientesCotizarPendientes').text(pendientes);
                $('#clientesCotizarCerrados').text(cerrados);
                $('#clientesCotizarArchivados').text(archivados);
            }

            async function renderCards() {
                const data = await getCurrentData();
                const search = $('#buscadorClienteCotizar').val().toLowerCase();
                const filtro = $('#filtroEstadoClienteCotizar').val();

                const visible = data.filter(item => {
                    const matchesSearch = !search || [item.nombre, item.empresa, item.producto, item.telefono].join(' ').toLowerCase().includes(search);
                    const matchesEstado = filtro === 'todos' || (filtro === 'archivado' ? item.archivado : (!item.archivado && item.estado === filtro));
                    return matchesSearch && matchesEstado;
                });

                const activos = visible.filter(item => !item.archivado);
                const archivados = visible.filter(item => item.archivado);

                const renderList = (items, targetId) => {
                    const $target = $(targetId);
                    if (!items.length) {
                        $target.html('<div class="empty-box">No hay resultados para este filtro.</div>');
                        return;
                    }

                    $target.html(items.map(item => {
                        const notas = Array.isArray(item.notas) ? item.notas : [];
                        const fotos = Array.isArray(item.fotos) ? item.fotos : [];

                        const notesHtml = notas.length ? notas.slice(0, 3).map(note => `
                            <div class="note-item">
                                <div class="small-label"><span>${escapeHtml(formatDate(note.fecha || item.created_at))}</span></div>
                                <div>${escapeHtml(note.texto || '')}</div>
                            </div>
                        `).join('') : '<div class="note-item text-muted">Sin notas aún.</div>';

                        const fotosHtml = fotos.length ? fotos.map((foto, index) => {
                            const url = escapeHtml(foto.url || foto.data || '');
                            const nombre = escapeHtml(foto.name || 'Evidencia');
                            const esImagen = String(foto.type || '').startsWith('image/') || String(url).startsWith('data:image/');
                            const vista = esImagen
                                ? `<img src="${url}" alt="Evidencia de cliente" title="${nombre}">`
                                : `<span class="small text-center px-1"><i class="bi bi-file-earmark-text d-block"></i>${nombre}</span>`;
                            return `<div class="client-photo-item">
                                <a href="${url}" target="_blank" rel="noopener" title="${nombre}">${vista}</a>
                                ${ES_MASTER ? `<button type="button" class="btn btn-danger btn-sm btn-quitar-foto-cotizar" data-id="${item.id}" data-foto-id="${Number(foto.id) || ''}" data-index="${index}" title="Quitar evidencia" aria-label="Quitar ${nombre}"><i class="bi bi-x"></i></button>` : ''}
                            </div>`;
                        }).join('') : '<span class="text-muted small">Sin fotos</span>';

                        const estadoClass = getEstadoClass(item.archivado ? 'archivado' : item.estado);
                        const estadoLabel = item.archivado ? 'Archivado' : getEstadoLabel(item.estado);

                        return `
                            <div class="cliente-card ${item.archivado ? 'status-archivado' : 'status-' + item.estado}">
                                <div class="cliente-card-header">
                                    <div>
                                        <h5>${escapeHtml(item.nombre)}</h5>
                                        <small class="text-muted">${escapeHtml(item.empresa || 'Sin empresa')}</small>
                                    </div>
                                    <span class="badge-estado ${estadoClass}">${estadoLabel}</span>
                                </div>

                                <div class="cliente-meta">
                                    <div class="meta-row"><i class="bi bi-telephone-fill"></i> <span>${escapeHtml(item.telefono || 'Sin teléfono')}</span></div>
                                    <div class="meta-row"><i class="bi bi-bag-check-fill"></i> <span>${escapeHtml(item.producto || 'Sin producto')}</span></div>
                                    <div class="meta-row"><i class="bi bi-whatsapp"></i> <span>Contacto por WA: ${formatDate(item.fecha_contacto_wa)}</span></div>
                                    <div class="meta-row"><i class="bi bi-person-badge-fill"></i> <span>Asignado: ${escapeHtml(item.asignado || 'Sin asignación')}</span></div>
                                    ${item.directorio_cliente_id ? '<div class="meta-row"><i class="bi bi-link-45deg"></i> <span>Vinculado al directorio</span></div>' : ''}
                                </div>

                                <div class="mini-badges">
                                    <span class="chip">${escapeHtml(notas.length)} notas</span>
                                    <span class="chip">${escapeHtml(fotos.length)} fotos</span>
                                </div>

                                <div class="notes-box">
                                    <div class="fw-bold mb-2 text-dark" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">Notas</div>
                                    <div class="note-list">${notesHtml}</div>
                                    <textarea class="form-control form-control-sm cct-nota-text" rows="2" data-id="${item.id}" placeholder="Guardar nota rápida..."></textarea>
                                    <button type="button" class="btn btn-sm btn-warning mt-2 btn-guardar-nota" data-id="${item.id}" style="border-radius: 10px;">
                                        <i class="bi bi-sticky-fill me-1"></i> Guardar nota
                                    </button>
                                </div>

                                <div class="client-images">${fotosHtml}</div>
                                <div class="mt-2">
                                    <label class="btn btn-sm btn-outline-primary w-100" style="border-radius: 10px;">
                                        <i class="bi bi-images me-2"></i> Agregar fotos
                                        <input type="file" class="d-none input-cotizar-fotos" data-id="${item.id}" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf">
                                    </label>
                                </div>

                                ${ES_MASTER ? `<div class="card-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-editar-cotizar" data-id="${item.id}" style="border-radius: 10px;">Editar</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-archivar-cotizar" data-id="${item.id}" style="border-radius: 10px;">${item.archivado ? 'Restaurar' : 'Archivar'}</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-cotizar" data-id="${item.id}" style="border-radius: 10px;">Eliminar</button>
                                </div>` : ''}
                            </div>
                        `;
                    }).join(''));
                };

                renderList(activos, '#listaClienteCotizar');
                renderList(archivados, '#listaClienteCotizarArchivado');
                renderStats(data);
            }

            function addLead() {
                const id = $('#clienteCotizarId').val();
                const payload = {
                    id: id || 'local-' + Date.now(),
                    nombre: $('#clienteCotizarNombre').val().trim(),
                    empresa: $('#clienteCotizarEmpresa').val().trim(),
                    telefono: $('#clienteCotizarTelefono').val().trim(),
                    producto: $('#clienteCotizarProducto').val().trim(),
                    estado: $('#clienteCotizarEstado').val() || 'pendiente',
                    fecha_contacto_wa: $('#clienteCotizarFechaContactoWa').val(),
                    vendedor_id: $('#clienteCotizarAsignadoId').val(),
                    asignado: $('#clienteCotizarAsignadoBusqueda').val().trim(),
                    directorio_cliente_id: $('#clienteCotizarDirectorioId').val(),
                    notas: [],
                    fotos: [],
                    archivado: false,
                    localOnly: false,
                    created_at: new Date().toISOString()
                };

                const nota = $('#clienteCotizarNotas').val().trim();
                if (nota) {
                    payload.notas.push({ fecha: new Date().toISOString().slice(0, 10), texto: nota });
                }

                const files = document.getElementById('clienteCotizarFotos').files;
                const formData = new FormData();
                formData.append('action', 'guardar');
                if (id) formData.append('id', id);
                formData.append('nombre', payload.nombre);
                formData.append('empresa', payload.empresa);
                formData.append('telefono', payload.telefono);
                formData.append('producto', payload.producto);
                formData.append('estado', payload.estado);
                formData.append('fecha_contacto_wa', payload.fecha_contacto_wa);
                formData.append('vendedor_id', payload.vendedor_id);
                formData.append('directorio_cliente_id', payload.directorio_cliente_id);
                formData.append('asignado', payload.asignado);
                if (nota) {
                    formData.append('notas', JSON.stringify(payload.notas));
                }
                for (let i = 0; i < files.length; i++) {
                    formData.append('fotos[]', files[i]);
                }

                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                }).done(function (res) {
                    if (res && res.success) {
                        const cached = getLocalData().filter(item => String(item.id) !== String(payload.id));
                        saveLocalData(cached);
                        resetForm();
                        mostrarModuloClientesCotizar();
                        renderCards();
                        Swal.fire('Guardado', 'El cliente quedó registrado en Clientes a Cotizar.', 'success');
                    } else {
                        Swal.fire('Error', res && res.message ? res.message : 'No se pudo guardar el cliente.', 'error');
                    }
                }).fail(function () {
                    const existing = getLocalData();
                    const idx = existing.findIndex(item => String(item.id) === String(payload.id));
                    if (idx >= 0) {
                        existing[idx] = { ...existing[idx], ...payload, localOnly: true };
                    } else {
                        existing.unshift({ ...payload, localOnly: true });
                    }
                    saveLocalData(existing);
                    resetForm();
                    mostrarModuloClientesCotizar();
                    renderCards();
                    Swal.fire(
                        'Guardado en este navegador',
                        'No se pudo conectar con el servidor. La tarjeta queda visible en este navegador, pero aún no está guardada en la base de datos.',
                        'warning'
                    );
                });
            }

            function editLead(id) {
                return getCurrentData().then(function (data) {
                    const item = data.find(entry => String(entry.id) === String(id));
                    if (!item) return;

                    $('#clienteCotizarId').val(item.id);
                    $('#clienteCotizarNombre').val(item.nombre || '');
                    $('#clienteCotizarEmpresa').val(item.empresa || '');
                    $('#clienteCotizarTelefono').val(item.telefono || '');
                    $('#clienteCotizarProducto').val(item.producto || '');
                    $('#clienteCotizarEstado').val(item.estado || 'pendiente');
                    $('#clienteCotizarFechaContactoWa').val(item.fecha_contacto_wa || '');
                    $('#clienteCotizarAsignadoId').val(item.vendedor_id || '');
                    $('#clienteCotizarAsignadoBusqueda').val(item.asignado || '');
                    $('#clienteCotizarAsignadoBusqueda').data('selected-name', item.asignado || '');
                    $('#resultadosVendedoresCotizar').empty();
                    $('#clienteCotizarDirectorioId').val(item.directorio_cliente_id || '');
                    if (item.directorio_cliente_id) {
                        $('#clienteCotizarYaRegistrado').val('si');
                        $('#panelBusquedaDirectorioCotizar').removeClass('d-none');
                        $('#clienteDirectorioSeleccionado')
                            .html(`<i class="bi bi-link-45deg me-1"></i> Vinculado al directorio: <strong>${escapeHtml(item.empresa || item.nombre)}</strong>`)
                            .removeClass('d-none');
                        $('#btnQuitarClienteDirectorio').removeClass('d-none');
                    } else {
                        $('#clienteCotizarYaRegistrado').val('no');
                        $('#panelBusquedaDirectorioCotizar').addClass('d-none');
                    }
                    $('#clienteCotizarNotas').val('');
                    $('#tituloFormClienteCotizar').text('Editar cliente');
                    $('#btnCancelarClienteCotizar').removeClass('d-none');
                    $('html, body').animate({ scrollTop: $('#vistaClientesCotizar').offset().top - 20 }, 300);
                });
            }

            function cargarVendedores() {
                if (!ES_MASTER) return;
                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { action: 'vendedores' }
                }).done(function (res) {
                    if (!res || !res.success || !Array.isArray(res.items)) {
                        Swal.fire('Error', res && res.message ? res.message : 'No se pudo cargar la lista de vendedores.', 'error');
                        return;
                    }
                    vendedoresActivosCotizar = res.items;
                }).fail(function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'No se pudo conectar para cargar la lista de vendedores.';
                    Swal.fire('Error', message, 'error');
                });
            }

            function archiveLead(id) {
                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'archivar', id: id }
                }).done(function (res) {
                    if (res && res.success) renderCards();
                }).fail(function () {
                    const data = getLocalData();
                    const item = data.find(entry => String(entry.id) === String(id));
                    if (!item) return;
                    item.archivado = !item.archivado;
                    item.estado = item.archivado ? 'archivado' : 'pendiente';
                    item.localOnly = true;
                    saveLocalData(data);
                    renderCards();
                    Swal.fire('Sin conexión con el servidor', 'El cambio quedó guardado solo en este navegador.', 'warning');
                });
            }

            function deleteLead(id) {
                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'eliminar', id: id }
                }).done(function (res) {
                    if (res && res.success) renderCards();
                }).fail(function () {
                    const data = getLocalData().filter(entry => String(entry.id) !== String(id));
                    saveLocalData(data);
                    renderCards();
                    Swal.fire('Sin conexión con el servidor', 'La tarjeta se quitó de este navegador, pero no se pudo confirmar su eliminación en la base de datos.', 'warning');
                });
            }

            function addNote(id) {
                const input = $('.cct-nota-text[data-id="' + id + '"]');
                const text = input.val().trim();
                if (!text) return;

                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'guardar_nota', id: id, texto: text }
                }).done(function (res) {
                    if (res && res.success) {
                        input.val('');
                        renderCards();
                    } else {
                        Swal.fire('Error', res && res.message ? res.message : 'No se pudo guardar la nota.', 'error');
                    }
                }).fail(function (xhr) {
                    if ([400, 403, 404].includes(xhr.status)) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'No se pudo guardar la nota.';
                        Swal.fire('Error', message, 'error');
                        return;
                    }
                    const data = getLocalData();
                    const item = data.find(entry => String(entry.id) === String(id));
                    if (!item) return;
                    item.notas = item.notas || [];
                    item.notas.push({ fecha: new Date().toISOString().slice(0, 10), texto: text });
                    item.localOnly = true;
                    saveLocalData(data);
                    input.val('');
                    renderCards();
                    Swal.fire('Sin conexión con el servidor', 'La nota quedó guardada solo en este navegador.', 'warning');
                });
            }

            function addFilesToLead(id, files) {
                if (!files || !files.length) return;
                const formData = new FormData();
                formData.append('action', 'subir_fotos');
                formData.append('id', id);
                for (let i = 0; i < files.length; i++) {
                    formData.append('fotos[]', files[i]);
                }

                $.ajax({
                    url: 'api/api_clientes_cotizar.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                }).done(function (res) {
                    if (res && res.success) {
                        renderCards();
                        Swal.fire('Evidencias guardadas', `Se agregaron ${res.guardadas} archivo(s) al cliente.`, 'success');
                    } else {
                        Swal.fire('Error', res && res.message ? res.message : 'No se pudieron guardar las evidencias.', 'error');
                    }
                }).fail(function (xhr) {
                    if ([400, 403, 404].includes(xhr.status)) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'No se pudieron guardar las evidencias.';
                        Swal.fire('Error', message, 'error');
                        return;
                    }
                    const data = getLocalData();
                    const item = data.find(entry => String(entry.id) === String(id));
                    if (!item) return;
                    item.fotos = item.fotos || [];
                    item.localOnly = true;
                    Array.from(files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function () {
                            item.fotos.push({ name: file.name, data: reader.result, type: file.type || 'image/png' });
                            saveLocalData(data);
                            renderCards();
                        };
                        reader.readAsDataURL(file);
                    });
                    Swal.fire('Sin conexión con el servidor', 'Las evidencias quedaron guardadas solo en este navegador.', 'warning');
                });
            }

            function removeFileFromLead(id, photoId, index) {
                if (photoId) {
                    $.ajax({
                        url: 'api/api_clientes_cotizar.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'eliminar_foto', id: id, foto_id: photoId }
                    }).done(function (res) {
                        if (!res || !res.success) {
                            Swal.fire('No se pudo quitar', res && res.message ? res.message : 'Intenta nuevamente.', 'error');
                            return;
                        }
                        renderCards();
                        if (res.warning) Swal.fire('Evidencia retirada', res.warning, 'warning');
                    }).fail(function (xhr) {
                        const mensaje = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'No se pudo quitar la evidencia del servidor.';
                        Swal.fire('Error', mensaje, 'error');
                    });
                    return;
                }

                const data = getLocalData();
                const item = data.find(entry => String(entry.id) === String(id));
                if (!item || !Array.isArray(item.fotos) || !item.fotos[index]) {
                    Swal.fire('No se pudo quitar', 'No se encontró la evidencia seleccionada.', 'error');
                    return;
                }
                item.fotos.splice(index, 1);
                saveLocalData(data);
                renderCards();
            }

            window.enviarClienteCotizar = function (event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                const form = document.getElementById('formClienteCotizar');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                if ($('#clienteCotizarYaRegistrado').val() === 'si' && !$('#clienteCotizarDirectorioId').val()) {
                    Swal.fire('Selecciona un cliente', 'Busca y elige el cliente del directorio, o cambia la opción a cliente nuevo.', 'warning');
                    return false;
                }
                if ($('#clienteCotizarAsignadoBusqueda').val().trim() && !$('#clienteCotizarAsignadoId').val()) {
                    Swal.fire('Selecciona un vendedor', 'Elige un vendedor activo de los resultados o deja el campo vacío.', 'warning');
                    return false;
                }
                addLead();
                return false;
            };

            function inicializarClientesCotizar() {
                cargarVendedores();

                $('#clienteCotizarAsignadoBusqueda').on('input focus', function (event) {
                    const valor = $(this).val().trim();
                    if (event.type === 'input' && $(this).data('selected-name') !== valor) {
                        $('#clienteCotizarAsignadoId').val('');
                        $(this).removeData('selected-name');
                    }

                    const busqueda = valor.toLocaleLowerCase();
                    const resultados = vendedoresActivosCotizar.filter(vendedor =>
                        vendedor.nombre.toLocaleLowerCase().includes(busqueda)
                    ).slice(0, 12);

                    $('#resultadosVendedoresCotizar').html(resultados.map(vendedor =>
                        `<button type="button" class="list-group-item list-group-item-action btn-elegir-vendedor-cotizar" data-id="${Number(vendedor.id)}">${escapeHtml(vendedor.nombre)}</button>`
                    ).join(''));
                });

                $(document).on('click', '.btn-elegir-vendedor-cotizar', function () {
                    const vendedor = vendedoresActivosCotizar.find(v => String(v.id) === String($(this).data('id')));
                    if (!vendedor) return;
                    $('#clienteCotizarAsignadoId').val(vendedor.id);
                    $('#clienteCotizarAsignadoBusqueda').val(vendedor.nombre);
                    $('#clienteCotizarAsignadoBusqueda').data('selected-name', vendedor.nombre);
                    $('#resultadosVendedoresCotizar').empty();
                });

                $('#clienteCotizarAsignadoBusqueda').on('keydown', function (event) {
                    if (event.key === 'Escape') $('#resultadosVendedoresCotizar').empty();
                });

                $('#clienteCotizarYaRegistrado').on('change', function () {
                    const quiereVincular = $(this).val() === 'si';
                    $('#panelBusquedaDirectorioCotizar').toggleClass('d-none', !quiereVincular);
                    if (!quiereVincular) {
                        if ($('#clienteCotizarDirectorioId').val()) {
                            $('#clienteCotizarNombre, #clienteCotizarEmpresa, #clienteCotizarTelefono').val('');
                        }
                        $('#clienteCotizarDirectorioId').val('');
                        $('#clienteDirectorioSeleccionado').addClass('d-none').empty();
                        $('#btnQuitarClienteDirectorio').addClass('d-none');
                        $('#busquedaDirectorioCotizar').val('');
                        $('#resultadosDirectorioCotizar').empty();
                    } else {
                        $('#busquedaDirectorioCotizar').trigger('focus');
                    }
                });

                $('#busquedaDirectorioCotizar').on('input', function () {
                    const termino = $(this).val();
                    clearTimeout(directorioSearchTimer);
                    directorioSearchTimer = setTimeout(function () {
                        buscarClienteDirectorio(termino);
                    }, 250);
                });

                $(document).on('click', '.btn-elegir-directorio-cotizar', function () {
                    const cliente = clientesDirectorioCotizar[String($(this).data('id'))];
                    if (cliente) seleccionarClienteDirectorio(cliente);
                });

                $('#btnQuitarClienteDirectorio').on('click', function () {
                    $('#clienteCotizarDirectorioId').val('');
                    $('#clienteCotizarNombre, #clienteCotizarEmpresa, #clienteCotizarTelefono').val('');
                    $('#clienteDirectorioSeleccionado').addClass('d-none').empty();
                    $(this).addClass('d-none');
                    $('#busquedaDirectorioCotizar').trigger('focus');
                });

                $('#btnAbrirFormClienteCotizar').on('click', function () {
                    resetForm();
                    $('#clienteCotizarNombre').focus();
                });

                $('#btnCancelarClienteCotizar').on('click', function () {
                    resetForm();
                });

                $('#buscadorClienteCotizar').on('input', renderCards);
                $('#filtroEstadoClienteCotizar').on('change', renderCards);

                $(document).on('click', '.btn-editar-cotizar', function () {
                    editLead($(this).data('id'));
                });

                $(document).on('click', '.btn-archivar-cotizar', function () {
                    archiveLead($(this).data('id'));
                });

                $(document).on('click', '.btn-eliminar-cotizar', function () {
                    const id = $(this).data('id');
                    if (confirm('¿Deseas eliminar definitivamente este cliente de la lista?')) {
                        deleteLead(id);
                    }
                });

                $(document).on('click', '.btn-guardar-nota', function () {
                    addNote($(this).data('id'));
                });

                $(document).on('change', '.input-cotizar-fotos', function (event) {
                    const files = event.target.files;
                    const id = $(this).data('id');
                    addFilesToLead(id, files);
                    event.target.value = '';
                });

                $(document).on('click', '.btn-quitar-foto-cotizar', function () {
                    const $button = $(this);
                    Swal.fire({
                        title: '¿Quitar esta evidencia?',
                        text: 'El archivo se eliminará de la tarjeta del cliente.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, quitar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            removeFileFromLead($button.data('id'), $button.attr('data-foto-id'), Number($button.attr('data-index')));
                        }
                    });
                });

                renderCards();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', inicializarClientesCotizar, { once: true });
            } else {
                inicializarClientesCotizar();
            }

            window.renderClientesCotizar = renderCards;
        })();
    </script>
</div>
<?php endif; ?>
