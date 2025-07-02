<?php
include '../Model/conexion.php';
session_start();

if(!isset($_SESSION['user'])){
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Vouchers</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="rrhh-container">
        <div class="tabs-container" role="tablist">
            <button class="tab-button active" onclick="openTab(event, 'no-aprobados')" aria-selected="true" aria-controls="no-aprobados">Vouchers No Aprobados</button>
            <button class="tab-button" onclick="openTab(event, 'aprobados')" aria-selected="false" aria-controls="aprobados">Vouchers Aprobados</button>
        </div>

        <section id="no-aprobados" class="tab-content active" role="tabpanel" aria-labelledby="no-aprobados">
            <div class="section-title">Vouchers No Aprobados</div>
            <div id="reporte-no-aprobados" class="reporte-cantidad" style="margin-bottom:10px; color:#444; font-size:1.05em;"></div>
            <form class="filter-section" id="filtrosNoAprobados" onsubmit="return false;">
                <label for="filter-no-aprobados-search">Buscar:</label>
                <input type="search" id="filter-no-aprobados-search" placeholder="Empresa o Pasajero">
                <label for="filter-no-aprobados-fecha-desde">Desde:</label>
                <input type="date" id="filter-no-aprobados-fecha-desde">
                <label for="filter-no-aprobados-fecha-hasta">Hasta:</label>
                <input type="date" id="filter-no-aprobados-fecha-hasta">
            </form>
            <table id="vouchers-no-aprobados-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>ID Remito</th>
                        <th>Pasajero</th>
                        <th>Empresa</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Observaciones</th>
                        <th>Tiempo de espera</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-no-aprobados"></tbody>
            </table>
            <div class="pagination" id="pagination-no-aprobados"></div>
        </section>

        <section id="aprobados" class="tab-content" style="display:none;" role="tabpanel" aria-labelledby="aprobados">
            <div class="section-title">Vouchers Aprobados</div>
            <div id="reporte-aprobados" class="reporte-cantidad" style="margin-bottom:10px; color:#444; font-size:1.05em;"></div>
            <form class="filter-section" id="filtrosAprobados" onsubmit="return false;">
                <label for="filter-aprobados-search">Buscar:</label>
                <input type="search" id="filter-aprobados-search" placeholder="Empresa o Pasajero">
                <label for="filter-aprobados-fecha-desde">Desde:</label>
                <input type="date" id="filter-aprobados-fecha-desde">
                <label for="filter-aprobados-fecha-hasta">Hasta:</label>
                <input type="date" id="filter-aprobados-fecha-hasta">
            </form>
            <table id="vouchers-aprobados-table">
                <thead>
                    <tr>
                        <th class="fecha-col">Fecha</th>
                        <th>ID Remito</th>
                        <th>Pasajero</th>
                        <th>Empresa</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Observaciones</th>
                        <th>Tiempo de espera</th>
                        <th>Mover</th>
                    </tr>
                </thead>
                <tbody id="tbody-aprobados"></tbody>
            </table>
            <div class="pagination" id="pagination-aprobados"></div>
        </section>
    </div>

    <script>
        // Functions for the "Mover" popup
        function mostrarFormulario(id) {
            document.querySelectorAll('.form-popup').forEach(form => form.style.display = 'none');
            const popup = document.getElementById('form-popup-' + id);
            if (popup) popup.style.display = 'block';
        }
        function ocultarFormulario(id) {
            const popup = document.getElementById('form-popup-' + id);
            if (popup) popup.style.display = 'none';
        }
        window.onclick = function(event) {
            document.querySelectorAll('.form-popup').forEach(form => {
                if (event.target !== form && !form.contains(event.target) && !event.target.classList.contains('mover-btn')) {
                    form.style.display = 'none';
                }
            });
        }

        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
                tablinks[i].setAttribute('aria-selected', 'false');
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.className += " active";
            evt.currentTarget.setAttribute('aria-selected', 'true');

            // Cargar los datos de la pestaña activa al cambiar
            if (tabName === 'no-aprobados') {
                triggerNoAprobados();
            } else if (tabName === 'aprobados') {
                triggerAprobados();
            }
        }
        function initMiniModalMover() {
            const modal = document.getElementById("mini-modal-mover");
            if (!modal) return;
            document.querySelectorAll(".btn-mover").forEach(btn => {
                btn.onclick = function() {
                    modal.style.display = "flex";
                    document.getElementById("mini-modal-id-remito").value = this.dataset.id;
                };
            });
            document.getElementById("close-mini-modal").onclick = function() {
                modal.style.display = "none";
            };
            modal.onclick = function(e) {
                if(e.target === this) this.style.display = "none";
            };
            document.getElementById("form-mover-voucher").onsubmit = function(e) {
                var select = document.getElementById("mini-modal-select-empresa");
                if(select.value === "desaprobar") {
                    e.preventDefault();
                    if(confirm("¿Seguro que deseas desaprobar este voucher?")) {
                        var form = document.createElement("form");
                        form.method = "post";
                        form.action = "../Model/asignarEmpresa.php";
                        var input1 = document.createElement("input");
                        input1.type = "hidden";
                        input1.name = "id_remito_v";
                        input1.value = document.getElementById("mini-modal-id-remito").value;
                        var input2 = document.createElement("input");
                        input2.type = "hidden";
                        input2.name = "id_empresa";
                        input2.value = "desaprobar";
                        form.appendChild(input1);
                        form.appendChild(input2);
                        document.body.appendChild(form);
                        form.submit();
                    }
                } else {
                    // Enviar por AJAX
                    e.preventDefault();
                    var formData = new FormData(this);
                    fetch("../Model/asignarEmpresa.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Puedes mostrar un mensaje de éxito o recargar la tabla
                        alert("Empresa asignada correctamente.");
                        modal.style.display = "none";
                        // Recargar la tabla de aprobados
                        if (typeof triggerAprobados === "function") triggerAprobados();
                    })
                    .catch(() => {
                        alert("Error al asignar empresa.");
                    });
                }
            };
        }
        // AJAX Filtering and Pagination Logic
        document.addEventListener('DOMContentLoaded', function() {
            // Get filter inputs for No Aprobados
            const filterNoAprobadosSearch = document.getElementById('filter-no-aprobados-search');
            const filterNoAprobadosDesde = document.getElementById('filter-no-aprobados-fecha-desde');
            const filterNoAprobadosHasta = document.getElementById('filter-no-aprobados-fecha-hasta');
            const tbodyNoAprobados = document.getElementById('tbody-no-aprobados');
            const paginationNoAprobados = document.getElementById('pagination-no-aprobados');

            // Get filter inputs for Aprobados
            const filterAprobadosSearch = document.getElementById('filter-aprobados-search');
            const filterAprobadosDesde = document.getElementById('filter-aprobados-fecha-desde');
            const filterAprobadosHasta = document.getElementById('filter-aprobados-fecha-hasta');
            const tbodyAprobados = document.getElementById('tbody-aprobados');
            const paginationAprobados = document.getElementById('pagination-aprobados');

            // Function to load vouchers via AJAX (acepta fechas y página)
            function loadVouchers(type, page, searchTerm, fechaDesde, fechaHasta, tbodyElement, paginationElement, reporteElement = null) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'filtrarVouchers.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        tbodyElement.innerHTML = response.html;
                        paginationElement.innerHTML = response.pagination;

                        // Mostrar cantidad de vouchers si se pasa el elemento reporte
                        if (reporteElement && typeof response.totalRecords !== "undefined") {
                            reporteElement.textContent = `Cantidad de vouchers: ${response.totalRecords}`;
                        }

                        // Re-attach event listeners for "Mover" buttons on new content
                        tbodyElement.querySelectorAll('.mover-btn').forEach(button => {
                            const idMatch = button.onclick.toString().match(/'([^']+)'/);
                            if (idMatch && idMatch[1]) {
                                const id = idMatch[1];
                                button.onclick = () => mostrarFormulario(id);
                            }
                        });
                        tbodyElement.querySelectorAll('.form-popup').forEach(popup => {
                            const id = popup.id.replace('form-popup-', '');
                            const cancelButton = popup.querySelector('button[type="button"]');
                            if (cancelButton) {
                                cancelButton.onclick = () => ocultarFormulario(id);
                            }
                        });

                        // Re-attach event listeners for pagination buttons
                        paginationElement.querySelectorAll('.page-link').forEach(link => {
                            link.addEventListener('click', function(e) {
                                e.preventDefault();
                                const newPage = this.dataset.page;
                                loadVouchers(type, newPage, searchTerm, fechaDesde, fechaHasta, tbodyElement, paginationElement, reporteElement);
                            });
                        });
                        initMiniModalMover();
                    }
                };
                xhr.send(
                    `tipo=${type}` +
                    `&page=${page}` +
                    `&search_term=${encodeURIComponent(searchTerm)}` +
                    `&fecha_desde=${encodeURIComponent(fechaDesde || '')}` +
                    `&fecha_hasta=${encodeURIComponent(fechaHasta || '')}`
                );
            }

            // Event listeners for No Aprobados filter
            function triggerNoAprobados(page = 1) {
                loadVouchers(
                    'noaprobados',
                    page,
                    filterNoAprobadosSearch.value,
                    filterNoAprobadosDesde.value,
                    filterNoAprobadosHasta.value,
                    tbodyNoAprobados,
                    paginationNoAprobados,
                    document.getElementById('reporte-no-aprobados')
                );
            }
            filterNoAprobadosSearch.addEventListener('input', () => triggerNoAprobados(1));
            filterNoAprobadosDesde.addEventListener('change', () => triggerNoAprobados(1));
            filterNoAprobadosHasta.addEventListener('change', () => triggerNoAprobados(1));

            // Event listeners for Aprobados filter
            function triggerAprobados(page = 1) {
                loadVouchers(
                    'aprobados',
                    page,
                    filterAprobadosSearch.value,
                    filterAprobadosDesde.value,
                    filterAprobadosHasta.value,
                    tbodyAprobados,
                    paginationAprobados,
                    document.getElementById('reporte-aprobados')
                );
            }
            filterAprobadosSearch.addEventListener('input', () => triggerAprobados(1));
            filterAprobadosDesde.addEventListener('change', () => triggerAprobados(1));
            filterAprobadosHasta.addEventListener('change', () => triggerAprobados(1));

            // Load initial data for both tabs
            triggerNoAprobados();
            triggerAprobados();
        });
    </script>
</body>
</html>