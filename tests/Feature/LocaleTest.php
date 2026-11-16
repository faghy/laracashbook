<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Interfaccia in inglese (predefinita) e in italiano.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_interface(): void
    {
        config(['app.default_locale' => 'en']);
        app()->setLocale('en');
        $this->seed(DemoSeeder::class);

        $this->actingAs(User::firstOrFail())->get('/movimenti')
            ->assertOk()
            ->assertSee('Movements')
            ->assertSee('Export CSV')
            ->assertSee('+ New movement')
            ->assertDontSee('Esporta CSV');

        $csv = $this->get('/movimenti/export')->streamedContent();
        $this->assertStringContainsString('Date,Type,Category,Description,Amount,"Foreign, VAT exempt"', $csv);
        $this->assertMatchesRegularExpression('/,Expense,.*,-\d+\.\d{2},No/', $csv);
    }

    public function test_italian_interface(): void
    {
        config(['app.default_locale' => 'it']);
        app()->setLocale('it');
        $this->seed(DemoSeeder::class);

        $this->actingAs(User::firstOrFail())->get('/movimenti')
            ->assertOk()
            ->assertSee('Movimenti')
            ->assertSee('Esporta CSV')
            ->assertSee('+ Nuova operazione');
    }

    public function test_browser_language_is_used_when_nothing_was_chosen(): void
    {
        config(['app.default_locale' => 'it']);

        $this->withHeader('Accept-Language', 'en-GB,en;q=0.9,it;q=0.5')->get('/manual-login')
            ->assertOk()->assertSee('<html lang="en">', false);
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/manual-login')
            ->assertSee('<html lang="it">', false);
    }

    public function test_browser_language_can_be_ignored(): void
    {
        config(['app.default_locale' => 'it', 'app.locale_from_browser' => false]);

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/manual-login')
            ->assertSee('<html lang="it">', false);
    }

    public function test_guest_choice_wins_over_the_browser(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9');

        $this->from('/manual-login')->post('/lingua', ['locale' => 'it'])->assertRedirect('/manual-login');
        $this->get('/manual-login')->assertSee('<html lang="it">', false)->assertSee('Accedi');

        $this->post('/lingua', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }

    public function test_user_choice_is_saved_and_used_at_next_login(): void
    {
        config(['app.default_locale' => 'it']);
        $this->seed(DemoSeeder::class);
        $user = User::where('email', 'demo@example.com')->firstOrFail();

        $this->actingAs($user)->get('/movimenti')->assertSee('Esporta CSV')->assertSee('name="locale" value="en"', false);
        $this->actingAs($user)->from('/movimenti')->post('/lingua', ['locale' => 'en'])->assertRedirect('/movimenti');
        $this->assertSame('en', $user->fresh()->locale);

        // Nuova sessione: vale la scelta salvata nel profilo, anche contro il browser
        $this->flushSession();
        $this->withHeader('Accept-Language', 'it-IT,it')->actingAs($user->fresh())->get('/movimenti')->assertSee('Export CSV');
    }

    public function test_demo_keeps_the_choice_in_the_session_and_opens_the_entity_in_that_language(): void
    {
        config(['app.default_locale' => 'it', 'app.demo' => true]);
        $this->seed(DemoSeeder::class);
        $user = User::where('email', 'demo@example.com')->firstOrFail();
        $this->assertSame(['Casa', 'Design studio', 'Home', 'Studio grafico'], Entity::orderBy('name')->pluck('name')->all());

        $this->actingAs($user)->get('/movimenti')->assertSee('Stipendio');
        $this->actingAs($user)->post('/lingua', ['locale' => 'en']);
        $this->assertNull($user->fresh()->locale); // account condiviso: non si salva
        $this->actingAs($user)->get('/movimenti')->assertSee('Export CSV')->assertSee('Salary')->assertDontSee('Stipendio');

        // Un altro visitatore con il browser in inglese apre subito l'entità inglese
        $this->flushSession();
        $this->withHeader('Accept-Language', 'en')->actingAs($user)->get('/movimenti')->assertSee('Salary');
    }

    public function test_demo_data_has_one_language_outside_the_demo(): void
    {
        config(['app.default_locale' => 'it', 'app.demo' => false]);
        $this->seed(DemoSeeder::class);

        $this->assertSame(['Casa', 'Studio grafico'], Entity::orderBy('name')->pluck('name')->all());
    }

    public function test_every_translation_key_has_an_italian_text(): void
    {
        $italian = json_decode(file_get_contents(lang_path('it.json')), true);
        $files = array_merge(
            glob(resource_path('views/**/*.blade.php')),
            glob(resource_path('views/*/*/*.blade.php')),
            glob(resource_path('views/*.blade.php')),
            glob(app_path('Http/*/*.php')),
        );

        foreach ($files as $file) {
            preg_match_all("/__\\('((?:\\\\'|[^'])*)'/", file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (! str_contains($key, '.') || str_contains($key, ' ')) {
                    $this->assertArrayHasKey($key, $italian, "Missing Italian text for \"{$key}\" ({$file})");
                }
            }
        }
    }

    public function test_euro_format_follows_the_language(): void
    {
        app()->setLocale('it');
        $this->assertSame('-1.234,50 €', AppServiceProvider::euro(-1234.5));

        app()->setLocale('en');
        $this->assertSame('-€1,234.50', AppServiceProvider::euro(-1234.5));
    }
}
