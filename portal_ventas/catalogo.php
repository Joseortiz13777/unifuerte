<?php
// Conexión XAMPP Local Unifuerte
$conexion = mysqli_connect("localhost", "root", "", "unifuerte_bd");
if (!$conexion) { die("Error de conexion a la base de datos: " . mysqli_connect_error()); }
mysqli_set_charset($conexion, "utf8mb4");

$cat_query = @mysqli_query($conexion, "SELECT nombre FROM categorias ORDER BY orden ASC");
$categorias_final = [];
if($cat_query) { while($c = mysqli_fetch_assoc($cat_query)) { $categorias_final[] = $c['nombre']; } }

$secciones_data = file_exists('secciones.json') ? json_decode(file_get_contents('secciones.json'), true) : [];
if (!$secciones_data) $secciones_data = [];

$ajustes_data = file_exists('ajustes.json') ? file_get_contents('ajustes.json') : '{"wa_pedidos": "584120000000"}';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="description" content="Unifuerte - Catálogo de Fábrica Textil">
    <title>Unifuerte - Cotizaciones Web</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/diseno.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <script>
        window.agotadosData = <?php echo file_exists('agotados.json') ? file_get_contents('agotados.json') : '{"productos":[],"adicionales":[],"sabores":[]}'; ?>;
        window.adicionalesData = <?php echo file_exists('adicionales.json') ? file_get_contents('adicionales.json') : '[]'; ?>;
        window.exclusionesData = <?php echo file_exists('exclusiones.json') ? file_get_contents('exclusiones.json') : '{}'; ?>;
        window.opcionesData = <?php echo file_exists('opciones.json') ? file_get_contents('opciones.json') : '{}'; ?>;
        window.seccionesData = <?php echo file_exists('secciones.json') ? file_get_contents('secciones.json') : '{}'; ?>;
        window.ajustesData = <?php echo $ajustes_data; ?>;
    </script>
</head>

