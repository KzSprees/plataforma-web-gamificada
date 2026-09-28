<?php
require_once 'sessao.php';
include 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit();
}

$id_usuario = (int) $_SESSION['id_usuario'];
$stmt = $conn->prepare('SELECT nome, pontos FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    session_unset();
    session_destroy();
    header('Location: inicio.php');
    exit();
}

$stmt = $conn->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(acertou = 1), 0) AS acertos FROM respostas_usuarios WHERE id_usuario = ? AND acertou IS NOT NULL');
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$resumo_respostas = $stmt->get_result()->fetch_assoc();
$total_respondidas = (int) $resumo_respostas['total'];
$acertos = (int) $resumo_respostas['acertos'];
$total_perguntas = (int) $conn->query('SELECT COUNT(*) AS total FROM exercicios')->fetch_assoc()['total'];
$perguntas_restantes = max(0, $total_perguntas - $total_respondidas);
$pontos = (int) $usuario['pontos'];
$aproveitamento = $total_respondidas > 0
    ? round(($acertos / $total_respondidas) * 100)
    : 0;

$stmt = $conn->prepare(
    'SELECT t.nome AS tema,
            COUNT(*) AS respondidas,
            SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) AS acertos,
            SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) AS erros,
            SUM(CASE WHEN r.acertou IS NULL THEN 1 ELSE 0 END) AS nao_classificadas
     FROM respostas_usuarios r
     INNER JOIN exercicios e ON e.id = r.id_exercicio
     INNER JOIN temas t ON t.id = e.tema_id
     WHERE r.id_usuario = ? AND r.acertou IS NOT NULL
     GROUP BY t.nome, e.nivel
     ORDER BY t.nome, e.nivel'
);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$detalhes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare(
    'SELECT e.nivel,
            COUNT(*) AS respondidas,
            SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) AS acertos,
            SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) AS erros,
            SUM(CASE WHEN r.acertou IS NULL THEN 1 ELSE 0 END) AS nao_classificadas
     FROM respostas_usuarios r
     INNER JOIN exercicios e ON e.id = r.id_exercicio
     WHERE r.id_usuario = ? AND r.acertou IS NOT NULL
     GROUP BY e.nivel
     ORDER BY FIELD(e.nivel, "Basico", "Intermediario", "Avancado")'
);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$por_nivel = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$melhor_tema = null;
foreach ($detalhes as $detalhe) {
    $classificadas = (int) $detalhe['acertos'] + (int) $detalhe['erros'];
    $percentual = $classificadas > 0 ? ((int) $detalhe['acertos'] / $classificadas) * 100 : -1;
    if ($percentual >= 0 && ($melhor_tema === null || $percentual > $melhor_tema['percentual'])) {
        $melhor_tema = ['nome' => $detalhe['tema'], 'percentual' => $percentual];
    }
}

$nomes_niveis = [
    'Basico' => 'Básico',
    'Intermediario' => 'Intermediário',
    'Avancado' => 'Avançado'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu desempenho - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao desempenho">
        <p><a href="index.php">← Voltar aos desafios</a></p>
        <h2>Meu desempenho</h2>
        <p>Olá, <strong><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>! Aqui está o seu progresso.</p>

        <section class="resumo" aria-label="Resumo do desempenho">
            <div class="indicador"><strong><?php echo $pontos; ?></strong><span>Pontos</span></div>
            <div class="indicador"><strong><?php echo $total_respondidas; ?></strong><span>Perguntas respondidas</span></div>
            <div class="indicador"><strong><?php echo $aproveitamento; ?>%</strong><span>Aproveitamento</span></div>
            <div class="indicador"><strong><?php echo $perguntas_restantes; ?></strong><span>Perguntas restantes</span></div>
        </section>

        <p><strong>Acertos estimados:</strong> <?php echo $acertos; ?> de <?php echo $total_respondidas; ?> perguntas.</p>
        <div class="barra" role="progressbar" aria-valuenow="<?php echo $aproveitamento; ?>" aria-valuemin="0" aria-valuemax="100">
            <span style="width: <?php echo min(100, $aproveitamento); ?>%;"></span>
        </div>

        <?php if ($melhor_tema !== null): ?>
            <div class="melhor-tema"><strong>Melhor desempenho:</strong> <?php echo htmlspecialchars($melhor_tema['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo round($melhor_tema['percentual']); ?>% de acertos)</div>
        <?php endif; ?>

        <h3 class="subtitulo-estatistica">Acertos e erros por tema</h3>
        <?php if (count($detalhes) > 0): ?>
            <div class="tabela-padrao"><table>
                <thead><tr><th>Tema</th><th>Respondidas</th><th>Acertos</th><th>Erros</th><th>Aproveitamento</th></tr></thead>
                <tbody>
                <?php foreach ($detalhes as $detalhe): ?>
                    <?php $classificadas = (int) $detalhe['acertos'] + (int) $detalhe['erros']; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detalhe['tema'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $detalhe['respondidas']; ?></td>
                        <td><?php echo (int) $detalhe['acertos']; ?></td>
                        <td><?php echo (int) $detalhe['erros']; ?></td>
                        <td><?php echo $classificadas > 0 ? round(((int) $detalhe['acertos'] / $classificadas) * 100) . '%' : 'N/D'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p>Você ainda não respondeu nenhuma pergunta. Comece um desafio para acompanhar seu progresso.</p>
        <?php endif; ?>

        <h3 class="subtitulo-estatistica">Aproveitamento por nível</h3>
        <?php if (count($por_nivel) > 0): ?>
            <div class="tabela-padrao"><table>
                <thead><tr><th>Nível</th><th>Respondidas</th><th>Acertos</th><th>Erros</th><th>Percentual</th></tr></thead>
                <tbody>
                <?php foreach ($por_nivel as $nivel): ?>
                    <?php $classificadas_nivel = (int) $nivel['acertos'] + (int) $nivel['erros']; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($nomes_niveis[$nivel['nivel']] ?? $nivel['nivel'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $nivel['respondidas']; ?></td>
                        <td><?php echo (int) $nivel['acertos']; ?></td>
                        <td><?php echo (int) $nivel['erros']; ?></td>
                        <td><?php echo $classificadas_nivel > 0 ? round(((int) $nivel['acertos'] / $classificadas_nivel) * 100) . '%' : 'N/D'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>

        <div class="acoes-pagina">
            <a href="index.php" class="botao">Continuar praticando</a>
            <a href="ranking.php" class="botao">Ver ranking</a>
            <a href="sair.php" class="botao">Sair da conta</a>
        </div>
    </main>
</body>
</html>

