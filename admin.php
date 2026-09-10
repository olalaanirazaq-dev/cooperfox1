<?php
declare(strict_types=1);

session_start();

require __DIR__ . DIRECTORY_SEPARATOR . 'listing-storage.php';

if (empty($_SESSION['admin_logged_in']) || ($_SESSION['admin_logged_in'] !== true)) {
    header('Location: admin-login.html');
    exit;
}

if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin-login.html');
    exit;
}

    header('Content-Security-Policy: default-src \'' . "self" . '\'; base-uri \'' . "self" . '\'; object-src \'' . "none" . '\'; frame-ancestors \'' . "none" . '\'; form-action \'' . "self" . '\'; img-src \'' . "self" . ' data: https:; style-src \'' . "self" . '\' \'' . "unsafe-inline" . '\' https:; script-src \'' . "self" . '\' \'' . "unsafe-inline" . '\' https://cdn.chatway.app; connect-src \'' . "self" . '\' https:;');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://www.gstatic.com; connect-src \'self\' https:;');
header('Content-Security-Policy: default-src \'self\'; base-uri \'self\'; object-src \'none\'; frame-ancestors \'none\'; form-action \'self\'; img-src \'self\' data: https:; style-src \'self\' \'unsafe-inline\' https:; script-src \'self\' \'unsafe-inline\' https://www.gstatic.com; connect-src \'self\' https:;');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

