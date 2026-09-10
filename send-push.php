<?php

declare(strict_types=1);

function loadPushSubscriptions(): array
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'push-subscriptions.json';
    if (!is_file($path)) {
        return [];
    }

    $payload = json_decode((string) file_get_contents($path), true);
    return is_array($payload) ? $payload : [];
}

function sendWebPush(string $endpoint, string $publicKey, string $auth, string $title, string $body, string $url): bool
{
    $key = sodium_base642bin($publicKey, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    $authSecret = sodium_base642bin($auth, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

    $payload = json_encode([
        'title' => $title,
        'body' => $body,
        'url' => $url,
    ], JSON_UNESCAPED_SLASHES);

    $encrypted = sodium_crypto_box_seal($payload, $key);
    $salt = random_bytes(16);
    $record = $authSecret . $salt;
    $salted = hash('sha256', $record, true);
    $nonce = sodium_crypto_secretbox($encrypted, $salted, $authSecret);

    $headers = [
        'TTL: 60',
        'Content-Encoding: aes128gcm',
        'Authorization: WebPush ' . base64_encode($auth),
        'Content-Type: application/octet-stream',
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $encrypted,
            'timeout' => 20,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    return $response !== false;
}

$subscriptions = loadPushSubscriptions();
if ($subscriptions === []) {
    http_response_code(204);
    exit;
}

$title = trim((string) ($_POST['title'] ?? 'Cooper Fox Realty'));
$body = trim((string) ($_POST['body'] ?? 'You have a new inquiry.'));
$url = trim((string) ($_POST['url'] ?? '/'));

foreach ($subscriptions as $subscription) {
    if (!is_array($subscription)) {
        continue;
    }

    $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
    $publicKey = trim((string) ($subscription['publicKey'] ?? ''));
    $auth = trim((string) ($subscription['auth'] ?? ''));

    if ($endpoint === '' || $publicKey === '' || $auth === '') {
        continue;
    }

    sendWebPush($endpoint, $publicKey, $auth, $title, $body, $url);
}

echo json_encode(['success' => true, 'sent' => count($subscriptions)]);
