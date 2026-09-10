<?php
declare(strict_types=1);

$email = strtolower(trim((string) ($_GET['email'] ?? $_POST['Email'] ?? '')));
$message = '';
$success = false;
$usersFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';

function verifyValue(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim((string) ($_POST['Code'] ?? ''));
    $users = is_file($usersFile) ? json_decode((string) file_get_contents($usersFile), true) : [];
    $user = is_array($users) ? ($users[$email] ?? null) : null;
    $valid = is_array($user) && preg_match('/^\d{6}$/', $code) && hash_equals((string) ($user['verification_code_hash'] ?? ''), hash('sha256', $code)) && (int) ($user['verification_expires'] ?? 0) >= time();
    if (!$valid) {
        $message = 'That confirmation code is invalid or expired.';
    } else {
        $users[$email]['email_verified'] = true;
        unset($users[$email]['verification_code_hash'], $users[$email]['verification_expires']);
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $success = true;
        $message = 'Your email is confirmed. You can now sign in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Confirm email | Cooper Fox Realty</title><link rel="stylesheet" href="css/style.css.css"></head>
<body><main class="account-page"><section class="account-card" aria-labelledby="verifyTitle"><a class="account-brand" href="index.html"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a><div class="account-heading"><p class="account-kicker">Email confirmation</p><h1 id="verifyTitle"><?php echo $success ? 'Email confirmed' : 'Check your email'; ?></h1><p><?php echo verifyValue($message ?: 'Enter the six-digit confirmation code sent from noreply@cooperfox.com.'); ?></p></div><?php if (!$success) : ?><form class="account-form" method="POST"><input type="hidden" name="Email" value="<?php echo verifyValue($email); ?>"><label for="verificationCode">Confirmation code</label><input id="verificationCode" name="Code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required><button class="account-submit" type="submit">Confirm email</button></form><?php else : ?><a class="account-submit" href="SignUp.html#signin">Go to sign in</a><?php endif; ?><p class="account-footer"><a href="SignUp.html#signup">Back to signup</a></p></section></main></body></html>