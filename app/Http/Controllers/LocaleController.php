<?php

namespace App\Http\Controllers;

use App\Http\Middleware\InitializeSelectedEntity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Sets the interface language. It is kept in the session and, for a logged-in user,
     * saved in the profile. In demo mode the accounts are shared, so only the session.
     */
    public function update(Request $request)
    {
        $locale = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('app.locales')))],
        ])['locale'];

        $request->session()->put('locale', $locale);

        if ($request->user() && ! config('app.demo')) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        // Sulla demo si passa anche all'entità di esempio nella nuova lingua
        if ($request->user() && config('app.demo')) {
            app()->setLocale($locale);
            if ($id = InitializeSelectedEntity::demoEntityId($request->user()->entityIds())) {
                $request->session()->put('selected_entity_id', $id);
            }
        }

        return back();
    }
}
