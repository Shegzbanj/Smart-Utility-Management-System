<?php
// Meterly one-time local database setup. Delete this file after successful setup.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This setup page is available only on this computer.');
}
$message = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbPassword = $_POST['db_password'] ?? '';
    $host = 'localhost';
    $dbname = 'smart_utility_bill';
    $username = 'meterly_app';

    try {
        $test = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $dbPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $config = <<<'PHPFILE'
<?php
// Meterly shared security configuration.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$host = "localhost";
$dbname = "smart_utility_bill";
$username = "meterly_app";
$password = __DB_PASSWORD__;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database connection failed. Check your configuration.");
}
?>
PHPFILE;
        $quotedPassword = var_export($dbPassword, true);
        $config = str_replace('__DB_PASSWORD__', $quotedPassword, $config);

        $configDir = __DIR__ . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($configDir)) {
            throw new RuntimeException('The config folder was not found. Check that this setup file is in the smart-utility-bill project folder.');
        }
        $target = $configDir . DIRECTORY_SEPARATOR . 'db.php';
        if (file_exists($target)) {
            $backup = $target . '.backup-' . date('Ymd-His');
            if (!copy($target, $backup)) {
                throw new RuntimeException('Could not create a backup of the existing db.php file.');
            }
        }
        if (file_put_contents($target, $config) === false) {
            throw new RuntimeException('Could not write config/db.php. Check folder permissions.');
        }
        $success = true;
        $message = 'Database connection verified and config/db.php updated. Delete setup.php now, then test your Meterly login.';
    } catch (Throwable $e) {
        $message = 'Setup failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Meterly Database Setup</title>
<style>
body{font-family:Arial,sans-serif;background:#f2f6f2;margin:0;padding:32px;color:#17251e}
main{max-width:520px;margin:40px auto;background:white;padding:28px;border-radius:16px;box-shadow:0 8px 30px #0001}
h1{font-size:24px}p{line-height:1.5}input{box-sizing:border-box;width:100%;padding:13px;border:1px solid #ccd5ce;border-radius:8px;margin:10px 0 18px}
button{background:#287653;color:white;border:0;border-radius:8px;padding:13px 20px;font-weight:bold;cursor:pointer}
.notice{padding:12px;border-radius:8px;background:#eef5ef;margin:16px 0}
label{font-weight:bold}
</style>
</head>
<body><main>
<h1>Meterly Database Setup</h1>
<p>Enter the password you created for the MySQL user <b>meterly_app</b>. It is checked on this computer and saved into your local configuration file. Do not share it.</p>
<?php if ($message !== ''): ?>
<div class="notice"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!$success): ?>
<form method="post">
<label for="db_password">MySQL password for meterly_app</label>
<input id="db_password" name="db_password" type="password" required autocomplete="off">
<button type="submit">Test and Save Configuration</button>
</form>
<?php else: ?>
<p><b>Next:</b> Delete <code>setup.php</code> from your project folder.</p>
<?php endif; ?>
</main></body></html>
