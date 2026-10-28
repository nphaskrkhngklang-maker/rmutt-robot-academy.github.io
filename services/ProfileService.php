<?php
declare(strict_types=1);
class ProfileService {
 public function save(array $user,array $data,bool $setup=false):array {
  $name=is_string($data['name']??null)?trim($data['name']):'';
  $email=is_string($data['email']??null)?strtolower(trim($data['email'])):'';
  $phone=is_string($data['phone']??null)?trim($data['phone']):'';
  if($phone!==''&&!preg_match('/^[0-9+() .-]{6,30}$/D',$phone))throw new DomainException('กรุณาระบุเบอร์โทรให้ถูกต้อง');
  $password=is_string($data['new_password']??null)?$data['new_password']:'';
  $confirm=is_string($data['confirm_password']??null)?$data['confirm_password']:'';
  $current=is_string($data['current_password']??null)?$data['current_password']:'';
  if($name===''||mb_strlen($name)>150)throw new DomainException('กรุณาระบุชื่อไม่เกิน 150 ตัวอักษร');
  if(($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||strlen($email)>190||($email===''&&empty($user['student_id'])))throw new DomainException('กรุณาระบุอีเมลให้ถูกต้อง');
  if($password!==''&&(strlen($password)<12||strlen($password)>72))throw new DomainException('รหัสผ่านใหม่ต้องยาว 12–72 ไบต์ แนะนำอักษรอังกฤษ ตัวเลข และสัญลักษณ์');
  if($setup&&$password==='')throw new DomainException('กรุณาตั้งรหัสผ่านสำหรับเข้าระบบครั้งถัดไป');
  if($password!==$confirm)throw new DomainException('รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน');
  $pdo=db();$pdo->beginTransaction();try{
   $q=$pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']]);$row=$q->fetch();
   if(!$row||!$row['is_active']||(int)$row['auth_version']!==(int)$user['auth_version'])throw new DomainException('บัญชีมีการเปลี่ยนแปลง กรุณาเข้าสู่ระบบใหม่');
   if($setup&&(!$row['needs_setup']||empty($row['student_id'])))throw new DomainException('บัญชีนี้ตั้งรหัสผ่านแล้ว กรุณาเข้าสู่ระบบตามปกติ');

   $sensitive=$email!==($row['email']??'')||$password!=='';
   if(!$setup&&!$row['needs_setup']&&$sensitive&&!password_verify($current,$row['password_hash']))throw new DomainException('กรุณากรอกรหัสผ่านปัจจุบันให้ถูกต้อง');
   if($password!==''&&password_verify($password,$row['password_hash']))throw new DomainException('กรุณาใช้รหัสผ่านใหม่ที่ต่างจากเดิม');
   $q=$pdo->prepare('UPDATE users SET name=?,email=?,phone=?,password_hash=?,needs_setup=?,auth_version=auth_version+? WHERE id=?');
   $q->execute([$name,$email?:null,$phone,$password!==''?password_hash($password,PASSWORD_DEFAULT):$row['password_hash'],($row['needs_setup']&&$password==='')?1:0,$sensitive?1:0,$user['id']]);
   if($password!==''){ $q=$pdo->prepare('DELETE FROM password_help_requests WHERE user_id=?');$q->execute([$user['id']]); }
   $pdo->commit();return (new User())->find((int)$user['id']);
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($e instanceof PDOException&&($e->errorInfo[1]??0)===1062)throw new DomainException('อีเมลนี้มีบัญชีใช้งานแล้ว');throw $e;}
 }
}
