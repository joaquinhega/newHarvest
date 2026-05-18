<?php
session_start();
include '../Model/conexion.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

$alerta = '';
$alerta_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nombre_empresa']) && !isset($_POST['editar_id_empresa']) && !isset($_POST['eliminar_id_empresa'])) {
        $nombreEmpresa = $_POST['nombre_empresa'];
        $path = null;

        if (isset($_FILES['logo_empresa']) && $_FILES['logo_empresa']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo_empresa']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $nombreArchivo = uniqid('logo_') . '.' . $ext;
                $destino = '../assets/logos/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['logo_empresa']['tmp_name'], $destino)) {
                    $path = 'assets/logos/' . $nombreArchivo;
                }
            }
        }

        $sqlInsert = "INSERT INTO empresa (nombre, path, borrado) VALUES ('$nombreEmpresa', " . ($path ? "'$path'" : "NULL") . ", 0)";
        if ($conn->query($sqlInsert)) {
            $alerta = "Empresa creada correctamente.";
            $alerta_tipo = "success";
        } else {
            $alerta = "Error al crear la empresa.";
            $alerta_tipo = "error";
        }
    }
    if (isset($_POST['editar_id_empresa']) && isset($_POST['nuevo_nombre'])) {
        $idEdit = $_POST['editar_id_empresa'];
        $nuevoNombre = $_POST['nuevo_nombre'];
        $setPath = "";

        if (isset($_POST['eliminar_logo']) && $_POST['eliminar_logo'] === '1') {
            $setPath = ", path = NULL";
        } else {
            if (isset($_FILES['logo_empresa_edit']) && $_FILES['logo_empresa_edit']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo_empresa_edit']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $nombreArchivo = uniqid('logo_') . '.' . $ext;
                    $destino = '../assets/logos/' . $nombreArchivo;
                    if (move_uploaded_file($_FILES['logo_empresa_edit']['tmp_name'], $destino)) {
                        $path = 'assets/logos/' . $nombreArchivo;
                        $setPath = ", path = '$path'";
                    }
                }
            }
        }

        $updateSql = "UPDATE empresa SET nombre = '$nuevoNombre' $setPath WHERE id_empresa = '$idEdit'";
        if ($conn->query($updateSql)) {
            $alerta = "Empresa editada correctamente.";
            $alerta_tipo = "success";
        } else {
            $alerta = "Error al editar la empresa.";
            $alerta_tipo = "error";
        }
    }
    if (isset($_POST['eliminar_id_empresa'])) {
        $idEliminar = $_POST['eliminar_id_empresa'];
        mysqli_begin_transaction($conn);
        $sql = "UPDATE empresa SET borrado = 1 WHERE id_empresa = '$idEliminar'";
        $resultado = $conn->query($sql);
        if ($resultado) {
            $sqlVouchers = "UPDATE voucher SET id_empresa = NULL WHERE id_empresa = '$idEliminar'";
            $conn->query($sqlVouchers);
            mysqli_commit($conn);
            $alerta = "Empresa eliminada correctamente.";
            $alerta_tipo = "success";
        } else {
            mysqli_rollback($conn);
            $alerta = "Error al eliminar la empresa.";
            $alerta_tipo = "error";
        }
    }
}

$sql = "SELECT e.*, COUNT(v.id_remito_v) AS total_vouchers 
        FROM empresa e
        LEFT JOIN voucher v ON e.id_empresa = v.id_empresa AND v.borrado = 0
        WHERE e.borrado = 0
        GROUP BY e.id_empresa
        ORDER BY e.nombre ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empresas</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
<style>
    .logo-preview {
    display: block;
    max-width: 120px;
    max-height: 80px;
    margin: 10px auto 0 auto;
    border: 1px solid #ccc;
    background: #fafafa;
    object-fit: contain;
    transition: filter 0.2s;
}
#logo_edit_container {
    position: relative;
    display: inline-block;
}
#btnEliminarLogo {
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.5);
    border: none;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 2;
}
#logo_edit_container:hover #editar_logo_preview {
    filter: brightness(0.4);
}
#logo_edit_container:hover #btnEliminarLogo {
    display: flex;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<?php if ($alerta): ?>
    <div class="alerta <?= $alerta_tipo ?>" id="alerta-empresa">
        <?= htmlspecialchars($alerta) ?>
    </div>
    <script>
    setTimeout(function() {
        var alerta = document.getElementById('alerta-empresa');
        if (alerta) alerta.style.display = 'none';
    }, 2500);
    </script>
