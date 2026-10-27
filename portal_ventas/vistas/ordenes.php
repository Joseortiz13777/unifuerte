<!-- SECCIÓN 5: ÓRDENES DE FÁBRICA -->
<div id="vistaOrdenes" class="vista-seccion p-3 p-md-4" style="display: none;">
    <div id="seccionOrdenes">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold m-0 font-sugo" style="color: #0B4C8C;">Órdenes de Producción</h2>
                <p class="text-muted m-0">Gestión logística, tallas exactas, Ficha Piloto y emisión de PDF de Orden de Fábrica</p>
            </div>
            <?php if(tieneVector(11) || $rol_actual === 'master'): ?>
            <button type="button" class="btn text-white fw-bold shadow-sm px-4 py-2" style="background-color: #FF7518; border-radius: 50px;" onclick="window.forzarAperturaModalNuevaOrden()">
                <i class="bi bi-file-earmark-plus-fill me-2"></i> Generar Nueva Orden
            </button>
            <?php endif; ?>
        </div>

        <!-- BANDEJA EN VIVO DE ALERTAS DE PRODUCCIÓN (ESTRICTAMENTE SOLO LAS ENVIADAS A LOGÍSTICA) -->
        <div id="bandejaAlertasProduccionEnOrdenes" class="card border-warning shadow-sm mb-3" style="display:none; background-color:#fffbeb;">
            <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-bell-fill me-1"></i> ALERTAS DE PRODUCCIÓN: Cotizaciones enviadas a Logística pendientes por emitir Orden de Fábrica y PDF</span>
                <span class="badge bg-danger rounded-pill" id="contadorBandejaOrdenes">0</span>
            </div>
            <div class="card-body p-2">
                <div id="listaCardsAlertasEnOrdenes" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscadorOrdenes" class="form-control border-start-0" placeholder="Buscar por Nro Orden, Cliente o Vendedor...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label for="filtroClienteOrdenes" class="form-label small fw-bold text-muted mb-1">Filtrar por cliente</label>
                        <select id="filtroClienteOrdenes" class="form-select">
                            <option value="0">Todos los clientes</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nro. Orden</th>
                                <th>Cotización Origen</th>
                                <th>Emisión</th>
                                <th>Entrega</th>
                                <th>Cliente</th>
                                <th class="text-center pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaOrdenesBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL CREAR NUEVA ORDEN DE FÁBRICA -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalNuevaOrden" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="max-height: 92vh;">
            
            <div class="modal-header text-white py-3 px-4 flex-shrink-0" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-clipboard2-data"></i> Emitir Orden de Fábrica y Ficha Piloto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formNuevaOrden" enctype="multipart/form-data" class="d-flex flex-column overflow-hidden flex-grow-1 m-0" onsubmit="return window.procesarSubmitNuevaOrdenNativo(event)">
                <input type="hidden" name="ficha_confeccion_json" id="ord_ficha_confeccion_json" value="">

                <div class="modal-body bg-light p-3 p-md-4 overflow-auto flex-grow-1">
                    
                    <!-- PASO 1: VINCULAR COTIZACIÓN -->
                    <div class="p-3 bg-white border rounded shadow-sm mb-3">
                        <label class="fw-bold text-primary mb-2"><i class="bi bi-link-45deg fs-5 align-middle"></i> 1. Seleccionar Cotización Enviada a Logística</label>
                        <select id="ord_select_cotizacion" name="id_cotizacion" class="form-select border-primary fw-bold" required onchange="window.cargarDatosCotizacionEnOrdenNativo(this)">
                            <option value="">Cargando cotizaciones enviadas a logística...</option>
                        </select>
                        <small class="text-muted">Solo aparecen las cotizaciones que Ventas ya revisó y envió formalmente a Logística.</small>
                    </div>

                    <div id="contenedor_datos_orden" style="display: none;">
                        <input type="hidden" name="id_cliente" id="ord_id_cliente">
                        <input type="hidden" name="id_vendedor" id="ord_id_vendedor">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-sm h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-person-badge"></i> Datos Generales</h6>
                                    <div class="mb-2"><span class="small text-muted fw-bold">Cliente:</span> <div id="ord_lbl_cliente" class="fw-bold text-primary"></div></div>
                                    <div class="mb-2"><span class="small text-muted fw-bold">Vendedor:</span> <div id="ord_lbl_vendedor" class="fw-bold text-dark"></div></div>
                                    <div><span class="small text-muted fw-bold">Nro. Orden Asignado:</span> <input type="text" name="nro_orden" id="ord_nro_orden" class="form-control form-control-sm fw-bold bg-light mt-1 text-danger" readonly></div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="p-3 bg-white border rounded shadow-sm h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-calendar-check"></i> Logística, Fechas e Imágenes de Diseño</h6>
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-4"><label class="small text-muted fw-bold">Fecha Emisión</label><input type="date" name="fecha_emision" id="ord_fecha_emision" class="form-control form-control-sm" required readonly></div>
                                        <div class="col-sm-4"><label class="small text-muted fw-bold text-danger">Fecha Tope Entrega</label><input type="date" name="fecha_entrega" id="ord_fecha_entrega" class="form-control form-control-sm border-danger fw-bold" required></div>
                                        <div class="col-sm-4"><label class="small text-muted fw-bold">¿Acepta Parciales?</label><select name="acepta_parciales" class="form-select form-select-sm"><option value="1">Sí</option><option value="0">No (Entrega Única)</option></select></div>
                                    </div>
                                    <div class="row g-2 align-items-center mt-2 pt-2 border-top">
                                        <div class="col-sm-5">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="es_express" id="ord_es_express" value="1" style="transform: scale(1.2);">
                                                <label class="form-check-label fw-bold text-danger ms-2" for="ord_es_express"><i class="bi bi-lightning-charge-fill"></i> ¡ORDEN EXPRESS!</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-7">
                                            <label class="small text-muted fw-bold"><i class="bi bi-images"></i> Subir más imágenes de diseño (Opcional)</label>
                                            <input type="file" name="fotos_diseno[]" id="ord_fotos_diseno" class="form-control form-control-sm" accept="image/*,application/pdf" multiple>
                                            <div id="fotos_diseno_previas" class="mt-2 d-flex flex-wrap gap-2"></div>
                                            <input type="hidden" name="fotos_existentes" id="ord_fotos_existentes" value="">
                                        </div>
                                    </div>
                                    <div id="ord_selectores_frente_espalda_wrap" class="row g-2 mt-2 pt-2 border-top" style="display:none;">
                                        <div class="col-sm-6">
                                            <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-bounding-box"></i> Foto VISTA FRENTE (Ficha Piloto):</label>
                                            <select id="ord_fp_foto_frente" class="form-select form-select-sm border-primary fw-bold"></select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-badge"></i> Foto VISTA ESPALDA (Ficha Piloto):</label>
                                            <select id="ord_fp_foto_espalda" class="form-select form-select-sm border-primary fw-bold"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 2: PRENDAS Y ARTES DETECTADOS -->
                        <div class="p-3 bg-white border rounded shadow-sm mb-3">
                            <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-basket"></i> 2. Resumen de Prendas, Telas y Bordados</h6>
                            <p class="small text-muted mb-2">Este texto alimenta la descripción general de la orden:</p>
                            <textarea name="descripcion_general" id="ord_descripcion" class="form-control fw-bold text-dark bg-light" rows="4" required></textarea>
                        </div>

                        <!-- PASO 2.5: FICHA PILOTO DE CONFECCIÓN, CHECKLISTS Y EMPAQUE -->
                        <div class="p-3 bg-white border border-primary rounded shadow-sm mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="fw-bold m-0" style="color: #0f2942;">
                                    <i class="bi bi-clipboard2-check-fill text-warning me-1"></i> 2.5 Ficha Piloto de Confección, Diseño, Etiquetado y Empaque (Bolsas / Sacos)
                                </h6>
                                <button class="btn btn-sm btn-outline-primary fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFichaPilotoNuevaOrden" aria-expanded="true">
                                    <i class="bi bi-sliders"></i> Mostrar / Ocultar Checklists de Ficha Piloto
                                </button>
                            </div>
                            <div class="collapse show" id="collapseFichaPilotoNuevaOrden">
                                <div id="ord_contenedor_ficha_piloto"></div>
                            </div>
                        </div>

                        <!-- PASO 3: ESPECIFICACIÓN DE TALLAS Y TELAS POR PERSONA -->
                        <div class="p-3 bg-white border rounded shadow-sm mb-3 border-start border-primary border-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="check_asignar_tallas" style="transform: scale(1.3); cursor: pointer;">
                                    <label class="form-check-label fw-bold text-primary ms-2" for="check_asignar_tallas" style="cursor: pointer; font-size: 1rem;">
                                        <i class="bi bi-rulers"></i> 3. Asignación de Tallas Específicas, Telas y Nombres por Colaborador (Rótulos de Bolsa)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="seccion_asignacion_tallas" style="display: none;" class="mt-3 pt-3 border-top">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                                    <div>
                                        <small class="text-muted">Se han cargado los nombres, tallas y telas especificados por el vendedor (cada fila genera su rótulo de bolsa individual):</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="window.autogenerarFilasTallas()"><i class="bi bi-magic"></i> Re-Sincronizar con Vendedor</button>
                                        <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="window.agregarFilaMedidaVacia()"><i class="bi bi-plus-lg"></i> Añadir Línea Extra</button>
                                    </div>
                                </div>
                                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 65px;" class="text-center">Cant.</th>
                                                <th style="width: 120px;">Género</th>
                                                <th style="width: 90px;" class="text-center">Talla</th>
                                                <th style="width: 220px;">Prenda</th>
                                                <th style="width: 220px;">Tela y Color Definitivo</th>
                                                <th>Nombre Persona (Rótulo Bolsa)</th>
                                                <th>Observación / Detalles</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_medidas_personales"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 4: MEDIDAS EXACTAS DE SASTRERÍA -->
                        <div class="p-3 bg-white border rounded shadow-sm mb-3 border-start border-warning border-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="check_sastreria" style="transform: scale(1.3); cursor: pointer;">
                                    <label class="form-check-label fw-bold text-dark ms-2" for="check_sastreria" style="cursor: pointer; font-size: 1rem;">
                                        <i class="bi bi-scissors text-warning"></i> 4. ¿Requiere Medidas Exactas de Sastrería (En centímetros)? (Opcional)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="seccion_sastreria" style="display: none;" class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted">Puedes importar los nombres de la tabla de arriba o añadir filas manualmente:</span>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-warning fw-bold text-dark me-1" onclick="window.autogenerarSastreria()"><i class="bi bi-arrow-down-circle"></i> Importar Personas de Arriba</button>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="window.agregarFilaSastreriaVacia()"><i class="bi bi-plus"></i> Fila Manual</button>
                                    </div>
                                </div>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered align-middle text-center" style="font-size: 0.8rem;">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th style="min-width: 150px;">Nombre Persona / Prenda</th>
                                                <th>Hombro</th>
                                                <th>Busto/Tórax</th>
                                                <th>Cintura</th>
                                                <th>Cadera</th>
                                                <th>Largo Camisa</th>
                                                <th>Largo Brazo</th>
                                                <th>Largo Pant.</th>
                                                <th>Tiro Total</th>
                                                <th>Muslo</th>
                                                <th>Rodilla</th>
                                                <th>Talón</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_sastreria"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                
                <div class="modal-footer bg-white py-3 px-4 mt-auto flex-shrink-0 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lg text-white fw-bold shadow-sm" style="background-color: #FF7518;" id="btnGuardarOrden"><i class="bi bi-printer-fill"></i> Guardar y Emitir PDF de Orden de Fábrica</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL EDITAR / ALTERAR ORDEN DE FÁBRICA EXISTENTE (VECTOR 53) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditarOrden" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="max-height: 92vh;">
            
            <div class="modal-header bg-warning text-dark py-3 px-4 flex-shrink-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Alterar Orden de Fábrica y Ficha Piloto: <span id="edit_ord_lbl_nro" class="text-danger"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formEditarOrden" enctype="multipart/form-data" class="d-flex flex-column overflow-hidden flex-grow-1 m-0">
                <input type="hidden" name="id_orden" id="edit_ord_id">
                <input type="hidden" name="fotos_existentes" id="edit_ord_fotos_existentes">
                <input type="hidden" name="ficha_confeccion_json" id="edit_ord_ficha_confeccion_json" value="">

                <div class="modal-body bg-light p-3 p-md-4 overflow-auto flex-grow-1">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded shadow-sm h-100">
                                <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-person-badge"></i> Datos Generales</h6>
                                <div class="mb-2"><span class="small text-muted fw-bold">Cliente:</span> <div id="edit_ord_lbl_cliente" class="fw-bold text-primary"></div></div>
                                <div class="mb-2"><span class="small text-muted fw-bold">Vendedor:</span> <div id="edit_ord_lbl_vendedor" class="fw-bold text-dark"></div></div>
                                <div><span class="small text-muted fw-bold">Cotización:</span> <div id="edit_ord_lbl_cotizacion" class="fw-bold text-secondary"></div></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="p-3 bg-white border rounded shadow-sm h-100">
                                <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-calendar-check"></i> Alterar Logística, Fechas e Imágenes</h6>
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-4"><label class="small text-muted fw-bold">Fecha Emisión</label><input type="date" name="fecha_emision" id="edit_ord_fecha_emision" class="form-control form-control-sm bg-light" readonly></div>
                                    <div class="col-sm-4"><label class="small text-muted fw-bold text-danger">Fecha Tope Entrega</label><input type="date" name="fecha_entrega" id="edit_ord_fecha_entrega" class="form-control form-control-sm border-danger fw-bold" required></div>
                                    <div class="col-sm-4"><label class="small text-muted fw-bold">¿Acepta Parciales?</label><select name="acepta_parciales" id="edit_ord_acepta_parciales" class="form-select form-select-sm"><option value="1">Sí</option><option value="0">No (Entrega Única)</option></select></div>
                                </div>
                                <div class="row g-2 align-items-center mt-2 pt-2 border-top">
                                    <div class="col-sm-5">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="es_express" id="edit_ord_es_express" value="1" style="transform: scale(1.2);">
                                            <label class="form-check-label fw-bold text-danger ms-2" for="edit_ord_es_express"><i class="bi bi-lightning-charge-fill"></i> ¡ORDEN EXPRESS!</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-7">
                                        <label class="small text-muted fw-bold"><i class="bi bi-images"></i> Añadir más imágenes de diseño</label>
                                        <input type="file" name="fotos_diseno[]" id="edit_ord_fotos_diseno" class="form-control form-control-sm" accept="image/*,application/pdf" multiple>
                                        <div id="edit_fotos_diseno_previas" class="mt-2 d-flex flex-wrap gap-2"></div>
                                    </div>
                                </div>
                                <div id="edit_ord_selectores_frente_espalda_wrap" class="row g-2 mt-2 pt-2 border-top" style="display:none;">
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-bounding-box"></i> Foto VISTA FRENTE (Ficha Piloto):</label>
                                        <select id="edit_ord_fp_foto_frente" class="form-select form-select-sm border-primary fw-bold"></select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-badge"></i> Foto VISTA ESPALDA (Ficha Piloto):</label>
                                        <select id="edit_ord_fp_foto_espalda" class="form-select form-select-sm border-primary fw-bold"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-white border rounded shadow-sm mb-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-basket"></i> Resumen de Prendas, Telas y Bordados</h6>
                        <textarea name="descripcion_general" id="edit_ord_descripcion" class="form-control fw-bold text-dark bg-light" rows="4" required></textarea>
                    </div>

                    <div class="p-3 bg-white border border-warning rounded shadow-sm mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold m-0" style="color: #0f2942;">
                                <i class="bi bi-clipboard2-check-fill text-warning me-1"></i> Alterar Ficha Piloto de Confección, Diseño, Etiquetado y Empaque (Bolsas / Sacos)
                            </h6>
                            <button class="btn btn-sm btn-outline-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFichaPilotoEditarOrden" aria-expanded="true">
                                <i class="bi bi-sliders"></i> Mostrar / Ocultar Checklists de Ficha Piloto
                            </button>
                        </div>
                        <div class="collapse show" id="collapseFichaPilotoEditarOrden">
                            <div id="edit_ord_contenedor_ficha_piloto"></div>
                        </div>
                    </div>

                    <div class="p-3 bg-white border rounded shadow-sm mb-3 border-start border-primary border-5">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                            <h6 class="fw-bold text-primary m-0"><i class="bi bi-rulers"></i> Asignación de Tallas Específicas, Telas y Nombres por Colaborador (Rótulos de Bolsa)</h6>
                            <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="window.agregarFilaMedidaVaciaEditar()"><i class="bi bi-plus-lg"></i> Añadir Línea Extra</button>
                        </div>
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 65px;" class="text-center">Cant.</th>
                                        <th style="width: 120px;">Género</th>
                                        <th style="width: 90px;" class="text-center">Talla</th>
                                        <th style="width: 220px;">Prenda</th>
                                        <th style="width: 220px;">Tela y Color Definitivo</th>
                                        <th>Nombre Persona (Rótulo Bolsa)</th>
                                        <th>Observación / Detalles</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="edit_tbody_medidas_personales"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="p-3 bg-white border rounded shadow-sm mb-3 border-start border-warning border-5">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-scissors text-warning"></i> Medidas Exactas de Sastrería (En centímetros)</h6>
                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold" onclick="window.agregarFilaSastreriaVaciaEditar()"><i class="bi bi-plus"></i> Añadir Fila Sastrería</button>
                        </div>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle text-center" style="font-size: 0.8rem;">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th style="min-width: 150px;">Nombre Persona / Prenda</th>
                                        <th>Hombro</th>
                                        <th>Busto/Tórax</th>
                                        <th>Cintura</th>
                                        <th>Cadera</th>
                                        <th>Largo Camisa</th>
                                        <th>Largo Brazo</th>
                                        <th>Largo Pant.</th>
                                        <th>Tiro Total</th>
                                        <th>Muslo</th>
                                        <th>Rodilla</th>
                                        <th>Talón</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="edit_tbody_sastreria"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer bg-white py-3 px-4 mt-auto flex-shrink-0 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lg btn-warning text-dark fw-bold shadow-sm" id="btnGuardarEdicionOrden"><i class="bi bi-save-fill"></i> Guardar Alteraciones de Orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =========================================================================
