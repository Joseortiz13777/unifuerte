<?php
session_start();
error_reporting(0);
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/index.php");
    exit();
}
require_once '../conexion.php';
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$id_orden = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT o.*,
               c.nro_cotizacion, c.notas AS notas_cotizacion, c.fotos_diseno AS fotos_cotizacion,
               cli.nombre AS cliente_nombre, cli.cedula_Rif, cli.tlf AS cliente_tlf,
               cli.direccion AS cliente_dir, cli.comprador AS cliente_contacto,
               u.nombre AS v_nombre, u.apellido AS v_apellido
        FROM orden_fabrica o
        LEFT JOIN cotizacion c ON o.id_cotizacion = c.ID
        LEFT JOIN cliente cli ON o.id_cliente = cli.ID
        LEFT JOIN usuario u ON o.id_usuario_vendedor = u.ID
        WHERE o.ID = $id_orden
        LIMIT 1";
$res = mysqli_query($conexion, $sql);
$orden = $res ? mysqli_fetch_assoc($res) : null;

if (!$orden) {
    die("Orden de fábrica no encontrada.");
}

function limpiarTextoNoNulo($valor, string $porDefecto = 'Estándar'): string {
    $v = trim((string)($valor ?? ''));
    if ($v === '' || $v === '-' || $v === '---' || strcasecmp($v, 'null') === 0 || strcasecmp($v, 'undefined') === 0 || strcasecmp($v, 'n/a') === 0) {
        return $porDefecto;
    }
    return $v;
}

function separarTelaYColor(string $textoTela): array {
    $t = trim($textoTela);
    if ($t === '' || $t === '-' || strcasecmp($t, 'n/a') === 0 || strcasecmp($t, 'no aplica') === 0) {
        return ['tela' => '', 'color' => ''];
    }
    if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $t, $m)) {
        return [
            'tela' => trim($m[1]),
            'color' => trim($m[2])
        ];
    }
    return ['tela' => $t, 'color' => ''];
}

