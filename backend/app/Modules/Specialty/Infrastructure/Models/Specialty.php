<?php

declare(strict_types=1);

namespace App\Modules\Specialty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $uuid
 * @property string $name
 * @property string $status
 * @property string|null $updated_at
 */
final class Specialty extends Model
{
    protected $table = 'specialties';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
