<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate([
                'google_id' => $googleUser->id,
            ], [
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            Auth::login($user, true); // O 'true' cria a sessão "lembrar de mim"

            return redirect('/');

        } catch (Exception $e) {
            // Pode adicionar um log aqui
            return redirect('/login')->with('error', 'Algo deu errado com o login do Google.');
        }
    }
}
