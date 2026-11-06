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
 * Verifica che un utente possa vedere e modificare solo i dati delle entità a cui è associato.
 */
class EntityIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entity $mine;

    private Entity $other;

    private Cat $myCat;

    private Cat $otherCat;

    private Movimento $otherMov;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->mine = Entity::create(['name' => 'Mia']);
        $this->other = Entity::create(['name' => 'Altra']);
        UserEntity::create(['user_id' => $this->user->id, 'entity_id' => $this->mine->id]);

        $this->myCat = $this->makeCat('Spesa', $this->mine);
        $this->otherCat = $this->makeCat('Segreta', $this->other);

        $owner = User::factory()->create();
        $this->otherMov = Movimento::create([
            'user_id' => $owner->id,
            'entity_id' => $this->other->id,
            'entratauscita' => 1,
            'categoria' => $this->otherCat->cat_id,
            'descrizione' => 'Movimento altra azienda',
            'importo' => 999,
        ]);
    }

    private function makeCat(string $name, Entity $entity): Cat
    {
        $cat = new Cat(['descriz_cat' => $name]);
        $cat->entity_id = $entity->id;
        $cat->save();

        return $cat;
    }

    public function test_cannot_select_an_entity_not_associated(): void
    {
        $this->actingAs($this->user)
            ->post('/seleziona-entita', ['entity' => $this->other->id])
            ->assertForbidden();
    }

    public function test_foreign_entity_in_session_is_replaced(): void
    {
        $this->actingAs($this->user)
            ->withSession(['selected_entity_id' => $this->other->id])
            ->get('/movimenti')
            ->assertOk()
            ->assertDontSee('Movimento altra azienda')
            ->assertSessionHas('selected_entity_id', $this->mine->id);
    }

    public function test_cannot_touch_movements_of_other_entity(): void
    {
        $this->actingAs($this->user)->get("/movimenti/{$this->otherMov->id}/edit")->assertForbidden();
        $this->actingAs($this->user)->delete("/movimenti/{$this->otherMov->id}")->assertForbidden();
        $this->otherMov->delete();
        $this->actingAs($this->user)->patch("/movimenti/{$this->otherMov->id}/ripristina")->assertForbidden();
        $this->actingAs($this->user)->delete("/movimenti/{$this->otherMov->id}/elimina-definitivamente")->assertForbidden();
        $this->actingAs($this->user)->get('/movimenti/cestino')->assertOk()->assertDontSee('Movimento altra azienda');
        $this->assertSoftDeleted('movimenti', ['id' => $this->otherMov->id]);
    }

    public function test_cannot_touch_categories_of_other_entity(): void
    {
        $id = $this->otherCat->cat_id;

        $this->actingAs($this->user)->get("/categoria/{$id}/edit")->assertNotFound();
        $this->actingAs($this->user)->patch("/categoria/{$id}", ['descriz' => 'Rubata'])->assertNotFound();
        $this->actingAs($this->user)->delete("/categoria/{$id}")->assertNotFound();

        $this->assertDatabaseHas('categorie', [
            'cat_id' => $id, 'descriz_cat' => 'Segreta', 'entity_id' => $this->other->id,
        ]);
    }

    public function test_category_update_is_validated(): void
    {
        $this->makeCat('Affitto', $this->mine);

        $this->actingAs($this->user)
            ->patch("/categoria/{$this->myCat->cat_id}", ['descriz' => 'Affitto'])
            ->assertSessionHasErrors('descriz');

        $this->actingAs($this->user)
            ->patch("/categoria/{$this->myCat->cat_id}", ['descriz' => 'Spesa casa'])
            ->assertRedirect(route('categoria.index'));
        $this->assertDatabaseHas('categorie', ['cat_id' => $this->myCat->cat_id, 'descriz_cat' => 'Spesa casa']);
    }

    public function test_movement_requires_category_of_selected_entity_and_valid_date(): void
    {
        $data = ['entratauscita' => 0, 'descrizione' => 'Test', 'importo' => 10];

        $this->actingAs($this->user)
            ->post('/movimenti', $data + ['categoria' => $this->otherCat->cat_id])
            ->assertSessionHasErrors('categoria');

        $this->actingAs($this->user)
            ->post('/movimenti', $data + ['categoria' => $this->myCat->cat_id, 'data' => 'non-una-data'])
            ->assertSessionHasErrors('data');

        $this->actingAs($this->user)
            ->post('/movimenti', $data + ['categoria' => $this->myCat->cat_id, 'data' => '2026-03-15'])
            ->assertRedirect(route('movimenti.index'));
        $this->assertDatabaseHas('movimenti', [
            'descrizione' => 'Test', 'importo' => -10, 'entity_id' => $this->mine->id, 'data_movimento' => '2026-03-15',
        ]);
    }

    public function test_description_is_not_injected_into_javascript(): void
    {
        Movimento::create([
            'user_id' => $this->user->id,
            'entity_id' => $this->mine->id,
            'entratauscita' => 1,
            'categoria' => $this->myCat->cat_id,
            'descrizione' => "x');alert(1);//",
            'importo' => 1,
        ]);

        $this->actingAs($this->user)
            ->get('/movimenti')
            ->assertOk()
            ->assertSee('data-confirm="Spostare nel cestino il movimento x&#039;);alert(1);//?"', false)
            ->assertDontSee("confirm('Spostare", false);
    }

    public function test_search_by_date_redirects_to_filtered_list(): void
    {
        $this->actingAs($this->user)
            ->get('/movimenti/cercadata?data=2026-03-15')
            ->assertRedirect(route('movimenti.index', ['dal' => '2026-03-15', 'al' => '2026-03-15']));
    }

    public function test_users_debug_routes_are_gone(): void
    {
        $this->actingAs($this->user)->get("/users/{$this->user->id}")->assertNotFound();
    }

    public function test_dashboard_password_and_entity_scoped_totals(): void
    {
        config(['app.dashboard_password' => 'segreta']);

        $this->actingAs($this->user)
            ->post('/dashboard', ['password' => 'sbagliata'])
            ->assertSessionHas('password_error', 'Password errata.');

        $this->actingAs($this->user)
            ->post('/dashboard', ['password' => 'segreta'])
            ->assertRedirect(route('dashboard'));

        // Febbraio 31 non deve dare errore né slittare a marzo; i totali dell'altra entità non compaiono
        $this->actingAs($this->user)
            ->withSession(['password_verified' => true])
            ->get('/dashboard?start_month=2&end_month=2&end_day=31')
            ->assertOk()
            ->assertDontSee('999');
    }

    public function test_dashboard_is_closed_without_configured_password(): void
    {
        config(['app.dashboard_password' => null]);

        $this->actingAs($this->user)
            ->post('/dashboard', ['password' => 'default_password'])
            ->assertSessionHas('password_error', 'Accesso alla dashboard non configurato.');
    }

    public function test_manual_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/manual-login', ['email' => $this->user->email, 'password' => 'sbagliata']);
        }

        $this->post('/manual-login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
