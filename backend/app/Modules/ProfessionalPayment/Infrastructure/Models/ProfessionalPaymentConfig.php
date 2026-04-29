<?php

declare(strict_types=1);

namespace App\Modules\ProfessionalPayment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $professional_uuid
 * @property string $payment_mode
 * @property string|null $payment_table_uuid
 * @property float|null $fixed_monthly_amount
 * @property float|null $fixed_per_attendance_amount
 * @property float|null $hybrid_base_amount
 * @property int|null $hybrid_threshold_quantity
 * @property float|null $hybrid_extra_amount_per_attendance
 * @property string $effective_start_date
 * @property string|null $effective_end_date
 * @property string $status
 * @property string|null $updated_at
 * @property string|null $deleted_at
 */
final class ProfessionalPaymentConfig extends Model
{
    use SoftDeletes;

    protected $table = 'professional_payment_configs';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
