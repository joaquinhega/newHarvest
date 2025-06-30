<?php
?>

<header class="main-header">
    <img src="../assets/logo-newHarvest.png" alt="New Harvest Logo" class="header-logo">
    <nav class="header-nav">
        <a href="rrhhVoucher.php" class="header-link <?= basename($_SERVER['PHP_SELF']) == 'rrhhVoucher.php' ? 'active' : '' ?>">Vouchers</a>
        <a href="rrhhCombustible.php" class="header-link <?= basename($_SERVER['PHP_SELF']) == 'rrhhCombustible.php' ? 'active' : '' ?>">Combustible</a>
        <a href="listaEmpresa.php" class="header-link <?= basename($_SERVER['PHP_SELF']) == 'listaEmpresa.php' ? 'active' : '' ?>">Empresa</a>
    </nav>
    <a href="../Controller/cerrarSesion.php" class="logout-btn-header">Cerrar Sesión</a>
</header>