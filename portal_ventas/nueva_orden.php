<?php
// Archivo: Unifuerte/ventas/index.php
require_once '../conexion.php';

// Consultar clientes y materia prima existentes para los selectores rápidos
$clientes = $pdo->query("SELECT ID, nombre, cedula_Rif FROM cliente ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$materias = $pdo->query("SELECT ID, nombre, color, costo FROM materia_prima ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Ventas / Órdenes - Unifuerte</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --sidebar-bg: #13131A; --sidebar-hover: #1C1C24; --main-bg: #F4F5F8; --card-bg: #FFFFFF;
            --text-dark: #111118; --text-muted: #8F90A6; --text-white: #FFFFFF; --border-color: #E4E4EB;
            --color-green: #059669; --bg-green: #D1FAE5; --color-red: #E11D48; --radius-sm: 8px;
            --radius-md: 12px; --radius-lg: 16px; --primary: #2563EB;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--main-bg); color: var(--text-dark); overflow: hidden; }
        
        /* SIDEBAR (Mantenido de tu diseño original) */
        .sidebar { width: 260px; background-color: var(--sidebar-bg); color: var(--text-muted); display: flex; flex-direction: column; padding: 24px 20px; flex-shrink: 0; }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.2rem; font-weight: 700; color: var(--text-white); margin-bottom: 40px; padding: 0 10px; }
        .logo-icon { background: var(--text-white); color: var(--sidebar-bg); padding: 4px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 4px; flex: 1; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500; font-size: 0.9rem; transition: 0.2s; color: var(--text-muted); text-decoration: none; }
        .nav-item:hover, .nav-item.active { background-color: var(--sidebar-hover); color: var(--text-white); }

        /* CONTENIDO PRINCIPAL REESTRUCTURADO */
        .main-content { flex: 1; padding: 32px 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        .header h1 { font-size: 1.6rem; font-weight: 700; color: var(--text-dark); }
        
        .grid-sections { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .card { background: var(--card-bg); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 24px; }
        .card-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        
        /* FORM CONTROLS COMODOS */
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); outline: none; font-size: 0.9rem; transition: 0.2s; }
        .form-control:focus { border-color: var(--text-dark); box-shadow: 0 0 0 2px rgba(17,17,24,0.05); }
        
        /* COMPONENTES DINÁMICOS */
        .dinamic-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .dinamic-table th { text-align: left; padding: 10px; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        .dinamic-table td { padding: 8px; border-bottom: 1px solid var(--border-color); }
        
        .btn { padding: 10px 20px; border-radius: 20px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s; }
        .btn-primary { background: var(--sidebar-bg); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary { background: var(--border-color); color: var(--text-dark); }
        
        .badge-express { background-color: #FEF3C7; color: #D97706; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
        .scrollable-table { max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); }
    </style>
</head>
<body>

    <main class="main-content">
        <header class="header">
            <h1>Panel de Ventas y Confección</h1>
        </header>

        <form id="formVenta" onsubmit="event.preventDefault(); guardarTodaLaVenta();">
            <div class="grid-sections">
                
                <div class="card">
                    <div class="card-title"><i class="ph-bold ph-user-focus"></i> 1. Información del Cliente</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Seleccionar Cliente Existente</label>
                            <select id="id_cliente" class="form-control" onchange="toggleNuevoCliente(this.value)">
                                <option value="">-- Seleccionar o Registrar Nuevo --</option>
                                <?php foreach($clientes as $cli): ?>
                                    <option value="<?= $cli['ID'] ?>"><?= htmlspecialchars($cli['nombre']) ?> (<?= $cli['cedula_Rif'] ?>)</option>
                                <?php endforeach; ?>
                                <option value="NUEVO">+ Registrar Nuevo Cliente en esta Venta</option>
                            </select>
                        </div>
                    </div>
                    <div id="wrapper-nuevo-cliente" style="display:none; background: #F8FAFC; padding:16px; border-radius:var(--radius-md); border:1px dashed var(--border-color); margin-top:10px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre / Razón Social</label>
                                <input type="text" id="c_nombre" class="form-control" placeholder="Ej: Modelarc C.A.">
                            </div>
                            <div class="form-group">
                                <label>Cédula / RIF</label>
                                <input type="text" id="c_rif" class="form-control" placeholder="Ej: J-501824860">
                            </div>
                            <div class="form-group">
                                <label>Contacto (Comprador)</label>
                                <input type="text" id="c_comprador" class="form-control" placeholder="Ej: Sra. Ingrid">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" id="c_tlf" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="c_email" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Dirección Entrega/Fiscal</label>
                                <input type="text" id="c_direccion" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i class="ph-bold ph-notepad"></i> 2. Datos de la Orden / Cotización</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nro Cotización / Orden</label>
                            <input type="text" id="nro_orden_cotizacion" class="form-control" required placeholder="Ej: 003101">
                        </div>
                        <div class="form-group">
                            <label>Fecha Emisión</label>
                            <input type="date" id="fecha_emision" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha Estimada Entrega</label>
                            <input type="date" id="fecha_entrega" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex-direction:row; align-items:center; gap:10px; padding-top:25px;">
                            <input type="checkbox" id="es_express" value="1" style="width:18px; height:18px;">
                            <label for="es_express">¿Es un Pedido Express? <span class="badge-express">+10% Cargo</span></label>
                        </div>
                        <div class="form-group" style="flex-direction:row; align-items:center; gap:10px; padding-top:25px;">
                            <input type="checkbox" id="acepta_entregas_parciales" value="1" checked style="width:18px; height:18px;">
                            <label for="acepta_entregas_parciales">¿Acepta Entregas Parciales?</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title" style="justify-content:space-between;">
                        <span><i class="ph-bold ph-scissors"></i> 3. Especificación de Prendas y Receta</span>
                        <button type="button" class="btn btn-secondary" onclick="agregarFilaPrenda()" style="padding: 6px 14px; font-size:0.8rem;"><i class="ph ph-plus"></i> Añadir Prenda</button>
                    </div>
                    <table class="dinamic-table" id="tablaPrendas">
                        <thead>
                            <tr>
                                <th>Tipo de Prenda</th>
                                <th>Materia Prima (Tela)</th>
                                <th>Combinación Colores</th>
                                <th>Cant</th>
                                <th>Precio Unit ($)</th>
                                <th>Detalle Diseño / Observaciones</th>
                                <th style="text-align:center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoPrendas">
                            </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-title" style="justify-content:space-between;">
                        <span><i class="ph-bold ph-ruler"></i> 4. Dimensiones y Tallaje del Personal (Medición Personal)</span>
                        <button type="button" class="btn btn-secondary" onclick="agregarFilaPersonal()" style="padding: 6px 14px; font-size:0.8rem;"><i class="ph ph-plus"></i> Añadir Fila</button>
                    </div>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">💡 Tip: Rellena los datos y presiona <strong>ENTER</strong> en la última celda para agregar otra persona rápidamente.</p>
                    <div class="scrollable-table">
                        <table class="dinamic-table" id="tablaPersonal">
                            <thead>
                                <tr style="background:#F8FAFC; position:sticky; top:0;">
                                    <th>Nombre Personal</th>
                                    <th>Género</th>
                                    <th>Talla</th>
                                    <th>Hombro</th>
                                    <th>Busto/Tórax</th>
                                    <th>Cintura</th>
                                    <th>Cadera</th>
                                    <th>L. Camisa</th>
                                    <th>L. Brazo</th>
                                    <th>L. Pantalón</th>
                                    <th>Observación Específica</th>
                                    <th style="text-align:center;"></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoPersonal">
                                </tbody>
                        </table>
                    </div>
                </div>

                <div style="text-align: right; padding-bottom: 40px;">
                    <button type="submit" class="btn btn-primary" style="padding: 14px 40px; font-size:1rem; border-radius:30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="ph-bold ph-sketch-logo"></i> Procesar y Generar Orden de Fábrica
                    </button>
                </div>

            </div>
        </form>
    </main>

    <script>
        // Arreglo de telas disponibles traídas desde PHP
        const materiasDisponibles = <?= json_encode($materias) ?>;

        function toggleNuevoCliente(val) {
            document.getElementById('wrapper-nuevo-cliente').style.display = (val === 'NUEVO') ? 'block' : 'none';
        }

        // Agregar fila de especificación de prendas
        function agregarFilaPrenda() {
            const tbody = document.getElementById('cuerpoPrendas');
            const index = tbody.children.length;
            const tr = document.createElement('tr');
            
            let opcionesTela = '<option value="">-- Seleccionar Tela --</option>';
            materiasDisponibles.forEach(m => {
                opcionesTela += `<option value="${m.ID}">${m.nombre} (${m.color})</option>`;
            });

            tr.innerHTML = `
                <td><input type="text" class="form-control" name="prenda_tipo[]" placeholder="Ej: Camisa Ejecutiva ML" required></td>
                <td><select class="form-control" name="prenda_materia[]" required>${opcionesTela}</select></td>
                <td><input type="text" class="form-control" name="prenda_colores[]" placeholder="Ej: Negro / Gris"></td>
                <td><input type="number" class="form-control" name="prenda_cantidad[]" placeholder="0" style="width:70px;" required></td>
                <td><input type="number" step="0.01" class="form-control" name="prenda_precio[]" placeholder="0.00" style="width:90px;" required></td>
                <td><input type="text" class="form-control" name="prenda_diseno[]" placeholder="Logo bordado blanco en busto"></td>
                <td style="text-align:center;"><button type="button" class="action-btn text-red" onclick="this.closest('tr').remove()" style="background:none; border:none; cursor:pointer; color:var(--color-red); font-size:1.2rem;"><i class="ph ph-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        }

        // Agregar fila de personal (Medición Personal) con comportamiento inteligente del teclado
        function agregarFilaPersonal() {
            const tbody = document.getElementById('cuerpoPersonal');
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td><input type="text" class="form-control" name="p_nombre[]" placeholder="Nombre completo" required></td>
                <td>
                    <select class="form-control" name="p_genero[]" style="width:110px;">
                        <option value="Dama">Dama</option>
                        <option value="Caballero">Caballero</option>
                        <option value="Otro">Perruna/Otro</option>
                    </select>
                </td>
                <td><input type="text" class="form-control" name="p_talla[]" placeholder="M" style="width:60px;" required></td>
                <td><input type="number" step="0.1" class="form-control" name="p_hombro[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_busto[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_cintura[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_cadera[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_lcamisa[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_lbrazo[]" style="width:65px;"></td>
                <td><input type="number" step="0.1" class="form-control" name="p_lpantalon[]" style="width:65px;"></td>
                <td><input type="text" class="form-control" name="p_obs[]" placeholder="Ej: 3er botón para busto" onkeydown="verificarEnter(event)"></td>
                <td style="text-align:center;"><button type="button" onclick="this.closest('tr').remove()" style="background:none; border:none; cursor:pointer; color:var(--color-red); font-size:1.1rem;"><i class="ph ph-x-circle"></i></button></td>
            `;
            tbody.appendChild(tr);
            // Hacer focus automático en el nuevo nombre agregado para agilizar la entrada de datos continuos
            tr.querySelector('input[name="p_nombre[]"]').focus();
        }

        // Al presionar enter en la observación, guarda la fila e inserta de inmediato la siguiente
        function verificarEnter(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                agregarFilaPersonal();
            }
        }

        // Envío estructurado de datos masivos usando la API Fetch nativa
        function guardarTodaLaVenta() {
            const formData = new FormData();
            
            // Datos del cliente / Cabecera
            formData.append('id_cliente', document.getElementById('id_cliente').value);
            formData.append('c_nombre', document.getElementById('c_nombre').value);
            formData.append('c_rif', document.getElementById('c_rif').value);
            formData.append('c_comprador', document.getElementById('c_comprador').value);
            formData.append('c_tlf', document.getElementById('c_tlf').value);
            formData.append('c_email', document.getElementById('c_email').value);
            formData.append('c_direccion', document.getElementById('c_direccion').value);
            
            // Datos de la orden
            formData.append('nro_orden_cotizacion', document.getElementById('nro_orden_cotizacion').value);
            formData.append('fecha_emision', document.getElementById('fecha_emision').value);
            formData.append('fecha_entrega', document.getElementById('fecha_entrega').value);
            formData.append('es_express', document.getElementById('es_express').checked ? '1' : '0');
            formData.append('acepta_entregas_parciales', document.getElementById('acepta_entregas_parciales').checked ? '1' : '0');

            // Recolectar arreglos dinámicos de prendas
            const prendas = document.getElementById('cuerpoPrendas').children;
            if(prendas.length === 0){
                Swal.fire('Atención', 'Debes añadir al menos una prenda a la orden.', 'warning');
                return;
            }
            
            // Serialización manual limpia para arrays paralelos de inputs en FormData
            document.querySelectorAll('#cuerpoPrendas tr').forEach((tr) => {
                formData.append('prenda_tipo[]', tr.querySelector('[name="prenda_tipo[]"]').value);
                formData.append('prenda_materia[]', tr.querySelector('[name="prenda_materia[]"]').value);
                formData.append('prenda_colores[]', tr.querySelector('[name="prenda_colores[]"]').value);
                formData.append('prenda_cantidad[]', tr.querySelector('[name="prenda_cantidad[]"]').value);
                formData.append('prenda_precio[]', tr.querySelector('[name="prenda_precio[]"]').value);
                formData.append('prenda_diseno[]', tr.querySelector('[name="prenda_diseno[]"]').value);
            });

            // Recolectar Mediciones de personal
            document.querySelectorAll('#cuerpoPersonal tr').forEach((tr) => {
                formData.append('p_nombre[]', tr.querySelector('[name="p_nombre[]"]').value);
                formData.append('p_genero[]', tr.querySelector('[name="p_genero[]"]').value);
                formData.append('p_talla[]', tr.querySelector('[name="p_talla[]"]').value);
                formData.append('p_hombro[]', tr.querySelector('[name="p_hombro[]"]').value);
                formData.append('p_busto[]', tr.querySelector('[name="p_busto[]"]').value);
                formData.append('p_cintura[]', tr.querySelector('[name="p_cintura[]"]').value);
                formData.append('p_cadera[]', tr.querySelector('[name="p_cadera[]"]').value);
                formData.append('p_lcamisa[]', tr.querySelector('[name="p_lcamisa[]"]').value);
                formData.append('p_lbrazo[]', tr.querySelector('[name="p_lbrazo[]"]').value);
                formData.append('p_lpantalon[]', tr.querySelector('[name="p_lpantalon[]"]').value);
                formData.append('p_obs[]', tr.querySelector('[name="p_obs[]"]').value);
            });

            // Enviar vía AJAX seguro al controlador transaccional
            fetch('procesar_venta.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Orden Generada con éxito!',
                        text: 'Se ha registrado la venta y las fichas técnicas.',
                        showCancelButton: true,
                        confirmButtonText: '🖨️ Ver Orden de Fábrica',
                        cancelButtonText: 'Quedarse aquí'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('orden_fabrica.php?id_orden=' + data.id_orden, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error de Red', 'No se pudo conectar con el servidor procesador.', 'error');
            });
        }

        // Inicializar con una fila vacía de cada sección por comodidad del vendedor al cargar la página
        agregarFilaPrenda();
        agregarFilaPersonal();
    </script>
</body>
</html>