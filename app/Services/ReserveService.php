<?php

namespace App\Services;

use App\Exceptions\RoomNotAvailableException;
use App\Models\Reserve;
use Illuminate\Support\Facades\DB;

/** Regras de negócio de reservas: disponibilidade do quarto e criação atômica. */
class ReserveService
{
    /**
     * Verifica se o quarto está livre em [checkIn, checkOut). O dia de
     * check-out de uma reserva existente não bloqueia um check-in no mesmo dia.
     * Datas no formato Y-m-d.
     */
    public function isRoomAvailable(
        int $roomId,
        string $checkIn,
        string $checkOut,
        ?int $excludeReserveId = null
    ): bool {
        $query = Reserve::query()->overlapping($roomId, $checkIn, $checkOut);

        if ($excludeReserveId !== null) {
            $query->where('id', '!=', $excludeReserveId);
        }

        return ! $query->exists();
    }

    /**
     * Cria a reserva com hóspedes, diárias e pagamentos em uma transação,
     * recusando o período se o quarto já estiver ocupado.
     *
     * Sem "total", ele é calculado pela soma das diárias.
     *
     * @param  array{
     *     hotel_id:int, room_id:int, check_in:string, check_out:string, total?:float|int|string,
     *     guests?:array<int, array{name:string,last_name?:string,phone?:string}>,
     *     dailies?:array<int, array{date:string, value:float|int|string}>,
     *     payments?:array<int, array{method:int, value:float|int|string}>
     * }  $data
     *
     * @throws RoomNotAvailableException
     */
    public function createReserve(array $data): Reserve
    {
        return DB::transaction(function () use ($data) {
            // lockForUpdate serializa a checagem de conflito entre requisições
            // concorrentes para o mesmo quarto (efetivo em MySQL/InnoDB).
            $hasConflict = Reserve::query()
                ->overlapping($data['room_id'], $data['check_in'], $data['check_out'])
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw new RoomNotAvailableException();
            }

            $dailies = $data['dailies'] ?? [];

            $reserve = Reserve::create([
                'id' => $this->nextId(),
                'hotel_id' => $data['hotel_id'],
                'room_id' => $data['room_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'total' => $data['total'] ?? collect($dailies)->sum('value'),
            ]);

            foreach ($data['guests'] ?? [] as $guest) {
                $reserve->guests()->create([
                    'name' => $guest['name'],
                    'last_name' => $guest['last_name'] ?? null,
                    'phone' => $guest['phone'] ?? null,
                ]);
            }

            foreach ($dailies as $daily) {
                $reserve->dailies()->create(['date' => $daily['date'], 'value' => $daily['value']]);
            }

            foreach ($data['payments'] ?? [] as $payment) {
                $reserve->payments()->create(['method' => $payment['method'], 'value' => $payment['value']]);
            }

            return $reserve->load('guests', 'dailies', 'payments');
        });
    }

    /**
     * A PK de reserves não é auto-incremento (reaproveita o ID do XML).
     * Reservas criadas pela API usam max(id)+1, a partir de 9.000.000 para
     * não colidir com IDs importados.
     * Limitação: duas criações simultâneas em quartos diferentes podem
     * calcular o mesmo ID.
     */
    private function nextId(): int
    {
        return max((Reserve::query()->max('id') ?? 0) + 1, 9_000_000);
    }
}
