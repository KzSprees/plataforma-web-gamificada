<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $nome = (string) ($_POST['nome'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    if (!preg_match('/^[\p{L}\p{N}]+$/u', $nome) || $senha === '') {
        $erro = 'Informe o nome de usuário e a senha.';
    } else {
        $stmt = $conn->prepare('SELECT id, nome, senha_hash FROM usuarios WHERE nome = ? LIMIT 1');
        $stmt->bind_param('s', $nome);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario || !$usuario['senha_hash'] || !password_verify($senha, $usuario['senha_hash'])) {
            $erro = 'Nome de usuário ou senha incorretos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int) $usuario['id'];
            $_SESSION['nome_usuario'] = $usuario['nome'];
            header('Location: index.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-pequeno pagina-formulario">
        <p><a href="inicio.php">← Conheça o projeto</a></p>
        <h2>Login do aluno</h2>
        <p>Entre com seu nome de usuário e sua senha para começar a jogar.</p>
        <?php if ($erro !== ''): ?>
            <p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="nome">Nome de usuário</label>
            <input id="nome" type="text" name="nome" maxlength="100" autocomplete="username" required>
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" autocomplete="current-password" required>
            <button type="submit" class="botao">Entrar</button>
        </form>
        <p><a href="recuperar.php">Esqueci a senha</a></p>
        <p>Ainda não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
    </main>
</body>
</html>

