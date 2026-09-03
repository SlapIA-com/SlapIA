<?php
$supported_langs = ['fr', 'en', 'de'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_langs, true)) {
    $lang = $_GET['lang'];
    setcookie('slapia_lang', $lang, time() + 60 * 60 * 24 * 365, '/');
} elseif (isset($_COOKIE['slapia_lang']) && in_array($_COOKIE['slapia_lang'], $supported_langs, true)) {
    $lang = $_COOKIE['slapia_lang'];
} else {
    $lang = 'fr';
}

$T = require __DIR__ . '/../lang/' . $lang . '.php';

function t($key) {
    global $T;
    $value = $T;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $key;
        }
        $value = $value[$part];
    }
    return $value;
}
