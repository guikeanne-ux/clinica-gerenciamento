<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class FailedLoginAttempt extends Model
{
    protected $table = 'failed_login_attempts';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
