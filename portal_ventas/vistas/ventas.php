<div id="vistaVentas" class="vista-spa" style="display: none;">
    <div class="alert mb-4 shadow-sm d-flex justify-content-between align-items-center" style="background-color: #e6f0fa; color: #0B4C8C; border: 1px solid #b8d4f2; border-left: 5px solid #FF7518; border-radius: 12px;">
        <div><i class="bi bi-person-check-fill me-2 fs-4 align-middle"></i><span class="fs-5">Cotización Estandar: <strong id="lblClienteSeleccionado" class="fs-4 text-uppercase">Nadie</strong></span><input type="hidden" id="idClienteSeleccionado" value=""></div>
        <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold bg-white" onclick="$('#btnMenuClientes').click();"><i class="bi bi-arrow-left-circle"></i> Cambiar Cliente</button>
    </div>

    <div class="nav-pills-category mt-4 mb-2" id="navPillsCategorias"></div>
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2"><h4 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-shop"></i> Catálogo de Prendas y Arte</h4><div class="search-box"><i class="bi bi-search"></i><input type="text" id="buscadorCatalogo" class="form-control form-control-sm" placeholder="Buscar producto..."></div></div>
    <div id="contenedorCatalogo"><div class="text-center text-muted py-5"><div class="spinner-border text-primary"></div><p>Cargando catálogo...</p></div></div>

    <div id="seccionCheckout" class="p-4 mt-5 transition-all">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h4 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-receipt text-warning"></i> Detalles de Facturación</h4>
            <span class="badge bg-danger" id="badgeEstadoEdicion" style="display:none; font-size: 1rem;">Editando Cotización</span>
        </div>
        <form id="formGuardarCotizacion">
            <input type="hidden" id="id_edit_cotizacion" value="0">
            <div class="row">
                <div class="col-lg-7">
                    
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-6">
                            <label class="custom-check-small w-100 p-2"><input type="checkbox" id="cot_contribuyente"><label for="cot_contribuyente" class="fs-6 m-0">¿Contribuyente Especial?</label></label>
                        </div>
                        <?php if(tieneVector(32)): ?>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <label class="custom-check-small w-100 p-2"><input type="checkbox" id="cot_aplica_iva" checked><label for="cot_aplica_iva" class="fs-6 m-0">Aplicar I.V.A (16%)</label></label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-4">
                        <?php if(tieneVector(31)): ?>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-percent me-1"></i> Descuento (%):</h6>
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <span class="input-group-text fw-bold bg-white text-dark">Porcentaje</span>
                                <input type="number" step="0.01" min="0" max="100" id="cot_desc_manual" class="form-control fw-bold border-secondary" placeholder="Ej: 5" value="0">
                                <span class="input-group-text fw-bold bg-white text-dark">%</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(tieneVector(33)): ?>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-truck me-1"></i> Serv. Logístico de Envío ($):</h6>
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <span class="input-group-text fw-bold bg-white text-dark">$</span>
                                <input type="number" step="0.01" min="0" id="cot_desc_logistica" class="form-control fw-bold border-secondary" placeholder="Monto" value="0">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 me-1"></i> Método de Pago Acordado:</h6>
                    <div class="custom-checkbox-grid mb-4">
                        <?php 
                            foreach($metodos_pago_bd as $p) {
                                $id_mp = $p['id'];
                                $nombre_mp = htmlspecialchars($p['forma_pago']);
                                $class_extra = (strpos(strtolower($nombre_mp), 'divisa') !== false) ? 'check-divisas' : '';
                                echo "<label class='custom-check-small'><input class='check-pago {$class_extra}' type='radio' name='id_forma_pago' value='{$id_mp}' id='pago_{$id_mp}'><label for='pago_{$id_mp}'>{$nombre_mp}</label></label>";
                            }
                        ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 mt-4"><i class="bi bi-truck me-1"></i> Método de Entrega (Obligatorio):</h6>
                    <div class="mb-4">
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="metodo_entrega" value="Retiro Sede: UNIFUERTE, C.A, Nro. 321, Zona Industrial de Desarollo, Edificio Unifuerte, Puerto Ordaz 8050, Bolívar">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Retiro Sede: UNIFUERTE, C.A, Nro. 321, Zona Industrial de Desarollo, Edificio Unifuerte, Puerto Ordaz 8050, Bolívar</span>
                        </label>
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="metodo_entrega" value="Retiro Sede: Edificio Kyra de Alta Vista, 4to piso, Puerto Ordaz, Ciudad Guayana, Puerto Ordaz 8050, Bolívar">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Retiro Sede: Edificio Kyra de Alta Vista, 4to piso, Puerto Ordaz, Ciudad Guayana, Puerto Ordaz 8050, Bolívar</span>
                        </label>
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="metodo_entrega" value="Envío por Delivery">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Envío por Delivery</span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-card-text me-1"></i> Notas Adicionales (Lo que SÍ incluye o detalles)</h6>
                        <textarea id="cot_notas" class="form-control bg-light" rows="2" placeholder="Descripción corta (máximo 10 palabras)"></textarea>
                        <small class="text-muted">Descripción breve del pedido; máximo 10 palabras.</small>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="resumen-checkout-box shadow-sm">
                        <h6 class="fw-bold mb-3 text-center" style="color: #FF7518;"><i class="bi bi-cart-check"></i> Resumen del Pedido</h6>
                        <div id="resumenCheckoutBox" style="max-height: 200px; overflow-y: auto; margin-bottom: 15px;"><div class="text-center text-muted small py-4">No hay prendas agregadas.</div></div>
                        <div class="border-top pt-2">
                            <div class="d-flex justify-content-between small text-muted mb-1"><span>Sub-Total:</span> <span id="lblSubtotalCheck">0,00 $</span></div>
                            <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="cajaDescLogisticaCheck" style="display: none !important;"><span>Serv. Logístico de Envío:</span> <span id="lblDescLogisticaCheck">0,00 $</span></div>
                            <div class="d-flex justify-content-between small text-danger fw-bold mb-1" id="cajaDescuentoCheck" style="display: none !important;"><span>Descuento (<span id="lblPorcentajeDescCheck">0</span>):</span> <span id="lblMontoDescCheck">-0,00 $</span></div>
                            <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="cajaBaseImponibleCheck" style="display: none !important;"><span>Base Imponible:</span> <span id="lblBaseImponibleCheck">0,00 $</span></div>
                            <div class="d-flex justify-content-between small text-muted mb-1" id="cajaIvaCheck"><span>I.V.A (16%):</span> <span id="lblIvaCheck">0,00 $</span></div>
                            <div class="d-flex justify-content-between small text-warning fw-bold mb-2" id="cajaIgtfCheck" style="display: none !important;"><span>IGTF (3% - Divisas Efectivo):</span> <span id="lblIgtfCheck">0,00 $</span></div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 mb-3"><span class="fw-bold fs-5">Total a Pagar:</span> <span class="fs-4 fw-bold" style="color: #0B4C8C;" id="lblTotalGeneralCheck">0,00 $</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if(tieneVector(5)): ?>
            <div class="text-end border-top pt-3"><button type="submit" class="btn fw-bold text-white shadow px-5 py-3 fs-5 btn-bloqueable" style="background-color: #FF7518; border-radius: 50px;" id="btnGenerarCotizacion">Generar Cotización Oficial <i class="bi bi-file-pdf ms-2"></i></button></div>
            <?php else: ?>
            <div class="alert alert-danger text-center fw-bold">No tienes el Vector [5] para Generar Cotizaciones.</div>
            <?php endif; ?>
        </form>
    </div>
    <button class="floating-cart btn-bloqueable" data-bs-toggle="modal" data-bs-target="#modalCarritoBubble"><i class="bi bi-cart3"></i> <span class="badge" id="lblBurbujaItems">0</span></button>
