<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct() {
        $this->middleware(['guest:admin'])->except('logout');
    }

    public function loginForm() {
        // Verifica della login
        // commentato perchè poi l'ho implementato Middleware/Authenticate->redirectTo()
       /* if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }*/
        return view('admins.guards-login');
    }

    public function login(Request $request)    {
        // Validazione
        $this->validate($request, [
            'email' =>  ['required', 'email'],
            'password' => ['required', 'min:8']
        ]);

        // Gestione Login - utilizzo della facade guard
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password],
            $request->remember) /* come secondo parametro di attempt() uso remember_me che è il nome della check box "Ricorda password" */
            ) {

            $user = Admin::findOrFail(1); //recupero il primo amministratore
            Auth::user($user->id);

            return redirect('admin/dashboard');
        }

        // Gestione errori
        return redirect()->back()->withErrors([
            'email' => 'Credenziali non valide.',
            'password' => 'Credenziali non valide.'
        ]);
    }

    public function logout() {
        Auth::guard('admin')->logout();
        return redirect('admin/login');
    }

}
