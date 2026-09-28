<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';

$perguntas_validas = [
    'cor' => 'Qual é a sua cor preferida?',
    'nascimento' => 'Em que ano você nasceu?',
    'animal' => 'Qual é o seu animal favorito?'
];
$erro = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $agora = time();
    $janela = 15 * 60;
    $tentativas = $_SESSION['recuperacao_tentativas'] ?? ['inicio' => $agora, 'quantidade' => 0];
    if (($agora - (int) $tentativas['inicio']) >= $janela) {
        $tentativas = ['inicio' => $agora, 'quantidade' => 0];
    }
    $tentativas['quantidade']++;
    $_SESSION['recuperacao_tentativas'] = $tentativas;

    if ($tentativas['quantidade'] > 5) {
        $erro = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    }

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $pergunta = (string) ($_POST['pergunta_seguranca'] ?? '');
    $resposta = trim((string) ($_POST['resposta_seguranca'] ?? ''));
    $nova_senha = (string) ($_POST['nova_senha'] ?? '');

    if ($erro === '' && ($nome === '' || !preg_match('/^[\p{L}\p{N}]+$/u', $nome) || !isset($perguntas_validas[$pergunta]) || $resposta === '' || mb_strlen($nova_senha) < 8)) {
        $erro = 'Preencha os dados corretamente. A nova senha precisa ter pelo menos 8 caracteres.';
    } elseif ($erro === '') {
        $stmt = $conn->prepare('SELECT id, pergunta_seguranca, resposta_seguranca_hash FROM usuarios WHERE nome = ? LIMIT 1');
        $stmt->bind_param('s', $nome);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $resposta_normalizada = mb_strtolower($resposta, 'UTF-8');

        if (!$usuario || $usuario['pergunta_seguranca'] !== $perguntas_validas[$pergunta] || !$usuario['resposta_seguranca_hash'] || !password_verify($resposta_normalizada, $usuario['resposta_seguranca_hash'])) {
            $erro = 'Os dados informados não conferem. Confira o usuário, a pergunta e a resposta.';
        } else {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $senha_hash, $usuario['id']);
            $stmt->execute();
            $_SESSION = [];
            session_regenerate_id(true);
            $mensagem = 'Senha alterada com sucesso! Agora você já pode voltar a jogar.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-medio pagina-formulario">
        <p><a href="cadastro.php">← Voltar ao cadastro</a></p>
        <h2>Esqueci a senha</h2>
        <p>Responda à pergunta escolhida no cadastro para criar uma nova senha.</p>
        <?php if ($erro !== ''): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <?php if ($mensagem !== ''): ?><p class="sucesso"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="nome">Nome de usuário</label>
            <input id="nome" type="text" name="nome" maxlength="100" autocomplete="username" required>
            <label for="pergunta_seguranca">Pergunta de segurança</label>
            <select id="pergunta_seguranca" name="pergunta_seguranca" required>
                <option value="">Escolha a pergunta usada no cadastro</option>
                <option value="cor">Qual é a sua cor preferida?</option>
                <option value="nascimento">Em que ano você nasceu?</option>
                <option value="animal">Qual é o seu animal favorito?</option>
            </select>
            <label for="resposta_seguranca">Resposta</label>
            <input id="resposta_seguranca" type="text" name="resposta_seguranca" autocomplete="off" required>
            <label for="nova_senha">Nova senha</label>
            <input id="nova_senha" type="password" name="nova_senha" minlength="8" autocomplete="new-password" required>
            <button type="submit" class="botao">Alterar senha</button>
        </form>
    </main>
</body>
</html>
