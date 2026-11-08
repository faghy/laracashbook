<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    /**
     * Ruoli dal più alto al più basso: il superadmin vede e fa tutto, l'admin gestisce
     * le sue entità (totali, categorie, utenti di pari livello o inferiore),
     * l'operatore lavora solo sui movimenti.
     */
    public const ROLES = ['superadmin' => 3, 'admin' => 2, 'operatore' => 1];

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Admin o superadmin: vede totali e dashboard, gestisce categorie e utenti.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function roleLevel(): int
    {
        return self::ROLES[$this->role] ?? 0;
    }

    /**
     * Ruoli che questo utente può assegnare: il superadmin tutti, l'admin admin e operatore.
     */
    public function assignableRoles(): array
    {
        return array_keys(array_filter(self::ROLES, fn ($level) => $level <= $this->roleLevel()));
    }

    /**
     * Può modificare l'utente indicato: il superadmin chiunque, l'admin gli utenti
     * di pari livello o inferiore che condividono almeno una delle sue entità.
     */
    public function canManage(User $user): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->isAdmin()
            && $user->roleLevel() <= $this->roleLevel()
            && array_intersect($user->entityIds(), $this->entityIds()) !== [];
    }

    public function movimenti() {
        return $this->hasMany(Movimento::class);
    }

    public function userEntities()
    {
        return $this->hasMany(UserEntity::class);
    }

    /**
     * ID delle entità a cui l'utente è associato.
     *
     * @return array<int, int>
     */
    public function entityIds(): array
    {
        // Il superadmin ha accesso a tutte le entità
        if ($this->isSuperadmin()) {
            return Entity::orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->userEntities->pluck('entity_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function hasEntity($entityId): bool
    {
        return in_array((int) $entityId, $this->entityIds(), true);
    }

    public function defaultEntity()
    {
        return $this->belongsTo(Entity::class, 'default_entity_id');
    }

    /**
     * Entità da selezionare quando non ce n'è una in sessione:
     * quella predefinita, se l'utente è ancora associato, altrimenti la prima.
     */
    public function initialEntityId(): ?int
    {
        $ids = $this->entityIds();

        return $this->default_entity_id && in_array((int) $this->default_entity_id, $ids, true)
            ? (int) $this->default_entity_id
            : ($ids[0] ?? null);
    }

}