// MOTOR 100% NATIVO PARA ALERTAS (ESTRICTAMENTE SOLO ENVIADAS A LOGÍSTICA)
// =========================================================================
window.sumarDiasHabilesLocalOrd = function(fechaInicio, dias) {
    var fecha = new Date(fechaInicio.getTime());
    var agregados = 0;
    while (agregados < dias) {
        fecha.setDate(fecha.getDate() + 1);
        var diaSemana = fecha.getDay();
        if (diaSemana !== 0 && diaSemana !== 6) agregados++;
    }
    return fecha.toISOString().split('T')[0];
};

window.emitirOrdenYAbrirPDFDirecto = function(idCotizacion, nroCotizacion, idCliente, idVendedor) {
    idCotizacion = parseInt(idCotizacion, 10) || 0;
    idCliente = parseInt(idCliente, 10) || 0;
    idVendedor = parseInt(idVendedor, 10) || 0;
    var etiqueta = nroCotizacion || ('#' + idCotizacion);

    Swal.fire({
        title: 'Emitir Orden de Fábrica (' + etiqueta + ')',
        html: '¿Deseas <b>emitir la Orden y abrir el PDF inmediatamente</b> con los datos cargados en la cotización, o prefieres abrir el formulario previo?',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#FF7518',
        denyButtonColor: '#0B4C8C',
        confirmButtonText: '🖨️ Emitir y Abrir PDF Ahora',
        denyButtonText: '✏️ Abrir Formulario Completo',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isDenied) {
            window.forzarAperturaModalNuevaOrden(idCotizacion);
            return;
        }
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Generando Orden de Fábrica y PDF...',
            allowOutsideClick: false,
            didOpen: function() { Swal.showLoading(); }
        });

        fetch('api/api_ordenes.php?accion=obtener_detalles_para_orden&id_cotizacion=' + encodeURIComponent(idCotizacion))
            .then(function(r) { return r.json(); })
            .then(function(resDet) {
                if (!resDet || !resDet.success) {
                    Swal.fire('Error', 'No se pudieron leer los detalles de la cotización.', 'error');
                    return;
                }

                var medidas_tallas = [];
                var medidas_sastreria = [];
                var descLineas = [];
                var fichaCot = resDet.ficha_confeccion_json || {};
                if (typeof fichaCot === 'string') {
                    try { fichaCot = JSON.parse(fichaCot); } catch(e) { fichaCot = {}; }
                }

                (resDet.data || []).forEach(function(d) {
                    var diseno = d.detalle_diseno || '';
                    var esArte = diseno.indexOf('Ubicación:') !== -1 || /^(bordado|serigraf|dtf|vinil)/i.test(String(d.tipo_prenda || '').trim());
                    if (/^tallas?\s+extras?\b/i.test(String(d.tipo_prenda || '').trim())) return;

                    if (esArte) {
                        descLineas.push('• ' + d.cantidad + 'x ' + d.tipo_prenda + ' -> ' + diseno);
                    } else {
                        descLineas.push('• ' + d.cantidad + 'x ' + d.tipo_prenda + ' (' + (d.genero || 'General') + ') - ' + diseno);
                        var telaCot = '';
                        if (diseno.indexOf('Tela:') !== -1) {
                            telaCot = diseno.split('| Talla:')[0].replace('Tela:', '').trim();
                        }

                        var specs = [];
                        if (d.especificaciones_tallas) {
                            try { specs = JSON.parse(d.especificaciones_tallas); } catch(e) {}
                        }

                        if (Array.isArray(specs) && specs.length > 0) {
                            specs.forEach(function(s) {
                                var cant = parseInt(s.cantidad, 10) || 1;
                                var gen = s.genero || d.genero || 'Caballero';
                                var talla = s.talla || 'M';
                                var tela = s.tela || telaCot || 'Estándar';
                                var nom = String(s.nombre || '').trim();
                                var obs = String(s.obs || '').trim();

                                var strOculto = '[TALLA:' + talla + '|GEN:' + gen + '|CANT:' + cant + '|PRENDA:' + d.tipo_prenda + '|TELA:' + tela + ']';
                                if (nom !== '') strOculto += ' Persona: ' + nom + '.';
                                if (obs !== '') strOculto += ' Nota: ' + obs;

                                medidas_tallas.push({
                                    nombre_personal: nom !== '' ? nom : ('Lote ' + cant + 'x ' + d.tipo_prenda),
                                    observacion: strOculto
                                });

                                if (s.medidas_personales) {
                                    var m = s.medidas_personales;
                                    var tieneM = Object.keys(m).some(function(k) { return parseFloat(m[k]) > 0; });
                                    if (tieneM) {
                                        medidas_sastreria.push({
                                            nombre_personal: (nom || 'Persona') + ' (' + d.tipo_prenda + ')',
                                            hombro: parseFloat(m.hombro) || 0,
                                            busto_torax: parseFloat(m.busto_torax) || 0,
                                            cintura: parseFloat(m.cintura) || 0,
                                            cadera: parseFloat(m.cadera) || 0,
                                            largo_camisa: parseFloat(m.largo_camisa) || 0,
                                            largo_brazo: parseFloat(m.largo_brazo) || 0,
                                            largo_pantalon: parseFloat(m.largo_pantalon) || 0,
                                            tiro_total: parseFloat(m.tiro_total) || 0,
                                            ancho_muslo: parseFloat(m.ancho_muslo) || 0,
                                            ancho_rodilla: parseFloat(m.ancho_rodilla) || 0,
                                            ancho_talon: parseFloat(m.ancho_talon) || 0,
                                            observacion: '[SASTRERIA]'
                                        });
                                    }
                                }
                            });
                        } else {
                            var cantDef = parseInt(d.cantidad, 10) || 1;
                            var genDef = d.genero || 'Caballero';
                            var strOcultoDef = '[TALLA:M|GEN:' + genDef + '|CANT:' + cantDef + '|PRENDA:' + d.tipo_prenda + '|TELA:' + (telaCot || 'Según cotización') + ']';
                            medidas_tallas.push({
                                nombre_personal: 'Lote ' + cantDef + 'x ' + d.tipo_prenda,
                                observacion: strOcultoDef
                            });
                        }
                    }
                });

                var hoy = new Date();
                var fechaEmision = hoy.toISOString().split('T')[0];
                var fechaEntrega = window.sumarDiasHabilesLocalOrd(hoy, 20);

                var fd = new FormData();
                fd.append('accion', 'guardar_orden');
                fd.append('id_cotizacion', idCotizacion);
                fd.append('id_cliente', idCliente);
                fd.append('id_vendedor', idVendedor);
                fd.append('fecha_emision', fechaEmision);
                fd.append('fecha_entrega', fechaEntrega);
                fd.append('acepta_parciales', '1');
                fd.append('descripcion_general', descLineas.join("\n"));
                fd.append('fotos_existentes', resDet.fotos_diseno || '');
                fd.append('medidas_tallas', JSON.stringify(medidas_tallas));
                fd.append('medidas_sastreria', JSON.stringify(medidas_sastreria));
                fd.append('ficha_confeccion_json', JSON.stringify(fichaCot || {}));

                fetch('api/api_ordenes.php', {
                    method: 'POST',
                    body: fd
                })
                .then(function(r2) { return r2.json(); })
                .then(function(resSave) {
                    if (resSave && resSave.success) {
                        window.refrescarAlertasProduccionGlobal();
                        if (typeof window.cargarOrdenes === 'function') window.cargarOrdenes();
                        else if (typeof window.cargarListaOrdenes === 'function') window.cargarListaOrdenes();

                        window.open('imprimir_orden.php?id=' + resSave.id_orden, '_blank');

                        Swal.fire({
                            icon: 'success',
                            title: '¡Orden ' + (resSave.nro_orden || '') + ' Emitida!',
                            html: 'La orden fue creada exitosamente.<br><br><a href="imprimir_orden.php?id=' + resSave.id_orden + '" target="_blank" class="btn btn-lg text-white fw-bold shadow" style="background-color:#FF7518;"><i class="bi bi-printer-fill"></i> Ver / Imprimir PDF de Orden Ahora</a>',
                            showConfirmButton: true,
                            confirmButtonText: 'Cerrar'
                        });
                    } else if (resSave && resSave.id_orden_existente) {
                        Swal.close();
                        window.open('imprimir_orden.php?id=' + resSave.id_orden_existente, '_blank');
                    } else {
                        Swal.fire('Error', (resSave && resSave.msg) ? resSave.msg : 'No se pudo emitir la orden.', 'error');
                    }
                })
                .catch(function() {
                    Swal.fire('Error', 'Fallo de conexión al emitir la orden.', 'error');
                });
            })
            .catch(function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            });
    });
};

