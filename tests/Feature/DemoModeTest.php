<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.demo' => true, 'app.dashboard_password' => 'demo']);
    }

    public function test_login_page_shows_demo_credentials()
    {
        $this->get('/manual-login')
            ->assertOk()
            ->assertSee('demo@example.com')
            ->assertDontSee(route('password.request'));
    }

    public function test_profile_cannot_be_changed_in_demo()
    {
        $user = User::factory()->create(['name' => 'Demo', 'email' => 'demo@example.com']);

        $this->actingAs($user)->from('/profile')
            ->patch('/profile', ['name' => 'Changed', 'email' => 'changed@example.com'])
            ->assertRedirect('/profile')
            ->assertSessionHas('error');

        $this->assertSame('demo@example.com', $user->fresh()->email);
    }

    public function test_password_cannot_be_changed_in_demo()
    {
        $user = User::factory()->create();
        $hash = $user->password;

        $this->actingAs($user)->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHas('error');

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_password_reset_link_is_blocked_in_demo()
    {
        $user = User::factory()->create();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('error');
    }

    public function test_dashboard_form_shows_demo_password()
    {
        $user = User::factory()->create();
        $entity = Entity::create(['name' => 'Casa']);
        UserEntity::create(['user_id' => $user->id, 'entity_id' => $entity->id]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('Demo password'))
            ->assertSee('<code>demo</code>', false);
    }
}
