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

{{-- Link para Painel do Aluno (Todos podem ver, pois admins/professores podem querer ver suas "aulas" como alunos) --}}
<x-dropdown-link :href="route('ant.home')">
    Minhas Aulas
</x-dropdown-link>