window.forzarAperturaModalNuevaOrden = function(idCotizacionPreseleccionada) {
    var modalEl = document.getElementById('modalNuevaOrden');
    if (modalEl && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    var formEl = document.getElementById('formNuevaOrden');
    if (formEl) formEl.reset();

    var contDatos = document.getElementById('contenedor_datos_orden');
    if (contDatos) contDatos.style.display = 'none';

    var tbMed = document.getElementById('tbody_medidas_personales');
    if (tbMed) tbMed.innerHTML = '';

    var tbSas = document.getElementById('tbody_sastreria');
    if (tbSas) tbSas.innerHTML = '';

    var selCot = document.getElementById('ord_select_cotizacion');
    if (selCot) selCot.innerHTML = '<option value="">Buscando cotizaciones enviadas a logística...</option>';

    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    fetch('api/api_ordenes.php?accion=obtener_cotizaciones_aprobadas')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!selCot) return;
            var enviadas = (res && res.success && Array.isArray(res.data))
                ? res.data.filter(function(c) { return parseInt(c.enviado_logistica || 0, 10) === 1; })
                : [];

            if (enviadas.length > 0) {
                var opts = '<option value="">-- Selecciona una Cotización Enviada a Logística --</option>';
                enviadas.forEach(function(c) {
                    var vendNom = (c.vendedor_nombre || ((c.v_nombre || '') + ' ' + (c.v_apellido || ''))).trim() || 'Ventas';
                    opts += '<option value="' + c.ID + '" data-cliente-id="' + c.id_cliente + '" data-cliente-nom="' + String(c.cliente_nombre || '').replace(/"/g, '&quot;') + '" data-vend-id="' + c.id_vendedor + '" data-vend-nom="' + String(vendNom).replace(/"/g, '&quot;') + '" data-notas="' + String(c.notas || '').replace(/"/g, '&quot;') + '" data-fotos="' + String(c.fotos_diseno || '').replace(/"/g, '&quot;') + '">' + c.nro_cotizacion + ' - ' + c.cliente_nombre + ' (Enviada a Logística)</option>';
                });
                selCot.innerHTML = opts;

                if (idCotizacionPreseleccionada) {
                    selCot.value = String(idCotizacionPreseleccionada);
                    window.cargarDatosCotizacionEnOrdenNativo(selCot);
                }
            } else {
                selCot.innerHTML = '<option value="">No hay cotizaciones enviadas a logística pendientes por orden.</option>';
            }
        });
};

