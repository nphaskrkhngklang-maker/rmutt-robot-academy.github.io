<?php
declare(strict_types=1);

class CampController
{
    public function index(array $user): void { redirect($user['role']==='main_staff'?'camp-settings':'dashboard'); }
    public function show(array $user): void {
        $camp = (new Camp())->findFor(record_id($_GET['id'] ?? null), $user);
        if (!$camp) abort_request(404, 'ไม่พบค่าย', 'ไม่พบค่ายนี้ หรือคุณยังไม่ได้รับสิทธิ์เข้าถึง');
        render('camp-detail', ['title' => $camp['name'], 'user' => $user, 'camp' => $camp]);
    }
    public function edit(array $user): void {
        require_main($user);
        $id = isset($_GET['id']) ? record_id($_GET['id']) : null;
        if (!$id) abort_request(404, 'ไม่มีหน้าสร้างค่าย', 'ระบบนี้ใช้ค่ายเดียว สามารถแก้ไขข้อมูลค่ายเดิมได้จากตั้งค่าค่าย');
        $data = $id ? (new Camp())->findFor($id, $user) : ['name' => '', 'start_date' => '', 'end_date' => '', 'status' => 'draft', 'description' => ''];
        if (!$data) abort_request(404, 'ไม่พบค่าย', 'ไม่พบข้อมูลค่ายที่ต้องการแก้ไข');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf(); $data = ['name' => input('name'), 'start_date' => input('start_date'), 'end_date' => input('end_date'), 'status' => input('status'), 'description' => input('description')];
            if ($data['name'] === '' || mb_strlen($data['name']) > 200) $errors[] = 'ระบุชื่อค่ายไม่เกิน 200 ตัวอักษร';
            foreach (['start_date', 'end_date'] as $field) {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data[$field]);
                if (!$date || $date->format('Y-m-d') !== $data[$field] || $data[$field] < '1000-01-01') $errors[] = 'ระบุวันที่เริ่มและสิ้นสุดให้ถูกต้อง';
            }
            if ($data['end_date'] < $data['start_date']) $errors[] = 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่ม';
            if (!array_key_exists($data['status'], statuses())) $errors[] = 'สถานะค่ายไม่ถูกต้อง';
            if (mb_strlen($data['description']) > 10000) $errors[] = 'รายละเอียดต้องไม่เกิน 10,000 ตัวอักษร';
            if (!$errors) { $id = (new Camp())->save($id, $data, (int) $user['id']); flash('บันทึกข้อมูลค่ายแล้ว'); redirect('camp', ['id' => $id]); }
        }
        render('camp-form', ['title' => $id ? 'แก้ไขค่าย' : 'สร้างค่าย', 'user' => $user, 'data' => $data, 'errors' => $errors, 'id' => $id]);
    }
}
