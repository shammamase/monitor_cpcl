<?php
session_start();
require_once __DIR__ . '/../helpers/functions.php';

if (!isset($_SESSION['user_upbs'])) {
    header('Location: ' . base_url('page_upbs/login.php'));
    exit;
}

$userUpbs = $_SESSION['user_upbs'];