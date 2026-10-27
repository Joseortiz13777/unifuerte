<!-- MODAL: REGISTRO DE MOVIMIENTO -->
<div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" id="movHeader"><h5 class="modal-title fw-bold" id="movTitle">...</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
      <form id="formMovimiento">
          <div class="modal-body p-4">
            <input type="hidden" id="movTipo" name="tipo"> <input type="hidden" name="accion" value="guardar_movimiento">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label text-muted small fw-bold">Cuenta de Afectación</label><select class="form-select bg-light fw-bold" name="id_cuenta" id="movCuenta" required onchange="validarMoneda()"></select></div>
                <div class="col-md-6"><label class="form-label text-muted small fw-bold">Fecha</label><input type="date" class="form-control" name="fecha" id="movFecha" required></div>
                <div class="col-md-4"><label class="form-label text-muted small fw-bold">Moneda</label><select class="form-select border-primary" name="moneda_origen" id="movMoneda" required onchange="validarMoneda()"><option value="USD">Dólares (USD)</option><option value="BS">Bolívares (BS)</option></select></div>
                <div class="col-md-4"><label class="form-label text-muted small fw-bold">Monto Registrado</label><input type="number" step="0.01" class="form-control fw-bold text-end" name="monto" id="movMonto" required placeholder="0.00" onkeyup="calcularEquivalente()" onchange="calcularEquivalente()"></div>
                <div class="col-md-4"><label class="form-label text-muted small fw-bold">Tasa BCV</label><input type="number" step="0.0001" class="form-control text-end bg-light" name="tasa_bcv" id="movTasa" value="1.0000" onkeyup="calcularEquivalente()" onchange="calcularEquivalente()"></div>
                <div class="col-12 mt-2 text-end"><small class="text-muted fw-bold p-2 bg-light rounded border shadow-sm">Cálculo Interno: <span id="lblEquivalente" class="text-primary fs-5">0.00 USD</span></small></div>
                <div class="col-md-6"><label class="form-label text-muted small fw-bold">Categoría</label><select class="form-select" name="id_categoria" id="movCategoria" required></select></div>
                <div class="col-md-6"><label class="form-label text-muted small fw-bold">Referencia Bancaria</label><input type="text" class="form-control" name="referencia" placeholder="..."></div>
                <div class="col-md-12"><label class="form-label text-muted small fw-bold">Descripción</label><textarea class="form-control" name="descripcion" rows="2" required></textarea></div>
            </div>
          </div>
          <div class="modal-footer bg-light border-0"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4 fw-bold" id="btnGuardarMov">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL GESTOR DE CONFIGURACIÓN -->
