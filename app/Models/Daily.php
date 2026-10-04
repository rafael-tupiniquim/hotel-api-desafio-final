<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Daily extends Model
{
    protected $fillable = ['reserve_id', 'date', 'value'];

    // "date" fica como string Y-m-d (sem cast), pelo mesmo motivo de Reserve.
    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function reserve(): BelongsTo
    {
        return $this->belongsTo(Reserve::class);
    }
}
