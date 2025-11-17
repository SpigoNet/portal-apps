# **Padrão de Desenvolvimento Modular (Laravel)**

## **1\. Introdução**

Este documento define o padrão de arquitetura para o desenvolvimento de novas funcionalidades neste repositório. O objetivo é manter uma base de código organizada, modular e consistente, seguindo os princípios estabelecidos.

A arquitetura é baseada em **Módulos do Laravel**, onde cada módulo encapsula sua própria lógica de negócios, rotas, controllers, models e views.

## **2\. Princípios Fundamentais**

Todo novo desenvolvimento deve aderir estritamente aos seguintes princípios:

1. **Modularidade:** Cada funcionalidade principal (ex: "Admin", "DspaceForms", "GestorDeTarefas") deve ser contida em seu próprio diretório dentro de app/Modules/.  
2. **Padrão MVC Clássico (Server-Side Rendering):** A aplicação segue o padrão Model-View-Controller tradicional do Laravel. Os dados **não devem** ser expostos via API REST para serem consumidos pelo frontend.  
3. **Fluxo de Dados:**  
   * **Controllers** são responsáveis por receber requisições HTTP, usar **Models** (Eloquent) para buscar ou persistir dados e, ao final, retornar uma **View** (Blade) compilada, passando os dados necessários diretamente para ela (ex: return view('MeuModulo::index', compact('dados'));).  
4. **Sem Livewire/Volt:** O uso de Livewire ou Volt é **proibido** para novas funcionalidades. A interatividade deve ser alcançada através de formulários HTML tradicionais, submissões de página e, apenas quando estritamente necessário, JavaScript mínimo (vanilla JS ou Alpine.js).  
5. **Consistência:** O padrão estabelecido pelos módulos Admin e DspaceForms (exceto pelo uso de Livewire neste último) deve ser seguido.

## **3\. Estrutura de um Módulo**

Um novo módulo (ex: app/Modules/NovoModulo) deve seguir esta estrutura de diretórios:

app/Modules/NovoModulo/  
├── Http/  
│   └── Controllers/  
│       └── ExemploController.php  
├── Models/  
│   └── ExemploModel.php  
├── resources/  
│   └── views/  
│       ├── index.blade.php  
│       └── create.blade.php  
├── routes.php  
└── NovoModuloServiceProvider.php

## **4\. Guia: Criando um Novo Módulo**

Vamos usar como exemplo a criação de um módulo chamado GestorTarefas.

### **Passo 1: Criar a Estrutura de Pastas**

Crie a seguinte estrutura:

app/Modules/GestorTarefas/  
├── Http/  
│   └── Controllers/  
├── Models/  
├── resources/  
│   └── views/  
├── routes.php  
└── GestorTarefasServiceProvider.php

### **Passo 2: Criar o Service Provider**

Crie o arquivo app/Modules/GestorTarefas/GestorTarefasServiceProvider.php. Este arquivo é o coração do módulo, responsável por carregar suas rotas e views.

\<?php  
namespace App\\Modules\\GestorTarefas;

use Illuminate\\Support\\Facades\\Route;  
use Illuminate\\Support\\ServiceProvider;

class GestorTarefasServiceProvider extends ServiceProvider  
{  
    /\*\*  
     \* Define o namespace do módulo para as views.  
     \* @var string  
     \*/  
    protected $namespace \= 'GestorTarefas';

    public function boot()  
    {  
        // Carrega as views do módulo com um namespace  
        // Ex: view('GestorTarefas::index')  
        $this-\>loadViewsFrom(\_\_DIR\_\_.'/resources/views', $this-\>namespace);

        // Carrega o arquivo de rotas do módulo  
        Route::middleware('web')  
            \-\>group(\_\_DIR\_\_ . '/routes.php');  
    }

    public function register()  
    {  
        //  
    }  
}

### **Passo 3: Registrar o Service Provider**

Adicione seu novo provider ao arquivo bootstrap/providers.php:

\<?php

return \[  
    App\\Providers\\AppServiceProvider::class,  
    App\\Providers\\VoltServiceProvider::class,  
    App\\Modules\\Admin\\AdminServiceProvider::class,  
    App\\Modules\\DspaceForms\\DspaceFormsServiceProvider::class,  
    App\\Modules\\GestorTarefas\\GestorTarefasServiceProvider::class, // \<-- ADICIONAR AQUI  
\];

### **Passo 4: Definir Modelos**

Todos os modelos Eloquent específicos deste módulo devem residir em app/Modules/GestorTarefas/Models/.

