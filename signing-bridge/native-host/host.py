#!/usr/bin/env python3
"""
newHarvest — Host nativo del puente de firma digital (DEMO / SIMULACIÓN)
==========================================================================

Qué es esto:
Este programa corre en la PC del apoderado y es la única pieza capaz de
"hablar" con el token criptográfico (protocolo PKCS#11). El navegador NO
puede hacerlo directamente por seguridad — por eso existe este puente.

Cómo se comunica con el navegador:
Chrome usa un protocolo llamado "Native Messaging": el navegador ejecuta
este script y le manda mensajes por stdin/stdout, cada uno con un prefijo
de 4 bytes indicando su longitud, seguido del mensaje en formato JSON.
Este script nunca se conecta a internet ni escucha en ningún puerto —
solo responde lo que el navegador le pide, en la misma máquina.

Estado actual (DEMO): la función firmar_pdf() de más abajo NO usa un
token real todavía — solo le agrega un sello de texto visible al PDF para
demostrar que el circuito completo (extensión → este programa → de vuelta
a la web) funciona de punta a punta. Está señalado con [PASO REAL] el
lugar exacto donde se conecta el token criptográfico de verdad cuando lo
tengan a mano para probar con el cliente.
"""

import sys
import json
import struct
import base64
import io
from datetime import datetime

# --- Utilidades del protocolo Native Messaging ---------------------------

def leer_mensaje():
    """Lee un mensaje que mandó la extensión del navegador."""
    raw_length = sys.stdin.buffer.read(4)
    if not raw_length:
        return None
    message_length = struct.unpack('=I', raw_length)[0]
    message = sys.stdin.buffer.read(message_length).decode('utf-8')
    return json.loads(message)


def enviar_mensaje(mensaje_dict):
    """Devuelve una respuesta a la extensión del navegador."""
    encoded = json.dumps(mensaje_dict).encode('utf-8')
    sys.stdout.buffer.write(struct.pack('=I', len(encoded)))
    sys.stdout.buffer.write(encoded)
    sys.stdout.buffer.flush()


# --- Lógica de firma -------------------------------------------------------

def firmar_pdf(pdf_bytes: bytes, pin: str, firmante: str) -> bytes:
    """
    Recibe los bytes de un PDF sin firmar y devuelve los bytes del PDF firmado.

    [PASO REAL — reemplazar en la prueba con el cliente]
    Acá es exactamente donde, con el token insertado, se debe:
      1. Abrir una sesión PKCS#11 contra la librería del fabricante del
         token (ej. en Windows suele ser un archivo .dll que instala el
         driver del token, distinto según la marca).
      2. Hacer login en el token con el `pin` que mandó el usuario.
      3. Tomar el certificado y la clave privada del token.
      4. Firmar criptográficamente el PDF con esa clave (formato PAdES),
         en la posición fija ya acordada (misma esquina siempre, porque
         el diseño del recibo no cambia).
      5. Cerrar la sesión del token.

    Hoy, en la demo, simplemente estampamos un sello de texto para probar
    que el viaje de ida y vuelta (navegador → este programa → navegador)
    funciona. No es una firma criptográfica real todavía.
    """
    from pypdf import PdfReader, PdfWriter
    from reportlab.pdfgen import canvas
    from reportlab.lib.pagesizes import letter

    reader = PdfReader(io.BytesIO(pdf_bytes))
    writer = PdfWriter()

    primera_pagina = reader.pages[0]
    ancho = float(primera_pagina.mediabox.width)
    alto = float(primera_pagina.mediabox.height)

    # Posición medida directamente sobre el PDF real de New Harvest: hay una
    # franja libre entre el pie de firma del recibo (y≈209, donde termina el
    # sello legacy "Documento firmado...") y el inicio de la tabla de
    # "CONTRIBUCIONES" (y≈177). Ahí entra el sello sin pisar nada. Esto es
    # fijo porque el recibo usa una plantilla de tamaño constante — si el
    # layout cambia, volver a medir con pdfplumber (page.extract_words())
    # en vez de ajustar a ojo.
    sello_x = 390
    sello_y_top_pdf = 203

    buffer_overlay = io.BytesIO()
    c = canvas.Canvas(buffer_overlay, pagesize=(ancho, alto))
    c.setFillColorRGB(0.05, 0.4, 0.2)
    c.setFont("Helvetica-Bold", 7.5)
    c.drawString(sello_x, sello_y_top_pdf, "✓ FIRMADO DIGITALMENTE (empresa)")
    c.setFont("Helvetica", 6)
    c.drawString(sello_x, sello_y_top_pdf - 9, f"Por: {firmante}")
    c.drawString(sello_x, sello_y_top_pdf - 17, f"Fecha: {datetime.now().strftime('%d/%m/%Y %H:%M')}")
    c.save()
    buffer_overlay.seek(0)

    overlay_reader = PdfReader(buffer_overlay)
    primera_pagina.merge_page(overlay_reader.pages[0])

    for pagina in reader.pages:
        writer.add_page(pagina)

    salida = io.BytesIO()
    writer.write(salida)
    return salida.getvalue()


# --- Loop principal ---------------------------------------------------------

def main():
    mensaje = leer_mensaje()
    if mensaje is None:
        return

    try:
        accion = mensaje.get('accion')

        if accion == 'ping':
            enviar_mensaje({'ok': True, 'mensaje': 'Puente de firma conectado.'})
            return

        if accion == 'firmar':
            pdf_b64 = mensaje['pdf_base64']
            pin = mensaje.get('pin', '')
            firmante = mensaje.get('firmante', 'Apoderado (demo)')

            if not pin:
                enviar_mensaje({'ok': False, 'error': 'Falta el PIN del token.'})
                return

            pdf_bytes = base64.b64decode(pdf_b64)
            pdf_firmado = firmar_pdf(pdf_bytes, pin, firmante)

            enviar_mensaje({
                'ok': True,
                'pdf_base64': base64.b64encode(pdf_firmado).decode('ascii'),
                'modo': 'simulado',
            })
            return

        enviar_mensaje({'ok': False, 'error': f'Acción desconocida: {accion}'})

    except Exception as e:
        enviar_mensaje({'ok': False, 'error': str(e)})


if __name__ == '__main__':
    main()
