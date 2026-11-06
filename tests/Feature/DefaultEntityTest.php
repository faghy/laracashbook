<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entità predefinita dell'utente, selezionata a ogni login.
 */
class DefaultEntityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entity $prima;

    private Entity $seconda;

    private Entity $estranea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->prima = Entity::create(['name' => 'Prima']);
        $this->seconda = Entity::create(['name' => 'Seconda']);
        $this->estranea = Entity::create(['name' => 'Estranea']);

        foreach ([$this->prima, $this->seconda] as $entity) {
            UserEntity::create(['user_id' => $this->user->id, 'entity_id' => $entity->id]);
        }
    }

    private function login(): void
    {
        $this->post('/manual-login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect();
    }

    public function test_without_default_the_first_entity_is_selected(): void
    {
        $this->login();
        $this->get('/movimenti')->assertOk();

        $this->assertSame($this->prima->id, session('selected_entity_id'));
    }

    public function test_default_entity_is_selected_at_every_login(): void
    {
        $this->user->forceFill(['default_entity_id' => $this->seconda->id])->save();

        // Anche se nella sessione precedente era stata scelta un'altra entità
        $this->withSession(['selected_entity_id' => $this->prima->id]);
        $this->login();
        $this->get('/movimenti')->assertOk();

        $this->assertSame($this->seconda->id, session('selected_entity_id'));
    }

    public function test_default_is_ignored_if_user_is_no_longer_associated(): void
    {
        $this->user->forceFill(['default_entity_id' => $this->seconda->id])->save();
        UserEntity::where('entity_id', $this->seconda->id)->delete();

        $this->login();
        $this->get('/movimenti')->assertOk();

        $this->assertSame($this->prima->id, session('selected_entity_id'));
    }

    public function test_user_can_set_default_entity_from_profile(): void
    {
        $this->actingAs($this->user)->get('/profile')
            ->assertOk()
            ->assertSee('Entità predefinita')
            ->assertSee('Seconda')
            ->assertDontSee('Estranea');

        $this->actingAs($this->user)->patch('/profile/entita-predefinita', ['default_entity_id' => $this->seconda->id])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('selected_entity_id', $this->seconda->id);

        $this->assertSame($this->seconda->id, $this->user->fresh()->default_entity_id);

        // Si può tornare a nessuna entità predefinita
        $this->actingAs($this->user)->patch('/profile/entita-predefinita', ['default_entity_id' => ''])
            ->assertRedirect(route('profile.edit'));
        $this->assertNull($this->user->fresh()->default_entity_id);
    }

    public function test_user_cannot_set_an_entity_not_associated(): void
    {
        $this->actingAs($this->user)->patch('/profile/entita-predefinita', ['default_entity_id' => $this->estranea->id])
            ->assertSessionHasErrors('default_entity_id');

        $this->assertNull($this->user->fresh()->default_entity_id);
    }

    public function test_dashboard_link_is_in_user_menu(): void
    {
        $html = $this->actingAs($this->user)->get('/movimenti')->assertOk()->getContent();

        // Nel menu utente (dopo il nome), non tra le voci principali
        $this->assertStringContainsString(route('dashboard'), substr($html, strpos($html, $this->user->name)));
        $this->assertStringNotContainsString(route('dashboard'), substr($html, 0, strpos($html, $this->user->name)));
    }
}
