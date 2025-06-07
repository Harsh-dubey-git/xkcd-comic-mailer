<?php
require_once 'connect.php';
require_once 'functions.php';

if (!file_exists(UNSUBSCRIBE_CODES_FILE)) {
    file_put_contents(UNSUBSCRIBE_CODES_FILE, '{}');
}

$message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (isset($_POST['send_code'])) {
        if ($email) {
            $code = generateVerificationCode();
            $hashedCode = hashVerificationCode($code);
            $pending = json_decode(file_get_contents(UNSUBSCRIBE_CODES_FILE), true);
            $pending[$email] = $hashedCode;
            file_put_contents(UNSUBSCRIBE_CODES_FILE, json_encode($pending));
            sendVerificationEmail($email, $code);
            $message = "📩 Unsubscribe verification code sent to <strong>$email</strong>.";
            $step = 2;
        } else {
            $message = "❌ Please enter a valid email.";
        }
    } elseif (isset($_POST['verify_code'])) {
        $code = trim($_POST['verification_code'] ?? '');
        if ($email && $code) {
            $salt = $_ENV['HASH_SALT'] ?? 'xkcd_secure_salt_2024';
            $hashedCode = hash('sha256', $code . $salt);
            $pending = json_decode(file_get_contents(UNSUBSCRIBE_CODES_FILE), true);

            if (isset($pending[$email]) && hash_equals($pending[$email], $hashedCode)) {
                unsubscribeEmail($email);
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe from XKCD</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        comic: ['cursive', 'Bangers'],
                    },
                    colors: {
                        primary: '#facc15',
                        panel: '#fff',
                        border: '#222',
                        accent: '#ef4444',
                        blue: '#3b82f6',
                    },
                    boxShadow: {
                        comic: '8px 8px 0 0 #222',
                    }
                }
            }
        };
    </script>
    <style>
        body {
            background-color: #facc15;
            background-image: repeating-radial-gradient(circle at 0 0, #fcd34d, #facc15 20px),
                              repeating-radial-gradient(circle at 100% 100%, #fde68a, #facc15 30px);
            background-size: 60px 60px, 80px 80px;
        }
        .comic-border {
            border: 4px solid #222;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-0">

    <!-- Navbar (logo only) -->
   <nav class="w-full flex items-center justify-between px-8 py-4 bg-white/90 comic-border font-comic text-2xl tracking-wider uppercase z-10 relative">
        <div class="flex items-center gap-3">
            <img src="images\logo.png" alt="Comic Icon" class="w-12 h-13" />
            <span class="font-comic text-3xl text-accent drop-shadow">XKCD Comics</span>
        </div>
        <div class="flex justify-end z-50">
    </div>
    </nav>

    <!-- Main Container -->
    <div class="bg-panel comic-border max-w-xl w-full p-8 rounded-xl shadow-comic text-center mt-10">
        <h1 class="font-comic text-4xl text-accent drop-shadow mb-4">Unsubscribe</h1>
        <p class="text-lg text-gray-700 font-semibold mb-6">We're sad to see you go! Enter your email to unsubscribe.</p>

        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg font-comic <?= stripos($message, '✅') !== false ? 'bg-green-100 border-2 border-green-400 text-green-800' : 'bg-red-100 border-2 border-red-400 text-red-800' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST" class="flex flex-col gap-4">
                <input type="email" name="email" placeholder="Enter your email" required
                       class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 comic-border">
                <button type="submit" name="send_code"
                        class="bg-accent text-white font-comic text-xl py-3 rounded-lg comic-border shadow-comic hover:bg-blue-500 hover:text-yellow-100 transition">
                    Send Verification Code
                </button>
            </form>
        <?php else: ?>
            <form method="POST" class="flex flex-col gap-4">
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                       class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 comic-border">
                <input type="text" name="verification_code" maxlength="6" placeholder="Enter verification code" required
                       class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 comic-border">
                <button type="submit" name="verify_code"
                        class="bg-accent text-white font-comic text-xl py-3 rounded-lg comic-border shadow-comic hover:bg-blue-500 hover:text-yellow-100 transition">
                    Verify & Unsubscribe
                </button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
