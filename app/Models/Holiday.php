<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'type', 'name', 'description', 'is_working_day',
        'applies_to_all_employees', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_working_day' => 'boolean',
            'applies_to_all_employees' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
