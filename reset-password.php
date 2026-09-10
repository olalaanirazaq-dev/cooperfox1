<?php
declare(strict_types=1);

$email = strtolower(trim((string) ($_GET['email'] ?? $_POST['Email'] ?? '')));
$token = (string) ($_GET['token'] ?? $_POST['Token'] ?? '');
$usersFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
$message = '';
$success = false;

function loadResetUsers(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $users = json_decode((string) file_get_contents($file), true);
    return is_array($users) ? $users : [];
}

function saveResetUsers(string $file, array $users): void
{
    $handle = fopen($file, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to access account storage.');
    }
    ftruncate($handle, 0);
    fwrite($handle, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['Password'] ?? '');
    $users = loadResetUsers($usersFile);
    $user = $users[$email] ?? null;
    $validToken = is_array($user)
        && hash_equals((string) ($user['reset_token_hash'] ?? ''), hash('sha256', $token))
        && (int) ($user['reset_expires'] ?? 0) >= time();

    if (!$validToken) {
        $message = 'This reset link is invalid or has expired.';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$/', $password)) {
        $message = 'Use at least 12 characters with uppercase, lowercase, numbers, and a symbol.';
    } else {
        $users[$email]['password'] = password_hash($password, PASSWORD_DEFAULT);
        unset($users[$email]['reset_token_hash'], $users[$email]['reset_expires']);
        saveResetUsers($usersFile, $users);
        $success = true;
        $message = 'Your password has been reset. You can now sign in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset password | Cooper Fox Realty</title>
    <link rel="stylesheet" href="cooper-fox-stylesheet.css" />
</head>
<body>
    <main class="account-page">
        <section class="account-card" aria-labelledby="resetTitle">
            <a class="account-brand" href="index.html" aria-label="Back to Cooper Fox Realty home"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a>
            <div class="account-heading">
                <p class="account-kicker">Account security</p>
                <h1 id="resetTitle"><?php echo $success ? 'Password updated' : 'Choose a new password'; ?></h1>
                <p><?php echo htmlspecialchars($message ?: 'Create a new password for your Cooper Fox Realty account.', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php if (!$success) : ?>
                <form class="account-form" method="POST">
                    <input type="hidden" name="Email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="Token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" />
                    <label for="newPassword">New password</label>
                    <input id="newPassword" type="password" name="Password" minlength="12" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}" title="Use at least 12 characters with uppercase, lowercase, numbers, and a symbol." autocomplete="new-password" required />
                    <button class="account-submit" type="submit">Update password</button>
                </form>
            <?php endif; ?>
            <p class="account-footer"><a href="SignUp.html#signin">Return to sign in</a></p>
        </section>
    </main>
</body>
</html>