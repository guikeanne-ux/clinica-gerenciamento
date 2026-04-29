<?php

declare(strict_types=1);

namespace App\Modules\Person\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $name
 * @property string|null $cpf
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class PatientResponsible extends Model
{
    use SoftDeletes;

    protected $table = 'patient_responsibles';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
