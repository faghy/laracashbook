<?php

namespace Tests\Feature;

use App\Models\Cat;
use App\Models\Entity;
use App\Models\Movimento;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtri, export CSV, cestino e riepiloghi della dashboard.
 */
class MovimentiFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entity $entity;

    private Cat $spesa;

    private Cat $stipendio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'responsabile']);
        $this->entity = Entity::create(['name' => 'Mia Azienda']);
        UserEntity::create(['user_id' => $this->user->id, 'entity_id' => $this->entity->id]);

        $this->spesa = $this->makeCat('Spesa');
        $this->stipendio = $this->makeCat('Stipendio');

        $this->makeMov('Supermercato', 0, 120.50, $this->spesa, '2026-03-10');
        $this->makeMov('Mercato rionale', 0, 30, $this->spesa, '2026-04-02');
        $this->makeMov('Stipendio marzo', 1, 1500, $this->stipendio, '2026-03-27');
    }

    private function makeCat(string $name): Cat
    {
        $cat = new Cat(['descriz_cat' => $name]);
        $cat->entity_id = $this->entity->id;
        $cat->save();

        return $cat;
    }

    private function makeMov(string $descr, int $tipo, float $importo, Cat $cat, string $data): Movimento
    {
        return Movimento::create([
            'user_id' => $this->user->id,
            'entity_id' => $this->entity->id,
            'entratauscita' => $tipo,
            'categoria' => $cat->cat_id,
            'descrizione' => $descr,
            'importo' => $importo,
            'data_movimento' => $data,
        ]);
    }

    public function test_list_filters(): void
    {
        // Testo
        $this->actingAs($this->user)->get('/movimenti?q=mercato')
            ->assertOk()
            ->assertSee('Supermercato')->assertSee('Mercato rionale')->assertDontSee('Stipendio marzo')
            ->assertDontSee('Totale');

        // Periodo e tipo
        $this->actingAs($this->user)->get('/movimenti?dal=2026-03-01&al=2026-03-31&tipo=0')
            ->assertOk()
            ->assertSee('Supermercato')->assertDontSee('Mercato rionale')->assertDontSee('Stipendio marzo')
            ->assertSee('-120,50 €');

        // Categoria
        $this->actingAs($this->user)->get("/movimenti?categoria={$this->stipendio->cat_id}")
            ->assertOk()
            ->assertSee('Stipendio marzo')->assertDontSee('Supermercato')
            ->assertSee('1.500,00 €');

        // I caratteri jolly di LIKE vengono cercati come testo
        $this->actingAs($this->user)->get('/movimenti?q=%25')
            ->assertOk()
            ->assertSee('Nessun movimento trovato.');
    }

    public function test_today_shortcut_redirects_to_filtered_list(): void
    {
        $today = now()->toDateString();

        $this->actingAs($this->user)->get('/movimenti/oggi')
            ->assertRedirect(route('movimenti.index', ['dal' => $today, 'al' => $today]));
    }

    public function test_single_day_shows_day_totals(): void
    {
        $today = now()->toDateString();
        $this->makeMov('Incasso oggi', 1, 200, $this->stipendio, $today);
        $this->makeMov('Pranzo oggi', 0, 45.5, $this->spesa, $today);

        $this->actingAs($this->user)->get("/movimenti?dal={$today}&al={$today}")
            ->assertOk()
            ->assertSee('Saldo della giornata')
            ->assertSeeInOrder(['Oggi', 'Entrate', '200,00 €', 'Uscite', '45,50 €', 'Saldo della giornata', '154,50 €']);

        // Su un periodo di più giorni i totali non compaiono
        $this->actingAs($this->user)->get('/movimenti?dal=2026-03-01&al=2026-03-31')
            ->assertOk()
            ->assertDontSee('Saldo della giornata');
    }

    public function test_dashboard_shows_separate_estero_total(): void
    {
        $this->makeMov('Server Hetzner', 0, 40, $this->spesa, '2026-03-10')->update(['estero_senza_iva' => true]);
        $this->makeMov('Dominio Ionos', 0, 12.5, $this->spesa, '2026-03-12')->update(['estero_senza_iva' => true]);

        $this->actingAs($this->user)
            ->withSession(['password_verified' => true])
            ->get('/dashboard?start_year=2026&end_year=2026')
            ->assertOk()
            ->assertSeeInOrder(['Movimenti esteri senza IVA', '2 movimenti', 'Entrate', '0,00 €', 'Uscite', '52,50 €', 'Saldo', '-52,50 €']);
    }

    public function test_category_presets_estero_flag_on_new_movements(): void
    {
        // "Hosting estero" viene prima di "Spesa" e "Stipendio", quindi è la categoria proposta
        $this->actingAs($this->user)->withSession(['selected_entity_id' => $this->entity->id])
            ->post('/categoria', ['descriz_cat' => 'Hosting estero', 'estero_senza_iva' => '1'])
            ->assertRedirect(route('categoria.index'));
        $hosting = Cat::where('descriz_cat', 'Hosting estero')->firstOrFail();
        $this->assertTrue($hosting->estero_senza_iva);

        $this->actingAs($this->user)->get('/categoria')->assertSee('Estero s/IVA');

        $html = $this->actingAs($this->user)->get(route('movimenti.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="estero_senza_iva"[^>]*checked/', $html);
        $this->assertStringContainsString('data-estero="1"', $html);

        // Con una categoria senza impostazione il flag parte spento
        $this->actingAs($this->user)->patch("/categoria/{$hosting->cat_id}", ['descriz' => 'Hosting estero'])
            ->assertRedirect(route('categoria.index'));
        $this->assertFalse($hosting->fresh()->estero_senza_iva);

        $html = $this->actingAs($this->user)->get(route('movimenti.create'))->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="estero_senza_iva"[^>]*checked/', $html);

        // In modifica conta solo il valore del movimento
        $this->actingAs($this->user)->patch("/categoria/{$hosting->cat_id}", ['descriz' => 'Hosting estero', 'estero_senza_iva' => '1']);
        $mov = $this->makeMov('Server', 0, 10, $hosting, '2026-03-01');
        $html = $this->actingAs($this->user)->get(route('movimenti.edit', $mov->id))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="estero_senza_iva"[^>]*checked/', $html);
    }

    public function test_category_presets_type_on_new_movements(): void
    {
        // "Affitto" viene prima di "Spesa" e "Stipendio", quindi è la categoria proposta
        $this->actingAs($this->user)->withSession(['selected_entity_id' => $this->entity->id])
            ->post('/categoria', ['descriz_cat' => 'Affitto', 'tipo_predefinito' => '0'])
            ->assertRedirect(route('categoria.index'));
        $affitto = Cat::where('descriz_cat', 'Affitto')->firstOrFail();
        $this->assertSame(0, $affitto->tipo_predefinito);

        $this->actingAs($this->user)->get('/categoria')->assertSeeInOrder(['Affitto', 'Uscita']);

        $html = $this->actingAs($this->user)->get(route('movimenti.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="OUT"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="IN"[^>]*checked/', $html);
        $this->assertStringContainsString('data-tipo="0"', $html);

        // Entrata predefinita
        $this->actingAs($this->user)->patch("/categoria/{$affitto->cat_id}", ['descriz' => 'Affitto', 'tipo_predefinito' => '1']);
        $this->assertSame(1, $affitto->fresh()->tipo_predefinito);
        $html = $this->actingAs($this->user)->get(route('movimenti.create'))->getContent();
        $this->assertMatchesRegularExpression('/id="IN"[^>]*checked/', $html);

        // Nessuna impostazione: si torna a "Entrata" come prima
        $this->actingAs($this->user)->patch("/categoria/{$affitto->cat_id}", ['descriz' => 'Affitto', 'tipo_predefinito' => '']);
        $this->assertNull($affitto->fresh()->tipo_predefinito);

        // Valori non validi rifiutati
        $this->actingAs($this->user)->patch("/categoria/{$affitto->cat_id}", ['descriz' => 'Affitto', 'tipo_predefinito' => '5'])
            ->assertSessionHasErrors('tipo_predefinito');

        // In modifica conta solo il tipo del movimento
        $this->actingAs($this->user)->patch("/categoria/{$affitto->cat_id}", ['descriz' => 'Affitto', 'tipo_predefinito' => '0']);
        $mov = $this->makeMov('Rimborso', 1, 10, $affitto, '2026-03-01');
        $html = $this->actingAs($this->user)->get(route('movimenti.edit', $mov->id))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="IN"[^>]*checked/', $html);
    }

    public function test_migration_sets_expense_on_existing_estero_categories(): void
    {
        $migration = require database_path('migrations/2026_11_08_000000_add_tipo_predefinito_to_categorie_table.php');
        $migration->down();
        $this->spesa->forceFill(['estero_senza_iva' => true])->save();

        $migration->up();

        $this->assertSame(0, $this->spesa->fresh()->tipo_predefinito);
        $this->assertNull($this->stipendio->fresh()->tipo_predefinito);
    }

    public function test_export_csv_respects_filters(): void
    {
        $response = $this->actingAs($this->user)->get('/movimenti/export?dal=2026-03-01&al=2026-03-31');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('movimenti-mia-azienda-', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Data;Tipo;Categoria;Descrizione;Importo;"Estero senza IVA"', $csv);
        $this->assertStringContainsString('10/03/2026;Uscita;Spesa;Supermercato;-120,50;No', $csv);
        $this->assertStringContainsString('27/03/2026;Entrata;Stipendio;"Stipendio marzo";1500,00;No', $csv);
        $this->assertStringNotContainsString('Mercato rionale', $csv);
    }

    public function test_trash_restore_and_force_delete(): void
    {
        $mov = Movimento::where('descrizione', 'Supermercato')->first();

        $this->actingAs($this->user)->delete("/movimenti/{$mov->id}")->assertRedirect();
        $this->assertSoftDeleted($mov);

        $this->actingAs($this->user)->get('/movimenti')->assertDontSee('Supermercato');
        $this->actingAs($this->user)->get('/movimenti/cestino')->assertOk()->assertSee('Supermercato');

        // Una categoria con movimenti nel cestino non si può eliminare
        $this->actingAs($this->user)->delete("/categoria/{$this->spesa->cat_id}");
        $this->assertDatabaseHas('categorie', ['cat_id' => $this->spesa->cat_id]);

        $this->actingAs($this->user)->patch("/movimenti/{$mov->id}/ripristina")->assertRedirect(route('movimenti.cestino'));
        $this->assertNotSoftDeleted($mov);

        $mov->delete();
        $this->actingAs($this->user)->delete("/movimenti/{$mov->id}/elimina-definitivamente")->assertRedirect(route('movimenti.cestino'));
        $this->assertDatabaseMissing('movimenti', ['id' => $mov->id]);
    }

    public function test_edit_form_shows_movement_data(): void
    {
        $mov = Movimento::where('descrizione', 'Supermercato')->first();

        $this->actingAs($this->user)->get("/movimenti/{$mov->id}/edit")
            ->assertOk()
            ->assertSee('value="2026-03-10"', false)
            ->assertSee('value="120.50"', false);

        $this->actingAs($this->user)->patch("/movimenti/{$mov->id}", [
            'entratauscita' => 0, 'categoria' => $this->spesa->cat_id,
            'descrizione' => 'Supermercato', 'importo' => 99, 'data' => '2026-03-11',
        ])->assertRedirect(route('movimenti.index'));

        $this->assertDatabaseHas('movimenti', ['id' => $mov->id, 'importo' => -99, 'data_movimento' => '2026-03-11']);
    }

    public function test_estero_senza_iva_flag(): void
    {
        $data = ['entratauscita' => 0, 'categoria' => $this->spesa->cat_id, 'descrizione' => 'Licenza software USA', 'importo' => 49, 'data' => '2026-04-10'];

        // Inserimento con la casella spuntata
        $this->actingAs($this->user)->post('/movimenti', $data + ['estero_senza_iva' => '1'])->assertRedirect(route('movimenti.index'));
        $mov = Movimento::where('descrizione', 'Licenza software USA')->firstOrFail();
        $this->assertTrue($mov->estero_senza_iva);

        // Il form di modifica la mostra spuntata
        $this->actingAs($this->user)->get("/movimenti/{$mov->id}/edit")
            ->assertOk()->assertSee('Movimento estero senza IVA');

        // Elenco: badge e filtro
        $this->actingAs($this->user)->get('/movimenti?estero=1')
            ->assertOk()->assertSee('Licenza software USA')->assertSee('Estero s/IVA')->assertDontSee('Supermercato');

        // Export
        $csv = $this->actingAs($this->user)->get('/movimenti/export?estero=1')->streamedContent();
        $this->assertStringContainsString('10/04/2026;Uscita;Spesa;"Licenza software USA";-49,00;Sì', $csv);

        // In modifica, senza la casella (arriva solo il campo nascosto) il flag si toglie
        $this->actingAs($this->user)->patch("/movimenti/{$mov->id}", $data + ['estero_senza_iva' => '0'])->assertRedirect();
        $this->assertFalse($mov->fresh()->estero_senza_iva);

        // I movimenti esistenti non sono esteri per default
        $this->assertFalse(Movimento::where('descrizione', 'Supermercato')->first()->estero_senza_iva);
    }

    public function test_dashboard_monthly_and_category_summary(): void
    {
        $this->actingAs($this->user)
            ->withSession(['password_verified' => true])
            ->get('/dashboard?start_year=2026&end_year=2026&start_month=3&end_month=4')
            ->assertOk()
            ->assertSee('Andamento mensile')
            ->assertSeeInOrder(['Mar 2026', '1.500,00 €', '120,50 €', 'Apr 2026', '30,00 €'])
            ->assertSee('Riepilogo per categoria')
            ->assertSeeInOrder(['Stipendio', '1.500,00 €', 'Spesa', '150,50 €'])
            ->assertSee('1.349,50 €');
    }

    public function test_pages_render_on_unified_layout(): void
    {
        foreach (['/movimenti', '/movimenti/create', '/movimenti/cestino', '/categoria', '/categoria/create', '/profile'] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk()->assertSee('Cestino');
        }

        $this->actingAs($this->user)->get("/categoria/{$this->spesa->cat_id}/edit")->assertOk();
        $this->actingAs($this->user)->get('/dashboard')->assertOk()->assertSee('Inserisci la password');
    }
}
