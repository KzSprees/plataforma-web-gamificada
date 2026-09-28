<?php
require_once 'sessao.php';
require_once 'conexao.php';
require_once 'csrf.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = $conn->prepare('SELECT id, usuario, senha_hash FROM administradores WHERE usuario = ? LIMIT 1');
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $administrador = $stmt->get_result()->fetch_assoc();

    if ($administrador && password_verify($senha, $administrador['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $administrador['id'];
        $_SESSION['admin_usuario'] = $administrador['usuario'];
        header('Location: admin.php');
        exit();
    }

    $erro = 'Usuário ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login administrativo</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-pequeno pagina-formulario">
        <h2>Acesso administrativo</h2>
        <?php if ($erro !== ''): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="usuario">Usuário</label>
            <input id="usuario" type="text" name="usuario" autocomplete="username" required>
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" autocomplete="current-password" required>
            <button type="submit" class="botao">Entrar</button>
        </form>
        <a href="inicio.php">Voltar ao início</a>
    </main>
</body>
</html>

