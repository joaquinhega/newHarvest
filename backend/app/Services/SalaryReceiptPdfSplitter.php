<?php

namespace App\Services;

use App\Models\Employee;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;

/**
 * Divide un PDF masivo de recibos de sueldo (todos los empleados en un solo
 * archivo, como lo exportan la mayoría de los sistemas de liquidación) en
 * un PDF individual por empleado.
 *
 * Estrategia: cada página trae el CUIT de la empresa (encabezado) Y el CUIL
 * del empleado (fila "APELLIDO, NOMBRE  LEGAJO  CUIL"). Se descarta el CUIT
 * propio de la empresa (config `newharvest.default_cuit`) y se toma el resto
 * como el CUIL del empleado. Se agrupan todas las páginas que comparten el
 * mismo CUIL — esto cubre tanto el caso de un recibo por página como el caso
 * de "original + duplicado" (que suelen aparecer en mitades separadas del
 * documento, no adyacentes). También se extraen nombre, legajo del
 * liquidador y los 3 montos (bruto/deducciones/neto) impresos en cada recibo.
 */
class SalaryReceiptPdfSplitter
{
    private const MONTHS = [
        'ENERO' => 'Enero', 'FEBRERO' => 'Febrero', 'MARZO' => 'Marzo',
        'ABRIL' => 'Abril', 'MAYO' => 'Mayo', 'JUNIO' => 'Junio',
        'JULIO' => 'Julio', 'AGOSTO' => 'Agosto', 'SEPTIEMBRE' => 'Septiembre',
        'OCTUBRE' => 'Octubre', 'NOVIEMBRE' => 'Noviembre', 'DICIEMBRE' => 'Diciembre',
    ];

    /**
     * Analiza el PDF y devuelve un grupo por cada CUIL de empleado detectado,
     * con las páginas correspondientes, el empleado ya emparejado si existe,
     * y los datos crudos leídos del documento (nombre, legajo, montos) para
     * que RRHH pueda comparar contra lo que el sistema matcheó.
     * No modifica ni crea nada — es un "dry run" para revisión manual.
     *
     * @return array{groups: array, suggested_period: ?string, total_pages: int}
     */
    public function analyze(string $absolutePath): array
    {
        $parser = new Parser();
        $document = $parser->parseFile($absolutePath);
        $pages = $document->getPages();

        $ownCuitDigits = preg_replace('/\D/', '', config('newharvest.default_cuit', ''));

        // employees.cuil normalizado (solo dígitos) => employee
        $employeesByCuil = Employee::where('borrado', false)
            ->get(['id', 'first_name', 'last_name', 'cuil'])
            ->keyBy(fn ($e) => preg_replace('/\D/', '', $e->cuil));

        $pagesByCuil = [];      // cuilDigits => [pageNumbers...]
        $acknowledgedPagesByCuil = []; // cuilDigits => [pageNumbers con "Recibí el importe..."]
        $rawDataByCuil = [];    // cuilDigits => datos crudos extraídos (última página gana si difiere)
        $suggestedPeriod = null;

        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1; // FPDI es 1-based
            $text = $page->getText();

            $cuilDigits = $this->detectEmployeeCuil($text, $ownCuitDigits);
            if ($cuilDigits) {
                $pagesByCuil[$cuilDigits][] = $pageNumber;

                // Algunos liquidadores incluyen 2 copias por persona: la copia
                // de archivo del empleador (firma en blanco) y la copia del
                // empleado, que trae la leyenda de conformidad. Preferimos
                // esta última como el documento final que va a firmar el chofer.
                if (preg_match('/Recib[ií]\s+el\s+importe/ui', $text)) {
                    $acknowledgedPagesByCuil[$cuilDigits][] = $pageNumber;
                }

                $detectedName    = $this->detectName($text);
                $detectedLegajo  = $this->detectLegajo($text);
                $detectedAmounts = $this->detectAmounts($text);

                $rawDataByCuil[$cuilDigits] = [
                    'name'    => $detectedName ?? ($rawDataByCuil[$cuilDigits]['name'] ?? null),
                    'legajo'  => $detectedLegajo ?? ($rawDataByCuil[$cuilDigits]['legajo'] ?? null),
                    'amounts' => $detectedAmounts ?? ($rawDataByCuil[$cuilDigits]['amounts'] ?? null),
                ];
            }

            if (! $suggestedPeriod && preg_match('/\b(ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)\s+(\d{4})\b/u', $text, $pm)) {
                $monthName = self::MONTHS[$pm[1]] ?? null;
                if ($monthName) {
                    $suggestedPeriod = "{$monthName} {$pm[2]}";
                }
            }
        }

