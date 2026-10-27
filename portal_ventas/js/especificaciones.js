let archivosSpecsMemoria = [];
let enviarLogisticaDespuesDeGuardarSpecs = false;
window.forzarEnvioMasterSinPago = false;
window.datosCxpMasterPendiente = null;
window.fichaConfeccionMemoria = {};
window.infoCotizacionSpecActual = {};
window.fotosGuardadasSpecActual = [];
window.prendasFisicasSpecActual = [];

// ================= ESTILOS DINÁMICOS PARA LA ALERTA DE WHATSAPP =================
if ($('#animacion-wa').length === 0) {
    $('head').append('<style id="animacion-wa">@keyframes pulseWA { 0% { transform: scale(1); } 50% { transform: scale(1.15); box-shadow: 0 0 10px rgba(220,53,69,0.7); } 100% { transform: scale(1); } } .pulse-wa { animation: pulseWA 1.5s infinite; }</style>');
}

// ================= CAMBIAR MODO DE VISTA EN EL MODAL (DIVIDIDO / FORMULARIO / PREVIEW) =================
window.cambiarModoVistaSpec = function(modo) {
    $('#btnModoDivididoSpec, #btnModoFormularioSpec, #btnModoPreviewSpec').removeClass('btn-light active').addClass('btn-outline-light');
    if (modo === 'formulario') {
        $('#btnModoFormularioSpec').removeClass('btn-outline-light').addClass('btn-light active');
        $('#colFormularioSpecs').removeClass('d-none col-lg-7').addClass('col-lg-12');
        $('#colPreviewSpecs').addClass('d-none');
    } else if (modo === 'preview') {
        $('#btnModoPreviewSpec').removeClass('btn-outline-light').addClass('btn-light active');
        $('#colFormularioSpecs').addClass('d-none');
        $('#colPreviewSpecs').removeClass('d-none col-lg-5').addClass('col-lg-12');
        window.actualizarVistaPreviaFichaConfeccion();
    } else {
        $('#btnModoDivididoSpec').removeClass('btn-outline-light').addClass('btn-light active');
        $('#colFormularioSpecs').removeClass('d-none col-lg-12').addClass('col-lg-7');
        $('#colPreviewSpecs').removeClass('d-none col-lg-12').addClass('col-lg-5');
        window.actualizarVistaPreviaFichaConfeccion();
    }
};

// ================= RENDERIZAR LISTA DE FOTOS Y SELECTORES FRENTE/ESPALDA POR CADA PRENDA =================
window.construirCatalogoImagenesActual = function() {
    let todasImagenes = [];
    (window.fotosGuardadasSpecActual || []).forEach((f, idx) => {
        const ext = f.split('.').pop().toLowerCase();
        if (ext !== 'pdf') {
            todasImagenes.push({
                valor: 'guardada:' + f,
                etiqueta: `Guardada #${idx + 1}: ${f}`,
                url: `../images/ordenes/${f}`
            });
        }
    });

    archivosSpecsMemoria.forEach((file, idx) => {
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'pdf') {
            if (!file._objectUrl) {
                file._objectUrl = URL.createObjectURL(file);
            }
            todasImagenes.push({
                valor: 'nueva:' + idx,
                etiqueta: `Nueva #${idx + 1}: ${file.name}`,
                url: file._objectUrl
            });
        }
    });

    window.catalogoImagenesSpecActual = todasImagenes;
    return todasImagenes;
};

window.actualizarSelectoresFrenteEspalda = function() {
    const todasImagenes = window.construirCatalogoImagenesActual();
    $('#selectores_frente_espalda_specs').hide(); // Usamos los selectores individuales dentro de cada Ficha de Producto

    $('.fp-sel-foto-frente').each(function() {
        const $sel = $(this);
        const valPrev = $sel.attr('data-saved') || $sel.val() || '';
        let opts = '<option value="">Automática (Primera imagen)</option>';
        todasImagenes.forEach(img => {
            opts += `<option value="${img.valor}">${img.etiqueta}</option>`;
        });
        $sel.html(opts);
        if (valPrev && $sel.find(`option[value="${valPrev}"]`).length > 0) {
            $sel.val(valPrev);
        }
        $sel.removeAttr('data-saved');
    });

    $('.fp-sel-foto-espalda').each(function() {
        const $sel = $(this);
        const valPrev = $sel.attr('data-saved') || $sel.val() || '';
        let opts = '<option value="">Automática (Segunda imagen)</option>';
        todasImagenes.forEach(img => {
            opts += `<option value="${img.valor}">${img.etiqueta}</option>`;
        });
        opts += '<option value="ninguna">Sin imagen de espalda</option>';
        $sel.html(opts);
        if (valPrev && $sel.find(`option[value="${valPrev}"]`).length > 0) {
            $sel.val(valPrev);
        }
        $sel.removeAttr('data-saved');
    });
};

window.renderizarListaFotosSpecs = function() {
    if($('#lista_fotos_seleccionadas_specs').length === 0) {
        $('#fotos_diseno_specs').after('<div id="lista_fotos_seleccionadas_specs"></div>');
    }
    let listHtml = '';
    if (archivosSpecsMemoria.length > 0) {
        listHtml += '<div class="mt-2 p-2 border border-primary rounded bg-white shadow-sm">';
        listHtml += '<strong class="small text-primary"><i class="bi bi-paperclip"></i> Nuevos archivos en cola para subir ('+archivosSpecsMemoria.length+'):</strong>';
        listHtml += '<div class="d-flex flex-wrap gap-2 mt-2">';
        archivosSpecsMemoria.forEach((file, index) => {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!file._objectUrl && ext !== 'pdf') {
                file._objectUrl = URL.createObjectURL(file);
            }
            const thumb = ext === 'pdf'
                ? `<div class="d-flex align-items-center justify-content-center bg-light border rounded" style="width:55px;height:55px;"><i class="bi bi-file-pdf-fill text-danger fs-4"></i></div>`
                : `<img src="${file._objectUrl}" style="width:55px;height:55px;object-fit:cover;border-radius:4px;border:1px solid #0B4C8C;">`;

            listHtml += `<div class="d-flex align-items-center border rounded p-1 bg-light gap-2" style="max-width: 240px;">
                ${thumb}
                <div class="text-truncate small" style="max-width: 130px;" title="${file.name}">${file.name}</div>
                <button type="button" class="btn btn-sm btn-danger py-0 px-2 rounded-pill" onclick="window.eliminarFotoSpec(${index})" title="Quitar de la lista"><i class="bi bi-trash"></i></button>
            </div>`;
        });
        listHtml += '</div></div>';
    }
    $('#lista_fotos_seleccionadas_specs').html(listHtml);
    window.actualizarSelectoresFrenteEspalda();
    window.actualizarVistaPreviaFichaConfeccion();
};

window.eliminarFotoSpec = function(index) {
    archivosSpecsMemoria.splice(index, 1);
    window.renderizarListaFotosSpecs();
};

$(document).on('change', '#fotos_diseno_specs', function(e) {
    let files = e.target.files;
    if(files.length > 0) {
        for(let i=0; i<files.length; i++) {
            archivosSpecsMemoria.push(files[i]);
        }
        window.renderizarListaFotosSpecs();
    }
    $(this).val(''); 
});

