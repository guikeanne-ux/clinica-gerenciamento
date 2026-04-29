<?php

declare(strict_types=1);

namespace App\Modules\Files\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $uuid
 * @property string $original_name
 * @property string $internal_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property string $checksum_hash
 * @property string $content_blob
 * @property bool $optimized
 * @property string $classification
 * @property string|null $related_module
 * @property string|null $related_entity_type
 * @property string|null $related_entity_uuid
 * @property string $uploaded_by_user_uuid
 * @property string $status
 * @property string|null $deleted_at
 * @property string|null $created_at
 */
final class FileRecord extends Model
{
    use SoftDeletes;

    protected $table = 'files';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
