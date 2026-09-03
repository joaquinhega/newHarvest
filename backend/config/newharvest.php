<?php

return [
    // Usados como respaldo en el encabezado de los PDF (recibos, etc.) cuando
    // la fila de la empresa en la tabla `companies` todavía no tiene cargado
    // el CUIT o el domicilio.
    'default_cuit' => env('NEWHARVEST_CUIT', '30-71129168-3'),
    'default_address' => env('NEWHARVEST_ADDRESS', 'AV. ESPAÑA 1248 PISO 4 OFICINA 53, Ciudad Capital, Mendoza'),
];
