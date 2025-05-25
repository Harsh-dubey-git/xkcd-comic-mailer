<?php
require_once 'connect.php';
require_once 'functions.php';

$message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (isset($_POST['send_code'])) {
        $code = generateVerificationCode();

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update code if exists
            $update = $conn->prepare("UPDATE subscribers SET verification_code = ?, is_verified = 0 WHERE email = ?");
            $update->bind_param("ss", $code, $email);
            $update->execute();
        } else {
            // Insert new record
            $insert = $conn->prepare("INSERT INTO subscribers (email, verification_code) VALUES (?, ?)");
            $insert->bind_param("ss", $email, $code);
            $insert->execute();
        }

        sendVerificationEmail($email, $code);
        $message = "Verification code sent to $email.";
        $step = 2;
    }

    elseif (isset($_POST['verify_code'])) {
        $code = trim($_POST['verification_code'] ?? '');

        // Check if code matches
        $stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ? AND verification_code = ?");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Mark as verified
            $update = $conn->prepare("UPDATE subscribers SET is_verified = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
            $message = "✅ Email verified and registered successfully.";
        } else {
            $message = "❌ Invalid verification code.";
            $step = 2;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>XKCD Subscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="subscription-card">
        <h1>XKCD Subscription</h1>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit" name="send_code">Send Verification Code</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                <input type="text" name="verification_code" maxlength="6" placeholder="Enter verification code" required>
                <button type="submit" name="verify_code">Verify & Subscribe</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
