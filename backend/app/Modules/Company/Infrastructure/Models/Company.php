<?php

declare(strict_types=1);

namespace App\Modules\Company\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string|null $document
 * @property string|null $email
 * @property string|null $phone
 * @property string $timezone
 */
final class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
