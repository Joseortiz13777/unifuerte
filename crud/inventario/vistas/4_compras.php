<div id="vistaCompras" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-cart-check-fill me-2" style="color: #FF7518;"></i> Compras e Inversiones</h3>
    </div>
    <ul class="nav nav-pills mb-3 border-bottom pb-2" id="pills-tab-compras" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="pills-insumos-tab" data-bs-toggle="pill" data-bs-target="#pills-insumos" type="button" role="tab"><i class="bi bi-box-seam"></i> Compras de Inventario</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold ms-2" id="pills-servicios-tab" data-bs-toggle="pill" data-bs-target="#pills-servicios" type="button" role="tab" style="background-color: #f8f9fa; color: #0B4C8C; border: 1px solid #dee2e6;" onclick="cargarOrdenesServicio(); this.style.backgroundColor='#0B4C8C'; this.style.color='#fff';" onfocusout="this.style.backgroundColor='#f8f9fa'; this.style.color='#0B4C8C';"><i class="bi bi-tools"></i> Órdenes de Servicio</button>
        </li>
    </ul>
    <div class="tab-content" id="pills-tabContent-compras">
        <div class="tab-pane fade show active" id="pills-insumos" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="abrirModalCompra()"><i class="bi bi-plus-lg"></i> Registrar Compra de Inventario</button>
            </div>
            <div class="bg-white p-3 rounded shadow-sm border">
                <div class="row mb-3 g-2">
                    <div class="col-md-8"><input type="text" id="buscadorCompras" class="form-control" placeholder="Buscar por código, proveedor, banco, referencia..."></div>
                    <div class="col-md-4">
                        <select id="filtroEstadoCompras" class="form-select border-primary fw-bold text-primary">
                            <option value="">Todos los Estados</option>
                            <option value="Pendiente de Recepción">Pendiente de Recepción</option>
                            <option value="Recibido">Recibido (Añadido a Almacén)</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
                        <thead class="table-light"><tr><th>Nro Compra</th><th>Fecha</th><th>Proveedor</th><th>Logística</th><th>Pago</th><th>Monto Total</th><th class="text-center">Acciones</th></tr></thead>
                        <tbody id="tbodyCompras"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-servicios" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-success fw-bold shadow-sm" onclick="abrirModalOrdenServicio()"><i class="bi bi-plus-lg"></i> Crear Orden de Servicio</button>
            </div>
            <div class="bg-white p-3 rounded shadow-sm border">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
                        <thead class="table-light"><tr><th>Orden</th><th>Proveedor</th><th>Servicios Listados</th><th class="text-end">Costo Total</th><th class="text-center">Días para Pagar</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
                        <tbody id="tbodyOrdenesServicio"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO DE COMPRAS INVENTARIO -->
