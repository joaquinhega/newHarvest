<?php
include '../Model/conexion.php';
session_start();

if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
    exit();
}

$id = $_GET['id_empresa'] ?? '';
if (!$id) {
    echo "Empresa no especificada.";
    exit();
}

// Obtener nombre de la empresa
$sqlEmpresa = "SELECT nombre FROM empresa WHERE id_empresa = '$id' AND borrado = 0";
$resultEmpresa = $conn->query($sqlEmpresa);
$empresa = $resultEmpresa->fetch_assoc();
if (!$empresa) {
    echo "Empresa no encontrada.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vouchers de <?= htmlspecialchars($empresa['nombre']) ?></title>
    <link rel="stylesheet" href="../Estilo/styles.css">
    <style>
    .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); justify-content:center; align-items:center; }
    .modal-content { background:#fff; padding:24px 18px 18px 18px; border-radius:8px; min-width:260px; max-width:90vw; box-shadow:0 8px 32px #0002; position:relative; }
    .modal-content .close { position:absolute; right:12px; top:8px; font-size:1.5em; cursor:pointer; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="rrhh-container">
        <h2>Vouchers de <?= htmlspecialchars($empresa['nombre']) ?></h2>
        <div id="reporte-empresa" class="reporte-cantidad" style="margin-bottom:10px; color:#444; font-size:1.05em;"></div>
        <form class="filter-section" id="filtrosEmpresa" onsubmit="return false;">
            <input type="hidden" id="filtro-id-empresa" value="<?= $id ?>">
            <label for="filtro-empresa-search">Buscar:</label>
            <input type="search" id="filtro-empresa-search" placeholder="Pasajero, Origen o Destino">
            <label for="filtro-empresa-fecha-desde">Desde:</label>
            <input type="date" id="filtro-empresa-fecha-desde">
            <label for="filtro-empresa-fecha-hasta">Hasta:</label>
            <input type="date" id="filtro-empresa-fecha-hasta">
        </form>
        <div class="styled-table">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>ID Remito</th>
                        <th>Pasajero</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Observaciones</th>
                        <th>Tiempo de Espera</th>
                        <th>Monto</th>
                        <th>Generar PDF</th>
                    </tr>
                </thead>
                <tbody id="tbody-empresa-vouchers"></tbody>
            </table>
        </div>
        <div class="pagination" id="pagination-empresa"></div>
        <a href="listaEmpresa.php"><button class="logout-btn">Volver</button></a>
    </div>

<!-- Modal para ingresar/modificar monto -->
<div id="modalMonto" class="modal">
  <div class="modal-content" style="background:#fff; padding:24px 18px 18px 18px; border-radius:8px; width:50%; box-shadow:0 8px 32px #0002; position:relative;">
    <span class="close" onclick="cerrarModalMonto()" style="position:absolute; right:12px; top:8px; font-size:1.5em; cursor:pointer;">&times;</span>
    <h2 style="color:#6A3D63;">Asignar/Modificar Monto</h2>
    <form id="formMontoVoucher">
      <input type="hidden" name="id_remito_v" id="modalMontoIdRemito">
      <label for="modalMontoInput" style="font-weight:bold; margin-bottom:5px; display:block;">Monto ($):</label>
      <input type="number" min="0" step="1" name="monto" id="modalMontoInput" required
        style="width:170px; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:4px; font-size:1em;">
      <div style="display:flex; justify-content:center; gap:15px;">
        <button type="button" onclick="cerrarModalMonto()" style="background-color:#D9534F; color:#fff; border:none; padding:10px 20px; border-radius:6px; font-size:1em; cursor:pointer;">Cancelar</button>
        <button type="submit" style="background-color:#753C83; color:#fff; border:none; padding:10px 20px; border-radius:6px; font-size:1em; cursor:pointer;">Generar PDF</button>
      </div>
    </form>
  </div>
</div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const idEmpresa = document.getElementById('filtro-id-empresa').value;
        const searchInput = document.getElementById('filtro-empresa-search');
        const fechaDesde = document.getElementById('filtro-empresa-fecha-desde');
        const fechaHasta = document.getElementById('filtro-empresa-fecha-hasta');
        const tbody = document.getElementById('tbody-empresa-vouchers');
        const pagination = document.getElementById('pagination-empresa');
        const reporte = document.getElementById('reporte-empresa');

        function loadVouchers(page = 1) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'filtrarVouchers.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    tbody.innerHTML = response.html;
                    pagination.innerHTML = response.pagination;
                    if (typeof response.totalRecords !== "undefined") {
                        reporte.textContent = `Cantidad de vouchers: ${response.totalRecords}`;
                    }
                    pagination.querySelectorAll('.page-link').forEach(link => {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            loadVouchers(this.dataset.page);
                        });
                    });

                    // Botones PDF
                    document.querySelectorAll('.btn-monto-pdf').forEach(btn => {
                        btn.onclick = function() {
                            abrirModalMonto(this.getAttribute('data-id'), this.getAttribute('data-monto'));
                        };
                    });
                }
            };
            xhr.send(
                `id_empresa=${encodeURIComponent(idEmpresa)}` +
                `&page=${page}` +
                `&search_term=${encodeURIComponent(searchInput.value)}` +
                `&fecha_desde=${encodeURIComponent(fechaDesde.value)}` +
                `&fecha_hasta=${encodeURIComponent(fechaHasta.value)}`
            );
        }

        searchInput.addEventListener('input', () => loadVouchers(1));
        fechaDesde.addEventListener('change', () => loadVouchers(1));
        fechaHasta.addEventListener('change', () => loadVouchers(1));

        loadVouchers();

        // Modal monto
        window.abrirModalMonto = function(id_remito, montoActual) {
            document.getElementById('modalMontoIdRemito').value = id_remito;
            document.getElementById('modalMontoInput').value = (montoActual && montoActual !== 'null') ? montoActual : '';
            document.getElementById('modalMonto').style.display = 'flex';
        }
        window.cerrarModalMonto = function() {
            document.getElementById('modalMonto').style.display = 'none';
        }

        document.getElementById('formMontoVoucher').onsubmit = function(e) {
            e.preventDefault();
            var id_remito = document.getElementById('modalMontoIdRemito').value;
            var monto = document.getElementById('modalMontoInput').value;
            // Actualiza el monto en la BBDD antes de generar el PDF
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../Model/setMontoVoucher.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200 && xhr.responseText === 'OK') {
                    // Generar PDF (abre en nueva ventana)
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../Controller/generarPdf.php';
                    form.target = '_blank';
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = id_remito;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                    cerrarModalMonto();
                    loadVouchers();
                } else {
                    alert('Error al guardar el monto');
                }
            };
            xhr.send('id_remito_v=' + encodeURIComponent(id_remito) + '&monto=' + encodeURIComponent(monto));
        };
    });
    </script>
</body>
</html>