<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Protezioni di accesso: intestazioni, limiti ai tentativi, reset password senza enumerazione.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers(): void
    {
        $this->get('/manual-login')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->get('https://localhost/manual-login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_login_is_limited_per_ip_across_different_emails(): void
    {
        // Prove su email diverse dallo stesso IP: oltre 10 al minuto si viene bloccati
        for ($i = 0; $i < 10; $i++) {
            $this->post('/manual-login', ['email' => "prova{$i}@example.com", 'password' => 'sbagliata'])->assertStatus(302);
        }
        $this->post('/manual-login', ['email' => 'altra@example.com', 'password' => 'sbagliata'])->assertStatus(429);
    }

    public function test_login_is_limited_per_email(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/manual-login', ['email' => $user->email, 'password' => 'sbagliata']);
        }

        // Anche con la password giusta, dopo 5 errori l'account è in attesa
        $this->post('/manual-login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_reset_does_not_reveal_registered_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $registered = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nessuno@example.com']);

        $registered->assertSessionHasNoErrors()->assertSessionHas('status');
        $unknown->assertSessionHasNoErrors()->assertSessionHas('status', session('status'));
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_password_reset_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => "a{$i}@example.com"])->assertStatus(302);
        }
        $this->post('/forgot-password', ['email' => 'b@example.com'])->assertStatus(429);
    }
}
