<?php require_once '../../conexion.php'; $usuarios = $pdo->query("SELECT * FROM usuario ORDER BY ID DESC")->fetchAll(PDO::FETCH_ASSOC); ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Usuarios</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><link rel="stylesheet" href="../../estilos.css"></head>
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
            <a href="../Provedor/" class="nav-item"><i class="ph ph-truck"></i> Proveedores</a>
            <a href="../Receta_Prenda/" class="nav-item"><i class="ph ph-notepad"></i> Receta Prenda</a>
            <a href="../Tarea/" class="nav-item"><i class="ph ph-clipboard-text"></i> Tareas</a>
            <a href="../Usuario/" class="nav-ite activem"><i class="ph ph-user-circle"></i> Usuarios</a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="header"><h1>Gestión de Usuarios</h1><div class="search-bar"><input type="text" id="buscador" onkeyup="buscarTabla()" placeholder="Buscar usuario..."></div></header>
        <div class="card" style="overflow-x:auto;">
            <div class="card-header"><h3>Total: <?= count($usuarios) ?> usuarios</h3><div style="display:flex; gap:10px;"><button class="btn btn-report" onclick="window.open('reporte.php', '_blank')"><i class="ph-bold ph-printer"></i> Imprimir</button><button class="btn btn-primary" onclick="agregarFilaVacia()"><i class="ph-bold ph-plus"></i> Añadir Usuario</button></div></div>
            <table id="tablaDatos" style="min-width: 1000px;">
                <thead><tr><th>ID</th><th>Usuario</th><th>Contraseña (Visible)</th><th>Rol</th><th>Nombre</th><th>Apellido</th><th>Cédula</th><th>Teléfono</th><th>Profesión</th><th style="text-align: center; position:sticky; right:0; background:white;">Acciones</th></tr></thead>
                <tbody id="cuerpoTabla">
                    <?php foreach($usuarios as $u): ?>
                    <tr id="row-<?= $u['ID'] ?>"><td>#<?= $u['ID'] ?></td>
                        <td data-campo="nombre_usuario"><?= htmlspecialchars($u['nombre_usuario'] ?? '') ?></td>
                        <td data-campo="contrasena" style="color:#E11D48; font-weight:bold; font-family:monospace;"><?= htmlspecialchars($u['contrasena'] ?? '') ?></td>
                        <td data-campo="rol"><span style="background:var(--bg-green); color:var(--color-green); padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold;"><?= htmlspecialchars($u['rol'] ?? 'Usuario') ?></span></td><td data-campo="nombre"><?= htmlspecialchars($u['nombre'] ?? '') ?></td><td data-campo="apellido"><?= htmlspecialchars($u['apellido'] ?? '') ?></td><td data-campo="cedula"><?= htmlspecialchars($u['cedula'] ?? '') ?></td><td data-campo="tlf"><?= htmlspecialchars($u['tlf'] ?? '') ?></td><td data-campo="profesion"><?= htmlspecialchars($u['profesion'] ?? '') ?></td>
                        <td style="text-align:center; position:sticky; right:0; background:var(--main-bg);"><button class="action-btn" onclick="editarFila('<?= $u['ID'] ?>')" id="btn-edit-<?= $u['ID'] ?>"><i class="ph-fill ph-pencil-simple"></i></button> <button class="action-btn text-red" onclick="eliminarFila('<?= $u['ID'] ?>')" id="btn-delete-<?= $u['ID'] ?>"><i class="ph-fill ph-trash"></i></button> <button class="action-btn text-green" onclick="guardarFila('<?= $u['ID'] ?>')" id="btn-save-<?= $u['ID'] ?>" style="display:none;"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('<?= $u['ID'] ?>')" id="btn-cancel-<?= $u['ID'] ?>" style="display:none;"><i class="ph-bold ph-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        let datosOrig={}; const campos=['nombre_usuario','contrasena','rol','nombre','apellido','cedula','tlf','profesion'];
        function editarFila(id){datosOrig[id]={};document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>{let c=td.getAttribute('data-campo');datosOrig[id][c]=td.innerHTML;td.innerHTML=`<input type="text" class="form-control" id="input-${id}-${c}" value="${td.innerText}">`;});document.getElementById(`btn-edit-${id}`).style.display='none';document.getElementById(`btn-delete-${id}`).style.display='none';document.getElementById(`btn-save-${id}`).style.display='inline-block';document.getElementById(`btn-cancel-${id}`).style.display='inline-block';}
        function cancelarEdicion(id){if(id==='nuevo')return document.getElementById('row-nuevo').remove();document.querySelectorAll(`#row-${id} td[data-campo]`).forEach(td=>td.innerHTML=datosOrig[id][td.getAttribute('data-campo')]);document.getElementById(`btn-edit-${id}`).style.display='inline-block';document.getElementById(`btn-delete-${id}`).style.display='inline-block';document.getElementById(`btn-save-${id}`).style.display='none';document.getElementById(`btn-cancel-${id}`).style.display='none';}
        function agregarFilaVacia(){if(document.getElementById('row-nuevo'))return;let tr=document.createElement('tr');tr.id='row-nuevo';tr.style.backgroundColor='#f0fdf4';tr.innerHTML=`<td>Nuevo</td>${campos.map(c=>`<td data-campo="${c}"><input type="text" class="form-control" id="input-nuevo-${c}"></td>`).join('')}<td style="text-align:center; position:sticky; right:0; background:#f0fdf4;"><button class="action-btn text-green" onclick="guardarFila('nuevo')"><i class="ph-bold ph-check"></i></button> <button class="action-btn text-red" onclick="cancelarEdicion('nuevo')"><i class="ph-bold ph-x"></i></button></td>`;document.getElementById('cuerpoTabla').prepend(tr);}
        function guardarFila(id){let fd=new FormData();fd.append('accion',id==='nuevo'?'crear':'editar');if(id!=='nuevo')fd.append('id',id);campos.forEach(c=>fd.append(c,document.getElementById(`input-${id}-${c}`).value));fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')location.reload();});}
        function eliminarFila(id){Swal.fire({title:'¿Borrar?',icon:'warning',showCancelButton:true,confirmButtonColor:'#E11D48'}).then((r)=>{if(r.isConfirmed){let fd=new FormData();fd.append('accion','eliminar');fd.append('id',id);fetch('crud.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.status==='success')document.getElementById(`row-${id}`).remove();});}});}
        function buscarTabla(){let i=document.getElementById("buscador").value.toLowerCase();document.querySelectorAll("#tablaDatos tbody tr").forEach(tr=>{if(tr.id!=='row-nuevo')tr.style.display=tr.innerText.toLowerCase().includes(i)?"":"none";});}
    </script>
</body></html>