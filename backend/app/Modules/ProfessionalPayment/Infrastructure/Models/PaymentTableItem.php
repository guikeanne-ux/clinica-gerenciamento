<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $payment_table_uuid
 * @property float|null $fixed_value
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class PaymentTableItem extends Model
{
    use SoftDeletes;

    protected $table = 'payment_table_items';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
