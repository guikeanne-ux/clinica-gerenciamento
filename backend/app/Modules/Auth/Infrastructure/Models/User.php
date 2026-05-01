<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $name
 * @property string $login
 * @property string $email
 * @property string $password_hash
 * @property string $status
 * @property string|null $last_access_at
 * @property string|null $professional_uuid
 */
final class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['password_hash'];

    public function roles()
    {
        return $this->belongsToMany(
            \App\Modules\ACL\Infrastructure\Models\Role::class,
            'user_roles',
            'user_uuid',
            'role_uuid'
        );
    }
}
