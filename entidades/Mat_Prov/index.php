<?php 
require_once '../../conexion.php'; 
$sql = "SELECT mp.*, p.nombre as prov_nombre, m.nombre as mat_nombre FROM mat_prov mp LEFT JOIN provedor p ON mp.id_provedor = p.ID LEFT JOIN materia_prima m ON mp.id_materia = m.ID ORDER BY mp.ID DESC"; 
$matprov = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
$proveedores = $pdo->query("SELECT ID, nombre, RIF_cedula FROM provedor ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$materias = $pdo->query("SELECT ID, nombre, color, codigo_color FROM materia_prima ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mat x Prov - Unifuerte</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(3px); }
        .modal-box { background: var(--card-bg); width: 680px; max-width: 95%; max-height: 85vh; border-radius: var(--radius-lg); padding: 24px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .modal-close { cursor: pointer; color: var(--text-muted); font-size: 1.5rem; background: transparent; border: none;} .modal-close:hover { color: var(--color-red); }
        .modal-tools { display: flex; gap: 10px; align-items: center; }
        .modal-search { flex: 1; padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; outline: none; } .modal-search:focus { border-color: var(--color-green); }
        .btn-crear-nuevo { background: var(--bg-green); color: var(--color-green); border: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; text-decoration: none;} .btn-crear-nuevo:hover { color: white; background: var(--color-green); }
        .btn-refresh { background: #e0e7ff; color: #4f46e5; border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 1.1rem; } .btn-refresh:hover { background: #4f46e5; color: white; }
        .modal-table-container { overflow-y: auto; max-height: 400px; border: 1px solid var(--border-color); border-radius: 8px;} .modal-table { width: 100%; border-collapse: collapse; } .modal-table th { background: #f4f5f8; padding: 10px; text-align: left; position: sticky; top: 0; z-index: 2;} .modal-table td { padding: 10px; border-bottom: 1px solid #eee; } .modal-table tr:hover { background: #f0fdf4; }
        .btn-elegir { background: var(--sidebar-bg); color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer;}
        .selector-box { border: 1px solid var(--border-color); background: #fafafa; padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; min-width: 180px; gap: 10px;}
    </style>
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
            <a href="../Mat_Prov/" class="nav-item active"><i class="ph ph-handshake"></i> Mat. Prov</a>
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
        <header class="header"><h1>Relación Material - Proveedor</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar material o prov..."></div></header>
        <div class="card">
            <div class="card-header"><h3>Total: <?= count($matprov) ?> vínculos</h3><div style="display:flex;gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php','_blank')"><i class="ph-bold ph-printer"></i> Imprimir</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Añadir Vínculo</button></div></div>
            <table id="tablaDatos">
                <thead><tr><th>ID</th><th>Proveedor</th><th>Materia Prima</th><th>Precio ($)</th><th>Calidad</th><th>Present.</th><th>Fecha Act.</th><th style="text-align:center;">Acciones</th></tr></thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($matprov as $mp): ?>
                    <tr id="row-<?= $mp['ID'] ?>"><td>#<?= $mp['ID'] ?></td>
                        <td data-campo="id_provedor" data-idval="<?= $mp['id_provedor'] ?>" data-text="<?= htmlspecialchars($mp['prov_nombre'] ?? 'N/A') ?>" style="font-weight:bold; color:var(--color-green);"><?= htmlspecialchars($mp['prov_nombre'] ?? 'N/A') ?></td>
                        <td data-campo="id_materia" data-idval="<?= $mp['id_materia'] ?>" data-text="<?= htmlspecialchars($mp['mat_nombre'] ?? 'N/A') ?>" style="font-weight:bold; color:var(--text-dark);"><?= htmlspecialchars($mp['mat_nombre'] ?? 'N/A') ?></td>
                        <td data-campo="precio_actual">$<?= htmlspecialchars($mp['precio_actual'] ?? '0.00') ?></td><td data-campo="calidad"><?= htmlspecialchars($mp['calidad'] ?? '') ?></td><td data-campo="presentacion"><?= htmlspecialchars($mp['presentacion'] ?? '') ?></td><td data-campo="fecha_actualizacion"><?= htmlspecialchars($mp['fecha_actualizacion'] ?? '') ?></td>
                        <td style="text-align:center;white-space:nowrap;"><button class="action-btn" onclick="editarFila('<?= $mp['ID'] ?>')" id="btn-edit-<?= $mp['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $mp['ID'] ?>')" id="btn-delete-<?= $mp['ID'] ?>"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $mp['ID'] ?>')" id="btn-save-<?= $mp['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $mp['ID'] ?>')" id="btn-cancel-<?= $mp['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="modalProv">
        <div class="modal-box">
            <div class="modal-header"><span>Seleccionar Proveedor</span><button class="modal-close" onclick="cerrarModal('modalProv')"><i class="ph-bold ph-x"></i></button></div>
            <div class="modal-tools">
                <input type="text" class="modal-search" id="searchProv" onkeyup="filtrarLista('searchProv', 'tableProv')" placeholder="🔍 Buscar por Empresa o RIF...">
                <button class="btn-refresh" onclick="location.reload()" title="Recargar Lista"><i class="ph-bold ph-arrows-clockwise"></i></button>
                <a href="../Provedor/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nuevo Prov.</a>
            </div>
            <div class="modal-table-container">
                <table class="modal-table" id="tableProv"><thead><tr><th>ID</th><th>Empresa</th><th>RIF</th><th>Acción</th></tr></thead>
                    <tbody><?php foreach($proveedores as $p): ?><tr><td><?= $p['ID'] ?></td><td style="font-weight:bold;"><?= htmlspecialchars($p['nombre']) ?></td><td><?= htmlspecialchars($p['RIF_cedula']) ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('id_provedor', '<?= $p['ID'] ?>', '<?= addslashes(htmlspecialchars($p['nombre'])) ?>')">Elegir</button></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalMat">
        <div class="modal-box">
            <div class="modal-header"><span>Seleccionar Materia Prima</span><button class="modal-close" onclick="cerrarModal('modalMat')"><i class="ph-bold ph-x"></i></button></div>
            <div class="modal-tools">
                <input type="text" class="modal-search" id="searchMat" onkeyup="filtrarLista('searchMat', 'tableMat')" placeholder="🔍 Buscar por Tela o Color...">
                <button class="btn-refresh" onclick="location.reload()" title="Recargar Lista"><i class="ph-bold ph-arrows-clockwise"></i></button>
                <a href="../Materia_Prima/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nueva Materia</a>
            </div>
            <div class="modal-table-container">
                <table class="modal-table" id="tableMat"><thead><tr><th>ID</th><th>Materia Prima</th><th>Color</th><th>Acción</th></tr></thead>
                    <tbody><?php foreach($materias as $m): ?><tr><td><?= $m['ID'] ?></td><td style="font-weight:bold;"><?= htmlspecialchars($m['nombre']) ?></td><td><?= htmlspecialchars($m['color']) ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('id_materia', '<?= $m['ID'] ?>', '<?= addslashes(htmlspecialchars($m['nombre'])) ?>')">Elegir</button></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let datosOrig={}; let filaActivaModal=null; const campos=['id_provedor','id_materia','precio_actual','calidad','presentacion','fecha_actualizacion'];
        function abrirModal(modalId, idFila) { filaActivaModal = idFila; document.getElementById(modalId).style.display='flex'; }
        function cerrarModal(modalId) { document.getElementById(modalId).style.display='none'; }
        function seleccionarItem(campoBd, idItem, nombreItem) { document.getElementById(`input-${filaActivaModal}-${campoBd}`).value = idItem; document.getElementById(`text-${filaActivaModal}-${campoBd}`).innerText = nombreItem; cerrarModal(campoBd === 'id_provedor' ? 'modalProv' : 'modalMat'); }
        function filtrarLista(inputId, tableId) { let filter = document.getElementById(inputId).value.toLowerCase(); let rows = document.getElementById(tableId).getElementsByTagName('tbody')[0].getElementsByTagName('tr'); for(let i=0; i<rows.length; i++) rows[i].style.display = rows[i].innerText.toLowerCase().includes(filter) ? "" : "none"; }

        function editarFila(id){
            datosOrig[id]={};
            document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{
                let c = td.getAttribute('data-campo'); datosOrig[id][c] = td.innerHTML;
                if(c === 'id_provedor' || c === 'id_materia') {
                    let idVal = td.getAttribute('data-idval'); let txtVal = td.getAttribute('data-text'); let modalTarget = c === 'id_provedor' ? 'modalProv' : 'modalMat';
                    td.innerHTML = `<div class="selector-box" onclick="abrirModal('${modalTarget}', '${id}')"><span id="text-${id}-${c}">${txtVal}</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-${id}-${c}" value="${idVal}">`;
                } else {
                    let val = td.innerText.replace('$','').trim(); let type = c==='fecha_actualizacion'?'date':(c==='precio_actual'?'number" step="0.01':'text');
                    td.innerHTML = `<input type="${type}" class="form-control" id="input-${id}-${c}" value="${val}">`;
                }
            });
            document.getElementById(`btn-edit-${id}`).style.display='none'; document.getElementById(`btn-delete-${id}`).style.display='none'; document.getElementById(`btn-save-${id}`).style.display='inline-block'; document.getElementById(`btn-cancel-${id}`).style.display='inline-block';
        }
        function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-delete-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
        function agregarFilaVacia(){if(document.getElementById('row-nuevo')) return; let tr=document.createElement('tr'); tr.id='row-nuevo'; tr.style.backgroundColor='#f0fdf4';
            tr.innerHTML=`<td>Nuevo</td>
                <td data-campo="id_provedor"><div class="selector-box" onclick="abrirModal('modalProv', 'nuevo')"><span id="text-nuevo-id_provedor">🔍 Seleccionar Prov...</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-nuevo-id_provedor"></td>
                <td data-campo="id_materia"><div class="selector-box" onclick="abrirModal('modalMat', 'nuevo')"><span id="text-nuevo-id_materia">🔍 Seleccionar Mat...</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-nuevo-id_materia"></td>
                <td data-campo="precio_actual"><input type="number" step="0.01" class="form-control" id="input-nuevo-precio_actual"></td><td data-campo="calidad"><input type="text" class="form-control" id="input-nuevo-calidad"></td><td data-campo="presentacion"><input type="text" class="form-control" id="input-nuevo-presentacion"></td><td data-campo="fecha_actualizacion"><input type="date" class="form-control" id="input-nuevo-fecha_actualizacion"></td><td style="text-align:center;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;
            document.getElementById('cuerpoTabla').prepend(tr);
        }
        function guardarFila(id){ let p = document.getElementById(`input-${id}-id_provedor`).value; let m = document.getElementById(`input-${id}-id_materia`).value; if(!p || !m) { Swal.fire('Alto', 'Debes usar el selector para elegir Proveedor y Materia Prima.', 'warning'); return; } let fd = new FormData(); fd.append('accion', id==='nuevo'?'crear':'editar'); if(id!=='nuevo') fd.append('id', id); campos.forEach(c => fd.append(c, document.getElementById(`input-${id}-${c}`).value)); fetch('crud.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); }); }
        function eliminarFila(id){Swal.fire({title:'¿Borrar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
        function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
    </script>
</body></html>