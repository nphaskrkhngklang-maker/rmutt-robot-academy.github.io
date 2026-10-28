<?php
declare(strict_types=1);

class StaffController
{
    public function index(array $user): void { require_main($user); render('staff', ['title' => 'บัญชีสตาฟ', 'user' => $user, 'staff' => (new User())->all(), 'passwordRequests'=>db()->query('SELECT user_id,requested_at FROM password_help_requests')->fetchAll(PDO::FETCH_KEY_PAIR)]); }
    public function edit(array $user): void {
        require_main($user); $id = isset($_GET['id']) ? record_id($_GET['id']) : null;
        $data = $id ? (new User())->find($id) : ['name' => '', 'email' => '', 'role' => 'staff', 'is_active' => 1];
        if (!$data) abort_request(404, 'ไม่พบบัญชี', 'ไม่พบบัญชีสตาฟที่ต้องการแก้ไข');
        $camps = (new Camp())->allFor($user); $campIds = $id ? (new User())->campIds($id) : []; $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf(); $studentId=$data['student_id']??null; $data = ['student_id'=>$studentId, 'name' => input('name'), 'email' => strtolower(input('email')), 'role' => input('role'), 'is_active' => input('is_active') === '1' ? 1 : 0];
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $rawCampIds = $_POST['camp_ids'] ?? [];
            $campIds = is_array($rawCampIds) ? array_values(array_unique(array_filter($rawCampIds, fn($v) => is_string($v) && ctype_digit($v)))) : [];
            $campIds = array_map('intval', $campIds);
            if (!is_array($rawCampIds) || count($campIds) !== count($rawCampIds)) $errors[] = 'ข้อมูลค่ายไม่ถูกต้อง';
            if ($data['name'] === '' || mb_strlen($data['name']) > 150) $errors[] = 'ระบุชื่อสตาฟไม่เกิน 150 ตัวอักษร';
            if ((($data['email']!=='' || empty($data['student_id'])) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) || strlen($data['email']) > 190) $errors[] = 'ระบุอีเมลให้ถูกต้อง';
            if (!in_array($data['role'], ['main_staff', 'staff'], true)) $errors[] = 'บทบาทไม่ถูกต้อง';
            if ((!$id || $password !== '') && (strlen($password) < 12 || strlen($password) > 72)) $errors[] = 'รหัสผ่านต้องมีความยาว 12–72 ไบต์ (แนะนำอักษรอังกฤษ ตัวเลข และสัญลักษณ์)';
            if (array_diff($campIds, array_map('intval', array_column($camps, 'id')))) $errors[] = 'พบค่ายที่ไม่มีอยู่ในระบบ';
            if (!$errors) {
                try {
                    (new AccountService())->save($id, $data, $password, $campIds);
                    if ($id === (int) $user['id']) {
                        // Keep this session only if the edited account remains active; other sessions are invalidated.
                        if (!$data['is_active']) { unset($_SESSION['user_id']); redirect('login'); }
                        $_SESSION['auth_version'] = (new User())->find($id)['auth_version'];
                        flash('บันทึกบัญชีแล้ว'); redirect($data['role'] === 'main_staff' ? 'staff' : 'dashboard');
                    }
                    flash('บันทึกบัญชีสตาฟแล้ว'); redirect('staff');
                } catch (DomainException $e) { $errors[] = $e->getMessage(); }
                catch (PDOException $e) { if (($e->errorInfo[1] ?? 0) === 1062) $errors[] = 'อีเมลนี้มีบัญชีอยู่แล้ว'; else throw $e; }
            }
        }
        render('staff-form', ['title' => $id ? 'แก้ไขบัญชีสตาฟ' : 'เพิ่มบัญชีสตาฟ', 'user' => $user, 'data' => $data, 'errors' => $errors, 'camps' => $camps, 'campIds' => $campIds, 'id' => $id]);
    }
}
