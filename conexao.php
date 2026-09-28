<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$configuracao_banco = getenv('CAPIVARAS_DB_CONFIG');
if (!is_string($configuracao_banco) || $configuracao_banco === '') {
    $configuracao_banco = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure' . DIRECTORY_SEPARATOR . 'capivaras-db.php';
}

if (is_readable($configuracao_banco)) {
    $config = require $configuracao_banco;
} else {
    $config = null;
    $configuracao_publicada = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    if (is_readable($configuracao_publicada)) {
        $config = require $configuracao_publicada;
    }

    if (!is_array($config)) {
        $config = [
            'host' => getenv('CAPIVARAS_DB_HOST'),
            'usuario' => getenv('CAPIVARAS_DB_USER'),
            'senha' => getenv('CAPIVARAS_DB_PASSWORD'),
            'banco' => getenv('CAPIVARAS_DB_NAME'),
        ];
    }
}

foreach (['host', 'usuario', 'senha', 'banco'] as $chave) {
    if (!array_key_exists($chave, $config) || !is_string($config[$chave]) || trim($config[$chave]) === '') {
        throw new RuntimeException('Configuração do banco inválida.');
    }
}

$conn = new mysqli($config['host'], $config['usuario'], $config['senha'], $config['banco']);
$conn->set_charset('utf8mb4');
