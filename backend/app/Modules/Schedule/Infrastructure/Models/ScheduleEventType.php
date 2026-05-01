<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property string|null $color
 * @property bool $requires_patient
 * @property bool $requires_professional
 * @property bool $can_generate_attendance
 * @property bool $can_generate_financial_entry
 * @property string $status
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class ScheduleEventType extends Model
{
    use SoftDeletes;

    protected $table = 'schedule_event_types';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'requires_patient' => 'boolean',
        'requires_professional' => 'boolean',
        'can_generate_attendance' => 'boolean',
        'can_generate_financial_entry' => 'boolean',
    ];
}
