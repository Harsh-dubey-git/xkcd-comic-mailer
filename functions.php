
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$gmailUser = $_ENV['GMAIL_USER'];
$gmailPass = $_ENV['GMAIL_PASS'];

define('VERIFY_CODES_FILE', __DIR__ . '/pending_codes.json');
define('UNSUBSCRIBE_CODES_FILE', __DIR__ . '/unsubscribe_codes.json');
define('REGISTERED_EMAILS_FILE', __DIR__ . '/registered_emails.txt');

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function registerEmail($email) {
    $file = REGISTERED_EMAILS_FILE;
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    if (!in_array($email, $emails)) {
        file_put_contents($file, $email . PHP_EOL, FILE_APPEND);
    }
}

// function unsubscribeEmail($email) {
//     $file = REGISTERED_EMAILS_FILE;
//     if (!file_exists($file)) return;
//     $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//     $filtered = array_filter($emails, fn($e) => strtolower($e) !== strtolower($email));
//     file_put_contents($file, implode(PHP_EOL, $filtered) . (count($filtered) ? PHP_EOL : ''));
// }

function unsubscribeEmail($email) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM subscribers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

function sendVerificationEmail($recipientEmail, $code) {
    global $gmailUser, $gmailPass;

    $subject = "Your Verification Code";
    $message = "<p>Your verification code is: <strong>{$code}</strong></p>";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailUser; 
        $mail->Password = $gmailPass; 
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom($gmailUser, 'XKCD Bot');
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


function verifyCode($email, $code, $filePath = VERIFY_CODES_FILE) {
    if (!file_exists($filePath)) return false;
    $data = json_decode(file_get_contents($filePath), true);
    return isset($data[$email]) && $data[$email] === $code;
}

function fetchAndFormatXKCDData() {
    $latest_json = file_get_contents('https://xkcd.com/info.0.json');
    if (!$latest_json) return false;
    $latest = json_decode($latest_json, true);
    $latest_num = $latest['num'];

    $random_num = rand(1, $latest_num);
    $comic_json = file_get_contents("https://xkcd.com/$random_num/info.0.json");
    if (!$comic_json) return false;
    $comic = json_decode($comic_json, true);

    return [
        'img' => htmlspecialchars($comic['img']),
        'title' => htmlspecialchars($comic['title']),
        'alt' => htmlspecialchars($comic['alt'])
    ];
}

function sendXKCDUpdatesToSubscribers() {
    global $gmailUser, $gmailPass, $conn;

    // Step 1: Fetch all verified subscribers
    $stmt = $conn->prepare("SELECT email FROM subscribers WHERE is_verified = 1");
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }

    if (empty($emails)) return;

    // Step 2: Fetch random XKCD comic
    $comicData = fetchAndFormatXKCDData(); 
    if (!$comicData) return;

    // Step 3: Prepare email sending
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailUser;
        $mail->Password = $gmailPass;
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom($gmailUser, 'XKCD Bot');
        $mail->isHTML(true);
        $mail->Subject = "Your XKCD Comic";

        // Step 4: Loop through and send to each user
        foreach ($emails as $email) {
            $unsubscribe_url = 'http://localhost/xkcd_email_subscription/src/unsubscribe.php?email=' . urlencode($email);

            $comicHtml = '
                <div style="max-width:600px;margin:20px auto;padding:20px;border:1px solid #ddd;border-radius:10px;background:#ffffff;font-family:Arial,sans-serif;text-align:center;">
                    <h2 style="color:#333;">XKCD Comic: ' . $comicData['title'] . '</h2>
                    <img src="' . $comicData['img'] . '" alt="' . $comicData['alt'] . '" style="max-width:100%;height:auto;border-radius:8px;margin:20px 0;">
                    <p style="color:#666;font-size:14px;">' . $comicData['alt'] . '</p>
                    <a href="' . $unsubscribe_url . '" style="display:inline-block;margin-top:20px;padding:10px 20px;color:#fff;background-color:#e74c3c;text-decoration:none;border-radius:5px;font-size:14px;">Unsubscribe</a>
                </div>';

            $mail->clearAddresses();
            $mail->addAddress($email);
            $mail->Body = $comicHtml;
            $mail->send();
        }

    } catch (Exception $e) {
        error_log("Failed to send XKCD emails. Error: {$mail->ErrorInfo}");
    }
}
