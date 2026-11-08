<?php

namespace Tests\Feature;

use App\Models\Cat;
use App\Models\Entity;
use App\Models\Movimento;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ruoli: superadmin (tutto), admin (le sue entità), operatore (solo movimenti); utenti disattivati.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private Entity $casa;

    private Entity $studio;

    private Entity $altra;

    private Cat $cat;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.dashboard_password' => null]);

        $this->casa = Entity::create(['name' => 'Casa']);
        $this->studio = Entity::create(['name' => 'Studio']);
        $this->altra = Entity::create(['name' => 'Altra']);

        $this->cat = new Cat(['descriz_cat' => 'Spesa']);
        $this->cat->entity_id = $this->casa->id;
        $this->cat->save();
    }

    private function user(string $role, array $entities = [], array $attributes = []): User
    {
        $user = User::factory()->create(['role' => $role] + $attributes);
        foreach ($entities as $entity) {
            UserEntity::create(['user_id' => $user->id, 'entity_id' => $entity->id]);
        }

        return $user;
    }

    public function test_operator_only_works_on_movements(): void
    {
        $op = $this->user('operatore', [$this->casa]);

        $this->actingAs($op)->get('/movimenti')->assertOk()
            ->assertDontSee('Esporta CSV')->assertDontSee(route('categoria.index'))->assertDontSee(route('dashboard'))->assertDontSee(route('utenti.index'));
        $this->actingAs($op)->get('/movimenti/create')->assertOk()->assertDontSee('+ Nuova categoria');

        // CRUD dei movimenti
        $this->actingAs($op)->post('/movimenti', ['entratauscita' => 0, 'categoria' => $this->cat->cat_id, 'descrizione' => 'Pane', 'importo' => 3, 'data' => now()->toDateString()])
            ->assertRedirect(route('movimenti.index'));
        $mov = Movimento::where('descrizione', 'Pane')->firstOrFail();
        $this->actingAs($op)->get("/movimenti/{$mov->id}/edit")->assertOk();
        $this->actingAs($op)->delete("/movimenti/{$mov->id}")->assertRedirect();
        $this->actingAs($op)->get('/movimenti/cestino')->assertOk()->assertDontSee('Elimina definitivamente');
        $this->actingAs($op)->patch("/movimenti/{$mov->id}/ripristina")->assertRedirect();

        // Niente totali del giorno, dashboard, categorie, export, eliminazione definitiva, utenti
        $this->actingAs($op)->get('/movimenti?dal='.now()->toDateString().'&al='.now()->toDateString())->assertOk()->assertDontSee('Saldo della giornata');
        foreach (['/dashboard', '/categoria', '/categoria/create', '/movimenti/export', '/utenti', '/utenti/nuovo', '/entita'] as $url) {
            $this->actingAs($op)->get($url)->assertForbidden();
        }
        $mov->delete();
        $this->actingAs($op)->delete("/movimenti/{$mov->id}/elimina-definitivamente")->assertForbidden();
        $this->assertSoftDeleted($mov);
    }

    public function test_admin_sees_totals_and_manages_users_of_their_entities(): void
    {
        $admin = $this->user('admin', [$this->casa]);
        $op = $this->user('operatore', [$this->casa], ['name' => 'Mario Operatore']);
        $fuori = $this->user('operatore', [$this->altra], ['name' => 'Luca Fuori']);
        $super = $this->user('superadmin', [$this->casa], ['name' => 'Il Capo']);

        $this->actingAs($admin)->get('/movimenti?dal='.now()->toDateString().'&al='.now()->toDateString())->assertSee('Saldo della giornata')->assertSee('Esporta CSV');
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/categoria')->assertOk();
        $this->actingAs($admin)->get('/entita')->assertForbidden();

        // Elenco: solo gli utenti delle sue entità, mai il superadmin
        $this->actingAs($admin)->get('/utenti')->assertOk()->assertSee('Mario Operatore')->assertDontSee('Luca Fuori')->assertDontSee('Il Capo');
        $this->actingAs($admin)->get(route('utenti.edit', $fuori))->assertForbidden();
        $this->actingAs($admin)->get(route('utenti.edit', $super))->assertForbidden();

        // Promuove l'operatore ad admin, ma non a superadmin
        $this->actingAs($admin)->patch(route('utenti.update', $op), ['name' => 'Mario', 'email' => $op->email, 'role' => 'superadmin', 'entities' => [$this->casa->id]])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch(route('utenti.update', $op), ['name' => 'Mario', 'email' => $op->email, 'role' => 'admin', 'entities' => [$this->casa->id]])
            ->assertRedirect(route('utenti.edit', $op));
        $this->assertSame('admin', $op->fresh()->role);

        // Non può assegnare entità che non sono sue
        $this->actingAs($admin)->patch(route('utenti.update', $op), ['name' => 'Mario', 'email' => $op->email, 'role' => 'admin', 'entities' => [$this->altra->id]])
            ->assertSessionHasErrors('entities.0');

        // Crea un operatore: password casuale mostrata una volta
        $response = $this->actingAs($admin)->post('/utenti', ['name' => 'Nuovo', 'email' => 'nuovo@example.com', 'role' => 'operatore', 'entities' => [$this->casa->id]]);
        $nuovo = User::where('email', 'nuovo@example.com')->firstOrFail();
        $response->assertRedirect(route('utenti.edit', $nuovo))->assertSessionHas('new_password');
        $password = session('new_password');
        $this->assertSame([$this->casa->id], $nuovo->entityIds());
        $this->post('/logout');
        $this->post('/manual-login', ['email' => 'nuovo@example.com', 'password' => $password]);
        $this->assertAuthenticatedAs($nuovo);
    }

    public function test_admin_keeps_entities_they_cannot_see(): void
    {
        $admin = $this->user('admin', [$this->casa]);
        $op = $this->user('operatore', [$this->casa, $this->altra]);

        $this->actingAs($admin)->patch(route('utenti.update', $op), ['name' => $op->name, 'email' => $op->email, 'role' => 'operatore', 'entities' => [$this->casa->id]]);

        $this->assertEqualsCanonicalizing([$this->casa->id, $this->altra->id], $op->fresh()->entityIds());
    }

    public function test_admin_cannot_change_own_role_or_disable_themselves(): void
    {
        $admin = $this->user('admin', [$this->casa]);

        $this->actingAs($admin)->patch(route('utenti.update', $admin), ['name' => 'Io', 'email' => $admin->email, 'role' => 'operatore', 'entities' => [$this->casa->id]])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('utenti.toggle', $admin))->assertForbidden();
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertFalse($admin->fresh()->isDisabled());
    }

    public function test_superadmin_sees_everything_and_manages_entities(): void
    {
        $super = $this->user('superadmin');
        $fuori = $this->user('operatore', [$this->altra], ['name' => 'Luca Fuori']);

        // Tutte le entità, anche senza associazioni
        $this->assertEqualsCanonicalizing([$this->casa->id, $this->studio->id, $this->altra->id], $super->entityIds());
        $this->actingAs($super)->get('/movimenti')->assertOk()->assertSee('Altra');
        $this->actingAs($super)->get('/utenti')->assertSee('Luca Fuori');

        // Nomina un altro superadmin
        $this->actingAs($super)->patch(route('utenti.update', $fuori), ['name' => 'Luca', 'email' => $fuori->email, 'role' => 'superadmin'])
            ->assertRedirect();
        $this->assertSame('superadmin', $fuori->fresh()->role);

        // Entità: crea, rinomina, elimina solo se vuota
        $this->actingAs($super)->post('/entita', ['name' => 'Nuova'])->assertRedirect(route('entita.index'));
        $nuova = Entity::where('name', 'Nuova')->firstOrFail();
        $this->actingAs($super)->patch(route('entita.update', $nuova), ['name' => 'Rinominata']);
        $this->assertSame('Rinominata', $nuova->fresh()->name);
        $this->actingAs($super)->delete(route('entita.destroy', $this->casa))->assertSessionHas('error');
        $this->assertDatabaseHas('entities', ['id' => $this->casa->id]);
        $this->actingAs($super)->delete(route('entita.destroy', $nuova));
        $this->assertDatabaseMissing('entities', ['id' => $nuova->id]);
    }

    public function test_superadmin_without_entities_is_sent_to_create_one(): void
    {
        Cat::query()->delete();
        Entity::query()->delete();
        $super = $this->user('superadmin');

        $this->actingAs($super)->get('/movimenti')->assertRedirect(route('entita.index'));
        $this->actingAs($super)->get('/entita')->assertOk();
    }

    public function test_disabled_user_cannot_sign_in_and_is_logged_out(): void
    {
        $admin = $this->user('admin', [$this->casa]);
        $op = $this->user('operatore', [$this->casa]);

        // Già collegato: alla richiesta successiva alla disattivazione viene disconnesso
        $this->actingAs($op)->get('/movimenti')->assertOk();
        $this->actingAs($admin)->post(route('utenti.toggle', $op))->assertRedirect(route('utenti.edit', $op));
        $this->assertTrue($op->fresh()->isDisabled());
        $this->actingAs($op->fresh())->get('/movimenti')->assertRedirect(route('manual.login'));

        // Login rifiutato, con lo stesso messaggio delle credenziali errate
        $this->post('/logout');
        $this->post('/manual-login', ['email' => $op->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Riattivato, entra di nuovo
        $this->actingAs($admin)->post(route('utenti.toggle', $op));
        $this->post('/logout');
        $this->post('/manual-login', ['email' => $op->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($op->fresh());
    }

    public function test_password_reset_by_admin(): void
    {
        $admin = $this->user('admin', [$this->casa]);
        $op = $this->user('operatore', [$this->casa]);

        $this->actingAs($admin)->post(route('utenti.password', $op))->assertSessionHas('new_password');
        $password = session('new_password');

        $this->post('/logout');
        $this->post('/manual-login', ['email' => $op->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post('/manual-login', ['email' => $op->email, 'password' => $password]);
        $this->assertAuthenticatedAs($op->fresh());
    }

    public function test_user_management_is_blocked_in_demo(): void
    {
        config(['app.demo' => true]);
        $admin = $this->user('admin', [$this->casa]);
        $op = $this->user('operatore', [$this->casa]);

        $this->actingAs($admin)->get('/utenti')->assertOk();
        $this->actingAs($admin)->post(route('utenti.toggle', $op))->assertSessionHas('error');
        $this->assertFalse($op->fresh()->isDisabled());
    }

    public function test_migration_maps_old_roles(): void
    {
        $migration = require database_path('migrations/2026_11_09_000000_roles_and_disabled_users.php');
        $migration->down();
        $responsabile = $this->user('responsabile');
        $stagista = $this->user('stagista');
        $operatore = $this->user('operatore');

        $migration->up();

        $this->assertSame('admin', $responsabile->fresh()->role);
        $this->assertSame('operatore', $stagista->fresh()->role);
        $this->assertSame('operatore', $operatore->fresh()->role);
        $this->assertSame('operatore', DB::table('users')->insertGetId(['name' => 'x', 'email' => 'x@example.com', 'password' => 'x']) ? User::where('email', 'x@example.com')->value('role') : null);
    }
}
