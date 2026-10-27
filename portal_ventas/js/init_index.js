// LEYENDO CONSTANTES GLOBALES DE PRIVILEGIOS DESDE LOS CAMPOS OCULTOS DEL INDEX
const inputOmitir60 = document.getElementById('permiso_omitir_60');
const inputEditarBloq = document.getElementById('permiso_editar_bloq');

const PERMISO_OMITIR_60 = (inputOmitir60 && inputOmitir60.value === '1');
const PERMISO_EDITAR_BLOQUEADAS = (inputEditarBloq && inputEditarBloq.value === 'true');

document.addEventListener("DOMContentLoaded", function() {
    const btnTogglePc = document.getElementById('btnToggleSidebarPc');
    const sidebar = document.getElementById('sidebar');
    const btnHamburguesa = document.getElementById('btnHamburguesa');
    const overlay = document.getElementById('sidebarOverlay');

    // =========================================================
    // 1. LÓGICA DE COMPUTADORA (Totalmente intacta)
    // =========================================================
    if (btnTogglePc && sidebar) {
        btnTogglePc.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
            
            // Guardar en el navegador si lo dejó abierto o cerrado
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebar_pc_collapsed', 'true');
            } else {
                localStorage.setItem('sidebar_pc_collapsed', 'false');
            }
        });

        // Revisar al recargar si lo había dejado cerrado en la última sesión
        if (localStorage.getItem('sidebar_pc_collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }

    // =========================================================
    // 2. LÓGICA PARA TELÉFONOS MÓVILES (Nueva)
    // =========================================================
    if (btnHamburguesa && sidebar && overlay) {
        // Abrir el menú al tocar el botón de hamburguesa
        btnHamburguesa.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.add('active'); 
            overlay.classList.add('active');
        });

        // Cerrar el menú al tocar el fondo oscuro (overlay)
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Cerrar el menú automáticamente al seleccionar una opción (solo en móviles)
        const navLinks = sidebar.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) { // Verifica que esté en pantalla de celular
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    }
});