<?php
require_once 'sessao.php';
require_once 'csrf.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Capivaras Code - Início</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-pequeno pagina-formulario">
        <p><a href="inicio.php">← Conheça o projeto</a></p>
        <h2>Cadastro de novo aluno</h2>
        <?php if (isset($_GET['erro'])): ?>
            <p class="erro"><?php echo htmlspecialchars((string) $_GET['erro'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <p>Cria o teu usuário para começarmos a somar os teus pontos.</p>
        <form action="iniciar.php" method="POST">
            <?php echo csrf_field(); ?>
            <label for="nome">Nome de usuário</label>
            <input id="nome" type="text" name="nome" maxlength="100" pattern="[A-Za-zÀ-ÿ0-9]+" autocomplete="username" placeholder="Ex.: capivara123" required>
            <small class="texto-ajuda">Use apenas letras e números, sem espaços ou caracteres especiais.</small>
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" minlength="6" autocomplete="new-password" placeholder="Mínimo de 6 caracteres" required>
            <label for="pergunta_seguranca">Pergunta de segurança</label>
            <select id="pergunta_seguranca" name="pergunta_seguranca" required>
                <option value="">Escolha uma pergunta</option>
                <option value="cor">Qual é a sua cor preferida?</option>
                <option value="nascimento">Em que ano você nasceu?</option>
                <option value="animal">Qual é o seu animal favorito?</option>
            </select>
            <label for="resposta_seguranca">Resposta de segurança</label>
            <input id="resposta_seguranca" type="text" name="resposta_seguranca" maxlength="100" autocomplete="off" placeholder="Digite sua resposta" required>
            <small class="texto-ajuda">Guarde essa resposta. Ela será usada se você esquecer a senha.</small>
            <button type="submit" class="botao">Criar conta e começar</button>
        </form>
        <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
        <p><a href="recuperar.php">Esqueci a senha</a></p>
    </main>
</body>
</html>
