<div id="vistaClientes" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-people-fill me-2"></i> Directorio de Clientes</h3>
    </div>
    <div class="row">
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="bg-white p-4 rounded shadow-sm border sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3" style="color: #FF7518;" id="tituloFormCliente">Nuevo Cliente</h5>
                <form id="formClienteAjax" class="needs-validation" novalidate>
                    <input type="hidden" name="accion_cliente_ajax" value="1">
                    <input type="hidden" name="id_cliente" id="cli_id" value="0">
                    <div class="mb-2"><input type="text" id="f_nombre" name="nombre" class="form-control bg-light border-secondary border-opacity-25" placeholder="Razón Social / Nombre *" required></div>
                    <div class="mb-2"><input type="text" id="f_cedula" name="cedula_rif" class="form-control bg-light border-secondary border-opacity-25" placeholder="Cédula / RIF *" required></div>
                    <div class="mb-2"><input type="text" id="f_tlf" name="tlf" class="form-control bg-light border-secondary border-opacity-25" placeholder="Teléfono (WhatsApp) *" required></div>
                    <div class="mb-2"><input type="text" id="f_comprador" name="comprador" class="form-control bg-light border-secondary border-opacity-25" placeholder="Contacto (Comprador)"></div>
                    <div class="mb-2"><input type="email" id="f_email" name="email" class="form-control bg-light border-secondary border-opacity-25" placeholder="Correo Electrónico"></div>
                    <div class="mb-2"><input type="text" id="f_redes" name="redes_sociales" class="form-control bg-light border-secondary border-opacity-25" placeholder="Redes Sociales (Ej: @instagram)"></div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="small fw-bold text-muted mb-1 text-nowrap"><i class="bi bi-calendar-check text-primary"></i> Frecuencia</label>
                            <select name="veces_uniforma" id="f_veces" class="form-select bg-light border-secondary border-opacity-25" style="font-size: 0.9rem;" required>
                                <option value="1">Anual</option>
                                <option value="2">Semestral</option>
                                <option value="3">Cuatrimestral</option>
                                <option value="4">Trimestral</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-muted mb-1 text-nowrap"><i class="bi bi-people text-primary"></i> Empleados</label>
                            <input type="number" id="f_personas" name="cantidad_personas" class="form-control bg-light border-secondary border-opacity-25" placeholder="Ej: 15" required min="1">
                        </div>
                    </div>

                    <div class="mb-3"><textarea name="direccion" id="f_direccion" class="form-control bg-light border-secondary border-opacity-25" rows="2" placeholder="Dirección Fiscal"></textarea></div>
                    
                    <?php if(tieneVector(2)): ?>
                    <button type="submit" class="btn text-white w-100 fw-bold shadow-sm" style="background-color: #0B4C8C;" id="btnRegistrarCliente">Guardar Cliente</button>
                    <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none" id="btnCancelarEdicion" onclick="cancelarEdicionCliente()">Cancelar Edición</button>
                    <?php else: ?>
                    <div class="alert alert-danger small py-2">No tienes permiso [2] para registrar clientes.</div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="col-xl-9 col-lg-8">
            <div class="bg-white p-3 rounded shadow-sm border">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
                    <h5 class="m-0 fw-bold" style="color: #0B4C8C;">Listado General</h5>
                    <div class="search-box w-100" style="max-width: 300px;"><i class="bi bi-search"></i><input type="text" id="buscadorDirectorio" class="form-control" placeholder="Buscar por Nombre o RIF..."></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaDirectorio">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="min-width: 200px;">Cliente</th>
                                <th class="text-nowrap">RIF</th>
                                <th class="text-nowrap">Frecuencia</th>
                                <th class="text-nowrap">Personal</th>
                                <th class="text-nowrap">WhatsApp</th>
                                <th class="text-center text-nowrap" style="min-width: 140px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaClientesBody">
                            <?php if(empty($clientes_bd)): ?>
                                <tr class="fila-vacia"><td colspan="6" class="text-center text-muted">No hay clientes registrados en la BD.</td></tr>
                            <?php else: ?>
                                <?php foreach($clientes_bd as $c): 
                                    $freq = "Anual";
                                    if($c['veces_uniforma'] == 2) $freq = "Semestral";
                                    elseif($c['veces_uniforma'] == 3) $freq = "Cuatrimestral";
                                    elseif($c['veces_uniforma'] == 4) $freq = "Trimestral";
                                ?>
                                <tr class="fila-datos">
                                    <td class="fw-bold col-nombre" style="color: #0B4C8C; min-width: 200px;">
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </td>
                                    <td class="col-cedula text-nowrap"><?php echo htmlspecialchars($c['cedula_Rif']); ?></td>
                                    <td class="text-nowrap"><span class="badge bg-info text-dark col-freq"><?= $freq ?></span></td>
                                    <td class="col-personal text-nowrap"><span class="badge bg-secondary"><?php echo $c['cantidad_personas']; ?></span></td>
                                    <td class="col-tlf text-nowrap"><?php echo htmlspecialchars($c['tlf']); ?></td>
                                    <td class="text-center text-nowrap">
                                        <?php if(tieneVector(2)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill me-1 btn-editar-cli" data-id="<?php echo $c['ID']; ?>" data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>" data-cedula="<?php echo htmlspecialchars($c['cedula_Rif']); ?>" data-tlf="<?php echo htmlspecialchars($c['tlf']); ?>" data-comprador="<?php echo htmlspecialchars($c['comprador']); ?>" data-email="<?php echo htmlspecialchars($c['email']); ?>" data-direccion="<?php echo htmlspecialchars($c['direccion']); ?>" data-redes="<?php echo htmlspecialchars($c['redes_sociales'] ?? ''); ?>" data-veces="<?php echo $c['veces_uniforma']; ?>" data-personas="<?php echo $c['cantidad_personas']; ?>"><i class="bi bi-pencil"></i></button>
                                        <?php endif; ?>
                                        
                                        <?php if(tieneVector(3)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill me-1 btn-eliminar-cli" data-id="<?php echo $c['ID']; ?>" title="Eliminar Cliente"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>

                                        <?php if(tieneVector(4) || tieneVector(26)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill btn-seleccionar-cli" data-id="<?php echo $c['ID']; ?>" data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>"><i class="bi bi-check-circle"></i> Cotizar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>