<div class="tab-pane fade" id="tab-pagar">
    <div class="card shadow-sm border-0 rounded-3 mb-4 bg-light">
        <div class="card-header bg-danger text-white pt-4 pb-3 d-flex justify-content-between align-items-center rounded-top">
            <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2"></i> Pasivos y Egresos Pendientes</h5>
            <button class="btn btn-sm btn-light text-danger fw-bold shadow-sm" onclick="abrirModalNuevaCuentaPagar()"><i class="bi bi-plus-circle"></i> Nueva Cuenta por Pagar</button>
        </div>
        <div class="card-body p-3">
            
            <div class="accordion shadow-sm" id="accordionPorPagar">
                
                <!-- 1. NOMINA POR EMPLEADO -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#colNomina">
                            <i class="bi bi-people-fill text-primary me-2 fs-5"></i> 1. Nomina por Pagar (Por Empleado) 
                            <span class="badge bg-danger ms-auto fs-6" id="badge_nomina">0.00 $</span>
                        </button>
                    </h2>
                    <div id="colNomina" class="accordion-collapse collapse show" data-bs-parent="#accordionPorPagar">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th class="ps-4">Empleado / Lote</th><th>Cargo / Dpto</th><th class="text-end">Deuda Restante</th><th class="text-center pe-4">Acciones</th></tr></thead>
                                    <tbody id="tbodyPagarNomina"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. COMPRAS TEXTILES -->
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#colCompras">
                            <i class="bi bi-box-seam text-warning me-2 fs-5"></i> 2. Compras (Insumos Textiles)
                            <span class="badge bg-danger ms-auto fs-6" id="badge_compras">0.00 $</span>
                        </button>
                    </h2>
                    <div id="colCompras" class="accordion-collapse collapse" data-bs-parent="#accordionPorPagar">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th class="ps-4">Doc / Origen</th><th>Proveedor</th><th class="text-end">Deuda Restante</th><th class="text-center pe-4">Acciones</th></tr></thead>
                                    <tbody id="tbodyPagarCompras"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. SERVICIOS Y GASTOS -->
                <div class="accordion-item border-0 mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#colServicios">
                            <i class="bi bi-lightning-charge text-success me-2 fs-5"></i> 3. Servicios y Gastos Operativos
                            <span class="badge bg-danger ms-auto fs-6" id="badge_servicios">0.00 $</span>
                        </button>
                    </h2>
                    <div id="colServicios" class="accordion-collapse collapse" data-bs-parent="#accordionPorPagar">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th class="ps-4">Doc / Origen</th><th>Beneficiario / Concepto</th><th class="text-end">Deuda Restante</th><th class="text-center pe-4">Acciones</th></tr></thead>
                                    <tbody id="tbodyPagarServicios"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="card-footer bg-white border-top-0 text-end py-3 rounded-bottom">
            <h5 class="fw-bold text-secondary mb-0">TOTAL PASIVOS GENERAL: <span class="text-danger fs-4 ms-2" id="lblTotalPasivos">0.00 $</span></h5>
        </div>
    </div>
</div>