<?php

namespace Tests\Feature;

use App\Models\Movimento;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Installazione da zero: dati di esempio, creazione utenti e logo configurabile.
 */
class InstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_usable_account(): void
    {
        $this->seed(DemoSeeder::class);

        $user = User::where('email', 'demo@example.com')->firstOrFail();
        $this->assertCount(2, $user->entityIds());
        $this->assertGreaterThan(100, Movimento::count());
        $this->assertTrue(Movimento::where('entratauscita', 0)->where('importo', '>=', 0)->doesntExist());
        $this->assertTrue(Movimento::where('estero_senza_iva', true)->exists());

        $this->post('/manual-login', ['email' => 'demo@example.com', 'password' => 'password'])->assertRedirect();
        $this->get('/movimenti')->assertOk()->assertSee('Stipendio mensile'); // APP_LOCALE=it nei test
    }

    public function test_create_user_command(): void
    {
        $this->artisan('cashbook:create-user', ['--name' => 'Mario', '--email' => 'mario@example.com', '--entity' => ['Casa', 'Ufficio']])
            ->expectsQuestion('Password (almeno 8 caratteri)', 'segreta123')
            ->assertSuccessful();

        $user = User::where('email', 'mario@example.com')->firstOrFail();
        $this->assertCount(2, $user->entityIds());

        $this->post('/manual-login', ['email' => 'mario@example.com', 'password' => 'segreta123'])->assertRedirect();
        $this->get('/movimenti')->assertOk();
    }

    public function test_create_user_command_rejects_short_password(): void
    {
        $this->artisan('cashbook:create-user', ['--name' => 'Mario', '--email' => 'mario@example.com', '--entity' => ['Casa']])
            ->expectsQuestion('Password (almeno 8 caratteri)', 'corta')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'mario@example.com']);
    }

    public function test_logo_is_configurable(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::firstOrFail();

        // Predefinito: logo di LaraCashbook, anche nella pagina di accesso, e favicon
        $this->assertSame('/images/laracashbook-logo.png', config('cashbook.logo_url'));
        $this->assertFileExists(public_path('images/laracashbook-logo.png'));
        $this->get('/manual-login')
            ->assertSee('images/laracashbook-logo.png', false)
            ->assertSee('favicon.ico', false);

        config(['app.name' => 'Il mio libro cassa']);
        $this->actingAs($user)->get('/movimenti')
            ->assertSee('Il mio libro cassa')
            ->assertSee('images/laracashbook-logo.png', false);

        // "none": solo il nome
        config(['cashbook.logo_url' => null]);
        $this->actingAs($user)->get('/movimenti')->assertDontSee('<img class="h-8', false);

        config(['cashbook.logo_url' => '/images/logo.svg', 'cashbook.logo_alt' => 'Logo']);
        $this->actingAs($user)->get('/movimenti')
            ->assertSee('/images/logo.svg"', false);
    }
}
