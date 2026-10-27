<div id="vistaDashboard" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-bar-chart-fill me-2"></i> <?= tieneVector(20) ? 'Panel Gerencial Estadístico' : 'Mi Desempeño' ?></h3>
        <div class="d-flex gap-2">
            <button class="btn text-white fw-bold shadow-sm" style="background-color: #FF7518; border-radius: 8px;" onclick="abrirFiltrosDashboard()"><i class="bi bi-funnel-fill me-1"></i> Reportes / Filtrar</button>
            <?php if($rol_actual === 'master'): ?>
                <button class="btn btn-danger text-white fw-bold shadow-sm" style="border-radius: 8px;" onclick="abrirModalCierre()"><i class="bi bi-envelope-paper-fill me-1"></i> Cierre Gerencial</button>
            <?php endif; ?>
        </div>
    </div>
    <div id="reporte-pdf-container" style="background: #f8fafc; padding: 10px;">
        <h4 id="titulo-reporte-actual" class="fw-bold text-dark mb-4 text-center border-bottom pb-2">Resumen General</h4>
        <div id="view-general">
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="dash-card dash-card-primary h-100">
                        <h6 class="text-white-50">Total Recaudado</h6>
                        <h2 class="fw-bold mb-0" id="dash_ingresos">$0.00</h2>
                        <div class="mt-2 d-flex align-items-center justify-content-between"><small class="text-white-50" id="dash_titulo_meta">Meta Asignada:</small><div id="meta_container"></div></div>
                        <div class="mt-1 small fw-bold" id="dash_meta_status">...</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card h-100 d-flex flex-column justify-content-center">
                        <h6 class="text-muted">Ventas Cerradas</h6>
                        <h2 class="fw-bold mb-0 text-dark" id="dash_ventas">0</h2>
                        <small class="text-success fw-bold mt-1"><i class="bi bi-check-circle-fill"></i> Pagado / Pago Inicial</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dash-card h-100 d-flex flex-column justify-content-center" style="background-color: #f0fdf4; border: 2px dashed #16a34a;">
                        <h6 class="text-muted fw-bold">Prendas terminadas en Envíos</h6>
                        <h2 class="fw-bold mb-0 text-success" id="dash_prendas">0</h2>
                        <small class="text-success fw-bold mt-1"><i class="bi bi-box-seam"></i> Pedidos finalizados: <span id="dash_pedidos_finalizados">0</span></small>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-12" id="col_chart_vendedores" style="display:none;">
                    <div class="dash-card">
                        <h6 class="text-muted mb-3 text-center fw-bold">Desempeño de Ventas por Asesor</h6>
                        <div style="height: 250px; position: relative;">
                            <canvas id="chartVendedores"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"><div class="dash-card"><h6 class="text-muted mb-3 text-center fw-bold">Top Productos</h6><div style="height: 250px; position: relative;"><canvas id="chartProductos"></canvas></div></div></div>
                <div class="col-md-6"><div class="dash-card"><h6 class="text-muted mb-3 text-center fw-bold">Ventas por Categoría</h6><div style="height: 250px; position: relative;"><canvas id="chartCategorias"></canvas></div></div></div>
            </div>
            <div class="dash-card mt-3">
                <h6 class="text-muted mb-3 fw-bold">Detalle de pagos del período</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>Fecha de pago</th><th>Cotización</th><th>Cliente</th><th>Vendedor</th><th>Estado</th><th>Referencia / Banco</th><th class="text-end">Monto ($)</th></tr>
                        </thead>
                        <tbody id="tablaPagosDashboard"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="view-vendedores" style="display:none;">
            <div class="table-responsive bg-white rounded shadow-sm border p-3">
                <table class="table align-middle table-hover">
                    <thead class="table-light"><tr><th>Vendedor</th><th>Vendido ($)</th><th class="text-center">Progreso de Meta</th><th class="text-center">Acciones</th></tr></thead>
                    <tbody id="tablaVendedoresBody"></tbody>
                </table>
            </div>
        </div>

        <div id="view-clientes" style="display:none;">
            <div class="table-responsive bg-white rounded shadow-sm border p-3">
                <table class="table align-middle table-hover">
                    <thead class="table-light"><tr><th>#</th><th>Cliente</th><th class="text-center">Compras</th><th class="text-end">Gastado ($)</th></tr></thead>
                    <tbody id="tablaTopClientesBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFiltrosDashboard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;"><h5 class="modal-title fw-bold"><i class="bi bi-funnel-fill"></i> Parámetros del Reporte</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Tipo de Reporte a Mostrar</label>
                    <select id="filtro_tipo_reporte" class="form-select border-primary fw-bold" style="color:#0B4C8C;" onchange="$('#div_filtro_vendedor').toggle(this.value === 'general');">
                        <option value="general">Resumen General (Gráficos)</option>
                        <?php if(tieneVector(20)): ?>
                        <option value="vendedores">Rendimiento y Asignar Metas (Vendedores)</option>
                        <option value="clientes">Ranking de Mejores Clientes</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <?php if(tieneVector(20)): ?>
                <div class="mb-3 border-top pt-3" id="div_filtro_vendedor">
                    <label class="fw-bold text-muted small">Filtrar por Vendedor Específico (Opcional)</label>
                    <select id="dash_vendedor" class="form-select border-secondary">
                        <option value="">Todos (Desempeño General de Empresa)</option>
                        <?php foreach($lista_vendedores as $v): ?>
                            <option value="<?= $v['ID'] ?>"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="row g-2 mb-3 border-top pt-3"><div class="col-6"><label class="fw-bold text-muted small">Desde</label><input type="date" id="dash_desde" class="form-control form-control-sm border-secondary"></div><div class="col-6"><label class="fw-bold text-muted small">Hasta</label><input type="date" id="dash_hasta" class="form-control form-control-sm border-secondary"></div></div>
                <div class="d-flex justify-content-center gap-2 mt-2"><button class="btn btn-sm btn-outline-secondary" onclick="setRangosRapidos('mes')">Este Mes</button><button class="btn btn-sm btn-outline-secondary" onclick="setRangosRapidos('mes_pasado')">Mes Pasado</button><button class="btn btn-sm btn-outline-secondary" onclick="setRangosRapidos('año')">Este Año</button><button class="btn btn-sm btn-outline-secondary" onclick="setRangosRapidos('todo')">Histórico</button></div>
            </div>
            <div class="modal-footer bg-white d-flex justify-content-between"><button class="btn btn-danger fw-bold shadow-sm" onclick="imprimirDashboard()"><i class="bi bi-file-pdf"></i> PDF</button><button class="btn fw-bold text-white shadow-sm" style="background-color: #FF7518;" onclick="aplicarFiltros()"><i class="bi bi-check2-circle"></i> Aplicar</button></div>
        </div>
    </div>