<div class="modal fade" id="modalConfig" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i> Gestor del Sistema Contable</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
         <ul class="nav nav-tabs px-3 pt-3" id="configTab">
             <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cfg-cat">Categorías Manuales</button></li>
             <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cfg-cta">Cuentas Bancarias / Bóveda</button></li>
         </ul>

         <div class="tab-content p-4 bg-light">
             <div class="tab-pane fade show active" id="cfg-cat">
                 <div class="row">
                     <div class="col-md-4">
                         <div class="card shadow-sm border-0"><div class="card-body">
                             <h6 class="fw-bold border-bottom pb-2 text-primary">Añadir Categoría</h6>
                             <form id="formAddCat" class="mt-3">
                                 <input type="hidden" name="accion" value="crear_categoria">
                                 <input type="text" class="form-control mb-3" name="nombre" placeholder="Nombre (Ej: Papelería)" required>
                                 <select class="form-select mb-3" name="tipo"><option value="GASTO">Para Gastos</option><option value="INGRESO">Para Ingresos</option></select>
                                 <button class="btn btn-primary w-100 fw-bold">Guardar</button>
                             </form>
                         </div></div>
                     </div>
                     <div class="col-md-8">
                         <div class="card shadow-sm border-0"><div class="card-body p-0">
                             <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                 <table class="table table-hover mb-0">
                                     <thead class="table-light sticky-top"><tr><th class="ps-3">ID</th><th>Nombre</th><th>Tipo</th><th class="text-center pe-3">Acciones</th></tr></thead>
                                     <tbody id="tbodyCategoriasAdmin"></tbody>
                                 </table>
                             </div>
                         </div></div>
                     </div>
                 </div>
             </div>
             <div class="tab-pane fade" id="cfg-cta">
                 <div class="row">
                     <div class="col-md-4">
                         <div class="card shadow-sm border-0"><div class="card-body">
                             <h6 class="fw-bold border-bottom pb-2 text-success">Añadir Cuenta Bóveda</h6>
                             <form id="formAddCuenta" class="mt-3">
                                 <input type="hidden" name="accion" value="crear_cuenta">
                                 <input type="text" class="form-control mb-3 form-control-sm" name="nombre" placeholder="Nombre (Ej: Banesco)" required>
                                 <input type="text" class="form-control mb-3 form-control-sm" name="nro_cuenta" placeholder="Nro. Cuenta o Teléfono principal">
                                 <select class="form-select mb-3 form-control-sm" name="moneda"><option value="USD">Dólares (USD)</option><option value="BS">Bolívares (BS)</option></select>
                                 <input type="number" step="0.01" class="form-control mb-3 form-control-sm" name="saldo" placeholder="Saldo Inicial" value="0">
                                 <hr>
                                 <h6 class="text-muted small mb-2 fw-bold">Datos Pago Móvil (Opcional)</h6>
                                 <input type="text" class="form-control mb-2 form-control-sm" name="pm_banco" placeholder="Banco Pago Móvil">
                                 <input type="text" class="form-control mb-2 form-control-sm" name="pm_cedula" placeholder="Cédula Pago Móvil">
                                 <input type="text" class="form-control mb-3 form-control-sm" name="pm_tlf" placeholder="Teléfono Pago Móvil">
                                 <button class="btn btn-success w-100 fw-bold">Guardar Cuenta</button>
                             </form>
                         </div></div>
                     </div>
                     <div class="col-md-8">
                         <div class="card shadow-sm border-0"><div class="card-body p-0">
                             <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                 <table class="table table-hover mb-0">
                                     <thead class="table-light sticky-top"><tr><th class="ps-3">Banco</th><th>Nro. Cuenta</th><th>Moneda</th><th class="text-end">Saldo Actual</th><th class="text-center pe-3">Acciones</th></tr></thead>
                                     <tbody id="tbodyCuentasAdmin"></tbody>
                                 </table>
                             </div>
                         </div></div>
                     </div>
                 </div>
             </div>
         </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL EDITAR CUENTA BANCARIA -->
<div class="modal fade" id="modalEditarCuenta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Editar Cuenta</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarCuenta">
          <div class="modal-body p-4 bg-light">
              <input type="hidden" name="accion" value="editar_cuenta">
              <input type="hidden" name="id" id="edit_cta_id">
              <div class="mb-3"><label class="small text-muted fw-bold mb-1">Nombre Banco *</label><input type="text" class="form-control border-primary" name="nombre" id="edit_cta_nombre" required></div>
              <div class="mb-4"><label class="small text-muted fw-bold mb-1">Nro de Cuenta / Tlf principal</label><input type="text" class="form-control" name="nro_cuenta" id="edit_cta_nro"></div>
              <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Datos Pago Móvil (Opcional)</h6>
              <div class="mb-3"><label class="small text-muted fw-bold mb-1">Banco PM</label><input type="text" class="form-control" name="pm_banco" id="edit_cta_pmb"></div>
              <div class="mb-3"><label class="small text-muted fw-bold mb-1">Cédula PM</label><input type="text" class="form-control" name="pm_cedula" id="edit_cta_pmc"></div>
              <div class="mb-3"><label class="small text-muted fw-bold mb-1">Teléfono PM</label><input type="text" class="form-control" name="pm_tlf" id="edit_cta_pmt"></div>
          </div>
          <div class="modal-footer border-0 bg-light justify-content-between">
              <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary fw-bold" id="btnGuardarEditCta"><i class="bi bi-save"></i> Guardar</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDITAR CATEGORÍA -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-tags-fill me-2"></i> Editar Categoría</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarCategoria">
          <div class="modal-body p-4 bg-light">
              <input type="hidden" name="accion" value="editar_categoria">
              <input type="hidden" name="id" id="edit_cat_id">
              <div class="mb-3"><label class="small text-muted fw-bold mb-1">Nombre de la Categoría *</label><input type="text" class="form-control border-primary" name="nombre" id="edit_cat_nombre" required></div>
          </div>
          <div class="modal-footer border-0 bg-light justify-content-between">
              <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary fw-bold" id="btnGuardarEditCat"><i class="bi bi-save"></i> Guardar</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL NUEVA CUENTA POR PAGAR (MULTISERVICIOS) -->
