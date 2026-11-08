<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'cashbook:create-user
                            {--name= : Nome dell\'utente}
                            {--email= : Email usata per accedere}
                            {--entity=* : Entità da associare (creata se non esiste), ripetibile}
                            {--role= : Ruolo (superadmin, admin, operatore); il primo utente è superadmin, poi admin}';

    protected $description = 'Crea un utente e lo associa a una o più entità';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nome');
        $email = $this->option('email') ?: $this->ask('Email');
        $entities = $this->option('entity') ?: [$this->ask('Entità', 'Personale')];
        $password = $this->secret('Password (almeno 8 caratteri)');

        $role = $this->option('role') ?: (User::where('role', 'superadmin')->exists() ? 'admin' : 'superadmin');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:superadmin,admin,operatore',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::forceCreate([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        foreach ($entities as $entityName) {
            $entity = Entity::firstOrCreate(['name' => $entityName]);
            UserEntity::firstOrCreate(['user_id' => $user->id, 'entity_id' => $entity->id]);
        }

        $this->info("Utente {$email} ({$role}) creato e associato a: ".implode(', ', $entities).'.');

        return self::SUCCESS;
    }
}
