<?php

declare(strict_types=1);

require __DIR__ . DIRECTORY_SEPARATOR . 'firebase-config.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? '',
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? '',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? '',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? '',
    'vapidKey' => $_ENV['FIREBASE_VAPID_KEY'] ?? '',
]);