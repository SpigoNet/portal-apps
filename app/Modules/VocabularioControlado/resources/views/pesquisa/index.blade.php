@extends('VocabularioControlado::layout')

@section('content')
<p>
    Apresentamos a macroestrutura desenvolvida de linguagem artificial, arranjada em ordem alfabética,
    permitindo a rápida consulta pelo controle da terminologia a serem adotadas no momento do cadastramento
    dos novos itens documentais.
</p>
<p>
    <a href="{{ route('vocabulario-controlado.index') }}/pdf" target="_blank" class="mdl-button mdl-js-button mdl-button--raised">
        Fazer download do PDF completo
    </a>
</p>

<form action="{{ route('vocabulario-controlado.buscar') }}" method="post" target="ifr-pesquisa">
    @csrf
    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
        <input class="mdl-textfield__input" type="text" id="palavra" name="palavra">
        <label class="mdl-textfield__label" for="palavra">Digite o termo ou parte dele para pesquisar</label>
    </div>
    <br>
    <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">
        Pesquisar
    </button>
</form>
<br>
<iframe src="" frameborder="0" name="ifr-pesquisa" id="ifr-pesquisa" style="width:100%;height:60vh;border:none;"></iframe>
@endsection
