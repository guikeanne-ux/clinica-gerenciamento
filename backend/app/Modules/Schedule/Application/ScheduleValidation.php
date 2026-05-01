<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

use App\Core\Exceptions\ValidationException;
use Illuminate\Database\Capsule\Manager as DB;

final class ScheduleValidation
{
    public static function assertRequiredString(array $data, string $field, string $message): string
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value === '') {
            throw new ValidationException('Erro de validação.', [['field' => $field, 'message' => $message]]);
        }

        return $value;
    }

    public static function assertIn(string $value, array $allowed, string $field, string $message): void
    {
        if (! in_array($value, $allowed, true)) {
            throw new ValidationException('Erro de validação.', [['field' => $field, 'message' => $message]]);
        }
    }

    public static function assertHexColor(?string $value, string $field = 'color'): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            throw new ValidationException('Erro de validação.', [[
                'field' => $field,
                'message' => 'Cor inválida. Use hexadecimal no formato #RRGGBB.',
            ]]);
        }
    }

    public static function assertUuidExists(string $table, string $uuid, string $field, string $message): void
    {
        $exists = DB::table($table)
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->exists();

        if (! $exists) {
            throw new ValidationException('Erro de validação.', [['field' => $field, 'message' => $message]]);
        }
    }

    public static function assertDateRange(string $startsAt, string $endsAt): void
    {
        $startTs = strtotime($startsAt);
        $endTs = strtotime($endsAt);

        if ($startTs === false) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'starts_at',
                'message' => 'Data/hora inicial inválida.',
            ]]);
        }

        if ($endTs === false) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'ends_at',
                'message' => 'Data/hora final inválida.',
            ]]);
        }

        if ($endTs <= $startTs) {
            throw new ValidationException('Erro de validação.', [[
                'field' => 'ends_at',
                'message' => 'Data/hora final deve ser posterior a data/hora inicial.',
            ]]);
        }
    }

    public static function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'sim', 'yes'], true);
        }

        return false;
    }
}
