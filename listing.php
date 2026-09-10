<?php
declare(strict_types=1);

$key = strtolower(trim((string) ($_GET['listing'] ?? '')));
$file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'listings.json';
$listings = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
$listings = is_array($listings) ? $listings : [];
$listing = null;
foreach ($listings as $item) {
    $itemKey = strtolower(trim((string) ($item['title'] ?? '')) . '-' . trim((string) ($item['city'] ?? '')));
    $itemKey = preg_replace('/[^a-z0-9]+/', '-', $itemKey) ?? '';
    $itemKey = trim($itemKey, '-');
    if ($itemKey === $key) {
        $listing = $item;
        break;
    }
}

function shareValue(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'cooperfoxrealty.com';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
$baseUrl = $scheme . '://' . $host;
$baseUrl = preg_replace('#/+$#', '', $baseUrl) ?? $baseUrl;

function buildAbsoluteUrl(string $value, string $baseUrl): string
{
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
}

$title = (string) ($listing['title'] ?? 'Cooper Fox Realty listing');
$city = (string) ($listing['city'] ?? '');
$state = (string) ($listing['state'] ?? '');
$price = (string) ($listing['price'] ?? '');
$details = (string) ($listing['details'] ?? '');
$image = (string) (($listing['gallery'][0] ?? ''));
$imageUrl = buildAbsoluteUrl($image, $baseUrl);
$url = $baseUrl . '/listing.php?listing=' . rawurlencode($key);
$description = trim($price . ' | ' . $details . ' | ' . $city . ($state !== '' ? ', ' . $state : '') . ' | Cooper Fox Realty');
if ($listing === null) {
    http_response_code(404);
    $title = 'Listing not found | Cooper Fox Realty';
    $description = 'Browse available properties from Cooper Fox Realty.';
    $url = $baseUrl . '/';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo shareValue($title); ?></title>
  <meta name="description" content="<?php echo shareValue($description); ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Cooper Fox Realty">
  <meta property="og:title" content="<?php echo shareValue($title); ?>">
  <meta property="og:description" content="<?php echo shareValue($description); ?>">
  <meta property="og:url" content="<?php echo shareValue($url); ?>">
  <?php if ($imageUrl !== ''): ?><meta property="og:image" content="<?php echo shareValue($imageUrl); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:image" content="<?php echo shareValue($imageUrl); ?>"><?php endif; ?>
  <meta http-equiv="refresh" content="0;url=<?php echo shareValue($baseUrl . '/index.html?listing=' . rawurlencode($key) . '#listings'); ?>">
</head>
<body>
  <p>Opening this Cooper Fox Realty listing...</p>
  <p><a href="<?php echo shareValue($baseUrl . '/index.html?listing=' . rawurlencode($key) . '#listings'); ?>">Open listing</a></p>
</body>
</html>
