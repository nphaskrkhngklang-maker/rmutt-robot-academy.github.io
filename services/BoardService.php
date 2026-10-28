<?php
declare(strict_types=1);
class BoardService {
 public function save(int $camp,array $data,string $token,string $author):void {
  $text=[];foreach(['author_name'=>80,'title'=>150,'body'=>2000] as $key=>$max){$v=$data[$key]??'';if(!is_string($v)||mb_strlen(trim($v))>$max)throw new DomainException('ข้อความยาวเกินกำหนด');$text[$key]=trim($v);}
  if($text['title']===''||$text['body']==='')throw new DomainException('กรุณาใส่หัวข้อและข้อความ');
  $tone=$data['tone']??'blue';if(!is_string($tone)||!in_array($tone,['blue','yellow','green','pink'],true))throw new DomainException('สีการ์ดไม่ถูกต้อง');
  if(($data['public_consent']??'')!=='1')throw new DomainException('กรุณายืนยันว่าข้อความนี้เผยแพร่ให้ทุกคนที่เปิดบอร์ดอ่านได้');
  if(!preg_match('/^[a-f0-9]{64}$/D',$token)||!preg_match('/^[a-f0-9]{64}$/D',$author))throw new DomainException('กรุณาเปิดบอร์ดใหม่');
  $pdo=db();$pdo->beginTransaction();try{
   $q=$pdo->prepare("SELECT id FROM camps WHERE id=? AND status<>'cancelled' FOR UPDATE");$q->execute([$camp]);if(!$q->fetchColumn())throw new DomainException('บอร์ดนี้ยังไม่เปิด');
   $q=$pdo->prepare('SELECT id FROM board_posts WHERE submission_token=?');$q->execute([$token]);if($q->fetchColumn()){$pdo->commit();return;}
   $q=$pdo->prepare('SELECT COUNT(*) FROM board_posts WHERE author_key=? AND created_at>DATE_SUB(NOW(),INTERVAL 1 MINUTE)');$q->execute([$author]);if((int)$q->fetchColumn()>=3)throw new DomainException('โพสต์ต่อเนื่องหลายครั้ง กรุณารอสักครู่แล้วลองใหม่');
   $q=$pdo->prepare('INSERT INTO board_posts(camp_id,submission_token,author_key,author_name,title,body,tone) VALUES (?,?,?,?,?,?,?)');$q->execute([$camp,$token,$author,...array_values($text),$tone]);$pdo->commit();
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
}