*As migrations* continuam globais, no diretório database/migrations/.

### **Passo 5: Definir Rotas**

Edite o arquivo app/Modules/GestorTarefas/routes.php. Defina um prefixo e um nome de rota para evitar conflitos.

\<?php

use Illuminate\\Support\\Facades\\Route;  
use App\\Modules\\GestorTarefas\\Http\\Controllers\\TarefaController;

Route::middleware(\['web', 'auth', 'admin'\]) // Use os middlewares necessários (ex: 'admin')  
    \-\>prefix('gestor-tarefas') // Prefixo da URL (ex: /gestor-tarefas/...)  
    \-\>name('gestor-tarefas.') // Prefixo do nome da rota (ex: route('gestor-tarefas.index'))  
    \-\>group(function () {  
          
        Route::get('/', \[TarefaController::class, 'index'\])-\>name('index');  
        Route::get('/criar', \[TarefaController::class, 'create'\])-\>name('create');  
        Route::post('/', \[TarefaController::class, 'store'\])-\>name('store');  
          
        // Exemplo de rota resource  
        // Route::resource('tarefas', TarefaController::class);  
    });

### **Passo 6: Criar o Controller**

Crie o controller em app/Modules/GestorTarefas/Http/Controllers/TarefaController.php. Siga o padrão de retornar views diretamente.

\<?php

namespace App\\Modules\\GestorTarefas\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;  
use App\\Modules\\GestorTarefas\\Models\\Tarefa; // Modelo do módulo  
use Illuminate\\Http\\Request;

class TarefaController extends Controller  
{  
    /\*\*  
     \* Exibe a lista de tarefas.  
     \*/  
    public function index()  
    {  
        // 1\. Busca dados usando o Model  
        $tarefas \= Tarefa::where('user\_id', auth()-\>id())-\>get();

        // 2\. Retorna a View do módulo, passando os dados  
        return view('GestorTarefas::index', compact('tarefas'));  
    }

    /\*\*  
     \* Salva uma nova tarefa.  
     \*/  
    public function store(Request $request)  
    {  
        // 1\. Validação padrão  
        $validated \= $request-\>validate(\[  
            'titulo' \=\> 'required|string|max:255',  
            'descricao' \=\> 'nullable|string',  
        \]);

        // 2\. Lógica de negócios (salvar no banco)  
        auth()-\>user()-\>tarefas()-\>create($validated); // Exemplo

        // 3\. Redirecionamento padrão com mensagem de sucesso  
        return redirect()-\>route('gestor-tarefas.index')  
                         \-\>with('success', 'Tarefa criada com sucesso.');  
    }  
}

### **Passo 7: Criar as Views**

Crie os arquivos Blade em app/Modules/GestorTarefas/resources/views/.

**index.blade.php (Exemplo):**

\<x-app-layout\>  
    \<x-slot name="header"\>  
        \<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"\>  
            {{ \_\_('Minhas Tarefas') }}  
        \</h2\>  
    \</x-slot\>

    \<div class="py-12"\>  
        \<div class="max-w-7xl mx-auto sm:px-6 lg:px-8"\>  
              
            \<\!-- Mensagem de Sucesso \--\>  
            @if(session('success'))  
                \<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert"\>  
                    \<p\>{{ session('success') }}\</p\>  
                \</div\>  
            @endif

            \<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"\>  
                \<div class="p-6 text-gray-900 dark:text-gray-100"\>  
                    \<ul\>  
                        @forelse ($tarefas as $tarefa)  
                            \<li\>{{ $tarefa-\>titulo }}\</li\>  
                        @empty  
                            \<p\>Nenhuma tarefa encontrada.\</p\>  
                        @endforelse  
                    \</ul\>  
                \</div\>  
            \</div\>  
        \</div\>  
    \</div\>  
\</x-app-layout\>

## **5\. Restrições Adicionais (O que NÃO fazer)**

* **NÃO use Livewire ou Volt:** Qualquer interatividade deve ser tratada por formulários HTML, recarregamento de página ou, em último caso, JavaScript mínimo.  
* **NÃO crie endpoints de API para o Frontend:** Os controllers devem sempre retornar view() ou redirect(). O frontend (Blade) não deve fazer chamadas fetch() ou axios() para buscar dados da própria aplicação.  
* **NÃO coloque Models fora da pasta Models/ do Módulo:** Mantenha os modelos encapsulados (ex: app/Modules/GestorTarefas/Models/Tarefa.php).  
* **NÃO coloque lógica de negócios nas Rotas ou Views:** Mantenha toda a lógica nos Controllers e Models.
