<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'name', 'city_node_id'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reserves(): HasMany
    {
        return $this->hasMany(Reserve::class);
    }

    public function cityNode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CityNode::class);
    }
}
