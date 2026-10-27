<div id="vistaAuditoria" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-eye-fill me-2"></i> Historial de Movimientos (Auditoría)</h3>
        <div class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border flex-wrap">
            
            <label class="small fw-bold text-muted ms-1">Filtro:</label>
            <select id="audit_departamento" class="form-select form-select-sm bg-light fw-bold" style="width: 140px; color: #0B4C8C;">
                <option value="todos">Todo lo Permitido</option>
                <option value="ventas">Dpto. Ventas</option>
                <option value="licitaciones">Licitaciones</option>
                <option value="logistica">Logística</option>
                <option value="admin">Administración</option>
            </select>

            <label class="small fw-bold text-muted ms-1">Usuario:</label>
            <select id="audit_usuario_especifico" class="form-select form-select-sm bg-light" style="width: 140px;">
                <option value="0">Cualquiera</option>
                <?php foreach($lista_todos_usuarios as $usr): ?>
                    <option value="<?= $usr['ID'] ?>"><?= htmlspecialchars($usr['nombre'] . ' ' . $usr['apellido']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" id="audit_desde" class="form-control form-control-sm bg-light ms-1" style="width: 120px;" value="<?= date('Y-m-01') ?>">
            <input type="date" id="audit_hasta" class="form-control form-control-sm bg-light" style="width: 120px;" value="<?= date('Y-m-t') ?>">
            <button class="btn btn-sm text-white fw-bold ms-1" style="background-color: #FF7518;" onclick="cargarAuditoria()"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border table-responsive">
        <table class="table table-hover align-middle table-sm">
            <thead class="table-dark" style="background-color: #0B4C8C;"><tr><th>Fecha y Hora</th><th>Usuario</th><th>Acción</th><th>Módulo / Tabla</th><th>Detalle Técnico</th></tr></thead>
            <tbody id="tablaAuditoriaBody"><tr><td colspan="5" class="text-center text-muted">Haga clic en filtrar.</td></tr></tbody>
        </table>
    </div>
</div>