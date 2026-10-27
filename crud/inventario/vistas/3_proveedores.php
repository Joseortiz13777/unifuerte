<!-- VISTA 4: PROVEEDORES -->
<div id="vistaProveedores" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-person-vcard me-2" style="color: #FF7518;"></i> Directorio de Proveedores</h3>
        <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518;" onclick="abrirModalProveedor()"><i class="bi bi-plus-lg"></i> Nuevo Proveedor</button>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border">
        <div class="row mb-3 g-2">
            <div class="col-md-4">
                <input type="text" id="buscadorProveedores" class="form-control" placeholder="Buscar por Razón Social, RIF, Contacto...">
            </div>
            <div class="col-md-3">
                <select id="filtroTipoProvBusqueda" class="form-select border-primary fw-bold text-primary">
                    <option value="">Cualquier Tipo de Prov.</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filtroServicioProvBusqueda" class="form-select border-primary fw-bold text-primary">
                    <option value="">Cualquier Servicio/Rubro</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filtroDeudaProveedores" class="form-select border-primary fw-bold text-primary">
                    <option value="">Deuda: Todos</option>
                    <option value="Con Deuda">Con Deuda</option>
                    <option value="Sin Deuda">Solventes</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr><th>ID</th><th>Razón Social</th><th>Tipo</th><th>Servicio / Rubro</th><th>Contacto</th><th>Ubicación</th><th class="text-center">Acciones</th></tr>
                </thead>
                <tbody id="tbodyProveedores"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PROVEEDORES -->
<div class="modal fade" id="modalProveedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formProveedor" class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;">
                <h5 class="modal-title fw-bold"><i class="bi bi-building"></i> Datos del Proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" name="accion" value="guardar_proveedor">
                <input type="hidden" name="ID" id="prov_id" value="0">
                
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Información General</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="small fw-bold text-muted">Nombre / Razón Social *</label>
                        <input type="text" name="nombre" id="prov_nombre" class="form-control border-primary" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">RIF / Cédula Identidad</label>
                        <input type="text" name="RIF_cedula" id="prov_rif" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Persona de Contacto</label>
                        <input type="text" name="contacto" id="prov_contacto" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Teléfono principal / WhatsApp</label>
                        <input type="text" name="tlf" id="prov_tlf" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Costo Flete Inter. Promedio ($)</label>
                        <input type="number" step="0.01" name="flete_internacional" id="prov_flete" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="small fw-bold text-muted">Ubicación / Dirección Física</label>
                        <textarea name="ubicacion" id="prov_ubicacion" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-tags"></i> Categorización de Proveedor</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Tipo de Proveedor</label>
                        <select name="tipo_proveedor" id="prov_tipo" class="form-select border-primary" style="width: 100%;">
                            <option value="">Seleccione o escriba...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Servicio o Rubro Ofrecido</label>
                        <select name="servicio_ofrecido" id="prov_servicio" class="form-select border-primary" style="width: 100%;">
                            <option value="">Seleccione o escriba...</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-bank"></i> Datos Bancarios</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Número de Cuenta Bancaria</label>
                        <input type="text" name="nro_cuenta" id="prov_cuenta" class="form-control" placeholder="0102...">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Tipo de Cuenta</label>
                        <select name="tipo_cuenta" id="prov_tipo_cuenta" class="form-select border-primary">
                            <option value="">Seleccione...</option>
                            <option value="Corriente">Corriente</option>
                            <option value="Ahorro">Ahorro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Banco (Pago Móvil)</label>
                        <select name="pm_banco" id="prov_pm_banco" class="form-select border-primary" style="width: 100%;">
                            <option value="">Seleccione o escriba...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Cédula / RIF (Pago Móvil)</label>
                        <input type="text" name="pm_cedula" id="prov_pm_cedula" class="form-control" placeholder="Ej: V-12345678">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Teléfono (Pago Móvil)</label>
                        <input type="text" name="pm_telefono" id="prov_pm_telefono" class="form-control" placeholder="Ej: 0414...">
                    </div>
                    <div class="col-md-12 mt-3">
                        <label class="small fw-bold text-muted">Notas / Descripción Adicional</label>
                        <textarea name="descripcion" id="prov_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn text-white fw-bold px-5" style="background-color: #FF7518;">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>