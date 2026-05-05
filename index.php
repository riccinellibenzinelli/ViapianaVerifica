<?php
session_start();
require 'config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'viapiana' && $password === 'verifica') {
        $_SESSION['user'] = $username;
    } else {
        $error = 'Credenziali errate!';
    }
}

$logged_in = isset($_SESSION['user']);
$action = $_GET['action'] ?? 'home';



