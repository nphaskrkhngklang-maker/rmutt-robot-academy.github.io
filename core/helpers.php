<?php
declare(strict_types=1);

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $page = 'dashboard', array $params = []): string { return 'index.php?' . http_build_query(['page' => $page] + $params); }
function redirect(string $page, array $params = []): never { header('Location: ' . url($page, $params), true, 303); exit; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function verify_csrf(): void {
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(csrf(), $_POST['csrf'])) {
        abort_request(419, 'แบบฟอร์มหมดอายุ', 'กรุณาเปิดหน้าใหม่แล้วลองอีกครั้ง');
    }
}
function abort_request(int $code, string $title, string $message): never {
    http_response_code($code); require __DIR__ . '/../views/error.php'; exit;
}
function flash(string $message): void { $_SESSION['flash'] = $message; }
function input(string $name): string { return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : ''; }
function record_id(mixed $id): int {
    if (!is_scalar($id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        abort_request(404, 'ไม่พบข้อมูล', 'รายการที่ระบุไม่ถูกต้อง');
    }
    return (int) $id;
}
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $user = (new User())->find((int) $_SESSION['user_id']);
    if (!$user || !$user['is_active'] || (int) ($_SESSION['auth_version'] ?? 0) !== (int) $user['auth_version']) {
        unset($_SESSION['user_id'], $_SESSION['auth_version']); return null;
    }
    return $user;
}
function require_user(): array { $user = current_user(); if (!$user) redirect('login'); return $user; }
function require_main(array $user): void { if ($user['role'] !== 'main_staff') abort_request(403, 'ไม่มีสิทธิ์เข้าถึง', 'ส่วนนี้สำหรับสตาฟหลักเท่านั้น'); }
function render(string $view, array $viewVariables = []): void {
    // Keep the payload name separate from the form's $data variable.
    extract($viewVariables, EXTR_SKIP);
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../views/' . $view . '.php';
    require __DIR__ . '/../includes/footer.php';
}
function statuses(): array { return ['draft' => 'เตรียมค่าย', 'active' => 'กำลังดำเนินการ', 'completed' => 'จบค่าย', 'cancelled' => 'ยกเลิก']; }
function role_label(string $role): string { return $role === 'main_staff' ? 'สตาฟหลัก' : 'สตาฟ'; }
function shirt_sizes(): array { return ['XS','S','M','L','XL','2XL','3XL','4XL']; }
function registration_statuses(): array { return ['not_started'=>'ยังไม่เริ่ม','in_progress'=>'กำลังกรอก','submitted'=>'ส่งข้อมูลแล้ว']; }
function selected_camp(array $user): array|false {
    require_main($user);
    $camps = (new Camp())->allFor($user);
    if (isset($_GET['camp_id'])) {
        $camp = (new Camp())->findFor(record_id($_GET['camp_id']),$user);
        if (!$camp) abort_request(404,'ไม่พบค่าย','กรุณาเลือกค่ายที่มีอยู่ในระบบ');
        return $camp;
    }
    return $camps[0] ?? false;
}
function operation_camp(array $user): array|false {
    if (isset($_GET['camp_id'])) {
        $camp=(new Camp())->findFor(record_id($_GET['camp_id']),$user);
        if (!$camp) abort_request(403,'ไม่มีสิทธิ์เข้าถึงค่าย','กรุณาเลือกค่ายที่คุณรับผิดชอบ');
        return $camp;
    }
    return (new Camp())->allFor($user)[0]??false;
}
function equipment_categories(): array { return ['Board','Sensor','อุปกรณ์เสริม','สาย','Battery']; }
function equipment_statuses(): array { return ['not_checked'=>'ยังไม่ตรวจ','checking'=>'กำลังตรวจ','checked'=>'ตรวจแล้ว']; }
function task_statuses(): array { return ['not_done'=>'ยังไม่เสร็จ','done'=>'เสร็จแล้ว']; }
function task_phases(): array { return ['before'=>'ก่อนค่าย','during'=>'ระหว่างค่าย','after'=>'หลังค่าย']; }
function duty_types(): array { return ['group'=>'ดูแลทีมหลัก','team'=>'ดูแลทีมย่อย','field'=>'ประจำสนาม','general'=>'งานทั่วไป']; }

function delete_link(string $kind,int $id):string { return '<a class="text-link delete-link" href="'.e(url('delete',['kind'=>$kind,'id'=>$id])).'">ลบ</a>'; }

function current_student(): ?array {
    if(empty($_SESSION['student_id'])||current_user())return null;
    $q=db()->prepare("SELECT p.id,p.name,p.camp_id,s.name school_name FROM participants p JOIN schools s ON s.id=p.school_id JOIN camps c ON c.id=p.camp_id WHERE p.id=? AND p.type='student' AND c.status<>'cancelled'");
    $q->execute([(int)$_SESSION['student_id']]);$student=$q->fetch();
    if(!$student){unset($_SESSION['student_id']);return null;}
    return $student;
}
