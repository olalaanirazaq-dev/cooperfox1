<?php
/*
 * Firebase Cloud Messaging web-push configuration.
 * Add your real values in a sibling file outside public_html, for example:
 * ../firebase-secrets.php
 * and keep this file as the safe fallback/default loader.
 */

$secretFiles = [
    __DIR__ . DIRECTORY_SEPARATOR . 'firebase-secrets.php',
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'firebase-secrets.php',
];
foreach ($secretFiles as $secretFile) {
    if (is_file($secretFile)) {
        require_once $secretFile;
        break;
    }
}

$_ENV['FIREBASE_API_KEY'] = $_ENV['FIREBASE_API_KEY'] ?? 'YOUR_API_KEY';
$_ENV['FIREBASE_AUTH_DOMAIN'] = $_ENV['FIREBASE_AUTH_DOMAIN'] ?? 'YOUR_PROJECT.firebaseapp.com';
$_ENV['FIREBASE_PROJECT_ID'] = $_ENV['FIREBASE_PROJECT_ID'] ?? 'YOUR_PROJECT_ID';
$_ENV['FIREBASE_MESSAGING_SENDER_ID'] = $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? 'YOUR_SENDER_ID';
$_ENV['FIREBASE_APP_ID'] = $_ENV['FIREBASE_APP_ID'] ?? 'YOUR_APP_ID';
$_ENV['FIREBASE_VAPID_KEY'] = $_ENV['FIREBASE_VAPID_KEY'] ?? 'YOUR_VAPID_KEY';
$_ENV['FIREBASE_SERVER_KEY'] = $_ENV['FIREBASE_SERVER_KEY'] ?? 'YOUR_SERVER_KEY';
