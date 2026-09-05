# Auditoría de seguridad — puente de firma con token

Este documento queda como referencia para retomar el desarrollo. Cubre qué
puede salir mal, qué ya está mitigado, y qué falta antes de un uso real con
clientes.

## Contexto importante encontrado en la investigación

Existe un mecanismo IDÉNTICO al nuestro (extensión Chrome + Native Messaging
Host) que malware real usó en una campaña documentada en Italia (2026): una
extensión falsa + un host nativo instalado vía un adjunto de mail, usado
como puente para ejecutar PowerShell fuera del sandbox de Chrome y robar
cookies de sesión. Esto no invalida el enfoque — es el mismo mecanismo que
usan gestores de contraseñas legítimos (Bitwarden) y sistemas de firma de
gobiernos enteros (GDE Argentina, AutoFirma España, eID Estonia) — pero
confirma que hay que tratarlo con el mismo cuidado que cualquier puente
entre el navegador y el sistema operativo.

## Riesgos identificados y su estado

### 1. Que otra extensión (maliciosa) hable con nuestro host nativo
**Mitigado.** El manifest del host (`allowed_origins`) solo acepta mensajes
de nuestra extensión, identificada por su ID fijo (generado con una clave
RSA propia, no depende de dónde se cargue). Ninguna otra extensión puede
invocarlo, ni siquiera conociendo su nombre.

### 2. Que una página web maliciosa (no newHarvest) dispare una firma
**Pendiente.** Hoy el popup es standalone — el usuario elige el PDF a mano,
no hay ninguna página web que pueda mandarle instrucciones a la extensión
todavía. Cuando se conecte con newHarvest (o con la futura SaaS), hay que
agregar: verificación de que el mensaje viene del origen exacto esperado
(`https://sistema.newharvest.com.ar`, no cualquier sitio), y pedir
confirmación explícita del usuario en cada firma — nunca firmar en
silencio. Este es el mismo patrón que usa el proyecto de referencia
`open-eid/chrome-token-signing` (firma de eID de Estonia, en producción
hace más de 10 años): valida el origen en cada mensaje de una sesión, y
exige que el usuario confirme manualmente el certificado a usar cada vez.

### 3. El PIN del token viaja por el mismo canal que el resto de los datos
**Riesgo bajo, pero mejorable.** El protocolo Native Messaging no encripta
nada — son simples pipes de texto entre procesos de la misma PC, no viaja
por ninguna red (no hay "hombre en el medio" posible ahí, porque no hay
"en el medio": es comunicación local, misma máquina, mismo usuario). El
riesgo real no es de red, es de que otro proceso en la misma PC ya
comprometida intercepte la memoria — y contra eso ningún diseño de software
protege del todo (ver punto de riesgos aceptados). Mejora recomendada antes
de producción: que el PIN se pida en una ventana nativa del sistema
operativo (no en el popup HTML de la extensión), para que nunca pase por
el JavaScript de la extensión ni quede en el historial de inputs del
navegador.

### 4. Que se guarde o filtre el PIN sin querer
**Mitigado en el código actual, reforzar antes de producción.** `host.py`
no escribe el PIN a ningún archivo ni lo imprime en logs. Antes de la
versión final: confirmar que no queda en ningún archivo temporal, ninguna
variable de entorno, ningún log de errores.

### 5. Manipulación del PDF en tránsito (que se firme un documento distinto al que el usuario ve)
**Pendiente de diseño.** Falta un chequeo de integridad: mostrarle al
usuario un resumen del documento (o una vista previa) antes de pedir el
PIN, para que no firme "a ciegas" algo que fue reemplazado en el camino
entre la web y la extensión. El proyecto de referencia de Estonia exige
que el usuario confirme el certificado explícitamente en cada operación
por esta misma razón.

### 6. Tamaño de página / mensajes malformados (DoS básico)
**Pendiente.** Buenas prácticas del ecosistema (ver referencia eID)
recomiendan poner un límite de tamaño a los mensajes aceptados y rechazar
prolijamente cualquier mensaje con formato inesperado, en vez de crashear.
Hoy `host.py` no tiene ese límite explícito.

### 7. Integridad del instalador/extensión (que no se pueda alterar antes de llegar al apoderado)
**Pendiente, importante para la versión final.** Antes de distribuir el
instalador `.exe` real: firmarlo digitalmente (Authenticode, certificado
de firma de código de Windows) para que el sistema operativo confirme que
viene de nosotros y no fue modificado. Publicar la extensión en la Chrome
Web Store agrega además una revisión de Google como capa extra.

## Riesgos que NO tienen solución de software (aceptados, se documentan para ser honestos con el cliente)

- **Si la PC del apoderado ya está infectada con malware/keylogger**, ningún
  diseño nuestro (ni el de Adobe, ni el de ningún sistema de firma del
  mundo) puede proteger el PIN — el atacante ya está "adentro" antes de que
  nuestra herramienta entre en juego. Esto es igual para todos: Adobe, GDE,
  AutoFirma, bancos. Se mitiga con buenas prácticas generales de seguridad
  de la PC (antivirus, no ejecutar adjuntos de mails desconocidos — el
  mismo vector que usó la campaña de malware italiana), no con el diseño
  de esta herramienta puntual.
- **El token físico perdido o robado** sigue siendo un riesgo humano/físico,
  no de software — se mitiga con el PIN (algo que sabés) sumado al token
  (algo que tenés), que es exactamente para lo que sirve esa combinación.

## Checklist antes de uso real con el cliente (no solo demo)

- [ ] Prompt de PIN nativo (fuera del popup HTML)
- [ ] Validación de origen cuando se conecte con una página web
- [ ] Confirmación explícita del usuario en cada firma (no automática/silenciosa)
- [ ] Límite de tamaño de mensaje en `host.py`
- [ ] Extensión firmada y publicada en Chrome Web Store (revisión de Google)
- [ ] Instalador `.exe` firmado digitalmente (Authenticode)
- [ ] Confirmar que el PIN nunca se escribe a disco ni a logs, en ningún punto del flujo
- [ ] Chequeo de vigencia del certificado (no vencido, no revocado — OCSP) antes de firmar
