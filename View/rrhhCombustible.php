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
    <title>Lista de Combustibles</title>
    <link rel="stylesheet" href="../Estilo/styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="rrhh-container">
        <div class="tabs-container" role="tablist">
            <button class="tab-button active" onclick="openTab(event, 'no-aprobados')" aria-selected="true" aria-controls="no-aprobados">Remitos No Aprobados</button>
            <button class="tab-button" onclick="openTab(event, 'aprobados')" aria-selected="false" aria-controls="aprobados">Remitos Aprobados</button>
        </div>

        <section id="no-aprobados" class="tab-content active" role="tabpanel" aria-labelledby="no-aprobados">
            <div class="section-title">Remitos No Aprobados</div>
            <div id="reporte-no-aprobados" class="reporte-cantidad" style="margin-bottom:10px; color:#444; font-size:1.05em;"></div>
            <form class="filter-section" id="filtrosNoAprobados" onsubmit="return false;">
                <label for="filter-no-aprobados-search">Buscar:</label>
                <input type="search" id="filter-no-aprobados-search" placeholder="Patente o Chofer">
                <label for="filter-no-aprobados-fecha-desde">Desde:</label>
                <input type="date" id="filter-no-aprobados-fecha-desde">
                <label for="filter-no-aprobados-fecha-hasta">Hasta:</label>
                <input type="date" id="filter-no-aprobados-fecha-hasta">
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Monto</th>
                        <th>Patente</th>
                        <th>Fecha</th>
                        <th>Nombre del Chofer</th>
                        <th>Aprobar</th>
                    </tr>
                </thead>
                <tbody id="tbody-no-aprobados"></tbody>
            </table>
            <div class="pagination" id="pagination-no-aprobados"></div>
        </section>

        <section id="aprobados" class="tab-content" style="display:none;" role="tabpanel" aria-labelledby="aprobados">
            <div class="section-title">Remitos Aprobados</div>
            <div id="reporte-aprobados" class="reporte-cantidad" style="margin-bottom:10px; color:#444; font-size:1.05em;"></div>
            <form class="filter-section" id="filtrosAprobados" onsubmit="return false;">
                <label for="filter-aprobados-search">Buscar:</label>
                <input type="search" id="filter-aprobados-search" placeholder="Patente o Chofer">
                <label for="filter-aprobados-fecha-desde">Desde:</label>
                <input type="date" id="filter-aprobados-fecha-desde">
                <label for="filter-aprobados-fecha-hasta">Hasta:</label>
                <input type="date" id="filter-aprobados-fecha-hasta">
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Monto</th>
                        <th>Patente</th>
                        <th>Fecha</th>
                        <th>Nombre del Chofer</th>
                    </tr>
                </thead>
                <tbody id="tbody-aprobados"></tbody>
            </table>
            <div class="pagination" id="pagination-aprobados"></div>
        </section>
    </div>

    <script>
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

        document.addEventListener('DOMContentLoaded', function() {
            // No Aprobados
            const filterNoAprobadosSearch = document.getElementById('filter-no-aprobados-search');
            const filterNoAprobadosDesde = document.getElementById('filter-no-aprobados-fecha-desde');
            const filterNoAprobadosHasta = document.getElementById('filter-no-aprobados-fecha-hasta');
            const tbodyNoAprobados = document.getElementById('tbody-no-aprobados');
            const paginationNoAprobados = document.getElementById('pagination-no-aprobados');

            // Aprobados
            const filterAprobadosSearch = document.getElementById('filter-aprobados-search');
            const filterAprobadosDesde = document.getElementById('filter-aprobados-fecha-desde');
            const filterAprobadosHasta = document.getElementById('filter-aprobados-fecha-hasta');
            const tbodyAprobados = document.getElementById('tbody-aprobados');
            const paginationAprobados = document.getElementById('pagination-aprobados');

            function loadCombustible(type, page, searchTerm, fechaDesde, fechaHasta, tbodyElement, paginationElement, reporteElement = null) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'filtrarCombustible.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        tbodyElement.innerHTML = response.html;
                        paginationElement.innerHTML = response.pagination;
                        if (reporteElement && typeof response.totalRecords !== "undefined") {
                            reporteElement.textContent = `Cantidad de remitos: ${response.totalRecords}`;
                        }
                        // Paginación
                        paginationElement.querySelectorAll('.page-link').forEach(link => {
                            link.addEventListener('click', function(e) {
                                e.preventDefault();
                                const newPage = this.dataset.page;
                                loadCombustible(type, newPage, searchTerm, fechaDesde, fechaHasta, tbodyElement, paginationElement, reporteElement);
                            });
                        });
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

            function triggerNoAprobados(page = 1) {
                loadCombustible(
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

            function triggerAprobados(page = 1) {
                loadCombustible(
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

            // Carga inicial
            triggerNoAprobados();
            triggerAprobados();
        });
    </script>
</body>
</html>