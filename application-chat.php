<?php
declare(strict_types=1);

session_start();

$id = trim((string) ($_GET['id'] ?? $_POST['Id'] ?? ''));
$file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'applications.json';
$applications = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
$applications = is_array($applications) ? $applications : [];
$applicationIndex = null;
foreach ($applications as $index => $item) {
    if (($item['id'] ?? '') === $id) {
        $applicationIndex = $index;
        break;
    }
}

function chatValue(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatHumanMessage(string $value): string
{
    $clean = trim($value);
    if ($clean === '') {
        return 'No additional details were provided.';
    }

    $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $clean) ?? $clean;
    $clean = preg_replace('/<[^>]+>/', ' ', $clean) ?? $clean;
    $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;
    $clean = trim($clean);

    if ($clean === '') {
        return 'No additional details were provided.';
    }

    if (stripos($clean, 'chatway') !== false || stripos($clean, 'widget.js') !== false) {
        return 'The applicant requested live chat support for this application.';
    }

    return $clean;
}

function normalizeMessages($messages): array
{
    if (!is_array($messages)) {
        return [];
    }

    $normalized = [];
    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }

        $from = (string) ($message['from'] ?? 'agent');
        $text = formatHumanMessage((string) ($message['text'] ?? ''));

        $normalized[] = [
            'from' => $from,
            'text' => $text,
            'sent_at' => $message['sent_at'] ?? gmdate('c'),
        ];
    }

    return $normalized;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $applicationIndex !== null) {
    $text = trim((string) ($_POST['Message'] ?? ''));
    if ($text !== '') {
        $cleanText = formatHumanMessage($text);
        $applications[$applicationIndex]['chat_messages'][] = ['from' => 'applicant', 'text' => $cleanText, 'sent_at' => gmdate('c')];
        $applications[$applicationIndex]['chat_messages'][] = ['from' => 'cooper-fox', 'text' => 'Your message is in the Cooper Fox queue. We will reply here with payment or application instructions.', 'sent_at' => gmdate('c')];
        file_put_contents($file, json_encode($applications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if (($_GET['ajax'] ?? '') === '1' || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'messages' => normalizeMessages($applications[$applicationIndex]['chat_messages'])]);
            exit;
        }
        header('Location: application-chat.php?id=' . rawurlencode($id));
        exit;
    }
}

$application = $applicationIndex === null ? null : $applications[$applicationIndex];
$messages = normalizeMessages($application['chat_messages'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $application !== null, 'messages' => $messages]);
    exit;
}
$feeStatus = (string) ($application['fee_status'] ?? 'Payment instructions in chat');
$applicantMessage = formatHumanMessage((string) ($application['message'] ?? ''));
$statusCards = [
    ['field' => 'application_status', 'label' => 'Application approval', 'value' => (string) ($application['application_status'] ?? 'Awaiting review'), 'icon' => '01'],
    ['field' => 'fee_status', 'label' => 'Application fee', 'value' => $feeStatus, 'icon' => '02'],
    ['field' => 'deposit_status', 'label' => 'Security deposit', 'value' => (string) ($application['deposit_status'] ?? 'Not requested'), 'icon' => '03'],
    ['field' => 'rent_status', 'label' => 'Rent fee', 'value' => (string) ($application['rent_status'] ?? 'Not requested'), 'icon' => '04'],
];

