<?php
session_start();
require_once __DIR__ . '/../helpers/functions.php';

session_unset();
session_destroy();

header('Location: ' . base_url('page_cpcl/'));
exit;