<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: admin-login.html'); exit; }
$file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
$applications = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
$applications = is_array($applications) ? $applications : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (string) ($_POST['Id'] ?? '');
    $field = (string) ($_POST['Field'] ?? '');
    $value = trim((string) ($_POST['Value'] ?? ''));
    $allowed = ['application_status', 'fee_status', 'deposit_status', 'rent_status'];
    if (in_array($field, $allowed, true)) {
        foreach ($applications as &$application) {
            if (($application['id'] ?? '') === $id) {
                $application[$field] = $value;
                if (!isset($application['status_updated_at']) || !is_array($application['status_updated_at'])) {
                    $application['status_updated_at'] = [];
                }
                $application['status_updated_at'][$field] = gmdate('c');
                if ($field === 'application_status' && $value === 'Approved' && (($application['fee_status'] ?? '') !== 'Paid')) { $application['fee_status'] = 'Payment due'; }
                break;
            }
        }
        unset($application);
        file_put_contents($file, json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    header('Location: applications.php'); exit;
}
function adminValue(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Applications | Cooper Fox Realty</title><link rel="stylesheet" href="css/style.css.css"></head>
<body><main class="account-page"><section class="account-card" style="max-width: 900px"><a class="account-brand" href="index.html"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a><div class="account-heading"><p class="account-kicker">Private owner workspace</p><h1>Application review</h1><p>Approve applicants, confirm fee payment after chatting with them, and update deposit and rent statuses.</p></div>
<?php if (!$applications) : ?><p class="account-status">No applications have been submitted yet.</p><?php endif; ?>
<?php foreach ($applications as $application) : ?><article class="application-review"><h2><?php echo adminValue((string) ($application['property'] ?? 'Property')); ?></h2><p><strong><?php echo adminValue((string) ($application['name'] ?? '')); ?></strong> · <?php echo adminValue((string) ($application['email'] ?? '')); ?> · <?php echo adminValue((string) ($application['phone'] ?? '')); ?></p><p><?php echo adminValue((string) ($application['message'] ?? 'No message provided.')); ?></p><div class="review-fields">
<?php $statusControls = ['application_status' => ['label' => 'Application approval', 'options' => ['Awaiting fee payment', 'Under review', 'Approved', 'Denied']], 'fee_status' => ['label' => 'Application fee', 'options' => ['Payment instructions in chat', 'Payment due', 'Paid']], 'deposit_status' => ['label' => 'Security deposit', 'options' => ['Not requested', 'Requested', 'Paid']], 'rent_status' => ['label' => 'Rent fee', 'options' => ['Not requested', 'Requested', 'Paid']]]; foreach ($statusControls as $field => $settings) : ?><form method="POST"><input type="hidden" name="Id" value="<?php echo adminValue((string) ($application['id'] ?? '')); ?>"><input type="hidden" name="Field" value="<?php echo adminValue($field); ?>"><label><?php echo adminValue($settings['label']); ?><select name="Value" onchange="this.form.submit()"><?php foreach ($settings['options'] as $option) : ?><option value="<?php echo adminValue($option); ?>" <?php echo (($application[$field] ?? '') === $option ? 'selected' : ''); ?>><?php echo adminValue($option); ?></option><?php endforeach; ?></select></label></form><?php endforeach; ?></div><p class="form-help">Reference: <?php echo adminValue((string) ($application['id'] ?? '')); ?></p></article><?php endforeach; ?></section></main></body></html>
