<?php
require_once 'sessao.php';
include 'conexao.php';
require_once 'csrf.php';
require_once 'admin_auth.php';

if (isset($_POST['nova_pergunta'])) {
    validar_csrf();
    $pergunta = trim((string) ($_POST['pergunta'] ?? ''));
    $opcoes = [];
    foreach (['a', 'b', 'c', 'd'] as $letra) {
        $opcoes[$letra] = trim((string) ($_POST['opcao_' . $letra] ?? ''));
    }
    $certa = strtoupper((string) ($_POST['resposta_certa'] ?? ''));
    $tema_id = filter_input(INPUT_POST, 'tema_id', FILTER_VALIDATE_INT);
    $nivel = (string) ($_POST['nivel'] ?? 'Basico');

    if ($pergunta === '' || in_array('', $opcoes, true) || !in_array($certa, ['A', 'B', 'C', 'D'], true) || !$tema_id || !in_array($nivel, ['Basico', 'Intermediario', 'Avancado'], true)) {
        $erro = 'Preencha a pergunta, as quatro opções e selecione uma resposta correta.';
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO exercicios (tema_id, nivel, pergunta, opcao_a, opcao_b, opcao_c, opcao_d, resposta_certa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssssss', $tema_id, $nivel, $pergunta, $opcoes['a'], $opcoes['b'], $opcoes['c'], $opcoes['d'], $certa);
        $stmt->execute();
        $mensagem_sucesso = 'Pergunta adicionada com sucesso!';
    }
}

$resultado_lista = null;
$resultado_usuarios = null;
$filtro_aplicado = false;
if (!empty($_SESSION['admin_id'])) {
    $resultado_temas = $conn->query('SELECT id, nome, nivel FROM temas ORDER BY nome, nivel');
    $temas = $resultado_temas->fetch_all(MYSQLI_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_usuario'])) {
        validar_csrf();
        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        if (!$id_usuario) {
            $erro = 'Usuário inválido.';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare('DELETE FROM respostas_usuarios WHERE id_usuario = ?');
                $stmt->bind_param('i', $id_usuario);
                $stmt->execute();

                $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
                $stmt->bind_param('i', $id_usuario);
                $stmt->execute();
                $conn->commit();

                if ($stmt->affected_rows === 0) {
                    $erro = 'Usuário não encontrado.';
                } else {
                    $mensagem_sucesso = 'Usuário excluído com sucesso.';
                }
            } catch (Throwable $erro_exclusao) {
                $conn->rollback();
                throw $erro_exclusao;
            }
        }
    }

    $filtro_tema = filter_input(INPUT_GET, 'tema_id', FILTER_VALIDATE_INT);
    $filtro_nivel = (string) ($_GET['nivel'] ?? '');
    $filtro_aplicado = array_key_exists('tema_id', $_GET) || array_key_exists('nivel', $_GET);
    $niveis_validos = ['Basico', 'Intermediario', 'Avancado'];
    if (!$filtro_tema) {
        $filtro_tema = 0;
    }
    if (!in_array($filtro_nivel, $niveis_validos, true)) {
        $filtro_nivel = '';
    }

    if ($filtro_aplicado) {
        $sql_perguntas = 'SELECT e.id, e.pergunta, t.nome AS tema, e.nivel
                        FROM exercicios e
                        LEFT JOIN temas t ON t.id = e.tema_id
                        WHERE (? = 0 OR e.tema_id = ?)
                            AND (? = "" OR e.nivel = ?)
                        ORDER BY e.id DESC';
        $stmt_perguntas = $conn->prepare($sql_perguntas);
        $stmt_perguntas->bind_param('iiss', $filtro_tema, $filtro_tema, $filtro_nivel, $filtro_nivel);
        $stmt_perguntas->execute();
        $resultado_lista = $stmt_perguntas->get_result();
    }
    $resultado_usuarios = $conn->query('SELECT id, nome, pontos FROM usuarios ORDER BY nome ASC');
}
function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function nome_nivel(string $nivel): string
{
    return [
        'Basico' => 'Básico',
        'Intermediario' => 'Intermediário',
        'Avancado' => 'Avançado'
    ][$nivel] ?? $nivel;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel administrativo - Capivaras Code</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="cartao admin-painel">
        <header class="admin-cabecalho">
            <div>
                <span class="admin-etiqueta">Área restrita</span>
                <h1>Painel administrativo</h1>
                <p>Gerencie perguntas e acompanhe os alunos cadastrados.</p>
            </div>
            <div class="admin-identidade">
                <strong><?php echo escapar((string) ($_SESSION['admin_usuario'] ?? 'Administrador')); ?></strong>
                <span>Administrador conectado</span>
            </div>
        </header>
        <nav class="admin-acoes" aria-label="Ações administrativas">
            <a href="admin_senha.php" class="botao botao-secundario">Trocar senha</a>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" formaction="admin_logout.php" class="botao-excluir">Sair do painel</button>
            </form>
        </nav>
        <?php if (isset($erro)): ?><p class="erro admin-alerta"><?php echo escapar($erro); ?></p><?php endif; ?>
        <?php if (isset($mensagem_sucesso)): ?><p class="sucesso admin-alerta"><?php echo escapar($mensagem_sucesso); ?></p><?php endif; ?>

        <section class="admin-secao">
            <div class="secao-titulo">
                <div>
                    <span class="admin-etiqueta">Conteúdo</span>
                    <h2>Adicionar pergunta</h2>
                </div>
                <span class="secao-icone">+</span>
            </div>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="nova_pergunta" value="1">
                <label for="pergunta">Enunciado</label>
                <textarea id="pergunta" name="pergunta" rows="3" placeholder="Digite o enunciado da pergunta" required></textarea>
                <div class="admin-grade admin-grade-2">
                    <div><label for="opcao_a">Opção A</label><input id="opcao_a" type="text" name="opcao_a" required></div>
                    <div><label for="opcao_b">Opção B</label><input id="opcao_b" type="text" name="opcao_b" required></div>
                    <div><label for="opcao_c">Opção C</label><input id="opcao_c" type="text" name="opcao_c" required></div>
                    <div><label for="opcao_d">Opção D</label><input id="opcao_d" type="text" name="opcao_d" required></div>
                </div>
                <div class="admin-grade admin-grade-3">
                    <div><label for="tema_id">Tema</label><select id="tema_id" name="tema_id" required>
                        <option value="">Selecione o tema</option>
                        <?php foreach ($temas as $tema): ?><option value="<?php echo (int) $tema['id']; ?>"><?php echo escapar($tema['nome'] . ' - ' . nome_nivel($tema['nivel'])); ?></option><?php endforeach; ?>
                    </select></div>
                    <div><label for="nivel">Nível</label><select id="nivel" name="nivel" required>
                        <option value="Basico">Básico</option><option value="Intermediario">Intermediário</option><option value="Avancado">Avançado</option>
                    </select></div>
                    <div><label for="resposta_certa">Resposta correta</label><select id="resposta_certa" name="resposta_certa">
                        <option value="A">Opção A</option><option value="B">Opção B</option><option value="C">Opção C</option><option value="D">Opção D</option>
                    </select></div>
                </div>
                <button type="submit" class="botao">Salvar pergunta</button>
            </form>
        </section>

        <section class="admin-secao">
            <div class="secao-titulo"><div><span class="admin-etiqueta">Biblioteca</span><h2>Perguntas cadastradas</h2></div><?php if ($filtro_aplicado): ?><span class="secao-contador"><?php echo (int) $resultado_lista->num_rows; ?></span><?php endif; ?></div>
            <form method="GET">
                <div class="admin-filtros"><select name="tema_id">
                    <option value="">Todos os temas</option>
                    <?php foreach ($temas as $tema_filtro): ?>
                        <option value="<?php echo (int) $tema_filtro['id']; ?>" <?php echo $filtro_tema === (int) $tema_filtro['id'] ? 'selected' : ''; ?>>
                            <?php echo escapar($tema_filtro['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><select name="nivel">
                    <option value="">Todos os níveis</option>
                    <option value="Basico" <?php echo $filtro_nivel === 'Basico' ? 'selected' : ''; ?>>Nível Básico</option>
                    <option value="Intermediario" <?php echo $filtro_nivel === 'Intermediario' ? 'selected' : ''; ?>>Nível Intermediário</option>
                    <option value="Avancado" <?php echo $filtro_nivel === 'Avancado' ? 'selected' : ''; ?>>Nível Avançado</option>
                </select><button type="submit" class="botao">Filtrar</button><a href="admin.php" class="botao botao-secundario">Limpar</a></div>
            </form>
            <?php if (!$filtro_aplicado): ?>
                <p class="admin-instrucao">Selecione um tema ou nível e clique em <strong>Filtrar</strong> para visualizar as questões.</p>
            <?php elseif ($resultado_lista->num_rows > 0): ?>
                <div class="tabela-responsiva"><table>
                    <tr><th>ID</th><th>Tema</th><th>Nível</th><th>Pergunta</th><th>Ação</th></tr>
                    <?php while ($linha = $resultado_lista->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int) $linha['id']; ?></td>
                            <td><?php echo escapar((string) $linha['tema']); ?></td>
                            <td><?php echo escapar(nome_nivel($linha['nivel'])); ?></td>
                            <td><?php echo escapar($linha['pergunta']); ?></td>
                            <td><a href="editar.php?id=<?php echo (int) $linha['id']; ?>" class="botao-editar">Editar</a></td>
                        </tr>
                    <?php endwhile; ?>
                </table></div>
            <?php else: ?>
                <p>Nenhuma pergunta cadastrada.</p>
            <?php endif; ?>
        </section>

        <section class="admin-secao">
            <div class="secao-titulo"><div><span class="admin-etiqueta">Alunos</span><h2>Usuários cadastrados</h2></div><span class="secao-contador"><?php echo (int) $resultado_usuarios->num_rows; ?></span></div>
            <?php if ($resultado_usuarios->num_rows > 0): ?>
                <div class="tabela-responsiva tabela-usuarios"><table>
                    <tr><th>ID</th><th>Nome</th><th>Pontos</th><th>Ação</th></tr>
                    <?php while ($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int) $usuario['id']; ?></td>
                            <td><?php echo escapar($usuario['nome']); ?></td>
                            <td><?php echo (int) $usuario['pontos']; ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário e todas as respostas dele?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="excluir_usuario" value="1">
                                    <input type="hidden" name="id_usuario" value="<?php echo (int) $usuario['id']; ?>">
                                    <button type="submit" class="botao-excluir">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table></div>
            <?php else: ?>
                <p>Nenhum usuário cadastrado.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