<div class="modal fade" id="modalNuevaCuentaPagar" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header text-white" style="background-color: #0B4C8C;">
          <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i> Registrar Cuenta por Pagar / Gasto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formNuevaCuentaPagar">
          <div class="modal-body bg-light p-4">
              <div class="row g-4">
                  <div class="col-lg-4">
                      <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                              <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">1. Datos Principales</h6>
                              <input type="hidden" name="accion" value="crear_cuenta_pagar">
                              <input type="hidden" name="detalles_json" id="ncp_detalles_json">
                              <input type="hidden" name="monto_total" id="ncp_monto_total_hidden" value="0">
                              
                              <div class="mb-3">
                                  <label class="small text-muted fw-bold mb-1">Proveedor / Beneficiario *</label>
                                  <select class="form-select border-primary fw-bold" name="id_proveedor" id="ncp_proveedor" required></select>
                              </div>
                              <div class="mb-3">
                                  <label class="small text-muted fw-bold mb-1">Fecha de la Deuda *</label>
                                  <input type="date" class="form-control" name="fecha" id="ncp_fecha" required>
                              </div>
                              <div class="mb-3">
                                  <label class="small text-muted fw-bold mb-1">Nro. Factura / Referencia Doc.</label>
                                  <input type="text" class="form-control" name="factura" placeholder="Ej: FAC-00123">
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="col-lg-8">
                      <div class="card shadow-sm border-0 h-100">
                          <div class="card-body">
                              <h6 class="text-success fw-bold border-bottom pb-2 mb-3">2. Servicios a Facturar</h6>
                              
                              <div class="row g-2 align-items-end mb-3 bg-light p-2 rounded border">
                                  <div class="col-md-7">
                                      <label class="small text-muted fw-bold mb-1">Descripción del Servicio</label>
                                      <input type="text" id="temp_desc_gasto" class="form-control" placeholder="Ej: Mantenimiento Preventivo">
                                  </div>
                                  <div class="col-md-3">
                                      <label class="small text-muted fw-bold mb-1">Costo ($)</label>
                                      <input type="number" id="temp_costo_gasto" class="form-control text-end" step="0.01" placeholder="0.00">
                                  </div>
                                  <div class="col-md-2 text-end">
                                      <button type="button" class="btn btn-outline-success w-100 fw-bold" onclick="agregarItemGasto()"><i class="bi bi-plus-lg"></i> Añadir</button>
                                  </div>
                              </div>

                              <div class="table-responsive border rounded" style="max-height: 200px; overflow-y: auto;">
                                  <table class="table table-sm table-hover mb-0">
                                      <thead class="table-light sticky-top">
                                          <tr>
                                              <th>Servicio / Descripción</th>
                                              <th class="text-end">Costo ($)</th>
                                              <th class="text-center" style="width: 50px;"></th>
                                          </tr>
                                      </thead>
                                      <tbody id="tbodyItemsGasto">
                                          <tr><td colspan="3" class="text-center text-muted py-3">Agrega servicios a la cuenta.</td></tr>
                                      </tbody>
                                  </table>
                              </div>
                              <div class="text-end mt-3">
                                  <h4 class="text-success fw-bold m-0">Total Deuda: <span id="lbl_ncp_total">0.00</span> $</h4>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <div class="modal-footer border-0 bg-white justify-content-between">
              <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary fw-bold px-4" id="btnGuardarNCP"><i class="bi bi-save"></i> Registrar Deuda</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal UNIVERSAL para Pagar Deuda (OS, Nómina, Insumos, Gastos) -->
