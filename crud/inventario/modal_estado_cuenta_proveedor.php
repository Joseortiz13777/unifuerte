<!-- modal_estado_cuenta_proveedor.php -->
<div class="modal fade" id="modalDeudaProveedor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background-color: #dc3545;">
        <h5 class="modal-title fw-bold"><i class="bi bi-wallet2"></i> Estado de Cuenta: <span id="lbl_nombre_proveedor_deuda"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <div class="row mb-3">
            <div class="col-md-6">
                <h4 class="m-0 text-muted">Deuda Total Pendiente: <strong class="text-danger" id="lbl_deuda_total_modal">0.00 $</strong></h4>
            </div>
        </div>
        <div class="table-responsive bg-white p-2 rounded shadow-sm border">
            <table class="table table-striped table-hover align-middle table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID Operación</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Factura/Ref</th>
                        <th class="text-end">Monto Total</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end text-danger">Saldo Deudor</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="tabla_deudas_body">
                    <tr><td colspan="8" class="text-center">Cargando deudas...</td></tr>
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>