// ================= REGLAS DE TALLAS (LETRAS, TALLAS EXTRAS Y TALLAS NUMÉRICAS / JUVENIL) =================
window.generarInputTelasLocal = function(telaActual, tipoPrenda) {
    let p = (tipoPrenda || '').toLowerCase();
    let catSugerida = '';
    
    if(p.includes('deportiv') || p.includes('sport') || p.includes('jersey') || p.includes('atletic') || p.includes('basket')) catSugerida = 'deportiva';
    else if(p.includes('ejecutiv') || p.includes('columbia') || p.includes('oxford') || p.includes('unioffice') || p.includes('racing') || p.includes('chemis') || p.includes('camisa')) catSugerida = 'empresarial';
    else if(p.includes('braga') || p.includes('jean') || p.includes('seguridad') || p.includes('industrial') || p.includes('chambray') || p.includes('dril')) catSugerida = 'industrial';
    else if(p.includes('medico') || p.includes('médico') || p.includes('enfermer') || p.includes('quirurg') || p.includes('filipina') || p.includes('bata')) catSugerida = 'médica';
    else if(p.includes('chef') || p.includes('delantal') || p.includes('cocin') || p.includes('gastron')) catSugerida = 'gastronómica';
    else if(p.includes('escolar') || p.includes('colegio')) catSugerida = 'escolar';

    let datalistId = 'dl_telas_' + Math.random().toString(36).substr(2, 9);
    let priorizadas = [];
    let resto = [];
    
    $('#lista_telas_inteligente option').each(function() {
        let val = $(this).attr('value');
        let text = $(this).text();
        let textLower = text.toLowerCase();
        
        let esTela = true;
        if (textLower.includes('hilo') || textLower.includes('boton') || textLower.includes('botón') || 
            textLower.includes('cierre') || textLower.includes('empaque') || textLower.includes('entretela') || 
            textLower.includes('bordado') || textLower.includes('pelón') || textLower.includes('pelon')) {
            esTela = false;
        }
        
        if(esTela) {
            if (catSugerida !== '' && textLower.includes(catSugerida)) {
                priorizadas.push(`<option value="${val}">⭐ Recomendada - ${val} - ${text}</option>`);
            } else {
                resto.push(`<option value="${val}">${val} - ${text}</option>`);
            }
        }
    });
    
    let finalOptions = priorizadas.join('') + resto.join('');
    let safeTela = String(telaActual || '').replace(/"/g, '&quot;');
    
    return `
        <datalist id="${datalistId}">${finalOptions}</datalist>
        <input type="text" list="${datalistId}" class="form-control form-control-sm spec-tela border-primary" value="${safeTela}" placeholder="Buscar tela..." required>
    `;
};

window.esPrendaPantalon = function(tipoPrenda) {
    const n = String(tipoPrenda || '').toLowerCase();
    return n.includes('pantalon') || n.includes('pantalón') || n.includes('jean') || n.includes('jogger') || n.includes('mono') || n.includes('bermuda') || n.includes('short');
};

window.obtenerOpcionesTallaLocal = function(genero, tipoPrenda = '') {
    if (window.esPrendaPantalon(tipoPrenda)) {
        if (genero === 'Caballero') {
            return '<optgroup label="Tallas Numéricas Caballero (28-36)"><option value="26">26</option><option value="28">28</option><option value="30">30</option><option value="32" selected>32</option><option value="34">34</option><option value="36">36</option></optgroup>' +
                   '<optgroup label="Tallas Extras Numéricas Caballero (38-48)"><option value="38">38</option><option value="40">40</option><option value="42">42</option><option value="44">44</option><option value="46">46</option><option value="48">48</option></optgroup>' +
                   '<optgroup label="Tallas en Letras (S - 5XL)"><option value="XS">XS</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option></optgroup>';
        }
        if (genero === 'Dama') {
            return '<optgroup label="Tallas Numéricas Dama (6-14)"><option value="6">6</option><option value="8">8</option><option value="10">10</option><option value="12" selected>12</option><option value="14">14</option></optgroup>' +
                   '<optgroup label="Tallas Extras Numéricas Dama (16-26)"><option value="16">16</option><option value="18">18</option><option value="20">20</option><option value="22">22</option><option value="24">24</option><option value="26">26</option></optgroup>' +
                   '<optgroup label="Tallas en Letras (XS - 5XL)"><option value="XS">XS</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option></optgroup>';
        }
    }
    if(genero === 'Dama') {
        return '<optgroup label="Tallas Estándar Dama"><option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option></optgroup>' +
               '<optgroup label="Tallas Extras Dama (2XL - 5XL)"><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option></optgroup>' +
               '<optgroup label="Tallas en Número Dama (6 - 24)"><option value="6">6</option><option value="8">8</option><option value="10">10</option><option value="12">12</option><option value="14">14</option><option value="16">16</option><option value="18">18</option><option value="20">20</option><option value="22">22</option><option value="24">24</option></optgroup>';
    }
    if(genero === 'Caballero') {
        return '<optgroup label="Tallas Estándar Caballero"><option value="XS">XS</option><option value="S">S</option><option value="M" selected>M</option><option value="L">L</option><option value="XL">XL</option></optgroup>' +
               '<optgroup label="Tallas Extras Caballero (2XL - 5XL)"><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option></optgroup>' +
               '<optgroup label="Tallas en Número Caballero (28 - 46)"><option value="28">28</option><option value="30">30</option><option value="32">32</option><option value="34">34</option><option value="36">36</option><option value="38">38</option><option value="40">40</option><option value="42">42</option><option value="44">44</option><option value="46">46</option></optgroup>' +
               '<optgroup label="Juvenil Numérica (12 - 18)"><option value="12">12</option><option value="14">14</option><option value="16">16</option><option value="18">18</option></optgroup>';
    }
    if(genero === 'Niña' || genero === 'Niño' || genero === 'Juvenil') {
        return '<optgroup label="Infantil / Juvenil en Número (2 - 18)"><option value="2">2</option><option value="4">4</option><option value="6">6</option><option value="8">8</option><option value="10">10</option><option value="12" selected>12</option><option value="14">14</option><option value="16">16</option><option value="18">18</option></optgroup>' +
               '<optgroup label="Juvenil en Letras"><option value="XS">XS</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option></optgroup>';
    }
    return '<option value="U" selected>Única / Ajustable</option><option value="XS">XS</option><option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="2XL">2XL</option><option value="3XL">3XL</option><option value="4XL">4XL</option><option value="5XL">5XL</option>';
};

const GENEROS_REGLA_TALLER = ['Dama', 'Caballero', 'Niña', 'Niño', 'Juvenil'];

function normalizarGeneroReglaTaller(genero) {
    const g = String(genero || '').trim();
    if (GENEROS_REGLA_TALLER.includes(g)) return g;
    if (/juvenil/i.test(g)) return 'Juvenil';
    if (/infantil|niñ/i.test(g)) return 'Niño';
    return '';
}

function generoValidoParaReglaTaller(tipoPrenda, genero) {
    const nombre = String(tipoPrenda || '').toLocaleLowerCase();
    if (nombre.includes('gorra') || nombre.includes('visera') || nombre.includes('vicera')) {
        return genero === 'Accesorio';
    }
    if (nombre.includes('bota') || nombre.includes('zapato') || nombre.includes('calzado')) {
        return genero === 'No aplica';
    }
    return GENEROS_REGLA_TALLER.includes(genero);
}

window.generarOpcionesTallaEspecificas = function(tipoPrenda, rangoCotizado, generoSeleccionado, generoCotizado) {
    let nombreP = (tipoPrenda || '').toLowerCase();
    if (nombreP.includes('gorra') || nombreP.includes('visera') || nombreP.includes('vicera')) {
        return `<option value="Única">Única</option>`;
    }
    if (nombreP.includes('bota') || nombreP.includes('zapato') || nombreP.includes('calzado')) {
        let opts = '';
        for(let i = 34; i <= 47; i++) opts += `<option value="${i}">${i}</option>`;
        return opts;
    }
    if (!generoSeleccionado) {
        return '<option value="">Selecciona primero el género</option>';
    }
    return window.obtenerOpcionesTallaLocal(generoSeleccionado, tipoPrenda);
};

window.generarCamposMedidasPersonalesSpec = function(medidas) {
    const campos = [
        ['hombro', 'Hombro'],
        ['busto_torax', 'Busto / tórax'],
        ['cintura', 'Cintura'],
        ['cadera', 'Cadera'],
        ['largo_camisa', 'Largo camisa'],
        ['largo_brazo', 'Largo brazo'],
        ['largo_pantalon', 'Largo pantalón'],
        ['tiro_total', 'Tiro total'],
        ['ancho_muslo', 'Ancho muslo'],
        ['ancho_rodilla', 'Ancho rodilla'],
        ['ancho_talon', 'Ancho talón']
    ];
    const valores = medidas || {};
    const tieneMedidas = campos.some(([key]) => Number(valores[key]) > 0);

    return `
        <tr class="fila-medidas-personales ${tieneMedidas ? '' : 'd-none'}">
            <td colspan="7">
                <div class="p-2 rounded border border-primary bg-light">
                    <div class="small fw-bold text-primary mb-2"><i class="bi bi-rulers me-1"></i> Medidas personales de sastrería (cm)</div>
                    <div class="row g-2">
                        ${campos.map(([key, label]) => {
                            const value = Number(valores[key]);
                            const safeValue = Number.isFinite(value) && value > 0 ? value : '';
                            return `<div class="col-6 col-md-3">
                                <label class="form-label small mb-1">${label}</label>
                                <input type="number" min="0" step="0.1" class="form-control form-control-sm spec-medida" data-medida="${key}" value="${safeValue}" placeholder="cm">
                            </div>`;
                        }).join('')}
                    </div>
                </div>
            </td>
        </tr>`;
};

window.extraerArtesPorPrendaCotizacion = function(items) {
    const mapaPorPrenda = {};
    const artesGenerales = [];
    const detalleArtesRaw = [];
    const prendasFisicas = (items || []).filter(it => {
        const esArte = (it.detalle_diseno && it.detalle_diseno.includes('Ubicación:')) || /^(bordado|serigraf|dtf|vinil|sublimaci)/i.test(String(it.tipo_prenda || '').trim());
        const esExtra = /^tallas?\s+extras?\b/i.test(String(it.tipo_prenda || '').trim());
        return !esArte && !esExtra;
    });

    (items || []).forEach(it => {
        const diseno = String(it.detalle_diseno || '');
        const nombreArte = String(it.tipo_prenda || '').trim();
        const esArte = diseno.includes('Ubicación:') || /^(bordado|serigraf|dtf|vinil)/i.test(nombreArte);
        if (!esArte) return;

        let vinculo = '';
        let ubicacion = '';
        if (diseno.includes('[Para: ')) {
            const partes = diseno.split('] - Ubicación:');
            vinculo = partes[0].replace('[Para:', '').trim();
            ubicacion = (partes[1] || '').trim();
        } else if (diseno.includes('Ubicación:')) {
            ubicacion = diseno.split('Ubicación:').pop().trim();
        }

        detalleArtesRaw.push({ nombre: nombreArte, ubicacion: ubicacion, vinculo: vinculo, cantidad: parseInt(it.cantidad) || 1 });

        const textoArte = ubicacion ? `${nombreArte} (${ubicacion})` : nombreArte;
        if (vinculo) {
            const clave = vinculo.toLowerCase().trim();
            if (!mapaPorPrenda[clave]) mapaPorPrenda[clave] = [];
            if (!mapaPorPrenda[clave].includes(textoArte)) mapaPorPrenda[clave].push(textoArte);
        } else {
            if (!artesGenerales.includes(textoArte)) artesGenerales.push(textoArte);
        }
    });

    return {
        detalleArtesRaw: detalleArtesRaw,
        obtenerTextoParaPrenda: function(tipoPrenda, genero) {
            const pLower = String(tipoPrenda || '').toLowerCase().trim();
            const gLower = String(genero || '').toLowerCase().trim();
            const claveCompleta = gLower && gLower !== 'no especificado' ? `${pLower} (${gLower})` : pLower;
            const claveSinGenero = `${pLower} (no especificado)`;

            let encontrados = [];
            if (mapaPorPrenda[claveCompleta]) encontrados = encontrados.concat(mapaPorPrenda[claveCompleta]);
            if (mapaPorPrenda[pLower]) encontrados = encontrados.concat(mapaPorPrenda[pLower]);
            if (mapaPorPrenda[claveSinGenero]) encontrados = encontrados.concat(mapaPorPrenda[claveSinGenero]);

            if (artesGenerales.length > 0) {
                const esPrendaSuperior = /(camisa|chemis|chemise|franela|jersey|filipina|bata|chaqueta|chaleco|sueter|delantal|gorra)/i.test(pLower);
                if (prendasFisicas.length === 1 || esPrendaSuperior) {
                    encontrados = encontrados.concat(artesGenerales);
                }
            }
            return [...new Set(encontrados)].join(' + ');
        }
    };
};

// ================= DEFINICIÓN OFICIAL DE CHECKLISTS DE LA FICHA PILOTO =================
window.CATALOGO_CHECKS_ARMADO = [
    ['cuello_camisero_clasico', 'Cuello Camisero Clásico'],
    ['cuello_camisero_mao', 'Cuello Camisero Mao'],
    ['cuello_sport_redondo', 'Cuello Sport Redondo'],
    ['cuello_sport_v', 'Cuello Sport "V"'],
    ['manga_corta_normal', 'Manga Corta Normal'],
    ['manga_larga_normal', 'Manga Larga Normal'],
    ['manga_corta_vivos', 'Manga Corta con vivos'],
    ['manga_3_4_ejecutivo', 'Manga 3/4 Ejecutivo'],
    ['manga_3_4_clasico', 'Manga 3/4 Clásico'],
    ['punos_normales', 'Puños Normales'],
    ['punos_con_vivos', 'Puños con Vivos'],
    ['punos_doble_tono', 'Puños Doble Tono'],
    ['espalda_tipo_columbia', 'Espalda Tipo Columbia'],
    ['espalda_ejecutiva', 'Espalda Ejecutiva'],
    ['botonadura_escondida', 'Botonadura Escondida'],
    ['cierre_chaqueta_escondido', 'Cierre Chaqueta Escondido'],
    ['bolsillos_tipo_colombia', 'Bolsillos Tipo Colombia'],
    ['dos_bolsillos_tapa_velcro', '2 Bolsillos con Tapa y Velcro'],
    ['dos_bolsillos_tapa_boton', '2 Bolsillos con Tapa y Botón'],
    ['un_bolsillo_ejecutivo_diamante', '1 Bolsillo Ejecutivo Punta Diamante'],
    ['un_bolsillo_interno', '1 Bolsillo Interno'],
    ['dos_bolsillos_internos', '2 Bolsillos Internos'],
    ['costura_doble', 'Costura Doble'],
    ['costura_ejecutiva', 'Costura Ejecutiva'],
    ['costura_casual', 'Costura Casual'],
    ['hilo_tono_tela', 'Hilo al Tono de la Tela'],
    ['hilo_contraste_tela', 'Hilo al Contraste de la Tela'],
    ['boton_color_tela', 'Botón del Color de la Tela'],
    ['boton_contraste_tela', 'Botón al Contraste de la Tela']
];

window.CATALOGO_CHECKS_PILOTO = [
    ['aprobacion_muestra_digital', 'Aprobación de Muestra Digital'],
    ['requiere_muestra_fisica', 'Requiere Muestra Física aprobada'],
    ['aprobacion_muestra_fisica', 'Aprobación de Muestra Física'],
    ['mantener_medidas_unifuerte', 'Mantener medidas exactas al tallaje UNIFUERTE'],
    ['comprador_ajuste_medidas', 'El Comprador necesita ajuste de medidas']
];

window.CATALOGO_CHECKS_TECNICA = [
    ['bordado_directo', 'Bordado Directo'],
    ['dtf_textil_hd', 'DTF Textil HD'],
    ['vinil_textil', 'Vinil Textil'],
    ['sublimado', 'Sublimado']
];

window.CATALOGO_CHECKS_ETIQUETAS = [
    ['etiqueta_talla_camiseria', 'Etiqueta de Talla Camisería'],
    ['etiqueta_talla_deportiva', 'Etiqueta de Talla Deportiva'],
    ['etiqueta_beisbol', 'Etiqueta de Beisbol'],
    ['etiqueta_futbol', 'Etiqueta de Futbol'],
    ['banderin_unifuerte', 'Banderín Unifuerte'],
    ['etiqueta_sencamer', 'Etiqueta Sencamer'],
    ['etiqueta_by_claritza', 'Etiqueta By Claritza Pinto']
];

window.CATALOGO_CHECKS_EMPAQUE = [
    ['bolsa_celofan', 'Bolsa de Celofán (Individual)'],
    ['bolsa_unifuerte', 'Bolsa de Unifuerte (Individual)'],
    ['bolsa_by_claritza', 'Bolsa By Claritza Pinto'],
    ['saco_plastico', 'Saco Plástico (Entrega Final con todas las bolsas)'],
    ['caja_carton', 'Caja de Cartón'],
    ['planchado', 'Planchado']
];

// ================= CONSTRUIR UNA FICHA PILOTO INDEPENDIENTE POR CADA PRODUCTO =================
window.generarCardFichaPorPrendaHTML = function(itemPrenda, idxPrenda, totalPrendasCot, fichaGuardadaItem, itemsCotizacion, infoCot) {
    const idDet = itemPrenda.ID;
    const f = fichaGuardadaItem || {};
    const armado = f.armado || {};
    const piloto = f.piloto || { aprobacion_muestra_digital: 1, mantener_medidas_unifuerte: 1 };
    const diseno = f.diseno || {};
    const tecnicas = diseno.tecnicas || {};
    const etiquetas = f.etiquetas || { etiqueta_talla_camiseria: 1, banderin_unifuerte: 1, etiqueta_sencamer: 1 };
    const empaque = f.empaque || { bolsa_celofan: 1, saco_plastico: 1, planchado: 1 };

    // Autodetectar checks de manga según el nombre de esta prenda si aún no tiene armado guardado
    const nombrePrendaLow = String(itemPrenda.tipo_prenda || '').toLowerCase();
    if (Object.keys(armado).length === 0) {
        if (nombrePrendaLow.includes('manga corta')) armado.manga_corta_normal = 1;
        if (nombrePrendaLow.includes('manga larga')) armado.manga_larga_normal = 1;
        if (nombrePrendaLow.includes('columbia')) armado.espalda_tipo_columbia = 1;
        if (nombrePrendaLow.includes('ejecutiv') || nombrePrendaLow.includes('camisa') || nombrePrendaLow.includes('chemis')) armado.cuello_camisero_clasico = 1;
    }

    // Extraer tela y color de ESTE producto específico
    let telaDefault = '';
    let colorDefault = '';
    let mTela = String(itemPrenda.detalle_diseno || '').match(/\bTela:\s*([^|]+)/i);
    let tStr = (mTela && mTela[1]) ? mTela[1].trim() : '';
    if (tStr && !/^(n\/a|no aplica)$/i.test(tStr)) {
        let mCol = tStr.match(/^(.+?)\s*\(([^)]+)\)$/);
        if (mCol) {
            telaDefault = mCol[1].trim();
            colorDefault = mCol[2].trim();
        } else {
            telaDefault = tStr;
        }
    }

    const buscadorArtes = window.extraerArtesPorPrendaCotizacion(itemsCotizacion || []);
    let fIzqDefault = '';
    let fDerDefault = '';
    let mDerDefault = '';
    let mIzqDefault = '';
    let espSupDefault = '';
    let cantLogosDefault = 0;

    buscadorArtes.detalleArtesRaw.forEach(a => {
        // Vincular arte si es general o si coincide con esta prenda
        const vincLow = (a.vinculo || '').toLowerCase();
        if (vincLow && !vincLow.includes(nombrePrendaLow) && !nombrePrendaLow.includes(vincLow.split('(')[0].trim())) {
            return;
        }
        cantLogosDefault++;
        const nLow = a.nombre.toLowerCase();
        if (nLow.includes('bordado')) tecnicas.bordado_directo = tecnicas.bordado_directo ?? 1;
        if (nLow.includes('dtf')) tecnicas.dtf_textil_hd = tecnicas.dtf_textil_hd ?? 1;
        if (nLow.includes('vinil')) tecnicas.vinil_textil = tecnicas.vinil_textil ?? 1;
        if (nLow.includes('serigraf') || nLow.includes('sublim')) tecnicas.sublimado = tecnicas.sublimado ?? 1;

        const uLow = (a.ubicacion || '').toLowerCase();
        const descArte = a.ubicacion ? `${a.nombre} (${a.ubicacion})` : a.nombre;
        if (uLow.includes('derech') && !uLow.includes('manga')) {
            fDerDefault = fDerDefault ? `${fDerDefault} / ${descArte}` : descArte;
        } else if (uLow.includes('manga') && uLow.includes('derech')) {
            mDerDefault = mDerDefault ? `${mDerDefault} / ${descArte}` : descArte;
        } else if (uLow.includes('manga')) {
            mIzqDefault = mIzqDefault ? `${mIzqDefault} / ${descArte}` : descArte;
        } else if (uLow.includes('espalda')) {
            espSupDefault = espSupDefault ? `${espSupDefault} / ${descArte}` : descArte;
        } else {
            fIzqDefault = fIzqDefault ? `${fIzqDefault} / ${descArte}` : descArte;
        }
    });

    const modeloVal = f.modelo !== undefined ? f.modelo : itemPrenda.tipo_prenda;
    const telaPrincipalVal = f.tela_principal !== undefined ? f.tela_principal : telaDefault;
    const colorTonoVal = f.color_tono !== undefined ? f.color_tono : colorDefault;
    const combinacionVal = f.combinacion_cortes !== undefined ? f.combinacion_cortes : (infoCot.notas || '');
    const prioridadVal = f.prioridad || 'Normal';
    const lugarEntregaVal = f.lugar_entrega || infoCot.metodo_entrega || infoCot.cliente_direccion || '';

    const fDerVal = diseno.frente_derecho_txt !== undefined ? diseno.frente_derecho_txt : fDerDefault;
    const fIzqVal = diseno.frente_izquierdo_txt !== undefined ? diseno.frente_izquierdo_txt : fIzqDefault;
    const mDerVal = diseno.manga_derecha_txt !== undefined ? diseno.manga_derecha_txt : mDerDefault;
    const mIzqVal = diseno.manga_izquierda_txt !== undefined ? diseno.manga_izquierda_txt : mIzqDefault;
    const espVal = diseno.espalda_superior_txt !== undefined ? diseno.espalda_superior_txt : espSupDefault;
    const cantLogosVal = diseno.cantidad_logos !== undefined ? diseno.cantidad_logos : cantLogosDefault;

    const chkFDer = diseno.frente_derecho_chk !== undefined ? diseno.frente_derecho_chk : (fDerVal ? 1 : 0);
    const chkFIzq = diseno.frente_izquierdo_chk !== undefined ? diseno.frente_izquierdo_chk : (fIzqVal ? 1 : 0);
    const chkMDer = diseno.manga_derecha_chk !== undefined ? diseno.manga_derecha_chk : (mDerVal ? 1 : 0);
    const chkMIzq = diseno.manga_izquierda_chk !== undefined ? diseno.manga_izquierda_chk : (mIzqVal ? 1 : 0);
    const chkEsp = diseno.espalda_superior_chk !== undefined ? diseno.espalda_superior_chk : (espVal ? 1 : 0);

    const cantBolsasManual = empaque.cantidad_bolsas_manual || '';
    const cantSacosManual = empaque.cantidad_sacos_manual || 1;
    const prendasPorSaco = empaque.prendas_por_saco || 25;
    const obsEmpaqueVal = empaque.observacion_empaque || '';

    const esc = s => String(s ?? '').replace(/"/g, '&quot;');

    return `
    <div class="card border-primary shadow-sm mt-3 card-ficha-piloto-prenda" data-id-detalle="${idDet}" data-nombre-prenda="${esc(itemPrenda.tipo_prenda)}">
        <div class="card-header text-white fw-bold d-flex justify-content-between align-items-center" style="background-color: #0f2942;">
            <span>
                <i class="bi bi-clipboard2-check-fill text-warning me-1"></i>
                FICHA PILOTO #${idxPrenda + 1} DE ${totalPrendasCot}: <span class="text-warning text-uppercase">${esc(itemPrenda.tipo_prenda)}</span> (${esc(itemPrenda.genero)})
            </span>
            <button class="btn btn-sm btn-warning text-dark fw-bold py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFP_${idDet}" aria-expanded="true">
                Mostrar / Ocultar Ficha #${idxPrenda + 1}
            </button>
        </div>
        <div class="collapse show" id="collapseFP_${idDet}">
            <div class="card-body bg-white p-3">
                
                <!-- SELECTOR DE FOTO FRENTE Y ESPALDA EXCLUSIVO DE ESTA PRENDA -->
                <div class="row g-2 mb-3 p-2 rounded border border-primary bg-light">
                    <div class="col-md-6">
                        <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-bounding-box"></i> Foto VISTA FRENTE para ${esc(itemPrenda.tipo_prenda)}:</label>
                        <select class="form-select form-select-sm border-primary fw-bold fp-sel-foto-frente fp-input-live" data-saved="${esc(f.foto_frente || '')}">
                            <option value="">Automática (Primera imagen)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-primary mb-1"><i class="bi bi-person-badge"></i> Foto VISTA ESPALDA para ${esc(itemPrenda.tipo_prenda)}:</label>
                        <select class="form-select form-select-sm border-primary fw-bold fp-sel-foto-espalda fp-input-live" data-saved="${esc(f.foto_espalda || '')}">
                            <option value="">Automática (Segunda imagen)</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-1 mb-2"><i class="bi bi-sliders"></i> 1 y 2. Especificaciones de "${esc(itemPrenda.tipo_prenda)}" y Entrega</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted">Modelo de Prenda</label>
                        <input type="text" class="form-control form-control-sm border-primary fw-bold fp-modelo fp-input-live" value="${esc(modeloVal)}">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Tela Principal</label>
                        <input type="text" class="form-control form-control-sm border-primary fp-tela-principal fp-input-live" value="${esc(telaPrincipalVal)}" placeholder="Ej: Algodón / Drill / Oxford">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Color / Tono Corporativo</label>
                        <input type="text" class="form-control form-control-sm border-primary fp-color-tono fp-input-live" value="${esc(colorTonoVal)}" placeholder="Ej: Azul Marino / Blanco">
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted">Combinación / Cortes / Notas de esta Prenda</label>
                        <input type="text" class="form-control form-control-sm fp-combinacion-cortes fp-input-live" value="${esc(combinacionVal)}" placeholder="Ej: Vivo en cuello y puños...">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted">Lugar de Entrega / Despacho</label>
                        <input type="text" class="form-control form-control-sm fp-lugar-entrega fp-input-live" value="${esc(lugarEntregaVal)}">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Prioridad de Orden</label>
                        <select class="form-select form-select-sm fw-bold border-danger fp-prioridad fp-input-live">
                            <option value="Normal" ${prioridadVal === 'Normal' ? 'selected' : ''}>Normal (Estándar)</option>
                            <option value="Alta" ${prioridadVal === 'Alta' ? 'selected' : ''}>🔥 Alta (Urgente / Express)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-check2-square"></i> 3. Detalle de Armado y Confección (${esc(itemPrenda.tipo_prenda)})</h6>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small" onclick="$(this).closest('.card-ficha-piloto-prenda').find('.fp-chk-armado').prop('checked', false); window.actualizarVistaPreviaFichaConfeccion();">Limpiar Checks</button>
                </div>
                <div class="row g-2 mb-3 p-2 bg-light rounded border" style="font-size: 0.82rem;">
                    ${window.CATALOGO_CHECKS_ARMADO.map(([key, label]) => `
                        <div class="col-6 col-md-3">
                            <div class="form-check m-0">
                                <input class="form-check-input fp-chk-armado fp-input-live" type="checkbox" id="fp_arm_${idDet}_${key}" data-key="${key}" ${armado[key] ? 'checked' : ''}>
                                <label class="form-check-label" for="fp_arm_${idDet}_${key}">${label}</label>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <h6 class="fw-bold text-secondary small text-uppercase mb-2"><i class="bi bi-shield-check"></i> Muestra Piloto & Control de Medidas (Tolerancia: ±0.5 cm)</h6>
                <div class="row g-2 mb-3 p-2 bg-light rounded border" style="font-size: 0.82rem;">
                    ${window.CATALOGO_CHECKS_PILOTO.map(([key, label]) => `
                        <div class="col-12 col-md-4">
                            <div class="form-check m-0">
                                <input class="form-check-input fp-chk-piloto fp-input-live" type="checkbox" id="fp_pil_${idDet}_${key}" data-key="${key}" ${piloto[key] ? 'checked' : ''}>
                                <label class="form-check-label fw-semibold" for="fp_pil_${idDet}_${key}">${label}</label>
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2"><i class="bi bi-palette2"></i> 5. Diseño Final y Logos para ${esc(itemPrenda.tipo_prenda)}</h6>
                        <div class="d-flex flex-wrap gap-3 mb-2 p-2 bg-light rounded border" style="font-size: 0.82rem;">
                            <span class="fw-bold text-muted">Técnica:</span>
                            ${window.CATALOGO_CHECKS_TECNICA.map(([key, label]) => `
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-tecnica fp-input-live" type="checkbox" id="fp_tec_${idDet}_${key}" data-key="${key}" ${tecnicas[key] ? 'checked' : ''}>
                                    <label class="form-check-label fw-bold" for="fp_tec_${idDet}_${key}">${label}</label>
                                </div>
                            `).join('')}
                        </div>

                        <div class="row g-1 align-items-center mb-1">
                            <div class="col-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-frente-der fp-input-live" type="checkbox" id="fp_chk_fder_${idDet}" ${chkFDer ? 'checked' : ''}>
                                    <label class="form-check-label small fw-bold" for="fp_chk_fder_${idDet}">Frente Derecho:</label>
                                </div>
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm fp-txt-frente-der fp-input-live" value="${esc(fDerVal)}" placeholder="Ej: Nombre / Cargo / Isotipo">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center mb-1">
                            <div class="col-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-frente-izq fp-input-live" type="checkbox" id="fp_chk_fizq_${idDet}" ${chkFIzq ? 'checked' : ''}>
                                    <label class="form-check-label small fw-bold" for="fp_chk_fizq_${idDet}">Frente Izquierdo:</label>
                                </div>
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm fp-txt-frente-izq fp-input-live" value="${esc(fIzqVal)}" placeholder="Ej: Logo Empresa (9.0 cm ancho) - Bordado">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center mb-1">
                            <div class="col-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-manga-der fp-input-live" type="checkbox" id="fp_chk_mder_${idDet}" ${chkMDer ? 'checked' : ''}>
                                    <label class="form-check-label small fw-bold" for="fp_chk_mder_${idDet}">Manga Derecha:</label>
                                </div>
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm fp-txt-manga-der fp-input-live" value="${esc(mDerVal)}" placeholder="Ej: Bandera / Isotipo (5.5 cm ancho)">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center mb-1">
                            <div class="col-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-manga-izq fp-input-live" type="checkbox" id="fp_chk_mizq_${idDet}" ${chkMIzq ? 'checked' : ''}>
                                    <label class="form-check-label small fw-bold" for="fp_chk_mizq_${idDet}">Manga Izquierda:</label>
                                </div>
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm fp-txt-manga-izq fp-input-live" value="${esc(mIzqVal)}" placeholder="Ej: Logo secundario / Sin aplicación">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center mb-2">
                            <div class="col-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-espalda-sup fp-input-live" type="checkbox" id="fp_chk_esp_${idDet}" ${chkEsp ? 'checked' : ''}>
                                    <label class="form-check-label small fw-bold" for="fp_chk_esp_${idDet}">Espalda Superior:</label>
                                </div>
                            </div>
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm fp-txt-espalda-sup fp-input-live" value="${esc(espVal)}" placeholder="Ej: Texto / Nombre Institucional 24 cm ancho">
                            </div>
                        </div>

                        <div class="row g-1 align-items-center">
                            <div class="col-5"><label class="small fw-bold text-muted">Cantidad de Logos en esta prenda:</label></div>
                            <div class="col-3"><input type="number" min="0" class="form-control form-control-sm text-center fw-bold border-primary fp-cant-logos fp-input-live" value="${esc(cantLogosVal)}"></div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <h6 class="fw-bold text-primary border-bottom pb-1 mb-2"><i class="bi bi-tags-fill"></i> 6. Etiquetado de Marca</h6>
                        <div class="p-2 bg-light rounded border d-flex flex-column gap-2" style="font-size: 0.84rem;">
                            ${window.CATALOGO_CHECKS_ETIQUETAS.map(([key, label]) => `
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-etiqueta fp-input-live" type="checkbox" id="fp_eti_${idDet}_${key}" data-key="${key}" ${etiquetas[key] ? 'checked' : ''}>
                                    <label class="form-check-label fw-semibold" for="fp_eti_${idDet}_${key}">${label}</label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded border border-info" style="background-color: #f0f9ff;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-primary m-0"><i class="bi bi-box2-heart-fill"></i> 7. Empaque, Bolsas y Sacos (${esc(itemPrenda.tipo_prenda)})</h6>
                        <span class="badge bg-primary">* Entregar siempre con NOTA DE ENTREGA</span>
                    </div>
                    <div class="row g-2 mb-2" style="font-size: 0.84rem;">
                        ${window.CATALOGO_CHECKS_EMPAQUE.map(([key, label]) => `
                            <div class="col-6 col-md-4">
                                <div class="form-check m-0">
                                    <input class="form-check-input fp-chk-empaque fp-input-live" type="checkbox" id="fp_emp_${idDet}_${key}" data-key="${key}" ${empaque[key] ? 'checked' : ''}>
                                    <label class="form-check-label fw-bold text-dark" for="fp_emp_${idDet}_${key}">${label}</label>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="row g-2 mt-1 pt-2 border-top border-info-subtle align-items-end">
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Bolsas Individuales</label>
                            <input type="number" min="0" class="form-control form-control-sm border-primary fw-bold text-center fp-cant-bolsas fp-input-live" value="${esc(cantBolsasManual)}" placeholder="Auto (= Cant. Prenda)">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Capacidad (Bolsas/Saco)</label>
                            <input type="number" min="1" class="form-control form-control-sm border-primary text-center fp-prendas-por-saco fp-input-live" value="${esc(prendasPorSaco)}">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Sacos Entrega Final</label>
                            <input type="number" min="0" class="form-control form-control-sm border-success fw-bold text-success text-center fp-cant-sacos fp-input-live" value="${esc(cantSacosManual)}">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Nota de Empaque</label>
                            <input type="text" class="form-control form-control-sm fp-obs-empaque fp-input-live" value="${esc(obsEmpaqueVal)}" placeholder="Ej: Empacar separado">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    `;
};

// ================= RECOLECTAR DATOS EN VIVO SEPARADOS POR CADA PRENDA =================
window.crearFilaMatrizVacia = function() {
    return { XS: 0, S: 0, M: 0, L: 0, XL: 0, '2XL': 0, '3XL': 0, '4XL': 0, '5XL': 0, numericas: {}, subtotal: 0 };
};

window.formatearTallasNumericasResumen = function(objNum) {
    if (!objNum) return '-';
    const pares = Object.keys(objNum)
        .filter(k => objNum[k] > 0)
        .sort((a, b) => (parseInt(a) || 999) - (parseInt(b) || 999))
        .map(k => `T${k}: <b>${objNum[k]}</b>`);
    return pares.length > 0 ? pares.join(', ') : '-';
};

window.recolectarFichaDeUnaCard = function($card) {
    const idDet = $card.data('id-detalle');
    const nombrePrenda = $card.data('nombre-prenda') || '';

    const armado = {};
    $card.find('.fp-chk-armado').each(function() {
        armado[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
    });

    const piloto = {};
    $card.find('.fp-chk-piloto').each(function() {
        piloto[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
    });

    const tecnicas = {};
    $card.find('.fp-chk-tecnica').each(function() {
        tecnicas[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
    });

    const etiquetas = {};
    $card.find('.fp-chk-etiqueta').each(function() {
        etiquetas[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
    });

    const empaque = {};
    $card.find('.fp-chk-empaque').each(function() {
        empaque[$(this).data('key')] = $(this).is(':checked') ? 1 : 0;
    });

    // Calcular matriz de tallas y lista de colaboradores SOLO de este producto (idDet)
    let totalPrendasItem = 0;
    const matrizTallas = {
        Dama: window.crearFilaMatrizVacia(),
        Caballero: window.crearFilaMatrizVacia(),
        Infantil: window.crearFilaMatrizVacia()
    };
    const colaboradoresItem = [];
    let telasEnTabla = [];

    $(`.fila-spec[data-id-detalle="${idDet}"]`).each(function() {
        const cant = parseInt($(this).find('.spec-cant').val()) || 0;
        const genero = $(this).find('.spec-genero').val() || '';
        const talla = String($(this).find('.spec-talla').val() || '').trim().toUpperCase();
        const tela = ($(this).find('.spec-tela').val() || '').trim();
        const nombre = ($(this).find('.spec-nombre').val() || '').trim();
        const obs = ($(this).find('.spec-obs').val() || '').trim();
        const tipoPrenda = $(this).data('tipo-prenda') || nombrePrenda;

        if (tela && tela !== 'No aplica' && !telasEnTabla.includes(tela)) {
            telasEnTabla.push(tela);
        }

        if (cant > 0) {
            totalPrendasItem += cant;
            let grupo = 'Caballero';
            if (genero === 'Dama') grupo = 'Dama';
            else if (genero === 'Niña' || genero === 'Niño' || genero === 'Juvenil') grupo = 'Infantil';

            if (matrizTallas[grupo]) {
                if (['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'].includes(talla)) {
                    matrizTallas[grupo][talla] += cant;
                } else if (talla !== '') {
                    matrizTallas[grupo].numericas[talla] = (matrizTallas[grupo].numericas[talla] || 0) + cant;
                }
                matrizTallas[grupo].subtotal += cant;
            }

            const medidas = {};
            let tieneSastreria = false;
            $(this).next('.fila-medidas-personales').find('.spec-medida').each(function() {
                const v = parseFloat($(this).val()) || 0;
                medidas[$(this).data('medida')] = v;
                if (v > 0) tieneSastreria = true;
            });

            colaboradoresItem.push({
                id_detalle: idDet,
                prenda: tipoPrenda,
                cantidad: cant,
                genero: genero || 'U',
                talla: talla || '-',
                tela: tela || '-',
                nombre: nombre || 'Sin Nombre Específico',
                obs: obs,
                tiene_sastreria: tieneSastreria,
                medidas_personales: medidas
            });
        }
    });

    const bolsasAuto = parseInt($card.find('.fp-cant-bolsas').val()) || totalPrendasItem;
    const capSaco = Math.max(1, parseInt($card.find('.fp-prendas-por-saco').val()) || 25);
    const sacosCalc = empaque.saco_plastico ? Math.max(1, Math.ceil((bolsasAuto || 1) / capSaco)) : 0;

    empaque.cantidad_bolsas_manual = bolsasAuto;
    empaque.prendas_por_saco = capSaco;
    empaque.cantidad_sacos_manual = parseInt($card.find('.fp-cant-sacos').val()) || sacosCalc;
    empaque.observacion_empaque = ($card.find('.fp-obs-empaque').val() || '').trim();

    let telaPrincipalInput = ($card.find('.fp-tela-principal').val() || '').trim();
    if (!telaPrincipalInput && telasEnTabla.length > 0) {
        telaPrincipalInput = telasEnTabla.join(' / ');
    }

    return {
        id_detalle: idDet,
        nombre_prenda: nombrePrenda,
        modelo: ($card.find('.fp-modelo').val() || '').trim() || nombrePrenda,
        tela_principal: telaPrincipalInput,
        color_tono: ($card.find('.fp-color-tono').val() || '').trim(),
        combinacion_cortes: ($card.find('.fp-combinacion-cortes').val() || '').trim(),
        lugar_entrega: ($card.find('.fp-lugar-entrega').val() || '').trim(),
        prioridad: $card.find('.fp-prioridad').val() || 'Normal',
        foto_frente: $card.find('.fp-sel-foto-frente').val() || '',
        foto_espalda: $card.find('.fp-sel-foto-espalda').val() || '',
        armado: armado,
        piloto: piloto,
        diseno: {
            tecnicas: tecnicas,
            frente_derecho_chk: $card.find('.fp-chk-frente-der').is(':checked') ? 1 : 0,
            frente_derecho_txt: ($card.find('.fp-txt-frente-der').val() || '').trim(),
            frente_izquierdo_chk: $card.find('.fp-chk-frente-izq').is(':checked') ? 1 : 0,
            frente_izquierdo_txt: ($card.find('.fp-txt-frente-izq').val() || '').trim(),
            manga_derecha_chk: $card.find('.fp-chk-manga-der').is(':checked') ? 1 : 0,
            manga_derecha_txt: ($card.find('.fp-txt-manga-der').val() || '').trim(),
            manga_izquierda_chk: $card.find('.fp-chk-manga-izq').is(':checked') ? 1 : 0,
            manga_izquierda_txt: ($card.find('.fp-txt-manga-izq').val() || '').trim(),
            espalda_superior_chk: $card.find('.fp-chk-espalda-sup').is(':checked') ? 1 : 0,
            espalda_superior_txt: ($card.find('.fp-txt-espalda-sup').val() || '').trim(),
            cantidad_logos: parseInt($card.find('.fp-cant-logos').val()) || 0
        },
        etiquetas: etiquetas,
        empaque: empaque,
        resumen_tallas: matrizTallas,
        total_prendas: totalPrendasItem,
        colaboradores: colaboradoresItem
    };
};

window.recolectarDatosFichaConfeccionLive = function() {
    const fichasPorPrenda = [];
    let totalGeneral = 0;
    let todosColaboradores = [];

    $('.card-ficha-piloto-prenda').each(function() {
        const fichaItem = window.recolectarFichaDeUnaCard($(this));
        fichasPorPrenda.push(fichaItem);
        totalGeneral += fichaItem.total_prendas;
        todosColaboradores = todosColaboradores.concat(fichaItem.colaboradores || []);
    });

    const primera = fichasPorPrenda[0] || {};
    return Object.assign({}, primera, {
        es_multificha: true,
        fichas_por_prenda: fichasPorPrenda,
        total_prendas_general: totalGeneral,
        colaboradores_general: todosColaboradores
    });
};

// ================= RENDERIZAR LA VISTA PREVIA EN TIEMPO REAL (UNA FICHA INDEPENDIENTE POR CADA PRODUCTO) =================
window.actualizarVistaPreviaFichaConfeccion = function() {
    const $contenedor = $('#contenedorVistaPreviaFicha');
    if (!$contenedor.length) return;

    const paquete = window.recolectarDatosFichaConfeccionLive();
    const fichas = Array.isArray(paquete.fichas_por_prenda) && paquete.fichas_por_prenda.length > 0
        ? paquete.fichas_por_prenda
        : [paquete];

    const info = window.infoCotizacionSpecActual || {};
    const esc = s => $('<div>').text(String(s ?? '')).html();
    const catalogoImgs = window.catalogoImagenesSpecActual || [];
    const chkHtml = activo => `<span class="fp-chk-box ${activo ? 'checked' : ''}">${activo ? 'X' : ''}</span>`;
    const nroCotLimpio = String(info.nro_cotizacion || 'S/N').replace(/^#/, '');
    const codigoCortoCot = (nroCotLimpio.match(/(\d+)\s*$/) || [null, nroCotLimpio])[1];

    let htmlTotal = '';

    fichas.forEach((datos, idxFicha) => {
        if (!datos || !datos.modelo) return;

        let urlFrente = '../img/unifuerte.jpg';
        let urlEspalda = '';

        if (datos.foto_frente) {
            const encontradaF = catalogoImgs.find(i => i.valor === datos.foto_frente);
            if (encontradaF) urlFrente = encontradaF.url;
        } else if (catalogoImgs[idxFicha]) {
            urlFrente = catalogoImgs[idxFicha].url;
        } else if (catalogoImgs[0]) {
            urlFrente = catalogoImgs[0].url;
        }

        if (datos.foto_espalda === 'ninguna') {
            urlEspalda = '';
        } else if (datos.foto_espalda) {
            const encontradaE = catalogoImgs.find(i => i.valor === datos.foto_espalda);
            if (encontradaE) urlEspalda = encontradaE.url;
        } else if (catalogoImgs[idxFicha + 1]) {
            urlEspalda = catalogoImgs[idxFicha + 1].url;
        }

        const colabs = datos.colaboradores || [];
        const primerColaborador = colabs.find(c => c.nombre && c.nombre !== 'Sin Nombre Específico') || colabs[0] || { nombre: 'Nombre y Apellido del Colaborador', talla: 'M/L' };

        const numDamaStr = window.formatearTallasNumericasResumen(datos.resumen_tallas.Dama.numericas);
        const numCabStr = window.formatearTallasNumericasResumen(datos.resumen_tallas.Caballero.numericas);
        const numInfStr = window.formatearTallasNumericasResumen(datos.resumen_tallas.Infantil.numericas);

        let filasColaboradoresHtml = '';
        if (colabs.length > 0) {
            filasColaboradoresHtml = colabs.map((c, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td class="text-start fw-bold">${esc(c.nombre)}</td>
                    <td>${esc(c.genero)}</td>
                    <td><span class="badge bg-dark">${esc(c.talla)}</span></td>
                    <td>${c.cantidad}</td>
                    <td class="text-start">${esc(c.prenda)} <small class="text-danger">(${esc(c.tela)})</small></td>
                    <td class="text-start small">${esc(c.obs || '-')}</td>
                    <td class="text-start small"><code>${esc(c.nombre)} | Talla: ${esc(c.talla)} | N° ${esc(codigoCortoCot)}</code></td>
                </tr>
            `).join('');
        } else {
            filasColaboradoresHtml = `<tr><td colspan="8" class="text-muted py-1">Sin líneas asignadas para esta prenda.</td></tr>`;
        }

        htmlTotal += `
        <div class="ficha-piloto-doc mb-4">
            <div class="bg-warning text-dark fw-bold px-2 py-1 mb-2 rounded d-flex justify-content-between align-items-center" style="font-size:11px;">
                <span><i class="bi bi-file-earmark-medical-fill"></i> FICHA PILOTO #${idxFicha + 1} DE ${fichas.length} — PRENDA: ${esc(datos.modelo.toUpperCase())}</span>
                <span class="badge bg-dark">${datos.total_prendas} Pzas</span>
            </div>

            <div class="fp-header">
                <div>
                    <div style="font-size: 15px; font-weight: 900; color: #0f2942; line-height: 1.1;">UNIFUERTE, C.A.</div>
                    <div style="font-size: 12px; font-weight: 800; color: #1e293b;">J-41282764-2</div>
                    <div style="font-size: 8px; color: #64748b; font-weight: 700;">FÁBRICA TEXTIL Y DOTACIONES PERSONALIZADAS</div>
                </div>
                <div class="text-center">
                    <div class="fp-badge-title">ORDEN DE CONFECCIÓN</div>
                    <div class="fp-subtitle-red">FICHA PILOTO #${idxFicha + 1}: ${esc(datos.modelo)}</div>
                </div>
                <div style="font-size: 9.5px; text-align: left; min-width: 165px;">
                    <div><strong>N° Orden:</strong> <span class="text-primary fw-bold">${esc(nroCotLimpio)}</span></div>
                    <div><strong>Emisión:</strong> ${esc(info.fecha_emision || new Date().toLocaleDateString('es-VE'))}</div>
                    <div><strong>Prioridad:</strong> <span class="text-danger fw-bold">[${datos.prioridad === 'Alta' ? 'X' : ' '}] Alta</span> &nbsp; <span class="text-primary fw-bold">[${datos.prioridad !== 'Alta' ? 'X' : ' '}] Normal</span></div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-7">
                    <div class="fp-box h-100 mb-0">
                        <div class="fp-sec-title"><span>1. DATOS DEL CLIENTE Y DESPACHO</span></div>
                        <div class="fp-field-line"><strong>Empresa / Razón Social:</strong> <span>${esc(info.cliente_nombre || 'Por definir')}</span></div>
                        <div class="d-flex gap-2">
                            <div class="fp-field-line flex-grow-1"><strong>RIF / C.I.:</strong> <span>${esc(info.cliente_rif || 'N/A')}</span></div>
                            <div class="fp-field-line flex-grow-1"><strong>Contacto:</strong> <span>${esc((info.cliente_comprador ? info.cliente_comprador + ' / ' : '') + (info.cliente_tlf || ''))}</span></div>
                        </div>
                        <div class="fp-field-line"><strong>Asesor Comercial:</strong> <span class="text-primary fw-bold">${esc(info.vendedor_nombre || $('#nombre_asesor_actual_global').val() || 'Ventas Corporativas')}</span></div>
                        <div class="fp-field-line"><strong>Lugar de Entrega:</strong> <span>${esc(datos.lugar_entrega || 'Por definir')}</span></div>

                        <div class="fp-sec-title mt-2"><span>2. ESPECIFICACIONES DE LA PRENDA (#${idxFicha + 1})</span></div>
                        <div class="fp-field-line"><strong>Modelo:</strong> <span class="fw-bold text-primary">${esc(datos.modelo)}</span></div>
                        <div class="fp-field-line"><strong>Tela Principal:</strong> <span class="fw-bold text-danger">${esc(datos.tela_principal || 'Según especificación')}</span></div>
                        <div class="fp-field-line"><strong>Color / Tono:</strong> <span>${esc(datos.color_tono || 'Corporativo')}</span></div>
                        <div class="fp-field-line mb-0"><strong>Combinación / Cortes:</strong> <span>${esc(datos.combinacion_cortes || 'Estándar')}</span></div>
                    </div>
                </div>
                <div class="col-5">
                    <div class="fp-box h-100 mb-0">
                        <div class="fp-sec-title"><span>MUESTRA DE REFERENCIA (${esc(datos.modelo)})</span></div>
                        <div class="row g-2 text-center">
                            <div class="${urlEspalda ? 'col-6' : 'col-12'}">
                                <div class="small fw-bold text-secondary mb-1" style="font-size:8.5px;">VISTA FRENTE</div>
                                <div style="border: 1px dashed #64748b; border-radius: 4px; padding: 3px; height: 140px; display:flex; align-items:center; justify-content:center; background:#f8fafc;">
                                    <img src="${urlFrente}" style="max-width:100%; max-height:132px; object-fit:contain;">
                                </div>
                            </div>
                            ${urlEspalda ? `
                            <div class="col-6">
                                <div class="small fw-bold text-secondary mb-1" style="font-size:8.5px;">VISTA ESPALDA</div>
                                <div style="border: 1px dashed #64748b; border-radius: 4px; padding: 3px; height: 140px; display:flex; align-items:center; justify-content:center; background:#f8fafc;">
                                    <img src="${urlEspalda}" style="max-width:100%; max-height:132px; object-fit:contain;">
                                </div>
                            </div>` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <div class="fp-box">
                <div class="fp-sec-title"><span>3. DETALLE DE ARMADO Y CONFECCIÓN — ${esc(datos.modelo)}</span></div>
                <div class="fp-check-grid mb-2 pb-1 border-bottom">
                    ${window.CATALOGO_CHECKS_ARMADO.map(([key, label]) => `
                        <div class="fp-check-item">${chkHtml(datos.armado[key])} <span>${esc(label)}</span></div>
                    `).join('')}
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size: 9.5px;">
                    ${window.CATALOGO_CHECKS_PILOTO.map(([key, label]) => `
                        <div class="fp-check-item">${chkHtml(datos.piloto[key])} <span>${esc(label)}</span></div>
                    `).join('')}
                </div>
            </div>

            <div class="fp-box">
                <div class="fp-sec-title"><span>4. DISTRIBUCIÓN DE TALLAS — ${esc(datos.modelo)}</span></div>
                <table class="fp-table-tallas">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Línea / Género</th>
                            <th>XS</th>
                            <th>S</th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                            <th>2XL</th>
                            <th>3XL</th>
                            <th>4XL/5XL</th>
                            <th>En Número (Pantalón / Juvenil)</th>
                            <th>Subtotal</th>
                            <th>Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align:left;">Dama</td>
                            <td>${datos.resumen_tallas.Dama.XS || '-'}</td>
                            <td>${datos.resumen_tallas.Dama.S || '-'}</td>
                            <td>${datos.resumen_tallas.Dama.M || '-'}</td>
                            <td>${datos.resumen_tallas.Dama.L || '-'}</td>
                            <td>${datos.resumen_tallas.Dama.XL || '-'}</td>
                            <td class="text-danger fw-bold">${datos.resumen_tallas.Dama['2XL'] || '-'}</td>
                            <td class="text-danger fw-bold">${datos.resumen_tallas.Dama['3XL'] || '-'}</td>
                            <td class="text-danger fw-bold">${(datos.resumen_tallas.Dama['4XL'] + datos.resumen_tallas.Dama['5XL']) || '-'}</td>
                            <td style="font-size:9px;">${numDamaStr}</td>
                            <td class="fw-bold">[ ${datos.resumen_tallas.Dama.subtotal} ]</td>
                            <td>[ &nbsp; ]</td>
                        </tr>
                        <tr>
                            <td style="text-align:left;">Caballero</td>
                            <td>${datos.resumen_tallas.Caballero.XS || '-'}</td>
                            <td>${datos.resumen_tallas.Caballero.S || '-'}</td>
                            <td>${datos.resumen_tallas.Caballero.M || '-'}</td>
                            <td>${datos.resumen_tallas.Caballero.L || '-'}</td>
                            <td>${datos.resumen_tallas.Caballero.XL || '-'}</td>
                            <td class="text-danger fw-bold">${datos.resumen_tallas.Caballero['2XL'] || '-'}</td>
                            <td class="text-danger fw-bold">${datos.resumen_tallas.Caballero['3XL'] || '-'}</td>
                            <td class="text-danger fw-bold">${(datos.resumen_tallas.Caballero['4XL'] + datos.resumen_tallas.Caballero['5XL']) || '-'}</td>
                            <td style="font-size:9px;">${numCabStr}</td>
                            <td class="fw-bold">[ ${datos.resumen_tallas.Caballero.subtotal} ]</td>
                            <td>[ &nbsp; ]</td>
                        </tr>
                        <tr>
                            <td style="text-align:left;">Infantil / Juvenil</td>
                            <td>${datos.resumen_tallas.Infantil.XS || '-'}</td>
                            <td>${datos.resumen_tallas.Infantil.S || '-'}</td>
                            <td>${datos.resumen_tallas.Infantil.M || '-'}</td>
                            <td>${datos.resumen_tallas.Infantil.L || '-'}</td>
                            <td>${datos.resumen_tallas.Infantil.XL || '-'}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td style="font-size:9px;">${numInfStr}</td>
                            <td class="fw-bold">[ ${datos.resumen_tallas.Infantil.subtotal} ]</td>
                            <td>[ &nbsp; ]</td>
                        </tr>
                        <tr style="background:#f8fafc;">
                            <td colspan="10" style="text-align:right; font-weight:800;">TOTAL ${esc(datos.modelo.toUpperCase())}:</td>
                            <td style="font-weight:900; color:#0f2942;">[ ${datos.total_prendas} ] Pzas</td>
                            <td style="color:#15803d; font-weight:800;">[ ✓ ]</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-7">
                    <div class="fp-box h-100 mb-0">
                        <div class="fp-sec-title"><span>5. DISEÑO FINAL Y PERSONALIZACIÓN DE LOGO</span></div>
                        <div class="d-flex flex-wrap gap-3 mb-1">
                            ${window.CATALOGO_CHECKS_TECNICA.map(([key, label]) => `
                                <div class="fp-check-item">${chkHtml(datos.diseno.tecnicas[key])} <strong>${esc(label)}</strong></div>
                            `).join('')}
                        </div>
                        <div class="fp-field-line">${chkHtml(datos.diseno.frente_derecho_chk)} <strong>Frente Derecho:</strong> <span>${esc(datos.diseno.frente_derecho_txt || 'Sin aplicación')}</span></div>
                        <div class="fp-field-line">${chkHtml(datos.diseno.frente_izquierdo_chk)} <strong>Frente Izquierdo:</strong> <span>${esc(datos.diseno.frente_izquierdo_txt || 'Sin aplicación')}</span></div>
                        <div class="fp-field-line">${chkHtml(datos.diseno.manga_derecha_chk)} <strong>Manga Derecha:</strong> <span>${esc(datos.diseno.manga_derecha_txt || 'Sin aplicación')}</span></div>
                        <div class="fp-field-line">${chkHtml(datos.diseno.manga_izquierda_chk)} <strong>Manga Izquierda:</strong> <span>${esc(datos.diseno.manga_izquierda_txt || 'Sin aplicación')}</span></div>
                        <div class="fp-field-line">${chkHtml(datos.diseno.espalda_superior_chk)} <strong>Espalda Superior:</strong> <span>${esc(datos.diseno.espalda_superior_txt || 'Sin aplicación')}</span></div>
                        <div class="mt-1"><strong>Cantidad de Logos:</strong> <span class="fw-bold">[ ${datos.diseno.cantidad_logos} ] logos por prenda</span></div>
                    </div>
                </div>
                <div class="col-5">
                    <div class="fp-box h-100 mb-0">
                        <div class="fp-sec-title"><span>6. ETIQUETADO DE MARCA</span></div>
                        <div class="d-flex flex-column gap-1">
                            ${window.CATALOGO_CHECKS_ETIQUETAS.map(([key, label]) => `
                                <div class="fp-check-item">${chkHtml(datos.etiquetas[key])} <span>${esc(label)}</span></div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>

            <div class="fp-empaque-box">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong style="color:#0369a1; font-size:10px;">7. EMPAQUE Y CONTROL POR COLABORADOR (${esc(datos.modelo)})</strong>
                    <em style="color:#0284c7; font-weight:700; font-size:9px;">* Entregar siempre con NOTA DE ENTREGA</em>
                </div>
                <div class="d-flex flex-wrap gap-3 mb-1">
                    ${window.CATALOGO_CHECKS_EMPAQUE.map(([key, label]) => `
                        <div class="fp-check-item">${chkHtml(datos.empaque[key])} <strong>${esc(label.replace(' (Individual)', '').replace(' (Entrega Final con todas las bolsas)', ''))}</strong></div>
                    `).join('')}
                </div>
                <div class="d-flex flex-wrap justify-content-between small fw-bold text-dark border-top pt-1">
                    <span>Bolsas Individuales: <span class="badge bg-primary">${datos.empaque.cantidad_bolsas_manual} Bolsas</span></span>
                    <span>Sacos Entrega Final: <span class="badge bg-success">${datos.empaque.saco_plastico ? datos.empaque.cantidad_sacos_manual + ' Saco(s)' : 'No aplica'}</span></span>
                </div>
                <div class="fp-rotulo-preview">
                    Rótulo Adhesivo Exterior: [ ${esc(primerColaborador.nombre)} ] &nbsp;|&nbsp; Talla: [ ${esc(primerColaborador.talla)} ] &nbsp;|&nbsp; N° Cotización: [ ${esc(codigoCortoCot)} ]
                </div>
            </div>

            <div class="fp-box mb-0">
                <div class="fp-sec-title">
                    <span>DESGLOSE NOMINAL: ${esc(datos.modelo.toUpperCase())}</span>
                    <span>Subtotal: ${datos.total_prendas} Pzas</span>
                </div>
                <div class="table-responsive">
                    <table class="fp-table-tallas">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th style="text-align:left;">Colaborador</th>
                                <th>Género</th>
                                <th>Talla</th>
                                <th>Cant.</th>
                                <th style="text-align:left;">Prenda y Tela</th>
                                <th style="text-align:left;">Obs.</th>
                                <th style="text-align:left;">Rótulo Bolsa</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filasColaboradoresHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;
    });

    $contenedor.html(htmlTotal);
};

$(document).on('input change', '#modalEspecificarDetalles input, #modalEspecificarDetalles select, #modalEspecificarDetalles textarea', function() {
    if (this.id === 'fotos_diseno_specs') return;
    window.actualizarVistaPreviaFichaConfeccion();
});

// ================= MODAL DE LA REGLA AZUL (GENERA UNA TABLA Y UNA FICHA PILOTO POR CADA PRENDA) =================
window.abrirModalEspecificarDetalles = function(id_cot, nro, enviarALogistica = false, forzarSinPagoMaster = false, datosCxpMaster = null) {
    enviarLogisticaDespuesDeGuardarSpecs = enviarALogistica;
    window.forzarEnvioMasterSinPago = Boolean(forzarSinPagoMaster);
    window.datosCxpMasterPendiente = datosCxpMaster || null;

    $('#id_cotizacion_especificar').val(id_cot);
    $('#badgeNroCotSpec').text(nro || ('Cotización #' + id_cot));
    $('#cuerpoEspecificaciones').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    $('#contenedorVistaPreviaFicha').html('<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Generando fichas independientes por producto...</div>');
    $('#btnGuardarSpecs').html(enviarALogistica
        ? '<i class="bi bi-check2-circle"></i> Guardar Fichas/Reglas y Enviar a Logística'
        : '<i class="bi bi-floppy-fill"></i> Guardar Tallas, Telas y Fichas de Confección');
    
    $('#fotos_diseno_specs').val('');
    $('#fotos_diseno_previas_regla').empty();
    archivosSpecsMemoria = [];
    window.fotosGuardadasSpecActual = [];
    if($('#lista_fotos_seleccionadas_specs').length > 0) {
        $('#lista_fotos_seleccionadas_specs').empty();
    }

    window.cambiarModoVistaSpec('dividido');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEspecificarDetalles')).show();

    $.get('api/api_cotizaciones.php', { accion: 'detalles_y_recursos', id_cotizacion: id_cot }, function(res) {
        if(res.success) {
            window.infoCotizacionSpecActual = res.cotizacion_info || { nro_cotizacion: nro || ('#' + id_cot) };
            let fichaGuardadaGlobal = {};
            if (res.ficha_confeccion_json) {
                try {
                    fichaGuardadaGlobal = typeof res.ficha_confeccion_json === 'string'
                        ? JSON.parse(res.ficha_confeccion_json)
                        : res.ficha_confeccion_json;
                } catch (e) { fichaGuardadaGlobal = {}; }
            }
            window.fichaConfeccionMemoria = fichaGuardadaGlobal || {};

            if(res.fotos_diseno && res.fotos_diseno !== '') {
                let fArr = res.fotos_diseno.split(',').map(s => s.trim()).filter(Boolean);
                window.fotosGuardadasSpecActual = fArr;
                let fHtml = '<div class="w-100 mb-1"><strong class="small text-success"><i class="bi bi-check-circle-fill"></i> Imágenes/Documentos guardados en este pedido ('+fArr.length+'):</strong></div>';
                fArr.forEach(f => {
                    let ext = f.split('.').pop().toLowerCase();
                    if (ext === 'pdf') {
                        fHtml += `<div class="position-relative shadow-sm me-2 mb-2 p-1 border rounded" style="background:#f0f8ff;">
                                    <a href="../images/ordenes/${f}" target="_blank" class="text-decoration-none text-primary" style="font-size: 0.8rem; font-weight:bold;">
                                        <i class="bi bi-file-pdf-fill text-danger fs-4 align-middle"></i> Documento PDF
                                    </a>
                                  </div>`;
                    } else {
                        fHtml += `<div class="position-relative shadow-sm me-2 mb-2">
                                    <a href="../images/ordenes/${f}" target="_blank">
                                        <img src="../images/ordenes/${f}" style="height: 60px; width: 60px; object-fit: cover; border-radius: 6px; border: 2px solid #0B4C8C;" title="${f}">
                                    </a>
                                  </div>`;
                    }
                });
                $('#fotos_diseno_previas_regla').html(fHtml);
            }

            const buscadorArtes = window.extraerArtesPorPrendaCotizacion(res.data || []);

            const prendasFisicas = (res.data || []).filter(item => {
                let esArte = (item.detalle_diseno && item.detalle_diseno.includes('Ubicación:')) || /^(bordado|serigraf|dtf|vinil)/i.test(String(item.tipo_prenda || '').trim());
                let esExtra = /^tallas?\s+extras?\b/i.test(String(item.tipo_prenda || '').trim());
                return !esArte && !esExtra;
            });
            window.prendasFisicasSpecActual = prendasFisicas;

            let html = '';
            let htmlFichasSeparadas = '';

            prendasFisicas.forEach((item, idxPrenda) => {
                let nombreP = (item.tipo_prenda || '').toLowerCase();
                let esGorra = nombreP.includes('gorra') || nombreP.includes('visera') || nombreP.includes('vicera');
                let esBota = nombreP.includes('bota') || nombreP.includes('zapato') || nombreP.includes('calzado');

                let rangoCotizado = '';
                if (item.detalle_diseno && item.detalle_diseno.includes('Talla:')) {
                    let match = item.detalle_diseno.match(/Talla:\s*([^|]+)/);
                    if (match && match[1]) rangoCotizado = match[1].trim();
                }

                let telaCotizada = '';
                let matchTela = String(item.detalle_diseno || '').match(/\bTela:\s*([^|]+)/i);
                if (matchTela && matchTela[1]) {
                    let telaDetectada = matchTela[1].trim();
                    if (!/^(n\/a|no aplica|no especificada)$/i.test(telaDetectada)) {
                        telaCotizada = telaDetectada;
                    }
                }

                const textoBordadoVinculado = buscadorArtes.obtenerTextoParaPrenda(item.tipo_prenda, item.genero);

                let specs = [];
                let fichaIndividualGuardada = null;
                try {
                    specs = JSON.parse(item.especificaciones_tallas);
                    if (Array.isArray(specs) && specs[0] && specs[0]._ficha_piloto_item) {
                        fichaIndividualGuardada = specs[0]._ficha_piloto_item;
                    }
                } catch(e) {}

                if (!fichaIndividualGuardada && Array.isArray(fichaGuardadaGlobal.fichas_por_prenda)) {
                    fichaIndividualGuardada = fichaGuardadaGlobal.fichas_por_prenda.find(fp => String(fp.id_detalle) === String(item.ID)) || fichaGuardadaGlobal.fichas_por_prenda[idxPrenda] || null;
                } else if (!fichaIndividualGuardada && idxPrenda === 0 && fichaGuardadaGlobal.modelo) {
                    fichaIndividualGuardada = fichaGuardadaGlobal;
                }

                const generoOriginal = String(item.genero || '');
                const generoReglaCotizada = normalizarGeneroReglaTaller(generoOriginal);
                if (!specs || specs.length === 0) {
                    specs = [{ cantidad: item.cantidad || 1, genero: generoReglaCotizada, talla: '', tela: telaCotizada, nombre: '', obs: textoBordadoVinculado }];
                } else if (textoBordadoVinculado) {
                    specs.forEach(s => {
                        const obsActual = String(s.obs || '').trim();
                        if (!obsActual) {
                            s.obs = textoBordadoVinculado;
                        } else if (!obsActual.toLowerCase().includes('bordado') && !obsActual.toLowerCase().includes(textoBordadoVinculado.toLowerCase())) {
                            s.obs = obsActual + ' | ' + textoBordadoVinculado;
                        }
                    });
                }

                const badgeBordado = textoBordadoVinculado
                    ? `<div class="small text-danger fw-bold mt-1"><i class="bi bi-award-fill me-1"></i>Arte/Bordado relacionado: ${textoBordadoVinculado}</div>`
                    : '';

                html += `
                <div class="mb-3 bg-white p-3 border rounded shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 border-bottom pb-2 gap-2">
                        <div>
                            <h6 class="fw-bold m-0" style="color: #0B4C8C;"><i class="bi bi-person-vcard-fill text-warning me-1"></i> Prenda #${idxPrenda + 1}: ${item.tipo_prenda} (${item.genero}) - Cantidad Facturada: <span class="badge bg-danger">${item.cantidad}</span></h6>
                            ${badgeBordado}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="window.agregarFilaSpec(${item.ID}, '${item.genero}', '${item.tipo_prenda.replace(/'/g, "\\'")}', '${rangoCotizado.replace(/'/g, "\\'")}', '${textoBordadoVinculado.replace(/'/g, "\\'")}')"><i class="bi bi-person-plus-fill"></i> + Añadir Persona / Talla</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle m-0" id="tabla_specs_${item.ID}" data-cant-facturada="${item.cantidad}" data-bordado-vinculado="${textoBordadoVinculado.replace(/"/g, '&quot;')}">
                            <thead class="text-muted small border-bottom">
                                <tr>
                                    <th>Cant.</th>
                                    <th>Género / Línea</th>
                                    <th>Talla (Letra / Extra / N°)</th>
                                    <th>Tela / Color</th>
                                    <th>Nombre y Apellido de la Persona</th>
                                    <th>Observación / Detalle</th>
                                    <th class="text-center">Medidas</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                specs.forEach(s => {
                    let genSel = normalizarGeneroReglaTaller(s.genero);
                    let telaVal = telaCotizada || s.tela || '';

                    if (esGorra) { genSel = 'Accesorio'; telaVal = 'No aplica'; }
                    if (esBota) { genSel = 'No aplica'; telaVal = 'No aplica'; }

                    let selectGeneroHtml = `
                        <select class="form-select form-select-sm spec-genero" onchange="window.actualizarTallaSpec(this)" required ${esGorra || esBota ? 'style="pointer-events:none; background-color:#e9ecef;" tabindex="-1"' : ''}>
                            ${esGorra ? `<option value="Accesorio" selected>Accesorio</option>` : ''}
                            ${esBota ? `<option value="No aplica" selected>No aplica</option>` : ''}
                            ${(!esGorra && !esBota) ? `
                            <option value="" ${genSel===''?'selected':''}>Seleccionar género</option>
                            <option value="Dama" ${genSel==='Dama'?'selected':''}>Dama</option>
                            <option value="Caballero" ${genSel==='Caballero'?'selected':''}>Caballero</option>
                            <option value="Juvenil" ${genSel==='Juvenil'?'selected':''}>Juvenil</option>
                            <option value="Niña" ${genSel==='Niña'?'selected':''}>Niña</option>
                            <option value="Niño" ${genSel==='Niño'?'selected':''}>Niño</option>` : ''}
                        </select>
                    `;

                    let inputTelaHtml = (esGorra || esBota) ? 
                        `<input type="text" class="form-control form-control-sm spec-tela" value="No aplica" readonly tabindex="-1">` : 
                        (telaCotizada
                            ? `<input type="text" class="form-control form-control-sm spec-tela border-primary" value="${telaCotizada.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;')}" readonly required>`
                            : window.generarInputTelasLocal(telaVal, item.tipo_prenda));

                    let obsSafe = String(s.obs || '').replace(/"/g, '&quot;');
                    let nomSafe = String(s.nombre || '').replace(/"/g, '&quot;');

                    html += `
                        <tr class="fila-spec" data-id-detalle="${item.ID}" data-tipo-prenda="${item.tipo_prenda.replace(/"/g, '&quot;')}" data-rango-cotizado="${rangoCotizado.replace(/"/g, '&quot;')}" data-genero-cotizado="${generoOriginal.replace(/"/g, '&quot;')}">
                            <td style="width: 70px;"><input type="number" class="form-control form-control-sm text-center spec-cant border-primary fw-bold" value="${s.cantidad}" placeholder="0" min="1" required></td>
                            <td style="width: 130px;">${selectGeneroHtml}</td>
                            <td style="width: 120px;"><select class="form-select form-select-sm border-primary fw-bold spec-talla" data-talla-actual="${s.talla}" required></select></td>
                            <td style="width: 200px;">${inputTelaHtml}</td>
                            <td style="min-width: 165px;"><input type="text" class="form-control form-control-sm spec-nombre border-info fw-bold" value="${nomSafe}" placeholder="Nombre y Apellido"></td>
                            <td><input type="text" class="form-control form-control-sm spec-obs" value="${obsSafe}" placeholder="Observaciones..."></td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-toggle-medidas" title="Especificar medidas de sastrería"><i class="bi bi-rulers"></i></button>
                                <button type="button" class="btn btn-sm text-danger" onclick="$(this).closest('tr').next('.fila-medidas-personales').remove(); $(this).closest('tr').remove(); window.actualizarVistaPreviaFichaConfeccion();"><i class="bi bi-x-circle-fill"></i></button>
                            </td>
                        </tr>${window.generarCamposMedidasPersonalesSpec(s.medidas_personales)}`;
                });
                html += `</tbody></table></div></div>`;

                // Generar su propia Ficha Piloto separada para esta prenda
                htmlFichasSeparadas += window.generarCardFichaPorPrendaHTML(item, idxPrenda, prendasFisicas.length, fichaIndividualGuardada, res.data || [], window.infoCotizacionSpecActual);
            });

            if (html === '') {
                html = '<div class="alert alert-warning text-center fw-bold">No hay prendas físicas facturadas en esta cotización.</div>';
            } else {
                html += `<div class="mt-4 mb-2 p-2 rounded text-white fw-bold d-flex justify-content-between align-items-center" style="background:#0B4C8C;">
                            <span><i class="bi bi-files me-1"></i> FICHAS PILOTO INDEPENDIENTES POR PRODUCTO (${prendasFisicas.length} FICHAS SEPARADAS)</span>
                            <span class="badge bg-warning text-dark">1 Hoja Carta por cada Prenda</span>
                         </div>` + htmlFichasSeparadas;
            }

            $('#cuerpoEspecificaciones').html(html);
            window.actualizarSelectoresFrenteEspalda();
            $('.spec-genero').each(function() { window.actualizarTallaSpec(this, true); });
            window.actualizarVistaPreviaFichaConfeccion();
        } else {
            $('#cuerpoEspecificaciones').html('<div class="alert alert-danger text-center">Error al cargar los detalles: ' + (res.msg || 'respuesta inválida') + '</div>');
        }
    }, 'json').fail(function() {
        $('#cuerpoEspecificaciones').html('<div class="alert alert-danger text-center">No se pudo cargar la cotización.</div>');
    });
};

window.agregarFilaSpec = function(idDetalle, generoDefecto, tipoPrenda, rangoCotizado, obsPorDefecto = '') {
    let bordadoTabla = obsPorDefecto || $(`#tabla_specs_${idDetalle}`).attr('data-bordado-vinculado') || '';
    let nombreP = (tipoPrenda || '').toLowerCase();
    let esGorra = nombreP.includes('gorra') || nombreP.includes('visera') || nombreP.includes('vicera');
    let esBota = nombreP.includes('bota') || nombreP.includes('zapato') || nombreP.includes('calzado');

    let tbody = $(`#tabla_specs_${idDetalle} tbody`);
    let gen = normalizarGeneroReglaTaller(generoDefecto);
    
    if (esGorra) gen = 'Accesorio';
    if (esBota) gen = 'No aplica';

    let selectGeneroHtml = `
        <select class="form-select form-select-sm spec-genero" onchange="window.actualizarTallaSpec(this)" required ${esGorra || esBota ? 'style="pointer-events:none; background-color:#e9ecef;" tabindex="-1"' : ''}>
            ${esGorra ? `<option value="Accesorio" selected>Accesorio</option>` : ''}
            ${esBota ? `<option value="No aplica" selected>No aplica</option>` : ''}
            ${(!esGorra && !esBota) ? `
            <option value="" ${gen===''?'selected':''}>Seleccionar género</option>
            <option value="Dama" ${gen==='Dama'?'selected':''}>Dama</option>
            <option value="Caballero" ${gen==='Caballero'?'selected':''}>Caballero</option>
            <option value="Juvenil" ${gen==='Juvenil'?'selected':''}>Juvenil</option>
            <option value="Niña" ${gen==='Niña'?'selected':''}>Niña</option>
            <option value="Niño" ${gen==='Niño'?'selected':''}>Niño</option>` : ''}
        </select>
    `;

    let telaHeredada = tbody.find('.spec-tela').first().val() || '';
    let esTelaReadonly = tbody.find('.spec-tela').first().prop('readonly') && !esGorra && !esBota;

    let inputTelaHtml = (esGorra || esBota) ? 
        `<input type="text" class="form-control form-control-sm spec-tela" value="No aplica" readonly tabindex="-1">` : 
        (esTelaReadonly
            ? `<input type="text" class="form-control form-control-sm spec-tela border-primary" value="${String(telaHeredada).replace(/"/g, '&quot;')}" readonly required>`
            : window.generarInputTelasLocal(telaHeredada, tipoPrenda));

    let html = `
        <tr class="fila-spec" data-id-detalle="${idDetalle}" data-tipo-prenda="${(tipoPrenda||'').replace(/"/g, '&quot;')}" data-rango-cotizado="${(rangoCotizado||'').replace(/"/g, '&quot;')}" data-genero-cotizado="${(generoDefecto||'').replace(/"/g, '&quot;')}">
            <td style="width: 70px;"><input type="number" class="form-control form-control-sm text-center spec-cant border-primary fw-bold" value="1" min="1" required></td>
            <td style="width: 130px;">${selectGeneroHtml}</td>
            <td style="width: 120px;"><select class="form-select form-select-sm border-primary fw-bold spec-talla" required></select></td>
            <td style="width: 200px;">${inputTelaHtml}</td>
            <td style="min-width: 165px;"><input type="text" class="form-control form-control-sm spec-nombre border-info fw-bold" placeholder="Nombre y Apellido"></td>
            <td><input type="text" class="form-control form-control-sm spec-obs" value="${String(bordadoTabla).replace(/"/g, '&quot;')}" placeholder="Observaciones..."></td>
            <td class="text-center text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-primary btn-toggle-medidas" title="Especificar medidas de sastrería"><i class="bi bi-rulers"></i></button>
                <button type="button" class="btn btn-sm text-danger" onclick="$(this).closest('tr').next('.fila-medidas-personales').remove(); $(this).closest('tr').remove(); window.actualizarVistaPreviaFichaConfeccion();"><i class="bi bi-x-circle-fill"></i></button>
            </td>
        </tr>${window.generarCamposMedidasPersonalesSpec({})}`;
    tbody.append(html);
    const $nuevaFila = tbody.find('.fila-spec').last();
    window.actualizarTallaSpec($nuevaFila.find('.spec-genero')[0], false);
    $nuevaFila.find('.spec-nombre').focus();
    window.actualizarVistaPreviaFichaConfeccion();
};

$(document).on('click', '.btn-toggle-medidas', function() {
    $(this).closest('tr').next('.fila-medidas-personales').toggleClass('d-none');
});

window.actualizarTallaSpec = function(selectElem, init = false) {
    let genero = $(selectElem).val();
    let $tr = $(selectElem).closest('tr');
    let $tallaSelect = $tr.find('.spec-talla');
    let tallaActual = $tallaSelect.data('talla-actual'); 
    let tipoPrenda = $tr.data('tipo-prenda') || '';
    let rangoCotizado = $tr.data('rango-cotizado') || '';
    let generoCotizado = $tr.data('genero-cotizado') || genero;

    let opcionesEspecificas = window.generarOpcionesTallaEspecificas(tipoPrenda, rangoCotizado, genero, generoCotizado);
    if (opcionesEspecificas !== null) {
        $tallaSelect.html(opcionesEspecificas);
    } else {
        let fallbackHTML = genero
            ? window.obtenerOpcionesTallaLocal(genero, tipoPrenda)
            : '<option value="">Selecciona primero el género</option>';
        $tallaSelect.html(fallbackHTML);
    }
    
    if (init && tallaActual) { 
        if ($tallaSelect.find(`option[value="${tallaActual}"]`).length === 0) {
            $tallaSelect.append(`<option value="${tallaActual}">${tallaActual}</option>`);
        }
        $tallaSelect.val(tallaActual); 
    }
    window.actualizarVistaPreviaFichaConfeccion();
};

$(document).on('submit', '#formGuardarEspecificaciones', function(e) {
    e.preventDefault();
    $('#btnGuardarSpecs').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    const fichaConfeccionData = window.recolectarDatosFichaConfeccionLive();
    const mapaFichasPorId = {};
    (fichaConfeccionData.fichas_por_prenda || []).forEach(fp => {
        if (fp && fp.id_detalle) mapaFichasPorId[fp.id_detalle] = fp;
    });

    let payload = [];
    let grupos = {};
    let validacionExitosa = true;
    let mensajeError = '';

    $('.fila-spec').each(function() {
        let idDetalle = $(this).data('id-detalle');
        if(!grupos[idDetalle]) grupos[idDetalle] = { specs: [], totalAsignado: 0 };
        
        let cantAsignada = parseInt($(this).find('.spec-cant').val()) || 0;
        let genero = $(this).find('.spec-genero').val();
        let talla = $(this).find('.spec-talla').val();
        let tela = $(this).find('.spec-tela').val();
        const tipoPrenda = $(this).data('tipo-prenda');
        if (cantAsignada < 1 || !generoValidoParaReglaTaller(tipoPrenda, genero) || !talla || !String(tela || '').trim()) {
            validacionExitosa = false;
            mensajeError = 'Selecciona género, talla y tela, y completa una cantidad mayor que cero en todas las líneas antes de guardar.';
            $(this).addClass('table-danger');
        } else {
            $(this).removeClass('table-danger');
        }

        const itemSpec = {
            cantidad: cantAsignada,
            genero: genero,
            talla: talla,
            tela: tela,
            nombre: $(this).find('.spec-nombre').val().trim(),
            obs: $(this).find('.spec-obs').val().trim(),
            medidas_personales: (function($row) {
                const medidas = {};
                $row.next('.fila-medidas-personales').find('.spec-medida').each(function() {
                    medidas[$(this).data('medida')] = $(this).val() || 0;
                });
                return medidas;
            })($(this))
        };

        // Guardar también la ficha individual en la primera fila de cada detalle_cotizacion
        if (grupos[idDetalle].specs.length === 0 && mapaFichasPorId[idDetalle]) {
            itemSpec._ficha_piloto_item = mapaFichasPorId[idDetalle];
        }

        grupos[idDetalle].specs.push(itemSpec);
        grupos[idDetalle].totalAsignado += cantAsignada;
    });

    $('#cuerpoEspecificaciones table[id^="tabla_specs_"]').each(function() {
        let idDetalle = String(this.id).replace('tabla_specs_', '');
        if (!grupos[idDetalle] || grupos[idDetalle].specs.length === 0) {
            validacionExitosa = false;
            mensajeError = 'Hay prendas sin líneas de talla y tela. Añade al menos una línea para cada prenda.';
        }
    });

    for (let id in grupos) {
        let cantFacturada = parseInt($(`#tabla_specs_${id}`).data('cant-facturada'));
        if (grupos[id].totalAsignado !== cantFacturada) {
            validacionExitosa = false;
            mensajeError = `La cantidad que has especificado (${grupos[id].totalAsignado}) no coincide con la cantidad que el cliente compró (${cantFacturada}) en uno de los renglones. Ajusta los números.`;
            break;
        }
        payload.push({ id_detalle: id, especificaciones: JSON.stringify(grupos[id].specs) });
    }

    if (!validacionExitosa) {
        $('#btnGuardarSpecs').prop('disabled', false).html('<i class="bi bi-floppy-fill"></i> Guardar Tallas, Telas y Fichas de Confección');
        return Swal.fire('Error de Cantidades / Datos', mensajeError, 'error');
    }

    let formData = new FormData(this);
    formData.append('accion', 'guardar_especificaciones');
    formData.append('id_cotizacion', $('#id_cotizacion_especificar').val());
    formData.append('data', JSON.stringify(payload));
    formData.append('ficha_confeccion_json', JSON.stringify(fichaConfeccionData));

    if (archivosSpecsMemoria && archivosSpecsMemoria.length > 0) {
        for (let i = 0; i < archivosSpecsMemoria.length; i++) {
            formData.append('fotos_diseno[]', archivosSpecsMemoria[i]);
        }
    }

    $.ajax({
        url: 'api/api_cotizaciones.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                if (enviarLogisticaDespuesDeGuardarSpecs) {
                    let postParams = {
                        accion: 'enviar_logistica',
                        id_cotizacion: $('#id_cotizacion_especificar').val(),
                        forzar_sin_pago_master: window.forzarEnvioMasterSinPago ? 1 : 0
                    };
                    $.post('api/api_cotizaciones.php', postParams, function(resEnvio) {
                        if (resEnvio.success) {
                            enviarLogisticaDespuesDeGuardarSpecs = false;
                            window.forzarEnvioMasterSinPago = false;
                            $('#modalEspecificarDetalles').modal('hide');
                            Swal.fire('¡Enviado!', resEnvio.msg || 'Las Fichas de Confección fueron guardadas y enviadas a logística.', 'success');
                            if (typeof window.cargarHistorial === 'function') window.cargarHistorial();
                            if (typeof window.chequearCotizacionesPendientes === 'function') window.chequearCotizacionesPendientes();
                        } else {
                            Swal.fire('No se pudo enviar', (resEnvio.msg || ''), 'warning');
                        }
                    }, 'json');
                } else {
                    Swal.fire({icon: 'success', title: 'Fichas Piloto por Producto Guardadas', timer: 1600, showConfirmButton: false});
                    $('#modalEspecificarDetalles').modal('hide');
                }
            } else { Swal.fire('Error', res.msg, 'error'); }
        },
        complete: function() {
            $('#btnGuardarSpecs').prop('disabled', false).html('<i class="bi bi-floppy-fill"></i> Guardar Tallas, Telas y Fichas de Confección');
        }
    });
});

// ================= WHATSAPP CON NOMBRE DE COTIZACIÓN Y ENLACES =================
window.construirUrlCotizacionWA = function(idCotizacion) {
    let basePortal = window.location.origin + window.location.pathname.replace(/\/index\.php$/i, '');
    if (!basePortal.endsWith('/')) basePortal += '/';
    return `${basePortal}factura.php?id=${encodeURIComponent(idCotizacion)}`;
};

window.construirUrlTrackingWA = function(hash) {
    if (!hash) return '';
    let baseRaiz = window.location.origin + window.location.pathname.replace(/portal_ventas\/index\.php$/i, '');
    if (!baseRaiz.endsWith('/')) baseRaiz += '/';
    return `${baseRaiz}envios/tracking.php?h=${encodeURIComponent(hash)}`;
};

window.enviarWAAvance = function(tlfRaw, nombreCliente, hash, idCotizacion, nroCotizacion = '') {
    let tlf = window.formatearNumeroWA(tlfRaw);
    if(!tlf) return Swal.fire('Error', 'El cliente no tiene un número válido.', 'warning');
    let etiquetaCot = nroCotizacion ? `*${nroCotizacion}*` : `*#${idCotizacion}*`;
    let urlCot = window.construirUrlCotizacionWA(idCotizacion);
    let urlTrack = window.construirUrlTrackingWA(hash);
    let msj = `Hola *${nombreCliente}*. Le enviamos un cordial saludo desde *Unifuerte*.\n\nLe notificamos que ha habido un avance en la producción de su pedido correspondiente a la cotización ${etiquetaCot}.\n\n📄 *Ver su Cotización:*\n🔗 ${urlCot}\n\n` + (urlTrack ? `🚚 *Ver progreso y fotografías en tiempo real (Tracking):*\n🔗 ${urlTrack}\n\n` : '') + `¡Gracias por su confianza!`;
    $.post('api/api_cotizaciones.php', {accion: 'limpiar_noti_wa', id: idCotizacion}, function(){ if(typeof window.cargarHistorial === 'function') window.cargarHistorial(); });
    window.open(`https://api.whatsapp.com/send?phone=${tlf}&text=${encodeURIComponent(msj)}`, '_blank');
};

window.enviarWACobro = function(tlfRaw, nombreCliente, nroCotizacion, idCotizacion, hash = '') {
    let tlf = window.formatearNumeroWA(tlfRaw);
    if(!tlf) return Swal.fire('Error', 'El cliente no tiene un número válido.', 'warning');
    let etiquetaCot = nroCotizacion ? `*${nroCotizacion}*` : `*#${idCotizacion}*`;
    let urlCot = window.construirUrlCotizacionWA(idCotizacion);
    let urlTrack = window.construirUrlTrackingWA(hash);
    let msj = `Estimado(a) *${nombreCliente}*, reciba un cordial saludo de parte de *Unifuerte*.\n\nNos complace informarle que la producción de su pedido perteneciente a la cotización ${etiquetaCot} ha sido concluida exitosamente y se encuentra lista para su entrega.\n\n📄 *Enlace de su Cotización:*\n🔗 ${urlCot}\n\n` + (urlTrack ? `🚚 *Enlace de Seguimiento (Tracking):*\n🔗 ${urlTrack}\n\n` : '') + `Para proceder con el despacho o retiro, le agradecemos realizar la cancelación del saldo pendiente. ¡Muchas gracias por su preferencia!`;
    $.post('api/api_cotizaciones.php', {accion: 'limpiar_noti_wa', id: idCotizacion}, function(){ if(typeof window.cargarHistorial === 'function') window.cargarHistorial(); });
    window.open(`https://api.whatsapp.com/send?phone=${tlf}&text=${encodeURIComponent(msj)}`, '_blank');
};

window.enviarWAFinalizado = function(tlfRaw, nombreCliente, hash, idCotizacion, nroCotizacion = '') {
    let tlf = window.formatearNumeroWA(tlfRaw);
    if(!tlf) return Swal.fire('Error', 'El cliente no tiene un número válido.', 'warning');
    let etiquetaCot = nroCotizacion ? `*${nroCotizacion}*` : `*#${idCotizacion}*`;
    let urlCot = window.construirUrlCotizacionWA(idCotizacion);
    let urlTrack = window.construirUrlTrackingWA(hash);
    let msj = `Estimado(a) *${nombreCliente}*. Le notificamos desde *Unifuerte* que la producción de su pedido correspondiente a la cotización ${etiquetaCot} ha concluido exitosamente y está lista.\n\n📄 *Ver su Cotización:*\n🔗 ${urlCot}\n\n` + (urlTrack ? `🚚 *Ver resumen final y Tracking:*\n🔗 ${urlTrack}\n\n` : '') + `Quedamos a su entera disposición para coordinar la entrega.`;
    $.post('api/api_cotizaciones.php', {accion: 'limpiar_noti_wa', id: idCotizacion}, function(){ if(typeof window.cargarHistorial === 'function') window.cargarHistorial(); });
    window.open(`https://api.whatsapp.com/send?phone=${tlf}&text=${encodeURIComponent(msj)}`, '_blank');
};

window.revertirOrden = function(id_orden) {
    Swal.fire({
        title: '¿Revertir Orden a Ventas?',
        html: "Esta acción <b>eliminará la orden de producción</b> actual y devolverá la cotización al vendedor conservando sus fichas y reglas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Destruir y Revertir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/api_ordenes.php', { accion: 'revertir_orden', id: id_orden }, function(res) {
                if(res.success) {
                    Swal.fire('¡Orden Revertida!', res.msg || 'La cotización regresó a Ventas.', 'success');
                    if (typeof window.cargarListaOrdenes === 'function') window.cargarListaOrdenes();
                    if (typeof window.chequearCotizacionesPendientes === 'function') window.chequearCotizacionesPendientes();
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            }, 'json');
        }
    });
};