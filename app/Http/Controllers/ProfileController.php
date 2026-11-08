<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */

    public function getInfo()    {

        if (Auth::check()) {
            return redirect()->intended('movimenti');
        }
        return view('manual-login');
    }

    public function manualLogin(LoginRequest $request) {
        // Verifica le credenziali con limite di 5 tentativi (vedi LoginRequest)
        $request->authenticate();

        // Rigenero la sessione per prevenire attacchi di session fixation
        $request->session()->regenerate();
        // L'entità verrà reimpostata dal middleware InitializeSelectedEntity
        $request->session()->forget(['selected_entity_id', 'password_verified']);

        return redirect()->intended('movimenti'); //fallback URI tiene in memoria la pagina cercata prima del login e mi ci porta dopo averlo fatto
    }

    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'entities' => Entity::whereIn('id', $request->user()->entityIds())->orderBy('name')->get(),
        ]);
    }

    /**
     * Imposta l'entità selezionata automaticamente a ogni login.
     */
    public function updateDefaultEntity(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'default_entity_id' => ['nullable', 'integer', function ($attribute, $value, $fail) use ($user) {
                if (! $user->hasEntity($value)) {
                    $fail('Puoi scegliere solo un\'entità a cui sei associato.');
                }
            }],
        ]);

        $user->default_entity_id = $validated['default_entity_id'] ?? null;
        $user->save();

        // La scelta vale subito anche per la sessione in corso
        if ($user->default_entity_id) {
            $request->session()->put('selected_entity_id', $user->default_entity_id);
        }

        return Redirect::route('profile.edit')->with('status', __('Default entity updated.'));
    }


    /**
     * Update the user's profile information.
     *
     * @param  \App\Http\Requests\ProfileUpdateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProfileUpdateRequest $request)
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
