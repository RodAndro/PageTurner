<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $module_type
 * @property string $file_name
 * @property string $file_path
 * @property string $status
 * @property int $total_rows
 * @property int $successful_rows
 * @property int $failed_rows
 * @property array|null $error_details
 * @property array|null $failure_report
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_type',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'error_details',
        'failure_report',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'error_details' => 'array',
            'failure_report' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
