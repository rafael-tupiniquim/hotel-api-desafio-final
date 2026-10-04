<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_all_xml_files_and_persists_the_expected_data(): void
    {
        $response = $this->postJson('/api/import/all');

        $response->assertOk()
            ->assertJsonPath('imported.hotels', 3)
            ->assertJsonPath('imported.rooms', 6)
            ->assertJsonPath('imported.reserves', 6);

        $this->assertDatabaseHas('hotels', ['id' => 1, 'name' => 'Hotel Foco Prime']);
        $this->assertDatabaseHas('rooms', ['id' => 1, 'hotel_id' => 1, 'name' => 'Room 1 Hotel 1']);
        $this->assertDatabaseHas('reserves', [
            'id' => 1, 'hotel_id' => 1, 'room_id' => 1,
            'check_in' => '2022-12-01', 'check_out' => '2022-12-04', 'total' => 300.00,
        ]);
        $this->assertDatabaseHas('guests', ['name' => 'Fulaninho', 'last_name' => 'de Tal', 'phone' => '5571995959595']);
        $this->assertDatabaseHas('dailies', ['date' => '2022-12-01', 'value' => 100.00]);
        $this->assertDatabaseHas('payments', ['method' => 1, 'value' => 100.00]);
    }

    public function test_import_is_idempotent_and_does_not_duplicate_records(): void
    {
        $this->postJson('/api/import/all')->assertOk();
        $this->postJson('/api/import/all')->assertOk();

        $this->assertDatabaseCount('hotels', 3);
        $this->assertDatabaseCount('rooms', 6);
        $this->assertDatabaseCount('reserves', 6);
        // Hóspedes são recriados a cada importação, sem duplicar.
        $this->assertDatabaseCount('guests', 6);
    }

    public function test_after_importing_a_reserved_room_is_not_available_for_its_booked_period(): void
    {
        $this->postJson('/api/import/all')->assertOk();

        // O quarto 1 está reservado de 2022-12-01 a 2022-12-04 (reserva 1).
        $this->getJson('/api/rooms/1/availability?check_in=2022-12-01&check_out=2022-12-04')
            ->assertOk()
            ->assertJsonPath('available', false);
    }

    public function test_artisan_command_imports_the_same_data(): void
    {
        $this->artisan('import:xml', ['entity' => 'all'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('hotels', 3);
        $this->assertDatabaseCount('rooms', 6);
        $this->assertDatabaseCount('reserves', 6);
    }
}