function crearMatrizVaciaImp(): array {
    return [
        'Dama' => ['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0, '4XL' => 0, '5XL' => 0, 'numericas' => [], 'subtotal' => 0],
        'Caballero' => ['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0, '4XL' => 0, '5XL' => 0, 'numericas' => [], 'subtotal' => 0],
        'Infantil' => ['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0, '4XL' => 0, '5XL' => 0, 'numericas' => [], 'subtotal' => 0]
    ];
}

function acumularEnMatrizImp(&$matriz, $gen_str, $talla_str, $cant_num) {
    $grupo = 'Caballero';
    if (stripos($gen_str, 'Dama') !== false) {
        $grupo = 'Dama';
    } elseif (stripos($gen_str, 'Niñ') !== false || stripos($gen_str, 'Infantil') !== false || stripos($gen_str, 'Juvenil') !== false) {
        $grupo = 'Infantil';
    }
    $talla_str = strtoupper(trim((string)$talla_str));
    if (in_array($talla_str, ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'], true)) {
        $matriz[$grupo][$talla_str] += $cant_num;
    } elseif ($talla_str !== '') {
        if (!isset($matriz[$grupo]['numericas'][$talla_str])) {
            $matriz[$grupo]['numericas'][$talla_str] = 0;
        }
        $matriz[$grupo]['numericas'][$talla_str] += $cant_num;
    }
    $matriz[$grupo]['subtotal'] += $cant_num;
}

function formatearNumericasImp($arr_num): string {
    if (empty($arr_num) || !is_array($arr_num)) return '-';
    uksort($arr_num, function($a, $b) { return intval($a) <=> intval($b); });
    $partes = [];
    foreach ($arr_num as $t => $c) {
        if ($c > 0) $partes[] = "T" . htmlspecialchars((string)$t) . ":<b>" . intval($c) . "</b>";
    }
    return !empty($partes) ? implode(', ', $partes) : '-';
}

// 1. Leer detalles de la cotización
$detalles_cot = [];
$ficha_desde_cotizacion = [];
$fichas_por_id_detalle = [];
if (!empty($orden['id_cotizacion'])) {
    $id_cot_origen = intval($orden['id_cotizacion']);
    $q_det = mysqli_query($conexion, "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = $id_cot_origen ORDER BY ID ASC");
    if ($q_det) {
        while ($rd = mysqli_fetch_assoc($q_det)) {
            $detalles_cot[] = $rd;
            if (!empty($rd['especificaciones_tallas'])) {
                $sp_temp = json_decode($rd['especificaciones_tallas'], true);
                if (is_array($sp_temp) && !empty($sp_temp[0])) {
                    if (empty($ficha_desde_cotizacion) && !empty($sp_temp[0]['_ficha_piloto']) && is_array($sp_temp[0]['_ficha_piloto'])) {
                        $ficha_desde_cotizacion = $sp_temp[0]['_ficha_piloto'];
                    }
                    if (!empty($sp_temp[0]['_ficha_piloto_item']) && is_array($sp_temp[0]['_ficha_piloto_item'])) {
                        $fichas_por_id_detalle[intval($rd['ID'])] = $sp_temp[0]['_ficha_piloto_item'];
                    }
                }
            }
        }
    }
}

// 2. Leer mediciones personales de la orden
$q_med = mysqli_query($conexion, "SELECT * FROM medicion_personal WHERE id_orden_fabrica = $id_orden ORDER BY ID ASC");
$lista_tallas_nominal = [];
$lista_sastreria = [];
$ficha_desde_orden = [];

if ($q_med) {
    while ($m = mysqli_fetch_assoc($q_med)) {
        $obs = $m['observacion'] ?? '';
        $nom_pers = trim($m['nombre_personal'] ?? '');

        if ($nom_pers === '__FICHA_PILOTO__' || strpos($obs, '[FICHA_JSON]') === 0) {
            $json_str = preg_replace('/^\[FICHA_JSON\]/', '', $obs);
            $dec_ord = json_decode($json_str, true);
            if (is_array($dec_ord)) $ficha_desde_orden = $dec_ord;
            continue;
        }

        if (strpos($obs, '[TALLA:') !== false) {
            preg_match('/\[TALLA:(.*?)\|GEN:(.*?)\|CANT:(.*?)\|PRENDA:(.*?)(?:\|TELA:(.*?))?\]/', $obs, $matches);
            $nota_limpia = trim(preg_replace('/\[TALLA:.*?\]/', '', $obs));
            $persona_nom = $nom_pers;

            if (preg_match('/Persona:\s*(.*?)\.(?:\s*Nota:\s*(.*))?$/i', $nota_limpia, $mPN)) {
                if ($persona_nom === '' || stripos($persona_nom, 'Lote ') === 0) {
                    $persona_nom = trim($mPN[1] ?? '');
                }
                $nota_limpia = trim($mPN[2] ?? '');
            } elseif (stripos($nota_limpia, 'Nota:') === 0) {
                $nota_limpia = trim(substr($nota_limpia, 5));
            }

            $lista_tallas_nominal[] = [
                'talla' => limpiarTextoNoNulo(strtoupper(trim($matches[1] ?? 'M')), 'M'),
                'gen' => limpiarTextoNoNulo($matches[2] ?? 'Caballero', 'Caballero'),
                'cant' => max(1, intval($matches[3] ?? 1)),
                'prenda' => limpiarTextoNoNulo($matches[4] ?? 'Prenda', 'Prenda'),
                'tela' => limpiarTextoNoNulo($matches[5] ?? '', 'Según especificación'),
                'persona' => limpiarTextoNoNulo($persona_nom, 'Por Asignar'),
                'nota' => trim($nota_limpia)
            ];
        } else {
            $m['observacion'] = trim(str_replace('[SASTRERIA]', '', $obs));
            $lista_sastreria[] = $m;
        }
    }
}

// Respaldo desde detalle_cotizacion si medicion_personal estaba vacía
if (empty($lista_tallas_nominal) && !empty($detalles_cot)) {
    foreach ($detalles_cot as $dc) {
        $es_arte = (stripos($dc['detalle_diseno'] ?? '', 'Ubicación:') !== false) || preg_match('/^(bordado|serigraf|dtf|vinil)/i', trim($dc['tipo_prenda'] ?? ''));
        $es_extra = preg_match('/^tallas?\s+extras?\b/i', trim($dc['tipo_prenda'] ?? ''));
        if ($es_arte || $es_extra) continue;

        $tela_cot_base = '';
        if (preg_match('/\bTela:\s*([^|]+)/i', (string)($dc['detalle_diseno'] ?? ''), $mTc)) {
            $tela_cot_base = trim($mTc[1]);
        }

        if (!empty($dc['especificaciones_tallas'])) {
            $sp_arr = json_decode($dc['especificaciones_tallas'], true);
            if (is_array($sp_arr)) {
                foreach ($sp_arr as $sp) {
                    $lista_tallas_nominal[] = [
                        'talla' => limpiarTextoNoNulo(strtoupper(trim($sp['talla'] ?? 'M')), 'M'),
                        'gen' => limpiarTextoNoNulo($sp['genero'] ?? 'Caballero', 'Caballero'),
                        'cant' => max(1, intval($sp['cantidad'] ?? 1)),
                        'prenda' => limpiarTextoNoNulo($dc['tipo_prenda'] ?? 'Prenda', 'Prenda'),
                        'tela' => limpiarTextoNoNulo($sp['tela'] ?? $tela_cot_base, 'Según cotización'),
                        'persona' => limpiarTextoNoNulo($sp['nombre'] ?? '', 'Por Asignar'),
                        'nota' => trim($sp['obs'] ?? '')
                    ];
                }
            }
        } else {
            $lista_tallas_nominal[] = [
                'talla' => 'U',
                'gen' => limpiarTextoNoNulo($dc['genero'] ?? 'Caballero', 'Caballero'),
                'cant' => max(1, intval($dc['cantidad'] ?? 1)),
                'prenda' => limpiarTextoNoNulo($dc['tipo_prenda'] ?? 'Prenda', 'Prenda'),
                'tela' => limpiarTextoNoNulo($tela_cot_base ?: ($dc['detalle_diseno'] ?? ''), 'Estándar'),
                'persona' => 'Lote General',
                'nota' => ''
            ];
        }
    }
}

// 3. Agrupar por Prenda para generar UNA FICHA PILOTO INDEPENDIENTE POR CADA PRODUCTO
$ficha_paquete = !empty($ficha_desde_orden) ? $ficha_desde_orden : $ficha_desde_cotizacion;
$fichas_guardadas_arr = [];
if (!empty($ficha_paquete['fichas_por_prenda']) && is_array($ficha_paquete['fichas_por_prenda'])) {
    $fichas_guardadas_arr = $ficha_paquete['fichas_por_prenda'];
} elseif (!empty($fichas_por_id_detalle)) {
    $fichas_guardadas_arr = array_values($fichas_por_id_detalle);
}

$grupos_por_prenda = [];
foreach ($lista_tallas_nominal as $lt) {
    $key_p = trim($lt['prenda'] ?: 'Prenda General');
    if (!isset($grupos_por_prenda[$key_p])) {
        $grupos_por_prenda[$key_p] = [];
    }
    $grupos_por_prenda[$key_p][] = $lt;
}
if (empty($grupos_por_prenda)) {
    $grupos_por_prenda['Prenda General'] = [];
}

$fichas_para_imprimir = [];
$idx_g = 0;
foreach ($grupos_por_prenda as $nombre_prenda_grupo => $lineas_prenda) {
    $cfg = null;
    foreach ($fichas_guardadas_arr as $fg) {
        if (isset($fg['nombre_prenda']) && mb_strtolower(trim($fg['nombre_prenda']), 'UTF-8') === mb_strtolower($nombre_prenda_grupo, 'UTF-8')) {
            $cfg = $fg;
            break;
        }
    }
    if ($cfg === null && isset($fichas_guardadas_arr[$idx_g])) {
        $cfg = $fichas_guardadas_arr[$idx_g];
    }
    if ($cfg === null && !empty($ficha_paquete)) {
        $cfg = $ficha_paquete;
    }
    if (!is_array($cfg)) $cfg = [];

    $matriz_item = crearMatrizVaciaImp();
    $total_item = 0;
    $telas_puras_detectadas = [];
    $colores_puros_detectados = [];

    foreach ($lineas_prenda as $lp) {
        $total_item += intval($lp['cant']);
        acumularEnMatrizImp($matriz_item, $lp['gen'], $lp['talla'], intval($lp['cant']));
        if (!empty($lp['tela']) && strcasecmp($lp['tela'], 'No aplica') !== 0) {
            $sep = separarTelaYColor($lp['tela']);
            if ($sep['tela'] !== '') $telas_puras_detectadas[] = $sep['tela'];
            if ($sep['color'] !== '') $colores_puros_detectados[] = $sep['color'];
        }
    }

    // Extraer también de la configuración si venía combinada "Tela (Color)"
    $cfg_sep = separarTelaYColor((string)($cfg['tela_principal'] ?? ''));
    $tela_final = limpiarTextoNoNulo(
        $cfg_sep['tela'] ?: implode(' / ', array_unique($telas_puras_detectadas)),
        'Según especificación'
    );
    $color_final = limpiarTextoNoNulo(
        (!empty($cfg['color_tono']) && $cfg['color_tono'] !== '-' && $cfg['color_tono'] !== 'Corporativo')
            ? $cfg['color_tono']
            : ($cfg_sep['color'] ?: implode(' / ', array_unique($colores_puros_detectados))),
        'Tono Corporativo'
    );

    $armado_item = $cfg['armado'] ?? [];
    if (empty($armado_item)) {
        $n_low = mb_strtolower($nombre_prenda_grupo, 'UTF-8');
        if (strpos($n_low, 'manga corta') !== false) $armado_item['manga_corta_normal'] = 1;
        if (strpos($n_low, 'manga larga') !== false) $armado_item['manga_larga_normal'] = 1;
        if (strpos($n_low, 'columbia') !== false) $armado_item['espalda_tipo_columbia'] = 1;
        if (strpos($n_low, 'camisa') !== false || strpos($n_low, 'chemis') !== false) $armado_item['cuello_camisero_clasico'] = 1;
    }

    $fichas_para_imprimir[] = [
        'titulo_prenda' => $nombre_prenda_grupo,
        'modelo' => limpiarTextoNoNulo((!empty($cfg['modelo']) && count($grupos_por_prenda) === 1) ? $cfg['modelo'] : $nombre_prenda_grupo, $nombre_prenda_grupo),
        'tela_principal' => $tela_final,
        'color_tono' => $color_final,
        'combinacion_cortes' => limpiarTextoNoNulo($cfg['combinacion_cortes'] ?? ($orden['notas_cotizacion'] ?? ''), 'Corte Estándar UNIFUERTE'),
        'lugar_entrega' => limpiarTextoNoNulo($cfg['lugar_entrega'] ?? ($orden['cliente_dir'] ?? ''), 'Sede UNIFUERTE / Despacho Acordado'),
        'prioridad_alta' => ($orden['es_express'] == 1) || (($cfg['prioridad'] ?? '') === 'Alta'),
        'foto_frente' => str_replace('guardada:', '', $cfg['foto_frente'] ?? ''),
        'foto_espalda' => str_replace('guardada:', '', $cfg['foto_espalda'] ?? ''),
        'armado' => $armado_item,
        'piloto' => $cfg['piloto'] ?? ['aprobacion_muestra_digital' => 1, 'mantener_medidas_unifuerte' => 1],
        'diseno' => $cfg['diseno'] ?? [],
        'etiquetas' => $cfg['etiquetas'] ?? ['etiqueta_talla_camiseria' => 1, 'banderin_unifuerte' => 1, 'etiqueta_sencamer' => 1],
        'empaque' => $cfg['empaque'] ?? ['bolsa_celofan' => 1, 'saco_plastico' => 1, 'planchado' => 1],
        'matriz_tallas' => $matriz_item,
        'total_prendas' => $total_item,
        'lineas_nominales' => $lineas_prenda
    ];
    $idx_g++;
}

$fotos_str = !empty($orden['fotos_diseno']) ? $orden['fotos_diseno'] : ($orden['fotos_cotizacion'] ?? '');
$todas_fotos = array_values(array_filter(array_map('trim', explode(',', (string)$fotos_str))));
$imagenes_solo = [];
foreach ($todas_fotos as $f_item) {
    if (strtolower(pathinfo($f_item, PATHINFO_EXTENSION)) !== 'pdf') {
        $imagenes_solo[] = $f_item;
    }
}

$nro_cot_str = !empty($orden['nro_cotizacion']) ? $orden['nro_cotizacion'] : $orden['nro_orden_fabrica'];
$codigo_corto_cot = preg_match('/(\d+)\s*$/', (string)$nro_cot_str, $mCod) ? $mCod[1] : $nro_cot_str;

$catalogo_armado_col1 = [
    ['cuello_camisero_clasico', 'Cuello Camisero Clásico'],
    ['cuello_camisero_mao', 'Cuello Camisero Mao'],
    ['cuello_sport_redondo', 'Cuello Sport Redondo'],
    ['cuello_sport_v', 'Cuello Sport "V"'],
    ['manga_corta_normal', 'Manga Corta Normal'],
    ['manga_larga_normal', 'Manga Larga Normal'],
    ['manga_corta_vivos', 'Manga Corta con vivos']
];
$catalogo_armado_col2 = [
    ['manga_3_4_ejecutivo', 'Manga 3/4 Ejecutivo'],
    ['manga_3_4_clasico', 'Manga 3/4 Clásico'],
    ['punos_normales', 'Puños Normales'],
    ['punos_con_vivos', 'Puños con Vivos'],
    ['punos_doble_tono', 'Puños Doble Tono'],
    ['espalda_tipo_columbia', 'Espalda Tipo Columbia'],
    ['espalda_ejecutiva', 'Espalda Ejecutiva']
];
$catalogo_armado_col3 = [
    ['botonadura_escondida', 'Botonadura Escondida'],
    ['cierre_chaqueta_escondido', 'Cierre Chaqueta Escondido'],
    ['bolsillos_tipo_colombia', 'Bolsillos Tipo Colombia'],
    ['dos_bolsillos_tapa_velcro', '2 Bolsillos con Tapa y Velcro'],
    ['dos_bolsillos_tapa_boton', '2 Bolsillos con Tapa y Botón'],
    ['un_bolsillo_ejecutivo_diamante', '1 Bolsillo Ejecutivo Punta Diamante'],
    ['un_bolsillo_interno', '1 Bolsillo Interno'],
    ['dos_bolsillos_internos', '2 Bolsillos Internos']
];
$catalogo_armado_col4 = [
    ['costura_doble', 'Costura Doble'],
    ['costura_ejecutiva', 'Costura Ejecutiva'],
    ['costura_casual', 'Costura Casual'],
    ['hilo_tono_tela', 'Hilo al Tono de la Tela'],
    ['hilo_contraste_tela', 'Hilo al Contraste de la Tela'],
    ['boton_color_tela', 'Botón del Color de la Tela'],
    ['boton_contraste_tela', 'Botón al Contraste de la Tela']
];

function renderChkBox($activo) {
    $on = !empty($activo);
    return '<span class="chk-box ' . ($on ? 'on' : '') . '">' . ($on ? 'X' : '&nbsp;') . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Confección <?= htmlspecialchars($orden['nro_orden_fabrica']) ?></title>
    <link rel="icon" href="../img/camisa.jpg" type="image/jpeg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @page {
            size: letter portrait;
            margin: 4mm 5mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: #cbd5e1;
            color: #0f172a;
            margin: 0;
            padding: 8px 0;
            font-size: 9.2px;
            line-height: 1.15;
        }
        .action-bar {
            max-width: 215.9mm;
            margin: 0 auto 8px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f2942;
            color: #fff;
            padding: 7px 14px;
            border-radius: 5px;
        }
        .btn-bar {
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer;
            font-size: 11px;
            color: #fff;
        }
        .btn-print { background: #FF7518; }
        .btn-pdf { background: #10b981; }
        .btn-close-win { background: #475569; }

        /* HOJA CARTA SIN ALTURA FORZADA PARA NO ESCUPIR HOJAS EN BLANCO */
        .letter-page {
            width: 206mm;
            margin: 0 auto 8px auto;
            background: #ffffff;
            padding: 3.5mm 5mm;
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            page-break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        /* CABECERA SIN TEXTO UNIFUERTE DUPLICADO */
        .fp-header {
            border: 1.5px solid #0f2942;
            padding: 3px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
        }
        .fp-brand-left {
            display: flex;
            align-items: center;
        }
        .fp-brand-left img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        .fp-title-badge {
            background: #0f2942;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            padding: 2.5px 14px;
            border-radius: 3px;
            text-transform: uppercase;
            display: inline-block;
        }
        .fp-subtitle-red {
            color: #b91c1c;
            font-size: 8.5px;
            font-weight: 900;
            margin-top: 1.5px;
            text-transform: uppercase;
        }
        .fp-meta-right {
            font-size: 8.8px;
            line-height: 1.22;
            border-left: 1px solid #cbd5e1;
            padding-left: 8px;
            text-align: left;
        }

        .fp-row-split {
            display: flex;
            gap: 4px;
            margin-bottom: 3px;
        }
        .fp-box {
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 3px 6px;
            margin-bottom: 3px;
            background: #ffffff;
        }
        .fp-sec-bar {
            background: #e9eef5;
            border-left: 3px solid #0f2942;
            color: #0f2942;
            font-weight: 800;
            font-size: 8.6px;
            padding: 1.5px 5px;
            margin-bottom: 2.5px;
            text-transform: uppercase;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fp-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 10px;
        }
        .fp-line {
            border-bottom: 1px dotted #cbd5e1;
            padding: 1px 0;
            display: flex;
            gap: 4px;
            font-size: 8.8px;
            overflow: hidden;
        }
        .fp-line strong {
            color: #0f172a;
            white-space: nowrap;
        }

        .chk-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 9.5px;
            height: 9.5px;
            border: 1.1px solid #0f2942;
            font-size: 7.5px;
            font-weight: 900;
            line-height: 1;
            margin-right: 3px;
            flex-shrink: 0;
        }
        .chk-box.on {
            background: #0f2942;
            color: #ffffff;
        }
        .armado-cols {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5px 5px;
            font-size: 8.3px;
        }
        .chk-row-item {
            display: flex;
            align-items: center;
            padding: 0.4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .fp-table th {
            background: #0f2942;
            color: #ffffff;
            padding: 2px 3px;
            border: 1px solid #0f2942;
            text-align: center;
            font-weight: 800;
        }
        .fp-table td {
            padding: 2px 3px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-weight: 600;
        }

        .fp-empaque-card {
            border: 1.2px solid #0284c7;
            background: #f0f9ff;
            border-radius: 3px;
            padding: 3px 6px;
            margin-bottom: 3px;
        }
        .fp-rotulo-band {
            border: 1px dashed #0284c7;
            background: #ffffff;
            padding: 2px 6px;
            text-align: center;
            font-weight: 800;
            font-size: 8.6px;
            color: #0f172a;
            border-radius: 3px;
            margin-top: 2px;
        }

        .fp-firmas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
            margin-top: 2px;
        }
        .fp-firma-box {
            border: 1px solid #cbd5e1;
            height: 28px;
            padding: 1.5px 4px;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            color: #475569;
        }

        .fp-footer-note {
            display: flex;
            justify-content: space-between;
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print { display: none !important; }
            .letter-page {
                box-shadow: none;
                margin: 0 auto;
                width: 100%;
                padding: 1mm 2mm;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar no-print">
        <div>
            <strong>ORDEN DE CONFECCIÓN: <?= htmlspecialchars($orden['nro_orden_fabrica']) ?></strong>
            <span style="margin-left:10px; font-size:11px; color:#fde047;">
                (<?= count($fichas_para_imprimir) ?> Ficha(s) por Producto — Ultra Compacta sin Hojas Extra)
            </span>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="button" class="btn-bar btn-print" onclick="window.print()">🖨️ Imprimir Fichas</button>
            <button type="button" class="btn-bar btn-pdf" onclick="descargarPDFOrden()">📥 Descargar PDF (Carta)</button>
            <button type="button" class="btn-bar btn-close-win" onclick="window.close()">✖ Cerrar</button>
        </div>
    </div>

    <div id="documentoOrdenCompleta">
        <?php foreach ($fichas_para_imprimir as $idxFicha => $fItem):
            $armado = $fItem['armado'];
            $piloto = $fItem['piloto'];
            $diseno = $fItem['diseno'];
            $tecnicas = $diseno['tecnicas'] ?? [];
            $etiquetas = $fItem['etiquetas'];
            $empaque = $fItem['empaque'];
            $matriz_tallas_db = $fItem['matriz_tallas'];
            $total_prendas_db = $fItem['total_prendas'];
            $lineas_prenda = $fItem['lineas_nominales'];

            $img_frente = '';
            if ($fItem['foto_frente'] !== '' && in_array($fItem['foto_frente'], $imagenes_solo, true)) {
                $img_frente = $fItem['foto_frente'];
            } elseif (!empty($imagenes_solo[$idxFicha])) {
                $img_frente = $imagenes_solo[$idxFicha];
            } elseif (!empty($imagenes_solo[0])) {
                $img_frente = $imagenes_solo[0];
            }

            $img_espalda = '';
            if ($fItem['foto_espalda'] === 'ninguna') {
                $img_espalda = '';
            } elseif ($fItem['foto_espalda'] !== '' && in_array($fItem['foto_espalda'], $imagenes_solo, true)) {
                $img_espalda = $fItem['foto_espalda'];
            } elseif (!empty($imagenes_solo[$idxFicha + 1]) && $imagenes_solo[$idxFicha + 1] !== $img_frente) {
                $img_espalda = $imagenes_solo[$idxFicha + 1];
            }

            $tiene_fotos_ficha = ($img_frente !== '' || $img_espalda !== '');

            $total_bolsas = !empty($empaque['cantidad_bolsas_manual']) ? intval($empaque['cantidad_bolsas_manual']) : $total_prendas_db;
            $cap_saco = !empty($empaque['prendas_por_saco']) ? max(1, intval($empaque['prendas_por_saco'])) : 25;
            $total_sacos = !empty($empaque['cantidad_sacos_manual']) ? intval($empaque['cantidad_sacos_manual']) : (!empty($empaque['saco_plastico']) ? max(1, (int)ceil(max(1, $total_bolsas) / $cap_saco)) : 0);

            $primer_colab = ['persona' => 'Por Asignar', 'talla' => 'M'];
            foreach ($lineas_prenda as $lp) {
                if (!empty($lp['persona']) && $lp['persona'] !== 'Por Asignar' && stripos($lp['persona'], 'Lote ') !== 0) {
                    $primer_colab = $lp;
                    break;
                }
            }
            if ($primer_colab['persona'] === 'Por Asignar' && !empty($lineas_prenda[0])) {
                $primer_colab = $lineas_prenda[0];
            }

            // Filtrar solo ubicaciones de logo que estén marcadas o tengan texto real
            $ubicaciones_logo = [
                ['Frente Derecho', !empty($diseno['frente_derecho_chk']), $diseno['frente_derecho_txt'] ?? ''],
                ['Frente Izquierdo', !empty($diseno['frente_izquierdo_chk']), $diseno['frente_izquierdo_txt'] ?? ''],
                ['Manga Derecha', !empty($diseno['manga_derecha_chk']), $diseno['manga_derecha_txt'] ?? ''],
                ['Manga Izquierda', !empty($diseno['manga_izquierda_chk']), $diseno['manga_izquierda_txt'] ?? ''],
                ['Espalda Superior', !empty($diseno['espalda_superior_chk']), $diseno['espalda_superior_txt'] ?? '']
            ];
            $ubicaciones_activas = [];
            foreach ($ubicaciones_logo as $ul) {
                $txtLimpio = trim((string)$ul[2]);
                if ($txtLimpio === '-' || $txtLimpio === '---' || strcasecmp($txtLimpio, 'Sin aplicación') === 0) {
                    $txtLimpio = '';
                }
                if ($ul[1] || $txtLimpio !== '') {
                    $ubicaciones_activas[] = [$ul[0], $txtLimpio !== '' ? $txtLimpio : 'Aplicar según arte'];
                }
            }
        ?>
        <div class="letter-page <?= $idxFicha > 0 ? 'page-break' : '' ?>">
            <!-- CABECERA COMPACTA SIN REPETIR UNIFUERTE -->
            <div class="fp-header">
                <div class="fp-brand-left">
                    <img src="../img/unifuerte.jpg" alt="Unifuerte, C.A. J-41282764-2" onerror="this.src='../img/camisa.jpg'">
                </div>
                <div style="text-align:center;">
                    <div class="fp-title-badge">ORDEN DE CONFECCIÓN</div>
                    <div class="fp-subtitle-red">FICHA PILOTO #<?= $idxFicha + 1 ?> DE <?= count($fichas_para_imprimir) ?> — <?= htmlspecialchars($fItem['titulo_prenda']) ?></div>
                </div>
                <div class="fp-meta-right">
                    <div><strong>N° Orden:</strong> <span style="color:#b91c1c; font-weight:900;"><?= htmlspecialchars($orden['nro_orden_fabrica']) ?></span> &nbsp;|&nbsp; <strong>Cot:</strong> <?= htmlspecialchars($orden['nro_cotizacion'] ?: $orden['nro_orden_fabrica']) ?></div>
                    <div><strong>Emisión:</strong> <?= !empty($orden['fecha_emision']) ? date('d/m/Y', strtotime($orden['fecha_emision'])) : date('d/m/Y') ?> &nbsp;|&nbsp; <strong>Entrega:</strong> <?= !empty($orden['fecha_entrega']) ? date('d/m/Y', strtotime($orden['fecha_entrega'])) : '-' ?></div>
                    <div>
                        <strong>Prioridad:</strong>
                        <span style="color:#b91c1c; font-weight:800;">[<?= $fItem['prioridad_alta'] ? 'X' : ' ' ?>] Alta</span>
                        &nbsp;
                        <span style="color:#0f2942; font-weight:800;">[<?= !$fItem['prioridad_alta'] ? 'X' : ' ' ?>] Normal</span>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 1 Y 2 COMPACTADAS EN 2 COLUMNAS (Y FOTO SOLO SI EXISTE O RECUADRO COMPACTO) -->
            <div class="fp-row-split">
                <div class="fp-box" style="width: <?= $tiene_fotos_ficha ? '64%' : '76%' ?>; margin-bottom:0;">
                    <div class="fp-sec-bar"><span>1. DATOS DEL CLIENTE Y DESPACHO</span></div>
                    <div class="fp-grid-2col">
                        <div class="fp-line"><strong>Cliente:</strong> <span><?= htmlspecialchars(limpiarTextoNoNulo($orden['cliente_nombre'] ?? '', 'Cliente Corporativo')) ?></span></div>
                        <div class="fp-line"><strong>RIF / C.I.:</strong> <span><?= htmlspecialchars(limpiarTextoNoNulo($orden['cedula_Rif'] ?? '', 'V/J-00000000')) ?></span></div>
                        <div class="fp-line"><strong>Contacto:</strong> <span><?= htmlspecialchars(limpiarTextoNoNulo(trim((!empty($orden['cliente_contacto']) ? $orden['cliente_contacto'] . ' / ' : '') . ($orden['cliente_tlf'] ?? '')), 'Registrado en sistema')) ?></span></div>
                        <div class="fp-line"><strong>Asesor:</strong> <span style="color:#0f2942; font-weight:800;"><?= htmlspecialchars(limpiarTextoNoNulo(trim(($orden['v_nombre'] ?? '') . ' ' . ($orden['v_apellido'] ?? '')), 'Gerencia Comercial')) ?></span></div>
                    </div>
                    <div class="fp-line"><strong>Entrega:</strong> <span><?= htmlspecialchars($fItem['lugar_entrega']) ?></span></div>

                    <div class="fp-sec-bar" style="margin-top:3px;"><span>2. ESPECIFICACIONES DE LA PRENDA (#<?= $idxFicha + 1 ?>)</span></div>
                    <div class="fp-grid-2col">
                        <div class="fp-line"><strong>Modelo:</strong> <span style="color:#0f2942; font-weight:900;"><?= htmlspecialchars($fItem['modelo']) ?></span></div>
                        <div class="fp-line"><strong>Tela Principal:</strong> <span style="color:#b91c1c; font-weight:800;"><?= htmlspecialchars($fItem['tela_principal']) ?></span></div>
                        <div class="fp-line"><strong>Color / Tono:</strong> <span style="font-weight:800;"><?= htmlspecialchars($fItem['color_tono']) ?></span></div>
                        <div class="fp-line"><strong>Combinación / Cortes:</strong> <span><?= htmlspecialchars($fItem['combinacion_cortes']) ?></span></div>
                    </div>
                </div>

                <div class="fp-box" style="width: <?= $tiene_fotos_ficha ? '36%' : '24%' ?>; margin-bottom:0; display:flex; flex-direction:column;">
                    <div class="fp-sec-bar"><span>MUESTRA DE REFERENCIA</span></div>
                    <?php if ($tiene_fotos_ficha): ?>
                    <div style="display:flex; gap:4px; flex:1; align-items:stretch;">
                        <?php if ($img_frente !== ''): ?>
                        <div style="flex:1; border:1px dashed #64748b; border-radius:3px; padding:2px; text-align:center; background:#f8fafc;">
                            <div style="font-size:7.5px; font-weight:800; color:#475569;">FRENTE</div>
                            <img src="../images/ordenes/<?= htmlspecialchars($img_frente) ?>" style="max-width:100%; height:72px; object-fit:contain;">
                        </div>
                        <?php endif; ?>
                        <?php if ($img_espalda !== ''): ?>
                        <div style="flex:1; border:1px dashed #64748b; border-radius:3px; padding:2px; text-align:center; background:#f8fafc;">
                            <div style="font-size:7.5px; font-weight:800; color:#475569;">ESPALDA</div>
                            <img src="../images/ordenes/<?= htmlspecialchars($img_espalda) ?>" style="max-width:100%; height:72px; object-fit:contain;">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div style="flex:1; border:1px dashed #cbd5e1; border-radius:3px; display:flex; align-items:center; justify-content:center; text-align:center; color:#64748b; font-size:8px; padding:4px; background:#f8fafc;">
                        Confección según Ficha Técnica Estándar UNIFUERTE
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECCIÓN 3 COMPACTA -->
            <div class="fp-box">
                <div class="fp-sec-bar">
                    <span>3. DETALLE DE ARMADO Y CONFECCIÓN — <?= htmlspecialchars($fItem['titulo_prenda']) ?></span>
                    <span style="font-size:7.8px; font-weight:700;">TOLERANCIA: ±0.5 CM</span>
                </div>
                <div class="armado-cols" style="border-bottom:1px solid #cbd5e1; padding-bottom:2px; margin-bottom:2px;">
                    <div>
                        <?php foreach ($catalogo_armado_col1 as $item_a): ?>
                            <div class="chk-row-item"><?= renderChkBox($armado[$item_a[0]] ?? 0) ?> <span><?= htmlspecialchars($item_a[1]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <?php foreach ($catalogo_armado_col2 as $item_a): ?>
                            <div class="chk-row-item"><?= renderChkBox($armado[$item_a[0]] ?? 0) ?> <span><?= htmlspecialchars($item_a[1]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <?php foreach ($catalogo_armado_col3 as $item_a): ?>
                            <div class="chk-row-item"><?= renderChkBox($armado[$item_a[0]] ?? 0) ?> <span><?= htmlspecialchars($item_a[1]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <?php foreach ($catalogo_armado_col4 as $item_a): ?>
                            <div class="chk-row-item"><?= renderChkBox($armado[$item_a[0]] ?? 0) ?> <span><?= htmlspecialchars($item_a[1]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; font-size:8.2px;">
                    <div class="chk-row-item"><?= renderChkBox($piloto['aprobacion_muestra_digital'] ?? 0) ?> <span>Muestra Digital Aprobada</span></div>
                    <div class="chk-row-item"><?= renderChkBox($piloto['requiere_muestra_fisica'] ?? 0) ?> <span>Requiere Muestra Física</span></div>
                    <div class="chk-row-item"><?= renderChkBox($piloto['aprobacion_muestra_fisica'] ?? 0) ?> <span>Muestra Física Aprobada</span></div>
                    <div class="chk-row-item"><?= renderChkBox($piloto['mantener_medidas_unifuerte'] ?? 0) ?> <span>Tallaje Exacto UNIFUERTE</span></div>
                    <div class="chk-row-item"><?= renderChkBox($piloto['comprador_ajuste_medidas'] ?? 0) ?> <span>Requiere Ajuste de Medidas</span></div>
                </div>
            </div>

            <!-- SECCIÓN 4: SOLO IMPRIME FILAS DE GÉNERO CON PRENDAS (> 0) PARA NO OCUPAR ESPACIO CON CEROS -->
            <div class="fp-box">
                <div class="fp-sec-bar"><span>4. DISTRIBUCIÓN DE TALLAS Y CANTIDADES — <?= htmlspecialchars($fItem['titulo_prenda']) ?></span></div>
                <table class="fp-table">
                    <thead>
                        <tr>
                            <th style="text-align:left; width:14%;">Línea / Género</th>
                            <th>XS</th>
                            <th>S</th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                            <th>2XL</th>
                            <th>3XL</th>
                            <th>4XL/5XL</th>
                            <th style="width:23%;">Tallas en Número (Pantalón / Juvenil)</th>
                            <th>Subtotal</th>
                            <th style="width:9%;">Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $etiquetas_generos = ['Dama' => 'Dama', 'Caballero' => 'Caballero', 'Infantil' => 'Infantil / Juvenil'];
                        $filas_impresas = 0;
                        foreach ($etiquetas_generos as $keyGen => $lblGen):
                            $filaG = $matriz_tallas_db[$keyGen];
                            if ($filaG['subtotal'] <= 0 && $total_prendas_db > 0) continue;
                            $filas_impresas++;
                        ?>
                        <tr>
                            <td style="text-align:left; font-weight:700;"><?= $lblGen ?></td>
                            <td><?= $filaG['XS'] ? '[ '.$filaG['XS'].' ]' : '-' ?></td>
                            <td><?= $filaG['S'] ? '[ '.$filaG['S'].' ]' : '-' ?></td>
                            <td><?= $filaG['M'] ? '[ '.$filaG['M'].' ]' : '-' ?></td>
                            <td><?= $filaG['L'] ? '[ '.$filaG['L'].' ]' : '-' ?></td>
                            <td><?= $filaG['XL'] ? '[ '.$filaG['XL'].' ]' : '-' ?></td>
                            <td style="color:#b91c1c; font-weight:800;"><?= $filaG['2XL'] ? '[ '.$filaG['2XL'].' ]' : '-' ?></td>
                            <td style="color:#b91c1c; font-weight:800;"><?= $filaG['3XL'] ? '[ '.$filaG['3XL'].' ]' : '-' ?></td>
                            <td style="color:#b91c1c; font-weight:800;"><?= ($filaG['4XL'] + $filaG['5XL']) ? '[ '.($filaG['4XL'] + $filaG['5XL']).' ]' : '-' ?></td>
                            <td style="font-size:8.2px;"><?= formatearNumericasImp($filaG['numericas']) ?></td>
                            <td style="font-weight:800;">[ <?= $filaG['subtotal'] ?> ]</td>
                            <td>[ &nbsp; ]</td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#f8fafc;">
                            <td colspan="10" style="text-align:right; font-weight:900; color:#0f2942;">TOTAL PRENDAS (<?= htmlspecialchars($fItem['titulo_prenda']) ?>):</td>
                            <td style="font-weight:900; font-size:9.5px; color:#0f2942;">[ <?= $total_prendas_db ?> ] Pzas</td>
                            <td style="color:#15803d; font-weight:900;">[ ✓ ]</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECCIÓN 5 Y 6 COMPACTAS (SIN LÍNEAS VACÍAS "---") -->
            <div class="fp-row-split">
                <div class="fp-box" style="width: 62%; margin-bottom:0;">
                    <div class="fp-sec-bar">
                        <span>5. DISEÑO FINAL Y PERSONALIZACIÓN DE LOGO</span>
                        <span>Cant. Logos: [ <?= intval($diseno['cantidad_logos'] ?? count($ubicaciones_activas)) ?> ]</span>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:2px; font-size:8.4px;">
                        <strong>Técnica:</strong>
                        <div class="chk-row-item"><?= renderChkBox($tecnicas['bordado_directo'] ?? 0) ?> <strong>Bordado Directo</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($tecnicas['dtf_textil_hd'] ?? 0) ?> <strong>DTF Textil HD</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($tecnicas['vinil_textil'] ?? 0) ?> <strong>Vinil Textil</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($tecnicas['sublimado'] ?? 0) ?> <strong>Sublimado</strong></div>
                    </div>
                    <?php if (!empty($ubicaciones_activas)): ?>
                    <div class="fp-grid-2col">
                        <?php foreach ($ubicaciones_activas as $ua): ?>
                            <div class="fp-line"><?= renderChkBox(1) ?> <strong><?= htmlspecialchars($ua[0]) ?>:</strong> <span><?= htmlspecialchars($ua[1]) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="fp-line" style="margin-bottom:0;"><strong>Posiciones de Arte:</strong> <span>Sin aplicación de logos adicionales / Confección lisa estándar</span></div>
                    <?php endif; ?>
                </div>

                <div class="fp-box" style="width: 38%; margin-bottom:0;">
                    <div class="fp-sec-bar"><span>6. ETIQUETADO DE MARCA</span></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5px 5px; font-size:8.3px;">
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['etiqueta_talla_camiseria'] ?? 0) ?> <span>Talla Camisería</span></div>
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['etiqueta_talla_deportiva'] ?? 0) ?> <span>Talla Deportiva</span></div>
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['etiqueta_beisbol'] ?? 0) ?> <span>Etiqueta Beisbol</span></div>
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['etiqueta_futbol'] ?? 0) ?> <span>Etiqueta Futbol</span></div>
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['banderin_unifuerte'] ?? 0) ?> <span>Banderín Unifuerte</span></div>
                        <div class="chk-row-item"><?= renderChkBox($etiquetas['etiqueta_sencamer'] ?? 0) ?> <span>Etiqueta Sencamer</span></div>
                        <div class="chk-row-item" style="grid-column: span 2;"><?= renderChkBox($etiquetas['etiqueta_by_claritza'] ?? 0) ?> <span>Etiqueta By Claritza Pinto</span></div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 7 COMPACTA -->
            <div class="fp-empaque-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5px;">
                    <strong style="color:#0369a1; font-size:8.6px; text-transform:uppercase;">7. EMPAQUE Y CONTROL POR COLABORADOR — <?= htmlspecialchars($fItem['titulo_prenda']) ?></strong>
                    <em style="color:#0284c7; font-weight:800; font-size:8px;">* Entregar siempre con NOTA DE ENTREGA</em>
                </div>
                <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:6px; font-size:8.4px;">
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <div class="chk-row-item"><?= renderChkBox($empaque['bolsa_celofan'] ?? 0) ?> <strong>Bolsa Celofán</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($empaque['bolsa_unifuerte'] ?? 0) ?> <strong>Bolsa Unifuerte</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($empaque['bolsa_by_claritza'] ?? 0) ?> <strong>Bolsa Claritza</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($empaque['saco_plastico'] ?? 0) ?> <strong>Saco Plástico</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($empaque['caja_carton'] ?? 0) ?> <strong>Caja Cartón</strong></div>
                        <div class="chk-row-item"><?= renderChkBox($empaque['planchado'] ?? 0) ?> <strong>Planchado</strong></div>
                    </div>
                    <div style="font-weight:800; color:#0f2942;">
                        Bolsas: [ <?= $total_bolsas ?> ] &nbsp;|&nbsp; Sacos Finales: [ <?= !empty($empaque['saco_plastico']) ? $total_sacos : '0' ?> ]
                    </div>
                </div>
                <div class="fp-rotulo-band">
                    Rótulo Adhesivo Exterior: [ <?= htmlspecialchars($primer_colab['persona']) ?> ] &nbsp;|&nbsp; Prenda: [ <?= htmlspecialchars($fItem['titulo_prenda']) ?> ] &nbsp;|&nbsp; Talla: [ <?= htmlspecialchars($primer_colab['talla']) ?> ] &nbsp;|&nbsp; N°: [ <?= htmlspecialchars($codigo_corto_cot) ?> ]
                </div>
            </div>

            <!-- LISTADO NOMINAL PEGADO EN LA MISMA HOJA (HASTA 10 FILAS) -->
            <?php if (!empty($lineas_prenda) && count($lineas_prenda) <= 10): ?>
            <div class="fp-box">
                <div class="fp-sec-bar">
                    <span>LISTADO NOMINAL DE PERSONAL Y RÓTULOS DE BOLSA — <?= htmlspecialchars($fItem['titulo_prenda']) ?></span>
                    <span>TOTAL: <?= $total_prendas_db ?> PZAS</span>
                </div>
                <table class="fp-table">
                    <thead>
                        <tr>
                            <th style="width:4%;">#</th>
                            <th style="text-align:left; width:25%;">Colaborador</th>
                            <th style="width:10%;">Género</th>
                            <th style="width:7%;">Talla</th>
                            <th style="width:6%;">Cant.</th>
                            <th style="text-align:left; width:23%;">Tela / Color / Obs.</th>
                            <th style="text-align:left; width:25%;">Rótulo Adhesivo Bolsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lineas_prenda as $idxLp => $lp): ?>
                        <tr>
                            <td><?= $idxLp + 1 ?></td>
                            <td style="text-align:left; font-weight:800; color:#0f2942;"><?= htmlspecialchars($lp['persona']) ?></td>
                            <td><?= htmlspecialchars($lp['gen']) ?></td>
                            <td style="font-weight:900; color:#b91c1c;"><?= htmlspecialchars($lp['talla']) ?></td>
                            <td><?= intval($lp['cant']) ?></td>
                            <td style="text-align:left; font-size:8px;"><?= htmlspecialchars($lp['tela']) ?><?= (!empty($lp['nota']) && $lp['nota'] !== $lp['persona']) ? ' | ' . htmlspecialchars($lp['nota']) : '' ?></td>
                            <td style="text-align:left; font-family:monospace; font-size:8px; background:#f8fafc;">
                                <?= htmlspecialchars($lp['persona']) ?> | <?= htmlspecialchars($lp['talla']) ?> | N° <?= htmlspecialchars($codigo_corto_cot) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- SECCIÓN 8 COMPACTA -->
            <div class="fp-box" style="margin-bottom:0;">
                <div class="fp-sec-bar" style="margin-bottom:2px;"><span>8. VERIFICACIÓN TÉCNICA Y FIRMAS DE CONFORMIDAD</span></div>
                <div class="fp-firmas">
                    <div class="fp-firma-box">Asesor Comercial</div>
                    <div class="fp-firma-box">Logística Textil</div>
                    <div class="fp-firma-box">Almacén / Corte</div>
                    <div class="fp-firma-box">Control de Calidad y Empaque</div>
                </div>
            </div>

            <div class="fp-footer-note">
                <span><strong>UNIFUERTE, C.A.</strong> | Ficha Piloto #<?= $idxFicha + 1 ?> de <?= count($fichas_para_imprimir) ?> (<?= htmlspecialchars($fItem['titulo_prenda']) ?>)</span>
                <span>Documento obligatorio para inicio de corte y confección</span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- ANEXO ADICIONAL SOLO SI HAY LISTADOS DE MÁS DE 10 PERSONAS O SASTRERÍA EN CM -->
        <?php
        $tiene_listado_largo = false;
        foreach ($fichas_para_imprimir as $fCheck) {
            if (count($fCheck['lineas_nominales']) > 10) $tiene_listado_largo = true;
        }
        if ($tiene_listado_largo || !empty($lista_sastreria)):
        ?>
        <div class="letter-page page-break">
            <div class="fp-header">
                <div class="fp-brand-left">
                    <img src="../img/unifuerte.jpg" alt="Unifuerte" onerror="this.src='../img/camisa.jpg'">
                </div>
                <div style="text-align:center;">
                    <div class="fp-title-badge">ANEXO NOMINAL Y SASTRERÍA</div>
                </div>
                <div class="fp-meta-right">
                    <div><strong>N° Orden Taller:</strong> <?= htmlspecialchars($orden['nro_orden_fabrica']) ?></div>
                    <div><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre'] ?? '') ?></div>
                </div>
            </div>

            <?php if ($tiene_listado_largo): ?>
            <div class="fp-box">
                <div class="fp-sec-bar"><span>LISTADO GENERAL DE COLABORADORES, TALLAS Y RÓTULOS DE BOLSA</span></div>
                <table class="fp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th style="text-align:left;">Colaborador</th>
                            <th>Género</th>
                            <th>Talla</th>
                            <th>Cant.</th>
                            <th style="text-align:left;">Prenda y Tela</th>
                            <th style="text-align:left;">Rótulo Bolsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_tallas_nominal as $idx => $lt): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td style="text-align:left; font-weight:800;"><?= htmlspecialchars($lt['persona']) ?></td>
                            <td><?= htmlspecialchars($lt['gen']) ?></td>
                            <td style="font-weight:900; color:#b91c1c;"><?= htmlspecialchars($lt['talla']) ?></td>
                            <td><?= intval($lt['cant']) ?></td>
                            <td style="text-align:left;"><strong><?= htmlspecialchars($lt['prenda']) ?></strong> (<?= htmlspecialchars($lt['tela']) ?>)</td>
                            <td style="text-align:left; font-family:monospace; font-size:8px;">[ <?= htmlspecialchars($lt['persona']) ?> ] | Talla: [ <?= htmlspecialchars($lt['talla']) ?> ] | N°: [ <?= htmlspecialchars($codigo_corto_cot) ?> ]</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($lista_sastreria)): ?>
            <div class="fp-box">
                <div class="fp-sec-bar"><span>MEDIDAS EXACTAS DE SASTRERÍA (EN CENTÍMETROS)</span></div>
                <table class="fp-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Persona / Prenda</th>
                            <th>Hombro</th>
                            <th>Busto/Tórax</th>
                            <th>Cintura</th>
                            <th>Cadera</th>
                            <th>L. Camisa</th>
                            <th>L. Brazo</th>
                            <th>L. Pant.</th>
                            <th>Tiro</th>
                            <th>Muslo</th>
                            <th>Rodilla</th>
                            <th>Talón</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_sastreria as $s): ?>
                        <tr>
                            <td style="text-align:left; font-weight:800;"><?= htmlspecialchars($s['nombre_personal']) ?></td>
                            <td><?= $s['hombro'] > 0 ? $s['hombro'] : '-' ?></td>
                            <td><?= $s['busto_torax'] > 0 ? $s['busto_torax'] : '-' ?></td>
                            <td><?= $s['cintura'] > 0 ? $s['cintura'] : '-' ?></td>
                            <td><?= $s['cadera'] > 0 ? $s['cadera'] : '-' ?></td>
                            <td><?= $s['largo_camisa'] > 0 ? $s['largo_camisa'] : '-' ?></td>
                            <td><?= $s['largo_brazo'] > 0 ? $s['largo_brazo'] : '-' ?></td>
                            <td><?= $s['largo_pantalon'] > 0 ? $s['largo_pantalon'] : '-' ?></td>
                            <td><?= $s['tiro_total'] > 0 ? $s['tiro_total'] : '-' ?></td>
                            <td><?= $s['ancho_muslo'] > 0 ? $s['ancho_muslo'] : '-' ?></td>
                            <td><?= $s['ancho_rodilla'] > 0 ? $s['ancho_rodilla'] : '-' ?></td>
                            <td><?= $s['ancho_talon'] > 0 ? $s['ancho_talon'] : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function descargarPDFOrden() {
        const elemento = document.getElementById('documentoOrdenCompleta');
        const opt = {
            margin:       [2, 2, 2, 2],
            filename:     'Orden_Confeccion_<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $orden['nro_orden_fabrica']) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'mm', format: 'letter', orientation: 'portrait' },
            pagebreak:    { mode: ['css', 'legacy'] }
        };
        html2pdf().set(opt).from(elemento).save();
    }
    </script>
</body>
</html>