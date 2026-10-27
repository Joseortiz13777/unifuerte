<!-- VISTA 8: GASTOS POR SERVICIOS (AIRE ACOND. ETC)-->
<div id="vistaGastos" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-tools me-2" style="color: #FF7518;"></i> Gastos por Servicios y Mantenimiento</h3>
        <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="abrirModalGasto()"><i class="bi bi-plus-lg"></i> Registrar Nuevo Gasto</button>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border">
        <div class="row mb-3 g-2">
            <div class="col-md-12">
                <input type="text" id="buscadorGastos" class="form-control" placeholder="Buscar por proveedor, descripción, factura...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Factura/Ref</th><th>Descripción</th><th>Monto Total</th><th>Estado Pago</th><th class="text-center">Acciones</th></tr>
                </thead>
                <tbody id="tbodyGastos"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO DE GASTO POR SERVICIO -->
<div class="modal fade" id="modalGastoServicio" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="formGastoServicio" class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools"></i> Registrar Gasto / Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="gasto_id_edit" name="id_gasto" value="0">
                <input type="hidden" name="accion" value="guardar_gasto">
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted">Proveedor de Servicio *</label>
                        <select name="id_proveedor" id="gasto_proveedor" class="form-select border-primary" style="width: 100%;" required></select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Fecha *</label>
                        <input type="date" name="fecha" id="gasto_fecha" class="form-control border-primary" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Nro. Factura / Referencia</label>
                        <input type="text" name="factura" id="gasto_factura" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted text-danger">Monto Total a Pagar ($) *</label>
                        <input type="number" step="0.01" name="monto_total" id="gasto_monto_total" class="form-control border-danger fw-bold text-end" required>
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Descripción del Servicio *</label>
                        <textarea name="descripcion" id="gasto_desc" class="form-control" rows="3" placeholder="Ej: Reparación de Máquina de Coser, Mantenimiento de Aire Acondicionado..." required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white fw-bold" style="background-color: #0B4C8C;" id="btnGuardarGasto"><i class="bi bi-save"></i> Guardar Gasto</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL GESTIÓN DE PAGOS DE GASTO (SERVICIOS) -->
<div class="modal fade" id="modalPagosGasto" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #198754;">
                <h5 class="modal-title fw-bold"><i class="bi bi-wallet2"></i> Cuentas por Pagar al Proveedor de Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="row">
                    <div class="col-md-4">
                        <div class="bg-white p-3 rounded border shadow-sm h-100">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3">Registrar Nuevo Pago</h6>
                            <form id="formPagoGasto" enctype="multipart/form-data">
                                <input type="hidden" name="accion" value="guardar_pago_gasto">
                                <input type="hidden" name="id_gasto" id="pago_gasto_id">
                                
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Fecha de Pago *</label>
                                    <input type="date" name="fecha_pago" id="pago_gasto_fecha" class="form-control border-success" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Monto Abonado ($) *</label>
                                    <input type="number" step="0.01" name="monto_usd" id="pago_gasto_usd" class="form-control fw-bold border-success text-end" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Método de Pago / Banco *</label>
                                    <input type="text" name="metodo_pago" id="pago_gasto_metodo" class="form-control" placeholder="Ej: Zelle, Efectivo" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Referencia</label>
                                    <input type="text" name="referencia" id="pago_gasto_ref" class="form-control" placeholder="Ej: 123456">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted">Comprobante (Imagen/PDF)</label>
                                    <input type="file" name="comprobante" id="pago_gasto_comprobante" class="form-control" accept="image/*,.pdf">
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-bold" id="btnGuardarAbonoGasto"><i class="bi bi-save"></i> Guardar Pago</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="bg-white p-3 rounded border shadow-sm h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="fw-bold text-dark m-0"><i class="bi bi-clock-history"></i> Historial de Pagos</h6>
                                <div class="text-end">
                                    <span class="d-block small text-muted fw-bold">Deuda Total: <span id="lbl_deuda_total_gasto" class="text-dark">0.00</span></span>
                                    <span class="d-block small text-muted fw-bold">Resta por Pagar: <span id="lbl_deuda_restante_gasto" class="text-danger fs-6">0.00</span></span>
                                </div>
                            </div>
                            <div class="table-responsive flex-grow-1">
                                <table class="table table-sm align-middle table-hover">
                                    <thead class="table-light"><tr><th>Fecha</th><th>Método</th><th>Ref</th><th class="text-end">Abono ($)</th><th class="text-center">Soporte</th><th></th></tr></thead>
                                    <tbody id="tbodyPagosGasto">
                                        <tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>