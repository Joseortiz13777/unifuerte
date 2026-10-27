<div id="vistaGestion" class="vista-spa" style="display: none;">
    <h3 class="fw-bold mb-4" style="color: #0B4C8C;"><i class="bi bi-boxes me-2"></i> Gestión de Catálogo</h3>
    <div class="row g-4">
        <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #e0e6ed !important;"><div class="card-body text-center p-4"><i class="bi bi-folder-fill fs-1 text-secondary mb-2"></i><h5 class="fw-bold">1. Categorías</h5><button class="btn btn-outline-primary w-100 fw-bold rounded-pill" onclick="abrirModalGestion('Categoria')">Gestionar Categorías</button></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="border-radius: 12px; border: 2px solid #e0e6ed !important;"><div class="card-body text-center p-4"><i class="bi bi-diagram-3-fill fs-1 text-secondary mb-2"></i><h5 class="fw-bold">2. Subcategorías</h5><button class="btn btn-outline-primary w-100 fw-bold rounded-pill" onclick="abrirModalGestion('Subcategoria')">Gestionar Subcategorías</button></div></div></div>
        <div class="col-md-4"><div class="card shadow-sm border-0 h-100" style="background-color: #fffaf0; border-radius: 12px; border: 2px solid #FF7518 !important;"><div class="card-body text-center p-4"><i class="bi bi-tags-fill fs-1" style="color: #FF7518;"></i><h5 class="fw-bold mt-2" style="color: #FF7518;">3. Productos</h5><button class="btn w-100 text-white fw-bold rounded-pill" style="background-color: #FF7518;" onclick="abrirModalGestion('Producto')">Gestionar Productos</button></div></div></div>
    </div>
</div>

<div class="modal fade" id="modalGestionCatalogo" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;"><h5 class="modal-title fw-bold" id="tituloModalGestion">Gestión</h5><div class="ms-auto me-3"><input type="text" id="buscadorGestion" class="form-control form-control-sm rounded-pill px-3" placeholder="Buscar general..." style="width: 200px;"></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-light" id="cuerpoModalGestion"></div>
        </div>
    </div>
</div>