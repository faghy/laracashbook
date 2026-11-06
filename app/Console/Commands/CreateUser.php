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
                            {--role=responsabile : Ruolo (stagista, operatore, responsabile)}';

    protected $description = 'Crea un utente e lo associa a una o più entità';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nome');
        $email = $this->option('email') ?: $this->ask('Email');
        $entities = $this->option('entity') ?: [$this->ask('Entità', 'Personale')];
        $password = $this->secret('Password (almeno 8 caratteri)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $this->option('role')],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:stagista,operatore,responsabile',
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
            'role' => $this->option('role'),
        ]);

        foreach ($entities as $entityName) {
            $entity = Entity::firstOrCreate(['name' => $entityName]);
            UserEntity::firstOrCreate(['user_id' => $user->id, 'entity_id' => $entity->id]);
        }

        $this->info("Utente {$email} creato e associato a: ".implode(', ', $entities).'.');

        return self::SUCCESS;
    }
}
