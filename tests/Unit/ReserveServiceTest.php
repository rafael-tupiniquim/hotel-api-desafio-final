<?php

namespace Tests\Unit;

use App\Exceptions\RoomNotAvailableException;
use App\Models\Hotel;
use App\Models\Room;
use App\Services\ReserveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReserveServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReserveService $service;
    private Hotel $hotel;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReserveService::class);
        $this->hotel = Hotel::factory()->create();
        $this->room = Room::factory()->create(['hotel_id' => $this->hotel->id]);
    }

    private function baseReserveData(array $overrides = []): array
    {
        return array_merge([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-12',
            'guests' => [['name' => 'Joao', 'last_name' => 'Silva', 'phone' => '5571999990000']],
            'dailies' => [
                ['date' => '2026-04-10', 'value' => 150],
                ['date' => '2026-04-11', 'value' => 150],
            ],
        ], $overrides);
    }

    public function test_room_is_available_when_there_are_no_reserves(): void
    {
        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-04-10', '2026-04-12')
        );
    }

    public function test_creating_a_reserve_makes_the_room_unavailable_for_the_same_period(): void
    {
        $this->service->createReserve($this->baseReserveData());

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-04-10', '2026-04-12')
        );
    }

    public function test_creating_a_reserve_makes_the_room_unavailable_for_an_overlapping_period(): void
    {
        $this->service->createReserve($this->baseReserveData());

        // Sobreposição parcial: começa antes do check-out existente e termina depois.
        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-04-11', '2026-04-15')
        );

        // Totalmente contido no período já reservado.
        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-04-10', '2026-04-11')
        );
    }

    public function test_room_is_available_right_after_checkout_date(): void
    {
        $this->service->createReserve($this->baseReserveData());

        // O dia do check-out (12/04) pode ser o check-in de outra reserva.
        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-04-12', '2026-04-14')
        );
    }

    public function test_room_is_available_right_before_checkin_date(): void
    {
        $this->service->createReserve($this->baseReserveData());

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-04-08', '2026-04-10')
        );
    }

    public function test_creating_an_overlapping_reserve_throws_exception(): void
    {
        $this->service->createReserve($this->baseReserveData());

        $this->expectException(RoomNotAvailableException::class);

        $this->service->createReserve($this->baseReserveData([
            'guests' => [['name' => 'Maria', 'last_name' => 'Souza']],
            'check_in' => '2026-04-11',
            'check_out' => '2026-04-13',
        ]));
    }

    public function test_a_different_room_is_not_affected_by_another_rooms_reserve(): void
    {
        $this->service->createReserve($this->baseReserveData());

        $otherRoom = Room::factory()->create(['hotel_id' => $this->hotel->id]);

        $this->assertTrue(
            $this->service->isRoomAvailable($otherRoom->id, '2026-04-10', '2026-04-12')
        );
    }

    public function test_total_is_calculated_from_dailies_when_not_informed(): void
    {
        $reserve = $this->service->createReserve($this->baseReserveData());

        $this->assertEquals('300.00', $reserve->total);
    }
}
