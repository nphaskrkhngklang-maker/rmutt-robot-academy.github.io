<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
spl_autoload_register(function (string $class): void {
    foreach (['models', 'services', 'controllers'] as $folder) {
        $file = __DIR__ . '/../' . $folder . '/' . basename($class) . '.php';
        if (is_file($file)) { require_once $file; return; }
    }
});
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operation failed. Check database configuration and server logs.\n"); exit(1); }
    $title = 'ระบบยังไม่พร้อมใช้งาน';
    $message = 'ไม่สามารถดำเนินการได้ในขณะนี้ กรุณาลองใหม่ หรือตรวจสอบการเชื่อมต่อฐานข้อมูลตาม README';
    require __DIR__ . '/../views/error.php';
});
if (PHP_SAPI !== 'cli') {
    $config = require __DIR__ . '/../config/app.php';
    if ($config['session_path']) { session_save_path($config['session_path']); }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('hands_on_session');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => '/']);
    session_start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}
