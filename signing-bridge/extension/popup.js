const estadoHost = document.getElementById('estadoHost');
const inputPdf = document.getElementById('inputPdf');
const inputFirmante = document.getElementById('inputFirmante');
const inputPin = document.getElementById('inputPin');
const btnFirmar = document.getElementById('btnFirmar');
const resultado = document.getElementById('resultado');

let pdfSeleccionado = null;

function fijarEstado(texto, tipo) {
    estadoHost.textContent = texto;
    estadoHost.className = `estado ${tipo}`;
}

// 1) Al abrir el popup, chequear que el programa local esté instalado y responda
chrome.runtime.sendMessage({ tipo: 'PING_HOST' }, (respuesta) => {
    if (respuesta && respuesta.ok) {
        fijarEstado('✓ Programa local conectado — ' + respuesta.mensaje, 'ok');
    } else {
        fijarEstado(
            '✗ No se encontró el programa local instalado en esta PC. ' +
            (respuesta?.error || ''),
            'error'
        );
    }
});

inputPdf.addEventListener('change', (e) => {
    pdfSeleccionado = e.target.files[0] || null;
    actualizarBotonFirmar();
});

function actualizarBotonFirmar() {
    btnFirmar.disabled = !pdfSeleccionado || !inputPin.value;
}
inputPin.addEventListener('input', actualizarBotonFirmar);

function pdfToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            // reader.result es "data:application/pdf;base64,XXXX" — nos quedamos con la parte de después de la coma
            const base64 = reader.result.split(',')[1];
            resolve(base64);
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

btnFirmar.addEventListener('click', async () => {
    if (!pdfSeleccionado) return;

    btnFirmar.disabled = true;
    btnFirmar.textContent = 'Firmando...';
    resultado.innerHTML = '';

    try {
        const pdfBase64 = await pdfToBase64(pdfSeleccionado);
        const firmante = inputFirmante.value || 'Apoderado (demo)';
        const pin = inputPin.value;

        chrome.runtime.sendMessage(
            { tipo: 'FIRMAR_PDF', pdfBase64, pin, firmante },
            (respuesta) => {
                btnFirmar.disabled = false;
                btnFirmar.textContent = 'Firmar (demo)';

                if (!respuesta || !respuesta.ok) {
                    resultado.innerHTML = `<span style="color:#b71c1c">Error: ${respuesta?.error || 'desconocido'}</span>`;
                    return;
                }

                // Convertir el PDF firmado (base64) de vuelta a un archivo descargable
                const byteCharacters = atob(respuesta.pdf_base64);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                const blob = new Blob([byteArray], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);

                resultado.innerHTML = `
                    <span style="color:#1e7e34">✓ Firmado (modo demo). ${respuesta.nota || ''}</span>
                    <a class="descargar" href="${url}" download="recibo_firmado_demo.pdf">Descargar PDF firmado (demo)</a>
                `;
            }
        );
    } catch (err) {
        btnFirmar.disabled = false;
        btnFirmar.textContent = 'Firmar (demo)';
        resultado.innerHTML = `<span style="color:#b71c1c">Error: ${err.message}</span>`;
    }
});
