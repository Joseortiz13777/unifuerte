<div id="vistaUsuarios" class="vista-spa" style="display: none;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-shield-lock-fill me-2"></i> Gestión de Usuarios</h3>
        <button class="btn text-white fw-bold shadow-sm" style="background-color: #0B4C8C;" id="btnNuevoUsuario"><i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario</button>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Cédula</th><th>Nombre Completo</th><th>Usuario (Login)</th><th>Rol Nominal</th><th>Vectores Activos</th><th class="text-center">Acciones</th></tr></thead>
            <tbody id="tablaUsuariosBody"><tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #0B4C8C;"><h5 class="modal-title fw-bold" id="modalUsuarioLabel">Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-light">
                <form id="formUsuario">
                    <input type="hidden" name="id_usuario" id="id_usuario_modal" value="0">
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="small fw-bold text-muted">Nombre</label><input type="text" name="nombre" id="u_nombre" class="form-control form-control-sm" required></div>
                        <div class="col-4"><label class="small fw-bold text-muted">Apellido</label><input type="text" name="apellido" id="u_apellido" class="form-control form-control-sm" required></div>
                        <div class="col-4"><label class="small fw-bold text-muted">Cédula</label><input type="text" name="cedula" id="u_cedula" class="form-control form-control-sm" required></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="small fw-bold text-muted">Teléfono</label><input type="text" name="tlf" id="u_tlf" class="form-control form-control-sm" required></div>
                        <div class="col-4"><label class="small fw-bold text-muted">Profesión/Cargo</label><input type="text" name="profesion" id="u_profesion" class="form-control form-control-sm"></div>
                        <div class="col-4"><label class="small fw-bold text-muted">Email</label><input type="email" name="email" id="u_email" class="form-control form-control-sm"></div>
                    </div>
                    <hr>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="small fw-bold text-muted text-danger">Usuario (Login)</label><input type="text" name="nombre_usuario" id="u_nombre_usuario" class="form-control form-control-sm border-danger" required></div>
                        <div class="col-4"><label class="small fw-bold text-muted text-danger">Contraseña</label><input type="text" name="contrasena" id="u_contrasena" class="form-control form-control-sm border-danger" placeholder="Dejar vacía para no cambiar"></div>
                        <div class="col-4"><label class="small fw-bold text-muted text-primary">Nivel Nominal (Rol Visual)</label>
                            <select name="rol" id="u_rol" class="form-select form-select-sm border-primary fw-bold" required>
                                <option value="ventas">Asesor de Ventas</option>
                                <option value="jefe_ventas">Jefe de Ventas</option>
                                <option value="logistica">Logística y Producción</option>
                                <option value="admin">Administrador</option>
                                <option value="licitaciones">Licitaciones (Visor)</option>
                                <option value="supervisor_licitaciones">Supervisor Licitaciones</option>
                                <option value="master">Master</option>
                                <option value="nomina">Nómina (RRHH)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 p-3 bg-white border rounded">
                        <label class="small fw-bold text-muted text-primary mb-2 d-block border-bottom pb-1"><i class="bi bi-shield-check"></i> Asignación de Permisos</label>
                        <div class="row g-2" style="font-size: 0.8rem;">
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="1" class="check-permiso"> [1] Ver Directorio de Clientes</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="2" class="check-permiso text-primary"> [2] Crear y Editar Clientes</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="3" class="check-permiso text-danger"> [3] Eliminar Clientes</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="4" class="check-permiso"> [4] Acceso al Cotizador (Catálogo)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="5" class="check-permiso text-primary"> [5] Generar Cotizaciones</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="26" class="check-permiso text-primary"> [26] Cotizador Especial (Licitación)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="6" class="check-permiso"> [6] Ver Cotizaciones (Solo Propias)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="7" class="check-permiso text-primary"> [7] Ver Cotizaciones (Todas - Gerencia)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="8" class="check-permiso text-warning"> [8] Editar Cotizaciones</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="9" class="check-permiso text-danger"> [9] Eliminar Cotizaciones</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="10" class="check-permiso"> [10] Ver Órdenes de Fábrica</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="11" class="check-permiso text-primary"> [11] Generar Órdenes de Fábrica</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="12" class="check-permiso text-danger"> [12] Eliminar Órdenes de Fábrica</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="13" class="check-permiso text-warning"> [13] Prealertas de Producción (Gerencia de Logística)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="14" class="check-permiso text-success"> [14] Gestión de Catálogo</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="15" class="check-permiso text-success"> [15] Gestión de Usuarios</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="16" class="check-permiso text-success"> [16] Auditoría TOTAL Y General</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="17" class="check-permiso"> [17] Licitaciones (Visor)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="18" class="check-permiso text-primary"> [18] Licitaciones (Administrar)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="19" class="check-permiso"> [19] Panel Estadísticas (Mi Desempeño)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="20" class="check-permiso text-primary"> [20] Panel Gerencial (Todas y Modificar Metas)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="21" class="check-permiso text-success"> [21] Gestión Almacén y MP</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="27" class="check-permiso text-success"> [27] Crear Recetas de Prendas</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="28" class="check-permiso text-primary"> [28] Especificar Tallas y Telas (Oculto en Cotizador)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="29" class="check-permiso text-primary"> [29] Enviar a Logística / Producción</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="22" class="check-permiso text-success"> [22] WhatsApp / Alertas de Re-uniforme</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="23" class="check-permiso text-info"> [23] Auditoría: Ver Dpto. Ventas</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="24" class="check-permiso text-info"> [24] Auditoría: Ver Licitaciones</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="25" class="check-permiso text-info"> [25] Auditoría: Ver Logística/Admin</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="30" class="check-permiso text-warning"> [30] Editar Cotizaciones Especiales</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="31" class="check-permiso text-danger"> [31] Otorgar Descuento (% / Especial)</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="32" class="check-permiso text-danger"> [32] Exonerar / Modificar I.V.A</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="33" class="check-permiso text-danger"> [33] Descuento Logística de Envío</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="34" class="check-permiso text-danger"> [34] Asignar Precio de Flete</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="35" class="check-permiso text-warning"> [35] Pagar / Gestionar Comisiones <i class="bi bi-coin text-warning"></i></label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="36" class="check-permiso text-info"> [36] Gestión de Envíos y Avances <i class="bi bi-truck text-info"></i></label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="37" class="check-permiso text-warning"> [37] Solicitar Insumo Textil (Operario) 🧵</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="38" class="check-permiso text-danger"> [38] Bandeja Insumos / Compras 🛒</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="39" class="check-permiso" style="color: #a855f7;"> [39] Módulo Nómina (Acceso) 💵</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="40" class="check-permiso" style="color: #a855f7;"> [40] Nómina: Registro 📝</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="41" class="check-permiso" style="color: #a855f7;"> [41] Nómina: Pagos 💰</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="42" class="check-permiso" style="color: #a855f7;"> [42] Nómina: Prestaciones 🏆</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="43" class="check-permiso text-danger"> [43] Revertir Orden a Ventas</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="44" class="check-permiso text-success"> [44] Envío Directo (Sin Orden) 📦</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="45" class="check-permiso text-info"> [45] Módulo Administración 🏢</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="46" class="check-permiso text-success"> [46] Módulo Inv. Prendas 👕</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="47" class="check-permiso" style="color: #f59e0b;"> [47] Gestión de Tareas ✅</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="48" class="check-permiso text-danger"> [48] Semáforo de Envíos (Prioridad) 🛑</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="49" class="check-permiso text-danger fw-bold"> [49] Omitir Restricción 60% (Pago Inicial) 🔓</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="51" class="check-permiso text-warning fw-bold"> [51] Editar Cotiz. Bloqueadas (Sin Límite) 🔓</label></div>
                            
                            <!-- AQUI ESTÁN LOS VECTORES 52 Y 53 -->
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="52" class="check-permiso text-info fw-bold"> [52] Módulo Muestras Físicas 🎁</label></div>
                            <div class="col-md-6"><label><input type="checkbox" name="permisos[]" value="53" class="check-permiso text-success fw-bold"> [53] Alterar Órdenes de Fábrica ⚙️</label></div>
                        </div>
                        <div class="mt-3 border-top pt-2">
                            <label class="d-flex align-items-center fw-bold text-dark"><input type="checkbox" name="tiene_meta" id="u_tiene_meta" value="1" class="me-2" style="transform: scale(1.2);"> ¿Trabaja con Metas de Ventas?</label>
                        </div>
                        
                        <div class="mt-2 border-top pt-2" style="background-color: #ffe5e5; padding: 10px; border-radius: 6px;" id="box_bloqueo_master">
                            <label class="d-flex align-items-center fw-bold text-danger"><input type="checkbox" name="permisos[]" value="BLOCKED" id="u_bloqueado" class="me-2 check-permiso" style="transform: scale(1.4);"> 🚫 BLOQUEAR ACCESO AL SISTEMA A ESTE USUARIO</label>
                        </div>
                        
                    </div>
                    
                    <div class="text-end border-top pt-3"><button type="button" class="btn btn-sm btn-outline-secondary me-2 fw-bold" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-sm text-white fw-bold shadow-sm" style="background-color: #FF7518;" id="btnGuardarUser">Guardar Datos</button></div>
                </form>
            </div>
        </div>
    </div>
</div>