<?php 
require_once '../../conexion.php'; 
// Consulta principal
$sql = "SELECT c.*, p.nombre as prov_nombre FROM compra c LEFT JOIN provedor p ON c.id_provedor = p.ID ORDER BY c.ID DESC"; 
$compras = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
$proveedores = $pdo->query("SELECT ID, nombre, RIF_cedula FROM provedor ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Compras Insumos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(3px); }
        .modal-box { background: var(--card-bg); width: 680px; max-width: 95%; max-height: 85vh; border-radius: var(--radius-lg); padding: 24px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .modal-close { cursor: pointer; color: var(--text-muted); font-size: 1.5rem; background: transparent; border: none;} .modal-close:hover { color: var(--color-red); }
        .modal-tools { display: flex; gap: 10px; align-items: center; } .modal-search { flex: 1; padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; outline: none; } .modal-search:focus { border-color: var(--color-green); }
        .btn-crear-nuevo { background: var(--bg-green); color: var(--color-green); border: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px;}
        .btn-refresh { background: #e0e7ff; color: #4f46e5; border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 1.1rem;}
        .modal-table-container { overflow-y: auto; max-height: 400px; border: 1px solid var(--border-color); border-radius: 8px;}
        .modal-table { width: 100%; border-collapse: collapse; } .modal-table th { background: #f4f5f8; padding: 10px; text-align: left; position: sticky; top: 0;} .modal-table td { padding: 10px; border-bottom: 1px solid #eee; } .modal-table tr:hover { background: #f0fdf4; }
        .btn-elegir { background: var(--sidebar-bg); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.75rem;}
        .selector-box { border: 1px solid var(--border-color); background: #fafafa; padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; min-width: 200px; font-size: 0.85rem;}
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
            <a href="../Compra/" class="nav-item active"><i class="ph ph-shopping-bag"></i> Compras</a>
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
        <header class="header"><h1>Registro de Compras (A Proveedores)</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar factura..."></div></header>
        <div class="card">
            <div class="card-header"><h3>Total: <?= count($compras) ?> compras</h3><div style="display:flex;gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php','_blank')"><i class="ph-bold ph-printer"></i> Imprimir</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Registrar Compra</button></div></div>
            <table id="tablaDatos">
                <thead><tr><th>Factura Nro</th><th>Proveedor (Empresa)</th><th>Fecha</th><th>Estado</th><th>Costo ($)</th><th style="text-align:center;">Acciones</th></tr></thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($compras as $c): ?>
                    <tr id="row-<?= $c['ID'] ?>">
                        <td style="font-weight:bold;">#<?= $c['ID'] ?></td>
                        <td data-campo="id_provedor" data-idval="<?= $c['id_provedor'] ?>" data-text="<?= htmlspecialchars($c['prov_nombre'] ?? 'N/A') ?>" style="color:var(--color-green); font-weight:bold;"><?= htmlspecialchars($c['prov_nombre'] ?? 'N/A') ?></td>
                        <td data-campo="fecha_compra"><?= htmlspecialchars($c['fecha_compra'] ?? '') ?></td>
                        <td data-campo="estado" data-val="<?= htmlspecialchars($c['estado'] ?? '') ?>">
                            <span style="background: <?= ($c['estado']=='Finalizada') ? 'var(--bg-green)' : '#fef08a'; ?>; color: <?= ($c['estado']=='Finalizada') ? 'var(--color-green)' : '#854d0e'; ?>; padding:4px 8px; border-radius:8px; font-size:0.75rem; font-weight:bold;"><?= htmlspecialchars($c['estado'] ?? 'En proceso') ?></span>
                        </td>
                        <td data-campo="costo" style="font-weight: 600;">$<?= htmlspecialchars($c['costo'] ?? '0.00') ?></td>
                        <td style="text-align:center;white-space:nowrap;"><button class="action-btn" onclick="editarFila('<?= $c['ID'] ?>')" id="btn-edit-<?= $c['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $c['ID'] ?>')" id="btn-delete-<?= $c['ID'] ?>"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $c['ID'] ?>')" id="btn-save-<?= $c['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $c['ID'] ?>')" id="btn-cancel-<?= $c['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="modalProv">
        <div class="modal-box">
            <div class="modal-header"><span>Seleccionar Proveedor</span><button class="modal-close" onclick="cerrarModal()"><i class="ph-bold ph-x"></i></button></div>
            <div class="modal-tools">
                <input type="text" class="modal-search" id="searchProv" onkeyup="filtrarLista('searchProv', 'tableProv')" placeholder="🔍 Buscar por Nombre o RIF...">
                <button class="btn-refresh" onclick="location.reload()" title="Recargar Lista"><i class="ph-bold ph-arrows-clockwise"></i></button>
                <a href="../Provedor/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nuevo Prov</a>
            </div>
            <div class="modal-table-container">
                <table class="modal-table" id="tableProv">
                    <thead><tr><th>ID</th><th>Empresa</th><th>RIF</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($proveedores as $p): ?>
                        <tr><td><?= $p['ID'] ?></td><td style="font-weight:bold;"><?= htmlspecialchars($p['nombre']) ?></td><td><?= htmlspecialchars($p['RIF_cedula']) ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('<?= $p['ID'] ?>', '<?= addslashes(htmlspecialchars($p['nombre'])) ?>')">Elegir</button></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let datosOrig={}; let filaActivaModal=null; const campos=['id_provedor','fecha_compra','estado','costo'];
        function abrirModal(idFila) { filaActivaModal = idFila; document.getElementById('modalProv').style.display='flex'; }
        function cerrarModal() { document.getElementById('modalProv').style.display='none'; }
        function seleccionarItem(idVal, txtVal) { document.getElementById(`input-${filaActivaModal}-id_provedor`).value = idVal; document.getElementById(`text-${filaActivaModal}-id_provedor`).innerText = txtVal; cerrarModal(); }
        function filtrarLista(inp, tab) { let f=document.getElementById(inp).value.toLowerCase(), r=document.getElementById(tab).getElementsByTagName('tbody')[0].getElementsByTagName('tr'); for(let i=0; i<r.length; i++) r[i].style.display = r[i].innerText.toLowerCase().includes(f) ? "" : "none"; }

        function editarFila(id){
            datosOrig[id]={};
            document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{
                let c = td.getAttribute('data-campo'); datosOrig[id][c]=td.innerHTML; 
                if(c === 'id_provedor'){
                    td.innerHTML = `<div class="selector-box" onclick="abrirModal('${id}')"><span id="text-${id}-id_provedor">${td.getAttribute('data-text')}</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-${id}-id_provedor" value="${td.getAttribute('data-idval')}">`;
                } else if(c === 'estado') {
                    let st = td.getAttribute('data-val');
                    td.innerHTML = `<select class="form-control" id="input-${id}-estado"><option value="En proceso" ${st==='En proceso'?'selected':''}>En proceso</option><option value="Finalizada" ${st==='Finalizada'?'selected':''}>Finalizada</option></select>`;
                } else {
                    let val = td.innerText.replace('$','').trim(); let type = c==='fecha_compra'?'date':'number" step="0.01';
                    td.innerHTML=`<input type="${type}" class="form-control" id="input-${id}-${c}" value="${val}">`;
                }
            });
            document.getElementById(`btn-edit-${id}`).style.display='none'; document.getElementById(`btn-delete-${id}`).style.display='none'; document.getElementById(`btn-save-${id}`).style.display='inline-block'; document.getElementById(`btn-cancel-${id}`).style.display='inline-block';
        }

        function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-delete-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
        
        function agregarFilaVacia(){
            if(document.getElementById('row-nuevo')) return;
            let tr=document.createElement('tr'); tr.id='row-nuevo'; tr.style.backgroundColor='#f0fdf4';
            tr.innerHTML=`<td>Nuevo</td><td data-campo="id_provedor"><div class="selector-box" onclick="abrirModal('nuevo')"><span id="text-nuevo-id_provedor">🔍 Elegir Prov...</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-nuevo-id_provedor"></td><td data-campo="fecha_compra"><input type="date" class="form-control" id="input-nuevo-fecha_compra"></td><td data-campo="estado"><select class="form-control" id="input-nuevo-estado"><option value="En proceso">En proceso</option><option value="Finalizada">Finalizada</option></select></td><td data-campo="costo"><input type="number" step="0.01" class="form-control" id="input-nuevo-costo"></td><td style="text-align:center;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;
            document.getElementById('cuerpoTabla').prepend(tr);
        }

        function guardarFila(id){
            let p = document.getElementById(`input-${id}-id_provedor`).value;
            if(!p) { Swal.fire('Alto', 'Debes seleccionar el proveedor.', 'warning'); return; }
            let fd=new FormData();fd.append('accion',id==='nuevo'?'crear':'editar');if(id!=='nuevo')fd.append('id',id);
            campos.forEach(c=>fd.append(c,document.getElementById(`input-${id}-${c}`).value));
            fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')location.reload();});
        }

        function eliminarFila(id){Swal.fire({title:'¿Borrar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
        function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
    </script>
</body></html>