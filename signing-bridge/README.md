# Puente de firma con token — newHarvest

Solución fija para New Harvest. Windows únicamente (es lo que usan los
apoderados). Un apoderado firma un recibo desde newHarvest sin salir del
navegador, insertando su token criptográfico y tipeando el PIN.

**Estado actual:** el circuito completo (extensión → programa local → de
vuelta al navegador) está terminado y probado. La firma en sí todavía es
**simulada** (agrega un sello visible en el PDF, en la posición exacta
donde va la firma real, medida sobre el recibo de New Harvest) porque
todavía no probamos con el token físico. Reemplazar la simulación por la
firma criptográfica real es el único paso que falta — está marcado con
`[PASO REAL]` en `native-host/host.py`, función `firmar_pdf()`.

## Instalación (una sola vez, en la PC de cada apoderado)

1. Instalar Python desde [python.org](https://python.org) (tildar "Add to PATH" en el instalador)
2. Abrir una terminal y correr: `pip install pypdf reportlab`
3. Doble click en `native-host/instalar_windows.bat` — este paso configura todo solo, no hay que editar ningún archivo a mano
4. En Chrome, ir a `chrome://extensions`, activar "Modo de desarrollador", click en "Cargar descomprimida" y elegir la carpeta `extension/`
5. Cerrar todas las ventanas de Chrome y volver a abrirlo

Listo. El ID de la extensión ya viene fijo (no depende de dónde se cargue
ni hay que copiarlo a mano), así que estos 5 pasos son siempre los mismos,
en cualquier PC.

## Uso

1. Insertar el token
2. Click en el ícono de la extensión (o, cuando esté conectado a newHarvest, en el botón "Firmar" del sistema)
3. Elegir el PDF, poner el nombre del firmante y el PIN
4. Firmar

## Conectar con la firma real (cuando esté el token para probar)

Todo el cambio va en una sola función: `firmar_pdf()` en `native-host/host.py`.
Reemplaza el sello de texto por una firma PKCS#11 real usando la librería del
fabricante del token. El resto (extensión, protocolo, instalador) no cambia.

## Conectar con newHarvest (integración con el sistema)

Hoy la extensión funciona como popup standalone (elegís el PDF a mano) para
poder probarla sin depender de que el resto del sistema esté enchufado.
El paso siguiente es que el botón "Firmar" de Recibos de sueldo en newHarvest
llame directamente a la extensión en vez de abrir el popup manual — mismo
mecanismo, un solo click menos.
