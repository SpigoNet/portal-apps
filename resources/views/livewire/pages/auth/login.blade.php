<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $origin = Session::get('module_origin');
        if ($origin === 'mundos-de-mim') {
            $this->redirect(route('mundos-de-mim.index'), navigate: true);
            return;
        }

        $this->redirectIntended(default: route('welcome', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email"
                class="block mt-1 w-full bg-white/5 border-spigo-violet/30 focus:border-spigo-lime focus:ring-spigo-lime"
                type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password"
                class="block mt-1 w-full bg-white/5 border-spigo-violet/30 focus:border-spigo-lime focus:ring-spigo-lime"
                type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded bg-spigo-dark border-spigo-violet/30 text-spigo-blue focus:ring-spigo-blue"
                    name="remember">
                <span class="ms-2 text-sm text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-400 hover:text-spigo-lime rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-spigo-lime focus:ring-offset-spigo-dark"
                    href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Esqueceu sua senha?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Entrar') }}
            </x-primary-button>
        </div>
    </form>

    <div class="relative flex py-5 items-center">
        <div class="flex-grow border-t border-spigo-violet/20"></div>
        <span class="flex-shrink mx-4 text-gray-400 text-sm">OU</span>
        <div class="flex-grow border-t border-spigo-violet/20"></div>
    </div>


    <!-- Login com Social -->
    <div class="flex flex-col gap-3">
        <!-- Google -->
        <a href="{{ route('google.redirect') }}"
            class="w-full inline-flex items-center justify-center px-4 py-2 bg-white/90 border border-transparent rounded-md font-semibold text-xs text-spigo-dark uppercase tracking-widest hover:bg-white focus:bg-white active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-spigo-lime focus:ring-offset-2 focus:ring-offset-spigo-dark transition ease-in-out duration-150">
            <i class="fa-brands fa-google mr-2 text-base"></i>
            {{ __('Login com o Google') }}
        </a>
    </div>
</div>