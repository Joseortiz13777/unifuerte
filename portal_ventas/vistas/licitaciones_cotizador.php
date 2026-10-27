<div id="vistaCotizadorLicitaciones" class="vista-spa" style="display: none;">
    <div class="alert mb-4 shadow-sm d-flex justify-content-between align-items-center" style="background-color: #fef08a; color: #854d0e; border: 1px solid #fde047; border-left: 5px solid #ca8a04; border-radius: 12px;">
        <div><i class="bi bi-briefcase-fill me-2 fs-4 align-middle"></i><span class="fs-5">Cotización Especial (Licitación): <strong id="lblClienteLicitacion" class="fs-4 text-uppercase">Nadie</strong></span></div>
        <button class="btn btn-sm btn-outline-dark rounded-pill fw-bold bg-white" onclick="$('#btnMenuClientes').click();"><i class="bi bi-arrow-left-circle"></i> Cambiar Cliente</button>
    </div>

    <div id="seccionCheckoutLicitacion" class="p-4 mt-3 transition-all" style="border: 2px solid #ca8a04 !important; background-color: #fcfdfe !important; border-radius: 16px !important; box-shadow: 0 8px 25px rgba(202, 138, 4, 0.1) !important;">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h4 class="fw-bold m-0" style="color: #854d0e;"><i class="bi bi-receipt text-warning"></i> Detalles de Facturación Especial</h4>
        </div>
        
        <div class="row">
            <div class="col-lg-7">
                <form id="formGuardarLicitacion">
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Nro. Cotización Especial *</label>
                            <input type="text" id="lic_nro_cotizacion" class="form-control border-warning fw-bold text-dark shadow-sm" placeholder="Ej: #UNI-2026-202" pattern="#UNI-[0-9]{4}-(00[1-9]|0[1-9][0-9]|[1-9][0-9]{2,})" title="Use el formato #UNI-AAAA-001" required>
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <label class="custom-check-small w-100 p-2"><input type="checkbox" id="lic_contribuyente"><label for="lic_contribuyente" class="fs-6 m-0">¿Es Contribuyente Especial?</label></label>
                        </div>
                    </div>

                    <div class="row mb-4 align-items-center">
                        <?php if(tieneVector(32)): ?>
                        <div class="col-md-3">
                            <label class="custom-check-small w-100 p-2"><input type="checkbox" id="lic_aplica_iva" checked><label for="lic_aplica_iva" class="fs-6 m-0">Aplicar I.V.A (16%)</label></label>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(tieneVector(31)): ?>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <label class="small fw-bold text-muted mb-1">Descuento Especial (%)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold bg-white text-dark">%</span>
                                <input type="number" step="0.01" min="0" max="100" id="lic_desc_manual" class="form-control fw-bold border-secondary" placeholder="0" value="0">
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(tieneVector(33)): ?>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <label class="small fw-bold text-muted mb-1">Serv. Logístico de Envío ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold bg-white text-dark">$</span>
                                <input type="number" step="0.01" min="0" id="lic_desc_logistica" class="form-control fw-bold border-secondary" placeholder="0.00" value="0">
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
                                $class_extra = (strpos(strtolower($nombre_mp), 'divisa') !== false) ? 'check-divisas-lic' : '';
                                echo "<label class='custom-check-small'><input class='check-pago-lic {$class_extra}' type='radio' name='lic_forma_pago' value='{$id_mp}' id='lic_pago_{$id_mp}'><label for='lic_pago_{$id_mp}'>{$nombre_mp}</label></label>";
                            }
                        ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 mt-4"><i class="bi bi-truck me-1"></i> Método de Entrega (Obligatorio):</h6>
                    <div class="mb-4">
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="lic_metodo_entrega" value="Retiro Sede: UNIFUERTE, C.A, Nro. 321, Zona Industrial de Desarollo, Edificio Unifuerte, Puerto Ordaz 8050, Bolívar">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Retiro Sede: UNIFUERTE, C.A, Nro. 321...</span>
                        </label>
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="lic_metodo_entrega" value="Retiro Sede: Edificio Kyra de Alta Vista, 4to piso, Puerto Ordaz, Ciudad Guayana, Puerto Ordaz 8050, Bolívar">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Retiro Sede: Edificio Kyra de Alta Vista...</span>
                        </label>
                        <label class="custom-check-small w-100 mb-2" style="justify-content: flex-start; padding: 12px;">
                            <input type="radio" name="lic_metodo_entrega" value="Envío por Delivery">
                            <span class="fs-6 fw-normal text-dark text-start lh-sm">Envío por Delivery</span>
                        </label>
                    </div>

                    <div class="mb-4 border p-3 rounded shadow-sm" style="background-color: #f0f8ff;">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-paperclip me-1 text-primary"></i> Anexos Adicionales</h6>
                        <input type="file" id="lic_anexos_files" class="form-control border-primary" accept="image/*,application/pdf" multiple>
                        <small class="text-muted mt-1 d-block">Sube imágenes o PDFs. Se imprimirán en <b>páginas completas</b> al final de tu cotización.</small>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-card-text me-1"></i> Notas Adicionales</h6>
                        <textarea id="lic_notas" class="form-control bg-light" rows="2" placeholder="Descripción corta (máximo 10 palabras)"></textarea>
                        <small class="text-muted">Descripción breve del pedido; máximo 10 palabras.</small>
                    </div>
                    
                    <button type="submit" id="btnOcultoGuardarLicitacion" style="display: none;"></button>
                </form>
            </div>

            <div class="col-lg-5">
                
                <div class="bg-white p-3 rounded shadow-sm border mb-4">
                    <h6 class="fw-bold mb-3 text-center" style="color: #0B4C8C;"><i class="bi bi-plus-circle"></i> Añadir Ítems Manuales</h6>
                    <form id="formAgregarItemLicitacion">
                        <div class="mb-2">
                            <label class="small fw-bold text-muted">Descripción Detallada</label>
                            <textarea id="lic_desc" class="form-control form-control-sm border-secondary btn-bloqueable" rows="2" placeholder="Ej: Jersey Deportiva..." required></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Precio Unit. ($)</label>
                                <input type="number" step="0.01" id="lic_precio" class="form-control form-control-sm text-center fw-bold btn-bloqueable" placeholder="0.00" required>
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold text-muted">Cantidad</label>
                                <input type="number" id="lic_cant" class="form-control form-control-sm text-center fw-bold btn-bloqueable" value="1" min="1" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm w-100 fw-bold text-white shadow-sm btn-bloqueable" style="background-color: #0B4C8C;" id="btnAddItemLic">+ Añadir al Resumen</button>
                    </form>
                </div>

                <div class="resumen-checkout-box shadow-sm">
                    <h6 class="fw-bold mb-3 text-center" style="color: #854d0e;"><i class="bi bi-cart-check"></i> Resumen del Pedido Especial</h6>
                    <div id="resumenLicitacionBox" style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle" style="font-size: 0.85rem;">
                                <thead class="table-light"><tr><th style="width:40px;">#</th><th>Descripción</th><th class="text-center">Cant</th><th class="text-end">P.Unit</th><th class="text-end">Subtotal</th><th class="text-center">Acción</th></tr></thead>
                                <tbody id="tbodyLicitacionItems">
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay elementos agregados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between small text-muted mb-1"><span>Sub-Total:</span> <span id="lic_lbl_subtotal">0,00 $</span></div>
                        <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="lic_cajaDescLogistica" style="display: none !important;"><span>Serv. Logístico de Envío:</span> <span id="lic_lbl_descLogistica">0,00 $</span></div>
                        <div class="d-flex justify-content-between small text-danger fw-bold mb-1" id="lic_cajaDescuento" style="display: none !important;"><span>Descuento (<span id="lic_lbl_porcentajeDesc">0</span>):</span> <span id="lic_lbl_montoDesc">-0,00 $</span></div>
                        <div class="d-flex justify-content-between small text-dark fw-bold mb-1" id="lic_cajaBaseImponible" style="display: none !important;"><span>Base Imponible:</span> <span id="lic_lbl_baseImponible">0,00 $</span></div>
                        <div class="d-flex justify-content-between small text-muted mb-1" id="lic_cajaIva"><span>I.V.A (16%):</span> <span id="lic_lbl_iva">0,00 $</span></div>
                        <div class="d-flex justify-content-between small text-warning fw-bold mb-2" id="lic_cajaIgtf" style="display: none !important;"><span>IGTF (3% - Divisas Efectivo):</span> <span id="lic_lbl_igtf">0,00 $</span></div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 mb-3"><span class="fw-bold fs-5">Total a Pagar:</span> <span class="fs-4 fw-bold" style="color: #0B4C8C;" id="lic_lbl_total">0,00 $</span></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-end border-top pt-3">
            <button type="button" class="btn fw-bold text-white shadow px-5 py-3 fs-5 btn-bloqueable" style="background-color: #ca8a04; border-radius: 50px;" onclick="$('#btnOcultoGuardarLicitacion').click();" id="btnVisualGuardarLicitacion">Generar Cotización Oficial <i class="bi bi-file-pdf ms-2"></i></button>
        </div>
    </div>
</div>