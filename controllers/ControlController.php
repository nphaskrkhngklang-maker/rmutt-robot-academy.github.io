<?php
declare(strict_types=1);
class ControlController {
 public function index(array $user,string $page):void {
  $camp=operation_camp($user);if(!$camp)abort_request(400,'ยังไม่มีค่าย','กรุณาตั้งค่าค่ายก่อน');$repo=new ControlCenter();$errors=[];
  if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{$repo->mutate($user,(int)$camp['id'],$page,$_POST);flash('บันทึกแล้ว');redirect($page);}catch(DomainException $e){$errors[]=$e->getMessage();}catch(PDOException $e){if(($e->errorInfo[1]??0)===1062)$errors[]='หมายเลขสนามนี้มีอยู่แล้ว';else throw $e;}}
  $_SESSION['adjustment_token']??=bin2hex(random_bytes(24));
  $rows=match($page){'arenas'=>$repo->rows('SELECT a.*, (SELECT COUNT(*) FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=a.camp_id AND m.field_id=a.field_no) match_count FROM arenas a WHERE camp_id=? ORDER BY field_no',[$camp['id']]),'scoreboard'=>$repo->scores((int)$camp['id']),'displays'=>$repo->rows('SELECT * FROM live_displays WHERE camp_id=? ORDER BY id',[$camp['id']]),default=>[]};
  $history=$page==='scoreboard'?$repo->rows('SELECT a.*,t.name,u.name actor FROM score_adjustments a JOIN sub_teams t ON t.id=a.team_id JOIN users u ON u.id=a.actor_id WHERE a.camp_id=? ORDER BY a.id DESC LIMIT 50',[$camp['id']]):[];
  render('control-center',['title'=>['arenas'=>'จัดการสนามแข่งขัน','scoreboard'=>'ตารางคะแนน','displays'=>'จอแสดงผล'][$page],'user'=>$user,'camp'=>$camp,'page'=>$page,'rows'=>$rows,'history'=>$history,'errors'=>$errors]);
 }
 public function display():void {
  $token=is_string($_GET['token']??null)?$_GET['token']:'';
  if(!preg_match('/^[a-f0-9]{64}$/D',$token))abort_request(404,'ไม่พบจอ','ลิงก์จอไม่ถูกต้อง');
  $repo=new ControlCenter();$found=$repo->rows('SELECT d.*,c.name camp_name FROM live_displays d JOIN camps c ON c.id=d.camp_id WHERE token=?',[$token]);if(!$found)abort_request(404,'ไม่พบจอ','ลิงก์จอถูกเปลี่ยนหรือลบแล้ว');$display=$found[0];
  $rows=$display['mode']==='scores'?$repo->scores((int)$display['camp_id']):$repo->rows("SELECT g.name,m.queue_no,m.field_id,m.status,GROUP_CONCAT(e.name_snapshot ORDER BY e.id SEPARATOR ' / ') teams FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id JOIN games g ON g.id=m.game_id LEFT JOIN competition_entries e ON e.match_id=m.id WHERE r.camp_id=? AND m.status<>'finished' GROUP BY m.id ORDER BY (m.status='playing') DESC,m.queue_no,m.field_id LIMIT 20",[$display['camp_id']]);
  require __DIR__.'/../views/live-display.php';
 }
}
