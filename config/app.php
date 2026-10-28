<?php
declare(strict_types=1);

$local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
return array_replace([
    'db_host' => getenv('HANDS_ON_DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('HANDS_ON_DB_PORT') ?: '3306',
    'db_name' => getenv('HANDS_ON_DB_NAME') ?: 'hands_on',
    'db_user' => getenv('HANDS_ON_DB_USER') ?: 'root',
    'db_password' => getenv('HANDS_ON_DB_PASSWORD') ?: '',
    'session_path' => getenv('HANDS_ON_SESSION_PATH') ?: '',
], $local);
