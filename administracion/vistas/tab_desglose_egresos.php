<div class="tab-pane fade" id="tab-egresos">
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <!-- Cabecera con el selector/filtro incluido -->
        <div class="card-header bg-danger bg-opacity-10 pt-4 pb-3 d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="fw-bold text-danger mb-0"><i class="bi bi-file-earmark-minus me-2"></i>Egresos Fijos (Nomina e Insumos)</h5>
            <div class="mt-2 mt-md-0">
                <select id="filtroProveedorEgresos" class="form-select form-select-sm shadow-sm border-danger text-danger fw-bold" onchange="window.renderEgresosFiltrados()">
                    <option value="ALL">Todos los Proveedores / Conceptos</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th class="ps-4">Concepto / ID</th><th>Tipo</th><th>Beneficiario / Proveedor</th><th>Fecha</th><th class="text-end">Monto</th><th class="text-center pe-4">Acciones</th></tr></thead>
                    <tbody id="tbodyDesgloseEgresos"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>