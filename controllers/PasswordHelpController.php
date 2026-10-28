<?php
declare(strict_types=1);
class PasswordHelpController {
 public function index():void {
  $errors=[];$email='';$sent=!empty($_SESSION['password_help_sent']);unset($_SESSION['password_help_sent']);
  if($_SERVER['REQUEST_METHOD']==='POST'){
   verify_csrf();$email=strtolower(input('email'));
   if((!filter_var($email,FILTER_VALIDATE_EMAIL)&&!preg_match('/^[0-9]{12}-?[0-9]$/D',$email))||strlen($email)>190)$errors[]='กรุณาระบุรหัสนักศึกษาหรืออีเมลให้ถูกต้อง';
   if(!$errors){
    if(time()-(int)($_SESSION['password_help_at']??0)>=60){
     $account=(new User())->byLogin($email);
     if($account&&$account['is_active']){
      $q=db()->prepare('INSERT IGNORE INTO password_help_requests(user_id) VALUES (?)');$q->execute([$account['id']]);
     }
     $_SESSION['password_help_at']=time();
    }
    $_SESSION['password_help_sent']=true;redirect('forgot-password');
   }
  }
  render('forgot-password',['title'=>'ลืมรหัสผ่าน','user'=>null,'errors'=>$errors,'email'=>$email,'sent'=>$sent]);
 }
}
