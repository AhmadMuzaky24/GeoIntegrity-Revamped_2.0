<?php

namespace Tests\Feature;

use App\Models\GeoLocation;
use App\Models\User;
use App\Services\EvmAnchorStore;
use App\Support\Actor;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeAnchorStore;
use Tests\TestCase;

/**
 * Demo script: A creates a point, B edits it, C detects the unilateral change.
 */
class DemoTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chain = new FakeAnchorStore;
        $this->app->instance(EvmAnchorStore::class, $this->chain);

        $this->seed(DemoUsersSeeder::class);
    }

    private FakeAnchorStore $chain;

    private function actAs(string $suffix): void
    {
        $user = User::where('email', strtolower($suffix).'@demo.test')->firstOrFail();

        $this->withSession([Actor::SESSION_KEY => $user->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Titik Demo',
            'description' => 'Anchor buatan User A',
            'latitude' => -6.2,
            'longitude' => 106.8166667,
        ], $overrides);
    }

    public function test_user_a_creates_anchor_and_is_recorded_as_owner(): void
    {
        $this->actAs('A');

        $this->post('/locations', $this->payload())->assertRedirect('/');

        $location = GeoLocation::sole();
        $anchor = $this->chain->anchors[0];

        $this->assertSame('User A', $location->created_by);
        $this->assertSame('User A', $location->updated_by);
        $this->assertSame('User A', $anchor['actor']);

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson([
                'created_by' => 'User A',
                'updated_by' => 'User A',
                'anchored_by' => 'User A',
                'changed_by_other' => false,
                'data_intact' => true,
            ]);
    }

    public function test_user_b_edit_is_flagged_as_foreign_change_for_user_c(): void
    {
        $this->actAs('A');
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->actAs('B');
        $this->put("/locations/{$location->id}", $this->payload(['longitude' => 111.1]))
            ->assertRedirect("/locations/{$location->id}");

        $fresh = $location->fresh();

        $this->assertSame('User A', $fresh->created_by, 'pembuat tidak boleh berubah');
        $this->assertSame('User B', $fresh->updated_by);
        $this->assertCount(1, $this->chain->anchors, 'edit B tidak boleh menambah anchor');

        $this->actAs('C');

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson([
                'created_by' => 'User A',
                'updated_by' => 'User B',
                'anchored_by' => 'User A',
                'changed_by_other' => true,
                'data_intact' => false,
            ]);

        $this->get("/locations/{$location->id}")
            ->assertOk()
            ->assertSee('Diubah oleh pihak lain')
            ->assertSee('Hash tidak valid');

        $this->get('/')
            ->assertOk()
            ->assertSee('diubah User B')
            ->assertSee('Hash tidak valid');

        $this->get('/chain')
            ->assertOk()
            ->assertSee('User A');
    }

    public function test_user_b_cannot_reanchor_the_edited_point(): void
    {
        $this->actAs('A');
        $this->post('/locations', $this->payload());
        $location = GeoLocation::sole();

        $this->actAs('B');
        $this->put("/locations/{$location->id}", $this->payload(['longitude' => 111.1]));

        $this->post("/locations/{$location->id}/anchor")->assertNotFound();

        $this->assertCount(1, $this->chain->anchors);
        $this->assertSame('User A', $location->fresh()->created_by);
        $this->assertSame('User B', $location->fresh()->updated_by);

        $this->actAs('C');

        $this->getJson("/chain/proof/{$location->id}")
            ->assertOk()
            ->assertJson([
                'index' => 0,
                'anchored_by' => 'User A',
                'changed_by_other' => true,
                'data_intact' => false,
            ]);
    }

    public function test_actor_switch_route_changes_who_is_credited(): void
    {
        $userB = User::where('email', 'b@demo.test')->firstOrFail();

        $this->get("/actor/{$userB->id}")->assertRedirect();

        $this->post('/locations', $this->payload());

        $this->assertSame('User B', GeoLocation::sole()->created_by);
        $this->assertSame('User B', $this->chain->anchors[0]['actor']);
    }
}
