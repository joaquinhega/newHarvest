<?php
include '../Model/conexion.php';
session_start();

$sql = "SELECT * FROM voucher WHERE borrado = 0 AND id_empresa IS NULL ORDER BY Fecha DESC ";
$resultNoAprobados = $conn->query($sql);
$resultAprobados = $conn->query($sql);

$empresasSql = "SELECT id_empresa, nombre FROM empresa WHERE borrado = 0";
$resultEmpresas = $conn->query($empresasSql);
if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
}?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Vouchers</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <div class="rrhh-container">
        <header>
            <img src="../logo-newHarvest.png" alt="New Harvest Logo">
        </header>
            <h2>No Aprobados</h2>
            <table>
                <tr>
                    <th>ID Remito</th>
                    <th>Empresa</th>
                    <th>Pasajero</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Fecha</th>
                    <th>Observaciones</th>
                    <th>Tiempo de espera</th>
                    <th>Asignar Monto</th>
                    <th>Acciones</th>
                </tr>
                <?php while($row = $resultNoAprobados->fetch_assoc()): 
                    if($row['aprobado'] == 0){ ?>
                <tr>
                    <td><?= $row['id_remito_v'] ?></td>
                    <td><?= $row['Empresa'] ?></td>
                    <td><?= $row['nombre_pasajero'] ?></td>
                    <td><?= $row['Origen'] . "({$row['hora_origen']}hs)" ?></td>
                    <td><?= $row['Destino'] . "({$row['hora_destino']}hs)" ?></td>
                    <td><?= $row['Fecha'] ?></td>
                    <td><?= $row['observaciones'] ?></td>
                    <td><?= $row['tiempo_espera'] ?></td>
                    <td>
                        <form action="../Model/aprobar_voucher.php" method="post">
                            <input type="hidden" name="id" value="<?= $row['id_remito_v'] ?>">
                            <input type="number" name="monto" placeholder="Monto" required>
                            <input type="submit" value="Aprobar">
                        </form>
                    </td>
                    <td>
                        <div class="boton-acciones">
                            <a href="../Model/editarVoucher.php?id_remito_v=<?= $row['id_remito_v'] ?>"><button id="boton-editar"><img src="../boton-editar.png" width="20px" height="20px"></button></a>
                            <a href="../Model/eliminarVoucher.php?id_remito_v=<?= $row['id_remito_v'] ?>"><button id="boton-eliminar"><img src="../boton-eliminar.png" width="20px" height="20px"></button></a>
                        </div>
                    </td>
                </tr>
                <?php } endwhile; ?>
            </table>
            <br>
            <h2>Aprobados</h2>
            <table>
                <tr>
                    <th>ID Remito</th>
                    <th>Empresa</th>
                    <th>Pasajero</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Observaciones</th>
                    <th>Tiempo de espera</th>
                    <th>Mover</th>
                </tr>
                <?php while($row = $resultAprobados->fetch_assoc()): 
                    if($row['aprobado'] == 1){ ?>
                <tr>
                    <td><?= $row['id_remito_v'] ?></td>
                    <td><?= $row['Empresa'] ?></td>
                    <td><?= $row['nombre_pasajero'] ?></td>
                    <td><?= $row['Origen'] . "({$row['hora_origen']}hs)" ?></td>
                    <td><?= $row['Destino'] . "({$row['hora_destino']}hs)" ?></td>
                    <td><?= $row['Fecha'] ?></td>
                    <td><?= $row['Monto'] ?></td>
                    <td><?= $row['observaciones'] ?></td>
                    <td><?= $row['tiempo_espera'] ?></td>
                    <td>
                        <button class="mover-btn" onclick="mostrarFormulario('<?= $row['id_remito_v'] ?>')">Mover</button>
                        <div id="form-popup-<?= $row['id_remito_v'] ?>" class="form-popup">
                            <h3>Mover a Empresa</h3>
                            <form action="../Model/asignarEmpresa.php" method="post">
                                <input type="hidden" name="id_remito_v" value="<?= $row['id_remito_v'] ?>">
                                <select name="id_empresa" required>
                                    <option value="no_autorizar">No autorizar</option> 
                                    <?php
                                    $resultEmpresas->data_seek(0);
                                    while($empresa = $resultEmpresas->fetch_assoc()): ?>
                                        <option value="<?= $empresa['id_empresa'] ?>"><?= $empresa['nombre'] ?></option>
                                    <?php endwhile; ?>
                                </select><br>
                                <button type="submit">Confirmar</button>
                                <button type="button" onclick="ocultarFormulario('<?= $row['id_remito_v'] ?>')">Cancelar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php } endwhile; ?>
            </table>
        <a href="rrhh.php"><button class="logout-btn">Volver</button></a>
    </div>
    <script>
        function mostrarFormulario(id) {
            document.querySelectorAll('.form-popup').forEach(form => form.style.display = 'none');
            document.getElementById('form-popup-' + id).style.display = 'block';
        }
        function ocultarFormulario(id) {
            document.getElementById('form-popup-' + id).style.display = 'none';
        }        
        window.onclick = function(event) {
            document.querySelectorAll('.form-popup').forEach(form => {
                if (event.target != form && !form.contains(event.target) && event.target.tagName != "BUTTON") {
                    form.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>