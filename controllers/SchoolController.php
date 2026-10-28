<?php
declare(strict_types=1);

class SchoolController
{
    public function index(array $user): void {
        $camp = selected_camp($user);
        render('schools',['title'=>'โรงเรียนและการลงทะเบียน','user'=>$user,'camp'=>$camp,'camps'=>(new Camp())->allFor($user),'schools'=>$camp?(new School())->all((int)$camp['id']):[]]);
    }
    public function edit(array $user): void {
        require_main($user);
        $id = isset($_GET['id']) ? record_id($_GET['id']) : null;
        $school = $id ? (new School())->find($id) : false;
        if ($id && !$school) abort_request(404,'ไม่พบโรงเรียน','ไม่พบโรงเรียนที่ต้องการแก้ไข');
        $camp = $school ? (new Camp())->findFor((int)$school['camp_id'],$user) : selected_camp($user);
        if (!$camp) redirect('camps');
        $data = ['name'=>$school['name'] ?? '']; $errors = [];
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            verify_csrf(); $data['name'] = input('name');
            if ($data['name']==='' || mb_strlen($data['name'])>200) $errors[] = 'ระบุชื่อโรงเรียนไม่เกิน 200 ตัวอักษร';
            if (!$errors) {
                try {
                    if ($id) (new School())->rename($id,$data['name']);
                    else $id = (new RegistrationTokenService())->createSchool((int)$camp['id'],$data['name']);
                    flash('บันทึกโรงเรียนแล้ว'); redirect('school',['id'=>$id]);
                } catch (PDOException $e) { if (($e->errorInfo[1]??0)===1062) $errors[] = 'ค่ายนี้มีชื่อโรงเรียนนี้แล้ว'; else throw $e; }
            }
        }
        render('school-form',['title'=>$id?'แก้ไขโรงเรียน':'เพิ่มโรงเรียน','user'=>$user,'camp'=>$camp,'data'=>$data,'errors'=>$errors]);
    }
    public function show(array $user): void {
        require_main($user); $school = (new School())->find(record_id($_GET['id']??null));
        if (!$school) abort_request(404,'ไม่พบโรงเรียน','ไม่พบข้อมูลโรงเรียนนี้');
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            verify_csrf(); $action = input('action');
            if (!in_array($action,['activate','deactivate','rotate'],true)) abort_request(400,'คำสั่งไม่ถูกต้อง','กรุณาลองใหม่');
            (new RegistrationTokenService())->manage((int)$school['id'],$action);
            flash(match($action) {'activate'=>'เปิดลิงก์ลงทะเบียนแล้ว','deactivate'=>'ปิดลิงก์ลงทะเบียนแล้ว','rotate'=>'ออกลิงก์ใหม่แล้ว ลิงก์เก่าถูกยกเลิก ข้อมูลผู้เข้าร่วมยังอยู่ครบ'});
            redirect('school',['id'=>$school['id']]);
        }
        $rows = (new Participant())->forSchool((int)$school['id']);
        render('school-detail',['title'=>$school['name'],'user'=>$user,'school'=>$school,'rows'=>$rows,'summary'=>Participant::summary($rows),'token'=>(new RegistrationTokenService())->current((int)$school['id'])]);
    }
}
