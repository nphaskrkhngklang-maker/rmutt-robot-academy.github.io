<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../core/bootstrap.php';
if ((int) db()->query("SELECT COUNT(*) FROM users WHERE role='main_staff' AND is_active=1")->fetchColumn() > 0) {
    fwrite(STDERR, "An active main staff account already exists. Use account management in the app.\n"); exit(1);
}
$name = trim(getenv('HANDS_ON_ADMIN_NAME') ?: '');
$email = strtolower(trim(getenv('HANDS_ON_ADMIN_EMAIL') ?: ''));
$password = getenv('HANDS_ON_ADMIN_PASSWORD') ?: '';
if ($name === '' || mb_strlen($name)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($password)<12 || strlen($password)>72) {
    fwrite(STDERR, "Set HANDS_ON_ADMIN_NAME, HANDS_ON_ADMIN_EMAIL and HANDS_ON_ADMIN_PASSWORD (12-72 bytes).\n"); exit(1);
}
(new AccountService())->save(null, ['name'=>$name,'email'=>$email,'role'=>'main_staff','is_active'=>1], $password, []);
echo "First main staff account created. No default credentials are installed.\n";