<?php endif; ?>

<div class="empresa-container">
    <button class="crear-btn" onclick="openModal()"><span style="font-weight:bold;font-size:1.2em;">+</span> &nbsp;Crear empresa</button>

    <div id="crearEmpresaModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Crear Empresa</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="nombre_empresa" placeholder="Nombre de la empresa" required>
                <br><br>
                <label>Logo (PNG/JPG):</label>
                <input type="file" name="logo_empresa" accept="image/png, image/jpeg" onchange="previewLogo(this, 'crear_logo_preview')">
                <img id="crear_logo_preview" class="logo-preview" style="display:none;max-width:120px;max-height:80px;">
                <br>
                <input type="submit" value="Crear">
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>Editar Empresa</h2>
            <form method="POST" id="formEditarEmpresa" enctype="multipart/form-data">
                <input type="hidden" name="editar_id_empresa" id="edit_id_empresa">
                <label for="nuevo_nombre">Nuevo nombre:</label>
                <input type="text" id="edit_nombre_empresa" name="nuevo_nombre" required>
                <br><br>
                <label>Logo (PNG/JPG):</label>
                <input type="file" name="logo_empresa_edit" accept="image/png, image/jpeg" onchange="previewLogo(this, 'editar_logo_preview')">
                <div id="logo_edit_container" style="position:relative; display:none; margin-top:10px;">
                    <img id="editar_logo_preview" class="logo-preview" style="display:none;max-width:120px;max-height:80px;">
                    <button type="button" id="btnEliminarLogo"
                    style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);border:none;cursor:pointer;align-items:center;justify-content:center;z-index:2;"
                    onclick="eliminarLogoEmpresa(event)">
                    <img src="../assets/boton-eliminar.png" alt="Eliminar" style="width:32px;height:32px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
                    </button>
                </div>
                <input type="hidden" name="eliminar_logo" id="eliminar_logo_hidden" value="0">
                <br>
                <input type="submit" value="Guardar Cambios">
            </form>
        </div>
    </div>

    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEliminar')">&times;</span>
            <h2>¿Eliminar Empresa?</h2>
            <p>Esta acción no se puede deshacer.</p>
            <form method="POST">
            <input type="hidden" id="delete_id_empresa" name="eliminar_id_empresa">
            <input type="submit" class="eliminar-btn" value="Eliminar">
            </form>
        </div>
    </div>

    <h2 style="color:#6c47a6; margin-top:40px;">Empresas Creadas</h2>

    <div class="empresas-grid">
        <?php while ($row = $result->fetch_assoc()): ?>
        <div class="empresa-wrapper">
            <div class="empresa-card">
                <div class="empresa-header">
                    <h3><?= htmlspecialchars($row['nombre']) ?></h3>
                    <span class="card-actions">
                        <button class="icon-btn edit" title="Editar"
                            onclick="abrirModalEditar('<?= $row['id_empresa'] ?>', '<?= htmlspecialchars($row['nombre']) ?>', '<?= htmlspecialchars($row['path']) ?>'); event.stopPropagation();">
                            <img src="../assets/boton-editar.png" alt="Editar">
                        </button>
                        <button class="icon-btn delete" title="Borrar"
                            onclick="abrirModalEliminar('<?= $row['id_empresa'] ?>'); event.stopPropagation();">
                            <img src="../assets/boton-eliminar.png" alt="Eliminar">
                        </button>
                    </span>
                </div>
                <a class="empresa-link" href="empresa.php?id_empresa=<?= $row['id_empresa'] ?>">
                    <p>Vouchers: <?= $row['total_vouchers'] ?? 0 ?></p>
                </a>
            </div>
        </div>
        <?php endwhile; ?>

        </div>
    </div>