<div class="modal fade" id="modalCompra" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formCompra" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-cart-plus"></i> Registrar/Editar Compra a Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="compra_id_edit" name="id_compra" value="0">
                <div class="row">
                    <div class="col-md-4">
                        <div class="bg-white p-3 rounded border shadow-sm h-100">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Detalles Principales</h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="small fw-bold text-muted m-0">Proveedor *</label>
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;" onclick="window.crearProveedorDesdeCompra()">+ Nuevo</button>
                                </div>
                                <select name="id_proveedor" id="compra_proveedor" class="form-select border-primary" style="width: 100%;" required></select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6"><label class="small fw-bold text-muted">Fecha Compra *</label><input type="date" name="fecha_compra" id="compra_fecha" class="form-control border-primary" required></div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Estado Almacén *</label>
                                    <select name="estado" id="compra_estado" class="form-select border-primary">
                                        <option value="Pendiente de Recepción">En Camino</option>
                                        <option value="Recibido">Recibido (Suma Stock)</option>
                                    </select>
                                </div>
                            </div>
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">2. Datos Facturación</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="small fw-bold text-muted">Nro. Factura</label><input type="text" name="nro_factura" id="compra_factura" class="form-control" placeholder="Ej: K-009782"></div>
                                <div class="col-6"><label class="small fw-bold text-muted">Nro. Control</label><input type="text" name="nro_control" id="compra_control" class="form-control" placeholder="Opcional"></div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Condición *</label>
                                    <select name="es_credito" id="compra_es_credito" class="form-select border-primary">
                                        <option value="0">Pagado Contado</option><option value="1" selected>A Crédito</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Moneda</label>
                                    <select name="moneda" id="compra_moneda" class="form-select border-secondary">
                                        <option value="USD">Dólares (USD)</option><option value="BS" selected>Bolívares (BS)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Tasa BCV del Día</label>
                                <input type="text" inputmode="decimal" name="tasa_bcv" id="compra_tasa_bcv" class="form-control border-info text-end fw-bold" placeholder="Ej: 36,50">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="small fw-bold text-muted">Banco / Forma Pago</label><input type="text" name="banco" id="compra_banco" class="form-control" placeholder="Ej: Debito"></div>
                                <div class="col-6"><label class="small fw-bold text-muted">Ref. Pago Inicial</label><input type="text" name="referencia" id="compra_referencia" class="form-control" placeholder="Ej: 123456"></div>
                            </div>
                            <div class="mb-2 mt-3">
                                <label class="small fw-bold text-muted m-0 mb-1">Descuento Global (Resta a Insumos)</label>
                                <input type="text" inputmode="decimal" name="descuento" id="compra_descuento" class="form-control text-end fw-bold border-warning" placeholder="0,00" value="0">
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="small fw-bold text-muted m-0">I.V.A Adicional</label>
                                    <button type="button" class="btn btn-sm btn-info text-white py-0 px-2" style="font-size: 0.7rem;" onclick="window.calcularIvaAutomatico()">Auto 16%</button>
                                </div>
                                <input type="text" inputmode="decimal" name="iva" id="compra_iva" class="form-control text-end fw-bold border-secondary" placeholder="0,00" value="0">
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold text-muted">Soporte Factura (PDF/Imagen)</label>
                                <input type="file" name="archivo_factura" id="compra_archivo" class="form-control" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="bg-white p-3 rounded border shadow-sm h-100 d-flex flex-column">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">3. Añadir Insumos al Carrito</h6>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="small fw-bold text-muted m-0">Insumo a Comprar</label>
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;" onclick="window.crearMpDesdeCompra()">+ Nuevo Insumo</button>
                                </div>
                                <select id="compra_mp" class="form-select bg-light" style="width: 100%;">
                                    <option value="">Buscar Insumo...</option>
                                    <?php foreach($materia_prima as $m): ?>
                                        <option value="<?= $m['ID'] ?>" data-und="<?= $m['unidad_medida'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= $m['unidad_medida'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-2"><label class="small fw-bold text-muted">Cant.</label><input type="text" inputmode="decimal" id="compra_cant" class="form-control text-center fw-bold border-primary" placeholder="0"></div>
                                <div class="col-3"><label class="small fw-bold text-muted">Costo Base</label><input type="text" inputmode="decimal" id="compra_costo_unit" class="form-control text-end fw-bold border-primary" placeholder="0,00"></div>
                                <div class="col-2"><label class="small fw-bold text-muted">% Desc</label><input type="text" inputmode="decimal" id="compra_desc_item" class="form-control text-center text-danger" placeholder="0" value="0"></div>
                                <div class="col-3"><label class="small fw-bold text-muted">Costo Neto</label><input type="text" id="compra_costo_neto" class="form-control text-end bg-light fw-bold text-primary" readonly placeholder="0,00"></div>
                                <div class="col-2 d-flex align-items-end"><button type="button" class="btn btn-success w-100 fw-bold" onclick="window.agregarItemCompra()"><i class="bi bi-plus-lg"></i></button></div>
                            </div>

                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-2">4. Lista de Compra</h6>
                            <div class="table-responsive flex-grow-1 border rounded p-1 mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light"><tr><th>Insumo</th><th class="text-center">Cant.</th><th class="text-end">Base</th><th class="text-center">Desc</th><th class="text-end">Neto</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                                    <tbody id="tbodyListaCompra"><tr><td colspan="7" class="text-center text-muted">Añade insumos a la compra.</td></tr></tbody>
                                </table>
                            </div>
                            
                            <!-- RESULTADOS MATEMÁTICOS TIPO FACTURA -->
                            <div class="bg-light p-3 rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted fw-bold">Subtotal Insumos:</span>
                                    <span id="lbl_subtotal_insumos" class="fw-bold text-dark">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted fw-bold">Base Imponible (Subtotal - Desc):</span>
                                    <span id="lbl_base_imponible" class="fw-bold text-dark">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <h4 class="fw-bold m-0" style="color: #FF7518;">Total a Pagar (Base + IVA):</h4>
                                    <h4 class="fw-bold m-0" style="color: #FF7518;" id="lbl_total_compra">0.00</h4>
                                </div>
                                <div class="text-end mt-1">
                                    <span class="fw-bold text-success" id="lbl_equivalente_compra">Equivalente: 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white fw-bold" style="background-color: #0B4C8C;" id="btnGuardarCompraFinalBtn"><i class="bi bi-save"></i> Registrar Compra Completa</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VER DETALLE COMPRA INVENTARIO -->
