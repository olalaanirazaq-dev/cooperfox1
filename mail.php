<?php
declare(strict_types=1);

session_start();

if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'mail-config.php';
}

$action = $_POST['Action'] ?? '';
$email = strtolower(trim((string) ($_POST['Email'] ?? '')));
$password = (string) ($_POST['Password'] ?? '');
$name = trim((string) ($_POST['Name'] ?? ''));
$redirect = 'SignUp.html';
$adminEmail = getenv('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? 'admin@cooperfoxrealty.com');
$adminPassword = getenv('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? 'CooperFoxAdmin2026!');

function redirectWithStatus(string $status, string $mode = 'signin'): never
{
    header('Location: ' . $GLOBALS['redirect'] . '?status=' . rawurlencode($status) . '&mode=' . rawurlencode($mode));
    exit;
}

function isStrongPassword(string $password): bool
{
    if (strlen($password) < 12) {
        return false;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    if (!preg_match('/\d/', $password)) {
        return false;
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }

    return true;
}

function readUsers(string $usersFile): array
{
    if (!is_file($usersFile)) {
        return [];
    }

    $users = json_decode((string) file_get_contents($usersFile), true);
    return is_array($users) ? $users : [];
}

function saveUsers(string $usersFile, array $users): void
{
    $directory = dirname($usersFile);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the account data directory.');
    }

    $handle = fopen($usersFile, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock account storage.');
    }

    ftruncate($handle, 0);
    fwrite($handle, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function getSmtpConfig(): array
{
    $host = getenv('MAIL_SMTP_HOST') ?: ($_ENV['MAIL_SMTP_HOST'] ?? 'smtp.mail.com');
    $port = getenv('MAIL_SMTP_PORT') ?: ($_ENV['MAIL_SMTP_PORT'] ?? '587');
    $secure = getenv('MAIL_SMTP_SECURE') ?: ($_ENV['MAIL_SMTP_SECURE'] ?? 'tls');
    $username = getenv('MAIL_SMTP_USERNAME') ?: ($_ENV['MAIL_SMTP_USERNAME'] ?? 'noreply@yourdomain.com');
    $password = getenv('MAIL_SMTP_PASSWORD') ?: ($_ENV['MAIL_SMTP_PASSWORD'] ?? '');
    $from = getenv('MAIL_SMTP_FROM') ?: ($_ENV['MAIL_SMTP_FROM'] ?? 'noreply@yourdomain.com');
    $fromName = getenv('MAIL_SMTP_FROM_NAME') ?: ($_ENV['MAIL_SMTP_FROM_NAME'] ?? 'Cooper Fox Realty');
    $domain = getenv('MAIL_SMTP_DOMAIN') ?: ($_ENV['MAIL_SMTP_DOMAIN'] ?? 'yourdomain.com');
    $siteUrl = getenv('SITE_BASE_URL') ?: ($_ENV['SITE_BASE_URL'] ?? 'https://yourdomain.com');

    return [
        'host' => $host,
        'port' => (int) $port,
        'secure' => $secure,
        'username' => $username,
        'password' => (string) $password,
        'from' => $from,
        'from_name' => $fromName,
        'domain' => $domain,
        'site_url' => $siteUrl,
    ];
}

function smtpReadResponse($socket): array
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (strlen($line) >= 3 && substr($line, 3, 1) === ' ') {
            break;
        }
    }

    $code = 0;
    if (preg_match('/^(\d{3})/', $response, $matches)) {
        $code = (int) $matches[1];
    }

    return ['code' => $code, 'text' => trim($response)];
}

function smtpSendCommand($socket, string $command): array
{
    fwrite($socket, $command . "\r\n");
    return smtpReadResponse($socket);
}

function sendSmtpEmail(string $to, string $subject, string $body): bool
{
    $config = getSmtpConfig();
    if ($config['password'] === '') {
        return false;
    }

    $scheme = $config['secure'] === 'ssl' ? 'ssl://' : '';
    $socket = fsockopen($scheme . $config['host'], $config['port'], $errno, $errstr, 30);
    if ($socket === false) {
        error_log('SMTP connection failed: ' . $errstr);
        return false;
    }

    stream_set_timeout($socket, 30);
    $banner = smtpReadResponse($socket);
    if ($banner['code'] !== 220) {
        fclose($socket);
        return false;
    }

    $reply = smtpSendCommand($socket, 'EHLO ' . $config['domain']);
    if ($reply['code'] < 200 || $reply['code'] >= 400) {
        fclose($socket);
        return false;
    }

    if ($config['secure'] === 'tls') {
        $starttls = smtpSendCommand($socket, 'STARTTLS');
        if ($starttls['code'] !== 220) {
            fclose($socket);
            return false;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }

        $reply = smtpSendCommand($socket, 'EHLO ' . $config['domain']);
        if ($reply['code'] < 200 || $reply['code'] >= 400) {
            fclose($socket);
            return false;
        }
    }

    $auth = smtpSendCommand($socket, 'AUTH LOGIN');
    if ($auth['code'] !== 334) {
        fclose($socket);
        return false;
    }

    $userReply = smtpSendCommand($socket, base64_encode($config['username']));
    if ($userReply['code'] !== 334) {
        fclose($socket);
        return false;
    }

    $passReply = smtpSendCommand($socket, base64_encode($config['password']));
    if ($passReply['code'] !== 235) {
        fclose($socket);
        return false;
    }

    $mailFrom = smtpSendCommand($socket, 'MAIL FROM:<' . $config['from'] . '>');
    if ($mailFrom['code'] !== 250) {
        fclose($socket);
        return false;
    }

    $rcpt = smtpSendCommand($socket, 'RCPT TO:<' . $to . '>');
    if ($rcpt['code'] !== 250 && $rcpt['code'] !== 251) {
        fclose($socket);
        return false;
    }

    $data = smtpSendCommand($socket, 'DATA');
    if ($data['code'] !== 354) {
        fclose($socket);
        return false;
    }

    $message = "From: \"" . $config['from_name'] . "\" <" . $config['from'] . ">\r\n";
    $message .= "To: <" . $to . ">\r\n";
    $message .= "Subject: " . str_replace("\r", '', str_replace("\n", ' ', $subject)) . "\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $body . "\r\n.";

    fwrite($socket, $message . "\r\n");
    $final = smtpReadResponse($socket);
    if ($final['code'] !== 250) {
        fclose($socket);
        return false;
    }

    $quit = smtpSendCommand($socket, 'QUIT');
    fclose($socket);

    return $quit['code'] === 221 || $quit['code'] === 250;
}

if ($action === 'sign_out') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.html');
    exit;
}

if ($action === 'admin_sign_in') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithStatus('Enter a valid admin email address.', 'signin');
    }

    if ($email === strtolower($adminEmail) && $password === $adminPassword) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = 'Admin';
        header('Location: admin.php?admin=1#adminPanel');
        exit;
    }

    redirectWithStatus('The admin email or password is incorrect.', 'signin');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithStatus('Enter a valid email address.', $action === 'sign_up' ? 'signup' : 'signin');
}

$usersFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
$users = readUsers($usersFile);

try {
    if ($action === 'sign_up') {
        if ($name === '' || strlen($name) > 100) {
            redirectWithStatus('Enter your full name.', 'signup');
        }

        if (!isStrongPassword($password)) {
            redirectWithStatus('Use at least 12 characters with uppercase, lowercase, numbers, and a symbol.', 'signup');
        }

        if (isset($users[$email])) {
            redirectWithStatus('An account with that email already exists. Try signing in.', 'signin');
        }

        $users[$email] = [
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => gmdate('c'),
        ];

        saveUsers($usersFile, $users);

        session_regenerate_id(true);
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;

        header('Location: index.html');
        exit;
    }

    if ($action === 'sign_in') {
        if (!isset($users[$email]) || !password_verify($password, (string) ($users[$email]['password'] ?? ''))) {
            redirectWithStatus('The email or password is incorrect.', 'signin');
        }

        session_regenerate_id(true);
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $users[$email]['name'];

        header('Location: index.html');
        exit;
    }

    if ($action === 'password_reset_request') {
        if (!isset($users[$email])) {
            redirectWithStatus('No account was found for that email address.', 'signin');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithStatus('Enter a valid email address.', 'signin');
        }

        $token = bin2hex(random_bytes(32));
        $users[$email]['reset_token_hash'] = hash('sha256', $token);
        $users[$email]['reset_expires'] = time() + 3600;
        saveUsers($usersFile, $users);

        $baseUrl = getSmtpConfig()['site_url'];
        $resetUrl = $baseUrl . '/reset-password.php?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);
        $emailBody = "Hello,\n\nWe received a request to reset your Cooper Fox Realty password.\n\nUse the link below to set a new password:\n\n" . $resetUrl . "\n\nIf you did not request this, you can ignore this message.\n";

        $emailSent = sendSmtpEmail($email, 'Reset your Cooper Fox Realty password', $emailBody);
        $statusMessage = $emailSent
            ? 'Reset link sent to your email.'
            : 'Reset link created. Use the link below to choose a new password.';

        header('Location: forgot-password.php?email=' . rawurlencode($email) . '&token=' . rawurlencode($token) . '&status=' . rawurlencode($statusMessage));
        exit;
    }

    if ($action === 'password_reset') {
        redirectWithStatus('Password reset is disabled in local mode. Please create a new account or contact support.', 'signin');
    }

    redirectWithStatus('That account action is not available.');
} catch (Throwable $error) {
    error_log($error->getMessage());
    redirectWithStatus('The account service is temporarily unavailable.');
}

