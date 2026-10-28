<?php
declare(strict_types=1);
class ControlCenter {
 public function rows(string $sql,array $args=[]):array {$q=db()->prepare($sql);$q->execute($args);return $q->fetchAll();}
 public function scores(int $camp):array {
  $totals=[];$overall=(new RankingService())->overall($camp);
  foreach($overall['rows']['sub_team']??[] as $r)$totals[(int)substr($r['key'],9)]=$r['total'];
  $rows=$this->rows('SELECT t.id,t.name,g.name group_name,COALESCE(a.adjustment,0) adjustment FROM sub_teams t JOIN camp_groups g ON g.id=t.group_id LEFT JOIN (SELECT team_id,SUM(amount) adjustment FROM score_adjustments WHERE camp_id=? GROUP BY team_id) a ON a.team_id=t.id WHERE t.camp_id=? ORDER BY g.name,t.name',[$camp,$camp]);
  foreach($rows as &$r){$r['total']=$totals[(int)$r['id']]??(int)$r['adjustment'];$r['base']=$r['total']-(int)$r['adjustment'];}unset($r);
  return (new RankingService())->ordered($rows);
 }
 public function mutate(array $user,int $camp,string $page,array $d):void {
  require_main($user);$action=$d['action']??'';$id=filter_var($d['id']??0,FILTER_VALIDATE_INT)?:0;
  $text=fn($k,$max)=>is_string($d[$k]??null)?trim($d[$k]):'';
  $pdo=db();$pdo->beginTransaction();try {
   $q=$pdo->prepare('SELECT id FROM camps WHERE id=? FOR UPDATE');$q->execute([$camp]);
   if($page==='arenas'){
    if($action==='save'){
     $name=$text('name',100);$no=filter_var($d['field_no']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
     if(!$no||$name===''||mb_strlen($name)>100)throw new DomainException('ระบุชื่อสนามและหมายเลข 1–100');
     if($id){$old=$this->rows('SELECT * FROM arenas WHERE id=? AND camp_id=?',[$id,$camp]);if(!$old)throw new DomainException('ไม่พบสนาม');if((int)$old[0]['field_no']!==$no)throw new DomainException('แก้ชื่อสนามได้ แต่หมายเลขสนามต้องคงเดิมเพื่อรักษาตารางแข่งขัน');$pdo->prepare('UPDATE arenas SET name=? WHERE id=? AND camp_id=?')->execute([$name,$id,$camp]);}
     else $pdo->prepare('INSERT INTO arenas(camp_id,field_no,name) VALUES (?,?,?)')->execute([$camp,$no,$name]);
    }elseif($action==='delete'){
     $a=$this->rows('SELECT * FROM arenas WHERE id=? AND camp_id=?',[$id,$camp]);if(!$a)throw new DomainException('ไม่พบสนาม');
     if($this->rows('SELECT m.id FROM competition_matches m JOIN competition_rounds r ON r.id=m.round_id WHERE r.camp_id=? AND m.field_id=? LIMIT 1',[$camp,$a[0]['field_no']]))throw new DomainException('สนามนี้มีแมตช์อยู่ จึงลบไม่ได้');
     $pdo->prepare('DELETE FROM arenas WHERE id=? AND camp_id=?')->execute([$id,$camp]);
    }else throw new DomainException('คำสั่งไม่ถูกต้อง');
   }elseif($page==='scoreboard'){
    $team=filter_var($d['team_id']??null,FILTER_VALIDATE_INT);$amount=$d['amount']??'';$reason=$text('reason',300);
    if(!is_string($amount)||!preg_match('/^-?\d{1,5}(?:\.\d{1,2})?$/D',$amount)||$reason===''||mb_strlen($reason)>300)throw new DomainException('ระบุคะแนนเพิ่ม/ลดและเหตุผล ไม่เกินทศนิยม 2 ตำแหน่ง');
    if(!$this->rows('SELECT id FROM sub_teams WHERE id=? AND camp_id=?',[$team,$camp]))throw new DomainException('ไม่พบทีม');
    $value=(int)round((float)$amount*100);if(!$value)throw new DomainException('คะแนนต้องไม่เป็นศูนย์');
    $submission=$d['submission']??'';if(!is_string($submission)||!hash_equals($_SESSION['adjustment_token']??'', $submission))throw new DomainException('บันทึกไปแล้ว กรุณาเปิดหน้าใหม่');
    $pdo->prepare('INSERT INTO score_adjustments(camp_id,team_id,amount,reason,actor_id) VALUES (?,?,?,?,?)')->execute([$camp,$team,$value,$reason,$user['id']]);unset($_SESSION['adjustment_token']);
   }elseif($page==='displays'){
    if($action==='create'){$name=$text('name',100);if($name===''||mb_strlen($name)>100)throw new DomainException('ระบุชื่อจอไม่เกิน 100 ตัวอักษร');$pdo->prepare('INSERT INTO live_displays(camp_id,name,token) VALUES (?,?,?)')->execute([$camp,$name,bin2hex(random_bytes(32))]);}
    else {if(!$this->rows('SELECT id FROM live_displays WHERE id=? AND camp_id=?',[$id,$camp]))throw new DomainException('ไม่พบจอ');
     if($action==='delete')$pdo->prepare('DELETE FROM live_displays WHERE id=? AND camp_id=?')->execute([$id,$camp]);
     elseif($action==='rotate')$pdo->prepare('UPDATE live_displays SET token=? WHERE id=?')->execute([bin2hex(random_bytes(32)),$id]);
     elseif($action==='mode'&&in_array($d['mode']??'', ['idle','scores','matches'],true))$pdo->prepare('UPDATE live_displays SET mode=? WHERE id=?')->execute([$d['mode'],$id]);
     else throw new DomainException('คำสั่งไม่ถูกต้อง');
    }
   }
   $pdo->commit();
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
}
