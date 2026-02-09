<?php
// Debug script for notion reviews
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/notion-satisfaction.php';

echo "Fetching reviews...\n";
try {
    $reviews = getNotionReviews(1, 'fr');
    print_r($reviews);
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
