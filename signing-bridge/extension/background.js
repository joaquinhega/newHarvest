// newHarvest — Puente de firma (DEMO)
// Este service worker es el único que puede hablar con el host nativo
// (el programita instalado en la PC). El popup le pide cosas a este
// archivo, y este archivo se las pasa al host nativo por Native Messaging.

const NOMBRE_HOST_NATIVO = 'com.newharvest.signer';

chrome.runtime.onMessage.addListener((mensaje, sender, sendResponse) => {
    if (mensaje.tipo === 'PING_HOST') {
        chrome.runtime.sendNativeMessage(
            NOMBRE_HOST_NATIVO,
            { accion: 'ping' },
            (respuesta) => {
                if (chrome.runtime.lastError) {
                    sendResponse({ ok: false, error: chrome.runtime.lastError.message });
                } else {
                    sendResponse(respuesta);
                }
            }
        );
        return true; // mantiene el canal abierto para la respuesta async
    }

    if (mensaje.tipo === 'FIRMAR_PDF') {
        chrome.runtime.sendNativeMessage(
            NOMBRE_HOST_NATIVO,
            {
                accion: 'firmar',
                pdf_base64: mensaje.pdfBase64,
                pin: mensaje.pin,
                firmante: mensaje.firmante,
            },
            (respuesta) => {
                if (chrome.runtime.lastError) {
                    sendResponse({ ok: false, error: chrome.runtime.lastError.message });
                } else {
                    sendResponse(respuesta);
                }
            }
        );
        return true;
    }
});
