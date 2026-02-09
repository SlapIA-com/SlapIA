<?php
// Language Management
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language logic
if (!isset($_SESSION['language'])) {
    // Auto-detect browser language
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    if ($browserLang === 'en') {
        $_SESSION['language'] = 'en';
    }
    else {
        $_SESSION['language'] = 'fr';
    }
}

// Check if language change is requested
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
}

$lang = $_SESSION['language'];

// Load Translation JSON
$langFile = __DIR__ . '/../assets/lang/' . $lang . '.json';
$translations = [];

if (file_exists($langFile)) {
    $json = file_get_contents($langFile);
    $translations = json_decode($json, true);
    if ($translations === null) {
        // Fallback or error logging could go here
        $translations = [];
    }
}

// Function to get translation
function t($key)
{
    global $translations;
    if (isset($translations[$key])) {
        return $translations[$key];
    }
    // Debug: return key if missing, or maybe fallback to default language
    return $key;
}
?>
