<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
