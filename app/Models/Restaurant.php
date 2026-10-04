<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restaurant extends Model
{
    protected $fillable = ['city_node_id', 'name', 'cuisine', 'rating', 'price_range'];

    protected $casts = [
        'rating' => 'float',
    ];

    public function cityNode(): BelongsTo
    {
        return $this->belongsTo(CityNode::class);
    }
}
