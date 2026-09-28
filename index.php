<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit();
}

$id_aluno = (int) $_SESSION['id_usuario'];
$ultimo_resultado = $_SESSION['ultimo_resultado'] ?? null;
unset($_SESSION['ultimo_resultado']);
$temas = $conn->query('SELECT id, nome, nivel FROM temas ORDER BY nome, nivel');
$tema_id = filter_input(INPUT_GET, 'tema_id', FILTER_VALIDATE_INT);
if (!$tema_id) {
    $tema_id = null;
}
$nivel = (string) ($_GET['nivel'] ?? '');
$niveis_validos = ['Basico', 'Intermediario', 'Avancado'];
$nomes_niveis = [
    'Basico' => 'Básico',
    'Intermediario' => 'Intermediário',
    'Avancado' => 'Avançado'
];
if (!in_array($nivel, $niveis_validos, true)) {
    $nivel = '';
}
$tema_filtro = $tema_id ?? 0;
$filtro_chave = $tema_filtro . '|' . $nivel;
$puladas = $_SESSION['questoes_puladas'][$filtro_chave] ?? [];
$puladas = array_values(array_filter(array_map('intval', $puladas), static function (int $id): bool {
    return $id > 0;
}));

$sql_exercicios = 'SELECT e.* FROM exercicios e
    WHERE NOT EXISTS (
        SELECT 1 FROM respostas_usuarios r
        WHERE r.id_exercicio = e.id AND r.id_usuario = ?
        ) AND (? = 0 OR e.tema_id = ?)
        AND (? = "" OR e.nivel = ?)';
$parametros = [$id_aluno, $tema_filtro, $tema_filtro, $nivel, $nivel];
$tipos = 'iiiss';
if ($puladas) {
    $sql_exercicios .= ' AND e.id NOT IN (' . implode(',', array_fill(0, count($puladas), '?')) . ')';
    $tipos .= str_repeat('i', count($puladas));
    $parametros = array_merge($parametros, $puladas);
}
$sql_exercicios .= ' ORDER BY RAND() LIMIT 1';
$stmt = $conn->prepare($sql_exercicios);
$bind = [$tipos];
foreach ($parametros as $indice => $valor) {
    $bind[] = &$parametros[$indice];
}
call_user_func_array([$stmt, 'bind_param'], $bind);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0 && $puladas) {
    unset($_SESSION['questoes_puladas'][$filtro_chave]);
    $stmt = $conn->prepare(
        'SELECT e.* FROM exercicios e
         WHERE NOT EXISTS (
             SELECT 1 FROM respostas_usuarios r
             WHERE r.id_exercicio = e.id AND r.id_usuario = ?
         ) AND (? = 0 OR e.tema_id = ?)
           AND (? = "" OR e.nivel = ?)
         ORDER BY RAND() LIMIT 1'
    );
    $stmt->bind_param('iiiss', $id_aluno, $tema_filtro, $tema_filtro, $nivel, $nivel);
    $stmt->execute();
    $resultado = $stmt->get_result();
}
$sem_exercicios = $resultado->num_rows === 0;
$exercicio = null;
$id_exercicio = 0;
$opcoes = [];
$letras = [];