window.cargarDatosCotizacionEnOrdenNativo = function(selectElem) {
    if (typeof window.cargarDatosCotizacionEnOrden === 'function') {
        window.cargarDatosCotizacionEnOrden(selectElem);
        return;
    }
    var id_cot = selectElem.value;
    var contDatos = document.getElementById('contenedor_datos_orden');
    if (!id_cot) {
        if (contDatos) contDatos.style.display = 'none';
        return;
    }
    var opt = selectElem.options[selectElem.selectedIndex];
    document.getElementById('ord_id_cliente').value = opt.getAttribute('data-cliente-id') || '';
    document.getElementById('ord_lbl_cliente').textContent = opt.getAttribute('data-cliente-nom') || '';
    document.getElementById('ord_id_vendedor').value = opt.getAttribute('data-vend-id') || '';
    document.getElementById('ord_lbl_vendedor').textContent = opt.getAttribute('data-vend-nom') || '';

    var txtCot = opt.text || '';
    var matchNum = txtCot.match(/#?UNI-\d+-(\d+)/i) || txtCot.match(/(\d+)/);
    var inputNroOrd = document.getElementById('ord_nro_orden');
    if (inputNroOrd && matchNum && matchNum[1]) {
        inputNroOrd.value = 'OF-' + matchNum[1];
    }

    var hoy = new Date();
    document.getElementById('ord_fecha_emision').value = hoy.toISOString().split('T')[0];
    document.getElementById('ord_fecha_entrega').value = window.sumarDiasHabilesLocalOrd(hoy, 20);
    if (contDatos) contDatos.style.display = 'block';
};

window.procesarSubmitNuevaOrdenNativo = function(e) {
    if (e) e.preventDefault();
    var idCot = document.getElementById('ord_select_cotizacion').value;
    var idCli = document.getElementById('ord_id_cliente').value;
    var idVen = document.getElementById('ord_id_vendedor').value;
    if (!idCot) {
        Swal.fire('Atención', 'Selecciona primero una cotización.', 'warning');
        return false;
    }
    window.emitirOrdenYAbrirPDFDirecto(idCot, '', idCli, idVen);
    return false;
};

// REFRESCAR BANDEJA AMARILLA Y MODAL DE ALERTAS DE PRODUCCIÓN (ESTRICTAMENTE SOLO enviado_logistica === 1)
window.refrescarAlertasProduccionGlobal = function() {
    fetch('api/api_ordenes.php?accion=obtener_cotizaciones_aprobadas')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.success) return;
            var lista = Array.isArray(res.data) ? res.data : [];
            // FILTRO ESTRICTO: jamás hacer fallback a cotizaciones no enviadas
            var itemsMostrar = lista.filter(function(c) { return parseInt(c.enviado_logistica || 0, 10) === 1; });
            var totalAlertas = itemsMostrar.length;

            var badgeMenu = document.getElementById('badge_pendientes_menu');
            if (badgeMenu) {
                if (totalAlertas > 0) {
                    badgeMenu.textContent = totalAlertas;
                    badgeMenu.style.display = 'inline-block';
                } else {
                    badgeMenu.style.display = 'none';
                }
            }

            var htmlModal = '';
            var htmlBandeja = '';
            var bandejaEl = document.getElementById('bandejaAlertasProduccionEnOrdenes');
            var contadorEl = document.getElementById('contadorBandejaOrdenes');
            var cardsEl = document.getElementById('listaCardsAlertasEnOrdenes');
            var listaModalEl = document.getElementById('listaCotizacionesProduccion');

            if (itemsMostrar.length > 0) {
                itemsMostrar.forEach(function(c) {
                    var nroSafe = String(c.nro_cotizacion || '').replace(/'/g, "\\'");
                    var badgeEnv = '<span class="badge bg-success">Enviada a Logística</span>';

                    htmlModal += '<li class="list-group-item d-flex justify-content-between align-items-center py-3">' +
                        '<div>' +
                            '<div class="fw-bold text-primary">' + c.nro_cotizacion + ' — ' + c.cliente_nombre + '</div>' +
                            '<div class="small text-muted"><i class="bi bi-person-badge"></i> Vendedor: ' + (c.vendedor_nombre || 'Ventas') + ' | ' + badgeEnv + '</div>' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm text-white fw-bold shadow-sm px-3" style="background-color:#FF7518;" onclick="window.emitirOrdenYAbrirPDFDirecto(' + c.ID + ', \'' + nroSafe + '\', ' + (c.id_cliente || 0) + ', ' + (c.id_vendedor || 0) + ')">' +
                            '<i class="bi bi-printer-fill me-1"></i> Emitir Orden y PDF' +
                        '</button>' +
                    '</li>';

                    htmlBandeja += '<div class="p-2 bg-white border border-warning rounded shadow-sm d-flex align-items-center justify-content-between gap-3" style="min-width: 310px; flex: 1;">' +
                        '<div>' +
                            '<div class="fw-bold text-dark">' + c.nro_cotizacion + ' <span class="text-primary">(' + c.cliente_nombre + ')</span></div>' +
                            '<div class="small text-muted">Asesor: ' + (c.vendedor_nombre || 'Ventas') + ' · ' + badgeEnv + '</div>' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm text-white fw-bold text-nowrap shadow-sm" style="background-color:#FF7518;" onclick="window.emitirOrdenYAbrirPDFDirecto(' + c.ID + ', \'' + nroSafe + '\', ' + (c.id_cliente || 0) + ', ' + (c.id_vendedor || 0) + ')">' +
                            '<i class="bi bi-file-earmark-plus-fill"></i> Emitir Orden / PDF' +
                        '</button>' +
                    '</div>';
                });

                if (bandejaEl) bandejaEl.style.display = 'block';
                if (contadorEl) contadorEl.textContent = itemsMostrar.length;
                if (cardsEl) cardsEl.innerHTML = htmlBandeja;
            } else {
                htmlModal = '<li class="list-group-item text-muted text-center py-4"><i class="bi bi-check2-circle fs-3 d-block text-success mb-1"></i> No hay cotizaciones enviadas a logística pendientes por Orden de Fábrica.</li>';
                if (bandejaEl) bandejaEl.style.display = 'none';
            }

            if (listaModalEl) listaModalEl.innerHTML = htmlModal;
        })
        .catch(function(err) {
            console.error('Error cargando alertas de producción:', err);
        });
};

window.addEventListener("load", function() {
    window.abrirModalNuevaOrden = window.forzarAperturaModalNuevaOrden;
    window.chequearCotizacionesPendientes = window.refrescarAlertasProduccionGlobal;
    window.refrescarAlertasProduccionGlobal();

    var btnOrd = document.getElementById('btnMenuOrdenes');
    if (btnOrd) {
        btnOrd.addEventListener('click', function() {
            setTimeout(function() {
                var vOrd = document.getElementById('vistaOrdenes');
                var sOrd = document.getElementById('seccionOrdenes');
                if (vOrd) vOrd.style.display = 'block';
                if (sOrd) sOrd.style.display = 'block';
                window.refrescarAlertasProduccionGlobal();
            }, 50);
        });
    }
});
</script>