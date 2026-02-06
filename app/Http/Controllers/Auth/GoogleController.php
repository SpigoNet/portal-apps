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
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate([
                'email' => $googleUser->email,
            ], [
                'google_id' => $googleUser->id,
                'name' => $googleUser->name,
                'avatar' => $googleUser->avatar,
            ]);

            Auth::login($user, true);

            $origin = session('module_origin');
            if ($origin === 'mundos-de-mim') {
                return redirect()->route('mundos-de-mim.index');
            }

            return redirect('/');

        } catch (Exception $e) {
            \Log::error('Erro no Login do Google: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect('/login')->with('error', 'Algo deu errado com o login do Google: ' . $e->getMessage());
        }
    }
}
