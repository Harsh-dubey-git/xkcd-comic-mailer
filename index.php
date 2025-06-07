<?php
require_once 'connect.php';
require_once 'functions.php';

if (!file_exists(VERIFY_CODES_FILE)) {
    file_put_contents(VERIFY_CODES_FILE, '{}');
}

$message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (isset($_POST['send_code'])) {
        $code = generateVerificationCode();
        $hashedCode = hashVerificationCode($code);

        // Store verification code in a temporary file
        $pending = json_decode(file_get_contents(VERIFY_CODES_FILE) ?: '{}', true);
        $pending[$email] = $hashedCode;
        file_put_contents(VERIFY_CODES_FILE, json_encode($pending));

        sendVerificationEmail($email, $code);
        $message = "Verification code sent to $email.";
        $step = 2;
    }

    elseif (isset($_POST['verify_code'])) {
        $code = trim($_POST['verification_code'] ?? '');
        $salt = $_ENV['HASH_SALT'] ?? 'xkcd_secure_salt_2024';
        $hashedCode = hash('sha256', $code . $salt);
        
        // Check verification code from temporary file
        $pending = json_decode(file_get_contents(VERIFY_CODES_FILE) ?: '{}', true);
        
        if (isset($pending[$email]) && hash_equals($pending[$email], $hashedCode)) {
            // Store in database with verification code after successful verification
            $stmt = $conn->prepare("INSERT INTO subscribers (email, verification_code, is_verified) VALUES (?, ?, 1)");
            $stmt->bind_param("ss", $email, $hashedCode);
            $stmt->execute();
            
            // Remove from pending verifications
            unset($pending[$email]);
            file_put_contents(VERIFY_CODES_FILE, json_encode($pending));
            
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
    <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        comic: ['cursive','Bangers',]
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#facc15', // comic yellow
                            dark: '#fbbf24',
                        },
                        panel: '#fff',
                        border: '#222',
                        accent: '#ef4444', // red
                        blue: '#3b82f6',
                    },
                    boxShadow: {
                        comic: '8px 8px 0 0 #222',
                    },
                    backgroundImage: {
                        halftone: "radial-gradient(circle at 20% 20%, #facc15 1px, transparent 1px), radial-gradient(circle at 80% 80%, #facc15 1px, transparent 1px)",
                    },
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #facc15;
            background-image: repeating-radial-gradient(circle at 0 0, #fcd34d, #facc15 20px), repeating-radial-gradient(circle at 100% 100%, #fde68a, #facc15 30px);
            background-size: 60px 60px, 80px 80px;
        }
        .comic-border {
            border: 4px solid #222;
            box-shadow: 6px px 0 0 #222;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col  items-center relative overflow-x-hidden">
    <!-- Notification Toast -->
    <div id="notification" class="fixed top-6 right-6 z-50 hidden bg-white p-4 rounded-lg shadow-lg border-2 border-accent font-comic text-lg transform transition-all duration-300 ease-in-out"></div>
    <!-- Comic-style Navbar -->
    <nav class="w-full flex items-center justify-between px-8 py-4 bg-white/90 comic-border font-comic text-2xl tracking-wider uppercase z-10 relative">
        <div class="flex items-center gap-3">
            <img src="images\logo.png" alt="Comic Icon" class="w-12 h-13" />
            <span class="font-comic text-3xl text-accent drop-shadow">XKCD Comics</span>
        </div>
        <div class="flex justify-end z-50">
        <button id="sendComicsBtn" class="bg-accent text-white font-comic text-lg px-6 py-3 rounded-lg comic-border shadow-comic hover:bg-blue-500 hover:text-yellow-100 transition flex items-center gap-2 min-w-[120px] disabled:bg-gray-400 disabled:cursor-not-allowed disabled:shadow-none">
            <span class="send-comics-text">Send Comics to All Subscribers</span>
            <div class="send-comics-spinner hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin-slow"></div>
        </button>
    </div>
    </nav>

    <!-- Comic Panel Main Card -->
    <div class="comic-border bg-panel max-w-4xl w-full max-h-screen mt-4 p-0 flex flex-col md:flex-row items-center relative overflow-hidden shadow-comic">
        <!-- Comic Character -->
        <div class="flex-1 flex flex-col items-center justify-center p-8">
            <img src="images\comic.png" alt="Comic Character" class="w-60 h-65 drop-shadow-2xl" />
        </div>
        <!-- Comic Speech Bubble/Panel -->
        <div class="flex-1 p-8 flex flex-col items-center justify-center">
            <h1 class="font-comic text-4xl md:text-5xl text-accent mb-2 drop-shadow-lg tracking-widest">New Comic Launching!</h1>
            <p class="font-comic text-xl text-blue-600 mb-2">Subscribe for daily XKCD comics!</p>
            <p class="text-gray-700 font-semibold mb-4">Get the latest XKCD comic delivered to your inbox every day. Enter your email to join the fun!</p>
            <?php if ($step === 1): ?>
                <form method="POST" class="w-full flex flex-col gap-3 items-center">
                    <input type="email" name="email" placeholder="Enter your email" required
                        class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 transition comic-border">
                    <button type="submit" name="send_code"
                        class="relative w-full bg-accent text-white font-comic text-xl py-3 rounded-lg comic-border shadow-comic hover:bg-blue-500 hover:text-yellow-100 transition flex items-center justify-center gap-2">
                        <span class="verify-text">Send Verification Code</span>
                        <div class="verify-spinner hidden w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin-slow"></div>
                    </button>
                </form>
                <div id="sendComicsMessage" class="mt-4 p-3 rounded-lg font-comic"></div>
            <?php else: ?>
                <form method="POST" class="w-full flex flex-col gap-3 items-center">
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                        class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 transition comic-border">
                    <input type="text" name="verification_code" maxlength="6" placeholder="Enter verification code" required
                        class="w-full px-4 py-3 rounded-lg border-2 border-accent font-comic text-lg focus:ring-2 focus:ring-blue-400 transition comic-border">
                    <button type="submit" name="verify_code"
                        class="relative w-full bg-accent text-white font-comic text-xl py-3 rounded-lg comic-border shadow-comic hover:bg-blue-500 hover:text-yellow-100 transition flex items-center justify-center gap-2">
                        <span class="verify-text">Verify & Subscribe</span>
                        <div class="verify-spinner hidden w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin-slow"></div>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Dark/Light Theme Toggle
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }

        // Load saved theme
        (function () {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();

        // AJAX Send Comics Spinner
        document.querySelectorAll('#sendComicsBtn').forEach(function(btn) {
            btn.addEventListener('click', async function () {
                const messageDiv = document.getElementById('sendComicsMessage');
                const button = this;
                const spinner = button.querySelector('.send-comics-spinner');
                const text = button.querySelector('.send-comics-text');
                try {
                    button.disabled = true;
                    if (spinner) spinner.classList.remove('hidden');
                    if (text) text.classList.add('opacity-50');
                    if (messageDiv) messageDiv.textContent = '';
                    const response = await fetch('send_comics.php', { method: 'POST' });
                    const result = await response.json();
                    if (messageDiv) {
                        messageDiv.textContent = result.message;
                        messageDiv.className = 'mt-4 p-3 rounded-lg font-comic ' + 
                            (result.success ? 'bg-green-100 border-2 border-green-400 text-green-800' : 'bg-red-100 border-2 border-red-400 text-red-800');
                    }
                } catch (error) {
                    if (messageDiv) {
                        messageDiv.textContent = 'Error: ' + error.message;
                        messageDiv.className = 'mt-4 p-3 rounded-lg font-comic bg-red-100 border-2 border-red-400 text-red-800';
                    }
                } finally {
                    button.disabled = false;
                    if (spinner) spinner.classList.add('hidden');
                    if (text) text.classList.remove('opacity-50');
                }
            });
        });
    </script>
    <?php if ($message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            notification.textContent = <?= json_encode($message) ?>;
            notification.className = "fixed top-6 right-6 z-50 bg-white p-4 rounded-lg shadow-lg border-2 border-accent font-comic text-lg transform transition-all duration-300 ease-in-out";
            notification.style.display = "block";
            
            // Add fade-out animation
            setTimeout(() => {
                notification.style.opacity = "0";
                notification.style.transform = "translateY(-20px)";
                setTimeout(() => {
                    notification.style.display = "none";
                    notification.style.opacity = "1";
                    notification.style.transform = "translateY(0)";
                }, 300);
            }, 3000);
        });
    </script>
    <?php endif; ?>
</body>
</html>
