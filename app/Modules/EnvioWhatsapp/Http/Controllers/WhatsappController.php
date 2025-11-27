<?php

namespace App\Modules\EnvioWhatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WhatsappController extends Controller
{
    /**
     * Passo 1: Exibe formulário de Upload.
     */
    public function step1()
    {
        return view('EnvioWhatsapp::step1');
    }

    /**
     * Passo 2: Recebe o CSV, lê os cabeçalhos e pede configuração.
     */
    public function step2(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        // Salva o arquivo na pasta storage/app/temp_whatsapp
        $path = $request->file('csv')->store('temp_whatsapp');

        // CORREÇÃO: Usa o Storage facade para pegar o caminho absoluto correto do SO
        $fullPath = Storage::path($path);

        // Tenta ler a primeira linha para pegar os cabeçalhos
        $headers = [];
        $preview = [];

        if (file_exists($fullPath) && ($handle = fopen($fullPath, "r")) !== FALSE) {
            // Lê a primeira linha (cabeçalhos)
            $data = fgetcsv($handle, 0, ',');
            if ($data) {
                $headers = $data;
            }
            // Lê a segunda linha (preview de dados) para ajudar o usuário
            $previewData = fgetcsv($handle, 0, ',');
            if ($previewData) {
                $preview = $previewData;
            }
            fclose($handle);
        } else {
            return back()->withErrors(['csv' => 'Erro ao salvar ou ler o arquivo. Verifique permissões.']);
        }

        if (empty($headers)) {
            return back()->withErrors(['csv' => 'O arquivo parece estar vazio ou inválido.']);
        }

        return view('EnvioWhatsapp::step2', compact('headers', 'preview', 'path'));
    }

    /**
     * Passo 3: Processa o arquivo e gera a lista.
     */
    public function step3(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
            'coluna_telefone' => 'required|integer',
            'msg' => 'required|string',
        ]);

        $path = $request->input('file_path');

        // CORREÇÃO: Verifica se o arquivo existe no disco do Laravel antes de pegar o caminho
        if (!Storage::exists($path)) {
            return redirect()->route('envio-whatsapp.index')
                ->withErrors(['arquivo' => 'O arquivo expirou ou não existe. Envie novamente.']);
        }

        // Pega o caminho absoluto correto
        $fullPath = Storage::path($path);

        $colTelefoneIdx = $request->input('coluna_telefone');
        $mensagemOriginal = $request->input('msg');

        $resultados = [];
        $erros = [];
        $headers = [];

        if (($handle = fopen($fullPath, "r")) !== FALSE) {

            // 1. Ler Cabeçalhos (Linha 0)
            $headers = fgetcsv($handle, 0, ',');

            // Validação se a coluna escolhida ainda existe
            if (!isset($headers[$colTelefoneIdx])) {
                fclose($handle);
                return back()->withErrors(['geral' => 'A coluna selecionada não existe no arquivo.']);
            }

            // 2. Ler o restante das linhas
            while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {

                // Pula linhas vazias ou incompletas
                if (count($row) < count($headers)) {
                    continue;
                }

                // Processa o telefone
                $telefoneRaw = $row[$colTelefoneIdx];
                $telefone = str_replace([' ', '-', '(', ')', '+', '\'', '.'], "", $telefoneRaw);

                // Processa a Mensagem
                $msgFinal = $mensagemOriginal;

                // Substitui variáveis
                foreach ($headers as $index => $colName) {
                    $valor = $row[$index] ?? '';
                    $msgFinal = str_ireplace("%".trim($colName)."%", $valor, $msgFinal);
                }

                $msgFinal = str_replace(["\r\n", "\r", "\n"], "%0A", $msgFinal);

                // Regras de validação do número
                $link = null;
                $displayTel = $telefone;
                $valido = false;

                if (strlen($telefone) >= 10 && strlen($telefone) <= 11) {
                    $link = "https://api.whatsapp.com/send?phone=55{$telefone}&text={$msgFinal}";
                    $displayTel = "+55 " . $telefone;
                    $valido = true;
                }
                elseif (strlen($telefone) > 11 && is_numeric($telefone)) {
                    $link = "https://api.whatsapp.com/send?phone={$telefone}&text={$msgFinal}";
                    $displayTel = "+" . $telefone;
                    $valido = true;
                }

                if ($valido) {
                    $uniqueId = md5($telefone . $msgFinal);
                    $resultados[] = [
                        'id' => $uniqueId,
                        'dados' => array_combine($headers, $row),
                        'telefone' => $displayTel,
                        'link' => $link
                    ];
                } else {
                    $erros[] = "Tel. Inválido: " . $telefoneRaw;
                }
            }
            fclose($handle);
        }

        // Remove o arquivo temporário
        Storage::delete($path);

        return view('EnvioWhatsapp::step3', compact('resultados', 'erros'));
    }
}
