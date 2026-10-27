<?php
// 1. Verificamos si ya se seleccionó una empresa por la URL
$empresa = isset($_GET['empresa']) ? $_GET['empresa'] : '';

// 2. Si NO hay empresa seleccionada, mostramos la ventana emergente y detenemos la carga del panel
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
        .opcion-empresa { cursor:pointer; padding: 15px; border: 2px solid #e5e7eb; border-radius: 15px; transition: all 0.3s; text-align: center; width: 45%; }
        .opcion-empresa:hover { transform: scale(1.05); }
        .opcion-unifuerte:hover { border-color: #2563eb; }
        .opcion-marinicol:hover { border-color: #059669; }
        .opcion-empresa img { width: 150px; height: 150px; object-fit: contain; border-radius: 10px; }
        .opcion-empresa p { font-weight: bold; margin-top: 15px; font-size: 1.1rem; color: #111827; }
    </style>
</head>
<body>
    <script>
        // Lanzamos la ventana emergente obligatoria
        Swal.fire({
            title: '¿Qué licitaciones deseas gestionar?',
            html: `
                <div style="display: flex; justify-content: space-around; gap: 10px; margin-top: 20px;">
                    <div class="opcion-empresa opcion-unifuerte" onclick="window.location.href='?empresa=unifuerte'">
                        <img src="img/unifuerte.jpg" alt="Unifuerte">
                        <p>Unifuerte</p>
                    </div>
                    <div class="opcion-empresa opcion-marinicol" onclick="window.location.href='?empresa=marinicol'">
                        <img src="img/marinicole.jpg" alt="Marinicol">
                        <p>Marinicol</p>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false, // Evita que se cierre tocando afuera
            allowEscapeKey: false, // Evita que se cierre con la tecla Esc
            width: '600px',
            background: '#ffffff'
        });
    </script>
</body>
</html>
<?php
    exit; // Detenemos la carga del panel principal hasta que seleccione una
}

// 3. Si YA seleccionó empresa, asignamos el nombre para el panel principal
$nombre_panel = ($empresa === 'marinicol') ? 'Marinicol' : 'Unifuerte';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Documentos - <?php echo $nombre_panel; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; margin: 0; padding: 40px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        h1 { color: #111827; margin: 0; }
        .btn-cambiar { background-color: #374151; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-cambiar:hover { background-color: #111827; }
        
        .section-title { 
            color: #374151; 
            margin: 40px 0 20px 0; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #e5e7eb; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-size: 1.4rem; 
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); text-decoration: none; color: #374151; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .card p { margin: 0; font-size: 0.85rem; color: #6b7280; }

        .card-word { border-left: 5px solid #2563eb; }
        .card-word h3 { margin: 0 0 10px 0; color: #2563eb; }

        .card-excel { border-left: 5px solid #10b981; }
        .card-excel h3 { margin: 0 0 10px 0; color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Botón para cambiar de empresa -->
        <div class="header-box">
            <h1><i class="ph-bold ph-files"></i> Panel de Control - <?php echo $nombre_panel; ?></h1>
            <a href="index.php" class="btn-cambiar"><i class="ph-bold ph-arrows-left-right"></i> Cambiar Empresa</a>
        </div>
        
        <h2 class="section-title">
            <i class="ph-fill ph-file-doc" style="color: #2563eb;"></i> Documentos Word
        </h2>
        <div class="grid">
            <a href="Documentos/Formatos_43_46/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 43-46</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_50_51/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 50-51</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_62_72/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 62-72</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_79/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formato 79</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_94_95/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 94-95</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_99/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formato 99</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_103_105/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 103-105</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_107/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formato 107</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_123_124/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formatos 123-124</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
            <a href="Documentos/Formatos_126_144/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Contratos 126-144</h3>
                <p>Proyecto de Contrato y anexos legales.</p>
            </a>
            <a href="Documentos/Formato_146_154/index.php?empresa=<?php echo $empresa; ?>" class="card card-word">
                <h3>Formato 146-154</h3>
                <p>Acceder a documentos de esta sección.</p>
            </a>
        </div>

        <h2 class="section-title">
            <i class="ph-fill ph-file-xls" style="color: #10b981;"></i> Documentos Excel
        </h2>
        <div class="grid">
            <a href="Excel/Formato_52_61/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formatos 52-61</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
            <a href="Excel/Formato_80_89/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formatos 80-89</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
            <a href="Excel/Formato_90_91/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formatos 90-91</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
            <a href="Excel/Formato_96_97/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formatos 96-97</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
            <a href="Excel/Formato_100_102/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formatos 100-102</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
            <a href="Excel/Formato_106/index.php?empresa=<?php echo $empresa; ?>" class="card card-excel">
                <h3>Formato 106</h3>
                <p>Acceder a hojas de cálculo de esta sección.</p>
            </a>
        </div>
    </div>
    
    <script src="../auditoria/auditoria_espia.js?v=<?= time(); ?>"></script>
</body>
</html>