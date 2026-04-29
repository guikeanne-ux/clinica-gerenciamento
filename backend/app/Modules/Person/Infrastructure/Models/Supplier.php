<?php

declare(strict_types=1);

namespace App\Modules\Person\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $name_or_legal_name
 * @property string|null $document
 * @property string|null $email
 * @property string|null $phone
 * @property string $status
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
