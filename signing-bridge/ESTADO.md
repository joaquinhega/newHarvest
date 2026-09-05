# Estado — pausado para retomar como SaaS aparte

Este proyecto se muda a un chat/repo nuevo para generalizarlo como
herramienta para cualquier profesional (no solo apoderados de New Harvest):
subida de varios PDF a la vez, panel lateral en vez de popup, cobro único
por publicar en Chrome Web Store.

## Lo que ya funciona (probado en este chat)
- Circuito completo: extensión → host nativo → de vuelta al navegador
- ID de extensión fijo (no depende de la carpeta ni hay que copiarlo)
- Instalador Windows automático (sin editar JSON a mano)
- Sello de firma posicionado con precisión sobre el recibo real de
  New Harvest (medido con pdfplumber, no a ojo)
- Firma todavía SIMULADA — falta conectar el token real (ver
  `[PASO REAL]` en `native-host/host.py`, función `firmar_pdf()`)

## Lo que falta (ver también SECURITY.md)
- Conseguir la marca del token del cliente para conectar la firma real
- Convertir el prompt de PIN a ventana nativa (no HTML del popup)
- Empaquetar como instalador .exe real (sin depender de Python instalado)
- Publicar la extensión en Chrome Web Store
- Generalizar: subida múltiple, panel lateral, multi-usuario/multi-cliente
- Todo el checklist de seguridad de SECURITY.md

## Para retomar
Todo el código sigue en `signing-bridge/` de este repo. Se puede copiar tal
cual a un repo nuevo cuando se arranque el chat de la SaaS.