<div class="modal fade" id="modalVerDetalleCompra" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-receipt"></i> Detalle de la Compra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 id="ver_compra_prov" class="fw-bold text-dark m-0">Proveedor</h5>
                    <span id="ver_compra_fechas" class="badge bg-secondary">Fecha</span>
                </div>
                <div class="row mb-3" id="ver_compra_datos_factura" style="display:none;">
                    <div class="col-md-3"><small class="text-muted fw-bold">Factura:</small><br><span id="ver_factura" class="fw-bold text-dark"></span></div>
                    <div class="col-md-3"><small class="text-muted fw-bold">Control:</small><br><span id="ver_control" class="fw-bold text-dark"></span></div>
                    <div class="col-md-3"><small class="text-muted fw-bold">Banco:</small><br><span id="ver_banco" class="fw-bold text-dark"></span></div>
                    <div class="col-md-3"><small class="text-muted fw-bold">Ref:</small><br><span id="ver_ref" class="fw-bold text-dark"></span></div>
                </div>
                <div class="table-responsive bg-white rounded shadow-sm border p-2 mt-3">
                    <table class="table table-sm align-middle table-hover m-0">
                        <thead class="table-light"><tr><th>Insumo</th><th class="text-center">Cant.</th><th class="text-end">Base U.</th><th class="text-center">% Desc</th><th class="text-end">Neto</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody id="tbodyVerDetalleCompra"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end align-items-center mt-3 gap-4">
                    <h6 class="fw-bold text-muted m-0">Desc Global: <span id="ver_compra_descuento" class="text-warning">0.00 $</span></h6>
                    <h6 class="fw-bold text-muted m-0">IVA Pagado: <span id="ver_compra_iva">0.00 $</span></h6>
                    <h4 class="fw-bold text-success m-0">Total: <span id="ver_compra_total">0.00 $</span></h4>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div id="ver_btn_descargar_factura"></div>
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GESTIÓN DE PAGOS DE COMPRA (INSUMOS) -->
<div class="modal fade" id="modalPagosCompra" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #198754;">
                <h5 class="modal-title fw-bold"><i class="bi bi-wallet2"></i> Cuentas por Pagar al Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="row">
                    <div class="col-md-4">
                        <div class="bg-white p-3 rounded border shadow-sm h-100">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3">Registrar Nuevo Abono</h6>
                            <form id="formPagoCompra" enctype="multipart/form-data">
                                <input type="hidden" name="accion" value="guardar_pago_compra">
                                <input type="hidden" name="id_compra" id="pago_compra_id">
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Fecha de Pago *</label>
                                    <input type="date" name="fecha_pago" id="pago_compra_fecha" class="form-control border-success" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Monto Abonado (Moneda Orig) *</label>
                                    <input type="text" inputmode="decimal" name="monto_usd" id="pago_compra_usd" class="form-control fw-bold border-success text-end" required placeholder="0,00">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="small fw-bold text-muted">Tasa BCV</label>
                                        <input type="text" inputmode="decimal" name="tasa_bcv" id="pago_compra_tasa" class="form-control" placeholder="Ej: 36,50">
                                    </div>
                                    <div class="col-6">
                                        <label class="small fw-bold text-muted">Equiv (BS)</label>
                                        <input type="text" inputmode="decimal" name="monto_bs" id="pago_compra_bs" class="form-control bg-light" readonly>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Método de Pago / Banco *</label>
                                    <input type="text" name="metodo_pago" id="pago_compra_metodo" class="form-control" placeholder="Ej: Zelle, Banesco, Efectivo" required>
                                </div>
                                <div class="mb-2">
                                    <label class="small fw-bold text-muted">Referencia</label>
                                    <input type="text" name="referencia" id="pago_compra_ref" class="form-control" placeholder="Ej: 123456">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted">Comprobante (Imagen/PDF)</label>
                                    <input type="file" name="comprobante" id="pago_compra_comprobante" class="form-control" accept="image/*,.pdf">
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-bold" id="btnGuardarAbono"><i class="bi bi-save"></i> Guardar Abono</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="bg-white p-3 rounded border shadow-sm h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="fw-bold text-dark m-0"><i class="bi bi-clock-history"></i> Historial de Pagos y Abonos</h6>
                                <div class="text-end">
                                    <span class="d-block small text-muted fw-bold">Deuda Total: <span id="lbl_deuda_total" class="text-dark">0.00</span></span>
                                    <span class="d-block small text-muted fw-bold">Resta por Pagar: <span id="lbl_deuda_restante" class="text-danger fs-6">0.00</span></span>
                                </div>
                            </div>
                            <div class="table-responsive flex-grow-1">
                                <table class="table table-sm align-middle table-hover">
                                    <thead class="table-light"><tr><th>Fecha</th><th>Método</th><th>Ref</th><th class="text-end">Abono</th><th class="text-center">Soporte</th><th></th></tr></thead>
                                    <tbody id="tbodyPagosCompra"><tr><td colspan="6" class="text-center text-muted">No hay pagos registrados.</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ORDEN DE SERVICIO TIPO FACTURA -->
