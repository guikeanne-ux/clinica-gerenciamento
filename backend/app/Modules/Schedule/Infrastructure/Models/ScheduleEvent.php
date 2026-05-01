<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $title
 * @property string|null $description
 * @property string $event_type_uuid
 * @property bool $is_attendance
 * @property string|null $patient_uuid
 * @property string|null $professional_uuid
 * @property string $starts_at
 * @property string $ends_at
 * @property bool $all_day
 * @property string $status
 * @property string $origin
 * @property string|null $recurrence_rule
 * @property string|null $recurrence_group_uuid
 * @property string|null $room_or_location
 * @property string|null $color_override
 * @property string|null $created_by_user_uuid
 * @property string|null $updated_by_user_uuid
 * @property string|null $canceled_at
 * @property string|null $canceled_by_user_uuid
 * @property string|null $cancel_reason
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class ScheduleEvent extends Model
{
    use SoftDeletes;

    protected $table = 'schedule_events';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'all_day' => 'boolean',
        'is_attendance' => 'boolean',
    ];
}
