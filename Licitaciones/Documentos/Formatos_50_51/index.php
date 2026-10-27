<?php 
require_once '../../conexion.php'; 

// 1. Capturamos la empresa seleccionada por URL
$empresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';

// 2. Si NO hay empresa seleccionada, mostramos la ventana emergente y detenemos la carga
if (empty($empresa)) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar Empresa</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f3f4f6; font-family: "Inter", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .opcion-empresa { cursor:pointer; padding: 15px; border: 2px solid #e5e7eb; border-radius: 15px; transition: all 0.3s; text-align: center; width: 45%; background: white;}
        .opcion-empresa:hover { transform: scale(1.05); }
        .opcion-unifuerte:hover { border-color: #2563eb; }
        .opcion-marinicol:hover { border-color: #059669; }
        .opcion-empresa img { width: 150px; height: 150px; object-fit: contain; border-radius: 10px; }
        .opcion-empresa p { font-weight: bold; margin-top: 15px; font-size: 1.1rem; color: #111827; }
    </style>
</head>
<body>
    <script>
        Swal.fire({
            title: '¿De qué empresa es este documento?',
            html: `
                <div style="display: flex; justify-content: space-around; gap: 10px; margin-top: 20px;">
                    <div class="opcion-empresa opcion-unifuerte" onclick="window.location.href='?empresa=unifuerte'">
                        <img src="../../img/unifuerte.jpg" alt="Unifuerte">
                        <p>Unifuerte</p>
                    </div>
                    <div class="opcion-empresa opcion-marinicol" onclick="window.location.href='?empresa=marinicol'">
                        <img src="../../img/marinicole.jpg" alt="Marinicol">
                        <p>Marinicol</p>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            width: '600px',
            background: '#ffffff'
        });
    </script>
</body>
</html>
<?php
    exit; // Detiene todo hasta que el usuario elija
}

// 3. Lógica de asignación de datos según la empresa elegida
if ($empresa === 'marinicol') {
    $nombre_empresa = 'MARINICOL Y ASOCIADOS, C.A';
    $rif_empresa = 'J-311662278';
    $direccion_empresa = 'DOMICILIO FISCAL CR UD-321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A, B, C.<br>ZONA ZONA INDUSTRIAL 321, PTO ORDAZ CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/marinicole.jpg';
    $archivo_json = 'formato_50_51_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_50_51_v11.json'; // Nombre para aislar los datos de Unifuerte
}

// Intentar cargar los datos guardados en el JSON si existe
$html_contenido = '';
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Plantilla limpia con la tabla corregida y sin los span que forzaban el ancho
if (empty($html_contenido)) {

    $encabezado = '
        <div class="header-top">
            <div class="logo-container">
                <img src="' . $logo_empresa . '" alt="Logo Empresa" ondblclick="desbloquearEdicion()">
            </div>
            <div class="info-fiscal">
                <b>' . $nombre_empresa . '</b><br>
                RIF: ' . $rif_empresa . '<br>
                ' . $direccion_empresa . '
            </div>
        </div>';

    $html_contenido = '
        <div class="hoja">
            ' . $encabezado . '
            
            <div class="titulo">
                FORMATO I.5. - MODELO DE DOCUMENTO QUE ACREDITA LA FACULTAD DE LA PERSONA QUE FIRMA LA OFERTA<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS COMPLETOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, Ciudad, Municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la Empresa (indicar datos completos de la empresa OFERENTE: Registro Mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del Documento Constitutivo, Acta de Asamblea o Documento Poder que lo faculta para tal tipo de representación) <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relation con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" (nombre del procedimiento)" N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento certifico que:</p>
            
            <p>1.- Las siguientes personas (los "representantes autorizados") han sido debidamente autorizados para actuar en nombre y representación de LA CONTRATISTA, en lo relativo al procedimiento de Concurso <span class="campo-editable">[NOMBRE DEL CONCURSO]</span> y para firmar todos los documentos relacionados con el procedimiento del Concurso a la fecha se encuentran ocupando sus cargos respectivos y la firma que aparece al lado de cada uno de los nombres de los representantes autorizados, es la firma de dichos representantes autorizados:</p>
            
            <table class="tabla-pequena">
                <thead>
                    <tr style="background-color: #e5e7eb; font-weight: bold; text-align: center;">
                        <th style="width: 40%;">NOMBRE</th>
                        <th style="width: 30%;">CARGO</th>
                        <th style="width: 30%;">FIRMA AUTOGRAFIADA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center;">
                            <span class="campo-editable">[NOMBRE Y APELLIDO]</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="campo-editable">[CARGO]</span>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">
                            <span class="campo-editable">[NOMBRE Y APELLIDO]</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="campo-editable">[CARGO]</span>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <p>2.- El suscrito está debidamente autorizado para dar fe, en nombre de la empresa, de la capacidad de los representantes autorizados.</p>
            <p>Todos los términos definidos en el Documento Normativo y utilizados, pero no definidos en este documento, tendrán los significados a ellos asignados en el Documento Normativo.</p>
            <p>En fe de lo anterior, se firma esta certificación.</p>
            
            <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>

        <div class="hoja">
            ' . $encabezado . '
            
            <div class="titulo">
                FORMATO I.6. - DECLARACIÓN JURADA DE LA AUTENTICACIÓN DE LA INFORMACIÓN SUMINISTRADA EN DOCUMENTOS CONSIGNADOS<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS COMPLETOS]</span>, cédula de identidad N° V-<span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, Ciudad, Municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la Empresa (indicar datos completos de la empresa OFERENTE: Registro Mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del Documento Constitutivo, Acta de Asamblea o Documento Poder que lo faculta para tal tipo de representación) <span class="campo-editable">[CLÁUSULAS/DOCUMENTO]</span>, en relación con el Procedimiento de Contratación "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN" (nombre del procedimiento)" N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento <b>Declaro bajo fe de juramento</b>: Que la documentación consignada por mi Representada, es auténtica y ha sido debidamente otorgada por los Organismos Públicos competentes.</p>
            
            <p>Por lo antes expresado, EL ENTE CONTRATANTE, en cualquiera de las fases del procedimiento de contratación, podrán realizar la práctica de las auditorías y/o las verificaciones que considere pertinentes a los fines de comprobar la información suministrada por nuestra empresa. Todo de conformidad a lo previsto en el Artículo 84 numeral 5 del Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP) y su Reglamento.</p>
            
            <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formatos 50-51 - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        * { box-sizing: border-box; } 

        /* Se cambió display flex por block para evitar cortes de hoja en PDF */
        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 8.5in; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin: 0 auto 20px auto; pointer-events: none;}
        
        .hoja { 
            background: white; 
            width: 8.5in; 
            min-height: 11in; 
            padding: 0.8in; 
            box-sizing: border-box; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 9pt; 
            line-height: 1.5; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            /* IMPORTANTE: Se quitó el overflow: hidden; que borraba las hojas en el PDF */
            overflow: visible; 
        }
        .hoja p { text-align: justify; margin-bottom: 10px; }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 25px; }
        .logo-container img { width: 130px; cursor: default; }
        .info-fiscal { text-align: right; font-size: 7pt; line-height: 1.3; max-width: 60%; word-wrap: break-word;}
        
        .titulo { font-size: 10pt; font-weight: bold; text-align: center; margin-bottom: 15px; text-transform: uppercase; }
        
        .campo-editable { 
            background-color: #fef08a; 
            border: 1px dashed #ca8a04; 
            display: inline-block; 
            padding: 2px 4px; 
            border-radius: 3px; 
            color: #111; 
            max-width: 100%; 
            box-sizing: border-box;
            white-space: normal !important; 
            word-wrap: break-word; 
            overflow-wrap: break-word;
        }

        /* =========================================================================
           AQUÍ EDITAS EL ANCHO DE LA TABLA
           Si la quieres más pequeña cambia el 75% por 60% o 50%
           ========================================================================= */
        .tabla-pequena {
            width: 75% !important; 
            margin: 15px auto !important; /* El 'auto' asegura que se centre perfectamente */
            table-layout: fixed !important; 
            border-collapse: collapse !important;
            font-size: 8pt !important; 
        }
        
        .tabla-pequena th, 
        .tabla-pequena td {
            padding: 5px !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important; 
            border: 1px solid #000 !important;
        }
        
        .firmas { margin-top: 40px; text-align: left; }
        .linea-firma { width: 250px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover, .btn-pdf:hover { opacity: 0.9; }

        @page { size: letter; margin: 1.5cm; } 
        
        /* REGLAS ESTRICTAS DE IMPRESIÓN PARA MOSTRAR TODAS LAS HOJAS */
        @media print {
            body { font-family: 'Arial', sans-serif; font-size: 9pt; background: white; margin: 0; padding: 0; }
            html, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; 
                height: auto !important; 
                position: static !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                background: transparent !important; 
                overflow: visible !important;
            }
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                width: 100% !important; 
                height: auto !important; 
                margin: 0 !important; 
                padding: 0.8in !important; 
                box-shadow: none !important; 
                border: none !important; 
                page-break-after: always !important; 
                overflow: visible !important; /* Desbloquea la impresión multihilo */
            }
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important; }
            ::-webkit-scrollbar { display: none !important; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-bold ph-factory"></i></div> 
            <?php echo ($empresa === 'marinicol') ? 'Marinicol' : 'Unifuerte'; ?>
        </div>
        <nav class="nav-menu">
            <a href="../../../portal_ventas/index.php?modulo=licitaciones" class="nav-item">
                <i class="ph ph-arrow-left"></i> Volver a Menú
            </a>
            <a href="index.php" class="nav-item" style="margin-top: 15px;">
                <i class="ph ph-arrows-left-right"></i> Cambiar Empresa
            </a>
        </nav>
    </aside>

    <main class="main-content" style="padding: 0;">
        <div class="editor-container">
            <div class="toolbar">
                <div style="display:flex; gap: 10px;">
                    <button id="btn-guardar" class="btn-guardar" onclick="guardarCambios()"><i class="ph-bold ph-floppy-disk"></i> Guardar Cambios</button>
                    <button class="btn btn-pdf" onclick="window.print()"><i class="ph-bold ph-file-pdf"></i> Imprimir / Guardar PDF</button>
                </div>
            </div>

            <div id="documento-editable" contenteditable="false">
                <?php echo $html_contenido; ?>
            </div>
        </div>
    </main>

    <script>
        function desbloquearEdicion() {
            Swal.fire({
                title: 'Desbloquear Edición',
                html: `
                    <div style="position: relative; width: 100%; max-width: 260px; margin: 15px auto;">
                        <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña..." style="width: 100%; margin: 0; padding-right: 40px; box-sizing: border-box;">
                        <i class="ph-bold ph-eye" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 22px; color: #6b7280;"></i>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Desbloquear',
                confirmButtonColor: '#059669',
                didOpen: () => {
                    const toggleBtn = document.getElementById('togglePassword');
                    const passInput = document.getElementById('swal-input-password');
                    toggleBtn.addEventListener('click', function() {
                        const isPassword = passInput.getAttribute('type') === 'password';
                        passInput.setAttribute('type', isPassword ? 'text' : 'password');
                        this.classList.toggle('ph-eye');
                        this.classList.toggle('ph-eye-closed');
                    });
                },
                preConfirm: () => {
                    return document.getElementById('swal-input-password').value;
                }
            }).then((result) => {
                if (result.value === 'clarita') {
                    document.getElementById('documento-editable').contentEditable = "true";
                    document.getElementById('btn-guardar').style.display = "inline-flex";
                    Swal.fire({
                        icon: 'success',
                        title: 'Modo edición activado',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Contraseña incorrecta',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }

        function guardarCambios() {
            let contenido = document.getElementById('documento-editable').innerHTML;
            let archivoDestino = '<?php echo $archivo_json; ?>';

            fetch('guardar_datos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ html: contenido, archivo: archivoDestino })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cambios guardados',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: data.message
                    });
                }
            });
        }
    </script>
</body>
</html>