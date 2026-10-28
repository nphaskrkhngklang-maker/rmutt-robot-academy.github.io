<?php
declare(strict_types=1);
class StudentController {
 public function login():void {
  $errors=[];$name='';$matches=[];
  if($_SERVER['REQUEST_METHOD']==='POST'){
   verify_csrf();$name=input('name');
   if(time()-(int)($_SESSION['student_attempt_at']??0)>900){$_SESSION['student_attempts']=0;$_SESSION['student_attempt_at']=time();}
   if(($_SESSION['student_attempts']??0)>=10){http_response_code(429);$errors[]='กรุณารอ 15 นาที แล้วลองใหม่';}
   else {
    $_SESSION['student_attempts']=($_SESSION['student_attempts']??0)+1;
    $matches=(new StudentAccess())->matches($name);
    $chosen=count($matches)===1?$matches[0]:null;
    if(count($matches)>1&&input('participant')!=='')foreach($matches as $candidate)if((string)$candidate['id']===input('participant'))$chosen=$candidate;
    if($chosen){session_regenerate_id(true);$_SESSION=['student_id'=>$chosen['id']];redirect('student');}
    if(!$matches)$errors[]='ไม่พบชื่อในรายชื่อนักเรียน กรุณาตรวจชื่อ–นามสกุล หรือติดต่อสตาฟ';
   }
  }
  render('student-login',['title'=>'เข้าสู่ระบบนักเรียน','user'=>null,'errors'=>$errors,'name'=>$name,'matches'=>$matches]);
 }
 public function index():void {
  $q=db()->prepare("SELECT p.id,p.name,p.camp_id,s.name school_name,t.name team_name,g.name group_name FROM participants p JOIN schools s ON s.id=p.school_id JOIN camps c ON c.id=p.camp_id LEFT JOIN sub_teams t ON t.id=p.sub_team_id LEFT JOIN camp_groups g ON g.id=t.group_id WHERE p.id=? AND p.type='student' AND c.status<>'cancelled'");
  $q->execute([(int)($_SESSION['student_id']??0)]);$student=$q->fetch();
  if(!$student){unset($_SESSION['student_id']);redirect('student-login');}
  $q=db()->prepare('SELECT name,task_date,task_time FROM timeline_tasks WHERE camp_id=? AND phase=\'during\' ORDER BY task_date,task_time,id');$q->execute([$student['camp_id']]);$schedule=$q->fetchAll();
  render('student',['title'=>'พื้นที่นักเรียน','user'=>null,'student'=>$student,'schedule'=>$schedule]);
 }
 public function logout():void {verify_csrf();$_SESSION=[];session_regenerate_id(true);redirect('home');}
}
