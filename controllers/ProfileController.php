<?php
declare(strict_types=1);
class ProfileController {
 public function setup():void {
  $account=(new User())->find((int)($_SESSION['setup_user_id']??0));
  if(!$account||!$account['is_active']||!$account['needs_setup']||(int)$account['auth_version']!==(int)($_SESSION['setup_version']??0)||time()-(int)($_SESSION['setup_at']??0)>1800){unset($_SESSION['setup_user_id']);redirect('login');}
  $errors=[];$data=['name'=>$account['name'],'email'=>$account['email']??'','phone'=>$account['phone']??''];$setup=true;
  if($_SERVER['REQUEST_METHOD']==='POST'){
   verify_csrf();$data=['name'=>input('name'),'email'=>input('email'),'phone'=>input('phone')];
   try{$saved=(new ProfileService())->save($account,$_POST,true);session_regenerate_id(true);$_SESSION=['user_id'=>$saved['id'],'auth_version'=>$saved['auth_version']];flash('ตั้งค่าโปรไฟล์แล้ว ยินดีต้อนรับ');redirect('dashboard');}catch(DomainException $e){$errors[]=$e->getMessage();}
  }
  render('profile-setup',['title'=>'ตั้งค่าโปรไฟล์ครั้งแรก','user'=>null,'account'=>$account,'data'=>$data,'errors'=>$errors]);
 }
 public function index(array $user):void {
  $errors=[];$data=['name'=>$user['name'],'email'=>$user['email']??'','phone'=>$user['phone']??''];
  if($_SERVER['REQUEST_METHOD']==='POST'){
   verify_csrf();$data=['name'=>input('name'),'email'=>input('email'),'phone'=>input('phone')];
   try{$saved=(new ProfileService())->save($user,$_POST);session_regenerate_id(true);$_SESSION['auth_version']=$saved['auth_version'];flash('บันทึกโปรไฟล์แล้ว');redirect('profile');}
   catch(DomainException $e){$errors[]=$e->getMessage();}
  }
  render('profile',['title'=>'โปรไฟล์ของฉัน','user'=>$user,'data'=>$data,'errors'=>$errors]);
 }
}
