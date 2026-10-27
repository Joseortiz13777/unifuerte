<div class="tab-pane fade" id="tab-cobrar">
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-success text-white pt-4 pb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2"></i> Cuentas Exigibles a Clientes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th class="ps-4">Cotizaci&oacute;n</th><th>Cliente</th><th class="text-end">Deuda Restante</th><th class="text-center pe-4">Acciones / Cobrar</th></tr></thead>
                    <tbody id="tbodyCobrar"></tbody>
                    <tfoot class="bg-light" id="tfootCobrar"></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER DETALLE COTIZACIÓN Y PAGOS (EXCLUSIVO ADMIN) -->
<div class="modal fade" id="modalVerDetalleCotizacionAdmin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-list-check"></i> Detalle del Pedido y Pagos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="table-responsive">
                    <table class="table table-hover bg-white rounded shadow-sm align-middle" style="font-size: 0.9rem;">
                        <thead class="table-light"><tr><th>Prenda / Arte</th><th>Posición / Detalle</th><th class="text-center">Cant.</th><th class="text-end">Precio Unit.</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody id="detallesCotizacionTablaBody"></tbody>
                    </table>
                </div>
                <div class="d-flex flex-column align-items-end mt-2 mb-4">
                    <div class="text-muted small">Subtotal: <span id="det_modal_subtotal" class="fw-bold text-dark"></span></div>
                    <div class="text-danger small">Descuento: <span id="det_modal_descuento" class="fw-bold"></span></div>
                    <div class="text-muted small">IVA (16%): <span id="det_modal_iva" class="fw-bold text-dark"></span></div>
                    <h5 class="fw-bold mt-1" style="color: #0B4C8C;">Total Facturado: <span id="det_modal_total"></span></h5>
                </div>
                
                <div id="box_comprobantes_vista" class="mt-4 border-top border-2 border-primary pt-3" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark m-0"><i class="bi bi-receipt"></i> Historial de Pagos del Cliente</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white shadow-sm align-middle text-center" style="font-size: 0.85rem;">
                            <thead class="table-light"><tr><th>Fecha</th><th>Tasa BCV</th><th>Monto USD</th><th>Banco / Método</th><th>Referencia</th><th>Comprobante</th></tr></thead>
                            <tbody id="tbody_pagos_multiples"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar Visor</button>
            </div>
        </div>
    </div>
</div>