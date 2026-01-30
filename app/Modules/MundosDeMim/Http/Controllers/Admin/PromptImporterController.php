<?php

namespace App\Modules\MundosDeMim\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\MundosDeMim\Models\Theme;
use App\Modules\MundosDeMim\Models\Prompt;
use App\Services\IaService; // <--- Serviço de IA existente
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptImporterController extends Controller
{
    protected $aiService;

    // Injeção de dependência do serviço de IA
    public function __construct(IaService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        return view('MundosDeMim::admin.import.index');
    }

    public function analyze(Request $request)
    {
        $rawPrompt = $request->input('raw_prompt');

        // 1. Construção do Prompt de Sistema (A "Inteligência")
        $systemInstruction = <<<EOT
Você é um especialista em engenharia de prompt para o sistema "Mundos de Mim".
Sua tarefa é converter prompts brutos de geração de imagem (Midjourney/Stable Diffusion) para o nosso formato de template dinâmico.

REGRAS DE CONVERSÃO:
1. SUJEITO: O prompt original geralmente descreve "uma mulher", "um homem", "uma pessoa".
   Você DEVE substituir isso para focar na "pessoa da referência".
   Exemplo: "A photo of a beautiful woman wearing santa clothes" -> "A photo of the person from the reference image wearing santa clothes".
   Use termos como "The subject", "The person", "The user".

2. VARIÁVEIS: Identifique características físicas fixas e substitua por nossas variáveis:
   - Cabelo (cor/tipo) -> {hair_type} hair
   - Olhos (cor) -> {eye_color} eyes
   - Corpo (magro/gordo/musculoso) -> {body_type} body
   - Altura -> {height}
   - Peso -> {weight}
   Exemplo: "Blue eyes and blonde hair" -> "{eye_color} eyes and {hair_type} hair".

3. REQUISITOS: Analise o contexto para definir quem pode usar este prompt.
   - Se mencionar cachorro/gato -> key: "has_relationship", value: "Pet"
   - Se mencionar casal/namorado/beijo -> key: "has_relationship", value: "Namorado(a)"
   - Se mencionar filhos/criança -> key: "has_relationship", value: "Filho(a)"
   - Se não tiver restrição, retorne lista vazia.

4. FORMATO DE SAÍDA:
   Retorne APENAS um JSON válido (sem markdown) com a seguinte estrutura:
   {
       "refined_prompt": "Texto do prompt convertido em inglês...",
       "suggested_theme_name": "Nome curto para o tema (ex: Christmas Special)",
       "requirements": [
           {"key": "has_relationship", "value": "Pet", "reason": "Mentioned a dog"}
       ]
   }
EOT;

        // 2. Chamada ao Serviço de IA
        try {
            $messages = [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => "Prompt Original: " . $rawPrompt]
            ];

            // Chama o serviço (Gemini, LmStudio ou Pollination - Texto)
            $aiResponse = $this->aiService->generateText($messages);

            Log::debug("Resposta da IA: " . $aiResponse);
            // Limpeza básica caso a IA retorne ```json ... ```
            $jsonString = preg_replace('/^```json\s*|\s*```$/', '', $aiResponse);
            $data = json_decode($jsonString, true);

            // Fallback se o JSON falhar
            if (!$data) {
                throw new \Exception("Falha ao interpretar resposta da IA.");
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erro na IA: ' . $e->getMessage());
        }

        // 3. Processamento dos Dados para a View
        $processedPrompt = $data['refined_prompt'] ?? $rawPrompt;
        $newThemeName = $data['suggested_theme_name'] ?? 'Novo Tema';
        $suggestedRequirements = $data['requirements'] ?? [];

        // Tenta achar tema existente pelo nome sugerido
        $allThemes = Theme::all();
        $suggestedThemeId = null;

        // Busca simples por similaridade de texto
        foreach ($allThemes as $theme) {
            if (stripos($newThemeName, $theme->name) !== false) {
                $suggestedThemeId = $theme->id;
                break;
            }
        }

        return view('MundosDeMim::admin.import.review', compact(
            'rawPrompt',
            'processedPrompt',
            'suggestedRequirements',
            'allThemes',
            'suggestedThemeId',
            'newThemeName'
        ));
    }

    public function store(Request $request)
    {
        // ... (O método store permanece idêntico ao anterior,
        // pois ele apenas recebe os dados já processados do formulário) ...

        $request->validate([
            'final_prompt' => 'required|string',
            'action_type' => 'required|in:existing_theme,new_theme',
            'theme_id' => 'required_if:action_type,existing_theme',
            'new_theme_name' => 'required_if:action_type,new_theme',
        ]);

        if ($request->action_type === 'new_theme') {
            $theme = Theme::create([
                'name' => $request->new_theme_name,
                'slug' => Str::slug($request->new_theme_name),
                'age_rating' => 'teen',
                'is_seasonal' => false
            ]);
        } else {
            $theme = Theme::findOrFail($request->theme_id);
        }

        $prompt = Prompt::create([
            'theme_id' => $theme->id,
            'prompt_text' => $request->final_prompt
        ]);

        if ($request->has('requirements')) {
            foreach ($request->requirements as $req) {
                if (isset($req['enabled']) && $req['enabled'] == 1) {
                    $prompt->requirements()->create([
                        'requirement_key' => $req['key'],
                        'operator' => '=',
                        'requirement_value' => $req['value']
                    ]);
                }
            }
        }

        return redirect()->route('mundos-de-mim.admin.themes.edit', $theme->id)
            ->with('success', 'Prompt importado via IA com sucesso!');
    }
}
