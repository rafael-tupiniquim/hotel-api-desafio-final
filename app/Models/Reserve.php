<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserve extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'hotel_id', 'room_id', 'check_in', 'check_out', 'total'];

    // check_in/check_out não usam o cast "date" de propósito: o Eloquent o
    // converteria para "Y-m-d 00:00:00" ao gravar. Em bancos sem tipo DATE
    // nativo (SQLite) isso quebra a comparação por string usada em
    // scopeOverlapping() e faz o dia de check-out bloquear um novo check-in.
    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function dailies(): HasMany
    {
        return $this->hasMany(Daily::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Reservas do quarto que se sobrepõem ao período [checkIn, checkOut).
     * O intervalo é semiaberto: o dia de check-out não conflita com um
     * check-in no mesmo dia. Datas devem estar no formato Y-m-d.
     */
    public function scopeOverlapping($query, int $roomId, string $checkIn, string $checkOut)
    {
        return $query->where('room_id', $roomId)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }
}
