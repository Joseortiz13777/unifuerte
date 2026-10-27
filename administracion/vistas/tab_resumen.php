<div class="tab-pane fade show active" id="tab-resumen">
    <div class="row mb-4 g-2">
        <div class="col-6">
            <button class="btn btn-success w-100 p-3 shadow-sm text-start" onclick="abrirModalMovimiento('INGRESO')">
                <div class="fs-5 fw-bold"><i class="bi bi-plus-circle"></i> Nuevo Ingreso</div>
                <div class="small text-white-50">Multimoneda (USD/BS)</div>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-danger w-100 p-3 shadow-sm text-start" onclick="abrirModalMovimiento('GASTO')">
                <div class="fs-5 fw-bold"><i class="bi bi-dash-circle"></i> Nuevo Egreso</div>
                <div class="small text-white-50">Afecta saldos de bóveda</div>
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Bóveda -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div class="card-header border-secondary border-opacity-25 pt-3 pb-2 d-flex justify-content-between align-items-center bg-transparent">
                    <h5 class="fw-bold mb-0"><i class="bi bi-safe2 me-2 text-info"></i>Cuentas y Efectivo (Bóveda)</h5>
                    <button class="btn btn-sm btn-outline-light border-0" onclick="abrirConfiguracion()"><i class="bi bi-gear"></i></button>
                </div>
                <div class="card-body" id="listaCuentasBoveda">
                    <div class="text-center py-3"><div class="spinner-border text-info"></div></div>
                </div>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-clipboard-data me-2"></i>Resumen General (USD)</h5>
                </div>
                <div class="card-body">
                    <!-- Ingresos Detallados -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-success fw-bold fs-5 border-bottom pb-1">
                            <span>Ingresos Totales</span><span id="lbl_ingresos_total">0.00 $</span>
                        </div>
                        <div class="d-flex justify-content-between small mt-1 text-muted">
                            <span>Cobros de Cotizaciones:</span><span id="lbl_ingresos_cotiz">0.00 $</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Ingresos Extras/Aportes:</span><span id="lbl_ingresos_manual">0.00 $</span>
                        </div>
                    </div>

                    <!-- Egresos Detallados -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between text-danger fw-bold fs-5 border-bottom pb-1">
                            <span>Egresos Totales</span><span id="lbl_egresos_total">0.00 $</span>
                        </div>
                        <div class="d-flex justify-content-between small mt-1 text-muted">
                            <span>Gastos Fijos (Nómina):</span><span id="lbl_egresos_nomina">0.00 $</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Compras (Insumos Textiles):</span><span id="lbl_egresos_compras">0.00 $</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Gastos Extras/Servicios:</span><span id="lbl_egresos_manual">0.00 $</span>
                        </div>
                    </div>

                    <!-- Balance -->
                    <div class="text-end bg-light p-3 rounded">
                        <span class="text-muted small d-block">Utilidad o Déficit del Mes</span>
                        <h2 class="fw-bold mb-0 text-dark" id="lbl_balance_mes">0.00 $</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>