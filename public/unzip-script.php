<?php
// Configurações de segurança
$token_esperado = 'S0yTn7VRDbiCUZPYdWoo';
$token_recebido = $_GET['token'] ?? '';

if ($token_recebido !== $token_esperado) {
    header('HTTP/1.1 403 Forbidden');
    die("Acesso negado.");
}

// Caminhos (ajustados para a raiz do projeto)
$rootPath = realpath(__DIR__ . '/../');
$zipFile = $rootPath . '/deploy.zip';

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    // 1. Extrair arquivos
    $zip->extractTo($rootPath);
    $zip->close();
    unlink($zipFile); // Remove o zip

    // 2. Rodar Migrations e Limpar Cache
    // Mudamos para o diretório raiz para o comando PHP encontrar o 'artisan'
    chdir($rootPath);

    // Executamos o migrate e o clear-cache para garantir que as novas rotas/configs subam
    // O 2>&1 no final serve para capturar erros de log se algo falhar
    $output = shell_exec('php artisan migrate --force 2>&1');
    $cacheOutput = shell_exec('php artisan optimize 2>&1');

    echo "### Deploy Finalizado com Sucesso! ###\n";
    echo "--- Migrations ---\n" . $output;
    echo "\n--- Otimização ---\n" . $cacheOutput;

} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo "Erro: Não foi possível abrir o arquivo deploy.zip em: " . $zipFile;
}