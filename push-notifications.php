<?php

declare(strict_types=1);

function cooperFoxPushSubscriptions(): array
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'push-subscriptions.json';
    if (!is_file($path)) {
        return [];
    }

    $subscriptions = json_decode((string) file_get_contents($path), true);
    return is_array($subscriptions) ? $subscriptions : [];
}

function cooperFoxSendPush(string $title, string $body, string $url = '/'): void
{
    if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php')) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php';
    }

    $serverKey = getenv('FIREBASE_SERVER_KEY') ?: ($_ENV['FIREBASE_SERVER_KEY'] ?? '');
    if ($serverKey === '') {
        return;
    }

    foreach (cooperFoxPushSubscriptions() as $subscription) {
        $token = is_array($subscription) ? trim((string) ($subscription['token'] ?? '')) : '';
        if ($token === '') {
            continue;
        }

        $payload = json_encode([
            'to' => $token,
            'notification' => ['title' => $title, 'body' => $body],
            'data' => ['url' => $url],
        ], JSON_UNESCAPED_SLASHES);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: key={$serverKey}\r\nContent-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 20,
            ],
        ]);

        @file_get_contents('https://fcm.googleapis.com/fcm/send', false, $context);
    }
}