<?php

declare(strict_types=1);

namespace App\Modules\ClinicalRecord\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $patient_uuid
 * @property string $professional_uuid
 * @property string $attendance_uuid
 * @property string $record_type
 * @property string $title
 * @property string|null $content_markdown
 * @property string $status
 * @property int $version
 * @property string $created_by_user_uuid
 * @property string|null $finalized_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class ClinicalRecord extends Model
{
    use SoftDeletes;

    protected $table = 'clinical_records';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
