<?php require_once '../../conexion.php'; 
$medidas = $pdo->query("SELECT m.*, d.tipo_prenda, o.nro_orden_cotizacion FROM medicion_personal m LEFT JOIN detalle_orden_compra d ON m.id_detalle_orden_compra = d.ID LEFT JOIN orden_compra o ON d.id_orden_compra = o.ID ORDER BY m.ID DESC")->fetchAll(PDO::FETCH_ASSOC); 
$prendas = $pdo->query("SELECT d.ID, o.nro_orden_cotizacion, d.tipo_prenda, d.talla FROM detalle_orden_compra d LEFT JOIN orden_compra o ON d.id_orden_compra = o.ID ORDER BY d.ID DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Medición Personal</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><link rel="stylesheet" href="../../estilos.css">
<style>.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;justify-content:center;align-items:center;} .modal-box{background:var(--card-bg);width:680px;max-width:95%;max-height:85vh;border-radius:var(--radius-lg);padding:24px;display:flex;flex-direction:column;gap:15px;} .modal-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:1.2rem;border-bottom:1px solid var(--border-color);padding-bottom:10px;} .modal-close{cursor:pointer;color:var(--text-muted);font-size:1.5rem;background:transparent;border:none;} .modal-close:hover{color:var(--color-red);} .modal-tools{display:flex;gap:10px;align-items:center;} .modal-search{flex:1;padding:10px 15px;border:2px solid var(--border-color);border-radius:8px;outline:none;} .btn-crear-nuevo{background:var(--bg-green);color:var(--color-green);border:none;padding:10px 15px;border-radius:8px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;} .btn-refresh{background:#e0e7ff;color:#4f46e5;border:none;padding:10px 12px;border-radius:8px;cursor:pointer;font-size:1.1rem;} .modal-table-container{overflow-y:auto;max-height:400px;border:1px solid var(--border-color);border-radius:8px;} .modal-table{width:100%;border-collapse:collapse;} .modal-table th{background:#f4f5f8;padding:10px;text-align:left;position:sticky;top:0;z-index:2;} .modal-table td{padding:10px;border-bottom:1px solid #eee;} .btn-elegir{background:var(--sidebar-bg);color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;} .selector-box{border:1px solid var(--border-color);background:#fafafa;padding:8px 12px;border-radius:6px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;min-width:150px;gap:5px;}
.form-select { padding: 4px; font-size: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; background: white; outline:none; cursor:pointer;} .form-select:focus { border-color: var(--sidebar-bg); }
</style></head>
<body><aside class="sidebar">
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
            <a href="../Medicion_Personal/" class="nav-item active"><i class="ph ph-ruler"></i> Medición Personal</a>
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
<main class="main-content" style="padding-right: 10px;"><header class="header"><h1>Medidas Corporales por Prenda</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar Empleado o Prenda..."></div></header>
<div class="card" style="overflow-x:auto;"><div class="card-header"><h3>Total: <?= count($medidas) ?> mediciones</h3><div style="display:flex;gap:10px;"><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Nueva Medida</button></div></div>
<table id="tablaDatos" style="font-size:0.75rem; min-width: 2500px;">
<thead><tr><th>ID</th><th>Asignado A (Prenda)</th><th>Nombre Emp.</th><th>Gen.</th><th>Talla Asig.</th><th>Hombro</th><th>Tórax</th><th>Cintura</th><th>Cadera</th><th>L.Camisa</th><th>L.Brazo</th><th>Observ.</th><th>L.Pant</th><th>Tiro</th><th>Muslo</th><th>Rodilla</th><th>Talón</th><th>Descrip.</th><th style="text-align:center; position:sticky; right:0; background:white; z-index:5;">Acciones</th></tr></thead>
<tbody id="cuerpoTabla">
<?php $cols = ['id_detalle_orden_compra','nombre','genero','talla_asignada','hombro','busto_torax','cintura','cadera','largo_camisa','largo_brazo','observacion','largo_pantalon','tiro_total','ancho_muslo','ancho_rodilla','ancho_talon','descripcion']; ?>
<?php foreach($medidas as $m): $txt_prenda = "Ord: " . ($m['nro_orden_cotizacion']??'') . " - " . ($m['tipo_prenda']??''); ?>
<tr id="row-<?= $m['ID'] ?>"><td>#<?= $m['ID'] ?></td>
<td data-campo="id_detalle_orden_compra" data-idval="<?= $m['id_detalle_orden_compra'] ?>" data-text="<?= htmlspecialchars($txt_prenda) ?>" style="font-weight:bold; color:var(--color-green);"><?= htmlspecialchars($txt_prenda) ?></td>
<?php foreach(array_slice($cols,1) as $c) { $val = htmlspecialchars($m[$c] ?? ''); if($c=='nombre') echo "<td data-campo='{$c}' style='font-weight:bold; color:var(--text-dark);'>{$val}</td>"; else if($c=='genero' || $c=='talla_asignada') echo "<td data-campo='{$c}' data-val='{$val}' style='font-weight:bold; color:#E11D48;'>{$val}</td>"; else echo "<td data-campo='{$c}' data-val='{$val}'>{$val}</td>"; } ?>
<td style="text-align:center;white-space:nowrap; position:sticky; right:0; background:var(--main-bg);"><button class="action-btn" onclick="editarFila('<?= $m['ID'] ?>')"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $m['ID'] ?>')"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $m['ID'] ?>')" id="btn-save-<?= $m['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $m['ID'] ?>')" id="btn-cancel-<?= $m['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td></tr>
<?php endforeach; ?>
</tbody></table></div></main>

<div class="modal-overlay" id="modalPrenda"><div class="modal-box"><div class="modal-header"><span>Elegir Prenda de la Orden</span><button class="modal-close" onclick="cerrarModal()"><i class="ph-bold ph-x"></i></button></div><div class="modal-tools"><input type="text" class="modal-search" id="searchPrenda" onkeyup="filtrarLista('searchPrenda', 'tablePrenda')" placeholder="🔍 Buscar por Orden o Prenda..."><button class="btn-refresh" onclick="location.reload()" title="Recargar"><i class="ph-bold ph-arrows-clockwise"></i></button><a href="../Detalle_Orden_Compra/" target="_blank" class="btn-crear-nuevo"><i class="ph-bold ph-plus"></i> Nueva Prenda</a></div><div class="modal-table-container"><table class="modal-table" id="tablePrenda"><thead><tr><th>ID Prenda</th><th>Nro Orden</th><th>Tipo Prenda</th><th>Talla Std</th><th>Acción</th></tr></thead><tbody>
<?php foreach($prendas as $p): $txt = "Ord: " . ($p['nro_orden_cotizacion']??'') . " - " . ($p['tipo_prenda']??''); ?>
<tr><td style="font-weight:bold;">#<?= $p['ID'] ?></td><td><?= htmlspecialchars($p['nro_orden_cotizacion']??'') ?></td><td><?= htmlspecialchars($p['tipo_prenda']??'') ?></td><td><?= htmlspecialchars($p['talla']??'') ?></td><td style="text-align:right;"><button class="btn-elegir" onclick="seleccionarItem('<?= $p['ID'] ?>', '<?= addslashes(htmlspecialchars($txt)) ?>')">Elegir</button></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>

<script>
let datosOrig={}; let filaActivaModal=null; const campos=<?= json_encode($cols) ?>;
const arrTallas = ['2','4','6','8','10','12','14','16','XS','S','M','L','XL','2XL','3XL','4XL','5XL'];

function abrirModal(idFila) { filaActivaModal = idFila; document.getElementById('modalPrenda').style.display='flex'; } function cerrarModal() { document.getElementById('modalPrenda').style.display='none'; }
function seleccionarItem(idVal, txtVal) { document.getElementById(`input-${filaActivaModal}-id_detalle_orden_compra`).value = idVal; document.getElementById(`text-${filaActivaModal}-id_detalle_orden_compra`).innerText = txtVal; cerrarModal(); }
function filtrarLista(inp, tab) { let f=document.getElementById(inp).value.toLowerCase(), r=document.getElementById(tab).getElementsByTagName('tr'); for(let i=1; i<r.length; i++) r[i].style.display = r[i].innerText.toLowerCase().includes(f) ? "" : "none"; }
function getType(c) { return ['nombre','observacion','descripcion'].includes(c) ? 'text' : 'number" step="0.01'; }

function editarFila(id){
    datosOrig[id]={}; document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{
        let c = td.getAttribute('data-campo'); let st = td.getAttribute('data-val') || td.innerText.trim(); datosOrig[id][c]=td.innerHTML; 
        if(c === 'id_detalle_orden_compra'){
            td.innerHTML = `<div class="selector-box" onclick="abrirModal('${id}')"><span id="text-${id}-${c}">${td.getAttribute('data-text')}</span> <i class="ph-bold ph-caret-down"></i></div><input type="hidden" id="input-${id}-${c}" value="${td.getAttribute('data-idval')}">`;
        } else if(c === 'genero') {
            td.innerHTML = `<select class="form-control form-select" style="min-width:100px; font-size:0.75rem; padding:4px;" id="input-${id}-${c}"><option value="">-- Elige --</option><option value="Masculino" ${st==='Masculino'?'selected':''}>Masculino</option><option value="Femenino" ${st==='Femenino'?'selected':''}>Femenino</option></select>`;
        } else if(c === 'talla_asignada') {
            let opts = arrTallas.map(t => `<option value="${t}" ${st===t?'selected':''}>${t}</option>`).join('');
            td.innerHTML = `<select class="form-control form-select" style="min-width:80px; font-size:0.75rem; padding:4px;" id="input-${id}-${c}"><option value="">-- Talla --</option>${opts}</select>`;
        } else {
            td.innerHTML=`<input type="${getType(c)}" class="form-control" style="min-width:80px; font-size:0.75rem; padding:4px;" id="input-${id}-${c}" value="${st}">`;
        }
    });
    td=document.querySelector(`#row-${id} td:last-child`);td.children[0].style.display='none';td.children[1].style.display='none';td.children[2].style.display='inline-block';td.children[3].style.display='inline-block';
}

