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
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            width: 80%;
            max-width: 550px;
            min-width: 300px;
            position: relative; 
        }

        .modal-content .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        .modal-content .close:hover,
        .modal-content .close:focus {
            color: #333;
            text-decoration: none;
        }

        .mini-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .mini-modal .modal-content {
            padding: 20px;
            max-width: 400px;
            text-align: center;
        }
        .mini-modal .modal-content label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .mini-modal .modal-content select,
        .mini-modal .modal-content input[type="submit"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .mini-modal .modal-content input[type="submit"] {
            background-color: #6A3D63;
            color: white;
            cursor: pointer;
            border: none;
        }
        .mini-modal .modal-content input[type="submit"]:hover {
            background-color: #753C83; 
        }

        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap; 
            gap: 8px; 
        }

        .pagination-controls .page-link {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-decoration: none;
            color: #753C83; 
            background-color: #fff;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
            min-width: 30px; 
            text-align: center;
        }

        .pagination-controls .page-link.active-page {
            background-color: #753C83; 
            color: white;
            border-color: #753C83; 
        }

        .pagination-controls .page-link:hover:not(.active-page) {
            background-color: #E6E0EA; 
            border-color: #753C83; 
        }
    </style>
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
                <input type="search" id="filter-no-aprobados-search" placeholder="Pasajero, Origen o Destino">
                <label for="filter-no-aprobados-fecha-desde">Desde:</label>
                <input type="date" id="filter-no-aprobados-fecha-desde">
                <label for="filter-no-aprobados-fecha-hasta">Hasta:</label>
                <input type="date" id="filter-no-aprobados-fecha-hasta">
            </form>
            <div class="styled-table">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>ID</th>
                            <th>Chofer</th>
                            <th>Pasajero</th>
                            <th>Empresa</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Observaciones</th>
                            <th>Tiempo Espera</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-no-aprobados"></tbody>
                </table>
            </div>
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
            <div class="styled-table">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>ID</th>
                            <th>Chofer</th>
                            <th>Pasajero</th>
                            <th>Empresa</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Observaciones</th>
                            <th>Tiempo Espera</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-aprobados"></tbody>
                </table>
            </div>
            <div class="pagination" id="pagination-aprobados"></div>
        </section>
    </div>

    <div id="modalEditarVoucher" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditarVoucher')">&times;</span>
            <h2>Editar Voucher</h2>
            <form id="formEditarVoucher">
                <input type="hidden" id="edit-id-remito-v">
                <label for="edit-pasajero">Pasajero:</label>
                <input type="text" id="edit-pasajero" required><br>
                <label for="edit-origen">Origen:</label>
                <input type="text" id="edit-origen" required><br>
                <label for="edit-hora-origen">Hora Origen:</label>
                <input type="text" id="edit-hora-origen"><br>
                <label for="edit-destino">Destino:</label>
                <input type="text" id="edit-destino" required><br>
                <label for="edit-hora-destino">Hora Destino:</label>
                <input type="text" id="edit-hora-destino"><br>
                <label for="edit-observaciones">Observaciones:</label>
                <textarea id="edit-observaciones"></textarea><br>
                <label for="edit-tiempo-espera">Tiempo de Espera:</label>
                <input type="text" id="edit-tiempo-espera"><br>
                <label for="edit-fecha">Fecha:</label>
                <input type="date" id="edit-fecha" required><br>
                <button type="submit" class="">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <div id="mini-modal-mover" class="mini-modal">
        <div class="modal-content">
            <span id="close-mini-modal" class="close">&times;</span>
            <form id="form-mover-voucher" method="post" action="../Model/asignarEmpresa.php" target="_self">
                <input type="hidden" name="id_remito_v" id="mini-modal-id-remito">
                <label for="mini-modal-select-empresa">Seleccionar empresa:</label>
                <select name="id_empresa" id="mini-modal-select-empresa" required>
                    </select>
                <input type="submit" value="Mover/Desaprobar Voucher">
            </form>
        </div>
    </div>

    <script>
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

            if (tabName === 'no-aprobados') {
                triggerNoAprobados();
            } else if (tabName === 'aprobados') {
                triggerAprobados();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.mostrarAlertaGlobal = function(mensaje, tipo = 'info') {
                const alertBox = document.createElement('div');
                alertBox.className = `alert-global alert-${tipo}`;
                alertBox.textContent = mensaje;
                document.body.appendChild(alertBox);
                setTimeout(() => {
                    alertBox.remove();
                }, 3000); 
            };

            window.abrirModal = function(idModal) {
                console.log(`[Modal] Abriendo modal: ${idModal}`);
                document.getElementById(idModal).style.display = 'flex';
            }
            window.cerrarModal = function(idModal) {
                console.log(`[Modal] Cerrando modal: ${idModal}`);
                document.getElementById(idModal).style.display = 'none';
            }

            const filterNoAprobadosSearch = document.getElementById('filter-no-aprobados-search');
            const filterNoAprobadosDesde = document.getElementById('filter-no-aprobados-fecha-desde');
            const filterNoAprobadosHasta = document.getElementById('filter-no-aprobados-fecha-hasta');
            const tbodyNoAprobados = document.getElementById('tbody-no-aprobados');
            const paginationNoAprobados = document.getElementById('pagination-no-aprobados');

            const filterAprobadosSearch = document.getElementById('filter-aprobados-search');
            const filterAprobadosDesde = document.getElementById('filter-aprobados-fecha-desde');
            const filterAprobadosHasta = document.getElementById('filter-aprobados-fecha-hasta');
            const tbodyAprobados = document.getElementById('tbody-aprobados');
            const paginationAprobados = document.getElementById('pagination-aprobados');

            const miniModalMover = document.getElementById('mini-modal-mover');
            const miniModalIdRemito = document.getElementById('mini-modal-id-remito');
            const miniModalSelectEmpresa = document.getElementById('mini-modal-select-empresa');
            const closeMiniModal = document.getElementById('close-mini-modal');

            function loadVouchers(type, page, searchTerm, fechaDesde, fechaHasta, idEmpresa = '', tbodyElement, paginationElement, reporteElement = null) {
                console.log(`[loadVouchers] Loading ${type} vouchers, page: ${page}, search: "${searchTerm}", from: ${fechaDesde}, to: ${fechaHasta}, idEmpresa: ${idEmpresa}`);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../Controller/filtrarVouchers.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        tbodyElement.innerHTML = response.html;
                        paginationElement.innerHTML = response.pagination;
                        if (reporteElement && typeof response.totalRecords !== "undefined") {
                            reporteElement.textContent = `Cantidad de vouchers: ${response.totalRecords}`;
                        }
                        console.log(`[loadVouchers] Data loaded. HTML length: ${response.html.length}, Pagination length: ${response.pagination.length}`);

                        paginationElement.querySelectorAll('.page-link').forEach(link => {
                            link.addEventListener('click', function(e) {
                                e.preventDefault();
                                console.log(`[loadVouchers] Pagination link clicked: page ${this.dataset.page}`);
                                const newPage = this.dataset.page;
                                loadVouchers(type, newPage, searchTerm, fechaDesde, fechaHasta, idEmpresa, tbodyElement, paginationElement, reporteElement);
                            });
                        });
                        console.log('[loadVouchers] Pagination listeners re-attached.');


                        if (type === 'noaprobados') {
                            const aprobarButtons = document.querySelectorAll('.aprobar-btn');
                            console.log(`[loadVouchers] Found ${aprobarButtons.length} "Aprobar" buttons.`);
                            aprobarButtons.forEach(button => {
                                button.onclick = function() {
                                    console.log('[aprobar-btn] Aprobar button clicked.');
                                    const idRemito = this.dataset.id;
                                    if (confirm('¿Estás seguro de que quieres aprobar este voucher?')) {
                                        fetch('../Model/aprobar_voucher.php', { 
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: `id=${encodeURIComponent(idRemito)}`
                                        })
                                        .then(response => response.text())
                                        .then(text => {
                                            if (text.trim() === 'OK') {
                                                mostrarAlertaGlobal('Voucher aprobado exitosamente.', 'success');
                                                triggerNoAprobados(); 
                                                triggerAprobados(); 
                                            } else {
                                                mostrarAlertaGlobal('Error al aprobar el voucher: ' + text, 'error');
                                                console.error('[aprobar-btn] Error al aprobar el voucher:', text);
                                            }
                                        })
                                        .catch(error => {
                                            mostrarAlertaGlobal('Error de conexión al aprobar el voucher.', 'error');
                                            console.error('[aprobar-btn] Error de conexión al aprobar el voucher:', error);
                                        });
                                    }
                                };
                            });
                        }

                        const editButtons = document.querySelectorAll('.edit-voucher-btn');
                        console.log(`[loadVouchers] Found ${editButtons.length} "Editar" buttons.`);
                        editButtons.forEach(button => {
                            button.onclick = function() {
                                console.log('[edit-voucher-btn] Edit button clicked.');
                                const row = this.closest('tr');
                                if (row) {
                                    document.getElementById('edit-id-remito-v').value = row.dataset.id;
                                    document.getElementById('edit-pasajero').value = row.dataset.pasajero;
                                    document.getElementById('edit-origen').value = row.dataset.origen;
                                    document.getElementById('edit-hora-origen').value = row.dataset.horaorigen; 
                                    document.getElementById('edit-destino').value = row.dataset.destino;
                                    document.getElementById('edit-hora-destino').value = row.dataset.horadestino; 
                                    document.getElementById('edit-observaciones').value = row.dataset.observaciones;
                                    document.getElementById('edit-tiempo-espera').value = row.dataset.tiempoespera;
                                    document.getElementById('edit-fecha').value = row.dataset.fecha;
                                } else {
                                    console.warn('[edit-voucher-btn] Parent row not found for edit button.');
                                }
                                abrirModal('modalEditarVoucher');
                            };
                        });
                        console.log('[loadVouchers] Edit listeners re-attached.');

                        const moveButtons = document.querySelectorAll('.btn-mover'); 
                        console.log(`[loadVouchers] Found ${moveButtons.length} "Mover" buttons.`);
                        moveButtons.forEach(button => {
                            button.onclick = function() {
                                console.log('[btn-mover] Move button clicked.');
                                const idRemito = this.dataset.id;
                                miniModalIdRemito.value = idRemito;
                                miniModalSelectEmpresa.innerHTML = '<option value="">Seleccionar empresa</option>';

                                fetch('../Model/get_companies.php') 
                                    .then(response => response.json())
                                    .then(empresas => {
                                        empresas.forEach(empresa => {
                                            const option = document.createElement('option');
                                            option.value = empresa.id_empresa;
                                            option.textContent = empresa.nombre;
                                            miniModalSelectEmpresa.appendChild(option);
                                        });
                                        const desaprobarOption = document.createElement('option');
                                        desaprobarOption.value = 'desaprobar';
                                        desaprobarOption.textContent = 'Desaprobar voucher';
                                        miniModalSelectEmpresa.appendChild(desaprobarOption);
                                        abrirModal('mini-modal-mover');
                                    })
                                    .catch(error => {
                                        console.error('[btn-mover] Error fetching companies:', error);
                                        mostrarAlertaGlobal('Error al cargar la lista de empresas.', 'error');
                                    });
                            };
                        });
                        console.log('[loadVouchers] Move listeners re-attached.');


                        const pdfButtons = document.querySelectorAll('.btn-monto-pdf');
                        console.log(`[loadVouchers] Found ${pdfButtons.length} "PDF" buttons.`);
                        pdfButtons.forEach(button => {
                            button.onclick = function() {
                                console.log('[btn-monto-pdf] PDF button clicked.');
                                const idRemito = this.dataset.id;
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '../Controller/generarPdf.php';
                                form.target = '_blank'; 
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'id';
                                input.value = idRemito;
                                form.appendChild(input);
                                document.body.appendChild(form);
                                form.submit();
                                document.body.removeChild(form);
                            };
                        });
                        console.log('[loadVouchers] PDF listeners re-attached.');

                        const deleteButtons = document.querySelectorAll('.delete-voucher-btn');
                        console.log(`[loadVouchers] Found ${deleteButtons.length} "Eliminar" buttons.`);
                        deleteButtons.forEach(button => {
                            button.onclick = function() {
                                console.log('[delete-voucher-btn] Delete button clicked.');
                                const idRemito = this.dataset.id;
                                if (confirm('¿Estás seguro de que quieres eliminar este voucher? Esta acción no se puede deshacer.')) {
                                    fetch('../Model/eliminarVoucher.php', { 
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `id=${encodeURIComponent(idRemito)}`
                                    })
                                    .then(response => response.text())
                                    .then(text => {
                                        if (text.trim() === 'OK') {
                                            mostrarAlertaGlobal('Voucher eliminado exitosamente.', 'success');
                                            triggerNoAprobados();
                                            triggerAprobados();
                                        } else {
                                            mostrarAlertaGlobal('Error al eliminar el voucher: ' + text, 'error');
                                            console.error('[delete-voucher-btn] Error al eliminar el voucher:', text);
                                        }
                                    })
                                    .catch(error => {
                                        mostrarAlertaGlobal('Error de conexión al eliminar el voucher.', 'error');
                                        console.error('[delete-voucher-btn] Error de conexión al eliminar el voucher:', error);
                                    });
                                }
                            };
                        });
                        console.log('[loadVouchers] Delete listeners re-attached.');


                    } else {
                        console.error('[loadVouchers] Error loading vouchers, status:', this.status, 'response:', this.responseText);
                    }
                };
                xhr.onerror = function() {
                    console.error('[loadVouchers] Network error during voucher load.');
                };
                xhr.send(
                    `tipo=${type}` +
                    `&page=${page}` +
                    `&search_term=${encodeURIComponent(searchTerm)}` +
                    `&fecha_desde=${encodeURIComponent(fechaDesde || '')}` +
                    `&fecha_hasta=${encodeURIComponent(fechaHasta || '')}` +
                    (idEmpresa ? `&id_empresa=${encodeURIComponent(idEmpresa)}` : '')
                );
            }

            function triggerNoAprobados(page = 1) {
                console.log('[triggerNoAprobados] Triggering no-aprobados load.');
                loadVouchers(
                    'noaprobados',
                    page,
                    filterNoAprobadosSearch.value,
                    filterNoAprobadosDesde.value,
                    filterNoAprobadosHasta.value,
                    '', 
                    tbodyNoAprobados,
                    paginationNoAprobados,
                    document.getElementById('reporte-no-aprobados')
                );
            }
            filterNoAprobadosSearch.addEventListener('input', () => triggerNoAprobados(1));
            filterNoAprobadosDesde.addEventListener('change', () => triggerNoAprobados(1));
            filterNoAprobadosHasta.addEventListener('change', () => triggerNoAprobados(1));

            function triggerAprobados(page = 1) {
                console.log('[triggerAprobados] Triggering aprobados load.');
                loadVouchers(
                    'aprobados',
                    page,
                    filterAprobadosSearch.value,
                    filterAprobadosDesde.value,
                    filterAprobadosHasta.value,
                    '',
                    tbodyAprobados,
                    paginationAprobados,
                    document.getElementById('reporte-aprobados')
                );
            }
            filterAprobadosSearch.addEventListener('input', () => triggerAprobados(1));
            filterAprobadosDesde.addEventListener('change', () => triggerAprobados(1));
            filterAprobadosHasta.addEventListener('change', () => triggerAprobados(1));

            console.log('[DOMContentLoaded] Initial load triggered.');
            triggerNoAprobados();
            document.getElementById('aprobados').style.display = 'none';
            triggerAprobados();

            document.getElementById('formEditarVoucher').addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('[formEditarVoucher] Submit event detected.');
                const idRemito = document.getElementById('edit-id-remito-v').value;
                const pasajero = document.getElementById('edit-pasajero').value;
                const origen = document.getElementById('edit-origen').value;
                const horaOrigen = document.getElementById('edit-hora-origen').value;
                const destino = document.getElementById('edit-destino').value;
                const horaDestino = document.getElementById('edit-hora-destino').value; 
                const observaciones = document.getElementById('edit-observaciones').value;
                const tiempoEspera = document.getElementById('edit-tiempo-espera').value;
                const fecha = document.getElementById('edit-fecha').value;

                console.log(`[formEditarVoucher] Submitting form for ID: ${idRemito}, Pasajero: ${pasajero}, Origen: ${origen}, Hora Origen: ${horaOrigen}, Destino: ${destino}, Hora Destino: ${horaDestino}, Observaciones: ${observaciones}, Tiempo Espera: ${tiempoEspera}, Fecha: ${fecha}`);
                fetch('../Model/editarVoucher.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_remito_v=${encodeURIComponent(idRemito)}&nombre_pasajero=${encodeURIComponent(pasajero)}&Origen=${encodeURIComponent(origen)}&hora_origen=${encodeURIComponent(horaOrigen)}&Destino=${encodeURIComponent(destino)}&hora_destino=${encodeURIComponent(horaDestino)}&observaciones=${encodeURIComponent(observaciones)}&tiempo_espera=${encodeURIComponent(tiempoEspera)}&Fecha=${encodeURIComponent(fecha)}`
                })
                .then(response => response.text())
                .then(text => {
                    if (text.trim() === 'OK') {
                        console.log("response text:", text);
                        mostrarAlertaGlobal('Voucher actualizado exitosamente.', 'success');
                        cerrarModal('modalEditarVoucher');
                        triggerNoAprobados(); 
                        triggerAprobados();
                        console.log('[formEditarVoucher] Voucher updated successfully.');
                    } else {
                        mostrarAlertaGlobal('Error al actualizar el voucher: ' + text, 'error');
                        console.error('[formEditarVoucher] Error al actualizar el voucher:', text);
                    }
                })
                .catch(error => {
                    mostrarAlertaGlobal('Error de conexión al actualizar el voucher.', 'error');
                    console.error('[formEditarVoucher] Error de conexión al actualizar el voucher:', error);
                });
            });
            
            document.getElementById('form-mover-voucher').addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('[form-mover-voucher] Submit event detected.');
                const idRemito = miniModalIdRemito.value;
                const idEmpresa = miniModalSelectEmpresa.value;

                fetch(this.action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_remito_v=${encodeURIComponent(idRemito)}&id_empresa=${encodeURIComponent(idEmpresa)}`
                })
                .then(response => response.text())
                .then(text => {
                    cerrarModal('mini-modal-mover');
                    if (text.trim() === 'OK') {
                        mostrarAlertaGlobal('Operación de voucher exitosa.', 'success');
                        triggerNoAprobados();
                        triggerAprobados();
                        console.log('[form-mover-voucher] Voucher operation successful.');
                    } else {
                        mostrarAlertaGlobal(text || "Error en la operación del voucher.", "error");
                        console.error('[form-mover-voucher] Error en la operación del voucher:', text);
                    }
                })
                .catch(error => {
                    mostrarAlertaGlobal("Error de conexión en la operación del voucher.", "error");
                    console.error('[form-mover-voucher] Error de conexión en la operación del voucher:', error);
                });
            });

            window.onclick = function(event) {
                if (event.target == document.getElementById('modalEditarVoucher')) {
                    cerrarModal('modalEditarVoucher');
                }
                if (event.target == miniModalMover) {
                    cerrarModal('mini-modal-mover');
                }
            };

            closeMiniModal.onclick = function() {
                cerrarModal('mini-modal-mover');
            };
        });
    </script>
</body>
</html>