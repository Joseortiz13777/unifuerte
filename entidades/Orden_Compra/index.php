<?php 
require_once '../../conexion.php'; 
// Consultas Generales
$sql = "SELECT o.*, c.nombre as cl_nombre, u.nombre as vend_nombre, u.apellido as vend_apellido FROM orden_compra o LEFT JOIN cliente c ON o.id_cliente = c.ID LEFT JOIN usuario u ON o.id_usuario_vendedor = u.ID ORDER BY o.ID DESC"; 
$ordenes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 

$clientes = $pdo->query("SELECT ID, nombre, cedula_Rif FROM cliente ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$vendedores = $pdo->query("SELECT ID, nombre, apellido, rol FROM usuario ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Órdenes Producción</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(3px); }
        .modal-box { background: var(--card-bg); width: 680px; max-width: 95%; max-height: 85vh; border-radius: var(--radius-lg); padding: 24px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .modal-close { cursor: pointer; color: var(--text-muted); font-size: 1.5rem; background: transparent; border: none;} .modal-close:hover{color:var(--color-red);}
        .modal-tools { display: flex; gap: 10px; align-items: center; }
        .modal-search { flex: 1; padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; outline: none; font-size: 0.95rem;}
        .btn-crear-nuevo { background: var(--bg-green); color: var(--color-green); padding: 10px 15px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px;}
        .btn-refresh { background: #e0e7ff; color: #4f46e5; border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center;}
        .modal-table-container { overflow-y: auto; max-height: 400px; border: 1px solid var(--border-color); border-radius: 8px;}
        .modal-table { width: 100%; border-collapse: collapse; } .modal-table th { background: #f4f5f8; padding: 10px; text-align: left; position: sticky; top: 0; z-index: 2;} .modal-table td { padding: 10px; border-bottom: 1px solid #eee; } .modal-table tr:hover { background: #f0fdf4; }
        .btn-elegir { background: var(--sidebar-bg); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;}
        .selector-box { border: 1px solid var(--border-color); background: #fafafa; padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; min-width: 140px; gap: 5px;}
        .img-preview { background: #dbeafe; color: #1d4ed8; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight:bold; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition:0.2s;} .img-preview:hover { background:#bfdbfe;}
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
            <a href="../Mat_Prov/" class="nav-item"><i class="ph ph-handshake"></i> Mat. Prov</a>
            <a href="../Materia_Prima/" class="nav-item"><i class="ph ph-t-shirt"></i> Materia Prima</a>
            <a href="../Medicion_Personal/" class="nav-item"><i class="ph ph-ruler"></i> Medición Personal</a>
            <a href="../Orden_Compra/" class="nav-item active"><i class="ph ph-shopping-cart"></i> Órdenes Compra</a>
            <a href="../Patron/" class="nav-item"><i class="ph ph-scissors"></i> Patrones</a>
            <a href="../Patron_orden/" class="nav-item"><i class="ph ph-copy"></i> Patrón Orden</a>
            <a href="../Posicion/" class="nav-item"><i class="ph ph-briefcase"></i> Posiciones</a>
            <a href="../Provedor/" class="nav-item"><i class="ph ph-truck"></i> Proveedores</a>
            <a href="../Receta_Prenda/" class="nav-item"><i class="ph ph-notepad"></i> Receta Prenda</a>
            <a href="../Tarea/" class="nav-item"><i class="ph ph-clipboard-text"></i> Tareas</a>
            <a href="../Usuario/" class="nav-item"><i class="ph ph-user-circle"></i> Usuarios</a>
        </nav>
    </aside>
    <main class="main-content" style="padding-right:10px;">
        <header class="header"><h1>Control de Órdenes (Ventas)</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar Nro o Cliente..."></div></header>
        <div class="card" style="overflow-x:auto;">
            <div class="card-header"><h3>Total: <?= count($ordenes) ?> órdenes</h3><div style="display:flex;gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php','_blank')"><i class="ph-bold ph-printer"></i> Imprimir Libro</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Nueva Orden</button></div></div>
            <table id="tablaDatos" style="font-size: 0.8rem; min-width: 1600px;">
                <thead><tr><th>ID</th><th>Cliente</th><th>Nro Orden</th><th>Emisión</th><th>Entrega</th><th>Exp.(1/0)</th><th>Vendedor Asignado</th><th>Ent. Parc.</th><th>Desc.</th><th>Foto Doc.</th><th>SubTotal</th><th>Flete</th><th>IVA</th><th>Total ($)</th><th style="text-align:center; position:sticky; right:0; background:white; z-index:5;">Acciones</th></tr></thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($ordenes as $o): 
                        $btn_foto = !empty($o['foto']) ? "<a href='../../uploads/{$o['foto']}' target='_blank' class='img-preview'><i class='ph-bold ph-image'></i> Ver Diseño</a>" : "<span style='color:var(--text-muted);'>- N/A -</span>";
                        $vend_nom_comp = ($o['vend_nombre'] ?? '') . ' ' . ($o['vend_apellido'] ?? '');
                    ?>
                    <tr id="row-<?= $o['ID'] ?>"><td>#<?= $o['ID'] ?></td>
                        <td data-campo="id_cliente" data-idval="<?= $o['id_cliente'] ?>" data-text="<?= htmlspecialchars($o['cl_nombre'] ?? 'N/A') ?>" style="color:var(--color-green); font-weight:600;"><?= htmlspecialchars($o['cl_nombre'] ?? 'N/A') ?></td>
                        <td data-campo="nro_orden_cotizacion"><?= htmlspecialchars($o['nro_orden_cotizacion'] ?? '') ?></td><td data-campo="fecha_emision"><?= htmlspecialchars($o['fecha_emision'] ?? '') ?></td><td data-campo="fecha_entrega"><?= htmlspecialchars($o['fecha_entrega'] ?? '') ?></td>
                        <td data-campo="es_express"><?= htmlspecialchars($o['es_express'] ?? '0') ?></td>
                        <td data-campo="id_usuario_vendedor" data-idval="<?= $o['id_usuario_vendedor'] ?>" data-text="<?= htmlspecialchars($vend_nom_comp) ?>"><?= htmlspecialchars($vend_nom_comp ?: 'N/A') ?></td>
                        <td data-campo="acepta_entregas_parciales"><?= htmlspecialchars($o['acepta_entregas_parciales'] ?? '0') ?></td><td data-campo="descripcion"><?= htmlspecialchars($o['descripcion'] ?? '') ?></td>
                        <td data-campo="foto" data-imgval="<?= htmlspecialchars($o['foto'] ?? '') ?>"><?= $btn_foto ?></td>
                        <td data-campo="sub_total"><?= htmlspecialchars($o['sub_total'] ?? '0.00') ?></td><td data-campo="Flete"><?= htmlspecialchars($o['Flete'] ?? '0.00') ?></td><td data-campo="iva"><?= htmlspecialchars($o['iva'] ?? '0.00') ?></td><td data-campo="total" style="font-weight:bold; background: var(--bg-green);">$<?= htmlspecialchars($o['total'] ?? '0.00') ?></td>
                        <td style="text-align:center;white-space:nowrap; position:sticky; right:0; background:var(--main-bg);"><button class="action-btn" onclick="editarFila('<?= $o['ID'] ?>')" id="btn-edit-<?= $o['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $o['ID'] ?>')" id="btn-delete-<?= $o['ID'] ?>"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $o['ID'] ?>')" id="btn-save-<?= $o['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $o['ID'] ?>')" id="btn-cancel-<?= $o['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal-overlay" id="modalCli">
        <div class="modal-box">
            <div class="modal-header"><span>Seleccionar Cliente</span><button class="modal-close" onclick="cerrarModal('modalCli')"><i class="ph-bold ph-x"></i></button></div>
            <div class="modal-tools">
                <input type="text" class="modal-search" id="searchCli" onkeyup="filtrarLista('searchCli', 'tableCli')" placeholder="🔍 Buscar por Nombre o Cédula...">
                <button class="btn-refresh" onclick="location.reload()" title="Recargar"><i class="ph-bold ph-arrows-clockwise"></i></button>
                <a href="../Cliente/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nuevo Cliente</a>
            </div>
            <div class="modal-table-container">
                <table class="modal-table" id="tableCli">
                    <thead><tr><th>ID</th><th>Nombre / Razón Social</th><th>RIF</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($clientes as $c): ?>
                        <tr><td><?= $c['ID'] ?></td><td style="font-weight:bold;"><?= htmlspecialchars($c['nombre']) ?></td><td><?= htmlspecialchars($c['cedula_Rif']) ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('id_cliente', '<?= $c['ID'] ?>', '<?= addslashes(htmlspecialchars($c['nombre'])) ?>')">Elegir</button></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalVend">
        <div class="modal-box">
            <div class="modal-header"><span>Seleccionar Vendedor (Usuario)</span><button class="modal-close" onclick="cerrarModal('modalVend')"><i class="ph-bold ph-x"></i></button></div>
            <div class="modal-tools">
                <input type="text" class="modal-search" id="searchVend" onkeyup="filtrarLista('searchVend', 'tableVend')" placeholder="🔍 Buscar Empleado/Vendedor...">
                <button class="btn-refresh" onclick="location.reload()" title="Recargar"><i class="ph-bold ph-arrows-clockwise"></i></button>
                <a href="../Usuario/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nuevo Usuario</a>
            </div>
            <div class="modal-table-container">
                <table class="modal-table" id="tableVend">
                    <thead><tr><th>ID</th><th>Nombre Empleado</th><th>Rol</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($vendedores as $u): $nom_c = htmlspecialchars($u['nombre'].' '.($u['apellido']??'')); ?>
                        <tr><td><?= $u['ID'] ?></td><td style="font-weight:bold;"><?= $nom_c ?></td><td><?= htmlspecialchars($u['rol']) ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('id_usuario_vendedor', '<?= $u['ID'] ?>', '<?= addslashes($nom_c) ?>')">Elegir</button></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let datosOrig={}; let filaActivaModal=null; 
        const campos=['id_cliente','nro_orden_cotizacion','fecha_emision','fecha_entrega','es_express','id_usuario_vendedor','acepta_entregas_parciales','descripcion','sub_total','Flete','iva','total'];

        function abrirModal(mId, idFila) { filaActivaModal = idFila; document.getElementById(mId).style.display='flex'; }
        function cerrarModal(mId) { document.getElementById(mId).style.display='none'; }
        function seleccionarItem(campo, idItem, txt) { document.getElementById(`input-${filaActivaModal}-${campo}`).value = idItem; document.getElementById(`text-${filaActivaModal}-${campo}`).innerText = txt; cerrarModal(campo==='id_cliente'?'modalCli':'modalVend'); }
        function filtrarLista(inp, tab) { let f=document.getElementById(inp).value.toLowerCase(), r=document.getElementById(tab).getElementsByTagName('tr'); for(let i=1;i<r.length;i++) r[i].style.display=r[i].innerText.toLowerCase().includes(f)?"":"none"; }

        function editarFila(id){
            datosOrig[id]={};
            document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{
                let c = td.getAttribute('data-campo'); datosOrig[id][c] = td.innerHTML;
                if(c === 'id_cliente' || c === 'id_usuario_vendedor') {
                    let idVal=td.getAttribute('data-idval'), txtVal=td.getAttribute('data-text'); let modal = c==='id_cliente' ? 'modalCli' : 'modalVend';
                    td.innerHTML=`<div class="selector-box" onclick="abrirModal('${modal}', '${id}')"><span id="text-${id}-${c}">${txtVal}</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-${id}-${c}" value="${idVal}">`;
                } else if(c === 'foto') {
                    let val = td.getAttribute('data-imgval') || '';
                    td.innerHTML = `<input type="file" id="input-${id}-foto_file" accept="image/*, .pdf" style="width:130px; font-size:10px;"><input type="hidden" id="input-${id}-foto_actual" value="${val}">`;
                } else {
                    let val = td.innerText.replace('$','').trim(); let type = c.includes('fecha')?'date':(c==='total'||c==='Flete'||c==='iva'||c==='sub_total'||c==='es_express'||c==='acepta_entregas_parciales'?'number" step="0.01':'text');
                    td.innerHTML = `<input type="${type}" class="form-control" id="input-${id}-${c}" value="${val}">`;
                }
            });
            document.getElementById(`btn-edit-${id}`).style.display='none'; document.getElementById(`btn-delete-${id}`).style.display='none'; document.getElementById(`btn-save-${id}`).style.display='inline-block'; document.getElementById(`btn-cancel-${id}`).style.display='inline-block';
        }

        function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-delete-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
        
        function agregarFilaVacia(){
            if(document.getElementById('row-nuevo'))return;let tr=document.createElement('tr');tr.id='row-nuevo';tr.style.backgroundColor='#f0fdf4';
            tr.innerHTML=`<td>Nuevo</td>
            <td data-campo="id_cliente"><div class="selector-box" onclick="abrirModal('modalCli', 'nuevo')"><span id="text-nuevo-id_cliente">🔍 Cliente...</span></div><input type="hidden" id="input-nuevo-id_cliente"></td>
            <td data-campo="nro_orden_cotizacion"><input type="text" class="form-control" id="input-nuevo-nro_orden_cotizacion"></td><td data-campo="fecha_emision"><input type="date" class="form-control" id="input-nuevo-fecha_emision"></td><td data-campo="fecha_entrega"><input type="date" class="form-control" id="input-nuevo-fecha_entrega"></td><td data-campo="es_express"><input type="number" class="form-control" id="input-nuevo-es_express" placeholder="0/1"></td>
            <td data-campo="id_usuario_vendedor"><div class="selector-box" onclick="abrirModal('modalVend', 'nuevo')"><span id="text-nuevo-id_usuario_vendedor">🔍 Vendedor...</span></div><input type="hidden" id="input-nuevo-id_usuario_vendedor"></td>
            <td data-campo="acepta_entregas_parciales"><input type="number" class="form-control" id="input-nuevo-acepta_entregas_parciales" placeholder="0/1"></td><td data-campo="descripcion"><input type="text" class="form-control" id="input-nuevo-descripcion"></td>
            <td data-campo="foto"><input type="file" id="input-nuevo-foto_file" accept="image/*, .pdf" style="width:130px; font-size:10px;"><input type="hidden" id="input-nuevo-foto_actual" value=""></td>
            <td data-campo="sub_total"><input type="number" step="0.01" class="form-control" id="input-nuevo-sub_total"></td><td data-campo="Flete"><input type="number" step="0.01" class="form-control" id="input-nuevo-Flete"></td><td data-campo="iva"><input type="number" step="0.01" class="form-control" id="input-nuevo-iva"></td><td data-campo="total"><input type="number" step="0.01" class="form-control" id="input-nuevo-total"></td>
            <td style="text-align:center; position:sticky; right:0; background:#f0fdf4;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;
            document.getElementById('cuerpoTabla').prepend(tr);
        }

        function guardarFila(id){
            let fd = new FormData(); fd.append('accion', id==='nuevo'?'crear':'editar'); if(id!=='nuevo') fd.append('id', id);
            
            // Validar Selectores Mágicos
            if(!document.getElementById(`input-${id}-id_cliente`).value || !document.getElementById(`input-${id}-id_usuario_vendedor`).value) { 
                Swal.fire('Alto', 'Debes usar la lupa 🔍 para elegir Cliente y Vendedor.', 'warning'); return; 
            }

            campos.forEach(c => fd.append(c, document.getElementById(`input-${id}-${c}`).value));

            // MAGIA DE LA FOTO: Empaquetar el archivo binario
            let fInput = document.getElementById(`input-${id}-foto_file`);
            if(fInput && fInput.files.length > 0) { fd.append('foto_file', fInput.files[0]); }
            
            let actualInput = document.getElementById(`input-${id}-foto_actual`);
            if(actualInput) { fd.append('foto_actual', actualInput.value); }

            fetch('crud.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); });
        }

        function eliminarFila(id){Swal.fire({title:'¿Borrar Orden?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
        function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
    </script>
</body></html>