function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);td=document.querySelector(`#row-${id} td:last-child`);td.children[0].style.display='inline-block';td.children[1].style.display='inline-block';td.children[2].style.display='none';td.children[3].style.display='none';}

function agregarFilaVacia(){
    if(document.getElementById('row-nuevo')) return; let tr=document.createElement('tr'); tr.id='row-nuevo'; tr.style.backgroundColor='#f0fdf4'; 
    let optsT = arrTallas.map(t => `<option value="${t}">${t}</option>`).join('');
    
    let htmlInner = `<td>Nuevo</td><td data-campo="id_detalle_orden_compra"><div class="selector-box" onclick="abrirModal('nuevo')"><span id="text-nuevo-id_detalle_orden_compra">🔍 Elegir Prenda...</span></div><input type="hidden" id="input-nuevo-id_detalle_orden_compra"></td>`;
    campos.slice(1).forEach(c => {
        if(c === 'genero') {
            htmlInner += `<td data-campo="genero"><select class="form-control form-select" style="min-width:100px; font-size:0.75rem; padding:4px;" id="input-nuevo-genero"><option value="">-- Elige --</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></td>`;
        } else if(c === 'talla_asignada') {
            htmlInner += `<td data-campo="talla_asignada"><select class="form-control form-select" style="min-width:80px; font-size:0.75rem; padding:4px;" id="input-nuevo-talla_asignada"><option value="">-- Talla --</option>${optsT}</select></td>`;
        } else {
            htmlInner += `<td data-campo="${c}"><input type="${getType(c)}" style="min-width:80px; font-size:0.75rem; padding:4px;" class="form-control" id="input-nuevo-${c}"></td>`;
        }
    });
    htmlInner += `<td style="text-align:center; position:sticky; right:0; background:#f0fdf4;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;
    tr.innerHTML = htmlInner; document.getElementById('cuerpoTabla').prepend(tr);
}

function guardarFila(id){let p = document.getElementById(`input-${id}-id_detalle_orden_compra`).value; if(!p) { Swal.fire('Alto', 'Usa la Lupa 🔍 para elegir la Prenda', 'warning'); return; } let fd = new FormData(); fd.append('accion', id==='nuevo'?'crear':'editar'); if(id!=='nuevo') fd.append('id', id); campos.forEach(c => fd.append(c, document.getElementById(`input-${id}-${c}`).value)); fetch('crud.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); });}
function eliminarFila(id){Swal.fire({title:'¿Borrar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')location.reload();});}});}
function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
</script></body></html>