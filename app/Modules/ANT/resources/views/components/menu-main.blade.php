@can('admin-do-app')
    <x-dropdown-link :href="route('ant.admin.home')">
        Painel Admin
    </x-dropdown-link>
@endcan

{{-- Link para Painel do Professor (Verifica se é professor ou admin) --}}
@if(auth()->user()->isProfessor() || (isset($isAdmin) && $isAdmin))
    <x-dropdown-link :href="route('ant.professor.index')">
        Painel Professor
    </x-dropdown-link>
@endif

{{-- Link para Painel do Aluno (apenas não-professores, evita duplicar com o Painel Professor) --}}
@unless(auth()->user()->isProfessor())
    <x-dropdown-link :href="route('ant.home')">
        Minhas Aulas
    </x-dropdown-link>
@endunless

@if(auth()->user()->isProfessor() || (isset($isAdmin) && $isAdmin))
    <x-dropdown-link :href="route('ant.professor.apresentacoes.index')">
        Apresentações (Professor)
    </x-dropdown-link>
@endif