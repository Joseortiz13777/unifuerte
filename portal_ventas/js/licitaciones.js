let auditoriaLicitacionRegistrada = false;

function cargarLicitaciones() {
    // Registrar en auditoría solo 1 vez por sesión activa para no inundar el historial
    if(!auditoriaLicitacionRegistrada) {
        $.post('api/api_licitaciones.php', { accion: 'registrar_ingreso' });
        auditoriaLicitacionRegistrada = true;
    }

    $('#contenedorLicitaciones').html('<div class="text-center py-5"><span class="spinner-border text-primary"></span><p class="mt-2 text-muted fw-bold">Cargando portafolios...</p></div>');

    $.get('api/api_licitaciones.php', { accion: 'cargar_todo' }, function(res) {
        if(res.success) {
            let html = '';
            let opcionesSelect = '';

            res.data.forEach(empresa => {
                opcionesSelect += `<option value="${empresa.id}">${empresa.nombre}</option>`;

                html += `<div class="mb-5 bg-white p-4 rounded shadow-sm border">
                            <h4 class="fw-bold mb-4 border-bottom pb-2" style="color: #111827;">
                                <i class="bi bi-buildings-fill text-secondary me-2"></i> ${empresa.nombre}
                            </h4>
                            <div class="row g-3">`;

                let docsWord = empresa.documentos.filter(d => d.tipo_doc === 'word');
                let docsExcel = empresa.documentos.filter(d => d.tipo_doc === 'excel');

                if(docsWord.length === 0 && docsExcel.length === 0) {
                    html += `<div class="col-12 text-center text-muted small py-3">No hay documentos registrados para esta empresa.</div>`;
                }

                docsWord.forEach(doc => {
                    html += generarTarjetaDoc(doc, 'border-primary', 'text-primary', 'bi-file-earmark-word-fill', res.es_supervisor);
                });

                docsExcel.forEach(doc => {
                    html += generarTarjetaDoc(doc, 'border-success', 'text-success', 'bi-file-earmark-excel-fill', res.es_supervisor);
                });

                html += `   </div>
                          </div>`;
            });

            if (res.data.length === 0) html = '<div class="alert alert-info text-center fw-bold">Aún no hay secciones de licitaciones registradas.</div>';
            
            $('#contenedorLicitaciones').html(html);
            $('#lic_doc_empresa').html(opcionesSelect);
        }
    }, 'json').fail(() => {
        $('#contenedorLicitaciones').html('<div class="alert alert-danger text-center">Error al conectar con la base de datos de Licitaciones.</div>');
    });
}

function generarTarjetaDoc(doc, borderClass, colorClass, icon, esSupervisor) {
    let opacidad = doc.visible == 0 ? 'opacity: 0.5; background-color: #f8f9fa;' : '';
    let etiquetaOculto = doc.visible == 0 ? '<span class="badge bg-secondary mb-2"><i class="bi bi-eye-slash"></i> Oculto para visores</span>' : '';

    let cardHtml = `
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm transition-all" style="border-left: 5px solid; border-radius: 12px; cursor:pointer; ${opacidad}" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
                <div class="card-body position-relative">
                    <a href="${doc.ruta}" class="text-decoration-none text-dark d-block mb-4">
                        ${etiquetaOculto}
                        <h5 class="fw-bold ${colorClass} mb-2"><i class="bi ${icon} me-1"></i> ${doc.titulo}</h5>
                        <p class="text-muted small mb-0 lh-sm">${doc.descripcion}</p>
                    </a>`;
                    
    if (esSupervisor) {
        let btnVisibilidad = doc.visible == 1 
            ? `<button class="btn btn-sm btn-outline-warning position-absolute" style="bottom: 10px; right: 45px; padding: 2px 6px;" onclick="toggleVisibilidadLicitacion(${doc.id}, 0)" title="Ocultar a visores"><i class="bi bi-eye-slash-fill"></i></button>`
            : `<button class="btn btn-sm btn-outline-success position-absolute" style="bottom: 10px; right: 45px; padding: 2px 6px;" onclick="toggleVisibilidadLicitacion(${doc.id}, 1)" title="Hacer Visible"><i class="bi bi-eye-fill"></i></button>`;

        cardHtml += btnVisibilidad + `
                    <button class="btn btn-sm btn-outline-danger position-absolute" style="bottom: 10px; right: 10px; padding: 2px 6px;" onclick="eliminarDocumentoLicitacion(${doc.id})" title="Eliminar Enlace">
                        <i class="bi bi-trash"></i>
                    </button>`;
    }
    
    cardHtml += `</div>
            </div>
        </div>`;
    
    return cardHtml.replace('border-left: 5px solid;', `border-left: 5px solid var(--bs-${borderClass.split('-')[1]});`);
}

function toggleVisibilidadLicitacion(id, estado) {
    $.post('api/api_licitaciones.php', { accion: 'toggle_visibilidad', id: id, estado: estado }, function(res) {
        if(res.success) cargarLicitaciones();
        else Swal.fire('Error', res.msg, 'error');
    }, 'json');
}

function abrirModalEmpresaLicitacion() {
    $('#formLicitacionEmpresa')[0].reset();
    new bootstrap.Modal(document.getElementById('modalLicitacionEmpresa')).show();
}

function abrirModalDocLicitacion() {
    $('#formLicitacionDoc')[0].reset();
    $('#lic_doc_id').val('0');
    new bootstrap.Modal(document.getElementById('modalLicitacionDoc')).show();
}

$('#formLicitacionEmpresa').submit(function(e) {
    e.preventDefault();
    $.post('api/api_licitaciones.php', $(this).serialize(), function(res) {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'Empresa Registrada', timer: 1500, showConfirmButton: false });
            $('#modalLicitacionEmpresa').modal('hide');
            cargarLicitaciones();
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json');
});

$('#formLicitacionDoc').submit(function(e) {
    e.preventDefault();
    $.post('api/api_licitaciones.php', $(this).serialize(), function(res) {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'Documento Vinculado', timer: 1500, showConfirmButton: false });
            $('#modalLicitacionDoc').modal('hide');
            cargarLicitaciones();
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    }, 'json');
});

function eliminarDocumentoLicitacion(id) {
    Swal.fire({ title: '¿Desvincular este documento?', text: "La carpeta original no se borrará, solo el enlace del portal.", icon: 'warning', showCancelButton: true }).then((result) => {
        if(result.isConfirmed) {
            $.post('api/api_licitaciones.php', { accion: 'eliminar_documento', id: id }, function(res) {
                if(res.success) cargarLicitaciones();
            }, 'json');
        }
    });
}