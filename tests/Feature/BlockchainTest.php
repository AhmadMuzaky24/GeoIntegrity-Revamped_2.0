<?php

namespace Tests\Feature;

use App\Models\GeoLocation;
use App\Services\EvmAnchorStore;
use App\Services\GeoLocationHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeAnchorStore;
use Tests\TestCase;

class BlockchainTest extends TestCase
{
    use RefreshDatabase;

    private FakeAnchorStore $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chain = new FakeAnchorStore;
        $this->app->instance(EvmAnchorStore::class, $this->chain);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Puskesmas A',
            'description' => 'Titik layanan kesehatan',
            'latitude' => -6.2,
            'longitude' => 106.8166667,
        ], $overrides);
    }

    public function test_editing_via_app_invalidates_credential_without_adding_a_block(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->put("/locations/{$location->id}", $this->payload(['longitude' => 110.5]))
            ->assertRedirect("/locations/{$location->id}");

        $this->assertCount(1, $this->chain->anchors, 'edit tidak boleh menambah anchor');

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['index' => 0, 'data_intact' => false]);

        $this->get("/locations/{$location->id}")
            ->assertOk()
            ->assertSee('Credential tidak dapat di-anchor ulang')
            ->assertDontSee('Anchor ulang data ini');
    }

    public function test_anchor_route_no_longer_exists(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->put("/locations/{$location->id}", $this->payload(['longitude' => 110.5]));

        $this->post("/locations/{$location->id}/anchor")->assertNotFound();

        $this->assertCount(1, $this->chain->anchors);
        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['index' => 0, 'data_intact' => false]);
    }

    public function test_creating_a_location_writes_one_anchor(): void
    {
        $this->post('/locations', $this->payload())->assertRedirect('/');

        $location = GeoLocation::sole();

        $this->assertCount(1, $this->chain->anchors);
        $this->assertSame($location->id, $this->chain->anchors[0]['location_id']);
        $this->assertSame($location->data_hash, $this->chain->anchors[0]['data_hash']);
    }

    public function test_verify_endpoint_reports_valid_chain(): void
    {
        $this->post('/locations', $this->payload());

        $this->get('/chain/verify')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'blocks' => 1,
                'data' => ['checked' => 1, 'tampered' => []],
            ]);
    }

    public function test_proof_endpoint_confirms_registered_point(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson([
                'registered' => true,
                'location_id' => $location->id,
                'index' => 0,
                'data_intact' => true,
                'chain_valid' => true,
            ]);
    }

    public function test_proof_endpoint_flags_unregistered_point(): void
    {
        $location = GeoLocation::create($this->payload());

        $this->getJson("/chain/proof/{$location->id}")
            ->assertNotFound()
            ->assertJson(['registered' => false]);
    }

    public function test_proof_endpoint_detects_tampered_location_data(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $location->update(['name' => 'Diubah diam-diam']);

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['registered' => true, 'data_intact' => false]);
    }

    public function test_every_content_column_is_covered_by_the_hash(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $mutations = [
            'name' => 'Nama palsu',
            'description' => 'Deskripsi palsu',
            'latitude' => -6.3,
            'longitude' => 106.9,
            'created_at' => '2020-01-01 00:00:00',
        ];

        foreach ($mutations as $column => $value) {
            $original = DB::table('geo_locations')->where('id', $location->id)->value($column);

            DB::table('geo_locations')->where('id', $location->id)->update([$column => $value]);

            $this->getJson("/chain/proof/{$location->id}")
                ->assertOk()
                ->assertJson(['data_intact' => false]);

            DB::table('geo_locations')->where('id', $location->id)->update([$column => $original]);
        }

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['data_intact' => true]);
    }

    public function test_tampered_data_hash_column_alone_is_detected(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        DB::table('geo_locations')
            ->where('id', $location->id)
            ->update(['data_hash' => str_repeat('f', 64)]);

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['registered' => true, 'data_intact' => false]);
    }

    public function test_verify_lists_tampered_locations(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        DB::table('geo_locations')->where('id', $location->id)->update(['name' => 'Diubah']);

        $this->get('/chain/verify')
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('data.tampered.0.id', $location->id)
            ->assertJsonPath('data.tampered.0.reason', 'hash_mismatch');
    }

    public function test_updated_at_change_alone_is_not_data_tampering(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        // ponytail: updated_at is bookkeeping, excluded on purpose — add it to
        // the hash if audit needs to prove when a row was last written.
        DB::table('geo_locations')
            ->where('id', $location->id)
            ->update(['updated_at' => '2020-01-01 00:00:00']);

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson(['data_intact' => true]);
    }

    public function test_untouched_location_hashes_identically_after_reload(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $fresh = GeoLocation::findOrFail($location->id);

        $this->assertSame($location->data_hash, app(GeoLocationHasher::class)->hash($fresh));
    }

    public function test_index_page_flags_tampered_row(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        DB::table('geo_locations')->where('id', $location->id)->update(['latitude' => -6.5]);

        $this->get('/')->assertOk()->assertSee('Hash tidak valid');
    }

    public function test_index_page_marks_registered_and_unregistered_points(): void
    {
        $this->post('/locations', $this->payload(['name' => 'Terdaftar']));
        GeoLocation::create($this->payload(['name' => 'Belum Terdaftar']));

        $this->get('/')
            ->assertOk()
            ->assertSee('Valid')
            ->assertSee('Belum terdaftar');
    }

    public function test_detail_page_shows_credential_and_integrity_state(): void
    {
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->get("/locations/{$location->id}")
            ->assertOk()
            ->assertSee('Credential Blockchain')
            ->assertSee('Hash cocok');

        $location->update(['name' => 'Diubah diam-diam']);

        $this->get("/locations/{$location->id}")
            ->assertOk()
            ->assertSee('Hash tidak valid');
    }
}
