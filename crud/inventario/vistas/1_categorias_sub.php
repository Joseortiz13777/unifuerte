<!-- VISTA 1: CATEGORÍAS MP -->
<div id="vistaCatMp" class="vista-spa">
    <h3 class="fw-bold mb-4" style="color: #0B4C8C;"><i class="bi bi-folder-fill me-2" style="color: #FF7518;"></i> Categorías de Materia Prima</h3>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="bg-white p-4 rounded shadow-sm border sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3" style="color: #FF7518;">Formulario Categoría</h5>
                <form id="formCatMp">
                    <input type="hidden" name="accion" value="guardar_cat">
                    <input type="hidden" name="id" id="id_cat" value="0">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nombre de la Categoría</label>
                        <input type="text" name="nombre" id="nombre_cat" class="form-control bg-light" required>
                    </div>
                    <button type="submit" class="btn text-white w-100 fw-bold" style="background-color: #0B4C8C;">Guardar</button>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none btn-cancelar">Cancelar Edición</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="bg-white p-3 rounded shadow-sm border table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>ID</th><th>Nombre</th><th class="text-center">Acciones</th></tr></thead>
                    <tbody id="tbodyCatMp"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- VISTA 2: SUBCATEGORÍAS MP -->
<div id="vistaSubCatMp" class="vista-spa" style="display: none;">
    <h3 class="fw-bold mb-4" style="color: #0B4C8C;"><i class="bi bi-diagram-3-fill me-2" style="color: #FF7518;"></i> Subcategorías de Materia Prima</h3>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="bg-white p-4 rounded shadow-sm border sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3" style="color: #FF7518;">Formulario Subcategoría</h5>
                <form id="formSubCatMp">
                    <input type="hidden" name="accion" value="guardar_subcat">
                    <input type="hidden" name="id" id="id_subcat" value="0">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Categoría Padre</label>
                        <select name="id_mp_categoria" id="id_mp_categoria_sub" class="form-select bg-light" required>
                            <option value="">Seleccione...</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nombre Subcategoría</label>
                        <input type="text" name="nombre" id="nombre_subcat" class="form-control bg-light" required>
                    </div>
                    <button type="submit" class="btn text-white w-100 fw-bold" style="background-color: #0B4C8C;">Guardar</button>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none btn-cancelar">Cancelar Edición</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="bg-white p-3 rounded shadow-sm border table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>ID</th><th>Categoría</th><th>Subcategoría</th><th class="text-center">Acciones</th></tr></thead>
                    <tbody id="tbodySubCatMp"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>