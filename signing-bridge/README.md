# Puente de firma con token — DEMO (sin token real todavía)

Esto es una demo funcional del mecanismo completo: navegador → extensión →
programa local → (simulación de firma) → de vuelta al navegador. Sirve para
comprobar que el circuito funciona, y cuando tengas el token a mano con el
cliente, solo hay que cambiar una función (`firmar_pdf` en `host.py`) para
que use el token real en vez de simular.

**Qué NO es esto todavía:** una firma digital criptográfica real. Es un
sello de texto que demuestra que el mensaje viaja de ida y vuelta
correctamente. El lugar exacto donde se conecta el token real está marcado
con `[PASO REAL]` en `native-host/host.py`.

## Instalación (una sola vez)

### 1. Instalar Python y las librerías

Si no tenés Python, bajalo de [python.org](https://python.org) (marcá la
casilla "Add to PATH" durante la instalación en Windows).

Después, en una terminal:
```
pip install pypdf reportlab
```

### 2. Cargar la extensión en Chrome

1. Abrí Chrome y andá a `chrome://extensions`
2. Activá "Modo de desarrollador" (arriba a la derecha)
3. Click en "Cargar descomprimida" y elegí la carpeta `extension/`
4. Copiá el **ID** que Chrome le asigna a la extensión (aparece debajo del
   nombre, es un código largo de letras)

### 3. Completar el manifest del host nativo

Abrí `native-host/com.newharvest.signer.json` y reemplazá:
- `"path"`: la ruta completa al archivo que corresponda:
  - **Windows**: ruta a `run_host_windows.bat` (ej: `C:\newHarvest\signing-bridge\native-host\run_host_windows.bat`)
  - **Mac/Linux**: ruta a `host.py` (ej: `/home/usuario/signing-bridge/native-host/host.py`)
- `"allowed_origins"`: reemplazá `ID_DE_LA_EXTENSION_AQUI` por el ID que copiaste en el paso 2

### 4. Registrar el host

- **Windows**: doble click en `instalar_windows.bat`
- **Mac/Linux**: `bash instalar_mac_linux.sh`

### 5. Probar

1. Click en el ícono de la extensión en Chrome (puede que tengas que
   fijarla desde el ícono de pieza de rompecabezas)
2. Si dice "✓ Programa local conectado" arriba, ya está funcionando
3. Elegí cualquier PDF, poné cualquier texto de "firmante" y "PIN" (en la
   demo no valida nada real), y apretá "Firmar (demo)"
4. Descargá el resultado y vas a ver el sello "FIRMADO (DEMO)" en la
   esquina inferior derecha

## Cuando llegue el momento de probar con el token real

Solo hay que tocar la función `firmar_pdf()` en `host.py` — está toda
señalada la sección donde va la conexión PKCS#11 real. El resto (la
extensión, el protocolo de mensajes, la interfaz) no cambia.

## Errores comunes

- **"No se encontró el programa local instalado"**: revisá que el `path`
  en el JSON sea correcto y que hayas corrido el instalador
- **Nada pasa al firmar**: abrí la consola del popup (click derecho sobre
  el popup → Inspeccionar) para ver el error exacto
