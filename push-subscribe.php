<?php

declare(strict_types=1);

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$payload = json_decode($raw !== false ? $raw : '{}', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
    exit;
}

$token = trim((string) ($payload['token'] ?? ''));

if ($token === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing push subscription data.']);
    exit;
}

$directory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to create data directory.']);
    exit;
}

$storagePath = $directory . DIRECTORY_SEPARATOR . 'push-subscriptions.json';
$subscriptions = is_file($storagePath) ? json_decode((string) file_get_contents($storagePath), true) : [];
if (!is_array($subscriptions)) {
    $subscriptions = [];
}

foreach ($subscriptions as $index => $existing) {
    if (!is_array($existing)) {
        continue;
    }

    if (($existing['token'] ?? '') === $token) {
        echo json_encode(['success' => true, 'message' => 'Subscription already registered.']);
        exit;
    }
}

$subscriptions[] = [
    'token' => $token,
    'created_at' => gmdate('c'),
];

file_put_contents($storagePath, json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

echo json_encode(['success' => true, 'message' => 'Subscription saved.']);
