<div class="tab-pane fade" id="tab-historial">
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white pt-4 pb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-journal-text me-2"></i>Libro Mayor del Mes</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="historialFiltroTipo" class="form-select form-select-sm w-auto" onchange="renderHistorialFiltrado()">
                    <option value="ALL">Todos</option>
                    <option value="INGRESO">Ingresos</option>
                    <option value="EGRESO">Egresos</option>
                </select>
                <button class="btn btn-sm btn-outline-primary" onclick="cargarHistorial()"><i class="bi bi-arrow-clockwise"></i> Refrescar</button>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-success bg-opacity-10 h-100">
                        <div class="small text-success fw-bold text-uppercase">Ingresos</div>
                        <div id="historialTotalIngresos" class="fw-bold fs-5 text-success mt-2">$ 0.00</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-danger bg-opacity-10 h-100">
                        <div class="small text-danger fw-bold text-uppercase">Egresos</div>
                        <div id="historialTotalEgresos" class="fw-bold fs-5 text-danger mt-2">$ 0.00</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-primary bg-opacity-10 h-100">
                        <div class="small text-primary fw-bold text-uppercase">Neto</div>
                        <div id="historialTotalNeto" class="fw-bold fs-5 text-primary mt-2">$ 0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th class="ps-4">Fecha</th><th>Tipo</th><th>Categor&iacute;a / Cuenta</th><th>Descripci&oacute;n</th><th class="text-end">Monto</th><th class="text-center pe-4">Acci&oacute;n</th></tr></thead>
                    <tbody id="tbodyHistorial"><tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>