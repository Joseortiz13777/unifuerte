<div class="tab-pane fade" id="tab-eventos" role="tabpanel">
    <input type="hidden" id="csrfEventos" value="<?= htmlspecialchars($csrf_eventos, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4 class="mb-1"><i class="bi bi-calendar-event text-warning me-2"></i>Solicitudes de asesoría</h4>
                    <p class="text-muted mb-0">Revisa las fechas propuestas, confirma o rechaza, responde por WhatsApp y elimina solicitudes de prueba.</p>
                </div>
                <button type="button" class="btn btn-outline-primary" id="btnRecargarEventos"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Solicitud</th>
                            <th>Cliente</th>
                            <th>Motivo / modalidad</th>
                            <th>Fecha propuesta</th>
                            <th>Necesidad</th>
                            <th>Estado / acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEventos">
                        <tr><td colspan="6" class="text-center text-muted py-4">Cargando solicitudes...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
