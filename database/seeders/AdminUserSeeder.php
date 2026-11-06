<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea l'amministratore dell'area /admin con i dati del .env.
     *
     * @return void
     */
    public function run()
    {
        // La password non va scritta nel codice: impostala nel .env prima di lanciare il seeder
        $password = env('ADMIN_SEED_PASSWORD');
        if (empty($password)) {
            throw new \RuntimeException('Imposta ADMIN_SEED_PASSWORD nel file .env per creare l\'amministratore.');
        }

        DB::table('admins')->insert([
            'name' => env('ADMIN_SEED_NAME', 'admin'),
            'email' => env('ADMIN_SEED_EMAIL', 'admin@example.com'),
            'password' => Hash::make($password),
            'remember_token' => Str::random(10),
        ]);
    }
}
