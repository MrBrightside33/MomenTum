<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_redirects_guests_to_login(): void
    {
        $response = $this->get(route('settings'));

        $response->assertRedirectToRoute('login');
    }

    public function test_settings_page_renders_the_authenticated_users_saved_values(): void
    {
        $user = User::factory()->create([
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
            'job_title' => 'Product designer',
            'bio' => 'I make useful things.',
            'settings' => [
                'workspace_name' => 'Rivera Studio',
                'appearance' => 'dark',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('settings'));

        $response
            ->assertOk()
            ->assertSee('Manage your account, workspace preferences, and application settings.')
            ->assertSee('Jamie Rivera')
            ->assertSee('jamie@example.com')
            ->assertSee('Product designer')
            ->assertSee('I make useful things.')
            ->assertSee('Rivera Studio')
            ->assertSee('data-theme="dark"', false)
            ->assertSee('data-theme-toggle-form', false)
            ->assertSee('title="Toggle dark mode"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_profile_changes_are_persisted_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
            'job_title' => 'Product designer',
            'bio' => 'I make useful things.',
        ]);

        $response->assertRedirectToRoute('settings');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
            'job_title' => 'Product designer',
            'bio' => 'I make useful things.',
        ]);
        $auditLog = AuditLog::query()->where('action', 'user.profile_updated')->firstOrFail();
        $this->assertSame(['name', 'email', 'job_title', 'bio'], $auditLog->metadata['changed_fields']);
    }

    public function test_profile_changes_reject_an_email_owned_by_another_user(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->from(route('settings'))
            ->put(route('settings.profile.update'), [
                'name' => 'Updated Name',
                'email' => 'taken@example.com',
                'job_title' => '',
                'bio' => '',
            ]);

        $response
            ->assertRedirectToRoute('settings')
            ->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'owner@example.com',
        ]);
    }

    public function test_profile_update_ignores_unvalidated_account_attributes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Other Account']);
        $originalPassword = $user->password;

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'job_title' => '',
            'bio' => '',
            'id' => $otherUser->id,
            'password' => 'attacker-controlled-password',
            'settings' => ['appearance' => 'dark'],
        ]);

        $response->assertRedirectToRoute('settings');
        $this->assertSame($user->id, $user->fresh()->id);
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertNull($user->fresh()->settings);
        $this->assertSame('Other Account', $otherUser->fresh()->name);
    }

    public function test_profile_bio_is_escaped_when_rendered(): void
    {
        $user = User::factory()->create([
            'bio' => '<script>alert("unsafe")</script>',
        ]);

        $response = $this->actingAs($user)->get(route('settings'));

        $response
            ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_uploaded_profile_photo_is_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $image = UploadedFile::fake()->createWithContent(
            'profile.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aYuoAAAAASUVORK5CYII='),
        );

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'job_title' => '',
            'bio' => '',
            'avatar' => $image,
        ]);

        $response->assertRedirectToRoute('settings');
        $this->assertNotNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_profile_photo_rejects_an_unsupported_file_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $image = UploadedFile::fake()->createWithContent('profile.svg', '<svg><script>alert(1)</script></svg>');

        $response = $this->actingAs($user)->from(route('settings'))
            ->put(route('settings.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => '',
                'bio' => '',
                'avatar' => $image,
            ]);

        $response
            ->assertRedirectToRoute('settings')
            ->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles("avatars/{$user->id}"));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();
        $oldPasswordHash = $user->password;

        $response = $this->actingAs($user)->from(route('settings'))
            ->put(route('settings.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'another-strong-password',
                'password_confirmation' => 'another-strong-password',
            ]);

        $response
            ->assertRedirectToRoute('settings')
            ->assertSessionHasErrors('current_password');
        $this->assertSame($oldPasswordHash, $user->fresh()->password);
    }

    public function test_password_change_persists_the_new_password_and_keeps_the_current_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'password',
            'password' => 'another-strong-password',
            'password_confirmation' => 'another-strong-password',
        ]);

        $response->assertRedirectToRoute('settings');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('another-strong-password', $user->fresh()->password));
        $auditLog = AuditLog::query()->where('action', 'user.password_changed')->firstOrFail();
        $this->assertSame(['password'], $auditLog->metadata['changed_fields']);
        $this->assertStringNotContainsString('another-strong-password', $auditLog->description);
    }

    public function test_preference_changes_are_persisted_and_used_for_the_default_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.preferences.update'), [
            'workspace_name' => 'My workspace',
            'default_page' => 'dashboard',
            'default_task_view' => 'calendar',
            'timezone' => 'Asia/Singapore',
            'appearance' => 'dark',
            'notifications' => [
                'task_assignments' => '1',
                'task_status_updates' => '0',
                'project_updates' => '1',
                'due_date_reminders' => '0',
                'team_activity' => '1',
            ],
        ]);

        $response->assertRedirectToRoute('settings');
        $settings = $user->fresh()->settings;
        $this->assertSame('My workspace', $settings['workspace_name']);
        $this->assertSame('Asia/Singapore', $settings['timezone']);
        $this->assertSame('dark', $settings['appearance']);
        $this->assertFalse($settings['notifications']['task_status_updates']);
        $this->assertSame('calendar', $user->fresh()->defaultPageRoute());
        $auditLog = AuditLog::query()->where('action', 'user.preferences_updated')->firstOrFail();
        $this->assertContains('timezone', $auditLog->metadata['changed_fields']);
        $this->assertContains('theme', $auditLog->metadata['changed_fields']);
        $this->assertContains('default_task_view', $auditLog->metadata['changed_fields']);
        $this->assertContains('notifications.task_status_updates', $auditLog->metadata['changed_fields']);
        $this->get(route('home'))->assertRedirectToRoute('calendar');
    }

    public function test_preference_changes_reject_unrecognized_notification_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('settings'))
            ->put(route('settings.preferences.update'), [
                'workspace_name' => 'My workspace',
                'default_page' => 'dashboard',
                'default_task_view' => 'list',
                'timezone' => 'UTC',
                'appearance' => 'system',
                'notifications' => [
                    'task_assignments' => '1',
                    'task_status_updates' => '1',
                    'project_updates' => '1',
                    'due_date_reminders' => '1',
                    'team_activity' => '1',
                    'unexpected' => '1',
                ],
            ]);

        $response
            ->assertRedirectToRoute('settings')
            ->assertSessionHasErrors('notifications');
        $this->assertNull($user->fresh()->settings);
    }

    public function test_theme_toggle_persists_appearance_for_only_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'settings' => ['workspace_name' => 'My workspace', 'appearance' => 'system'],
        ]);
        $otherUser = User::factory()->create([
            'settings' => ['appearance' => 'light'],
        ]);

        $response = $this->actingAs($user)->putJson(route('settings.appearance.update'), [
            'appearance' => 'dark',
        ]);

        $response->assertOk()->assertExactJson(['appearance' => 'dark']);
        $this->assertSame([
            'workspace_name' => 'My workspace',
            'appearance' => 'dark',
        ], $user->fresh()->settings);
        $this->assertSame(['appearance' => 'light'], $otherUser->fresh()->settings);
    }

    public function test_theme_toggle_rejects_unsupported_appearance_values(): void
    {
        $user = User::factory()->create([
            'settings' => ['appearance' => 'system'],
        ]);

        $response = $this->actingAs($user)->putJson(route('settings.appearance.update'), [
            'appearance' => 'system',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('appearance');
        $this->assertSame(['appearance' => 'system'], $user->fresh()->settings);
    }

    public function test_theme_toggle_requires_authentication(): void
    {
        $response = $this->put(route('settings.appearance.update'), [
            'appearance' => 'dark',
        ]);

        $response->assertRedirectToRoute('login');
    }
}
