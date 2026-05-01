<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $patient_uuid
 * @property string $professional_uuid
 * @property string|null $original_professional_uuid
 * @property string|null $substituted_professional_uuid
 * @property string|null $substitution_reason
 * @property string|null $substituted_at
 * @property string|null $substituted_by_user_uuid
 * @property string|null $schedule_event_uuid
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property int|null $duration_minutes
 * @property string $status
 * @property string $attendance_type
 * @property string $modality
 * @property string|null $financial_table_snapshot_json
 * @property int|null $calculated_payout_value
 * @property string|null $internal_notes
 * @property string|null $created_by_user_uuid
 * @property string|null $updated_by_user_uuid
 * @property string|null $finalized_at
 * @property string|null $finalized_by_user_uuid
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'attendances';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
