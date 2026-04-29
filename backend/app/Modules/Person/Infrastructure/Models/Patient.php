<?php

declare(strict_types=1);

namespace App\Modules\Person\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $full_name
 * @property string $birth_date
 * @property string|null $cpf
 * @property string|null $email
 * @property string|null $phone_primary
 * @property string $status
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class Patient extends Model
{
    use SoftDeletes;

    protected $table = 'patients';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
