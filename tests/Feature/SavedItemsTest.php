<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_save_and_remove_an_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/favorites/{$opportunity->slug}")
            ->assertOk();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'opportunity_id' => $opportunity->id,
        ]);

        $this->actingAs($user)->getJson('/api/v1/favorites')
            ->assertOk()
            ->assertJsonPath('data.0.id', $opportunity->id)
            ->assertJsonPath('data.0.is_favorite', true);

        $this->actingAs($user)->deleteJson("/api/v1/favorites/{$opportunity->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'opportunity_id' => $opportunity->id]);
    }

    public function test_verified_user_can_store_list_and_delete_a_search(): void
    {
        $user = User::factory()->create();
        $payload = [
            'name' => 'فرصت‌های برلین',
            'filters' => [
                'q' => 'Pflege',
                'category' => 'pflege',
                'city' => 'Berlin',
                'german_level' => 'b1',
                'international' => true,
                'sort' => 'latest',
            ],
        ];

        $id = $this->actingAs($user)->postJson('/api/v1/saved-searches', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'فرصت‌های برلین')
            ->json('data.id');

        $this->actingAs($user)->getJson('/api/v1/saved-searches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.filters.city', 'Berlin');

        $this->actingAs($user)->deleteJson("/api/v1/saved-searches/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('saved_searches', ['id' => $id]);
    }

    public function test_user_cannot_delete_another_users_saved_search(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $search = $owner->savedSearches()->create([
            'name' => 'خصوصی',
            'filters' => ['international' => false, 'sort' => 'latest'],
        ]);

        $this->actingAs($other)->deleteJson("/api/v1/saved-searches/{$search->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }

    public function test_guest_and_unverified_user_cannot_access_saved_items(): void
    {
        $this->getJson('/api/v1/saved-searches')->assertUnauthorized();

        $this->actingAs(User::factory()->unverified()->create())
            ->getJson('/api/v1/saved-searches')
            ->assertForbidden();
    }
}
