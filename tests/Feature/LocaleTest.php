<?php

namespace Tests\Feature;

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
        app()->setLocale('it');
        $this->seed(DemoSeeder::class);

        $this->actingAs(User::firstOrFail())->get('/movimenti')
            ->assertOk()
            ->assertSee('Movimenti')
            ->assertSee('Esporta CSV')
            ->assertSee('+ Nuova operazione');
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