function readJsonArray(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$users = readJsonArray($dataDir . DIRECTORY_SEPARATOR . 'users.json');
$applications = readJsonArray($dataDir . DIRECTORY_SEPARATOR . 'applications.json');
$listingsFile = $dataDir . DIRECTORY_SEPARATOR . 'listings.json';
$listings = cooperFoxReadListings();
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$listingStatus = trim((string) ($_GET['listing_status'] ?? ''));

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST' && isset($_POST['listing_action'])) {
    $listingAction = (string) $_POST['listing_action'];
    $listingStatus = '';

    if ($listingAction === 'delete') {
        $deleteIndex = filter_var($_POST['listing_index'] ?? null, FILTER_VALIDATE_INT);
        if ($deleteIndex !== false && isset($listings[$deleteIndex])) {
            array_splice($listings, $deleteIndex, 1);
            $listingStatus = 'Listing deleted successfully.';
        }
    } elseif ($listingAction === 'add' || $listingAction === 'update') {
        $listingIndex = filter_var($_POST['listing_index'] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string) ($_POST['title'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? 'California'));
        $details = trim((string) ($_POST['details'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $zipCode = trim((string) ($_POST['zip_code'] ?? ''));
        $utilities = trim((string) ($_POST['utilities'] ?? ''));
        $rawAmenities = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
        $amenities = array_values(array_unique(array_filter(array_map(static function ($value): string {
            return trim((string) $value);
        }, $rawAmenities), static function (string $value): bool {
            return $value !== '';
        })));
        $priceValue = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
        $gallery = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['gallery'] ?? '')))));
        $uploadedGallery = [];

        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'] ?? null)) {
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $allowedImageTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];
            $fileCount = min(count($_FILES['photos']['name']), 35 - count($gallery));
            for ($fileIndex = 0; $fileIndex < $fileCount; $fileIndex++) {
                if ($_FILES['photos']['error'][$fileIndex] !== UPLOAD_ERR_OK || $_FILES['photos']['size'][$fileIndex] > 8 * 1024 * 1024) {
                    continue;
                }
                $tmpName = $_FILES['photos']['tmp_name'][$fileIndex];
                $mimeType = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) : (function_exists('mime_content_type') ? mime_content_type($tmpName) : '');
                if (!$mimeType && @getimagesize($tmpName) !== false) {
                    $mimeType = 'image/' . strtolower(pathinfo((string) $_FILES['photos']['name'][$fileIndex], PATHINFO_EXTENSION));
                    if ($mimeType === 'image/jpg') $mimeType = 'image/jpeg';
                }
                if (!isset($allowedImageTypes[$mimeType])) {
                    continue;
                }
                $safeName = 'listing-' . bin2hex(random_bytes(8)) . '.' . $allowedImageTypes[$mimeType];
                if (move_uploaded_file($tmpName, $uploadsDir . DIRECTORY_SEPARATOR . $safeName)) {
                    $uploadedGallery[] = 'uploads/' . $safeName;
                }
            }
        }

        if ($uploadedGallery !== []) {
            $gallery = array_values(array_unique(array_merge($uploadedGallery, $gallery)));
        }

        if ($gallery === [] && $listingAction === 'update' && $listingIndex !== false && isset($listings[$listingIndex]['gallery'])) {
            $gallery = (array) $listings[$listingIndex]['gallery'];
        }

        $selectedFeaturedIndex = filter_var($_POST['featured_image_index'] ?? null, FILTER_VALIDATE_INT);
        if ($selectedFeaturedIndex !== false && $gallery !== []) {
            $selectedFeaturedIndex = max(0, min((int) $selectedFeaturedIndex, count($gallery) - 1));
            if ($selectedFeaturedIndex > 0) {
                $featuredImage = $gallery[$selectedFeaturedIndex];
                array_splice($gallery, $selectedFeaturedIndex, 1);
                array_unshift($gallery, $featuredImage);
            }
        }

        if ($title !== '' && $city !== '' && $details !== '' && $priceValue !== false && $priceValue >= 0 && $gallery !== []) {
            $existingListing = ($listingAction === 'update' && $listingIndex !== false && isset($listings[$listingIndex])) ? $listings[$listingIndex] : [];
            $updatedListing = [
                'title' => $title,
                'price' => '$' . number_format($priceValue) . '/mo',
                'priceValue' => $priceValue,
                'propertyType' => (string) ($_POST['property_type'] ?? 'House'),
                'status' => (string) ($_POST['status'] ?? 'For Rent'),
                'details' => $details,
                'description' => $description !== '' ? $description : ((string) ($existingListing['description'] ?? '')),
                'location' => $location !== '' ? $location : ((string) ($existingListing['location'] ?? '')),
                'address' => $address !== '' ? $address : ((string) ($existingListing['address'] ?? '')),
                'streetAddress' => $address !== '' ? $address : ((string) ($existingListing['streetAddress'] ?? $existingListing['address'] ?? '')),
                'zipCode' => $zipCode !== '' ? $zipCode : ((string) ($existingListing['zipCode'] ?? $existingListing['zip'] ?? '')),
                'zip' => $zipCode !== '' ? $zipCode : ((string) ($existingListing['zip'] ?? $existingListing['zipCode'] ?? '')),
                'utilities' => $utilities !== '' ? $utilities : ((string) ($existingListing['utilities'] ?? '')),
                'amenities' => $amenities !== [] ? $amenities : (array) ($existingListing['amenities'] ?? []),
                'city' => $city,
                'state' => $state !== '' ? $state : 'California',
                'contactPhone' => trim((string) ($_POST['contact_phone'] ?? '+13463163488')) ?: '+13463163488',
                'gallery' => array_slice($gallery, 0, 35),
            ];

            if ($listingAction === 'update' && $listingIndex !== false && isset($listings[$listingIndex])) {
                $listings[$listingIndex] = $updatedListing;
            } else {
                $listings[] = $updatedListing;
            }
            $listingStatus = $listingAction === 'update' ? 'Listing updated successfully.' : 'Listing published successfully.';
        } else {
            $listingStatus = 'Listing not saved. Enter all property fields and choose at least one valid image under 8 MB.';
        }
    }

    if ($listingStatus !== '' && (str_contains($listingStatus, 'successfully') || $listingAction === 'delete')) {
        $written = cooperFoxWriteListings(array_values($listings));
        if ($written === false) $listingStatus = 'Listing could not be saved. Make the data folder writable by PHP.';
    }
    header('Location: admin.php?admin=1&listing_status=' . rawurlencode($listingStatus) . '#listings');
    exit;
}

