<?php
// Archivo: Unifuerte/entidades/Provedor/index.php
require_once '../../conexion.php';
$stmt = $pdo->query("SELECT * FROM provedor ORDER BY ID DESC");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores - Unifuerte</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-bold ph-factory"></i></div>
            Unifuerte
        </div>
        <nav class="nav-menu" style="overflow-y: auto; padding-right: 5px;">
            <a href="../Cliente/" class="nav-item"><i class="ph ph-users"></i> Clientes</a>
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
            <a href="../Provedor/" class="nav-item active"><i class="ph ph-truck"></i> Proveedores</a>
            <a href="../Receta_Prenda/" class="nav-item"><i class="ph ph-notepad"></i> Receta Prenda</a>
            <a href="../Tarea/" class="nav-item"><i class="ph ph-clipboard-text"></i> Tareas</a>
            <a href="../Usuario/" class="nav-item"><i class="ph ph-user-circle"></i> Usuarios</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="header">
            <h1>Directorio de Proveedores</h1>
            <div class="search-bar"><i class="ph ph-magnifying-glass"></i><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar proveedor..."></div>
        </header>

        <div class="card">
            <div class="card-header">
                <h3>Total: <?= count($proveedores) ?> proveedores</h3>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-report" onclick="window.open('reporte.php', '_blank')"><i class="ph-bold ph-printer"></i> Imprimir Reporte</button>
                    <button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Añadir Proveedor</button>
                </div>
            </div>
            <table id="tablaDatos">
                <thead>
                    <tr>
                        <th>ID</th><th>Empresa</th><th>RIF/CI</th><th>Contacto</th><th>Teléfono</th><th>Ubicación</th><th>Flete Ext.($)</th><th>Descripción</th><th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($proveedores as $p): ?>
                    <tr id="row-<?= $p['ID'] ?>">
                        <td style="color: var(--text-muted); font-weight: 500;">#<?= $p['ID'] ?></td>
                        <td data-campo="nombre"><?= htmlspecialchars($p['nombre'] ?? '') ?></td>
                        <td data-campo="RIF_cedula"><?= htmlspecialchars($p['RIF_cedula'] ?? '') ?></td>
                        <td data-campo="contacto"><?= htmlspecialchars($p['contacto'] ?? '') ?></td>
                        <td data-campo="tlf"><?= htmlspecialchars($p['tlf'] ?? '') ?></td>
                        <td data-campo="ubicacion"><?= htmlspecialchars($p['ubicacion'] ?? '') ?></td>
                        <td data-campo="flete_internacional"><?= htmlspecialchars($p['flete_internacional'] ?? '0.00') ?></td>
                        <td data-campo="descripcion"><?= htmlspecialchars($p['descripcion'] ?? '') ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="action-btn" onclick="editarFila('<?= $p['ID'] ?>')" id="btn-edit-<?= $p['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button>
                            <button class="action-btn text-red" onclick="eliminarFila('<?= $p['ID'] ?>')" id="btn-delete-<?= $p['ID'] ?>"><i class="ph-fill ph-trash"></i></button>
                            <button class="action-btn text-green" onclick="guardarFila('<?= $p['ID'] ?>')" id="btn-save-<?= $p['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button>
                            <button class="action-btn text-red" onclick="cancelarEdicion('<?= $p['ID'] ?>')" id="btn-cancel-<?= $p['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        let datosOriginales = {};
        const campos = ['nombre', 'RIF_cedula', 'contacto', 'tlf', 'ubicacion', 'flete_internacional', 'descripcion'];

        function editarFila(id) {
            datosOriginales[id] = {};
            document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td => {
                let c = td.getAttribute('data-campo');
                let val = td.innerText;
                datosOriginales[id][c] = val;
                let tipo = (c === 'flete_internacional') ? 'number" step="0.01' : 'text';
                td.innerHTML = `<input type="${tipo}" class="form-control" id="input-${id}-${c}" value="${val}">`;
            });
            document.getElementById(`btn-edit-${id}`).style.display = 'none'; document.getElementById(`btn-delete-${id}`).style.display = 'none';
            document.getElementById(`btn-save-${id}`).style.display = 'inline-block'; document.getElementById(`btn-cancel-${id}`).style.display = 'inline-block';
        }

        function cancelarEdicion(id) {
            if(id === 'nuevo') return document.getElementById('row-nuevo').remove();
            document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td => td.innerHTML = datosOriginales[id][td.getAttribute('data-campo')]);
            document.getElementById(`btn-edit-${id}`).style.display = 'inline-block'; document.getElementById(`btn-delete-${id}`).style.display = 'inline-block';
            document.getElementById(`btn-save-${id}`).style.display = 'none'; document.getElementById(`btn-cancel-${id}`).style.display = 'none';
        }

        function agregarFilaVacia() {
            if(document.getElementById('row-nuevo')) return;
            let tr = document.createElement('tr'); tr.id = 'row-nuevo'; tr.style.backgroundColor = '#f0fdf4'; 
            tr.innerHTML = `
                <td style="color:var(--color-green); font-weight:bold;">Nuevo</td>
                ${campos.map(c => `<td data-campo="${c}"><input type="${c==='flete_internacional'?'number':'text'}" step="0.01" class="form-control" id="input-nuevo-${c}" placeholder="${c.replace('_',' ')}"></td>`).join('')}
                <td style="text-align: center; white-space: nowrap;">
                    <button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button>
                    <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button>
                </td>
            `;
            document.getElementById('cuerpoTabla').prepend(tr);
        }

        function guardarFila(id) {
            let fd = new FormData(); fd.append('accion', id === 'nuevo' ? 'crear' : 'editar');
            if (id !== 'nuevo') fd.append('id', id);
            campos.forEach(c => fd.append(c, document.getElementById(`input-${id}-${c}`).value));

            fetch('crud.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if(d.status === 'success') { Swal.fire({ icon: 'success', title: 'Guardado', timer: 1500, showConfirmButton: false }).then(() => location.reload()); }
            });
        }

        function eliminarFila(id) {
            Swal.fire({ title: '¿Eliminar proveedor?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E11D48', confirmButtonText: 'Borrar' }).then((r) => {
                if (r.isConfirmed) {
                    let fd = new FormData(); fd.append('accion', 'eliminar'); fd.append('id', id);
                    fetch('crud.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if(d.status === 'success') { document.getElementById(`row-${id}`).remove(); } });
                }
            });
        }

        function buscarTabla() { let i = document.getElementById("buscador").value.toLowerCase(); document.querySelectorAll("#tablaDatos tbody tr").forEach(tr => { if(tr.id !== 'row-nuevo') tr.style.display = tr.innerText.toLowerCase().includes(i) ? "" : "none"; }); }
    </script>
</body>
</html>