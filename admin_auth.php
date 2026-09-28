<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    require_once 'sessao.php';
}

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

