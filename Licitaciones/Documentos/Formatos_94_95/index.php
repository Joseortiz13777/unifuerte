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
    $archivo_json = 'formato_94_95_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v7.json'; // Mantenemos v7 para Unifuerte
}

$html_contenido = '';

// Intentar cargar los datos guardados en el JSON si existe
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, generamos la plantilla exacta de las pág 94 y 95
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
                FORMATO II.4. - DECLARACIÓN JURADA DEL PORCENTAJE DEL VALOR AGREGADO NACIONAL (VAN) Y DE PARTICIPACIÓN DE PYMI, COOPERATIVA Y/O GRAN EMPRESA EN ALIANZA<br><br>
                PROCESO DE CONTRATACIÓN N°. A-182-26-<span class="campo-editable">0394</span><br>
                REFERIDO A: "ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES<br>ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"
            </div>
            
            <p>Yo, <span class="campo-editable">[NOMBRES Y APELLIDOS COMPLETOS]</span>, de nacionalidad <span class="campo-editable">[NACIONALIDAD]</span>, con cédula de identidad N° <span class="campo-editable">[00000000]</span>, domiciliado en <span class="campo-editable">[DIRECCIÓN COMPLETA]</span>, en mi carácter de <span class="campo-editable">[CARGO O PUESTO QUE OCUPA]</span>, de la sociedad mercantil <span class="campo-editable">' . $nombre_empresa . '</span> domiciliada en <span class="campo-editable">[DIRECCIÓN EMPRESA]</span> e inscrita en el Registro Mercantil <span class="campo-editable">[REGISTRO]</span> de la Circunscripción Judicial del <span class="campo-editable">[CIRCUNSCRIPCIÓN]</span>, en fecha <span class="campo-editable">[FECHA]</span> bajo el Nro. <span class="campo-editable">[NÚMERO]</span>, tomo <span class="campo-editable">[TOMO]</span>, suficientemente autorizado para este acto por <span class="campo-editable">[ESTATUTOS DE LA EMPRESA / ACTA / PODER]</span>, declaro bajo fe de juramento y sujeto a las sanciones previstas en la Ley de Contrataciones Públicas y su Reglamento vigente que: los componentes para el autocálculo del porcentaje de "Valor Agregado Nacional" (VAN) de la oferta para el procedimiento de contratación en referencia, que tiene por objeto <b>"ADQUISICIÓN DE CHAQUETAS Y GORRAS PARA LOS TRABAJADORES ADSCRITOS A LA DIRECCIÓN DE PRODUCCIÓN"</b>, de fecha <span class="campo-editable">[FECHA]</span>, resulta de <span class="campo-editable">[PORCENTAJE]</span>% (NOTA: si la oferta contempla varios renglones o Posiciones, presentar %VAN ponderado o hacer referencia a los % indicados en el Anexo que corresponda al autocálculo del VAN por Renglón o Posición), de conformidad con el formato para el autocálculo del % VAN de la oferta.</p>

            <p>Declaro, que la empresa por mí representada califica como: (se indica con "x", la categoría a la que corresponde).</p>
            <ul style="list-style:none; padding-left: 0; line-height: 1.8;">
                <li><span class="campo-editable">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Cooperativa</li>
                <li><span class="campo-editable">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Pequeña y Mediana Industria (PyMI)</li>
                <li><span class="campo-editable">&nbsp;&nbsp;&nbsp;&nbsp;</span> - Alianza, Consorcio o Asociación, se acompaña la participación porcentual de las empresas en el precio de la oferta, con la documentación de soporte requerida, con información sobre aportes y responsabilidades de cada una de las empresas en alianza o consorcio. (Opcional Gran Empresa)</li>
            </ul>

            <p><b>Determinación del tipo de Alianza, Consorcio o Asociación:</b></p>
            <p>A continuación, se especifica la información solicitada, a fin de determinar si la oferta es presentada por una o más Pequeñas y Medianas Industria (PyMI\'s), Cooperativas o cualquier Otra Forma Asociativa para el trabajo en Alianza, Consorcio o Asociación; o si se trata de una Gran Empresa en Alianza o Consorcio o Asociación con una o más PyMI\'s, Cooperativas o cualquier Otra Forma Asociativa para el trabajo. La Alianza, Consorcio o Asociación pueden estar conformados por cualquiera de las figuras jurídicas mencionadas anteriormente; se debe especificar la conformación de la asociación y el porcentaje de participación de cada una de ellas.</p>

            <p><b>Tipo de Asociación: (se indica con "X", la categoría a la que corresponde):</b></p>
            <ul style="list-style:none; padding-left: 0; line-height: 1.8;">
                <li><span class="campo-editable">&nbsp;&nbsp;&nbsp;&nbsp;</span> Empresa PyMI, Cooperativa u Otra Forma Asociativa, en Alianza, Consorcio o Asociación, con una o más PyMI\'s, Cooperativas u Otra Forma Asociativa.</li>
                <li><span class="campo-editable">&nbsp;&nbsp;&nbsp;&nbsp;</span> Gran Empresa en Alianza, Consorcio o Asociación con una o más PyMI\'s, Cooperativas u Otra Forma Asociativa.</li>
            </ul>

            <p><b>Nivel de aporte de cada figura jurídica dentro de la Alianza, Consorcio o Asociación:</b></p>
        </div>

        <div class="hoja">
            ' . $encabezado . '
            <table class="tabla-pdf" style="margin-top: 0 !important;">
                <colgroup><col style="width: 40%;"><col style="width: 40%;"><col style="width: 20%;"></colgroup>
                <thead>
                    <tr>
                        <th>TIPO DE EMPRESA</th>
                        <th>PORCENTAJE (%) PARTICIPACION PyMI</th>
                        <th>VAN(%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td style="text-align: left !important; font-weight: bold; padding-left: 10px !important;">Nombre Gran Empresa</td><td><div class="campo-editable-tabla"><br></div></td><td><div class="campo-editable-tabla"><br></div></td></tr>
                    <tr><td style="text-align: left !important; font-weight: bold; padding-left: 10px !important;">Nombre Empresa PyMI</td><td><div class="campo-editable-tabla"><br></div></td><td><div class="campo-editable-tabla"><br></div></td></tr>
                    <tr><td style="text-align: left !important; font-weight: bold; padding-left: 10px !important;">Nombre Cooperativa</td><td><div class="campo-editable-tabla"><br></div></td><td><div class="campo-editable-tabla"><br></div></td></tr>
                    <tr><td style="text-align: left !important; font-weight: bold; padding-left: 10px !important;">Nombre Otra Forma Asociativa</td><td><div class="campo-editable-tabla"><br></div></td><td><div class="campo-editable-tabla"><br></div></td></tr>
                    <tr><td style="text-align: left !important; font-weight: bold; padding-left: 10px !important;">TOTAL, OFERTA (%)</td><td><div class="campo-editable-tabla"><br></div></td><td><div class="campo-editable-tabla"><br></div></td></tr>
                </tbody>
            </table>

            <p>Se anexa copia del acuerdo formal de la Alianza, Consorcio o Asociación <b>(Cuando aplique)</b> Autorizo de manera expresa e irrevocable a <b>BARIVEN, S.A.</b> o a quién ésta designe, a realizar el examen de documentos, inspección o auditorias necesarias para confirmar la veracidad de los datos suministrados dentro de los dos (2) años siguientes a la presentación de la oferta.</p>
            <p>En caso de que la información suministrada resulte ser falsa o no se ajuste a los porcentajes antes señalados, <b>BARIVEN, S.A.</b>, sin perjuicio del ejercicio de las sanciones legales, podrá rescindir el contrato suscrito, con o sin pago de indemnización por daños y perjuicios, o requerir la repetición del monto correspondiente a la diferencia entre la mejor oferta antes de aplicar las preferencias y el precio efectivamente contratado con motivo de las preferencias otorgadas.</p>
            <p>Asimismo, me comprometo a que los subcontratistas que sean requeridos para la ejecución del trabajo objeto del contrato, cumplan al menos con los porcentajes de participación de Valor Agregado Nacional (VAN) señalados.</p>
            <p>Declaración que hago a los fines pertinentes.</p>

            <p>En (ciudad) <span class="campo-editable">[CIUDAD]</span>, a los <span class="campo-editable">[DÍA]</span> días del mes de <span class="campo-editable">[MES]</span> de 20<span class="campo-editable">[AÑO]</span>.</p>

            <div class="firmas">
                <div class="linea-firma"></div>
                <b>FIRMA DEL REPRESENTANTE</b><br>
                C.I. REPRESENTANTE LEGAL O PERSONA AUTORIZADA<br>
                SELLO DE LA CONTRATISTA
            </div>

            <div style="margin-top: 40px;">
                <b style="font-size: 10pt;">INSTRUCCIONES GENERALES:</b><br>
                <ol style="padding-left: 20px; font-size: 9pt; text-align: justify; line-height: 1.5;">
                    <li>La Declaración Jurada debe ser hecha por el declarante bajo juramento de decir verdad, en papel común y sin timbres fiscales o estampillas.</li>
                    <li>No requiere la firma de personas distintas del declarante, ni ser autenticada ante oficinas públicas.</li>
                    <li>El declarante debe estampar su firma en original al final de la declaración y en cada una de las páginas que la conformen.</li>
                    <li>La omisión de este formato implicará la pérdida del beneficio de las Medidas, pero el Oferente continuará participando en el proceso de selección de contratistas.</li>
                </ol>
            </div>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Formatos 94-95 - <?php echo $nombre_empresa; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../estilos.css">
    <style>
        *, *:before, *:after { box-sizing: border-box !important; }

        .editor-container { background-color: #e5e7eb; padding: 40px; display: block; min-height: 100vh; }
        .toolbar { position: sticky; top: 0; background: transparent; width: 100%; max-width: 8.5in; display: flex; justify-content: flex-end; align-items: center; z-index: 100; margin: 0 auto 20px auto; pointer-events: none;}
        
        .hoja { 
            background: white; 
            width: 8.5in; 
            min-height: 11in; 
            padding: 0.8in; 
            margin: 0 auto 30px auto; 
            font-family: 'Arial', sans-serif; 
            font-size: 11pt; 
            line-height: 1.5; 
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            overflow: visible; 
            box-sizing: border-box;
        }
        .hoja p { text-align: justify; margin-bottom: 10px; }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .logo-container img { width: 150px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 8pt; line-height: 1.3; max-width: 65%; word-wrap: break-word;}
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        
        .campo-editable { 
            background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; min-height: 1em; word-wrap: break-word; overflow-wrap: break-word;
        }

        .tabla-pdf { 
            width: 85% !important; 
            max-width: 100% !important; 
            border-collapse: collapse !important; 
            table-layout: fixed !important; 
            margin: 15px auto !important; 
            font-size: 9pt !important; 
            box-sizing: border-box !important;
        }

        .tabla-pdf th, .tabla-pdf td { 
            border: 1px solid #000 !important; 
            padding: 4px 2px !important; 
            text-align: center !important; 
            vertical-align: middle !important; 
            word-wrap: break-word !important; 
            overflow-wrap: break-word !important; 
            word-break: break-word !important; 
            white-space: normal !important;
        }
        .tabla-pdf th { background-color: #f0f0f0 !important; font-weight: bold; font-size: 8.5pt !important; }
        
        .campo-editable-tabla { 
            display: block; width: 100%; min-height: 15px; outline: none; word-wrap: break-word !important; overflow-wrap: break-word !important;
        }
        [contenteditable="true"] .campo-editable-tabla { background-color: #fef08a; border: 1px dashed #ca8a04;}

        .firmas { margin-top: 60px; text-align: left; }
        .linea-firma { width: 300px; border-bottom: 1px solid #000; margin-bottom: 5px; }

        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover, .btn-pdf:hover { opacity: 0.9; }

        @page { size: letter; margin: 0.8in; }
        
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; height: auto !important; overflow: visible !important; position: static !important; margin: 0 !important; padding: 0 !important; background: white !important;
            }
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; height: auto !important; page-break-after: always !important; box-shadow: none !important; border: none !important; box-sizing: border-box !important; overflow: visible !important;
            }
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important;}
            [contenteditable="true"] .campo-editable-tabla { background: transparent !important; border: none !important;}
            .tabla-pdf th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;}
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