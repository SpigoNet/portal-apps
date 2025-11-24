<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    // Novos campos
    public string $whatsapp_phone = '';
    public string $whatsapp_apikey = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->whatsapp_phone = $user->whatsapp_phone ?? '';
        $this->whatsapp_apikey = $user->whatsapp_apikey ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_apikey' => ['nullable', 'string', 'max:20'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);

        // Opcional: Enviar msg de teste ao salvar se tiver preenchido
        if($user->wasChanged('whatsapp_apikey') && !empty($user->whatsapp_apikey)){
            send_whatsapp_user($user, "Configuração de WhatsApp salva com sucesso no Portal Spigo!");
        }
    }

    public function sendVerification(): void
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }
        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Informações do Perfil') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Atualize as informações do seu perfil, email e notificações.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}
                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <i class="fab fa-whatsapp text-green-500 mr-2"></i> Configuração de Notificações WhatsApp
            </h3>

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 mb-6 text-sm text-blue-700 dark:text-blue-300">
                <p class="font-bold mb-2">Você precisa obter uma ApiKey do bot antes de usar:</p>
                <ol class="list-decimal ml-5 space-y-1">
                    <li>Adicione o número <strong>+34 644 87 21 57</strong> aos seus contatos (Nomeie como "CallMeBot").</li>
                    <li>Envie a mensagem: <code class="bg-blue-100 dark:bg-blue-800 px-1 py-0.5 rounded">I allow callmebot to send me messages</code> para este contato via WhatsApp.</li>
                    <li>Aguarde a mensagem: "API Activated for your phone number. Your APIKEY is 123123".</li>
                    <li>Insira seu número e a APIKEY recebida nos campos abaixo.</li>
                </ol>
                <p class="mt-2 text-xs opacity-75">Nota: Se não receber em 2 minutos, tente novamente após 24h.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="whatsapp_phone" :value="__('Seu WhatsApp (com código país)')" />
                    <x-text-input wire:model="whatsapp_phone" id="whatsapp_phone" name="whatsapp_phone" type="text" class="mt-1 block w-full" placeholder="Ex: 5519999999999" />
                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_phone')" />
                </div>

                <div>
                    <x-input-label for="whatsapp_apikey" :value="__('Sua API Key (CallMeBot)')" />
                    <x-text-input wire:model="whatsapp_apikey" id="whatsapp_apikey" name="whatsapp_apikey" type="text" class="mt-1 block w-full" placeholder="Ex: 123123" />
                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_apikey')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
