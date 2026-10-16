<x-Cardapio::layout titulo="Nova casa" :menu="false">
    <div class="max-w-md mx-auto pt-10">
        <div class="cd-card space-y-4">
            <h1 class="text-2xl font-extrabold">🍽️ Bem-vindo ao Cardápio</h1>
            <p>Crie a sua casa para começar. Depois você cadastra quem come aqui — com ou sem login.</p>
            <form method="POST" action="{{ route('cardapio.casa.salvar') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="cd-label" for="nome">Nome da casa</label>
                    <input class="cd-input" id="nome" name="nome" value="{{ old('nome', 'Minha casa') }}" required maxlength="120">
                </div>
                <button class="cd-btn w-full" type="submit">Criar casa</button>
            </form>
        </div>
    </div>
</x-Cardapio::layout>
