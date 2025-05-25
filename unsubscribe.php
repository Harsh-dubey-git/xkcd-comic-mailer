<?php
require_once 'connect.php';
require_once 'functions.php';


if (!file_exists(UNSUBSCRIBE_CODES_FILE)) {
    file_put_contents(UNSUBSCRIBE_CODES_FILE, '{}');
}

$message = '';
$step = 1;  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (isset($_POST['send_code'])) {
        if ($email) {
            $code = generateVerificationCode();
            $pending = json_decode(file_get_contents(UNSUBSCRIBE_CODES_FILE), true);
            $pending[$email] = $code;
            file_put_contents(UNSUBSCRIBE_CODES_FILE, json_encode($pending));
            sendVerificationEmail($email, $code);
            $message = "📩 Unsubscribe verification code sent to <strong>$email</strong>.";
            $step = 2;
        } else {
            $message = "❌ Please enter a valid email.";
        }
    } elseif (isset($_POST['verify_code'])) {
        $code = isset($_POST['verification_code']) ? trim($_POST['verification_code']) : '';
        if ($email && $code) {
            if (verifyCode($email, $code, UNSUBSCRIBE_CODES_FILE)) {
                unsubscribeEmail($email);
                $pending = json_decode(file_get_contents(UNSUBSCRIBE_CODES_FILE), true);
                unset($pending[$email]);
                file_put_contents(UNSUBSCRIBE_CODES_FILE, json_encode($pending));
                $message = "✅ You have been unsubscribed successfully.";
                $step = 1;
                $email = '';
            } else {
                $message = "❌ Invalid verification code.";
                $step = 2;
            }
        } else {
            $message = "❗ Please enter both email and verification code.";
            $step = 2;
        }
    }
} else {
    $email = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe from XKCD</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
</head>
<body>
    <div class="container">
        <h1>Unsubscribe from XKCD Emails</h1>
        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST" class="form">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email">
                <button type="submit" name="send_code">Send Verification Code</button>
            </form>
        <?php else: ?>
            <form method="POST" class="form">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>

                <label for="verification_code">Verification Code:</label>
                <input type="text" name="verification_code" id="verification_code" maxlength="6" required placeholder="6-digit code">

                <button type="submit" name="verify_code">Verify</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
