<!-- ================= VISTA DE CONSUMOS DE ÓRDENES ================= -->
<div id="vistaConsumos" class="vista-spa" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold" style="color: #0B4C8C;"><i class="bi bi-scissors me-2" style="color: #FF7518;"></i> Consumo de Órdenes (Inventario)</h3>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Nro Orden</th>
                            <th>Fecha Emisión</th>
                            <th>Cliente</th>
                            <th>Estado Inventario</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyOrdenesConsumo">
                        <!-- Llenado por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL PREVISUALIZAR CONSUMO ================= -->
<div class="modal fade" id="modalConsumoOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-calculator"></i> Previsualización de Consumo: <span id="lbl_orden_consumo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <p class="text-muted small mb-3">El sistema ha cruzado las prendas de esta orden con las recetas guardadas. Esto es lo que se requiere del almacén:</p>
                <div class="table-responsive bg-white rounded shadow-sm border p-2">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo / Materia Prima</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-end">A Descontar</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalleConsumo">
                            <!-- Llenado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmarDescuento" onclick="confirmarDescuentoInventario()"><i class="bi bi-check-circle"></i> Confirmar Descuento</button>
            </div>
        </div>
    </div>
</div>