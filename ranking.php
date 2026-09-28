<?php
require_once 'sessao.php';
include 'conexao.php';

$resultado = $conn->query('SELECT nome, pontos FROM usuarios ORDER BY pontos DESC, nome ASC LIMIT 10');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ranking - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao ranking-pagina">
        <h2>🏆 Top 10 - Melhores Programadores</h2>
        <p>Confere quem está a dominar os desafios!</p>
        <?php if ($resultado->num_rows === 0): ?>
            <p>Ainda não há alunos no ranking.</p>
        <?php else: ?>
            <div class="tabela-padrao"><table>
                <tr><th>Posição</th><th>Nome</th><th>Pontos</th></tr>
                <?php $posicao = 1; ?>
                <?php while ($linha = $resultado->fetch_assoc()): ?>
                    <?php $icone = $posicao === 1 ? '🥇' : ($posicao === 2 ? '🥈' : ($posicao === 3 ? '🥉' : '🏅')); ?>
                    <tr>
                        <td><span class="medalha"><?php echo $icone; ?></span> <?php echo $posicao; ?>º</td>
                        <td><?php echo htmlspecialchars($linha['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo (int) $linha['pontos']; ?></strong></td>
                    </tr>
                    <?php $posicao++; ?>
                <?php endwhile; ?>
            </table></div>
        <?php endif; ?>
        <?php if (!isset($_SESSION['id_usuario'])): ?>
            <p>Quer participar do ranking?</p>
            <div class="ranking-acoes"><a href="cadastro.php" class="botao">Cadastrar novo aluno</a>
            <a href="login.php" class="botao">Fazer login</a></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['id_usuario'])): ?>
            <div class="ranking-acoes"><a href="desempenho.php" class="botao">Meu desempenho</a>
            <a href="sair.php" class="botao">Sair da conta</a>
            <a href="index.php" class="botao">Voltar aos desafios</a></div>
        <?php endif; ?>
    </main>
</body>
</html>
