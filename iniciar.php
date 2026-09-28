<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit();
}
validar_csrf();

$nome_recebido = (string) ($_POST['nome'] ?? '');
$nome = trim($nome_recebido);
$senha = (string) ($_POST['senha'] ?? '');
$pergunta_seguranca = (string) ($_POST['pergunta_seguranca'] ?? '');
$resposta_seguranca = trim((string) ($_POST['resposta_seguranca'] ?? ''));
$perguntas_validas = [
    'cor' => 'Qual é a sua cor preferida?',
    'nascimento' => 'Em que ano você nasceu?',
    'animal' => 'Qual é o seu animal favorito?'
];

if ($nome === '' || $nome !== $nome_recebido || mb_strlen($nome) > 100 || !preg_match('/^[\p{L}\p{N}]+$/u', $nome)) {
    header('Location: cadastro.php?erro=' . rawurlencode('Informe um nome de usuário válido, usando apenas letras e números, sem espaços ou caracteres especiais.'));
    exit();
}

if (mb_strlen($senha) < 6) {
    header('Location: cadastro.php?erro=' . rawurlencode('A senha precisa ter pelo menos 6 caracteres.'));
    exit();
}

if (!isset($perguntas_validas[$pergunta_seguranca]) || $resposta_seguranca === '') {
    header('Location: cadastro.php?erro=' . rawurlencode('Escolha uma pergunta de segurança e informe a resposta.'));
    exit();
}

$stmt = $conn->prepare('SELECT id FROM usuarios WHERE nome = ? LIMIT 1');
$stmt->bind_param('s', $nome);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: cadastro.php?erro=' . rawurlencode('Este nome de usuário já está em uso. Escolha outro.'));
    exit();
}

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$resposta_seguranca_hash = password_hash(mb_strtolower($resposta_seguranca, 'UTF-8'), PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO usuarios (nome, senha_hash, pergunta_seguranca, resposta_seguranca_hash, pontos) VALUES (?, ?, ?, ?, 0)');
$stmt->bind_param('ssss', $nome, $senha_hash, $perguntas_validas[$pergunta_seguranca], $resposta_seguranca_hash);
try {
    $stmt->execute();
} catch (mysqli_sql_exception $erro) {
    if ($erro->getCode() === 1062) {
        header('Location: cadastro.php?erro=' . rawurlencode('Este nome de usuário já está em uso. Escolha outro.'));
        exit();
    }
    throw $erro;
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = $stmt->insert_id;
$_SESSION['nome_usuario'] = $nome;

header('Location: index.php');
exit();