if (!$sem_exercicios) {
    $exercicio = $resultado->fetch_assoc();
    $id_exercicio = (int) $exercicio['id'];
    $opcoes = [
        'A' => $exercicio['opcao_a'],
        'B' => $exercicio['opcao_b'],
        'C' => $exercicio['opcao_c'],
        'D' => $exercicio['opcao_d']
    ];
    $letras = array_keys($opcoes);
    shuffle($letras);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Desafios - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao questao-pagina">
        <?php if (is_array($ultimo_resultado)): ?>
            <section class="feedback-resposta <?php echo $ultimo_resultado['acertou'] ? 'feedback-correto' : 'feedback-incorreto'; ?>" aria-live="polite">
                <div class="feedback-cabecalho">
                    <span class="feedback-icone"><?php echo $ultimo_resultado['acertou'] ? '✓' : '×'; ?></span>
                    <div>
                        <strong><?php echo $ultimo_resultado['acertou'] ? 'Resposta correta' : 'Resposta incorreta'; ?></strong>
                        <p><?php echo $ultimo_resultado['acertou'] ? 'Parabéns! Você selecionou a alternativa correta.' : 'A alternativa correta era ' . htmlspecialchars($ultimo_resultado['correta'], ENT_QUOTES, 'UTF-8') . '.'; ?></p>
                    </div>
                </div>
                <div class="feedback-opcoes">
                    <?php foreach ($ultimo_resultado['opcoes'] as $letra => $texto): ?>
                        <div class="feedback-opcao <?php echo $letra === $ultimo_resultado['correta'] ? 'feedback-opcao-correta' : ($letra === $ultimo_resultado['resposta'] ? 'feedback-opcao-escolhida' : ''); ?>">
                            <span class="feedback-letra"><?php echo $letra; ?></span>
                            <span><?php echo htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="gabarito-comentado">
                    <strong>Gabarito comentado</strong>
                    <p>A alternativa <?php echo htmlspecialchars($ultimo_resultado['correta'], ENT_QUOTES, 'UTF-8'); ?> é a correta porque corresponde diretamente ao conceito solicitado no enunciado. As demais opções não atendem ao que a questão pede.</p>
                </div>
            </section>
        <?php endif; ?>
        <form method="GET" class="filtros-desafio">
            <div class="filtro-campo">
                <label for="tema_id">Tema de estudo</label>
                <select id="tema_id" name="tema_id" onchange="this.form.submit()">
                    <option value="">Todos os temas</option>
                    <?php while ($tema = $temas->fetch_assoc()): ?>
                        <option value="<?php echo (int) $tema['id']; ?>" <?php echo $tema_id === (int) $tema['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tema['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filtro-campo">
                <label for="nivel">Nível de dificuldade</label>
                <select id="nivel" name="nivel" onchange="this.form.submit()">
                    <option value="">Todos os níveis</option>
                    <?php foreach ($niveis_validos as $nivel_opcao): ?>
                        <option value="<?php echo $nivel_opcao; ?>" <?php echo $nivel === $nivel_opcao ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars('Nível ' . $nomes_niveis[$nivel_opcao], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if ($sem_exercicios): ?>
            <h2>Parabéns! 🏆</h2>
            <p>Completaste todos os desafios disponíveis.</p>
        <?php else: ?>
            <header class="questao-cabecalho"><span class="admin-etiqueta">Desafio rápido</span><h2>Desafio de lógica</h2></header>
            <p class="questao-enunciado"><strong><?php echo htmlspecialchars($exercicio['pergunta'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <form action="corrigir.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_exercicio" value="<?php echo $id_exercicio; ?>">
                <input type="hidden" name="tema_id" value="<?php echo $tema_id ? (int) $tema_id : ''; ?>">
                <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="opcoes-questao"><?php foreach ($letras as $letra): ?>
                    <button type="submit" name="resposta" value="<?php echo $letra; ?>" class="opcao-questao">
                        <?php echo $letra . ') ' . htmlspecialchars($opcoes[$letra], ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endforeach; ?></div>
            </form>
            <form action="pular.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_exercicio" value="<?php echo (int) $exercicio['id']; ?>">
                <input type="hidden" name="tema_id" value="<?php echo $tema_id ? (int) $tema_id : ''; ?>">
                <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="botao botao-pular">Pular esta questão</button>
            </form>
        <?php endif; ?>
        <nav class="navegacao-pagina"><a href="desempenho.php">Meu desempenho</a><a href="ranking.php">Ver ranking</a><a href="sair.php">Sair da conta</a></nav>
    </main>
</body>
</html>
