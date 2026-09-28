<?php
require_once 'sessao.php';
require_once 'csrf.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inicio.php');
    exit();
}
validar_csrf();

$id_exercicio = filter_input(INPUT_POST, 'id_exercicio', FILTER_VALIDATE_INT);
$tema_id = filter_input(INPUT_POST, 'tema_id', FILTER_VALIDATE_INT);
$nivel = (string) ($_POST['nivel'] ?? '');
$niveis_validos = ['Basico', 'Intermediario', 'Avancado'];
if (!$id_exercicio || ($nivel !== '' && !in_array($nivel, $niveis_validos, true))) {
    http_response_code(400);
    exit('Questão inválida.');
}

$tema_filtro = $tema_id ?: 0;
$filtro_chave = $tema_filtro . '|' . $nivel;
if (!isset($_SESSION['questoes_puladas'][$filtro_chave])) {
    $_SESSION['questoes_puladas'][$filtro_chave] = [];
}
if (!in_array($id_exercicio, $_SESSION['questoes_puladas'][$filtro_chave], true)) {
    $_SESSION['questoes_puladas'][$filtro_chave][] = $id_exercicio;
}

$parametros = [];
if ($tema_id) {
    $parametros['tema_id'] = $tema_id;
}
if ($nivel !== '') {
    $parametros['nivel'] = $nivel;
}
$query = http_build_query($parametros);
header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
exit();

