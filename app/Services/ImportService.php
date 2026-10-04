<?php

namespace App\Services;

use App\Models\Daily;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reserve;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;

/**
 * Lê os XMLs de hotéis, quartos e reservas e grava no banco.
 *
 * É idempotente: cada registro é identificado pelo ID presente no XML
 * (updateOrCreate), então reexecutar não duplica dados.
 */
class ImportService
{
    /**
     * Importa na ordem de dependência: hotels -> rooms -> reserves.
     *
     * @return array<string, int> registros importados por entidade
     */
    public function importAll(string $hotelsPath, string $roomsPath, string $reservesPath): array
    {
        return [
            'hotels' => $this->importHotels($hotelsPath),
            'rooms' => $this->importRooms($roomsPath),
            'reserves' => $this->importReserves($reservesPath),
        ];
    }

    public function importHotels(string $path): int
    {
        $count = 0;

        foreach ($this->loadXml($path)->Hotel as $node) {
            Hotel::updateOrCreate(
                ['id' => (int) $node['id']],
                ['name' => (string) $node->Name]
            );
            $count++;
        }

        return $count;
    }

    public function importRooms(string $path): int
    {
        $count = 0;

        foreach ($this->loadXml($path)->Room as $node) {
            $this->ensureHotelExists((int) $node['hotelCode']);

            Room::updateOrCreate(
                ['id' => (int) $node['id']],
                ['hotel_id' => (int) $node['hotelCode'], 'name' => (string) $node->Name]
            );
            $count++;
        }

        return $count;
    }

    public function importReserves(string $path): int
    {
        $count = 0;

        foreach ($this->loadXml($path)->Reserve as $node) {
            DB::transaction(fn () => $this->importReserve($node));
            $count++;
        }

        return $count;
    }

    private function importReserve(SimpleXMLElement $node): void
    {
        $hotelId = (int) $node['hotelCode'];
        $roomId = (int) $node['roomCode'];

        // Permite importar reservas antes de hotéis/quartos; os nomes
        // provisórios são sobrescritos quando hotels.xml/rooms.xml forem importados.
        $this->ensureHotelExists($hotelId);
        Room::firstOrCreate(['id' => $roomId], ['hotel_id' => $hotelId, 'name' => 'Room '.$roomId]);

        $reserve = Reserve::updateOrCreate(
            ['id' => (int) $node['id']],
            [
                'hotel_id' => $hotelId,
                'room_id' => $roomId,
                'check_in' => (string) $node->CheckIn,
                'check_out' => (string) $node->CheckOut,
                'total' => (float) $node->Total,
            ]
        );

        // Hóspedes, diárias e pagamentos são recriados para refletir o XML atual.
        // isset() é necessário: o SimpleXML não devolve null para filhos
        // ausentes (Payments, por exemplo, nem sempre existe).
        $reserve->guests()->delete();
        if (isset($node->Guests->Guest)) {
            foreach ($node->Guests->Guest as $guest) {
                Guest::create([
                    'reserve_id' => $reserve->id,
                    'name' => (string) $guest->Name,
                    'last_name' => (string) $guest->LastName,
                    'phone' => (string) $guest->Phone,
                ]);
            }
        }

        $reserve->dailies()->delete();
        if (isset($node->Dailies->Daily)) {
            foreach ($node->Dailies->Daily as $daily) {
                Daily::create([
                    'reserve_id' => $reserve->id,
                    'date' => (string) $daily->Date,
                    'value' => (float) $daily->Value,
                ]);
            }
        }

        $reserve->payments()->delete();
        if (isset($node->Payments->Payment)) {
            foreach ($node->Payments->Payment as $payment) {
                Payment::create([
                    'reserve_id' => $reserve->id,
                    'method' => (int) $payment->Method,
                    'value' => (float) $payment->Value,
                ]);
            }
        }
    }

    private function ensureHotelExists(int $hotelId): void
    {
        Hotel::firstOrCreate(['id' => $hotelId], ['name' => 'Hotel '.$hotelId]);
    }

    private function loadXml(string $path): SimpleXMLElement
    {
        if (! is_file($path)) {
            throw new RuntimeException("Arquivo XML não encontrado: {$path}");
        }

        $xml = simplexml_load_string((string) file_get_contents($path));

        if ($xml === false) {
            throw new RuntimeException("XML inválido: {$path}");
        }

        return $xml;
    }
}
