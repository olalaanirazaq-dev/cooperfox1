<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_email'])) {
    header('Location: SignUp.html#signin');
    exit;
}

$currentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
$applicationsFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
$storedApplications = is_file($applicationsFile) ? json_decode((string) file_get_contents($applicationsFile), true) : [];
$storedApplications = is_array($storedApplications) ? $storedApplications : [];

$myApplications = array_values(array_filter(
    $storedApplications,
    static fn (array $application): bool => strtolower((string) ($application['email'] ?? '')) === $currentEmail
));

function appValue(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My applications | Cooper Fox Realty</title>
    <link rel="stylesheet" href="css/style.css.css" />
</head>
<body>
    <main class="account-page">
        <section class="account-card" style="max-width: 980px;">
            <a class="account-brand" href="index.html" aria-label="Back to Cooper Fox Realty home">
                <span class="account-mark">CF</span>
                <span>Cooper Fox Realty</span>
            </a>

            <div class="account-heading">
                <p class="account-kicker">Your account</p>
                <h1>My applications</h1>
                <p>Track your rental requests, payment updates, and next steps.</p>
            </div>

            <?php if (!$myApplications) : ?>
                <p class="account-status">You have not submitted any rental applications yet.</p>
                <a class="account-submit" href="index.html#listings" style="display:inline-block; text-decoration:none; margin-top: 10px;">Browse available homes</a>
            <?php else : ?>
                <?php foreach ($myApplications as $application) : ?>
                    <article class="application-review" style="margin-bottom:18px;">
                        <h2><?php echo appValue((string) ($application['property'] ?? 'Property')); ?></h2>
                        <p>
                            <strong><?php echo appValue((string) ($application['city'] ?? '')); ?></strong>
                            <span> · </span>
                            <span><?php echo appValue((string) ($application['price'] ?? '')); ?></span>
                        </p>
                        <p>
                            Status: <strong><?php echo appValue((string) ($application['application_status'] ?? 'Under review')); ?></strong>
                        </p>
                        <p>
                            Fee status: <strong><?php echo appValue((string) ($application['fee_status'] ?? 'Not started')); ?></strong>
                        </p>
                        <p>
                            Deposit status: <strong><?php echo appValue((string) ($application['deposit_status'] ?? 'Not requested')); ?></strong>
                        </p>
                        <p>
                            Rent status: <strong><?php echo appValue((string) ($application['rent_status'] ?? 'Not requested')); ?></strong>
                        </p>
                        <p class="form-help">Reference: <?php echo appValue((string) ($application['id'] ?? '')); ?></p>
                        <a class="account-submit" href="application-chat.php?id=<?php echo rawurlencode((string) ($application['id'] ?? '')); ?>" style="display:inline-block; text-decoration:none; margin-top:10px;">Open application</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

            <p class="account-footer">
                <a href="index.html">← Back to home</a>
            </p>
        </section>
    </main>
</body>
</html>
