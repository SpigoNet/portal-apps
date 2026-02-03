<?php
// Configurações de segurança
$token_esperado = 'SEU_TOKEN_AQUI'; // O mesmo que você colocar no GitHub Secrets
$token_recebido = $_GET['token'] ?? '';

if ($token_recebido !== $token_esperado) {
    header('HTTP/1.1 403 Forbidden');
    die("Acesso negado.");
}

// O arquivo está um nível acima da pasta public
$zipFile = __DIR__ . '/../deploy.zip';
$extractTo = __DIR__ . '/../';

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();

    // Tenta remover o zip após extrair para não ocupar espaço
    unlink($zipFile);

    echo "Sucesso: Projeto descompactado na raiz!";
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Erro: Não foi possível abrir o arquivo deploy.zip em: " . $zipFile;
}