<body>
    <a class="floating-cart d-none d-lg-flex btn-ver-carrito" href="javascript:void(0);">
        <i class="bi-calculator-fill"></i><span>Ver Cotización</span>
        <span id="floatingCartCount" class="badge rounded-pill bg-white text-danger">0</span>
    </a>

    <nav class="bottom-nav d-lg-none shadow-lg">
        <a href="#section_1" class="bottom-nav-item click-scroll active" id="navHome">
            <i class="bi-house-door-fill"></i><span>Catálogo</span>
        </a>
        <a href="javascript:void(0);" class="bottom-nav-item btn-ver-carrito">
            <span class="cart-badge" id="bottomCartCount">0</span>
            <i class="bi-calculator-fill"></i><span>Cotizar</span>
        </a>
    </nav>

    <main>
        <div class="bg-white sticky-top shadow-sm" style="z-index: 1020;" id="section_1">
            <div class="container py-2 d-flex flex-column align-items-center">
                <a href="index.php" class="mb-2 mt-1">
                    <img src="../img/unifuerte.jpg" alt="Unifuerte Logo" style="max-height: 60px; width: auto; object-fit: contain;">
                </a>
                <div class="search-container w-100 px-2 mb-2" style="max-width: 600px;">
                    <i class="bi-search"></i>
                    <input type="text" id="searchMenu" class="form-control form-control-sm shadow-none border-1" placeholder="Buscar prendas...">
                </div>
                <div class="category-scroll w-100 px-2 mt-1">
                    <?php 
                    $firstCat = true;
                    foreach($categorias_final as $catName) { 
                        $catId = 'categoria_' . substr(md5($catName), 0, 8); 
                        $activeClass = $firstCat ? 'active' : '';
                    ?>
                        <a href="javascript:void(0);" class="category-chip filter-chip <?php echo $activeClass; ?>" data-target="<?php echo $catId; ?>"><?php echo htmlspecialchars($catName); ?></a>
                    <?php $firstCat = false; } ?>
                </div>
            </div>
        </div>

        <section class="py-3" id="section_menu">
            <div class="container" style="max-width: 900px;">
                <?php 
                $firstCat = true;
                foreach($categorias_final as $catName) { 
                    $catId = 'categoria_' . substr(md5($catName), 0, 8);
                    $displayStyle = 'block';
                    $firstCat = false;
                ?>
                <div class="mb-4 menu-category-wrap" id="<?php echo $catId; ?>" style="scroll-margin-top: 140px; display: <?php echo $displayStyle; ?>;">
                    <h5 class="fw-bold text-dark px-2 mb-2 border-bottom pb-1" style="font-size: 1.2rem; color: #0B4C8C !important;"><?php echo htmlspecialchars($catName); ?></h5>
                    
                    <?php
                    $q = @mysqli_query($conexion, "SELECT * FROM productos WHERE categoria = '".mysqli_real_escape_string($conexion, $catName)."' ORDER BY orden ASC, nombre ASC");
                    $productos_cat = ['' => []]; 
                    if($q){
                        while ($prod = mysqli_fetch_assoc($q)) {
                            $sec = isset($secciones_data[$prod['id']]) ? trim($secciones_data[$prod['id']]) : '';
                            if (!isset($productos_cat[$sec])) { $productos_cat[$sec] = []; }
                            $productos_cat[$sec][] = $prod;
                        }
                    }

                    foreach($productos_cat as $nombre_seccion => $lista_prods) {
                        if (empty($lista_prods)) continue;
                        echo '<div class="subcategory-wrap">';
                        $is_collapsible = false; $header_title = $nombre_seccion;
                        
                        if ($nombre_seccion === '') {
                            if (count($productos_cat) > 1) { $header_title = 'Modelos Generales'; $is_collapsible = true; } else { $is_collapsible = false; }
                        } else { $is_collapsible = true; }
                        
                        if($is_collapsible) {
                            echo '<h6 class="fw-bold text-danger px-2 mt-3 mb-2 subcat-header d-flex align-items-center justify-content-between" style="font-size: 0.9rem; text-transform: uppercase; cursor: pointer;">';
                            echo '<span><i class="bi-bookmark-fill me-2"></i> '.htmlspecialchars($header_title).'</span>';
                            echo '<i class="bi-chevron-down toggle-icon" style="transition: transform 0.3s;"></i></h6>';
                            echo '<div class="menu-grid px-2 mb-3 subcategory-content" style="display: none;">';
                        } else { echo '<div class="menu-grid px-2 mb-3 subcategory-content">'; }

                        foreach($lista_prods as $prod) {
                        ?>
                            <div class="product-card" id="prod_<?php echo $prod['id']; ?>" data-desc="<?php echo htmlspecialchars($prod['descripcion']); ?>">
                                <div>
                                    <?php if(!empty($prod['foto'])): ?>
                                        <img src="../img/<?php echo htmlspecialchars($prod['foto']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" style="width: 100%; height: 130px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
                                    <?php endif; ?>

                                    <div class="item-name" style="font-size: 0.95rem;"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                                    <?php if(!empty($prod['etiqueta'])) { echo '<span class="badge bg-danger mb-1" style="font-size:0.6rem;">'.htmlspecialchars($prod['etiqueta']).'</span>'; } ?>
                                    <?php if(!empty($prod['descripcion'])): ?>
                                        <div class="item-desc" style="font-size: 0.75rem;"><?php echo htmlspecialchars($prod['descripcion']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <div class="item-price mb-2" style="font-size: 0.9rem;">$<?php echo rtrim(rtrim(number_format($prod['precio'], 2, '.', ''), '0'), '.'); ?></div>
                                    <button class="btn-add add-to-cart-btn btn-sm" data-type="textil">Agregar</button>
                                </div>
                            </div>
                        <?php } echo '</div></div>'; } ?>
                </div>
                <?php } ?>
            </div>
        </section>

        <section class="py-4 bg-white" id="section_pedido" style="border-top: 1px solid #eaeaea;">
            <div class="container" style="max-width: 500px;"> <h5 class="fw-bold mb-2 text-center" style="color: #0B4C8C;">SOLICITUD DE COTIZACIÓN</h5>
                <p class="text-center small text-muted mb-4" style="font-size: 0.8rem;">Generaremos un N° de cotización para tu Orden.</p>
                <form id="pedidoForm" class="formulario-coqueto">
                    <div class="mb-3 px-2">
                        <label class="form-label fw-bold small text-muted">Nombre del Responsable *</label>
                        <input type="text" id="name" class="form-control form-control-sm" placeholder="Tu Nombre">
                    </div>
                    <div class="mb-3 px-2">
                        <label class="form-label fw-bold small text-muted">Empresa / Organización</label>
                        <input type="text" id="empresa" class="form-control form-control-sm" placeholder="Ej: Industrias C.A.">
                    </div>
                    <div class="mb-3 px-2">
                        <label class="form-label fw-bold small text-muted">Teléfono / WhatsApp *</label>
                        <input type="tel" id="phone" class="form-control form-control-sm" placeholder="Ej: 0414-1234567">
                    </div>
                    <div class="mb-4 px-2">
                        <label class="form-label fw-bold small text-muted">Notas de la Cotización</label>
                        <textarea id="sugerenciasPedido" class="form-control form-control-sm" rows="2" placeholder="Detalles adicionales..."></textarea>
                    </div>

                    <div class="px-2 mb-4">
                        <div class="p-3 rounded-4 shadow-sm btn-ver-carrito" style="background-color: #0B4C8C; color: white; cursor: pointer; transition: transform 0.2s;">
                            <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-2 mb-2">
                                <h6 class="fw-bold m-0" style="font-size: 0.9rem;">RESUMEN DE PRENDAS</h6>
                                <span class="badge bg-light text-primary" style="font-size: 0.7rem;"><i class="bi-pencil-square"></i> Editar</span>
                            </div>
                            <ul id="invoiceList" class="list-unstyled mb-2 text-white" style="font-size: 0.8rem; opacity: 0.9;">
                                <li>No hay prendas agregadas aún.</li>
                            </ul>
                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light">
                                <span class="fw-bold" style="font-size: 0.9rem;">Total Referencial</span> 
                                <span class="fw-bold text-white" id="invoiceTotal" style="font-size: 1.2rem;">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-2 pb-4">
                        <button type="submit" id="realizarPedidoBtn" class="btn btn-danger w-100 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.95rem;">
                            Guardar Cotización <i class="bi-whatsapp ms-1"></i>
                        </button>
                    </div>
                    <input type="hidden" id="detallePedidoInput">
                </form>
            </div>
        </section>
    </main>
    
    <div class="modal fade" id="modalCantidad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header border-bottom-0 bg-light" style="border-radius: 15px 15px 0 0; padding: 12px 16px;">
                    <h6 class="modal-title fw-bold text-primary m-0"><i class="bi-cart-plus me-1"></i> Añadir prenda</h6>
                    <button type="button" class="btn-close shadow-none" style="font-size: 0.7rem;" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <h6 id="modalProdName" class="mb-1 fw-bold text-dark" style="font-size: 0.95rem;">Producto</h6>
                    <p id="modalProdPrice" class="text-muted mb-3 fw-bold" style="font-size: 0.85rem;">$0.00</p>
                    
                    <div class="d-flex justify-content-center align-items-center mb-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle fw-bold d-flex align-items-center justify-content-center" id="btnMinus" style="width: 35px; height: 35px;">-</button>
                        <input type="number" id="inputCantidad" value="1" min="1" class="form-control form-control-sm text-center mx-2 fw-bold border-0 bg-light" style="width: 60px; font-size: 1.1rem; border-radius: 8px;">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle fw-bold d-flex align-items-center justify-content-center" id="btnPlus" style="width: 35px; height: 35px;">+</button>
                    </div>
                </div>
                <div class="modal-footer border-top-0 justify-content-center p-2 pb-3">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm" id="btnConfirmAdd">
                        Confirmar <i class="bi-check2 ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCarrito" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header bg-light border-bottom-0" style="border-radius: 15px 15px 0 0; padding: 15px;">
                    <h6 class="modal-title fw-bold text-primary m-0"><i class="bi-list-ul me-2"></i>Editar Prendas</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-2">
                    <div id="carritoEmptyMsg" class="text-center text-muted py-4" style="display:none; font-size: 0.85rem;">
                        No hay prendas en tu cotización.
                    </div>
                    <ul id="carritoEditList" class="list-group list-group-flush">
                        </ul>
                </div>
                <div class="modal-footer border-top-0 bg-light rounded-bottom p-3">
                    <div class="w-100 d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-muted" style="font-size: 0.9rem;">Total Estimado:</span>
                        <span id="carritoEditTotal" class="fw-bold text-primary fs-5">$0.00</span>
                    </div>
                    <button type="button" class="btn btn-danger w-100 rounded-pill btn-sm fw-bold shadow-sm" data-bs-dismiss="modal">
                        Aceptar y Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="vistaGestion" style="display: none; padding: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary fw-bold">Gestión de Catálogo</h2>
        <button class="btn btn-primary" id="btnNuevoProducto">+ Agregar Prenda</button>
    </div>

    <div class="table-responsive bg-white rounded shadow-sm p-3">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Descripción</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaProductosBody">
                </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalProductoTitle">Nuevo Producto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formProducto">
          <div class="modal-body">
              <input type="hidden" id="prod_id" name="id_producto" value="0">
              <input type="hidden" name="accion" value="guardar">
              
              <div class="mb-3">
                  <label class="form-label fw-bold">Nombre de la Prenda</label>
                  <input type="text" class="form-control" id="prod_nombre" name="nombre" required>
              </div>
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold">Categoría</label>
                      <select class="form-select" id="prod_categoria" name="categoria" required>
                          <option value="Camisas">Camisas</option>
                          <option value="Pantalones">Pantalones</option>
                          <option value="Gorras">Gorras</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label fw-bold">Precio ($)</label>
                      <input type="number" step="0.01" class="form-control" id="prod_precio" name="precio" required>
                  </div>
              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold">Descripción breve</label>
                  <textarea class="form-control" id="prod_descripcion" name="descripcion" rows="2"></textarea>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar Producto</button>
          </div>
      </form>
    </div>
  </div>
</div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/logica.js?v=<?php echo time(); ?>"></script>
</body>
</html>