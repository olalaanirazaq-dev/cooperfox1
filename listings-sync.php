<?php
session_start();

require __DIR__ . DIRECTORY_SEPARATOR . 'listing-storage.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($requestMethod === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput !== false ? $rawInput : '[]', true);
    $listings = is_array($payload) ? $payload : [];

    $saved = cooperFoxWriteListings(array_values($listings));
    if ($saved === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to save listings to SQLite storage.']);
        exit;
    }

    echo json_encode(['ok' => true, 'count' => count($listings)]);
    exit;
}

$stored = cooperFoxReadListings();
echo json_encode($stored);