function statusUpdatedLabel(array $application, string $field): string
{
    $updatedAt = $application['status_updated_at'][$field] ?? '';
    if ($updatedAt === '') {
        return 'Updated by Cooper Fox Realty';
    }

    $timestamp = strtotime((string) $updatedAt);
    return $timestamp ? 'Updated ' . gmdate('M j, Y g:i A', $timestamp) . ' UTC' : 'Updated by Cooper Fox Realty';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Application <?php echo chatValue($id); ?> | Cooper Fox Realty</title>
    <link rel="stylesheet" href="cooper-fox-stylesheet.css" />
    <style>
        body:has(.application-dashboard) { background:#eef1ed; }
        .application-dashboard { width:min(1180px,calc(100% - 36px)); max-width:none; margin:28px auto 56px; padding:0; border:0; border-radius:0; background:transparent; box-shadow:none; }
        .application-dashboard .account-heading { margin-bottom:24px; }
        .application-dashboard .account-heading h1 { color:#17352f; font-family:Georgia,'Times New Roman',serif; font-size:clamp(2.35rem,5vw,4.8rem); font-weight:500; line-height:.98; }
        .application-dashboard .account-heading p:last-child { color:#60736d; line-height:1.65; }
        .application-summary,.tenant-status-card,.portal-box { border:1px solid #d9e0da; border-radius:14px; background:#fffdf8; box-shadow:0 12px 28px rgba(30,55,45,.06); }
        .application-summary { padding:20px; margin-bottom:24px; }
        .tenant-status-grid { grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:24px; }
        .tenant-status-card { min-height:142px; padding:17px; }
        .status-card-number { display:grid; place-items:center; width:28px; height:28px; margin-bottom:16px; border-radius:50%; background:#173d35; color:#fff; font-size:.64rem; font-weight:800; }
        .status-card-label,.status-card-note { display:block; color:#687873; font-size:.72rem; }
        .status-card-note { margin-top:10px; color:#9aaba5; font-size:.66rem; }
        .tenant-status-card strong { display:block; margin-top:6px; color:#173d35; font-size:.9rem; }
        .portal-columns { display:grid; grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr); gap:24px; align-items:start; }
        .portal-box { overflow:hidden; }
        .portal-box-heading { padding:20px 22px; border-bottom:1px solid #e4e9e4; }
        .portal-box-heading h2 { margin:0; color:#173d35; font-size:1.12rem; }
        .portal-box-heading p { margin:5px 0 0; color:#71817c; font-size:.8rem; }
        .portal-next { margin:0; padding:22px; background:#173d35; color:#fff; }
        .portal-next small { color:#d9bd83; font-size:.67rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .portal-next h3 { margin:8px 0 7px; font-family:Georgia,'Times New Roman',serif; font-size:1.45rem; font-weight:500; }
        .portal-next p { margin:0 0 16px; color:rgba(255,255,255,.72); font-size:.82rem; line-height:1.5; }
        .portal-next a { display:inline-flex; min-height:42px; align-items:center; padding:0 16px; border-radius:8px; background:#d9bd83; color:#17352f; text-decoration:none; font-size:.78rem; font-weight:800; }
        .application-chat-log { max-height:440px; padding:20px 22px; overflow:auto; background:#f5f7f3; }
        .chat-message { max-width:82%; margin-bottom:12px; padding:12px 14px; border-radius:12px 12px 12px 3px; background:#fff; box-shadow:0 5px 14px rgba(30,55,45,.05); }
        .chat-message.applicant { margin-left:auto; border-radius:12px 12px 3px 12px; background:#dfeee3; }
        .chat-message strong { display:block; margin-bottom:4px; color:#173d35; font-size:.7rem; }
        .chat-message p { margin:0; color:#30463f; font-size:.83rem; line-height:1.5; }
        .application-dashboard .chat-form { display:grid; gap:10px; padding:18px 22px 22px; }
        .application-dashboard .chat-form textarea { width:100%; min-height:90px; padding:12px; border:1px solid #d4ded7; border-radius:9px; font:inherit; }
        .application-dashboard .chat-form .account-submit { width:auto; justify-self:end; min-height:42px; padding:0 17px; border-radius:8px; font-size:.78rem; }
        @media (max-width:760px) { .application-dashboard { width:calc(100% - 24px); margin-top:18px; } .tenant-status-grid,.portal-columns { grid-template-columns:1fr 1fr; } .portal-columns { grid-template-columns:1fr; } .application-dashboard .account-heading h1 { font-size:clamp(2.5rem,13vw,4rem); } }
    </style>
</head>
<body>
    <?php $simpleChat = isset($_GET['simple']) && $_GET['simple'] == '1'; ?>
    <?php if ($simpleChat) : ?>
        <main class="account-page">
            <section class="account-card application-dashboard" aria-labelledby="chatTitle">
                <a class="account-brand" href="index.html"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a>

                <div class="account-heading">
                    <p class="account-kicker">Application received</p>
                    <h1 id="chatTitle">We have received your rental application and it is in review.</h1>
                    <p>Kindly use the chat with landlord button or text (346) 316-3488 for the next step.</p>
                </div>

                <div class="account-status" style="margin-bottom:16px;">
                    <strong>Reference:</strong> <?php echo chatValue($id); ?>
                </div>

                <button id="chatWithLandlord" type="button" class="account-submit" style="max-width: 340px; margin: 0 auto 8px; display:block; border-radius: 999px; font-size: 1rem; background: linear-gradient(135deg, #102722 0%, #1d4d46 100%); box-shadow: 0 10px 24px rgba(16,39,34,0.16);">Chat with the landlord</button>
                <a href="tel:+13463163488" style="display:block; text-align:center; color:#d8bd84; text-decoration:none; margin-top:8px; font-weight:700;">Text (346) 316-3488</a>
            </section>
        </main>
    <?php else : ?>
        <main class="account-page">
            <section class="account-card application-dashboard" aria-labelledby="chatTitle">
                <a class="account-brand" href="index.html"><span class="account-mark">CF</span><span>Cooper Fox Realty</span></a>

                <div class="account-heading">
                    <p class="account-kicker">Your Cooper Fox application ? <?php echo chatValue($id); ?></p>
                    <h1 id="chatTitle">Your application dashboard</h1>
                    <p>We will guide you through the payment steps and application updates in plain language.</p>
                </div>

                <?php if ($application) : ?>
                    <div class="application-summary">
                        <strong><?php echo chatValue((string) ($application['property'] ?? 'Property')); ?></strong>
                        <span><?php echo chatValue((string) ($application['city'] ?? '')); ?> ? <?php echo chatValue((string) ($application['price'] ?? '')); ?></span>
                    </div>

                    <div class="tenant-status-grid">
                        <?php foreach ($statusCards as $card) : ?>
                            <div class="tenant-status-card">
                                <span class="status-card-number"><?php echo chatValue((string) $card['icon']); ?></span>
                                <span class="status-card-label"><?php echo chatValue((string) $card['label']); ?></span>
                                <strong><?php echo chatValue((string) $card['value']); ?></strong>
                                <span class="status-card-note"><?php echo chatValue(statusUpdatedLabel($application, (string) ($card['field'] ?? ''))); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="portal-columns">
                        <div class="portal-box">
                            <div class="portal-box-heading"><h2>Next step</h2><p>Keep your application moving with one clear action.</p></div>
                            <div class="portal-next"><small>Before approval</small><h3>Discuss the $70 application fee</h3><p>Message Cooper Fox for current payment instructions. Never enter payment details here.</p><a id="openPaymentChat" href="application-chat.php?id=<?php echo rawurlencode($id); ?>">Open application chat</a></div>
                        </div>
                        <div class="portal-box">
                            <div class="portal-box-heading"><h2>Conversation</h2><p>Keep your application history together.</p></div>

                    <?php if ($feeStatus === 'Paid') : ?>
                        <div class="account-status">Cooper Fox confirmed your fee. <a href="apply.php?id=<?php echo rawurlencode($id); ?>">Complete the full application form</a>.</div>
                    <?php else : ?>
                        <div class="account-status">Message Cooper Fox here to receive payment instructions. The full application unlocks after Cooper Fox confirms payment.</div>
                    <?php endif; ?>

                    <?php if ($applicantMessage !== 'No additional details were provided.') : ?>
                        <div class="account-status" style="background:rgba(10,28,25,0.08); border-color: rgba(132, 169, 155, 0.35); color:#123b33;">
                            <strong>Your note:</strong> <?php echo chatValue($applicantMessage); ?>
                        </div>
                    <?php endif; ?>

                    <div class="application-chat-log" aria-live="polite">
                        <div class="chat-message agent">
                            <strong>Cooper Fox</strong>
                            <p>Your request is received. We will guide you through the next steps for your application.</p>
                        </div>

                        <?php foreach ($messages as $message) : ?>
                            <div class="chat-message <?php echo ($message['from'] ?? '') === 'applicant' ? 'applicant' : 'agent'; ?>">
                                <strong><?php echo (($message['from'] ?? '') === 'applicant') ? 'You' : 'Cooper Fox'; ?></strong>
                                <p><?php echo chatValue((string) ($message['text'] ?? '')); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" class="chat-form" id="applicationChatForm">
                        <input type="hidden" name="Id" value="<?php echo chatValue($id); ?>" />
                        <label for="chatMessage">Chat with Cooper Fox</label>
                        <textarea id="chatMessage" name="Message" rows="3" placeholder="Ask for payment instructions or an update..."></textarea>
                        <button class="account-submit" type="submit">Send message</button>
                    </form>
                        </div>
                    </div>
                <?php else : ?>
                    <p class="account-status">We could not find that application.</p>
                <?php endif; ?>
            </section>
        </main>
    <?php endif; ?>
    <script>
        const propertyName = <?php echo json_encode((string) ($application['property'] ?? 'this property')); ?>;
        const propertyCity = <?php echo json_encode((string) ($application['city'] ?? '')); ?>;
        const propertyPrice = <?php echo json_encode((string) ($application['price'] ?? '')); ?>;
        const applicationReference = <?php echo json_encode((string) ($application['id'] ?? $id)); ?>;
        const applicationContext = <?php echo json_encode($application ? 'I am applying for ' . (string) ($application['property'] ?? 'this property') . (isset($application['city']) && $application['city'] !== '' ? ' in ' . (string) $application['city'] : '') . (isset($application['price']) && $application['price'] !== '' ? ' for ' . (string) $application['price'] : '') . '. My rental application reference is ' . (string) ($application['id'] ?? $id) . '. I would like to continue with the next step. The application fee is $70 and is refundable.' : 'I am applying for a Cooper Fox property. My rental application reference is ' . (string) $id . '. I would like to continue with the next step. The application fee is $70 and is refundable.'); ?>;

        const persistChatwayContext = (message) => {
            try {
                sessionStorage.setItem('chatway_property_context', message);
                localStorage.setItem('chatway_property_context', message);
            } catch (error) {
                console.warn('Chatway context storage failed:', error);
            }
        };

        persistChatwayContext(applicationContext);

        const launchChatway = () => {
            const candidates = [
                window.$chatway,
                window.chatway,
                window.Chatway,
                window.chatwayWidget,
                window.chatwayApi,
                window.ChatwayWidget,
                typeof window.chatway === 'function' ? window.chatway : null
            ];

            for (const candidate of candidates) {
                if (!candidate) continue;

                const methodNames = ['openChatwayWidget', 'openChatWidget', 'openWidget', 'openChat', 'open'];
                for (const methodName of methodNames) {
                    const method = typeof candidate === 'object' ? candidate[methodName] : null;
                    if (typeof method === 'function') {
                        try {
                            method.call(candidate);
                            return true;
                        } catch (error) {
                            console.warn('Chatway open failed:', error);
                        }
                    }
                }

                if (typeof candidate === 'function') {
                    try {
                        candidate();
                        return true;
                    } catch (error) {
                        console.warn('Chatway function failed:', error);
                    }
                }
            }

            const showButton = [...document.querySelectorAll('button, [role="button"]')].find((node) => {
                const text = (node.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                return text.includes('show chatway messenger') || text.includes('show chat');
            });

            if (showButton) {
                showButton.click();
                return true;
            }

            const chatFrame = document.querySelector('iframe[src*="chatway"], iframe[src*="cdn.chatway"]');
            if (chatFrame) {
                chatFrame.style.display = 'block';
                chatFrame.style.visibility = 'visible';
                chatFrame.style.opacity = '1';
                return true;
            }

            return false;
        };

        const openPosterChat = () => {
            const runtimeContext = `I am applying for ${propertyName}${propertyCity ? ' in ' + propertyCity : ''}${propertyPrice ? ' for ' + propertyPrice : ''}. My rental application reference is ${applicationReference}. I would like to continue with the next step. The application fee is $70 and is refundable.`;
            persistChatwayContext(runtimeContext);
            window.location.href = `application-chat.php?id=${encodeURIComponent(applicationReference)}`;
        };

        document.getElementById('openPaymentChat')?.addEventListener('click', openPosterChat);
        document.getElementById('chatWithLandlord')?.addEventListener('click', openPosterChat);

        const applicationChatForm = document.getElementById('applicationChatForm');
        const applicationChatLog = document.querySelector('.application-chat-log');
        const renderApplicationMessages = (messages) => {
            if (!applicationChatLog || !Array.isArray(messages)) return;
            applicationChatLog.innerHTML = messages.map((message) => {
                const sender = message.from === 'applicant' ? 'You' : 'Cooper Fox';
                const className = message.from === 'applicant' ? 'applicant' : 'agent';
                const text = String(message.text || '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
                return `<div class="chat-message ${className}"><strong>${sender}</strong><p>${text}</p></div>`;
            }).join('');
            applicationChatLog.scrollTop = applicationChatLog.scrollHeight;
        };

        const refreshApplicationMessages = async () => {
            if (!applicationChatLog || !applicationReference) return;
            try {
                const response = await fetch(`application-chat.php?id=${encodeURIComponent(applicationReference)}&format=json`, { cache: 'no-store' });
                const data = await response.json();
                if (data.success) renderApplicationMessages(data.messages);
            } catch (error) {
                console.warn('Unable to refresh application messages:', error);
            }
        };

        applicationChatForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const input = applicationChatForm.querySelector('textarea[name="Message"]');
            const submit = applicationChatForm.querySelector('button[type="submit"]');
            if (!input || !input.value.trim() || !submit) return;
            const originalLabel = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'Sending...';
            try {
                const response = await fetch(`application-chat.php?id=${encodeURIComponent(applicationReference)}&ajax=1`, { method: 'POST', body: new FormData(applicationChatForm) });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error('Message could not be sent.');
                input.value = '';
                renderApplicationMessages(data.messages);
            } catch (error) {
                window.alert('Your message could not be sent. Please try again.');
            } finally {
                submit.disabled = false;
                submit.textContent = originalLabel;
            }
        });

        refreshApplicationMessages();
        window.setInterval(refreshApplicationMessages, 4000);

        const autoOpenChatway = () => {
            const tryOpen = () => {
                if (launchChatway()) {
                    return true;
                }
                return false;
            };

            if (tryOpen()) return;
            const chatwayWait = window.setInterval(() => {
                if (tryOpen()) {
                    window.clearInterval(chatwayWait);
                }
            }, 250);
            window.setTimeout(() => window.clearInterval(chatwayWait), 15000);
        };

        if (window.location.search.includes('simple=1')) {
            window.setTimeout(autoOpenChatway, 350);
        } else if (!launchChatway()) {
            autoOpenChatway();
        }
    </script>
    <script id="chatway" async="true" src="https://cdn.chatway.app/widget.js?id=6JgHgXhdfRSk"></script>
</body>
</html>
