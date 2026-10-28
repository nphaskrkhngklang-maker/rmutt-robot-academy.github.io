<?php
declare(strict_types=1);
class CampResetController {
    public function index(array $user):void {
        require_main($user);$camp=operation_camp($user);$service=new CampResetService();$errors=[];$selected=[];
        if($_SERVER['REQUEST_METHOD']==='POST'){
            verify_csrf();$selected=is_array($_POST['clear']??null)?array_values(array_filter($_POST['clear'],'is_string')):[];
            try {
                if(!$camp)throw new DomainException('ไม่พบข้อมูลค่าย');
                if(!isset($_SESSION['camp_reset_token'])||!hash_equals($_SESSION['camp_reset_token'],input('reset_token')))throw new DomainException('หน้านี้หมดอายุ กรุณาเปิดหน้าเริ่มรอบใหม่อีกครั้ง');
                $service->validate($selected);
                if(input('confirmation')!=='เริ่มรอบใหม่')throw new DomainException('กรุณาพิมพ์ เริ่มรอบใหม่ เพื่อยืนยันรายการที่เลือก');
                $account=(new User())->find((int)$user['id']);
                if(!$account||!password_verify(is_string($_POST['password']??null)?$_POST['password']:'',$account['password_hash']))throw new DomainException('รหัสผ่านไม่ถูกต้อง');
                $service->reset($user,(int)$camp['id'],$selected);unset($_SESSION['camp_reset_token']);flash('ล้างข้อมูลที่เลือกแล้ว ข้อมูลรายการอื่นยังอยู่ครบ');redirect('dashboard');
            }catch(DomainException $e){$errors[]=$e->getMessage();}
        }
        $_SESSION['camp_reset_token']??=bin2hex(random_bytes(32));
        render('camp-reset',['title'=>'เริ่มค่ายรอบใหม่','user'=>$user,'camp'=>$camp,'counts'=>$camp?$service->counts((int)$camp['id']):[],'errors'=>$errors,'selected'=>$selected]);
    }
}
