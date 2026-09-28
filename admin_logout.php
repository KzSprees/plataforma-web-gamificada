<?php
require_once 'sessao.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.php');
    exit();
}

validar_csrf();
unset($_SESSION['admin_id'], $_SESSION['admin_usuario']);
session_regenerate_id(true);
header('Location: admin_login.php');
exit();

