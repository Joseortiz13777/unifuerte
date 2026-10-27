<style>
    .card-objetivo-fija {
        border: 2px solid #d93845;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .card-objetivo-header {
        background-color: #d93845;
        color: #ffffff;
        padding: 10px 14px;
        font-weight: bold;
    }
    .input-titulo-objetivo {
        border: 2px solid #0B4C8C;
        font-weight: 700;
        color: #0B4C8C;
        background-color: #f8fbff;
    }
    .input-titulo-objetivo:focus {
        background-color: #ffffff;
        box-shadow: 0 0 0 0.2rem rgba(11, 76, 140, 0.2);
    }
    .tarea-caja {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .tarea-caja.border-warning {
        border: 1.5px solid #f59f00 !important;
        background: #fffdf7;
    }
    .tarea-caja.completada {
        background-color: #e8f5e9;
        border-color: #198754 !important;
    }
    .tarea-caja.completada .texto-tarea {
        text-decoration: line-through;
        color: #6c757d;
    }
    .tarea-caja-contenido {
        flex: 1;
        min-width: 0;
    }
    .tarea-caja-acciones {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .check-tarea {
        width: 1.35rem;
        height: 1.35rem;
        cursor: pointer;
        flex-shrink: 0;
    }
    .tarea-adjunto-preview {
        max-height: 90px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
    .btn-actualizar-objetivo {
        background-color: #198754;
        color: #ffffff;
        font-weight: bold;
        border: none;
        border-radius: 6px;
        padding: 9px 12px;
    }
    .btn-actualizar-objetivo:hover {
        background-color: #146c43;
        color: #ffffff;
    }
</style>

<div class="tab-pane fade" id="tab-tareas">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-card-checklist text-primary me-2"></i> Mi Agenda / 4 Objetivos Fijos</h5>
            <small class="text-muted">Escribe el título de cada objetivo arriba, ancla sus tareas y completa el checklist para subir la barra al 100%.</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary fw-bold shadow-sm" onclick="abrirHistorialTareas()">
                <i class="bi bi-archive me-1"></i> Historial
            </button>
            <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="abrirModalNuevaTarea(1)">
                <i class="bi bi-plus-lg me-1"></i> Agregar Tarea
            </button>
        </div>
    </div>

    <!-- CUADRÍCULA DE LAS 4 TARJETAS FIJAS DE OBJETIVOS -->
    <div class="row g-4">
        <?php for ($obj = 1; $obj <= 4; $obj++): ?>
        <div class="col-12 col-lg-6">
            <div class="card-objetivo-fija shadow-sm">
                <div class="card-objetivo-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bullseye me-1"></i> OBJETIVO #<?= $obj ?></span>
                    <span class="small lbl-fecha-tareas-card">--/--/----</span>
                </div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <!-- INPUT SUPERIOR PARA ESCRIBIR EL OBJETIVO -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            <i class="bi bi-flag-fill text-danger me-1"></i> Escribe el Objetivo de esta Tarjeta:
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text"
                                   id="input_titulo_objetivo_<?= $obj ?>"
                                   class="form-control input-titulo-objetivo"
                                   data-nro="<?= $obj ?>"
                                   placeholder="Ej: Meta de Producción / Cobranzas / Bordados..."
                                   value="Objetivo #<?= $obj ?>">
                            <button class="btn btn-primary fw-bold" type="button" onclick="guardarTituloObjetivo(<?= $obj ?>, true)" title="Guardar nombre del objetivo">
                                <i class="bi bi-check2"></i> Fijar
                            </button>
                        </div>
                    </div>

                    <!-- BARRA DE PROGRESO DEL OBJETIVO -->
                    <div class="mb-3 bg-light p-2 rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark" id="lbl_detalle_progreso_<?= $obj ?>">0 de 0 completadas</span>
                            <span class="badge bg-secondary fw-bold" id="badge_porcentaje_<?= $obj ?>">0%</span>
                        </div>
                        <div class="progress" style="height: 14px; border-radius: 8px;">
                            <div id="barra_progreso_objetivo_<?= $obj ?>"
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                                 role="progressbar"
                                 style="width: 0%;"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold m-0" style="color: #d93845;">Tareas ancladas al objetivo:</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold py-0 px-2" onclick="abrirModalNuevaTarea(<?= $obj ?>)">
                            <i class="bi bi-plus-circle me-1"></i> Añadir aquí
                        </button>
                    </div>

                    <!-- LISTA DE TAREAS DE ESTE OBJETIVO -->
                    <div id="contenedorTareasObjetivo_<?= $obj ?>" class="flex-grow-1 mb-2" style="min-height: 130px; max-height: 310px; overflow-y: auto;">
                        <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div></div>
                    </div>

                    <!-- BOTÓN INFERIOR VERDE -->
                    <div class="d-flex gap-2 mt-auto pt-2 border-top">
                        <button type="button" class="btn-actualizar-objetivo flex-grow-1 shadow-sm" onclick="cargarTareasHoy()">
                            <i class="bi bi-check2-all me-1"></i> ACTUALIZAR LISTA
                        </button>
                        <button type="button" class="btn btn-outline-success fw-bold btn-sm" onclick="limpiarCompletadasObjetivo(<?= $obj ?>)" title="Enviar tareas completadas de este objetivo al historial">
                            <i class="bi bi-archive"></i> Limpiar Listas
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- MODAL CREAR / EDITAR TAREA -->
<div class="modal fade" id="modalFormTarea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formTarea" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="titModalTarea"><i class="bi bi-pencil-square"></i> Nueva Tarea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <input type="hidden" name="accion" id="accion_tarea" value="crear_tarea">
                <input type="hidden" name="id_tarea" id="id_tarea_edit" value="">
                <input type="hidden" name="fecha_tarea" id="input_fecha_tarea" value="">

                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-1">Seleccionar Tarjeta / Objetivo Destino *</label>
                    <select name="nro_objetivo" id="select_nro_objetivo" class="form-select border-primary fw-bold" required>
                        <option value="1">Objetivo #1</option>
                        <option value="2">Objetivo #2</option>
                        <option value="3">Objetivo #3</option>
                        <option value="4">Objetivo #4</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-1">Descripción de la tarea *</label>
                    <textarea name="tarea" id="txt_tarea_desc" class="form-control border-primary fw-bold" rows="3" placeholder="Escribe la tarea que quedará anclada en este objetivo..." required></textarea>
                </div>

                <div class="mb-2" id="box_foto_tarea">
                    <label class="small text-muted fw-bold mb-1">Adjuntar Imagen (Opcional)</label>
                    <input type="file" name="foto" id="foto_tarea" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div id="foto_actual_tarea" class="mt-2" hidden></div>
                    <div class="form-check mt-2" id="quitar_foto_tarea_label" hidden>
                        <input class="form-check-input" type="checkbox" name="quitar_foto" id="quitar_foto_tarea" value="1">
                        <label class="form-check-label small text-danger fw-bold" for="quitar_foto_tarea">Eliminar imagen actual</label>
                    </div>
                    <div id="foto_nueva_preview" class="mt-2" hidden></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnGuardarTarea"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORIAL DE TAREAS -->
<div class="modal fade" id="modalHistorialTareas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-archive"></i> Historial de Tareas Archivadas / Anteriores</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Fecha</th>
                                <th>Objetivo</th>
                                <th>Tarea</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistorialTareas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>