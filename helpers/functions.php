<?php

function base_url($path = '')
{
    $base = '/monitor_cpcl/';
    return $base . ltrim($path, '/');
}

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}