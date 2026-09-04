<?php

return [
    // Usados como respaldo en el encabezado de los PDF (recibos, etc.) cuando
    // la fila de la empresa en la tabla `companies` todavía no tiene cargado
    // el CUIT o el domicilio.
    'default_cuit' => env('NEWHARVEST_CUIT', '30-71129168-3'),
    'default_address' => env('NEWHARVEST_ADDRESS', 'AV. ESPAÑA 1248 PISO 4 OFICINA 53, Ciudad Capital, Mendoza'),

    // Modo de firma de empresa: 'simulado' (desarrollo) | 'real' (producción con token USB).
    // En modo simulado se registra la fecha y el firmante pero no se estampa
    // firma criptográfica en el PDF — el flujo y los estados son idénticos.
    // Cambiar a 'real' en producción una vez disponible el certificado (Lote 9).
    'firma_modo' => env('NEWHARVEST_FIRMA_MODO', 'simulado'),
];
