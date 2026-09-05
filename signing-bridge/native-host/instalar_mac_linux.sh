#!/bin/bash
# ============================================================
#  Instalador del puente de firma (DEMO) - macOS / Linux
#  Correr UNA sola vez: bash instalar_mac_linux.sh
#  Antes: pip3 install pypdf reportlab
#  Y tener ya cargada la extensión en Chrome (ver README.md)
# ============================================================

set -e
CARPETA="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARCHIVO_MANIFEST="$CARPETA/com.newharvest.signer.json"

chmod +x "$CARPETA/host.py"

if [[ "$OSTYPE" == "darwin"* ]]; then
    DESTINO="$HOME/Library/Application Support/Google/Chrome/NativeMessagingHosts"
else
    DESTINO="$HOME/.config/google-chrome/NativeMessagingHosts"
fi

mkdir -p "$DESTINO"
cp "$ARCHIVO_MANIFEST" "$DESTINO/com.newharvest.signer.json"

echo ""
echo "LISTO. Copiado a: $DESTINO/com.newharvest.signer.json"
echo "Recordá haber completado antes en com.newharvest.signer.json:"
echo "  - \"path\": la ruta absoluta a host.py de esta misma carpeta"
echo "  - \"allowed_origins\": el ID de la extensión ya cargada en Chrome"
echo ""
