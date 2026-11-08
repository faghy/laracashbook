<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gestione utenti. Il superadmin vede e modifica tutti; l'admin solo gli utenti di pari
 * livello o inferiore delle sue entità (vedi User::canManage), e i superadmin gli restano
 * invisibili. Gli utenti non si cancellano: si disattivano, così i loro movimenti restano.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $users = User::with('userEntities')
            ->when(! $me->isSuperadmin(), fn ($q) => $q
                ->where('role', '!=', 'superadmin')
                ->whereHas('userEntities', fn ($q) => $q->whereIn('entity_id', $me->entityIds())))
            ->orderByRaw('disabled_at is not null')
            ->orderBy('name')
            ->get();

        return view('utenti.index', [
            'users' => $users,
            'entityNames' => Entity::pluck('name', 'id'),
        ]);
    }

    public function create(Request $request)
    {
        return view('utenti.create', $this->formData($request->user(), (new User)->forceFill(['role' => 'operatore'])));
    }

    public function store(Request $request)
    {
        $me = $request->user();
        $data = $this->validated($request, $me);

        $user = new User(['name' => $data['name'], 'email' => $data['email']]);
        $user->forceFill([
            'role' => $data['role'],
            'password' => Hash::make($password = $this->newPassword()),
            'email_verified_at' => now(),
        ])->save();

        $this->syncEntities($me, $user, $data['entities'] ?? []);

        return redirect()->route('utenti.edit', $user)
            ->with('status', __('User created.'))
            ->with('new_password', $password);
    }

    public function edit(Request $request, User $user)
    {
        $this->authorize('manage-user', $user);

        return view('utenti.edit', $this->formData($request->user(), $user));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('manage-user', $user);
        $me = $request->user();
        $data = $this->validated($request, $me, $user);

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        // Il proprio ruolo non si cambia, per non chiudersi fuori
        if (! $user->is($me)) {
            $user->role = $data['role'];
        }
        $user->save();

        $this->syncEntities($me, $user, $data['entities'] ?? []);

        return redirect()->route('utenti.edit', $user)->with('status', __('User updated.'));
    }

    /**
     * Imposta una password casuale e la mostra una sola volta, da comunicare all'utente.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('manage-user', $user);

        $user->forceFill([
            'password' => Hash::make($password = $this->newPassword()),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('utenti.edit', $user)
            ->with('status', __('New password set.'))
            ->with('new_password', $password);
    }

    /**
     * Disattiva o riattiva l'utente: un utente disattivato non può più entrare.
     */
    public function toggleActive(Request $request, User $user)
    {
        $this->authorize('manage-user', $user);
        abort_if($user->is($request->user()), 403, __('You cannot disable your own account.'));

        $user->forceFill([
            'disabled_at' => $user->isDisabled() ? null : now(),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('utenti.edit', $user)
            ->with('status', $user->isDisabled() ? __('User disabled.') : __('User enabled.'));
    }

    private function formData(User $me, User $user): array
    {
        return [
            'user' => $user,
            'roles' => $me->assignableRoles(),
            'entities' => Entity::whereIn('id', $me->entityIds())->orderBy('name')->get(),
            'selected' => $user->exists ? $user->userEntities->pluck('entity_id')->map(fn ($id) => (int) $id)->all() : [],
            'isSelf' => $user->is($me),
        ];
    }

    private function validated(Request $request, User $me, ?User $user = null): array
    {
        $role = $user?->is($me) ? $me->role : $request->input('role');

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in($user?->is($me) ? [$me->role] : $me->assignableRoles())],
            // Il superadmin vede tutte le entità e può restare senza associazioni
            'entities' => [$role === 'superadmin' ? 'nullable' : 'required', 'array'],
            'entities.*' => ['integer', Rule::in($me->entityIds())],
        ]);
    }

    /**
     * Associa l'utente alle entità scelte tra quelle visibili a chi modifica,
     * senza toccare le associazioni alle entità che chi modifica non vede.
     */
    private function syncEntities(User $me, User $user, array $chosen): void
    {
        $scope = $me->entityIds();
        $chosen = array_values(array_intersect(array_map('intval', $chosen), $scope));

        UserEntity::where('user_id', $user->id)->whereIn('entity_id', $scope)->whereNotIn('entity_id', $chosen)->delete();
        foreach ($chosen as $entityId) {
            UserEntity::firstOrCreate(['user_id' => $user->id, 'entity_id' => $entityId]);
        }
        $user->unsetRelation('userEntities');
    }

    private function newPassword(): string
    {
        return Str::password(14, symbols: false);
    }
}