<div class="modal fade" id="modalOrdenServicio" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formOrdenServicio" class="modal-content border-0 shadow">
            <div class="modal-header text-white bg-success">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools"></i> Registrar / Editar Orden de Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="orden_servicio_id" name="id_orden_servicio" value="0">
                <div class="row">
                    <div class="col-md-5">
                        <div class="bg-white p-3 rounded border shadow-sm h-100">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3">1. Datos Principales</h6>
                            <div class="mb-3">
                                <label class="fw-bold text-muted small">Proveedor *</label>
                                <select name="id_proveedor" id="orden_servicio_proveedor" class="form-select border-success" style="width: 100%;" required></select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-muted small">Fecha de la Orden *</label>
                                <input type="date" name="fecha_orden" id="orden_servicio_fecha" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold text-danger small">Fecha Límite de Pago *</label>
                                <input type="date" name="fecha_pago_limite" id="orden_servicio_limite" class="form-control border-danger" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="bg-white p-3 rounded border shadow-sm h-100 d-flex flex-column">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-3">2. Servicios a Facturar</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-7">
                                    <label class="small fw-bold text-muted">Descripción del Servicio</label>
                                    <input type="text" id="os_item_desc" class="form-control form-control-sm border-success" placeholder="Ej: Mantenimiento Preventivo">
                                </div>
                                <div class="col-3">
                                    <label class="small fw-bold text-muted">Costo ($)</label>
                                    <input type="text" inputmode="decimal" id="os_item_costo" class="form-control form-control-sm fw-bold border-success text-end" placeholder="0,00">
                                </div>
                                <div class="col-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100 fw-bold" onclick="window.agregarItemOS()"><i class="bi bi-plus-lg"></i> Añadir</button>
                                </div>
                            </div>
                            <div class="table-responsive flex-grow-1 border rounded shadow-sm">
                                <table class="table table-sm align-middle table-hover mb-0">
                                    <thead class="table-light"><tr><th>Servicio</th><th class="text-end">Costo</th><th></th></tr></thead>
                                    <tbody id="tbodyListaOS"><tr><td colspan="3" class="text-center text-muted">Añade servicios a la orden.</td></tr></tbody>
                                </table>
                            </div>
                            <div class="border-top pt-3 mt-3 d-flex flex-column align-items-end mt-auto">
                                <h4 class="fw-bold m-0" style="color: #198754;">Total Orden: <span id="lbl_total_os">0.00 $</span></h4>
                                <input type="hidden" name="costo" id="orden_servicio_costo" value="0">
                                <input type="hidden" name="servicio" id="orden_servicio_desc" value="[]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success fw-bold" id="btnGuardarOrdenServ"><i class="bi bi-save"></i> Registrar Orden</button>
            </div>
        </form>
    </div>
</div>