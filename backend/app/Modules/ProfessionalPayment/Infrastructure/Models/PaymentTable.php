<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $name
 * @property string $status
 * @property string $calculation_type
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class PaymentTable extends Model
{
    use SoftDeletes;

    protected $table = 'payment_tables';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function defaultFixedAmount(): Attribute
    {
        return Attribute::make(
            get: static fn ($value): ?float => $value === null ? null : ((int) $value / 100),
            set: static fn ($value): ?int => $value === null || $value === '' ? null : (int) round((float) $value * 100)
        );
    }
}
