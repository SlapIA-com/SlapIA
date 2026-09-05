<?php

/**
 * EN placeholder — structure only. Full port of the old lang/en.php content
 * is tracked as a separate follow-up (see ARCHITECTURE.md, Task "i18n EN/DE").
 * Until then, useTranslation() on the frontend falls back to the raw key
 * for anything missing here, exactly like the old includes/i18n.php did.
 */
return [
    'meta' => ['html_lang' => 'en', 'og_locale' => 'en_US'],
    'nav' => [
        'home' => 'Home', 'courses' => 'Courses', 'services' => 'PC Services', 'pricing' => 'Pricing',
        'about' => 'About', 'contact' => 'Contact', 'blog' => 'Blog', 'login' => 'Login',
        'dashboard' => 'My account', 'admin' => 'Admin', 'logout' => 'Log out', 'account_menu' => 'Account menu',
    ],
    'common' => [
        'book_call' => 'Book a call', 'open_menu' => 'Open menu', 'close_menu' => 'Close menu',
        'toggle_theme' => 'Toggle theme', 'switch_lang' => 'Switch language',
    ],
];
