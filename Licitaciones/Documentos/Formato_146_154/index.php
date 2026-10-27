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
    $archivo_json = 'documento_data_v15_marinicol.json';
} else {
    // Por defecto asume Unifuerte
    $nombre_empresa = 'UNIFUERTE, C.A.';
    $rif_empresa = 'J-412827642';
    $direccion_empresa = 'CR UD 321 EDIF PARCELA 321-16-10A-11 PISO 1 OF A,B,C<br>ZONA INDUSTRIAL 321 CIUDAD GUAYANA BOLIVAR ZONA POSTAL 8050';
    $logo_empresa = '../../img/unifuerte.jpg';
    $archivo_json = 'documento_data_v15_unifuerte.json'; // Separado para no mezclar con marinicol
}

$html_contenido = '';

// Leer JSON correspondiente
if(file_exists($archivo_json)) {
    $json_data = json_decode(file_get_contents($archivo_json), true);
    $html_contenido = $json_data['html'] ?? '';
}

// Si el JSON está vacío o no existe, generamos la plantilla base
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

    $html_contenido = '';

    // =====================================================================
    // HOJA 1 (PDF 146) - ANEXO B
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div style="text-align: center; margin-bottom: 30px; font-weight: bold;">
                ANEXO B<br><br>
                <u>PLAZO, CRONOGRAMA DE ENTREGA DE LOS "BIENES" Y VIGENCIA DEL CONTRATO.</u>
            </div>
            
            <p style="font-weight: bold;"><u>1.0 PLAZO PARA LA EJECUCIÓN DEL CONTRATO.<br>TIEMPO DE EJECUCIÓN:</u></p>
            
            <p><b>"LA CONTRATISTA"</b> EJECUTARÁ EL SUMINISTRO DE LOS <b>"BIENES"</b> EN <span class="campo-editable">_______________</span> <b>(XX)</b> DÍAS CONTINUOS, CONTADOS A PARTIR DE LA FECHA DE LA NOTIFICACIÓN DE LA LIBERACIÓN DEL PEDIDO, LOS CUALES SERÁN ENTREGADOS EN EL LUGAR INDICADO <b>"EL CONTRATANTE"</b> EN EL REPÚBLICA BOLIVARIANA DE VENEZUELA, Y <b>EL CONTRATO</b> PERMANECERÁ VIGENCIA HASTA LA SUSCRIPCIÓN POR <b>LAS PARTES</b> DEL FINIQUITO DEL <b>CONTRATO</b>, SIEMPRE Y CUANDO CUMPLA CON TODOS Y CADA UNO DE LAS ESPECIFICACIONES TÉCNICAS OBJETO DE LA PRESENTE CONTRATACIÓN. EL PLAZO DE EJECUCIÓN DEL <b>CONTRATO</b> PODRÁ SER PRORROGADO MEDIANTE <b>ADDENDUM</b>, DE CONSIDERARLO CONVENIENTE <b>"EL CONTRATANTE"</b>, POR EL LAPSO QUE ÉSTA ESTIME NECESARIO, IGUALMENTE, PODRÁ SER RENOVADO DE MUTUO ACUERDO POR <b>LAS PARTES</b>. EN AMBOS CASOS DEBERÁ CUMPLIRSE CON LAS DISPOSICIONES LEGALES, EL ORDENAMIENTO JURÍDICO VENEZOLANO Y LOS PROCEDIMIENTOS INTERNOS APLICABLES.</p>
            
            <p style="margin-top: 60px;">EN CARACAS, A LOS <span class="campo-editable">_______</span> (__) DÍAS DEL MES DE <span class="campo-editable">__________________</span> DE 2026.</p>
            <p>ACEPTADO CONFORME POR:</p>
            
            <div style="text-align: center; margin-top: 50px;">
                <b>"LA CONTRATISTA":</b><br><br><br><br>
                <span class="campo-editable">......................................................................</span><br>
                <b>C.I. V-</b> <span class="campo-editable">-----------------</span>
                
                <div class="huellas" style="margin-top: 15px;">
                    <div class="huella-caja">PULGAR IZQUIERDO</div>
                    <div class="huella-caja">PULGAR DERECHO</div>
                </div>
            </div>
        </div>
    ';

    // =====================================================================
    // HOJA 2 (PDF 147) - ANEXO C
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div style="text-align: center; margin-bottom: 30px; font-weight: bold;">
                ANEXO C<br>
                DECLARACIÓN JURADA DE ORIGEN Y DESTINO DE LOS FONDOS<br>
                PERSONA JURÍDICA
            </div>
            
            <p>SEÑORES:<br><b>BARIVEN, S.A.</b><br>PRESENTE.-</p>
            
            <p>YO, <span class="campo-editable">_______________________________</span>, TITULAR DOCUMENTO DE IDENTIDAD N° <span class="campo-editable">__</span>, REGISTRO ÚNICO DE INFORMACIÓN FISCAL (R.I.F.) N° <span class="campo-editable">____________________</span> EN MI CARÁCTER DE <b>PRESIDENTE Y/ CARGO</b> <span class="campo-editable">________________________</span>, SUFICIENTEMENTE FACULTADO PARA ESTE ACTO DE CONFORMIDAD CON LA CLÁUSULA <span class="campo-editable">_______________________</span> Y/O NUMERAL <span class="campo-editable">____________</span> DE LOS ESTATUTOS SOCIALES DE LA EMPRESA O SEGÚN ACTA DE FECHA <span class="campo-editable">____</span> DE <span class="campo-editable">________</span> MES <span class="campo-editable">_________</span> DE 2026, REGISTRADA POR ANTE EL MENCIONADO REGISTRO MERCANTIL O COLOCAR SI ES OTRO REGISTRO DE ACUERDO AL CASO; EN FECHA <span class="campo-editable">____</span> DE <span class="campo-editable">_______</span> MES <span class="campo-editable">___________</span> DE 2026 BAJO EN N° <span class="campo-editable">___</span> TOMO <span class="campo-editable">_________</span>, DE LA SOCIEDAD MERCANTIL <span class="campo-editable">_______________________________________</span>, DEBIDAMENTE INSCRITA POR ANTE EL REGISTRO MERCANTIL <span class="campo-editable">-------</span> EN FECHA <span class="campo-editable">_______</span> DE MAYO DE 2026, QUEDANDO ANOTADA BAJO EL N° <span class="campo-editable">___</span>, TOMO <span class="campo-editable">___</span>, INSCRITA E IDENTIFICADA CON EL REGISTRO ÚNICO DE INFORMACIÓN FISCAL (RIF) N° J-<span class="campo-editable">______________</span>; <b>DECLARO BAJO FE DE JURAMENTO</b> QUE LOS FONDOS DE MI REPRESENTADA SON DE PROCEDENCIA LÍCITA Y DE SU PROPIO PECULIO. IGUALMENTE, DECLARO BAJO FE DE JURAMENTO QUE LOS RECURSOS QUE SERÁN RECIBIDOS, A TRAVÉS DE LA PETRÓLEOS DE VENEZUELA, S.A, SUS FILIALES, EMPRESAS MIXTAS O LA FUENTE DE FINANCIAMIENTO QUE DESIGNE, MEDIANTE ESTE <b>CONTRATO</b> SERÁN DESTINADOS ÚNICA Y EXCLUSIVAMENTE A CUMPLIR CON EL OBJETO DEL MISMO EN LOS TÉRMINOS ESTIPULADOS, Y NO ESTARÁN RELACIONADOS O INVOLUCRADOS CON EL FINANCIAMIENTO EN FORMA ALGUNA CON ACTIVIDADES ILÍCITAS QUE PUEDAN SERVIR DE INSTRUMENTO PARA COMETER LOS DELITOS DE LEGITIMACIÓN DE CAPITALES Y FINANCIAMIENTO AL TERRORISMO, ASÍ COMO DE NINGUNA OTRA ACTIVIDAD ILÍCITA.</p>
            
            <p>DECLARACIÓN QUE HAGO, EN LA CIUDAD DE CARACAS, A LOS <span class="campo-editable">__________</span> (__) DÍAS DEL MES DE <span class="campo-editable">__________________</span> DE 2026.</p>
            
            <div style="text-align: center; margin-top: 60px;">
                <b>"LA CONTRATISTA"</b><br><br><br><br>
                <span class="campo-editable">-------------------------------------</span><br>
                <b>C.I. V-</b> <span class="campo-editable">-----------------</span>
                
                <div class="huellas" style="margin-top: 15px;">
                    <div class="huella-caja">PULGAR IZQUIERDO</div>
                    <div class="huella-caja">PULGAR DERECHO</div>
                </div>
            </div>
        </div>
    ';

    // =====================================================================
    // HOJA 3 (PDF 148) - ANEXO D (Parte 1)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div style="text-align: center; margin-bottom: 20px; font-weight: bold;">
                ANEXO D<br><br>
                ACUERDO DE CONFIDENCIALIDAD
            </div>
            
            <p>QUIEN SUSCRIBE <span class="campo-editable">_______________________</span>, MAYOR DE EDAD, DE NACIONALIDAD <span class="campo-editable">________________</span>, DE ESTE DOMICILIO, TITULAR DE LA CÉDULA DE IDENTIDAD N° V-<span class="campo-editable">_________</span>, ACTUANDO EN MI CARÁCTER DE <b>PRESIDENTE/DIRECTOR</b>, EN NOMBRE Y REPRESENTACIÓN DE <span class="campo-editable">_______________________</span>, SOCIEDAD MERCANTIL, INSCRITA ANTE EL REGISTRO MERCANTIL <span class="campo-editable">_______________________</span>, BAJO EL N° <span class="campo-editable">___________</span>, TOMO <span class="campo-editable">__________</span> DE FECHA 24 DE MAYO DE 2026, IDENTIFICADA CON EL REGISTRO ÚNICO DE INFORMACIÓN FISCAL (RIF) N° J____-__, DEBIDAMENTE FACULTADO PARA REALIZAR ESTE ACTO, SEGÚN CONSTA EN LA CLÁUSULA <span class="campo-editable">______</span> DE LOS ESTATUTOS DE LA EMPRESA Y/O ACTA DE ASAMBLEA DE ACCIONES TAS DE FECHA <span class="campo-editable">_____</span> LA CUAL ESTÁ REGISTRADA EN LA CITADA OFICINA DE REGISTRO EN FECHA<span class="campo-editable">_____</span>, BAJO EL N° <span class="campo-editable">_____</span> TOMO<span class="campo-editable">____</span>, QUIEN A LOS EFECTOS DEL PRESENTE DOCUMENTO SE DENOMINARÁ <b>"LA CONTRATISTA"</b></p>
            
            <p style="text-align: center; font-weight: bold;">CONSIDERANDO:</p>
            <p>QUE LA EMPRESA <b>BARIVEN, S.A.</b>, FILIAL DE <b>PETRÓLEOS DE VENEZUELA, S.A.</b>, DEBIDAMENTE INSCRITA POR ANTE EL REGISTRO MERCANTIL PRIMERO DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL DISTRITO CAPITAL Y ESTADO MIRANDA EN FECHA 24 DE DICIEMBRE DE 1975 BAJO EL N° 31, TOMO 59-A SGDO., CUYA ÚLTIMA MODIFICACIÓN ESTATUTARIA APARECE INSCRITA EN LA CITADA OFICINA DE REGISTRO EN FECHA 15 DE FEBRERO DE 2007, BAJO EL N° 52, TOMO 19-A-PRO., DOMICILIADA EN LA CIUDAD DE CARACAS, E INSCRITA E IDENTIFICADA CON EL REGISTRO ÚNICO DE INFORMACIÓN FISCAL <b>(RIF) N° J-000925020</b>, REPRESENTADA POR EL CIUDADANO: <b><span class="campo-editable">----------------------------------------------------</span></b>; VENEZOLANO, MAYOR DE EDAD, CIVILMENTE HÁBIL Y DE ESTE DOMICILIO, TITULAR DE LA CÉDULA DE IDENTIDAD N° V-<span class="campo-editable">__________</span>, EN SU CARÁCTER DE <b>PRESIDENTE</b> DE LA MISMA, QUE EN LO SUCESIVO Y A LOS EFECTOS DE ESTE <b>CONTRATO</b> SE DENOMINA <b>"EL CONTRATANTE"</b>,</p>
            
            <p>QUE EN VIRTUD DEL <b>CONTRATO</b> ESCRITO N° <span class="campo-editable">___________________________</span>, SOLPED N° <span class="campo-editable">___________________</span>, PROCEDIMIENTO DE CONTRATACIÓN N° D-182-XX- 66000<span class="campo-editable">_______</span> Y PEDIDO N° <span class="campo-editable">__________</span> DENOMINADO ADQUISICIÓN DE <span class="campo-editable">_________________________</span>, ACORDADO ENTRE <b>"EL CONTRATANTE"</b>, Y <b>"LA CONTRATISTA"</b>, ESTA ÚLTIMA RECIBIÓ INFORMACIÓN CONFIDENCIAL, ENTENDIÉNDOSE ESTA COMO TODA LA INFORMACIÓN RELACIONADA CON EL <b>CONTRATO</b>, INDEPENDIENTEMENTE DE QUE LA MISMA SEA IDENTIFICADA O NO COMO TAL, QUE SEA SUMINISTRADA POR <b>"EL CONTRATANTE"</b> DE MANERA VERBAL O ESCRITA, INCLUYENDO FORMATO GRÁFICO, DIGITAL, DE DATOS O ELECTROMAGNÉTICO.</p>
            
            <p>EN ESTE SENTIDO, <b>"LA CONTRATISTA"</b>, CON EL FIN DE ASEGURAR LA DEBIDA PROTECCIÓN Y CONSERVACIÓN DE INFORMACIÓN CONFIDENCIAL Y PRIVILEGIADA PUESTA A DISPOSICIÓN DE LA MISMA CON MOTIVO DEL PROCESO DE CONTRATACIÓN REALIZADO, CONVIENE EN SUSCRIBIR EL PRESENTE ACUERDO DE CONFIDENCIALIDAD, EN ADELANTE DENOMINADO EL "ACUERDO", EN LOS TÉRMINOS SIGUIENTES:</p>
            
            <p>A LOS EFECTOS DEL PRESENTE "ACUERDO" DE "INFORMACIÓN CONFIDENCIAL" SIGNIFICA CUALQUIER INFORMACIÓN RELATIVA A <b>"EL CONTRATANTE"</b> PUESTA DIRECTA O INDIRECTAMENTE A DISPOSICIÓN DE <b>"LA CONTRATISTA"</b>, SEA EN FORMA VERBAL, ESCRITA, GRÁFICA, DIGITAL O ELECTRÓNICA. EN PARTICULAR, LA "INFORMACIÓN CONFIDENCIAL "INCLUIRÁ CUALQUIER INFORMACIÓN, PROCESO, TÉCNICA, ALGORITMO, PROGRAMA DE SOFTWARE (INCLUYENDO CÓDIGO DE FUENTE), DISEÑO, DIBUJO, FÓRMULA O DATOS DE PRUEBA O EN PRODUCCIÓN RELACIONADOS CON CUALQUIER PROYECTO DE INVESTIGACIÓN, TRABAJO EN CURSO, CREACIÓN, INGENIERÍA, ELABORACIÓN, MERCADEO, SERVICIOS, FINANCIAMIENTO, INFORMACIÓN DIGITAL O POR ESCRITO, O ASUNTO DEL PERSONAL RELACIONADO CON <b>"EL CONTRATANTE"</b>, SUS PRODUCTOS ACTUALES O FUTUROS, VENTAS, PROVEEDORES, CLIENTES, EMPLEADOS, INVERSIONISTAS O NEGOCIOS, ENTRE OTRAS.</p>
            
            <p><b>"LA CONTRATISTA"</b> RECONOCE QUE LA TOTALIDAD DE LA "INFORMACIÓN CONFIDENCIAL" ENTREGADA POR <b>"EL CONTRATANTE"</b> COMO CONSECUENCIA, RESULTADO O EN EL MARCO DEL</p>
        </div>
    ';

    // =====================================================================
    // HOJA 4 (PDF 149) - ANEXO D (Parte 2)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <p>CONTRATO ANTES MENCIONADO, ES PROPIEDAD DE <b>"EL CONTRATANTE"</b> Y SÓLO PODRÁ SER UTILIZADA POR <b>"LA CONTRATISTA"</b> CON LA FINALIDAD DE EJECUCIÓN Y CABAL CUMPLIMIENTO DEL <b>CONTRATO</b>.</p>
            
            <p style="text-align: center; font-weight: bold;">USO DE LA INFORMACIÓN CONFIDENCIAL</p>
            <p><b>"LA CONTRATISTA"</b> SE COMPROMETE A MANTENER LA PRIVACIDAD DE LA "INFORMACIÓN CONFIDENCIAL" Y, SALVO QUE <b>"EL CONTRATANTE"</b> LO AUTORICE EXPRESAMENTE POR ESCRITO, A NO DIVULGAR LA "INFORMACIÓN CONFIDENCIAL" A UNA TERCERA PERSONA. <b>"LA CONTRATISTA"</b> SE COMPROMETE EXPRESAMENTE A MANTENERLA CONFIDENCIALIDAD SOBRE LA EXISTENCIA DE ESTE "ACUERDO" Y SOBRE LAS CONVERSACIONES O NEGOCIACIONES QUE MANTENGA CON <b>"EL CONTRATANTE"</b>.</p>
            
            <p><b>"LA CONTRATISTA"</b> GUARDARÁ RESERVA DE LA "INFORMACIÓN CONFIDENCIAL". SÓLO UTILIZARÁ DICHA INFORMACIÓN O DOCUMENTACIÓN CON EL FIN PARA EL CUAL LE FUE ENTREGADA Y UTILIZARÁ LOS MISMOS MEDIOS PARA PROTEGER LA PRIVACIDAD DE LA "INFORMACIÓN CONFIDENCIAL" QUE UTILIZA PARA PROTEGER SU PROPIA INFORMACIÓN CONFIDENCIAL, PERO EN CUALQUIER CASO REALIZARÁ, COMO MÍNIMO, UN ESFUERZO RAZONABLE.</p>
            
            <p><b>"LA CONTRATISTA"</b> PODRÁ DIVULGAR LA "INFORMACIÓN CONFIDENCIAL" RECIBIDA BAJO EL PRESENTE "ACUERDO" ÚNICAMENTE A AQUELLOS DE SUS EMPLEADOS, FUNCIONARIOS, DIRECTORES, REPRESENTANTES Y ASESORES INTERNOS O EXTERNOS O A LOS EMPLEADOS, FUNCIONARIOS, DIRECTORES, REPRESENTANTES Y ASESORES, QUE NECESITEN CONOCERLA "INFORMACIÓN CONFIDENCIAL" PARA LOS FINES DISPUESTOS EN EL PRESENTE "ACUERDO" Y CONFORME REQUIERAN CONOCER LA MISMA. EN TODO CASO, SE ASEGURARÁ QUE DICHAS PERSONAS ESTÉN INFORMADAS RESPECTO DEL CARÁCTER RESERVADO DE LA "INFORMACIÓN CONFIDENCIAL".</p>
            
            <p>LA DIVULGACIÓN DE LA "INFORMACIÓN CONFIDENCIAL" POR PARTE DE <b>"LA CONTRATISTA"</b> EN INCUMPLIMIENTO DEL PRESENTE "ACUERDO", LO HARÁ RESPONSABLE DE LOS DAÑOS Y PERJUICIOS DIRECTOS QUE OCASIONE A <b>"EL CONTRATANTE"</b> DERIVADOS DE SU CONDUCTA CULPOSA O DOLOSA.</p>
            
            <p><b>"LA CONTRATISTA"</b> DESIGNARÁ A UNA O MÁS PERSONAS DENTRO DE SU ORGANIZACIÓN COMO LA O LAS PERSONAS AUTORIZADAS PARA ENTREGAR Y RECIBIR "INFORMACIÓN CONFIDENCIAL".</p>
            
            <p style="text-align: center; font-weight: bold;">INFORMACIÓN EXCLUIDA</p>
            <p>NO SE CONSIDERARÁ COMO "INFORMACIÓN CONFIDENCIAL" Y, POR ENDE, <b>"LA CONTRATISTA"</b> NO SERÁ RESPONSABLE DE LA DIVULGACIÓN NI TENDRÁ OBLIGACIONES FRENTE A <b>"EL CONTRATANTE"</b> DE INFORMACIÓN RECIBIDA BAJO EL PRESENTE "ACUERDO" CUANDO:</p>
            
            <p>(A) LA INFORMACIÓN SEA O SE VUELVA PARTE DEL DOMINIO PÚBLICO SIN INCUMPLIMIENTO ALGUNO DEL PRESENTE "ACUERDO";</p>
            
            <p>(B) <b>"EL CONTRATANTE"</b> HUBIERA DADO SU CONSENTIMIENTO PREVIO POR ESCRITO RESPECTO DE DICHA DIVULGACIÓN O MANIFESTADO SU CARÁCTER NO CONFIDENCIAL; O</p>
            
            <p>(C) LA DIVULGACIÓN SEA REALIZADA PARA SATISFACER UN REQUISITO LEGAL O DEMANDA DE UNA AUTORIDAD JUDICIAL U ORGANISMO GUBERNAMENTAL O REGULADOR O POR MANDATO LEGAL O REGULATORIO. EN ESTE CASO, DE SER LEGALMENTE POSIBLE, <b>"LA CONTRATISTA"</b> NOTIFICARÁ INMEDIATAMENTE A <b>"EL CONTRATANTE"</b> POR ESCRITO A FIN QUE ESTA ÚLTIMA PUEDA IMPLEMENTAR LAS ACCIONES NECESARIAS PARA RESGUARDAR SUS DERECHOS, EXCEPTO QUE ESTA NOTIFICACIÓN ESTÉ PROHIBIDA POR LEY. <b>"LA CONTRATISTA"</b> DEMORARÁ CUALQUIER DIVULGACIÓN EN FORMA LEGAL Y RAZONABLEMENTE POSIBLE (SIN INCURRIR EN RESPONSABILIDAD) POR NO REALIZAR DICHA DIVULGACIÓN) PARA PERMITIR A <b>"EL CONTRATANTE"</b> TOMAR LAS ACCIONES NECESARIAS.</p>
        </div>
    ';

    // =====================================================================
    // HOJA 5 (PDF 150) - ANEXO D (Parte 3)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <p style="text-align: center; font-weight: bold;">DEVOLUCIÓN DE LA INFORMACIÓN</p>
            <p><b>"LA CONTRATISTA"</b> TENDRÁ DERECHO A CONSERVAR COPIAS O REPRODUCCIONES DE LA INFORMACIÓN: (I) PARA EFECTOS DE AUDITORÍA INTERNA Y EXTERNA, JURÍDICOS Y REGLAMENTARIOS, (II) PARA DAR CUMPLIMIENTO A DISPOSICIONES LEGALES O A POLÍTICAS INTERNAS EN RELACIÓN CON LA GUARDA DE DOCUMENTOS Y REGISTROS, (III) PARA EFECTOS DE RESOLUCIÓN DE CONTROVERSIAS, DE CONFORMIDAD CON SU MANEJO HABITUAL DE RETENCIÓN DE INFORMACIÓN, O (IV) EN LA MEDIDA EN QUE CUALQUIER AUTORIDAD JUDICIAL COMPETENTE GUBERNAMENTAL, REGULATORIA REQUIERA MANTENER LA "INFORMACIÓN CONFIDENCIAL", O (V) CUANDO LA INFORMACIÓN HAYA SIDO DIVULGADA EN VIRTUD DE LA SECCIÓN ANTERIOR.</p>
            
            <p style="text-align: center; font-weight: bold;">VIGENCIA</p>
            <p>EL PRESENTE "ACUERDO" SE MANTENDRÁ VIGENTE MIENTRAS <b>"LA CONTRATISTA"</b> SIGA RECIBIENDO "INFORMACIÓN CONFIDENCIAL" POR PARTE DE <b>"EL CONTRATANTE"</b>. EN TODO CASO, LAS DISPOSICIONES AQUÍ PREVISTAS RELACIONADAS CON EL USO DE LA INFORMACIÓN CONFIDENCIAL" PERMANECERÁN EN VIGENCIA, AÚN DESPUÉS DE LA TERMINACIÓN DEL PRESENTE "ACUERDO".</p>
            
            <p style="text-align: center; font-weight: bold;">LEY Y JURISDICCIÓN APLICABLE</p>
            <p>ESTE "ACUERDO" SE REGIRÁ E INTERPRETARÁ DE CONFORMIDAD CON LAS LEYES DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA SOMETIÉNDOSE A LOS TRIBUNALES DE LA CIUDAD DE CARACAS.</p>
            <p>NADA DE LO ESTABLECIDO EN ESTE DOCUMENTO PUEDE O DEBE INTERPRETARSE COMO UNA RENUNCIA A LOS PRIVILEGIOS, EXENCIONE OTORGADAS A <b>"EL CONTRATANTE"</b>, POR SU CONVENIO CONSTITUTIVO O POR LOS ACUERDOS FIRMADOS, O POR CUALQUIER OTRA LEGISLACIÓN APLICABLE.</p>
            <p>EN FE DE LO CUAL <b>"LA CONTRATISTA"</b> SUSCRIBE EL PRESENTE ACUERDO DE CONFIDENCIALIDAD, EN TRES (03) EJEMPLARES, DE UN MISMO TENOR Y A UN SOLO EFECTO, EN CARACAS, A LOS <span class="campo-editable">________</span> (__) DÍAS DEL MES DE <span class="campo-editable">_____________</span> DE 2026.</p>
            
            <div style="text-align: center; margin-top: 60px;">
                <b>"LA CONTRATISTA":</b><br><br><br><br>
                <span class="campo-editable">-------------------------------------</span><br>
                <b>C.I. V-</b> <span class="campo-editable">-----------------</span>
                
                <div class="huellas" style="margin-top: 15px;">
                    <div class="huella-caja">PULGAR IZQUIERDO</div>
                    <div class="huella-caja">PULGAR DERECHO</div>
                </div>
            </div>
        </div>
    ';

    // =====================================================================
    // HOJA 6 (PDF 151) - ANEXO E (Parte 1)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div style="text-align: center; margin-bottom: 20px; font-weight: bold;">
                ANEXO E<br><br>
                <u>FIANZA FIEL CUMPLIMIENTO</u>
            </div>
            
            <p>YO, <span class="campo-editable">_______________________</span>, VENEZOLANO(A), MAYOR DE EDAD, PORTADOR(A) DE LA CÉDULA DE IDENTIDAD N° <span class="campo-editable">___________</span>, PROCEDIENDO EN ESTE ACTO EN MI CARÁCTER DE APODERADO(A) DE <span class="campo-editable">_________________________________</span> SOCIEDAD INSCRITA POR ANTE EL REGISTRO <span class="campo-editable">______________________________</span> DEL ESTADO <span class="campo-editable">________________</span>, EL DÍA <span class="campo-editable">___</span> DE <span class="campo-editable">____________</span> DE <span class="campo-editable">____</span> BAJO EL N° <span class="campo-editable">____</span>, TOMO <span class="campo-editable">______</span>, SEGÚN CONSTA EN PODER DEBIDAMENTE AUTENTICADO POR ANTE LA NOTARÍA <span class="campo-editable">_________________________</span> EN FECHA <span class="campo-editable">___</span> DE <span class="campo-editable">______________</span> DE <span class="campo-editable">_____</span>, BAJO EL N° <span class="campo-editable">_____</span>, TOMO <span class="campo-editable">________</span> E INSCRITA EN LA SUPERINTENDENCIA DE SEGUROS BAJO EL N° <span class="campo-editable">______</span>, EN LO ADELANTE DENOMINADA "LA COMPAÑÍA", POR EL PRESENTE DOCUMENTO DECLARO QUE: CONSTITUYO A MI REPRESENTADA EN FIADORA SOLIDARIA Y PRINCIPAL PAGADORA DE <span class="campo-editable">__________________________</span> DOMICILIADA EN <span class="campo-editable">_________________</span> Y CONSTITUIDA POR DOCUMENTO INSCRITO EN EL REGISTRO <span class="campo-editable">___________________________________</span> DEL ESTADO <span class="campo-editable">____________</span> EN FECHA <span class="campo-editable">______</span> DE <span class="campo-editable">________________</span> DE <span class="campo-editable">____</span>, BAJO EL N° <span class="campo-editable">_____</span>, TOMO <span class="campo-editable">_____</span>, EN LO ADELANTE DENOMINADO "EL AFIANZADO", HASTA POR LA CANTIDAD DE <span class="campo-editable">__________________________________________________</span> DÓLARES ESTADOS UNIDOS DE AMÉRICA (USD: <span class="campo-editable">______________</span>,<span class="campo-editable">__</span>), EQUIVALENTE AL MONTO DE <span class="campo-editable">___________________________</span> BOLÍVARES CON <span class="campo-editable">_________________</span> CÉNTIMOS (BS. 000.0000,00) DE CONFORMIDAD CON LA PUBLICACIÓN DEL TIPO DE CAMBIO REFERENCIAL DEL BANCO CENTRAL DE VENEZUELA DE FECHA <span class="campo-editable">_____________</span>, A FIN DE DAR CUMPLIMIENTO A LO ESTABLECIDO EN EL ARTÍCULO 130 DE LA LEY DEL BANCO CENTRAL DE VENEZUELA PARA GARANTIZAR A <b>BARIVEN, S.A.</b>, FILIAL DE PETRÓLEOS DE VENEZUELA, S.A., SOCIEDAD MERCANTIL, DOMICILIADA EN LA CIUDAD DE CARACAS, VENEZUELA, DEBIDAMENTE INSCRITA POR ANTE EL REGISTRO MERCANTIL PRIMERO DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL DISTRITO FEDERAL Y ESTADO MIRANDA, EN FECHA 24 DE DICIEMBRE DE 1975, BAJO EL N° 31, TOMO 59-A SEGUNDO, CUYA ÚLTIMA MODIFICACIÓN APARECE INSCRITA EN LA CITADA OFICINA DE REGISTRO, EN FECHA 15 DE FEBRERO DE 2007, BAJO EL N° 52, TOMO 19-A-PRO., EN LO SUCESIVO DENOMINADO "EL ACREEDOR", EL FIEL, CABAL Y OPORTUNO CUMPLIMIENTO POR PARTE DE "EL AFIANZADO" DE TODAS Y CADA UNA DE LAS OBLIGACIONES QUE RESULTEN A SU CARGO Y A FAVOR DE "EL ACREEDOR", SEGÚN CONTRATO N° <span class="campo-editable">__________</span>, EL CUAL TIENE POR OBJETO "<span class="campo-editable">__________________________________</span>", EN ADELANTE "EL CONTRATO". LA PRESENTE FIANZA COMENZARÁ A REGIR A PARTIR DE LA FIRMA DE EL CONTRATO Y TENDRÁ VALIDEZ HASTA QUE EL ÓRGANO O ENTE CONTRATANTE DICTE EL ACTO ADMINISTRATIVO DE LIBERACIÓN DE LAS FIANZAS, Y UNA VEZ QUE SE EFECTÚE LA RECEPCIÓN DEFINITIVA DEL OBJETO DE LA CONTRATACIÓN, SE OTORGUE EL FINIQUITO CONTABLE, LA EVALUACIÓN DE DESEMPEÑO, SE CERTIFIQUE EL CUMPLIMIENTO DEL COMPROMISO DE RESPONSABILIDAD SOCIAL CUANDO APLIQUE, O CUALQUIER OTRA OBLIGACIÓN QUE A EL AFIANZADO IMPONGA LA LEGISLACIÓN QUE REGULA LA MATERIA DE CONTRATACIONES PÚBLICAS O EL CONTRATO. QUEDA EXPRESAMENTE CONVENIDO QUE EL PRESENTE CONTRATO DE FIANZA NO PUEDE SER CANCELADO, NI EFECTUARSE CAMBIO ALGUNO EN ÉL, SIN DAR PREVIO AVISO POR ESCRITO AL "ACREEDOR" POR LO MENOS CON SESENTA (60) DÍAS DE ANTICIPACIÓN; Y A TAL EFECTO, DEBERÁ OBTENERSE CERTIFICACIÓN ESCRITA DE LA FECHA DE RECIBO POR PARTE DEL "ACREEDOR", CONTÁNDOSE EL PRECITADO LAPSO A PARTIR DE DICHA FECHA. "EL ACREEDOR" DEBERÁ NOTIFICAR A "LA COMPAÑÍA", POR ESCRITO LA OCURRENCIA DE CUALQUIER HECHO O CIRCUNSTANCIA QUE PUEDA DAR ORIGEN A RECLAMO AMPARADO POR ESTA FIANZA, DENTRO DE LOS NOVENTA (90) DÍAS HÁBILES SIGUIENTES AL CONOCIMIENTO DE DICHA OCURRENCIA. EN CASO DE EJECUCIÓN DE LA PRESENTE FIANZA, LA MISMA SERÁ PAGADA EN DÓLARES AMERICANOS (USD), SIN DESCUENTO NI DEDUCCIONES. LA NO CANCELACIÓN DE LA PRIMA POR PARTE DE LA AFIANZADA, NO SERÁ CAUSAL PARA LA ANULACIÓN DE MANERA UNILATERAL POR PARTE DE LA COMPAÑÍA. ESTA FIANZA SE REGIRÁ POR LOS TÉRMINOS Y CONDICIONES ESTABLECIDOS EN EL MENCIONADO CONTRATO DE FIANZA DE FIEL CUMPLIMIENTO, POR EL DECRETO CON RANGO, VALOR Y FUERZA DE LEY DE LA ACTIVIDAD ASEGURADORA PUBLICADO EN LA GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 6.211 EXTRAORDINARIO DE FECHA 30 DE DICIEMBRE DE 2015, POR EL DECRETO CON RANGO, VALOR Y FUERZA DE LEY DE CONTRATACIONES PÚBLICAS PUBLICADO EN LA GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 6.145 EXTRAORDINARIO DE FECHA 19 DE NOVIEMBRE DE 2014; Y POR SU REGLAMENTO, PUBLICADO EN LA</p>
        </div>
    ';

    // =====================================================================
    // HOJA 7 (PDF 152) - ANEXO E (Parte 2)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <p>GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 39.181 DE FECHA 19 DE MAYO DE 2009, DE CONFORMIDAD CON LOS TÉRMINOS Y CONDICIONES PARTICULARES AQUÍ DESCRITOS Y POR LAS CONDICIONES GENERALES ANEXA. "LA COMPAÑÍA" RENUNCIA EXPRESAMENTE A LOS BENEFICIOS ACORDADOS POR LOS ARTÍCULOS 1833, 1834 Y 1836 DEL CÓDIGO CIVIL VIGENTE. SE FIJA COMO DOMICILIO ESPECIAL PARA TODOS LOS EFECTOS DE ESTE CONTRATO, LA CIUDAD DE CARACAS, A LA JURISDICCIÓN DE CUYOS TRIBUNALES DECLARAN LAS PARTES SOMETERSE, CON EXCLUSIÓN DE CUALQUIER OTRO.</p>
        </div>
    ';

    // =====================================================================
    // HOJA 8 (PDF 153) - ANEXO F (Parte 1)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <div style="text-align: center; margin-bottom: 20px; font-weight: bold;">
                ANEXO F<br><br>
                <u>FIANZA DE ANTICIPO</u>
            </div>
            
            <p>YO, <span class="campo-editable">_______________________</span>, VENEZOLANO, MAYOR DE EDAD, DOMICILIADO EN <span class="campo-editable">______________</span> Y TITULAR DE LA CÉDULA DE IDENTIDAD N° <span class="campo-editable">___________</span>, PROCEDIENDO EN ESTE ACTO EN MI CARÁCTER DE GERENTE DE LA SUCURSAL <span class="campo-editable">________________</span> DE ENTIDAD <span class="campo-editable">________________</span> SOCIEDAD MERCANTIL INSCRITA BAJO EL N° <span class="campo-editable">____</span> EN EL LIBRO DE REGISTRO DE EMPRESAS DE SEGUROS LLEVADO POR LA SUPERINTENDENCIA DE SEGUROS DEL MINISTERIO DE FINANZAS, CONSTITUIDA SEGÚN DOCUMENTO ORIGINALMENTE INSCRITO EN EL REGISTRO MERCANTIL DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL DISTRITO <span class="campo-editable">_______</span> L DEL ESTADO <span class="campo-editable">________</span>, BAJO EL N° <span class="campo-editable">__</span>, TOMO <span class="campo-editable">________</span>, EL <span class="campo-editable">_____</span> DE <span class="campo-editable">_____________</span> DE <span class="campo-editable">______</span>, CON POSTERIORES MODIFICACIONES EN SU DENOMINACIÓN SOCIAL, LA PRIMERA INSCRITA POR ANTE LA CITADA OFICINA DE REGISTRO MERCANTIL, DE FECHA <span class="campo-editable">____</span> DE <span class="campo-editable">_____________</span> DE <span class="campo-editable">_____</span>, BAJO EL N° <span class="campo-editable">_____</span>, TOMO <span class="campo-editable">_____</span> Y LA SEGUNDA POR ANTE EL REGISTRO MERCANTIL DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL DISTRITO <span class="campo-editable">______________</span> EL <span class="campo-editable">____</span> DE <span class="campo-editable">_______</span> DE <span class="campo-editable">_______</span> BAJO EL N°<span class="campo-editable">___</span> TOMO <span class="campo-editable">_______</span> SUFICIENTEMENTE FACULTADO PARA ESTE ACTO SEGÚN PODER AUTENTICADO POR ANTE LA NOTARÍA PÚBLICA <span class="campo-editable">___________</span> DEL MUNICIPIO <span class="campo-editable">_______</span>, BAJO EL N°<span class="campo-editable">___</span>, TOMO <span class="campo-editable">____</span> DE FECHA <span class="campo-editable">___</span> DE <span class="campo-editable">________</span> DEL 20_, Y POR LO RESUELTO EN LA REUNIÓN DE LA JUNTA DIRECTIVA DEL <span class="campo-editable">_____</span> DE <span class="campo-editable">__________</span> DEL 20_ SEGÚN ACTA N°<span class="campo-editable">_______</span> EN LO ADELANTE DENOMINADA "LA COMPAÑÍA", DECLARO: CONSTITUYO A MI REPRESENTADA EN FIADORA SOLIDARIA Y PRINCIPAL PAGADORA DE LA EMPRESA MERCANTIL: <span class="campo-editable">_______________</span>, INSCRITA EN EL JUZGADO <span class="campo-editable">________</span> DE <span class="campo-editable">______</span> INSTANCIA EN LO CIVIL Y MERCANTIL DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL ESTADO <span class="campo-editable">_______</span> EN FECHA <span class="campo-editable">____</span> DE <span class="campo-editable">________</span> DE <span class="campo-editable">______</span>, BAJO EL N°<span class="campo-editable">__</span>, FOLIO <span class="campo-editable">___</span>DEL LIBRO <span class="campo-editable">___</span>, REPRESENTADA EN ESTE ACTO POR EL CIUDADANO <span class="campo-editable">__________________________</span>, TITULAR DE LA CÉDULA DE IDENTIDAD N° <span class="campo-editable">_____________</span>, EN SU CARÁCTER DE <span class="campo-editable">________________</span> SEGÚN SE EVIDENCIA EN ACTA DE ASAMBLEA EXTRAORDINARIA DE LOS ACCIONISTAS CELEBRADA EL DÍA <span class="campo-editable">___</span> DE <span class="campo-editable">_______</span> DEL 20<span class="campo-editable">__</span>, <span class="campo-editable">________</span> PUNTO, Y REGISTRADA POR ANTE EL REGISTRO MERCANTIL SEGUNDO DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL ESTADO <span class="campo-editable">_______</span> EL DÍA <span class="campo-editable">__</span> DE <span class="campo-editable">_____</span> DEL 20<span class="campo-editable">__</span>, BAJO EL N°<span class="campo-editable">____</span> TOMO <span class="campo-editable">_________</span> TRIMESTRE, EN LO SUCESIVO DENOMINADO "EL AFIANZADO" HASTA POR LA CANTIDAD DE <span class="campo-editable">_______________________</span> DÓLARES DE LOS ESTADOS UNIDOS DE AMÉRICA (USD), <span class="campo-editable">_________________</span> QUE DE MANERA REFERENCIAL Y A LOS SOLOS EFECTOS DE DAR CUMPLIMIENTO A LO ESTABLECIDO EN EL ARTÍCULO 130 DE LA LEY DEL BANCO CENTRAL DE VENEZUELA, AL CAMBIO DE <span class="campo-editable">___________</span> BOLÍVARES SIN CÉNTIMOS (BS. <span class="campo-editable">___</span>,00) PARA GARANTIZAR A BARIVEN, S.A., FILIAL DE PETRÓLEOS DE VENEZUELA, S.A., SOCIEDAD MERCANTIL, DOMICILIADA EN LA CIUDAD DE CARACAS, REPÚBLICA BOLIVARIANA DE VENEZUELA, DEBIDAMENTE INSCRITA POR ANTE EL REGISTRO MERCANTIL PRIMERO DE LA CIRCUNSCRIPCIÓN JUDICIAL DEL DISTRITO FEDERAL Y ESTADO MIRANDA, EN FECHA 24 DE DICIEMBRE DE 1975, BAJO EL N° 31, TOMO 59-A SEGUNDO, CUYA ÚLTIMA MODIFICACIÓN APARECE INSCRITA EN LA CITADA OFICINA DE REGISTRO EN FECHA 15 DE FEBRERO DE 2007, BAJO EL N° 52, TOMO 19-A, EN LO SUCESIVO DENOMINADO "EL ACREEDOR", EL REINTEGRO DEL ANTICIPO QUE POR LA CANTIDAD YA MENCIONADA HARÁ "EL AFIANZADO", SEGÚN CONTRATO N°<span class="campo-editable">_____________</span>, CELEBRADO ENTRE AMBOS PARA LA ADQUISICIÓN DE: "<span class="campo-editable">______________________</span>". LA PRESENTE FIANZA COMENZARÁ A REGIR A PARTIR DE LA FECHA EN QUE "EL AFIANZADO" RECIBA EL ALUDIDO ANTICIPO Y PERMANECERÁ EN VIGENCIA HASTA CUANDO SE HAYA EFECTUADO SU TOTAL REINTEGRO MEDIANTE LAS DEDUCCIONES DEL PORCENTAJE DE AMORTIZACIÓN ESTABLECIDO EN EL CONTRATO QUE DEBE EFECTUAR "EL ACREEDOR" DE CADA VALUACIÓN PAGADA A "EL AFIANZADO". EL MONTO DE ESTA FIANZA SE REDUCIRÁ PROGRESIVAMENTE EN LA MISMA MEDIDA EN QUE SE FUERE AMORTIZANDO EL ANTICIPO Y EN NINGÚN CASO ESE MONTO AFIANZADO PODRÁ SER INFERIOR A LA PARTE NO AMORTIZADA DEL ANTICIPO SEGÚN EL RÉGIMEN DE AMORTIZACIÓN ESTABLECIDO EN EL CONTRATO. "LA COMPAÑÍA" RENUNCIA EXPRESAMENTE A LOS BENEFICIOS ACORDADOS POR LOS ARTÍCULOS 1833, 1834 Y 1836 DEL CÓDIGO CIVIL. QUEDA ENTENDIDO Y CONVENIDO QUE LAS CONDICIONES GENERALES DEL CONTRATO DE FIANZA DE ANTICIPO ANEXAS AL PRESENTE DOCUMENTO, FORMAN PARTE INTEGRANTE DEL PRESENTE CONTRATO. EN CASO DE EJECUCIÓN DE LA PRESENTE FIANZA, LA MISMA SERÁ PAGADA EN DÓLARES AMERICANOS (USD), SIN NINGÚN TIPO DE REBAJAS NI DEDUCCIONES. LA NO CANCELACIÓN DE LA PRIMA POR PARTE DE LA AFIANZADA, NO SERÁ CAUSAL PARA LA ANULACIÓN DE MANERA UNILATERAL POR PARTE DE LA</p>
        </div>
    ';

    // =====================================================================
    // HOJA 9 (PDF 154) - ANEXO F (Parte 2)
    // =====================================================================
    $html_contenido .= '
        <div class="hoja">
            ' . $encabezado . '
            <p>COMPAÑÍA. ESTA FIANZA SE REGIRÁ POR LOS TÉRMINOS Y CONDICIONES ESTABLECIDOS EN EL MENCIONADO CONTRATO DE FIANZA DE ANTICIPO, POR EL DECRETO CON RANGO, VALOR Y FUERZA DE LEY DE LA ACTIVIDAD ASEGURADORA PUBLICADO EN LA GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 6.211 EXTRAORDINARIO DE FECHA 30 DE DICIEMBRE DE 2015, POR EL DECRETO CON RANGO, VALOR Y FUERZA DE LEY DE CONTRATACIONES PÚBLICAS PUBLICADO EN LA GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 6.145 EXTRAORDINARIO DE FECHA 19 DE NOVIEMBRE DE 2014; Y POR SU REGLAMENTO, PUBLICADO EN LA GACETA OFICIAL DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA N° 39.181 DE FECHA 19 DE MAYO DE 2009, DE CONFORMIDAD CON LOS TÉRMINOS Y CONDICIONES PARTICULARES AQUÍ DESCRITOS Y POR LAS CONDICIONES GENERALES ANEXA. SE FIJA COMO DOMICILIO ESPECIAL PARA TODOS LOS EFECTOS DE ESTE CONTRATO, LA CIUDAD DE CARACAS, A LA JURISDICCIÓN DE CUYOS TRIBUNALES DECLARAN LAS PARTES SOMETERSE, CON EXCLUSIÓN DE CUALQUIER OTRO.</p>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Licitaciones - <?php echo $nombre_empresa; ?></title>
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
            font-size: 9pt;  
            line-height: 1.4;  
            outline: none; 
            position: relative; 
            page-break-after: always; 
            text-align: justify; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.2); 
            overflow: visible; 
            box-sizing: border-box;
        }
        .hoja p { text-align: justify; margin-bottom: 12px; }

        .header-top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .logo-container img { width: 150px; cursor: default; } 
        .info-fiscal { text-align: right; font-size: 8pt; line-height: 1.3; max-width: 65%; word-wrap: break-word;}
        .titulo { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        
        .campo-editable { background-color: #fef08a; border: 1px dashed #ca8a04; display: inline-block; padding: 0px 4px; border-radius: 3px; color: #111; min-height: 1em; word-wrap: break-word; overflow-wrap: break-word; }

        .tabla-pdf { 
            width: 95% !important; 
            max-width: 100% !important; 
            border-collapse: collapse !important; 
            table-layout: fixed !important; 
            margin: 15px auto !important; 
            font-size: 9pt !important; 
            box-sizing: border-box !important;
        }

        .tabla-pdf th, .tabla-pdf td { 
            border: 1px solid #000 !important; 
            padding: 6px 4px !important; 
            text-align: center !important; 
            vertical-align: middle !important; 
            word-wrap: break-word !important; 
            overflow-wrap: break-word !important; 
            word-break: break-word !important; 
            white-space: normal !important;
        }
        .tabla-pdf th { background-color: #f0f0f0 !important; font-weight: bold; font-size: 8.5pt !important; }

        .huellas { display: flex; gap: 10px; justify-content: center; margin-top: 10px; }
        .huella-caja { width: 70px; height: 90px; border: 1px solid #000; font-size: 5pt; text-align: center; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; }

        .btn-guardar, .btn-pdf { pointer-events: auto; }
        .btn-guardar { background-color: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-pdf { background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .btn-guardar:hover, .btn-pdf:hover { opacity: 0.9; }

        /* ==========================================
           MAGIA ANTI-HOJAS EN BLANCO PARA EL PDF
           ========================================== */
        @page { 
            size: letter; 
            margin: 0 !important; 
        }
        
        @media print {
            html, body, main, .main-content, .editor-container, #documento-editable { 
                display: block !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                background: white !important;
            }
            .toolbar, .sidebar, .header { display: none !important; }
            
            .hoja { 
                width: 8.5in !important; 
                height: 11in !important; 
                max-height: 11in !important;
                padding: 0.8in !important; 
                margin: 0 !important; 
                page-break-after: always !important; 
                page-break-inside: avoid !important;
                box-shadow: none !important; 
                border: none !important; 
                box-sizing: border-box !important; 
                overflow: hidden !important; 
                font-size: 8.5pt !important; 
                line-height: 1.3 !important;
            }
            
            .hoja p { margin-bottom: 8px !important; }
            
            .campo-editable { background: transparent !important; border: none !important; padding: 0 !important; color: black !important;}
            ::-webkit-scrollbar { display: none !important; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="ph-bold ph-factory"></i></div> 
            <!-- Nombre en el panel lateral dinámico -->
            <?php echo ($empresa === 'marinicol') ? 'Marinicol' : 'Unifuerte'; ?> 
        </div>
        <nav class="nav-menu">
            <a href="../../../portal_ventas/index.php?modulo=licitaciones" class="nav-item">
                <i class="ph ph-arrow-left"></i> Volver a Menú
            </a>
            <!-- Botón opcional para cambiar de empresa sin volver atrás -->
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
                        // Cambiamos el icono para el efecto de abierto/cerrado
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
            let archivoDestino = '<?php echo $archivo_json; ?>'; // El PHP ya sabe qué archivo le toca a qué empresa

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