</div>

<div class="modal fade" id="modalCarritoBubble" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0" style="background-color: #FF7518; color: white;"><h5 class="modal-title fw-bold"><i class="bi bi-cart-fill"></i> Tu Carrito Actual</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-light p-0"><div id="listaCarritoBubble" class="p-3"></div></div>
            <div class="modal-footer d-block bg-white border-top-0">
                <div class="d-flex justify-content-between small text-muted mb-1"><span>Sub-Total:</span> <span id="lblSubtotal">0,00 $</span></div>
                <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="cajaDescLogistica" style="display: none !important;"><span>Serv. Logístico de Envío:</span> <span id="lblDescLogistica">0,00 $</span></div>
                <div class="d-flex justify-content-between small text-danger fw-bold mb-1" id="cajaDescuento" style="display: none !important;"><span>Descuento (<span id="lblPorcentajeDesc">0</span>):</span> <span id="lblMontoDesc">-0,00 $</span></div>
                <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="cajaBaseImponible" style="display: none !important;"><span>Base Imponible:</span> <span id="lblBaseImponible">0,00 $</span></div>
                <div class="d-flex justify-content-between small text-muted mb-1" id="cajaIva"><span>I.V.A (16%):</span> <span id="lblIva">0,00 $</span></div>
                <div class="d-flex justify-content-between small text-warning fw-bold mb-2" id="cajaIgtf" style="display: none !important;"><span>IGTF:</span> <span id="lblIgtf">0,00 $</span></div>
                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 mb-3"><span class="fw-bold fs-5">Total a Pagar:</span> <span class="fs-3 fw-bold" style="color: #0B4C8C;" id="lblTotalGeneral">0,00 $</span></div>
                <button type="button" class="btn w-100 fw-bold text-white shadow-sm btn-bloqueable" style="background-color: #0B4C8C; padding: 12px; border-radius: 30px;" onclick="irAPagar()">Proceder al Pago / Cierre</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgregarPrenda" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;"><h6 class="modal-title fw-bold">Configurar Elemento</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center p-4">
                <input type="hidden" id="modal_edit_index" value="-1"><input type="hidden" id="modal_id_prod"><input type="hidden" id="modal_precio_prod"><input type="hidden" id="modal_categoria_prod">
                <h6 id="modal_nombre_prod" class="fw-bold mb-1">Nombre Producto</h6><p class="text-muted small mb-3" id="modal_precio_display">0,00 $</p>
                
                <div class="mb-3 text-start" id="wrapper_modal_genero">
                    <label class="small fw-bold text-muted">Género</label>
                    <select id="modal_genero" class="form-select bg-light" onchange="if(typeof window.actualizarTallaModalPrenda === 'function') window.actualizarTallaModalPrenda(this)">
                        <option value="Adulto (Dama o Caballero)">Adulto (Dama o Caballero)</option>
                        <option value="Infantil (Unisex)">Infantil (Unisex)</option>
                        <option value="Juvenil (Unisex)">Juvenil (Unisex)</option>
                    </select>
                </div>
                
                <div class="mb-3 text-start" id="wrapper_modal_talla">
                    <label class="small fw-bold text-muted">Rango de Tallas Sugeridas</label>
                    <input type="text" id="modal_talla" class="form-control bg-light fw-bold" readonly>
                </div>

                <div class="mb-3 text-start" id="wrapper_modal_vincular" style="display: none;">
                    <label class="small fw-bold text-muted" style="color: #FF7518 !important;"><i class="bi bi-link-45deg"></i> ¿A qué prenda se le aplicará?</label>
                    <select id="modal_vincular_prenda" class="form-select bg-light" style="border-color: #FF7518;"><option value="" data-cant="9999">No vincular (Arte suelto)</option></select>
                </div>
                <div class="mb-3 text-start" id="wrapper_modal_detalle" style="display: none;">
                    <label class="small fw-bold text-muted">Posición de Arte</label>
                    <input type="text" id="modal_detalle_diseno" class="form-control bg-light" placeholder="Ej: Manga Izquierda, Espalda...">
                </div>
                <div class="mb-3 text-start">
                    <label class="small fw-bold text-muted">Cantidad</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary px-3 fw-bold btn-bloqueable" type="button" onclick="ajustarCant(-1)">-</button>
                        <input type="number" id="modal_cantidad" class="form-control text-center fw-bold btn-bloqueable" value="1" min="1">
                        <button class="btn btn-outline-secondary px-3 fw-bold btn-bloqueable" type="button" onclick="ajustarCant(1)">+</button>
                    </div>
                </div>
                <button type="button" class="btn w-100 fw-bold text-white btn-bloqueable" style="background-color: #0B4C8C; border-radius: 20px; padding: 10px;" onclick="confirmarAgregarAlCarrito()">Añadir a Cotización <i class="bi bi-cart-plus ms-1"></i></button>
            </div>
        </div>
    </div>
</div>