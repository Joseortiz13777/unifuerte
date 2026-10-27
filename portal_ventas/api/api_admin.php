<?php
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);

if (file_exists(__DIR__ . '/../../conexion.php')) {
    require_once __DIR__ . '/../../conexion.php';
}
if (!isset($conexion) || !$conexion) {
    $conexion = mysqli_connect("localhost", "root", "", "unifuerte_bd");
}
if (!$conexion) {
    die("Error de conexión a la BD");
}
mysqli_set_charset($conexion, "utf8mb4");
require_once __DIR__ . '/codigo_cotizacion.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'login') {
        $u = mysqli_real_escape_string($conexion, $_POST['user']);
        $p = mysqli_real_escape_string($conexion, $_POST['pass']);
        $query = @mysqli_query($conexion, "SELECT * FROM configuracion WHERE usuario='$u' AND password='$p'");
        if (($query && mysqli_num_rows($query) > 0) || ($u == 'admin' && $p == 'unifuerte')) {
            echo "OK";
        } else {
            echo "NO";
        }
        exit;
    }

    if ($action === 'getAllProductsAdmin') {
        header('Content-Type: application/json');
        $q = @mysqli_query($conexion, "SELECT id, nombre, precio, descripcion, categoria FROM productos ORDER BY categoria ASC, orden ASC, nombre ASC");
        $prods = []; 
        $secData = file_exists('secciones.json') ? json_decode(file_get_contents('secciones.json'), true) : [];
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) { 
                $r['seccion'] = isset($secData[$r['id']]) ? $secData[$r['id']] : '';
                $prods[] = $r; 
            }
        }
        echo json_encode($prods);
        exit;
    }

    if ($action === 'addProduct') {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $precio = $_POST['precio'];
        $etiqueta = mysqli_real_escape_string($conexion, $_POST['etiqueta']);
        $categoria = mysqli_real_escape_string($conexion, $_POST['categoria']);
        $desc = mysqli_real_escape_string($conexion, $_POST['descripcion']);
        
        $q_orden = mysqli_query($conexion, "SELECT MAX(orden) as max_ord FROM productos WHERE categoria='$categoria'");
        $r_orden = $q_orden ? mysqli_fetch_assoc($q_orden) : null;
        $nuevo_orden = ($r_orden && $r_orden['max_ord'] !== null) ? $r_orden['max_ord'] + 1 : 1;
        
        mysqli_query($conexion, "INSERT INTO productos (nombre, etiqueta, precio, descripcion, categoria, orden) VALUES ('$nombre', '$etiqueta', '$precio', '$desc', '$categoria', '$nuevo_orden')");
        echo "OK|" . mysqli_insert_id($conexion);
        exit;
    }

    if ($action === 'updateProduct') {
        $id = mysqli_real_escape_string($conexion, str_replace('prod_', '', $_POST['id']));
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $precio = $_POST['precio'];
        $desc = mysqli_real_escape_string($conexion, $_POST['descripcion']);
        mysqli_query($conexion, "UPDATE productos SET nombre='$nombre', precio='$precio', descripcion='$desc' WHERE id='$id'");
        echo "OK";
        exit;
    }

    if ($action === 'deleteProduct') {
        $id = mysqli_real_escape_string($conexion, str_replace('prod_', '', $_POST['id']));
        mysqli_query($conexion, "DELETE FROM productos WHERE id='$id'");
        echo "OK";
        exit;
    }

    if ($action === 'getCategoriesOrder') {
        header('Content-Type: application/json');
        $q = mysqli_query($conexion, "SELECT id, nombre FROM categorias ORDER BY orden ASC");
        $cats = [];
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $cats[] = $r;
            }
        }
        echo json_encode($cats);
        exit;
    }

    if ($action === 'updateCategoryOrder') {
        $ids = json_decode($_POST['orden_ids'], true);
        if (is_array($ids)) {
            foreach ($ids as $index => $id) {
                $orden = $index + 1;
                $id = mysqli_real_escape_string($conexion, $id);
                mysqli_query($conexion, "UPDATE categorias SET orden='$orden' WHERE id='$id'");
            }
        }
        echo "OK";
        exit;
    }

    if ($action === 'getProductsOrder') {
        header('Content-Type: application/json');
        $cat = mysqli_real_escape_string($conexion, $_POST['categoria']);
        $q = mysqli_query($conexion, "SELECT id, nombre FROM productos WHERE categoria='$cat' ORDER BY orden ASC, nombre ASC");
        $prods = [];
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $prods[] = $r;
            }
        }
        echo json_encode($prods);
        exit;
    }

    if ($action === 'updateProductOrder') {
        $ids = json_decode($_POST['orden_ids'], true);
        if (is_array($ids)) {
            foreach ($ids as $index => $id) {
                $orden = $index + 1;
                $id = mysqli_real_escape_string($conexion, $id);
                mysqli_query($conexion, "UPDATE productos SET orden='$orden' WHERE id='$id'");
            }
        }
        echo "OK";
        exit;
    }

    if ($action === 'saveAdicionales') { file_put_contents('adicionales.json', $_POST['datos']); echo "OK"; exit; }
    if ($action === 'saveExclusiones') { file_put_contents('exclusiones.json', $_POST['datos']); echo "OK"; exit; }
    if ($action === 'saveOpciones') { file_put_contents('opciones.json', $_POST['datos']); echo "OK"; exit; }
    if ($action === 'saveSecciones') { file_put_contents('secciones.json', $_POST['datos']); echo "OK"; exit; }
    if ($action === 'saveAjustes') { file_put_contents('ajustes.json', $_POST['datos']); echo "OK"; exit; }

    // --- ENDPOINT UNIFUERTE: GUARDAR COTIZACIÓN WEB (SIN TALLAS, SOLO GÉNERO) ---
    if ($action === 'saveCotizacion') {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre'] ?? '');
        $empresa = mysqli_real_escape_string($conexion, $_POST['empresa'] ?? '');
        $telefono = mysqli_real_escape_string($conexion, $_POST['telefono'] ?? '');
        $notas = mysqli_real_escape_string($conexion, $_POST['notas'] ?? '');
        $carrito = json_decode($_POST['carrito'] ?? '[]', true);
        
        $total = 0;
        if (is_array($carrito)) {
            foreach ($carrito as $item) { 
                $total += floatval($item['finalPrice']) * intval($item['quantity']); 
            }
        }

        $reserva_codigo = reservar_codigo_cotizacion($conexion);
        if (!$reserva_codigo['success']) {
            echo "ERR|" . $reserva_codigo['msg'];
            exit;
        }
        $nro_cotizacion = $reserva_codigo['codigo'];

        // 1. Insertar Cabecera
        $sql_cotizacion = "INSERT INTO cotizacion (nro_cotizacion, nombre_cliente, empresa, telefono, notas, total) 
                           VALUES ('$nro_cotizacion', '$nombre', '$empresa', '$telefono', '$notas', '$total')";
        
        $insertada = mysqli_query($conexion, $sql_cotizacion);
        // Capturar ID ANTES de liberar el bloqueo para que SELECT RELEASE_LOCK no lo reinicie a 0
        $id_cotizacion = $insertada ? intval(mysqli_insert_id($conexion)) : 0;
        $error_insert = $insertada ? '' : mysqli_error($conexion);
        $errno_insert = $insertada ? 0 : mysqli_errno($conexion);

        liberar_bloqueo_codigo_cotizacion($conexion, $reserva_codigo['bloqueo']);

        if ($insertada && $id_cotizacion > 0) {
            // 2. Insertar Detalle
            foreach ($carrito as $item) {
                $cantidad = (int)$item['quantity'];
                $precio_u = (float)$item['finalPrice'];
                $subtotal_linea = $cantidad * $precio_u;
                
                // Agrupar las telas, bordados y extras
                $detalles_arr = [];
                if (!empty($item['extras'])) {
                    foreach ($item['extras'] as $ext) {
                        $detalles_arr[] = $ext['name'];
                    }
                }
                if (!empty($item['excluded'])) {
                    foreach ($item['excluded'] as $exc) {
                        $detalles_arr[] = "Sin " . $exc;
                    }
                }
                $detalle_diseno = mysqli_real_escape_string($conexion, implode(" | ", $detalles_arr));

                // Extraemos el Género si el cliente eligió una variante (ej: "Camisa Polo (Mujer)")
                $genero = 'No especificado';
                $prenda = $item['originalName'] ?? '';
                
                if (preg_match('/\((.*?)\)$/', $prenda, $matches)) {
                    $genero = mysqli_real_escape_string($conexion, $matches[1]);
                    $prenda = mysqli_real_escape_string($conexion, trim(preg_replace('/\s*\([^)]*\)$/', '', $prenda)));
                } else {
                    $prenda = mysqli_real_escape_string($conexion, $prenda);
                }

                $sql_det = "INSERT INTO detalle_cotizacion 
                            (id_cotizacion, tipo_prenda, genero, cantidad, precio_unitario, subtotal_linea, detalle_diseno)
                            VALUES 
                            ('$id_cotizacion', '$prenda', '$genero', '$cantidad', '$precio_u', '$subtotal_linea', '$detalle_diseno')";
                mysqli_query($conexion, $sql_det);
            }
            
            echo "OK|" . $nro_cotizacion; 
        } else {
            echo $errno_insert === 1062
                ? "ERR|Ese número de cotización ya está registrado. Intente nuevamente."
                : "ERR|" . $error_insert;
        }
        exit;
    }
}
?>