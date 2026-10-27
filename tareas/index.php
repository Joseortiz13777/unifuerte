<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../portal_ventas/login/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Operaciones y Tareas - Unifuerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <style>
        body {
            background: linear-gradient(135deg, #0b1a2e 0%, #1e1b4b 100%);
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        .kanban-column {
            min-height: 75vh;
            padding: 15px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255,255,255,0.02);
        }

        .kanban-title {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.9rem;
            font-weight: 800;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .task-card {
            background: rgba(11, 76, 140, 0.4); /* Azul Unifuerte Transparente */
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid transparent;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .task-card:active { cursor: grabbing; transform: scale(0.98); }

        /* NEÓN POR PRIORIDAD */
        .priority-alta { border-left-color: #ef4444; box-shadow: 0 0 12px rgba(239, 68, 68, 0.3); }
        .priority-media { border-left-color: #f59e0b; box-shadow: 0 0 12px rgba(245, 158, 11, 0.3); }
        .priority-baja { border-left-color: #0dcaf0; box-shadow: 0 0 12px rgba(13, 202, 240, 0.3); }

        .task-card:hover { transform: translateY(-3px); background: rgba(11, 76, 140, 0.6); }

        .emp-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(45deg, #0B4C8C, #FF7518); /* Colores Unifuerte */
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 0.8rem; color: white;
            box-shadow: 0 0 8px rgba(255, 117, 24, 0.5);
        }

        .btn-neon {
            background: linear-gradient(45deg, #0B4C8C, #0dcaf0);
            border: none;
            color: white;
            box-shadow: 0 0 15px rgba(11, 76, 140, 0.5);
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }
        .btn-neon:hover { box-shadow: 0 0 25px rgba(13, 202, 240, 0.7); color: white; transform: scale(1.05);}

        /* Elementos UI */
        .modal-content { background: #0f172a; color: white; border: 1px solid rgba(255,255,255,0.2); border-radius: 15px;}
        .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .modal-footer { border-top: 1px solid rgba(255,255,255,0.1); }
        .form-control, .form-select { background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.15) !important; color: white !important; font-weight: 500;}
        .form-control:focus, .form-select:focus { box-shadow: 0 0 10px rgba(13, 202, 240, 0.5); border-color: #0dcaf0 !important; }
        
        .unifuerte-logo {
            height: 35px;
            width: 35px;
            object-fit: contain;
            border-radius: 6px;
            background-color: #fff;
            padding: 3px;
            box-shadow: 0 0 10px rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 glass-panel p-3 gap-3">
        <div class="d-flex align-items-center">
            <a href="../portal_ventas/index.php" class="btn btn-outline-light btn-sm me-3"><i class="bi bi-arrow-left"></i> Volver</a>
            <img src="../img/camisa.jpg" class="unifuerte-logo me-3" alt="Logo Unifuerte">
            <h3 class="m-0 fw-bold d-none d-md-block" style="text-shadow: 0 0 10px rgba(255,255,255,0.3);">Unifuerte | Asignación de Tareas</h3>
        </div>
        
        <!-- FILTRO DE DEPARTAMENTOS -->
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold text-info small d-none d-sm-block">Filtrar Área:</label>
            <select id="filtroDepartamento" class="form-select form-select-sm" style="width: 180px;">
                <option value="Todos">🌐 Todas las Áreas</option>
                <option value="Corte">✂️ Corte</option>
                <option value="Confección">🪡 Confección</option>
                <option value="Bordado">🧵 Bordado</option>
                <option value="Logística y Envíos">📦 Logística y Envíos</option>
                <option value="General">🛠️ General</option>
            </select>
            
            <button class="btn btn-neon px-3 ms-2" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                <i class="bi bi-plus-lg"></i> Asignar Tarea
            </button>
        </div>
    </div>

    <!-- TABLERO KANBAN -->
    <div class="row g-4">
        <!-- COLUMNA: PENDIENTES -->
        <div class="col-md-4">
            <div class="glass-panel kanban-column">
                <div class="kanban-title text-danger"><i class="bi bi-exclamation-circle-fill"></i> Cola de Espera</div>
                <div id="col_0" class="task-list h-100 pb-5" data-estado="0">
                    <!-- Tareas renderizadas por JS -->
                </div>
            </div>
        </div>

        <!-- COLUMNA: EN PROCESO -->
        <div class="col-md-4">
            <div class="glass-panel kanban-column">
                <div class="kanban-title text-warning"><i class="bi bi-gear-wide-connected spin"></i> En Ejecución</div>
                <div id="col_1" class="task-list h-100 pb-5" data-estado="1">
                </div>
            </div>
        </div>

        <!-- COLUMNA: COMPLETADAS -->
        <div class="col-md-4">
            <div class="glass-panel kanban-column">
                <div class="kanban-title text-success"><i class="bi bi-check-circle-fill"></i> Misiones Cumplidas</div>
                <div id="col_2" class="task-list h-100 pb-5" data-estado="2">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREAR TAREA -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-crosshair"></i> Nueva Asignación de Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaTarea">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small text-info fw-bold mb-1">Descripción de la Tarea / Proceso</label>
                        <textarea name="tarea" class="form-control" rows="3" required placeholder="Ej: Empaquetar 50 camisas cliente PDVSA..."></textarea>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small text-info fw-bold mb-1">Operador / Empleado</label>
                            <select name="id_empleado" id="select_empleados" class="form-select" required>
                                <option value="">Cargando Activos...</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small text-info fw-bold mb-1">Departamento</label>
                            <select name="departamento" class="form-select" required>
                                <option value="Corte">Corte</option>
                                <option value="Confección">Confección</option>
                                <option value="Bordado">Bordado</option>
                                <option value="Logística y Envíos">Logística y Envíos</option>
                                <option value="General">General / Mantenimiento</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small text-info fw-bold mb-1">Nivel de Prioridad</label>
                        <select name="prioridad" class="form-select fw-bold" required>
                            <option value="Alta" class="text-danger">🔴 ALTA (Urgente)</option>
                            <option value="Media" class="text-warning" selected>🟡 MEDIA (Normal)</option>
                            <option value="Baja" class="text-info">🔵 BAJA (Cuando haya tiempo)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Abortar</button>
                    <button type="submit" class="btn btn-neon">Autorizar Despliegue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js?v=<?= time(); ?>"></script>
</body>
</html>