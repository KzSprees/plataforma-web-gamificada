<?php
require_once 'sessao.php';
require_once 'conexao.php';
require_once 'csrf.php';
require_once 'admin_auth.php';

$erro = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $senha_atual = (string) ($_POST['senha_atual'] ?? '');
    $nova_senha = (string) ($_POST['nova_senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmacao'] ?? '');

    if (strlen($nova_senha) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($nova_senha !== $confirmacao) {
        $erro = 'A confirmação da nova senha não confere.';
    } else {
        $stmt = $conn->prepare('SELECT senha_hash FROM administradores WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $administrador = $stmt->get_result()->fetch_assoc();

        if (!$administrador || !password_verify($senha_atual, $administrador['senha_hash'])) {
            $erro = 'A senha atual está incorreta.';
        } else {
            $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE administradores SET senha_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $novo_hash, $_SESSION['admin_id']);
            $stmt->execute();
            session_regenerate_id(true);
            $mensagem = 'Senha alterada com sucesso.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar senha administrativa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-pequeno pagina-formulario">
        <h2>Trocar senha administrativa</h2>
        <?php if ($erro !== ''): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <?php if ($mensagem !== ''): ?><p class="sucesso"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="senha_atual">Senha atual</label>
            <input id="senha_atual" type="password" name="senha_atual" autocomplete="current-password" required>
            <label for="nova_senha">Nova senha</label>
            <input id="nova_senha" type="password" name="nova_senha" minlength="8" autocomplete="new-password" required>
            <label for="confirmacao">Confirme a nova senha</label>
            <input id="confirmacao" type="password" name="confirmacao" minlength="8" autocomplete="new-password" required>
            <button type="submit" class="botao">Salvar nova senha</button>
        </form>
        <a href="admin.php">Voltar ao painel</a>
    </main>
</body>
</html>

