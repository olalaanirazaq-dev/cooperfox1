<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Only POST is allowed.']);
    exit;
}

if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No photos were uploaded.']);
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$thumbDir = $uploadDir . DIRECTORY_SEPARATOR . 'thumbs';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to create the uploads directory.']);
    exit;
}
if (!is_dir($thumbDir) && !mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to create the thumbnails directory.']);
    exit;
}

$files = [];
$thumbs = [];
$names = $_FILES['photos']['name'];
$tmpNames = $_FILES['photos']['tmp_name'];
$errors = $_FILES['photos']['error'];
$size = $_FILES['photos']['size'];

for ($i = 0; $i < count($names); $i++) {
    if (($errors[$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        continue;
    }

    $name = basename((string) $names[$i]);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'avif', 'svg', 'tif', 'tiff'];
    if (!in_array($ext, $allowed, true)) {
        continue;
    }

    if (($size[$i] ?? 0) <= 0 || ($size[$i] ?? 0) > 10 * 1024 * 1024) {
        continue;
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
    $safeBase = trim((string) $safeBase, '-_.') ?: 'listing-photo';
    $safeName = $safeBase . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($tmpNames[$i], $target)) {
        continue;
    }

    $filePath = 'uploads/' . $safeName;
    $files[] = $filePath;

    $thumbFile = $thumbDir . DIRECTORY_SEPARATOR . pathinfo($safeName, PATHINFO_FILENAME) . '-thumb.jpg';
    if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled') && function_exists('imagejpeg')) {
        $source = @file_get_contents($target);
        if ($source !== false) {
            $image = @imagecreatefromstring($source);
            if ($image !== false) {
                $width = imagesx($image);
                $height = imagesy($image);
                $thumbWidth = 280;
                $thumbHeight = (int) floor($height * ($thumbWidth / $width));
                if ($thumbHeight < 1) $thumbHeight = 1;
                $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
                if ($thumb !== false) {
                    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                    imagejpeg($thumb, $thumbFile, 82);
                    imagedestroy($image);
                    imagedestroy($thumb);
                    $thumbs[] = 'uploads/thumbs/' . pathinfo($safeName, PATHINFO_FILENAME) . '-thumb.jpg';
                }
            }
        }
    }
}

if ($files === []) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No valid photos were accepted.']);
    exit;
}

http_response_code(200);
echo json_encode(['ok' => true, 'files' => $files, 'thumbs' => $thumbs]);
