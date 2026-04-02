<?php
session_start();
require_once __DIR__ . '/../helpers/functions.php';

if (!isset($_SESSION['user_login'])) {
    header('Location: ' . base_url('page_cpcl/index.php'));
    exit;
}

$userLogin = $_SESSION['user_login'];