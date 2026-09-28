<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';
require_once 'admin_auth.php';


$id_exercicio = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_exercicio) {
    http_response_code(400);
    exit('Exercício inválido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $pergunta = trim((string) ($_POST['pergunta'] ?? ''));
    $opcoes = [];
    foreach (['a', 'b', 'c', 'd'] as $letra) {
        $opcoes[$letra] = trim((string) ($_POST['opcao_' . $letra] ?? ''));
    }
    $certa = strtoupper((string) ($_POST['resposta_certa'] ?? ''));

    if ($pergunta === '' || in_array('', $opcoes, true) || !in_array($certa, ['A', 'B', 'C', 'D'], true)) {
        $erro = 'Preencha todos os campos corretamente.';
    } else {
        $stmt = $conn->prepare(
            'UPDATE exercicios SET pergunta = ?, opcao_a = ?, opcao_b = ?, opcao_c = ?, opcao_d = ?, resposta_certa = ? WHERE id = ?'
        );
        $stmt->bind_param('ssssssi', $pergunta, $opcoes['a'], $opcoes['b'], $opcoes['c'], $opcoes['d'], $certa, $id_exercicio);
        $stmt->execute();
        $mensagem = 'Pergunta atualizada com sucesso!';
    }
}

$stmt = $conn->prepare('SELECT * FROM exercicios WHERE id = ?');
$stmt->bind_param('i', $id_exercicio);
$stmt->execute();
$exercicio = $stmt->get_result()->fetch_assoc();
if (!$exercicio) {
    http_response_code(404);
    exit('Exercício não encontrado.');
}
function escapar_edicao(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Pergunta - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao cartao-medio pagina-formulario">
        <h2>Editar Pergunta ✍️</h2>
        <?php if (isset($erro)): ?><p class="erro"><?php echo escapar_edicao($erro); ?></p><?php endif; ?>
        <?php if (isset($mensagem)): ?><p class="sucesso"><?php echo escapar_edicao($mensagem); ?></p><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="pergunta">Enunciado</label>
            <textarea id="pergunta" name="pergunta" rows="3" required><?php echo escapar_edicao($exercicio['pergunta']); ?></textarea>
            <input type="text" name="opcao_a" value="<?php echo escapar_edicao($exercicio['opcao_a']); ?>" required>
            <input type="text" name="opcao_b" value="<?php echo escapar_edicao($exercicio['opcao_b']); ?>" required>
            <input type="text" name="opcao_c" value="<?php echo escapar_edicao($exercicio['opcao_c']); ?>" required>
            <input type="text" name="opcao_d" value="<?php echo escapar_edicao($exercicio['opcao_d']); ?>" required>
            <select name="resposta_certa">
                <?php foreach (['A', 'B', 'C', 'D'] as $letra): ?>
                    <option value="<?php echo $letra; ?>" <?php echo $exercicio['resposta_certa'] === $letra ? 'selected' : ''; ?>>Resposta Certa: <?php echo $letra; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="botao">Salvar Alterações</button>
        </form>
        <a href="admin.php" class="voltar">⬅ Voltar ao Painel</a>
    </main>
</body>
</html>
