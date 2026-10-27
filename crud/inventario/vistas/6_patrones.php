<!-- VISTA 6: PATRONES DE COSTURA -->
<div id="vistaPatrones" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-bezier me-2" style="color: #FF7518;"></i> Directorio de Patrones</h3>
        <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="abrirModalPatron()"><i class="bi bi-plus-lg"></i> Nuevo Patrón</button>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border table-responsive">
        <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
            <thead class="table-light">
                <tr><th>ID</th><th>Foto</th><th>Código</th><th>Modelo de Prenda</th><th>Talla</th><th>Descripción</th><th class="text-center">Acciones</th></tr>
            </thead>
            <tbody id="tbodyPatrones"></tbody>
        </table>
    </div>
</div>

<!-- MODAL PATRONES -->
<div class="modal fade" id="modalPatron" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="formPatron" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-bezier"></i> Ficha de Patrón</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" name="accion" value="guardar_patron">
                <input type="hidden" name="ID" id="patron_id" value="0">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Código del Patrón *</label>
                        <input type="text" name="codigo_patron" id="patron_codigo" class="form-control border-primary" placeholder="Ej: PAT-001" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Modelo de prenda (Camisa, Pantalón, etc) *</label>
                        <input type="text" name="categoria" id="patron_categoria" class="form-control border-primary" placeholder="Ej: Camisas Ejecutivas" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Talla</label>
                        <input type="text" name="Talla" id="patron_talla" class="form-control" placeholder="Ej: M, 34, Única">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Medidas Específicas</label>
                        <input type="text" name="medidas" id="patron_medidas" class="form-control" placeholder="Ej: Hombro: 40cm, Pecho: 100cm">
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Descripción Detallada / Notas</label>
                        <textarea name="descripcion" id="patron_desc" class="form-control" rows="3" placeholder="Ej: Patrón base para camisas ejecutivas manga corta..."></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Foto del Patrón (Opcional)</label>
                        <input type="file" name="foto" id="patron_foto" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white fw-bold px-5" style="background-color: #FF7518;">Guardar Patrón</button>
            </div>
        </form>
    </div>
</div>