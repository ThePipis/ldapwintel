<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
 

    use AuthenticatesUsers;
 
    protected $redirectTo = RouteServiceProvider::HOME;
 
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login(Request $request)
    {
        $request->validate([
            'mail' => 'required|string',
            'password' => 'required|string',
        ]);
    
        \Log::info('Intentando conectar con el servidor LDAP para ' . $request->input('mail'));
    
        $ldapconn = ldap_connect(env('LDAP_SERVER'));
         
        if ($ldapconn) {
            \Log::info('Intentando hacer bind con LDAP para ' . $request->input('mail'));
    
            #$ldapbind = @ldap_bind($ldapconn, $request->input('mail').','.env('LDAP_DN'), $request->input('password'));
            // Usando UPN directamente para el bind
            $ldapbind = @ldap_bind($ldapconn, $request->input('mail'), $request->input('password'));

            if ($ldapbind) {
                \Log::info('Bind exitoso con LDAP para ' . $request->input('mail'));
                
                $user = \App\Models\User::firstOrNew(['mail' => $request->input('mail')]);
                $user->mail = $request->input('mail');  // Asegúrate de que el campo 'mail' está establecido
                
                // Aquí puedes sincronizar otros campos si lo deseas
                $user->name = $request->input('mail');  // Ejemplo
                $user->password = \Hash::make($request->input('password'));
                
                $user->save();
                
                Auth::login($user, $remember = true);
            
                return redirect()->intended('/home');
            } else {
                \Log::warning('Falló el bind con LDAP para ' . $request->input('mail') . '. Error: ' . ldap_error($ldapconn));
                return back()->withErrors([
                    'mail' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
                ]);
            }
        } else {
            \Log::warning('No se pudo conectar al servidor LDAP.');
            return back()->withErrors([
                'mail' => 'No se pudo conectar al servidor LDAP.',
            ]);
        }
    }

}
