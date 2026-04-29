<?php

declare(strict_types=1);

namespace App\Modules\ACL\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
