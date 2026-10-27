<div id="vistaCotizaciones" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-file-earmark-text me-2"></i> Historial Cotizaciones</h3>
        
        <div class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border flex-wrap" style="border-radius: 10px;">
            <?php if(tieneVector(7)): ?>
            <label class="small fw-bold text-muted ms-2">Vendedor:</label>
            <select id="filtro_vendedor_hist" class="form-select form-select-sm bg-light" style="width: 130px; border-radius: 6px;">
                <option value="">Todos</option>
                <?php foreach($lista_vendedores as $v): ?>
                    <option value="<?= $v['ID'] ?>"><?= htmlspecialchars($v['nombre'] . ' ' . $v['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <label class="small fw-bold text-muted ms-2" for="filtro_cliente_hist_busqueda">Cliente:</label>
            <div class="position-relative" style="width: 220px;">
                <input type="search" id="filtro_cliente_hist_busqueda" class="form-control form-control-sm bg-light" style="border-radius: 6px;" placeholder="Buscar cliente..." autocomplete="off">
                <div id="resultados_filtro_cliente_hist" class="list-group position-absolute w-100 shadow d-none" style="z-index: 20; max-height: 240px; overflow-y: auto;"></div>
            </div>
            <select id="filtro_cliente_hist" class="d-none" aria-hidden="true">
                <option value="">Todos los clientes</option>
            </select>

            <label class="small fw-bold text-muted ms-2">Estado:</label>
            <select id="filtro_estado_hist" class="form-select form-select-sm bg-light" style="width: 130px; border-radius: 6px;">
                <option value="">Todos</option>
                <option value="sin pagar">Sin Pagar</option>
                <option value="Pago Inicial">Pago Inicial</option>
                <option value="Pagado">Pagado</option>
                <option value="con_pagos">Cualquier Pago</option>
            </select>

            <label class="small fw-bold text-muted ms-2">Tracking:</label>
            <select id="filtro_tracking_hist" class="form-select form-select-sm bg-light" style="width: 130px; border-radius: 6px;">
                <option value="">Todos</option>
                <option value="sin_tracking">Sin Tracking</option>
                <option value="En Proceso">En Proceso</option>
                <option value="Terminado">Terminado</option>
            </select>
            
            <label class="small fw-bold text-muted ms-2">Desde:</label>
            <input type="date" id="filtro_desde_hist" class="form-control form-control-sm bg-light" style="width: 120px; border-radius: 6px;">
            <label class="small fw-bold text-muted">Hasta:</label>
            <input type="date" id="filtro_hasta_hist" class="form-control form-control-sm bg-light" style="width: 120px; border-radius: 6px;">
            
            <button class="btn btn-sm btn-primary fw-bold" onclick="cargarHistorial()"><i class="bi bi-search"></i> Filtrar</button>
        </div>
    </div>

    <div class="bg-white p-3 rounded shadow-sm border">
        <!-- TABS DE COTIZACIONES -->
        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="activas-tab" data-bs-toggle="tab" data-bs-target="#activas" type="button" role="tab"><i class="bi bi-file-earmark-text"></i> Cotizaciones Activas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-info" id="congeladas-tab" data-bs-toggle="tab" data-bs-target="#congeladas" type="button" role="tab"><i class="bi bi-snow"></i> Archivo (Congeladas)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-danger fw-bold" id="garantias-tab" data-bs-toggle="tab" data-bs-target="#garantias-textiles" type="button" role="tab"><i class="bi bi-shield-exclamation"></i> Garantías Textiles <span id="badgeGarantiasVentas" class="badge bg-danger rounded-pill d-none">0</span></button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="activas" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>#</th><th class="text-nowrap">Nro. Cotizaci&oacute;n</th><th style="min-width: 200px;">Cliente</th><th style="min-width: 170px;">Descripción</th><th class="text-nowrap">Fecha</th><th class="text-nowrap">Total</th><th class="text-nowrap">Estado Pago</th><th class="text-center">Comunicaciones</th><th class="text-center text-nowrap" style="min-width: 200px;">Acciones</th></tr></thead>
                        <tbody id="tablaHistorialBody"><tr><td colspan="9" class="text-center text-muted">Cargando historial...</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="congeladas" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>#</th><th class="text-nowrap">Nro. Cotizaci&oacute;n</th><th style="min-width: 200px;">Cliente</th><th style="min-width: 170px;">Descripción</th><th class="text-nowrap">Fecha</th><th class="text-nowrap">Total</th><th class="text-nowrap">Estado Pago</th><th class="text-center">Comunicaciones</th><th class="text-center text-nowrap" style="min-width: 200px;">Acciones</th></tr></thead>
                        <tbody id="tablaPausadasBody"><tr><td colspan="9" class="text-center text-muted">El archivo está vacío.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="garantias-textiles" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>#</th><th>Solicitud</th><th>Orden / Cotización</th><th>Cliente</th><th>Vendedor</th><th>Estado</th><th>Fecha</th><th>Evidencias / Tracking</th></tr></thead>
                        <tbody id="tablaGarantiasTextiles"><tr><td colspan="8" class="text-center text-muted">Cargando garantías textiles...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>