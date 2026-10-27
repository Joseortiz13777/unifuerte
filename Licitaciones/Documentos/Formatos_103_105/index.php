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
    $archivo_json = 'formato_103_105_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'formato_103_105_data.json'; // Mantenemos el nombre original para Unifuerte
}

// Intentar cargar los datos guardados en el JSON si existe
$html_contenido = '';
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, cargamos la plantilla para las páginas 103 a 105
if (empty($html_contenido)) {

    $encabezado = '
        <div class="header-top">
            <div class="logo-container">
                <img src="' . $logo_empresa . '" alt="Logo ' . $nombre_empresa . '" ondblclick="desbloquearEdicion()">
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
                FORMATO II.10.- DECLARACIÓN BAJO FE DE JURAMENTO SOBRE LA COMPOSICIÓN DEL CAPITAL SOCIAL DE LA EMPRESA<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS COMPLETOS]</span>, cédula de identidad N° <span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, ciudad, municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la empresa (indicar datos completos de la empresa oferente: registro mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del documento constitutivo, acta de asamblea o documento poder que lo faculta para tal tipo de representación) <span class="campo-editable">[DOCUMENTO CONSTITUTIVO/PODER]</span>, en relación con el procedimiento de contratación <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b> N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro bajo fe de juramento y sujeto a las sanciones previstas en el Decreto con Rango, Valor y Fuerza de Ley de Contrataciones Públicas (DRVFLCP), que: la información que a continuación se señala es cierta y que la misma se adecúa fielmente a la composición de la empresa y a los porcentajes nacionales que se aplicarán en caso de otorgarse a mi representada la contratación a que se contrae.</p>
            
            <p>INFORMACIÓN REQUERIDA:</p>
            <p>COMPOSICIÓN DEL CAPITAL SOCIAL DE LA EMPRESA</p>
            <p>Capital de la Empresa</p>
            <p>Monto de Capital Social de la Empresa: Bs. <span class="campo-editable">[MONTO CAPITAL]</span></p>
            <p>Monto del Capital Social Nacional: Bs. <span class="campo-editable">[MONTO NACIONAL]</span></p>
            <p>Monto del Capital Social Extranjero: Bs. <span class="campo-editable">[MONTO EXTRANJERO]</span></p>
            <p>(En caso de ser necesaria la utilización de otra moneda, favor indicar la misma) <span class="campo-editable">[NO APLICA]</span></p>
            
            <p>Autorizo de manera expresa e irrevocable a <b>EL ENTE CONTRATANTE</b> para que esta información sea investigada y verificada en cualquier momento del proceso de Contratación y durante la ejecución del contrato y en caso de que la misma sea falsa o no se ajuste a los porcentajes antes señalados <b>EL ENTE CONTRATANTE</b>, sin perjuicio del ejercicio de las sanciones legales podrá optar entre descontar o recobrar lo pagado de más, producto de la aplicación de las Medidas o rescindir el convenio otorgado, sin pago de indemnización alguna.</p>
            
            <p>Asimismo, me comprometo a que los subcontratistas que sean requeridos para la ejecución del Convenio cumplirán al menos con los porcentajes de participación nacional antes señalados.</p>
            
            <p style="margin-top: 15px;">En (ciudad) <span class="campo-editable">[CIUDAD GUAYANA]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>
            
            <p style="margin-top: 15px;"><b>INSTRUCCIONES GENERALES:</b></p>
        </div>

        <div class="hoja">
            ' . $encabezado . '
            
            <p><b>1.</b> La declaración jurada debe ser hecha por el declarante bajo juramento de decir verdad, en papel común y sin timbres fiscales o estampillas.</p>
            
            <p><b>2.</b> No requiere la firma de personas distintas del declarante, ni ser autenticada ante oficinas públicas.</p>
            
            <p><b>3.</b> El declarante debe estampar su firma en original al final de la declaración y en cada una de las páginas que la conformen.</p>
            
            <p><b>4.</b> La omisión de este formato implicará la pérdida del beneficio de las Medidas, pero el Oferente continuará participando en el proceso de contratación.</p>
            
            <p>Esta información deberá formar parte integrante del sobre contentivo de la oferta técnica y será utilizada como criterio de evaluación de las ofertas.</p>
        </div>

        <div class="hoja">
            ' . $encabezado . '
            
            <div class="titulo">
                FORMATO II.11. - DECLARACIÓN JURADA DE DISPONIBILIDAD PARA EL INICIO DE LA ACTIVIDAD DE LA CONTRATACIÓN.<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS COMPLETOS]</span>, cédula de identidad N° <span class="campo-editable">[00000000]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, estado civil <span class="campo-editable">[ESTADO CIVIL]</span>, profesión (u ocupación) <span class="campo-editable">[PROFESIÓN]</span>, domiciliado en (dirección completa, ciudad, municipio y estado) <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, actuando en este acto en mi carácter de (indicar cargo) <span class="campo-editable">[CARGO]</span>, de la empresa (indicar datos completos de la empresa oferente: registro mercantil) <span class="campo-editable">' . $nombre_empresa . '</span> ("el oferente") debidamente facultado para este acto por (indicar las cláusulas del documento constitutivo, acta de asamblea o documento poder que lo faculta para tal tipo de representación) <span class="campo-editable">[DOCUMENTO CONSTITUTIVO/PODER]</span>, en relación con el procedimiento de contratación <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b> N° A-182-26-<span class="campo-editable">0394</span>, referente a la (adquisición de bienes, prestación de servicio o ejecución de obra) <span class="campo-editable">adquisición de bienes</span>, llevado a efecto por EL ENTE CONTRATANTE, por el presente documento declaro bajo fe de juramento y sujeto a las sanciones previstas en el DRVFLCP y su reglamento vigente que la empresa a la cual represento tiene la disponibilidad de iniciar la ejecución de la obra en (colocar en letras) <span class="campo-editable">CERO</span> (colocar en números) <span class="campo-editable">0</span> días (inmediatamente).</p>
            
            <p style="margin-top: 30px;">En (ciudad) <span class="campo-editable">[CIUDAD GUAYANA]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>
            
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
    <title>Formatos 103-105 - PDVSA <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        .editor-container { background-color: #e5e7eb; padding: 40px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 850px; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin-bottom: 20px; pointer-events: none;}
        
        /* HOJA TIPO CARTA ESTRICTA - LETRA AÚN MÁS PEQUEÑA (9pt) y Menos Padding */
        .hoja { background: white; width: 8.5in; min-height: 11in; padding: 0.6in 0.8in; box-sizing: border-box; box-shadow: 0 5px 10px rgba(0,0,0,0.2); margin: 0 auto 30px auto; font-family: 'Arial', sans-serif; font-size: 9pt; line-height: 1.3; outline: none; position: relative; page-break-after: always; text-align: justify; }
        .hoja:last-child { page-break-after: auto; }
        .hoja p { text-align: justify; margin-bottom: 8px; }

        /* ENCABEZADO CON FLEXBOX ALINEADO AL FONDO (bottom) */
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
        .logo-container img { width: 130px; cursor: default; } /* Ligeramente más pequeño el logo */
        .info-fiscal { text-align: right; font-size: 7.5pt; line-height: 1.2; max-width: 65%; word-wrap: break-word;}
        
        /* TÍTULOS PERFECTAMENTE CENTRADOS */
        .titulo { font-size: 9.5pt; font-weight: bold; text-align: center; margin-bottom: 15px; text-transform: uppercase; }
        
        .campo-editable { background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; }
        
        .firmas { margin-top: 30px; text-align: left; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        /* BOTONES FLOTANTES */
        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover { opacity: 0.9; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf:hover { opacity: 0.9; }

        @page { size: letter; margin: 0; }
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { display: block !important; height: auto !important; overflow: visible !important; position: static !important; margin: 0 !important; padding: 0 !important; background: white !important;}
            .toolbar, .sidebar, .header { display: none !important; }
            .hoja { box-shadow: none !important; margin: 0 !important; padding: 0.6in 0.8in !important; width: 8.5in !important; height: 11in !important; page-break-after: always !important; border: none !important; box-sizing: border-box !important;}
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important;}
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