</div>

<!-- MODAL CIERRE GERENCIAL (SOLO MASTER) -->
<div class="modal fade" id="modalCierreGerencial" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #dc3545;">
                <h5 class="modal-title fw-bold"><i class="bi bi-envelope-paper-fill me-2"></i> Envío de Cierre Gerencial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-0">
                <ul class="nav nav-tabs px-3 pt-3 bg-white" id="tabCierreGerencial">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold text-danger" data-bs-toggle="tab" data-bs-target="#tab-cierre-manual" type="button"><i class="bi bi-send-fill"></i> Envío Inmediato</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold text-primary" data-bs-toggle="tab" data-bs-target="#tab-cierre-auto" type="button"><i class="bi bi-clock-history"></i> Programar Automatización</button>
                    </li>
                </ul>
                <div class="tab-content p-3">
                    <!-- ENVÍO INMEDIATO -->
                    <div class="tab-pane fade show active" id="tab-cierre-manual">
                        <p class="text-muted small">Se enviará un enlace de descarga en PDF abarcando del <span class="fw-bold text-dark" id="lblCierreDesde"></span> al <span class="fw-bold text-dark" id="lblCierreHasta"></span> a este correo:</p>
                        <div class="mb-3">
                            <label class="fw-bold text-dark small">Correo Electrónico de Destino *</label>
                            <input type="email" id="email_cierre_gerencial" class="form-control border-danger form-control-lg fs-6" placeholder="ejemplo@correo.com">
                        </div>
                        <div class="text-end">
                            <button class="btn btn-danger fw-bold shadow-sm rounded-pill px-4 w-100" id="btnEnviarCierreM" onclick="enviarCierreGerencial()"><i class="bi bi-send-fill me-1"></i> Generar y Enviar Enlace</button>
                        </div>
                    </div>

                    <!-- PROGRAMACIÓN AUTOMÁTICA -->
                    <div class="tab-pane fade" id="tab-cierre-auto">
                        <p class="text-muted small">El robot de la empresa enviará el reporte por enlace automáticamente de manera constante.</p>
                        <div class="mb-3">
                            <label class="fw-bold text-dark small">Correo Electrónico de Destino *</label>
                            <input type="email" id="auto_email_cierre" class="form-control border-primary" placeholder="ejemplo@correo.com">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="fw-bold text-dark small">Cada cuántos Días</label>
                                <select id="auto_dias_cierre" class="form-select border-primary">
                                    <option value="1">Diario (Cada 1 día)</option>
                                    <option value="7">Semanal (Cada 7 días)</option>
                                    <option value="15">Quincenal (Cada 15 días)</option>
                                    <option value="30">Mensual (Cada 30 días)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="fw-bold text-dark small">Hora de Envío</label>
                                <input type="time" id="auto_hora_cierre" class="form-control border-primary">
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4 w-100" id="btnGuardarAuto" onclick="guardarAutomatizacionCierre()"><i class="bi bi-save-fill me-1"></i> Guardar Programación</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>