<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class MicrosoftController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('azure')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $microsoftUser = Socialite::driver('azure')->user();

            $user = User::updateOrCreate([
                'email' => $microsoftUser->email,
            ], [
                'microsoft_id' => $microsoftUser->id,
                'name' => $microsoftUser->name,
                // Azure's avatar is usually handled differently, but we can try to get it if available
                'avatar' => $microsoftUser->avatar ?? null,
            ]);

            Auth::login($user, true);

            return redirect('/');

        } catch (Exception $e) {
            \Log::error('Erro no Login da Microsoft: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect('/login')->with('error', 'Algo deu errado com o login da Microsoft: ' . $e->getMessage());
        }
    }
}
