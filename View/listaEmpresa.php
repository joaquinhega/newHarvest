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
    // Crear empresa
    if (isset($_POST['nombre_empresa']) && !isset($_POST['editar_id_empresa']) && !isset($_POST['eliminar_id_empresa'])) {
        $nombreEmpresa = $_POST['nombre_empresa'];
        $sqlInsert = "INSERT INTO empresa (nombre, borrado) VALUES ('$nombreEmpresa', 0)";
        if ($conn->query($sqlInsert)) {
            $alerta = "Empresa creada correctamente.";
            $alerta_tipo = "success";
        } else {
            $alerta = "Error al crear la empresa.";
            $alerta_tipo = "error";
        }
    }
    // Editar empresa
    if (isset($_POST['editar_id_empresa']) && isset($_POST['nuevo_nombre'])) {
        $idEdit = $_POST['editar_id_empresa'];
        $nuevoNombre = $_POST['nuevo_nombre'];
        $updateSql = "UPDATE empresa SET nombre = '$nuevoNombre' WHERE id_empresa = '$idEdit'";
        if ($conn->query($updateSql)) {
            $alerta = "Empresa editada correctamente.";
            $alerta_tipo = "success";
        } else {
            $alerta = "Error al editar la empresa.";
            $alerta_tipo = "error";
        }
    }
    // Eliminar empresa (borrado lógico)
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

// Obtener empresas y cantidad de vouchers
$sql = "SELECT e.*, COUNT(v.id_remito_v) AS total_vouchers 
        FROM empresa e
        LEFT JOIN voucher v ON e.id_empresa = v.id_empresa
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

    </style>
</head>
<body>

<?php include 'header.php'; ?>

<?php if ($alerta): ?>
    <div class="alerta <?= $alerta_tipo ?>">
        <?= htmlspecialchars($alerta) ?>
    </div>
<?php endif; ?>

<div class="empresa-container">
    <button class="crear-btn" onclick="openModal()"><span style="font-weight:bold;font-size:1.2em;">+</span> &nbsp;Crear empresa</button>

    <!-- Modal Crear Empresa -->
    <div id="crearEmpresaModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Crear Empresa</h2>
            <form method="POST">
                <input type="text" name="nombre_empresa" placeholder="Nombre de la empresa" required>
                <br><br>
                <input type="submit" value="Crear">
            </form>
        </div>
    </div>

<!-- MODAL EDITAR EMPRESA -->
<div id="modalEditar" class="modal">
  <div class="modal-content">
    <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
    <h2>Editar Empresa</h2>
    <form method="POST" id="formEditarEmpresa">
    <input type="hidden" name="editar_id_empresa" id="edit_id_empresa">
    <label for="nuevo_nombre">Nuevo nombre:</label>
    <input type="text" id="edit_nombre_empresa" name="nuevo_nombre" required>
    <br><br>
    <input type="submit" value="Guardar Cambios">
    </form>
  </div>
</div>

<!-- MODAL ELIMINAR EMPRESA -->
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
                    onclick="abrirModalEditar('<?= $row['id_empresa'] ?>', '<?= htmlspecialchars($row['nombre']) ?>'); event.stopPropagation();">
                    <img src="../assets/boton-editar.png" alt="Editar">
                </button>
                <button class="icon-btn delete" title="Borrar"
                    onclick="abrirModalEliminar('<?= $row['id_empresa'] ?>'); event.stopPropagation();">
                    <img src="../assets/boton-eliminar.png" alt="Eliminar">
                </button>
            </span>
        </div>
        <a class="empresa-link" href="empresa.php?id_empresa=<?= $row['id_empresa'] ?>">
            <p>Vouchers: <?= $row['total_vouchers'] ?></p>
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
}
function closeModal() {
    document.getElementById('crearEmpresaModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

function abrirModalEditar(id, nombre) {
    document.getElementById('edit_id_empresa').value = id;
    document.getElementById('edit_nombre_empresa').value = nombre;
    document.getElementById('modalEditar').style.display = 'flex';
    document.body.classList.add('modal-open');
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


    // Cerrar modales al hacer click fuera
    window.onclick = function(event) {
        const modal = document.getElementById('crearEmpresaModal');
        const editModal = document.getElementById('editarEmpresaModal');
        const eliminarModal = document.getElementById('eliminarEmpresaModal');
        if (event.target === modal) modal.style.display = 'none';
        if (event.target === editModal) editModal.style.display = 'none';
        if (event.target === eliminarModal) eliminarModal.style.display = 'none';
    }
    // Bloquea el tab fuera del modal cuando hay uno abierto
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
            } else { // tab
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