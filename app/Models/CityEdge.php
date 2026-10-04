<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityEdge extends Model
{
    protected $fillable = ['from_node_id', 'to_node_id', 'distance_km', 'bidirectional'];

    protected $casts = [
        'distance_km' => 'float',
        'bidirectional' => 'boolean',
    ];

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(CityNode::class, 'from_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(CityNode::class, 'to_node_id');
    }
}
