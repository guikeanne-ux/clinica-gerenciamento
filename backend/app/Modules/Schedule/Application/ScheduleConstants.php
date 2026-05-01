<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Application;

final class ScheduleConstants
{
    public const EVENT_TYPE_CATEGORIES = [
        'atendimento',
        'reuniao',
        'bloqueio',
        'ferias',
        'feriado',
        'evento_interno',
        'lembrete',
        'outro',
    ];

    public const EVENT_TYPE_STATUSES = ['ativo', 'inativo'];

    public const EVENT_STATUSES = [
        'agendado',
        'confirmado',
        'realizado',
        'cancelado',
        'falta',
        'remarcado',
        'bloqueado',
    ];

    public const EVENT_ORIGINS = [
        'manual',
        'atendimento',
        'importacao',
        'sistema',
    ];

    public const COMMITMENT_KINDS = [
        'common_event',
        'scheduled_attendance',
    ];

    public const RECURRENCE_WEEK_DAYS = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
    public const MAX_RECURRING_OCCURRENCES = 120;
}