<script>
    function openModal() {
        document.getElementById('crearEmpresaModal').style.display = 'flex';
        document.body.classList.add('modal-open');
        document.getElementById('crear_logo_preview').style.display = 'none';
    }
    function closeModal() {
        document.getElementById('crearEmpresaModal').style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    function abrirModalEditar(id, nombre, path) {
        document.getElementById('edit_id_empresa').value = id;
        document.getElementById('edit_nombre_empresa').value = nombre;
        document.getElementById('modalEditar').style.display = 'flex';
        document.body.classList.add('modal-open');
        var preview = document.getElementById('editar_logo_preview');
        var btnEliminar = document.getElementById('btnEliminarLogo');
        var eliminarLogoHidden = document.getElementById('eliminar_logo_hidden');
        var container = document.getElementById('logo_edit_container');
        eliminarLogoHidden.value = "0";
        if (path && path !== 'null') {
            preview.src = '../' + path;
            preview.style.display = 'block';
            container.style.display = 'inline-block';
            btnEliminar.style.display = 'none'; 
        } else {
            preview.style.display = 'none';
            btnEliminar.style.display = 'none';
            eliminarLogoHidden.value = "0";
            container.style.display = 'none';
        }
    }
    document.getElementById('logo_edit_container').addEventListener('mouseenter', function() {
        var preview = document.getElementById('editar_logo_preview');
        var btnEliminar = document.getElementById('btnEliminarLogo');
        if (preview.style.display === 'block') {
            btnEliminar.style.display = 'flex';
        }
    });
    document.getElementById('logo_edit_container').addEventListener('mouseleave', function() {
        document.getElementById('btnEliminarLogo').style.display = 'none';
    });

    function eliminarLogoEmpresa(e) {
        e.preventDefault();
        var preview = document.getElementById('editar_logo_preview');
        var eliminarLogoHidden = document.getElementById('eliminar_logo_hidden');
        preview.style.display = 'none';
        eliminarLogoHidden.value = "1";
        document.getElementById('btnEliminarLogo').style.display = 'none';
    }

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    function abrirModalEliminar(id) {
        document.getElementById('delete_id_empresa').value = id;
        document.getElementById('modalEliminar').style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function previewLogo(input, imgId) {
        const file = input.files[0];
        const img = document.getElementById(imgId);
        const btnEliminar = document.getElementById('btnEliminarLogo');
        const eliminarLogoHidden = document.getElementById('eliminar_logo_hidden');
        const container = document.getElementById('logo_edit_container');
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.style.display = 'block';
                btnEliminar.style.display = 'none';
                eliminarLogoHidden.value = "0";
                container.style.display = 'inline-block';
            }
            reader.readAsDataURL(file);
        } else {
            img.style.display = 'none';
            btnEliminar.style.display = 'none';
            eliminarLogoHidden.value = "0";
            container.style.display = 'none';
        }
    }

    function togglePreviewEliminar() {
        var cb = document.getElementById('eliminar_logo_cb');
        var img = document.getElementById('editar_logo_preview');
        if (cb.checked) {
            img.style.display = 'none';
        } else if (img.src && img.src !== window.location.href) {
            img.style.display = 'block';
        }
    }

    window.onclick = function(event) {
        const modal = document.getElementById('crearEmpresaModal');
        const editModal = document.getElementById('modalEditar');
        const eliminarModal = document.getElementById('modalEliminar');
        if (event.target === modal) modal.style.display = 'none';
        if (event.target === editModal) editModal.style.display = 'none';
        if (event.target === eliminarModal) eliminarModal.style.display = 'none';
    }
    document.addEventListener('keydown', function(e) {
        const modals = document.querySelectorAll('.modal');
        const modalAbierto = Array.from(modals).find(m => m.style.display === 'flex');
        if (modalAbierto) {
            const focusables = modalAbierto.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.key === 'Tab') {
                if (e.shiftKey) { 
                    if (document.activeElement === first) {
                        last.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === last) {
                        first.focus();
                        e.preventDefault();
                    }
                }
            }
        }
    });
</script>
</body>
</html>