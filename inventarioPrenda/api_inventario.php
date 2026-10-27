<?php
ob_start();
session_start();
require_once '../conexion.php';
ob_clean(); 

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['usuario_id']) || !isset($_POST['accion'])) {
        echo json_encode(['success' => false, 'msg' => 'Acceso no autorizado.']);
        exit();
    }

    $id_vendedor_actual = intval($_SESSION['usuario_id']);
    $accion = $_POST['accion'];

    // ==============================================================================
    // FUNCIÓN CENTRAL DE AUDITORÍA
    // ==============================================================================
    function registrar_auditoria($con, $id_usr, $accion, $tabla, $id_reg, $detalle) {
        $acc = mysqli_real_escape_string($con, $accion);
        $tab = mysqli_real_escape_string($con, $tabla);
        $det = mysqli_real_escape_string($con, $detalle);
        mysqli_query($con, "INSERT INTO historial_actividad (id_usuario, accion, tabla_afectada, id_registro_afectado, fecha_hora, detalle_cambio) VALUES ($id_usr, '$acc', '$tab', $id_reg, NOW(), '$det')");
    }
    // ==============================================================================

    if ($accion === 'get_prendas') {
        $search = mysqli_real_escape_string($conexion, $_POST['search'] ?? '');
        $sede = intval($_POST['sede'] ?? 0);
        $estante = intval($_POST['estante'] ?? 0);
        $cat = intval($_POST['cat'] ?? 0);
        $sub = intval($_POST['sub'] ?? 0);
        $marca = intval($_POST['marca'] ?? 0);
        $nivel = intval($_POST['nivel'] ?? 0);

        $where = ["1=1"];
        if ($search !== '') $where[] = "(p.codigo_lote LIKE '%$search%' OR p.articulo LIKE '%$search%' OR p.talla LIKE '%$search%' OR p.color LIKE '%$search%' OR m.nombre LIKE '%$search%')";
        if ($sede > 0) $where[] = "p.id_sede = $sede";
        if ($estante > 0) $where[] = "p.id_estante = $estante";
        if ($cat > 0) $where[] = "p.id_categoria = $cat";
        if ($sub > 0) $where[] = "p.id_subcategoria = $sub";
        if ($marca > 0) $where[] = "p.id_marca = $marca";
        if ($nivel > 0) $where[] = "p.nivel_estante = $nivel";

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT p.*, 
                       m.nombre as marca_nombre, c.nombre as cat_nombre, s.nombre as subcat_nombre,
                       sd.nombre as sede_nombre, es.nombre as estante_nombre, es.codigo as estante_codigo,
                       es.niveles as estante_niveles, es.columnas as estante_columnas
                FROM inv_fisico_prendas p 
                LEFT JOIN inv_fisico_marcas m ON p.id_marca = m.id 
                LEFT JOIN inv_fisico_categorias c ON p.id_categoria = c.id 
                LEFT JOIN inv_fisico_subcategorias s ON p.id_subcategoria = s.id 
                LEFT JOIN inv_fisico_sedes sd ON p.id_sede = sd.id
                LEFT JOIN inv_fisico_estantes es ON p.id_estante = es.id
                WHERE $whereClause
                ORDER BY p.id DESC";
                
        $q = mysqli_query($conexion, $sql);
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'get_estantes_por_sede') {
        $id_sede = intval($_POST['id_sede']);
        $q = mysqli_query($conexion, "SELECT * FROM inv_fisico_estantes WHERE id_sede = $id_sede ORDER BY nombre ASC");
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'get_slot_content') {
        $id_estante = intval($_POST['id_estante']);
        $nivel = intval($_POST['nivel']);
        $lado = mysqli_real_escape_string($conexion, $_POST['lado']);

        $sql = "SELECT p.id, p.articulo, p.color, p.talla, p.genero, p.stock, p.detalles_extra,
                       IFNULL(m.nombre, 'Genérico') as marca_nombre,
                       IFNULL(c.nombre, 'Sin Cat') as cat_nombre
                FROM inv_fisico_prendas p
                LEFT JOIN inv_fisico_marcas m ON p.id_marca = m.id
                LEFT JOIN inv_fisico_categorias c ON p.id_categoria = c.id
                WHERE p.id_estante = $id_estante AND p.nivel_estante = $nivel AND p.lado_estante = '$lado' AND p.stock > 0
                ORDER BY p.orden_apilado ASC, p.id ASC";
                
        $q = mysqli_query($conexion, $sql);
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'get_estante_organizador') {
        $sede = intval($_POST['sede'] ?? 0);
        $estante = intval($_POST['estante'] ?? 0);

        $where = ["p.stock > 0"];
        if ($sede > 0) $where[] = "p.id_sede = $sede";
        if ($estante > 0) $where[] = "p.id_estante = $estante";
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT p.id, p.id_sede, p.id_estante, p.detalles_extra,
                       IFNULL(sd.nombre, 'Sin Sede Asignada') as sede_nombre, 
                       IFNULL(es.nombre, 'Sin Estante Asignado') as estante_nombre, es.codigo as estante_codigo,
                       p.nivel_estante, p.lado_estante, p.articulo, p.color, p.talla, p.stock, p.genero, 
                       IFNULL(c.nombre, 'Sin Categoría') as categoria, IFNULL(m.nombre, 'Genérico') as marca
                FROM inv_fisico_prendas p
                LEFT JOIN inv_fisico_sedes sd ON p.id_sede = sd.id
                LEFT JOIN inv_fisico_estantes es ON p.id_estante = es.id
                LEFT JOIN inv_fisico_categorias c ON p.id_categoria = c.id
                LEFT JOIN inv_fisico_marcas m ON p.id_marca = m.id
                WHERE $whereClause
                ORDER BY p.id_sede ASC, p.id_estante ASC, p.nivel_estante ASC, CAST(p.lado_estante AS UNSIGNED) ASC, p.orden_apilado ASC, p.id ASC";
                
        $q = mysqli_query($conexion, $sql);
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'reordenar_estante') {
        $id_prenda = intval($_POST['id_prenda']);
        $direccion = $_POST['direccion']; 
        $q1 = mysqli_query($conexion, "SELECT id_estante, nivel_estante, lado_estante FROM inv_fisico_prendas WHERE id = $id_prenda");
        $row1 = mysqli_fetch_assoc($q1);
        $id_estante = $row1['id_estante']; $nivel = $row1['nivel_estante']; $lado = $row1['lado_estante'];

        $q2 = mysqli_query($conexion, "SELECT id FROM inv_fisico_prendas WHERE id_estante = $id_estante AND nivel_estante = $nivel AND lado_estante = '$lado' AND stock > 0 ORDER BY orden_apilado ASC, id ASC");
        $prendas = [];
        while($r = mysqli_fetch_assoc($q2)) $prendas[] = $r['id'];

        $idx = array_search($id_prenda, $prendas);

        if ($direccion === 'up' && $idx > 0) {
            $temp = $prendas[$idx - 1]; $prendas[$idx - 1] = $prendas[$idx]; $prendas[$idx] = $temp;
        } else if ($direccion === 'down' && $idx < (count($prendas) - 1)) {
            $temp = $prendas[$idx + 1]; $prendas[$idx + 1] = $prendas[$idx]; $prendas[$idx] = $temp;
        }

        foreach ($prendas as $nuevo_orden => $p_id) {
            mysqli_query($conexion, "UPDATE inv_fisico_prendas SET orden_apilado = $nuevo_orden WHERE id = $p_id");
        }
        
        registrar_auditoria($conexion, $id_vendedor_actual, 'EDITAR', 'inv_fisico_prendas', $id_prenda, "Reordenó prenda físicamente dentro de un estante.");
        echo json_encode(['success' => true]);
        exit();
    }

    if ($accion === 'save_prenda') {
        $id = intval($_POST['id']);
        $codigo_lote = mysqli_real_escape_string($conexion, $_POST['codigo_lote']);
        $id_sede = intval($_POST['id_sede'] ?? 0);
        $id_estante = intval($_POST['id_estante'] ?? 0);
        $id_marca = intval($_POST['id_marca'] ?? 0);
        $id_categoria = intval($_POST['id_categoria'] ?? 0);
        $id_subcategoria = intval($_POST['id_subcategoria'] ?? 0);
        
        $articulo = mysqli_real_escape_string($conexion, $_POST['articulo']);
        $color = mysqli_real_escape_string($conexion, $_POST['color']);
        $genero = mysqli_real_escape_string($conexion, $_POST['genero'] ?? 'Unisex');
        $talla = mysqli_real_escape_string($conexion, $_POST['talla']);
        
        $stock = intval($_POST['stock']);
        $precio_base = floatval($_POST['precio_base']);
        $nivel_estante = intval($_POST['nivel_estante'] ?? 1);
        $lado_estante = mysqli_real_escape_string($conexion, $_POST['lado_estante'] ?? '1');
        $detalles_extra = mysqli_real_escape_string($conexion, $_POST['detalles_extra'] ?? '');

        $id_s_v = $id_sede > 0 ? $id_sede : "NULL";
        $id_e_v = $id_estante > 0 ? $id_estante : "NULL";
        $id_m_v = $id_marca > 0 ? $id_marca : "NULL";
        $id_c_v = $id_categoria > 0 ? $id_categoria : "NULL";
        $id_sc_v= $id_subcategoria > 0 ? $id_subcategoria : "NULL";

        if ($id > 0) {
            $sql = "UPDATE inv_fisico_prendas 
                    SET id_sede=$id_s_v, id_estante=$id_e_v, id_marca=$id_m_v, id_categoria=$id_c_v, id_subcategoria=$id_sc_v, 
                        articulo='$articulo', color='$color', genero='$genero', talla='$talla', stock=$stock, precio_base=$precio_base, 
                        nivel_estante=$nivel_estante, lado_estante='$lado_estante', detalles_extra='$detalles_extra' 
                    WHERE id=$id";
        } else {
            $sql = "INSERT INTO inv_fisico_prendas 
                    (id_sede, id_estante, id_marca, id_categoria, id_subcategoria, codigo_lote, articulo, color, genero, talla, stock, precio_base, nivel_estante, lado_estante, detalles_extra, orden_apilado) 
                    VALUES 
                    ($id_s_v, $id_e_v, $id_m_v, $id_c_v, $id_sc_v, '$codigo_lote', '$articulo', '$color', '$genero', '$talla', $stock, $precio_base, $nivel_estante, '$lado_estante', '$detalles_extra', 999)";
        }
        
        if(mysqli_query($conexion, $sql)) {
            $id_aud = ($id > 0) ? $id : mysqli_insert_id($conexion);
            $acc_aud = ($id > 0) ? 'EDITAR' : 'CREAR';
            $det_aud = ($id > 0) ? "Actualizó inventario de prenda ($articulo, Stock: $stock)" : "Ingresó nueva prenda a inventario ($articulo, Stock: $stock)";
            registrar_auditoria($conexion, $id_vendedor_actual, $acc_aud, 'inv_fisico_prendas', $id_aud, $det_aud);
            
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'msg'=> 'Error SQL: ' . mysqli_error($conexion)]);
        }
        exit();
    }

    if ($accion === 'delete_prenda') {
        $id = intval($_POST['id']);
        if(mysqli_query($conexion, "DELETE FROM inv_fisico_prendas WHERE id = $id")) {
            registrar_auditoria($conexion, $id_vendedor_actual, 'ELIMINAR', 'inv_fisico_prendas', $id, "Eliminó un registro de prenda del inventario físico.");
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'msg'=> 'Error SQL: ' . mysqli_error($conexion)]);
        }
        exit();
    }

    if ($accion === 'checkout_carrito') {
        $esNuevoCli = $_POST['nuevo_cli'] === 'true' ? true : false;
        $id_cliente = intval($_POST['id_cliente'] ?? 0);
        $total_usd = floatval($_POST['total_usd']);
        $carrito = json_decode($_POST['carrito'], true);

        if ($esNuevoCli) {
            $nombre = mysqli_real_escape_string($conexion, $_POST['cli_nombre']);
            $cedula = mysqli_real_escape_string($conexion, $_POST['cli_cedula']);
            $tlf = mysqli_real_escape_string($conexion, $_POST['cli_tlf'] ?? '');
            $sql_cli = "INSERT INTO cliente (nombre, cedula_Rif, tlf) VALUES ('$nombre', '$cedula', '$tlf')";
            if (mysqli_query($conexion, $sql_cli)) {
                $id_cliente = mysqli_insert_id($conexion);
                registrar_auditoria($conexion, $id_vendedor_actual, 'CREAR', 'cliente', $id_cliente, "Creó nuevo cliente ($nombre) desde Ventas de Inventario.");
            } else { 
                echo json_encode(['success' => false, 'msg' => 'Error al crear cliente.']); exit(); 
            }
        }

        $sql_nota = "INSERT INTO inv_fisico_notas (id_cliente, id_vendedor, nro_nota, total_usd, estado) VALUES ($id_cliente, $id_vendedor_actual, 'PENDIENTE', $total_usd, 'Pendiente')";
        if (mysqli_query($conexion, $sql_nota)) {
            $id_nota = mysqli_insert_id($conexion);
            $nro_nota = "NE-INV-" . str_pad($id_nota, 4, "0", STR_PAD_LEFT);
            mysqli_query($conexion, "UPDATE inv_fisico_notas SET nro_nota = '$nro_nota' WHERE id = $id_nota");

            foreach ($carrito as $item) {
                $id_prenda = intval($item['id']); $cant = intval($item['cantidad']); $prec = floatval($item['precio']); $subt = floatval($item['subtotal']);
                mysqli_query($conexion, "INSERT INTO inv_fisico_notas_detalle (id_nota, id_prenda, cantidad, precio_unitario, subtotal) VALUES ($id_nota, $id_prenda, $cant, $prec, $subt)");
                // Al generar la cotización se descuenta stock de reserva
                mysqli_query($conexion, "UPDATE inv_fisico_prendas SET stock = stock - $cant WHERE id = $id_prenda");
            }
            
            registrar_auditoria($conexion, $id_vendedor_actual, 'CREAR', 'inv_fisico_notas', $id_nota, "Procesó Venta Directa de Inventario Físico. Nro. Nota: $nro_nota");
            echo json_encode(['success' => true, 'id_nota' => $id_nota]);
        } else { echo json_encode(['success' => false, 'msg' => 'Error al crear la nota.']); }
        exit();
    }

    if ($accion === 'get_notas') {
        $estado = $_POST['estado']; 
        $condicion = ($estado === 'activas') ? "n.estado = 'Pendiente'" : "n.estado IN ('Pagado', 'Finalizada')";
        $sql = "SELECT n.*, c.nombre as cliente_nombre, u.nombre as vendedor_nombre, u.apellido as vendedor_apellido,
                       (SELECT SUM(cantidad) FROM inv_fisico_notas_detalle WHERE id_nota = n.id) as prendas_entregadas
                FROM inv_fisico_notas n
                JOIN cliente c ON n.id_cliente = c.ID
                LEFT JOIN usuario u ON n.id_vendedor = u.ID
                WHERE $condicion ORDER BY n.id DESC";
        $q = mysqli_query($conexion, $sql);
        $data = [];
        if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'pagar_nota_inventario') {
        $id_nota = intval($_POST['id_nota']);
        $metodo = mysqli_real_escape_string($conexion, $_POST['metodo_pago']);
        $ref = mysqli_real_escape_string($conexion, $_POST['referencia']);
        
        $comprobante_nombre = NULL;
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
            $comprobante_nombre = 'pago_inv_' . time() . '_' . rand(100,999) . '.' . $ext;
            
            // LA RUTA CORREGIDA: Sube un nivel desde /inventarioPrenda/ y entra en /uploads/
            $ruta_destino = '../uploads/' . $comprobante_nombre;
            
            if (!is_dir('../uploads/')) { 
                @mkdir('../uploads/', 0777, true); 
            }
            
            move_uploaded_file($_FILES['comprobante']['tmp_name'], $ruta_destino);
        }

        if ($comprobante_nombre) {
            $sql = "UPDATE inv_fisico_notas SET estado = 'Pagado', metodo_pago = '$metodo', referencia = '$ref', comprobante = '$comprobante_nombre' WHERE id = $id_nota";
        } else {
            $sql = "UPDATE inv_fisico_notas SET estado = 'Pagado', metodo_pago = '$metodo', referencia = '$ref' WHERE id = $id_nota";
        }

        if(mysqli_query($conexion, $sql)) { 
            registrar_auditoria($conexion, $id_vendedor_actual, 'ESTADO_PAGO', 'inv_fisico_notas', $id_nota, "Registró pago para Nota de Inventario por $metodo.");
            echo json_encode(['success' => true]); 
        } else { echo json_encode(['success' => false, 'msg' => 'Error SQL: ' . mysqli_error($conexion)]); }
        exit();
    }

    if ($accion === 'cambiar_estado_nota') {
        $id = intval($_POST['id']); $estado = mysqli_real_escape_string($conexion, $_POST['estado']); 
        if(mysqli_query($conexion, "UPDATE inv_fisico_notas SET estado = '$estado' WHERE id = $id")) {
            registrar_auditoria($conexion, $id_vendedor_actual, 'EDITAR', 'inv_fisico_notas', $id, "Forzó el cambio de estado de la Venta de Inventario a: $estado.");
            echo json_encode(['success' => true]);
        } else echo json_encode(['success' => false, 'msg' => 'Error SQL.']);
        exit();
    }

    if ($accion === 'delete_nota') {
        $id = intval($_POST['id']);
        $q_det = mysqli_query($conexion, "SELECT id_prenda, cantidad FROM inv_fisico_notas_detalle WHERE id_nota = $id");
        if($q_det) {
            while($r = mysqli_fetch_assoc($q_det)) {
                $id_p = $r['id_prenda']; $cant = $r['cantidad'];
                mysqli_query($conexion, "UPDATE inv_fisico_prendas SET stock = stock + $cant WHERE id = $id_p");
            }
        }
        mysqli_query($conexion, "DELETE FROM inv_fisico_notas_detalle WHERE id_nota = $id");
        if(mysqli_query($conexion, "DELETE FROM inv_fisico_notas WHERE id = $id")) {
            registrar_auditoria($conexion, $id_vendedor_actual, 'ELIMINAR', 'inv_fisico_notas', $id, "Anuló/Eliminó una Cotización Directa y se restauró el stock de las prendas.");
            echo json_encode(['success'=>true]);
        } else echo json_encode(['success'=>false, 'msg'=> 'Error SQL.']);
        exit();
    }

    if ($accion === 'get_mantenimiento') {
        $tipo = $_POST['tipo'];
        $data = [];
        if ($tipo === 'Sedes') {
            $q = mysqli_query($conexion, "SELECT id, nombre FROM inv_fisico_sedes ORDER BY nombre ASC");
            if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        } else if ($tipo === 'Estantes') {
            $q = mysqli_query($conexion, "SELECT e.id, e.codigo, e.nombre, e.niveles, e.columnas, e.id_sede, s.nombre as sede_nombre FROM inv_fisico_estantes e LEFT JOIN inv_fisico_sedes s ON e.id_sede = s.id ORDER BY s.nombre ASC, e.nombre ASC");
            if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        } else if ($tipo === 'Marcas') {
            $q = mysqli_query($conexion, "SELECT id, nombre FROM inv_fisico_marcas ORDER BY nombre ASC");
            if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        } else if ($tipo === 'Categorías') {
            $q = mysqli_query($conexion, "SELECT id, nombre FROM inv_fisico_categorias ORDER BY nombre ASC");
            if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        } else if ($tipo === 'Subcategorías') {
            $q = mysqli_query($conexion, "SELECT s.id, s.nombre, s.id_categoria, c.nombre as categoria_nombre FROM inv_fisico_subcategorias s LEFT JOIN inv_fisico_categorias c ON s.id_categoria = c.id ORDER BY c.nombre ASC, s.nombre ASC");
            if($q) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    }

    if ($accion === 'save_mantenimiento') {
        $tipo = $_POST['tipo'];
        $id = intval($_POST['id']);
        $nombre = mysqli_real_escape_string($conexion, trim($_POST['nombre']));
        $id_cat = intval($_POST['id_categoria'] ?? 0);
        $id_sede = intval($_POST['id_sede'] ?? 0);
        $codigo = mysqli_real_escape_string($conexion, trim($_POST['codigo'] ?? ''));
        $niveles = intval($_POST['niveles'] ?? 5);
        $columnas = intval($_POST['columnas'] ?? 2);

        if (empty($nombre)) { echo json_encode(['success' => false, 'msg' => 'El nombre no puede estar vacío.']); exit(); }

        $tabla_afectada = '';

        if ($tipo === 'Sedes') {
            $tabla_afectada = 'inv_fisico_sedes';
            $sql = ($id > 0) ? "UPDATE inv_fisico_sedes SET nombre = '$nombre' WHERE id = $id" : "INSERT INTO inv_fisico_sedes (nombre) VALUES ('$nombre')";
        } else if ($tipo === 'Estantes') {
            $tabla_afectada = 'inv_fisico_estantes';
            if ($id_sede == 0) { echo json_encode(['success' => false, 'msg' => 'Selecciona una Sede.']); exit(); }
            $sql = ($id > 0) ? "UPDATE inv_fisico_estantes SET id_sede=$id_sede, codigo='$codigo', nombre='$nombre', niveles=$niveles, columnas=$columnas WHERE id = $id" : "INSERT INTO inv_fisico_estantes (id_sede, codigo, nombre, niveles, columnas) VALUES ($id_sede, '$codigo', '$nombre', $niveles, $columnas)";
        } else if ($tipo === 'Marcas') {
            $tabla_afectada = 'inv_fisico_marcas';
            $sql = ($id > 0) ? "UPDATE inv_fisico_marcas SET nombre = '$nombre' WHERE id = $id" : "INSERT INTO inv_fisico_marcas (nombre) VALUES ('$nombre')";
        } else if ($tipo === 'Categorías') {
            $tabla_afectada = 'inv_fisico_categorias';
            $sql = ($id > 0) ? "UPDATE inv_fisico_categorias SET nombre = '$nombre' WHERE id = $id" : "INSERT INTO inv_fisico_categorias (nombre) VALUES ('$nombre')";
        } else if ($tipo === 'Subcategorías') {
            $tabla_afectada = 'inv_fisico_subcategorias';
            if ($id_cat == 0) { echo json_encode(['success' => false, 'msg' => 'Selecciona Categoría Padre.']); exit(); }
            $sql = ($id > 0) ? "UPDATE inv_fisico_subcategorias SET nombre = '$nombre', id_categoria = $id_cat WHERE id = $id" : "INSERT INTO inv_fisico_subcategorias (nombre, id_categoria) VALUES ('$nombre', $id_cat)";
        }

        if (mysqli_query($conexion, $sql)) {
            $id_aud = ($id > 0) ? $id : mysqli_insert_id($conexion);
            $acc_aud = ($id > 0) ? 'EDITAR' : 'CREAR';
            registrar_auditoria($conexion, $id_vendedor_actual, $acc_aud, $tabla_afectada, $id_aud, "Realizó mantenimiento en Catálogo de Inventario ($tipo: $nombre)");
            echo json_encode(['success' => true]);
        } else echo json_encode(['success' => false, 'msg' => 'Error SQL: ' . mysqli_error($conexion)]);
        exit();
    }

    if ($accion === 'delete_mantenimiento') {
        $tipo = $_POST['tipo'];
        $id = intval($_POST['id']);
        
        $tabla_afectada = '';

        if ($tipo === 'Sedes') {
            $tabla_afectada = 'inv_fisico_sedes';
            $check = mysqli_query($conexion, "SELECT id FROM inv_fisico_estantes WHERE id_sede = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) { echo json_encode(['success' => false, 'msg' => 'Tiene estantes asignados.']); exit(); }
            $sql = "DELETE FROM inv_fisico_sedes WHERE id = $id";
        } else if ($tipo === 'Estantes') {
            $tabla_afectada = 'inv_fisico_estantes';
            $check = mysqli_query($conexion, "SELECT id FROM inv_fisico_prendas WHERE id_estante = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) { echo json_encode(['success' => false, 'msg' => 'Tiene prendas guardadas.']); exit(); }
            $sql = "DELETE FROM inv_fisico_estantes WHERE id = $id";
        } else if ($tipo === 'Categorías') {
            $tabla_afectada = 'inv_fisico_categorias';
            $check = mysqli_query($conexion, "SELECT id FROM inv_fisico_subcategorias WHERE id_categoria = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) { echo json_encode(['success' => false, 'msg' => 'Tiene Subcategorías asignadas.']); exit(); }
            $sql = "DELETE FROM inv_fisico_categorias WHERE id = $id";
        } else if ($tipo === 'Subcategorías') {
            $tabla_afectada = 'inv_fisico_subcategorias';
            $check = mysqli_query($conexion, "SELECT id FROM inv_fisico_prendas WHERE id_subcategoria = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) { echo json_encode(['success' => false, 'msg' => 'Tiene prendas asignadas.']); exit(); }
            $sql = "DELETE FROM inv_fisico_subcategorias WHERE id = $id";
        } else if ($tipo === 'Marcas') {
            $tabla_afectada = 'inv_fisico_marcas';
            $check = mysqli_query($conexion, "SELECT id FROM inv_fisico_prendas WHERE id_marca = $id LIMIT 1");
            if (mysqli_num_rows($check) > 0) { echo json_encode(['success' => false, 'msg' => 'Tiene prendas asignadas.']); exit(); }
            $sql = "DELETE FROM inv_fisico_marcas WHERE id = $id";
        }

        if (mysqli_query($conexion, $sql)) {
            registrar_auditoria($conexion, $id_vendedor_actual, 'ELIMINAR', $tabla_afectada, $id, "Eliminó un registro de mantenimiento en Catálogo de Inventario ($tipo).");
            echo json_encode(['success' => true]);
        } else echo json_encode(['success' => false, 'msg' => 'Error al eliminar.']);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Excepción: ' . $e->getMessage()]);
    exit();
}
?>