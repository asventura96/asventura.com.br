<?php
session_start();

// Verifica se a variável de sessão existe
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Se não estiver logado, chuta para a tela de login
    // Como o auth.php é incluído no projects.php, o caminho relativo pro login é apenas 'login.php'
    header("Location: login.php");
    exit;
}
?>