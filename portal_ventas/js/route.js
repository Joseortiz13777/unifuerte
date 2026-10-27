document.addEventListener('DOMContentLoaded', () => {
    const API_URL = 'http://localhost:3000/api';

    const origenSelect = document.getElementById('origen');
    const destinoSelect = document.getElementById('destino');
    const productoSelect = document.getElementById('producto');
    const precioOrdenInput = document.getElementById('precioOrden');
    const realizarPedidoBtn = document.getElementById('realizarPedidoBtn');
    const resultadoRutaDiv = document.getElementById('resultadoRuta');
    const tiempoTotalSpan = document.getElementById('tiempoTotal');
    const rutaDetalleSpan = document.getElementById('rutaDetalle'); // Ahora sí lo vamos a usar
    const mensajeErrorRutaDiv = document.getElementById('mensajeErrorRuta');
    const pedidoForm = document.getElementById('pedidoForm'); // Referencia al formulario completo

    // --- Cargar opciones de Origen (Distribuidoras) y Destino (Zonas) ---
    async function cargarLugares() {
        try {
            const response = await fetch(`${API_URL}/lugares`);
            if (!response.ok) {
                throw new Error(`Error en la solicitud: ${response.status}`);
            }
            const lugares = await response.json();

            origenSelect.innerHTML = '<option value="" selected disabled>Selecciona la sucursal...</option>';
            destinoSelect.innerHTML = '<option value="" selected disabled>Selecciona tu zona...</option>';

            lugares.forEach(lugar => {
                const option = document.createElement('option');
                option.value = lugar.nombre;
                option.textContent = lugar.nombre;

                if (lugar.tipo === 'CentroDistribucion') {
                    origenSelect.appendChild(option.cloneNode(true));
                } else if (lugar.tipo === 'Zona') {
                    destinoSelect.appendChild(option.cloneNode(true));
                }
            });

        } catch (error) {
            console.error('Error al cargar lugares:', error);
            mostrarMensajeError('No se pudieron cargar las opciones de sucursal y zona. Por favor, verifica que el backend esté corriendo.');
        }
    }

    // --- Actualizar precio de la orden al seleccionar un producto ---
    productoSelect.addEventListener('change', () => {
        const selectedOption = productoSelect.options[productoSelect.selectedIndex];
        const price = selectedOption.dataset.price;
        precioOrdenInput.value = price ? `$${parseFloat(price).toFixed(2)}` : '$0.00';
    });

    // --- Funciones para mostrar/ocultar mensajes de error/resultados ---
    function mostrarMensajeError(mensaje) {
        mensajeErrorRutaDiv.textContent = mensaje;
        mensajeErrorRutaDiv.style.display = 'block';
        resultadoRutaDiv.style.display = 'block'; // Muestra el div para el error
        tiempoTotalSpan.textContent = ''; // Limpia resultados previos
        rutaDetalleSpan.textContent = ''; // Limpia resultados previos
    }

    function ocultarMensajeError() {
        mensajeErrorRutaDiv.style.display = 'none';
        mensajeErrorRutaDiv.textContent = '';
    }

    function resetFormularioYResultados() {
        pedidoForm.reset(); // Resetea todos los campos del formulario
        productoSelect.value = ''; // Asegura que el selector de producto se resetee al placeholder
        precioOrdenInput.value = '$0.00'; // Resetea el precio
        origenSelect.value = ''; // Resetea el selector de origen al placeholder
        destinoSelect.value = ''; // Resetea el selector de destino al placeholder
        ocultarMensajeError(); // Oculta cualquier mensaje de error
        resultadoRutaDiv.style.display = 'none'; // Oculta el div de resultados
        tiempoTotalSpan.textContent = ''; // Limpia el tiempo total
        rutaDetalleSpan.textContent = ''; // Limpia la ruta detalle
    }

    // --- Manejar el clic del botón "Realizar Pedido" ---
    realizarPedidoBtn.addEventListener('click', async (event) => {
        // Prevenir el envío del formulario para manejarlo con JS
        event.preventDefault();

        // Limpiar mensajes y resultados previos
        ocultarMensajeError();
        resultadoRutaDiv.style.display = 'none';

        // 1. Validar campos del formulario HTML5 (ej. required)
        if (!pedidoForm.checkValidity()) {
            // Si el formulario no es válido, el navegador mostrará sus mensajes de error.
            // Para asegurar que el usuario vea esos mensajes, simplemente retornamos.
            pedidoForm.reportValidity(); // Muestra los mensajes de validación nativos
            return;
        }

        // 2. Validaciones específicas para selectores de ruta
        const origen = origenSelect.value;
        const destino = destinoSelect.value;

        if (!origen || origen === '') {
            mostrarMensajeError('Por favor, selecciona una Sucursal de Origen para tu pedido.');
            return;
        }
        if (!destino || destino === '') {
            mostrarMensajeError('Por favor, selecciona una Zona de Entrega para tu pedido.');
            return;
        }
        if (origen === destino) {
             mostrarMensajeError('La sucursal de origen y la zona de entrega no pueden ser el mismo lugar.');
             return;
        }

        // Obtener datos del formulario
        const nombre = document.getElementById('name').value;
        const telefono = document.getElementById('phone').value;
        const direccion = document.getElementById('address').value;
        const producto = productoSelect.value;
        const precio = precioOrdenInput.value;
        const notasAdicionales = document.getElementById('message').value;

        try {
            // Realizar la petición POST al backend para calcular la ruta
            const response = await fetch(`${API_URL}/ruta-mas-rapida`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ origen, destino }),
            });

            const data = await response.json();

            if (!response.ok) {
                // Si hay un error del backend, usar el mensaje de error del backend
                throw new Error(data.error || data.message || `Error en la solicitud: ${response.status}`);
            }

            // Mostrar el tiempo total y la ruta detallada
            tiempoTotalSpan.textContent = data.tiempoTotalMinutos.toFixed(1);
            rutaDetalleSpan.textContent = data.rutaCompleta.join(' → '); // Usamos una flecha más estética

            resultadoRutaDiv.style.display = 'block'; // Mostrar la sección de resultados

            // Aquí podrías añadir la lógica para "guardar el pedido" en tu backend
            // por ejemplo, enviando una segunda petición POST a una ruta /api/pedido
            console.log("Pedido a procesar:", {
                nombre,
                telefono,
                direccion,
                producto,
                precio,
                notasAdicionales,
                origenEntrega: origen,
                destinoEntrega: destino,
                tiempoEstimado: data.tiempoTotalMinutos,
                ruta: data.rutaCompleta
            });

            // Después de mostrar los resultados y "procesar" el pedido, limpiar el formulario
            setTimeout(() => {
                resetFormularioYResultados();
                alert('¡Tu pedido ha sido realizado con éxito! Por favor, espera tu deliciosa pizza.');
            }, 3000); // Dar 3 segundos para que el usuario vea la información antes de limpiar

        } catch (error) {
            console.error('Error al realizar el pedido y calcular la ruta:', error);
            mostrarMensajeError(`Lo sentimos, no pudimos procesar tu pedido. ${error.message}.`);
        }
    });

    // --- Cargar los lugares al iniciar la página ---
    cargarLugares();
});