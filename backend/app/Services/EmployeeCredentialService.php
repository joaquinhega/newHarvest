<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Genera las credenciales de acceso al sistema (usuario, contraseña y letra
 * de chofer) a partir de los datos del legajo, replicando la convención
 * histórica usada por New Harvest/YAM:
 *
 * - DNI: el segmento del medio del CUIL (formato XX-DNI-X). Es también el
 *   `id_usuario` (PK de `users`, no autoincremental) y la contraseña inicial.
 * - Usuario: nombre(s) sin espacios/tildes + primeras letras del primer
 *   apellido (ej. "Dario Robles" -> "dariorob"). Es solo una SUGERENCIA:
 *   RRHH puede editarla a mano antes de guardar (así lo pidió Joaco, dado
 *   que la planilla real tiene alguna excepción a la regla, ej. "juanpabloa").
 * - Letra: correlativo A-Z que se asigna solo a choferes en la planilla
 *   histórica. Se sugiere la próxima libre, pero también es editable.
 */
class EmployeeCredentialService
{
    /**
     * Extrae el DNI del CUIL. Acepta con o sin guiones (20-43942223-9 o
     * 20439422239). Devuelve null si el formato no matchea.
     */
    public function extractDniFromCuil(?string $cuil): ?int
    {
        if (!$cuil) {
            return null;
        }

        if (!preg_match('/^\d{2}-?(\d{7,8})-?\d$/', trim($cuil), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Sugiere un username disponible. $excludeUserId permite reeditar el
     * legajo de un empleado sin que choque contra su propio usuario actual.
     */
    public function suggestUsername(string $firstName, string $lastName, ?int $excludeUserId = null): string
    {
        $firstNamePart = $this->onlyLetters($firstName);

        $firstSurname = trim(strtok(trim($lastName), " \t")) ?: $lastName;
        $lastNamePart = substr($this->onlyLetters($firstSurname), 0, 3);

        $base = $firstNamePart . $lastNamePart;
        if ($base === '') {
            $base = 'usuario';
        }

        $username = $base;
        $suffix = 2;

        while ($this->usernameTaken($username, $excludeUserId)) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }

    /**
     * Próxima letra A-Z libre entre los usuarios activos como chofer.
     * $currentLetter se excluye de la búsqueda (para no "robarse a sí mismo"
     * la letra al reeditar un chofer que ya la tenía asignada).
     */
    public function suggestNextLetter(?string $currentLetter = null): ?string
    {
        $used = User::query()
            ->whereNotNull('letter')
            ->pluck('letter')
            ->map(fn ($letter) => strtoupper($letter))
            ->filter(fn ($letter) => $letter !== strtoupper((string) $currentLetter))
            ->all();

        foreach (range('A', 'Z') as $letter) {
            if (!in_array($letter, $used, true)) {
                return $letter;
            }
        }

        return null;
    }

    private function onlyLetters(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z]/', '')->toString();
    }

    private function usernameTaken(string $username, ?int $excludeUserId): bool
    {
        return User::query()
            ->where('username', $username)
            ->when($excludeUserId, fn ($query) => $query->where('id_usuario', '!=', $excludeUserId))
            ->exists();
    }
}
