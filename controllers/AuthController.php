<?php
declare(strict_types=1);

class AuthController
{
    public function login(): void {
        if (current_user()) redirect('dashboard');
        $errors = []; $email = ''; $passwordStep=false; $adminMode=($_GET['mode']??'')==='admin';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf(); $email = strtolower(input('email'));
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            // Fixed time windows and a row lock prevent bypass through parallel login requests.
            $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . (str_contains($email,'@')?$email:str_replace(['-',' '],'',$email)));
            $pdo = db(); $pdo->beginTransaction();
            try {
                $q = $pdo->prepare('INSERT IGNORE INTO login_attempts (attempt_key, attempts, window_start) VALUES (?,0,UTC_TIMESTAMP())'); $q->execute([$key]);
                $q = $pdo->prepare('SELECT attempts, TIMESTAMPDIFF(SECOND,window_start,UTC_TIMESTAMP()) AS age FROM login_attempts WHERE attempt_key=? FOR UPDATE'); $q->execute([$key]); $attempt = $q->fetch();
                if ((int) $attempt['age'] >= 900) { $q = $pdo->prepare('UPDATE login_attempts SET attempts=0,window_start=UTC_TIMESTAMP() WHERE attempt_key=?'); $q->execute([$key]); $attempt['attempts'] = 0; }
                if ((int) $attempt['attempts'] >= 5) {
                    $pdo->commit(); http_response_code(429); $errors[] = 'ลองเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ 15 นาที';
                } else {
                    $user = (new User())->byLogin($email);
                    if($user && $user['is_active'] && !empty($user['student_id']) && !str_contains($email,'@')) {
                        $q=$pdo->prepare('DELETE FROM login_attempts WHERE attempt_key=?');$q->execute([$key]);$pdo->commit();session_regenerate_id(true);$_SESSION=['user_id'=>$user['id'],'auth_version'=>$user['auth_version']];redirect('dashboard');
                    }
                    if(input('step')==='identify' && $user && $user['is_active'] && !$user['needs_setup']) { $pdo->commit(); $passwordStep=true; } else {
                    $valid = password_verify($password, $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
                    if ($user && $valid && $user['is_active'] && !$user['needs_setup']) {
                        $q = $pdo->prepare('DELETE FROM login_attempts WHERE attempt_key=?'); $q->execute([$key]); $pdo->commit();
                        session_regenerate_id(true); $_SESSION = ['user_id' => $user['id'], 'auth_version' => $user['auth_version']];
                        redirect('dashboard');
                    }
                    $q = $pdo->prepare('UPDATE login_attempts SET attempts=attempts+1 WHERE attempt_key=?'); $q->execute([$key]); $pdo->commit();
                    $passwordStep=input('step')==='password';
                    $errors[] = str_contains($email,'@')?'อีเมลหรือรหัสผ่านไม่ถูกต้อง':'ไม่พบรหัสนักศึกษาที่เปิดใช้งาน กรุณาติดต่อสตาฟหลัก';
                    }
                }
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        }
        render('login', ['title' => 'เข้าสู่ระบบ', 'errors' => $errors, 'email' => $email, 'user' => null,'passwordStep'=>$passwordStep,'adminMode'=>$adminMode]);
    }
    public function logout(): never {
        verify_csrf(); $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time()-42000, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy(); redirect('home');
    }
}
