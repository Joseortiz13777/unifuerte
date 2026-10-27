<div id="vistaCotizacionesEliminadas" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-archive-fill me-2"></i> Archivador de Cotizaciones Eliminadas</h3>
            <p class="text-muted small mb-0 mt-1">Solo Master puede consultar y restaurar cotizaciones guardadas antes de su eliminación.</p>
        </div>
        <button id="btnRecargarCotizacionesEliminadas" class="btn btn-sm btn-primary fw-bold">
            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
        </button>
    </div>
    <div class="alert alert-warning small">
        La restauración no sobrescribe cotizaciones existentes. Las órdenes archivadas se vuelven a vincular únicamente si siguen sin cotización y pertenecen al mismo cliente.
    </div>
    <div class="bg-white p-3 rounded shadow-sm border table-responsive">
        <table class="table table-hover align-middle table-sm">
            <thead class="table-light">
                <tr>
                    <th>Nro. Cotización</th>
                    <th>Cliente</th>
                    <th>Eliminada</th>
                    <th>Eliminó</th>
                    <th>Detalles / Órdenes</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody id="tablaCotizacionesEliminadas">
                <tr><td colspan="7" class="text-center text-muted">Cargando archivador...</td></tr>
            </tbody>
        </table>
    </div>
</div>
