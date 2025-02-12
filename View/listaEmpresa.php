<?php 
session_start();
include '../Model/conexion.php';

if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
    exit();
}

$crearEmpresa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nombre_empresa'])) {
        $nombreEmpresa = $_POST['nombre_empresa'];
        $sqlInsert = "INSERT INTO empresa (nombre, borrado) VALUES ('$nombreEmpresa', 0)";
        $conn->query($sqlInsert);
    } elseif (isset($_POST['toggle_crear'])) {
        $crearEmpresa = $_POST['toggle_crear'] === 'mostrar';
    }
}

// Consultar todas las empresas
$sql = "SELECT * FROM empresa WHERE borrado = 0 ORDER BY nombre ASC"; 
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Estilo/styles.css">
    <title>Empresas</title>
</head>
<body>
    <div class="empresa-container">
        <header>
            <img src="../logo-newHarvest.png" alt="New Harvest Logo">
        </header>
        <br><br>
        <button onclick="openModal()">+ Crear Empresa</button>

        <div id="crearEmpresaModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Crear Empresa</h2>
                <form action="" method="POST">
                    <label for="nombre_empresa">Nombre de la Empresa:</label>
                    <input type="text" autocomplete="off" id="nombre_empresa" name="nombre_empresa" required><br>
                    <input type="submit" value="Crear">
                </form>
            </div>
        </div>

        <div class="lista-empresas">
            <h2>Empresas Creadas</h2>
            <?php while ($row = $result->fetch_assoc()): ?>
                <a href="empresa.php?id_empresa=<?= $row['id_empresa'] ?>">
                    <button><?= htmlspecialchars($row['nombre']) ?></button>
                </a><br>
            <?php endwhile; ?>
        </div>
    </div>
    
    <a href="rrhh.php"><button class="logout-btn">Volver</button></a>

    <script>
        // JavaScript para abrir y cerrar el modal
        function openModal() {
            document.getElementById('crearEmpresaModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('crearEmpresaModal').style.display = 'none';
        }

        // Cierra el modal si el usuario hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('crearEmpresaModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>