<div class="modal fade" id="modalPagarDeuda" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPagarDeuda" class="modal-content border-0 shadow" enctype="multipart/form-data">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-wallet2"></i> Pasarela de Pagos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <input type="hidden" name="accion" value="pagar_deuda_dashboard">
                <input type="hidden" name="id_deuda" id="pd_id">
                <input type="hidden" name="tipo_deuda" id="pd_tipo_deuda">
                <input type="hidden" name="deuda_actual" id="pd_deuda_actual">

                <!-- Indicador dinámico de estado de pago -->
                <div class="alert alert-secondary text-center fw-bold mb-3 py-2" id="box_tipo_pago" style="border: 2px dashed #6c757d; background-color:#f8f9fa;">
                    <span id="lbl_tipo_pago" class="fs-4 text-secondary">INGRESE UN MONTO</span>
                </div>

                <div class="alert alert-warning text-center fw-bold mb-3 border-warning" style="background-color: #fff3cd;">
                    Deuda Pendiente Restante: <span id="lbl_pd_deuda" class="fs-5 text-danger">0.00 $</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted" id="lbl_monto_pagar">Monto a Pagar (USD) *</label>
                        <input type="number" step="0.01" name="monto_pagar" id="pd_monto" class="form-control fw-bold text-success fs-5" placeholder="Ej: 20.00" onkeyup="evaluarTipoPagoDeuda(); window.calcularEquivalentePagarDeuda();" onchange="evaluarTipoPagoDeuda(); window.calcularEquivalentePagarDeuda();" required>
                        <small class="text-muted">Abona una parte o paga el total.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Tasa BCV</label>
                        <input type="number" step="0.0001" name="tasa_bcv" id="pd_tasa" class="form-control fw-bold fs-5 bg-white border-primary" placeholder="Ej: 36.50" onkeyup="window.calcularEquivalentePagarDeuda();" onchange="window.calcularEquivalentePagarDeuda();">
                        <small class="text-muted text-primary"><i class="bi bi-info-circle"></i> Tasa automática (editable)</small>
                    </div>
                </div>

                <!-- Bloque Equivalente en Bolívares (Cálculo Inmediato) -->
                <div class="col-12 text-end mb-3">
                    <small class="text-muted fw-bold p-2 bg-white rounded border shadow-sm d-inline-block">
                        Equivalente a pagar: <span id="lblEquivalenteBs" class="text-primary fs-5">0.00 Bs</span>
                    </small>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-muted">Método de Pago *</label>
                    <input type="text" name="metodo_pago" id="pd_metodo" class="form-control border-success" placeholder="Ej: Zelle, Pago Móvil, Efectivo" required>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Referencia</label>
                    <input type="text" name="referencia" id="pd_ref" class="form-control" placeholder="Opcional">
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Comprobante (Imagen/PDF) *</label>
                    <input type="file" name="comprobante[]" id="pd_comp" class="form-control" accept="image/*,.pdf" multiple required>
                    <small class="text-muted">Puedes adjuntar varias capturas a la vez.</small>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold px-4" id="btnGuardarPagoDeuda"><i class="bi bi-check-circle"></i> Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORIAL PROVEEDOR -->
<div class="modal fade" id="modalHistorialProveedor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i> Historial de Proveedor: <span id="hp_nombre_proveedor" class="text-info"></span></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-light">
          <div class="row mb-3">
              <div class="col-md-6 mb-2">
                  <div class="card bg-success text-white shadow-sm border-0 h-100">
                      <div class="card-body py-2">
                          <h6 class="card-title mb-1 small text-white-50">Total Pagado (Equiv. USD)</h6>
                          <h4 class="fw-bold mb-0" id="hp_total_pagado">0.00 $</h4>
                      </div>
                  </div>
              </div>
              <div class="col-md-6 mb-2">
                  <div class="card bg-danger text-white shadow-sm border-0 h-100">
                      <div class="card-body py-2">
                          <h6 class="card-title mb-1 small text-white-50">Total Adeudado (Equiv. USD)</h6>
                          <h4 class="fw-bold mb-0" id="hp_total_deuda">0.00 $</h4>
                      </div>
                  </div>
              </div>
          </div>
          <div class="table-responsive bg-white border rounded">
              <table class="table table-hover table-sm mb-0">
                  <thead class="table-light sticky-top">
                      <tr>
                          <th>Fecha</th>
                          <th>Documento</th>
                          <th>Tipo</th>
                          <th class="text-end">Monto Total</th>
                          <th class="text-end">Pagado</th>
                          <th class="text-center">Estado</th>
                      </tr>
                  </thead>
                  <tbody id="tbodyHistorialProveedor">
                  </tbody>
              </table>
          </div>
      </div>
      <div class="modal-footer border-0 bg-light">
          <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>