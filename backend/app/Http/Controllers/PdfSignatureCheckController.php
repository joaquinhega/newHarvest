<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Chequeo rápido de firma digital en PDF, sin depender de portales externos
 * (como la Plataforma de Firma Digital Remota de ONTI, que exige tener una
 * cuenta personal con CUIL para poder usarla).
 *
 * No valida la cadena de certificación completa (eso requeriría verificar
 * contra una Autoridad Certificante licenciada) — solo responde la pregunta
 * más básica y más útil en la práctica: ¿este PDF tiene una firma digital
 * con contenido criptográfico real, o es un sello visual (imagen/texto)
 * sin nada verificable detrás? Esto alcanza para detectar el caso más común
 * de "parece firmado pero no lo está", como el que motivó esta herramienta.
 */
class PdfSignatureCheckController extends Controller
{
    public function index()
    {
        return Inertia::render('Herramientas/VerificarFirma');
    }

    public function check(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $bytes = file_get_contents($request->file('pdf')->getRealPath());

        $declaredSignatures = preg_match_all('/\/Type\s*\/Sig\b/', $bytes);
        $contentBlocks = $this->extractContentBlocks($bytes);

        $producer = null;
        if (preg_match('/\/Producer\s*\(([^)]*)\)/', $bytes, $m)) {
            // El texto suele venir en UTF-16BE con BOM; nos quedamos solo con
            // los caracteres imprimibles para mostrarlo de forma legible.
            $producer = trim(preg_replace('/[^\x20-\x7E]/', '', $m[1]));
        }

        return response()->json([
            'filename'             => $request->file('pdf')->getClientOriginalName(),
            'producer'             => $producer ?: null,
            'declared_signatures'  => $declaredSignatures,
            'content_blocks'       => $contentBlocks,
            'has_any_real_signature' => collect($contentBlocks)->contains('has_real_content', true),
        ]);
    }

    /**
     * Busca cada bloque /Contents <HEX...> del PDF (donde iría el blob
     * criptográfico real de una firma — el hash firmado con la clave
     * privada del token) y determina si tiene contenido real o está vacío
     * (relleno de ceros, un placeholder que nunca se completó).
     *
     * Nota honesta: no mapea cada bloque a "cuál firma le corresponde" —
     * algunos PDF referencian el contenido de forma indirecta (objeto
     * separado) y detectar eso con precisión requeriría un parser de PDF
     * completo, fuera del alcance de un chequeo rápido. Alcanza con saber
     * si existe AL MENOS UN bloque de firma con datos criptográficos reales
     * en todo el documento.
     */
    private function extractContentBlocks(string $bytes): array
    {
        preg_match_all('/\/Contents\s*<([0-9A-Fa-f]+)>/', $bytes, $matches);

        $results = [];
        foreach ($matches[1] as $index => $hex) {
            $isAllZeros = trim($hex, '0') === '';

            $results[] = [
                'index'            => $index + 1,
                'contents_length'  => strlen($hex),
                'has_real_content' => ! $isAllZeros,
                'status'           => $isAllZeros
                    ? 'placeholder sin completar (relleno de ceros)'
                    : 'contiene datos criptográficos',
            ];
        }

        return $results;
    }
}
