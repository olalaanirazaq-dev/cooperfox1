<?php
declare(strict_types=1);

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$property = trim((string) ($_GET['property'] ?? $_POST['Property'] ?? ''));
$city = trim((string) ($_GET['city'] ?? $_POST['City'] ?? ''));
$price = trim((string) ($_GET['price'] ?? $_POST['Price'] ?? ''));
$message = '';
$success = false;
$applicationId = '';
$application = null;

function applicationValue(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function notifyAdminOfApplication(array $application): void
{
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? 'admin@cooperfoxrealty.com');
    $landlordEmail = getenv('LANDLORD_EMAIL') ?: ($_ENV['LANDLORD_EMAIL'] ?? 'Coopermathewfoxhomes@realtyagent.com');
    if ($adminEmail === '' && $landlordEmail === '') {
        return;
    }

    $to = array_filter([$adminEmail, $landlordEmail], static fn ($value) => $value !== '');
    if ($to === []) {
        return;
    }

    $subject = 'New rental application: ' . ($application['id'] ?? 'Pending');
    $body = "New application received\n\n";
    $body .= "Application ID: " . ($application['id'] ?? 'Pending') . "\n";
    $body .= "Applicant: " . ($application['name'] ?? '') . "\n";
    $body .= "Email: " . ($application['email'] ?? '') . "\n";
    $body .= "Phone: " . ($application['phone'] ?? '') . "\n";
    $body .= "Property: " . ($application['property'] ?? '') . "\n";
    $body .= "City: " . ($application['city'] ?? '') . "\n";
    $body .= "Price: " . ($application['price'] ?? '') . "\n";
    $body .= "Message: " . ($application['message'] ?? '') . "\n";
    $body .= "Submitted at: " . ($application['submitted_at'] ?? gmdate('c')) . "\n";

    $from = 'noreply@cooperfoxrealty.com';
    $headers = "From: Cooper Fox Realty <{$from}>\r\n";
    $headers .= "Reply-To: " . ($application['email'] ?? $from) . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail(implode(', ', $to), $subject, $body, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['id'])) {
    $applicationsFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
    $storedApplications = is_file($applicationsFile) ? json_decode((string) file_get_contents($applicationsFile), true) : [];
    foreach (is_array($storedApplications) ? $storedApplications : [] as $storedApplication) {
        if (($storedApplication['id'] ?? '') === (string) $_GET['id']) { $application = $storedApplication; break; }
    }
    if (!$application || (($application['fee_status'] ?? '') !== 'Paid')) {
        header('Location: application-chat.php?id=' . rawurlencode((string) ($_GET['id'] ?? '')));
        exit;
    }
    $property = (string) ($application['property'] ?? $property);
    $city = (string) ($application['city'] ?? $city);
    $price = (string) ($application['price'] ?? $price);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || ($_GET['ajax'] ?? '') === '1';
    $name = trim((string) ($_POST['Name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['Email'] ?? '')));
    $phone = trim((string) ($_POST['Phone'] ?? ''));
    $address = trim((string) ($_POST['Address'] ?? ''));
    $note = trim((string) ($_POST['Message'] ?? ''));
    $moveDate = trim((string) ($_POST['MoveDate'] ?? ''));
    $employment = trim((string) ($_POST['Employment'] ?? ''));
    $income = trim((string) ($_POST['Income'] ?? ''));
    $occupants = trim((string) ($_POST['Occupants'] ?? ''));
    $pets = trim((string) ($_POST['Pets'] ?? ''));
    $creditScore = trim((string) ($_POST['CreditScore'] ?? ''));
    $monthlySalary = trim((string) ($_POST['MonthlySalary'] ?? ''));
    $feePaymentMethod = trim((string) ($_POST['FeePaymentMethod'] ?? ''));
    $availableFunds = trim((string) ($_POST['AvailableFunds'] ?? ''));
    $agreedToTerms = ($_POST['AgreeToTerms'] ?? '') === 'yes';
    $stage = trim((string) ($_POST['Stage'] ?? 'full'));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $property === '') {
        $message = 'Please enter your name, email, phone number, and select a property.';
    } elseif (!$agreedToTerms) {
        $message = 'Please confirm that the application information is complete and accurate.';
    } elseif ($stage === 'conversation') {
        $applicationsFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
        $directory = dirname($applicationsFile);
        if (!is_dir($directory)) { mkdir($directory, 0700, true); }
        $applications = is_file($applicationsFile) ? json_decode((string) file_get_contents($applicationsFile), true) : [];
        $applications = is_array($applications) ? $applications : [];
        $applicationId = 'CF-' . strtoupper(bin2hex(random_bytes(4)));
        $newApplication = ['id' => $applicationId, 'property' => $property, 'city' => $city, 'price' => $price, 'name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address, 'message' => $note, 'move_date' => $moveDate, 'employment' => $employment, 'income' => $income, 'occupants' => $occupants, 'pets' => $pets, 'credit_score' => $creditScore, 'monthly_salary' => $monthlySalary, 'available_funds' => $availableFunds, 'agreed_to_terms' => true, 'application_status' => 'Awaiting fee payment', 'fee_status' => 'Payment instructions in chat', 'deposit_status' => 'Not requested', 'rent_status' => 'Not requested', 'chat_messages' => [['from' => 'applicant', 'text' => $note ?: 'I would like to apply for this property.', 'sent_at' => gmdate('c')]], 'submitted_at' => gmdate('c')];
        $applications[] = $newApplication;
        file_put_contents($applicationsFile, json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        notifyAdminOfApplication($newApplication);
        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'redirect' => 'application-chat.php?id=' . rawurlencode($applicationId) . '&simple=1']);
            exit;
        }
        header('Location: application-chat.php?id=' . rawurlencode($applicationId) . '&simple=1');
        exit;
    } else {
        $applicationsFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
        $directory = dirname($applicationsFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $applications = is_file($applicationsFile)
            ? json_decode((string) file_get_contents($applicationsFile), true)
            : [];
        if (!is_array($applications)) {
            $applications = [];
        }
        $existingApplicationId = trim((string) ($_POST['ApplicationId'] ?? ''));
        if ($existingApplicationId !== '') {
            foreach ($applications as &$existingApplication) {
                if (($existingApplication['id'] ?? '') === $existingApplicationId && (($existingApplication['fee_status'] ?? '') === 'Paid')) {
                    $existingApplication = array_merge($existingApplication, ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address, 'message' => $note, 'move_date' => $moveDate, 'employment' => $employment, 'income' => $income, 'occupants' => $occupants, 'pets' => $pets, 'credit_score' => $creditScore, 'monthly_salary' => $monthlySalary, 'available_funds' => $availableFunds, 'agreed_to_terms' => true, 'application_status' => 'Completed']);
                    $applicationId = $existingApplicationId;
                    break;
                }
            }
            unset($existingApplication);
            file_put_contents($applicationsFile, json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            header('Location: application-chat.php?id=' . rawurlencode($applicationId) . '&simple=1');
            exit;
        }
        $applicationId = 'CF-' . strtoupper(bin2hex(random_bytes(4)));
        $newApplication = [
            'id' => $applicationId,
            'property' => $property,
            'city' => $city,
            'price' => $price,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'message' => $note,
            'move_date' => $moveDate,
            'employment' => $employment,
            'income' => $income,
            'occupants' => $occupants,
            'pets' => $pets,
            'credit_score' => $creditScore,
            'monthly_salary' => $monthlySalary,
            'fee_payment_method' => $feePaymentMethod,
            'available_funds' => $availableFunds,
            'agreed_to_terms' => true,
            'application_status' => 'Completed',
            'fee_status' => 'Paid',
            'deposit_status' => 'Not requested',
            'rent_status' => 'Not requested',
            'submitted_at' => gmdate('c'),
        ];
        $applications[] = $newApplication;
        file_put_contents($applicationsFile, json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        notifyAdminOfApplication($newApplication);
        $success = true;
        header('Location: application-chat.php?id=' . rawurlencode($applicationId) . '&simple=1');
        exit;
    }
}

$defaultName = (string) ($_SESSION['user_name'] ?? '');
$defaultEmail = (string) ($_SESSION['user_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Apply for <?php echo applicationValue($property ?: 'a property'); ?> | Cooper Fox Realty</title>
    <link rel="stylesheet" href="stylesheet.css" />
    <style>
        body {
            background: linear-gradient(135deg, #edf3f1 0%, #f9f5ee 100%);
        }

        .account-page {
            padding: 36px 18px;
        }

        .application-form-card {
            max-width: 940px;
            padding: 22px 22px 26px;
            border-radius: 24px;
            background: rgba(255,255,255,0.97);
        }

        .application-hero-image {
            position: relative;
            display: none;
            overflow: hidden;
            border-radius: 18px;
            margin-top: 18px;
            min-height: 180px;
            background: linear-gradient(135deg, #112c28 0%, #214f46 100%);
        }

        .application-hero-image img {
            display: none;
        }

        .application-hero-image div {
            position: absolute;
            inset: auto 18px 18px 18px;
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(8, 22, 20, 0.48);
            backdrop-filter: blur(6px);
            color: #fff;
        }

        .application-hero-image span {
            display: block;
            font-size: 0.7rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .application-hero-image strong {
            display: block;
            font-size: 1.25rem;
            margin-top: 6px;
        }

        .application-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
            margin-top: 22px;
        }

        .application-form > label,
        .application-form > textarea,
        .application-form > input,
        .application-form > select,
        .application-form > .check-label,
        .application-form > .form-help,
        .application-form > .account-status,
        .application-form > .review-summary,
        .application-form > .review-actions,
        .application-form > p,
        .application-form > button,
        .application-form > #reviewStep {
            grid-column: 1 / -1;
        }

        .application-form label {
            display: grid;
            gap: 8px;
            color: #1d312e;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .application-form input,
        .application-form select,
        .application-form textarea {
            width: 100%;
            min-height: 52px;
            padding: 0 14px;
            border: 1px solid rgba(15,31,27,0.12);
            border-radius: 12px;
            background: #fff;
            color: #172a28;
            font: inherit;
            box-shadow: inset 0 1px 2px rgba(14,37,34,0.03);
        }

        .application-form textarea {
            min-height: 120px;
            padding: 14px;
        }

        .application-form .check-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 6px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(13,122,95,0.12);
            background: rgba(13,122,95,0.03);
            color: #23413d;
            font-size: 0.82rem;
            text-transform: none;
            letter-spacing: 0;
            line-height: 1.6;
        }

        .application-form .check-label input {
            width: 18px;
            height: 18px;
            min-height: 18px;
            margin-top: 3px;
        }

        .form-progress {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
            padding: 6px 8px;
            border-radius: 14px;
            background: rgba(13,122,95,0.04);
            border: 1px solid rgba(13,122,95,0.08);
        }

        .form-progress span {
            flex: 1;
            min-width: 120px;
            padding: 10px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.7);
            text-align: center;
            color: #58706c;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .form-progress span.active {
            background: linear-gradient(135deg, #102722 0%, #1d4d46 100%);
            color: #fff;
        }

        .form-help,
        .account-status {
            grid-column: 1 / -1;
            margin: 0;
            padding: 12px 14px;
            border-radius: 12px;
            line-height: 1.6;
            font-size: 0.82rem;
        }

        .form-help {
            color: #5d7270;
            background: rgba(15,31,27,0.02);
            border: 1px solid rgba(15,31,27,0.06);
        }

        .account-status {
            border: 1px solid rgba(13,122,95,0.12);
            background: rgba(13,122,95,0.05);
            color: #193d35;
        }

        .review-summary {
            display: grid;
            gap: 10px;
            margin-top: 18px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(13,122,95,0.04);
            border: 1px solid rgba(13,122,95,0.12);
        }

        .review-row {
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(14,37,34,0.06);
            color: #173c35;
            font-size: 0.82rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .secondary-button {
            flex: 1;
            min-height: 52px;
            border: 1px solid rgba(15,31,27,0.12);
            border-radius: 999px;
            background: #fff;
            color: #173c35;
            font: 700 0.95rem Arial, sans-serif;
            cursor: pointer;
        }

        @media (max-width: 720px) {
            .application-form {
                grid-template-columns: 1fr;
            }

            .account-form .check-label {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 640px) {
            .application-form-card {
                padding: 18px 16px 20px;
            }

            .account-heading h1 {
                font-size: 2rem;
            }

            .review-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="account-page">
        <section class="account-card application-form-card" aria-labelledby="applicationTitle">
            <a class="account-brand" href="index.html" aria-label="Back to Cooper Fox Realty home"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a>
            <div class="application-hero-image">
                <img src="cupertino1.webp" alt="Cooper Fox Realty available home" />
                <div><span>Cooper Fox Realty</span><strong>Your next home starts here.</strong></div>
            </div>
            <div class="account-heading">
                <p class="account-kicker">Rental application</p>
                <h1 id="applicationTitle"><?php echo $success ? 'Application received' : 'Apply for this home'; ?></h1>
                <p><?php echo applicationValue($success ? $message : ($property ? $property . ($city ? ', ' . $city : '') . ($price ? ' · ' . $price : '') : 'Choose a property from the available listings.')); ?></p>
            </div>
            <?php if (!$success) : ?><div class="form-progress" aria-label="Application steps"><span class="active">01 Your details</span><span>02 Review</span><span>03 Cooper Fox response</span></div><?php endif; ?>
            <?php if (!$success) : ?>
                <form id="applicationForm" class="account-form application-form" method="POST">
                    <input type="hidden" name="Property" value="<?php echo applicationValue($property); ?>" />
                    <?php if ($application) : ?><input type="hidden" name="ApplicationId" value="<?php echo applicationValue((string) ($_GET['id'] ?? '')); ?>" /><?php endif; ?>
                    <input type="hidden" name="City" value="<?php echo applicationValue($city); ?>" />
                    <input type="hidden" name="Price" value="<?php echo applicationValue($price); ?>" />
                    <input type="hidden" name="Stage" value="conversation" />
                    <?php if ($message) : ?><p class="account-status"><?php echo applicationValue($message); ?></p><?php endif; ?>
                    <label for="applicationName">Full name</label>
                    <input id="applicationName" type="text" name="Name" value="<?php echo applicationValue($defaultName); ?>" autocomplete="name" required />
                    <label for="applicationEmail">Email address</label>
                    <input id="applicationEmail" type="email" name="Email" value="<?php echo applicationValue($defaultEmail); ?>" autocomplete="email" required />
                    <label for="applicationPhone">Phone number</label>
                    <input id="applicationPhone" type="tel" name="Phone" autocomplete="tel" required />
                    <label for="applicationAddress">Current address</label>
                    <textarea id="applicationAddress" name="Address" rows="2" autocomplete="street-address" placeholder="Street, city, state, ZIP"></textarea>
                    <label for="moveDate">Preferred move-in date</label>
                    <input id="moveDate" type="date" name="MoveDate" required />
                    <label for="employment">Employment or income source</label>
                    <input id="employment" type="text" name="Employment" placeholder="Employer, self-employed, student, etc." required />
                    <label for="income">Approximate monthly income</label>
                    <input id="income" type="text" name="Income" placeholder="$6,000" required />
                    <label for="occupants">Number of occupants</label>
                    <input id="occupants" type="number" name="Occupants" min="1" max="20" required />
                    <label for="pets">Pets</label>
                    <select id="pets" name="Pets"><option value="No">No</option><option value="Yes">Yes, I have a pet</option></select>
                    <label for="creditScore">Credit score</label>
                    <input id="creditScore" type="number" name="CreditScore" min="300" max="850" placeholder="650" />
                    <label for="monthlySalary">Monthly salary</label>
                    <input id="monthlySalary" type="text" name="MonthlySalary" placeholder="$6,000" />
                    <input type="hidden" name="FeePaymentMethod" value="Discuss directly in Cooper Fox chat" />
                    <p class="form-help">The refundable $70 application fee will be discussed directly in your Cooper Fox chat after you submit. Do not enter payment details on this form.</p>
                    <label for="availableFunds">Funds available to secure the property if approved (USD)</label>
                    <input id="availableFunds" type="number" name="AvailableFunds" min="0" step="50" placeholder="2500" />
                    <label for="applicationMessage">Message</label>
                    <textarea id="applicationMessage" name="Message" rows="4" placeholder="Tell us a little about your move."></textarea>
                    <label class="check-label" for="agreeToTerms"><input id="agreeToTerms" type="checkbox" name="AgreeToTerms" value="yes" required /> I confirm my information is complete and accurate, understand this is not a lease, and agree that Cooper Fox Realty may request more information.</label>
                    <button id="nextToReview" class="account-submit" type="button">Next: Review</button>
                    <div id="reviewStep" hidden>
                        <div class="review-summary" id="reviewSummary"></div>
                        <div class="review-actions">
                            <button id="backToDetails" type="button" class="secondary-button">Back</button>
                            <button class="account-submit" type="submit">Submit your application</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
            <p class="account-footer">No account required to apply. <a href="index.html#listings">Return to available homes</a></p>
        </section>
    </main>
    <script>
        const applicationForm = document.getElementById('applicationForm');
        const progress = document.querySelector('.form-progress');
        const nextToReview = document.getElementById('nextToReview');
        const backToDetails = document.getElementById('backToDetails');
        const reviewStep = document.getElementById('reviewStep');
        const reviewSummary = document.getElementById('reviewSummary');
        if (applicationForm && progress && nextToReview && reviewStep && reviewSummary) {
            const requiredFields = [...applicationForm.querySelectorAll('[required]')];
            const progressText = progress.querySelector('.active');
            const detailElements = [...applicationForm.children].filter((element) => element !== nextToReview && element !== reviewStep && !(element.tagName === 'INPUT' && element.type === 'hidden'));
            const setStep = (review) => {
                detailElements.forEach((element) => { element.hidden = review; });
                nextToReview.hidden = review;
                reviewStep.hidden = !review;
                progressText.textContent = review ? '02 Review' : '01 Your details';
                progress.style.setProperty('--completion', review ? '72%' : '0%');
            };
            nextToReview.addEventListener('click', () => {
                if (!applicationForm.reportValidity()) return;
                const fields = requiredFields.map((field) => `${field.name}: ${field.value || (field.checked ? 'Yes' : 'Not provided')}`);
                reviewSummary.innerHTML = fields.map((field) => `<div class="review-row"><strong>${field}</strong></div>`).join('');
                setStep(true);
            });
            backToDetails.addEventListener('click', () => setStep(false));
            const updateProgress = () => {
                const completed = requiredFields.filter((field) => field.type === 'checkbox' ? field.checked : field.value.trim() !== '').length;
                const percentage = Math.round((completed / requiredFields.length) * 100);
                if (!reviewStep.hidden) return;
                progressText.textContent = `01 Your details · ${percentage}% complete`;
                progress.style.setProperty('--completion', `${percentage}%`);
            };
            requiredFields.forEach((field) => field.addEventListener('input', updateProgress));
            requiredFields.forEach((field) => field.addEventListener('change', updateProgress));
            applicationForm.addEventListener('submit', () => {
                const submitButton = reviewStep.querySelector('.account-submit[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Sending your application...';
            });
            updateProgress();
        }
    </script>
</body>
</html>
