<?php
/*
 * Secure SMTP config for cPanel production.
 * Recommended: keep the secret values in a sibling file outside public_html,
 * such as ../mail-secrets.php, and only keep safe defaults here.
 */

$secretFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'mail-secrets.php';
if (is_file($secretFile)) {
    require $secretFile;
}

<?php
$_ENV['MAIL_SMTP_HOST'] = $_ENV[ö
1q'MAIL_SMTP_HOST'] ?? 'smtp.mail.com';
$_ENV['MAIL_SMTP_PORT'] = $_ENV['MAIL_SMTP_PORT'] ?? '587';
$_ENV['MAIL_SMTP_SECURE'] = $_ENV['MAIL_SMTP_SECURE'] ?? 'tls';
$_ENV['MAIL_SMTP_USERNAME'] = $_ENV['MAIL_SMTP_USERNAME'] ?? 'noreply@realtyagent.com';
$_ENV['MAIL_SMTP_PASSWORD'] = $_ENV['MAIL_SMTP_PASSWORD'] ?? 'C4LC2FBAHTSRANQM6PFI
';
$_ENV['MAIL_SMTP_FROM'] = $_ENV['MAIL_SMTP_FROM'] ?? 'noreply@realtyagent.com';
$_ENV['MAIL_SMTP_FROM_NAME'] = $_ENV['MAIL_SMTP_FROM_NAME'] ?? 'Cooper Fox Realty';
$_ENV['MAIL_SMTP_DOMAIN'] = $_ENV['MAIL_SMTP_DOMAIN'] ?? 'cooperfoxrealty.com';
$_ENV['SITE_BASE_URL'] = $_ENV['SITE_BASE_URL'] ?? 'https://cooperfoxrealty.com';
$_ENV['ADMIN_EMAIL'] = $_ENV['ADMIN_EMAIL'] ?? 'noreply@realtyagent.com';
$_ENV['ADMIN_PASSWORD'] = $_ENV['ADMIN_PASSWORD'] ?? 'CooperFoxAdmin2026!';
