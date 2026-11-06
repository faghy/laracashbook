<?php

namespace Database\Seeders;

use App\Models\Cat;
use App\Models\Entity;
use App\Models\Movimento;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Dati di esempio per provare l'applicazione: un utente demo, due entità
 * con le loro categorie e circa sei mesi di movimenti.
 * Accesso: demo@example.com / password (solo per installazioni di prova).
 */
class DemoSeeder extends Seeder
{
    /**
     * Categorie per entità: descrizione => [tipo (1 entrata, 0 uscita), importo min, importo max, movimenti al mese, estero senza IVA]
     */
    private const ENTITIES = [
        'Casa' => [
            'Stipendio' => [1, 1800, 2100, 1, false],
            'Affitto' => [0, 750, 750, 1, false],
            'Spesa alimentare' => [0, 25, 140, 6, false],
            'Bollette' => [0, 40, 180, 2, false],
            'Trasporti' => [0, 15, 70, 3, false],
            'Tempo libero' => [0, 10, 90, 3, false],
        ],
        'Studio grafico' => [
            'Fatture clienti' => [1, 300, 2500, 3, false],
            'Clienti esteri' => [1, 400, 1800, 1, true],
            'Software e abbonamenti' => [0, 12, 60, 2, true],
            'Materiale ufficio' => [0, 10, 120, 1, false],
            'Commercialista' => [0, 150, 150, 1, false],
        ],
    ];

    private const DESCRIPTIONS = [
        'Stipendio' => ['Stipendio mensile'],
        'Affitto' => ['Affitto appartamento'],
        'Spesa alimentare' => ['Supermercato', 'Mercato rionale', 'Panetteria', 'Spesa settimanale'],
        'Bollette' => ['Bolletta luce', 'Bolletta gas', 'Internet casa', 'Bolletta acqua'],
        'Trasporti' => ['Carburante', 'Abbonamento bus', 'Parcheggio', 'Biglietto treno'],
        'Tempo libero' => ['Cinema', 'Cena fuori', 'Libri', 'Palestra'],
        'Fatture clienti' => ['Logo e immagine coordinata', 'Impaginazione catalogo', 'Grafica social', 'Sito vetrina'],
        'Clienti esteri' => ['Illustrazioni per cliente UE', 'Packaging cliente extra UE'],
        'Software e abbonamenti' => ['Licenza software grafica', 'Font', 'Archivio cloud'],
        'Materiale ufficio' => ['Cancelleria', 'Toner stampante', 'Carta fotografica'],
        'Commercialista' => ['Parcella commercialista'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        mt_srand(2026); // dati sempre uguali a ogni installazione

        $user = User::forceCreate([
            'name' => 'Utente demo',
            'email' => 'demo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'responsabile',
        ]);

        foreach (self::ENTITIES as $entityName => $categories) {
            $entity = Entity::create(['name' => $entityName]);
            UserEntity::create(['user_id' => $user->id, 'entity_id' => $entity->id]);

            foreach ($categories as $description => [$tipo, $min, $max, $perMonth, $estero]) {
                $cat = new Cat(['descriz_cat' => $description]);
                $cat->entity_id = $entity->id;
                $cat->estero_senza_iva = $estero;
                $cat->save();

                $this->seedMovements($user, $entity, $cat, $tipo, $min, $max, $perMonth, $estero);
            }
        }

        $user->forceFill(['default_entity_id' => Entity::where('name', 'Casa')->value('id')])->save();
    }

    private function seedMovements(User $user, Entity $entity, Cat $cat, int $tipo, int $min, int $max, int $perMonth, bool $estero): void
    {
        $descriptions = self::DESCRIPTIONS[$cat->descriz_cat];

        for ($month = 5; $month >= 0; $month--) {
            $start = Carbon::today()->startOfMonth()->subMonths($month);
            $lastDay = $month === 0 ? Carbon::today()->day : $start->daysInMonth;

            for ($i = 0; $i < $perMonth; $i++) {
                // entratauscita va impostato prima di importo: il mutator rende negative le uscite
                Movimento::create([
                    'entratauscita' => $tipo,
                    'importo' => $min === $max ? $min : mt_rand($min * 100, $max * 100) / 100,
                    'categoria' => $cat->cat_id,
                    'descrizione' => $descriptions[mt_rand(0, count($descriptions) - 1)],
                    'data_movimento' => $start->copy()->addDays(mt_rand(0, $lastDay - 1))->toDateString(),
                    'estero_senza_iva' => $estero,
                    'user_id' => $user->id,
                    'entity_id' => $entity->id,
                ]);
            }
        }
    }
}
