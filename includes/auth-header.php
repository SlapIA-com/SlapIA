<?php
/**
 * Layout minimal et immersif pour les pages d'authentification (login, reset-password).
 * Volontairement séparé de includes/header.php : pas de nav, pas de footer du site,
 * toujours en mode sombre — un "moment" à part, pas soumis au thème clair/sombre global.
 */
$title = isset($page_title) ? $page_title . ' — SlapIa' : 'SlapIa';
?>
<!doctype html>
<html lang="<?php echo t('meta.html_lang'); ?>" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $title; ?></title>
<link rel="stylesheet" href="<?php echo assetUrl('/assets/css/style.css'); ?>">
<link rel="stylesheet" href="<?php echo assetUrl('/assets/css/auth.css'); ?>">
<link rel="icon" type="image/png" href="/assets/img/brand/logo.png">
</head>
<body class="auth-body">

<div class="auth-screen">
  <div class="auth-mesh" aria-hidden="true">
    <div class="auth-blob auth-blob--1"></div>
    <div class="auth-blob auth-blob--2"></div>
    <div class="auth-blob auth-blob--3"></div>
  </div>

  <a href="/index.php" class="auth-logo">
    <img src="/assets/img/brand/logo.svg" alt="" class="auth-logo__mark"> SlapIa
  </a>

  <main class="auth-stage">
