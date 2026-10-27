<?php require_once '../../conexion.php'; $materiales = $pdo->query("SELECT * FROM materia_prima ORDER BY ID DESC")->fetchAll(PDO::FETCH_ASSOC); ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Materia Prima</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><link rel="stylesheet" href="../../estilos.css"><style>.badge-danger{background:#fee2e2;color:#b91c1c;padding:2px 6px;border-radius:8px;font-weight:bold;font-size:0.75rem;margin-left:5px;}</style></head>
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
            <a href="../Materia_Prima/" class="nav-item active"><i class="ph ph-t-shirt"></i> Materia Prima</a>
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
<main class="main-content"><header class="header"><h1>Inventario: Materia Prima</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar tela o color..."></div></header>
<div class="card" style="overflow-x:auto;"><div class="card-header"><h3>Total: <?= count($materiales) ?> insumos</h3><div style="display:flex; gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php', '_blank')"><i class="ph-bold ph-printer"></i> Imprimir Stock</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Registrar</button></div></div>
<table id="tablaDatos" style="min-width:1400px; font-size:0.8rem;">
<thead><tr><th>ID</th><th>Nombre</th><th>Textura</th><th>Color</th><th>Cód. Color</th><th>Dimensión</th><th>Origen</th><th>Unidad</th><th>Stock Act.</th><th>Mínimo</th><th>Composición</th><th>Costo ($)</th><th>Descrip.</th><th style="text-align:center; position:sticky; right:0; background:white;">Acciones</th></tr></thead>
<tbody id="cuerpoTabla">
<?php foreach($materiales as $m): $alerta = ($m['stock_actual'] <= $m['stock_minimo']) ? '<span class="badge-danger">¡Bajo!</span>' : ''; ?>
<tr id="row-<?= $m['ID'] ?>"><td>#<?= $m['ID'] ?></td><td data-campo="nombre"><?= htmlspecialchars($m['nombre'] ?? '') ?></td><td data-campo="textura"><?= htmlspecialchars($m['textura'] ?? '') ?></td><td data-campo="color"><?= htmlspecialchars($m['color'] ?? '') ?></td><td data-campo="codigo_color"><?= htmlspecialchars($m['codigo_color'] ?? '') ?></td><td data-campo="tamano_dimension"><?= htmlspecialchars($m['tamano_dimension'] ?? '') ?></td><td data-campo="origen_procedencia"><?= htmlspecialchars($m['origen_procedencia'] ?? '') ?></td><td data-campo="unidad_medida"><?= htmlspecialchars($m['unidad_medida'] ?? '') ?></td><td data-campo="stock_actual"><?= htmlspecialchars($m['stock_actual'] ?? '0') ?> <?= $alerta ?></td><td data-campo="stock_minimo"><?= htmlspecialchars($m['stock_minimo'] ?? '0') ?></td><td data-campo="composicion"><?= htmlspecialchars($m['composicion'] ?? '') ?></td><td data-campo="costo"><?= htmlspecialchars($m['costo'] ?? '0.00') ?></td><td data-campo="descripcion"><?= htmlspecialchars($m['descripcion'] ?? '') ?></td><td style="text-align:center; position:sticky; right:0; background:var(--main-bg);"><button class="action-btn" onclick="editarFila('<?= $m['ID'] ?>')" id="btn-edit-<?= $m['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $m['ID'] ?>')"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $m['ID'] ?>')" id="btn-save-<?= $m['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $m['ID'] ?>')" id="btn-cancel-<?= $m['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td></tr>
<?php endforeach; ?>
</tbody></table></div></main>
<script>
let datosOrig={}; const campos=['nombre','textura','color','codigo_color','tamano_dimension','origen_procedencia','unidad_medida','stock_actual','stock_minimo','composicion','costo','descripcion'];
function editarFila(id){datosOrig[id]={};document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{let c=td.getAttribute('data-campo');let val=td.innerText.replace('¡Bajo!','').trim();datosOrig[id][c]=td.innerHTML;let type=(c.includes('stock')||c==='costo')?'number" step="0.01':'text';td.innerHTML=`<input type="${type}" class="form-control" id="input-${id}-${c}" value="${val}">`;});document.getElementById(`btn-edit-${id}`).style.display='none';document.getElementById(`btn-save-${id}`).style.display='inline-block';document.getElementById(`btn-cancel-${id}`).style.display='inline-block';}
function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
function agregarFilaVacia(){if(document.getElementById('row-nuevo'))return;let tr=document.createElement('tr');tr.id='row-nuevo';tr.style.backgroundColor='#f0fdf4';tr.innerHTML=`<td>Nuevo</td>${campos.map(c=>`<td data-campo="${c}"><input type="${(c.includes('stock')||c==='costo')?'number" step="0.01':'text'}" class="form-control" id="input-nuevo-${c}"></td>`).join('')}<td style="text-align:center; position:sticky; right:0; background:#f0fdf4;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;document.getElementById('cuerpoTabla').prepend(tr);}
function guardarFila(id){let fd=new FormData();fd.append('accion',id==='nuevo'?'crear':'editar');if(id!=='nuevo')fd.append('id',id);campos.forEach(c=>fd.append(c,document.getElementById(`input-${id}-${c}`).value));fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')location.reload();});}
function eliminarFila(id){Swal.fire({title:'¿Borrar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
</script></body></html>