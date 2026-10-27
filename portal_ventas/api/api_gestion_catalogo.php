<?php
session_start();
require_once '../../conexion.php';

mysqli_query($conexion, "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS orden INT DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE subcategorias ADD COLUMN IF NOT EXISTS orden INT DEFAULT 0");
mysqli_query($conexion, "ALTER TABLE productos ADD COLUMN IF NOT EXISTS orden INT DEFAULT 0");

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

if ($accion == 'listar_productos_venta') {
    header('Content-Type: application/json');
    // ORDEN ABSOLUTO POR SUBCATEGORIA PARA NO MEZCLAR CHEMIS CON PANTALONES EN EL CATALOGO
    $sql = "SELECT p.*, s.nombre as subcategoria, c.nombre as categoria 
            FROM productos p 
            LEFT JOIN subcategorias s ON p.id_subcategoria = s.id 
            LEFT JOIN categorias c ON s.id_categoria = c.id
            ORDER BY c.orden ASC, c.id ASC, s.orden ASC, s.id ASC, p.orden ASC, p.id ASC";
    $query = mysqli_query($conexion, $sql);
    $data = [];
    if($query){ while($row = mysqli_fetch_assoc($query)){ $data[] = $row; } }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($accion == 'eliminar') {
    header('Content-Type: application/json');
    $entidad = $_POST['entidad'];
    $id = intval($_POST['id']);
    $tabla = strtolower($entidad) . 's'; 

    if ($entidad == 'Producto') {
        $q = mysqli_query($conexion, "SELECT foto FROM productos WHERE id = $id");
        if ($r = mysqli_fetch_assoc($q) && !empty($r['foto'])) {
            $ruta = "../../images/uploads/" . $r['foto'];
            if(file_exists($ruta)) unlink($ruta); 
        }
    }

    if(mysqli_query($conexion, "DELETE FROM $tabla WHERE id = $id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => mysqli_error($conexion)]);
    }
    exit;
}

if ($accion == 'ordenar') {
    header('Content-Type: application/json');
    $entidad = $_POST['entidad'];
    $id = intval($_POST['id']);
    $dir = $_POST['direccion']; 
    $tabla = strtolower($entidad) . 's';

    $q1 = mysqli_query($conexion, "SELECT * FROM $tabla WHERE id=$id");
    $r1 = mysqli_fetch_assoc($q1);
    $orden_actual = $r1['orden'];

    $filtro_padre = "";
    if ($entidad == 'Producto') {
        $fk = intval($r1['id_subcategoria']);
        $filtro_padre = "AND id_subcategoria = $fk";
    } elseif ($entidad == 'Subcategoria') {
        $fk = intval($r1['id_categoria']);
        $filtro_padre = "AND id_categoria = $fk";
    }

    if ($dir == 'up') {
        $q2 = mysqli_query($conexion, "SELECT id, orden FROM $tabla WHERE orden < $orden_actual $filtro_padre ORDER BY orden DESC LIMIT 1");
    } else {
        $q2 = mysqli_query($conexion, "SELECT id, orden FROM $tabla WHERE orden > $orden_actual $filtro_padre ORDER BY orden ASC LIMIT 1");
    }

    if ($r2 = mysqli_fetch_assoc($q2)) {
        $id_swap = $r2['id'];
        $orden_swap = $r2['orden'];
        mysqli_query($conexion, "UPDATE $tabla SET orden=$orden_swap WHERE id=$id");
        mysqli_query($conexion, "UPDATE $tabla SET orden=$orden_actual WHERE id=$id_swap");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

if ($accion == 'guardar') {
    header('Content-Type: application/json');
    $entidad = $_POST['entidad'];
    $id_edit = intval($_POST['id_edit'] ?? 0);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    
    if ($entidad == 'Categoria') {
        if($id_edit > 0) $sql = "UPDATE categorias SET nombre='$nombre' WHERE id=$id_edit";
        else {
            $max = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT MAX(orden) as m FROM categorias"));
            $ord = intval($max['m']) + 1;
            $sql = "INSERT INTO categorias (nombre, orden) VALUES ('$nombre', $ord)";
        }
        mysqli_query($conexion, $sql);
    } 
    elseif ($entidad == 'Subcategoria') {
        $id_cat = intval($_POST['id_categoria']);
        if($id_edit > 0) $sql = "UPDATE subcategorias SET nombre='$nombre', id_categoria=$id_cat WHERE id=$id_edit";
        else {
            $max = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT MAX(orden) as m FROM subcategorias WHERE id_categoria=$id_cat"));
            $ord = intval($max['m']) + 1;
            $sql = "INSERT INTO subcategorias (nombre, id_categoria, orden) VALUES ('$nombre', $id_cat, $ord)";
        }
        mysqli_query($conexion, $sql);
    } 
    elseif ($entidad == 'Producto') {
        $id_sub = intval($_POST['id_subcategoria']);
        $precio = floatval($_POST['precio']);
        
        $foto_sql = "";
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $dir_uploads = "../../images/uploads/";
            if (!is_dir($dir_uploads)) mkdir($dir_uploads, 0777, true); 

            if($id_edit > 0){
                $q = mysqli_query($conexion, "SELECT foto FROM productos WHERE id = $id_edit");
                if ($r = mysqli_fetch_assoc($q) && !empty($r['foto'])) {
                    if (file_exists($dir_uploads . $r['foto'])) unlink($dir_uploads . $r['foto']);
                }
            }

            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = "prod_" . time() . "_" . rand(100,999) . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $dir_uploads . $nombre_archivo);
            $foto_sql = ", foto='$nombre_archivo'";
        }

        if($id_edit > 0) {
            $sql = "UPDATE productos SET id_subcategoria=$id_sub, nombre='$nombre', precio=$precio $foto_sql WHERE id=$id_edit";
        } else {
            $max = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT MAX(orden) as m FROM productos WHERE id_subcategoria=$id_sub"));
            $ord = intval($max['m']) + 1;
            $n_foto = isset($nombre_archivo) ? "'$nombre_archivo'" : "NULL";
            $sql = "INSERT INTO productos (id_subcategoria, nombre, precio, orden, foto) VALUES ($id_sub, '$nombre', $precio, $ord, $n_foto)";
        }
        mysqli_query($conexion, $sql);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($accion == 'ui_categoria') {
    $res = mysqli_query($conexion, "SELECT * FROM categorias ORDER BY orden ASC");
    $html = '<form id="formCrudCatalogo" class="mb-4 d-flex gap-2 bg-white p-2 rounded shadow-sm">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="entidad" value="Categoria">
                <input type="hidden" name="id_edit" value="0">
                <input type="text" name="nombre" class="form-control" placeholder="Nombre Categoría" required>
                <button type="submit" class="btn btn-primary fw-bold btn-submit-crud">Guardar</button>
                <button type="button" class="btn btn-secondary d-none fw-bold" id="btnCancelarCrud">Cancelar</button>
             </form>
             <table class="table table-bordered bg-white table-sm shadow-sm rounded">
                <thead class="table-light"><tr><th style="width: 50px;">Orden</th><th>Categoría Maestra</th><th class="text-center">Acciones</th></tr></thead><tbody>';
    while($r = mysqli_fetch_assoc($res)) {
        $html .= "<tr class='fila-gestion'>
                    <td class='text-center'>
                        <button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Categoria' data-id='{$r['id']}' data-dir='up'><i class='bi bi-caret-up-fill'></i></button>
                        <br><button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Categoria' data-id='{$r['id']}' data-dir='down'><i class='bi bi-caret-down-fill'></i></button>
                    </td>
                    <td class='fw-bold align-middle'>{$r['nombre']}</td>
                    <td class='text-center align-middle'>
                        <button class='btn btn-sm btn-outline-secondary btn-editar-crud' data-id='{$r['id']}' data-nombre='{$r['nombre']}'><i class='bi bi-pencil'></i></button>
                        <button class='btn btn-sm btn-outline-danger btn-eliminar-crud' data-entidad='Categoria' data-id='{$r['id']}'><i class='bi bi-trash'></i></button>
                    </td>
                  </tr>";
    }
    echo $html . '</tbody></table>';
    exit;
}

if ($accion == 'ui_subcategoria') {
    $cats = mysqli_query($conexion, "SELECT id, nombre FROM categorias ORDER BY orden ASC");
    $opciones = '';
    while($c = mysqli_fetch_assoc($cats)) $opciones .= "<option value='{$c['id']}'>{$c['nombre']}</option>";

    $res = mysqli_query($conexion, "SELECT s.*, c.nombre as cat_padre FROM subcategorias s JOIN categorias c ON s.id_categoria = c.id ORDER BY c.orden ASC, s.orden ASC");
    $html = '<form id="formCrudCatalogo" class="mb-4 row g-2 bg-white p-3 rounded shadow-sm">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="entidad" value="Subcategoria">
                <input type="hidden" name="id_edit" value="0">
                <div class="col-md-4"><select name="id_categoria" class="form-select" required><option value="">Perteneciente a...</option>'.$opciones.'</select></div>
                <div class="col-md-5"><input type="text" name="nombre" class="form-control" placeholder="Nombre Subcategoría" required></div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100 fw-bold btn-submit-crud">Guardar</button>
                    <button type="button" class="btn btn-secondary d-none fw-bold" id="btnCancelarCrud">Cancelar</button>
                </div>
             </form>
             <table class="table table-bordered bg-white table-sm shadow-sm rounded">
                <thead class="table-light"><tr><th style="width: 50px;">Orden</th><th>Subcategoría</th><th>Pertenece a</th><th class="text-center">Acciones</th></tr></thead><tbody>';
    while($r = mysqli_fetch_assoc($res)) {
        $html .= "<tr class='fila-gestion'>
                    <td class='text-center'>
                        <button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Subcategoria' data-id='{$r['id']}' data-dir='up'><i class='bi bi-caret-up-fill'></i></button>
                        <br><button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Subcategoria' data-id='{$r['id']}' data-dir='down'><i class='bi bi-caret-down-fill'></i></button>
                    </td>
                    <td class='fw-bold align-middle'>{$r['nombre']}</td>
                    <td class='align-middle'><span class='badge bg-secondary'>{$r['cat_padre']}</span></td>
                    <td class='text-center align-middle'>
                        <button class='btn btn-sm btn-outline-secondary btn-editar-crud' data-id='{$r['id']}' data-nombre='{$r['nombre']}' data-fk='{$r['id_categoria']}'><i class='bi bi-pencil'></i></button>
                        <button class='btn btn-sm btn-outline-danger btn-eliminar-crud' data-entidad='Subcategoria' data-id='{$r['id']}'><i class='bi bi-trash'></i></button>
                    </td>
                  </tr>";
    }
    echo $html . '</tbody></table>';
    exit;
}

if ($accion == 'ui_producto') {
    $cats_q = mysqli_query($conexion, "SELECT id, nombre FROM categorias ORDER BY orden ASC");
    $cats_options = '<option value="">(Todas las Categorías)</option>';
    while($c = mysqli_fetch_assoc($cats_q)) {
        $cats_options .= "<option value='{$c['id']}'>{$c['nombre']}</option>";
    }

    $subs_q = mysqli_query($conexion, "SELECT id, id_categoria, nombre FROM subcategorias ORDER BY orden ASC");
    $subs_options = '<option value="">(Todas las Subcategorías)</option>';
    while($s = mysqli_fetch_assoc($subs_q)) {
        $subs_options .= "<option value='{$s['id']}' data-padre='{$s['id_categoria']}'>{$s['nombre']}</option>";
    }

    $subs = mysqli_query($conexion, "SELECT s.id, s.nombre, c.nombre as cat FROM subcategorias s JOIN categorias c ON s.id_categoria = c.id ORDER BY c.orden ASC, s.orden ASC");
    $opciones = '';
    while($s = mysqli_fetch_assoc($subs)) $opciones .= "<option value='{$s['id']}'>[{$s['cat']}] - {$s['nombre']}</option>";

    $res = mysqli_query($conexion, "SELECT p.*, s.nombre as sub_padre, s.id_categoria FROM productos p JOIN subcategorias s ON p.id_subcategoria = s.id ORDER BY s.id_categoria ASC, s.orden ASC, p.orden ASC");
    
    $html = '
             <div class="row mb-3 bg-white p-2 rounded shadow-sm border border-info align-items-center">
                <div class="col-md-2 text-center text-info fw-bold"><i class="bi bi-funnel-fill"></i> Filtro de Orden:</div>
                <div class="col-md-5">
                    <select id="filtro_cat_gestion" class="form-select form-select-sm border-info fw-bold">'.$cats_options.'</select>
                </div>
                <div class="col-md-5">
                    <select id="filtro_subcat_gestion" class="form-select form-select-sm border-info fw-bold">'.$subs_options.'</select>
                </div>
             </div>

             <form id="formCrudCatalogo" class="mb-4 border p-3 bg-white shadow-sm rounded" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="entidad" value="Producto">
                <input type="hidden" name="id_edit" value="0">
                <div class="row g-2">
                    <div class="col-md-3"><label class="small fw-bold">Subcategoría</label><select name="id_subcategoria" class="form-select" required><option value="">Seleccione...</option>'.$opciones.'</select></div>
                    <div class="col-md-4"><label class="small fw-bold">Nombre Prenda</label><input type="text" name="nombre" class="form-control" placeholder="Ej: Camisa Ovejita" required></div>
                    <div class="col-md-2"><label class="small fw-bold">Precio ($)</label><input type="number" step="0.01" name="precio" class="form-control" placeholder="15.00" required></div>
                    <div class="col-md-3"><label class="small fw-bold">Subir/Cambiar Foto</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-secondary d-none fw-bold" id="btnCancelarCrud">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 btn-submit-crud">Guardar Producto</button>
                </div>
             </form>
             <div class="table-responsive">
             <table class="table table-bordered bg-white table-sm shadow-sm rounded" style="font-size: 0.9rem;">
                <thead class="table-light"><tr><th style="width: 50px;">Orden</th><th>Foto</th><th>Prenda</th><th>Subcategoría</th><th>Precio</th><th class="text-center">Acciones</th></tr></thead><tbody>';
    while($r = mysqli_fetch_assoc($res)) {
        $precioFormat = number_format($r['precio'], 2, ',', '.');
        $img = $r['foto'] ? "<img src='../images/uploads/{$r['foto']}' style='height:40px; border-radius:5px;'>" : "<i class='bi bi-image text-muted'></i>";
        
        $html .= "<tr class='fila-gestion fila-prod' data-idcat='{$r['id_categoria']}' data-idsubcat='{$r['id_subcategoria']}'>
                    <td class='text-center align-middle'>
                        <button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Producto' data-id='{$r['id']}' data-dir='up'><i class='bi bi-caret-up-fill'></i></button>
                        <br><button class='btn btn-sm text-muted p-0 btn-ordenar-crud' data-entidad='Producto' data-id='{$r['id']}' data-dir='down'><i class='bi bi-caret-down-fill'></i></button>
                    </td>
                    <td class='text-center align-middle'>{$img}</td>
                    <td class='fw-bold align-middle'>{$r['nombre']}</td>
                    <td class='align-middle'><span class='badge bg-dark'>{$r['sub_padre']}</span></td>
                    <td class='text-primary fw-bold align-middle'>$ {$precioFormat}</td>
                    <td class='text-center align-middle text-nowrap'>
                        <button class='btn btn-sm btn-outline-secondary btn-editar-crud' data-id='{$r['id']}' data-nombre='{$r['nombre']}' data-fk='{$r['id_subcategoria']}' data-precio='{$r['precio']}'><i class='bi bi-pencil'></i> Editar</button>
                        <button class='btn btn-sm btn-outline-danger btn-eliminar-crud' data-entidad='Producto' data-id='{$r['id']}'><i class='bi bi-trash'></i></button>
                    </td>
                  </tr>";
    }
    echo $html . '</tbody></table></div>';
    exit;
}
?>