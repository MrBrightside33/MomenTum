<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminUserDirectoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_filter_accounts_by_search_role_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingUser = User::factory()->manager()->create([
            'name' => 'Taylor Searchable',
            'job_title' => 'Product manager',
            'last_login_at' => now(),
        ]);
        $otherUser = User::factory()->inactive()->create(['name' => 'Other Account']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'q' => 'Searchable',
                'role' => UserRole::Manager->value,
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSeeText($matchingUser->name)
            ->assertDontSeeText($otherUser->name);
    }

    public function test_directory_escapes_user_supplied_names(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_user_details_endpoint_returns_only_whitelisted_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->manager()->create([
            'name' => 'Alex Example',
            'job_title' => 'Designer',
            'bio' => 'Creates clear and accessible interfaces.',
            'last_login_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.show', $user))
            ->assertOk()
            ->assertJsonPath('data.name', 'Alex Example')
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'Manager')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.job_title', 'Designer')
            ->assertJsonPath('data.bio', 'Creates clear and accessible interfaces.');

        $this->assertSame([
            'name',
            'email',
            'role',
            'status',
            'job_title',
            'bio',
            'joined',
            'last_active',
            'assigned_tasks',
            'owned_projects',
        ], array_keys($response->json('data')));
    }

    public function test_members_cannot_open_the_user_directory_or_details_endpoint(): void
    {
        $member = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->getJson(route('admin.users.show', $target))
            ->assertForbidden();
    }

    public function test_invalid_role_filter_returns_a_validation_error(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->get(route('admin.users.index', ['role' => 'workspace-owner']))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('role');
    }
}