        $groups = [];
        foreach ($pagesByCuil as $cuilDigits => $pageNumbers) {
            $employee = $employeesByCuil->get($cuilDigits);
            $raw = $rawDataByCuil[$cuilDigits] ?? [];
            $amounts = $raw['amounts'] ?? null;

            $gross = $amounts['gross'] ?? 0;
            $deductions = $amounts['deductions'] ?? 0;
            $net = $amounts['net'] ?? 0;
            // Los recibos reales pueden traer redondeos de centavos — margen de tolerancia chico.
            // El recibo real es: neto = bruto + no_remunerativo - deducciones.
            // No extraemos el "no remunerativo" por separado (está repartido
            // en varias líneas de conceptos sin una posición fija en el
            // texto), así que lo derivamos: si es negativo o mayor que el
            // bruto entero, es una señal real de error de lectura. Si es un
            // valor positivo razonable, es simplemente el no remunerativo
            // normal del recibo — no hay nada sospechoso.
            $impliedNonRemunerative = $net - $gross + $deductions;
            $amountsSuspicious = $amounts !== null
                && ($impliedNonRemunerative < -0.05 || $impliedNonRemunerative > $gross + 0.05);

            // Si detectamos la página con la leyenda de conformidad del empleado,
            // el PDF final usa SOLO esa (evita duplicar el mismo recibo 2 veces
            // en el documento que va a firmar el chofer). Si no se detectó
            // ninguna, se conservan todas las páginas encontradas por las dudas
            // — preferimos mostrar de más antes que perder información.
            $preferredPages = $acknowledgedPagesByCuil[$cuilDigits] ?? $pageNumbers;
            $hadDuplicates = count($pageNumbers) > count($preferredPages);

            $groups[] = [
                'cuil_detected'      => $cuilDigits,
                'cuil_formatted'     => $this->formatCuil($cuilDigits),
                'pages'              => $preferredPages,
                'all_detected_pages' => $pageNumbers,
                'page_count'         => count($preferredPages),
                'had_duplicate_copy' => $hadDuplicates,
                'employee_id'        => $employee?->id,
                'employee_name'      => $employee ? "{$employee->last_name}, {$employee->first_name}" : null,
                'matched'            => (bool) $employee,
                // Datos crudos leídos del PDF, útiles cuando no matchea a nadie
                // (para que RRHH sepa a quién corresponde y decida manualmente).
                'detected_name'      => $raw['name'] ?? null,
                'detected_legajo'    => $raw['legajo'] ?? null,
                'gross_amount'       => $gross,
                'deductions_amount'  => $deductions,
                'net_amount'         => $net,
                'implied_non_remunerative' => round($impliedNonRemunerative, 2),
                'amounts_detected'   => $amounts !== null,
                'amounts_suspicious' => $amountsSuspicious,
            ];
        }

        return [
            'groups'           => $groups,
            'suggested_period' => $suggestedPeriod,
            'total_pages'      => count($pages),
        ];
    }

    /**
     * Extrae un subconjunto de páginas del PDF fuente y las guarda como un
     * nuevo archivo PDF independiente.
     */
    public function extractPages(string $sourcePath, array $pageNumbers, string $destPath): void
    {
        $pdf = new Fpdi();
        $pdf->setSourceFile($sourcePath);

        sort($pageNumbers);
        foreach ($pageNumbers as $pageNumber) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->Output('F', $destPath);
    }

    /**
     * Busca todos los patrones tipo CUIT/CUIL (NN-NNNNNNNN-N) en el texto de
     * la página y descarta el que coincide con el CUIT propio de la empresa
     * (que siempre aparece en el encabezado). Del resto, se toma el que
     * aparece más tarde en el texto — en el formato real de estos recibos,
     * el CUIT de la empresa figura primero (encabezado) y el CUIL del
     * empleado aparece después, junto a su nombre.
     */
    private function detectEmployeeCuil(string $text, string $ownCuitDigits): ?string
    {
        if (! preg_match_all('/(\d{2}-?\d{7,8}-?\d{1})/', $text, $matches)) {
            return null;
        }

        $candidates = array_map(fn ($m) => preg_replace('/\D/', '', $m), $matches[1]);
        $candidates = array_values(array_filter($candidates, fn ($digits) => $digits !== $ownCuitDigits));

        if (empty($candidates)) {
            return null;
        }

        return $candidates[count($candidates) - 1];
    }

    /**
     * Extrae "APELLIDO, NOMBRE" de la fila que precede al CUIL detectado.
     * Formato real: "GARRITANO, OSVALDO GUSTAVO  6  20-23955835-7"
     * (nombre, legajo del liquidador, CUIL, en la misma línea/bloque).
     */
    private function detectName(string $text): ?string
    {
        if (preg_match('/([A-ZÑÁÉÍÓÚ][A-ZÑÁÉÍÓÚ .\'\-]{3,60},\s*[A-ZÑÁÉÍÓÚ][A-ZÑÁÉÍÓÚ .\'\-]{2,40})\s+\d{1,5}\s+\d{2}-?\d{7,8}-?\d{1}/u', $text, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[1]));
        }

        return null;
    }

    /**
     * Extrae el número de legajo interno del sistema liquidador (distinto
     * del ID interno de newHarvest). Sirve solo como referencia informativa
     * para que RRHH compare y confirme el matching.
     */
    private function detectLegajo(string $text): ?string
    {
        if (preg_match('/[A-ZÑÁÉÍÓÚ .\'\-]{5,60},\s*[A-ZÑÁÉÍÓÚ .\'\-]{2,40}\s+(\d{1,5})\s+\d{2}-?\d{7,8}-?\d{1}/u', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Extrae bruto/deducciones/neto del bloque de totales impreso al pie
     * de cada recibo:
     *   TOTALES
     *   IMPORTE NETO:
     *    1342427.33 258832.49
     *    1321822.89 SON PESOS: ...
     */
    private function detectAmounts(string $text): ?array
    {
        if (preg_match(
            '/TOTALES\s*IMPORTE\s*NETO:\s*([\d]+[.,]\d{2})\s+([\d]+[.,]\d{2})\s+([\d]+[.,]\d{2})\s+SON\s+PESOS/us',
            $text,
            $m
        )) {
            return [
                'gross'      => (float) str_replace(',', '.', $m[1]),
                'deductions' => (float) str_replace(',', '.', $m[2]),
                'net'        => (float) str_replace(',', '.', $m[3]),
            ];
        }

        return null;
    }

    private function formatCuil(string $digits): string
    {
        if (strlen($digits) < 10) {
            return $digits;
        }
        return substr($digits, 0, 2) . '-' . substr($digits, 2, -1) . '-' . substr($digits, -1);
    }
}
