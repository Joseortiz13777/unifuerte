<!-- VISTA 3: MATERIA PRIMA (NUEVO CATÁLOGO) -->
<div id="vistaMp" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-box-seam me-2" style="color: #FF7518;"></i> Catálogo Materia Prima</h3>
        <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="abrirModalMp()"><i class="bi bi-plus-lg"></i> Nuevo Insumo/MP</button>
    </div>

    <!-- Panel de Filtros -->
    <div class="bg-white p-3 rounded shadow-sm border mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="small fw-bold text-muted">Buscar Insumo</label>
                <input type="text" id="filtroBuscadorMp" class="form-control" placeholder="Nombre, color, código...">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Filtrar por Categoría</label>
                <select id="filtroCatMp" class="form-select border-primary fw-bold text-primary">
                    <option value="">Todas las Categorías</option>
                    <?php foreach($categorias as $c): ?>
                        <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted">Filtrar por Subcategoría</label>
                <select id="filtroSubCatMp" class="form-select border-primary fw-bold text-primary">
                    <option value="">Todas las Subcategorías</option>
                    <?php foreach($subcategorias as $s): ?>
                        <option value="<?= htmlspecialchars($s['nombre']) ?>" data-categoria="<?= htmlspecialchars($s['id_mp_categoria']) ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">Estado Stock</label>
                <select id="filtroStockMp" class="form-select border-danger fw-bold text-danger">
                    <option value="">Todos</option>
                    <option value="agotado">Agotados (0)</option>
                    <option value="critico">Crítico (Bajo Mínimo)</option>
                    <option value="ok">Con Disponibilidad</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white p-3 rounded shadow-sm border table-responsive">
        <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;" id="tablaCatalogoMp">
            <!-- AQUI ESTA LA CORRECCION: Ajustadas las cabeceras para que coincidan con las 8 columnas del JS -->
            <thead class="table-light">
                <tr>
                    <th>Foto</th>
                    <th>Insumo / ID</th>
                    <th>Color/Cód</th>
                    <th>Dimensiones</th>
                    <th>Und</th>
                    <th class="text-center">Stock</th>
                    <th class="text-end">Costo ($)</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyMp"></tbody>
        </table>
    </div>
</div>

<!-- MODAL MATERIA PRIMA -->
<div class="modal fade" id="modalMp" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formMp" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-boxes"></i> Ficha de Materia Prima</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" name="accion" value="guardar_mp">
                <input type="hidden" name="ID" id="id_mp" value="0">
                
                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Información Principal</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Nombre del Insumo *</label>
                        <input type="text" name="nombre" id="mp_nombre" class="form-control border-primary" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Categoría General *</label>
                        <select id="mp_cat" class="form-select border-primary" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Subcategoría Específica *</label>
                        <select name="id_mp_subcategoria" id="mp_subcat" class="form-select border-primary" required disabled>
                            <option value="">Seleccione Categoría...</option>
                        </select>
                    </div>
                </div>
                
                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Atributos Físicos y Costos</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Color</label>
                        <input type="text" name="color" id="mp_color" class="form-control" placeholder="Ej: Azul Marino">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Código Color</label>
                        <input type="text" name="codigo_color" id="mp_codigo_color" class="form-control" placeholder="Ej: #000080">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Textura</label>
                        <input type="text" name="textura" id="mp_textura" class="form-control" placeholder="Ej: Suave, Corrugada">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Dimensiones / Tamaño</label>
                        <input type="text" name="tamano_dimension" id="mp_tamano" class="form-control" placeholder="Ej: Rollo 1000m, 15mm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Composición</label>
                        <input type="text" name="composicion" id="mp_comp" class="form-control" placeholder="Ej: 100% Algodón">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Origen / Procedencia</label>
                        <input type="text" name="origen_procedencia" id="mp_origen" class="form-control" placeholder="Ej: Nacional, Brasil">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted text-danger">Unidad Medida *</label>
                        <input type="text" name="unidad_medida" id="mp_und" class="form-control border-danger" placeholder="Ej: M, Kg, Und, Par" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted text-danger">Costo Unitario ($) *</label>
                        <input type="number" step="0.01" name="costo" id="mp_costo" class="form-control border-danger" required>
                    </div>
                </div>
                
                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Imagen del Insumo</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Subir Foto (Opcional)</label>
                        <input type="file" name="foto" id="mp_foto" class="form-control" accept="image/*">
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Inventario y Notas</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Stock Actual</label>
                        <input type="number" step="0.01" name="stock_actual" id="mp_stock" class="form-control fw-bold" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Stock Mínimo Alerta</label>
                        <input type="number" step="0.01" name="stock_minimo" id="mp_stock_min" class="form-control fw-bold text-danger" value="0">
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Descripción Detallada</label>
                        <textarea name="descripcion" id="mp_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white fw-bold px-5" style="background-color: #FF7518;">Guardar Materia Prima</button>
            </div>
        </form>
    </div>
</div>