<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CityNode extends Model
{
    protected $fillable = ['name', 'type'];

    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(CityEdge::class, 'from_node_id');
    }

    public function incomingEdges(): HasMany
    {
        return $this->hasMany(CityEdge::class, 'to_node_id');
    }

    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    public function hotel(): HasOne
    {
        return $this->hasOne(Hotel::class);
    }
}
