<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReserveApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Hotel $hotel, Room $room, array $overrides = []): array
    {
        return array_merge([
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-12',
            'guests' => [['name' => 'Joao', 'last_name' => 'Silva', 'phone' => '5571999990000']],
            'dailies' => [
                ['date' => '2026-04-10', 'value' => 150],
                ['date' => '2026-04-11', 'value' => 150],
            ],
        ], $overrides);
    }

    public function test_it_creates_a_reserve_for_an_available_room(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $response = $this->postJson('/api/reserves', $this->payload($hotel, $room));

        $response->assertCreated()
            ->assertJsonPath('room_id', $room->id)
            ->assertJsonPath('guests.0.name', 'Joao')
            ->assertJsonPath('total', '300.00');

        $this->assertDatabaseHas('reserves', [
            'room_id' => $room->id,
            'check_in' => '2026-04-10',
            'check_out' => '2026-04-12',
        ]);
    }

    public function test_it_shows_a_reserve_with_its_details(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);
        $id = $this->postJson('/api/reserves', $this->payload($hotel, $room))->json('id');

        $this->getJson("/api/reserves/{$id}")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('room_id', $room->id)
            ->assertJsonPath('guests.0.name', 'Joao')
            ->assertJsonCount(2, 'dailies');
    }

    public function test_showing_an_unknown_reserve_returns_404(): void
    {
        $this->getJson('/api/reserves/999')->assertNotFound();
    }

    public function test_it_deletes_a_reserve_and_frees_the_room(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);
        $id = $this->postJson('/api/reserves', $this->payload($hotel, $room))->json('id');

        $this->deleteJson("/api/reserves/{$id}")->assertNoContent();

        $this->assertDatabaseMissing('reserves', ['id' => $id]);
        $this->getJson("/api/rooms/{$room->id}/availability?check_in=2026-04-10&check_out=2026-04-12")
            ->assertJsonPath('available', true);
    }

    public function test_it_rejects_a_reserve_that_overlaps_an_existing_one(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $this->postJson('/api/reserves', $this->payload($hotel, $room))->assertCreated();

        $response = $this->postJson('/api/reserves', $this->payload($hotel, $room, [
            'guests' => [['name' => 'Maria', 'last_name' => 'Souza']],
            'check_in' => '2026-04-11',
            'check_out' => '2026-04-13',
        ]));

        $response->assertStatus(409)->assertJsonStructure(['message']);
        $this->assertDatabaseCount('reserves', 1);
    }

    public function test_it_allows_a_new_reserve_right_after_the_previous_checkout(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $this->postJson('/api/reserves', $this->payload($hotel, $room))->assertCreated();

        $response = $this->postJson('/api/reserves', $this->payload($hotel, $room, [
            'guests' => [['name' => 'Maria', 'last_name' => 'Souza']],
            'check_in' => '2026-04-12',
            'check_out' => '2026-04-14',
        ]));

        $response->assertCreated();
        $this->assertDatabaseCount('reserves', 2);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/reserves', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hotel_id', 'room_id', 'check_in', 'check_out', 'guests']);
    }

    public function test_it_validates_that_checkout_is_after_checkin(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $response = $this->postJson('/api/reserves', $this->payload($hotel, $room, [
            'check_in' => '2026-04-12',
            'check_out' => '2026-04-10',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['check_out']);
    }

    public function test_availability_endpoint_reflects_existing_reserves(): void
    {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->create(['hotel_id' => $hotel->id]);

        $this->postJson('/api/reserves', $this->payload($hotel, $room))->assertCreated();

        $this->getJson("/api/rooms/{$room->id}/availability?check_in=2026-04-10&check_out=2026-04-12")
            ->assertOk()
            ->assertJsonPath('available', false);

        $this->getJson("/api/rooms/{$room->id}/availability?check_in=2026-05-01&check_out=2026-05-03")
            ->assertOk()
            ->assertJsonPath('available', true);
    }
}
