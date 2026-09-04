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
 * Estrategia: cada recibo trae un CUIL impreso en el encabezado de la
 * página. Se extrae el texto de cada página, se detecta el CUIL, y se
 * agrupan todas las páginas que comparten el mismo CUIL — esto cubre tanto
 * el caso de un recibo por página como el caso de "original + duplicado"
 * (que suelen aparecer en mitades separadas del documento, no adyacentes).
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
     * Analiza el PDF y devuelve un grupo por cada CUIL detectado, con las
     * páginas correspondientes y el empleado ya emparejado si existe.
     * No modifica ni crea nada — es un "dry run" para revisión manual.
     *
     * @return array{groups: array, suggested_period: ?string, total_pages: int}
     */
    public function analyze(string $absolutePath): array
    {
        $parser = new Parser();
        $document = $parser->parseFile($absolutePath);
        $pages = $document->getPages();

        // employees.cuil normalizado (solo dígitos) => employee
        $employeesByCuil = Employee::where('borrado', false)
            ->get(['id', 'first_name', 'last_name', 'cuil'])
            ->keyBy(fn ($e) => preg_replace('/\D/', '', $e->cuil));

        $pagesByCuil = []; // cuilDigits => [pageNumbers...]
        $suggestedPeriod = null;

        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1; // FPDI es 1-based
            $text = $page->getText();

            if (preg_match('/(\d{2}-?\d{7,8}-?\d{1})/', $text, $m)) {
                $cuilDigits = preg_replace('/\D/', '', $m[1]);
                $pagesByCuil[$cuilDigits][] = $pageNumber;
            }

            if (! $suggestedPeriod && preg_match('/LIQ\.?\s*CORRESPONDIENTE.{0,20}?([A-ZÑ]{4,12})\s+(\d{4})/us', $text, $pm)) {
                $monthName = self::MONTHS[$pm[1]] ?? null;
                if ($monthName) {
                    $suggestedPeriod = "{$monthName} {$pm[2]}";
                }
            }
        }

        $groups = [];
        foreach ($pagesByCuil as $cuilDigits => $pageNumbers) {
            $employee = $employeesByCuil->get($cuilDigits);
            $groups[] = [
                'cuil_detected'  => $cuilDigits,
                'cuil_formatted' => $this->formatCuil($cuilDigits),
                'pages'          => $pageNumbers,
                'page_count'     => count($pageNumbers),
                'employee_id'    => $employee?->id,
                'employee_name'  => $employee ? "{$employee->last_name}, {$employee->first_name}" : null,
                'matched'        => (bool) $employee,
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

    private function formatCuil(string $digits): string
    {
        if (strlen($digits) < 10) {
            return $digits;
        }
        return substr($digits, 0, 2) . '-' . substr($digits, 2, -1) . '-' . substr($digits, -1);
    }
}
