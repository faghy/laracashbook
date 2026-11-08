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
 * con le loro categorie e circa sei mesi di movimenti, in inglese o in italiano.
 * Accesso: demo@example.com (admin) oppure operatore@example.com (operatore), password "password"
 * (solo per installazioni di prova).
 */
class DemoSeeder extends Seeder
{
    /**
     * Categorie per entità: descrizione => [tipo (1 entrata, 0 uscita), importo min, importo max, movimenti al mese, estero senza IVA, descrizioni]
     * I dati sono in inglese o in italiano secondo la lingua dell'applicazione.
     */
    private const DATA = [
        'en' => [
            'Home' => [
                'Salary' => [1, 1800, 2100, 1, false, ['Monthly salary']],
                'Rent' => [0, 750, 750, 1, false, ['Flat rent']],
                'Groceries' => [0, 25, 140, 6, false, ['Supermarket', 'Farmers market', 'Bakery', 'Weekly shopping']],
                'Bills' => [0, 40, 180, 2, false, ['Electricity bill', 'Gas bill', 'Home internet', 'Water bill']],
                'Transport' => [0, 15, 70, 3, false, ['Fuel', 'Bus pass', 'Parking', 'Train ticket']],
                'Leisure' => [0, 10, 90, 3, false, ['Cinema', 'Dinner out', 'Books', 'Gym']],
            ],
            'Design studio' => [
                'Client invoices' => [1, 300, 2500, 3, false, ['Logo and brand identity', 'Catalogue layout', 'Social media graphics', 'Showcase website']],
                'Foreign clients' => [1, 400, 1800, 1, true, ['Illustrations for EU client', 'Packaging for non-EU client']],
                'Software and subscriptions' => [0, 12, 60, 2, true, ['Design software licence', 'Fonts', 'Cloud storage']],
                'Office supplies' => [0, 10, 120, 1, false, ['Stationery', 'Printer toner', 'Photo paper']],
                'Accountant' => [0, 150, 150, 1, false, ['Accountant fee']],
            ],
        ],
        'it' => [
            'Casa' => [
                'Stipendio' => [1, 1800, 2100, 1, false, ['Stipendio mensile']],
                'Affitto' => [0, 750, 750, 1, false, ['Affitto appartamento']],
                'Spesa alimentare' => [0, 25, 140, 6, false, ['Supermercato', 'Mercato rionale', 'Panetteria', 'Spesa settimanale']],
                'Bollette' => [0, 40, 180, 2, false, ['Bolletta luce', 'Bolletta gas', 'Internet casa', 'Bolletta acqua']],
                'Trasporti' => [0, 15, 70, 3, false, ['Carburante', 'Abbonamento bus', 'Parcheggio', 'Biglietto treno']],
                'Tempo libero' => [0, 10, 90, 3, false, ['Cinema', 'Cena fuori', 'Libri', 'Palestra']],
            ],
            'Studio grafico' => [
                'Fatture clienti' => [1, 300, 2500, 3, false, ['Logo e immagine coordinata', 'Impaginazione catalogo', 'Grafica social', 'Sito vetrina']],
                'Clienti esteri' => [1, 400, 1800, 1, true, ['Illustrazioni per cliente UE', 'Packaging cliente extra UE']],
                'Software e abbonamenti' => [0, 12, 60, 2, true, ['Licenza software grafica', 'Font', 'Archivio cloud']],
                'Materiale ufficio' => [0, 10, 120, 1, false, ['Cancelleria', 'Toner stampante', 'Carta fotografica']],
                'Commercialista' => [0, 150, 150, 1, false, ['Parcella commercialista']],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        mt_srand(2026); // dati sempre uguali a ogni installazione

        $data = self::DATA[app()->getLocale()] ?? self::DATA['en'];

        $user = User::forceCreate([
            'name' => app()->getLocale() === 'it' ? 'Utente demo' : 'Demo user',
            'email' => 'demo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        foreach ($data as $entityName => $categories) {
            $entity = Entity::create(['name' => $entityName]);
            UserEntity::create(['user_id' => $user->id, 'entity_id' => $entity->id]);

            foreach ($categories as $description => [$tipo, $min, $max, $perMonth, $estero, $descriptions]) {
                $cat = new Cat(['descriz_cat' => $description]);
                $cat->entity_id = $entity->id;
                $cat->estero_senza_iva = $estero;
                $cat->save();

                $this->seedMovements($user, $entity, $cat, $tipo, $min, $max, $perMonth, $estero, $descriptions);
            }
        }

        // La prima entità (la casa) è quella predefinita
        $first = Entity::where('name', array_key_first($data))->value('id');
        $user->forceFill(['default_entity_id' => $first])->save();

        // Un operatore sulla prima entità, per provare il ruolo che vede solo i movimenti
        $operatore = User::forceCreate([
            'name' => app()->getLocale() === 'it' ? 'Operatore demo' : 'Demo operator',
            'email' => 'operatore@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'operatore',
        ]);
        UserEntity::create(['user_id' => $operatore->id, 'entity_id' => $first]);
    }

    private function seedMovements(User $user, Entity $entity, Cat $cat, int $tipo, int $min, int $max, int $perMonth, bool $estero, array $descriptions): void
    {
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
