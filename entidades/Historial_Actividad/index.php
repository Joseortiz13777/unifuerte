<?php 
require_once '../../conexion.php'; 
// JOIN para saber el nombre del usuario
$sql = "SELECT h.*, u.nombre_usuario FROM historial_actividad h LEFT JOIN usuario u ON h.id_usuario = u.ID ORDER BY h.fecha_hora DESC LIMIT 500"; 
$historial = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - Unifuerte</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><link rel="stylesheet" href="../../estilos.css">
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
            <a href="../Historial_Actividad/" class="nav-item active"><i class="ph ph-clock-counter-clockwise"></i> Historial</a>
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
        <header class="header"><h1>Bitácora y Logs del Sistema</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar tabla o usuario..."></div></header>
        <div class="card">
            <div class="card-header"><h3>Últimos <?= count($historial) ?> registros de auditoría</h3><div style="display:flex;gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php','_blank')"><i class="ph-bold ph-printer"></i> Imprimir Logs</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Añadir Log Manual</button></div></div>
            <table id="tablaDatos">
                <thead><tr><th>ID</th><th>Usuario</th><th>Acción</th><th>Tabla Afectada</th><th>ID Registro</th><th>Fecha/Hora</th><th>Detalle Técnico</th><th style="text-align:center;">Acciones</th></tr></thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($historial as $h): ?>
                    <tr id="row-<?= $h['ID'] ?>">
                        <td style="color:var(--text-muted);">#<?= $h['ID'] ?></td>
                        <td data-campo="id_usuario"><?= htmlspecialchars($h['id_usuario'] ?? '') ?> <span style="font-size:0.75rem; color:var(--color-green);"> (<?= htmlspecialchars($h['nombre_usuario'] ?? 'Sistema') ?>)</span></td>
                        <td data-campo="accion_txt"><?= htmlspecialchars($h['accion'] ?? '') ?></td>
                        <td data-campo="tabla_afectada" style="font-weight:bold;"><?= htmlspecialchars($h['tabla_afectada'] ?? '') ?></td>
                        <td data-campo="id_registro_afectado"><?= htmlspecialchars($h['id_registro_afectado'] ?? '') ?></td>
                        <td data-campo="fecha_hora"><?= htmlspecialchars($h['fecha_hora'] ?? '') ?></td>
                        <td data-campo="detalle_cambio" style="font-size:0.75rem; color:#555;"><?= htmlspecialchars($h['detalle_cambio'] ?? '') ?></td>
                        <td style="text-align:center;white-space:nowrap;"><button class="action-btn" onclick="editarFila('<?= $h['ID'] ?>')" id="btn-edit-<?= $h['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $h['ID'] ?>')" id="btn-delete-<?= $h['ID'] ?>"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $h['ID'] ?>')" id="btn-save-<?= $h['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $h['ID'] ?>')" id="btn-cancel-<?= $h['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        let datosOrig={}; const campos=['id_usuario','accion_txt','tabla_afectada','id_registro_afectado','fecha_hora','detalle_cambio'];
        function editarFila(id){datosOrig[id]={};document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{let c=td.getAttribute('data-campo');let val=td.innerText;if(c==='id_usuario') val = val.split(' ')[0]; datosOrig[id][c]=td.innerHTML;let type=c==='fecha_hora'?'datetime-local':(c.includes('id_')?'number':'text');td.innerHTML=`<input type="${type}" class="form-control" id="input-${id}-${c}" value="${val}">`;});document.getElementById(`btn-edit-${id}`).style.display='none';document.getElementById(`btn-delete-${id}`).style.display='none';document.getElementById(`btn-save-${id}`).style.display='inline-block';document.getElementById(`btn-cancel-${id}`).style.display='inline-block';}
        function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-delete-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
        function agregarFilaVacia(){if(document.getElementById('row-nuevo'))return;let tr=document.createElement('tr');tr.id='row-nuevo';tr.style.backgroundColor='#f0fdf4';tr.innerHTML=`<td>Nuevo</td><td data-campo="id_usuario"><input type="number" class="form-control" id="input-nuevo-id_usuario"></td><td data-campo="accion_txt"><input type="text" class="form-control" id="input-nuevo-accion_txt"></td><td data-campo="tabla_afectada"><input type="text" class="form-control" id="input-nuevo-tabla_afectada"></td><td data-campo="id_registro_afectado"><input type="number" class="form-control" id="input-nuevo-id_registro_afectado"></td><td data-campo="fecha_hora"><input type="datetime-local" class="form-control" id="input-nuevo-fecha_hora"></td><td data-campo="detalle_cambio"><input type="text" class="form-control" id="input-nuevo-detalle_cambio"></td><td style="text-align:center;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;document.getElementById('cuerpoTabla').prepend(tr);}
        function guardarFila(id){let fd=new FormData();fd.append('accion',id==='nuevo'?'crear':'editar');if(id!=='nuevo')fd.append('id',id);campos.forEach(c=>fd.append(c,document.getElementById(`input-${id}-${c}`).value));fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')location.reload();});}
        function eliminarFila(id){Swal.fire({title:'¿Borrar Log?',text:"Esto elimina la evidencia del sistema",icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
        function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
    </script>
</body>
</html>