<?php

declare(strict_types=1);

header('Content-Type: application/json');

function cooperFoxChatDirectory(): string
{
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the chat data directory.');
    }
    return $directory;
}

function cooperFoxChatStoragePath(): string
{
    return cooperFoxChatDirectory() . DIRECTORY_SEPARATOR . 'chat-conversations.json';
}

function cooperFoxLoadChatStore(): array
{
    $storagePath = cooperFoxChatStoragePath();
    if (!is_file($storagePath)) {
        return [];
    }

    $payload = json_decode((string) file_get_contents($storagePath), true);
    return is_array($payload) ? $payload : [];
}

function cooperFoxWriteChatStore(array $store): void
{
    file_put_contents(cooperFoxChatStoragePath(), json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function cooperFoxNormalizeKey(string $value): string
{
    $normalized = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($value)));
    return trim((string) $normalized, '-');
}

try {
    $action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? 'list')));

    if ($action === 'list') {
        $conversations = cooperFoxLoadChatStore();
        $filtered = [];

        $listingKey = cooperFoxNormalizeKey((string) ($_GET['listing_key'] ?? $_POST['listing_key'] ?? ''));
        foreach ($conversations as $conversation) {
            if (!is_array($conversation)) {
                continue;
            }

            if ($listingKey !== '' && (cooperFoxNormalizeKey((string) ($conversation['listing_key'] ?? '')) !== $listingKey)) {
                continue;
            }

            $filtered[] = $conversation;
        }

        echo json_encode(['success' => true, 'conversations' => $filtered]);
        exit;
    }

    if ($action === 'send') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody !== false ? $rawBody : '{}', true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
            exit;
        }

        $listingKey = cooperFoxNormalizeKey((string) ($payload['listing_key'] ?? $_POST['listing_key'] ?? ''));
        $property = trim((string) ($payload['property'] ?? $_POST['property'] ?? ''));
        $city = trim((string) ($payload['city'] ?? $_POST['city'] ?? ''));
        $sender = trim((string) ($payload['sender'] ?? $_POST['sender'] ?? 'guest'));
        $text = trim((string) ($payload['text'] ?? $_POST['text'] ?? ''));
        $participantName = trim((string) ($payload['participant_name'] ?? $_POST['participant_name'] ?? 'Cooper Fox'));

        if ($listingKey === '' || $text === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Listing key and message text are required.']);
            exit;
        }

        $store = cooperFoxLoadChatStore();
        $conversationKey = $listingKey;

        $conversation = null;
        foreach ($store as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if ((string) ($entry['listing_key'] ?? '') === $conversationKey) {
                $conversation = $entry;
                break;
            }
        }

        if ($conversation === null) {
            $conversation = [
                'id' => 'chat-' . strtoupper(bin2hex(random_bytes(5))),
                'listing_key' => $conversationKey,
                'property' => $property,
                'city' => $city,
                'participant_name' => $participantName,
                'messages' => [],
                'updated_at' => gmdate('c'),
            ];
            $store[] = $conversation;
        }

        $message = [
            'id' => 'msg-' . strtoupper(bin2hex(random_bytes(5))),
            'sender' => $sender,
            'participant_name' => $participantName,
            'text' => $text,
            'created_at' => gmdate('c'),
            'status' => (string) ($payload['status'] ?? $_POST['status'] ?? 'sent'),
            'read_at' => (string) ($payload['read_at'] ?? $_POST['read_at'] ?? ''),
        ];

        $conversation['messages'][] = $message;
        $conversation['updated_at'] = gmdate('c');
        $conversation['participant_name'] = $participantName;
        $conversation['property'] = $property !== '' ? $property : ($conversation['property'] ?? 'Property');
        $conversation['city'] = $city !== '' ? $city : ($conversation['city'] ?? 'Local');

        $updatedStore = [];
        foreach ($store as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if ((string) ($entry['listing_key'] ?? '') === $conversationKey) {
                $updatedStore[] = $conversation;
            } else {
                $updatedStore[] = $entry;
            }
        }

        cooperFoxWriteChatStore($updatedStore);

        if ($sender === 'agent') {
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'push-notifications.php';
            cooperFoxSendPush('Cooper Fox replied', $text, '/index.html');
        }

        echo json_encode([
            'success' => true,
            'conversation' => $conversation,
            'message' => $message,
        ]);
        exit;
    }

    if ($action === 'mark-read') {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody !== false ? $rawBody : '{}', true);
        $listingKey = cooperFoxNormalizeKey((string) ($payload['listing_key'] ?? $_POST['listing_key'] ?? ''));
        if ($listingKey === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Listing key is required.']);
            exit;
        }

        $store = cooperFoxLoadChatStore();
        foreach ($store as &$conversation) {
            if (!is_array($conversation) || ((string) ($conversation['listing_key'] ?? '')) !== $listingKey) {
                continue;
            }

            foreach ($conversation['messages'] ?? [] as &$message) {
                if (!is_array($message)) {
                    continue;
                }
                if (($message['sender'] ?? '') !== 'guest') {
                    $message['status'] = 'read';
                    $message['read_at'] = gmdate('c');
                }
            }
            unset($message);
        }
        unset($conversation);

        cooperFoxWriteChatStore($store);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    error_log('Chat API failed: ' . $exception->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to process chat request.']);
    exit;
}
