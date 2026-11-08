<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetRole extends Command
{
    protected $signature = 'cashbook:set-role {email : Email dell\'utente} {role : superadmin, admin oppure operatore}';

    protected $description = "Cambia il ruolo di un utente (ad esempio per nominare il superadmin)";

    public function handle(): int
    {
        $role = $this->argument('role');
        if (! array_key_exists($role, User::ROLES)) {
            $this->error('Ruolo non valido: usa superadmin, admin oppure operatore.');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Nessun utente con questa email.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();
        $this->info("{$user->name} ora è {$role}.");

        return self::SUCCESS;
    }
}
