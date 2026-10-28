<?php
declare(strict_types=1);

class RegistrationController
{
    public function success(): void {
        header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
        $token = is_string($_GET['token']??null) ? $_GET['token'] : '';
        $school = (new RegistrationTokenService())->resolve($token);
        if (!$school) abort_request(404,'ลิงก์ลงทะเบียนใช้ไม่ได้','ลิงก์อาจถูกปิดหรือออกใหม่แล้ว กรุณาติดต่อสตาฟหลัก');
        if ($school['registration_status']!=='submitted') redirect('register',['token'=>$token]);
        $summary = Participant::summary((new Participant())->forSchool((int)$school['id']));
        $publicContent = 'registration-success';
        require __DIR__ . '/../views/registration-public.php';
    }
    public function index(?array $user = null): void {
        $public = $user===null; $token = null;
        if ($public) {
            header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
            $token = is_string($_GET['token']??null) ? $_GET['token'] : '';
            $school = (new RegistrationTokenService())->resolve($token);
            if (!$school) abort_request(404,'ลิงก์ลงทะเบียนใช้ไม่ได้','ลิงก์อาจถูกปิดหรือออกใหม่แล้ว กรุณาติดต่อสตาฟหลักเพื่อรับลิงก์ปัจจุบัน');
        } else {
            require_main($user); $school = (new School())->find(record_id($_GET['id']??null));
            if (!$school) abort_request(404,'ไม่พบโรงเรียน','ไม่พบข้อมูลโรงเรียนที่ต้องการ');
        }
        $rows = (new Participant())->forSchool((int)$school['id']);
        $version = (string)$school['registration_version']; $errors = [];
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            verify_csrf(); $action = input('action'); $version = input('version');
            if (!in_array($action,['draft','submit','add_student','add_teacher'],true)) abort_request(400,'คำสั่งไม่ถูกต้อง','เลือกบันทึกร่างหรือส่งข้อมูล');
            [$rows,$errors] = (new RegistrationService())->validate($_POST['participants']??[],$action==='submit');
            if (input('form_complete')!=='1') $errors[] = 'ข้อมูลที่ส่งมาไม่ครบ กรุณาลดจำนวนรายการหรือให้ผู้ดูแลตรวจขนาดแบบฟอร์ม';
            if (in_array($action,['add_student','add_teacher'],true)) {
                if (count($rows)<RegistrationService::MAX_ROWS) $rows[] = ['id'=>'','type'=>$action==='add_student'?'student':'teacher','name'=>'','shirt_size'=>''];
                else $errors[] = 'รองรับผู้เข้าร่วมสูงสุด 150 คนต่อโรงเรียน';
            } elseif (!$errors) {
                try {
                    (new RegistrationService())->save((int)$school['id'],$token,$rows,$version,$action==='submit');
                    if ($public) {
                        if ($action==='submit') {
                            unset($_SESSION['registration_notice']);
                            redirect('registration-success',['token'=>$token]);
                        }
                        $_SESSION['registration_notice'] = ['school_id'=>$school['id'],'message'=>'บันทึกร่างแล้ว คุณกลับมากรอกต่อผ่านลิงก์นี้ได้'];
                        redirect('register',['token'=>$token]);
                    }
                    flash('บันทึกข้อมูลผู้เข้าร่วมแล้ว'); redirect('school',['id'=>$school['id']]);
                } catch (DomainException $e) { $errors[] = $e->getMessage(); }
            }
        }
        if (!$rows) $rows = [['id'=>'','type'=>'student','name'=>'','shirt_size'=>''],['id'=>'','type'=>'teacher','name'=>'','shirt_size'=>'']];
        $notice = null;
        if ($public && ($_SESSION['registration_notice']['school_id']??null)===$school['id']) {
            $notice = $_SESSION['registration_notice']['message']; unset($_SESSION['registration_notice']);
        }
        $data = ['title'=>'ลงทะเบียนผู้เข้าร่วม','user'=>$user,'school'=>$school,'rows'=>$rows,'version'=>$version,'errors'=>$errors,'public'=>$public,'notice'=>$notice,'summary'=>Participant::summary($rows)];
        if ($public) { extract($data,EXTR_SKIP); require __DIR__ . '/../views/registration-public.php'; }
        else render('registration-form',$data);
    }
}
