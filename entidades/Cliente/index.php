<?php
// Archivo: Unifuerte/entidades/Cliente/index.php
require_once '../../conexion.php';

// Consultar clientes
$stmt = $pdo->query("SELECT * FROM cliente ORDER BY ID DESC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalClientes = count($clientes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Unifuerte</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <style>
        /* TUS VARIABLES ORIGINALES */
        :root {
            --sidebar-bg: #13131A; --sidebar-hover: #1C1C24; --main-bg: #F4F5F8; --card-bg: #FFFFFF;
            --text-dark: #111118; --text-muted: #8F90A6; --text-white: #FFFFFF; --border-color: #E4E4EB;
            --color-green: #059669; --bg-green: #D1FAE5; --color-red: #E11D48; --radius-sm: 8px;
            --radius-md: 12px; --radius-lg: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--main-bg); color: var(--text-dark); overflow: hidden; }

        /* SIDEBAR */
        .sidebar { width: 260px; background-color: var(--sidebar-bg); color: var(--text-muted); display: flex; flex-direction: column; padding: 24px 20px; flex-shrink: 0; }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.2rem; font-weight: 700; color: var(--text-white); margin-bottom: 40px; padding: 0 10px; }
        .logo-icon { background: var(--text-white); color: var(--sidebar-bg); padding: 4px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 4px; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500; font-size: 0.9rem; transition: 0.2s; color: var(--text-muted); text-decoration: none; }
        .nav-item:hover, .nav-item.active { background-color: var(--sidebar-hover); color: var(--text-white); }
        .nav-item i { font-size: 1.25rem; }

        /* CONTENIDO PRINCIPAL */
        .main-content { flex: 1; padding: 32px 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; font-weight: 600; }
        .search-bar { display: flex; align-items: center; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; padding: 8px 16px; width: 280px; gap: 8px; }
        .search-bar input { border: none; outline: none; width: 100%; font-size: 0.9rem; }
        
        .header-actions { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border-radius: 20px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s;}
        .btn-primary { background: var(--sidebar-bg); color: white; }
        .btn-primary:hover { opacity: 0.8; }
        .btn-report { background: var(--bg-green); color: var(--color-green); }

        /* TABLA DE DATOS */
        .card { background: var(--card-bg); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 24px; overflow-x: auto; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 0.85rem; border-bottom: 1px solid var(--border-color); }
        td { padding: 12px; font-size: 0.85rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        
        /* ESTILOS DE EDICIÓN EN LÍNEA */
        .form-control { width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; outline: none; font-family: inherit; }
        .form-control:focus { border-color: var(--sidebar-bg); }
        .action-btn { background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-muted); padding: 4px; transition: 0.2s;}
        .action-btn:hover { color: var(--text-dark); }
        .action-btn.text-red:hover { color: var(--color-red); }
        .action-btn.text-green:hover { color: var(--color-green); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-bold ph-factory"></i></div>
            Unifuerte
        </div>
        <nav class="nav-menu" style="overflow-y: auto; padding-right: 5px;">
            <a href="../Cliente/" class="nav-item active"><i class="ph ph-users"></i> Clientes</a>
            <a href="../Compra/" class="nav-item"><i class="ph ph-shopping-bag"></i> Compras</a>
            <a href="../Detalle_Compra/" class="nav-item"><i class="ph ph-receipt"></i> Detalle Compras</a>
            <a href="../Detalle_Orden_Compra/" class="nav-item"><i class="ph ph-list-numbers"></i> Detalle Órdenes</a>
            <a href="../Envio/" class="nav-item"><i class="ph ph-airplane-tilt"></i> Envíos</a>
            <a href="../Historial_Actividad/" class="nav-item"><i class="ph ph-clock-counter-clockwise"></i> Historial</a>
            <a href="../Mat_Prov/" class="nav-item"><i class="ph ph-handshake"></i> Mat. Prov</a>
            <a href="../Materia_Prima/" class="nav-item"><i class="ph ph-t-shirt"></i> Materia Prima</a>
            <a href="../Medicion_Personal/" class="nav-item"><i class="ph ph-ruler"></i> Medición Personal</a>
            <a href="../Orden_Compra/" class="nav-item"><i class="ph ph-shopping-cart"></i> Órdenes Compra</a>
            <a href="../Patron/" class="nav-item"><i class="ph ph-scissors"></i> Patrones</a>
            <a href="../Patron_orden/" class="nav-item"><i class="ph ph-copy"></i> Patrón Orden</a>
            <a href="../Posicion/" class="nav-item"><i class="ph ph-briefcase"></i> Posiciones</a>
            <a href="../Provedor/" class="nav-item"><i class="ph ph-truck"></i> Proveedores</a>
            <a href="../Receta_Prenda/" class="nav-item"><i class="ph ph-notepad"></i> Receta Prenda</a>
            <a href="../Tarea/" class="nav-item"><i class="ph ph-clipboard-text"></i> Tareas</a>
            <a href="../Usuario/" class="nav-item"><i class="ph ph-user-circle"></i> Usuarios</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Directorio de Clientes</h1>
            <div class="header-actions">
                <div class="search-bar">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar cliente...">
                </div>
            </div>
        </header>

        <div class="card">
            <div class="card-header">
                <h3 style="font-size: 1.1rem;">Total: <?= $totalClientes ?> clientes</h3>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-report" onclick="window.open('reporte.php', '_blank')"><i class="ph-bold ph-printer"></i> Imprimir Reporte</button>
                    <button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Añadir Cliente</button>
                </div>
            </div>

            <table id="tablaDatos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre / Razón Social</th>
                        <th>Cédula/RIF</th>
                        <th>Contacto (Comprador)</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($clientes as $c): ?>
                    <tr id="row-<?= $c['ID'] ?>">
                        <td style="color: var(--text-muted); font-weight: 500;">#<?= $c['ID'] ?></td>
                        <td data-campo="nombre"><?= htmlspecialchars($c['nombre'] ?? '') ?></td>
                        <td data-campo="cedula_Rif"><?= htmlspecialchars($c['cedula_Rif'] ?? '') ?></td>
                        <td data-campo="comprador"><?= htmlspecialchars($c['comprador'] ?? '') ?></td>
                        <td data-campo="tlf"><?= htmlspecialchars($c['tlf'] ?? '') ?></td>
                        <td data-campo="email"><?= htmlspecialchars($c['email'] ?? '') ?></td>
                        <td data-campo="direccion"><?= htmlspecialchars($c['direccion'] ?? '') ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="action-btn" onclick="editarFila('<?= $c['ID'] ?>')" id="btn-edit-<?= $c['ID'] ?>" title="Editar"><i class="ph-fill ph-pencil-simple"></i></button>
                            <button class="action-btn text-red" onclick="eliminarFila('<?= $c['ID'] ?>')" id="btn-delete-<?= $c['ID'] ?>" title="Eliminar"><i class="ph-fill ph-trash"></i></button>
                            <button class="action-btn text-green" onclick="guardarFila('<?= $c['ID'] ?>')" id="btn-save-<?= $c['ID'] ?>" style="display:none;" title="Guardar"><i class="ph-bold ph-check"></i></button>
                            <button class="action-btn text-red" onclick="cancelarEdicion('<?= $c['ID'] ?>')" id="btn-cancel-<?= $c['ID'] ?>" style="display:none;" title="Cancelar"><i class="ph-bold ph-x"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        let datosOriginales = {};

        // 1. Convertir la fila en cuadros de texto (Inputs)
        function editarFila(id) {
            let celdas = document.querySelectorAll(`#row-${id} td[data-campo]`);
            datosOriginales[id] = {};
            
            celdas.forEach(td => {
                let campo = td.getAttribute('data-campo');
                let valor = td.innerText;
                datosOriginales[id][campo] = valor;
                td.innerHTML = `<input type="text" class="form-control" id="input-${id}-${campo}" value="${valor}">`;
            });
            
            document.getElementById(`btn-edit-${id}`).style.display = 'none';
            document.getElementById(`btn-delete-${id}`).style.display = 'none';
            document.getElementById(`btn-save-${id}`).style.display = 'inline-block';
            document.getElementById(`btn-cancel-${id}`).style.display = 'inline-block';
        }

        // 2. Cancelar edición y volver a modo texto
        function cancelarEdicion(id) {
            if(id === 'nuevo') {
                document.getElementById('row-nuevo').remove();
                return;
            }
            let celdas = document.querySelectorAll(`#row-${id} td[data-campo]`);
            celdas.forEach(td => {
                let campo = td.getAttribute('data-campo');
                td.innerHTML = datosOriginales[id][campo];
            });
            
            document.getElementById(`btn-edit-${id}`).style.display = 'inline-block';
            document.getElementById(`btn-delete-${id}`).style.display = 'inline-block';
            document.getElementById(`btn-save-${id}`).style.display = 'none';
            document.getElementById(`btn-cancel-${id}`).style.display = 'none';
        }

        // 3. Añadir fila nueva en blanco
        function agregarFilaVacia() {
            if(document.getElementById('row-nuevo')) return; // Evita añadir múltiples vacías
            let tbody = document.getElementById('cuerpoTabla');
            let tr = document.createElement('tr');
            tr.id = 'row-nuevo';
            tr.style.backgroundColor = '#f0fdf4'; 

            tr.innerHTML = `
                <td style="color: var(--color-green); font-weight:bold;">Nuevo</td>
                <td data-campo="nombre"><input type="text" class="form-control" id="input-nuevo-nombre" placeholder="Nombre"></td>
                <td data-campo="cedula_Rif"><input type="text" class="form-control" id="input-nuevo-cedula_Rif" placeholder="J-00000000"></td>
                <td data-campo="comprador"><input type="text" class="form-control" id="input-nuevo-comprador" placeholder="Contacto"></td>
                <td data-campo="tlf"><input type="text" class="form-control" id="input-nuevo-tlf" placeholder="Teléfono"></td>
                <td data-campo="email"><input type="email" class="form-control" id="input-nuevo-email" placeholder="Email"></td>
                <td data-campo="direccion"><input type="text" class="form-control" id="input-nuevo-direccion" placeholder="Dirección"></td>
                <td style="text-align: center; white-space: nowrap;">
                    <button class="action-btn text-green" onclick="guardarFila('nuevo')" title="Guardar"><i class="ph-bold ph-check"></i></button>
                    <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')" title="Cancelar"><i class="ph-bold ph-x"></i></button>
                </td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);
        }

        // 4. Guardar en Base de Datos vía AJAX (Sin recargar)
        function guardarFila(id) {
            let formData = new FormData();
            formData.append('accion', id === 'nuevo' ? 'crear' : 'editar');
            if (id !== 'nuevo') formData.append('id', id);

            ['nombre', 'cedula_Rif', 'comprador', 'tlf', 'email', 'direccion'].forEach(campo => {
                let valor = document.getElementById(`input-${id}-${campo}`).value;
                formData.append(campo, valor);
            });

            fetch('crud.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Guardado', text: 'Datos actualizados correctamente.', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload()); // Recarga suave para actualizar el ID en caso de ser nuevo
                }
            });
        }

        // 5. Eliminar Registro
        function eliminarFila(id) {
            Swal.fire({
                title: '¿Eliminar cliente?', text: "Esta acción no se puede deshacer",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#E11D48',
                confirmButtonText: 'Sí, borrar', cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = new FormData();
                    formData.append('accion', 'eliminar');
                    formData.append('id', id);

                    fetch('crud.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            document.getElementById(`row-${id}`).remove();
                            Swal.fire('Eliminado', 'El cliente ha sido borrado.', 'success');
                        }
                    });
                }
            });
        }

        // 6. Buscador en tiempo real
        function buscarTabla() {
            let input = document.getElementById("buscador").value.toLowerCase();
            let trs = document.querySelectorAll("#tablaDatos tbody tr");
            trs.forEach(tr => {
                if(tr.id === 'row-nuevo') return;
                let texto = tr.innerText.toLowerCase();
                tr.style.display = texto.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>