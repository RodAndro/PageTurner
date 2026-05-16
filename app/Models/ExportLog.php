<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $module_type
 * @property string $export_format
 * @property string $file_name
 * @property string $file_path
 * @property string $status
 * @property int $total_records
 * @property array|null $filters
 * @property array|null $selected_columns
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 */
class ExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_type',
        'export_format',
        'file_name',
        'file_path',
        'status',
        'total_records',
        'filters',
        'selected_columns',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'filters' => 'array',
            'selected_columns' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
