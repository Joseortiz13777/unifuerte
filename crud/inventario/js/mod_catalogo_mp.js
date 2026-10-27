// Función global segura para formato de moneda (ej: 1,000.00)
if (typeof window.formatMoney === 'undefined') {
    window.formatMoney = function(val) {
        return parseFloat(val || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };
}

let inventarioDataGlobal = [];

// ================= 1. CATEGORÍAS MP =================
function cargarCatMp() {
    $('#tbodyCatMp').html('<tr><td colspan="3" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_cat'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(c => {
            html += `<tr>
                <td class="text-muted">${c.id}</td><td class="fw-bold">${c.nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editarCat(${c.id}, '${c.nombre}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="window.eliminar('eliminar_cat', ${c.id}, cargarCatMp)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodyCatMp').html(html || '<tr><td colspan="3" class="text-center text-muted">Sin categorías</td></tr>');
    }, 'json');
}

function editarCat(id, nombre) {
    $('#id_cat').val(id); $('#nombre_cat').val(nombre);
    $('#formCatMp .btn-cancelar').removeClass('d-none');
}

// ================= 2. SUBCATEGORÍAS MP =================
function cargarSubCatMp() {
    $('#tbodySubCatMp').html('<tr><td colspan="4" class="text-center"><span class="spinner-border text-primary"></span></td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_subcat'}, function(res) {
        let html = '';
        if(res.data) res.data.forEach(s => {
            html += `<tr>
                <td class="text-muted">${s.id}</td><td><span class="badge bg-light text-dark border">${s.categoria_padre}</span></td><td class="fw-bold text-primary">${s.nombre}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editarSubCat(${s.id}, ${s.id_mp_categoria}, '${s.nombre}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="window.eliminar('eliminar_subcat', ${s.id}, cargarSubCatMp)"><i class="bi bi-trash"></i></button>
                </td></tr>`;
        });
        $('#tbodySubCatMp').html(html || '<tr><td colspan="4" class="text-center text-muted">Sin subcategorías</td></tr>');
    }, 'json');
}

function editarSubCat(id, id_cat, nombre) {
    $('#id_subcat').val(id); $('#id_mp_categoria_sub').val(id_cat); $('#nombre_subcat').val(nombre);
    $('#formSubCatMp .btn-cancelar').removeClass('d-none');
}

// ================= 3. MATERIA PRIMA (CATÁLOGO AGRUPADO) =================
function cargarMp() {
    $('#tbodyMp').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border text-primary"></span> Buscando en el almacén...</td></tr>');
    $.get('api/api_inventario.php', {accion:'listar_mp'}, function(res) {
        if(res.data) {
            inventarioDataGlobal = res.data;
            renderizarCatalogoMp(inventarioDataGlobal);
        } else {
            $('#tbodyMp').html('<tr><td colspan="8" class="text-center text-muted">No hay materia prima registrada</td></tr>');
        }
    }, 'json');
}

function renderizarCatalogoMp(data) {
    let html = '';
    if(data.length === 0) {
        $('#tbodyMp').html('<tr><td colspan="8" class="text-center text-muted py-4">No se encontraron insumos con esos filtros</td></tr>');
        return;
    }

    data.sort((a, b) => {
        let catA = (a.categoria_nombre || 'Z Otras Categorías').toLowerCase();
        let catB = (b.categoria_nombre || 'Z Otras Categorías').toLowerCase();
        if(catA !== catB) return catA.localeCompare(catB);

        let subA = (a.subcategoria || 'Z Sin Subcategoría').toLowerCase();
        let subB = (b.subcategoria || 'Z Sin Subcategoría').toLowerCase();
        if(subA !== subB) return subA.localeCompare(subB);

        return a.nombre.toLowerCase().localeCompare(b.nombre.toLowerCase());
    });

    let lastGroup = '';
    data.forEach(m => {
        let currentGroup = `${m.categoria_nombre || 'Otras Categorías'} <i class="bi bi-chevron-right text-muted mx-1"></i> <span class="text-dark">${m.subcategoria || 'Sin Subcategoría'}</span>`;
        if (currentGroup !== lastGroup) {
            html += `<tr class="table-secondary" style="border-top: 3px solid #0B4C8C; background-color: #e9ecef !important;">
                        <td colspan="8" class="fw-bold py-2" style="color: #0B4C8C; font-size: 1.1rem;">
                            <i class="bi bi-tags-fill text-warning me-2"></i> ${currentGroup}
                        </td>
                     </tr>`;
            lastGroup = currentGroup;
        }

        let infoFisica = `${m.color || '-'} / ${m.codigo_color || '-'}`;
        let tamano = m.tamano_dimension || '-';
        let fotoUrl = m.foto ? `uploads/${m.foto}` : '../../img/camisa.jpg'; 
        
        let stockActual = parseFloat(m.stock_actual);
        let stockMin = parseFloat(m.stock_minimo);
        
        let badgeStock = '';
        let filaClass = '';

        if (stockActual <= 0) {
            badgeStock = `<span class="badge bg-danger fs-6 w-100"><i class="bi bi-exclamation-triangle"></i> AGOTADO (0)</span>`;
            filaClass = 'table-danger';
        } else if (stockActual <= stockMin) {
            badgeStock = `<span class="badge bg-warning text-dark fs-6 w-100">${window.formatMoney(stockActual)} (Crítico)</span>`;
        } else {
            badgeStock = `<span class="badge bg-success fs-6 w-100">${window.formatMoney(stockActual)}</span>`;
        }

        html += `<tr class="${filaClass}">
            <td>
                <img src="${fotoUrl}" style="width:45px; height:45px; object-fit:cover; border-radius:6px; cursor:pointer; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" 
                     onclick="window.verFotoGrande('${fotoUrl}', '${m.nombre.replace(/'/g, "\\'")}')" title="Ver imagen"
                     onerror="this.src='../../img/camisa.jpg'">
            </td>
            <td>
                <span class="fw-bold" style="color:#0B4C8C; font-size: 1.05rem;">${m.nombre}</span><br>
                <small class="text-muted">ID: ${m.ID}</small>
            </td>
            <td><small>${infoFisica}</small></td>
            <td><small>${tamano}</small></td>
            <td><span class="badge bg-dark">${m.unidad_medida}</span></td>
            <td class="text-center" style="width: 130px;">${badgeStock}</td>
            <td class="fw-bold text-success text-end text-nowrap">${window.formatMoney(m.costo)} $</td>
            <td class="text-center text-nowrap no-print">
                <button class="btn btn-sm btn-outline-warning rounded-pill" onclick='window.editarMp(${JSON.stringify(m).replace(/'/g, "&#39;")})'><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="window.eliminar('eliminar_mp', ${m.ID}, cargarMp)"><i class="bi bi-trash"></i></button>
            </td></tr>`;
    });
    
    $('#tbodyMp').html(html);
}

$(document).on('input change', '#filtroBuscadorMp, #filtroCatMp, #filtroSubCatMp, #filtroStockMp', function() {
    let texto = $('#filtroBuscadorMp').val().toLowerCase();
    let catFiltro = $('#filtroCatMp').val();
    let subCatFiltro = $('#filtroSubCatMp').val();
    let stockFiltro = $('#filtroStockMp').val();

    let filtrados = inventarioDataGlobal.filter(m => {
        let filaTexto = `${m.nombre} ${m.color} ${m.codigo_color} ${m.categoria_nombre} ${m.subcategoria}`.toLowerCase();
        let stockActual = parseFloat(m.stock_actual);
        let stockMin = parseFloat(m.stock_minimo);
        let estadoStockFiltro = 'ok';
        
        if (stockActual <= 0) estadoStockFiltro = 'agotado';
        else if (stockActual <= stockMin) estadoStockFiltro = 'critico';

        let matchTexto = filaTexto.includes(texto);
        let matchCat = (catFiltro === '' || m.categoria_nombre === catFiltro);
        let matchSubCat = (subCatFiltro === '' || m.subcategoria === subCatFiltro);
        let matchStock = (stockFiltro === '' || estadoStockFiltro === stockFiltro);

        return matchTexto && matchCat && matchSubCat && matchStock;
    });

    renderizarCatalogoMp(filtrados);
});

window.generarReportePdf = function() {
    let tablaHtml = document.getElementById('tablaCatalogoMp').outerHTML;
    let catFiltro = $('#filtroCatMp').val() || 'Todas';
    let stockFiltro = $('#filtroStockMp option:selected').text();
    let fecha = new Date().toLocaleDateString('es-ES');

    let printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Reporte de Inventario - Unifuerte</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
                h2 { color: #0B4C8C; margin-bottom: 5px; }
                p { margin-top: 0; color: #666; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                th { background-color: #f8f9fa; border: 1px solid #ddd; padding: 8px; text-align: left; color: #0B4C8C; }
                td { border: 1px solid #ddd; padding: 8px; }
                .table-secondary td { background-color: #e9ecef; font-weight: bold; font-size: 14px; text-transform: uppercase; border-top: 2px solid #0B4C8C; }
                .no-print { display: none !important; }
                .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; display: inline-block; text-align: center; }
                .bg-danger { background-color: #dc3545; color: white; }
                .bg-warning { background-color: #ffc107; color: black; }
                .bg-success { background-color: #198754; color: white; }
                .bg-dark { background-color: #212529; color: white; }
                img { max-width: 40px; border-radius: 4px; }
                .table-danger td { background-color: #f8d7da; }
            </style>
        </head>
        <body>
            <h2>Reporte de Inventario y Stock</h2>
            <p><strong>Fecha:</strong> ${fecha} | <strong>Categoría:</strong> ${catFiltro} | <strong>Estado:</strong> ${stockFiltro}</p>
            ${tablaHtml}
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};

window.abrirModalMp = function() {
    $('#formMp')[0].reset();
    $('#id_mp').val('0');
    $('#mp_foto').val(''); 
    $('#mp_cat').val('').trigger('change');
    $('#modalMp').modal('show');
}

window.editarMp = function(obj) {
    $('#id_mp').val(obj.ID); 
    $('#mp_nombre').val(obj.nombre); 

    let subcatObj = window.listadoSubcategoriasGlobal.find(s => s.id == obj.id_mp_subcategoria);
    if(subcatObj) {
        $('#mp_cat').val(subcatObj.id_mp_categoria).trigger('change');
        setTimeout(() => { $('#mp_subcat').val(obj.id_mp_subcategoria); }, 50);
    } else {
        $('#mp_cat').val('').trigger('change');
    }

    $('#mp_color').val(obj.color); 
    $('#mp_codigo_color').val(obj.codigo_color); 
    $('#mp_textura').val(obj.textura); 
    $('#mp_tamano').val(obj.tamano_dimension); 
    $('#mp_origen').val(obj.origen_procedencia); 
    $('#mp_comp').val(obj.composicion); 
    $('#mp_desc').val(obj.descripcion); 
    $('#mp_und').val(obj.unidad_medida); 
    $('#mp_costo').val(obj.costo);
    $('#mp_stock').val(obj.stock_actual); 
    $('#mp_stock_min').val(obj.stock_minimo); 
    $('#mp_foto').val(''); 
    $('#modalMp').modal('show');
}