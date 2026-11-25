<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemBackupRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_name',
        'file_path',
        'file_size_bytes',
        'status',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
