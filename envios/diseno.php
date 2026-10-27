<?php
session_start();
require_once '../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login/');
    exit;
}

$id_usuario = intval($_SESSION['usuario_id']);
$q_usuario = mysqli_query($conexion, "SELECT nombre, apellido, rol, permisos FROM usuario WHERE ID = $id_usuario");
$usuario = $q_usuario ? mysqli_fetch_assoc($q_usuario) : null;
$rol_actual = strtolower(trim($usuario['rol'] ?? ''));
$permisos = array_map('trim', explode(',', $usuario['permisos'] ?? ''));
$es_jefe_diseno = $rol_actual === 'jefe_diseno' || $rol_actual === 'master' || in_array('55', $permisos, true);
$es_operador = in_array($rol_actual, ['disenador', 'cortador', 'corte', 'bordador', 'bordado'], true) || in_array('56', $permisos, true);
$embebido = isset($_GET['embebido']) && $_GET['embebido'] === '1';
$vista_archivador = isset($_GET['vista']) && $_GET['vista'] === 'archivador';

if (!$es_jefe_diseno && !$es_operador) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>Acceso denegado</h2><p>No tienes permisos para el módulo de Diseño, Corte y Bordado.</p><a href="../portal_ventas/index.php">Volver al portal</a></div>');
}
if (!$embebido) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Diseño, Corte y Bordado - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #16283b; font-family: 'Segoe UI', sans-serif; }
        .topbar { background: #0a2540; }
        .card-order { border: 1px solid #e4e4eb; border-radius: 12px; }
        .order-title { color: #0B4C8C; }
        .priority-input { width: 82px; }
        .design-heading { color: #0B4C8C; }
        .btn-primary { background-color: #0B4C8C; border-color: #0B4C8C; }
        .btn-primary:hover, .btn-primary:focus { background-color: #083766; border-color: #083766; }
        .btn-outline-primary { color: #0B4C8C; border-color: #0B4C8C; }
        .btn-outline-primary:hover { color: #fff; background-color: #0B4C8C; border-color: #0B4C8C; }
        .badge-area-diseno { background-color: #0B4C8C; color: #fff; }
        .badge-area-corte { background-color: #dc3545; color: #fff; }
        .badge-area-bordado { background-color: #FF7518; color: #fff; }
        .box-tarea-asignada {
            background-color: #fff8e6;
            border-left: 4px solid #FF7518;
            border-radius: 6px;
            padding: 10px 12px;
        }
        <?php if ($embebido): ?>
        body { overflow-x: hidden; }
        main { padding-top: 20px !important; }
        <?php endif; ?>
    </style>

    <!-- Sugerencias opcionales para el campo libre de fases por escrito -->
    <datalist id="sugerencias_fases_area">
        <option value="Boceto">
        <option value="Diseño preliminar">
        <option value="En revisión">
        <option value="Ajustes">
        <option value="Arte final">
        <option value="Tendido y trazo de tela">
        <option value="Corte de piezas">
        <option value="Separación por tallas">
        <option value="Corte finalizado">
        <option value="Ponchado / Matriz de bordado">
        <option value="Prueba de bordado">
        <option value="Bordado en producción">
        <option value="Limpieza y auditoría de bordado">
    </datalist>
</head>
<body>
    <main class="container-fluid px-3 px-md-4 py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1 design-heading">
                    <?= $vista_archivador
                        ? 'Archivador de Órdenes Finalizadas (Diseño, Corte y Bordado)'
                        : ($es_jefe_diseno ? 'Gestión de Áreas: Diseño, Corte y Bordado' : 'Mis Órdenes de Fábrica y Tareas Asignadas') ?>
                </h1>
                <p class="text-muted mb-0">
                    <?= $vista_archivador
                        ? 'Consulta las órdenes finalizadas por cada área. Si reabres una, volverá a las órdenes activas.'
                        : ($es_jefe_diseno
                            ? 'Emite una misma Orden de Fábrica a Diseño, Corte y/o Bordado, asigna tareas a cada encargado y supervisa sus fases.'
                            : 'Revisa la Orden de Fábrica, las tareas que te fueron asignadas y escribe el avance de tus fases.') ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($es_jefe_diseno): ?>
                <a class="btn btn-outline-secondary fw-bold" href="../portal_ventas/index.php" target="_top"><i class="bi bi-arrow-left-circle me-1"></i>Volver a Ventas</a>
                <?php endif; ?>
                <button class="btn btn-outline-primary fw-bold" id="btnActualizarDiseno"><i class="bi bi-arrow-clockwise me-1"></i> Actualizar</button>
            </div>
        </div>

        <?php if ($es_jefe_diseno): ?>
        <?php if (!$vista_archivador): ?>
        <section class="card card-order shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i>Emitir Orden de Fábrica a Diseño, Corte y/o Bordado
            </div>
            <div class="card-body">
                <form id="formAsignarDiseno" class="row g-3">
                    <div class="col-lg-5">
                        <label for="buscarOrdenDiseno" class="form-label small fw-bold">1. Buscar Orden de Fábrica</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" id="buscarOrdenDiseno" class="form-control" placeholder="Buscar por número de orden (OF-...) o cliente..." autocomplete="off">
                            <span class="input-group-text d-none" id="cargandoOrdenesDiseno" aria-label="Buscando órdenes">
                                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            </span>
                        </div>
                        <select id="disenoOrden" class="form-select" required size="5" aria-label="Resultados de órdenes disponibles">
                            <option value="">Cargando órdenes...</option>
                        </select>
                        <div class="form-text" id="resultadoBusquedaOrdenDiseno">Puedes emitir una misma orden a Diseño, a Corte y a Bordado al mismo tiempo o por separado.</div>
                    </div>

                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="disenoArea" class="form-label small fw-bold">2. Área Destino *</label>
                                <select id="disenoArea" class="form-select border-primary fw-bold" required>
                                    <option value="Diseño">🎨 Diseño</option>
                                    <option value="Corte">✂️ Corte</option>
                                    <option value="Bordado">🧵 Bordado</option>
                                    <option value="TODAS">🚀 Emitir a las 3 Áreas (Diseño + Corte + Bordado)</option>
                                </select>
                            </div>

                            <div class="col-md-5" id="boxSelectorUnicoOperador">
                                <label for="disenoUsuario" class="form-label small fw-bold">3. Encargado / Operador *</label>
                                <select id="disenoUsuario" class="form-select border-primary fw-bold">
                                    <option value="">Cargando personal...</option>
                                </select>
                            </div>

                            <!-- Selectores múltiples cuando elige emitir a las 3 áreas de una sola vez -->
                            <div class="col-md-5 d-none" id="boxSelectoresMultiplesAreas">
                                <label class="form-label small fw-bold text-primary mb-1">3. Encargado por cada Área</label>
                                <div class="mb-1">
                                    <small class="fw-bold text-muted">🎨 Diseño:</small>
                                    <select id="disenoUsuarioMulti_Diseno" class="form-select form-select-sm"></select>
                                </div>
                                <div class="mb-1">
                                    <small class="fw-bold text-muted">✂️ Corte:</small>
                                    <select id="disenoUsuarioMulti_Corte" class="form-select form-select-sm"></select>
                                </div>
                                <div>
                                    <small class="fw-bold text-muted">🧵 Bordado:</small>
                                    <select id="disenoUsuarioMulti_Bordado" class="form-select form-select-sm"></select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="disenoPrioridad" class="form-label small fw-bold">4. Prioridad (1 = urgente)</label>
                                <input type="number" id="disenoPrioridad" class="form-control fw-bold" min="0" value="1" required>
                            </div>

                            <div class="col-12">
                                <label for="disenoTareasAsignadas" class="form-label small fw-bold text-danger">
                                    <i class="bi bi-card-checklist me-1"></i>5. Tareas e Instrucciones Asignadas para la Orden (Opcional)
                                </label>
                                <textarea id="disenoTareasAsignadas" class="form-control border-primary" rows="2" placeholder="Escribe aquí las tareas o indicaciones específicas para Diseño, el Cortador o Bordado (ej: Cortar 20 camisas Columbia azul marino, bordar logo en bolsillo izquierdo)..."></textarea>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-send-check-fill me-1"></i>Asignar Orden y Tareas
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <!-- FILTROS POR ÁREA PARA EL JEFE DE DISEÑO -->
        <ul class="nav nav-pills mb-3 gap-2" id="tabsFiltroAreaJefe">
            <li class="nav-item">
                <button type="button" class="nav-link active fw-bold" data-area="TODAS">Todas las Áreas</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link fw-bold" data-area="Diseño">🎨 Diseño</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link fw-bold" data-area="Corte">✂️ Corte</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link fw-bold" data-area="Bordado">🧵 Bordado</button>
            </li>
        </ul>

        <section class="card card-order shadow-sm">
            <div class="card-header bg-white fw-bold py-3">
                <i class="bi bi-list-check text-primary me-2"></i><?= $vista_archivador ? 'Órdenes finalizadas por Área' : 'Órdenes emitidas por Área, Tareas y Avance' ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Prioridad</th>
                                <th>Orden</th>
                                <th>Área</th>
                                <th>Cliente</th>
                                <th>Encargado</th>
                                <th>Tareas Asignadas</th>
                                <th>Avance / Fases Escritas</th>
                                <th>Fases Envíos</th>
                                <th>Orden de Fábrica</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tablaAsignacionesDiseno">
                            <tr><td colspan="10" class="text-center py-4 text-muted">Cargando asignaciones...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php else: ?>
        <div id="listaMisOrdenesDiseno" class="row g-3">
            <div class="col-12 text-center py-5 text-muted"><span class="spinner-border text-primary"></span></div>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.DISENO_ES_JEFE = <?= $es_jefe_diseno ? 'true' : 'false' ?>;
        window.DISENO_VISTA_ARCHIVADOR = <?= $vista_archivador ? 'true' : 'false' ?>;
        window.ROL_OPERADOR_ACTUAL = <?= json_encode($rol_actual) ?>;
    </script>
    <script src="js/diseno.js?v=<?= time() ?>"></script>
</body>
</html>