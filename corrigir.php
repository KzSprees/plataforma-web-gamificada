<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit();
}
validar_csrf();

$id_exercicio = filter_input(INPUT_POST, 'id_exercicio', FILTER_VALIDATE_INT);
$resposta_aluno = strtoupper(trim((string) ($_POST['resposta'] ?? '')));
$id_aluno = (int) $_SESSION['id_usuario'];
$tema_id = filter_input(INPUT_POST, 'tema_id', FILTER_VALIDATE_INT);
$nivel = (string) ($_POST['nivel'] ?? '');
$niveis_validos = ['Basico', 'Intermediario', 'Avancado'];
if (!$tema_id) {
    $tema_id = null;
}
if (!in_array($nivel, $niveis_validos, true)) {
    $nivel = '';
}

if (!$id_exercicio || !in_array($resposta_aluno, ['A', 'B', 'C', 'D'], true)) {
    http_response_code(400);
    exit('Resposta inválida.');
}

$stmt = $conn->prepare('SELECT * FROM exercicios WHERE id = ?');
$stmt->bind_param('i', $id_exercicio);
$stmt->execute();
$exercicio = $stmt->get_result()->fetch_assoc();
if (!$exercicio) {
    http_response_code(404);
    exit('Exercício não encontrado.');
}

$stmt = $conn->prepare('SELECT 1 FROM respostas_usuarios WHERE id_usuario = ? AND id_exercicio = ? LIMIT 1');
$stmt->bind_param('ii', $id_aluno, $id_exercicio);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    exit('Este exercício já foi respondido. <a href="index.php">Voltar aos desafios</a>');
}

$letra_certa = strtoupper($exercicio['resposta_certa']);
$texto_correto = $exercicio['opcao_' . strtolower($letra_certa)];
$acertou = $resposta_aluno === $letra_certa;

$conn->begin_transaction();
try {
    $resultado_acerto = $acertou ? 1 : 0;
    $stmt = $conn->prepare('INSERT INTO respostas_usuarios (id_usuario, id_exercicio, acertou) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $id_aluno, $id_exercicio, $resultado_acerto);
    $stmt->execute();

    if ($acertou) {
        $stmt = $conn->prepare('UPDATE usuarios SET pontos = pontos + 10 WHERE id = ?');
        $stmt->bind_param('i', $id_aluno);
        $stmt->execute();
    }
    $conn->commit();
} catch (Throwable $erro) {
    $conn->rollback();
    throw $erro;
}

$stmt = $conn->prepare('SELECT pontos FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id_aluno);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$pontos_atuais = (int) ($usuario['pontos'] ?? 0);
$resultado = [
    'acertou' => $acertou,
    'pergunta' => $exercicio['pergunta'],
    'opcoes' => [
        'A' => $exercicio['opcao_a'],
        'B' => $exercicio['opcao_b'],
        'C' => $exercicio['opcao_c'],
        'D' => $exercicio['opcao_d']
    ],
    'resposta' => $resposta_aluno,
    'correta' => $letra_certa,
    'texto_correto' => $texto_correto,
    'pontos' => $pontos_atuais
];
$_SESSION['ultimo_resultado'] = $resultado;
$url = 'index.php';
$parametros = [];
if ($tema_id) {
    $parametros['tema_id'] = $tema_id;
}
if ($nivel !== '') {
    $parametros['nivel'] = $nivel;
}
if ($parametros) {
    $url .= '?' . http_build_query($parametros);
}
header('Location: ' . $url);
exit();
