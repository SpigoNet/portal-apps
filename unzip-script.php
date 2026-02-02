<?php
// Exemplo básico de unzip-script.php
$token = $_GET['token'] ?? '';
if ($token !== 'S0yTn7VRDbiCUZPYdWoo') {
    die('Acesso negado');
}

$zipFile = 'deploy.zip';
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo('./');
    $zip->close();
    echo "Sucesso!";
    unlink($zipFile); // Deleta o zip após extrair
} else {
    echo "Erro ao extrair";
}