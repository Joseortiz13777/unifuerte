// SCRIPT DE AUDITORÍA Y SEGURIDAD (DLP) - UNIFUERTE C.A.
document.addEventListener("DOMContentLoaded", function() {

    // Función que envía los reportes a la base de datos sin recargar la página
    function registrarActividad(accion, modulo, detalle) {
        let formData = new FormData();
        formData.append('accion', accion);
        formData.append('modulo', modulo);
        formData.append('detalle', detalle);

        // Usamos sendBeacon porque garantiza el envío de datos incluso si el usuario cierra la pestaña huyendo
        if (navigator.sendBeacon) {
            navigator.sendBeacon('../auditoria/api_rastreo.php', formData);
        } else {
            fetch('../auditoria/api_rastreo.php', { method: 'POST', body: formData }).catch(e => {});
        }
    }

    // 1. DETECCIÓN DE EXTRACCIÓN DE TEXTO (Ctrl+C / Clic derecho -> Copiar)
    document.addEventListener('copy', function(e) {
        let textoCopiado = window.getSelection().toString().trim();
        // Solo reporta si están copiando textos largos (más de 15 caracteres)
        if (textoCopiado.length > 15) { 
            let extracto = textoCopiado.substring(0, 100) + (textoCopiado.length > 100 ? '...' : '');
            registrarActividad('ALERTA DE ROBO', 'COPIA AL PORTAPAPELES', 'El usuario copió información: "' + extracto + '"');
        }
    });

    // 2. DETECCIÓN DE INTENTOS DE IMPRESIÓN / GUARDADO COMO PDF
    window.addEventListener('beforeprint', function() {
        registrarActividad('ALERTA DE ROBO', 'IMPRESIÓN', 'El usuario intentó imprimir la pantalla o guardarla como PDF (Ctrl + P).');
    });

    // 3. DETECCIÓN DE TECLAS SOSPECHOSAS (CAPTURAS E INSPECCIÓN)
    document.addEventListener('keydown', function(e) {
        // Captura de Pantalla
        if (e.key === 'PrintScreen') {
            registrarActividad('ALERTA DE ROBO', 'CAPTURA DE PANTALLA', 'El usuario presionó la tecla PrintScreen (Captura de pantalla).');
        }
        // F12 o Ctrl+Shift+I (Inspeccionar Elemento para ver datos ocultos)
        if (e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'i')) {
            registrarActividad('ALERTA DE ROBO', 'INSPECCIÓN DE CÓDIGO', 'El usuario intentó abrir la consola de desarrollador (F12 o Ctrl+Shift+I).');
        }
    });

    // 4. SEGUIMIENTO EXACTO DE NAVEGACIÓN Y MÓDULOS (Tiempo de Uso)
    let enlacesNavegacion = document.querySelectorAll('a.nav-link-custom, .nav-link, button.nav-link, .nav-item button');
    enlacesNavegacion.forEach(btn => {
        btn.addEventListener('click', function(e) {
            let moduloDestino = this.innerText.trim();
            if(moduloDestino !== '') {
                registrarActividad('NAVEGACIÓN', 'MÓDULO', 'Ingresó a: ' + moduloDestino);
            }
        });
    });

});