if ($requestMethod === 'POST' && isset($_POST['application_action'])) {
    $applicationId = trim((string) ($_POST['application_id'] ?? ''));
    $reply = trim((string) ($_POST['reply'] ?? ''));
    if ($_POST['application_action'] === 'reply' && $applicationId !== '' && $reply !== '') {
        foreach ($applications as &$application) {
            if (($application['id'] ?? '') === $applicationId) {
                if (!isset($application['chat_messages']) || !is_array($application['chat_messages'])) {
                    $application['chat_messages'] = [];
                }
                $application['chat_messages'][] = ['from' => 'cooper-fox', 'text' => $reply, 'sent_at' => gmdate('c')];
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'push-notifications.php';
                cooperFoxSendPush('Cooper Fox replied', $reply, '/application-chat.php?id=' . rawurlencode($applicationId));
                break;
            }
        }
        unset($application);
        file_put_contents($dataDir . DIRECTORY_SEPARATOR . 'applications.json', json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    header('Location: admin.php#messages');
    exit;
}

$usersCount = is_array($users) ? count($users) : 0;
$applicationsCount = count($applications);
$editListingIndex = filter_var($_GET['edit_listing'] ?? null, FILTER_VALIDATE_INT);
$editListing = ($editListingIndex !== false && isset($listings[$editListingIndex])) ? $listings[$editListingIndex] : null;
$usStates = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'];
$listingStatuses = ['For Sale', 'For Rent', 'Open House', 'New', 'Coming Soon', 'Pending', 'Sold', 'Off Market', 'Draft'];
$pendingApplications = 0;
foreach ($applications as $application) {
    if (is_array($application) && (($application['application_status'] ?? '') !== 'Approved')) {
        $pendingApplications++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js"></script>
    <title>Admin portal | Cooper Fox Realty</title>
    <style>
        :root {
            --bg: #06131d;
            --bg-soft: #0d1f2d;
            --panel: #12283a;
            --panel-alt: #17344d;
            --line: rgba(255,255,255,0.08);
            --text: #ebf3ff;
            --muted: #9ab0c4;
            --gold: #d7b670;
            --gold-soft: #f3e2ad;
            --success: #38c172;
            --warning: #ffb454;
            --danger: #ff6b6b;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #06131d 0%, #102234 100%);
            color: var(--text);
        }

        a { color: inherit; }

        .wrap {
            max-width: 1460px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border: 1px solid var(--line);
            background: rgba(11, 22, 33, 0.94);
            border-radius: 18px;
            position: sticky;
            top: 12px;
            z-index: 10;
            backdrop-filter: blur(12px);
        }

        .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; }
        .brand-mark {
            width: 42px; height: 42px; border-radius: 12px;
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            color: #0b1117; font-weight: 800;
        }

        .actions { display: flex; align-items: center; gap: 14px; }
        .meta-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 999px; font-size: 0.82rem;
            background: rgba(158, 197, 255, 0.08); border: 1px solid var(--line);
            color: var(--muted);
        }

        .logout-btn {
            border: 1px solid rgba(255,255,255,.12); background: transparent; color: var(--text);
            border-radius: 999px; padding: 10px 16px; font-weight: 700; cursor: pointer;
        }

        .hero {
            display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 18px; margin: 26px 0 28px;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(18, 40, 58, 0.96), rgba(11, 22, 33, 0.96));
            border: 1px solid var(--line);
            border-radius: 18px; padding: 22px 18px;
        }

        .stat-card small {
            display: block; color: var(--muted); margin-bottom: 12px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em;
        }

        .stat-card strong {
            display: block; font-size: clamp(1.8rem, 2.3vw, 2.4rem); line-height: 1.1; color: var(--gold-soft);
        }

        .panel {
            background: rgba(15, 27, 38, 0.96);
            border: 1px solid var(--line);
            border-radius: 20px; overflow: hidden;
            margin-top: 28px;
        }

        .panel-header {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
            gap: 12px; padding: 18px 22px; border-bottom: 1px solid var(--line);
            background: rgba(255,255,255,0.02);
        }

        .panel-header h2 {
            margin: 0; font-size: clamp(1.15rem, 2vw, 1.5rem);
        }

        .panel-body { padding: 18px 22px 22px; }

        table {
            width: 100%; border-collapse: collapse; font-size: 0.95rem;
        }

        th, td {
            text-align: left; padding: 12px 10px; border-bottom: 1px solid rgba(255,255,255,0.08); vertical-align: top;
        }

        th { color: var(--muted); font-weight: 700; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.07em; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }
        .muted { color: var(--muted); }
        .badge {
            display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
            border: 1px solid rgba(255,255,255,0.12);
        }
        .badge.warning { background: rgba(255,180,84,0.12); color: var(--warning); }
        .badge.success { background: rgba(56,193,114,0.12); color: var(--success); }
        .badge.info { background: rgba(127,164,255,0.12); color: #9ec0ff; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; border: 1px solid var(--line);
            background: rgba(255,255,255,0.02); color: var(--muted);
        }

        .note {
            padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.02);
            color: var(--muted); margin-top: 10px;
        }

        .listing-editor { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 22px; }
        .listing-editor input, .listing-editor select, .listing-editor textarea { width: 100%; min-width: 0; padding: 11px 12px; border: 1px solid var(--line); border-radius: 9px; background: #0d1f2d; color: var(--text); font: inherit; }
        .listing-editor textarea { grid-column: span 2; resize: vertical; }
        .amenity-grid {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255,255,255,0.02);
        }
        .amenity-option {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .amenity-option input { width: auto; margin: 0; accent-color: var(--gold); }
        .preview-picker {
            grid-column: 1 / -1;
            display: block;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255,255,255,0.02);
        }
        .preview-picker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .preview-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        .preview-choice {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255,255,255,0.02);
            overflow: hidden;
            cursor: pointer;
            text-align: left;
        }
        .preview-choice img {
            display: block;
            width: 100%;
            height: 110px;
            object-fit: cover;
            background: #0b1621;
        }
        .preview-choice.is-primary {
            border-color: var(--gold);
            box-shadow: 0 0 0 1px rgba(216, 189, 132, 0.45);
        }
        .preview-choice-tag {
            position: absolute;
            left: 8px;
            bottom: 8px;
            padding: 5px 8px;
            border-radius: 999px;
            background: rgba(11, 22, 33, 0.86);
            color: var(--text);
            font-size: 0.7rem;
            font-weight: 700;
        }
        .admin-submit, .danger-btn { border: 0; border-radius: 999px; padding: 11px 16px; font-weight: 700; cursor: pointer; }
        .admin-submit { background: var(--gold); color: #111820; }
        .danger-btn { background: rgba(255,107,107,.14); color: var(--danger); }
        .edit-link, .cancel-link { color: var(--gold-soft); font-weight: 700; text-decoration: none; margin-right: 8px; }
        td form { display: inline; }
        .message-thread { padding: 16px; margin-bottom: 14px; border: 1px solid var(--line); border-radius: 12px; background: rgba(255,255,255,.02); }
        .message-thread h3 { margin: 0 0 12px; font-size: 1rem; }
        .message-thread p { margin: 8px 0; color: var(--muted); line-height: 1.5; }
        .reply-form { display: grid; grid-template-columns: 1fr auto; gap: 10px; margin-top: 14px; }
        .reply-form textarea { width: 100%; min-width: 0; padding: 11px 12px; border: 1px solid var(--line); border-radius: 9px; background: #0d1f2d; color: var(--text); font: inherit; resize: vertical; }
        .department-nav { display:flex; gap:8px; flex-wrap:wrap; margin:18px 0 0; padding:10px; border:1px solid var(--line); border-radius:14px; background:rgba(11,22,33,.72); }
        .department-nav a { display:inline-flex; align-items:center; gap:8px; padding:10px 13px; border-radius:9px; color:var(--muted); text-decoration:none; font-size:.78rem; font-weight:800; }
        .department-nav a:hover, .department-nav a:focus-visible { background:rgba(215,182,112,.14); color:var(--gold-soft); outline:none; }
        .department-nav a strong { color:var(--gold); font-size:.68rem; }
        .section-kicker { margin:0 0 5px; color:var(--gold); font-size:.68rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .inbox-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; padding:12px 14px; border:1px solid var(--line); border-radius:12px; background:rgba(255,255,255,.025); }
        .inbox-toolbar strong { font-size:.86rem; }
        .inbox-toolbar span { color:var(--muted); font-size:.75rem; }
        .inbox-list { display:grid; gap:12px; }
        .inbox-list .message-thread { margin:0; border-left:3px solid var(--gold); }

        @media (max-width: 920px) {
            .hero { grid-template-columns: repeat(2, minmax(170px, 1fr)); }
            .topbar { align-items: flex-start; }
            .actions { width: 100%; justify-content: space-between; }
        }

        @media (max-width: 600px) {
            .wrap { padding-left: 14px; padding-right: 14px; }
            .hero { grid-template-columns: 1fr; }
            .panel-body { overflow-x: auto; }
            table { min-width: 820px; }
            .listing-editor { grid-template-columns: 1fr; }
            .listing-editor textarea { grid-column: auto; }
            .reply-form { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">CF</div>
                <div>
                    <div>Cooper Fox Realty</div>
                    <small class="muted">Admin portal</small>
                </div>
            </div>
            <div class="actions">
                <span class="meta-badge">Signed in as <?php echo htmlspecialchars((string) ($_SESSION['user_name'] ?? 'Admin')); ?></span>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="logout" value="1" />
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            </div>
        </header>

        <nav class="department-nav" aria-label="Admin departments">
            <a href="#overview"><strong>01</strong> Overview</a>
            <a href="#messages"><strong>02</strong> Application inbox</a>
            <a href="#applications"><strong>03</strong> Applications</a>
            <a href="#listings"><strong>04</strong> Listings</a>
        </nav>

        <section class="hero" id="overview">
            <article class="stat-card">
                <small>Total users</small>
                <strong><?php echo $usersCount; ?></strong>
            </article>
            <article class="stat-card">
                <small>Applications</small>
                <strong><?php echo $applicationsCount; ?></strong>
            </article>
            <article class="stat-card">
                <small>Open review items</small>
                <strong><?php echo $pendingApplications; ?></strong>
            </article>
            <article class="stat-card">
                <small>Admin access</small>
                <strong>Secure</strong>
            </article>
        </section>

        <section class="panel" id="adminPanel">
            <div class="panel-header">
                <h2>Users</h2>
                <span class="pill">All created accounts</span>
            </div>
            <div class="panel-body">
                <?php if ($usersCount === 0): ?>
                    <div class="note">No user accounts have been created yet.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Account status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $email => $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($user['name'] ?? 'Unknown user')); ?></td>
                                    <td><?php echo htmlspecialchars((string) $email); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($user['created_at'] ?? 'Unknown')); ?></td>
                                    <td><span class="badge success">Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header" id="listings">
                <h2>Rental listings</h2>
                <span class="pill"><?php echo count($listings); ?> published listings</span>
            </div>
            <div class="panel-body">
                <?php if ($listingStatus !== ''): ?><div class="note" style="margin-bottom:16px; color:<?php echo str_contains($listingStatus, 'successfully') ? 'var(--success)' : 'var(--warning)'; ?>;"><?php echo htmlspecialchars($listingStatus); ?></div><?php endif; ?>
                <form method="post" class="listing-editor" enctype="multipart/form-data">
                    <input type="hidden" name="listing_action" value="<?php echo $editListing ? 'update' : 'add'; ?>" />
                    <?php if ($editListing): ?><input type="hidden" name="listing_index" value="<?php echo $editListingIndex; ?>" /><?php endif; ?>
                    <input name="title" placeholder="Property title" value="<?php echo htmlspecialchars((string) ($editListing['title'] ?? '')); ?>" required />
                    <input name="city" placeholder="City" value="<?php echo htmlspecialchars((string) ($editListing['city'] ?? '')); ?>" required />
                    <input name="price" type="number" min="0" step="50" placeholder="Monthly rent" value="<?php echo htmlspecialchars((string) ($editListing['priceValue'] ?? '')); ?>" required />
                    <select name="property_type"><option>House</option><option>Apartment</option><option>Condo</option><option>Townhome</option><option>Studio</option></select>
                    <select name="status">
                        <?php foreach ($listingStatuses as $status): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php echo (($editListing['status'] ?? 'For Rent') === $status) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option><?php endforeach; ?>
                    </select>
                    <select name="state">
                        <?php foreach ($usStates as $state): ?><option value="<?php echo htmlspecialchars($state); ?>" <?php echo (($editListing['state'] ?? 'California') === $state) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option><?php endforeach; ?>
                    </select>
                    <input name="details" placeholder="3 bed, 2 bath, 1,850 sq ft" value="<?php echo htmlspecialchars((string) ($editListing['details'] ?? '')); ?>" required />
                    <input name="address" placeholder="Street address or neighborhood" value="<?php echo htmlspecialchars((string) ($editListing['address'] ?? $editListing['streetAddress'] ?? '')); ?>" />
                    <input name="location" placeholder="Location detail" value="<?php echo htmlspecialchars((string) ($editListing['location'] ?? '')); ?>" />
                    <input name="zip_code" placeholder="ZIP code" value="<?php echo htmlspecialchars((string) ($editListing['zipCode'] ?? $editListing['zip'] ?? '')); ?>" />
                    <input name="contact_phone" type="tel" placeholder="Landlord phone: (346) 316-3488" value="<?php echo htmlspecialchars((string) ($editListing['contactPhone'] ?? '+13463163488')); ?>" required />
                    <textarea name="description" rows="3" placeholder="Property description"><?php echo htmlspecialchars((string) ($editListing['description'] ?? '')); ?></textarea>
                    <textarea name="utilities" rows="3" placeholder="Utilities and notes (water, gas, internet, etc.)"><?php echo htmlspecialchars((string) ($editListing['utilities'] ?? '')); ?></textarea>
                    <div class="amenity-grid">
                        <?php
                        $currentAmenities = array_map('strval', (array) ($editListing['amenities'] ?? []));
                        $amenityOptions = ['Parking', 'Air conditioning', 'Washer / Dryer', 'Pet friendly', 'Dishwasher', 'Balcony', 'Patio', 'Gym', 'Pool', 'Storage', 'Furnished', 'Security', 'Fireplace', 'EV charging', 'Hardwood floors'];
                        foreach ($amenityOptions as $amenity):
                            $checked = in_array($amenity, $currentAmenities, true) ? 'checked' : '';
                            echo '<label class="amenity-option"><input type="checkbox" name="amenities[]" value="' . htmlspecialchars($amenity) . '" ' . $checked . ' /> ' . htmlspecialchars($amenity) . '</label>';
                        endforeach;
                        ?>
                    </div>
                    <input name="photos[]" id="listingPhotoInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" multiple <?php echo $editListing ? '' : 'required'; ?> />
                    <div class="preview-picker" id="previewPicker">
                        <div class="preview-picker-header">
                            <strong>Choose a cover photo</strong>
                            <span>First image shown on the listing card</span>
                        </div>
                        <div class="preview-picker-grid" id="previewPickerGrid"></div>
                        <input type="hidden" name="featured_image_index" id="featuredImageIndex" value="0" />
                    </div>
                    <textarea name="gallery" rows="3" placeholder="Or paste image filename or URL, one per line"><?php echo htmlspecialchars(implode("\n", (array) ($editListing['gallery'] ?? []))); ?></textarea>
                    <button class="admin-submit" type="submit"><?php echo $editListing ? 'Save changes' : 'Add listing'; ?></button>
                    <?php if ($editListing): ?><a class="cancel-link" href="admin.php#listings">Cancel edit</a><?php endif; ?>
                </form>
                <?php if ($listings === []): ?>
                    <div class="note">No rental listings have been published.</div>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Property</th><th>Location</th><th>Price</th><th>Contact phone</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($listings as $listingIndex => $listing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($listing['title'] ?? 'Untitled')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($listing['city'] ?? '')); ?>, <?php echo htmlspecialchars((string) ($listing['state'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($listing['price'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($listing['contactPhone'] ?? '+13463163488')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($listing['status'] ?? 'For Rent')); ?></td>
                                    <td><a class="edit-link" href="admin.php?edit_listing=<?php echo $listingIndex; ?>#listings">Edit</a> <form method="post" onsubmit="return confirm('Delete this listing?');"><input type="hidden" name="listing_action" value="delete" /><input type="hidden" name="listing_index" value="<?php echo $listingIndex; ?>" /><button class="danger-btn" type="submit">Delete</button></form></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel" id="applications">
            <div class="panel-header">
                <div><h2>Rental applications</h2><p class="section-kicker">Review and payment tracking</p></div>
                <a href="applications.php" class="logout-btn" style="display:inline-flex; text-decoration:none; align-items:center; justify-content:center;">Manage statuses</a>
            </div>
            <div class="panel-body">
                <?php if (empty($applications)): ?>
                    <div class="note">No applications have been submitted yet.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Application ID</th>
                                <th>Applicant</th>
                                <th>Property</th>
                                <th>City</th>
                                <th>Price</th>
                                <th>Phone</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $application): ?>
                                <?php
                                $ref = htmlspecialchars((string) ($application['id'] ?? 'CF-UNKNOWN'));
                                $name = htmlspecialchars((string) ($application['name'] ?? 'Unknown applicant'));
                                $email = htmlspecialchars((string) ($application['email'] ?? 'No email provided'));
                                $property = htmlspecialchars((string) ($application['property'] ?? 'Unknown property'));
                                $city = htmlspecialchars((string) ($application['city'] ?? 'Unknown city'));
                                $price = htmlspecialchars((string) ($application['price'] ?? 'Price unavailable'));
                                $phone = htmlspecialchars((string) ($application['phone'] ?? 'Phone unavailable'));
                                $status = (string) ($application['application_status'] ?? 'Awaiting review');
                                $statusClass = (stripos($status, 'approved') !== false) ? 'success' : ((stripos($status, 'declined') !== false || stripos($status, 'rejected') !== false) ? 'warning' : 'info');
                                ?>
                                <tr>
                                    <td><?php echo $ref; ?></td>
                                    <td><?php echo $name; ?><div class="muted"><?php echo $email; ?></div></td>
                                    <td><?php echo $property; ?></td>
                                    <td><?php echo $city; ?></td>
                                    <td><?php echo $price; ?></td>
                                    <td><?php echo $phone; ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel" id="messages">
            <div class="panel-header"><div><p class="section-kicker">Communications department</p><h2>Application inbox</h2></div><span class="pill">Applicant conversations</span></div>
            <div class="panel-body">
                <div class="inbox-toolbar"><strong>Applicant threads</strong><button type="button" id="enableNotificationsBtn">Enable notifications</button></div>
                <div class="inbox-list">
                <?php $hasMessages = false; foreach ($applications as $application): $chatMessages = is_array($application['chat_messages'] ?? null) ? $application['chat_messages'] : []; if ($chatMessages === []) continue; $hasMessages = true; ?>
                    <article class="message-thread">
                        <h3><?php echo htmlspecialchars((string) ($application['name'] ?? 'Applicant')); ?> · <?php echo htmlspecialchars((string) ($application['property'] ?? 'Property')); ?></h3>
                        <?php foreach ($chatMessages as $chatMessage): ?><p><strong><?php echo (($chatMessage['from'] ?? '') === 'applicant') ? 'Applicant' : 'Cooper Fox'; ?>:</strong> <?php echo htmlspecialchars((string) ($chatMessage['text'] ?? '')); ?></p><?php endforeach; ?>
                        <form method="post" class="reply-form"><input type="hidden" name="application_action" value="reply" /><input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) ($application['id'] ?? '')); ?>" /><textarea name="reply" rows="2" placeholder="Reply to this applicant" required></textarea><button class="admin-submit" type="submit">Send reply</button></form>
                    </article>
                <?php endforeach; if (!$hasMessages): ?><div class="note">No applicant messages yet.</div><?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <script>
            const enableNotificationsButton = document.getElementById('enableNotificationsBtn');
            async function enableAdminNotifications() {
                if (!('Notification' in window)) return;
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') return;
                const configResponse = await fetch('push-config.php', { cache: 'no-store' });
                const config = await configResponse.json();
                if (!config.apiKey || config.apiKey.startsWith('YOUR_') || !config.vapidKey || config.vapidKey.startsWith('YOUR_')) return;
                if (!firebase.apps.length) firebase.initializeApp(config);
                const messaging = firebase.messaging();
                await navigator.serviceWorker.register('firebase-messaging-sw.js');
                const serviceWorkerRegistration = await navigator.serviceWorker.ready;
                serviceWorkerRegistration.active?.postMessage({ type: 'firebase-config', config });
                const token = await messaging.getToken({ vapidKey: config.vapidKey, serviceWorkerRegistration });
                if (token) {
                    await fetch('push-subscribe.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ token }) });
                }
            }
            enableNotificationsButton?.addEventListener('click', enableAdminNotifications);
            enableAdminNotifications();
      (function () {
        const photoInput = document.getElementById('listingPhotoInput');
        const previewPicker = document.getElementById('previewPicker');
        const previewPickerGrid = document.getElementById('previewPickerGrid');
        const featuredImageIndex = document.getElementById('featuredImageIndex');
        const galleryField = document.querySelector('textarea[name="gallery"]');
        if (!photoInput || !featuredImageIndex || !previewPickerGrid) return;

        let currentItems = [];
        let selectedIndex = 0;

        function parseGalleryValues() {
          if (!galleryField) return [];
          return String(galleryField.value || '')
            .split(/\r?\n/)
            .map((value) => value.trim())
            .filter(Boolean);
        }

        function reorderGalleryForSelection() {
          if (!galleryField || currentItems.length === 0) return;
          const urlEntries = currentItems.filter((item) => item.kind === 'url').map((item) => item.value);
          if (urlEntries.length) {
            galleryField.value = urlEntries.slice(selectedIndex, urlEntries.length)
              .concat(urlEntries.slice(0, selectedIndex))
              .join('\n');
          }
        }

        function createChoiceButton(item, index) {
          const choice = document.createElement('button');
          choice.type = 'button';
          choice.className = 'preview-choice' + (index === selectedIndex ? ' is-primary' : '');
          choice.setAttribute('data-index', String(index));
          choice.title = 'Use as cover photo';

          const image = document.createElement('img');
          image.src = item.value;
          image.loading = 'lazy';
          image.alt = 'Listing preview choice ' + (index + 1);
          image.onerror = function () {
            this.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400"><rect width="600" height="400" fill="#0f1d2b"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#d8bd84" font-family="sans-serif" font-size="34">Preview unavailable</text></svg>');
          };

          const tag = document.createElement('span');
          tag.className = 'preview-choice-tag';
          tag.textContent = index === selectedIndex ? 'Cover' : 'Select';

          choice.appendChild(image);
          choice.appendChild(tag);
          choice.addEventListener('click', function () {
            selectedIndex = index;
            featuredImageIndex.value = String(selectedIndex);
            Array.from(previewPickerGrid.children).forEach((button, buttonIndex) => {
              button.classList.toggle('is-primary', buttonIndex === selectedIndex);
              const tagNode = button.querySelector('.preview-choice-tag');
              if (tagNode) tagNode.textContent = buttonIndex === selectedIndex ? 'Cover' : 'Select';
            });
            reorderGalleryForSelection();
          });

          return choice;
        }

        function renderPreviewPicker() {
          const existingUrls = parseGalleryValues();
          const files = Array.from(photoInput.files || []);

          const uploadedItemsPromise = files.length
            ? Promise.all(files.map((file) => new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function () {
                  resolve({ kind: 'upload', value: String(reader.result || ''), name: file.name });
                };
                reader.onerror = function () {
                  resolve({ kind: 'upload', value: '', name: file.name });
                };
                reader.readAsDataURL(file);
              })))
            : Promise.resolve([]);

          uploadedItemsPromise.then((uploadedUrls) => {
            const allItems = existingUrls.map((value) => ({ kind: 'url', value, name: value }))
              .concat(uploadedUrls.filter((entry) => entry.value));
            currentItems = allItems;

            previewPickerGrid.innerHTML = '';
            if (currentItems.length === 0) {
              const emptyState = document.createElement('div');
              emptyState.className = 'note';
              emptyState.textContent = 'Choose some images and then pick the cover photo here.';
              previewPickerGrid.appendChild(emptyState);
              featuredImageIndex.value = '0';
              return;
            }

            selectedIndex = Math.min(Number(featuredImageIndex.value || 0), currentItems.length - 1);
            featuredImageIndex.value = String(selectedIndex);

            currentItems.forEach((item, index) => {
              previewPickerGrid.appendChild(createChoiceButton(item, index));
            });
          });
        }

        renderPreviewPicker();

        photoInput.addEventListener('change', renderPreviewPicker);
        if (galleryField) {
          galleryField.addEventListener('input', renderPreviewPicker);
        }
      })();
    </script>
</body>
</html>
