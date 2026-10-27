<!-- ================= VISTA DE RECETAS POR PRENDA ================= -->
<div id="vistaReceta" class="vista-spa" style="display: <?= !tieneVector(21) ? 'block' : 'none' ?>;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-4" style="color: #0B4C8C;"><i class="bi bi-file-earmark-ruled me-2" style="color: #FF7518;"></i> Ficha T&eacute;cnica de Prendas</h3>
    </div>
    
    <div class="row">
        <!-- PANEL IZQUIERDO: Constructor / Editor -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="bg-white p-4 rounded shadow-sm border sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3" style="color: #FF7518;">1. Elegir Prenda Base</h5>
                
                <div class="mb-3 border-bottom pb-3">
                    <select id="receta_prod_master" class="form-select border-primary bg-light fw-bold" style="color: #0B4C8C; font-size: 1.1rem;">
                        <option value="">--- Seleccione la Prenda del Cat&aacute;logo ---</option>
                        <?php foreach($productos as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="panel_constructor_receta" style="display: none; transition: 0.3s;">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-info"></i> 2. Detalles de la Variante / Receta</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold">Nombre / Descripción Base *</label>
                            <input type="text" id="receta_nombre_variante" class="form-control border-primary fw-bold text-dark" placeholder="Ej: Diseño Estándar">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">¿Talla Específica?</label>
                            <input type="text" id="receta_talla" class="form-control border-info text-center fw-bold text-dark" placeholder="Ej: S, M, XL, 34">
                            <small class="text-muted" style="font-size:10px;">Dejar vacío si aplica a todas</small>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">¿Género?</label>
                            <select id="receta_genero" class="form-select border-info text-dark fw-bold">
                                <option value="">Unisex / Todos</option>
                                <option value="Caballero">Caballero</option>
                                <option value="Dama">Dama</option>
                                <option value="Infantil">Infantil</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold">Foto Referencia</label>
                            <input type="file" id="receta_foto" class="form-control border-secondary" accept="image/*">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3 mt-4"><i class="bi bi-plus-circle-fill text-success"></i> 3. Agregar Insumo a la Receta</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <select id="receta_mp_tmp" class="form-select bg-light">
                                <option value="" data-costo="0">Buscar Insumo (Tela, Hilo, Cierre, etc)...</option>
                                <?php foreach($materia_prima as $m): ?>
                                    <option value="<?= $m['ID'] ?>" data-und="<?= $m['unidad_medida'] ?>" data-costo="<?= $m['costo'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= $m['unidad_medida'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-bold">Cantidad (<span id="lblUndRecetaTmp" class="text-danger">UND</span>)</label>
                            <input type="number" step="0.01" id="receta_cant_tmp" class="form-control text-center fw-bold" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted fw-bold">Costo ($)</label>
                            <input type="text" id="receta_costo_tmp" class="form-control bg-white text-end fw-bold text-success" readonly placeholder="0.00 $">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100 fw-bold mb-4" onclick="window.agregarItemAListaReceta()"><i class="bi bi-chevron-double-down"></i> Agregar a la Lista</button>

                    <div class="receta-cart-box mb-3">
                        <h6 class="fw-bold text-muted mb-2 border-bottom pb-1">Insumos de esta Variante</h6>
                        <div class="lista-cart-items">
                            <ul class="list-group list-group-flush" id="ul_lista_receta">
                                <li class="list-group-item text-center text-muted small pb-0 border-0">Agregar insumos a la lista.</li>
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <span class="small fw-bold text-muted">Costo Producci&oacute;n (Unitario):</span>
                            <h5 class="fw-bold m-0 text-danger" id="lbl_costo_total_receta">0.00 $</h5>
                        </div>
                    </div>

                    <button type="button" class="btn text-white w-100 fw-bold shadow-sm" style="background-color: #0B4C8C; font-size: 1.1rem;" onclick="window.guardarRecetaCompleta()"><i class="bi bi-save"></i> Guardar Ficha / Variante</button>
                </div>
            </div>
        </div>
        
        <!-- PANEL DERECHO: Listado y Buscador -->
        <div class="col-xl-7 col-lg-6">
            <div class="bg-white p-3 rounded shadow-sm border table-responsive">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-muted">Variantes y Fichas T&eacute;cnicas Guardadas</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="cargarRecetasAgrupadas()"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                </div>

                <!-- BUSCADOR INTELIGENTE -->
                <div class="input-group mb-4 shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscadorRecetas" class="form-control border-start-0 fw-bold text-primary" placeholder="Buscar por nombre de prenda, talla o variante...">
                </div>

                <table class="table table-hover align-middle table-sm">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Foto</th>
                            <th>Prenda & Variante</th>
                            <th class="text-center">Insumos</th>
                            <th class="text-end">Costo ($)</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyRecetasAgrupadas">
                        <!-- Llenado por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VISTA PREVIA DE RECETA -->
<div class="modal fade" id="modalVerReceta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-card-checklist"></i> Desglose de Ficha T&eacute;cnica</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex align-items-center border-bottom pb-3 mb-4">
                    <img id="img_receta_preview" src="../../img/camisa.jpg" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #ccc; margin-right: 15px;">
                    <div>
                        <h4 id="lbl_receta_prenda_nombre" class="fw-bold text-dark m-0">Nombre de la Prenda</h4>
                        <span id="lbl_receta_variante_nombre" class="text-muted fs-6">Variante</span>
                    </div>
                </div>
                
                <div class="table-responsive bg-white rounded shadow-sm border p-2">
                    <table class="table table-sm align-middle table-hover m-0">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo</th>
                                <th class="text-center">Requerido</th>
                                <th class="text-end">Costo ($)</th>
                                <th class="text-center">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalleReceta"></tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end align-items-center mt-3 pt-2">
                    <h4 class="fw-bold m-0" style="color: #FF7518;">Costo Total Producci&oacute;n: <span id="lbl_receta_costo_total">0.00 $</span></h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>