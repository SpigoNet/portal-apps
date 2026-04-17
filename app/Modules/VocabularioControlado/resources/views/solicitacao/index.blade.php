@php $semMenu = true; @endphp
@extends('VocabularioControlado::layout')

@section('content')

@if ($perfil === null)
    {{-- Modo anônimo: apenas lista pública --}}
    @include('VocabularioControlado::solicitacao._lista-completa')
@else
    @if ($perfil->perfil === 'bibliotecario')
        @include('VocabularioControlado::solicitacao.bibliotecario')
    @elseif ($perfil->perfil === 'aprovador')
        {{-- A view de aprovador tem seu próprio carregamento via POST; aqui exibimos a tela inicial --}}
        @php
            $pendentes = \App\Modules\VocabularioControlado\Models\Vocabulario::where('status','Solicitado')->orderByDesc('dt_solicitado')->get();
            $unidadeIds = $pendentes->pluck('unidade')->unique()->filter()->values()->toArray();
            $unidades = \App\Modules\VocabularioControlado\Models\ListaValores::where('value_pairs_name','common_publisher')->whereIn('stored_value',$unidadeIds)->get()->keyBy('stored_value');
            $todosTermos = \App\Modules\VocabularioControlado\Models\Vocabulario::whereIn('status',['Disponível','Aprovado'])->orderByRaw('TRIM(palavra)')->get();
        @endphp
        @include('VocabularioControlado::solicitacao.aprovador')
    @elseif ($perfil->perfil === 'implantacao')
        @php
            $termos = \App\Modules\VocabularioControlado\Models\Vocabulario::whereIn('status',['Disponível','Aprovado'])->orderByRaw('TRIM(palavra)')->get();
        @endphp
        @include('VocabularioControlado::solicitacao.implantacao')
    @endif
@endif

@endsection
