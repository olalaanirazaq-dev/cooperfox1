<?php

declare(strict_types=1);

header('Content-Type: application/json');

function cooperFoxInquiryDirectory(): string
{
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the inquiry data directory.');
    }
    return $directory;
}

function cooperFoxWriteInquiry(array $inquiry): void
{
    $directory = cooperFoxInquiryDirectory();
    $filePath = $directory . DIRECTORY_SEPARATOR . 'inquiries.json';

    $inquiries = is_file($filePath)
        ? json_decode((string) file_get_contents($filePath), true)
        : [];

    if (!is_array($inquiries)) {
        $inquiries = [];
    }

    $fingerprint = $inquiry['fingerprint'] ?? hash('sha256', json_encode($inquiry, JSON_UNESCAPED_SLASHES));
    foreach ($inquiries as $existing) {
        if (!is_array($existing)) {
            continue;
        }

        if (($existing['fingerprint'] ?? '') === $fingerprint) {
            return;
        }
    }

    $inquiries[] = $inquiry;
    file_put_contents($filePath, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function cooperFoxSendInquiryNotification(array $inquiry): void
{
    if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php')) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php';
    }

    $adminEmail = getenv('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? 'admin@cooperfoxrealty.com');
    $landlordEmail = getenv('LANDLORD_EMAIL') ?: ($_ENV['LANDLORD_EMAIL'] ?? 'Coopermathewfoxhomes@realtyagent.com');

    $recipients = array_values(array_filter([
        $adminEmail,
        $landlordEmail,
    ], static fn ($value): bool => is_string($value) && $value !== ''));

    if ($recipients === []) {
        return;
    }

    $subject = 'New property inquiry: ' . ($inquiry['property'] ?? 'Property inquiry');
    $body = "New property inquiry received\n\n";
    $body .= 'Property: ' . ($inquiry['property'] ?? '') . "\n";
    $body .= 'City: ' . ($inquiry['city'] ?? '') . "\n";
    $body .= 'Name: ' . ($inquiry['name'] ?? '') . "\n";
    $body .= 'Email: ' . ($inquiry['email'] ?? '') . "\n";
    $body .= 'Message: ' . ($inquiry['message'] ?? '') . "\n";
    $body .= 'Submitted: ' . ($inquiry['submitted_at'] ?? gmdate('c')) . "\n";

    $headers = [
        'From: Cooper Fox Realty <noreply@cooperfoxrealty.com>',
        'Reply-To: ' . ($inquiry['email'] ?? 'noreply@cooperfoxrealty.com'),
        'Content-Type: text/plain; charset=UTF-8',
    ];

    @mail(implode(', ', $recipients), $subject, $body, implode("\r\n", $headers));

    $pushTitle = 'New property inquiry';
    $pushBody = ($inquiry['name'] ?? 'Someone') . ' inquired about ' . ($inquiry['property'] ?? 'a property');
    $pushUrl = '/admin.php';

    require_once __DIR__ . DIRECTORY_SEPARATOR . 'push-notifications.php';
    cooperFoxSendPush($pushTitle, $pushBody, $pushUrl);
}

$property = trim((string) ($_POST['property'] ?? ''));
$city = trim((string) ($_POST['city'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$source = trim((string) ($_POST['source'] ?? 'website'));
$listingKey = trim((string) ($_POST['listing_key'] ?? ''));
$submittedAt = gmdate('c');

if ($property === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'duplicate' => false, 'message' => 'Property and message are required.']);
    exit;
}

$fingerprint = hash('sha256', json_encode([
    'property' => $property,
    'city' => $city,
    'message' => $message,
    'name' => $name,
    'email' => $email,
    'listing_key' => $listingKey,
    'source' => $source,
], JSON_UNESCAPED_SLASHES));

$inquiry = [
    'id' => 'INQ-' . strtoupper(bin2hex(random_bytes(6))),
    'property' => $property,
    'city' => $city,
    'message' => $message,
    'name' => $name,
    'email' => $email,
    'listing_key' => $listingKey,
    'source' => $source,
    'submitted_at' => $submittedAt,
    'fingerprint' => $fingerprint,
];

try {
    cooperFoxWriteInquiry($inquiry);
    cooperFoxSendInquiryNotification($inquiry);
    echo json_encode(['success' => true, 'duplicate' => false, 'message' => 'Inquiry saved successfully.']);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    error_log('Save inquiry failed: ' . $exception->getMessage());
    echo json_encode(['success' => false, 'duplicate' => false, 'message' => 'Unable to save inquiry.']);
    exit;
}
